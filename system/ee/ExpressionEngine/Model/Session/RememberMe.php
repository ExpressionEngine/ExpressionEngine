<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Session;

use ExpressionEngine\Service\Model\Model;

/**
 * Remember Me Model
 */
class RememberMe extends Model
{
    protected static $_primary_key = 'remember_me_id';
    protected static $_table_name = 'remember_me';

    protected static $_relationships = array(
        'Member' => array(
            'type' => 'BelongsTo'
        ),
        'Site' => array(
            'type' => 'BelongsTo'
        )
    );

    protected $remember_me_id;
    protected $member_id;
    protected $ip_address;
    protected $user_agent;
    protected $admin_sess;
    protected $site_id;
    protected $expiration;
    protected $last_refresh;
}

// EOF
