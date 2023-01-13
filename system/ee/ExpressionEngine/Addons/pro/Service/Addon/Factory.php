<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Service\Addon;

use ExpressionEngine\Service\Addon as Core;
use ExpressionEngine\Core\Provider;

/**
 * Addon Service Factory
 */
class Factory extends Core\Factory
{
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
     * Is a given provider an addon?
     *
     * @return bool Is an addon?
     */
    protected function isAddon(Provider $provider)
    {
        $path = $provider->getPath();

        return (strpos($path, PATH_ADDONS) === 0 || (defined('PATH_PRO_ADDONS') && strpos($path, PATH_PRO_ADDONS) === 0) || strpos($path, PATH_THIRD) === 0);
    }
}

// EOF
