<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

/**
 * Template Router Base64 Converter
 */
class EE_Template_router_base64_converter implements EE_Template_router_converter {

	public function validator()
	{
		return "([a-zA-Z0-9\/\+=]+)";
	}

}

// EOF
