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
 File: mcp.blacklist.php
-----------------------------------------------------
 Purpose: Blacklist class - CP
=====================================================
*/

if ( ! defined('EXT'))
{
	exit('Invalid file request');
}

class Blacklist_upd {

	var $version	= '3.0';

	function Blacklist_upd()
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
		$this->EE->load->dbforge();

		$data = array(
			'module_name' 	 => 'Blacklist',
			'module_version' => $this->version,
			'has_cp_backend' => 'y'
		);

		$this->EE->db->insert('modules', $data);

		$fields = array(
						'blacklisted_id'	=> array(
													'type'				=> 'int',
													'constraint'		=> 10,
													'unsigned'			=> TRUE,
													'auto_increment'	=> TRUE
												),
						'blacklisted_type'  => array(
													'type' 				=> 'varchar',
													'constraint'		=> '20',
												),
						'blacklisted_value' => array(
													'type'				=> 'text'
												)
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('blacklisted_id', TRUE);
		$this->EE->dbforge->create_table('blacklisted');

		$fields = array(
						'whitelisted_id'	=> array(
													'type'				=> 'int',
													'constraint'		=> 10,
													'unsigned'			=> TRUE,
													'auto_increment'	=> TRUE
												),
						'whitelisted_type'  => array(
													'type' 				=> 'varchar',
													'constraint'		=> '20',
												),
						'whitelisted_value' => array(
													'type'				=> 'text'
												)
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('whitelisted_id', TRUE);
		$this->EE->dbforge->create_table('whitelisted');

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
		$this->EE->load->dbforge();

		$this->EE->db->select('module_id');
		$query = $this->EE->db->get_where('modules', array('module_name' => 'Blacklist'));
		$module_id_row = $query->row();
		$module_id = $module_id_row->module_id;

		$this->EE->db->where('module_id', $module_id);
		$this->EE->db->delete('module_member_groups');

		$this->EE->db->where('module_name', 'Blacklist');
		$this->EE->db->delete('modules');

		$this->EE->db->where('class', 'Blacklist');
		$this->EE->db->delete('actions');

		$this->EE->db->where('class', 'Blacklist_mcp');
		$this->EE->db->delete('actions');

		$this->EE->dbforge->drop_table('blacklisted');
		$this->EE->dbforge->drop_table('whitelisted');

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
		if ($current < 3.0)
		{
			$this->EE->load->dbforge();

			$sql = array();

			//if the are using a very old version this table won't exist at all
			if ( ! $this->EE->db->table_exists('whitelisted'))
			{
				$fields = array(
								'whitelisted_id'	=> array(
															'type'				=> 'int',
															'constraint'		=> 10,
															'unsigned'			=> TRUE,
															'auto_increment'	=> TRUE
														),
								'whitelisted_type'  => array(
															'type' 		 => 'varchar',
															'constraint' => '20',
														),
								'whitelisted_value' => array(
															'type' => 'text'
														)
				);

				$this->EE->dbforge->add_field($fields);
				$this->EE->dbforge->add_key('whitelisted_id', TRUE);
				$this->EE->dbforge->create_table('whitelisted');
			}
			else
			{
				$sql[] = "ALTER TABLE `exp_blacklisted` ADD COLUMN `blacklisted_id` int(10) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY FIRST";
				$sql[] = "ALTER TABLE `exp_whitelisted` ADD COLUMN `whitelisted` int(10) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY FIRST";
			}

			foreach($sql as $query)
			{
				$this->EE->db->query($query);
			}

			return TRUE;
		}

		return FALSE;
	}
}

// END CLASS

/* End of file upd.blacklist.php */
/* Location: ./system/expressionengine/modules/blacklist/upd.blacklist.php */