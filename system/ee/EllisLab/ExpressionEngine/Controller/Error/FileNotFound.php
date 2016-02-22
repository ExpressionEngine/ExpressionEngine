<?php

namespace EllisLab\ExpressionEngine\Controller\Error;

use CP_Controller;

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
 * ExpressionEngine CP 404 Controller Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class FileNotFound extends CP_Controller {

	public function __construct()
	{
		ee()->remove('__legacy_controller');
		parent::__construct();
	}

	public function index($url = '')
	{
		ee()->output->out_type = 404;
		ee()->view->cp_page_title = lang('404_does_not_exist');
		ee()->cp->render('errors/file_not_found', compact('url'));
	}
}
