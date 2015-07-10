<?php

namespace EllisLab\ExpressionEngine\Model\Security;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Captcha Model
 *
 * @package		ExpressionEngine
 * @subpackage	Security
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Captcha extends Model {

	protected static $_primary_key = 'captcha_id';
	protected static $_table_name = 'captcha';

	protected static $_validation_rules = array(
		'ip_address' => 'ip_address'
	);

	protected $captcha_id;
	protected $date;
	protected $ip_address;
	protected $word;

}
