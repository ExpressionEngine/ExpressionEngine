<?php

namespace EllisLab\ExpressionEngine\Service\Profiler;

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
	public function make($sections = array())
	{
		return new Profiler($sections);
	}

}
