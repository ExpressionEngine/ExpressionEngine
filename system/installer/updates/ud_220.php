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
		
		// Build file-related tables
		$this->_build_file_tables();
		
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
					'batch_location' => array(
								'type'			=> 'VARCHAR',
								'constraint'	=> 255,
								'null'			=> FALSE,
								'default'		=> ''
								));
		
		$this->EE->dbforge->add_column('upload_prefs', $fields);
	}

	// ------------------------------------------------------------------------

	/**
	 *
	 *
	 *
	 */
	private function _build_file_tables()
	{
		
	}
	
}
/* END CLASS */

/* End of file ud_220.php */
/* Location: ./system/expressionengine/installer/updates/ud_220.php */