<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Service\Mfa;

use ExpressionEngine\Dependency\OTPHP\TOTP;
use ExpressionEngine\Dependency\ParagonIE\ConstantTime\Base32;
use ExpressionEngine\Dependency\BaconQrCode\Renderer\ImageRenderer;
use ExpressionEngine\Dependency\BaconQrCode\Renderer\Image\SvgImageBackEnd;
use ExpressionEngine\Dependency\BaconQrCode\Renderer\RendererStyle\RendererStyle;
use ExpressionEngine\Dependency\BaconQrCode\Writer;

/**
 * MultiFactorAuth Service
 */
class Mfa
{
    protected static $backupCode;
    protected static $formReturn;

    /**
     * Generate backup code used to restore access
     *
     * @param string $fallback If directly posted, will be used instead
     * @return string
     */
    public function backupCode($fallback = '')
    {
        if (empty(self::$backupCode)) {
            if (!empty($fallback) && strlen($fallback) == 16) {
                self::$backupCode = $fallback;
            } else {
                self::$backupCode = strtoupper(random_string('alnum', 16));
            }
        }
        return self::$backupCode;
    }

    /**
     * Generate QR code
     *
     * @param string $secret
     * @return string
     */
    public function generateQrCode($secret, $size = 400)
    {
        $totp = TOTP::create(Base32::encodeUpper($secret));
        $totp->setIssuer(ee()->config->item('site_name'));
        $totp->setLabel(ee()->session->userdata('username'));

        $str = $totp->getProvisioningUri();

        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        return $writer->writeString($str);
    }

    /**
     * Validate one-time password
     *
     * @param string $input provided OTP
     * @param string $secret string that was used to generate QR code
     * @return bool
     */
    public function validateOtp($input, $secret)
    {
        $totp = TOTP::create(Base32::encodeUpper($secret));
        return $totp->verify($input);
    }

    /**
     * Display appropriate MFA dialogs/messages
     *
     * @return void
     */
    public function invokeMfaDialog()
    {
        // User needs to be logged in
        if (ee()->session->userdata('member_id') == 0) {
            return;
        }

        // If MFA not setup, show setup screen
        if (ee()->session->getMember()->enable_mfa !== true) {
            return $this->formEnableMfa();
        }
        // If user has MFA enabled, show the dialog screen
        if (ee()->session->userdata('mfa_flag') == 'show') {
            return $this->formValidateMfa();
        }
    }

    public function formValidateMfa()
    {
        $sessions = ee('Model')
            ->get('Session')
            ->filter('member_id', ee()->session->userdata('member_id'))
            ->filter('fingerprint', ee()->session->userdata('fingerprint'))
            ->all();
        foreach ($sessions as $session) {
            $session->mfa_flag = 'required';
            $session->save();
        }

        ee()->lang->load('login');
        ee()->lang->load('pro');
        $vars = [
            'title' => lang('mfa_required'),
            'heading' => lang('mfa'),
            'content' => $this->form('validateMfa', 'pro:messages/mfa'),
            'url_themes' => URL_THEMES,
        ];
        return ee()->output->show_message($vars, false, false, 'mfa_template');
    }

    public function formEnableMfa()
    {
        $sessions = ee('Model')
            ->get('Session')
            ->filter('member_id', ee()->session->userdata('member_id'))
            ->filter('fingerprint', ee()->session->userdata('fingerprint'))
            ->all();
        foreach ($sessions as $session) {
            $session->mfa_flag = 'required';
            $session->save();
        }

        ee()->lang->load('login');
        ee()->lang->load('pro');
        $formVars = [
            'qr_link' => ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . ee()->functions->fetch_action_id('Pro', 'qrCode') . AMP . 'code=' . $this->backupCode(ee()->session->cache('pro', 'backup_mfa_code')),
            'backup_code' => $this->backupCode()
        ];

        $vars = [
            'title' => lang('mfa_required'),
            'heading' => lang('mfa'),
            'content' => $this->form('enableMfa', 'pro:messages/mfa-setup', 'confirm', false, $formVars),
            'url_themes' => URL_THEMES,
        ];
        return ee()->output->show_message($vars, false, false, 'mfa_template');
    }

    /**
     * Build the default variables we'll send to form
     *
     * @return array
     */
    private function _formVars($label = 'confirm', $check_password_lockout = true)
    {
        $formVars = [
            'btn_class' => 'button button--primary button--large button--wide',
            'btn_label' => lang($label),
            'btn_disabled' => '',
            'reset_mfa_link' => ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . ee()->functions->fetch_action_id('Pro', 'resetMfa') . AMP . 'RET=' . self::formReturn(),
            'errors' => (!empty(ee()->session->cache('mfa', 'errors'))) ? ee()->session->cache('mfa', 'errors') : []
        ];

        if ($check_password_lockout && ee()->session->check_password_lockout(ee()->session->userdata('username')) === true) {
            $formVars['btn_class'] .= ' disable';
            $formVars['btn_label'] = lang('locked');
            $formVars['btn_disabled'] = 'disabled';
            $formVars['errors'][] = sprintf(
                lang('password_lockout_in_effect'),
                ee()->config->item('password_lockout_interval')
            );
        }

        return $formVars;
    }

    /**
     * Generate form tag for front-end use
     *
     * @param string $action action name
     * @param string $tagdata
     * @return string
     */
    public function form($action, $viewFile, $buttonLabel = 'confirm', $check_password_lockout = true, $extraFormVars = [])
    {
        if (empty($action) || empty($viewFile)) {
            return '';
        }

        ee()->load->helper('form');

        $return = self::formReturn(ee('Request')->get('RET'));
        $vars = array_merge($this->_formVars($buttonLabel, $check_password_lockout), $extraFormVars);

        $tagdata = ee('View')->make($viewFile)->render($vars);

        $data = [
            'id' => (isset(ee()->TMPL) && ee()->TMPL->form_id) ? ee()->TMPL->form_id : $action,
            'class' => (isset(ee()->TMPL) && ee()->TMPL->form_class) ? ee()->TMPL->form_class : $action,
            'hidden_fields' => [
                'ACT' => ee()->functions->fetch_action_id('Pro', $action),
                'RET' => $return,
            ]
        ];

        $form = ee()->functions->form_declaration($data);
        $form .= $tagdata;
        $form .= "</form>";

        return ee()->functions->add_form_security_hash(ee()->functions->insert_action_ids($form));
    }

    private static function formReturn($return = '')
    {
        if (empty(self::$formReturn)) {
            // Set a sensible default for the return value
            $default = (REQ == 'ACTION') ? '/' : ee()->uri->uri_string();

            // If $return wasnt passed in, and this is a call involving the template parser,
            // then lets try to get the template tag param "return". Otherwise use the default that was just set
            $return = (empty($return) && isset(ee()->TMPL)) ? ee()->TMPL->fetch_param('return', ee()->uri->uri_string) : $default;

            // Now lets check for $_GET and $_POST variables. If "RET" is set, we'll use that,
            // and if not we'll fallback on what we currently have
            $return = ee('Request')->isPost() ? ee('Request')->post('RET', $return) : ee('Request')->get('RET', $return);

            // Now lets just make sure we've cleaned the result
            self::$formReturn = ee('Security/XSS')->clean($return);
        }

        return self::$formReturn;
    }
}
// EOF
