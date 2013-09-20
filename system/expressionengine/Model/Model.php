<?php

/**
 * The base Model class 
 */
abstract class Model {
	/**
	 * The database entity object for the related database table.
	 */
	protected $entity;

	/**
	 * An array storing the names of modified properties. Used in validation.
	 */	
	private $dirty = array();
	
	/**
	 * Initialize this model with a set of data to set on the entity.
	 *
	 * @param	mixed[]	$data	An array of initial property values to
	 * 						set on this model.  The array indexes must
	 * 						be valid properties on this model's entity.
	 */	
	public function __construct($entity_name, array $data=array()) 
	{
		$this->entity = new $entity_name();
		foreach ($data as $key => $value) 
		{
			$this->{$key} = $value;
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
		if (property_exists($name, $this->entity))
		{
			return $this->entity->{$name};
		}
		throw new NonExistentPropertyException('Attempt to access a non-existent property on ' . __CLASS__);
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
		if (property_exists($name, $this->entity))
		{
			$this->entity->{$name} = $value;
			$this->entity->dirty[$name] = TRUE;
			return;
		}
	
		throw new NonExistentPropertyException('Attempt to access a non-existent property on ' . __CLASS__); 
	}

	/**
	 * Validate this model's data for saving.
	 *
	 * @return	Errors	A class containing the errors resulting from validation.
	 */
	public function validate()
	{
		return new Errors(); 
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
		if ( ! $this->validate()) 
		{
			throw new ModelException('Model failed to validate on save call!');
		}
			
		$this->entity->save();
	}


	/**
	 * Delete this model.
	 *
	 * @return	void
	 */
	public function delete()
	{
		$this->entity->delete();
	}
}
