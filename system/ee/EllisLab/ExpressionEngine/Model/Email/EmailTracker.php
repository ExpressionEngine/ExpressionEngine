<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Email;

use EllisLab\ExpressionEngine\Service\Model\Model;

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
