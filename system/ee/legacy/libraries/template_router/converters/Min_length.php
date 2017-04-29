<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

/**
 * Template Router Integer Converter
 */
class EE_Template_router_min_length_converter implements EE_Template_router_converter {

	public function __construct($length) {
		$this->length = $length;
	}

	public function validator()
	{
		return "(.{{$this->length},})";
	}

}
