<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

 /**
 * namespace is required and must be unique (include file name),
 * because we'll be reusing same class name
 */

namespace ExpressionEngine\Addons\Pro\Widgets;

use ExpressionEngine\Addons\Pro\Service\Dashboard;

class Recent_entries extends Dashboard\AbstractDashboardWidget implements Dashboard\DashboardWidgetInterface
{
    public $title = 'Recent entries';
    public $content = "Static content here";

    public function getTitle()
    {
        return lang('recent_entries');
    }

    public function getContent()
    {
        $vars = [];
        $vars['assigned_channels'] = ee()->functions->fetch_assigned_channels();
        if (!ee('Permission')->isSuperAdmin() && empty($vars['assigned_channels'])) {
            return '';
        }

        return ee('View')->make('pro:widgets/recent_entries')->render($vars);
    }

    public function getRightHead()
    {
        if (ee('Permission')->hasAny('can_edit_other_entries', 'can_edit_self_entries')) {
            return '<a href="' . ee('CP/URL', 'publish/edit') . '" class="button button--default button--small">' . lang('view_all') . '</a>';
        }
        return '';
    }
}
