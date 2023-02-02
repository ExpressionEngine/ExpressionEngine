<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Data;

use Closure;
use Countable;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;

/**
 * ExpressionEngine Collection
 *
 * A collection is essentially an array of objects. Any calls to the
 * collection will be passed to each of the parent objects.
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * @var array Elements in the collection
     */
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
        foreach ($this->elements as $element) {
            if (is_array($element)) {
                $element[$key] = $value;
            } else {
                $element->$key = $value;
            }
        }
    }

    /**
     * Allow the calling of element methods by the collection.
     * First argument is assumed to be a callback to handle
     * the return of the methods.
     *
     * @param String $method   Method name
     * @param Array $arguments List of arguments
     * @return array of esults
     */
    public function __call($method, $arguments)
    {
        if (empty($this->elements)) {
            return;
        }

        $callback = null;

        if (count($arguments) && $arguments[0] instanceof Closure) {
            $callback = array_shift($arguments);
        }

        return $this->map(function ($item) use ($method, $arguments, $callback) {
            $result = call_user_func_array(array($item, $method), $arguments);

            if (isset($callback)) {
                $callback($result);
            }

            return $result;
        });
    }

    /**
     * Compare to toArray() which exists on models and converts them.
     *
     * @return array
     */
    public function asArray()
    {
        return $this->elements;
    }

    /**
     * Retrieve the first item
     *
     * @return Mixed First child
     */
    public function first()
    {
        return $this->count() ? $this->elements[0] : null;
    }

    /**
    * Retrieve the last item
    *
    * @return Mixed Last child
    */
    public function last()
    {
        $count = $this->count();

        return $count ? $this->elements[$count - 1] : null;
    }

    /**
     * Get a given value for all elements
     *
     * @param String $key The key to get from each element
     * @return array of values
     */
    public function pluck($key)
    {
        return $this->map(function ($item) use ($key) {
            return is_array($item) ? $item[$key] : $item->$key;
        });
    }

    /**
     * Get an arbitrary value from each element, depending on whether
     * the parameter is a key or a closure. Useful for internal methods
     * that can take both.
     *
     * @param Closure|String $collector Property name or callback used to extract
     * @return array Collected values
     */
    public function collect($collector)
    {
        if ($collector instanceof Closure) {
            return $this->map($collector);
        }

        return $this->pluck($collector);
    }

    /**
     * Sort the data by a given column and return a new
     * collection containing the sorted results
     *
     * @param Closure|String $collector The property name to collect (or a closure)
     * @param Int    $flags Sort flags (as per http://php.net/sort)
     * @return Sorted collection
     */
    public function sortBy($collector, $flags = SORT_REGULAR)
    {
        $values = $this->collect($collector);

        asort($values, $flags);

        $elements = array();

        foreach ($values as $key => $value) {
            $elements[] = $this->elements[$key];
        }

        return new static($elements);
    }

    /**
     * Reverse the collection data and return a new collection
     * containing the reversed elements
     *
     * @return Collection Reversed collection
     */
    public function reverse()
    {
        return new static(array_reverse($this->elements));
    }

    /**
     * Given a property name or callback, create an array of elements
     * that is indexed by the property value or the return value of
     * the callback for each element
     *
     * @param Closure|String $collector Property name or callback to extract keys
     * @return array of [Collector keys => Collection elements]
     */
    public function indexBy($collector)
    {
        $keys = $this->collect($collector);
        $values = $this->elements;

        // 5.3 requires array_combine arguments to have at least one element
        if (empty($keys) or empty($values)) {
            return array();
        }

        return array_combine($keys, $values);
    }

    /**
     * Get a key => value array. Basically indexBy + pluck.
     *
     * @param Closure|String $key Collector to extract keys
     * @param Closure|String $value Collector to extract values
     * @return Associative array of [key => value]
     */
    public function getDictionary($key, $value)
    {
        $keys = $this->collect($key);
        $values = $this->collect($value);

        // 5.3 requires array_combine arguments to have at least one element
        if (empty($keys) or empty($values)) {
            return array();
        }

        return array_combine($keys, $values);
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
     * @param Callable $callback Function to apply
     * @return array  results
     */
    public function mapProperty($key, callable $callback)
    {
        return $this->each(function ($item) use ($key, $callback) {
            if (is_array($item)) {
                $item[$key] = $callback($item[$key]);
            } else {
                $item->$key = $callback($item->$key);
            }
        });
    }

    /**
     * Applies the given callback to the collection and returns an array
     * of the results.
     *
     * @param Closure $callback Function to apply
     * @return Collection  results
     */
    public function filter($callback)
    {
        return new static(array_filter($this->elements, $callback));
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
    #[\ReturnTypeWillChange]
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
    #[\ReturnTypeWillChange]
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
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        // If you push `$collection[] = $value`, the key is null
        if ($offset === null) {
            $this->elements[] = $value;
        } else {
            $this->elements[$offset] = $value;
        }
    }

    /**
     * Remove an array element
     *
     * @param mixed $offset Array key
     * @return void
     */
    #[\ReturnTypeWillChange]
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
    #[\ReturnTypeWillChange]
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
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new ArrayIterator($this->elements);
    }
}

// EOF
