<?php

namespace EllisLab\ExpressionEngine\Library\Data;

use Closure;
use InvalidArgumentException;
use Serializable;
use EllisLab\ExpressionEngine\Library\Mixin\MixableImpl;
use EllisLab\ExpressionEngine\Service\Event\Emitter;
use EllisLab\ExpressionEngine\Service\Event\Publisher;
use EllisLab\ExpressionEngine\Service\Event\Subscriber;

abstract class Entity extends MixableImpl implements Publisher {

	/**
	 * @var Event emitter
	 */
	protected $_event;

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
		$this->_event = new Emitter();

		// Subscribe to events on self if the class is also a subscriber
		if ($this instanceOf Subscriber)
		{
			$this->_event->subscribe($this);
		}

		$this->initialize();

		foreach ($data as $k => $v)
		{
			if ($this->hasProperty($k))
			{
				$this->setRawProperty($k, $v);
			}
		}
	}

	protected function initialize()
	{
		// nothing here, this is for you.
	}

	/**
	 *
	 */
	public function __get($name)
	{
		return $this->getProperty($name);
	}

	/**
	 * Isset implementation, also required for empty() to work
	 */
	public function __isset($name)
	{
		return $this->hasGetterFor($name) OR ($this->hasProperty($name) && $this->getRawProperty($name) !== NULL);
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
		return isset($this->_filters[$type]) ? $this->_filters[$type] : array();
	}

	/**
	 * Apply known filters to a given value
	 *
	 * @param String $type Filter type
	 * @param String $type Filter type
	 * @param Array $args List of arguments
	 * @return Filtered value
	 */
	protected function filter($type)
	{
		$args = array_slice(func_get_args(), 1);

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
			$this->fillProperty($k, $v);
		}

		return $this;
	}

	/**
	 * Fill a value without passing through a getter
	 *
	 * This exists so that we can override it for variable columns. Attempting
	 * to fill by setting `$this->$k` on a variable column results in a call to
	 * __set otherwise and that marks the column as dirty, which we don't want.
	 *
	 * @param String $k Key to fill
	 * @param Mixed  $v Value of fill
	 * @return $this
	 */
	public function fillProperty($k, $v)
	{
		if ($this->hasProperty($k))
		{
			$v = $this->filter('fill', $v, $k);
			$this->$k = $v;
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
	 * Get all modified keys and values
	 */
	public function getModified()
	{
		$modified = array();

		foreach (array_keys($this->_clean_backups) as $key)
		{
			$modified[$key] = $this->getRawProperty($key);
		}

		return $modified;
	}

	/**
	 * Get all modified keys and values prepped for storage
	 *
	 * @return array Dirty properties and their values
	 */
	public function getDirty()
	{
		$dirty = $this->getModified();

		foreach ($dirty as $key => &$value)
		{
			$value = $this->filter('store', $value, $key);
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

		return $this->filter('get', $value, $name);
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
		$value = $this->filter('set', $value, $name);

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
	 * Set a property directly, bypassing the setter. This method should
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
	 * Bind an event listener
	 *
	 * @param String $event Event name
	 * @param Closure $listener The event listener callback
	 * @return $this
	 */
	public function on($event, Closure $listener)
	{
		$this->_event->on($event, $listener);
	}

	/**
	 * Bind an event listener that only fires once
	 *
	 * @param String $event Event name
	 * @param Closure $listener The event listener callback
	 * @return $this
	 */
	public function once($event, Closure $listener)
	{
		$this->_event->once($event, $listener);
	}

	/**
	 * Subscribe an object to events on this entity. Any public method
	 * called `on<EventName>` will be considered a listener on that event.
	 *
	 * @param Subscriber $subscriber Subscriber to add
	 */
	public function subscribe(Subscriber $subscriber)
	{
		$this->_event->subscribe($subscriber);
	}

	/**
	 * Remove a subscription.
	 *
	 * @param Subscriber $subscriber Subscriber to remove
	 */
	public function unsubscribe(Subscriber $subscriber)
	{
		$this->_event->unsubscribe($subscriber);
	}

	/**
	 * Emit an event
	 *
	 * @param String $event Event name
	 * @param Any number of additional parameters to pass to the listeners
	 * @return $this
	 */
	public function emit(/* $event, ...$args */)
	{
		call_user_func_array(
			array($this->_event, 'emit'),
			func_get_args()
		);
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

		return $default;
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

// EOF
