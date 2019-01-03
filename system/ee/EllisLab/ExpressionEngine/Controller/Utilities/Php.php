<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Utilities;

/**
 * PHP Info Controller
 */
class Php extends Utilities {

	/**
	 * PHP Info
	 *
	 * @access	public
	 * @return	void
	 */
	public function index()
	{
		if ( ! ee()->cp->allowed_group('can_access_utilities'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		exit(phpinfo());
	}
}
// END CLASS

// EOF
