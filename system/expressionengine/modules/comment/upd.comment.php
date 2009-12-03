<?php
/*
=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 http://expressionengine.com/
-----------------------------------------------------
 Copyright (c) 2003 - 2009, EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 http://expressionengine.com/docs/license.html
=====================================================
 File: mcp.comment.php
-----------------------------------------------------
 Purpose: Commenting class - CP
=====================================================
*/

if ( ! defined('EXT'))
{
	exit('Invalid file request');
}


class Comment_upd {

	var $version = '2.0';

	function Comment_upd()
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
			'module_name' => 'Comment' ,
			'module_version' => $this->version,
			'has_cp_backend' => 'n'
		);

		$this->EE->db->insert('modules', $data);

		$data = array(
			'class'		=> 'Comment' ,
			'method'	=> 'insert_new_comment'
		);

		$this->EE->db->insert('actions', $data);

		$data = array(
			'class'		=> 'Comment_mcp' ,
			'method'	=> 'delete_comment_notification'
		);

		$this->EE->db->insert('actions', $data);

		$fields = array(
						'comment_id'		  => array(	'type' 			 => 'int',
														'constraint'	 => '10',
														'unsigned'		 => TRUE,
														'auto_increment' => TRUE),
						'site_id'			  => array(	'type'			=> 'int',
														'constraint'	=> '4',
														'default'		=> 1),
						'entry_id'			  => array(	'type'			=> 'int',
														'constraint'	=> '10',
														'unsigned'		=> TRUE,
														'default'		=> 0),
						'channel_id'		  => array( 'type'		 	=> 'int',
														'constraint' 	=> '4',
														'unsigned'	 	=> TRUE,
														'default'	 	=> 1),
						'author_id'			  => array(	'type'		 	=> 'int',
														'constraint' 	=> '10',
														'unsigned'	 	=> TRUE,
														'default'	 	=> 0),
						'status'			 => array(	'type'			=> 'char',
														'constraint'	=> '1',
														'default'	 	=> '0'),
						'name'				 => array('type' => 'varchar' , 'constraint' => '50'),
						'email'				 => array('type' => 'varchar' , 'constraint' => '50'),
						'url'				 => array('type' => 'varchar' , 'constraint' => '75'),
						'location'			 => array('type' => 'varchar' , 'constraint' => '50'),
						'ip_address'		 => array('type' => 'varchar' , 'constraint' => '16'),
						'comment_date'		 => array('type'	=> 'int'  , 'constraint' => '10'),
						'edit_date'			 => array('type'	=> 'int'  , 'constraint' => '10'),
						'comment'			 => array('type'	=> 'text'),
						'notify'			 => array('type'		=> 'char',
													  'constraint' 	=> '1',
													  'default'		=> 'n')
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('comment_id', TRUE);
		$this->EE->dbforge->add_key(array('entry_id', 'channel_id', 'author_id', 'status', 'site_id'));
		$this->EE->dbforge->create_table('comments');

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
		$query = $this->EE->db->get_where('modules', array('module_name' => 'Comment'));

		$this->EE->db->where('module_id', $query->row('module_id'));
		$this->EE->db->delete('module_member_groups');

		$this->EE->db->where('module_name', 'Comment');
		$this->EE->db->delete('modules');

		$this->EE->db->where('class', 'Comment');
		$this->EE->db->delete('actions');

		$this->EE->db->where('class', 'Comment_mcp');
		$this->EE->db->delete('actions');

		$this->EE->dbforge->drop_table('comments');

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
		if ($current < 2.0)
		{
			$this->EE->db->query("ALTER TABLE `exp_comments` CHANGE `weblog_id` `channel_id` INT(4) UNSIGNED NOT NULL DEFAULT 1");
		}

		return TRUE;
	}
}
// END CLASS

/* End of file upd.comment.php */
/* Location: ./system/expressionengine/modules/comment/upd.comment.php */