<?php
namespace EllisLab\ExpressionEngine\Model;

use EllisLab\ExpressionEngine\Model\Error\Errors;
use EllisLab\ExpressionEngine\Model\Query\QueryBuilder;
use EllisLab\ExpressionEngine\Model\Relationship\RelationshipBag;
use EllisLab\ExpressionEngine\Model\Relationship\RelationshipQuery;

use EllisLab\ExpressionEngine\Model\ModelAliasService;


/**
 * The base Model class
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
	 *
	 */
	protected static $_key_map = array();

	/**
	 * Optional keys
	 */
	protected static $_polymorph = NULL;
	protected static $_cascade = array();
	protected static $_validation_rules = array();
	protected static $_relationships = array();


	/**
	 *
	 */
	protected $_factory = NULL;

	/**
	 *
	 */
	protected $_alias_service = NULL;

	/**
	 * The database gateway object for the related database table.
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
	 * Initialize this model with a set of data to set on the gateway.
	 *
	 * @param \EllisLab\ExpressionEngine\Model\ModelFactory
	 * @param \Ellislab\ExpressionEngine\Model\ModelAliasService
	 * @param	mixed[]	$data	An array of initial property values to set on
	 * 		this model.  The array indexes must be valid properties on this
	 * 		model's gateway.
	 * @param	boolean	$dirty	(Optional) Should we mark the initial data as
	 * 		dirty?  If TRUE, all initial data that the model is sent will be
	 * 		marked as dirty data that will be validated and saved on the next
	 * 		save call.  Otherwise, it will be treated as clean and assumed
	 * 		to have come from the database.
	 */
	public function __construct(ModelFactory $factory, ModelAliasService $alias_service, array $data = array(), $dirty = TRUE)
	{
		$this->_factory = $factory;
		$this->_alias_service = $alias_service;

		foreach ($data as $property => $value)
		{
			if (property_exists($this, $property))
			{
				$this->{$property} = $value;
				if ($dirty)
				{
					$this->setDirty($property);
				}
			}
		}

		$this->_related_models = new RelationshipBag();
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

		if (property_exists($this, $name) && strpos('_', $name) !== 0)
		{
			return $this->{$name};
		}

		throw new \InvalidArgumentException('Attempt to access a non-existent property, "' . $name . '", on ' . get_called_class());
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

		if (property_exists($this, $name) && strpos('_', $name) !== 0)
		{
			$this->{$name} = $value;
			$this->setDirty($name);
			return;
		}

		throw new \InvalidArgumentException('Attempt to access a non-existent property "' . $name . '" on ' . get_called_class());
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

		$value = static::$$property;

		$should_be_array = is_array(self::$$property);

		$parent = get_parent_class(get_called_class());

		// if there's inheritance on an array or unset value,
		// then we need to dig through the parent classes. ugh.
		if ($parent && (empty($value) || $should_be_array))
		{
			$parent_value = $parent::getMetaData($key);

			if ($should_be_array)
			{
				$value = array_merge($value, $parent_value);
			}
			else
			{
				$value = $parent_value;
			}
		}

		// empty but not optional? If at top, throw error
//		if (empty($value) && ! in_array($key, array('validation_rules', 'cascade', 'polymorph')))
//		{
//			throw new \DomainException('Missing meta data, "' . $key . '", in ' . get_called_class());
//		}

		return $value;
	}

	/**
	 * Get the primary id for this model
	 *
	 * @return int	Primary key value of the model
	 */
	public function getId()
	{
		$primary_key = static::getMetaData('primary_key');
		return $this->{$primary_key};
	}

	/**
	 * Validate this model's data for saving.  May cascade the validation
	 * through any set of related models using the same grouping language
 	 * that is used in the query builder.  For example:
	 *
	 * $entry = $qb->get('ChannelEntry')
	 *		->with(
	 * 			'Channel',
	 * 			array('Member'=>'MemberGroup'),
	 * 			array('Categories' => 'CategoryGroup')
	 *		)
 	 * 		->filter('MemberGroup.member_group_id', 5)
	 *		->first();
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
		$this->map();

		$cascade = func_get_args();

		$errors = new Errors();

		foreach ($this->_gateways as $gateway)
		{
			$errors->addErrors($gateway->validate());
		}

		$this->cascade($cascade, 'validate',
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
		$this->map();
		$cascade = func_get_args();

		$errors = call_user_func_array(array($this, 'validate'), $cascade);
		if ($errors->exist())
		{
			throw new \Exception('Model failed to validate on save call!');
		}

		foreach($this->_gateways as $gateway)
		{
			$gateway->save();
		}

		$this->cascade($cascade, 'save');
	}

	/**
	 *  Save but always insert so that we can restore from a database backup.
	 */
	public function restore()
	{
		$this->map();
		$cascade = func_get_args();

		$errors = call_user_func_array(array($this, 'validate'), $cascade);
		if ($errors->exist())
		{
			throw new \Exception('Model failed to validate on restore call!');
		}

		foreach($this->_gateways as $gateway)
		{
			$gateway->restore();
		}

		$this->cascade($cascade, 'restore');
		return $this;
	}

	/**
	 * Delete this model.
	 *
	 * @return	void
	 */
	public function delete()
	{
		$this->map();

		$cascade = func_get_args();

		foreach($this->_gateways as $gateway)
		{
			$gateway->delete();
		}

		$this->cascade($cascade, 'delete');
	}

	protected function map()
	{
		if (empty($this->_gateways))
		{
			foreach (static::getMetaData('gateway_names') as $gateway_name)
			{
				$this->_gateways[$gateway_name] = $this->_factory->makeGateway($gateway_name);
			}
		}

		foreach (get_object_vars($this) as $property => $value)
		{
			// Ignore the ones we've hidden.
			if (strpos($property, '_') === 0)
			{
				continue;
			}

			foreach ($this->_gateways as $gateway)
			{
				if (property_exists($gateway, $property))
				{
					$gateway->{$property} = $value;
					if ($this->isDirty($property))
					{
						$gateway->setDirty($property);
					}
				}
			}
		}
	}

	/**
	 *
	 * 		'Channel',
	 *		array('Member' => array('MemberGroup'=>'Members')),
	 * 		array('Category' => 'CategoryGroup')
	 */
	protected function cascade($cascade, $method, $callback = NULL)
	{
		foreach($cascade as $relationship_name)
		{
			if (is_array($relationship_name))
			{
				$this->cascadeRecursive($relationship_name, $method, $callback);
			}
			else
			{
				$relationship_method = 'get' . $relationship_name;
				$models = $this->$relationship_method();

				foreach ($models as $model)
				{
					if ($callback !== NULL)
					{
						$callback($relationship_name, $model->$method());
					}
					else
					{
						$model->$method();
					}
				}
			}
		}
	}

	protected function cascadeRecursive($cascade, $method, $callback = NULL)
	{
		foreach ($cascade as $from_relationship => $to_relationship)
		{
			$method = 'get' . $from_relationship;
			$models = $this->$method();

			foreach ($models as $model)
			{
				if (is_array($to_relationship))
				{
					$model->cascadeRecursive($to_relationship, $method, $callback);
				}
				else
				{
					$relationship_method = 'get' . $to_relationship;
					$to_models = $model->$relationship_method();

					foreach ($to_models as $to_model)
					{
						if ($callback !== NULL)
						{
							$callback($to_relationship, $to_model->$method());
						}
						else
						{
							$to_model->$method();
						}
					}
				}
			}
		}
	}

	/**
	 * Get a relationship
	 *
	 * @param String $name		Name of the relationship
	 * @param Object $model		Object to relate to
	 *
	 * @return Object $this
	 */
	protected function getRelated($to_name)
	{
		$info = $this->getRelationshipInfo($to_name);

		// if we already have data, we return it.
		if ($this->_related_models->has($to_name))
		{
			return $this->_related_models->get($to_name, $info->is_collection);
		}

		$query = new RelationshipQuery($this, $info);

		// No data, if no id as well, then we're in a query
		// or importing (@see `fromArray()`). Will just return metadata
		if ($this->getId() === NULL)
		{
			return $query->eager($this->_alias_service);
		}

		return $query->lazy($this->_factory);
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
	 * Add a related model
	 *
	 * @param String  $name		Name of the relationship
	 * @param Object $model		Object to relate to
	 *
	 * @return Boolean
	 */
	public function addRelated($name, $model)
	{
		$this->_related_models->add($name, $model);

		return $this;
	}

	/**
	 * Set related data for a given relationship.
	 *
	 * @param String $name The name by which this relationship is
	 * 		identified.  In most cases this will be the name of the Model, but
	 * 		sometimes it will be specific to the relationship.  For example,
	 * 		ChannelEntry has an Author relationship (getAuthor(), setAuthor()).
	 * @param Mixed  $value      Collection or single Model
	 *
	 * @return void
	 */
	public function setRelated($name, $value)
	{
		$this->_related_models->set($name, $value);

		$info = $this->getRelationshipInfo($name);

		// update the related key
		switch ($info->type)
		{
			case 'one_to_one':
				if ($info->key != static::getMetaData('primary_key'))
				{
					$this->{$info->key} = $value->{$info->to_key};
				}

				$to_class = $info->to_class;

				if ($info->to_key != $to_class::getMetaData('primary_key'))
				{
					$value->{$info->to_key} = $this->{$info->key};
				}
				break;
			case 'one_to_many':
				foreach ($value as $model)
				{
					$value->{$info->to_key} = $this->{$info->key};
				}
				break;
			case 'many_to_one':
				$this->{$info->key} = $value->{$info->to_key};
				break;
			case 'many_to_many':
				// nada
				break;
		}

		return $this;
	}

	private function getRelationshipInfo($name)
	{
		$relationships = static::getMetaData('relationships');

		$data = $relationships[$name];
		$keys = array(
			'name'  => $name,
			'model'	=> $name,
			'type'	=> NULL,
			'key'	=> NULL,
			'to_key'=> NULL
		);

		// make sure all the keys are there - as null if not given
		$data = array_merge($keys, $data);

		$to_class = $this->_alias_service->getRegisteredClass($data['model']);

		// use a reasonable key structure
		switch ($data['type'])
		{
			case 'one_to_many': // default: primary key of the one side (e.g group_id for template groups and templates)
				$data['key']	= $data['key'] ?: static::getMetaData('primary_key');
				$data['to_key']	= $data['to_key'] ?: $data['key'];
				break;
			case 'many_to_one': // default: same as one_to_many, but looked up in the other direction
				$data['to_key'] = $data['to_key'] ?: $to_class::getMetaData('primary_key');
				$data['key']	= $data['key'] ?: $data['to_key'];
				break;
			case 'many_to_many': // default: both primary keys on pivot
				$data['key']	= $data['key'] ?: static::getMetaData('primary_key');
				$data['to_key'] = $data['to_key'] ?: $to_class::getMetaData('primary_key');
				break;
			case 'one_to_one': // default: opposing keys, typically means one needs to be declared
				$data['key']	= $data['key'] ?: $to_class::getMetaData('primary_key');
				$data['to_key']	= $data['to_key'] ?: static::getMetaData('primary_key');
				break;
		}

		// useful to know
		$data['to_class'] = $to_class;
		$data['is_collection'] = (substr($data['type'], -4) == 'many');

		return (object) $data;
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
				$relationship = $this->$relationship_getter();
				$export['related_models'][$related_name] = $relationship->toArray($related_cascade);
			}
		}

		return $export;
	}

	public function fromArray($data)
	{
		unset($data[static::getMetaData('primary_key')]);

		if (isset($data['related_models']))
		{
			foreach ($data['related_models'] as $relationship_name => $values)
			{
				$models = new Collection();

				$relationship_getter = 'get' . $relationship_name;
				$relationship_meta = $this->$relationship_getter();

				foreach ($values as $related_data)
				{
					$models[] = $this->_factory
						->make($relationship_meta->to_model_name)
						->fromArray($related_data);
				}

				$this->setRelated($relationship_name, $models);
			}

			unset($data['related_models']);
		}

		foreach ($data as $key => $value)
		{
			// we export with __get, so import with __set
			$this->__set($key, $value);
		}

		return $this->restore();
	}

	public function toJson()
	{
		$data = call_user_func_array(array($this, 'toArray'), func_get_args());

		$dumper = new namespace\Serializers\JsonSerializer();
		return $dumper->serialize($model, $data); // idea: make toArray cascade compatible?
	}

	public function fromJson($model_json)
	{
		$dumper = new namespace\Serializers\JsonSerializer();
		$dumper->unserialize($this, $model_json);
	}

	public function toXml()
	{
		$dumper = new namespace\Serializers\XmlSerializer();
		return $dumper->serialize($this, func_get_args()); // idea: make toArray cascade compatible?
	}

	public function fromXml($model_xml)
	{
		$dumper = new namespace\Serializers\XmlSerializer();
		return $this->fromArray($dumper->unserialize($this, $model_xml));
	}

/*
	public function testPrint($depth='')
	{
		if ($depth == "\t\t\t")
		{
			return;
		}
		$primary_key = static::getMetaData('primary_key');
		$model_name = substr(get_class($this), strrpos(get_class($this), '\\')+1);
		echo $depth . '=====' . $model_name . ': ' . $this->{$primary_key} . ' Obj(' . spl_object_hash($this) . ')'. "=====\n";
		foreach($this->_related_models as $relationship_name=>$models)
		{
			echo $depth . '----Relationship: ' . $relationship_name . "----\n";
			foreach($models as $model)
			{
				$model->testPrint($depth . "\t");
			}
			echo $depth . '---- END Relationship: ' . $relationship_name . "----\n";
		}
		echo $depth . '===== END ' . $model_name . ': ' . $this->{$primary_key} . "=====\n";
	}
*/
}
