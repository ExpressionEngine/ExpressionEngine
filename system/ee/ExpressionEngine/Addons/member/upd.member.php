<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Service\Addon\Installer;

/**
 * Member Management update class
 */
class Member_upd extends Installer
{
    public $actions = [
        [
            'method' => 'registration_form'
        ],
        [
            'method' => 'register_member'
        ],
        [
            'method' => 'activate_member'
        ],
        [
            'method' => 'member_login'
        ],
        [
            'method' => 'member_logout'
        ],
        [
            'method' => 'send_reset_token'
        ],
        [
            'method' => 'process_reset_password'
        ],
        [
            'method' => 'send_member_email'
        ],
        [
            'method' => 'update_un_pw'
        ],
        [
            'method' => 'do_member_search'
        ],
        [
            'method' => 'member_delete'
        ],
        [
            'method' => 'send_username'
        ],
        [
            'method' => 'update_profile'
        ],
        [
            'method' => 'upload_avatar'
        ],
        [
            'method' => 'recaptcha_check',
            'csrf_exempt' => 1
        ],
        [
            'method' => 'validate'
        ]
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function update($current = '')
    {
        if (version_compare($current, '2.2.1', '<')) {
            ee()->db->where('method', 'member_search');
            ee()->db->update('actions', ['method' => 'do_member_search']);
        }

        return true;
    }
}
// END CLASS

// EOF
