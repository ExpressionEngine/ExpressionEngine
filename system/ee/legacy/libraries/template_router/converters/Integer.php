<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

/**
 * Template Router Integer Converter
 */
class EE_Template_router_integer_converter implements EE_Template_router_converter {

	public function validator()
	{
		return "([\-+]?[0-9]+)";
	}

}

// EOF
