<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Log;

use ExpressionEngine\Service\Model\Model;

/**
 * Email Console Log Model
 */
class EmailConsoleCache extends Model
{
    protected static $_primary_key = 'cache_id';
    protected static $_table_name = 'email_console_cache';

    protected static $_relationships = array(
        'Member' => array(
            'type' => 'belongsTo'
        ),
    );

    protected static $_validation_rules = array(
        'ip_address' => 'ip_address'
    );

    // Properties
    protected $cache_id;
    protected $cache_date;
    protected $member_id;
    protected $member_name;
    protected $ip_address;
    protected $recipient;
    protected $recipient_name;
    protected $subject;
    protected $message;
}

// EOF
