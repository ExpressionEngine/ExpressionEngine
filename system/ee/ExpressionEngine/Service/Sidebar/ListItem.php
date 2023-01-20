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

use ExpressionEngine\Service\View\View;
use ExpressionEngine\Library\CP\URL;

/**
 * Siebar List Item
 */
abstract class ListItem
{
    /**
     * @var string $text The text of the item
     */
    protected $text;

    /**
     * @var string $text Optional icon for the item
     */
    protected $icon;

    /**
     * @var URL|string $url The URL to use as an href attribute
     */
    protected $url;

    /**
     * @var bool $url_is_external Flag for external URLs
     */
    protected $url_is_external = false;

    /**
     * @var array $class The class of the item
     */
    protected $class = array();

    /**
     * @var string $attributes Extra attributes
     */
    protected $attributes = '';

    /**
     * Constructor: sets the text and url properties of the item
     *
     * @param string $text The text of the item
     * @param URL|string $url An optional CP\URL object or string containing the
     *   URL for the text.
     */
    public function __construct($text, $url = null)
    {
        $this->text = $text;
        if ($url) {
            $this->withUrl($url);
        }
    }

    /**
     * Sets the URL property of the item
     *
     * @param URL|string $url A CP\URL object or string containing the
     *   URL for the item.
     * @return self This returns a reference to itself
     */
    public function withUrl($url)
    {
        $this->url = $url;
        if ($url instanceof URL && $url->isTheRequestedURI()) {
            $this->isActive();
        }

        return $this;
    }

    /**
     * Sets the $url_is_external property
     *
     * @param bool $external (optional) TRUE if it is external, FALSE if not
     * @return self This returns a reference to itself
     */
    public function urlIsExternal($external = true)
    {
        $this->url_is_external = $external;

        return $this;
    }

    /**
     * Sets the icon of the item
     *
     * @param string $icon Name of the icon
     * @return self This returns a reference to itself
     */
    public function withIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Sets extra HTML attributes
     *
     * @param string $attributes Atrributes string
     * @return self This returns a reference to itself
     */
    public function withAttributes($attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Adds a class to the class array
     *
     * @return self This returns a reference to itself
     */
    public function addClass($class)
    {
        $this->class[$class] = true;

        return $this;
    }

    /**
     * Removes a class to the class array
     *
     * @return self This returns a reference to itself
     */
    public function removeClass($class)
    {
        if (isset($this->class[$class])) {
            unset($this->class[$class]);
        }

        return $this;
    }

    /**
     * Converts the class array into a space delimited string.
     *
     * @return string All the classes separated by spaces.
     */
    public function getClass()
    {
        return implode(' ', array_keys($this->class));
    }

    /**
     * Marks the item as active
     *
     * @return self This returns a reference to itself
     */
    public function isActive()
    {
        return $this->addClass('active');
    }

    /**
     * Marks the item as inactive
     *
     * @return self This returns a reference to itself
     */
    public function isInactive()
    {
        return $this->removeClass('active');
    }

    /**
     * Marks the item as selected
     *
     * @return self This returns a reference to itself
     */
    public function isSelected()
    {
        return $this->addClass('selected');
    }

    /**
     * Marks the item as not selected
     *
     * @return self This returns a reference to itself
     */
    public function isDeselected()
    {
        return $this->removeClass('selected');
    }

    /**
     * Gets the item's text
     *
     * @return string $text
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Gets the item's url
     *
     * @return string $url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Checks to see if the item's url matches the passed url
     *
     * @return bool
     */
    public function urlMatches($url)
    {
        return ((string) $this->getUrl() === (string) $url);
    }
}

// EOF
