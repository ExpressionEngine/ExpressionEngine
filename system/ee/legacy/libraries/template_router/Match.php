<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Route Match Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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
