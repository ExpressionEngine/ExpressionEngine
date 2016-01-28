<?php

namespace EllisLab\ExpressionEngine\Model\Log;

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
 * ExpressionEngine Email Console Log Model
 *
 * @package		ExpressionEngine
 * @subpackage	Log
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class EmailConsoleCache extends Model {

	protected static $_primary_key = 'cache_id';
	protected static $_table_name = 'email_console_cache';

	protected static $_relationships = array(
		'Member' => array(
			'type' => 'belongsTo'
		),
	);

	protected static $_validation_rules = array(
		'ip_address' => 'ip_address'
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

// EOF
