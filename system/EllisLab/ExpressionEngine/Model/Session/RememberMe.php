<?php
namespace EllisLab\ExpressionEngine\Model\Session;

use EllisLab\ExpressionEngine\Service\Model\Model;

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
 * ExpressionEngine Remember Me Model
 *
 * @package		ExpressionEngine
 * @subpackage	Session
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class RememberMe extends Model {

	public static $_primary_key = 'remember_me_id';
	public static $_gateway_names = array('RememberMeGateway');

	protected static $relationships = array(
		'Member' => array(
			'type' => 'BelongsTo'
		),
		'Site' => array(
			'type' => 'BelongsTo'
		)
	);

	public $remember_me_id;
	public $member_id;
	public $ip_address;
	public $user_agent;
	public $admin_sess;
	public $site_id;
	public $expiration;
	public $last_refresh;
}
