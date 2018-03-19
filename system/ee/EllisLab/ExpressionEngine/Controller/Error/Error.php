<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Controller\Error;

use CP_Controller;

/**
 * Error / 404 Controller
 */
class Error extends CP_Controller {

	public function index()
	{
		show_404();
	}
}

// EOF
