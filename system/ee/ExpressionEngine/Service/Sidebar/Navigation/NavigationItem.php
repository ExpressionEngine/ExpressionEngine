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
use ExpressionEngine\Library\CP\URL;

/**
 * Main Sidebar Navigation Item
 */
class NavigationItem extends ListItem
{
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
            'attrs' => $attrs,
            'class' => $class,
            'icon' => $this->icon
        );

        return $view->make('_shared/sidebar/navigation/item')->render($vars);
    }
}

// EOF
