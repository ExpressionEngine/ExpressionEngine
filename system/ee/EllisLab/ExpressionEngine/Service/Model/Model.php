<?php

namespace EllisLab\ExpressionEngine\Service\Model;

use Closure;
use OverflowException;

use EllisLab\ExpressionEngine\Library\Data\Entity;
use EllisLab\ExpressionEngine\Service\Event\Publisher as EventPublisher;
use EllisLab\ExpressionEngine\Service\Event\Subscriber as EventSubscriber;
use EllisLab\ExpressionEngine\Service\Model\Association\Association;
use EllisLab\ExpressionEngine\Service\Validation\Validator;
use EllisLab\ExpressionEngine\Service\Validation\ValidationAware;

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
class Model extends Entity implements EventPublisher, EventSubscriber, ValidationAware {


	/**
	 * @var String model short name
	 */
	protected $_name;

	/**
	 * @var Is new instance
	 */
	protected $_new = TRUE;

	/**
	 * @var Query frontend object
	 */
	protected $_frontend = NULL;

	/**
	 * @var Validator object
	 */
	protected $_validator = NULL;

	/**
	 *
	 */
	protected $_associations = array();

	/**
	 * @var Relationships property must default to array
	 */
	protected static $_relationships = array();

	/**
	 * @var Default mixins for models
	 */
	protected static $_mixins = array(
		'EllisLab\ExpressionEngine\Service\Event\Mixin',
		'EllisLab\ExpressionEngine\Service\Model\Mixin\TypedColumn',
		'EllisLab\ExpressionEngine\Service\Model\Mixin\CompositeColumn'
	);

	protected function initialize()
	{
		// Nothing. Use this for any setup work you need to do.
	}

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

		$this->_new = is_null($id);

		return $this;
	}

	/**
	 * Attempt to get a property. Overriden from Entity to support events
	 *
	 * @param String $name Name of the property
	 * @return Mixed  $value Value of the property
	 */
	public function getProperty($name)
	{
		$this->emit('beforeGet', $name);

		$value = parent::getProperty($name);

		$this->emit('afterGet', $name);

		return $value;
	}

	/**
	 * Attempt to set a property. Overriden from Entity to support events
	 *
	 * @param String $name Name of the property
	 * @param Mixed  $value Value of the property
	 */
	public function setProperty($name, $value)
	{
		$this->emit('beforeSet', $name, $value);

		parent::setProperty($name, $value);

		$this->emit('afterSet', $name, $value);

		return $this;
	}

	/**
	 * Fill data without passing through a getter
	 *
	 * @param array $data Data to fill
	 * @return $this
	 */
	public function fill(array $data = array())
	{
		$pk = $this->getPrimaryKey();

		if (isset($data[$pk]))
		{
			$this->_new = FALSE;
		}

		return parent::fill($data);
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
		return $this->_new;
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

		$this->emit('beforeValidate');

		if ($this->isNew())
		{
			$result = $this->_validator->validate($this);
		}
		else
		{
			$result = $this->_validator->validatePartial($this);
		}

		$this->emit('afterValidate');

		return $result;
	}

	/**
	 * Set the validator
	 *
	 * @param Validator $validator The validator to use
	 * @return Current scope
	 */
	public function setValidator(Validator $validator)
	{
		$this->_validator = $validator;

		// alias unique to the validateUnique callback
		$validator->defineRule('unique', array($this, 'validateUnique'));

		// alias uniqueWithinSiblings to the validateUniqueWithinSiblings callback
		$validator->defineRule('uniqueWithinSiblings', array($this, 'validateUniqueWithinSiblings'));

		return $this;
	}

	/**
	 * Get the validator
	 *
	 * @return Validator object
	 */
	public function getValidator()
	{
		return $this->_validator;
	}

	/**
	 * Support ValidationAware
	 */
	public function getValidationData()
	{
		return $this->getDirty();
	}

	/**
	 * Support ValidationAware
	 */
	public function getValidationRules()
	{
		return $this->getMetaData('validation_rules') ?: array();
	}

	/**
	 * Default callback to validate unique columns
	 *
	 * @param String $key    Property name
	 * @param String $value  Property value
	 * @param Array  $params Rule parameters
	 * @return Mixed String if error, TRUE if success
	 */
	public function validateUnique($key, $value, array $params = array())
	{
		$unique = $this->getFrontend()
			->get($this->getName())
			->filter($key, $value);

		foreach ($params as $field)
		{
			$unique->filter($field, $this->getProperty($field));
		}

		if ($unique->count() > 0)
		{
			return 'unique'; // lang key
		}

		return TRUE;
	}

	/**
	 * Default callback to validate unique columns across siblings
	 *
	 * @param String $key    Property name
	 * @param String $value  Property value
	 * @param Array  $params Rule parameters, first parameter must be the parent
	 *	relationship name, second must be child relationship name from parent
	 * @return Mixed String if error, TRUE if success
	 */
	public function validateUniqueWithinSiblings($key, $value, array $params)
	{
		if (count($params) != 2)
		{
			throw new InvalidArgumentException('uniqueWithinSiblings must have at least two arguments.');
		}

		$get_parent = 'get' . $params[0];
		$get_siblings = 'get' . $params[1];

		$unique = $this->$get_parent()
			->$get_siblings()
			->filter(function ($element) use ($key, $value)
			{
				return $element->$key == $value;
			});

		// Greater than one to account for self
		if (count($unique) > 1)
		{
			return 'unique';
		}

		return TRUE;
	}

	/**
	 * Interface method to implement Event\Subscriber
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
	* @return $this
	*/
	public function setAssociation($name, Association $association)
	{
		$this->emit('beforeSetAssociation', $name, $association);

		$association->setFrontend($this->getFrontend());

		$this->_associations[$name] = $association;

		$this->emit('afterSetAssociation', $name, $association);

		return $this;
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