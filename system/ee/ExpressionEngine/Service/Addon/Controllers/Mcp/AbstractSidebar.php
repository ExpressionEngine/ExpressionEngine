<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Addon\Controllers\Mcp;

abstract class AbstractSidebar
{
    /**
     * @var bool - determines if sidebar will be generated automatically
     */
    protected $automatic = true;

    protected $header = null;

    protected $routeClasses = [];

    /**
     * @var CP/Sidebar
     */
    protected $sidebar;

    public function __construct($addon, $namespace)
    {
        $this->addon = $addon;
        $this->namespace = $namespace;
        $this->sidebar = ee('CP/Sidebar')->make();

        if ($this->automatic) {
            $this->processAutomatic();
        }
    }

    public function getSidebar()
    {
        return $this->sidebar;
    }

    public function processAutomatic()
    {
        if (is_null($this->header)) {
            $this->header = $this->addon;
        }
        $subsHeader = $this->sidebar->addHeader(lang($this->header));
        $subsHeaderList = $subsHeader->addBasicList();

        foreach ($this->getAbstractRoutes() as $route) {
            // If we are excluding this from the sidebar, do it now
            if ($this->getProperty($route, 'exclude_from_sidebar')) {
                continue;
            }

            // Get the items to set
            $title = $this->getProperty($route, 'sidebar_title') ?: $this->getProperty($route, 'cp_page_title');
            $cp_url = ee('CP/URL')->make('addons/settings/' . $this->addon . '/' . $this->getProperty($route, 'route_path'));
            $sidebar_icon = $this->getProperty($route, 'sidebar_icon');

            // Create sidebar item
            $newItem = $subsHeaderList->addItem(lang($title), $cp_url);

            // Set the icon if it exists
            if ($sidebar_icon) {
                $newItem->withIcon($sidebar_icon);
            }
        }
    }

    protected function getProperty($class, $property)
    {
        $ref = $this->getRouteClass($class);

        if (! $ref->hasProperty($property)) {
            return null;
        }

        return $ref->getProperty($property)->getValue(new $class());
    }

    protected function getRouteClass($class)
    {
        if (isset($routeClasses[$class])) {
            return $routeClasses[$class];
        }

        return $routeClasses[$class] = new \ReflectionClass($class);
    }

    protected function getAbstractRoutes()
    {
        $routes = [];

        // Loop through each route file
        $contents = ee('Filesystem')->getDirectoryContents(PATH_THIRD . $this->addon . '/Mcp/');
        foreach ($contents as $c) {
            $class = $this->namespace . '\Mcp\\' . substr(basename($c), 0, -4);

            if (class_exists($class) && is_subclass_of($class, 'ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute')) {
                $routes[] = $class;
            }
        }

        return $routes;
    }
}
