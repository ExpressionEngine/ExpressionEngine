<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Addon\Controllers;

use ExpressionEngine\Service\Addon\Exceptions\ControllerException;
use ExpressionEngine\Service\Addon\Addon;

class Controller
{
    /**
     * Used to locate child objects
     * @var string
     */
    protected $route_namespace = '';

    /**
     * The canonical name for the Add-on this Controller is used on
     * @var string
     */
    protected $addon_name = '';

    /**
     * @param string $namespace
     * @return $this
     */
    public function setRouteNamespace(string $namespace): Controller
    {
        $this->route_namespace = $namespace;
        return $this;
    }

    /**
     * @return string
     * @throws ControllerException
     */
    public function getRouteNamespace(): string
    {
        if ($this->route_namespace == '') {
            $addon = ee('Addon')->get($this->getAddonName());
            if (!$addon instanceof Addon) {
                throw new ControllerException("Your addon_name property hasn't been setup!");
            }

            $provider = $addon->getProvider();
            $this->setRouteNamespace($provider->get('namespace'));
        }

        return $this->route_namespace;
    }

    /**
     * @param string $addon_name
     * @return $this
     */
    public function setAddonName(string $addon_name): Controller
    {
        $this->addon_name = $addon_name;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddonName(): string
    {
        return $this->addon_name;
    }
}
