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
 * Logging Settings Controller
 */
class Logging extends Settings
{
    /**
     * General Settings
     */
    public function index()
    {
        $vars['sections'] = array(
            array(
                array(
                    'title' => 'anonymize_consent_logs',
                    'desc' => 'anonymize_consent_logs_desc',
                    'fields' => array(
                        'anonymize_consent_logs' => array(
                            'type' => 'checkbox',
                            'choices' => array(
                                'ip_address' => 'ip_address',
                            )
                        )
                    )
                ),
            ),
            // settings for Show all settings button + toggle
            '' => array(
                array(
                    'title' => '',
                    'desc' => '',
                    'attrs' => array(
                        'class' => 'show_all_settings',
                    ),
                    'fields' => array(
                        'show_all' => array(
                            'type' => 'html',
                            'content' => '<button class="button button--primary show_all_settings_button js-show_all_settings_button">' . lang('show_all_settings') . '</button>'
                        )
                    )
                ),
                array(
                    'title' => '',
                    'desc' => 'This toggle controls the visibility of all settings.',
                    'attrs' => array(
                        'class' => 'js-hidden-toggle-for-show-all-settings hidden',
                    ),
                    'fields' => array(
                        'show_all' => array(
                            'type' => 'yes_no',
                            'group_toggle' => array(
                                'y' => 'show_all_settings_group',
                            ),
                            'value' => 'n'
                        )
                    )
                ),
            ),
            // End of settings for Show all settings button + toggle
        );


        $base_url = ee('CP/URL')->make('settings/logging');

        if (! empty($_POST)) {
            if (is_array($_POST['anonymize_consent_logs'])) {
                $_POST['anonymize_consent_logs'] = implode('|', $_POST['anonymize_consent_logs']);
            }
            if ($this->saveSettings($vars['sections'])) {
                ee()->view->set_message('success', lang('preferences_updated'), lang('preferences_updated_desc'), true);
            } else {
                ee()->view->set_message('issue', lang('settings_save_error'), lang('settings_save_error_desc'));
            }
        }

        ee()->view->base_url = $base_url;
        ee()->view->cp_page_title = lang('logging');
        ee()->view->save_btn_text = 'btn_save_settings';
        ee()->view->save_btn_text_working = 'btn_saving';
        ee()->view->cp_breadcrumbs = array(
            '' => lang('logging')
        );
        ee()->cp->render('settings/form', $vars);
    }
}
// END CLASS

// EOF
