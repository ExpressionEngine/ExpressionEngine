<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Router Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Router extends CI_Router {

    public $routes = array();
    public $route_template = '';
    public $route_group = '';

    public function __construct()
    {
    }

	public function parse_route($route)
	{
	}

    public function parse_segments($route)
    {
    }

    public function parse_rules($segment)
    {
    }

}
// END CLASS

/* End of file Router.php */
/* Location: ./system/expressionengine/libraries/template_router/Router.php */