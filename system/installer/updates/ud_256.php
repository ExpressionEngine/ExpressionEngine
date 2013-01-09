<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.5.6
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
	 * Update Session table
	 * We duplicate this from the 254 update as it wasn't added to the 
	 *
	 * @return TRUE
	 */
	private function _update_session_table()
	{
		if ( ! $this->EE->db->field_exists('fingerprint', 'sessions'))
		{
			$this->EE->dbforge->add_column(
				'sessions',
				array(
					'fingerprint' => array(
						'type'			=> 'varchar',
						'constraint'	=> 40
					),
					'sess_start' => array(
						'type'			=> 'int',
						'constraint'	=> 10,
						'unsigned'		=> TRUE,
						'default'		=> 0,
						'null'			=> FALSE
					)
				),
				'user_agent'
			);	
		}
		
		return TRUE;
	}
	
}	
/* END CLASS */

/* End of file ud_256.php */
/* Location: ./system/expressionengine/installer/updates/ud_256.php */