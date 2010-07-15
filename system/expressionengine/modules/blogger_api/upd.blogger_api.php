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
 File: mcp.blogger_api.php
-----------------------------------------------------
 Purpose: Blogger API class - CP
=====================================================
*/

if ( ! defined('EXT'))
{
	exit('Invalid file request');
}

class Blogger_api_upd {

	var $version = '2.0';

	function Blogger_api_upd()
	{
		// Make a local reference to the ExpressionEngine super object
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
			'module_name' 	 => 'Blogger_api',
			'module_version' => $this->version,
			'has_cp_backend' => 'y'
		);

		$this->EE->db->insert('modules', $data);

		$data = array(
			'class' 	=> 'Blogger_api',
			'method' 	=> 'incoming'
		);

		$this->EE->db->insert('actions', $data);

		$data = array(
			'class' 	=> 'Blogger_api',
			'method' 	=> 'edit_uri_output'
		);

		$this->EE->db->insert('actions', $data);

		$fields = array(
						'blogger_id'		  => array(
													'type' 			 => 'int',
													'constraint'	 => '5',
													'unsigned'		 => TRUE,
													'auto_increment' => TRUE
												),
						'blogger_pref_name'	  => array(
													'type'		 => 'varchar',
													'constraint' => '80',
													'default'	 => ''
												),
						'blogger_field_id'	  => array(
													'type'		 => 'varchar',
													'constraint' => '10',
													'default'	 => '1:2'
												),
						'blogger_block_entry' => array(
													'type'		 => 'char',
													'constraint' => '1',
													'default'	 => 'n'
												),
						'blogger_parse_type'  => array(
													'type'		 => 'char',
													'constraint' => '1',
													'default'	 => 'y'
												),
						'blogger_text_format' => array(
													'type'		 => 'char',
													'constraint' => '1',
													'default'	 => 'n'
												),
						'blogger_html_format' => array(
													'type'		 => 'varchar',
													'constraint' => '50',
													'default'	 => ''
												)
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('blogger_id', TRUE);
		$this->EE->dbforge->create_table('blogger');

		$data = array(
			'blogger_pref_name'   => 'Default',
			'blogger_html_format' => 'safe'
		);

		$this->EE->db->insert('blogger', $data);

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
		$query = $this->EE->db->get_where('modules', array('module_name' => 'Blogger_api'));

		$this->EE->db->where('module_id', $query->row('module_id'));
		$this->EE->db->delete('module_member_groups');

		$this->EE->db->where('module_name', 'Blogger_api');
		$this->EE->db->delete('modules');

		$this->EE->db->where('class', 'Blogger_api');
		$this->EE->db->delete('actions');

		$this->EE->dbforge->drop_table('blogger');

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

/* End of file upd.blogger_api.php */
/* Location: ./system/expressionengine/modules/blogger_api/upd.blogger_api.php */