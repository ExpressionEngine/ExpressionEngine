<?php

namespace {{namespace}}\Widgets;

use ExpressionEngine\Addons\Pro\Service\Dashboard;

class {{widget_name}} extends Dashboard\AbstractDashboardWidget implements Dashboard\DashboardWidgetInterface
{
    public function getTitle()
    {
        return lang('New Widget for {{addon_name}}: {{widget_name}}');
    }

    public function getContent()
    {
        $member = ee()->session->getMember();

        return "Hello {$member->username}!";
    }

    public function getRightHead()
    {
        return '<a href="' . ee('CP/URL', 'addons/settings/{{addon}}') . '" class="button button--default button--small">' . lang('{{addon_name}}') . '</a>';
    }
}
