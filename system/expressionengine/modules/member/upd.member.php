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

// --------------------------------------------------------------------

/**
 * Member Management Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class Member_upd {

	var $version = '2.1';

	public function __construct()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Module Installer
	 *
	 * @return	bool
	 */
	public function install()
	{
		$sql[] = "INSERT INTO exp_modules (module_name, module_version, has_cp_backend) VALUES ('Member', '$this->version', 'n')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'registration_form')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'register_member')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'activate_member')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'member_login')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'member_logout')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'retrieve_password')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'reset_password')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'send_member_email')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'update_un_pw')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'member_search')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'member_delete')";

		foreach ($sql as $query)
		{
			$this->EE->db->query($query);
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */
	public function uninstall()
	{
		$query = $this->EE->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Member'");

		$sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row('module_id') ."'";
		$sql[] = "DELETE FROM exp_modules WHERE module_name = 'Member'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Member'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Member_mcp'";

		foreach ($sql as $query)
		{
			$this->EE->db->query($query);
		}

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
// END CLASS

/* End of file upd.member.php */
/* Location: ./system/expressionengine/modules/member/upd.member.php */