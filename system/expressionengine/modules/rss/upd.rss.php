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
 File: mcp.rss.php
-----------------------------------------------------
 Purpose: Rss class - CP
=====================================================
*/

if ( ! defined('EXT'))
{
	exit('Invalid file request');
}


class Rss_upd {

	var $version = '2.0';

	function Rss_upd()
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
		$sql[] = "INSERT INTO exp_modules (module_name, module_version, has_cp_backend) VALUES ('Rss', '$this->version', 'n')";
	
		foreach ($sql as $query)
		{
			$this->EE->db->query($query);
		}
		
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
		$query = $this->EE->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Rss'"); 
				
		$sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row('module_id') ."'";		
		$sql[] = "DELETE FROM exp_modules WHERE module_name = 'Rss'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Rss'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Rss_mcp'";

		foreach ($sql as $query)
		{
			$this->EE->db->query($query);
		}

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

/* End of file upd.rss.php */
/* Location: ./system/expressionengine/modules/rss/upd.rss.php */