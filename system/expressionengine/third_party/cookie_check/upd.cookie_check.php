<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Cookie Check Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Update File
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Cookie_check_upd {

	var $version = '1.0';
	
	function Cookie_check_upd()
	{
		$this->EE =& get_instance();
	}
	
	// --------------------------------------------------------------------

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */	
	function install()
	{
		// Add Module
		$this->EE->db->insert('modules', array(
			'module_name'    => 'Cookie_check',
			'module_version'     => $this->version,
			'has_cp_backend'   => 'n'
		));



		// Add action
		$this->EE->db->insert('exp_actions', array(
			'class' => 'Cookie_check',
			'method' => 'set_cookies_allowed',
		));

		// Add action
		$this->EE->db->insert('exp_actions', array(
			'class' => 'Cookie_check',
			'method' => 'clear_ee_cookies',
		));		


		// Checks if cookies are allowed before setting them
		$this->EE->db->insert('extensions', array(
			'class'    => 'Cookie_check_ext',
			'hook'     => 'set_cookie_start',
			'method'   => 'check_cookie_permission',
			'settings' => '',
			'priority' => 5,
			'version'  => $this->version,
			'enabled'  => 'y'
		));



		$this->EE->db->insert('extensions', array(
			'class'    => 'Cookie_check_ext',
			'hook'     => 'forum_include_extras',
			'method'   => 'parse_forum_template',
			'settings' => '',
			'priority' => 5,
			'version'  => $this->version,
			'enabled'  => 'y'
		));



		$this->EE->db->insert('extensions', array(
			'class'    => 'Cookie_check_ext',
			'hook'     => 'forum_add_template',
			'method'   => 'forum_add_template',
			'settings' => '',
			'priority' => 5,
			'version'  => $this->version,
			'enabled'  => 'y'
		));


	// Frontend login- require cookies
	$this->EE->db->insert('extensions', array(
			'class'    => 'Cookie_check_ext',
			'hook'     => 'member_member_login_start',
			'method'   => 'front_login_cookie_required',
			'settings' => '',
			'priority' => 5,
			'version'  => $this->version,
			'enabled'  => 'y'
		));	

	// Frontend registration- require cookies
	$this->EE->db->insert('extensions', array(
			'class'    => 'Cookie_check_ext',
			'hook'     => 'member_member_register_errors',
			'method'   => 'front_register_cookie_required',
			'settings' => '',
			'priority' => 5,
			'version'  => $this->version,
			'enabled'  => 'y'
		));	


	// CP login- require cookies
	$this->EE->db->insert('extensions', array(
			'class'    => 'Cookie_check_ext',
			'hook'     => 'login_authenticate_start',
			'method'   => 'cp_login_cookie_required',
			'settings' => '',
			'priority' => 5,
			'version'  => $this->version,
			'enabled'  => 'y'
		));	

		return TRUE;
	}
	
	
	// --------------------------------------------------------------------

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */
	function uninstall()
	{
		$query = $this->EE->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Cookie_check'"); 
				
		$this->EE->db->delete('module_member_groups', array('module_id' => $query->row('module_id')));
		$this->EE->db->delete('modules', array('module_name' => 'Cookie_Check'));
		$this->EE->db->delete('actions', array('class' => 'Cookie_check'));
		
		
		// Disable extension
		$this->EE->db->delete('extensions', array('class' => 'Cookie_check_ext'));

		return TRUE;
	}



	// --------------------------------------------------------------------

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */	
	
	function update($current='')
	{
		return TRUE;
	}
	
}
/* END Class */

/* End of file upd.cookie_check.php */
/* Location: ./system/expressionengine/modules/cookie_check/upd.cookie_check.php */