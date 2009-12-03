<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Js_tool_upd {

	var $version		= '1.0';

	function Js_tool_upd()
	{
		// Make a local reference to the ExpressionEngine super object
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
		$sql[] = "INSERT INTO exp_modules (module_name, module_version, has_cp_backend) VALUES ('Js_tool', '$this->version', 'y')";

		$sql[] = "CREATE TABLE `exp_javascript_checksums` (
				`checksum_id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`filepath` VARCHAR( 100 ) NOT NULL,
				`checksum` VARCHAR( 32 ) NOT NULL
				)";

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
	function uninstall()
	{
		$sql[] = "DELETE FROM exp_modules WHERE module_name = 'Js_tool'";
		$sql[] = "DELETE FROM exp_accessories WHERE class = 'Js_tool_acc'";
		$sql[] = "DROP TABLE `exp_javascript_checksums`";

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
		return FALSE;
	}
}
// END CLASS

/* End of file upd.js_tool.php */
/* Location: ./system/expressionengine/third_party/modules/js_tool/upd.js_tool.php */