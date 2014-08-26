<?php
namespace EllisLab\ExpressionEngine\Model\Log\Gateway;

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
 * ExpressionEngine Email Log Table
 *
 * Emails sent from the member profile email console are saved here.
 *
 * @package		ExpressionEngine
 * @subpackage	Log\Gateway
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EmailConsoleCacheGateway extends RowDataGateway {

	protected static $_table_name = 'email_console_cache';
	protected static $_primary_key = 'cache_id';
	protected static $_related_entites = array(
		'member_id' => array(
			'gateway' => 'MemberGateway',
			'key' => 'member_id'
		)
	);

	// Properties
	protected $cache_id;
	protected $cache_date;
	protected $member_id;
	protected $member_name;
	protected $ip_address;
	protected $recipient;
	protected $recipient_name;
	protected $subject;
	protected $message;
}
