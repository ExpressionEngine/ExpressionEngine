<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Addon\Controllers\Mcp;

abstract class AbstractSidebar
{
    /**
     * @var bool - determines if sidebar will be generated automatically
     */
    protected $automatic = true;

    /**
     * @var string - optional header for the sidebar generation when set automatically
     */
    protected $header;

    /**
     * @var array - array of route classes used for getting properties
     */
    protected $routeClasses = [];

    public $routes = [];
    public $lists = [];
    public $folders = [];
    public $items = [];

    /**
     * @var CP/Sidebar
     */
    protected $sidebar;

    protected $addon;
    protected $namespace;

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

    public function getAll()
    {
        return $this->routes;
    }

    public function getLists()
    {
        return $this->lists;
    }

    public function getList($list)
    {
        return $this->lists[$list];
    }

    public function getItems()
    {
        return $this->items;
    }

    public function getItem($item)
    {
        return $this->items[$item];
    }

    public function getFolders()
    {
        return $this->folders;
    }

    public function getFolder($folder)
    {
        return $this->folders[$folder];
    }

    public function processAutomatic()
    {
        // If the user sets a header, use it
        if (! is_null($this->header)) {
            $this->sidebar->addHeader(lang($this->header));
        }

        $sidebarItems = [];

        // For each available route, create an item
        foreach ($this->getAbstractRoutes() as $route) {
            // If we are excluding this from the sidebar, do it now
            if ($this->getProperty($route, 'exclude_from_sidebar')) {
                continue;
            }

            $route_path = $this->getProperty($route, 'route_path');

            // Get the items to set
            $sidebarItem = [
                'title' => $this->getProperty($route, 'sidebar_title') ?: $this->getProperty($route, 'cp_page_title'),
                'route_path' => $route_path,
                'cp_url' => ee('CP/URL')->make('addons/settings/' . $this->addon . '/' . $route_path),
                'sidebar_icon' => $this->getProperty($route, 'sidebar_icon'),
                'sidebar_is_folder' => $this->getProperty($route, 'sidebar_is_folder'),
                'sidebar_is_list' => $this->getProperty($route, 'sidebar_is_list'),
                'sidebar_divider_before' => $this->getProperty($route, 'sidebar_divider_before'),
                'sidebar_divider_after' => $this->getProperty($route, 'sidebar_divider_after'),
                'sidebar_priority' => $this->getProperty($route, 'sidebar_priority'),
            ];

            $sidebarItems[] = $sidebarItem;
        }

        // Sort the sidebar items by priority
        usort($sidebarItems, function ($a, $b) {
            return $b['sidebar_priority'] - $a['sidebar_priority'];
        });

        foreach ($sidebarItems as $sidebarItem) {
            // If there is supposed to be a divider before, add it now
            if ($sidebarItem['sidebar_divider_before']) {
                $this->sidebar->addDivider();
            }

            // If the item is a folder, create it as a folder
            if ($sidebarItem['sidebar_is_folder']) {
                $header = $this->sidebar->addHeader('');
                $currentItem = $header->addFolderList(lang($sidebarItem['title']));
                $item = $currentItem->addItem($sidebarItem['title'], $sidebarItem['cp_url']);
                $this->folders[$sidebarItem['route_path']] = $currentItem;

            // If the item is a list, create it that way
            } elseif ($sidebarItem['sidebar_is_list']) {
                $header = $this->sidebar->addHeader(lang($sidebarItem['title']));
                $currentItem = $header->addBasicList();

                // Add this to the lists array to easily access it later
                $this->lists[$sidebarItem['route_path']] = $currentItem;
            } else {
                // Create a normal sidebar item
                $currentItem = $this->sidebar->addItem(lang($sidebarItem['title']), $sidebarItem['cp_url']);

                // Set the icon if it exists
                if ($sidebarItem['sidebar_icon']) {
                    $currentItem->withIcon($sidebarItem['sidebar_icon']);
                }

                $this->items[$sidebarItem['route_path']] = $currentItem;
            }

            // If there is supposed to be a divider after, process it now
            if ($sidebarItem['sidebar_divider_after']) {
                $this->sidebar->addDivider();
            }

            $this->routes[$sidebarItem['route_path']] = $currentItem;
        }
    }

    protected function getProperty($class, $property)
    {
        $refClass = $this->getRouteClass($class);

        // The class doesnt have the property, so we return null
        if (! $refClass->hasProperty($property)) {
            return null;
        }

        // Get the property and set it to accessible
        $property = $refClass->getProperty($property);
        $property->setAccessible(true);

        // Get the value of the property
        return $property->getValue(new $class());
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

        // Locations of route folders we need to check
        $routeLocations = [
            [
                'path' => PATH_THIRD . $this->addon . '/ControlPanel/Routes/',
                'namespace' => $this->namespace . '\ControlPanel\Routes\\'
            ],
            [
                'path' => PATH_THIRD . $this->addon . '/Mcp/',
                'namespace' => $this->namespace . '\Mcp\\'
            ],
        ];

        foreach ($routeLocations as $routeLocation) {
            // If the directory doesnt exist, skip it
            if (! ee('Filesystem')->exists($routeLocation['path'])) {
                continue;
            }

            // Loop through each route file in that directory
            $contents = ee('Filesystem')->getDirectoryContents($routeLocation['path']);
            foreach ($contents as $c) {
                $class = $routeLocation['namespace'] . substr(basename($c), 0, -4);

                if (class_exists($class) && is_subclass_of($class, 'ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute')) {
                    $routes[] = $class;
                }
            }
        }

        return $routes;
    }
}
