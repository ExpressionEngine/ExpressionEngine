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
 * Remember Me Model
 */
class RememberMe extends Model {

	protected static $_primary_key = 'remember_me_id';
	protected static $_table_name = 'remember_me';

	protected static $_relationships = array(
		'Member' => array(
			'type' => 'BelongsTo'
		),
		'Site' => array(
			'type' => 'BelongsTo'
		)
	);

	protected $remember_me_id;
	protected $member_id;
	protected $ip_address;
	protected $user_agent;
	protected $admin_sess;
	protected $site_id;
	protected $expiration;
	protected $last_refresh;
}

// EOF
