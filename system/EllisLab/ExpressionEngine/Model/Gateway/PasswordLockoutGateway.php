<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

namespace EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class PasswordLockoutGateway extends RowDataGateway {
	protected static $_table_name = 'password_lockout';
	protected static $_primary_id = 'lockout_id';

	// Properties
	public $lockout_id;
	public $login_date;
	public $ip_address;
	public $user_agent;
	public $username;
}
