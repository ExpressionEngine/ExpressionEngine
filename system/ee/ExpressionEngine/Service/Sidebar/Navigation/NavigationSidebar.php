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

    private $bottomItems;

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
            $list->addItem('<i class="fas fa-eye"></i> ' . lang('view_all'), ee('CP/URL', 'publish/edit'))->withDivider();

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
     * Populate developer menu
     *
     * @return Array sidebar developer menu items
     */
    private function populateBottomItems()
    {
        if (!empty($this->bottomItems)) {
            return $this->bottomItems;
        }

        $devItems = [];
        if (
            ee('Permission')->can('admin_channels') &&
            ee('Permission')->hasAny(
                'can_create_channels',
                'can_edit_channels',
                'can_delete_channels',
                'can_create_channel_fields',
                'can_edit_channel_fields',
                'can_delete_channel_fields',
                'can_create_statuses',
                'can_delete_statuses',
                'can_edit_statuses',
                'can_create_categories',
                'can_edit_categories',
                'can_delete_categories'
            )
        ) {
            $sections = array(
                'channels' => 'channels',
                'channel_fields' => 'fields'
            );

            foreach ($sections as $name => $path) {
                if (
                    ee('Permission')->hasAny(
                        "can_create_{$name}",
                        "can_edit_{$name}",
                        "can_delete_{$name}"
                    )
                ) {
                    $name = $name == 'channel_fields' ? 'fields' : $name;
                    $devItems[$name] = ee('CP/URL')->make($path);
                }
            }
        }

        if (ee('Permission')->can('access_design')) {
            $devItems['templates'] = ee('CP/URL')->make('design');
        }

        if (ee()->config->item('multiple_sites_enabled') == 'y' && ee('Permission')->can('admin_sites')) {
            $devItems['msm_manager'] = ee('CP/URL')->make('msm');
        }

        if (ee('Permission')->can('access_utilities')) {

            $utility_options = array(
                'can_access_comm' => ee('CP/URL')->make('utilities'),
                'can_access_translate' => ee('CP/URL')->make('utilities/translate'),
                'can_access_import' => ee('CP/URL')->make('utilities/member-import'),
                'can_access_sql_manager' => ee('CP/URL')->make('utilities/sql'),
                'can_access_data' => ee('CP/URL')->make('utilities/cache')
                );

            foreach ($utility_options as $allow => $link) {
                if (ee('Permission')->hasAll($allow)) {
                    $devItems['utilities'] = $link;
                    break;
                }
            }

            // If none of the above are allowed, see if addon admin is
            // If so, land on extension debug page

            if (! isset($devItems['utilities'])) {
                if (ee('Permission')->can('access_addons') && ee('Permission')->can('admin_addons')) {
                    $devItems['utilities'] = ee('CP/URL')->make('utilities/extensions');
                }
            }
        }

        if (ee('Permission')->can('access_logs')) {
            $devItems['logs'] = ee('CP/URL')->make('logs');
        }

        if (!empty($devItems)) {
            $item = $this->addBottomItem(lang('nav_developer'), ee('CP/URL')->make('channels'))->withIcon('database')->addClass('js-dropdown-hover')->withAttributes('data-dropdown-use-root="true" data-dropdown-pos="right-start"');
            $devMenu = $this->addBottomList(lang('developer'));
            if (in_array(ee()->uri->segment(2), ['fields', 'channels', 'design', 'msm', 'utilities', 'logs'])) {
                $item->isActive();
            }
            foreach ($devItems as $name => $link) {
                $devMenu->addItem(lang($name), $link);
            }
        }

        if (ee('Permission')->can('access_sys_prefs')) {
            $item = $this->addBottomItem(lang('nav_settings'), ee('CP/URL', 'settings'))->withIcon('cog');
            if (ee()->uri->segment(2) == 'settings') {
                $item->isActive();
            }
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

        if (empty($this->bottomItems)) {
            $this->populateBottomItems();
        }

        $output = '';
        $bottom = '';

        foreach ($this->items as $item) {
            $output .= $item->render($this->view);
        }

        if (!empty($this->bottomItems)) {
            foreach ($this->bottomItems as $item) {
                $bottom .= $item->render($this->view);
            }
        }

        return $this->view->make('_shared/sidebar/navigation/sidebar')
            ->render([
                'class' => $this->class,
                'sidebar' => $output,
                'bottom' => $bottom
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
     * Adds a basic item to the bottom of sidebar
     *
     * @param string $text The text of the item
     * @param URL|string $url An optional CP\URL object or string containing the
     *   URL for the text.
     * @return NavigationItem A new NavigationItem object.
     */
    public function addBottomItem($text, $url = NULL)
    {
        $item = new NavigationItem($text, $url);
        $this->bottomItems[] = $item;
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
     * Adds a list to the sidebar
     *
     * @param string $name The name of the folder list
     * @return NavigationList A new NavigationList object
     */
    public function addBottomList($name)
    {
        $item = new NavigationList($name);
        $this->bottomItems[] = $item;
        return $item;
    }
}

// EOF
