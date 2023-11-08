<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Widgets;

use ExpressionEngine\Addons\Pro\Service\Dashboard;
use ExpressionEngine\Addons\Pro\Model\Dashboard\DashboardWidget as DashboardWidgetModel;

class Recent_templates extends Dashboard\AbstractDashboardWidget implements Dashboard\DashboardWidgetInterface
{
    private $allowedTemplateGroups;

    public function __construct(DashboardWidgetModel $widgetObject, $edit_mode, $enabled)
    {
        parent::__construct($widgetObject, $edit_mode, $enabled);
    }

    public function getTitle()
    {
        return lang('recent_templates');
    }

    public function getContent()
    {
        if (!ee('Permission')->isSuperAdmin()) {
            $allowedTemplateGroups = ee()->session->getMember()->getAssignedTemplateGroups()->pluck('group_id');
        } else {
            $allowedTemplateGroups = ee('Model')->get('TemplateGroup')->all()->pluck('group_id');
        }

        if (!empty($allowedTemplateGroups)) {
            $templates = ee('Model')
                ->get('Template')
                ->with('TemplateGroup')
                ->filter('group_id', 'IN', $allowedTemplateGroups)
                ->filter('site_id', ee()->config->item('site_id'))
                ->order('edit_date', 'desc')
                ->limit(7)
                ->all();
            if (!empty($templates)) {
                return ee('View')->make('pro:widgets/recent_templates')->render(['templates' => $templates]);
            }
        }

        return '';
    }

    public function getRightHead()
    {
        return '<a href="' . ee('CP/URL', 'design') . '" class="button button--default button--small">' . lang('view_all') . '</a>';
    }
}
