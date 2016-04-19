<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

class Member_upd {

	var $version = '2.1.0';

	/**
	 * Module Installer
	 *
	 * @return	bool
	 */
	public function install()
	{
		ee()->db->query("INSERT INTO exp_modules (module_name, module_version, has_cp_backend) VALUES ('Member', '$this->version', 'n')");
		$module_id = ee()->db->insert_id();

		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'registration_form')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'register_member')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'activate_member')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'member_login')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'member_logout')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'send_reset_token')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'process_reset_password')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'send_member_email')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'update_un_pw')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'member_search')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'member_delete')";

		foreach ($sql as $query)
		{
			ee()->db->query($query);
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
		$module = ee('Model')->get('Module')->filter('module_name', 'Member')->first();
		$module_id = $module->module_id;
		$directories = ee('Model')->get('UploadDestination')->filter('module_id', $module_id)->all();

		if (count($directories) > 0)
		{
			ee()->load->model('file_upload_preferences_model');
			$ids = array();

			foreach ($directories as $dir)
			{
				$ids[] = $dir->id;
			}

			ee()->file_upload_preferences_model->delete_upload_preferences($ids);
		}

		$sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '" . $module_id . "'";
		$sql[] = "DELETE FROM exp_modules WHERE module_name = 'Member'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Member'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Member_mcp'";

		foreach ($sql as $query)
		{
			ee()->db->query($query);
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

	// --------------------------------------------------------------------

	/**
	 * Update Upload Directories
	 *
	 * @access	public
	 * @return	bool
	 */

	function update_upload_directories($current, $data)
	{
		$directories = array(
			ee()->config->item('avatar_path') => 'avatar',
			ee()->config->item('photo_path') => 'photo',
			ee()->config->item('sig_img_path') => 'sig_img',
			ee()->config->item('prv_msg_upload_path') => 'prv_msg_upload'
		);

		if ( ! isset($directories[$current->server_path]))
		{
			return FALSE;
		}

		$config_prefix = $directories[$current->server_path] . '_';

		if ($config_prefix == 'prv_msg_upload')
		{
			$fields = array(
				'prv_msg_upload_path' => $data['current_path'],
				'prv_msg_attach_imaxsize' => $data['max_size']
			);
		}
		else
		{
			$fields = array(
				$config_prefix . 'path' => $data['server_path'],
				$config_prefix . 'url' => $data['url'],
				$config_prefix . 'max_kb' => $data['max_size'],
				$config_prefix . 'max_width' => $data['max_width'],
				$config_prefix . 'max_height' => $data['max_height']
			);
		}

		$config_update = ee()->config->update_site_prefs($fields);

		return TRUE;
	}
}
// END CLASS

// EOF
