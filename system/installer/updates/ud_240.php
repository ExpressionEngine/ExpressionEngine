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
		$this->EE->dbforge->modify_column('file_watermarks',
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
		$column = array(
			'site_id'			=> array(
				'type'			=> 'int',
				'constraint'	=> 4,
				'unsigned'		=> TRUE,
				'default'		=> '1',
				'null'			=> FALSE
			)
		);

		$this->EE->dbforge->add_column('file_dimensions', $column);
	}
}	
/* END CLASS */

/* End of file ud_240.php */
/* Location: ./system/expressionengine/installer/updates/ud_240.php */