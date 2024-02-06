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
 * Member Profile Auth Settings Controller
 */
class Auth extends Settings
{
    private $base_url = 'members/profile/auth';

    /**
     * Auth Settings
     */
    public function index()
    {
        $this->base_url = ee('CP/URL')->make($this->base_url, $this->query_string);

        $vars['errors'] = null;

        if (! empty($_POST)) {
            // set and save the member as the various permissions allow
            if (ee()->config->item('allow_username_change') == 'y' or
                ee('Permission')->isSuperAdmin()) {
                $this->member->username = ee()->input->post('username');
            }

            // If the screen name field is empty, we'll assign is from the username field.
            if (ee()->input->post('screen_name') == '') {
                $this->member->screen_name = ee()->input->post('username');
            } else {
                $this->member->screen_name = ee()->input->post('screen_name');
            }

            // require authentication to change user/pass
            $validator = ee('Validation')->make();
            $validator->setRule('verify_password', 'authenticated[useAuthTimeout]');

            if (ee()->input->post('password')) {
                $this->member->password = ee()->input->post('password');
                $validator->setRule('confirm_password', 'matches[password]');
            }

            $result = $this->member->validate();
            $password_confirm = $validator->validate($_POST);

            // Add password confirmation failure to main result object
            if ($password_confirm->failed()) {
                $rules = $password_confirm->getFailed();
                foreach ($rules as $field => $rule) {
                    $result->addFailed($field, $rule[0]);
                }
            }

            if (AJAX_REQUEST && ee()->input->post('ee_fv_field') !== false) {
                return ee('Validation')->ajax($result);
            }

            if ($result->isValid()) {
                // if the password was set, need to hash it before saving and kill all other sessions
                if (ee()->input->post('password')) {
                    $this->member->hashAndUpdatePassword($this->member->password);
                }

                $this->member->save();

                if (ee('Request')->get('modal_form') == 'y') {
                    $result = [
                        'saveId' => $this->member->getId(),
                        'item' => [
                            'value' => $this->member->getId(),
                            'label' => $this->member->screen_name,
                            'instructions' => $this->member->username
                        ]
                    ];
                    return $result;
                }

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('member_updated'))
                    ->addToBody(lang('member_updated_desc'))
                    ->defer();
                ee()->functions->redirect($this->base_url);
            }

            $vars['errors'] = $result;
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('settings_save_error'))
                ->addToBody(lang('settings_save_error_desc'))
                ->now();
        }

        $vars['sections'] = array(
            array(
                array(
                    'title' => 'username',
                    'fields' => array(
                        'username' => array(
                            'type' => 'text',
                            'required' => true,
                            'value' => $this->member->username,
                            'maxlength' => USERNAME_MAX_LENGTH,
                            'attrs' => 'autocomplete="off"'
                        )
                    )
                ),
                array(
                    'title' => 'screen_name',
                    'fields' => array(
                        'screen_name' => array(
                            'type' => 'text',
                            'required' => true,
                            'value' => $this->member->screen_name,
                            'maxlength' => USERNAME_MAX_LENGTH,
                            'attrs' => 'autocomplete="off"'
                        )
                    )
                )
            ),
            'change_password' => array(
                ee('CP/Alert')->makeInline('permissions-warn')
                    ->asWarning()
                    ->addToBody(lang('password_change_exp'))
                    ->cannotClose()
                    ->render(),
                array(
                    'title' => 'new_password',
                    'desc' => 'new_password_desc',
                    'fields' => array(
                        'password' => array(
                            'type' => 'password',
                            'maxlength' => PASSWORD_MAX_LENGTH
                        )
                    )
                ),
                array(
                    'title' => 'new_password_confirm',
                    'desc' => 'new_password_confirm_desc',
                    'fields' => array(
                        'confirm_password' => array(
                            'type' => 'password',
                            'maxlength' => PASSWORD_MAX_LENGTH
                        )
                    )
                )
            )
        );

        if (! ee('Session')->isWithinAuthTimeout()) {
            $vars['sections']['secure_form_ctrls'] = array(
                array(
                    'title' => 'existing_password',
                    'desc' => 'existing_password_exp',
                    'fields' => array(
                        'verify_password' => array(
                            'type' => 'password',
                            'required' => true,
                            'maxlength' => PASSWORD_MAX_LENGTH
                        )
                    )
                )
            );
        }

        $vars['base_url'] = $this->base_url;
        $vars['ajax_validate'] = true;
        $vars['cp_page_title'] = lang('auth_settings');
        $vars['save_btn_text'] = 'btn_authenticate_and_save';
        $vars['save_btn_text_working'] = 'btn_saving';

        $vars['cp_breadcrumbs'] = array_merge($this->breadcrumbs, [
            '' => lang('auth_settings')
        ]);

        if (ee('Request')->get('modal_form') == 'y') {
            $sidebar = ee('CP/Sidebar')->render();
            if (! empty($sidebar)) {
                $vars['left_nav'] = $sidebar;
                $vars['left_nav_collapsed'] = ee('CP/Sidebar')->collapsedState;
            }
            return ee('View')->make('settings/modal-form')->render($vars);
        }

        ee()->cp->render('settings/form', $vars);
    }
}
// END CLASS

// EOF
