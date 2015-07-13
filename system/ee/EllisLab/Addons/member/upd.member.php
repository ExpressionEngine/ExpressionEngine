<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
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

		// Install member upload directories
		$site_id = ee()->config->item('site_id');
		$member_directories = array();

		if (ee()->config->item('enable_avatars') == 'y')
		{
			$member_directories['Avatars'] = array(
				'server_path' => ee()->config->item('avatar_path'),
				'url' => ee()->config->item('avatar_url'),
				'allowed_types' => 'img',
				'max_width' => ee()->config->item('avatar_max_width'),
				'max_height' => ee()->config->item('avatar_max_height'),
				'max_size' => ee()->config->item('avatar_max_kb'),
			);
		}

		if (ee()->config->item('enable_photos') == 'y')
		{
			$member_directories['Member Photos'] = array(
				'server_path' => ee()->config->item('photo_path'),
				'url' => ee()->config->item('photo_url'),
				'allowed_types' => 'img',
				'max_width' => ee()->config->item('photo_max_width'),
				'max_height' => ee()->config->item('photo_max_height'),
				'max_size' => ee()->config->item('photo_max_kb'),
			);
		}

		if (ee()->config->item('allow_signatures') == 'y')
		{
			$member_directories['Signature Attachments'] = array(
				'server_path' => ee()->config->item('sig_img_path'),
				'url' => ee()->config->item('sig_img_url'),
				'allowed_types' => 'img',
				'max_width' => ee()->config->item('sig_img_max_width'),
				'max_height' => ee()->config->item('sig_img_max_height'),
				'max_size' => ee()->config->item('sig_img_max_kb'),
			);
		}

		$member_directories['PM Attachments'] = array(
			'server_path' => ee()->config->item('prv_msg_upload_path'),
			'url' => str_replace('avatars', 'pm_attachments', ee()->config->item('avatar_url')),
			'allowed_types' => 'img',
			'max_size' => ee()->config->item('prv_msg_attach_maxsize')
		);

		$module = ee('Model')->get('Module', array($module_id))->first();

		foreach ($member_directories as $name => $dir)
		{
			$directory = ee('Model')->make('UploadDestination');
			$directory->site_id = $site_id;
			$directory->name = $name;
			$directory->removeNoAccess();
			$directory->setModule($module);

			foreach ($dir as $property => $value)
			{
				$directory->$property = $value;
			}

			$directory->save();

			// Insert Files
			$files = scandir($dir['server_path']);

			foreach ($files as $filename)
			{
				$path = $dir['server_path'] . $filename;

				if ($filename != 'index.html' && is_file($path))
				{
					$time = time();
					$file = ee('Model')->make('File');
					$file->site_id = $site_id;
					$file->upload_location_id = $directory->id;
					$file->uploaded_by_member_id = 1;
					$file->modified_by_member_id = 1;
					$file->title = $filename;
					$file->file_name = $filename;
					$file->upload_date = $time;
					$file->modified_date = $time;
					$file->mime_type = mime_content_type($path);
					$file->file_size = filesize($path);
					$file->save();
				}
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

/* End of file upd.member.php */
/* Location: ./system/expressionengine/modules/member/upd.member.php */
