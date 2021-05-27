<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Addon;

use ExpressionEngine\Core\Application;
use ExpressionEngine\Core\Provider;

/**
 * Addon Service Factory
 */
class Factory
{
    /**
     * @var Application object
     */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get the addon `$name`
     *
     * @param String $name Addon short name
     * @return Object Addon The requested addon
     */
    public function get($name)
    {
        if (! $this->app->has($name)) {
            return null;
        }

        $provider = $this->app->get($name);

        if ($this->isAddon($provider)) {
            return new Addon($provider);
        }

        return null;
    }

    /**
     * Get all addons
     *
     * @return array An array of Addon objects.
     */
    public function all()
    {
        $providers = $this->app->getProviders();

        $all = array();

        foreach ($providers as $key => $obj) {
            if ($this->isAddon($obj)) {
                $all[$key] = new Addon($obj);
            }
        }

        return $all;
    }

    /**
     * Fetch all installed addons
     *
     * @return array An array of Addon objects.
     */
    public function installed()
    {
        return array_filter($this->all(), function ($addon) {
            return $addon->isInstalled();
        });
    }

    /**
     * Is a given provider an addon?
     *
     * @return bool Is an addon?
     */
    protected function isAddon(Provider $provider)
    {
        $path = $provider->getPath();

        return (strpos($path, PATH_ADDONS) === 0 || strpos($path, PATH_THIRD) === 0);
    }
}

// EOF
