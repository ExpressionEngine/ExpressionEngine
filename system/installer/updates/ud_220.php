<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package     ExpressionEngine
 * @author      ExpressionEngine Dev Team
 * @copyright   Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license     http://expressionengine.com/user_guide/license.html
 * @link        http://expressionengine.com
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
 * @author      ExpressionEngine Dev Team
 * @link        http://expressionengine.com
 */
class Updater {

	private $EE;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}

	// ------------------------------------------------------------------------

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		$this->EE->load->dbforge();
		
		// Add batch dir preference to exp_upload_prefs
		$this->_do_upload_pref_update();
		
		// Update category group
		$this->_do_cat_group_update();
		
		// Build file-related tables
		$this->_do_build_file_tables();
		
		return TRUE;
	}

	// ------------------------------------------------------------------------
	
	/**
	 * Upload pref table update
	 *
	 * This method adds the batch_location column to the table
	 *
	 * @return void
	 */
	private function _do_upload_pref_update()
	{
		$fields = array(
					'batch_location' 	=> array(
								'type'			=> 'VARCHAR',
								'constraint'	=> 255,
								)
					'cat_group'			=> array(
								'type'			=> 'VARCHAR',
								'constraint'	=> 255
					));

		$this->EE->dbforge->add_column('upload_prefs', $fields);
		
		$fields = array(
					'server_path'	=> array(
								'name'			=> 'server_path',
								'type'			=> 'VARCHAR',
								'constraint'	=> 255
					),
		);
		
		$this->EE->dbforge->modify_column('upload_prefs', $fields);
	}

	// ------------------------------------------------------------------------

	/**
	 *
	 *
	 *
	 */
	private function _do_build_file_tables()
	{
		
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Update exp_category_groups
	 *
	 * Add a column for excluding a group from files or channel group assignment
	 *
	 * @return void
	 */
	private function _do_cat_group_update()
	{
		$fields = array(
					'exclude_group' 	=> array(
								'type'			=> 'TINYINT',
								'constraint'	=> 1,
								'null'			=> FALSE,
								'default'		=> 0
								));

		$this->EE->dbforge->add_column('category_groups', $fields);		
	}

	// ------------------------------------------------------------------------	
	
}
/* END CLASS */

/* End of file ud_220.php */
/* Location: ./system/expressionengine/installer/updates/ud_220.php */