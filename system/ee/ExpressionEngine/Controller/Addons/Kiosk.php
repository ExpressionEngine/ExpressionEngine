<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Addons;

use CP_Controller;
use Cache;
use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Service\Addon\Mcp;

/**
 * Addons Kiosk Controller
 */
class Kiosk extends CP_Controller
{
    public $perpage = 25;
    public $params = array();
    public $base_url;

    public $assigned_modules = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        if (! ee('Permission')->can('admin_addons')) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->lang->loadfile('addons');

        $this->params['perpage'] = $this->perpage; // Set a default

        $this->base_url = ee('CP/URL')->make('addons/kiosk');

    }

    /**
     * Index function
     *
     * @return  void
     */
    public function index()
    {

        ee()->view->cp_page_title = lang('addon_manager');

        $return_url = ee('CP/URL')->getCurrentUrl();
        $vars['base_url'] = $this->base_url->setQueryStringVariable('return', $return_url->encode());

        // Get the add-on licenses info from the feed
        $feed = ee()->cache->get('addon-feed', Cache::GLOBAL_SCOPE);

        if (! $feed) {
            try {
                $feed = ee('Curl')->get(
                    'https://expressionengine.com/add-ons/addon-feed'
                )->exec();

                $feed = json_decode($feed, true);

                ee()->cache->save(
                    'addon-feed',
                    $feed,
                    60 * 60 * 24,
                    Cache::GLOBAL_SCOPE
                );
            } catch (\Exception $e) {
                if (empty($feed)) {
                    $feed = ['items' => []];
                }
            }
        }

        $addons = $feed['items'];
        $total = count($addons);
        $addonIconActionId = ee()->db->select('action_id')
            ->where('class', 'File')
            ->where('method', 'addonIcon')
            ->get('actions');

        $filters = ee('CP/Filter')
            ->add('Keyword')
            ->add('Perpage', $total, 'all_addons', true);
        $filter_values = $filters->values();

        $page = ee('Request')->get('page') ?: 1;
        $per_page = $filter_values['perpage'];

        $paged_addons = [];
        for ($i = $per_page * ($page - 1); $i < $total && $i < $per_page * $page; $i++) {
            $paged_addons[] = $addons[$i];
        }
        foreach ($paged_addons as $addon) {
            $slug = $addon['id'];
            $addon['package'] = $slug;
            $addon['name'] = $addon['title'];
            $addon['description'] = strip_quotes(strip_tags(ee('Security/XSS')->entity_decode($addon['summary'])));
            $addon['install_url'] = ee('CP/URL')->make('addons/download/' . $slug)->compile();
            $addon['update_url'] = ee('CP/URL')->make('addons/download/' . $slug, ['return' => $return_url->encode()]);
            $addon['remove_url'] = ee('CP/URL')->make('addons/remove/' . $slug, ['return' => $return_url->encode()]);
            $addon['confirm_url'] = ee('CP/URL')->make('addons/confirm/' . $slug);
            // does the icon file exist? if not, cache for a month
            $icon = ee()->cache->get('store/' . $slug . '/icon', Cache::GLOBAL_SCOPE);
            if (! $icon) {
                try {
                    $icon = ee('Curl')->get(
                        $addon['image']
                    )->exec();

                    ee()->cache->save(
                        'store/' . $slug . '/icon',
                        $icon,
                        30 * 60 * 60 * 24,
                        Cache::GLOBAL_SCOPE
                    );
                } catch (\Exception $e) {

                }
            }
            $addon['icon_url'] = ee()->functions->fetch_site_index() . QUERY_MARKER . 'ACT=' . $addonIconActionId->row('action_id') . AMP . 'addon=' . $slug;
            $addon['show_license_status'] = true;

            $toolbar_items = [];
            if ($addon['price'] == 'Free') {
                $toolbar_items['install'] = [
                    'href' => $addon['install_url'],
                    'title' => lang('install'),
                    'content' => ' ' . lang('install')
                ];
            } else {
                $toolbar_items['purchase'] = [
                    'href' => $addon['url'],
                    'title' => lang('buy_now'),
                    'content' => ' $' . $addon['price'],
                    'target' => '_blank'
                ];
            }

            $vars['addons'][] = [
                'id' => $addon['id'],
                'label' => $addon['title'],
                'href' => $addon['url'],
                'extra' => [
                    'encode' => false,
                    'content' => '<img src="' . $addon['icon_url'] . '" alt="' . $addon['title'] . '" />' . 
                        '<p>' . $addon['description'] . '</p>'
                ],
                'toolbar_items' => $toolbar_items
            ];
        }

        $vars['pagination'] = ee('CP/Pagination', $total)
            ->perPage($per_page)
            ->currentPage($page)
            ->render(ee('CP/URL')->make('addons/kiosk', $filter_values));

        $vars['no_results'] = ['text' => lang('unable_to_fetch_addons_feed')];

        $vars['header'] = array(
            'search_button_value' => lang('search_addons_button'),
            'title' => ee()->view->cp_page_title,
            'form_url' => $vars['base_url']
        );

        ee()->view->cp_breadcrumbs = array(
            '' => lang('addons')
        );

        ee()->cp->render('addons/kiosk', $vars);
    }
}

// EOF
