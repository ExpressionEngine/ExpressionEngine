<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Email Module update class
 */
class Email_upd {

	var $version = '2.1.0';

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */
	function install()
	{
		$sql[] = "INSERT INTO exp_modules (module_name, module_version, has_cp_backend) VALUES ('Email', '$this->version', 'n')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Email', 'send_email')";
		$sql[] = "CREATE TABLE IF NOT EXISTS exp_email_tracker (
			email_id int(10) unsigned NOT NULL auto_increment,
			email_date int(10) unsigned default '0' NOT NULL,
			sender_ip varchar(45) NOT NULL,
			sender_email varchar(75) NOT NULL ,
			sender_username varchar(50) NOT NULL ,
			number_recipients int(4) unsigned default '1' NOT NULL,
			PRIMARY KEY `email_id` (`email_id`)
		) DEFAULT CHARACTER SET ".ee()->db->escape_str(ee()->db->char_set)." COLLATE ".ee()->db->escape_str(ee()->db->dbcollat);

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
		$query = ee()->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Email'");

		$sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row('module_id') ."'";
		$sql[] = "DELETE FROM exp_modules WHERE module_name = 'Email'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Email'";
		$sql[] = "DROP TABLE IF EXISTS exp_email_tracker";

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

	function update($current='')
	{
		return TRUE;
	}

}
// END CLASS

// EOF
