<?php

namespace EllisLab\ExpressionEngine\Module\Email\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class EmailTracker extends Model
{
	protected static $_primary_key = 'email_id';
	protected static $_gateway_names = array('EmailTrackerGateway');

	protected $email_id;
	protected $email_date;
	protected $sender_ip;
	protected $sender_email;
	protected $sender_username;
}
