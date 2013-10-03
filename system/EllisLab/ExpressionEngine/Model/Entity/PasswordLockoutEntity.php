<?php
namespace EllisLab\ExpressionEngine\Model\Entity;

namespace EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class PasswordLockoutEntity extends Entity {
	protected static $meta = array(
		'table_name' => 'password_lockout',
		'primary_id' => 'lockout_id'
	);
	
	// Properties
	public $lockout_id;
	public $login_date;
	public $ip_address;
	public $user_agent;
	public $username;
}
