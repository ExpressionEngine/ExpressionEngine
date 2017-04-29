<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Model\Session;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Session Model
 */
class Session extends Model {

	protected static $_primary_key = 'session_id';
	protected static $_table_name = 'sessions';

	protected static $_typed_columns = array(
		'can_debug' => 'boolString'
	);

	protected static $_relationships = array(
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
	protected $can_debug;

}

// EOF
