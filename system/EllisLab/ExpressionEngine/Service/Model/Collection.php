<?php namespace EllisLab\ExpressionEngine\Service\Model;

use Closure;
use Countable;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;

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
 * ExpressionEngine Collection
 *
 * If more than one element is returned for a result, we put them together
 * in a model collection. A collection acts like an array, with the additional
 * benefit of being able to call save and delete on it.
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate {

	protected $elements = array();

	/**
	 * @param Array $elements Contents of the collection
	 */
	public function __construct(array $elements = array())
	{
		$this->elements = array_values($elements);
	}

	/**
	 * Allow for setting in batches. Be careful, folks!
	 *
	 * @param String $key  Property name
	 * @param Mixed $value Property value
	 */
	public function __set($key, $value)
	{
		foreach ($this->elements as $element)
		{
			$element->$key = $value;
		}
	}

	/**
	 * Allow the calling of model methods by the collection.
	 * First argument is assumed to be a callback to handle
	 * the return of the methods.
	 *
	 * @param String $method   Method name
	 * @param Array $arguments List of arguments
	 * @return Array of esults
	 */
	public function __call($method, $arguments)
	{
		if (empty($this->elements))
		{
			return;
		}

		$callback = NULL;

		if (count($arguments) && $arguments[0] instanceOf Closure)
		{
			$callback = array_shift($arguments);
		}

		return $this->map(function($model) use ($method, $arguments, $callback)
		{
			$result = call_user_func_array(array($model, $method), $arguments);

			if (isset($callback))
			{
				$callback($result);
			}

			return $result;
		});
	}

	/**
	 * Retrieve a list of all ids in this collection
	 *
	 * @return Array Ids
	 */
	public function getIds()
	{
		return $this->map(function($model)
		{
			return $model->getId();
		});
	}

	/**
	 * Compare to toArray() which also converts models.
	 */
	public function asArray()
	{
		return $this->elements;
	}

	/**
	 * Retrieve the first model
	 *
	 * @return Mixed First model object
	 */
	public function first()
	{
		return $this->elements[0];
	}

	/**
	 * Get a given value for all elements
	 *
	 * @param String $key The key to get from each element
	 * @return Array of values
	 */
	public function pluck($key)
	{
		return $this->map(function($model) use($key)
		{
			return $model->$key;
		});
	}

	/**
	 * Applies the given callback to the collection and returns an array
	 * of the results.
	 *
	 * @param Closure $callback Function to apply
	 * @return array  results
	 */
	public function map(Closure $callback)
	{
		return array_map($callback, $this->elements);
	}

	/**
	 * Applies the given callback to the collection and returns an array
	 * of the results.
	 *
	 * @param Closure $callback Function to apply
	 * @return array  results
	 */
	public function filter(Closure $callback)
	{
		return array_values(array_filter($this->elements, $callback));
	}

	/**
	 * Applies the given callback to the collection and returns the
	 * collection.
	 *
	 * @param Closure $callback Function to apply
	 * @return Collection $this
	 */
	public function each(Closure $callback)
	{
		array_map($callback, $this->elements);
		return $this;
	}

	// Implement Array Access

	/**
	 * Check if an array element is set
	 *
	 * @param mixed $offset Array key
	 * @return void
	 */
	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->elements);
	}

	/**
	 * Retrieve an array element
	 *
	 * @param mixed $offset Array key
	 * @return mixed The element
	 */
	public function offsetGet($offset)
	{
		return $this->elements[$offset];
	}

	/**
	 * Set an array element
	 *
	 * @param mixed $offset Array key
	 * @param mixed $value Array value
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		// If you push `$collection[] = $value`, the key is null
		if ($offset === NULL)
		{
			$this->elements[] = $value;
		}
		else
		{
			$this->elements[$offset] = $value;
		}
	}

	/**
	 * Remove an array element
	 *
	 * @param mixed $offset Array key
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		unset($this->elements[$offset]);
	}

	// Implement Countable

	/**
	 * Find the length of the collection
	 *
	 * @return int Length
	 */
	public function count()
	{
		return count($this->elements);
	}

	// Implement IteratorAggregate

	/**
	 * Allow for foreach loops over the collection
	 *
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->elements);
	}
}
