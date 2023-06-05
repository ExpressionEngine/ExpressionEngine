<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Sidebar\Navigation;

use ExpressionEngine\Service\Sidebar\ListItem;
use ExpressionEngine\Service\View\ViewFactory;

/**
 * Main Sidebar Navigation Item
 */
class NavigationListItem extends ListItem
{
    /**
     * @var URL|string $url The URL to use as an 'add' link
     */
    protected $addlink;

    protected $addlinkAttibutes;

    /**
     * @var bool $divider Whether to display divider element below item
     */
    protected $divider = false;

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
        if ($url instanceof URL && $url->matchesTheRequestedURI()) {
            $this->isActive();
        }

        return $this;
    }

    /**
     * Sets the addlink property of the item
     *
     * @param URL|string $url A CP\URL object or string containing the
     *   URL for the item.
     * @return self This returns a reference to itself
     */
    public function withAddLink($url, $attrs = '')
    {
        $this->addlink = $url;

        $this->addlinkAttibutes = $attrs;

        return $this;
    }

    /**
     * Sets the divider property of the item
     *
     * @return self This returns a reference to itself
     */
    public function withDivider()
    {
        $this->divider = true;

        return $this;
    }

    /**
     * Renders this item. This should not be called directly. Instead use
     * the NavigationSidebar's render method.
     *
     * @see NavigationSidebar::render
     * @param ViewFactory $view A ViewFactory object to use with rendering
     * @return string The rendered HTML of the item
     */
    public function render(ViewFactory $view)
    {
        $class = $this->getClass();

        $attrs = $this->attributes;
        if ($this->url_is_external) {
            $attrs .= ' rel="external"';
        }

        $vars = array(
            'text' => $this->text,
            'url' => $this->url,
            'addlink' => $this->addlink,
            'addlinkAttibutes' => $this->addlinkAttibutes,
            'divider' => $this->divider,
            'attrs' => $attrs,
            'class' => $class,
            'icon' => $this->icon
        );

        return $view->make('_shared/sidebar/navigation/list_item')->render($vars);
    }
}

// EOF
