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
 File: mcp.pages.php
-----------------------------------------------------
 Purpose: Pages class - CP
=====================================================
*/
if ( ! defined('EXT'))
{
	exit('Invalid file request');
}



class Pages_upd {

	var $version		= '2.0';

	function Pages_upd($switch=TRUE)
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}
	
	function tabs()
	{
		$tabs['pages'] = array(
			'pages_template_id'	=> array(
								'visible'		=> 'true',
								'collapse'		=> 'false',
								'htmlbuttons'	=> 'true',
								'width'			=> '100%'
								),
			
			'pages_uri'		=> array(
								'visible'		=> 'true',
								'collapse'		=> 'false',
								'htmlbuttons'	=> 'true',
								'width'			=> '100%'
								)
				);	
				
		return $tabs;	
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
		$sql[] = "INSERT INTO exp_modules (module_name, module_version, has_cp_backend) VALUES ('Pages', '$this->version', 'y')";

		if ( ! $this->EE->db->field_exists('site_pages', 'exp_sites'))
		{
			$sql[] = "ALTER TABLE `exp_sites` ADD `site_pages` TEXT NOT NULL";
		}

		$sql[] = "CREATE TABLE `exp_pages_configuration` (
				`configuration_id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`site_id` INT( 8 ) UNSIGNED NOT NULL DEFAULT '1',
				`configuration_name` VARCHAR( 60 ) NOT NULL ,
				`configuration_value` VARCHAR( 100 ) NOT NULL
				)";

		foreach ($sql as $query)
		{
			$this->EE->db->query($query);
		}
		
		$this->EE->load->library('layout');
		$this->EE->layout->add_layout_tabs($this->tabs());

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
		$query = $this->EE->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Pages'");

		$sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row('module_id') ."'";
		$sql[] = "DELETE FROM exp_modules WHERE module_name = 'Pages'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Pages'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Pages_mcp'";
		$sql[] = "ALTER TABLE `exp_sites` DROP `site_pages`";
		$sql[] = "DROP TABLE `exp_pages_configuration`";

		foreach ($sql as $query)
		{
			$this->EE->db->query($query);
		}
		
		$this->EE->load->library('layout');
		$this->EE->layout->delete_layout_tabs($this->tabs());

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

/* End of file upd.pages.php */
/* Location: ./system/expressionengine/modules/pages/upd.pages.php */