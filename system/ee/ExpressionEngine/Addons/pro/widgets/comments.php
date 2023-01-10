<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Widgets;

use ExpressionEngine\Addons\Pro\Service\Dashboard;

class Comments extends Dashboard\AbstractDashboardWidget implements Dashboard\DashboardWidgetInterface
{
    public $width = 'full';

    public function getTitle()
    {
        return lang('comments');
    }

    public function getContent()
    {
        ee()->load->helper('text');
        $vars = [];
        $vars['can_moderate_comments'] = ee('Permission')->can('moderate_comments');
        $vars['can_edit_comments'] = ee('Permission')->can('edit_all_comments');

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

        return ee('View')->make('pro:widgets/comments')->render($vars);
    }

    public function getRightHead()
    {
        if (ee('Permission')->can('edit_all_comments')) {
            $number_of_new_comments = ee('Model')->get('Comment')
                ->filter('site_id', ee()->config->item('site_id'))
                ->filter('comment_date', '>', ee()->session->userdata['last_visit'])
                ->count();
            return '<a class="button button--default button--small" href="' . ee('CP/URL', 'publish/comments') . '">' . $number_of_new_comments . lang('new_comments') . '</a>';
        }
        return '';
    }

    public function getHtml()
    {
        return ee('View')->make('pro:dashboard/widget-nowrap')->render($this->vars);
    }
}
