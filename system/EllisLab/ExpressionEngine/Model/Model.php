<?php
namespace EllisLab\ExpressionEngine\Model;

use EllisLab\ExpressionEngine\Model\Error\Errors;
use EllisLab\ExpressionEngine\Model\Query\QueryBuilder;
use EllisLab\ExpressionEngine\Model\Relationship\RelationshipBag;
use EllisLab\ExpressionEngine\Model\Relationship\RelationshipData;
use EllisLab\ExpressionEngine\Model\Relationship\RelationshipFluentBuilder;

use EllisLab\ExpressionEngine\Model\ModelAliasService;


/**
 * The base Model class
 */
abstract class Model {

	protected static $_meta = array();

	protected $builder = NULL;

	protected $alias_service = NULL;

	/**
	 * The database gateway object for the related database table.
	 */
	protected $gateways = array();

	/**
	 *
	 */
	protected $related_models = array();

	/**
	 *
	 */
	protected $dirty = array();

	/**
	 * Initialize this model with a set of data to set on the gateway.
	 *
	 * @param \EllisLab\ExpressionEngine\Model\ModelBuilder
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
	public function __construct(ModelBuilder $builder, ModelAliasService $alias_service, array $data = array(), $dirty = TRUE)
	{
		$this->builder = $builder;
		$this->alias_service = $alias_service;

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

		$this->related_models = new RelationshipBag();
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
		$this->dirty[$property] = TRUE;
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
		return (isset($this->dirty[$property]) && $this->dirty[$property]);
	}

	/**
	 * Get the model metadata
	 *
	 * @param String $key Metadata key name [optional]
	 * @return Mixed Value for $key or full metadata array
	 */
	public static function getMetaData($key = NULL)
	{
		if (empty(static::$_meta))
		{
			throw new \UnderflowException('No meta data set for ' . get_called_class());
		}

		if ( ! isset($key))
		{
			return static::$_meta;
		}

		// If the key is not set, and is not an optional key such as validation_rules,
		// throw an exception.
		if ( ! isset (static::$_meta[$key]) && ! in_array($key, array('validation_rules', 'cascade')))
		{
			throw new \DomainException('Missing meta data, "' . $key . '", in ' . get_called_class());
		}
		else if ( ! isset (static::$_meta[$key]))
		{
			return NULL;
		}

		return static::$_meta[$key];
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

		foreach ($this->gateways as $gateway)
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

		foreach($this->gateways as $gateway)
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

		foreach($this->gateways as $gateway)
		{
			$gateway->restore();
		}

		$this->cascade($cascade, 'restore');
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

		foreach($this->gateways as $gateway)
		{
			$gateway->delete();
		}

		$this->cascade($cascade, 'delete');
	}

	protected function map()
	{
		if (empty($this->gateways))
		{
			foreach (static::getMetaData('gateway_names') as $gateway_name)
			{
				$this->gateways[$gateway_name] = $this->builder->makeGateway($gateway_name);
			}
		}

		foreach (get_object_vars($this) as $property => $value)
		{
			// Ignore the ones we've hidden.
			if (strpos($property, '_') === 0)
			{
				continue;
			}

			foreach ($this->gateways as $gateway)
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
	 * Create a one-to-one relationship
	 *
	 * @param String $to_model_name	Name of the model to relate to
	 * @param String $this_key		Name of the relating key
	 * @param String $that_key		Name of the key on the related model
	 * @param String $name			The name of the method on the calling model
	 *
	 * @return Relationship object or related data
	 */
	public function oneToOne($to_model_name, $to_key = NULL)
	{
		return $this->newRelationshipBuilder('one-to-one')
			->to($to_model_name, $to_key);
	}

	/**
	 * Create a many-to-one relationship
	 *
	 * @param String $to_model_name	Name of the model to relate to
	 * @param String $this_key		Name of the relating key
	 * @param String $that_key		Name of the key on the related model
	 * @param String $name			The name of the method on the calling model
	 *
	 * @return Relationship object or related data
	 */
	public function manyToOne($to_model_name, $to_key = NULL)
	{
		return $this->newRelationshipBuilder('many-to-one')
			->to($to_model_name, $to_key);
	}

	/**
	 * Create a one-to-many relationship
	 *
	 * @param String $to_model_name	Name of the model to relate to
	 * @param String $this_key		Name of the relating key
	 * @param String $that_key		Name of the key on the related model
	 * @param String $name			The name of the method on the calling model
	 *
	 * @return Relationship object or related data
	 */
	public function oneToMany($to_model_name, $to_key = NULL)
	{
		return $this->newRelationshipBuilder('one-to-many')
			->to($to_model_name, $to_key);
	}

	/**
	 * Create a many-to-many relationship
	 *
	 * @param String $to_model_name	Name of the model to relate to
	 * @param String $this_key		Name of the relating key
	 * @param String $that_key		Name of the key on the related model
	 * @param String $name			The name of the method on the calling model
	 *
	 * @return Relationship object or related data
	 */
	public function manyToMany($to_model_name, $to_key = NULL)
	{
		return $this->newRelationshipBuilder('many-to-many')
			->to($to_model_name, $to_key);
	}


	// alias the more human relationship names
	public function hasOne($to_model, $to_key = NULL)
	{
		return $this->oneToOne($to_model, $to_key);
	}

	public function belongsTo($to_model, $to_key = NULL)
	{
		return $this->oneToOne($to_model, $to_key);
	}

	public function hasMany($to_model, $to_key = NULL)
	{
		return $this->oneToMany($to_model, $to_key);
	}

	public function belongsToMany($to_model, $to_key = NULL)
	{
		return $this->manyToOne($to_model, $to_key);
	}

	public function hasAndBelongsToMany($to_model, $to_key = NULL)
	{
		return $this->manyToMany($to_model, $to_key);
	}


	/**
	 * Set related data for a given relationship.
	 *
	 * @param String $model_name The name by which this relationship is
	 * 		identified.  In most cases this will be the name of the Model, but
	 * 		sometimes it will be specific to the relationship.  For example,
	 * 		ChannelEntry has an Author relationship (getAuthor(), setAuthor()).
	 * @param Mixed  $value      Collection or single Model
	 *
	 * @return void
	 */
	public function setRelated($relationship_key, $value)
	{
		$this->related_models->set($relationship_key, $value);
		return $this;
	}

	public function hasRelated($relationship_key, $primary_key = NULL)
	{
		return $this->related_models->has($relationship_key, $primary_key);
	}

	public function addRelated($relationship_key, $model)
	{
		$this->related_models->add($relationship_key, $model);
		return $this;
	}

	private function newRelationshipBuilder($type)
	{
		$data = new RelationshipData(
			$this->related_models,
			$this->alias_service,
			$this->builder
		);

		$fluent = new RelationshipFluentBuilder($this, $data);
		return $fluent->type($type);
	}

	/**
	 * Retrieve the model as an array
	 *
	 * @return Array Merged values of all gateways.
	 */
	public function toArray()
	{
		// extract all public vars from our gateways and flatten them
		$keys = array_keys(call_user_func_array(
			'array_merge',
			array_map('get_object_vars', $this->gateways)
		));

		// Combine the keys with their value as controlled by __get
		// Without array_keys the above gives us our values, but we
		// need to be consistent with any potential getters.
		return array_combine(
			$keys,
			array_map(array($this, '__get'), $keys)
		);
	}

	public function toJson()
	{
		$dumper = new namespace\Serializers\JsonSerializer();
		return $dumper->serialize($figure_this_out);
	}

	public function fromJson($model_json)
	{
		$dumper = new namespace\Serializers\JsonSerializer();
		$dumper->unserialize($this, $model_json);
	}

	public function toXml()
	{
		$cascade = func_get_args(); // don't forget this!
		$dumper = new namespace\Serializers\XmlSerializer();
		return $dumper->serialize($figure_this_out); // idea: make toArray cascade compatible?
	}

	public function fromXml($model_xml)
	{
		$dumper = new namespace\Serializers\XmlSerializer();
		$dumper->unserialize($this, $model_xml);
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
		foreach($this->related_models as $relationship_name=>$models)
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
