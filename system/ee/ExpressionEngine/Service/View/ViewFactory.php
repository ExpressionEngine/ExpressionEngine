<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\View;

use ExpressionEngine\Core\Provider;

/**
 * ViewFactory
 */
class ViewFactory
{
    /**
     * @var ExpressionEngine\Core\Provider
     */
    protected $provider;

    /**
     * Constructor
     *
     * @param Provider $provider The default provider for views
     */
    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * This will make and return a Service\View object
     *
     * @param string $path The path to the view template file (ex: '_shared/form')
     * @return object A ExpressionEngine\Service\View\View object
     */
    public function make($path)
    {
        $provider = $this->provider;

        if (strpos($path, ':')) {
            list($prefix, $path) = explode(':', $path, 2);
            $provider = $provider->make('App')->get($prefix);
        }

        return new View($path, $provider);
    }

    /**
    * This will make and return a Service\View\StringView object
    *
    * @param string $string The contents of the unrendered view
    * @return object ExpressionEngine\Service\View\StringView
    */
    public function makeFromString($string)
    {
        return new StringView($string);
    }

    /**
     * This will make and return a Stub object
     * Unlike Views, Stubs are passed as string with 3 parts separated by colons
     *
     * @param string $name The path to the stub file, prefixed with add-on name and generator folder
     * @return object A ExpressionEngine\Service\View\Stub object
     */
    public function makeStub($path)
    {
        $provider = $this->provider;

        if (strpos($path, ':')) {
            $parts = explode(':', $path, 3);
            $prefix = $parts[0];
            if (isset($parts[2])) {
                $generatorFolder = $parts[1]; //adding leading slash as that makes building full path easier
                $path = $parts[2];
            } else {
                $generatorFolder = '';
                $path = $parts[1];
            }
            $provider = $provider->make('App')->get($prefix);
        }

        $stub = new Stub($path, $provider);
        $stub->generatorFolder = $generatorFolder;

        return $stub;
    }
}
// EOF
