<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

class Pro
{
    public function __construct()
    {
    }

    /**
     * Sets allowed cookie
     * example: ?ACT=XX&frontedit=off
     *
     * @return void
     */
    public function setCookie()
    {
        $allowedCookies = ee('Addon')->get('pro')->get('cookies.functionality');
        if (!empty($allowedCookies) && is_array($allowedCookies)) {
            foreach ($allowedCookies as $cookie) {
                if (ee()->input->get($cookie) != '') {
                    $value = ee('Security/XSS')->clean(ee()->input->get($cookie));
                    ee()->input->set_cookie($cookie, $value, 31104000);
                    ee()->output->send_ajax_response(['success' => true, $cookie => $value]);
                }
            }
        }

        ee()->output->send_ajax_response(['error']);
    }

    /**
     * Enables MFA
     *
     * @return void
     */
    public function enableMfa()
    {
        if (REQ != 'ACTION') {
            return;
        }
        if (ee()->config->item('enable_mfa') !== false && ee()->config->item('enable_mfa') !== 'y') {
            return;
        }
        if (ee()->session->userdata('member_id') != 0 && ee()->session->getMember()->enable_mfa !== true) {
            if (ee('Request')->post('mfa_code') != '') {
                $sessions = ee('Model')
                    ->get('Session')
                    ->filter('member_id', ee()->session->userdata('member_id'))
                    ->filter('fingerprint', ee()->session->userdata('fingerprint'))
                    ->all();
                $validated = ee('pro:Mfa')->validateOtp(ee('Security/XSS')->clean(ee('Request')->post('mfa_code')), ee()->session->userdata('unique_id') . md5(ee('Security/XSS')->clean(ee('Request')->post('backup_mfa_code'))));
                if (!$validated) {
                    foreach ($sessions as $session) {
                        $session->mfa_flag = 'show';
                        $session->save();
                    }

                    ee()->lang->load('pro');
                    ee()->session->set_cache('mfa', 'errors', [lang('mfa_wrong_code_desc')]);
                    ee()->session->set_cache('mfa', 'backup_mfa_code', ee('Security/XSS')->clean(ee('Request')->post('backup_mfa_code')));
                    //sync the session
                    ee()->session->mfa_flag = 'show';
                } else {
                    $member = ee()->session->getMember();
                    $member->enable_mfa = true;
                    $member->backup_mfa_code = md5(ee('Security/XSS')->clean(ee('Request')->post('backup_mfa_code')));
                    $member->save();

                    foreach ($sessions as $session) {
                        $session->mfa_flag = 'skip';
                        $session->save();
                    }

                    $this->redirectBack();
                }
            }
            return ee('pro:Mfa')->formEnableMfa();
        }
        $this->redirectBack();
    }

    /**
     * Show appropriate MFA dialog
     *
     * @return void
     */
    public function invokeMfa()
    {
        if (REQ != 'ACTION') {
            return;
        }
        if (ee()->config->item('enable_mfa') !== false && ee()->config->item('enable_mfa') !== 'y') {
            return;
        }
        return ee('pro:Mfa')->invokeMfadialog();
    }

    /**
     * Resets MFA
     *
     * @return void
     */
    public function resetMfa()
    {
        if (REQ != 'ACTION') {
            return;
        }
        if (ee()->config->item('enable_mfa') !== false && ee()->config->item('enable_mfa') !== 'y') {
            return;
        }
        if (ee()->session->userdata('member_id') != 0 && ee()->session->getMember()->enable_mfa === true) {
            if (ee('Request')->post('backup_mfa_code') != '') {
                $member = ee()->session->getMember();
                $sessions = ee('Model')
                    ->get('Session')
                    ->filter('member_id', ee()->session->userdata('member_id'))
                    ->filter('fingerprint', ee()->session->userdata('fingerprint'))
                    ->all();
                if (md5(ee('Security/XSS')->clean(ee('Request')->post('backup_mfa_code'))) == $member->backup_mfa_code) {
                    ee()->session->delete_password_lockout();
                    $member->set(['backup_mfa_code' => '', 'enable_mfa' => false]);
                    $member->save();
                    //sync the session
                    foreach ($sessions as $session) {
                        $session->mfa_flag = 'skip';
                        $session->save();
                    }
                    ee()->session->mfa_flag = 'skip';
                    $this->redirectBack();
                } else {
                    ee()->session->save_password_lockout(ee()->session->userdata('username'));
                    ee()->lang->load('pro');
                    ee()->session->set_cache('mfa', 'errors', [lang('mfa_wrong_backup_code_desc')]);
                    //sync the session
                    ee()->session->mfa_flag = 'show';
                }
            }

            ee()->lang->load('login');
            ee()->lang->load('pro');
            $vars = [
                'title' => lang('reset_mfa'),
                'heading' => lang('reset_mfa'),
                'content' => ee('pro:Mfa')->form('resetMfa', 'pro:messages/mfa-reset', 'reset'),
                'url_themes' => URL_THEMES,
            ];
            return ee()->output->show_message($vars, false, false, 'mfa_template');
        }
        $this->redirectBack();
    }

    /**
     * Disables MFA
     *
     * @return void
     */
    public function disableMfa()
    {
        if (REQ != 'ACTION') {
            return;
        }
        if (ee()->config->item('enable_mfa') !== false && ee()->config->item('enable_mfa') !== 'y') {
            return;
        }
        if (ee()->session->userdata('member_id') != 0 && ee()->session->getMember()->enable_mfa === true) {
            if (ee('Request')->post('password_confirm') != '') {
                $sessions = ee('Model')
                    ->get('Session')
                    ->filter('member_id', ee()->session->userdata('member_id'))
                    ->filter('fingerprint', ee()->session->userdata('fingerprint'))
                    ->all();

                ee()->lang->load('myaccount');
                $rules = [
                    'password_confirm' => 'required|authenticated'
                ];
                $validator = ee('Validation')->make($rules);

                $validation = $validator->validate($_POST);

                if ($validation->passed()) {
                    $member = ee()->session->getMember();
                    $member->enable_mfa = false;
                    $member->backup_mfa_code = '';
                    $member->save();
                    ee()->session->delete_password_lockout();

                    foreach ($sessions as $session) {
                        $session->mfa_flag = 'skip';
                        $session->save();
                    }

                    $this->redirectBack();
                } else {
                    foreach ($sessions as $session) {
                        $session->mfa_flag = 'show';
                        $session->save();
                    }
                    ee()->lang->load('pro');
                    ee()->session->set_cache('mfa', 'errors', [lang('existing_password_mfa_reset_desc')]);
                    //sync the session
                    ee()->session->mfa_flag = 'show';
                }
            }
            ee()->lang->load('myaccount');
            ee()->lang->load('pro');
            $vars = [
                'title' => lang('disable_mfa'),
                'heading' => lang('disable_mfa'),
                'content' => ee('pro:Mfa')->form('disableMfa', 'pro:messages/mfa-disable', 'disable'),
                'url_themes' => URL_THEMES,
            ];
            return ee()->output->show_message($vars, false, false, 'mfa_template');
        }
        $this->redirectBack();
    }

    /**
     * Validates one-time password
     *
     * @return void
     */
    public function validateMfa()
    {
        if (REQ != 'ACTION') {
            return;
        }
        if (ee()->config->item('enable_mfa') !== false && ee()->config->item('enable_mfa') !== 'y') {
            return;
        }
        if (ee()->session->userdata('member_id') != 0 && ee()->session->getMember()->enable_mfa === true && ee()->session->userdata('mfa_flag') != 'skip') {
            if (ee('Request')->post('mfa_code') != '') {
                $sessions = ee('Model')
                    ->get('Session')
                    ->filter('member_id', ee()->session->userdata('member_id'))
                    ->filter('fingerprint', ee()->session->userdata('fingerprint'))
                    ->all();
                $validated = ee('pro:Mfa')->validateOtp(ee('Security/XSS')->clean(ee('Request')->post('mfa_code')), ee()->session->userdata('unique_id') . ee()->session->getMember()->backup_mfa_code);
                if (!$validated) {
                    foreach ($sessions as $session) {
                        $session->mfa_flag = 'show';
                        $session->save();
                    }
                    ee()->lang->load('pro');
                    ee()->session->save_password_lockout(ee()->session->userdata('username'));
                    ee()->session->set_cache('mfa', 'errors', [lang('mfa_wrong_code_desc')]);
                    //sync the session
                    ee()->session->mfa_flag = 'show';
                } else {
                    ee()->session->delete_password_lockout();

                    foreach ($sessions as $session) {
                        $session->mfa_flag = 'skip';
                        $session->save();
                    }
                    $this->redirectBack();
                }
            }
            return ee('pro:Mfa')->formValidateMfa();
        }
        $this->redirectBack();
    }

    /**
     * Generate the QR code for setting up MFA
     *
     * @return resource
     */
    public function qrCode()
    {
        if (ee()->session->userdata('member_id') != 0 && ee('Request')->get('code') != '') {
            header('Content-Type: image/svg+xml');
            echo ee('pro:Mfa')->generateQrCode(ee()->session->userdata('unique_id') . md5(ee('Security/XSS')->clean(ee('Request')->get('code'))), 300);
        }
        exit();
    }

    /**
     * Redirect to page according to posted parameter
     *
     * @param string $postedReturnVariable
     * @return void
     */
    private function redirectBack($postedReturnVariable = 'RET')
    {
        ee()->functions->redirect(ee()->functions->create_url(ee('Security/XSS')->clean(ee('Request')->post('RET'))));
    }
}
