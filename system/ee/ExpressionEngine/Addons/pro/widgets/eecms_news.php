<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Widgets;

use ExpressionEngine\Addons\Pro\Service\Dashboard;

class Eecms_news extends Dashboard\AbstractDashboardWidget implements Dashboard\DashboardWidgetInterface
{
    public function getTitle()
    {
        return lang('eecms_news');
    }

    public function getContent()
    {
        $vars = [];
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
                        'title'   => strip_tags($item->get_title()),
                        'date'    => ee()->localize->format_date(
                            ee()->session->userdata('date_format', ee()->config->item('date_format')),
                            $item->get_date('U')
                        ),
                        'content' => ee('Security/XSS')->clean(
                            ee()->typography->parse_type(
                                $item->get_content(),
                                array(
                                    'text_format'   => 'xhtml',
                                    'html_format'   => 'all',
                                    'auto_links'    => 'y',
                                    'allow_img_url' => 'n'
                                )
                            )
                        ),
                        'link'    => ee()->cp->masked_url($item->get_permalink())
                    );
                }

                $vars['news'] = $news;
            } catch (\Exception $e) {
                // Nothing to see here, the view will take care of it
            }
        }

        return ee('View')->make('pro:widgets/eecms_news')->render($vars);
    }

    public function getRightHead()
    {
        $url_rss = 'https://expressionengine.com/blog/rss-feed/cpnews/';

        return '<a class="button button--default button--small" href="' . $url_rss . '" rel="external">RSS</a>';
    }
}
