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
 File: mcp.metaweblog_api.php
-----------------------------------------------------
 Purpose: Metaweblog API class - CP
=====================================================
*/
if ( ! defined('EXT'))
{
	exit('Invalid file request');
}



class Metaweblog_api_upd {

	var $version = '2.0';

	function Metaweblog_api_upd()
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
			'module_name' 	=> 'Metaweblog_api',
			'module_version' 	=> $this->version,
			'has_cp_backend' 	=> 'y'
		);
		$this->EE->db->insert('modules', $data);

		$data = array(
			'class' 	=> 'Metaweblog_api',
			'method' 	=> 'incoming'
		);
		$this->EE->db->insert('actions', $data);

		$fields = array(
						'metaweblog_id'	=> array(
													'type'				=> 'int',
													'constraint'		=> 5,
													'unsigned'			=> TRUE,
													'null'				=> FALSE,
													'auto_increment'	=> TRUE
												),
						'metaweblog_pref_name'  => array(
													'type' 				=> 'varchar',
													'constraint'		=> '80',
													'null'				=> FALSE,
													'default'			=> ''
												),
						'metaweblog_parse_type'  => array(
													'type' 				=> 'varchar',
													'constraint'		=> '1',
													'null'				=> FALSE,
													'default'			=> 'y'
												),
						'entry_status'  => array(
													'type' 				=> 'varchar',
													'constraint'		=> '50',
													'null'				=> FALSE,
													'default'			=> 'NULL'
												),
						'field_group_id'  => array(
													'type' 				=> 'int',
													'constraint'		=> '5',
													'unsigned'			=> TRUE,
													'null'				=> FALSE,
													'default'			=> 0
												),
						'excerpt_field_id'	=> array(
													'type'				=> 'int',
													'constraint'		=> 7,
													'unsigned'			=> TRUE,
													'null'				=> FALSE,
													'default'			=> 0
												),
						'content_field_id'	=> array(
													'type'				=> 'int',
													'constraint'		=> 7,
													'unsigned'			=> TRUE,
													'null'				=> FALSE,
													'default'			=> 0
												),
						'more_field_id'	=> array(
													'type'				=> 'int',
													'constraint'		=> 7,
													'unsigned'			=> TRUE,
													'null'				=> FALSE,
													'default'			=> 0
												),
						'keywords_field_id'	=> array(
													'type'				=> 'int',
													'constraint'		=> 7,
													'unsigned'			=> TRUE,
													'null'				=> FALSE,
													'default'			=> 0
												),
						'upload_dir'	=> array(
													'type'				=> 'int',
													'constraint'		=> 5,
													'unsigned'			=> TRUE,
													'null'				=> FALSE,
													'default'			=> 1
												),
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('metaweblog_id', TRUE);
		$this->EE->dbforge->create_table('metaweblog_api', TRUE);

		$data = array(
			'metaweblog_pref_name' 	=> 'Default',
			'field_group_id' 	=> 1,
			'content_field_id' 	=> 2
		);
		$this->EE->db->insert('metaweblog_api', $data);

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
		$query = $this->EE->db->get_where('modules', array('module_name' => 'Metaweblog_api'));

		$this->EE->db->where('module_id', $query->row('module_id'));
		$this->EE->db->delete('module_member_groups');

		$this->EE->db->where('module_name', 'Metaweblog_api');
		$this->EE->db->delete('modules');

		$this->EE->db->where('class', 'Metaweblog_api');
		$this->EE->db->delete('actions');

		$this->EE->dbforge->drop_table('metaweblog_api');

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */

	function update($version = '')
	{
		if ($this->EE->db->table_exists('exp_metaweblog_api'))
		{
			$existing_fields = array();

			$new_fields = array('entry_status' => "`entry_status` varchar(50) NOT NULL default 'null' AFTER `metaweblog_parse_type`");

			$query = $this->EE->db->query("SHOW COLUMNS FROM exp_metaweblog_api");

			foreach($query->result_array() as $row)
			{
				$existing_fields[] = $row['Field'];
			}

			foreach($new_fields as $field => $alter)
			{
				if ( ! in_array($field, $existing_fields))
				{
					$this->EE->db->query("ALTER table exp_metaweblog_api ADD COLUMN {$alter}");
				}
			}
		}

		return TRUE;
	}
}


/* End of file upd.metaweblog_api.php */
/* Location: ./system/expressionengine/modules/metaweblog_api/upd.metaweblog_api.php */