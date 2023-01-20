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
abstract class AbstractSidebar
{
    /**
     * @var array $items The items in this sidebar
     */
    protected $items = [];

    /**
     * @var ViewFactory $view A ViewFactory object with which we will render the sidebar.
     */
    protected $view;

    /**
     * @var FolderList $list Primary folder list for this sidebar
     */
    protected $list;

    /**
     * @var ActionBar $action_bar Primary action bar for this sidebar
     */
    protected $action_bar;

    /**
     * @var string $class Any extra classes to apply to the containing div
     */
    protected $class;

    /**
     * Constructor: sets the ViewFactory property
     *
     * @param ViewFactory $view A ViewFactory object to use with rendering
     */
    public function __construct(ViewFactory $view)
    {
        $this->view = $view;
    }

    /**
     * Syntactic sugar ¯\_(ツ)_/¯
     */
    public function make()
    {
        return $this;
    }

    /**
     * Creates a new Sidebar object for when the singleton won't do
     */
    public function makeNew()
    {
        return new static($this->view);
    }

    /**
     * Renders the sidebar
     *
     * @return string The rendered HTML of the sidebar
     */
    abstract public function render();

    /**
     * Adds a basic item to the sidebar
     *
     * @param string $text The text of the item
     * @param URL|string $url An optional CP\URL object or string containing the
     *   URL for the text.
     * @return Header A new BasicItem object.
     */
    abstract public function addItem($text, $url = null);
}

// EOF
