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
 * RSS Module update class
 */
class Rss_upd {

	var $version = '2.0.0';

	/**
	 * Module Installer
	 *
	 * @return	bool
	 */
	public function install()
	{
		$sql[] = "INSERT INTO exp_modules (module_name, module_version, has_cp_backend) VALUES ('Rss', '$this->version', 'n')";

		foreach ($sql as $query)
		{
			ee()->db->query($query);
		}

		return TRUE;
	}

	/**
	 * Module Uninstaller
	 *
	 * @return	bool
	 */
	public function uninstall()
	{
		$query = ee()->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Rss'");

		$sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row('module_id') ."'";
		$sql[] = "DELETE FROM exp_modules WHERE module_name = 'Rss'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Rss'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Rss_mcp'";

		foreach ($sql as $query)
		{
			ee()->db->query($query);
		}

		return TRUE;
	}

	/**
	 * Module Updater
	 *
	 * @return	bool
	 */

	public function update($current='')
	{
		return TRUE;
	}

}
// END CLASS

// EOF
