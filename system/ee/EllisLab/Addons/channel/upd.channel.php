<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Channel Module update
 */
class Channel_upd {

	var $version		= '2.0.1';

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */
	function install()
	{
		$data = array(
			'module_name' => 'Channel',
			'module_version' => $this->version,
			'has_cp_backend' => 'n'
		);

		ee()->db->insert('modules', $data);

		$data = array(
			'class' => 'Channel',
			'method' => 'submit_entry'
		);

		ee()->db->insert('actions', $data);

		$data = array(
			'class' => 'Channel',
			'method' => 'filemanager_endpoint'
		);

		ee()->db->insert('actions', $data);

		$data = array(
			'class' => 'Channel',
			'method' => 'smiley_pop'
		);

		ee()->db->insert('actions', $data);

		$data = array(
			'class' => 'Channel',
			'method' => 'combo_loader'
		);

		ee()->db->insert('actions', $data);

		ee()->db->insert('content_types', array('name' => 'channel'));

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
		ee()->db->from('modules');
		ee()->db->where('module_name', 'Channel');
		$query = ee()->db->get();

		ee()->db->delete('module_member_groups', array('module_id' => $query->row('module_id')));
		ee()->db->delete('modules', array('module_name' => 'Channel'));
		ee()->db->delete('actions', array('class' => 'Channel'));
		ee()->db->delete('actions', array('class' => 'Channel_mcp'));

		return TRUE;
	}

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */
	function update()
	{
		return TRUE;
	}

}
// END CLASS

// EOF
