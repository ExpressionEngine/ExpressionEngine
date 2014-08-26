<?php
namespace EllisLab\ExpressionEngine\Model\Security\Gateway;

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
 * ExpressionEngine Password Reset Table
 *
 * @package		ExpressionEngine
 * @subpackage	Security\Gateway
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class ResetPasswordGateway extends RowDataGateway {
	protected static $_table_name = 'reset_password';
	protected static $_primary_key = 'reset_id';
	protected static $_related_gateways = array(
		'member_id' => array(
			'gateway' => 'MemberGateway',
			'key' => 'member_id'
		)
	);

	// Properties
	protected $reset_id;
	protected $member_id;
	protected $resetcode;
	protected $date;
}
