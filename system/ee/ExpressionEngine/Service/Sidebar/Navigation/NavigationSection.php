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

use ExpressionEngine\Service\View\ViewFactory;

/**
 * Sidebar NavigationSection
 */
class NavigationSection
{
    /**
     * @var string $header Section header text
     */
    protected $header = '';

    /**
     * @var string $class Section container class suffix
     */
    protected $class_suffix = 'section';

    /**
     * @var array $items Items in the list
     */
    protected $items = array();

    /**
     * set header if provided
     */
    public function __construct($header = '', $class_suffix = 'section')
    {
        $this->header = $header;
        $this->class_suffix = $class_suffix;
    }

    /**
     * Adds a basic item to the sidebar
     *
     * @param string $text The text of the item
     * @param URL|string $url An optional CP\URL object or string containing the
     *   URL for the text.
     * @return NavigationItem A new NavigationItem object.
     */
    public function addItem($text, $url = null)
    {
        $item = new NavigationItem($text, $url);
        $this->items[] = $item;

        return $item;
    }

    /**
     * Adds a list to the sidebar
     *
     * @param string $name The name of the folder list
     * @return NavigationList A new NavigationList object
     */
    public function addList($name)
    {
        $item = new NavigationList($name);
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

        return $view->make('_shared/sidebar/navigation/section')
            ->render(array(
                'items' => $items,
                'header' => $this->header,
                'class_suffix' => $this->class_suffix
            ));
    }
}

// EOF
