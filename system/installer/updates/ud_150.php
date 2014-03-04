<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Updater {

	function Updater()
	{
		$this->EE =& get_instance();

		// Grab the config file
		if ( ! @include(ee()->config->config_path))
		{
			show_error('Your config'.EXT.' file is unreadable. Please make sure the file exists and that the file permissions to 666 on the following file: expressionengine/config/config.php');
		}

		if (isset($conf))
		{
			$config = $conf;
		}

		// Does the config array exist?
		if ( ! isset($config) OR ! is_array($config))
		{
			show_error('Your config'.EXT.' file does not appear to contain any data.');
		}

		$this->config =& $config;
	}

	function do_update()
	{
		$Q[] = 	"CREATE TABLE `exp_member_search`
				 (
					 `search_id` varchar(32) NOT NULL,
					 `search_date` int(10) unsigned NOT NULL,
					 `keywords` varchar(200) NOT NULL,
					 `fields` varchar(200) NOT NULL,
					 `member_id` int(10) unsigned NOT NULL,
					 `ip_address` varchar(16) NOT NULL,
					 `total_results` int(8) unsigned NOT NULL,
					 `query` text NOT NULL,
					 PRIMARY KEY `search_id` (`search_id`),
					 KEY `member_id` (`member_id`)
				 )";

		$Q[] =	"CREATE TABLE `exp_member_bulletin_board`
				(
  					`bulletin_id` int(10) unsigned NOT NULL auto_increment,
					`sender_id` int(10) unsigned NOT NULL,
					`bulletin_group` int(8) unsigned NOT NULL,
					`bulletin_date` int(10) unsigned NOT NULL,
					`bulletin_expires` int(10) unsigned NOT NULL DEFAULT 0,
					`bulletin_message` text NOT NULL,
					PRIMARY KEY `bulletin_id` (`bulletin_id`),
					KEY `sender_id` (`sender_id`)
				)";

		// Member Search
		$Q[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'member_search')";
		$Q[] = "ALTER TABLE exp_member_groups ADD `group_description` TEXT NOT NULL AFTER `group_title`";

		// Bulletin Board Related
		$Q[] = "ALTER TABLE exp_member_homepage ADD `bulletin_board` char(1) NOT NULL default 'n'";
		$Q[] = "ALTER TABLE exp_member_homepage ADD `bulletin_board_order` int(3) unsigned NOT NULL default '0'";

		$Q[] = "ALTER TABLE exp_member_groups ADD `can_send_bulletins` char(1) NOT NULL default 'n'";
		$Q[] = "UPDATE exp_member_groups SET `can_send_bulletins` = 'y' WHERE group_id = 1";

		$Q[] = "ALTER TABLE exp_members ADD `last_view_bulletins` int(10) NOT NULL default 0 AFTER `accept_messages`";
		$Q[] = "ALTER TABLE exp_members ADD `last_bulletin_date` int(10) NOT NULL default 0 AFTER `last_view_bulletins`";

		// New file specific stuff for upload directories!
		$Q[] = "ALTER TABLE exp_upload_prefs ADD `file_properties` varchar(120) NOT NULL";
		$Q[] = "ALTER TABLE exp_upload_prefs ADD `file_pre_format` varchar(120) NOT NULL";
		$Q[] = "ALTER TABLE exp_upload_prefs ADD `file_post_format` varchar(120) NOT NULL";
		$Q[] = "UPDATE exp_upload_prefs SET file_pre_format = pre_format";
		$Q[] = "UPDATE exp_upload_prefs SET file_post_format = post_format";

		// Channel Prefs
		$Q[] = "ALTER TABLE exp_weblogs ADD `default_entry_title` varchar(100) NOT NULL";
		$Q[] = "ALTER TABLE exp_weblogs ADD `url_title_prefix` varchar(80) NOT NULL";

		// Bump up a few fields from TEXT to MEDIUMTEXT
		$Q[] = "ALTER TABLE `exp_email_cache` CHANGE `message` `message` MEDIUMTEXT NOT NULL";
		$Q[] = "ALTER TABLE `exp_email_console_cache` CHANGE `message` `message` MEDIUMTEXT NOT NULL";
		$Q[] = "ALTER TABLE `exp_revision_tracker` CHANGE `item_data` `item_data` MEDIUMTEXT NOT NULL";
		$Q[] = "ALTER TABLE `exp_templates` CHANGE `template_data` `template_data` MEDIUMTEXT NOT NULL";
		$Q[] = "ALTER TABLE `exp_templates` ADD `enable_http_auth` CHAR(1) DEFAULT 'n' NOT NULL AFTER `no_auth_bounce`";

		// Category Group Groups
		$Q[] = "ALTER TABLE `exp_weblogs` CHANGE `cat_group` `cat_group` VARCHAR(255) NOT NULL";

		// Channel Fields Changes
		$Q[] = "ALTER TABLE `exp_weblog_fields` ADD `field_instructions` TEXT NOT NULL AFTER `field_label`";
		$Q[] = "ALTER TABLE `exp_weblog_fields` ADD `field_text_direction` CHAR(3) DEFAULT 'ltr' NOT NULL AFTER `field_required`";

		// Reverse Related Entry Field
		$Q[] = "ALTER TABLE `exp_relationships` ADD `reverse_rel_data` MEDIUMTEXT NOT NULL";

		// Empty Cached Related Entry Data
		$Q[] = "UPDATE exp_relationships SET rel_data = ''";

		// Cat Names in URL require look up, need index
		$Q[] = "ALTER TABLE exp_categories ADD INDEX (`cat_name`)";

		// New email templates - PM InBox full notification and Forum topic moderation notification
		$Q[] = "INSERT INTO exp_specialty_templates(template_name, data_title, template_data) VALUES ('pm_inbox_full', '".addslashes(trim(pm_inbox_full_title()))."', '".addslashes(pm_inbox_full())."')";

		// New Group preferences
		$Q[] = "ALTER TABLE `exp_member_groups` ADD `can_delete_self` CHAR(1) DEFAULT 'n' NOT NULL AFTER `can_view_profiles`";
		$Q[] = "ALTER TABLE `exp_member_groups` ADD `mbr_delete_notify_emails` VARCHAR(255) NOT NULL AFTER `can_delete_self`";
		$Q[] = "ALTER TABLE `exp_member_groups` ADD `prv_msg_send_limit` SMALLINT UNSIGNED DEFAULT '20' NOT NULL AFTER `can_send_private_messages`";
		$Q[] = "ALTER TABLE `exp_member_groups` ADD `prv_msg_storage_limit` SMALLINT UNSIGNED DEFAULT '60' NOT NULL AFTER `can_send_private_messages`";

		// Use existing settings
		$Q[] = "UPDATE exp_member_groups SET prv_msg_send_limit = '".$DB->escape_str($this->config['prv_msg_send_limit'])."'";
		$Q[] = "UPDATE exp_member_groups SET prv_msg_storage_limit = '".$DB->escape_str($this->config['prv_msg_storage_limit'])."'";

		// Member self-delete
		$Q[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'member_delete')";

		// Run the queries
		foreach ($Q as $sql)
		{
			ee()->db->query($sql);
		}

		// Update config data
		$data['xss_clean_uploads'] = "y";
		$data['template_debugging'] = "n";

		ee()->config->_append_config_1x($data);

		unset($config);
		unset($conf);

		include(ee()->config->config_path);

		if (isset($conf))
		{
			$config = $conf;
		}

		// These config items are no longer needed
		unset($config['prv_msg_send_limit']);
		unset($config['prv_msg_storage_limit']);

		ee()->config->_update_config_1x(array(), $config);

		return TRUE;
	}

}
// END CLASS

/* -------------------------------------
/*  Notification of Full PM InBox
/* -------------------------------------*/

function pm_inbox_full_title()
{
return <<<EOF
Your private message mailbox is full
EOF;
}

function pm_inbox_full()
{
return <<<EOF
{recipient_name},

{sender_name} has just attempted to send you a Private Message,
but your InBox is full, exceeding the maximum of {pm_storage_limit}.

Please log in and remove unwanted messages from your InBox at:
{site_url}
EOF;
}
/* END */



/* End of file ud_150.php */
/* Location: ./system/expressionengine/installer/updates/ud_150.php */