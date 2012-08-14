<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.5.3
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
 * @link		http://expressionengine.com
 */
class Updater {
	
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
		
		$this->_change_site_preferences_column_type();
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Changes column type for the `site_system_preferences column` in
	 * `sites` from TEXT to MEDIUMTEXT
	 */
	private function _change_site_preferences_column_type()
	{
		$this->EE->dbforge->modify_column(
			'sites',
			array(
				'site_system_preferences' => array(
					'name' => 'site_system_preferences',
					'type' => 'mediumtext'
				)
			)
		);
	}
}	
/* END CLASS */

/* End of file ud_253.php */
/* Location: ./system/expressionengine/installer/updates/ud_253.php */