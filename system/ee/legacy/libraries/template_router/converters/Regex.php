<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

/**
 * Template Router Regex Converter
 */
class EE_Template_router_regex_converter implements EE_Template_router_converter {

	public function __construct($regex) {
		$this->regex = $regex;
	}

	public function validator()
	{
		return $this->regex;
	}

}
