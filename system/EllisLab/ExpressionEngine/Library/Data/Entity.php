<?php

namespace EllisLab\ExpressionEngine\Library\Data;

use InvalidArgumentException;
use EllisLab\ExpressionEngine\Library\Mixin\MixableImpl;

abstract class Entity extends MixableImpl {

	/**
	 * @var Array Filter storage
	 */
	protected $_filters = array();

	/**
	 * @var Backup of clean values
	 */
	protected $_clean_backups = array();

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
	 * Access any static metadata you might need. This automatically
	 * merges metadata for extended classes.
	 *
	 * @param String $key Name of the static property
	 * @return mixed The metadata value
	 */
	public static function getMetaData($key)
	{
		$values = static::getMetaDataByClass($key);

		if ( ! count($values))
		{
			return NULL;
		}

		$result = array_shift($values);

		foreach ($values as $class => $value)
		{
			if (is_array($result) && is_array($value))
			{
				$result = array_merge($result, $value);
			}
			else
			{
				$result = $value;
			}
		}

		return $result;
	}

	/**
	 * Access all static metadata, grouped by class name.
	 *
	 * @param String $key Metadata name
	 * @return Array [class => value] for all classes that define the metadata
	 */
	public static function getMetaDataByClass($key)
	{
		$key = '_'.$key;
		$values = array();

		$class = get_called_class();
		$child = NULL;

		do
		{
			if (property_exists($class, $key))
			{
				$values[$class] = $class::$$key;

				// If the child result is the same as the parent, then
				// we read a fallback from the child and don't actually
				// want to store that. Yick.
				if (isset($child) && $values[$child] == $values[$class])
				{
					unset($values[$child]);
				}
			}

			$child = $class;
		}
		while ($class = get_parent_class($class));

		return array_reverse($values);
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
	 * Add a filter
	 *
	 * @param String $type Filter type
	 * @param Callable $callback Filter callback
	 */
	public function addFilter($type, /*Callable */ $callback)
	{
		if ( ! array_key_exists($type, $this->_filters))
		{
			$this->_filters[$type] = array();
		}

		$this->_filters[$type][] = $callback;
	}

	/**
	 * Get all known filters of a given type
	 *
	 * @param String $type Filter type
	 * @return Array of callables
	 */
	protected function getFilters($type)
	{
		return $this->_filters[$type];
	}

	/**
	 * Apply known filters to a given value
	 *
	 * @param String $type Filter type
	 * @param String $type Filter type
	 * @param Array $args List of arguments
	 * @return Filtered value
	 */
	protected function filter($type, $value, $args = array())
	{
		array_unshift($args, $value);

		foreach ($this->getFilters($type) as $filter)
		{
			$args[0] = call_user_func_array($filter, $args);
		}

		return $args[0];
	}

	/**
	 * Batch update properties
	 *
	 * Safely updates any properties that might exist,
	 * passing them through the getters along the way.
	 *
	 * @param array $data Data to update
	 * @return $this
	 */
	public function set(array $data = array())
	{
		foreach ($data as $k => $v)
		{
			if ($this->hasProperty($k))
			{
				$this->setProperty($k, $v);
			}
		}

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
	 * Check if entity has dirty values
	 *
	 * @return bool is dirty?
	 */
	public function isDirty($name = NULL)
	{
		if ( ! isset($name))
		{
			return ! empty($this->_clean_backups);
		}

		return $this->hasBackup($name);
	}

	/**
	 * Get all dirty keys and values
	 *
	 * @return array Dirty properties and their values
	 */
	public function getDirty()
	{
		$dirty = array();

		foreach (array_keys($this->_clean_backups) as $key)
		{
			$dirty[$key] = $this->$key;
		}

		return $dirty;
	}

	/**
	 * Get the list of original values that have changed.
	 *
	 * @return Array of old values
	 */
	public function getOriginal()
	{
		return $this->_clean_backups;
	}

	/**
	 * Mark a property or the entire entity as clean.
	 *
	 * @param String $name Property name [optional]
	 */
	public function markAsClean($name = NULL)
	{
		if (isset($name))
		{
			$this->deleteBackup($name);
		}
		else
		{
			$this->clearBackups();
		}

		return $this;
	}

	/**
	 * Restore all or one original value(s).
	 *
	 * @param String $name Name of property to restore [optional]
	 */
	public function restore($name = NULL)
	{
		if ( ! isset($name))
		{
			foreach (array_keys($this->_clean_backups) as $key)
			{
				$this->restore($key);
			}
		}
		else
		{
			$this->$name = $this->getBackup($name);
			$this->markAsClean($name);
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
		if ($this->hasGetterFor($name))
		{
			$value = $this->{'get__'.$name}();
		}
		else
		{
			$value = $this->getRawProperty($name);
		}

		return $this->filter('get', $value, array($name));
	}

	/**
	 * Attempt to set a property. Called by __set.
	 *
	 * @param String $name Name of the property
	 * @param Mixed  $value Value of the property
	 * @return $this
	 */
	public function setProperty($name, $value)
	{
		$value = $this->filter('set', $value, array($name));

		if ($this->hasSetterFor($name))
		{
			$this->{'set__'.$name}($value);
		}
		else
		{
			$this->setRawProperty($name, $value);
		}

		return $this;
	}

	/**
	 * Check if the child class provides a getter
	 *
	 * @param String $name Property name
	 * @return Bool Has getter?
	 */
	protected function hasGetterFor($name)
	{
		return method_exists($this, 'get__'.$name);
	}

	/**
	 * Check if the child class provides a setter
	 *
	 * @param String $name Property name
	 * @return Bool Has setter?
	 */
	protected function hasSetterFor($name)
	{
		return method_exists($this, 'set__'.$name);
	}

	/**
	 * Get a property directly, bypassing the getter. This method should
	 * not be extended with additional logic, it should be treated as a
	 * way to bypass __get() and all that comes with it.
	 *
	 * @param String $name Name of the property
	 * @return Mixed $value Value of the property
	 */
	public function getRawProperty($name)
	{
		if ($this->hasProperty($name))
		{
			return $this->$name;
		}

		throw new InvalidArgumentException("No such property: '{$name}' on ".get_called_class());
	}

	/**
	 * Get a property directly, bypassing the getter. This method should
	 * not be extended with additional logic, it should be treated as a
	 * way to bypass __set() and all that comes with it.
	 *
	 * @param String $name Name of the property
	 * @param Mixed  $value Value of the property
	 * @return $this
	 */
	public function setRawProperty($name, $value)
	{
		if ($this->hasProperty($name))
		{
			$this->backupIfChanging($name, $this->$name, $value);

			$this->$name = $value;
			return $this;
		}

		throw new InvalidArgumentException("No such property: '{$name}' on ".get_called_class());
	}

	/**
	 * Get a list of fields
	 *
	 * @return array field names
	 */
	public function getFields()
	{
		return static::getClassFields();
	}

	/**
	 * Get a static list of fields.
	 */
	public static function getClassFields()
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


	/**
	 * Track changes to properties so we can report which ones have been
	 * modified. This method is smart enough to recognize changing things
	 * and then changing them back.
	 */
	protected function backupIfChanging($name, $old_value, $new_value)
	{
		if ($new_value !== $old_value)
		{
			if ( ! $this->hasBackup($name))
			{
				$this->setBackup($name, $old_value);
			}
			elseif ($new_value === $this->getBackup($name))
			{
				$this->markAsClean($name);
			}
		}

		return $this;
	}

	/**
	 * Check if we've had to backup a given property
	 *
	 * @param String $name Name of property
	 * @return bool Backup for given property exists
	 */
	protected function hasBackup($name)
	{
		return array_key_exists($name, $this->_clean_backups);
	}

	/**
	 * Get the original value for a property
	 *
	 * @param String $name    Name of property
	 * @param Mixed  $default Value to return if property has not been modified
	 * @return Mixed Original value
	 */
	protected function getBackup($name, $default = NULL)
	{
		if ($this->hasBackup($name))
		{
			return $this->_clean_backups[$name];
		}

		return NULL;
	}

	/**
	 * Set a backup
	 *
	 * @param String $name  Name of property
	 * @param Mixed  $value Original value of the property
	 */
	protected function setBackup($name, $value)
	{
		$this->_clean_backups[$name] = $value;
	}

	/**
	 * Delete a backup
	 *
	 * @param String $name Name of property
	 */
	protected function deleteBackup($name)
	{
		unset($this->_clean_backups[$name]);
	}

	/**
	 * Delete all backups
	 */
	protected function clearBackups()
	{
		$this->_clean_backups = array();
	}

}