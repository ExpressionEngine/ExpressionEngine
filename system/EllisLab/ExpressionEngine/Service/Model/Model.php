<?php

namespace EllisLab\ExpressionEngine\Service\Model;

use BadMethodCallException;
use InvalidArgumentException;
use OverflowException;

use EllisLab\ExpressionEngine\Library\Data\Entity;
use EllisLab\ExpressionEngine\Service\Model\DataStore;

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
class Model extends Entity {

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
	protected $_default_events = array(
		'beforeFetch',
		'afterFetch',
		'beforeSave', // willSave?
		'afterSave', // didSave?
		'beforeDelete',
		'afterDelete',
		'beforeSet',
		'afterSet',
		'beforeGet',
		'afterGet'
	);

	/**
	 *
	 */
	public function __construct($data = array())
	{
		parent::__construct($data);
		$this->bootEvents();
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
	 * Provide the default mixins
	 *
	 * TODO get rid of this and simply have a metadata key of the known
	 * default mixins. Depends on proper metadata booting/inheritance
	 */
	public function getMixinClasses()
	{
		$mixins = parent::getMixinClasses();

		return array_merge(
			$mixins,
			array(
				__NAMESPACE__.'\Mixin\Event',
				__NAMESPACE__.'\Mixin\Relationship',
				__NAMESPACE__.'\Mixin\TypedColumn',
				__NAMESPACE__.'\Mixin\CompositeColumn',
				__NAMESPACE__.'\Mixin\Validation'
			)
		);
	}

	public function getProperty($name)
	{
		$this->emit('beforeGet', $name);

		$result = parent::getProperty($name);

		$this->emit('afterGet', $name);

		return $result;
	}

	/**
	 * Attempt to set a property. Overriden to support dirty values.
	 *
	 * @param String $name Name of the property
	 * @param Mixed  $value Value of the property
	 */
	public function setProperty($name, $value)
	{
		$this->emit('beforeSet', $name, $value);

		parent::setProperty($name, $value);
		$this->markAsDirty($name);

		$this->emit('afterSet', $name, $value);

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
	 * Get all current values
	 *
	 * @return array Current values. Including null values - Beware.
	 */
	public function getValues()
	{
		$this->saveCompositeColumns();

		return parent::getValues();
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
			$this->saveCompositeColumns();
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

	public function getFrontend()
	{
		return $this->_frontend;
	}


	/**
	 * Support method for the event mixin
	 */
	public function getEvents()
	{
		$events = $this->getMetaData('events') ?: array();
		$events = array_merge($this->_default_events, $events); // todo remove when metadata extending

		return $events;
	}


	/**
	 * Support method for the typed columns mixin
	 */
	public function getTypedColumns()
	{
		return $this->getMetaData('typed_columns') ?: array();
	}

	/**
	 * Support method for the composite column mixin
	 */
	public function getCompositeColumns()
	{
		$definitions = array();

		$columns = $this->getMetaData('columns') ?: array();
		$columns = array_reverse($columns);

		foreach ($columns as $name => $property)
		{
			$class = $this->getNamespacePrefix().'\\Column\\'.$name;

			$definitions[$name] = compact('class', 'property');
		}

		return $definitions;
	}

	/**
	 *
	 */
	protected function getNamespacePrefix()
	{
		$class = get_called_class();
		return substr($class, 0, strrpos($class, '\\'));
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

	/**
	 * Clean up var_dump output for developers on PHP 5.6+
	 */
	public function __debugInfo()
	{
		$name = $this->_name;
		$values = $this->getValues();
		$related_to = array_keys($this->getAllAssociations());

		return compact('name', 'values', 'related_to');
	}

}