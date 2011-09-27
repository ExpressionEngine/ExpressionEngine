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
				
		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Update Session Table
	 *
	 * Drops site_id field from sessions table 
	 *
	 * @return 	void
	 */
	private function _update_session_table()
	{
		// Drop site_id
		$this->dbforge->drop_column('sessions', 'site_id');
    }
}   
/* END CLASS */

/* End of file ud_223.php */
/* Location: ./system/expressionengine/installer/updates/ud_223.php */