<?php

namespace ExpressionEngine\Service\Addon\Controllers;

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
     */
    public function getRouteNamespace(): string
    {
        if($this->route_namespace == '') {
            $addon = ee('Addon')->get($this->getAddonName());
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
