<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Sidebar;

use ExpressionEngine\Service\View\ViewFactory;

/**
 * Sidebar Service
 */
class Sidebar extends AbstractSidebar
{
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

        return $this->view->make('_shared/sidebar/sidebar')
            ->render([
                'class' => $this->class,
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
}

// EOF
