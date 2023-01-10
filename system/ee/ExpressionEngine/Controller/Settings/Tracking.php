<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Settings;

use CP_Controller;

/**
 * Tracking Settings Controller
 */
class Tracking extends Settings
{
    /**
     * General Settings
     */
    public function index()
    {
        $vars['sections'] = array(
            array(
                array(
                    'title' => 'enable_tracking_cookie',
                    'desc' => 'enable_tracking_cookie_desc',
                    'fields' => array(
                        'enable_tracking_cookie' => array(
                            'type' => 'yes_no',
                            'value' => ee()->config->item('enable_tracking_cookie') !== 'n'
                        ),
                    )
                ),
                array(
                    'title' => 'enable_online_user_tracking',
                    'desc' => 'enable_online_user_tracking_desc',
                    'fields' => array(
                        'enable_online_user_tracking' => array('type' => 'yes_no')
                    )
                ),
                array(
                    'title' => 'enable_hit_tracking',
                    'desc' => 'enable_hit_tracking_desc',
                    'fields' => array(
                        'enable_hit_tracking' => array('type' => 'yes_no')
                    )
                ),
                array(
                    'title' => 'enable_entry_view_tracking',
                    'desc' => 'enable_entry_view_tracking_desc',
                    'fields' => array(
                        'enable_entry_view_tracking' => array('type' => 'yes_no')
                    )
                ),
                array(
                    'title' => 'dynamic_tracking_disabling',
                    'desc' => sprintf(
                        lang('dynamic_tracking_disabling_desc'),
                        DOC_URL . 'control-panel/settings/hit-tracking.html#suspend-threshold'
                    ),
                    'fields' => array(
                        'dynamic_tracking_disabling' => array('type' => 'text')
                    )
                ),
            )
        );

        $base_url = ee('CP/URL')->make('settings/tracking');

        ee()->form_validation->set_rules(array(
            array(
                'field' => 'dynamic_tracking_disabling',
                'label' => 'lang:dynamic_tracking_disabling',
                'rules' => 'is_numeric'
            )
        ));

        ee()->form_validation->validateNonTextInputs($vars['sections']);

        if (AJAX_REQUEST) {
            ee()->form_validation->run_ajax();
            exit;
        } elseif (ee()->form_validation->run() !== false) {
            if ($this->saveSettings($vars['sections'])) {
                ee()->view->set_message('success', lang('preferences_updated'), lang('preferences_updated_desc'), true);
            }

            ee()->functions->redirect($base_url);
        } elseif (ee()->form_validation->errors_exist()) {
            ee()->view->set_message('issue', lang('settings_save_error'), lang('settings_save_error_desc'));
        }

        ee()->view->base_url = $base_url;
        ee()->view->ajax_validate = true;
        ee()->view->cp_page_title = lang('tracking');
        ee()->view->save_btn_text = 'btn_save_settings';
        ee()->view->save_btn_text_working = 'btn_saving';

        ee()->view->cp_breadcrumbs = array(
            '' => lang('tracking')
        );

        ee()->cp->render('settings/form', $vars);
    }
}
// END CLASS

// EOF
