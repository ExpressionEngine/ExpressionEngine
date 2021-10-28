<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
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
     * @param str $path The path to the view template file (ex: '_shared/form')
     * @return obj A ExpressionEngine\Service\View\View object
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
    * @param str $string The contents of the unrendered view
    * @return object ExpressionEngine\Service\View\StringView
    */
    public function makeFromString($string)
    {
        return new StringView($string);
    }
}
// EOF
