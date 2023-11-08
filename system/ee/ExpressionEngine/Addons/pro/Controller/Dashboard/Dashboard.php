<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Controller\Dashboard;

use ExpressionEngine\Controller\Pro\Pro as Pro;

/**
 * Dashboard Controller
 */
class Dashboard extends Pro
{
    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Editing dashboard layout
     */
    public function layout($segments)
    {
        $member = ee()->session->getMember();
        $member_id = $member->member_id;

        $output = '';

        //member id can be specified as segment, but that's superadmin feature only
        if (!empty($segments)) {
            if (ee('Permission')->isSuperAdmin()) {
                $member_id = array_shift($segments);
                //TODO - need to check whether is valid member_id
            }
        }

        $dashboard_layout = ee('Model')->get('DashboardLayout')
            ->filter('member_id', $member_id)
            ->first();
        if (empty($dashboard_layout)) {
            $dashboard_layout = ee('Model')->make('DashboardLayout', ['member_id' => $member_id]);
        }

        //if this is POST request, save the data
        if (! empty($_POST)) {
            //remove everything
            $dashboard_layout->DashboardWidgets = null;
            $dashboard_layout->save();
            $order = [];

            foreach (ee()->input->post('widgets_enabled') as $widget_id => $enabled) {
                if ($enabled == 'y') {
                    $widget = ee('Model')->get('pro:DashboardWidget', $widget_id)->first();
                    $dashboard_layout->DashboardWidgets->add($widget);
                    $order[] = $widget_id;
                }
            }
            $dashboard_layout->order = implode('|', $order);

            if ($dashboard_layout->DashboardWidgets->save() && $dashboard_layout->save()) {
                ee('CP/Alert')->makeBanner('dashboard')
                    ->asSuccess()
                    ->withTitle(lang('success'))
                    ->addToBody(lang('dashboard_layout_saved'))
                    ->defer();
                ee()->functions->redirect(ee('CP/URL')->make('homepage'));
            } else {
                $error = lang('dashboard_layout_save_error');
                if ($dashboard_layout->DashboardWidgets->count() == 0) {
                    $error = lang('dashboard_needs_some_widgets');
                }
                ee('CP/Alert')->makeBanner('dashboard')
                    ->asIssue()
                    ->canClose()
                    ->withTitle(lang('error'))
                    ->addToBody($error)
                    ->now();
            }
        }

        $output = $dashboard_layout->generateDashboardHtml(true);

        //synchronize template widgets
        $template_groups = ee('Model')->get('TemplateGroup')
            ->filter('group_name', 'pro-dashboard-widgets')->all();
        $template_widgets = [];
        if (! empty($template_groups)) {
            foreach ($template_groups as $template_group) {
                foreach ($template_group->Templates as $template) {
                    if (!empty($template) && !empty($template->template_data)) {
                        $key = $template->template_name . '__site_id_' . $template_group->site_id;
                        $template_widgets[$key] = [
                            'widget_name' => $key,
                            'widget_data' => serialize(['group_id' => $template->group_id, 'template_id' => $template->template_id]),
                            'widget_type' => 'html',
                            'widget_source' => 'template'
                        ];
                    }
                }
            }
        }
        //is anything already installed?
        //if something is not in the list, remove it
        $widgets_q = ee()->db->select()
            ->from('dashboard_widgets')
            ->where('widget_source', 'template')
            ->get();
        if ($widgets_q->num_rows() > 0) {
            foreach ($widgets_q->result_array() as $row) {
                if (!isset($template_widgets[$row['widget_name']])) {
                    ee()->db->where('widget_id', $row['widget_id']);
                    ee()->db->delete('dashboard_widgets');

                    ee()->db->where('widget_id', $row['widget_id']);
                    ee()->db->delete('dashboard_layout_widgets');
                } else {
                    unset($template_widgets[$row['widget_name']]);
                }
            }
        }
        //is still something in the list? install those
        if (!empty($template_widgets)) {
            ee()->db->insert_batch('dashboard_widgets', $template_widgets);
        }

        //add hidden widgets
        $displayed_widgets = $dashboard_layout->DashboardWidgets->pluck('widget_id');
        $all_widgets = ee('Model')->get('pro:DashboardWidget')->all();
        foreach ($all_widgets as $widget) {
            if (!in_array($widget->widget_id, $displayed_widgets)) {
                $output .= $widget->generateHtml(true, false);
            }
        }

        $vars = [
            'header' => [
                'title' => ee()->config->item('site_name'),
                'action_buttons' => array(
                    'save' => array(
                        'href' => ee('CP/URL')->make('pro/dashboard/layout/' . $member->member_id),
                        'rel' => 'save_layout',
                        'text' => lang('save_dashboard_layout'),
                        'type' => 'primary'
                    )
                )
            ],
            'dashboard' => $output,
            'edit_mode' => true
        ];

        ee()->cp->add_js_script(array(
            'ui' => array(
                'sortable'
            ),
            'file' => array(
                'cp/sort_helper'
            ),
            'pro_file' => array(
                'dashboard'
            )
        ));

        ee()->view->cp_page_title = ee()->config->item('site_name') . ' ' . lang('edit_dashboard_layout');

        return ee()->cp->render('pro:dashboard/dashboard', $vars);
    }
}

// EOF
