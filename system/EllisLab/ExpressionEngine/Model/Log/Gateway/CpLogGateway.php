<?php
namespace EllisLab\ExpressionEngine\Model\Log\Gateway;

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
 * ExpressionEngine CP Log Table
 *
 * @package		ExpressionEngine
 * @subpackage	Log\Gateway
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class CpLogGateway extends Gateway {

	protected static $_table_name 		= 'cp_log';
	protected static $_primary_key 		= 'id';

	protected $id;
	protected $site_id;
	protected $member_id;
	protected $username;
	protected $ip_address;
	protected $act_date;
	protected $action;
}
