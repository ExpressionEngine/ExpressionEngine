<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.9.1
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Updater {

	var $version_suffix = '';

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		ee()->load->dbforge();

		$steps = new ProgressIterator(
			array(
				'_recompile_template_routes'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	// -------------------------------------------------------------------------

	/**
	 * Load all routes and resave to get rid of md5 hashes
	 * 
	 * @access private
	 * @return void
	 */
	private function _recompile_template_routes()
	{
		ee()->db->select('template_id, route_required, route');
		ee()->db->from('templates');
		ee()->db->join('template_routes', 'templates.template_id = template_routes.template_id');
		ee()->db->where('route_parsed is not null');
		$query = ee()->db->get();

		foreach ($query->result() as $template)
		{
			$ee_route = new EE_Route($template->route, $template->route_required == 'y');
			$compiled = $ee_route->compile();
			$data = array('route_parsed' => $compiled);
			$this->template_model->update_template_route($template->template_id, $data);
		}
	}
}
/* END CLASS */

/* End of file ud_291.php */
/* Location: ./system/expressionengine/installer/updates/ud_291.php */
