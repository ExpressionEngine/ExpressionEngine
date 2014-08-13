<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Core\Validation\ValidationFactoryInterface;
use EllisLab\ExpressionEngine\Core\Validation\Error\ValidationError;

use EllisLab\ExpressionEngine\Model\Error\Errors;


/**
 * Base Gateway Class
 *
 * This is the base class for all database table Gateways in ExpressionEngine.
 * It provides basic CRUD operations against a single database table.  An
 * instance of an Gateway represents a single row in the represented table. It
 * tracks which properties are "dirty" (have been changed since loading) and
 * only validates/saves those properties that are dirty.
 */
abstract class RowDataGateway {

	/**
	 * Database connection
	 */
	protected $_db = NULL;

	/**
	 * Model builder instance.
	 */
	protected $_validation_factory = NULL;

	/**
	 * Array to track which properties have been modified, so that we
	 * only save or validate those that need it.
	 */
	protected $_dirty = array();

	/**
	 * Construct an gateway.  Initialize it with the Depdency Injection object
	 * and, optionally, with an array of data from the database.
	 *
	 * @param	mixed[]	$data	(Optional.) An array of data to be used to
	 * 		initialize the Gateway's public properties.  Of the form
	 * 		'property_name' => 'value'.
	 */
	public function __construct(array $data = array())
	{

		foreach ($data as $property => $value)
		{
			if (property_exists($this, $property))
			{
				$this->{$property} = $value;
			}
		}

		if (function_exists('ee'))
		{
			$this->setConnection(ee()->db);
		}
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
		$method = $this->propertyToMethod($name, 'get');
		if (method_exists($this, $method))
		{
			return $this->$method();
		}

		if (property_exists($this, $name) && strpos($name, '_') !== 0)
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
		$method = $this->propertyToMethod($name, 'set');
		if (method_exists($this, $method))
		{
			return $this->$method($value);
		}

		if (property_exists($this, $name) && strpos($name, '_') !== 0)
		{
			$this->{$name} = $value;
			return;
		}

		throw new \InvalidArgumentException('Attempt to access a non-existent property "' . $name . '" on ' . get_called_class());
	}

	private function propertyToMethod($name, $method)
	{
		$split_name = explode('_', $name);
		foreach($split_name as $key => $name_part)
		{
			$split_name[$key] = ucfirst($name_part);
		}
		$name = implode($split_name);
		return $method . $name;
	}

	/**
	 * Get Meta Data
	 *
	 * Get a piece of meta data on this gateway.  If no key is given, then all
	 * meta data is returned.  The meta data available is:
	 *
	 * 	table_name			string	-  The name of the database table that is
	 * 		linked to this gateway.  Is returned as a single string.
	 * 	primary_key			string  - The name of the primary key of the linked
	 * 		table.
	 * 	related_gateways	mixed[] - Information on all gateways that have
	 * 		some sort of relationship to this gateway.  Returned as an array of
	 * 		the form:
	 * 			'this_gateways_key' => array(
	 * 				'gateway' => 'GatewayName',
	 * 				'key' => 'related_gateways_key',
	 * 				'pivot_table' => 'pivot_table_name',
	 * 				'pivot_key' => 'this_gateways_key_in_pivot_table'
	 * 				'pivot_foreign_key' => 'related_gateways_key_in_pivot_table'
	 * 			)
	 * 	validation_rules	mixed[] - Validation rules assigned to each
	 * 		property of this gateway.  Returned as an array of
	 * 		property => rule string pairs.  Where a rule string is a pipe
	 * 		separated list of rule names.
	 *
	 * @param	mixed	$key	Which piece of meta data do you want? Available
	 * 				values are 'table_name', 'primary_key', 'related_gateways'
	 * 				and 'validation_rules'.
	 *
	 * @return	mixed[]|mixed	The requested meta data.
	 */
	public static function getMetaData($key)
	{
		if ($key === 'field_list')
		{
			$raw_fields = get_class_vars(get_called_class());
			$fields = array();
			foreach($raw_fields as $field => $default)
			{
				if (strpos($field, '_') === 0)
				{
					continue;
				}
				$fields[] = $field;
			}
			return $fields;
		}

		$property = '_' . $key;
		if ( ! isset (static::$$property))
		{
			return NULL;
		}

		return static::$$property;
	}

	/**
	 * Mark a Property as Dirty
	 *
	 * Marks a property on this gateway as having been modified and needing
	 * validation on saving.  If Gateway::save() is called, the property will
	 * be validated and any validation errors will result in an exception
	 * being thrown.
	 *
	 * @param	string	$property	The name of the property which is dirty.
	 * 		Must be a valid property defined on the gateway.
	 *
	 * @return void
	 */
	public function setDirty($property)
	{
		$this->_dirty[$property] = TRUE;
		return $this;
	}

	/**
	 * Set the validation factory
	 *
	 * @param   Optional validation object that implements the validation
	 * 			factory interface
	 */
	public function setValidationFactory(ValidationFactoryInterface $validation_factory)
	{
		$this->_validation_factory = $validation_factory;
	}

	/**
	 * Validate the Gateway
	 *
	 * Vaildate the gateway prior to saving based on validation rules set on
	 * the {$property}_validation properties.
	 *
	 * @return	Errors 	An object containing any errors generated by failed
	 * 				validation.  If no errors were generated, then
	 * 				Errors::hasErrors() will return false.
	 */
	public function validate($errors = NULL)
	{
		$errors = $errors ?: new Errors();

		// no validation hooked up
		if ( ! isset($this->_validation_factory))
		{
			return $errors;
		}

		// Nothing to validate!
		if (empty($this->_dirty))
		{
			return $errors;
		}

		$validation_rules = static::getMetaData('validation_rules');

		// Nothing to validate.
		if ($validation_rules === NULL)
		{
			return $errors;
		}

		foreach ($this->_dirty as $property => $dirty)
		{
			if (isset($validation_rules[$property]))
			{
				$validator = $this->_validation_factory->getValidator();

				if ( ! $validator->validate($validation_rules[$property], $this->$property))
				{
					foreach ($validator->getFailedRules() as $rule)
					{
						$errors->addError(new ValidationError($property, $rule));
					}
				}
			}
		}

		return $errors;
	}

	/**
	 * Save this Gateway
	 *
	 * Saves this Gateway to the database.  The Gateway represents a single row
	 * in its database table, and saving will result in it either being
	 * updated or inserted depending on whether its primary_key has been set.
	 *
	 * @throws Exception	If validation fails, then an Exception will be
	 * 		thrown.
	 *
	 * @return void
	 */
	public function save()
	{
		// Nothing to save!
		if (empty($this->_dirty))
		{
			return;
		}

		$save_array = array();
		foreach ($this->_dirty as $property => $dirty)
		{
			$save_array[$property] = $this->{$property};
		}

		$id_name = static::getMetaData('primary_key');
		if (isset($this->{$id_name}))
		{
			$this->_db->where($id_name, $this->{$id_name});
			$this->_db->update(static::getMetaData('table_name'), $save_array);
		}
		else
		{
			$this->_db->insert(static::getMetaData('table_name'), $save_array);
			$this->{$id_name} = $this->_db->insert_id();
		}
	}

	/**
	 * Like save, but always insert so that we can restore from
	 * a database backup.
	 */
	public function restore()
	{
		// Nothing to save!
		if (empty($this->_dirty))
		{
			return;
		}

		$save_array = array();
		foreach ($this->_dirty as $property => $dirty)
		{
			$save_array[$property] = $this->{$property};
		}

		$id_name = static::getMetaData('primary_key');
		$this->_db->insert(static::getMetaData('table_name'), $save_array);
	}

	/**
	 *
	 */
	public function delete()
	{
		$primary_key = static::getMetaData('primary_key');
		if ( ! isset($this->{$primary_key}))
		{
			throw new ModelException('Attempt to delete an Gateway with out an attached ID!');
		}

		$this->_db->delete(
			static::getMetaData('table_name'),
			array($primary_key => $this->{$primary_key})
		);
	}

	/**
	 * Set the current db connection.
	 */
	public function setConnection($db)
	{
		$this->_db = $db;
	}
}
