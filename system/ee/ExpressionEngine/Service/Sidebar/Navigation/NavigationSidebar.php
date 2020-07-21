<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Sidebar\Navigation;

use ExpressionEngine\Service\Sidebar\AbstractSidebar;
use ExpressionEngine\Service\View\ViewFactory;

/**
 * Main Navigation Sidebar Service
 */
class NavigationSidebar extends AbstractSidebar
{

    /**
     * Populate the navigation
     *
     * @return Array Array of sidebar items
     */
    private function populateItems()
    {
        if (!empty($this->items)) {
            return $this->items;
        }

        $cp_main_menu = ee()->menu->generate_menu();

        $this->addItem(lang('nav_overview'), ee('CP/URL', 'homepage'))->withIcon('tachometer-alt');
        if (ee('Permission')->hasAny('can_edit_other_entries', 'can_edit_self_entries', 'can_create_entries')) {
            $item = $this->addItem(lang('menu_entries'), ee('CP/URL', 'publish/edit'))->withIcon('newspaper')->addClass('js-dropdown-hover')->withAttributes('data-dropdown-use-root="true" data-dropdown-pos="right-start"');
            if (ee()->uri->segment(2) == 'publish') {
                $item->isActive();
            }

            $list = $this->addList(lang('menu_entries'));
            $list->addItem(lang('view_all'), ee('CP/URL', 'publish/edit'));
            foreach ($cp_main_menu['channels']['all'] as $channel_name => $link) {
                $url = isset($cp_main_menu['channels']['edit'][$channel_name]) ? $cp_main_menu['channels']['edit'][$channel_name] : '#';
                $listitem = $list->addItem($channel_name, $url);
                if (ee('Permission')->can('create_entries') && array_key_exists($channel_name, $cp_main_menu['channels']['create'])) {
                    $listitem->withAddLink($cp_main_menu['channels']['create'][$channel_name]);
                }
            }
        }
        if (ee('Permission')->can('access_files')) {
            $this->addItem(lang('menu_files'), ee('CP/URL', 'files'))->withIcon('folder');
        }
        if (ee('Permission')->can('access_members')) {
            $this->addItem(lang('members'), ee('CP/URL', 'members'))->withIcon('users');
        }
        if (ee('Permission')->can('admin_channels') && ee('Permission')->hasAny('can_create_categories', 'can_edit_categories', 'can_delete_categories')) {
            $this->addItem(lang('categories'), ee('CP/URL', 'categories'))->withIcon('tags');
        }
        if (ee('Permission')->can('access_addons')) {
            $this->addItem(lang('addons'), ee('CP/URL', 'addons'))->withIcon('puzzle-piece');
        }
    }

    /**
     * Renders the sidebar
     *
     * @return string The rendered HTML of the sidebar
     */
    public function render()
    {
        if (empty($this->items)) {
            $this->populateItems();
        }

        $output = '';

        foreach ($this->items as $item) {
            $output .= $item->render($this->view);
        }

        return $this->view->make('_shared/sidebar/navigation/sidebar')
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
     * @return NavigationItem A new NavigationItem object.
     */
    public function addItem($text, $url = NULL)
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
}

// EOF
