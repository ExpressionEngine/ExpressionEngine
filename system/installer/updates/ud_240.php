<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
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
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
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
		
		return TRUE;
	}
	
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
	
	/**
	 * Update File Dimensions Table
	 *
	 * Adds a site_id column to file_dimensions table
	 *
	 * @return	void
	 */
	private function _update_file_dimensions_table()
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
	
	/**
	 * Add Developer Log table
	 *
	 * @return	void
	 */
	private function _add_developer_log_table()
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
/* END CLASS */

/* End of file ud_240.php */
/* Location: ./system/expressionengine/installer/updates/ud_240.php */