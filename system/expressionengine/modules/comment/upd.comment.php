<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Comment_upd {

	var $version = '2.3.2';

	function Comment_upd()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		ee()->load->dbforge();
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

		ee()->db->insert('modules', $data);

		$data = array(
			'class'		=> 'Comment' ,
			'method'	=> 'insert_new_comment'
		);

		ee()->db->insert('actions', $data);

		$data = array(
			'class'		=> 'Comment_mcp' ,
			'method'	=> 'delete_comment_notification'
		);

		ee()->db->insert('actions', $data);

		$data = array(
			'class'		=> 'Comment' ,
			'method'	=> 'comment_subscribe'
		);

		ee()->db->insert('actions', $data);

		$data = array(
			'class'		=> 'Comment' ,
			'method'	=> 'edit_comment'
		);

		ee()->db->insert('actions', $data);


		$fields = array(
			'comment_id'	=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'site_id'		=> array('type' => 'int', 'constraint' => '4', 'default' => 1),
			'entry_id'		=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'default' => 0),
			'channel_id'	=> array('type' => 'int', 'constraint' => '4', 'unsigned' => TRUE, 'default' => 1),
			'author_id'		=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'default' => 0),
			'status'		=> array('type' => 'char', 'constraint'	=> '1', 'default' => 0),
			'name'			=> array('type' => 'varchar' , 'constraint' => '50'),
			'email'			=> array('type' => 'varchar' , 'constraint' => '75'),
			'url'			=> array('type' => 'varchar' , 'constraint' => '75'),
			'location'		=> array('type' => 'varchar' , 'constraint' => '50'),
			'ip_address'	=> array('type' => 'varchar' , 'constraint' => '45'),
			'comment_date'	=> array('type' => 'int' , 'constraint' => '10'),
			'edit_date'		=> array('type' => 'int' , 'constraint' => '10'),
			'comment'		=> array('type' => 'text')
		);

		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('comment_id', TRUE);
		ee()->dbforge->add_key(array('entry_id', 'channel_id', 'author_id', 'status', 'site_id'));
		ee()->dbforge->create_table('comments');

		ee()->load->library('smartforge');
		ee()->smartforge->add_key('comments', 'comment_date', 'comment_date_idx');


		$fields = array(
			'subscription_id'	=> array('type' => 'int'	, 'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'entry_id'			=> array('type' => 'int'	, 'constraint' => '10', 'unsigned' => TRUE),
			'member_id'			=> array('type' => 'int'	, 'constraint' => '10', 'default' => 0),
			'email'				=> array('type' => 'varchar', 'constraint' => '75'),
			'subscription_date'	=> array('type' => 'varchar', 'constraint' => '10'),
			'notification_sent'	=> array('type' => 'char'	, 'constraint' => '1', 'default' => 'n'),
			'hash'				=> array('type' => 'varchar', 'constraint' => '15')
		);

		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('subscription_id', TRUE);
		ee()->dbforge->add_key(array('entry_id', 'member_id'));
		ee()->dbforge->create_table('comment_subscriptions');


		ee()->load->library('layout');
		ee()->layout->add_layout_fields($this->tabs());

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
		ee()->db->select('module_id');
		$query = ee()->db->get_where('modules', array('module_name' => 'Comment'));

		ee()->db->where('module_id', $query->row('module_id'));
		ee()->db->delete('module_member_groups');

		ee()->db->where('module_name', 'Comment');
		ee()->db->delete('modules');

		ee()->db->where('class', 'Comment');
		ee()->db->delete('actions');

		ee()->db->where('class', 'Comment_mcp');
		ee()->db->delete('actions');

		ee()->dbforge->drop_table('comments');
		ee()->dbforge->drop_table('comment_subscriptions');

		ee()->db->update('channel_titles', array('comment_total' => 0, 'recent_comment_date' => 0));

		//  Remove a couple items from the file

		ee()->config->_update_config(array(), array('comment_word_censoring' => '', 'comment_moderation_override' => '', 'comment_edit_time_limit' => ''));


		ee()->load->library('layout');
		ee()->layout->delete_layout_fields('comment_expiration_date');

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
		if (version_compare($current, '2.0', '<'))
		{
			ee()->db->query("ALTER TABLE `exp_comments` CHANGE `weblog_id` `channel_id` INT(4) UNSIGNED NOT NULL DEFAULT 1");
		}

		if (version_compare($current, '2.1', '<'))
		{
			ee()->db->query("UPDATE `exp_modules` SET `has_cp_backend` = 'y' WHERE module_name = 'comment'");

			$data = array(
				'class'		=> 'Comment' ,
				'method'	=> 'comment_subscribe'
				);

			ee()->db->insert('actions', $data);

			$data = array(
				'class'		=> 'Comment' ,
				'method'	=> 'edit_comment'
				);

			ee()->db->insert('actions', $data);

			// Note that the subscription table and notify migration occur in the ud_211.php file
		}

		if (version_compare($current, '2.2', '<'))
		{
			$query = ee()->db->query("SHOW INDEX FROM `exp_comments`");
			$indexes = array();

			foreach ($query->result_array() as $row)
			{

				$indexes[] = $row['Key_name'];
			}

			if (in_array('weblog_id', $indexes))
			{
				ee()->db->query("ALTER TABLE `exp_comments` DROP KEY `weblog_id`");
			}

			if ( ! in_array('channel_id', $indexes))
			{
				ee()->db->query("ALTER TABLE `exp_comments` ADD KEY (`channel_id`)");
			}
		}

		if (version_compare($current, '2.3', '<'))
		{
			// Update ip_address column
			ee()->load->dbforge();

			ee()->dbforge->modify_column(
				'comments',
				array(
					'ip_address' => array(
						'name' 			=> 'ip_address',
						'type' 			=> 'varchar',
						'constraint'	=> '45'
					)
				)
			);
		}

		if (version_compare($current, '2.3.1', '<'))
		{
			ee()->load->library('smartforge');

			// Correcting schema disparities from upgrade vs. fresh install.
			// Just going for a full pass.
			$fields = array(
				'comment_id'	=> array('type' => 'int',		'constraint' => '10',	'unsigned' => TRUE,	'auto_increment' => TRUE),
				'site_id'		=> array('type' => 'int',		'constraint' => '4',	'default' => 1),
				'entry_id'		=> array('type' => 'int',		'constraint' => '10',	'unsigned' => TRUE,	'default' => 0),
				'channel_id'	=> array('type' => 'int',		'constraint' => '4',	'unsigned' => TRUE,	'default' => 1),
				'author_id'		=> array('type' => 'int',		'constraint' => '10',	'unsigned' => TRUE,	'default' => 0),
				'status'		=> array('type' => 'char',		'constraint' => '1',	'default' => 0),
				'name'			=> array('type' => 'varchar',	'constraint' => '50'),
				'email'			=> array('type' => 'varchar',	'constraint' => '75'),
				'url'			=> array('type' => 'varchar',	'constraint' => '75'),
				'location'		=> array('type' => 'varchar',	'constraint' => '50'),
				'ip_address'	=> array('type' => 'varchar',	'constraint' => '45'),
				'comment_date'	=> array('type'	=> 'int',		'constraint' => '10'),
				'edit_date'		=> array('type'	=> 'int',		'constraint' => '10'),
				'comment'		=> array('type'	=> 'text')
			);

			ee()->smartforge->modify_column('comments', $fields);

			ee()->smartforge->add_key('comments', 'comment_date', 'comment_date_idx');
		}

		if (version_compare($current, '2.3.2', '<'))
		{
			ee()->load->library('smartforge');

			// Correcting schema disparities from upgrade vs. fresh install.
			// Just going for a full pass.
			$fields = array(
				'email'			=> array('type' => 'varchar',	'constraint' => '75')
			);

			ee()->smartforge->modify_column('comments', $fields);

			ee()->smartforge->modify_column('comment_subscriptions', $fields);

		}

		return TRUE;
	}
}
// END CLASS

/* End of file upd.comment.php */
/* Location: ./system/expressionengine/modules/comment/upd.comment.php */