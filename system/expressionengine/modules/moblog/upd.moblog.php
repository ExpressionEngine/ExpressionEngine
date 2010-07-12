<?php

/*
=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 http://expressionengine.com/
-----------------------------------------------------
 Copyright (c) 2003 - 2010, EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 http://expressionengine.com/user_guide/license.html
=====================================================
 File: mcp.moblog.php
-----------------------------------------------------
 Purpose: Moblog class - CP
=====================================================
*/

if ( ! defined('EXT'))
{
	exit('Invalid file request');
}


class Moblog_upd {

	var $version 			= '3.0';

	function Moblog_upd()
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
		$sql[] = "INSERT INTO exp_modules (module_name, module_version, has_cp_backend) VALUES ('Moblog', '{$this->version}', 'y')";
		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_moblogs` (
		`moblog_id` int(4) unsigned NOT NULL auto_increment,
		`moblog_full_name` varchar(80) NOT NULL default '',
		`moblog_short_name` varchar(20) NOT NULL default '',
		`moblog_enabled` char(1) NOT NULL default 'y',
		`moblog_file_archive` char(1) NOT NULL default 'n',
		`moblog_time_interval` int(4) unsigned NOT NULL default '0',
		`moblog_type` varchar(10) NOT NULL default '',
		`moblog_gallery_id` int(6) unsigned NOT NULL default '0',
		`moblog_gallery_category` int(10) unsigned NOT NULL default '0',
		`moblog_gallery_status` varchar(50) NOT NULL default '',
		`moblog_gallery_comments` varchar(10) NOT NULL default 'y',
		`moblog_gallery_author` int(10) unsigned NOT NULL default '1',
		`moblog_channel_id` int(4) unsigned NOT NULL default '1',
		`moblog_categories` varchar(25) NOT NULL default '',
		`moblog_field_id` varchar(5) NOT NULL default '',
		`moblog_status` varchar(50) NOT NULL default '',
		`moblog_author_id` int(10) unsigned NOT NULL default '1',
		`moblog_sticky_entry` char(1) NOT NULL default 'n',
		`moblog_allow_overrides` char(1) NOT NULL default 'y',
		`moblog_auth_required` char(1) NOT NULL default 'n',
		`moblog_auth_delete` char(1) NOT NULL default 'n',
		`moblog_upload_directory` int(4) unsigned NOT NULL default '1',
		`moblog_template` text NOT NULL,
		`moblog_image_width` int(5) unsigned NOT NULL default '0',
		`moblog_image_height` int(5) unsigned NOT NULL default '0',
		`moblog_resize_image` char(1) NOT NULL default '',
		`moblog_resize_width` int(5) unsigned NOT NULL default '0',
		`moblog_resize_height` int(5) unsigned NOT NULL default '0',
		`moblog_create_thumbnail` char(1) NOT NULL default 'n',
		`moblog_thumbnail_width` int(5) NOT NULL default '0',
		`moblog_thumbnail_height` int(5) NOT NULL default '0',
		`moblog_email_type` varchar(10) NOT NULL default '',
		`moblog_email_address` varchar(125) NOT NULL default '',
		`moblog_email_server` varchar(100) NOT NULL default '',
		`moblog_email_login` varchar(125) NOT NULL default '',
		`moblog_email_password` varchar(125) NOT NULL default '',
		`moblog_subject_prefix` varchar(50) NOT NULL default '',
		`moblog_valid_from` text NOT NULL,
		`moblog_ignore_text` text NOT NULL,
		`moblog_ping_servers` varchar(50) NOT NULL default '',
		PRIMARY KEY `moblog_id` (`moblog_id`))";

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
		$query = $this->EE->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Moblog'");
		$sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row('module_id') ."'";
		$sql[] = "DELETE FROM exp_modules WHERE module_name = 'Moblog'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Moblog'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Moblog_mcp'";
		$sql[] = "DROP TABLE IF EXISTS exp_moblogs";

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

	function update($current = '')
	{
		if ($current == $this->version)
		{
			return FALSE;
		}

		/** ----------------------------------
		/**  Update Fields
		/** ----------------------------------*/

		if ($this->EE->db->table_exists('exp_moblogs') && $current != $this->version)
		{
			$existing_fields = array();

			$new_fields = array('moblog_type'				=> "`moblog_type` varchar(10) NOT NULL default '' AFTER `moblog_time_interval`",
								'moblog_gallery_id'			=> "`moblog_gallery_id` int(6) unsigned NOT NULL default '0' AFTER `moblog_type`",
								'moblog_gallery_category'	=> "`moblog_gallery_category` int(10) unsigned NOT NULL default '0' AFTER `moblog_gallery_id`",
								'moblog_gallery_status'		=> "`moblog_gallery_status` varchar(50) NOT NULL default '' AFTER `moblog_gallery_category`",
								'moblog_gallery_comments'	=> "`moblog_gallery_comments` varchar(10) NOT NULL default 'y' AFTER `moblog_gallery_status`",
								'moblog_gallery_author'		=> "`moblog_gallery_author` int(10) unsigned NOT NULL default '1' AFTER `moblog_gallery_comments`",
								'moblog_ping_servers'		=> "`moblog_ping_servers` varchar(50) NOT NULL default ''",
								'moblog_allow_overrides'	=> "`moblog_allow_overrides` char(1) NOT NULL default 'y'",
								'moblog_sticky_entry'		=> "`moblog_sticky_entry` char(1) NOT NULL default 'n'");

			$query = $this->EE->db->query("SHOW COLUMNS FROM exp_moblogs");

			foreach($query->result_array() as $row)
			{
				$existing_fields[] = $row['Field'];
			}

			foreach($new_fields as $field => $alter)
			{
				if ( ! in_array($field, $existing_fields))
				{
					$this->EE->db->query("ALTER table exp_moblogs ADD COLUMN {$alter}");
				}
			}
		}

		if ($current < 3.0)
		{
			// @confrim- should be able to drop is_user_blog as well?
			$this->EE->db->query("ALTER TABLE `exp_moblogs` DROP COLUMN `is_user_blog`");
			$this->EE->db->query("ALTER TABLE `exp_moblogs` DROP COLUMN `user_blog_id`");
			$this->EE->db->query("ALTER TABLE `exp_moblogs` CHANGE `moblog_weblog_id` `moblog_channel_id` INT(4) UNSIGNED NOT NULL DEFAULT 1");
		}

		return TRUE;
	}
	// END




}
// END CLASS

/* End of file upd.moblog.php */
/* Location: ./system/expressionengine/modules/moblog/upd.moblog.php */