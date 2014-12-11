<?php

namespace EllisLab\ExpressionEngine\Service\Model;

use BadMethodCallException;
use InvalidArgumentException;
use OverflowException;

use EllisLab\ExpressionEngine\Service\Model\DataStore;
use EllisLab\ExpressionEngine\Service\Model\Association\Association;
use EllisLab\ExpressionEngine\Service\Validation\Validator;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Model
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
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
	protected $_validator = NULL;

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
			list($_, $action, $assoc_name) = $matches;

			if ($this->hasAssociation($assoc_name))
			{
				return $this->runAssociationAction($assoc_name, $action, $args);
			}
		}

		throw new BadMethodCallException("Method not found: {$method}.");
	}

	/**
	 *
	 */
	protected function runAssociationAction($assoc_name, $action, $args)
	{
		$assoc = $this->getAssociation($assoc_name);
		$result = call_user_func_array(array($assoc, $action), $args);

		if ($action == 'has' || $action == 'get')
		{
			return $result;
		}

		return $this;
	}

	/**
	 * Clean up var_dump output for developers on PHP 5.6+
	 */
	public function __debugInfo()
	{
		$name = $this->_name;
		$values = $this->getRawValues();
		$related_to = array_keys($this->_associations);

		return compact('name', 'values', 'related_to');
	}

	/**
	 * Metadata is static. If you need to access it, it's recommended
	 * to use the datastore's lazy caches, which will make for much
	 * more testable code and avoid iterating over gateways and creating
	 * relations repeatedly.
	 *
	 * @param String $key Name of the static property
	 * @return mixed The metadata value Set the short name of this model
	 *
	 * @param String $name The short name
	 */
	public static function getMetaData($key)
	{
		$key = '_'.$key;

		if ( ! property_exists(get_called_class(), $key))
		{
			return NULL;
		}

		return static::$$key;
	}

	/**
	 * Set the short name of this model
	 *
	 * @param String $name The short name
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
	 * Get the short name
	 *
	 * @return String short name
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * Access the primary key name
	 *
	 * @return string primary key name
	 */
	public function getPrimaryKey()
	{
		return $this->getMetaData('primary_key');
	}

	/**
	 * Get the primary key value
	 *
	 * @return int Primary key
	 */
	public function getId()
	{
		$pk = $this->getPrimaryKey();
		return $this->$pk;
	}

	/**
	 * Set the primary key value
	 *
	 * @return $this
	 */
	protected function setId($id)
	{
		$pk = $this->getPrimaryKey();
		$this->$pk = $id;

		return $this;
	}

	/**
	 * Fill model data without marking as dirty.
	 *
	 * @param array $data Data to fill
	 * @return $this
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
	 * Check if the model has a given property
	 *
	 * @param String $name Property name
	 * @return bool has property?
	 */
	public function hasProperty($name)
	{
		return (property_exists($this, $name) && $name[0] !== '_');
	}

	/**
	 * Attempt to get a property. Called by __get.
	 *
	 * @param String $name Name of the property
	 * @return Mixed Value of the property
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
	 * Attempt to set a property. Called by __set.
	 *
	 * @param String $name Name of the property
	 * @param Mixed  $value Value of the property
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
	 * Check if model has dirty values
	 *
	 * @return bool is dirty?
	 */
	public function isDirty()
	{
		return ! empty($this->_dirty);
	}

	/**
	 * Mark a property as dirty
	 *
	 * @param String $name property name
	 * @return $this
	 */
	protected function markAsDirty($name)
	{
		$this->_dirty[$name] = TRUE;

		return $this;
	}

	/**
	 * Clear out our dirty marker. Happens after saving.
	 *
	 * @param String $name property name [optional]
	 * @return $this
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
	 * Get all dirty keys and values
	 *
	 * @return array Dirty properties and their values
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
	 * Get a list of fields
	 *
	 * @return array field names
	 */
	public static function getFields()
	{
		$vars = get_class_vars(get_called_class());
		$fields = array();

		foreach ($vars as $key => $value)
		{
			if ($key[0] != '_')
			{
				$fields[] = $key;
			}
		}

		return $fields;
	}

	/**
	 * Get all current values
	 *
	 * @return array Current values. Including null values - Beware.
	 */
	public function getValues()
	{
		$result = array();

		foreach ($this->getFields() as $field)
		{
			$result[$field] = $this->getProperty($field);
		}

		return $result;
	}

	/**
	 * Check if the model has been saved
	 *
	 * @return bool is new?
	 */
	public function isNew()
	{
		return ($this->getId() === NULL);
	}

	/**
	 * Validate the model
	 *
	 * @return validation result
	 */
	public function validate()
	{
		if ( ! isset($this->_validator))
		{
			return TRUE;
		}

		return $this->_validator->validate($this->getDirty());

		// TODO validate relationships?
		foreach ($this->getAllAssociations() as $assoc)
		{
			$assoc->validate();
		}
	}

	/**
	 * Save the model
	 *
	 * @return $this
	 */
	public function save()
	{
		$qb = $this->newQuery();

		if ($this->isNew())
		{
			// insert
			$qb->set($this->getValues());

			$new_id = $qb->insert();
			$this->setId($new_id);
		}
		else
		{
			// update
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
	 * Delete the model
	 *
	 * @return $this
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
	 * Retrieve data as an array. All getters will be hit.
	 *
	 * @return array Data including NULl values
	 */
	public function toArray()
	{
		return $this->getValues();
	}

	/**
	 * Limit a query to the primary id of this model
	 *
	 * @param QueryBuilder $query The query that will be sent
	 */
	protected function constrainQueryToSelf($query)
	{
		$pk = $this->getPrimaryKey();
		$id = $this->getId();

		$query->filter($pk, $id);
	}

	/**
	 * Get all associations
	 *
	 * @return array associations
	 */
	public function getAllAssociations()
	{
		return $this->_associations;
	}

	/**
	 * Check if an association of a given name exists
	 *
	 * @param String $name Name of the association
	 * @return bool has association?
	 */
	public function hasAssociation($name)
	{
		return array_key_exists($name, $this->_associations);
	}

	/**
	 * Get an association of a given name
	 *
	 * @param String $name Name of the association
	 * @return Mixed the association
	 */
	public function getAssociation($name)
	{
		return $this->_associations[$name];
	}

	/**
	 * Set a given association
	 *
	 * @param String $name Name of the association
	 * @param Association $association Association to set
	 * @return $this;
	 */
	public function setAssociation($name, Association $association)
	{
		$association->setFrontend($this->_frontend);

		$this->_associations[$name] = $association;

		return $this;
	}

	/**
	 * Set the frontend
	 *
	 * @param Frontend $frontend The frontend to use
	 * @return $this
	 */
	public function setFrontend(Frontend $frontend)
	{
		if (isset($this->_frontend))
		{
			throw new OverflowException('Cannot override existing frontend.');
		}

		$this->_frontend = $frontend;

		return $this;
	}

	/**
	 * Set the validatior
	 *
	 * @param Validator $validator The validator to use
	 * @return $this;
	 */
	public function setValidator(Validator $validator)
	{
		$this->_validator = $validator;

		$rules = $this->getMetaData('validation_rules');
		$validator->setRules($rules);

		return $this;
	}

	/**
	 * Create a new query
	 *
	 * @return QueryBuilder new query
	 */
	protected function newQuery()
	{
		return $this->_frontend->get($this);
	}
}