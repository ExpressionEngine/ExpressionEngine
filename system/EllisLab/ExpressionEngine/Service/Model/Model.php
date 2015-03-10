<?php

namespace EllisLab\ExpressionEngine\Service\Model;

use BadMethodCallException;
use Closure;
use OverflowException;

use EllisLab\ExpressionEngine\Library\Data\Entity;
use EllisLab\ExpressionEngine\Service\Event\Publisher as EventPublisher;
use EllisLab\ExpressionEngine\Service\Event\Subscriber as EventSubscriber;
use EllisLab\ExpressionEngine\Service\Event\ReflexiveSubscriber;

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
class Model extends Entity implements EventPublisher, ReflexiveSubscriber {

	/**
	 * @var String model short name
	 */
	protected $_name;

	/**
	 * @var List of dirty values
	 */
	protected $_dirty = array();

	/**
	 * @var Query frontend object
	 */
	protected $_frontend = NULL;

	/**
	 * @var Default mixins for models
	 */
	protected static $_mixins = array(
		'EllisLab\ExpressionEngine\Service\Event\Mixin',
		'EllisLab\ExpressionEngine\Service\Model\Mixin\TypedColumn',
		'EllisLab\ExpressionEngine\Service\Model\Mixin\Validation',
		'EllisLab\ExpressionEngine\Service\Model\Mixin\CompositeColumn',
		'EllisLab\ExpressionEngine\Service\Model\Mixin\Relationship',
	);

	/**
	 * Forward methods to various mixins
	 *
	 * @param String $method Method name to call
	 * @param Array $args Arguments to pass to the method
	 * @return Mixed return value of the called method
	 */
	public function __call($method, $args)
	{
		if ($column = $this->getMixin('Model:CompositeColumn')->getCompositeColumnNameFromMethod($method))
		{
			return $this->getMixin('Model:CompositeColumn')->getCompositeColumn($column);
		}

		if ($action = $this->getMixin('Model:Relationship')->getAssociationActionFromMethod($method))
		{
			return $this->getMixin('Model:Relationship')->runAssociationAction($action, $args);
		}

		return parent::__call($method, $args);
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
	 * This will not trigger a dirty state. Primary keys should be
	 * considered immutable!
	 *
	 * @return $this
	 */
	public function setId($id)
	{
		$pk = $this->getPrimaryKey();
		$this->$pk = $id;

		return $this;
	}

	/**
	 * Attempt to get a property. Overriden from Entity to support events
	 * and typed columns.
	 *
	 * @param String $name Name of the property
	 * @return Mixed  $value Value of the property
	 */
	public function getProperty($name)
	{
		$this->emit('beforeGet', $name);

		$value = parent::getProperty($name);
		$value = $this->filter('get', $value, array($name));

		$this->emit('afterGet', $name);

		return $value;
	}

	/**
	 * Attempt to set a property. Overriden from Entity to support events,
	 * dirty values, and typed columns.
	 *
	 * @param String $name Name of the property
	 * @param Mixed  $value Value of the property
	 */
	public function setProperty($name, $value)
	{
		$this->emit('beforeSet', $name, $value);

		$value = $this->filter('set', $value, array($name));

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
		$qb = $this->newSelfReferentialQuery();

		$this->saveCompositeColumns();

		if ($this->isNew())
		{
			$qb->insert();
		}
		else
		{
			$this->constrainQueryToSelf($qb);
			$qb->update();
		}

		$this->markAsClean();

		// update relationships
		foreach ($this->getAllAssociations() as $assoc)
		{
			if (isset($assoc))
			{
				$assoc->save();
			}
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

		$qb = $this->newSelfReferentialQuery();

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

	/**
	 *
	 */
	public function getFrontend()
	{
		return $this->_frontend;
	}

	/**
	 * Support method for the model validation mixin
	 */
	public function getValidationData()
	{
		return $this->getDirty();
	}

	/**
	 * Support method for the model validation mixin
	 */
	public function getValidationRules()
	{
		return $this->getMetaData('validation_rules') ?: array();
	}

	/**
	 * Interface method to implement Event\Subscriber, which automatically
	 * subscribes this class to itself to call on<EventName>.
	 */
	public function getSubscribedEvents()
	{
		return $this->getMetaData('events') ?: array();
	}

	/**
	 * Interface method to implement Event\Publisher so that others can
	 * subscribe to events on this object.
	 *
	 * Technically this works automatically since the method exists on the
	 * mixin, but doing this lets us enforce an interface, which will be
	 * useful when hopefully replacing mixins with traits in future.
	 */
	public function subscribe(EventSubscriber $subscriber)
	{
		return $this->getMixin('Event')->subscribe($subscriber);
	}

	/**
	 * Interface method to implement Event\Publisher
	 *
	 * @see Model::subscribe()
	 */
	public function unsubscribe(EventSubscriber $subscriber)
	{
		return $this->getMixin('Event')->unsubscribe($subscriber);
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

		$all = $this->getMetaDataByClass('composite_columns');

		foreach ($all as $class => $columns)
		{
			$ns_prefix = substr($class, 0, strrpos($class, '\\'));

			foreach ($columns as $property => $name)
			{
				$class = $ns_prefix.'\\Column\\'.$name;

				$definitions[$name] = compact('class', 'property');
			}
		}

		return $definitions;
	}

	/**
	 * Create a new query tied to this object
	 *
	 * @return QueryBuilder new query
	 */
	protected function newSelfReferentialQuery()
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