<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Members\Profile;

use CP_Controller;

/**
 * Template Settings Controller
 */
class Pro extends Settings
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        if (! ee('pro:Access')->hasRequiredLicense() && ! $this->isRequiredMfaEnrollment()) {
            show_error(lang('unauthorized_access'), 403);
        }
    }

    /**
     * Allow members to complete required MFA enrollment when licensing is unavailable.
     *
     * @return bool Whether this is the member's own required enrollment form, or the QR code it shows.
     */
    private function isRequiredMfaEnrollment()
    {
        if (
            ee()->uri->segment(5) !== 'mfa' ||
            ! in_array((string) ee()->uri->segment(6), ['', 'qrCode'], true) ||
            $this->member->member_id != ee()->session->userdata('member_id') ||
            $this->member->enable_mfa === true
        ) {
            return false;
        }

        $settings = $this->member->PrimaryRole->RoleSettings->filter('site_id', ee()->config->item('site_id'))->first();

        return $settings !== null && $settings->require_mfa == 'y';
    }

    public function __call($name, $arguments)
    {
        $function = array_shift($arguments);
        if (empty($function)) {
            $function = $name;
        }
        $name = ucfirst($name);
        $class = "\ExpressionEngine\Addons\Pro\Controller\Members\Profile\\" . $name;
        $controller = new $class();

        return $controller->$function($arguments);
    }
}
// END CLASS

// EOF
