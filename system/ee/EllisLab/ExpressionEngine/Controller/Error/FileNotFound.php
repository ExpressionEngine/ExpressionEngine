<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Error;

use CP_Controller;

/**
 * Error\FileNotFound Controller
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

// EOF
