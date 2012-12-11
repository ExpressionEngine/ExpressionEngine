<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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
		
		$this->_update_watermarks_table();
		$this->_update_file_dimensions_table();
		$this->_update_files_table();
		$this->_add_developer_log_table();
		$this->_create_remember_me();
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Update Watermarks Table
	 *
	 * Renames watermark offset columns to be more consistent with CodeIgniter
	 *
	 * @return	void
	 */
	private function _update_watermarks_table()
	{
		// Rename offset columns
		$this->EE->dbforge->modify_column(
			'file_watermarks',
			array(
				'wm_x_offset' => array(
					'name' => 'wm_hor_offset',
					'type' => 'int'
				),
				'wm_y_offset' => array(
					'name' => 'wm_vrt_offset',
					'type' => 'int'
				)
			)
		);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Update File Dimensions Table
	 *
	 * Adds a site_id column to file_dimensions table
	 *
	 * @return	void
	 */
	private function _update_file_dimensions_table()
	{
		if ( ! $this->EE->db->field_exists('site_id', 'file_dimensions'))
		{
			$this->EE->dbforge->add_column(
				'file_dimensions',
				array(
					'site_id' => array(
						'type'			=> 'int',
						'constraint'	=> 4,
						'unsigned'		=> TRUE,
						'default'		=> '1',
						'null'			=> FALSE
					)
				)
			);	
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Update Files Table
	 *
	 * Adds extra metadata fields to file table, and deletes old custom fields
	 *
	 * @return	void
	 */
	private function _update_files_table()
	{
		$this->EE->dbforge->add_column(
			'files',
			array(
				'credit' => array(
					'type'			=> 'varchar',
					'constraint'	=> 255
				),
				'location' => array(
					'type'			=> 'varchar',
					'constraint'	=> 255
				)
			)
		);
		
		// Rename "caption" field to "description"
		$this->EE->dbforge->modify_column(
			'files',
			array(
				'caption' => array(
					'name' => 'description',
					'type' => 'text'
				),
			)
		);
		
		// Drop the 6 custom fields
		for ($i = 1; $i < 7; $i++)
		{ 
			$this->EE->dbforge->drop_column('files', 'field_'.$i);
			$this->EE->dbforge->drop_column('files', 'field_'.$i.'_fmt');
		}
		
		// Drop 'metadata' and 'status' fields
		$this->EE->dbforge->drop_column('files', 'metadata');
		$this->EE->dbforge->drop_column('files', 'status');
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Add Developer Log table
	 *
	 * @return	void
	 */
	private function _add_developer_log_table()
	{
		if ( ! $this->EE->db->table_exists('developer_log'))
		{
			$this->EE->dbforge->add_field(
				array(
					'log_id' => array(
						'type'				=> 'int',
						'constraint'		=> 10,
						'unsigned'			=> TRUE,
						'auto_increment'	=> TRUE
					),
					'timestamp' => array(
						'type'				=> 'int',
						'constraint'		=> 10,
						'unsigned'			=> TRUE
					),
					'viewed' => array(
						'type'				=> 'char',
						'constraint'		=> 1,
						'default'			=> 'n'
					),
					'description' => array(
						'type'				=> 'text',
						'null'				=> TRUE
					),
					'function' => array(
						'type'				=> 'varchar',
						'constraint'		=> 100,
						'null'				=> TRUE
					),
					'line' => array(
						'type'				=> 'int',
						'constraint'		=> 10,
						'unsigned'			=> TRUE,
						'null'				=> TRUE
					),
					'file' => array(
						'type'				=> 'varchar',
						'constraint'		=> 255,
						'null'				=> TRUE
					),
					'deprecated_since' => array(
						'type'				=> 'varchar',
						'constraint'		=> 10,
						'null'				=> TRUE
					),
					'use_instead' => array(
						'type'				=> 'varchar',
						'constraint'		=> 100,
						'null'				=> TRUE
					)
				)
			);
			
			$this->EE->dbforge->add_key('log_id', TRUE);
			$this->EE->dbforge->create_table('developer_log');
		}
	}
	
	
	// --------------------------------------------------------------------

	/**
	 * Adds the new remember_me table and drops the remember_me column
	 * from the member table
	 *
	 * @return 	void
	 */
	private function _create_remember_me()
	{
		if ($this->EE->db->field_exists('remember_me', 'members'))
		{
			// Hotness coming up, drop it!
			$this->EE->dbforge->drop_column('members', 'remember_me');	
		}

		if ( ! $this->EE->db->table_exists('remember_me'))
		{
			// This has the same structure as sessions, except for the
			// primary key and "last_activity" fields. Also added site_id back
			// for this table so that we can count active remember me's per
			// member per site
			$this->EE->dbforge->add_field(array(
				'remember_me_id'	=> array(
					'type'				=> 'VARCHAR',
					'constraint'		=> 40,
					'default'			=> '0'
				),
				'member_id'			=> array(
					'type'				=> 'INT',
					'constraint'		=> 10,
					'default'			=> '0'
				),
				'ip_address'		=> array(
					'type'				=> 'VARCHAR',
					'constraint'		=> 16,
					'default'			=> '0'
				),
				'user_agent'		=> array(
					'type'				=> 'VARCHAR',
					'constraint'		=> 120,
					'default'			=> ''
				),
				'admin_sess'		=> array(
					'type'				=> 'TINYINT',
					'constraint'		=> 1,
					'default'			=> '0'
				),
				'site_id'			=> array(
					'type'				=> 'INT',
					'constraint'		=> 4,
					'default'			=> '1'
				),
				'expiration'		=> array(
					'type'				=> 'INT',
					'constraint'		=> 10,
					'default'			=> '0'
				),
				'last_refresh'		=> array(
					'type'				=> 'INT',
					'constraint'		=> 10,
					'default'			=> '0'
				)
			));
			
			$this->EE->dbforge->add_key('remember_me_id', TRUE);
			$this->EE->dbforge->add_key('member_id');
			
			$this->EE->dbforge->create_table('remember_me');
		}
	}
}	
/* END CLASS */

/* End of file ud_240.php */
/* Location: ./system/expressionengine/installer/updates/ud_240.php */