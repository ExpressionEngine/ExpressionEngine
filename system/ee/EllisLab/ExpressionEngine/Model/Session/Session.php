<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
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
	protected $login_state;
	protected $fingerprint;
	protected $sess_start;
	protected $auth_timeout;
	protected $last_activity;
	protected $can_debug;

	/**
	 * Manage sudo-like timeout for "trust but verify" actions
	 */
	const AUTH_TIMEOUT = '+15 minutes';
	public function resetAuthTimeout()
	{
		$this->setProperty('auth_timeout', ee()->localize->string_to_timestamp(self::AUTH_TIMEOUT));
		$this->save();
	}
	public function isWithinAuthTimeout()
	{
		return $this->auth_timeout > ee()->localize->now;
	}
}

// EOF
