<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
*/

use ExpressionEngine\Service\JumpMenu\AbstractJumpMenu;

class Pro_jump extends AbstractJumpMenu
{
    protected static $items = [
        'cookies' => [
            'icon' => 'fa-wrench',
            'command' => 'cookie_settings',
            'command_title' => 'cookie_settings',
            'dynamic' => false,
            'addon' => true,
            'target' => 'cookies',
            'permission' => 'super_admin'
        ],
        'mfa' => [
            'icon' => 'fa-user',
            'command' => 'my_profile my_account mfa',
            'command_title' => 'jump_mfa',
            'dynamic' => false,
            'addon' => false,
            'target' => 'members/profile/pro/mfa',
        ]
    ];

    public function getItems()
    {
        if (!ee('pro:Access')->hasValidLicense() || !ee('Permission')->canUsePro()) {
            return [];
        }
        ee()->lang->load('pro', ee()->session->get_language(), false, true, PATH_ADDONS . 'pro/');

        return self::$items;
    }
}
