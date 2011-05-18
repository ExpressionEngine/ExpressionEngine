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

		// $this->_update_members_table();

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
	 * Update members table
	 *
	 * So this is fun, this update will alter the password field on the members
	 * table to be VARCHAR(64) so we can use hash('sha256', 'val') for the
	 * password hashing scheme.
	 */
	// private function _update_members_table()
	// {
	// 	$field = array(
	// 		'password'		=> array(
	// 			'name'			=> 'password',
	// 			'type'			=> 'VARCHAR',
	// 			'constraint'	=> 64
	// 		)
	// 	);

	// 	$this->EE->dbforge->modify_column('members', $field);
	// }

	// --------------------------------------------------------------------
}
/* END CLASS */

/* End of file ud_215.php */
/* Location: ./system/expressionengine/installer/updates/ud_215.php */
