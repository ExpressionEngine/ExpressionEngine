<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package     ExpressionEngine
 * @author      EllisLab Dev Team
 * @copyright   Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license     http://ellislab.com/expressionengine/user-guide/license.html
 * @link        http://ellislab.com
 * @since       Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package     ExpressionEngine
 * @subpackage  Core
 * @category    Core
 * @author      EllisLab Dev Team
 * @link        http://ellislab.com
 */
class Updater {

	private $EE;
	var $version_suffix = '';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------
	
	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		$this->EE->load->dbforge();
		
		$this->_update_session_table();

		$this->_update_password_lockout_table();
		
		$this->_update_members_table();
		
		$this->_update_files_table();

		$this->_update_comments_table();

		$this->_update_template_groups();

		$this->_alter_sidebar_deft();

		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Update Session Table
	 *
	 * This method updates the sessions table to add an index on 
	 * `last_activity` as it should help speed up session gc on large sites
	 * secondly, updating `user_agent` to VARCHAR(120) to more closely
	 * match what's going on in CodeIgniter's session class.
	 *
	 * @return 	void
	 */
	private function _update_session_table()
	{
		// Add an index on last_activity
		$this->EE->db->query("CREATE INDEX last_activity_idx on exp_sessions(last_activity)");

		$field = array(
			'user_agent'	=> array(
				'name'			=> 'user_agent',
				'type'			=> 'VARCHAR',
				'constraint'	=> 120
			)
		);

		$this->EE->dbforge->modify_column('sessions', $field);
	}

	// --------------------------------------------------------------------

	/**
	 * Update Password lockout Table
	 *
	 * This method updates the password_lockout table, updating `user_agent` 
	 * to VARCHAR(120) to more closely match what's going on in CodeIgniter's session class.
	 *
	 * @return 	void
	 */
	private function _update_password_lockout_table()
	{

		$field = array(
			'user_agent'	=> array(
				'name'			=> 'user_agent',
				'type'			=> 'VARCHAR',
				'constraint'	=> 120
			)
		);

		$this->EE->dbforge->modify_column('password_lockout', $field);
	}
	// --------------------------------------------------------------------

	/**
	 * Update members table
	 *
	 * Oh this is fun!  So since we're implementing a better password hashing
	 * scheme, we'll bump up the `password` field in exp_members to 
	 * be able to handle hashing algorithims such as sha256/sha512.
	 * Additionally, we're adding a salt column to use for salting the 
	 * users passwords.
	 */
	private function _update_members_table()
	{
		// Update password column to VARCHAR(128)
		$field = array(
			'password'		=> array(
				'name'			=> 'password',
				'type'			=> 'VARCHAR',
				'constraint'	=> 128
			)
		);

		$this->EE->dbforge->modify_column('members', $field);


		// Add a salt column VARCHAR(128)
		$field = array(
			'salt'			=> array(
				'type'			=> 'VARCHAR',
				'constraint'	=> 128,
				'default'		=> '',
				'null'			=> FALSE
			)
		);

		$this->EE->dbforge->add_column('members', $field);
		
		
		// Add a remember_me column VARCHAR(32)
		$field = array(
			'remember_me'	=> array(
				'type'			=> 'VARCHAR',
				'constraint'	=> 32,
				'default'		=> '',
				'null'			=> FALSE
			)
		);

		$this->EE->dbforge->add_column('members', $field);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Add caption field to files
	 */
	private function _update_files_table()
	{
		$field = array(
			'caption'	=> array(
				'type'		=> 'text'
			)
		);

		$this->EE->dbforge->add_column('files', $field);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Adds an index on exp_comments(comment_date)
	 */
	private function _update_comments_table()
	{
		if ($this->EE->db->table_exists('exp_comments'))
		{
			// Add an index on comment_date
			$this->EE->db->query("CREATE INDEX comment_date_idx on exp_comments(comment_date)");
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Adds an index on template_groups(group_name) & template_groups(group_order)
	 */
	private function _update_template_groups()
	{
		$this->EE->db->query("CREATE INDEX group_name_idx on exp_template_groups(group_name)");
		$this->EE->db->query("CREATE INDEX group_order_idx on exp_template_groups(group_order)");
	}

	// --------------------------------------------------------------------	

	/**
	 * Alter Sidebar state default
	 */
	private function _alter_sidebar_deft()
	{
		$this->EE->db->query("ALTER TABLE exp_members ALTER COLUMN show_sidebar SET DEFAULT 'n'");
	}

	// --------------------------------------------------------------------	

}
/* END CLASS */

/* End of file ud_220.php */
/* Location: ./system/expressionengine/installer/updates/ud_220.php */