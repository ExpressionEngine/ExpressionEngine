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
		$Q[] = "CREATE TABLE exp_extensions (
			extension_id int(10) unsigned NOT NULL auto_increment,
			class varchar(50) NOT NULL default '',
			method varchar(50) NOT NULL default '',
			hook varchar(50) NOT NULL default '',
			settings text NOT NULL,
			priority int(2) NOT NULL default '10',
			version varchar(10) NOT NULL default '',
			enabled char(1) NOT NULL default 'y',
			PRIMARY KEY `extension_id` (`extension_id`)
		)";

		$Q[] = "CREATE TABLE exp_search_log (
			id int(10) NOT NULL auto_increment,
			member_id int(10) unsigned NOT NULL,
			screen_name varchar(50) NOT NULL,
			ip_address varchar(16) default '0' NOT NULL,
			search_date int(10) NOT NULL,
			search_type varchar(32) NOT NULL,
			search_terms varchar(200) NOT NULL,
			PRIMARY KEY `id` (`id`)
		)";

		$Q[] = "CREATE TABLE exp_entry_versioning (
		 version_id int(10) unsigned NOT NULL auto_increment,
		 entry_id int(10) unsigned NOT NULL,
		 weblog_id int(4) unsigned NOT NULL,
		 author_id int(10) unsigned NOT NULL,
		 version_date int(10) NOT NULL,
		 version_data mediumtext NOT NULL,
		 PRIMARY KEY `version_id` (`version_id`),
		 KEY `entry_id` (`entry_id`)
		)";


		$Q[] = "CREATE TABLE exp_relationships (
		 rel_id int(6) unsigned NOT NULL auto_increment,
		 rel_parent_id int(10) NOT NULL default '0',
		 rel_child_id int(10) NOT NULL default '0',
		 rel_type varchar(12) NOT NULL,
		 rel_data mediumtext NOT NULL,
		 PRIMARY KEY `rel_id` (`rel_id`),
		 KEY `rel_parent_id` (`rel_parent_id`),
		 KEY `rel_child_id` (`rel_child_id`)
		)";


		/** -------------------------------
		/**  Is the Forum module installed?
		/** -------------------------------*/

        $query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_modules WHERE module_name = 'Forum'");

        if ($query->row('count') > 0)
        {
        	$Q[] = "ALTER TABLE exp_forum_topics ADD INDEX(last_post_author_id);";

        	$query = ee()->db->query("SELECT forum_permissions, forum_id FROM exp_forums");

        	foreach($query->result_array() as $row)
        	{
        		$perms = unserialize(stripslashes($row['forum_permissions']));

        		$perms['can_post_reply'] = $perms['can_post_topics'];

				ee()->db->query("UPDATE exp_forums SET forum_permissions = '".addslashes(serialize($perms))."'
							WHERE forum_id = '".$DB->escape_str($row['forum_id'])."'");

        	}
        }

		/** -------------------------------
		/**  Is the Gallery module installed?
		/** -------------------------------*/
        $query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_modules WHERE module_name = 'Gallery'");

        if ($query->row('count') > 0)
        {
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_one char(1) NOT NULL default 'n'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_one_label varchar(80) NOT NULL";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_one_type char(1) NOT NULL default 'i'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_one_list text NOT NULL";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_one_rows tinyint(2) default '8'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_one_formatting char(10) NOT NULL default 'xhtml'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_one_auto_link char(1) NOT NULL default 'y'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_two char(1) NOT NULL default 'n'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_two_label varchar(80) NOT NULL";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_two_type char(1) NOT NULL default 'i'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_two_list text NOT NULL";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_two_rows tinyint(2) default '8'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_two_formatting char(10) NOT NULL default 'xhtml'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_two_auto_link char(1) NOT NULL default 'y'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_three char(1) NOT NULL default 'n'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_three_label varchar(80) NOT NULL";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_three_type char(1) NOT NULL default 'i'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_three_list text NOT NULL";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_three_rows tinyint(2) default '8'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_three_formatting char(10) NOT NULL default 'xhtml'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_three_auto_link char(1) NOT NULL default 'y'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_four char(1) NOT NULL default 'n'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_four_label varchar(80) NOT NULL";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_four_type char(1) NOT NULL default 'i'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_four_list text NOT NULL";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_four_rows tinyint(2) default '8'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_four_formatting char(10) NOT NULL default 'xhtml'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_four_auto_link char(1) NOT NULL default 'y'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_five char(1) NOT NULL default 'n'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_five_label varchar(80) NOT NULL";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_five_type char(1) NOT NULL default 'i'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_five_list text NOT NULL";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_five_rows tinyint(2) default '8'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_five_formatting char(10) NOT NULL default 'xhtml'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_five_auto_link char(1) NOT NULL default 'y'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_six char(1) NOT NULL default 'n'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_six_label varchar(80) NOT NULL";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_six_type char(1) NOT NULL default 'i'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_six_list text NOT NULL";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_six_rows tinyint(2) default '8'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_six_formatting char(10) NOT NULL default 'xhtml'";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_six_auto_link char(1) NOT NULL default 'y'";

			$Q[] = "ALTER TABLE exp_gallery_entries ADD COLUMN custom_field_one text NOT NULL";
			$Q[] = "ALTER TABLE exp_gallery_entries ADD COLUMN custom_field_two text NOT NULL";
			$Q[] = "ALTER TABLE exp_gallery_entries ADD COLUMN custom_field_three text NOT NULL";
			$Q[] = "ALTER TABLE exp_gallery_entries ADD COLUMN custom_field_four text NOT NULL";
			$Q[] = "ALTER TABLE exp_gallery_entries ADD COLUMN custom_field_five text NOT NULL";
			$Q[] = "ALTER TABLE exp_gallery_entries ADD COLUMN custom_field_six text NOT NULL";
        }

		$Q[] = "ALTER TABLE exp_weblog_titles CHANGE COLUMN edit_date edit_date varchar(19) NOT NULL";
		$Q[] = "ALTER TABLE exp_weblog_titles ADD COLUMN view_count_one int(10) unsigned NOT NULL default '0'";
		$Q[] = "ALTER TABLE exp_weblog_titles ADD COLUMN view_count_two int(10) unsigned NOT NULL default '0'";
		$Q[] = "ALTER TABLE exp_weblog_titles ADD COLUMN view_count_three int(10) unsigned NOT NULL default '0'";
		$Q[] = "ALTER TABLE exp_weblog_titles ADD COLUMN view_count_four int(10) unsigned NOT NULL default '0'";
		$Q[] = "ALTER TABLE exp_weblog_titles ADD COLUMN versioning_enabled char(1) NOT NULL default 'n'";
		$Q[] = "ALTER TABLE exp_weblog_titles ADD COLUMN dst_enabled varchar(1) NOT NULL default 'n'";
		$Q[] = "ALTER TABLE exp_search ADD COLUMN `custom_fields` TEXT NOT NULL AFTER `query`";
		$Q[] = "ALTER TABLE exp_weblog_fields ADD COLUMN field_pre_populate char(1) NOT NULL default 'n'";
		$Q[] = "ALTER TABLE exp_weblog_fields ADD COLUMN field_pre_weblog_id int(6) unsigned NOT NULL";
		$Q[] = "ALTER TABLE exp_weblog_fields ADD COLUMN field_pre_field_id int(6) unsigned NOT NULL";
		$Q[] = "ALTER TABLE exp_throttle ADD COLUMN locked_out char(1) NOT NULL default 'n'";
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN enable_versioning char(1) NOT NULL default 'n'";
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN max_revisions smallint(4) unsigned NOT NULL default 10";
		$Q[] = "ALTER TABLE exp_member_fields ADD COLUMN m_field_description text NOT NULL";
		$Q[] = "ALTER TABLE exp_member_groups ADD COLUMN can_edit_categories char(1) NOT NULL default 'n'";
		$Q[] = "ALTER TABLE exp_member_groups ADD COLUMN can_delete_categories char(1) NOT NULL default 'n'";
		$Q[] = "ALTER TABLE exp_member_groups ADD COLUMN include_in_memberlist char(1) NOT NULL default 'y'";
		$Q[] = "ALTER TABLE exp_weblog_fields ADD COLUMN field_related_to varchar(12) NOT NULL default 'blog'";
		$Q[] = "ALTER TABLE exp_weblog_fields ADD COLUMN field_related_id int(6) unsigned NOT NULL";
		$Q[] = "ALTER TABLE exp_weblog_fields ADD COLUMN field_related_orderby varchar(12) NOT NULL default 'date'";
		$Q[] = "ALTER TABLE exp_weblog_fields ADD COLUMN field_related_sort varchar(4) NOT NULL default 'desc'";
		$Q[] = "ALTER TABLE exp_weblog_fields ADD COLUMN field_related_max smallint(4) NOT NULL";
		$Q[] = "INSERT INTO exp_specialty_templates(template_name, data_title, template_data) VALUES ('admin_notify_mailinglist', '".addslashes(trim(admin_notify_mailinglist_title()))."', '".addslashes(admin_notify_mailinglist())."')";


		$Q[] = "ALTER TABLE exp_comments ADD INDEX (`weblog_id`)";
		$Q[] = "DELETE FROM exp_throttle";
		$Q[] = "DELETE FROM exp_search";


		// Run the queries
		foreach ($Q as $sql)
		{
			ee()->db->query($sql);
		}


		// Update mailing list templates
        $query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_modules WHERE module_name = 'Mailinglist'");

        if ($query->row('count') > 0)
        {
			ee()->db->query("ALTER TABLE exp_mailing_lists ADD COLUMN list_template text NOT NULL");

			$query = ee()->db->query("SELECT list_id FROM exp_mailing_lists");

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					ee()->db->query("UPDATE exp_mailing_lists SET list_template ='".addslashes(mailinglist_template())."' WHERE list_id = '".$row['list_id']."'");
				}
			}
		}

		// Fetch email character set so we can add it to config file

		$charset = 'utf-8';
		if ( ! class_exists('EEmail'))
		{
			if ( include('./core/core.email.php'))
			{
				$EMAIL = new EEmail(FALSE);
				$charset = $EMAIL->charset;
			}
		}

		// Update config data
		$data['allow_extensions'] = 'n';
		$data['email_charset'] = $charset;
		$data['honor_entry_dst'] = "y";
		$data['allow_member_localization'] = "y";
		$data['banish_masked_ips'] = 'y';
		$data['max_page_loads'] = "10";
		$data['time_interval'] = "8";
		$data['lockout_time'] = '30';
		$data['banishment_type'] = "message";
		$data['banishment_url'] = "";
		$data['banishment_message'] = "You have exceeded the allowed page load frequency.";
		$data['enable_search_log'] = "y";
		$data['max_logged_searches'] = "500";
		$data['webmaster_name'] = '';
		$data['censor_replacement'] = '';
		$data['mailinglist_enabled'] = 'y';
		$data['mailinglist_notify'] = 'n';
		$data['mailinglist_notify_emails'] = '';
		$data['memberlist_order_by'] = "total_posts";
		$data['memberlist_sort_order'] = "desc";
		$data['memberlist_row_limit'] = "20";

		ee()->config->_append_config_1x($data);

		unset($config);
		unset($conf);

		include(ee()->config->config_path);

		if (isset($conf))
		{
			$config = $conf;
		}

		// This config item is no longer needed
		unset($config['tmpl_display_mode']);

		ee()->config->_update_config_1x(array(), $config);


		return TRUE;
	}

}
// END CLASS


//  Mailing List Template
// --------------------------------------------------------------------
// --------------------------------------------------------------------

function mailinglist_template()
{
return <<<EOF
{message_text}

To remove your email from this mailing list, click here:
{if html_email}<a href="{unsubscribe_url}">{unsubscribe_url}</a>{/if}
{if plain_email}{unsubscribe_url}{/if}
EOF;
}
/* END */


//---------------------------------------------------
//	Admin Notification of Mailinglist subscription
//--------------------------------------------------

function admin_notify_mailinglist_title()
{
return <<<EOF
Someone has subscribed to your mailing list
EOF;
}

function admin_notify_mailinglist()
{
return <<<EOF
A new mailing list subscription has been accepted.

Email Address: {email}
Mailing List: {mailing_list}
EOF;
}
/* END */


/* End of file ud_140.php */
/* Location: ./system/expressionengine/installer/updates/ud_140.php */