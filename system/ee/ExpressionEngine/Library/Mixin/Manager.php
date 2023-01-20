<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Mixin;

use BadMethodCallException;
use ExpressionEngine\Service\Event\Publisher;

/**
 * Mixin Manager
 */
class Manager
{
    protected $scope;
    protected $mixins = array();
    protected $instances = array();
    protected $forwarded = array();

    protected $mounted = false;

    /**
     * @param Object $scope Object to mix into
     */
    public function __construct($scope)
    {
        $this->scope = $scope;
    }

    /**
     * @param array $mixins List of class names
     */
    public function setMixins($mixins)
    {
        $this->mixins = $mixins;
        $this->mountMixins();
    }

    /**
     * Boot the mixin objects and collect their names
     */
    public function mountMixins()
    {
        // first the publishers
        $done = array();

        foreach ($this->mixins as $class) {
            if ($class instanceof Publisher) {
                $this->createMixinObject($class);
                $done[] = $class;
            }
        }

        // then the subscribers and everyone else
        foreach ($this->mixins as $class) {
            if (! in_array($class, $done)) {
                $this->createMixinObject($class);
            }
        }

        $this->mounted = true;
    }

    /**
     * Check if a given mixin was mounted on the current scope.
     *
     * @param String $name  Mixin name as exposed by getName()
     * @return Bool Mixin mounted
     */
    public function hasMixin($name)
    {
        if (! $this->mounted) {
            throw new \Exception('Mixins not mounted. Cannot check if mixin exists.');
        }

        return array_key_exists($name, $this->instances);
    }

    /**
     * Directly access a mixin object
     *
     * @param String $name
     * @return Mixin instance
     */
    public function getMixin($name)
    {
        return $this->instances[$name];
    }

    /**
     * Call a function on the aggregate of all mixins as well as
     * all other mixables.
     *
     * It's generally not a good idea to rely on return values, but
     * if you must the value will be the last mixin called that is
     * not null.
     *
     * @param String $fn Method name
     * @param Array $args List of arguments
     * @return Mixed last non-null result, or null if no results
     */
    public function call($fn, $args)
    {
        if ($fn == 'getName') {
            throw new BadMethodCallException("No such method {$fn}.");
        }

        return $this->runMixins($fn, $args);
    }

    /**
     * Run a function on all mixins
     *
     * @throws BadMethodClassException if none of the mixins implement it
     * @param String $fn Function name
     * @param Array $args Arguments to pass to the method
     * @return Last return value [or NULL].
     */
    protected function runMixins($fn, $args)
    {
        $return = null;
        $method_exists = false;

        foreach ($this->instances as $obj) {
            $callable = array($obj, $fn);

            if (is_callable($callable)) {
                $method_exists = true;
                $new_return = call_user_func_array($callable, $args);

                if (! is_null($new_return)) {
                    $return = $new_return;
                }
            }
        }

        if (! $method_exists) {
            throw new BadMethodCallException("No such method {$fn}.");
        }

        return $return;
    }

    /**
     * Helper function to create mixin objects
     *
     * @param String $class Class name
     */
    protected function createMixinObject($class)
    {
        $obj = new $class($this->scope, $this);
        $this->instances[$obj->getName()] = $obj;
    }
}
