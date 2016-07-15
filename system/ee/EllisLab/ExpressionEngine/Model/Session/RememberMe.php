<?php

namespace EllisLab\ExpressionEngine\Model\Session;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Remember Me Model
 *
 * @package		ExpressionEngine
 * @subpackage	Session
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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
