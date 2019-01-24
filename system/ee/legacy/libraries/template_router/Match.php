<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Route Match
 */
class EE_Route_match {

	public $end_point = array();
	public $matches = array();

	public function __construct($end_point, $matches, $route)
	{
		$this->end_point = $end_point;

		foreach($route->subpatterns as $hash => $variable)
		{
			$this->matches[$variable] = $matches[$hash];
		}
	}

}
// END CLASS

// EOF
