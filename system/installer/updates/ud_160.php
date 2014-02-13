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
		/** ---------------------------------------
		/**  Default Site and Sets Created
		/** ---------------------------------------*/

		$Q[] = "CREATE TABLE `exp_sites` (
			  `site_id` int(5) unsigned NOT NULL auto_increment,
			  `site_label` varchar(100) NOT NULL default '',
			  `site_name` varchar(50) NOT NULL default '',
			  `site_description` text NOT NULL,
			  `site_system_preferences` TEXT NOT NULL ,
			  `site_mailinglist_preferences` TEXT NOT NULL ,
			  `site_member_preferences` TEXT NOT NULL ,
			  `site_template_preferences` TEXT NOT NULL ,
			  `site_weblog_preferences` TEXT NOT NULL ,
			  PRIMARY KEY `site_id` (`site_id`),
			  KEY `site_name` (`site_name`))";

		$Q[] = $DB->insert_string('exp_sites',
								  array('site_id'		=> 1,
								  		'site_label'	=> $this->config['site_name'],
								  		'site_name'		=> $this->create_short_name($this->config['site_name'])));

		/** ---------------------------------------
		/**  Default Administration Prefs
		/** ---------------------------------------*/

		// $UD->conf['sites_tab_behavior'] = 'hover';

		$admin_default = array( 'encryption_type',
								'site_index',
								'site_name',
								'site_url',
								'theme_folder_url',
								'webmaster_email',
								'webmaster_name',
								'weblog_nomenclature',
								'max_caches',
								'captcha_url',
								'captcha_path',
								'captcha_font',
								'captcha_rand',
								'captcha_require_members',
								'enable_db_caching',
								'enable_sql_caching',
								'force_query_string',
								'show_queries',
								'template_debugging',
								'include_seconds',
								'cookie_domain',
								'cookie_path',
								'cookie_prefix',
								'user_session_type',
								'admin_session_type',
								'allow_username_change',
								'allow_multi_logins',
								'password_lockout',
								'password_lockout_interval',
								'require_ip_for_login',
								'require_ip_for_posting',
								'allow_multi_emails',
								'require_secure_passwords',
								'allow_dictionary_pw',
								'name_of_dictionary_file',
								'xss_clean_uploads',
								'redirect_method',
								'deft_lang',
								'xml_lang',
								'charset',
								'send_headers',
								'gzip_output',
								'log_referrers',
								'max_referrers',
								'time_format',
								'server_timezone',
								'server_offset',
								'daylight_savings',
								'default_site_timezone',
								'default_site_dst',
								'honor_entry_dst',
								'mail_protocol',
								'smtp_server',
								'smtp_username',
								'smtp_password',
								'email_debug',
								'email_charset',
								'email_batchmode',
								'email_batch_size',
								'mail_format',
								'word_wrap',
								'email_console_timelock',
								'log_email_console_msgs',
								'cp_theme',
								'email_module_captchas',
								'log_search_terms',
								'secure_forms',
								'deny_duplicate_data',
								'redirect_submitted_links',
								'enable_censoring',
								'censored_words',
								'censor_replacement',
								'banned_ips',
								'banned_emails',
								'banned_usernames',
								'banned_screen_names',
								'ban_action',
								'ban_message',
								'ban_destination',
								'enable_emoticons',
								'emoticon_path',
								'recount_batch_total',
								'remap_pm_urls',  		// Get out of Channel Prefs
								'remap_pm_dest',		// Get out of Channel Prefs
								'new_version_check',
								'publish_tab_behavior',
								'sites_tab_behavior',
								'enable_throttling',
								'banish_masked_ips',
								'max_page_loads',
								'time_interval',
								'lockout_time',
								'banishment_type',
								'banishment_url',
								'banishment_message',
								'enable_search_log',
								'max_logged_searches');

		$prefs = array('is_site_on' => 'y');

		if (@realpath(PATH) !== FALSE)
		{
			$prefs['theme_folder_path'] = preg_replace("#/+#", "/", substr(str_replace("\\", "/", realpath(PATH)), 0, - strlen(ee()->config->item('system_folder').'/')).'/themes/');
		}
		else
		{
			$prefs['theme_folder_path'] = preg_replace("#/+#", "/", PATH.'/themes/');
		}

		foreach($admin_default as $value)
		{
			$prefs[$value] = str_replace('\\', '\\\\', ee()->config->item($value));
		}

		unset($prefs['site_name']);  // In exp_sites now

		$Q[] = $DB->update_string('exp_sites', array('site_system_preferences' => addslashes(serialize($prefs))), "site_id = 1");

		/** ---------------------------------------
		/**  Default Mailinglists Prefs
		/** ---------------------------------------*/

		$mailinglist_default = array('mailinglist_enabled', 'mailinglist_notify', 'mailinglist_notify_emails');

		$prefs = array();

		foreach($mailinglist_default as $value)
		{
			$prefs[$value] = str_replace('\\', '\\\\', ee()->config->item($value));
		}

		$Q[] = $DB->update_string('exp_sites', array('site_mailinglist_preferences' => addslashes(serialize($prefs))), "site_id = 1");

		/** ---------------------------------------
		/**  Default Members Prefs
		/** ---------------------------------------*/

		$member_default = array('un_min_len',
								'pw_min_len',
								'allow_member_registration',
								'allow_member_localization',
								'req_mbr_activation',
								'new_member_notification',
								'mbr_notification_emails',
								'require_terms_of_service',
								'use_membership_captcha',
								'default_member_group',
								'profile_trigger',
								'member_theme',
								'enable_avatars',
								'allow_avatar_uploads',
								'avatar_url',
								'avatar_path',
								'avatar_max_width',
								'avatar_max_height',
								'avatar_max_kb',
								'enable_photos',
								'photo_url',
								'photo_path',
								'photo_max_width',
								'photo_max_height',
								'photo_max_kb',
								'allow_signatures',
								'sig_maxlength',
								'sig_allow_img_hotlink',
								'sig_allow_img_upload',
								'sig_img_url',
								'sig_img_path',
								'sig_img_max_width',
								'sig_img_max_height',
								'sig_img_max_kb',
								'prv_msg_upload_path',
								'prv_msg_max_attachments',
								'prv_msg_attach_maxsize',
								'prv_msg_attach_total',
								'prv_msg_html_format',
								'prv_msg_auto_links',
								'prv_msg_max_chars',
								'memberlist_order_by',
								'memberlist_sort_order',
								'memberlist_row_limit');

		$prefs = array();

		foreach($member_default as $value)
		{
			$prefs[$value] = str_replace('\\', '\\\\', ee()->config->item($value));
		}

		$Q[] = $DB->update_string('exp_sites', array('site_member_preferences' => addslashes(serialize($prefs))), "site_id = 1");

		/** ---------------------------------------
		/**  Default Templates Prefs
		/** ---------------------------------------*/

		$template_default = array('site_404',
								  'save_tmpl_revisions',
								  'max_tmpl_revisions',
								  'save_tmpl_files',
								  'tmpl_file_basepath');
		$prefs = array();

		foreach($template_default as $value)
		{
			$prefs[$value] = str_replace('\\', '\\\\', ee()->config->item($value));
		}

		$Q[] = $DB->update_string('exp_sites', array('site_template_preferences' => addslashes(serialize($prefs))), "site_id = 1");

		/** ---------------------------------------
		/**  Default Channels Prefs
		/** ---------------------------------------*/

		$weblog_default = array('enable_image_resizing',
								'image_resize_protocol',
								'image_library_path',
								'thumbnail_prefix',
								'word_separator',
								'use_category_name',
								'reserved_category_word',
								'auto_convert_high_ascii',
								'new_posts_clear_caches',
								'auto_assign_cat_parents');

		$prefs = array();

		foreach($weblog_default as $value)
		{
			$prefs[$value] = str_replace('\\', '\\\\', ee()->config->item($value));
		}

		$Q[] = $DB->update_string('exp_sites', array('site_weblog_preferences' => addslashes(serialize($prefs))), "site_id = 1");

		/** ---------------------------------------
		/**  Assigned Sites for Member Group
		/** ---------------------------------------*/

		$Q[] = "ALTER TABLE `exp_member_groups` CHANGE `group_id` `group_id` SMALLINT( 4 ) UNSIGNED NOT NULL";
		$Q[] = "ALTER TABLE `exp_member_groups` DROP PRIMARY KEY";
		$Q[] = "ALTER TABLE `exp_member_groups` ADD INDEX ( `group_id` )";

		$Q[] = "ALTER TABLE `exp_member_groups` ADD `include_in_mailinglists` CHAR(1) NOT NULL DEFAULT 'y' AFTER `include_in_memberlist`";

		/** ---------------------------------------
		/**  Tables Requiring a Site ID - Data
		/**  Basically, all data tables that might NOT be called/checked via a specific, unique id (ex: entry_id, member_id, weblog_id, cache_id)
		/**  Oh, and is not a module...as they are their own little beasts.
		/** ---------------------------------------*/

		$tables = array('exp_categories'			=> array('cat_id', 1),
						'exp_category_groups'		=> array('group_id', 1),
						'exp_comments'				=> array('comment_id', 1),
						'exp_cp_log'				=> array('id', 1),
						'exp_field_groups'			=> array('group_id', 1),
						'exp_global_variables'		=> array('variable_id', 1),
						'exp_html_buttons'			=> array('id', 1),				// Because there are site defaults using member_id = 0
						'exp_member_groups'			=> array('group_id', 1),
						'exp_member_search'			=> array('search_id', 1),
						'exp_online_users'			=> array('member_id', 1),
						'exp_ping_servers'			=> array('id', 1),
						'exp_referrers'				=> array('ref_id', 1),
						'exp_search'				=> array('search_id', 1),
						'exp_search_log'			=> array('id', 1),
						'exp_sessions'				=> array('session_id', 1),
						'exp_specialty_templates'	=> array('template_id', 1),
						'exp_stats'					=> array('weblog_id', 1),
						'exp_statuses'				=> array('status_id', 1),
						'exp_status_groups'			=> array('group_id', 1),
						'exp_templates'				=> array('template_id', 1),
						'exp_template_groups'		=> array('group_id', 1),
						'exp_trackbacks'			=> array('trackback_id', 1),
						'exp_upload_prefs'			=> array('id', 1),
						'exp_weblogs'				=> array('weblog_id', 1),
						'exp_weblog_data'			=> array('entry_id', 1),		// I can see someone calling this without using an entry_id. Fools!
						'exp_weblog_fields'			=> array('field_id', 1),
						'exp_weblog_titles'			=> array('entry_id', 1),
						);

		/*
			Update install.php, buck-o
		*/

		foreach($tables as $table => $options)
		{
			if ($DB->table_exists($table) === FALSE) continue;  // For a few modules that can be uninstalled

			$Q[] = "ALTER TABLE `".$DB->escape_str($table)."` ADD `site_id` INT(4) UNSIGNED NOT NULL DEFAULT 1 AFTER `".$DB->escape_str($options[0])."`";
			$Q[] = "ALTER TABLE `".$DB->escape_str($table)."` ADD INDEX (`site_id`)";
			$Q[] = "UPDATE `".$DB->escape_str($table)."` SET `site_id` = '".$DB->escape_str($options[1])."'";
		}

		/** ---------------------------------------
		/**  Stuff for Category URL Titles
		/** ---------------------------------------*/

		$Q[] = "ALTER TABLE `exp_categories` CHANGE `cat_name` `cat_name` varchar(100) NOT NULL";
		$Q[] = "ALTER TABLE `exp_categories` ADD `cat_url_title` varchar(75) NOT NULL AFTER `cat_name`";
		$Q[] = "UPDATE `exp_categories` SET `cat_url_title` = `cat_name`";

		/** ---------------------------------------
		/**  Category Custom Fields
		/** ---------------------------------------*/

		$Q[] = "CREATE TABLE `exp_category_fields` (
				`field_id` int(6) unsigned NOT NULL auto_increment,
				`site_id` int(4) unsigned NOT NULL default 1,
				`group_id` int(4) unsigned NOT NULL,
				`field_name` varchar(32) NOT NULL default '',
				`field_label` varchar(50) NOT NULL default '',
				`field_type` varchar(12) NOT NULL default 'text',
				`field_list_items` text NOT NULL,
				`field_maxl` smallint(3) NOT NULL default 128,
				`field_ta_rows` tinyint(2) NOT NULL default 8,
				`field_default_fmt` varchar(40) NOT NULL default 'none',
				`field_show_fmt` char(1) NOT NULL default 'y',
				`field_text_direction` CHAR(3) NOT NULL default 'ltr',
				`field_required` char(1) NOT NULL default 'n',
				`field_order` int(3) unsigned NOT NULL,
				PRIMARY KEY `field_id` (`field_id`),
				KEY `site_id` (`site_id`),
				KEY `group_id` (`group_id`)
				)";

		$Q[] = "CREATE TABLE `exp_category_field_data` (
				`cat_id` int(4) unsigned NOT NULL,
				`site_id` int(4) unsigned NOT NULL default 1,
				`group_id` int(4) unsigned NOT NULL,
				PRIMARY KEY `cat_id` (`cat_id`),
				KEY `site_id` (`site_id`),
				KEY `group_id` (`group_id`)
				)";

		$Q[] = "ALTER TABLE `exp_category_groups` ADD `field_html_formatting` char(4) NOT NULL default 'all' AFTER `sort_order`";
		$Q[] = "ALTER TABLE `exp_category_groups` ADD `can_edit_categories` TEXT NOT NULL AFTER `field_html_formatting`";
		$Q[] = "ALTER TABLE `exp_category_groups` ADD `can_delete_categories` TEXT NOT NULL AFTER `can_edit_categories`";

		/** ---------------------------------------
		/**  Trackback URL preference
		/** ---------------------------------------*/

		$Q[] = "ALTER TABLE `exp_weblogs` ADD `trackback_use_url_title` char(1) NOT NULL default 'n' AFTER `enable_trackbacks`";

		/** ---------------------------------------
		/**  Pages Module Related
		/** ---------------------------------------*/

		$Q[] = "ALTER TABLE `exp_weblogs` ADD `show_pages_cluster` CHAR(1) NOT NULL DEFAULT 'y' AFTER `show_forum_cluster`";

		/** ---------------------------------------
		/**  Plain text alternative field for emails
		/**  sent from the Communicate page
		/** ---------------------------------------*/

		$Q[] = "ALTER TABLE `exp_email_cache` ADD `plaintext_alt` MEDIUMTEXT NOT NULL AFTER `message`";

		/** -------------------------------
		/**  Is the Gallery module installed?
		/** -------------------------------*/

        $query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_modules WHERE module_name = 'Gallery'");

        if ($query->row('count') > 0)
        {
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_one_searchable char(1) NOT NULL default 'y' AFTER `gallery_cf_one_auto_link`";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_two_searchable char(1) NOT NULL default 'y' AFTER `gallery_cf_two_auto_link`";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_three_searchable char(1) NOT NULL default 'y' AFTER `gallery_cf_three_auto_link`";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_four_searchable char(1) NOT NULL default 'y' AFTER `gallery_cf_four_auto_link`";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_five_searchable char(1) NOT NULL default 'y' AFTER `gallery_cf_five_auto_link`";
			$Q[] = "ALTER TABLE exp_galleries ADD COLUMN gallery_cf_six_searchable char(1) NOT NULL default 'y' AFTER `gallery_cf_six_auto_link`";
        }

		/** ---------------------------------------
		/**  Run Queries
		/** ---------------------------------------*/

		foreach ($Q as $sql)
		{
			ee()->db->query($sql);
		}

		/** ---------------------------------------
		/**  Insert field data rows for existing categories
		/** ---------------------------------------*/

		$query = ee()->db->query("SELECT cat_id, group_id FROM exp_categories");

		if ($query->num_rows() > 0)
		{
			$cat_ids = array();

			foreach($query->result_array() as $row)
			{
				$cat_ids[] = array('cat_id' => $row['cat_id'], 'group_id' => $row['group_id'], 'site_id' => '1');
			}

			foreach ($cat_ids as $cat)
			{
				ee()->db->query(ee()->db->insert_string('exp_category_field_data', $cat));
			}
		}

		/** ---------------------------------------
		/**  Update the Config File
		/** ---------------------------------------*/

		$data['multiple_sites_enabled'] = "n";

		ee()->config->_append_config_1x($data);

		unset($config);
		unset($conf);

		include(ee()->config->config_path);

		if (isset($conf))
		{
			$config = $conf;
		}

		foreach(array_merge($admin_default, $mailinglist_default, $member_default, $template_default, $weblog_default) as $value)
		{
			unset($config[$value]);
		}

		$config['doc_url'] = str_replace('eedocs.pmachine.com', 'expressionengine.com/docs', $config['doc_url']);

		ee()->config->_update_config_1x(array(), $config);

		return TRUE;
	}
	/* END */


	/** -------------------------------------------------
    /**  Create Short Name
    /** -------------------------------------------------*/

	function create_short_name($str)
	{
		if (function_exists('mb_convert_encoding'))
		{
			$str = mb_convert_encoding($str, strtoupper('ISO-8859-1'), 'auto');
		}
		elseif(function_exists('iconv') AND ($iconvstr = @iconv('', strtoupper(ee()->config->item('charset')), $str)) !== FALSE)
		{
			$str = $iconvstr;
		}
		else
		{
			$str = utf8_decode($str);
		}

		$str = preg_replace_callback('/(.)/', array($this, "convert_accented_characters"), $str);

		$str = strip_tags(strtolower($str));
		$str = preg_replace('/\&#\d+\;/', "", $str);

		// Use dash as separator

		if (ee()->config->item('word_separator') == 'dash')
		{
			$trans = array(
							"_"									=> '-',
							"\&\#\d+?\;"                        => '',
							"\&\S+?\;"                          => '',
							"['\"\?\.\!*\$\#@%;:,\_=\(\)\[\]]"  => '',
							"\s+"                               => '-',
							"\/"                                => '-',
							"[^a-z0-9-_]"						=> '',
							"-+"                                => '-',
							"\&"                                => '',
							"-$"                                => '',
							"^-"                                => ''
						   );
		}
		else // Use underscore as separator
		{
			$trans = array(
							"-"									=> '_',
							"\&\#\d+?\;"                        => '',
							"\&\S+?\;"                          => '',
							"['\"\?\.\!*\$\#@%;:,\-=\(\)\[\]]"  => '',
							"\s+"                               => '_',
							"\/"                                => '_',
							"[^a-z0-9-_]"						=> '',
							"_+"                                => '_',
							"\&"                                => '',
							"_$"                                => '',
							"^_"                                => ''
						   );
		}

		foreach ($trans as $key => $val)
		{
			$str = preg_replace("#".$key."#", $val, $str);
		}

		$str = trim(stripslashes($str));

		return $str;
	}
	/* END */


	/** ---------------------------------------
	/**  Convert Accented Characters to Unaccented Equivalents
	/** ---------------------------------------*/

	function convert_accented_characters($match)
	{
		$foreign_characters = array('223'	=>	"ss", // ß
    								'224'	=>  "a",  '225' =>  "a", '226' => "a", '229' => "a",
    								'227'	=>	"ae", '230'	=>	"ae", '228' => "ae",
    								'231'	=>	"c",
    								'232'	=>	"e",  // è
    								'233'	=>	"e",  // é
    								'234'	=>	"e",  // ê
    								'235'	=>	"e",  // ë
    								'236'	=>  "i",  '237' =>  "i", '238' => "i", '239' => "i",
    								'241'	=>	"n",
    								'242'	=>  "o",  '243' =>  "o", '244' => "o", '245' => "o",
    								'246'	=>	"oe", // ö
    								'249'	=>  "u",  '250' =>  "u", '251' => "u",
    								'252'	=>	"ue", // ü
    								'255'	=>	"y",
    								'257'	=>	"aa",
									'269'	=>	"ch",
									'275'	=>	"ee",
									'291'	=>	"gj",
									'299'	=>	"ii",
									'311'	=>	"kj",
									'316'	=>	"lj",
									'326'	=>	"nj",
									'353'	=>	"sh",
									'363'	=>	"uu",
									'382'	=>	"zh",
									'256'	=>	"aa",
									'268'	=>	"ch",
									'274'	=>	"ee",
									'290'	=>	"gj",
									'298'	=>	"ii",
									'310'	=>	"kj",
									'315'	=>	"lj",
									'325'	=>	"nj",
									'352'	=>	"sh",
									'362'	=>	"uu",
									'381'	=>	"zh",
    								);

    	$ord = ord($match[1]);

		if (isset($foreign_characters[$ord]))
		{
			return $foreign_characters[$ord];
		}
		else
		{
			return $match[1];
		}
	}
	/* END */

}
/* END CLASS */



/* End of file ud_160.php */
/* Location: ./system/expressionengine/installer/updates/ud_160.php */