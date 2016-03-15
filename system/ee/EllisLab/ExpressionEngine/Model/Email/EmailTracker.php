<?php

namespace EllisLab\ExpressionEngine\Model\Email;

use EllisLab\ExpressionEngine\Service\Model\Model;

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
