<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Filesystem;

/**
 * AdapterManager
 */
class AdapterManager
{
    private $adapters = [];

    public function __construct()
    {
        //register local filesystem adapter
        $this->registerAdapter(__NAMESPACE__ .'\Adapter\Local');
    }

    public function get($key)
    {
        if(!array_key_exists($key, $this->adapters)) {
            throw new \Exception("Missing filesystem adapter for [$key]");
        }

        return $this->adapters[$key];
    }

    public function make($key, $settings = [])
    {
        $adapter = $this->get($key);

        return new $adapter($settings);
    }

    public function createSettingsFields($key, $values = [])
    {
        $adapter = $this->get($key);

        return $adapter::getSettingsForm($values);
    }

    public function registerAdapter($adapterClass)
    {
        $interfaces = class_implements($adapterClass);
        if (! isset($interfaces[Adapter\AdapterInterface::class])) {
            return;
        }

        $reflection = new \ReflectionClass($adapterClass);
        $key = strtolower($reflection->getShortName());

        $this->adapters[$key] = $adapterClass;
    }

    public function all()
    {
        return $this->adapters;
    }

}
