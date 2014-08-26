<?php
namespace EllisLab\ExpressionEngine\Model\Session\Gateway;

use EllisLab\ExpressionEngine\Service\Model\RowDataGateway;

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
 * ExpressionEngine Remember Me Table
 *
 * @package		ExpressionEngine
 * @subpackage	Session\Gateway
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class RememberMeGateway extends RowDataGateway {
	protected static $_table_name = 'remember_me';
	protected static $_primary_key = 'remember_me_id';

	protected static $_related_gateways = array(
		'member_id' => array(
			'gateway' => 'MemberGateway',
			'key' => 'member_id'
		),
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key' => 'site_id'
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
