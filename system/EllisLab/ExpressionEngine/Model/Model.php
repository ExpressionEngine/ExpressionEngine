<?php
namespace EllisLab\ExpressionEngine\Model;

use EllisLab\ExpressionEngine\Service\Validation\ValidationResult;
use EllisLab\ExpressionEngine\Core\Dependencies;
use EllisLab\ExpressionEngine\Model\Query\QueryBuilder;
use EllisLab\ExpressionEngine\Model\Query\ModelRelationshipMeta;
use EllisLab\ExpressionEngine\Model\Collection;

/**
 * The base Model class
 */
abstract class Model {
	private $dependencies = NULL;

	protected static $meta = array();

	/**
	 * The database entity object for the related database table.
	 */
	protected $entities = array();

	/**
	 *
	 */
	protected $related_models = array();

	/**
	 * Initialize this model with a set of data to set on the entity.
	 *
	 * @param	mixed[]	$data	An array of initial property values to
	 * 						set on this model.  The array indexes must
	 * 						be valid properties on this model's entity.
	 */
	public function __construct(Dependencies $dependencies, array $data = array())
	{
		$this->dependencies = $dependencies;
		foreach (static::getMetaData('entity_names') as $entity_name)
		{
			$entity = QueryBuilder::getQualifiedClassName($entity_name);
			$this->entities[$entity_name] = new $entity($dependencies, $data);
		}
	}

	/**
	 * Pass through getter that allows properties to be gotten from this model
	 * but stored in the wrapped entity.
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

		foreach ($this->entities as $entity)
		{
			if (property_exists($entity, $name))
			{
				return $entity->{$name};
			}
		}

		throw new \InvalidArgumentException('Attempt to access a non-existent property on ' . __CLASS__);
	}

	/**
	 * Pass through setter that allows properties to be set on this model,
	 * but stored in the wrapped entity.
	 *
	 * @param	string	$name	The name of the property being set. Must be
	 * 						a valid property on the wrapped entity.
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

		foreach($this->entities as $entity)
		{
			if (property_exists($entity, $name))
			{
				$entity->{$name} = $value;
				$entity->setDirty($name);
				return;
			}
		}

		throw new \InvalidArgumentException('Attempt to access a non-existent property "' . $name . '" on ' . __CLASS__);
	}

	/**
	 * Get the model metadata
	 *
	 * @param String $key Metadata key name [optional]
	 * @return Mixed Value for $key or full metadata array
	 */
	public static function getMetaData($key = NULL)
	{
		if (empty(static::$meta))
		{
			throw new \UnderflowException('No meta data set for this class!');
		}

		if ( ! isset($key))
		{
			return static::$meta;
		}

		return static::$meta[$key];
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
		$cascade = func_get_args();

		$validation = new ValidationResult();

		foreach ($this->entities as $entity)
		{
			$validation->addErrors($entity->validate());
		}

		foreach($cascade as $model_name)
		{
			if (is_array($model_name))
			{
				$this->cascadeValidate($validation, $model_name);
			}
			else
			{
				$method = 'get' . $model_name;
				$models = $this->$method();

				foreach ($models as $model)
				{
					$model->validate();
				}
			}
		}


		return $validation;
	}

	/**
	 * Cascade validation
	 *
	 * Cascades validation into related classes.  Gets the array of a cascaded
	 * relation and recursively walks through that, validating. 
	 * 
	 * @param	Errors	$validation	The validation object to which cascaded
	 * 				errors should be added.
	 * @param	string[]	$model_names	The names of the models that you
	 * 				wish to cascade into in array format.  The array must
	 * 				be formatted in the following way:
	 * 					array('Model Related to $this' => 'Model related to <- that model')
	 * 
	 * @return	Errors	A class containing the errors resulting from validation.
	 *
	 */
	protected function cascadeValidate($validation, $model_names)
	{
		foreach ($model_names as $from_model_name => $to_model_name)
		{
			$method = 'get' . $from_model_name;
			$models = $this->$method();
		
			foreach ($models as $model)			
			{
				if (is_array($to_model_name))
				{
					$validation->addErrors($model->cascadeValidate($to_model_name));
				}	
				else
				{
					$to_method = 'get' . $to_model_name;
					$to_models = $model->$to_method();

					foreach ($to_models as $to_model)
					{
						$validation->addErrors($to_model->validate());
					}
				}
			}
		}

		return $validation;
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
		$cascade = func_get_args();

		$validation = call_user_func_array(array($this, 'validate'), $cascade);
		if ($validation->failed())
		{
			throw new \Exception('Model failed to validate on save call!');
		}

		foreach($this->entities as $entity)
		{
			$entity->save();
		}

		// Handle Cascade
		foreach($cascade as $model_name)
		{
			if (is_array($model_name))
			{
				$this->cascadeSave($model_name);
			}
			else
			{
				$method = 'get' . $model_name;
				$models = $this->$method();

				foreach ($models as $model)
				{
					$model->save();
				}
			}
		}
	}

	/**
	 * Cascade save
 	 *
	 * Cascades saving through related Models.  Works the same as
	 * Model::cascadeValidate(), but doesn't return anything.
	 * 
	 * @param	string[]	$model_names	An array of Model names to be saved
	 * 				in the format of array('from_model' => 'to_model').
	 * 
 	 * @return	void
	 *
	 * @throws	Exception	If any of the related models fails to validate
	 * 				it will throw an exception. 
	 */
	protected function cascadeSave($model_names)
	{
		foreach ($model_names as $from_model_name => $to_model_name)
		{
			$method = 'get' . $from_model_name;
			$models = $this->$method();
		
			foreach ($models as $model)			
			{
				if (is_array($to_model_name))
				{
					$model->cascadeSave($to_model_name);
				}	
				else
				{
					$to_method = 'get' . $to_model_name;
					$to_models = $model->$to_method();

					foreach ($to_models as $to_model)
					{
						$to_model->save();
					}
				}
			}
		}
	}


	/**
	 * Delete this model.
	 *
	 * @return	void
	 */
	public function delete()
	{
		$cascade = func_get_args();

		foreach($this->entities as $entity)
		{
			$entity->delete();
		}

		// Handle Cascade
		foreach($cascade as $model_name)
		{
			if (is_array($model_name))
			{
				$this->cascadeSave($model_name);
			}
			else
			{
				$method = 'get' . $model_name;
				$models = $this->$method();

				foreach ($models as $model)
				{
					$model->save();
				}
			}
		}
	}

	/**
	 *
	 */
	protected function cascadeDelete($model_names)
	{
		foreach ($model_names as $from_model_name => $to_model_name)
		{
			$method = 'get' . $from_model_name;
			$models = $this->$method();
		
			foreach ($models as $model)
			{
				if (is_array($to_model_name))
				{
					$model->cascadeDelete($to_model_name);
				}	
				else
				{
					$to_method = 'get' . $to_model_name;
					$to_models = $model->$to_method();

					foreach ($to_models as $to_model)
					{
						$to_model->delete();
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
	public function oneToOne(
		$to_model_name, $this_key, $that_key = NULL, $name=NULL)
	{
		return $this->related(
			ModelRelationshipMeta::TYPE_ONE_TO_ONE,
			$to_model_name,
			$this_key,
			$that_key,
			$name
		);
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
	public function manyToOne(
		$to_model_name, $this_key, $that_key = NULL, $name=NULL)
	{
		return $this->related(
			ModelRelationshipMeta::TYPE_MANY_TO_ONE,
			$to_model_name,
			$this_key,
			$that_key,
			$name
		);
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
	public function oneToMany(
		$to_model_name, $this_key, $that_key = NULL, $name=NULL)
	{
		return $this->related(
			ModelRelationshipMeta::TYPE_ONE_TO_MANY,
			$to_model_name,
			$this_key,
			$that_key,
			$name
		);
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
	public function manyToMany(
		$to_model_name, $this_key, $that_key = NULL, $name=NULL)
	{
		return $this->related(
			ModelRelationshipMeta::TYPE_MANY_TO_MANY,
			$to_model_name,
			$this_key,
			$that_key,
			$name
		);
			
	}

	/**
	 * Retrieve the model as an array
	 *
	 * @return Array Merged values of all entities.
	 */
	public function toArray()
	{
		// extract all public vars from our entities and flatten them
		$keys = array_keys(call_user_func_array(
			'array_merge',
			array_map('get_object_vars', $this->entities)
		));

		// Combine the keys with their value as controlled by __get
		// Without array_keys the above gives us our values, but we
		// need to be consistent with any potential getters.
		return array_combine(
			$keys,
			array_map(array($this, '__get'), $keys)
		);
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
		$this->related_models[$relationship_key] = $value;
		return $this;
	}

	public function hasRelated($relationship_key, $primary_key=NULL)
	{
		if ( ! isset($this->related_models[$relationship_key]))
		{
			return FALSE;
		}

		if ($primary_key !== NULL)
		{
			$ids = $this->related_models[$relationship_key]->getIds();
			return in_array($primary_key, $ids);
		}

		return TRUE;
	}

	public function addRelated($relationship_key, $model)
	{
		if ( ! isset($this->related_models[$relationship_key]))
		{
			$this->related_models[$relationship_key] = new Collection();
		}
		$this->related_models[$relationship_key][] = $model;
		return $this;
	}

	/**
	 * Helper method used when setting up a relationship
	 *
	 * @param String $type			Relationship type (dash-words)
	 * @param String $to_model_name	Name of the model to relate to in
	 * 		StudlyCaps (as you would use it in code). This will be used as
	 * 		the relationship name if no other name is given.
	 * @param String $this_key		Name of the relating key
	 * @param String $to_key		Name of the key on the related model
	 * @param String $name			The name of the Relationship, when
	 * 		different from the name of the model.  For example ChannelEntry has
	 * 		an Author (getAuthor(), setAuthor()). 
	 *
	 * @return Relationship object or related data
	 */
	private function related(
		$type, $to_model_name, $this_key, $to_key = NULL, $name=NULL)
	{
		// If we already have data, return it
		$relationship_key = (isset($name) ? $name : $to_model_name);
		if (array_key_exists($relationship_key, $this->related_models))
		{
			return $this->related_models[$to_model_name];
		}

		// At this point, if we don't have a to_key we'll need to default
		// to the primary key of the target model.
		if ( ! isset($to_key))
		{
			$to_model_class = QueryBuilder::getQualifiedClassName($to_model_name);
			$to_key = $to_model_class::getMetaData('primary_key');
		}

		// Eager Load
		// 	If no id is set, then we're doing an eager load during a
		// 	query.  This model is probably mostly empty.
		if ($this->getId() === NULL)
		{
			$relationship = new ModelRelationshipMeta(
				$type,
				$relationship_key,
				array(
					'model_class' => get_class($this),
					'model_name' => substr(get_class($this), strrpos(get_class($this), '\\')+1),
					'key' => $this_key
				),
				array(
					'model_class' => QueryBuilder::getQualifiedClassName($to_model_name),
					'model_name' => $to_model_name,
					'key' => $to_key
				)
			);
			return $relationship;
		}

		// Lazy Load
		// 	Otherwise, if we haven't hit one of the previous cases, then this
		// 	is a lazy load on an existing model.
		$query = $this->dependencies->getQueryBuilder()->get($to_model_name);
		$query->filter($to_model_name . '.' . $to_key, $this->$this_key);

		if ($type == 'one-to-one' OR $type == 'many-to-one')
		{
			$result = $query->first();
		}
		else
		{
			$result = $query->all();
		}

		$this->setRelated($relationship_key, $result);
		return $result;
	}

	public function testPrint($depth='')
	{
		$primary_key = static::getMetaData('primary_key');
		echo $depth . '=====' . substr(get_class($this), strrpos(get_class($this), '\\')+1) . ': ' . $this->{$primary_key} . "=====\n";
		$depth .= "\t";
		foreach($this->related_models as $relationship_name=>$models)
		{
			echo $depth . 'Relationship: ' . $relationship_name . "\n";
			foreach($models as $model)
			{
				$model->testPrint($depth);
			}
		}
		echo "\n";

	}
}
