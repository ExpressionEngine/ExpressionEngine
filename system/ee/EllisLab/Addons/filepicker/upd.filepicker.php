<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * File Picker Module update class
 */
class Filepicker_upd {

	public $version	= '1.0';

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */
	function install()
	{
		$mod_data = array(
			'module_name'        => 'Filepicker',
			'module_version'     => $this->version,
			'has_cp_backend'     => 'y',
			'has_publish_fields' => 'n'
		);

		ee()->db->insert('modules', $mod_data);

		// Install default upload directories
		$site_id = 1;
		$member_directories = array();

		// When installing, ee()->config will contain the installer app values,
		// not the ExpressionEngine application values.
		// So fetch them from the model - dj
		$member_prefs = ee('Model')->get('Site', $site_id)->first()->site_member_preferences;

		$member_directories['Avatars'] = array(
			'server_path' => $member_prefs->avatar_path,
			'url' => $member_prefs->avatar_url,
			'allowed_types' => 'img',
			'max_width' => $member_prefs->avatar_max_width,
			'max_height' => $member_prefs->avatar_max_height,
			'max_size' => $member_prefs->avatar_max_kb,
		);

		$member_directories['Default Avatars'] = array(
			'server_path' => rtrim($member_prefs->avatar_path, '/').'/default/',
			'url' => rtrim($member_prefs->avatar_url, '/').'/default/',
			'allowed_types' => 'img',
			'max_width' => $member_prefs->avatar_max_width,
			'max_height' => $member_prefs->avatar_max_height,
			'max_size' => $member_prefs->avatar_max_kb,
		);

		$member_directories['Member Photos'] = array(
			'server_path' => $member_prefs->photo_path,
			'url' => $member_prefs->photo_url,
			'allowed_types' => 'img',
			'max_width' => $member_prefs->photo_max_width,
			'max_height' => $member_prefs->photo_max_height,
			'max_size' => $member_prefs->photo_max_kb,
		);

		$member_directories['Signature Attachments'] = array(
			'server_path' => $member_prefs->sig_img_path,
			'url' => $member_prefs->sig_img_url,
			'allowed_types' => 'img',
			'max_width' => $member_prefs->sig_img_max_width,
			'max_height' => $member_prefs->sig_img_max_height,
			'max_size' => $member_prefs->sig_img_max_kb,
		);

		$member_directories['PM Attachments'] = array(
			'server_path' => $member_prefs->prv_msg_upload_path,
			'url' => str_replace('avatars', 'pm_attachments', $member_prefs->avatar_url),
			'allowed_types' => 'img',
			'max_size' => $member_prefs->prv_msg_attach_maxsize
		);

		$existing = ee('Model')->get('UploadDestination')
			->fields('name')
			->filter('name', 'IN', array_keys($member_directories))
			->filter('site_id', $site_id)
			->all()
			->pluck('name');

		foreach ($existing as $name)
		{
			unset($member_directories[$name]);
		}

		foreach ($member_directories as $name => $data)
		{
			$dir = ee('Model')->make('UploadDestination', $data);
			$dir->site_id = $site_id;
			$dir->name = $name;
			$dir->removeNoAccess();
			$dir->module_id = 1; // this is a terribly named column - should be called `hidden`
			$dir->save();
		}

		return TRUE;
	}

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */
	function uninstall()
	{
		$mod_id = ee()->db->select('module_id')
			->get_where('modules', array(
				'module_name' => 'Filepicker'
			))->row('module_id');

		ee()->db->where('module_id', $mod_id)
			->delete('module_member_groups');

		ee()->db->where('module_name', 'Filepicker')
			->delete('modules');

		return TRUE;
	}

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */
	function update($current = '')
	{
		return TRUE;
	}

}

// EOF
