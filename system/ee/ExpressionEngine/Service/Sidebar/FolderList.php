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
 * Sidebar FolderList
 */
class FolderList
{
    /**
     * @var string $name The name of this folder list
     */
    protected $name;

    /**
     * @var array $items Items in the list
     */
    protected $items = array();

    /**
     * @var URL|string $remove_url The URL to use as an href attribute
     */
    protected $remove_url = '';

    /**
     * @var string $removal_key The data attribute to use when removing an item
     */
    protected $removal_key = 'id';

    /**
     * @var string $no_results The text to display when the list(s) are empty.
     */
    protected $no_results = '';

    /**
     * @var boolean $can_reorder Whether or not the folder list can be reordered
     */
    protected $can_reorder = false;

    /**
     * @var string Extra confirmation message or toggle to delete
     */
    protected $remove_confirmation;

    /**
     * Constructor: sets the name of the list
     *
     * @param string $text The text of the header
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Sets the URL to use when removing an item
     *
     * @param URL|string $url A CP\URL object or string containing the
     *   URL to use when removing an item.
     * @return self This returns a reference to itself
     */
    public function withRemoveUrl($url)
    {
        $this->remove_url = $url;

        return $this;
    }

    /**
     * Sets removal conformation HTML
     *
     * @param string $html
     * @return self
     */
    public function withRemoveConfirmation($html)
    {
        $this->remove_confirmation = $html;

        return $this;
    }

    /**
     * Sets the name of variable passed with the removal action
     *
     * @param string $key The name of the variable with
     * @return self This returns a reference to itself
     */
    public function withRemovalKey($key)
    {
        $this->removal_key = $key;

        return $this;
    }

    /**
     * Sets the no results text which will display if this header's list(s) are
     * empty.
     *
     * @param string $msg The text to display when the list(s) are empty.
     * @return self This returns a reference to itself
     */
    public function withNoResultsText($msg)
    {
        $this->no_results = $msg;

        return $this;
    }

    /**
     * Allows the folder list to be reordered
     *
     * @return self This returns a reference to itself
     */
    public function canReorder()
    {
        $this->can_reorder = true;

        return $this;
    }

    /**
     * Adds an item to this list
     *
     * @param string $text The text of the item
     * @param URL|string $url An optional CP\URL object or string containing the
     *   URL for the item.
     * @return BasicItem A new BasicItem object
     */
    public function addItem($text, $url = null)
    {
        $item = new FolderItem($text, $url, $this->name, $this->removal_key);
        $this->items[] = $item;

        return $item;
    }

    /**
     * Renders this list. This should not be called directly. Instead use
     * the Sidebar's render method.
     *
     * @see Sidebar::render
     * @param ViewFactory $view A ViewFactory object to use with rendering
     * @return string The rendered HTML of the list and its items
     */
    public function render(ViewFactory $view)
    {
        $items = '';

        foreach ($this->items as $item) {
            $items .= $item->render($view);
        }

        if (empty($items) && $this->no_results) {
            $items = '<div class="no-results">' . $this->no_results . '</div>';
        }

        return $view->make('_shared/sidebar/folder_list')
            ->render(array(
                'items' => $items,
                'name' => $this->name,
                'remove_url' => $this->remove_url,
                'removal_key' => $this->removal_key,
                'remove_confirmation' => $this->remove_confirmation,
                'can_reorder' => $this->can_reorder
            ));
    }

    /**
     * Gets an item in the list from the url
     *
     * @param string $url The url of the item to search for
     * @return FolderItem The searched for item
     */
    public function getItemByUrl($url)
    {
        foreach ($this->items as &$item) {
            if (method_exists($item, 'urlMatches') && $item->urlMatches($url)) {
                return $item;
            }
        }

        // Item not found
        return null;
    }

    /**
     * Gets all items
     *
     * @return array of items in the basic list
     */
    public function getItems()
    {
        return $this->items;
    }
}

// EOF
