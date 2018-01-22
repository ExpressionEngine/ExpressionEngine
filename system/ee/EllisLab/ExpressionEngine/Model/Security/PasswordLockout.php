<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Model\Security;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Password Lockout Model
 */
class PasswordLockout extends Model {

	protected static $_primary_key = 'lockout_id';
	protected static $_table_name = 'password_lockout';

	protected static $_validation_rules = array(
		'ip_address' => 'ip_address'
	);

	protected $lockout_id;
	protected $login_date;
	protected $ip_address;
	protected $user_agent;
	protected $username;

}

// EOF
