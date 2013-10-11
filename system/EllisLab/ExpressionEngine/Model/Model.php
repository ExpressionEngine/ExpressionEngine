<?php
namespace EllisLab\ExpressionEngine\Model;

use EllisLab\ExpressionEngine\Service\Validation\ValidationResult as ValidationResult;

/**
 * The base Model class
 */
abstract class Model {

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
	public function __construct(array $data = array())
	{
		foreach (static::getMetaData('entity_names') as $entity_name)
		{
			$entity = QueryBuilder::getQualifiedClassName($entity_name);
			$this->entities[$entity_name] = new $entity($data);
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
				$entity->dirty[$name] = TRUE;
				return;
			}
		}

		throw new \InvalidArgumentException('Attempt to access a non-existent property on ' . __CLASS__);
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
		$key_map = static::getMetaData('key_map');
		$entity_name = $key_map[$primary_key];
		return $this->entities[$entity_name]->{$primary_key};
	}

	/**
	 * Validate this model's data for saving.
	 *
	 * @return	Errors	A class containing the errors resulting from validation.
	 */
	public function validate()
	{
		$validation = new ValidationResult();
		foreach ($this->entities as $entity)
		{
			$validation->addErrors($entity->validate());
		}
		return $validation;
	}

	/**
	 * Save this model. Calls validation before saving to ensure that invalid
	 * data doesn't get saved, however, expects validation to have been called
	 * already and the errors handled.  Thus, if validation returns errors,
	 * save will throw an exception.
	 *
	 * @return 	void
	 *
	 * @throws	InvalidDataException	If the model fails to validate, an
	 * 						exception is thrown.  Validation should be called
	 * 						and any errors handled before attempting to save.
	 */
	public function save()
	{
		$validation = $this->validate();
		if ($validation->failed())
		{
			throw new \Exception('Model failed to validate on save call!');
		}

		foreach($this->entities as $entity)
		{
			$entity->save();
		}
	}

	/**
	 * Delete this model.
	 *
	 * @return	void
	 */
	public function delete()
	{
		foreach($this->entities as $entity)
		{
			$entity->delete();
		}
	}

	/**
	 * Create a one-to-one relationship
	 *
	 * @param String $to_model_name	Name of the model to relate to
	 * @param String $this_key		Name of the relating key
	 * @param String $that_key		Name of the key on the related model
	 *
	 * @return Relationship object or related data
	 */
	public function oneToOne(
		$to_model_name, $this_key, $that_key = NULL, $name=NULL)
	{
		return $this->related(
			'one-to-one',
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
	 *
	 * @return Relationship object or related data
	 */
	public function manyToOne(
		$to_model_name, $this_key, $that_key = NULL, $name=NULL)
	{
		return $this->related(
			'many-to-one',
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
	 *
	 * @return Relationship object or related data
	 */
	public function oneToMany(
		$to_model_name, $this_key, $that_key = NULL, $name=NULL)
	{
		return $this->related(
			'one-to-many',
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
	 *
	 * @return Relationship object or related data
	 */
	public function manyToMany(
		$to_model_name, $this_key, $that_key = NULL, $name=NULL)
	{}

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
		$type, $to_model_name, $this_key, $that_key = NULL, $name=NULL)
	{
		// If we already have data, return it
		$relationship_key = (isset($name) ? $name : $to_model_name);
		if (array_key_exists($relationship_key, $this->related_models))
		{
			return $this->related_models[$to_model_name];
		}

		$relationship = new Relationship($this, $to_model_name, $type);

		// No id, we must be querying
		if ($this->getId() === NULL)
		{
			return $relationship->eagerLoad($this_key, $that_key);
		}

		return $relationship->lazyLoad($this_key, $that_key);
	}
}
