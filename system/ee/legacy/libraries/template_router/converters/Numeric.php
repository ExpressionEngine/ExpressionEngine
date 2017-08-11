<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

/**
 * Template Router Numeric Converter
 */
class EE_Template_router_numeric_converter implements EE_Template_router_converter {

	public function validator()
	{
		return "([\-+]?[0-9]*\.?[0-9]+)";
	}

}
