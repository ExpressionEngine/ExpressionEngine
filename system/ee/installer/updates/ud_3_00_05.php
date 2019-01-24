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
 * Update
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
