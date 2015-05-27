<?php

namespace EllisLab\ExpressionEngine\Module\Email\Model\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

class EmailTrackerGateway extends Gateway
{
	protected static $_primary_key = 'email_id';
	protected static $_table_name = 'email_tracker';

	public $email_id;
	public $email_date;
	public $sender_ip;
	public $sender_email;
	public $sender_username;

}
