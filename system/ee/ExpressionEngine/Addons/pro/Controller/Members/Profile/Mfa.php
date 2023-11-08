<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Pro\Controller\Members\Profile;

use ExpressionEngine\Controller\Members\Profile;

/**
 * Member Profile MFA Controller
 */
class Mfa extends Profile\Pro
{
    private $base_url = 'members/profile/pro/mfa';

    public function __construct()
    {
        ee()->lang->load('pro');

        if (ee()->config->item('enable_mfa') !== false && ee()->config->item('enable_mfa') !== 'y') {
            show_error(lang('unauthorized_access'), 403);
        }

        $id = ee()->input->get('id');
        if (empty($id)) {
            $id = ee()->session->userdata['member_id'];
        }

        if ($id != ee()->session->userdata['member_id']) {
            show_error(lang('unauthorized_access'), 403);
        }

        $qs = array('id' => $id);
        $this->query_string = $qs;
        $this->member = ee('Model')->get('Member', $id)->with('PrimaryRole', 'Roles', 'RoleGroups')->first();

        if (is_null($this->member)) {
            show_404();
        }

        $this->breadcrumbs = array(
            ee('CP/URL')->make('members')->compile() => lang('members'),
            ee('CP/URL')->make('members/profile', $qs)->compile() => $this->member->screen_name
        );

        ee()->view->header = array(
            'title' => lang('mfa'),
        );
        $this->base_url = ee('CP/URL')->make($this->base_url, $this->query_string);
    }

    /**
     * MFA Settings
     */
    public function mfa()
    {
        ee()->lang->load('login');
        $rules = [];
        if ($this->member->enable_mfa === false && ee('Request')->post('enable_mfa') == 'y') {
            $rules[] = [
                'field' => 'mfa_code',
                'label' => 'lang:mfa_code',
                'rules' => 'required|integer|exact_length[6]'
            ];
            $rules[] = [
                'field' => 'backup_mfa_code',
                'label' => 'lang:backup_mfa_code',
                'rules' => 'required|alpha_numeric|exact_length[16]'
            ];
        }
        if ($this->member->enable_mfa === true && ee('Request')->post('enable_mfa') == 'n' && ! ee('Session')->isWithinAuthTimeout()) {
            $rules[] = [
                'field' => 'password_confirm',
                'label' => 'lang:password',
                'rules' => 'required|auth_password[useAuthTimeout]'
            ];
        }
        ee()->form_validation->set_rules($rules);

        if (ee('Request')->isPost() && (ee()->form_validation->run() !== false || empty($rules))) {
            $sessions = ee('Model')
                ->get('Session')
                ->filter('member_id', ee()->session->userdata('member_id'))
                ->filter('fingerprint', ee()->session->userdata('fingerprint'))
                ->all();
            if (!empty($_POST['mfa_code'])) {
                $validated = ee('pro:Mfa')->validateOtp(ee('Security/XSS')->clean(ee('Request')->post('mfa_code')), ee()->session->userdata('unique_id') . md5(ee('Security/XSS')->clean(ee('Request')->post('backup_mfa_code'))));
                if (!$validated) {
                    ee('CP/Alert')->makeInline('shared-form')
                        ->asIssue()
                        ->withTitle(lang('mfa_wrong_code'))
                        ->addToBody(lang('mfa_wrong_code_desc'))
                        ->now();
                } else {
                    $this->member->enable_mfa = true;
                    $this->member->backup_mfa_code = md5(ee('Security/XSS')->clean(ee('Request')->post('backup_mfa_code')));
                    $this->member->save();

                    foreach ($sessions as $session) {
                        $session->mfa_flag = 'skip';
                        $session->save();
                    }

                    ee('CP/Alert')->makeInline('shared-form')
                        ->asSuccess()
                        ->withTitle(lang('mfa_enabled'))
                        ->addToBody(lang('mfa_enabled_desc'))
                        ->now();
                }
            } elseif (ee('Request')->post('enable_mfa') == 'n') {
                $this->member->enable_mfa = false;
                $this->member->save();

                foreach ($sessions as $session) {
                    $session->mfa_flag = 'skip';
                    $session->save();
                }

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('mfa_disabled'))
                    ->addToBody(lang('mfa_disabled_desc'))
                    ->now();
            }
        } elseif (ee('Request')->isPost() && ee()->form_validation->errors_exist()) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('settings_save_error'))
                ->addToBody(lang('mfa_save_error_desc'))
                ->now();
        }

        $vars['sections'] = array(
            array(
                array(
                    'title' => 'enable_mfa',
                    'fields' => array(
                        'enable_mfa' => array(
                            'type' => 'yes_no',
                            'disabled' => version_compare(PHP_VERSION, 7.1, '<'),
                            'value' => $this->member->enable_mfa,
                            'group_toggle' => array(
                                'n' => 'password',
                                'y' => 'qr_code'
                            )
                        )
                    )
                ),
            )
        );

        if (version_compare(PHP_VERSION, 7.1, '<')) {
            ee()->lang->load('addons');
            $vars['sections'] = array_merge($vars['sections'], [
                [
                ee('CP/Alert')->makeInline('mfa_not_available')
                    ->asWarning()
                    ->withTitle(lang('mfa_not_available'))
                    ->addToBody(sprintf(lang('version_required'), 'PHP', 7.1))
                    ->cannotClose()
                    ->render()
                ],
                [
                    form_hidden('enable_mfa', 'n')
                ],
            ]);
        }

        if (version_compare(PHP_VERSION, 7.1, '>=') && $this->member->enable_mfa === false) {
            $vars['sections'][0] = array_merge($vars['sections'][0], array(
                array(
                    'title' => 'mfa_qr_code',
                    'desc' => 'mfa_qr_code_desc',
                    'group' => 'qr_code',
                    'fields' => array(
                        'mfa_qr_code' => array(
                            'type' => 'html',
                            'content' => '<img src="' . ee('CP/URL')->make('members/profile/pro/mfa/qrCode', ['code' => ee('pro:Mfa')->backupCode(ee('Request')->post('backup_mfa_code'))])->compile() . '" />'
                        )
                    )
                ),
                array(
                    'title' => 'mfa_backup_code',
                    'desc' => 'mfa_backup_code_desc',
                    'group' => 'qr_code',
                    'fields' => array(
                        'backup_mfa_code' => array(
                            'type' => 'hidden',
                            'value' => ee('pro:Mfa')->backupCode()
                        ),
                        'mfa_backup_code' => array(
                            'type' => 'html',
                            'content' => ee('CP/Alert')->makeInline('backup-code-warn')
                                ->asWarning()
                                ->addToBody(lang('mfa_backup_warning_desc'))
                                ->addToBody('<code>' . ee('pro:Mfa')->backupCode() . '</code>')
                                ->cannotClose()
                                ->render()
                        )
                    )
                ),
                array(
                    'title' => 'mfa_code',
                    'desc' => 'mfa_code_desc',
                    'group' => 'qr_code',
                    'fields' => array(
                        'mfa_code' => array(
                            'type' => 'text',
                            'required' => true,
                            'maxlength' => 6,
                            'attrs' => 'autocomplete="off"'
                        )
                    )
                ),
            ));
        }
        if ($this->member->enable_mfa == true && ! ee('Session')->isWithinAuthTimeout()) {
            $vars['sections']['secure_form_ctrls'] = array(
                array(
                    'title' => 'existing_password',
                    'desc' => 'existing_password_exp',
                    'group' => 'password',
                    'fields' => array(
                        'password_confirm' => array(
                            'type' => 'password',
                            'required' => true,
                            'maxlength' => PASSWORD_MAX_LENGTH
                        )
                    )
                )
            );
        }

        ee()->view->cp_page_title = lang('mfa');

        ee()->view->cp_breadcrumbs = array_merge($this->breadcrumbs, [
            '' => lang('mfa')
        ]);

        $vars['buttons'] = $buttons = [
            [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save',
                'text' => 'save',
                'working' => 'btn_saving',
                'shortcut' => 's'
            ]
        ];

        ee()->view->base_url = $this->base_url;

        ee()->cp->render('settings/form', $vars);
    }

    public function qrCode()
    {
        header('Content-Type: image/svg+xml');
        echo ee('pro:Mfa')->generateQrCode(ee()->session->userdata('unique_id') . md5(ee('Security/XSS')->clean(ee('Request')->get('code'))));
        exit();
    }
}
// END CLASS

// EOF
