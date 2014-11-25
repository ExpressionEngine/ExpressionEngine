<?php
namespace EllisLab\ExpressionEngine\Model\Addon\Gateway;

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
 * ExpressionEngine Action Table
 *
 * @package		ExpressionEngine
 * @subpackage	Addon\Gateway
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class ActionGateway extends Gateway {

	protected static $_table_name = 'actions';
	protected static $_primary_key = 'action_id';

	protected $action_id;
	protected $class;
	protected $method;
	protected $csrf_exempt;

}
