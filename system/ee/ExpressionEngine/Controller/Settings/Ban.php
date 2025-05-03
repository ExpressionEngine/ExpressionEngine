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
 * Ban Settings Controller
 */
class Ban extends Settings
{
    public function __construct()
    {
        parent::__construct();

        if (! ee('Permission')->hasAll('can_access_members', 'can_ban_users')) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->lang->load('members');
    }

    public function index()
    {
        $values = [
            'banned_ips' => '',
            'banned_emails' => '',
            'banned_usernames' => '',
            'banned_screen_names' => '',
        ];

        foreach (array_keys($values) as $item) {
            $value = ee()->config->item($item);

            if ($value != '') {
                foreach (explode('|', $value) as $line) {
                    $values[$item] .= $line . NL;
                }
            }
        }

        $ban_action = ee()->config->item('ban_action');

        $vars['sections'] = array(
            array(
                array(
                    'title' => 'ip_address_banning',
                    'desc' => 'ip_banning_instructions',
                    'fields' => array(
                        'banned_ips' => array(
                            'type' => 'textarea',
                            'value' => $values['banned_ips']
                        )
                    )
                ),
                array(
                    'title' => 'email_address_banning',
                    'desc' => 'email_banning_instructions',
                    'fields' => array(
                        'banned_emails' => array(
                            'type' => 'textarea',
                            'value' => $values['banned_emails']
                        )
                    )
                ),
                array(
                    'title' => 'username_banning',
                    'desc' => 'username_banning_instructions',
                    'fields' => array(
                        'banned_usernames' => array(
                            'type' => 'textarea',
                            'value' => $values['banned_usernames']
                        )
                    )
                ),
                array(
                    'title' => 'screen_name_banning',
                    'desc' => 'screen_name_banning_instructions',
                    'fields' => array(
                        'banned_screen_names' => array(
                            'type' => 'textarea',
                            'value' => $values['banned_screen_names']
                        )
                    )
                ),
                array(
                    'title' => 'ban_options',
                    'desc' => 'ban_options_desc',
                    'fields' => array(
                        'ban_action_pt1' => array(
                            'type' => 'radio',
                            'name' => 'ban_action',
                            'choices' => array(
                                'restrict' => lang('restrict_to_viewing'),
                                'message' => lang('show_this_message'),
                            ),
                            'value' => $ban_action
                        ),
                        'ban_message' => array(
                            'type' => 'textarea',
                            'value' => ee()->config->item('ban_message')
                        ),
                        'ban_action_pt2' => array(
                            'type' => 'radio',
                            'name' => 'ban_action',
                            'choices' => array(
                                'bounce' => lang('send_to_site'),
                            ),
                            'value' => $ban_action
                        ),
                        'ban_destination' => array(
                            'type' => 'text',
                            'value' => ee()->config->item('ban_destination')
                        ),
                    )
                )
            )
        );

        // @TODO: Stop using form_validation
        ee()->form_validation->set_rules(array(
            array(
                'field' => 'banned_usernames',
                'label' => 'lang:banned_usernames',
                'rules' => 'valid_xss_check'
            ),
            array(
                'field' => 'banned_screen_names',
                'label' => 'lang:banned_screen_names',
                'rules' => 'valid_xss_check'
            ),
            array(
                'field' => 'banned_emails',
                'label' => 'lang:banned_emails',
                'rules' => 'valid_xss_check'
            ),
            array(
                'field' => 'banned_ips',
                'label' => 'lang:banned_ips',
                'rules' => 'valid_xss_check'
            )
        ));

        $base_url = ee('CP/URL')->make('settings/ban');

        if (AJAX_REQUEST) {
            ee()->form_validation->run_ajax();
            exit;
        } elseif (ee()->form_validation->run() !== false) {
            $prefs = [
                'ban_action' => ee('Request')->post('ban_action'),
                'ban_message' => ee('Request')->post('ban_message'),
                'ban_destination' => ee('Request')->post('ban_destination'),
            ];

            foreach (array_keys($values) as $item) {
                $value = ee('Request')->post($item);
                $value = implode('|', explode(NL, $value));
                $prefs[$item] = $value;
            }

            ee()->config->update_site_prefs($prefs);

            ee('CP/Alert')->makeInline('shared-form')
                ->asSuccess()
                ->withTitle(lang('ban_settings_updated'))
                ->defer();

            ee()->functions->redirect($base_url);
        } elseif (ee()->form_validation->errors_exist()) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('settings_save_error'))
                ->addToBody(lang('settings_save_error_desc'))
                ->now();
        }

        ee()->view->ajax_validate = true;
        ee()->view->base_url = $base_url;
        ee()->view->cp_page_title = lang('manage_bans');
        ee()->view->save_btn_text = 'btn_save_settings';
        ee()->view->save_btn_text_working = 'btn_saving';

        ee()->view->cp_breadcrumbs = array(
            '' => lang('manage_bans')
        );

        ee()->cp->render('settings/form', $vars);
    }

}
// END CLASS

// EOF
