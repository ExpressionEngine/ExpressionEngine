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
 * Sidebar FolderItem
 */
class FolderItem extends ListItem
{
    /**
     * @var URL|string $edit_url The URL to use as an href attribute
     */
    protected $edit_url = '';

    /**
     * @var string $name The name of the folder list this item belongs to
     */
    protected $name;

    /**
     * @var string $remove_confirmation The message that will be displayed as
     *  the confirmation when attempting to remove this item
     */
    protected $remove_confirmation;

    /**
     * @var string $removal_key The data attribute name to use when removing an item
     */
    protected $removal_key;

    /**
     * @var string $removal_key The value to place in the data attribute for use
     *  when removing an item
     */
    protected $removal_key_value;

    /**
     * @var bool $has_edit Whether or not to render an edit button
     */
    protected $has_edit = true;

    /**
     * @var bool $has_remove Whether or not to render a remove button
     */
    protected $has_remove = true;

    /**
     * Constructor: Sets the text, url, name, and removal key for this item.
     *
     * @see ListItem::__construct()
     * @param string $text The text of the item
     * @param URL|string $url An optional CP\URL object or string containing the
     *   URL for the text.
     * @param string $name The name of the folder list this item belongs to
     * @param string $removal_key The data attribute name to use when removing an item
     */
    public function __construct($text, $url, $name, $removal_key)
    {
        parent::__construct($text, $url);

        $this->name = $name;
        $this->removal_key = $removal_key;
    }

    /**
     * Marks the item as default
     *
     * @return self This returns a reference to itself
     */
    public function asDefaultItem()
    {
        $this->addClass('default');

        return $this;
    }

    /**
     * Sets the edit URL property of the item
     *
     * @param URL|string $url A CP\URL object or string containing the
     *   URL in order to edit the item.
     * @return self This returns a reference to itself
     */
    public function withEditUrl($url)
    {
        $this->edit_url = $url;

        return $this;
    }

    /**
     * Sets the has an edit button property to TRUE
     *
     * @return self This returns a reference to itself
     */
    public function canEdit()
    {
        $this->has_edit = true;

        return $this;
    }

    /**
     * Sets the has an edit button property to FALSE
     *
     * @return self This returns a reference to itself
     */
    public function cannotEdit()
    {
        $this->has_edit = false;

        return $this;
    }

    /**
     * Sets the has an remove button property to TRUE
     *
     * @return self This returns a reference to itself
     */
    public function canRemove()
    {
        $this->has_remove = true;

        return $this;
    }

    /**
     * Sets the has an remove button property to FALSE
     *
     * @return self This returns a reference to itself
     */
    public function cannotRemove()
    {
        $this->has_remove = false;

        return $this;
    }

    /**
     * Sets the remove confirmation message for this item.
     *
     * @var string $msg The message that will be displayed as the confirmation
     *   when attempting to remove this item
     * @return self This returns a reference to itself
     */
    public function withRemoveConfirmation($msg)
    {
        $this->remove_confirmation = $msg;

        return $this;
    }

    /**
     * Sets the identity value for this item which is used when this item is
     * removed.
     *
     * @param string $val The value to place in the data attribute for use
     *  when removing an item
     * @return self This returns a reference to itself
     */
    public function identifiedBy($val)
    {
        $this->removal_key_value = $val;

        return $this;
    }

    /**
     * Renders this item. This should not be called directly. Instead use
     * the Sidebar's render method.
     *
     * @see Sidebar::render
     * @param ViewFactory $view A ViewFactory object to use with rendering
     * @return string The rendered HTML of the item
     */
    public function render(ViewFactory $view)
    {
        $class = $this->getClass();

        $vars = array(
            'text' => $this->text,
            'url' => $this->url,
            'external' => $this->url_is_external,
            'class' => $class,
            'edit' => $this->has_edit && !empty($this->edit_url),
            'remove' => $this->has_remove && !empty($this->remove_confirmation),
            'edit_url' => $this->edit_url,
            'modal_name' => $this->name,
            'confirm' => $this->remove_confirmation,
            'key' => $this->removal_key,
            'value' => $this->removal_key_value,
            'icon' => $this->icon
        );

        return $view->make('_shared/sidebar/folder_item')->render($vars);
    }
}

// EOF
