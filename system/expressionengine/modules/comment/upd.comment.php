<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Comment Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class Comment_upd {

	var $version = '2.1';

	function Comment_upd()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		$this->EE->load->dbforge();
	}

	function tabs()
	{
		$tabs['date'] = array(
			'comment_expiration_date'	=> array(
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
		$data = array(
			'module_name' => 'Comment' ,
			'module_version' => $this->version,
			'has_cp_backend' => 'y'
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
		
		$data = array(
			'class'		=> 'Comment' ,
			'method'	=> 'comment_subscribe'
		);

		$this->EE->db->insert('actions', $data);
		
		$data = array(
			'class'		=> 'Comment' ,
			'method'	=> 'edit_comment'
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
						'comment'			 => array('type'	=> 'text')
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('comment_id', TRUE);
		$this->EE->dbforge->add_key(array('entry_id', 'channel_id', 'author_id', 'status', 'site_id'));
		$this->EE->dbforge->create_table('comments');
		
		
		$fields = array(
			'subscription_id'	=> array('type' => 'int'	, 'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'entry_id'			=> array('type' => 'int'	, 'constraint' => '10', 'unsigned' => TRUE),
			'member_id'			=> array('type' => 'int'	, 'constraint' => '10', 'default' => 0),
			'email'				=> array('type' => 'varchar', 'constraint' => '50'),
			'subscription_date'	=> array('type' => 'varchar', 'constraint' => '10'),
			'notification_sent'	=> array('type' => 'char'	, 'constraint' => '1', 'default' => 'n'),
			'hash'				=> array('type' => 'varchar', 'constraint' => '15')
		);
		
		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('subscription_id', TRUE);
		$this->EE->dbforge->add_key(array('entry_id', 'member_id'));
		$this->EE->dbforge->create_table('comment_subscriptions');
		

		$this->EE->load->library('layout');
		$this->EE->layout->add_layout_fields($this->tabs());
		
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
		$this->EE->dbforge->drop_table('comment_subscriptions');

		$this->EE->db->update('channel_titles', array('comment_total' => 0, 'recent_comment_date' => 0));

		//  Remove a couple items from the file
		
		$this->EE->config->_update_config(array(), array('comment_word_censoring' => '', 'comment_moderation_override' => '', 'comment_edit_time_limit' => ''));

		
		$this->EE->load->library('layout');
		$this->EE->layout->delete_layout_fields('comment_expiration_date');

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
		
		if ($current < 2.1)
		{
			$this->EE->db->query("UPDATE `exp_modules` SET `has_cp_backend` = 'y' WHERE module_name = 'comment'");

			$data = array(
				'class'		=> 'Comment' ,
				'method'	=> 'comment_subscribe'
				);

			$this->EE->db->insert('actions', $data);
		
			$data = array(
				'class'		=> 'Comment' ,
				'method'	=> 'edit_comment'
				);

			$this->EE->db->insert('actions', $data);
			
			// Note that the subscription table and notify migration occur in the ud_211.php file
		}		

		return TRUE;
	}
}
// END CLASS

/* End of file upd.comment.php */
/* Location: ./system/expressionengine/modules/comment/upd.comment.php */