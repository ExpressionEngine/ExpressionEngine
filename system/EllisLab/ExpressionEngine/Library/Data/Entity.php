<?php

namespace EllisLab\ExpressionEngine\Library\Data;

use EllisLab\ExpressionEngine\Library\Mixin\MixableImpl;

abstract class Entity extends MixableImpl {

	/**
	 * Constructor
	 */
	public function __construct(array $data = array())
	{
		foreach ($data as $key => $value)
		{
			$this->setProperty($key, $value);
		}
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
	 *
	 */
	public function __call($method, $args)
	{
		return $this->getMixinManager()->call($method, $args);
	}

	/**
	 * Access any static metadata you might need.
	 *
	 * @param String $key Name of the static property
	 * @return mixed The metadata value Set the short name of this model
	 *
	 * @param String $name The short name
	 */
	public static function getMetaData($key)
	{
		$key = '_'.$key;

		if ( ! property_exists(get_called_class(), $key))
		{
			return NULL;
		}

		return static::$$key;
	}

	/**
	 * The default mixin implementation is to rely on a `mixins`
	 * metadata key that contains the mixin class names.
	 */
	protected function getMixinClasses()
	{
		return $this->getMetaData('mixins') ?: array();
	}

	/**
	 * Fill data without passing through a getter
	 *
	 * @param array $data Data to fill
	 * @return $this
	 */
	public function fill(array $data = array())
	{
		foreach ($data as $k => $v)
		{
			if ($this->hasProperty($k))
			{
				$this->$k = $v;
			}
		}

		return $this;
	}

	/**
	 * Check if the entity has a given property
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
	 * Get all current raw values
	 *
	 * @return array Raw values, including null properties - Beware.
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
	 * Retrieve data as an array. All getters will be hit.
	 *
	 * @return array Data including NULL values
	 */
	public function toArray()
	{
		return $this->getValues();
	}

	/**
	 * Same as `toArray()`, but retrieve data as json
	 *
	 * @return string json formatted data
	 */
	public function toJson()
	{
		return json_encode($this->toArray());
	}
}