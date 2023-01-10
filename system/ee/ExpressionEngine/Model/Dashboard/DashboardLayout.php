<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Dashboard;

use ExpressionEngine\Service\Model\Model;

/**
 * Dashboard Layout Model
 */
class DashboardLayout extends Model
{
    protected static $_primary_key = 'layout_id';
    protected static $_table_name = 'dashboard_layouts';

    protected static $_validation_rules = array();

    protected $layout_id;
    protected $member_id;
    protected $role_id;
    protected $order = '';

    /**
     * Generate dashboard html
     */
    public function generateDashboardHtml()
    {
        $vars = [];

        // First login, this is 0 on the first page load
        $vars['last_visit'] = (empty(ee()->session->userdata['last_visit'])) ? ee()->localize->human_time() : ee()->localize->human_time(ee()->session->userdata['last_visit']);

        if (ee()->config->item('enable_comments') == 'y') {
            $vars['number_of_new_comments'] = ee('Model')->get('Comment')
                ->filter('site_id', ee()->config->item('site_id'))
                ->filter('comment_date', '>', ee()->session->userdata['last_visit'])
                ->count();

            $vars['number_of_pending_comments'] = ee('Model')->get('Comment')
                ->filter('site_id', ee()->config->item('site_id'))
                ->filter('status', 'p')
                ->count();

            $vars['number_of_spam_comments'] = ee('Model')->get('Comment')
                ->filter('site_id', ee()->config->item('site_id'))
                ->filter('status', 's')
                ->count();
        }

        $vars['number_of_channels'] = ee('Model')->get('Channel')
            ->filter('site_id', ee()->config->item('site_id'))
            ->count();

        if ($vars['number_of_channels'] == 1) {
            $vars['channel_id'] = ee('Model')->get('Channel')
                ->filter('site_id', ee()->config->item('site_id'))
                ->first()
                ->channel_id;
        }

        $vars['spam_module_installed'] = (bool) ee('Model')->get('Module')->filter('module_name', 'Spam')->count();

        if ($vars['spam_module_installed']) {
            $vars['number_of_new_spam'] = ee('Model')->get('spam:SpamTrap')
                ->filter('site_id', ee()->config->item('site_id'))
                ->filter('trap_date', '>', ee()->session->userdata['last_visit'])
                ->count();

            $vars['number_of_spam'] = ee('Model')->get('spam:SpamTrap')
                ->filter('site_id', ee()->config->item('site_id'))
                ->count();

            // db query to aggregate
            $vars['trapped_spam'] = ee()->db->select('content_type, COUNT(trap_id) as total_trapped')
                ->group_by('content_type')
                ->get('spam_trap')
                ->result();

            foreach ($vars['trapped_spam'] as $trapped) {
                ee()->lang->load($trapped->content_type);
            }

            $vars['can_moderate_spam'] = ee('Permission')->can('moderate_spam');
        }

        $vars['can_view_homepage_news'] = bool_config_item('show_ee_news')
            && ee('Permission')->can('view_homepage_news');

        if ($vars['can_view_homepage_news']) {
            // Gather the news
            ee()->load->library(array('rss_parser', 'typography'));
            $url_rss = 'https://expressionengine.com/blog/rss-feed/cpnews/';
            $vars['url_rss'] = ee()->cp->masked_url($url_rss);
            $news = array();

            try {
                $feed = ee()->rss_parser->create(
                    $url_rss,
                    60 * 6, // 6 hour cache
                    'cpnews_feed'
                );

                foreach ($feed->get_items(0, 10) as $item) {
                    $news[] = array(
                        'title' => strip_tags($item->get_title()),
                        'date' => ee()->localize->format_date(
                            ee()->session->userdata('date_format', ee()->config->item('date_format')),
                            $item->get_date('U')
                        ),
                        'content' => ee('Security/XSS')->clean(
                            ee()->typography->parse_type(
                                $item->get_content(),
                                array(
                                    'text_format' => 'xhtml',
                                    'html_format' => 'all',
                                    'auto_links' => 'y',
                                    'allow_img_url' => 'n'
                                )
                            )
                        ),
                        'link' => ee()->cp->masked_url($item->get_permalink())
                    );
                }

                $vars['news'] = $news;
            } catch (\Exception $e) {
                // Nothing to see here, the view will take care of it
            }
        }

        $vars['can_moderate_comments'] = ee('Permission')->can('moderate_comments');
        $vars['can_edit_comments'] = ee('Permission')->can('edit_all_comments');
        $vars['can_access_members'] = ee('Permission')->can('access_members');
        $vars['can_create_members'] = ee('Permission')->can('create_members');
        $vars['can_access_channels'] = ee('Permission')->can('admin_channels');
        $vars['can_create_channels'] = ee('Permission')->can('create_channels');
        $vars['can_access_fields'] = ee('Permission')->hasAll('can_create_channel_fields', 'can_edit_channel_fields', 'can_delete_channel_fields');
        $vars['can_access_member_settings'] = ee('Permission')->hasAll('can_access_sys_prefs', 'can_access_members');
        $vars['can_create_entries'] = ee('Permission')->can('create_entries');

        return ee('View')->make('_shared/dashboard/dashboard')->render($vars);
    }
}
