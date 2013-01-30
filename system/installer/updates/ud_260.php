<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.6
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
		
		$this->_change_member_totals_length();
		$this->_update_session_table();
		$this->_update_config_add_cookie_httponly();

		return TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Changes column type for `total_entries` and `total_comments` in the
	 * members table from smallint to mediumint to match the columns in the
	 * channels table and stats table.
	 */
	private function _change_member_totals_length()
	{
		$this->EE->dbforge->modify_column(
			'members',
			array(
				'total_entries' => array(
					'name' => 'total_entries',
					'type' => 'mediumint(8)'
				),
				'total_comments' => array(
					'name' => 'total_comments',
					'type' => 'mediumint(8)'
				),
			)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * update Session table
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

	/**
	 * Update Config to Add cookie_httponly
	 *
	 * Update the config.php file to add the new cookie_httponly paramter and
	 * set it to default to 'y'.  
 	 */
	private function _update_config_add_cookie_httponly()
	{
		$this->EE->config->_update_config(
			array(
				'cookie_httponly' => 'y' 
			)
		);
	}
}	
/* END CLASS */

/* End of file ud_260.php */
/* Location: ./system/expressionengine/installer/updates/ud_260.php */
