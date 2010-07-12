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
 File: mcp.mailinglist.php
-----------------------------------------------------
 Purpose: Basic Mailint List class - CP
=====================================================
*/

if ( ! defined('EXT'))
{
	exit('Invalid file request');
}


class Mailinglist_upd {

	var $version = '3.0';

	function Mailinglist_upd()
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
		$fields = array(
						'list_id'	=> array(
													'type'				=> 'int',
													'constraint'		=> 7,
													'unsigned'			=> TRUE,
													'null'				=> FALSE,
													'auto_increment'	=> TRUE
												),
						'list_name'  => array(
													'type' 				=> 'varchar',
													'constraint'		=> '40',
													'null'				=> FALSE
												),
						'list_title'  => array(
													'type' 				=> 'varchar',
													'constraint'		=> '100',
													'null'				=> FALSE
												),
						'list_template' => array(
													'type'				=> 'text',
													'null'				=> FALSE
												)
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('list_id', TRUE);
		$this->EE->dbforge->add_key('list_name');
		$this->EE->dbforge->create_table('mailing_lists', TRUE);

		$fields = array(
						'user_id'	=> array(
													'type'				=> 'int',
													'constraint'		=> 10,
													'unsigned'			=> TRUE,
													'null'				=> FALSE,
													'auto_increment'	=> TRUE
												),
						'list_id'	=> array(
													'type'				=> 'int',
													'constraint'		=> 7,
													'unsigned'			=> TRUE,
													'null'				=> FALSE,
												),
						'authcode'  => array(
													'type' 				=> 'varchar',
													'constraint'		=> '10',
													'null'				=> FALSE
												),
						'email'  => array(
													'type' 				=> 'varchar',
													'constraint'		=> '50',
													'null'				=> FALSE
												),
						'ip_address'  => array(
													'type' 				=> 'varchar',
													'constraint'		=> '16',
													'null'				=> FALSE
												),
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('user_id', TRUE);
		$this->EE->dbforge->add_key('list_id');
		$this->EE->dbforge->create_table('mailing_list', TRUE);

		$fields = array(
						'queue_id'	=> array(
													'type'				=> 'int',
													'constraint'		=> 10,
													'unsigned'			=> TRUE,
													'null'				=> FALSE,
													'auto_increment'	=> TRUE
												),
						'email'  => array(
													'type' 				=> 'varchar',
													'constraint'		=> '50',
													'null'				=> FALSE
												),
						'list_id'	=> array(
													'type'				=> 'int',
													'constraint'		=> 7,
													'unsigned'			=> TRUE,
													'null'				=> FALSE,
													'default'			=> 0
												),
						'authcode'  => array(
													'type' 				=> 'varchar',
													'constraint'		=> '10',
													'null'				=> FALSE
												),
						'date'  => array(
													'type' 				=> 'int',
													'constraint'		=> '10',
													'null'				=> FALSE
												),
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('queue_id', TRUE);
		$this->EE->dbforge->create_table('mailing_list_queue', TRUE);

		if ( ! function_exists('mailinglist_template'))
		{
			if ( ! file_exists(APPPATH.'language/'.$this->EE->config->item('deft_lang').'/email_data'.EXT))
			{
				return FALSE;
			}

			require APPPATH.'language/'.$this->EE->config->item('deft_lang').'/email_data'.EXT;
		}

		$data = array(
			'list_name' 	=> 'default',
			'list_title' 	=> 'Default Mailing List',
			'list_template' 	=> addslashes(mailinglist_template())
		);
		$this->EE->db->insert('mailing_lists', $data);

		$data = array(
			'module_name' 	=> 'Mailinglist',
			'module_version' 	=> $this->version,
			'has_cp_backend' 	=> 'y'
		);
		$this->EE->db->insert('modules', $data);

		$data = array(
			'class' 	=> 'Mailinglist',
			'method' 	=> 'insert_new_email'
		);
		$this->EE->db->insert('actions', $data);

		$data = array(
			'class' 	=> 'Mailinglist',
			'method' 	=> 'authorize_email'
		);
		$this->EE->db->insert('actions', $data);

		$data = array(
			'class' 	=> 'Mailinglist',
			'method' 	=> 'unsubscribe'
		);
		$this->EE->db->insert('actions', $data);

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
		$query = $this->EE->db->get_where('modules', array('module_name' => 'Mailinglist'));

		$this->EE->db->where('module_id', $query->row('module_id'));
		$this->EE->db->delete('module_member_groups');

		$this->EE->db->where('module_name', 'Mailinglist');
		$this->EE->db->delete('modules');

		$this->EE->db->where('class', 'Mailinglist');
		$this->EE->db->delete('actions');

		$this->EE->db->where('class', 'Mailinglist_mcp');
		$this->EE->db->delete('actions');

		$this->EE->dbforge->drop_table('mailing_lists');
		$this->EE->dbforge->drop_table('mailing_list');
		$this->EE->dbforge->drop_table('mailing_list_queue');

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
			$this->EE->db->query("ALTER TABLE `exp_mailing_list` MODIFY COLUMN `user_id` int(10) unsigned NOT NULL PRIMARY KEY auto_increment");
			$this->EE->db->query("ALTER TABLE `exp_mailing_list` DROP KEY `user_id`");
			$this->EE->db->query("ALTER TABLE `exp_mailing_list_queue` ADD COLUMN `queue_id` int(10) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY FIRST");
		}

		return TRUE;
	}
}
// END CLASS

/* End of file upd.mailinglist.php */
/* Location: ./system/expressionengine/modules/mailinglist/upd.mailinglist.php */