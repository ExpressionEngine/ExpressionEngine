<?php

namespace EllisLab\ExpressionEngine\Service\Model\Column;

use InvalidArgumentException;

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
 * ExpressionEngine Composite Column
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
abstract class Composite implements Column {

	abstract protected function serialize($data);

	abstract protected function unserialize($data);


	public function fill($db_data)
	{
		$data = $this->unserialize($db_data);

		foreach ($data as $key => $value)
		{
			$this->setProperty($key, $value);
		}
	}

	public function getValue()
	{
		return $this->serialize($this->getRawValues());
	}

	/**
	 * Get a list of fields
	 *
	 * @return array field names
	 */
	public static function getFields()
	{
		$vars = get_class_vars(get_called_class());
		$fields = array();

		foreach ($vars as $key => $value)
		{
			if ($key[0] != '_')
			{
				$fields[] = $key;
			}
		}

		return $fields;
	}

	/**
	 * Get all current values
	 *
	 * @return array Current values. Including null values - Beware.
	 */
	public function getValues()
	{
		$result = array();

		foreach ($this->getFields() as $field)
		{
			$result[$field] = $this->getProperty($field);
		}

		return $result;
	}

	/**
	 * Get all current values
	 *
	 * @return array Current values. Including null values - Beware.
	 */
	public function getRawValues()
	{
		$result = array();

		foreach ($this->getFields() as $field)
		{
			$result[$field] = $this->$field;
		}

		return $result;
	}

	/**
	 *
	 */
	public function __get($name)
	{
		return $this->getProperty($name);
	}

	/**
	 *
	 */
	public function __set($name, $value)
	{
		$this->setProperty($name, $value);
		return $value;
	}

	/**
	 * Check if the model has a given property
	 *
	 * @param String $name Property name
	 * @return bool has property?
	 */
	public function hasProperty($name)
	{
		return (property_exists($this, $name) && $name[0] !== '_');
	}

	/**
	 * Attempt to get a property. Called by __get.
	 *
	 * @param String $name Name of the property
	 * @return Mixed Value of the property
	 */
	public function getProperty($name)
	{
		if (method_exists($this, 'get__'.$name))
		{
			return $this->{'get__'.$name}();
		}

		if ($this->hasProperty($name))
		{
			return $this->$name;
		}

		throw new InvalidArgumentException("No such property: '{$name}' on ".get_called_class());
	}

	/**
	 * Attempt to set a property. Called by __set.
	 *
	 * @param String $name Name of the property
	 * @param Mixed  $value Value of the property
	 */
	public function setProperty($name, $value)
	{
		if (method_exists($this, 'set__'.$name))
		{
			$this->{'set__'.$name}($value);
		}
		elseif ($this->hasProperty($name))
		{
			$this->$name = $value;
		}
		else
		{
			throw new InvalidArgumentException("No such property: '{$name}' on ".get_called_class());
		}

		return $this;
	}
}