<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

/**
 * Template Router pagination Converter
 */
class EE_Template_router_pagination_converter implements EE_Template_router_converter {

	public function validator()
	{
		return "((P|R|N)[0-9]+)";
	}

}
