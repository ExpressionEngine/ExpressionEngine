<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Model\Dashboard;

use ExpressionEngine\Model\Dashboard as Core;

/**
 * Dashboard Layout Model
 */
class DashboardLayout extends Core\DashboardLayout
{
    protected static $_relationships = array(
        'DashboardWidgets' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'pro:DashboardWidget',
            'pivot' => array(
                'table' => 'dashboard_layout_widgets',
                'left' => 'layout_id',
                'right' => 'widget_id'
            )
        ),
        'Members' => array(
            'model' => 'ee:Member',
            'type' => 'belongsTo',
            'from_key' => 'member_id'
        ),
        'Roles' => array(
            'model' => 'ee:Role',
            'type' => 'belongsTo',
            'from_key' => 'role_id'
        ),
    );

    /**
     * Generate dashboard html
     */
    public function generateDashboardHtml($edit_mode = false)
    {
        //if the files are here, but Pro version not installed - fallaback
        if (!ee('pro:Access')->hasRequiredLicense()) {
            $legacy_layout = new Core\DashboardLayout();

            return $legacy_layout->generateDashboardHtml();
        }

        $widgets = $this->DashboardWidgets;
        $order = explode('|', $this->order);

        //is this a real saved layout? if not, display all available widgets
        if (!$edit_mode && empty($this->layout_id)) {
            $widgets = ee('Model')->get('pro:DashboardWidget')->all();
        }

        $output = '';
        $widgets_indexed = [];

        //build id-indexed array of widgets
        foreach ($widgets as $widget) {
            $widgets_indexed[$widget->widget_id] = $widget;
        }

        foreach ($order as $id) {
            if (isset($widgets_indexed[$id])) {
                $output .= $widgets_indexed[$id]->generateHtml($edit_mode);
            }
            unset($widgets_indexed[$id]);
        }

        //anything still not displayed?
        foreach ($widgets_indexed as $widget) {
            $output .= $widget->generateHtml($edit_mode);
        }

        return $output;
    }
}
