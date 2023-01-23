<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Member;

use ExpressionEngine\Service\Model\Model;

/**
 * Online Member
 */
class Online extends Model
{
    protected static $_primary_key = 'online_id';
    protected static $_table_name = 'online_users';

    protected static $_relationships = [
        'Member' => [
            'type' => 'belongsTo'
        ],
        'Site' => [
            'type' => 'belongsTo'
        ]
    ];

    protected static $_typed_columns = [
        'online_id' => 'int',
        'site_id' => 'int',
        'member_id' => 'int',
        'in_forum' => 'boolString',
        'name' => 'string',
        'ip_address' => 'string',
        'date' => 'timestamp',
        'anon' => 'boolString'
    ];

    protected $online_id;
    protected $site_id;
    protected $member_id;
    protected $in_forum;
    protected $name;
    protected $ip_address;
    protected $date;
    protected $anon;
}
// END CLASS

// EOF
