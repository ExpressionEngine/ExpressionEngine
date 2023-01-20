<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Email;

use ExpressionEngine\Service\Model\Model;

/**
 * Email Tracker Model
 */
class EmailTracker extends Model
{
    protected static $_primary_key = 'email_id';
    protected static $_table_name = 'email_tracker';

    protected $email_id;
    protected $email_date;
    protected $sender_ip;
    protected $sender_email;
    protected $sender_username;
}

// EOF
