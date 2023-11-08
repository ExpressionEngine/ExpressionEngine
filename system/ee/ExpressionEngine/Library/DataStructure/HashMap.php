<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\DataStructure;

/**
 * PHP's arrays don't accept objects as keys and SplObjectStorage
 * won't give you access to all keys or all values and is limited
 * to object keys.
 *
 * This class finds a happy medium. Store any key with any value.
 */
class HashMap
{
    protected $keys = array();
    protected $data = array();

    /**
     *
     */
    public function set($key, $value)
    {
        $index = $this->hash($key);

        $this->keys[$index] = $key;
        $this->data[$index] = $value;
    }

    /**
     * Same as `set()` but enforce uniqueness.
     */
    public function add($key, $value)
    {
        if ($this->hasKey($key)) {
            throw new \Exception('Element ' . $key . ' already exists in Map');
        }

        $this->set($key, $value);
    }

    /**
     *
     */
    public function hasKey($key)
    {
        return array_key_exists($this->hash($key), $this->data);
    }

    /**
     *
     */
    public function get($key)
    {
        $index = $this->hash($key);

        if (array_key_exists($index, $this->data)) {
            return $this->data[$index];
        }
    }

    /**
     *
     */
    public function remove($key)
    {
        $index = $this->hash($key);

        unset($this->data[$index]);
        unset($this->data[$index]);
    }

    /**
     *
     */
    public function getKeys()
    {
        return array_values($this->keys);
    }

    /**
     *
     */
    public function getValues()
    {
        return array_values($this->data);
    }

    /**
     *
     */
    protected function hash($element)
    {
        if (is_object($element)) {
            return spl_object_hash($element);
        }

        return $element;
    }
}
