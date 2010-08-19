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
 File: mcp.stats.php
-----------------------------------------------------
 Purpose: Statistical tracking module - backend
=====================================================
*/
if ( ! defined('EXT'))
{
	exit('Invalid file request');
}


class Stats_upd {

	var $version	= '2.0';
	
	function Stats_upd()
	{
		$this->EE =& get_instance();
		$this->EE->load->dbforge();
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
					'module_name' => 'Stats',
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
		$this->EE->db->from('modules');
		$this->EE->db->where('module_name', 'Stats');
		$query = $this->EE->db->get();

		$this->EE->db->delete('module_member_groups', array('module_id' => $query->row('module_id')));
		$this->EE->db->delete('modules', array('module_name' => 'Stats'));
		$this->EE->db->delete('actions', array('class' => 'Stats'));
		$this->EE->db->delete('actions', array('class' => 'Stats_mcp'));

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
		if ($current == $this->version)
		{
			return FALSE;
		}
		
		if ($current < 2.0)
		{
			$this->EE->dbforge->drop_column('stats', 'weblog_id');
		}

		return TRUE;
	}

}
// END CLASS

/* End of file upd.stats.php */
/* Location: ./system/expressionengine/modules/stats/upd.stats.php */