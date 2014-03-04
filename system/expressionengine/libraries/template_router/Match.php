<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Route Match Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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

/* End of file Match.php */
/* Location: ./system/expressionengine/libraries/template_router/Match.php */