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
		$Q[] = "CREATE TABLE exp_throttle (
		  ip_address varchar(16) default '0' NOT NULL,
		  last_activity int(10) unsigned DEFAULT '0' NOT NULL,
		  hits int(10) unsigned NOT NULL,
		  KEY `ip_address` (`ip_address`),
		  KEY `last_activity` (`last_activity`)
		)";

		$Q[] = "CREATE TABLE exp_message_attachments (
		  attachment_id int(10) unsigned NOT NULL auto_increment,
		  sender_id int(10) unsigned NOT NULL default '0',
		  message_id int(10) unsigned NOT NULL default '0',
		  attachment_name varchar(50) NOT NULL default '',
		  attachment_hash varchar(40) NOT NULL default '',
		  attachment_extension varchar(20) NOT NULL default '',
		  attachment_location varchar(125) NOT NULL default '',
		  attachment_date int(10) unsigned NOT NULL default '0',
		  attachment_size int(10) unsigned NOT NULL default '0',
		  is_temp char(1) NOT NULL default 'y',
		  PRIMARY KEY `attachment_id` (`attachment_id`)
		)";

		$Q[] = "CREATE TABLE exp_message_copies (
		  copy_id int(10) unsigned NOT NULL auto_increment,
		  message_id int(10) unsigned NOT NULL default '0',
		  sender_id int(10) unsigned NOT NULL default '0',
		  recipient_id int(10) unsigned NOT NULL default '0',
		  message_received char(1) NOT NULL default 'n',
		  message_read char(1) NOT NULL default 'n',
		  message_time_read int(10) unsigned NOT NULL default '0',
		  attachment_downloaded char(1) NOT NULL default 'n',
		  message_folder int(10) unsigned NOT NULL default '1',
		  message_authcode varchar(10) NOT NULL default '',
		  message_deleted char(1) NOT NULL default 'n',
		  message_status varchar(10) NOT NULL default '',
		  PRIMARY KEY `copy_id` (`copy_id`),
		  KEY `message_id` (`message_id`),
		  KEY `recipient_id` (`recipient_id`),
		  KEY `sender_id` (`sender_id`)
		)";

		$Q[] = "CREATE TABLE exp_message_data (
		  message_id int(10) unsigned NOT NULL auto_increment,
		  sender_id int(10) unsigned NOT NULL default '0',
		  message_date int(10) unsigned NOT NULL default '0',
		  message_subject varchar(255) NOT NULL default '',
		  message_body text NOT NULL,
		  message_tracking char(1) NOT NULL default 'y',
		  message_attachments char(1) NOT NULL default 'n',
		  message_recipients varchar(200) NOT NULL default '',
		  message_cc varchar(200) NOT NULL default '',
		  message_hide_cc char(1) NOT NULL default 'n',
		  message_sent_copy char(1) NOT NULL default 'n',
		  total_recipients int(5) unsigned NOT NULL default '0',
		  message_status varchar(25) NOT NULL default '',
		  PRIMARY KEY `message_id` (`message_id`),
		  KEY `sender_id` (`sender_id`)
		)";

		$Q[] = "CREATE TABLE exp_message_folders (
		  member_id int(10) unsigned NOT NULL default '0',
		  folder1_name varchar(50) NOT NULL default 'InBox',
		  folder2_name varchar(50) NOT NULL default 'Sent',
		  folder3_name varchar(50) NOT NULL default '',
		  folder4_name varchar(50) NOT NULL default '',
		  folder5_name varchar(50) NOT NULL default '',
		  folder6_name varchar(50) NOT NULL default '',
		  folder7_name varchar(50) NOT NULL default '',
		  folder8_name varchar(50) NOT NULL default '',
		  folder9_name varchar(50) NOT NULL default '',
		  folder10_name varchar(50) NOT NULL default '',
		  KEY `member_id` (`member_id`)
		)";

		$Q[] = "CREATE TABLE exp_message_listed (
		  listed_id int(10) unsigned NOT NULL auto_increment,
		  member_id int(10) unsigned NOT NULL default '0',
		  listed_member int(10) unsigned NOT NULL default '0',
		  listed_description varchar(100) NOT NULL default '',
		  listed_type varchar(10) NOT NULL default 'blocked',
		  PRIMARY KEY `listed_id` (`listed_id`)
		)";



		$Q[] = "UPDATE exp_members SET theme = 'default' WHERE theme != 'default'";
		$Q[] = "INSERT INTO exp_actions (class, method) VALUES ('Member', 'update_un_pw')";
		$Q[] = "ALTER TABLE exp_upload_prefs CHANGE COLUMN url url varchar(100) NOT NULL";
		$Q[] = "ALTER TABLE exp_sessions CHANGE COLUMN last_visit last_activity int(10) unsigned DEFAULT '0' NOT NULL";
		$Q[] = "ALTER TABLE exp_members CHANGE COLUMN theme cp_theme varchar(32) NOT NULL";
		$Q[] = "ALTER TABLE exp_members ADD COLUMN quick_tabs text NOT NULL";
		$Q[] = "ALTER TABLE exp_members ADD COLUMN signature text NOT NULL";
		$Q[] = "ALTER TABLE exp_members ADD COLUMN private_messages INT(4) UNSIGNED DEFAULT '0' NOT NULL";
		$Q[] = "ALTER TABLE exp_members ADD COLUMN last_activity int(10) unsigned default '0' NOT NULL";
		$Q[] = "ALTER TABLE exp_stats   ADD COLUMN last_cache_clear int(10) unsigned default '0' NOT NULL";
		$Q[] = "ALTER TABLE exp_members ADD COLUMN forum_theme varchar(32) NOT NULL";
		$Q[] = "ALTER TABLE exp_members ADD COLUMN profile_theme varchar(32) NOT NULL";
		$Q[] = "ALTER TABLE exp_members ADD COLUMN total_forum_topics mediumint(8) unsigned NOT NULL default '0'";
		$Q[] = "ALTER TABLE exp_members ADD COLUMN total_forum_posts mediumint(8) unsigned NOT NULL default '0'";
		$Q[] = "ALTER TABLE exp_members ADD COLUMN last_forum_post_date int(10) unsigned default '0' NOT NULL";
		$Q[] = "ALTER TABLE exp_members ADD COLUMN avatar_filename varchar(120) NOT NULL";
		$Q[] = "ALTER TABLE exp_members ADD COLUMN avatar_width int(4) unsigned NOT NULL";
		$Q[] = "ALTER TABLE exp_members ADD COLUMN avatar_height int(4) unsigned NOT NULL";
		$Q[] = "ALTER TABLE exp_members ADD COLUMN photo_filename varchar(120) NOT NULL";
		$Q[] = "ALTER TABLE exp_members ADD COLUMN photo_width int(4) unsigned NOT NULL";
		$Q[] = "ALTER TABLE exp_members ADD COLUMN photo_height int(4) unsigned NOT NULL";
		$Q[] = "ALTER TABLE exp_members ADD COLUMN sig_img_filename varchar(120) NOT NULL";
		$Q[] = "ALTER TABLE exp_members ADD COLUMN sig_img_width int(4) unsigned NOT NULL";
		$Q[] = "ALTER TABLE exp_members ADD COLUMN sig_img_height int(4) unsigned NOT NULL";
		$Q[] = "ALTER TABLE exp_members ADD COLUMN notify_of_pm char(1) NOT NULL default 'y'";
		$Q[] = "ALTER TABLE exp_members ADD COLUMN display_avatars char(1) NOT NULL default 'y'";
		$Q[] = "ALTER TABLE exp_members ADD COLUMN display_signatures char(1) NOT NULL default 'y'";
		$Q[] = "ALTER TABLE exp_members ADD COLUMN smart_notifications char(1) NOT NULL default 'y'";
		$Q[] = "ALTER TABLE exp_stats ADD COLUMN total_forum_topics mediumint(8) default '0' NOT NULL";
		$Q[] = "ALTER TABLE exp_stats ADD COLUMN total_forum_posts mediumint(8) default '0' NOT NULL";
		$Q[] = "ALTER TABLE exp_stats ADD COLUMN last_forum_post_date int(10) unsigned default '0' NOT NULL";
		$Q[] = "ALTER TABLE exp_stats ADD COLUMN recent_member_id int(10) default '0' NOT NULL";
		$Q[] = "ALTER TABLE exp_stats ADD COLUMN recent_member varchar(50) NOT NULL";
		$Q[] = "ALTER TABLE exp_member_groups ADD COLUMN can_send_private_messages char(1) NOT NULL default 'n'";
		$Q[] = "ALTER TABLE exp_member_groups ADD COLUMN can_attach_in_private_messages char(1) NOT NULL default 'n'";
		$Q[] = "ALTER TABLE exp_weblog_fields ADD COLUMN field_is_hidden char(1) NOT NULL default 'n'";
		$Q[] = "ALTER TABLE exp_captcha ADD COLUMN captcha_id bigint(13) unsigned NOT NULL auto_increment primary key";
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN rss_url varchar(80) NOT NULL";


		$query = ee()->db->query("SELECT screen_name, member_id FROM exp_members ORDER BY member_id DESC LIMIT 1");
		$Q[] = "UPDATE exp_stats SET recent_member = '".$DB->escape_str($query->row('screen_name'))."', recent_member_id = '".$query->row('member_id')."' WHERE weblog_id ='0'";
		$Q[] = "UPDATE exp_stats SET last_cache_clear = '".time()."' WHERE weblog_id ='0'";

		$Q[] = "UPDATE exp_member_groups set can_send_private_messages = 'y' WHERE group_id = '1'";
		$Q[] = "UPDATE exp_member_groups set can_attach_in_private_messages = 'y' WHERE group_id = '1'";

		$Q[] = "ALTER TABLE exp_members ADD INDEX(pmember_id)";

		$Q[] = "INSERT INTO exp_specialty_templates(template_name, data_title, template_data) values (private_message_notification', 'Someone has sent you a Private Message', '".addslashes(private_message_notification())."')";

		// Run the queries

		foreach ($Q as $sql)
		{
			ee()->db->query($sql);
		}


		// Update message template

		$query = ee()->db->query("SELECT template_data FROM exp_specialty_templates WHERE template_name = 'message_template'");
		$template = str_replace("'", "\'", $query->row('template_data'));

		if ( ! preg_match("/<h1>{heading}<\/h1>/", $template))
		{
			$template = str_replace('{heading}', '<h1>{heading}</h1>', $template);
			$template = str_replace('{link}', "<p>{link}</p>", $template);
			ee()->db->query("UPDATE exp_specialty_templates SET template_data = '".$DB->escape_str($template)."' WHERE template_name = 'message_template'");
		}


		// Append config items
		if (substr($this->config['site_url'], -1) != '/')
		{
			$this->config['site_url'] .= '/';
		}

		$data['max_caches']					= '150';
		$data['profile_trigger']			= 'member';
		$data['theme_folder_url'] 			= $this->config['site_url'].'themes/';
		$data['enable_avatars'] 			= 'y';
		$data['allow_avatar_uploads']		= 'n';
		$data['avatar_url'] 				= str_replace('cp_images/', 'avatars/', $this->config['cp_image_path']);
		$data['avatar_path'] 				= (@realpath('../images/avatars/') !== FALSE) ? realpath('../images/avatars/').'/' : './images/avatars/';
		$data['avatar_max_width'] 			= '100';
		$data['avatar_max_height'] 			= '100';
		$data['avatar_max_kb'] 				= '50';
		$data['enable_photos'] 				= 'y';
		$data['photo_url'] 					= str_replace('cp_images/', 'member_photos/', $this->config['cp_image_path']);
		$data['photo_path'] 				= (@realpath('../images/member_photos/') !== FALSE) ? realpath('../images/member_photos/').'/' : './images/member_photos/';
		$data['photo_max_width'] 			= '100';
		$data['photo_max_height'] 			= '100';
		$data['photo_max_kb'] 				= '50';
		$data['allow_signatures'] 			= 'y';
		$data['sig_maxlength'] 				= '500';
		$data['sig_allow_img_hotlink'] 		= 'n';
		$data['sig_allow_img_upload'] 		= 'n';
		$data['sig_img_url'] 				= str_replace('cp_images/', 'signature_attachments/', $this->config['cp_image_path']);
		$data['sig_img_path'] 				= (@realpath('../images/signature_attachments/') !== FALSE) ? realpath('../images/signature_attachments/').'/' : './images/signature_attachments/';
		$data['sig_img_max_width'] 			= '480';
		$data['sig_img_max_height'] 		= '80';
		$data['sig_img_max_kb'] 			= '30';
		$data['prv_msg_storage_limit'] 		= "60";
		$data['prv_msg_send_limit'] 		= "20";
		$data['prv_msg_upload_path'] 		= (@realpath('../images/pm_attachments/') !== FALSE) ? realpath('../images/pm_attachments/').'/' : './images/pm_attachments/';
		$data['prv_msg_attach_maxsize'] 	= "250";
		$data['prv_msg_max_attachments'] 	= "3";
		$data['prv_msg_attach_total'] 		= "100";
		$data['prv_msg_html_format'] 		= "safe";
		$data['prv_msg_auto_links'] 		= "y";
		$data['prv_msg_max_chars'] 			= "6000";
		$data['email_module_captchas'] 		= "n";
		$data['enable_throttling'] 			= "y";

		ee()->config->_append_config_1x($data);

		unset($data);

		$data['cp_theme'] = 'default';  // Change the name of the default CSS file

		ee()->config->_update_config_1x($data);

		return TRUE;
	}


}
// END CLASS


function private_message_notification()
{
return <<<EOF

{recipient_name},

{sender_name} has just sent you a Private Message titled '{message_subject}'.

You can see the Private Message by logging in and viewing your InBox at:
{site_url}

To stop receiving notifications of Private Messages, turn the option off in your Email Settings.
EOF;
}
/* END */




/* End of file ud_130.php */
/* Location: ./system/expressionengine/installer/updates/ud_130.php */