<?php

namespace EllisLab\ExpressionEngine\Model\Session;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Session Model
 *
 * @package		ExpressionEngine
 * @subpackage	Session
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Session extends Model {

	protected static $_primary_key = 'session_id';
	protected static $_table_name = 'sessions';

	protected static $relationships = array(
		'Member' => array(
			'type' => 'BelongsTo'
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
