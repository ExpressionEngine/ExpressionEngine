<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

namespace EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class PasswordLockoutGateway extends RowDataGateway {
	protected static $_table_name = 'password_lockout';
	protected static $_primary_id = 'lockout_id';

	// Properties
	protected $lockout_id;
	protected $login_date;
	protected $ip_address;
	protected $user_agent;
	protected $username;
}
