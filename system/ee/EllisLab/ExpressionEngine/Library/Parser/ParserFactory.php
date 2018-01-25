<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Library\Parser;

/**
 * Core Parser Factory
 */
class ParserFactory {

	public static function createConditionalRunner()
	{
		return new Conditional\Runner();
	}
}

// EOF
