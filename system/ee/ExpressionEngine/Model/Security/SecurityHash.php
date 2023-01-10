<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Security;

use ExpressionEngine\Service\Model\Model;

/**
 * Security Hash Model
 */
class SecurityHash extends Model
{
    protected static $_primary_key = 'hash_id';
    protected static $_table_name = 'security_hashes';

    protected static $_relationships = array(
        'Session' => array(
            'type' => 'belongsTo'
        )
    );

    protected $hash_id;
    protected $date;
    protected $session_id;
    protected $hash;
    protected $used;
}

// EOF
