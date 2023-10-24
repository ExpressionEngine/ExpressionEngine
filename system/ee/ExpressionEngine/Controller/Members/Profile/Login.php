<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Members\Profile;

use CP_Controller;

/**
 * Member Profile Login Controller
 */
class Login extends Profile
{
    private $base_url = 'members/profile/login';

    public function __construct()
    {
        parent::__construct();

        if (! ee('Permission')->isSuperAdmin()) {
            show_error(lang('unauthorized_access'), 403);
        }
    }

    /**
     * Login as index
     */
    public function index()
    {
        ee()->lang->load('pro');
        $this->base_url = ee('CP/URL')->make($this->base_url, $this->query_string);
        ee()->view->cp_page_title = sprintf(lang('login_as'), $this->member->screen_name);
        ee()->view->cp_breadcrumbs = array_merge($this->breadcrumbs, [
            '' => sprintf(lang('login_as'), '...')
        ]);

        if ($this->member->enable_mfa === true) {
            if (ee()->session->getMember()->enable_mfa !== true) {
                ee('CP/Alert')->makeInline('mfa-required')
                    ->asIssue()
                    ->cannotClose()
                    ->withTitle(lang('mfa_required'))
                    ->addToBody(lang('mfa_required_login_as'))
                    ->now();
                ee()->view->extra_alerts = ['mfa-required'];
                return ee()->cp->render('settings/page', []);
            }
        }

        $vars['sections'] = array(
            array(
                array(
                    'title' => 'redirect_to',
                    'desc' => sprintf(lang('redirect_to_desc'), $this->member->screen_name),
                    'fields' => array(
                        'redirect' => array(
                            'type' => 'radio',
                            'choices' => array(
                                'site_index' => lang('site_index'),
                                'other' => 'other'
                            ),
                            'group_toggle' => [
                                'other' => 'other'
                            ],
                            'value' => 'site_index'
                        ),
                        'other' => array(
                            'group' => 'other',
                            'type' => 'text',
                            'placeholder' => lang('url')
                        )
                    )
                )
            )
        );

        $rules = [
            [
                'field' => 'redirect',
                'label' => 'lang:redirect_to',
                'rules' => 'enum[site_index,cp_index,other]'
            ]
        ];

        if (! ee('Session')->isWithinAuthTimeout()) {
            $vars['sections']['secure_form_ctrls'] = array(
                array(
                    'title' => 'existing_password',
                    'desc' => 'existing_password_exp',
                    'fields' => array(
                        'password_confirm' => array(
                            'type' => 'password',
                            'required' => true,
                            'maxlength' => PASSWORD_MAX_LENGTH
                        )
                    )
                )
            );

            $rules[] = [
                'field' => 'password_confirm',
                'label' => 'lang:password',
                'rules' => 'required|auth_password[useAuthTimeout]'
            ];
        }

        if ($this->member->can('access_cp')) {
            $choices = & $vars['sections'][0][0]['fields']['redirect']['choices'];
            $choices = array_slice($choices, 0, 1, true)
                + array('cp_index' => 'cp_index')
                + array_slice($choices, 1, 1, true);
        }

        ee('CP/Alert')->makeInline('shared-form')
            ->asWarning()
            ->cannotClose()
            ->addToBody(sprintf(lang('login_as_warning'), $this->member->screen_name), 'warning')
            ->now();

        ee()->form_validation->set_rules($rules);

        if (AJAX_REQUEST) {
            ee()->form_validation->run_ajax();
            exit;
        } elseif (ee()->form_validation->run() !== false) {
            $this->login();
        } elseif (ee()->form_validation->errors_exist()) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('settings_save_error'))
                ->addToBody(lang('settings_save_error_desc'))
                ->now();
        }

        ee()->view->base_url = $this->base_url;
        ee()->view->ajax_validate = true;
        ee()->view->save_btn_text = 'btn_authenticate_and_login';
        ee()->view->save_btn_text_working = 'btn_login_working';
        ee()->cp->render('settings/form', $vars);
    }

    private function login()
    {
        ee()->logger->log_action(sprintf(
            lang('member_login_as'),
            $this->member->username,
            $this->member->member_id
        ));

        //  Do we allow multiple logins on the same account?
        if (ee()->config->item('allow_multi_logins') == 'n') {
            // Kill old sessions first
            ee()->session->gc_probability = 100;
            ee()->session->delete_old_sessions();
            $expire = time() - ee()->session->session_length;

            // See if there is a current session
            // no gateway to the Session model, need to consider
            // semver implications, so using QB for now -dj 2016-10-12
            $sess_query = ee()->db->select('ip_address', 'user_agent')
                ->where('member_id', $this->member->member_id)
                ->where('last_activity >', $expire)
                ->get('sessions');

            if ($sess_query->num_rows() > 0) {
                if (ee()->session->userdata['ip_address'] != $sess_query->row('ip_address') or
                    ee()->session->userdata['user_agent'] != $sess_query->row('user_agent')) {
                    show_error(lang('multi_login_warning'));
                }
            }
        }

        $redirect = ee()->input->post('redirect');

        // Set cookie expiration to one year if the "remember me" button is clicked

        $expire = 0;
        $type = $redirect == 'cp' ? ee()->config->item('cp_session_type') : ee()->config->item('website_session_type');

        if ($type != 's') {
            ee()->input->set_cookie(ee()->session->c_anon, 1, $expire);
        }

        $setMfaFlag = false;
        if ($this->member->enable_mfa === true && ee()->session->getMember()->enable_mfa === true && ee()->session->mfa_flag == 'skip') {
            $setMfaFlag = true;
        }

        // Create a new session
        $session_id = ee()->session->create_new_session($this->member->member_id, true, true);
        if ($setMfaFlag) {
            $session = ee('Model')->get('Session', $session_id)->first();
            $session->mfa_flag = 'skip';
            $session->save();
            ee()->session->mfa_flag = 'skip';
        }

        // Delete old password lockouts
        ee()->session->delete_password_lockout();

        // Redirect the user to the return page

        $return_path = ee()->functions->fetch_site_index();
        $url = ee()->input->post('other');

        if (! empty($redirect)) {
            if ($redirect == 'cp_index') {
                $return_path = $this->member->getCPHomepageURL();
            } elseif ($redirect == 'other' && ! empty($url)) {
                $return_path = ee('Security/XSS')->clean(strip_tags($url));
            }
        }

        ee()->functions->redirect($return_path);
    }
}
// END CLASS

// EOF
