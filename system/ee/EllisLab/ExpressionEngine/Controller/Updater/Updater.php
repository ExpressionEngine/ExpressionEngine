<?php

namespace EllisLab\ExpressionEngine\Controller\Updater;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 4.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Updater Controller Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Updater extends CP_Controller {

	/**
	 * Request end-point for updater tasks
	 */
	public function index($step = '')
	{
		// Temporary for testing; final controller implementation will probably
		// allow you to step through download steps individually via AJAX
		ee('Updater')->getUpdateFiles();

		// TODO: Can we catch a PHP timeout and report that to the user?
	}
}
// EOF
