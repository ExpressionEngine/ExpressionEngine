<?php
namespace EllisLab\ExpressionEngine\Service\Model;

use InvalidArgumentException;

use EllisLab\ExpressionEngine\Service\AliasServiceInterface;
use EllisLab\ExpressionEngine\Service\Error\Errors;
use EllisLab\ExpressionEngine\Service\Model\Relationship\Cascade;
use EllisLab\ExpressionEngine\Service\Model\Relationship\Bag as RelationshipBag;
use EllisLab\ExpressionEngine\Service\Validation\Factory as ValidationFactory;


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
 * ExpressionEngine Base Model
 *
 * The base Model class
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
abstract class Model {

	/**
	 *
	 */
	protected static $_primary_key = '';

	/**
	 *
	 */
	protected static $_gateway_names = array();

	/**
	 * Optional keys
	 */
	protected static $_key_map = array();
	protected static $_cascade = array();
	protected static $_validation_rules = array();
	protected static $_relationships = array();


	/**
	 *
	 */
	protected $_name;

	/**
	 *
	 */
	protected $_factory = NULL;

	/**
	 *
	 */
	protected $_validation_factory = NULL;

	/**
	 *
	 */
	protected $_relationship_edges;

	/**
	 *
	 */
	protected $_gateways = array();

	/**
	 *
	 */
	protected $_related_models = array();

	/**
	 *
	 */
	protected $_dirty = array();

	/**
	 *
	 */
	protected $_dirty_relationships = array();

	/**
	 *
	 */
	protected $_deleted = FALSE;


	/**
	 * Initialize this model with a set of data to set on the gateway.
	 *
	 * @param \EllisLab\ExpressionEngine\Model\ModelFactory
	 * @param	mixed[]	$data	An array of initial property values to set on
	 * 		this model.  The array indexes must be valid properties on this
	 * 		model's gateway.
	 */
	public function __construct(Factory $factory, array $data = array())
	{
		$this->_factory = $factory;
		$this->_related_models = new RelationshipBag();

		$this->fill($data);
	}

	/**
	 * Pass through getter that allows properties to be gotten from this model
	 * but stored in the wrapped gateway.
	 *
	 * @param	string	$name	The name of the property to be retrieved.
	 *
	 * @return	mixed	The property being retrieved.
	 *
	 * @throws	NonExistentPropertyException	If the property doesn't exist,
	 * 					an appropriate exception is thrown.
	 */
	public function __get($name)
	{
		$method = 'get' . ucfirst($name);

		if (method_exists($this, $method))
		{
			return $this->$method();
		}

		if (property_exists($this, $name) && strpos($name, '_') !== 0)
		{
			return $this->$name;
		}

		throw new InvalidArgumentException('Attempt to access a non-existent property, "' . $name . '", on ' . get_called_class());
	}

	/**
	 * Pass through setter that allows properties to be set on this model,
	 * but stored in the wrapped gateway.
	 *
	 * @param	string	$name	The name of the property being set. Must be
	 * 						a valid property on the wrapped gateway.
	 * @param	mixed	$value	The value to set the property to.
	 *
	 * @return	void
	 *
	 * @throws	NonExistentPropertyException	If the property doesn't exist,
	 * 					and appropriate exception is thrown.
	 */
	public function __set($name, $value)
	{
		$method = 'set' . ucfirst($name);

		if (method_exists($this, $method))
		{
			return $this->$method($value);
		}

		if (property_exists($this, $name) && strpos($name, '_') !== 0)
		{
			$this->$name = $value;
			$this->setDirty($name);
			return;
		}

		throw new InvalidArgumentException('Attempt to access a non-existent property "' . $name . '" on ' . get_called_class());
	}

	/**
	 *
	 */
	public function __call($method, $arguments)
	{
		$actions = 'has|get|set|add|remove|create|delete|fill';

		if ( ! preg_match("/^({$actions})(.+)/", $method, $matches))
		{
			throw new \Exception("Method not found: {$method}.");
		}

		list($_, $action, $relationship) = $matches;

		if ( ! $this->hasRelationshipEdge($relationship))
		{
			throw new \Exception("Trying to {$action} unknown relationship: {$relationship}.");
		}

		array_unshift($arguments, $relationship);

		return call_user_func_array(
			array($this, "{$action}Related"),
			$arguments
		);
	}

	/**
	 *
	 */
	public function fill(array $data = array())
	{
		foreach ($data as $property => $value)
		{
			if (property_exists($this, $property))
			{
				$this->$property = $value;
			}
		}
	}

	/**
	 * Set the name used to construct this model
	 */
	public function setName($name)
	{
		$this->_name = $name;
	}

	/**
	 * Get the name used to construct this model
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * Mark a property as dirty
	 *
	 * @param String $property Name of the property to mark
	 * @return $this
	 */
	protected function setDirty($property)
	{
		$this->_dirty[$property] = TRUE;
		return $this;
	}

	/**
	 * Check if a property is marked as dirty
	 *
	 * @param String $property Name of the property to mark
	 * @return Bool  dirty?
	 */
	protected function isDirty($property)
	{
		return (isset($this->_dirty[$property]) && $this->_dirty[$property]);
	}

	/**
	 * Get the model metadata
	 *
	 * @param String $key Metadata key name
	 * @return Mixed Value for $key or full metadata array
	 */
	public static function getMetaData($key)
	{
		$property = '_' . $key;

		if ( ! property_exists(get_called_class(), $property))
		{
			$parent = get_parent_class(get_called_class());

			if ($parent)
			{
				return $parent::getMetaData($key);
			}

			return NULL;
		}

		$value = static::$$property;

		$should_be_array = is_array($value);

		$parent = get_parent_class(get_called_class());

		if ($parent)
		{
			$parent_value = $parent::getMetaData($key);

			if ($should_be_array && ! empty($value) && ! empty($parent_value))
			{
				$value = array_merge($value, $parent_value);
			}
			elseif (empty($value) && ! empty($parent_value))
			{
				$value = $parent_value;
			}
		}

		return $value;
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
	public function markAsNew()
	{
		$primary_key = $this->getMetaData('primary_key');
		$this->$primary_key = NULL;

		return $this;
	}

	/**
	 * Get the primary id for this model
	 *
	 * @return int	Primary key value of the model
	 */
	public function getId()
	{
		$primary_key = $this->getMetaData('primary_key');
		return $this->$primary_key;
	}

	/**
	 * Using setter injection allows third parties and tests to flip out the
	 * validation. This is automatically passed on to the gateways.
	 */
	public function setValidationFactory(ValidationFactory $validation_factory = NULL)
	{
		$this->_validation_factory = $validation_factory;
	}

	/**
	 * Validate this model's data for saving.  May cascade the validation
	 * through any set of related models using the same grouping language
 	 * that is used in the query builder.  For example:
	 *
	 * $entry->title = 'New Title';
	 * $channel = $entry->getChannel();
	 * $channel->short_name = 'new_short_name';
	 *
	 * $validation = $entry->validate(
	 * 		'Channel',
	 *		array('Member' => 'MemberGroup'),
	 * 		array('Category' => 'CategoryGroup')
	 * 	);
	 *
 	 * This will cascade the validation through all related models and return
 	 * any errors found in any of the related models.
	 *
	 * @return	Errors	A class containing the errors resulting from validation.
	 */
	public function validate()
	{
		return;
		$gateways = $this->getGateways();

		$cascade = func_get_args();

		$errors = new Errors();

		foreach ($gateways as $gateway)
		{
			$errors->addErrors($gateway->validate());
		}

		$this->cascade(
			$cascade,
			'validate',
			function($relationship_name, $cascade_errors) use ($errors)
			{
				$errors->addErrors($cascade_errors);
			}
		);

		return $errors;
	}

	/**
	 * Save this model. Calls validation before saving to ensure that invalid
	 * data doesn't get saved, however, expects validation to have been called
	 * already and the errors handled.  Thus, if validation returns errors,
	 * save will throw an exception.  Accepts a related model cascade.
	 *
	 * @return 	void
	 *
	 * @throws	Exception	If the model fails to validate, an
	 * 						exception is thrown.  Validation should be called
	 * 						and any errors handled before attempting to save.
	 */
	public function save()
	{
		$this->_factory
			->get($this)
			->filter($this->getMetaData('primary_key'), $this->getId())
			->update();

		$save_related = array_unique($this->_dirty_relationships);

		foreach ($save_related as $name)
		{
			$edge = $this->getRelationshipEdge($name);

			$edge->sync($this, $this->getRelated($name));
		}

		$this->_dirty_relationships = array();

		return $this;
	}

	/**
	 * Save but always insert so that we can restore from a database backup.
	 */
	public function restore()
	{
		$this->markAsNew();
		return $this->save();
	}

	/**
	 * Delete this model.
	 *
	 * @return	void
	 */
	public function delete()
	{
		$this->_factory->get($this)->delete();
	}

	/**
	 *
	 */
	public function getGateways()
	{
		// the first step of instantiating them. only happens once
		$this->createGateways();

		// populate them and mark new values as dirty
		foreach (get_object_vars($this) as $property => $value)
		{
			// Ignore the ones we've hidden.
			if ($property[0] == '_')
			{
				continue;
			}

			foreach ($this->_gateways as $gateway)
			{
				if (property_exists($gateway, $property))
				{
					$gateway->$property = $value;

					if ($this->isDirty($property))
					{
						$gateway->setDirty($property);
					}
				}
			}
		}

		return $this->_gateways;
	}

	/**
	 *
	 */
	protected function createGateways()
	{
		if ( ! empty($this->_gateways))
		{
			return;
		}

		foreach ($this->getMetaData('gateway_names') as $gateway_name)
		{
			$gateway = $this->_factory->makeGateway($gateway_name);

			if ( ! is_null($this->_validation_factory))
			{
				$gateway->setValidationFactory($this->_validation_factory);
			}

			$this->_gateways[$gateway_name] = $gateway;
		}
	}

	/**
	 * Check for a relationship
	 *
	 * @param String  $name			Name of the relationship
	 * @param Integer $primary_key	Optional primary key of the related model
	 *
	 * @return Boolean
	 */
	public function hasRelated($name, $primary_key = NULL)
	{
		return $this->_related_models->has($name, $primary_key);
	}

	/**
	 * Get a relationship
	 *
	 * @param String $name		Name of the relationship
	 * @param Object $model		Object to relate to
	 *
	 * @return Object $this
	 */
	public function getRelated($to_name)
	{
		// no data? lazy query it
		if ( ! $this->_related_models->has($to_name))
		{
			// the result builder will set the relationship for us
			$this->_factory->get($this)
				->with($to_name)
				->all();
		}

		return $this->_related_models->get($to_name);
	}

	/**
	 * Set related data for a given relationship.
	 *
	 * @param String $name The name by which this relationship is
	 * 		identified.  In most cases this will be the name of the Model, but
	 * 		sometimes it will be specific to the relationship.  For example,
	 * 		ChannelEntry has an Author relationship (getAuthor(), setAuthor()).
	 * @param Mixed  $related      Collection or single Model
	 *
	 * @return Object $this
	 */
	public function setRelated($name, $related = NULL)
	{
		$edge = $this->getEdgeIfAllowed($name, 'set');

		$this->fillRelated($name, $related);

		if (isset($related))
		{
			$edge->connect($this, $related);
		}

		$this->_dirty_relationships[] = $name;

		return $this;
	}

	/**
	 * Add a related model
	 *
	 * This only works on many-to-many relationships.
	 *
	 * @param String  $name   Name of the relationship
	 * @param Object  $model  Object to relate to
	 */
	public function addRelated($name, Model $model = NULL)
	{
		$edge = $this->getEdgeIfAllowed($name, 'add');

		$this->_related_models->add($name, $model);

		$edge->connect($this, $model);

		$this->_dirty_relationships[] = $name;

		return $this;
	}

	/**
	 *
	 */
	public function removeRelated($name, $value)
	{
		$edge = $this->getEdgeIfAllowed($name, 'remove');

		$this->_related_models->remove($name, $value);

		$edge->disconnect($this, $value);

		$this->_dirty_relationships[] = $name;

		return $this;
	}

	/**
	 * Relate a model and persist it immediately.
	 *
	 * Only applicable to children in strong relationships.
	 */
	public function createRelated($name, $new_model)
	{
		$edge = $this->getEdgeIfAllowed($name, 'create');

		if ( ! ($new_model instanceof Model))
		{
			$new_model = $this->factory->new_model($edge->model, $new_model);
		}

		$this->addRelated($new_model);
		$new_model->save();

		return $this;
	}

	/**
	 * Unrelate a model and delete it immediately.
	 *
	 * Only applicable to children in strong relationships.
	 */
	public function deleteRelated($name, $value)
	{
		$edge = $this->getEdgeIfAllowed($name, 'delete');

		$this->removeRelated($name, $value);

		$value->delete();

		return $this;
	}

	/**
	 * Populate the relationship arrays. Assumes that the ids
	 * are already correct (as is the case when coming from a query).
	 */
	public function fillRelated($name, $value = NULL)
	{
		$edge = $this->getRelationshipEdge($name);

		if ($edge->is_collection)
		{
			if ($value instanceOf Collection)
			{
				$this->_related_models->setCollection($name, $value);
			}
			else
			{
				$this->_related_models->add($name, $value);
			}
		}
		else
		{
			$this->_related_models->setModel($name, $value);
		}

		return $this;
	}

	/**
	 *
	 */
	protected function getEdgeIfAllowed($name, $action)
	{
		$edge = $this->getRelationshipEdge($name);
		$edge->assertAcceptsAction($action);

		return $edge;
	}

	/**
	 *
	 */
	public function setRelationshipEdges($edges)
	{
		if (isset($this->_relationship_edges))
		{
			throw new \Exception('Cannot override relationships.');
		}

		$this->_relationship_edges = $edges;
	}

	/**
	 *
	 */
	public function hasRelationshipEdge($name)
	{
		return array_key_exists($name, $this->_relationship_edges);
	}

	/**
	 *
	 */
	public function getRelationshipEdge($name)
	{
		if ( ! $this->hasRelationshipEdge($name))
		{
			throw new \Exception("Unknown relationship '{$name}'");
		}

		return $this->_relationship_edges[$name];
	}

	/**
	 * Retrieve the model as an array
	 *
	 * @return Array Merged values of all gateways.
	 */
	public function toArray()
	{
		// extract all public vars from our gateways and flatten them
		$export = array();

		foreach (get_object_vars($this) as $key => $value)
		{
			if ($key[0] != '_')
			{
				// Call get to export the data as it is accessed, not as
				// it is stored in the database.
				$export[$key] = $this->__get($key);
			}
		}

		$export['related_models'] = array();

		// Allow for cascading export
		$cascade = func_get_args();

		foreach ($cascade as $relationship)
		{
			if ( ! is_array($relationship))
			{
				$relationship = array($relationship => array());
			}

			foreach ($relationship as $related_name => $related_cascade)
			{
				$relationship_getter = 'get' . $related_name;
				$related_data = $this->$relationship_getter();

				$export['related_models'][$related_name] = $related_data->toArray($related_cascade);
			}
		}

		return $export;
	}

	/**
	 *
	 */
	public function fromArray($data)
	{
		$data[$this->getMetaData('primary_key')] = NULL;

		if (isset($data['related_models']))
		{
			foreach ($data['related_models'] as $relationship_name => $values)
			{
				$models = new Collection();

				// todo use relationship bag directly?

				$relationship_getter = 'get' . $relationship_name;
				$relationship_meta = $this->$relationship_getter();

				foreach ($values as $related_data)
				{
					$models[] = $this->_factory
						->make($relationship_meta->to_model_name)
						->fromArray($related_data);
				}

				$relationship_setter = 'set' . $relationship_name;
				$this->$relationship_setter($models);
			}

			unset($data['related_models']);
		}

		foreach ($data as $key => $value)
		{
			// we export with __get, so import with __set
			$this->__set($key, $value);
		}

		return $this;
	}

	/**
	 *
	 */
	public function toJson()
	{
		$data = call_user_func_array(array($this, 'toArray'), func_get_args());

		$dumper = new namespace\Serializers\JsonSerializer();
		return $dumper->serialize($model, $data); // idea: make toArray cascade compatible?
	}

	/**
	 *
	 */
	public function fromJson($model_json)
	{
		$dumper = new namespace\Serializers\JsonSerializer();
		$dumper->unserialize($this, $model_json);
	}

	/**
	 *
	 */
	public function toXml()
	{
		$dumper = new namespace\Serializers\XmlSerializer();
		return $dumper->serialize($this, func_get_args()); // idea: make toArray cascade compatible?
	}

	/**
	 *
	 */
	public function fromXml($model_xml)
	{
		$dumper = new namespace\Serializers\XmlSerializer();
		return $dumper->unserialize($this, $model_xml);
	}
}
