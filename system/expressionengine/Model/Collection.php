<?php namespace EllisLab\ExpressionEngine\Model;

/**
 * Model Collection
 *
 * If more than one element is returned for a result, we put them together
 * in a model collection. A collection acts like an array, with the additional
 * benefit of being able to call save and delete on it.
 */
class Collection implements \ArrayAccess, \Countable, \IteratorAggregate {

	protected $elements = array();

	public function __construct(array $elements = array())
	{
		$this->elements = $elements;
	}

	/**
	 * Retrieve a list of all ids in this collection
	 *
	 * @return Array Ids
	 */
	public function getIds()
	{
		return array_map(function($model)
		{
			return $model->getId();
		}, $this->elements);
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
	 * Save all elements in the collection
	 *
	 * @return void
	 */
	public function save()
	{
		foreach ($this->elements as $model)
		{
			$model->save();
		}
	}

	/**
	 * Delete all elements in the collection
	 *
	 * @return void
	 */
	public function delete()
	{
		foreach ($this->elements as $model)
		{
			$model->delete();
		}
	}

	/**
	 * Turn the entire collection into an array
	 *
	 * @return Array of data
	 */
	public function toArray()
	{
		return array_map(function($model)
		{
			return $model->toArray();
		}, $this->elements);
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
		return new \ArrayIterator($this->elements);
	}
}