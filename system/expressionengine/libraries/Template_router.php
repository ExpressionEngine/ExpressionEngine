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
 * ExpressionEngine Template Router Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Template_Router extends CI_Router {

    public $end_points = array();

    function __construct()
    {
        $this->set_routes();
    }

    public function match($uri) {
    }

    protected function set_routes()
    {
	    ee()->db->select('route_parsed, template_name, group_name');
		ee()->db->from('templates');
		ee()->db->join('template_groups', 'templates.group_id = template_groups.group_id');
	    ee()->db->where('route_parsed is not null');
	    $query = ee()->db->get();
		foreach ($query->result() as $template)
		{
			var_dump($template);
			$this->end_points[$template->route_parsed] = array(
				"template" => $template->template_name,
				"group"    => $template->group_name
			);
        }
    }

}
// END CLASS

/* End of file Template_router.php */
/* Location: ./system/expressionengine/libraries/Template_router.php */