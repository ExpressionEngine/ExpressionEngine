<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
		$sql[] = "INSERT INTO exp_modules (module_name, module_version, has_cp_backend, has_publish_fields) VALUES ('FilePicker', '$this->version', 'y', 'n')";

		foreach ($sql as $query)
		{
			ee()->db->query($query);
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
		$query = ee()->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'FilePicker'");
		$sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row('module_id') ."'";
		$sql[] = "DELETE FROM exp_modules WHERE module_name = 'Filepicker'";

		foreach ($sql as $query)
		{
			ee()->db->query($query);
		}

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

?>
