<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Widgets;

use ExpressionEngine\Addons\Pro\Service\Dashboard;

class Members extends Dashboard\AbstractDashboardWidget implements Dashboard\DashboardWidgetInterface
{
    public function getTitle()
    {
        return lang('members');
    }

    public function getContent()
    {
        $vars = [];
        $vars['can_access_members'] = ee('Permission')->can('access_members');

        return ee('View')->make('pro:widgets/members')->render($vars);
    }

    public function getRightHead()
    {
        return ee('View')->make('pro:widgets/_embed/member_buttons')->render();
    }
}
