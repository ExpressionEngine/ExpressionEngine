<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Template\Annotation;

/**
 * Template Runtime Annotation
 *
 * Sometimes, at runtime, you want to mark a piece of template code and
 * maybe store some metadata about that marked bit. For example, on a
 * first pass through the template you might store the original line number
 * to parts of the template that might error. Since template contents
 * change throughtout the execution process, we mark these locations with
 * a small comment right in the string.
 *
 * To prevent having to serialize, unserialize, and parse complicated
 * comment strings, the data itself is stored elsewhere. So an annotation
 * in the template looks like this:
 *
 * {!-- ra:s81208b000048104753 --},	where the random looking part is an hash
 *									that uniquely identifies the data object
 *
 * So when you see a comment in a template, you can then check if it's an
 * annotation and retrieve the related data again.
 */
class Runtime
{
    protected $store;
    protected static $shared_store;

    private $use_shared = false;

    public function __construct()
    {
        $this->store = array();
    }

    /**
     * Create an annotation comment
     *
     * @param Array $data Initial annotation data
     */
    public function create(array $data = array())
    {
        $key = $this->save($data);

        return '{!-- ra:' . $key . ' --}';
    }

    /**
     * Retrieve annotation data for a comment
     *
     * @return Object annotation object
     */
    public function read($comment_text)
    {
        if (preg_match('/^\{!-- ra:(\w+) --\}$/', $comment_text, $matches)) {
            return $this->get($matches[1]);
        }

        return null;
    }

    /**
     * Use singleton annotation store
     */
    public function useSharedStore()
    {
        if (! isset(static::$shared_store)) {
            static::$shared_store = array();
        }

        $this->store = & static::$shared_store;
    }

    /**
     * Clear shared memory store.
     *
     * Mostly here to allow clearing for tests. Typically you don't
     * know who else might be using the shared store, so you don't
     * want to clear it.
     */
    public function clearSharedStore()
    {
        static::$shared_store = null;
    }

    /**
     * Save some new annotation data and retrieve an annotation key.
     *
     * @param Array $data Initial data
     * @return Annotation key
     */
    protected function save($data)
    {
        // We store an object so we can easily return a reference
        // for editing annotation data without creating a new one.
        $obj = (object) $data;
        $key = spl_object_hash($obj);

        $this->store[$key] = $obj;

        return $key;
    }

    /**
     * Retrieve an annotation object from a key
     *
     * @param String $key Annotation key
     * @return Object<StdClass> Data object
     */
    protected function get($key)
    {
        if (! isset($this->store[$key])) {
            return null;
        }

        return $this->store[$key];
    }
}
