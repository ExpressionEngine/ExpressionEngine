<?php

namespace EllisLab\ExpressionEngine\Service\Model;

use BadMethodCallException;
use InvalidArgumentException;
use OverflowException;

use EllisLab\ExpressionEngine\Service\Model\DataStore;
use EllisLab\ExpressionEngine\Service\Model\Association\Association;


class Model {

	/**
	 *
	 */
	protected $_name;

	/**
	 *
	 */
	protected $_dirty = array();

	/**
	 *
	 */
	protected $_frontend = NULL;

	/**
	 *
	 */
	protected $_associations = array();

	/**
	 *
	 */
	protected $_relations = array();

	/**
	 * Constructor
	 */
	public function __construct(array $data = array())
	{
		foreach ($data as $key => $value)
		{
			$this->setProperty($key, $value);
		}
	}

	/**
	 *
	 */
	public function __get($name)
	{
		return $this->getProperty($name);
	}

	/**
	 *
	 */
	public function __set($name, $value)
	{
		$this->setProperty($name, $value);
		return $value;
	}

	/**
	 *
	 */
	public function __call($method, $args)
	{
		$actions = 'has|get|set|add|remove|create|delete|fill';

		if (preg_match("/^({$actions})(.+)/", $method, $matches))
		{
			list($_, $action, $relationship) = $matches;

			if ($this->hasAssociation($relationship))
			{
				$assoc = $this->getAssociation($relationship);
				$result = call_user_func_array(array($assoc, $action), $args);

				if ($action == 'has' || $action == 'get')
				{
					return $result;
				}

				return $this;
			}
		}

		throw new BadMethodCallException("Method not found: {$method}.");
	}

	/**
	 * Clean up var_dump output for developers on PHP 5.6+
	 */
	public function __debugInfo()
	{
		$name = $this->_name;
		$values = $this->getValues();
		$related_to = array_keys($this->_associations);

		return compact('name', 'values', 'related_to');
	}

	/**
	 * Metadata is static. If you need to access it, it's recommended
	 * to use the datastore's lazy caches, which will make for much
	 * more testable code and avoid iterating over gateways and creating
	 * relations repeatedly.
	 */
	public static function getMetaData($key)
	{
		$key = '_'.$key;
		return static::$$key;
	}

	/**
	 *
	 */
	public function getPrimaryKey()
	{
		return $this->getMetaData('primary_key');
	}

	/**
	 *
	 */
	public function setName($name)
	{
		if (isset($this->_name))
		{
			throw new OverflowException('Cannot modify name after it has been set.');
		}

		$this->_name = $name;
		return $this;
	}

	/**
	 *
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 *
	 */
	public function getId()
	{
		$pk = $this->getPrimaryKey();
		return $this->$pk;
	}

	/**
	 *
	 */
	protected function setId($id)
	{
		$pk = $this->getPrimaryKey();
		$this->$pk = $id;

		return $this;
	}

	/**
	 *
	 */
	public function fill(array $data = array())
	{
		foreach ($data as $k => $v)
		{
			if ($this->hasProperty($k))
			{
				$this->$k = $v;
			}
		}

		return $this;
	}

	/**
	 *
	 */
	public function hasProperty($name)
	{
		return (property_exists($this, $name) && $name[0] !== '_');
	}

	/**
	 * Normal usage will be through __get, but the tests need to mock this.
	 */
	public function getProperty($name)
	{
		if (method_exists($this, 'get__'.$name))
		{
			return $this->{'get__'.$name}();
		}

		if ($this->hasProperty($name))
		{
			return $this->$name;
		}

		throw new InvalidArgumentException("No such property: '{$name}' on ".get_called_class());
	}

	/**
	 * Normal usage will be through __set, but the tests need to mock this.
	 */
	public function setProperty($name, $value)
	{
		if (method_exists($this, 'set__'.$name))
		{
			$this->{'set__'.$name}($value);
		}
		elseif ($this->hasProperty($name))
		{
			$this->$name = $value;
		}
		else
		{
			throw new InvalidArgumentException("No such property: '{$name}' on ".get_called_class());
		}

		$this->markAsDirty($name);
		return $this;
	}

	/**
	 *
	 */
	public function isDirty()
	{
		return ! empty($this->_dirty);
	}

	/**
	 *
	 */
	protected function markAsDirty($name)
	{
		$this->_dirty[$name] = TRUE;
		return $this;
	}

	/**
	 * Clear out our dirty marker. Happens after saving.
	 */
	public function markAsClean($name = NULL)
	{
		if (isset($name))
		{
			unset($this->_dirty[$name]);
		}
		else
		{
			$this->_dirty = array();
		}

		return $this;
	}

	/**
	 *
	 */
	public function getDirty()
	{
		$dirty = array();

		foreach (array_keys($this->_dirty) as $key)
		{
			$dirty[$key] = $this->$key;
		}

		return $dirty;
	}

	/**
	 *
	 */
	public function getValues()
	{
		$values = get_object_vars($this);

		foreach ($values as $key => $value)
		{
			if ( ! isset($value) || $key[0] == '_')
			{
				unset($values[$key]);
			}
		}

		return $values;
	}

	/**
	 *
	 */
	public function isNew()
	{
		return ($this->getId() === NULL);
	}


	/**
	 *
	 */
	public function validate()
	{
		return TRUE;

		// check own validity
		(new Validation($this->rules))->check($this->values);

		foreach ($this->relationships as $name => $ship)
		{
			$ship->validate();
		}
	}

	/**
	 *
	 */
	public function save()
	{
		// insert / save
		$qb = $this->newQuery();

		if ($this->isNew())
		{
			$qb->set($this->getValues());

			$new_id = $qb->insert();
			$this->setId($new_id);
		}
		else
		{
			// TODO someone might try to change the id
			$qb->set($this->getDirty());

			$this->constrainQueryToSelf($qb);
			$qb->update();
		}

		$this->markAsClean();

		// update relationships
		foreach ($this->getAllAssociations() as $assoc)
		{
			$assoc->save();
		}

		return $this;
	}

	/**
	 *
	 */
	public function delete()
	{
		if ($this->isNew())
		{
			return $this;
		}

		$qb = $this->newQuery();

		$this->constrainQueryToSelf($qb);
		$qb->delete();

		$this->setId(NULL);
		$this->markAsClean();

		// clear relationships
		foreach ($this->getAllAssociations() as $name => $assoc)
		{
			$assoc->clear();
			$assoc->save();
		}

		return $this;
	}

	/**
	 * Limit a query to the primary id of this model
	 */
	protected function constrainQueryToSelf($query)
	{
		$pk = $this->getPrimaryKey();
		$id = $this->getId();

		$query->filter($pk, $id);
	}

	/**
	 *
	 */
	public function getAllAssociations()
	{
		return $this->_associations;
	}

	/**
	 *
	 */
	public function hasAssociation($name)
	{
		return array_key_exists($name, $this->_associations);
	}


	/**
	 *
	 */
	public function getAssociation($name)
	{
		return $this->_associations[$name];
	}


	/**
	 *
	 */
	public function setAssociation($name, Association $association)
	{
		$association->setFrontend($this->_frontend);

		$this->_associations[$name] = $association;
	}

	/**
	 *
	 */
	public function setFrontend(Frontend $frontend)
	{
		if (isset($this->_frontend))
		{
			throw new OverflowException('Cannot override existing frontend.');
		}

		$this->_frontend = $frontend;
	}

	/**
	 *
	 */
	protected function newQuery()
	{
		return $this->_frontend->get($this);
	}
}