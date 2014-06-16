<?php

namespace EllisLab\ExpressionEngine\Library\Parser;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.9.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Core Parser Factory Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class ParserFactory {

	public static function createConditionalRunner()
	{
		return new Conditional\Runner();
	}
}

