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
 * ExpressionEngine Captcha Table
 *
 * @package		ExpressionEngine
 * @subpackage	Security\Gateway
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class CaptchaGateway extends RowDataGateway {
	protected static $_table_name = 'captcha';
	protected static $_primary_key = 'captcha_id';


	// Properties
	protected $captcha_id;
	protected $date;
	protected $ip_address;
	protected $word;

}
