<?php

/*
=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 http://expressionengine.com/
-----------------------------------------------------
 Copyright (c) 2003 - 2010, EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 http://expressionengine.com/user_guide/license.html
=====================================================
 File: mcp.emoticon.php
-----------------------------------------------------
 Purpose: Emoticon class - CP
=====================================================
*/
if ( ! defined('EXT'))
{
	exit('Invalid file request');
}


class Emoticon_upd {

	var $version = '2.0';

	function Emoticon_upd()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */
	function install()
	{
		$data = array(
					'module_name' => 'Emoticon',
					'module_version' => $this->version,
					'has_cp_backend' => 'n'
					);

		$this->EE->db->insert('modules', $data);
		
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */
	function uninstall()
	{
		$this->EE->db->select('module_id');
		$query = $this->EE->db->get_where('modules', array('module_name' => 'Emoticon'));

		$this->EE->db->where('module_id', $query->row('module_id'));
		$this->EE->db->delete('module_member_groups');

		$this->EE->db->where('module_name', 'Emoticon');
		$this->EE->db->delete('modules');

		$this->EE->db->where('class', 'Emoticon');
		$this->EE->db->delete('actions');

		$this->EE->db->where('class', 'Emoticon_mcp');
		$this->EE->db->delete('actions');

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */
	function update($current='')
	{
		return FALSE;
	}
}
// END CLASS

/* End of file upd.emoticon.php */
/* Location: ./system/expressionengine/modules/emoticon/upd.emoticon.php */