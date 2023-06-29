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

use ExpressionEngine\Service\Sidebar\AbstractSidebar;
use ExpressionEngine\Service\Sidebar\Header;
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

        if (ee()->session->getMember()->getCPHomepageURL()->path == 'homepage') {
            $this->addItem(lang('nav_overview'), ee('CP/URL', 'homepage'))->withIcon('home');
        } else {
            $this->addItem(lang('nav_homepage'), ee()->session->getMember()->getCPHomepageURL())->withIcon('home');
            $this->addItem(lang('nav_overview'), ee('CP/URL', 'homepage'))->withIcon('tachometer-alt');
        }

        if (ee('Permission')->hasAny('can_edit_other_entries', 'can_edit_self_entries', 'can_create_entries', 'can_access_files') || (ee('Permission')->has('can_admin_channels') && ee('Permission')->hasAny('can_create_categories', 'can_edit_categories', 'can_delete_categories'))) {
            $section = $this->addSection(lang('nav_content'));

            if (ee('Permission')->hasAny('can_edit_other_entries', 'can_edit_self_entries', 'can_create_entries')) {
                $item = $section->addItem(lang('menu_entries'), ee('CP/URL', 'publish/edit'))->withIcon('newspaper')->addClass('js-dropdown-hover')->withAttributes('data-dropdown-use-root="true" data-dropdown-pos="right-start"');
                if (ee()->uri->segment(2) == 'publish') {
                    $item->isActive();
                }

                $allowed_channel_ids = (ee('Permission')->isSuperAdmin()) ? null : array_keys(ee()->session->userdata['assigned_channels']);

                $channels = ee('Model')->get('Channel', $allowed_channel_ids)
                    ->fields('channel_id', 'channel_title', 'max_entries', 'total_records')
                    ->filter('site_id', ee()->config->item('site_id'))
                    ->order('channel_title', 'ASC')
                    ->all();

                $list = $section->addList(lang('menu_entries'));

                if (count($channels)) {
                    $list->addItem('<i class="fal fa-eye"></i> ' . lang('view_all'), ee('CP/URL', 'publish/edit'))->withDivider();
                }

                foreach ($channels as $channel) {
                    $editLink = null;
                    $publishLink = null;
                    if (ee('Permission')->can('create_entries_channel_id_' . $channel->getId())) {
                        // Only add Create link if channel has room for more entries
                        if (!$channel->maxEntriesLimitReached()) {
                            $publishLink = ee('CP/URL')->make('publish/create/' . $channel->channel_id);
                        }
                    }
                    if (ee('Permission')->hasAny('can_edit_other_entries_channel_id_' . $channel->getId(), 'can_edit_self_entries_channel_id_' . $channel->getId())) {
                        $editLink = ee('CP/URL')->make('publish/edit', array('filter_by_channel' => $channel->channel_id));
                        // If there's a limit of 1, just send them to the edit screen for that entry
                        if ($channel->total_records == 1 && $channel->maxEntriesLimitReached()) {
                            $entry = ee('Model')->get('ChannelEntry')
                                ->fields('entry_id')
                                ->filter('channel_id', $channel->channel_id)
                                ->first();
                            // Just in case $channel->total_records is inaccurate
                            if ($entry) {
                                $editLink = ee('CP/URL')->make('publish/edit/entry/' . $entry->getId());
                            }
                        }
                    }
                    $listitem = $list->addItem($channel->channel_title, $editLink ?: '#');
                    if (!empty($publishLink)) {
                        $listitem->withAddLink($publishLink);
                    }
                }
            }
            if (ee('Permission')->has('can_access_files')) {
                $section->addItem(lang('menu_files'), ee('CP/URL', 'files'))->withIcon('archive');
            }
            if (ee('Permission')->has('can_admin_channels') && ee('Permission')->hasAny('can_create_categories', 'can_edit_categories', 'can_delete_categories')) {
                $section->addItem(lang('categories'), ee('CP/URL', 'categories'))->withIcon('tags');
            }
        }

        if (ee('Permission')->has('can_access_members')) {
            $section = $this->addSection(lang('members'));

            $item = $section->addItem(lang('members'), ee('CP/URL', 'members'))->withIcon('users');

            if (ee()->uri->segment(3) == 'roles') {
                $item->isInactive();
            }

            if (ee('Permission')->has('can_admin_roles') && ee('Permission')->hasAny('can_create_roles', 'can_edit_roles', 'can_delete_roles')) {
                $section->addItem(lang('roles'), ee('CP/URL', 'members/roles'))->withIcon('user-tag');
            }
        }

        $this->addCustomSection();

        if (ee('Permission')->hasAny('can_access_design', 'can_access_addons', 'can_access_utilities', 'can_access_logs', 'can_access_sys_prefs', 'can_create_channel_fields', 'can_edit_channel_fields', 'can_delete_channel_fields') || (ee('Permission')->has('can_admin_channels') && ee('Permission')->hasAny('can_create_channels', 'can_edit_channels', 'can_delete_channels')) || (ee()->config->item('multiple_sites_enabled') == 'y' && ee('Permission')->has('can_admin_sites'))) {
            $section = $this->addSection(lang('nav_developer'), 'dev');

            if (ee()->config->item('multiple_sites_enabled') == 'y' && ee('Permission')->has('can_admin_sites')) {
                $section->addItem(lang('msm_manager'), ee('CP/URL')->make('msm'))->withIcon('globe');
            }

            if (
                ee('Permission')->has('can_admin_channels') &&
                ee('Permission')->hasAny(
                    'can_create_channels',
                    'can_edit_channels',
                    'can_delete_channels'
                )
            ) {
                $section->addItem(lang('channels'), ee('CP/URL')->make('channels'))->withIcon('sitemap');
            }

            if (
                ee('Permission')->hasAny(
                    'can_create_channel_fields',
                    'can_edit_channel_fields',
                    'can_delete_channel_fields'
                )
            ) {
                $section->addItem(lang('fields'), ee('CP/URL')->make('fields'))->withIcon('pen-field');
            }

            if (ee('Permission')->has('can_access_design')) {
                $section->addItem(lang('templates'), ee('CP/URL')->make('design'))->withIcon('file-code');
            }

            $tools = [];

            if (ee('Permission')->has('can_access_utilities')) {
                $utility_options = array(
                    'can_access_comm' => ee('CP/URL')->make('utilities'),
                    'can_access_translate' => ee('CP/URL')->make('utilities/translate'),
                    'can_access_import' => ee('CP/URL')->make('utilities/member-import'),
                    'can_access_sql_manager' => ee('CP/URL')->make('utilities/sql'),
                    'can_access_data' => ee('CP/URL')->make('utilities/cache')
                );

                foreach ($utility_options as $allow => $link) {
                    if (ee('Permission')->hasAll($allow)) {
                        $tools['utilities'] = $link;

                        break;
                    }
                }

                // If none of the above are allowed, see if add-on admin is
                // If so, land on extension debug page

                if (! isset($tools['utilities'])) {
                    if (ee('Permission')->has('can_access_addons') && ee('Permission')->has('can_admin_addons')) {
                        $tools['utilities'] = ee('CP/URL')->make('utilities/extensions');
                    }
                }
            }

            if (ee('Permission')->has('can_access_logs')) {
                $tools['logs'] = ee('CP/URL')->make('logs');
            }

            if (!empty($tools)) {
                $item = $section->addItem(lang('nav_tools'), current($tools))->withIcon('tools')->addClass('js-dropdown-hover')->withAttributes('data-dropdown-use-root="true" data-dropdown-pos="right-start"');
                $devMenu = $section->addList(lang('nav_tools'));
                if (in_array(ee()->uri->segment(2), ['utilities', 'logs'])) {
                    $item->isActive();
                }

                foreach ($tools as $name => $link) {
                    $devMenu->addItem(lang($name), $link);
                }
            }

            if (ee('Permission')->has('can_access_addons')) {
                $section->addItem(lang('addons'), ee('CP/URL', 'addons'))->withIcon('puzzle-piece');
            }

            if (ee('Permission')->has('can_access_sys_prefs')) {
                $section->addItem(lang('nav_settings'), ee('CP/URL', 'settings'))->withIcon('cog');
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

        $output = '';

        foreach ($this->items as $item) {
            $output .= $item->render($this->view);
        }

        return $this->view->make('_shared/sidebar/navigation/sidebar')
            ->render([
                'class' => $this->class,
                'sidebar' => $output
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
     * Adds a section
     *
     * @param string $name The name of the folder list
     * @return NavigationSection A new NavigationSection object
     */
    public function addSection($name = '', $class = 'section')
    {
        $item = new NavigationSection($name, $class);
        $this->items[] = $item;

        return $item;
    }

    /**
     * Adds "custom" section
     *
     * @return NavigationCustomSection A new NavigationCustomSection object
     */
    public function addCustomSection()
    {
        $item = new NavigationCustomSection();
        $this->items[] = $item;

        return $item;
    }
}

// EOF
