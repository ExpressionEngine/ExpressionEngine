<?php

namespace EllisLab\ExpressionEngine\Service\Profiler;

use \EE_Lang;
use EllisLab\ExpressionEngine\Service\View\View;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Profiler Factory
 *
 * @package		ExpressionEngine
 * @subpackage	Profiler
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Factory {

	/**
	 *
	 */
	public function make(EE_Lang $lang, View $view)
	{
		return new Profiler($lang, $view);
	}

}
