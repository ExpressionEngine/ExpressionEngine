<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0.5
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
 * @link		https://ellislab.com
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
				'install_required_modules',
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
	 * Ensure required modules are installed
	 * @return void
	 */
	public function install_required_modules()
	{
		ee()->load->library('addons');

		$installed_modules = ee()->db->select('module_name')->get('modules');
		$required_modules = array('channel', 'comment', 'member', 'stats', 'rte', 'file', 'filepicker', 'search');

		foreach ($installed_modules->result() as $installed_module)
		{
			$key = array_search(
				strtolower($installed_module->module_name),
				$required_modules
			);

			if ($key !== FALSE)
			{
				unset($required_modules[$key]);
			}
		}

		ee()->addons->install_modules($required_modules);
	}
}
/* END CLASS */

// EOF
