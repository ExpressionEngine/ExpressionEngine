<?php

namespace ExpressionEngine\Structure\Conduit;

@include_once PATH_ADDONS . 'structure/Conduit/McpNav.php';

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
abstract class FluxNav
{
    private $items = array();
    private $buttons = array();
    private $active_map = array();

    public $sidebar;
    public $nav_items;
    public $active_item;
    public $last_seg;
    public $active_title;

    public function __construct()
    {
        $last_seg = ee()->uri->segment_array();
        $this->last_seg = end($last_seg);

        $this->preGenerateNav();

        // Set all the nav data
        $this->items = $this->defaultItems() ?: array();
        $this->buttons = $this->defaultButtons() ?: array();
        $this->active_map = $this->defaultActiveMap() ?: array();

        // We need to format the items now
        $this->formatItems();

        // Allow the user to defer generation
        if (! $this->deferGenerate()) {
            $this->generateNav();
            $this->postGenerateNav();
        }
    }

    /**
     * [generateNav description]
     * @return [type] [description]
     */
    public function generateNav()
    {
        $this->sidebar = ee('CP/Sidebar')->make();
        $this->itemsToNav();
        $this->buttonsToItems();
        $this->setActive();
        $this->setBreadcrumb();
    }

    public function formatItems()
    {
        // Formats the items to use 'index' rather than '/' or ''
        $items = array();
        foreach ($this->items as $k => $item) {
            if ($k === '' || $k === '/') {
                $items['index'] = $item;
            } else {
                $items[$k] = $item;
            }
        }
        $this->items = $items;

        // Formats the map to use 'index' rather than '/' or ''
        $activeMap = array();
        foreach ($this->active_map as $url => $mapto) {
            if ($mapto === '' || $mapto === '/') {
                $activeMap[$url] = 'index';
            } else {
                $activeMap[$url] = $mapto;
            }
        }
        $this->active_map = $activeMap;

        // Check if we have an index method, and no map for the shortname.
        // Then set the shortname to map to index
        $hasIndex = isset($this->items['index']);
        $missingShortname = !isset($this->active_map['structure']);

        if ($hasIndex && $missingShortname) {
            $this->active_map['structure'] = 'index';
        }
    }

    public function itemsToNav()
    {
        // If the url starts with HEADING, it is a heading instead of a link
        $noUrl = 'HEADING';

        // Set each nav item
        foreach ($this->items as $method => $title) {
            $this->nav_items[$method] = $this->sidebar->addHeader($title);

            // If the method starts with "HEADING", dont add a url
            if (substr($method, 0, strlen($noUrl)) !== $noUrl) {
                $this->nav_items[$method]->withUrl($this->getUrl($method));
            }
        }
    }

    public function buttonsToItems()
    {
        // Set all buttons on the nav items
        foreach ($this->buttons as $method => $button) {
            foreach ($button as $button_method => $title) {
                $this->nav_items[$method]->withButton($title, ee('CP/URL')->make('addons/settings/structure/' . $button_method));
            }
        }
    }

    public function setActive()
    {
        // Get the last segment in the URL. This is either the method inside the add-on (like /history or /validation)
        // and see if it has a corresponding nav item assigned to it.
        if (isset($this->nav_items[$this->last_seg])) {
            // Get the title for this nav_item. The `items` is a key => value array whereas the nav_items are
            // actual items inside the sidebar instance.
            $this->active_title = $this->items[$this->last_seg];

            // Set the nav item that corresponds to the last segment as the active one.
            $this->nav_items[$this->last_seg]->isActive();

            // Passes a reference to the actual active item inside the sidebar instance so you can
            // perform modifications directly on the item (like $this->active_item->addBasicList()).
            $this->active_item = &$this->nav_items[$this->last_seg];
        }

        // Same as above except the `active_map` is a custom override if we have aliases or sub-items
        // where we want the parent item to also be marked as active.
        if (isset($this->active_map[$this->last_seg])) {
            $this->active_title = $this->items[$this->active_map[$this->last_seg]];
            $this->nav_items[$this->active_map[$this->last_seg]]->isActive();

            // Passes a reference to the actual active item inside the sidebar instance so you can
            // perform modifications directly on the item (like $this->active_item->addBasicList()).
            $this->active_item = &$this->nav_items[$this->active_map[$this->last_seg]];
        }
    }

    public function setBreadcrumb()
    {
        if (isset($this->active_title)) {
            ee()->view->cp_page_title = $this->active_title;
            ee()->cp->set_breadcrumb(ee('CP/URL', 'addons/settings/structure'), 'Structure');
        }
    }

    public function getActiveItem()
    {
        return $this->active_item;
    }

    public function deferGenerate()
    {
        return false;
    }

    public function preGenerateNav()
    {
    }

    public function postGenerateNav()
    {
    }

    // // This allows returning any property, even though they are private
    // public function __get($property)
    // {
    //     if (property_exists($this, $property)) {
    //         return $this->$property;
    //     }

    //     return null;
    // }

    // public function navHeaders()
    // {
    //     $this->sidebar = ee('CP/Sidebar')->make();
    //     $sidebar = new \ReflectionClass(ee('CP/Sidebar'));
    //     $headers = $sidebar->getProperty('headers');
    //     $headers->setAccessible(true);
    //     $this->headers = $headers->getValue($this->sidebar);
    //     return $this->headers;
    // }

    public function getUrl($method = 'index')
    {
        if ($method == '/') {
            $method = 'index';
        }

        if (strpos($method, 'http') === false) {
            $url = ee('CP/URL', 'addons/settings/structure/' . $method);
        } else {
            $url = $method;
        }

        return $url;
    }

    protected function defaultItems()
    {
        return array();
    }

    protected function defaultButtons()
    {
        return array();
    }

    protected function defaultActiveMap()
    {
        return array();
    }

    public function setToolbarIcon($title = null, $url = 'settings', $icon = 'settings')
    {
        if (!$title) {
            $title = 'Structure';
        }

        ee()->view->header = array(
            'title' => $title,
            'toolbar_items' => array(
                $icon => array(
                    'href' => $this->getUrl($url),
                    'title' => $title
                )
            )
        );
    }
}
