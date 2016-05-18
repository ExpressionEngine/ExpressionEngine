<?php

namespace EllisLab\ExpressionEngine\Model\Security;

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
 * ExpressionEngine Throttle Model
 *
 * @package		ExpressionEngine
 * @subpackage	Security
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Throttle extends Model {

	protected static $_primary_key = 'throttle_id';
	protected static $_table_name = 'throttle';

	protected static $_validation_rules = array(
		'ip_address' => 'ip_address'
	);

	protected $throttle_id;
	protected $ip_address;
	protected $last_activity;
	protected $hits;
	protected $locked_out;

}

// EOF
