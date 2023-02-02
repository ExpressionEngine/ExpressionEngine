<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Validation\Rule;

use ExpressionEngine\Service\Validation\ValidationRule;

/**
 * Authentication Rule
 */
class Authenticated extends ValidationRule
{
    public function validate($key, $password)
    {
        $auth_timeout = in_array('useAuthTimeout', $this->parameters);

        if ($auth_timeout && ee('Session')->isWithinAuthTimeout()) {
            ee('Session')->resetAuthTimeout();

            return true;
        }

        ee()->load->library('auth');
        $validate = ee()->auth->authenticate_id(
            ee()->session->userdata('member_id'),
            $password
        );

        if ($validate !== false && $auth_timeout) {
            ee('Session')->resetAuthTimeout();
        }

        return ($validate !== false) ? true : $this->stop();
    }

    public function getLanguageKey()
    {
        return 'auth_password';
    }
}

// EOF
