<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
 */

class Member_upd {

	var $version = '2.1';

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

		$site_id = ee()->config->item('site_id');

		$avatar_directory = ee('Model')->make('UploadDestination');
		$avatar_directory->site_id = $site_id;
		$avatar_directory->name = 'Avatars';
		$avatar_directory->server_path = ee()->config->item('avatar_path');
		$avatar_directory->url = ee()->config->item('avatar_url');
		$avatar_directory->allowed_types = 'img';
		$avatar_directory->removeNoAccess();
		$avatar_directory->save();

		// Insert Avatars
		$dir = ee()->config->item('avatar_path');
		$files = scandir($dir); 

		foreach ($files as $file)
		{
			$path = $dir . $file;
			
			if ($file != 'index.html' && is_file($path))
			{
				$time = time();
				$avatar = ee('Model')->make('File');
				$avatar->site_id = $site_id;
				$avatar->upload_location_id = $avatar_directory->id;
				$avatar->uploaded_by_member_id = 1;
				$avatar->modified_by_member_id = 1;
				$avatar->title = $file;
				$avatar->rel_path = $file;
				$avatar->file_name = $file;
				$avatar->upload_date = $time;
				$avatar->modified_date = $time;
				$avatar->mime_type = mime_content_type($path);
				$avatar->file_size = filesize($path);
				$avatar->save();
			}
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
		// Remove avatar upload directory
		$directory = ee('Model')->get('UploadDestination')->filter('server_path', ee()->config->item('avatar_path'))->first();

		if ( ! empty($directory))
		{
			ee()->load->model('file_upload_preferences_model');
			ee()->file_upload_preferences_model->delete_upload_preferences(array($directory->id));
		}

		$query = ee()->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Member'");

		$sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row('module_id') ."'";
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
}
// END CLASS

/* End of file upd.member.php */
/* Location: ./system/expressionengine/modules/member/upd.member.php */
