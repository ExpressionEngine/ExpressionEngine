<?php
namespace EllisLab\ExpressionEngine\Model\Session\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Session Table
 *
 * @package		ExpressionEngine
 * @subpackage	Session\Gateway
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class SessionGateway extends Gateway {

	protected static $_table_name = 'sessions';
	protected static $_primary_key = 'session_id';
	protected static $_related_gateways = array(
		'member_id' => array(
			'gateway' => 'member_id',
			'key' => 'member_id',
		)
	);


	protected $session_id;
	protected $member_id;
	protected $admin_sess;
	protected $ip_address;
	protected $user_agent;
	protected $fingerprint;
	protected $sess_start;
	protected $last_activity;
}
