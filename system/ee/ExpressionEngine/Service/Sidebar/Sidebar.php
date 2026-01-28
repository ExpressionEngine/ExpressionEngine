<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Sidebar;

use ExpressionEngine\Service\View\ViewFactory;

/**
 * Sidebar Service
 */
class Sidebar extends AbstractSidebar
{
    public $collapsedState;

    /**
     * Renders the sidebar
     *
     * @return string The rendered HTML of the sidebar
     */
    public function render()
    {
        $output = '';

        if (! empty($this->list)) {
            $output .= $this->list->render($this->view);
        }

        $is_legacy = false;

        foreach ($this->items as $item) {
            $output .= $item->render($this->view);

            // LEGACY: Check if the header has a link, if it does, the legacy sidebar styles need to be used.
            if ($item instanceof Header && $item->hasUrl()) {
                $is_legacy = true;
            }
        }

        if (! empty($this->action_bar)) {
            $output .= $this->action_bar->render($this->view);
        }

        if (empty($output)) {
            return '';
        }

        if ($is_legacy) {
            $this->class .= ' ';
        }

        //depending on where it is inserted, set class for owner
        $owner = ee()->uri->segment(2);
        if (ee()->uri->segment(2) == 'addons') {
            $owner = ee()->uri->segment(4);
        }
        $containerClass = ' secondary-sidebar__' . $owner;
        $state = json_decode(ee()->input->cookie('secondary_sidebar'));
        if (!empty($state) && isset($state->$owner) && $state->$owner == 1) {
            $containerClass = ' secondary-sidebar__collapsed';
            $this->collapsedState = true;
        }

        return $this->view->make('_shared/sidebar/sidebar')
            ->render([
                'class' => $this->class,
                'containerClass' => $containerClass,
                'owner' => $owner,
                'sidebar' => $output,
            ]);
    }

    /**
     * Adds a basic item to the sidebar
     *
     * @param string $text The text of the item
     * @param URL|string $url An optional CP\URL object or string containing the
     *   URL for the text.
     * @return Header A new BasicItem object.
     */
    public function addItem($text, $url = null)
    {
        $item = new BasicItem($text, $url);
        $this->items[] = $item;

        return $item;
    }

    /**
     * Adds a header to the sidebar
     *
     * @param string $text The text of the header
     * @param URL|string $url An optional CP\URL object or string containing the
     *   URL for the text.
     * @return Header A new Header object.
     */
    public function addHeader($text, $url = null)
    {
        $header = new Header($text, $url);
        $this->items[] = $header;

        return $header;
    }

    /**
     * Adds a divider to the sidebar
     *
     * @return Divider A new divider object.
     */
    public function addDivider()
    {
        $divider = new Divider();
        $this->items[] = $divider;

        return $divider;
    }

    /**
     * Adds a folder list to the sidebar, without a header
     *
     * @param string $name The name of the folder list
     * @return FolderList A new FolderList object
     */
    public function addFolderList($name)
    {
        $this->list = new FolderList($name);

        return $this->list;
    }

    /**
     * Adds a folder list under this header
     *
     * @param string $name The name of the folder list
     * @return FolderList A new FolderList object
     */
    public function addActionBar()
    {
        $this->action_bar = new ActionBar();

        return $this->action_bar;
    }

    /**
     * Adds some bottom margin to this sidebar
     *
     * @return self
     */
    public function addMarginBottom()
    {
        $this->class = ' mb';

        return $this;
    }

    /**
     * Gets the list
     *
     * @return BasicList
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * Gets the items
     *
     * @return mixed BasicItem|Header|Divider
     */
    public function getItems()
    {
        return $this->items;
    }

    public function getItemByUrl($url)
    {
        $matchedItem = null;

        // Loop through the items and search for
        foreach ($this->getItems() as $item) {
            // If the sidebar item implement urlMatches, lets check if it matches
            if (method_exists($item, 'urlMatches') && $item->urlMatches($url)) {
                $matchedItem = $item;
            }

            // If this is a header, search through the sub-items for the selected one
            if ($item instanceof Header) {
                $foundItem = $item->getItemByUrl($url);
                if (!is_null($foundItem)) {
                    $matchedItem = $foundItem;
                }
            }
        }

        // If there is a list
        if (!empty($this->getList())) {
            $foundItem = $this->getList()->getItemByUrl($url);
            if (!is_null($foundItem)) {
                $matchedItem = $foundItem;
            }
        }

        // If there is a list
        if (!empty($this->getList())) {
            $foundItem = $this->getList()->getItemByUrl($url);
            if (!is_null($foundItem)) {
                $matchedItem = $foundItem;
            }
        }

        return  $matchedItem;
    }
}

// EOF
