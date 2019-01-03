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
 * jQuery Module update class
 */
class Jquery_upd {

	var $version = '1.0.0';

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */

	function install()
	{
		$data = array(
			'module_name' 	 => 'Jquery',
			'module_version' => $this->version,
			'has_cp_backend' => 'n'
		);

		ee()->db->insert('modules', $data);

		return TRUE;
	}

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */

	function uninstall()
	{
		ee()->db->select('module_id');
		$query = ee()->db->get_where('modules', array('module_name' => 'Jquery'));
		$module_id = $query->row('module_id');

		ee()->db->where('module_id', $module_id);
		ee()->db->delete('module_member_groups');

		ee()->db->where('module_name', 'Jquery');
		ee()->db->delete('modules');

		ee()->db->where('class', 'Jquery');
		ee()->db->delete('actions');

		ee()->db->where('class', 'Jquery_mcp');
		ee()->db->delete('actions');

		return TRUE;
	}

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */

	function update($current='')
	{
		return TRUE;
	}
}
// End Jquery CP Class

// EOF
