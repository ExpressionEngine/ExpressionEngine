<?php

namespace EllisLab\ExpressionEngine\Service\Model;

use Closure;
use OverflowException;

use EllisLab\ExpressionEngine\Library\Data\Entity;
use EllisLab\ExpressionEngine\Library\Data\SerializableEntity;
use EllisLab\ExpressionEngine\Service\Model\Association\Association;
use EllisLab\ExpressionEngine\Service\Model\Column\StaticType;
use EllisLab\ExpressionEngine\Service\Validation\Validator;
use EllisLab\ExpressionEngine\Service\Validation\ValidationAware;
use EllisLab\ExpressionEngine\Service\Event\Subscriber;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
 */
class Model extends SerializableEntity implements Subscriber, ValidationAware {


	/**
	 * @var String model short name
	 */
	protected $_name;

	/**
	 * @var Is new instance
	 */
	protected $_new = TRUE;

	/**
	 * @var Model facade object
	 */
	protected $_facade = NULL;

	/**
	 * @var Validator object
	 */
	protected $_validator = NULL;

	/**
	 * @var Hook recursion prevention
	 */
	protected $_in_hook = array();

	/**
	 * @var Associated models
	 */
	protected $_associations = array();

	/**
	 * @var Cache of variable types - can be class names or objects
	 */
	protected $_property_types = array();

	/**
	 * @var Type names and their corresponding classes
	 */
	protected static $_type_classes = array(
		'bool' => 'EllisLab\ExpressionEngine\Service\Model\Column\Scalar\Boolean',
		'boolean' => 'EllisLab\ExpressionEngine\Service\Model\Column\Scalar\Boolean',

		'float' => 'EllisLab\ExpressionEngine\Service\Model\Column\Scalar\FloatNumber',
		'double' => 'EllisLab\ExpressionEngine\Service\Model\Column\Scalar\FloatNumber',

		'int' => 'EllisLab\ExpressionEngine\Service\Model\Column\Scalar\Integer',
		'integer' => 'EllisLab\ExpressionEngine\Service\Model\Column\Scalar\Integer',

		'string' => 'EllisLab\ExpressionEngine\Service\Model\Column\Scalar\StringLiteral',

		'yesNo' => 'EllisLab\ExpressionEngine\Service\Model\Column\Scalar\YesNo',
		'boolString' => 'EllisLab\ExpressionEngine\Service\Model\Column\Scalar\YesNo',

		'timestamp' => 'EllisLab\ExpressionEngine\Service\Model\Column\Object\Timestamp',

		'base64' => 'EllisLab\ExpressionEngine\Service\Model\Column\Serialized\Base64',
		'base64Array' => 'EllisLab\ExpressionEngine\Service\Model\Column\Serialized\Base64Array',
		'base64Serialized' => 'EllisLab\ExpressionEngine\Service\Model\Column\Serialized\Base64Native',

		'json' => 'EllisLab\ExpressionEngine\Service\Model\Column\Serialized\Json',

		'commaDelimited' => 'EllisLab\ExpressionEngine\Service\Model\Column\Serialized\CommaDelimited',
		'pipeDelimited' => 'EllisLab\ExpressionEngine\Service\Model\Column\Serialized\PipeDelimited',
		'serialized' => 'EllisLab\ExpressionEngine\Service\Model\Column\Serialized\Native',
	);

	/**
	 * @var Typed columns must default to array
	 */
	protected static $_typed_columns = array();

	/**
	 * @var Relationships property must default to array
	 */
	protected static $_relationships = array();

	/**
	 * @var Default mixins for models
	 */
	protected static $_mixins = array(
		'EllisLab\ExpressionEngine\Service\Model\Mixin\Relationship'
	);

	/**
	 * Add some default filters that we need for models. Might hardcode some
	 * of these in the long run.
	 */
	protected function initialize()
	{
		$this->addFilter('get', array($this, 'typedGet'));
		$this->addFilter('set', array($this, 'typedSet'));
		$this->addFilter('fill', array($this, 'typedLoad'));
		$this->addFilter('store', array($this, 'typedStore'));

		if ($publish_as = $this->getMetaData('hook_id'))
		{
			$this->forwardEventsToHooks($publish_as);
		}
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
		if ($action = $this->getMixin('Model:Relationship')->getAssociationActionFromMethod($method))
		{
			return $this->getMixin('Model:Relationship')->runAssociationAction($action, $args);
		}

		return parent::__call($method, $args);
	}

	/**
	 * Extend __get to grant access to associated objects
	 *
	 * Associations must start with an uppercase letter
	 *
	 * @param String $key The property to access
	 * @return Mixed The property value
	 */
	public function __get($key)
	{
		if ($key && strtoupper($key[0]) == $key[0])
		{
			if ($this->hasAssociation($key))
			{
				return $this->getAssociation($key)->get();
			}
		}

		return parent::__get($key);
	}

	/**
	 * Allow use of __set to set an association
	 *
	 * @param String $key The property to set
	 * @param Mixed $value The property value
	 */
	public function __set($key, $value)
	{
		if ($key && strtoupper($key[0]) == $key[0])
		{
			if ($this->hasAssociation($key))
			{
				return $this->getAssociation($key)->set($value);
			}
		}

		return parent::__set($key, $value);
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

		$this->emit('setId', $id);

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
	 * Check if the model has been saved
	 *
	 * @return bool is new?
	 */
	public function isNew()
	{
		return $this->_new;
	}

	public function getModified()
	{
		return array_merge(
			$this->getChangedTypeValues(),
			parent::getModified()
		);
	}

	/**
	 * Save the model
	 *
	 * @return $this
	 */
	public function save()
	{
		$qb = $this->newSelfReferentialQuery();

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
			if (isset($assoc))
			{
				$assoc->set(NULL);
			}
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
	 * Set the facade
	 *
	 * @param Facade $facade The model facade to use
	 * @return $this
	 */
	public function setFacade(Facade $facade)
	{
		if (isset($this->_facade))
		{
			throw new OverflowException('Cannot override existing model facade.');
		}

		$this->_facade = $facade;

		return $this;
	}

	/**
	 * Get the model facade
	 *
	 * @return Facade The model facade object
	 */
	public function getModelFacade()
	{
		return $this->_facade;
	}

	// alias
	public function getFrontend()
	{
		return $this->getModelFacade();
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
	 * @return $this
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
		return $this->getModified();
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
		$unique = $this->getModelFacade()
			->get($this->getName())
			->filter($key, $value);

		foreach ($params as $field)
		{
			$unique->filter($field, $this->getProperty($field));
		}

		// Do not match self
		if ($this->getId())
		{
			$unique->filter($this->getPrimaryKey(), '!=', $this->getId());
		}

		if ($unique->count() > 0)
		{
			return 'unique'; // lang key
		}

		return TRUE;
	}

	/**
	 * Forwards lifecycle events to consistently named hooks
	 *
	 * This is fired automatically from initialize() if `hook_id` is
	 * given in the model metadata.
	 *
	 * @param String $hook_basename The name that identifies the subject of the hook
	 */
	protected function forwardEventsToHooks($hook_basename)
	{
		$trigger = $this->getHookTrigger();

		$forwarded = array(
			'beforeInsert' => 'before_'.$hook_basename.'_insert',
			'afterInsert' => 'after_'.$hook_basename.'_insert',
			'beforeUpdate' => 'before_'.$hook_basename.'_update',
			'afterUpdate' => 'after_'.$hook_basename.'_update',
			'beforeSave' => 'before_'.$hook_basename.'_save',
			'afterSave' => 'after_'.$hook_basename.'_save',
			'beforeDelete' => 'before_'.$hook_basename.'_delete',
			'afterDelete' => 'after_'.$hook_basename.'_delete'
		);

		$that = $this;

		foreach ($forwarded as $event => $hook)
		{
			$this->on($event, function() use ($trigger, $hook, $that)
			{
				$addtl_args = func_get_args();
				$args = array($hook, $that, $that->getValues());

				call_user_func_array($trigger, array_merge($args, $addtl_args));
			});
		}
	}

	/**
	 * Returns a function that can be used to trigger a hook outside the current
	 * object scope. Thank you PHP 5.3, you hunk of garbage.
	 *
	 * @return Closure Function that takes hookname and parameters and calls the hook
	 */
	protected function getHookTrigger()
	{
		$in_hook =& $this->_in_hook;

		return function($name) use ($in_hook)
		{
			if (in_array($name, $in_hook))
			{
				return;
			}

			$in_hook[] = $name;

			if (isset(ee()->extensions) && ee()->extensions->active_hook($name) === TRUE)
			{
				$args = func_get_args();
				call_user_func_array(array(ee()->extensions, 'call'), $args);
			}

			array_pop($in_hook);
		};
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

		$parent = $params[0];
		$siblings = $params[1];

		if ($this->$parent && $this->$parent->$siblings)
		{
			$count = $this->$parent->$siblings->filter($key, $value)->count();

			if ($count > 1)
			{
				return 'unique';
			}
		}

		return TRUE;
	}

	public function typedLoad($value, $name)
	{
		if ($type = $this->getTypeFor($name))
		{
			return $type->load($value);
		}

		return $value;
	}

	public function typedStore($value, $name)
	{
		if ($type = $this->getTypeFor($name))
		{
			return $type->store($value);
		}

		return $value;
	}

	public function typedGet($value, $name)
	{
		if ($type = $this->getTypeFor($name))
		{
			return $type->get($value);
		}

		return $value;
	}

	public function typedSet($value, $name)
	{
		if ($type = $this->getTypeFor($name))
		{
			return $type->set($value);
		}

		return $value;
	}

	public function getTypeFor($name)
	{
		if ( ! array_key_exists($name, $this->_property_types))
		{
			$this->_property_types[$name] = $this->createTypeFor($name);
		}

		return $this->_property_types[$name];
	}

	public function createTypeFor($name)
	{
		$columns = $this->getMetadata('typed_columns') ?: array();
		$types = $this->getMetadata('type_classes');

		if ( ! array_key_exists($name, $columns))
		{
			return NULL;
		}

		$type = $columns[$name];
		$class = $types[$type];

		return $class::create();
	}

	/**
	 * Sync up typed column values
	 */
	protected function getChangedTypeValues()
	{
		$changed = array();

		foreach ($this->_property_types as $name => $type)
		{
			$set = $this->getRawProperty($name);
			$type = $this->getTypeFor($name);

			if ($this->isDirty($name) || $type instanceOf Entity)
			{
				$value = $this->getBackup($name, $set);
				$new_value = $this->typedStore($set, $name);

				if ($new_value !== $value)
				{
					$changed[$name] = $set;
				}
			}
		}

		return $changed;
	}

	/**
	 * Getter for serialization
	 *
	 * @return Mixed Data to serialize
	 */
	protected function getSerializeData()
	{
		return array(
			'name' => $this->getName(),
			'values' => parent::getSerializeData()
		);
	}

	/**
	 * Overridable setter for unserialization
	 *
	 * @param Mixed $data Data returned from `getSerializedData`
	 * @return void
	 */
	public function setSerializeData($data)
	{
		// datastore requires a name
		$this->setName($data['name']);

		// set all of the external dependencies
		ee('Model')->make($this);

		parent::setSerializeData($data['values']);
	}

	/**
	 * Interface method to implement Event\Subscriber
	 */
	public function getSubscribedEvents()
	{
		return $this->getMetaData('events') ?: array();
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
		$association->setFacade($this->getModelFacade());

		$this->_associations[$name] = $association;

		return $this;
	}

	/**
	 * Alias an association
	 *
	 * @param String Associaton name to create an alias for
	 * @param String Alias name
	 */
	public function alias($association, $as)
	{
		if (strpos($association, ':') === FALSE)
		{
			throw new \Exception('Cannot alias relationship.');
		}

		return $this->setAssociation($as, $this->getAssociation($association));
	}


	/**
	 * Create a new query tied to this object
	 *
	 * @return QueryBuilder new query
	 */
	protected function newSelfReferentialQuery()
	{
		return $this->_facade->get($this);
	}

	public function __toString()
	{
		return spl_object_hash($this).':'.$this->getName().':'.$this->getId();
	}
}

// EOF
