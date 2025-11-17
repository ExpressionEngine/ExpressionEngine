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
use ExpressionEngine\Library\CP\URL;

/**
 * Sidebar Header
 */
class Header
{
    /**
     * @var string $text The text of the header
     */
    protected $text;

    /**
     * @var string $class The class of the header
     */
    protected $class = '';

    /**
     * @var URL|string $url The URL to use as an href attribute
     */
    protected $url;

    /**
     * @var bool $url_is_external Flag for external URLs
     */
    protected $url_is_external = false;

    /**
     * @var array $button An array with a text and url key that defines a button
     */
    protected $button;

    /**
     * @var array $list Lists under this header
     */
    protected $list = array();

    /**
     * Constructor: sets the text and url properties of the header
     *
     * @param string $text The text of the header
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
     * Sets the URL property of the header
     *
     * @param URL|string $url A CP\URL object or string containing the
     *   URL for the header.
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
     * Returns true if the header has a url set
     *
     * @return bool
     * @deprecated Don't use
     */
    public function hasUrl()
    {
        return !empty($this->url);
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
     * Marks the header as active
     *
     * @return self This returns a reference to itself
     */
    public function isActive()
    {
        $this->class .= 'active ';

        return $this;
    }

    /**
     * Marks the header as inactive
     *
     * @return self This returns a reference to itself
     */
    public function isInactive()
    {
        $this->class = str_replace('active', '', $this->class);

        return $this;
    }

    /**
     * Sets the button property of the header
     *
     * @param string $text The text of the button
     * @param URL|string $url A CP\URL object or string containing the
     *   URL for the button.
     * @return self This returns a reference to itself
     */
    public function withButton($text, $url)
    {
        $this->button = array(
            'text' => $text,
            'url' => $url
        );

        return $this;
    }

    /**
     * Adds a basic list under this header
     *
     * @return BasicList A new BasicList object
     */
    public function addBasicList()
    {
        $this->list = new BasicList();

        return $this->list;
    }

    /**
     * Adds a folder list under this header
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
     * Renders this header. This should not be called directly. Instead use
     * the Sidebar's render method.
     *
     * @see Sidebar::render
     * @param ViewFactory $view A ViewFactory object to use with rendering
     * @return string The rendered HTML of the header and its lists
     */
    public function render(ViewFactory $view)
    {
        $vars = array(
            'text' => $this->text,
            'class' => $this->class,
            'url' => $this->url,
            'external' => $this->url_is_external,
            'button' => $this->button
        );

        $output = $view->make('_shared/sidebar/header')->render($vars);

        if (! empty($this->list)) {
            $output .= $this->list->render($view);
        }

        return $output;
    }

    /**
     * @return string the header list
     */
    public function getList()
    {
        return $this->list;
    }

    public function getItemByUrl($url)
    {
        // If there is a list
        if (!empty($this->getList())) {
            $item = $this->getList()->getItemByUrl($url);
            if (!is_null($item)) {
                return $item;
            }
        }

        // Item not found
        return null;
    }
}

// EOF
