<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Schema Class
 */
class EE_Schema
{
    // All of these variables are set dynamically
    public $now;
    public $year;
    public $month;
    public $day;
    public $default_entry = '';
    public $theme_path = '';
    public $version;
    public $userdata;

    public $roles = array(
        1 => array(
            'name' => 'Super Admin',
            'short_name' => 'super_admin',
            'role_id' => 1,
            'is_locked' => 'y',
            'exclude_from_moderation' => 'y',
            'include_in_authorlist' => 'y',
            'search_flood_control' => '0',
            'show_field_names' => 'y'
        ),
        2 => array(
            'name' => 'Banned',
            'short_name' => 'banned',
            'role_id' => 2,
            'is_locked' => 'n',
            'include_in_memberlist' => 'n',
            'search_flood_control' => '60'
        ),
        3 => array(
            'name' => 'Guests',
            'short_name' => 'guests',
            'role_id' => 3,
            'is_locked' => 'n',
            'search_flood_control' => '10'
        ),
        4 => array(
            'name' => 'Pending',
            'short_name' => 'pending',
            'role_id' => 4,
            'is_locked' => 'n',
            'search_flood_control' => '10'
        ),
        5 => array(
            'name' => 'Members',
            'short_name' => 'members',
            'role_id' => 5,
            'is_locked' => 'n',
            'search_flood_control' => '10'
        )
    );

    public $role_permissions = [
        1 => [
            'can_view_offline_system',
            'can_access_cp',
            'can_access_dock',
            'can_access_footer_report_bug',
            'can_access_footer_new_ticket',
            'can_access_footer_user_guide',
            'can_view_homepage_news',
            'can_upload_new_files',
            'can_edit_files',
            'can_delete_files',
            'can_upload_new_toolsets',
            'can_edit_toolsets',
            'can_delete_toolsets',
            'can_create_upload_directories',
            'can_edit_upload_directories',
            'can_delete_upload_directories',
            'can_access_files',
            'can_access_design',
            'can_access_addons',
            'can_access_members',
            'can_access_sys_prefs',
            'can_access_comm',
            'can_access_utilities',
            'can_access_data',
            'can_access_logs',
            'can_admin_channels',
            'can_create_channels',
            'can_edit_channels',
            'can_delete_channels',
            'can_create_channel_fields',
            'can_edit_channel_fields',
            'can_delete_channel_fields',
            'can_create_statuses',
            'can_delete_statuses',
            'can_edit_statuses',
            'can_create_categories',
            'can_create_roles',
            'can_delete_roles',
            'can_edit_roles',
            'can_admin_design',
            'can_create_members',
            'can_edit_members',
            'can_delete_members',
            'can_admin_roles',
            'can_admin_mbr_templates',
            'can_ban_users',
            'can_admin_addons',
            'can_create_templates',
            'can_edit_templates',
            'can_delete_templates',
            'can_create_template_groups',
            'can_edit_template_groups',
            'can_delete_template_groups',
            'can_create_template_partials',
            'can_edit_template_partials',
            'can_delete_template_partials',
            'can_create_template_variables',
            'can_delete_template_variables',
            'can_edit_template_variables',
            'can_edit_categories',
            'can_delete_categories',
            'can_view_other_entries',
            'can_edit_other_entries',
            'can_assign_post_authors',
            'can_delete_self_entries',
            'can_delete_all_entries',
            'can_view_other_comments',
            'can_edit_own_comments',
            'can_delete_own_comments',
            'can_edit_all_comments',
            'can_delete_all_comments',
            'can_moderate_comments',
            'can_send_cached_email',
            'can_email_roles',
            'can_email_from_profile',
            'can_view_profiles',
            'can_edit_html_buttons',
            'can_post_comments',
            'can_delete_self',
            'can_send_private_messages',
            'can_attach_in_private_messages',
            'can_send_bulletins',
            'can_search',
            'can_create_entries',
            'can_edit_self_entries',
            'can_access_security_settings',
            'can_access_translate',
            'can_access_import',
            'can_access_sql_manager',
            'can_moderate_spam',
            'can_manage_consents',
        ],
        3 => [
            'can_view_online_system',
        ],
        4 => [
            'can_view_online_system',
        ],
        5 => [
            'can_view_online_system',
            'can_email_from_profile',
            'can_view_profiles',
            'can_edit_html_buttons',
            'can_delete_self',
            'can_send_private_messages',
            'can_attach_in_private_messages',
        ]
    ];

    private $default_engine = 'InnoDB';

    /**
     * Returns a platform-specific query that looks for EE tables
     *
     * @access	public
     * @return	string
     */
    public function sql_find_like()
    {
        return "SHOW tables LIKE '" . ee()->db->escape_like_str($this->userdata['db_prefix']) . "%'";
    }

    /**
     * Installs the DB tables and data
     *
     * @access	public
     * @return	bool
     */
    public function install_tables_and_data()
    {
        // Sites

        $Q[] = "CREATE TABLE `exp_sites` (
			`site_id` int(5) unsigned NOT NULL auto_increment,
			`site_label` varchar(100) NOT NULL default '',
			`site_name` varchar(50) NOT NULL default '',
			`site_description` text NULL,
			`site_color` varchar(6) NOT NULL default '',
			`site_bootstrap_checksums` TEXT NOT NULL ,
			`site_pages` MEDIUMTEXT NOT NULL ,
			PRIMARY KEY `site_id` (`site_id`),
			KEY `site_name` (`site_name`)
		)";

        // Session data

        $Q[] = "CREATE TABLE exp_sessions (
			session_id varchar(40) default '0' NOT NULL,
			member_id int(10) default '0' NOT NULL,
			admin_sess tinyint(1) default '0' NOT NULL,
			ip_address varchar(45) default '0' NOT NULL,
			user_agent varchar(120) NOT NULL,
			login_state varchar(32) NULL DEFAULT NULL,
			fingerprint varchar(40) NOT NULL,
			sess_start int(10) unsigned DEFAULT '0' NOT NULL,
			auth_timeout int(10) unsigned DEFAULT '0' NOT NULL,
			last_activity int(10) unsigned DEFAULT '0' NOT NULL,
			can_debug char(1) NOT NULL DEFAULT 'n',
            mfa_flag ENUM('skip','show','required') NOT NULL DEFAULT 'skip',
            pro_banner_seen char(1) NOT NULL DEFAULT 'n',
			PRIMARY KEY `session_id` (`session_id`),
			KEY `member_id` (`member_id`),
			KEY `last_activity_idx` (`last_activity`)
		)";

        // Throttle

        $Q[] = "CREATE TABLE exp_throttle (
			throttle_id int(10) unsigned NOT NULL auto_increment,
			ip_address varchar(45) default '0' NOT NULL,
			last_activity int(10) unsigned DEFAULT '0' NOT NULL,
			hits int(10) unsigned NOT NULL,
			locked_out char(1) NOT NULL default 'n',
			PRIMARY KEY `throttle_id` (`throttle_id`),
			KEY `ip_address` (`ip_address`),
			KEY `last_activity` (`last_activity`)
		)";

        // System stats

        $Q[] = "CREATE TABLE exp_stats (
			stat_id int(10) unsigned NOT NULL auto_increment,
			site_id INT(4) UNSIGNED NOT NULL DEFAULT 1,
			total_members mediumint(7) NOT NULL default '0',
			recent_member_id int(10) default '0' NOT NULL,
			recent_member varchar(75) NOT NULL,
			total_entries mediumint(8) default '0' NOT NULL,
			total_forum_topics mediumint(8) default '0' NOT NULL,
			total_forum_posts mediumint(8) default '0' NOT NULL,
			total_comments mediumint(8) default '0' NOT NULL,
			last_entry_date int(10) unsigned default '0' NOT NULL,
			last_forum_post_date int(10) unsigned default '0' NOT NULL,
			last_comment_date int(10) unsigned default '0' NOT NULL,
			last_visitor_date int(10) unsigned default '0' NOT NULL,
			most_visitors mediumint(7) NOT NULL default '0',
			most_visitor_date int(10) unsigned default '0' NOT NULL,
			last_cache_clear int(10) unsigned default '0' NOT NULL,
			PRIMARY KEY `stat_id` (`stat_id`),
			KEY `site_id` (`site_id`)
		)";

        // Online users

        $Q[] = "CREATE TABLE exp_online_users (
			online_id int(10) unsigned NOT NULL auto_increment,
			site_id INT(4) UNSIGNED NOT NULL DEFAULT 1,
			member_id int(10) default '0' NOT NULL,
			in_forum char(1) NOT NULL default 'n',
			name varchar(" . USERNAME_MAX_LENGTH . ") default '0' NOT NULL,
			ip_address varchar(45) default '0' NOT NULL,
			date int(10) unsigned default '0' NOT NULL,
			anon char(1) NOT NULL,
			PRIMARY KEY `online_id` (`online_id`),
			KEY `date` (`date`),
			KEY `site_id` (`site_id`)
		)";

        // Actions table
        // Actions are events that require processing. Used by modules class.

        $Q[] = "CREATE TABLE exp_actions (
			action_id int(4) unsigned NOT NULL auto_increment,
			class varchar(50) NOT NULL,
			method varchar(50) NOT NULL,
			csrf_exempt tinyint(1) UNSIGNED NOT NULL default 0,
			PRIMARY KEY `action_id` (`action_id`)
		)";

        // Modules table
        // Contains a list of all installed modules

        $Q[] = "CREATE TABLE exp_modules (
			module_id int(4) unsigned NOT NULL auto_increment,
			module_name varchar(50) NOT NULL,
			module_version varchar(12) NOT NULL,
			has_cp_backend char(1) NOT NULL default 'n',
			has_publish_fields char(1) NOT NULL default 'n',
			PRIMARY KEY `module_id` (`module_id`)
		)";

        // Plugins table
        // Contains a list of all installed plugins

        $Q[] = "CREATE TABLE exp_plugins (
			plugin_id int(10) unsigned NOT NULL auto_increment,
			plugin_name varchar(50) NOT NULL,
			plugin_package varchar(50) NOT NULL,
			plugin_version varchar(12) NOT NULL,
			is_typography_related char(1) NOT NULL default 'n',
			PRIMARY KEY `plugin_id` (`plugin_id`)
		)";

        // Security Hashes
        // Used to store hashes needed to process forms in 'secure mode'

        $Q[] = "CREATE TABLE exp_security_hashes (
			hash_id int(10) unsigned NOT NULL auto_increment,
			date int(10) unsigned NOT NULL,
			session_id varchar(40) default '0' NOT NULL,
			hash varchar(40) NOT NULL,
			PRIMARY KEY `hash_id` (`hash_id`),
			KEY `session_id` (`session_id`)
		)";

        // CAPTCHA data

        $Q[] = "CREATE TABLE exp_captcha (
			captcha_id bigint(13) unsigned NOT NULL auto_increment,
			date int(10) unsigned NOT NULL,
			ip_address varchar(45) default '0' NOT NULL,
			word varchar(20) NOT NULL,
			PRIMARY KEY `captcha_id` (`captcha_id`),
			KEY `word` (`word`)
		)";

        // Password Lockout
        // If password lockout is enabled, a user only gets
        // four attempts to log-in within a specified period.
        // This table holds the a list of locked out users

        $Q[] = "CREATE TABLE exp_password_lockout (
			lockout_id int(10) unsigned NOT NULL auto_increment,
			login_date int(10) unsigned NOT NULL,
			ip_address varchar(45) default '0' NOT NULL,
			user_agent varchar(120) NOT NULL,
			username varchar(" . USERNAME_MAX_LENGTH . ") NOT NULL,
			PRIMARY KEY `lockout_id` (`lockout_id`),
			KEY `login_date` (`login_date`),
			KEY `ip_address` (`ip_address`),
			KEY `user_agent` (`user_agent`)
		)";

        // Reset password
        // If a user looses their password, this table
        // holds the reset code.

        $Q[] = "CREATE TABLE exp_reset_password (
			reset_id int(10) unsigned NOT NULL auto_increment,
			member_id int(10) unsigned NOT NULL,
			resetcode varchar(12) NOT NULL,
			date int(10) NOT NULL,
			PRIMARY KEY `reset_id` (`reset_id`)
		)";

        // Email Cache
        // We store all email messages that are sent from the CP

        $Q[] = "CREATE TABLE exp_email_cache (
			cache_id int(6) unsigned NOT NULL auto_increment,
			cache_date int(10) unsigned default '0' NOT NULL,
			total_sent int(6) unsigned NOT NULL,
			from_name varchar(70) NOT NULL,
			from_email varchar(254) NOT NULL,
			recipient text NOT NULL,
			cc text NOT NULL,
			bcc text NOT NULL,
			recipient_array mediumtext NOT NULL,
			subject varchar(120) NOT NULL,
			message mediumtext NOT NULL,
			`plaintext_alt` MEDIUMTEXT NOT NULL,
			mailtype varchar(6) NOT NULL,
			text_fmt varchar(40) NOT NULL,
			wordwrap char(1) NOT NULL default 'y',
			attachments MEDIUMTEXT,
			PRIMARY KEY `cache_id` (`cache_id`)
		)";

        // Cached Member Groups
        // We use this table to store the member group assignments
        // for each email that is sent.  Since you can send email
        // to various combinations of members, we store the member
        // group numbers in this table, which is joined to the
        // table above when we need to re-send an email from cache.

        $Q[] = "CREATE TABLE exp_email_cache_mg (
			cache_id int(6) unsigned NOT NULL,
			role_id int(10) NOT NULL,
			PRIMARY KEY `cache_id_role_id` (`cache_id`, `role_id`)
		)";

        // We do the same with mailing lists

        $Q[] = "CREATE TABLE exp_email_cache_ml (
			cache_id int(6) unsigned NOT NULL,
			list_id smallint(4) NOT NULL,
			PRIMARY KEY `cache_id_list_id` (`cache_id`, `list_id`)
		)";

        // Email Console Cache
        // Emails sent from the member profile email console are saved here.

        $Q[] = "CREATE TABLE exp_email_console_cache (
			cache_id int(6) unsigned NOT NULL auto_increment,
			cache_date int(10) unsigned default '0' NOT NULL,
			member_id int(10) unsigned NOT NULL,
			member_name varchar(50) NOT NULL,
			ip_address varchar(45) default '0' NOT NULL,
			recipient varchar(75) NOT NULL,
			recipient_name varchar(50) NOT NULL,
			subject varchar(120) NOT NULL,
			message mediumtext NOT NULL,
			PRIMARY KEY `cache_id` (`cache_id`)
		)";

        // Member table
        // Contains the member info

        $Q[] = "CREATE TABLE exp_members (
			member_id int(10) unsigned NOT NULL auto_increment,
			role_id int(10) NOT NULL default '0',
			pending_role_id int(10) NOT NULL default '0',
			username varchar(" . USERNAME_MAX_LENGTH . ") NOT NULL,
			screen_name varchar(" . USERNAME_MAX_LENGTH . ") NOT NULL,
			password varchar(128) NOT NULL DEFAULT '',
			salt varchar(128) NOT NULL DEFAULT '',
			unique_id varchar(40) NOT NULL,
			crypt_key varchar(40) NULL DEFAULT NULL,
			backup_mfa_code varchar(128) NULL DEFAULT NULL,
			authcode varchar(10) NULL DEFAULT NULL,
			email varchar(254) NOT NULL,
			signature text NULL,
			avatar_filename varchar(120) NULL DEFAULT NULL,
			avatar_width int(4) unsigned NULL DEFAULT NULL,
			avatar_height int(4) unsigned NULL DEFAULT NULL,
			photo_filename varchar(120) NULL DEFAULT NULL,
			photo_width int(4) unsigned NULL DEFAULT NULL,
			photo_height int(4) unsigned NULL DEFAULT NULL,
			sig_img_filename varchar(120) NULL DEFAULT NULL,
			sig_img_width int(4) unsigned NULL DEFAULT NULL,
			sig_img_height int(4) unsigned NULL DEFAULT NULL,
			ignore_list text NULL,
			private_messages int(4) unsigned DEFAULT '0' NOT NULL,
			accept_messages char(1) NOT NULL default 'y',
			last_view_bulletins int(10) NOT NULL default 0,
			last_bulletin_date int(10) NOT NULL default 0,
			ip_address varchar(45) default '0' NOT NULL,
			join_date int(10) unsigned default '0' NOT NULL,
			last_visit int(10) unsigned default '0' NOT NULL,
			last_activity int(10) unsigned default '0' NOT NULL,
			total_entries mediumint(8) unsigned NOT NULL default '0',
			total_comments mediumint(8) unsigned NOT NULL default '0',
			total_forum_topics mediumint(8) default '0' NOT NULL,
			total_forum_posts mediumint(8) default '0' NOT NULL,
			last_entry_date int(10) unsigned default '0' NOT NULL,
			last_comment_date int(10) unsigned default '0' NOT NULL,
			last_forum_post_date int(10) unsigned default '0' NOT NULL,
			last_email_date int(10) unsigned default '0' NOT NULL,
			in_authorlist char(1) NOT NULL default 'n',
			accept_admin_email char(1) NOT NULL default 'y',
			accept_user_email char(1) NOT NULL default 'y',
			notify_by_default char(1) NOT NULL default 'y',
			notify_of_pm char(1) NOT NULL default 'y',
			display_signatures char(1) NOT NULL default 'y',
			parse_smileys char(1) NOT NULL default 'y',
			smart_notifications char(1) NOT NULL default 'y',
			language varchar(50) NOT NULL,
			timezone varchar(50) NULL DEFAULT NULL,
			time_format char(2) NULL DEFAULT NULL,
			date_format varchar(8) NULL DEFAULT NULL,
			week_start varchar(8) NULL DEFAULT NULL,
			include_seconds char(1) NULL DEFAULT NULL,
			profile_theme varchar(32) NULL DEFAULT NULL,
			forum_theme varchar(32) NULL DEFAULT NULL,
			tracker text NULL,
			template_size varchar(2) NOT NULL default '28',
			notepad text NULL,
			notepad_size varchar(2) NOT NULL default '18',
			bookmarklets text NULL,
			quick_links text NULL,
			quick_tabs text NULL,
			show_sidebar char(1) NOT NULL default 'n',
			pmember_id int(10) NOT NULL default '0',
			cp_homepage varchar(20) NULL DEFAULT NULL,
			cp_homepage_channel text NULL,
			cp_homepage_custom varchar(100) NULL DEFAULT NULL,
			dismissed_banner char(1) NOT NULL DEFAULT 'n',
			enable_mfa char(1) NOT NULL default 'n',
			PRIMARY KEY `member_id` (`member_id`),
			KEY `role_id` (`role_id`),
			KEY `unique_id` (`unique_id`),
			KEY `password` (`password`)
		)";

        $Q[] = "CREATE TABLE `exp_roles` (
			`role_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`name` varchar(100) NOT NULL,
			`short_name` varchar(50) NOT NULL,
			`description` text,
			`site_color` varchar(6) NOT NULL default '',
			`total_members` mediumint(8) unsigned NOT NULL DEFAULT '0',
			`is_locked` char(1) NOT NULL DEFAULT 'n',
            `highlight` varchar(6) NOT NULL default '',
			PRIMARY KEY (`role_id`)
		)";

        $Q[] = "CREATE TABLE `exp_members_roles` (
			`member_id` int(10) unsigned NOT NULL,
			`role_id` int(10) unsigned NOT NULL,
			PRIMARY KEY (`member_id`,`role_id`)
		)";

        $Q[] = "CREATE TABLE `exp_role_groups` (
			`group_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`name` varchar(100) NOT NULL,
			PRIMARY KEY (`group_id`)
		)";

        $Q[] = "CREATE TABLE `exp_roles_role_groups` (
			`role_id` int(10) unsigned NOT NULL,
			`group_id` int(10) unsigned NOT NULL,
			PRIMARY KEY (`role_id`,`group_id`),
			KEY `group_id_idx` (`group_id`)
		)";

        $Q[] = "CREATE TABLE `exp_members_role_groups` (
			`member_id` int(10) unsigned NOT NULL,
			`group_id` int(10) unsigned NOT NULL,
			PRIMARY KEY (`member_id`,`group_id`)
		)";

        $Q[] = "CREATE TABLE `exp_permissions` (
			`permission_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		    `role_id` int(10) unsigned NOT NULL,
			`site_id` int(5) unsigned NOT NULL,
			`permission` varchar(64) NOT NULL,
			PRIMARY KEY (`permission_id`),
			KEY `role_id_site_id` (`role_id`,`site_id`)
		)";

        // Member Groups table

        $Q[] = "CREATE TABLE exp_role_settings (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`role_id` int(10) unsigned NOT NULL,
			`site_id` int(4) unsigned NOT NULL DEFAULT '1',
			`menu_set_id` int(5) unsigned NOT NULL DEFAULT '1',
			`mbr_delete_notify_emails` varchar(255) DEFAULT NULL,
			`exclude_from_moderation` char(1) NOT NULL DEFAULT 'n',
			`search_flood_control` mediumint(5) unsigned NOT NULL,
			`prv_msg_send_limit` smallint(5) unsigned NOT NULL DEFAULT '20',
			`prv_msg_storage_limit` smallint(5) unsigned NOT NULL DEFAULT '60',
			`include_in_authorlist` char(1) NOT NULL DEFAULT 'n',
			`include_in_memberlist` char(1) NOT NULL DEFAULT 'y',
			`cp_homepage` varchar(20) DEFAULT NULL,
			`cp_homepage_channel` int(10) unsigned NOT NULL DEFAULT '0',
			`cp_homepage_custom` varchar(100) DEFAULT NULL,
			`require_mfa` char(1) NOT NULL DEFAULT 'n',
			`show_field_names` char(1) NOT NULL DEFAULT 'y',
			PRIMARY KEY (`id`),
			KEY `role_id_site_id` (`role_id`, `site_id`)
		)";

        // Channel access privs
        // Role assignment for each channel

        $Q[] = "CREATE TABLE exp_channel_member_roles (
			role_id int(10) unsigned NOT NULL,
			channel_id int(6) unsigned NOT NULL,
			PRIMARY KEY `role_id_channel_id` (`role_id`, `channel_id`)
		)";

        // Module access privs
        // Role assignment for each module

        $Q[] = "CREATE TABLE exp_module_member_roles (
		role_id int(10) unsigned NOT NULL,
			module_id mediumint(5) unsigned NOT NULL,
			PRIMARY KEY `role_id_module_id` (`role_id`, `module_id`)
		)";

        // Template Group access privs
        // Role assignment for each template group

        $Q[] = "CREATE TABLE exp_template_groups_roles (
			role_id int(10) unsigned NOT NULL,
			template_group_id mediumint(5) unsigned NOT NULL,
			PRIMARY KEY `role_id_template_group_id` (`role_id`, `template_group_id`)
		)";

        // Member Custom Fields
        // Stores the definition of each field

        $Q[] = "CREATE TABLE exp_member_fields (
			m_field_id int(4) unsigned NOT NULL auto_increment,
			m_field_name varchar(32) NOT NULL,
			m_field_label varchar(50) NOT NULL,
			m_field_description text NOT NULL,
			m_field_type varchar(50) NOT NULL default 'text',
			m_field_list_items text NOT NULL,
			m_field_ta_rows tinyint(2) default '8',
			m_field_maxl smallint(3) NULL DEFAULT NULL,
			m_field_width varchar(6) NULL DEFAULT NULL,
			m_field_search char(1) NOT NULL default 'y',
			m_field_required char(1) NOT NULL default 'n',
			m_field_public char(1) NOT NULL default 'y',
			m_field_reg char(1) NOT NULL default 'n',
			m_field_cp_reg char(1) NOT NULL default 'n',
			m_field_fmt varchar(40) NOT NULL default 'none',
			m_field_show_fmt char(1) NOT NULL default 'y',
			m_field_exclude_from_anon char(1) NOT NULL default 'n',
			m_field_order int(3) unsigned NULL DEFAULT NULL,
			m_field_text_direction char(3) DEFAULT 'ltr',
			m_field_settings text NULL,
			m_legacy_field_data char(1) NOT NULL default 'n',
			PRIMARY KEY `m_field_id` (`m_field_id`)
			)";

        // Member Data
        // Stores the actual data

        $Q[] = "CREATE TABLE exp_member_data (
			member_id int(10) unsigned NOT NULL,
			PRIMARY KEY `member_id` (`member_id`)
		)";

        // Changelog view tracking for members

        $Q[] = "CREATE TABLE exp_member_news_views (
			news_id int(10) unsigned NOT NULL auto_increment,
			version varchar(10) NULL,
			member_id int(10) unsigned NOT NULL DEFAULT '0',
			PRIMARY KEY `news_id` (`news_id`),
			KEY `member_id` (`member_id`)
		)";

        // Channel Table

        // @confirm: I changed comment_max_chars from a NULL default to 5000 - DA

        $Q[] = "CREATE TABLE exp_channels (
			channel_id int(6) unsigned NOT NULL auto_increment,
			site_id INT(4) UNSIGNED NOT NULL DEFAULT 1,
			channel_name varchar(40) NOT NULL,
			channel_title varchar(100) NOT NULL,
			channel_url varchar(100) NOT NULL,
			channel_description varchar(255) NULL DEFAULT NULL,
			channel_lang varchar(12) NOT NULL,
			total_entries mediumint(8) default '0' NOT NULL,
			total_records mediumint(8) unsigned NOT NULL DEFAULT '0',
			total_comments mediumint(8) default '0' NOT NULL,
			last_entry_date int(10) unsigned default '0' NOT NULL,
			last_comment_date int(10) unsigned default '0' NOT NULL,
			cat_group varchar(255) NULL DEFAULT NULL,
			deft_status varchar(50) NOT NULL default 'open',
			search_excerpt int(4) unsigned NULL DEFAULT NULL,
			deft_category varchar(60) NULL DEFAULT NULL,
			deft_comments char(1) NOT NULL default 'y',
			channel_require_membership char(1) NOT NULL default 'y',
			channel_max_chars int(5) unsigned NULL DEFAULT NULL,
			channel_html_formatting char(4) NOT NULL default 'all',
			channel_allow_img_urls char(1) NOT NULL default 'y',
			channel_auto_link_urls char(1) NOT NULL default 'n',
			channel_notify char(1) NOT NULL default 'n',
			channel_notify_emails varchar(255) NULL DEFAULT NULL,
			sticky_enabled char(1) NOT NULL default 'n',
			enable_entry_cloning char(1) NOT NULL default 'y',
			comment_url varchar(80) NULL DEFAULT NULL,
			comment_system_enabled char(1) NOT NULL default 'y',
			comment_require_membership char(1) NOT NULL default 'n',
			comment_moderate char(1) NOT NULL default 'n',
			comment_max_chars int(5) unsigned NULL DEFAULT '5000',
			comment_timelock int(5) unsigned NOT NULL default '0',
			comment_require_email char(1) NOT NULL default 'y',
			comment_text_formatting char(40) NOT NULL default 'xhtml',
			comment_html_formatting char(4) NOT NULL default 'safe',
			comment_allow_img_urls char(1) NOT NULL default 'n',
			comment_auto_link_urls char(1) NOT NULL default 'y',
			comment_notify char(1) NOT NULL default 'n',
			comment_notify_authors char(1) NOT NULL default 'n',
			comment_notify_emails varchar(255) NULL DEFAULT NULL,
			comment_expiration int(4) unsigned NOT NULL default '0',
			search_results_url varchar(80) NULL DEFAULT NULL,
			rss_url varchar(80) NULL DEFAULT NULL,
			enable_versioning char(1) NOT NULL default 'n',
			max_revisions smallint(4) unsigned NOT NULL default 10,
			default_entry_title varchar(100) NULL DEFAULT NULL,
			title_field_label varchar(100) NOT NULL DEFAULT 'Title',
			title_field_instructions TEXT NULL,
			url_title_prefix varchar(80) NULL DEFAULT NULL,
			enforce_auto_url_title char(1) NOT NULL default 'n',
			preview_url varchar(100) NULL DEFAULT NULL,
			allow_preview char(1) NOT NULL default 'y',
			max_entries int(10) unsigned NOT NULL DEFAULT '0',
			conditional_sync_required char(1) NOT NULL default 'n',
			PRIMARY KEY `channel_id` (`channel_id`),
			KEY `cat_group` (`cat_group`(191)),
			KEY `channel_name` (`channel_name`),
			KEY `site_id` (`site_id`)
		)";

        // Channel Titles
        // We store channel titles separately from channel data

        $Q[] = "CREATE TABLE exp_channel_titles (
			entry_id int(10) unsigned NOT NULL auto_increment,
			site_id INT(4) UNSIGNED NOT NULL DEFAULT 1,
			channel_id int(4) unsigned NOT NULL,
			author_id int(10) unsigned NOT NULL default 0,
			forum_topic_id int(10) unsigned NULL DEFAULT NULL,
			ip_address varchar(45) default '0' NOT NULL,
			title varchar(200) NOT NULL,
			url_title varchar(" . URL_TITLE_MAX_LENGTH . ") NOT NULL,
			status varchar(50) NOT NULL,
			status_id int(4) unsigned NOT NULL,
			versioning_enabled char(1) NOT NULL default 'n',
			view_count_one int(10) unsigned NOT NULL default 0,
			view_count_two int(10) unsigned NOT NULL default 0,
			view_count_three int(10) unsigned NOT NULL default 0,
			view_count_four int(10) unsigned NOT NULL default 0,
			allow_comments varchar(1) NOT NULL default 'y',
			sticky varchar(1) NOT NULL default 'n',
			entry_date int(10) NOT NULL,
			year char(4) NOT NULL,
			month char(2) NOT NULL,
			day char(3) NOT NULL,
			expiration_date int(10) NOT NULL default 0,
			comment_expiration_date int(10) NOT NULL default 0,
			edit_date bigint(14),
			recent_comment_date int(10) NULL DEFAULT NULL,
			comment_total int(4) unsigned NOT NULL default 0,
			PRIMARY KEY `entry_id` (`entry_id`),
			KEY `channel_id` (`channel_id`),
			KEY `author_id` (`author_id`),
			KEY `url_title` (`url_title`(191)),
			KEY `status` (`status`),
			KEY `entry_date` (`entry_date`),
			KEY `expiration_date` (`expiration_date`),
			KEY `site_id` (`site_id`),
			KEY `sticky_date_id_idx` (`sticky`,`entry_date`,`entry_id`)
		)";

        // Channel Titles Autosave
        // Used for the autosave functionality
        $Q[] = "CREATE TABLE exp_channel_entries_autosave (
			entry_id int(10) unsigned NOT NULL auto_increment,
			original_entry_id int(10) unsigned NOT NULL,
			site_id INT(4) UNSIGNED NOT NULL DEFAULT 1,
			channel_id int(4) unsigned NOT NULL,
			author_id int(10) unsigned NOT NULL default 0,
			forum_topic_id int(10) unsigned NULL DEFAULT NULL,
			ip_address varchar(45) default '0' NOT NULL,
			title varchar(100) NOT NULL,
			url_title varchar(" . URL_TITLE_MAX_LENGTH . ") NOT NULL,
			status varchar(50) NOT NULL,
			versioning_enabled char(1) NOT NULL default 'n',
			view_count_one int(10) unsigned NOT NULL default 0,
			view_count_two int(10) unsigned NOT NULL default 0,
			view_count_three int(10) unsigned NOT NULL default 0,
			view_count_four int(10) unsigned NOT NULL default 0,
			allow_comments varchar(1) NOT NULL default 'y',
			sticky varchar(1) NOT NULL default 'n',
			entry_date int(10) NOT NULL,
			year char(4) NOT NULL,
			month char(2) NOT NULL,
			day char(3) NOT NULL,
			expiration_date int(10) NOT NULL default 0,
			comment_expiration_date int(10) NOT NULL default 0,
			edit_date bigint(14),
			recent_comment_date int(10) NULL DEFAULT NULL,
			comment_total int(4) unsigned NOT NULL default 0,
			entry_data text NULL,
			PRIMARY KEY `entry_id` (`entry_id`),
			KEY `channel_id` (`channel_id`),
			KEY `author_id` (`author_id`),
			KEY `url_title` (`url_title`(191)),
			KEY `status` (`status`),
			KEY `entry_date` (`entry_date`),
			KEY `expiration_date` (`expiration_date`),
			KEY `site_id` (`site_id`)
		)";

        $Q[] = "CREATE TABLE exp_entry_versioning (
			version_id int(10) unsigned NOT NULL auto_increment,
			entry_id int(10) unsigned NOT NULL,
			channel_id int(4) unsigned NOT NULL,
			author_id int(10) unsigned NOT NULL,
			version_date int(10) NOT NULL,
			version_data mediumtext NOT NULL,
			PRIMARY KEY `version_id` (`version_id`),
			KEY `entry_id` (`entry_id`)
		)";

        // Channel Custom Field Groups

        $Q[] = "CREATE TABLE exp_field_groups (
			group_id int(4) unsigned NOT NULL auto_increment,
			site_id INT(4) UNSIGNED NULL DEFAULT 1,
			group_name varchar(50) NOT NULL,
			short_name varchar(50) NOT NULL,
			group_description text NULL,
			PRIMARY KEY `group_id` (`group_id`),
			KEY `site_id` (`site_id`)
		)";

        $Q[] = "CREATE TABLE exp_channels_channel_field_groups (
			channel_id int(4) unsigned NOT NULL,
			group_id int(4) unsigned NOT NULL,
			PRIMARY KEY `channel_id_group_id` (`channel_id`, `group_id`)
		)";

        // Channel Custom Field Definitions

        $Q[] = "CREATE TABLE exp_channel_fields (
			field_id int(6) unsigned NOT NULL auto_increment,
			site_id INT(4) UNSIGNED NULL DEFAULT 1,
			field_name varchar(32) NOT NULL,
			field_label varchar(50) NOT NULL,
			field_instructions TEXT NULL,
			field_type varchar(50) NOT NULL default 'text',
			field_list_items text NOT NULL,
			field_pre_populate char(1) NOT NULL default 'n',
			field_pre_channel_id int(6) unsigned NULL DEFAULT NULL,
			field_pre_field_id int(6) unsigned NULL DEFAULT NULL,
			field_ta_rows tinyint(2) default '8',
			field_maxl smallint(3) NULL DEFAULT NULL,
			field_required char(1) NOT NULL default 'n',
			field_text_direction CHAR(3) NOT NULL default 'ltr',
			field_search char(1) NOT NULL default 'n',
			field_is_hidden char(1) NOT NULL default 'n',
			field_is_conditional char(1) NOT NULL default 'n',
			field_fmt varchar(40) NOT NULL default 'xhtml',
			field_show_fmt char(1) NOT NULL default 'y',
			field_order int(3) unsigned NOT NULL,
			field_content_type varchar(20) NOT NULL default 'any',
			field_settings text NULL,
			legacy_field_data char(1) NOT NULL default 'n',
			enable_frontedit char(1) NOT NULL default 'y',
			PRIMARY KEY `field_id` (`field_id`),
			KEY `field_type` (`field_type`),
			KEY `site_id` (`site_id`)
		)";

        $Q[] = "CREATE TABLE exp_channels_channel_fields (
			channel_id int(4) unsigned NOT NULL,
			field_id int(6) unsigned NOT NULL,
			PRIMARY KEY `channel_id_field_id` (`channel_id`, `field_id`)
		)";

        $Q[] = "CREATE TABLE exp_channel_field_groups_fields (
			field_id int(6) unsigned NOT NULL,
			group_id int(4) unsigned NOT NULL,
			PRIMARY KEY `field_id_group_id` (`field_id`, `group_id`)
		)";

        $Q[] = "CREATE TABLE exp_channels_statuses (
			channel_id int(4) unsigned NOT NULL,
			status_id int(4) unsigned NOT NULL,
			PRIMARY KEY `channel_id_status_id` (`channel_id`, `status_id`)
		)";

        $Q[] = "CREATE TABLE exp_channel_category_groups (
			channel_id int(6) unsigned NOT NULL,
			group_id int(6) unsigned NOT NULL,
			PRIMARY KEY `channel_id_group_id` (`channel_id`, `group_id`),
			KEY `group_id` (`group_id`)
		)";

        // Frontend Channel Form Settings

        $Q[] = "CREATE TABLE `exp_channel_form_settings` (
			`channel_form_settings_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`site_id` int(4) unsigned NOT NULL DEFAULT '0',
			`channel_id` int(6) unsigned NOT NULL DEFAULT '0',
			`default_status` varchar(50) NOT NULL DEFAULT 'open',
			`allow_guest_posts` char(1) NOT NULL DEFAULT 'n',
			`default_author` int(11) NOT NULL DEFAULT '0',
			PRIMARY KEY `channel_form_settings_id` (`channel_form_settings_id`),
			KEY `site_id` (`site_id`),
			KEY `channel_id` (`channel_id`)
		)";

        // Relationships table

        $Q[] = "CREATE TABLE exp_relationships (
			relationship_id int(6) UNSIGNED NOT NULL auto_increment,
			parent_id int(10) UNSIGNED NOT NULL default 0,
			child_id int(10) UNSIGNED NOT NULL default 0,
			field_id int(10) UNSIGNED NOT NULL default 0,
			fluid_field_data_id int(10) UNSIGNED NOT NULL default 0,
			grid_field_id int(10) UNSIGNED NOT NULL default 0,
			grid_col_id int(10) UNSIGNED NOT NULL default 0,
			grid_row_id int(10) UNSIGNED NOT NULL default 0,
			`order` int(10) UNSIGNED NOT NULL default 0,
			PRIMARY KEY `relationship_id` (`relationship_id`),
			KEY `parent_id` (`parent_id`),
			KEY `child_id` (`child_id`),
			KEY `field_id` (`field_id`),
			KEY `fluid_field_data_id` (`fluid_field_data_id`),
			KEY `grid_row_id` (`grid_row_id`)
		)";

        // Member Relationships table

        $Q[] = "CREATE TABLE exp_member_relationships (
			relationship_id int(6) UNSIGNED NOT NULL auto_increment,
			parent_id int(10) UNSIGNED NOT NULL default 0,
			child_id int(10) UNSIGNED NOT NULL default 0,
			field_id int(10) UNSIGNED NOT NULL default 0,
			fluid_field_data_id int(10) UNSIGNED NOT NULL default 0,
			grid_field_id int(10) UNSIGNED NOT NULL default 0,
			grid_col_id int(10) UNSIGNED NOT NULL default 0,
			grid_row_id int(10) UNSIGNED NOT NULL default 0,
			`order` int(10) UNSIGNED NOT NULL default 0,
			PRIMARY KEY `relationship_id` (`relationship_id`),
			KEY `parent_id` (`parent_id`),
			KEY `child_id` (`child_id`),
			KEY `field_id` (`field_id`),
			KEY `fluid_field_data_id` (`fluid_field_data_id`),
			KEY `grid_row_id` (`grid_row_id`)
		)";

        // Channel data
        $Q[] = "CREATE TABLE exp_channel_data (
			entry_id int(10) unsigned NOT NULL,
			site_id INT(4) UNSIGNED NOT NULL DEFAULT 1,
			channel_id int(4) unsigned NOT NULL,
			PRIMARY KEY `entry_id` (`entry_id`),
			KEY `channel_id` (`channel_id`),
			KEY `site_id` (`site_id`)
		)";

        // Status data

        $Q[] = "CREATE TABLE exp_statuses (
			status_id int(6) unsigned NOT NULL auto_increment,
			status varchar(50) NOT NULL,
			status_order int(3) unsigned NOT NULL,
			highlight varchar(30) NOT NULL default '000000',
			PRIMARY KEY `status_id` (`status_id`)
		)";

        $Q[] = "CREATE TABLE exp_statuses_roles (
			role_id int(10) unsigned NOT NULL,
			status_id int(6) unsigned NOT NULL,
			PRIMARY KEY `status_id_role_id` (`status_id`, `role_id`)
		)";

        // Category Groups

        $Q[] = "CREATE TABLE exp_category_groups (
			group_id int(6) unsigned NOT NULL auto_increment,
			site_id INT(4) UNSIGNED NOT NULL DEFAULT 1,
			group_name varchar(50) NOT NULL,
			sort_order char(1) NOT NULL default 'a',
			exclude_group TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
			`field_html_formatting` char(4) NOT NULL default 'all',
			`can_edit_categories` TEXT NULL,
			`can_delete_categories` TEXT NULL,
			PRIMARY KEY `group_id` (`group_id`),
			KEY `site_id` (`site_id`)
		)";

        // Category Group Settings

        $Q[] = "CREATE TABLE exp_category_group_settings (
			category_group_settings_id int(10) unsigned NOT NULL auto_increment,
			site_id INT(4) UNSIGNED NOT NULL DEFAULT 1,
			channel_id INT(4) UNSIGNED NOT NULL DEFAULT 1,
			group_id INT(6) UNSIGNED NOT NULL DEFAULT 1,
			cat_required char(1) NOT NULL default 'n',
			cat_allow_multiple char(1) NOT NULL default 'y',
			PRIMARY KEY `category_group_settings_id` (`category_group_settings_id`),
			KEY `channel_id_group_id` (`channel_id`, `group_id`),
			KEY `site_id` (`site_id`),
			KEY `group_id` (`group_id`)
		)";

        // Category data

        $Q[] = "CREATE TABLE exp_categories (
			cat_id int(10) unsigned NOT NULL auto_increment,
			site_id INT(4) UNSIGNED NOT NULL DEFAULT 1,
			group_id int(6) unsigned NOT NULL,
			parent_id int(4) unsigned NOT NULL,
			cat_name varchar(100) NOT NULL,
			`cat_url_title` varchar(75) NOT NULL,
			cat_description text NULL,
			cat_image varchar(120) NULL,
			cat_order int(4) unsigned NOT NULL,
			PRIMARY KEY `cat_id` (`cat_id`),
			KEY `group_id` (`group_id`),
			KEY `parent_id` (`parent_id`),
			KEY `cat_name` (`cat_name`),
			KEY `site_id` (`site_id`)
		)";

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
			`field_settings` text NULL,
			`legacy_field_data` char(1) NOT NULL default 'n',
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

        // Category posts
        // This table stores the channel entry ID and the category IDs
        // that are assigned to it

        $Q[] = "CREATE TABLE exp_category_posts (
			entry_id int(10) unsigned NOT NULL,
			cat_id int(10) unsigned NOT NULL,
			PRIMARY KEY `entry_id_cat_id` (`entry_id`, `cat_id`)
		)";

        // Control panel log

        $Q[] = "CREATE TABLE exp_cp_log (
			id int(10) NOT NULL auto_increment,
			site_id INT(4) UNSIGNED NOT NULL DEFAULT 1,
			member_id int(10) unsigned NOT NULL,
			username varchar(" . USERNAME_MAX_LENGTH . ") NOT NULL,
			ip_address varchar(45) default '0' NOT NULL,
			act_date int(10) NOT NULL,
			action text NOT NULL,
			PRIMARY KEY `id` (`id`),
			KEY `site_id` (`site_id`)
		)";

        // HTML buttons
        // These are the buttons that appear on the PUBLISH page.
        // Each member can have their own set of buttons

        $Q[] = "CREATE TABLE exp_html_buttons (
			id int(10) unsigned NOT NULL auto_increment,
			site_id INT(4) UNSIGNED NOT NULL DEFAULT 1,
			member_id int(10) default 0 NOT NULL,
			tag_name varchar(32) NOT NULL,
			tag_open varchar(120) NOT NULL,
			tag_close varchar(120) NOT NULL,
			accesskey varchar(32) NOT NULL,
			tag_order int(3) unsigned NOT NULL,
			tag_row char(1) NOT NULL default 1,
			classname varchar(20) NULL DEFAULT NULL,
			PRIMARY KEY `id` (`id`),
			KEY `site_id` (`site_id`)
		)";

        // Layout Publish
        // Custom layout for for the publish page.
        $Q[] = "CREATE TABLE exp_layout_publish (
			layout_id int(10) UNSIGNED NOT NULL auto_increment,
			site_id int(4) UNSIGNED NOT NULL default 1,
			channel_id int(4) UNSIGNED NOT NULL default 0,
			layout_name varchar(50) NOT NULL,
			field_layout text,
			PRIMARY KEY(`layout_id`),
			KEY `site_id` (`site_id`),
			KEY `channel_id` (`channel_id`)
		)";

        $Q[] = "CREATE TABLE exp_layout_publish_member_roles (
			layout_id int(10) UNSIGNED NOT NULL,
			role_id int(10) UNSIGNED NOT NULL,
			PRIMARY KEY `layout_id_role_id` (`layout_id`, `role_id`)
		)";

        // Template Groups

        $Q[] = "CREATE TABLE exp_template_groups (
			group_id int(6) unsigned NOT NULL auto_increment,
			site_id INT(4) UNSIGNED NOT NULL DEFAULT 1,
			group_name varchar(50) NOT NULL,
			group_order int(3) unsigned NOT NULL,
			is_site_default char(1) NOT NULL default 'n',
			PRIMARY KEY `group_id` (`group_id`),
			KEY `site_id` (`site_id`),
			KEY `group_name_idx` (`group_name`),
			KEY `group_order_idx` (`group_order`)
		)";

        // Template data

        $Q[] = "CREATE TABLE exp_templates (
			template_id int(10) unsigned NOT NULL auto_increment,
			site_id INT(4) UNSIGNED NOT NULL DEFAULT 1,
			group_id int(6) unsigned NOT NULL,
			template_name varchar(50) NOT NULL,
			template_type varchar(16) NOT NULL default 'webpage',
            template_engine varchar(24) DEFAULT NULL,
			template_data mediumtext NULL,
			template_notes text NULL,
			edit_date int(10) NOT NULL DEFAULT 0,
			last_author_id int(10) UNSIGNED NOT NULL default 0,
			cache char(1) NOT NULL default 'n',
			refresh int(6) unsigned NOT NULL default 0,
			no_auth_bounce varchar(50) NOT NULL default '',
			enable_http_auth CHAR(1) NOT NULL default 'n',
			allow_php char(1) NOT NULL default 'n',
			php_parse_location char(1) NOT NULL default 'o',
			hits int(10) unsigned NOT NULL default 0,
			protect_javascript char(1) NOT NULL default 'n',
			enable_frontedit char(1) NOT NULL default 'y',
			PRIMARY KEY `template_id` (`template_id`),
			KEY `group_id` (`group_id`),
			KEY `template_name` (`template_name`),
			KEY `site_id` (`site_id`)
		)";

        // Template Routes

        $Q[] = "CREATE TABLE `exp_template_routes` (
			`route_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`template_id` int(10) unsigned NOT NULL,
			`order` int(10) unsigned DEFAULT NULL,
			`route` varchar(512) DEFAULT NULL,
			`route_parsed` varchar(512) DEFAULT NULL,
			`route_required` char(1) NOT NULL DEFAULT 'n',
			PRIMARY KEY (`route_id`),
			KEY `template_id` (`template_id`)
		)";

        $Q[] = "CREATE TABLE exp_templates_roles (
			role_id int(10) unsigned NOT NULL,
			template_id int(10) unsigned NOT NULL,
			PRIMARY KEY `template_id_role_id` (`template_id`, `role_id`)
		)";

        // Specialty Templates
        // This table contains the various specialty templates, like:
        // Admin notification of new members
        // Admin notification of comments
        // Membership activation instruction
        // Member lost password instructions
        // Validated member notification

        $Q[] = "CREATE TABLE exp_specialty_templates (
			template_id int(6) unsigned NOT NULL auto_increment,
			site_id INT(4) UNSIGNED NOT NULL DEFAULT 1,
			enable_template char(1) NOT NULL default 'y',
			template_name varchar(50) NOT NULL,
			data_title varchar(80) NOT NULL,
			template_type varchar(16) NULL,
			template_subtype varchar(16) NULL,
			template_data text NOT NULL,
			template_notes text NULL,
			edit_date int(10) NOT NULL DEFAULT 0,
			last_author_id int(10) UNSIGNED NOT NULL default 0,
			PRIMARY KEY `template_id` (`template_id`),
			KEY `template_name` (`template_name`),
			KEY `site_id` (`site_id`)
		)";

        // Global variables
        // These are user-definable variables

        $Q[] = "CREATE TABLE exp_global_variables (
			variable_id int(6) unsigned NOT NULL auto_increment,
			site_id INT(4) UNSIGNED NOT NULL DEFAULT 1,
			variable_name varchar(50) NOT NULL,
			variable_data text NOT NULL,
			`edit_date` int(10) NOT NULL DEFAULT 0,
			PRIMARY KEY `variable_id` (`variable_id`),
			KEY `variable_name` (`variable_name`),
			KEY `site_id` (`site_id`)
		)";

        // Snippets
        // These are user-definable early-parsed variables
        // for holding dynamic content

        $Q[] = "CREATE TABLE `exp_snippets` (
			`snippet_id` int(10) unsigned NOT NULL auto_increment,
			`site_id` int(4) NOT NULL,
			`snippet_name` varchar(75) NOT NULL,
			`snippet_contents` text NULL,
			`edit_date` int(10) NOT NULL DEFAULT 0,
			PRIMARY KEY (`snippet_id`),
			KEY `site_id` (`site_id`)
		)";

        // Revision tracker
        // This is our versioning table, used to store each
        // change that is made to a template.

        $Q[] = "CREATE TABLE exp_revision_tracker (
			tracker_id int(10) unsigned NOT NULL auto_increment,
			item_id int(10) unsigned NOT NULL,
			item_table varchar(50) NOT NULL,
			item_field varchar(32) NOT NULL,
			item_date int(10) NOT NULL,
			item_author_id int(10) UNSIGNED NOT NULL,
			item_data mediumtext NOT NULL,
			PRIMARY KEY `tracker_id` (`tracker_id`),
			KEY `item_id` (`item_id`)
		)";

        // Upload preferences

        $Q[] = "CREATE TABLE exp_upload_prefs (
			id int(4) unsigned NOT NULL auto_increment,
			site_id INT(4) UNSIGNED NOT NULL DEFAULT 1,
			name varchar(50) NOT NULL,
			adapter varchar(50) NOT NULL DEFAULT 'local',
			adapter_settings text NULL DEFAULT NULL,
			server_path varchar(255) NOT NULL default '',
			url varchar(100) NOT NULL,
			allowed_types varchar(100) NOT NULL default 'img',
			allow_subfolders ENUM('y','n') NOT NULL DEFAULT 'n',
			subfolders_on_top ENUM('y','n') NOT NULL DEFAULT 'y',
			default_modal_view varchar(5) NOT NULL default 'list',
			max_size varchar(16) NULL DEFAULT NULL,
			max_height varchar(6) NULL DEFAULT NULL,
			max_width varchar(6) NULL DEFAULT NULL,
			properties varchar(120) NULL DEFAULT NULL,
			pre_format varchar(120) NULL DEFAULT NULL,
			post_format varchar(120) NULL DEFAULT NULL,
			file_properties varchar(120) NULL DEFAULT NULL,
			file_pre_format varchar(120) NULL DEFAULT NULL,
			file_post_format varchar(120) NULL DEFAULT NULL,
			cat_group varchar(255) NULL DEFAULT NULL,
			batch_location varchar(255) NULL DEFAULT NULL,
			module_id int(4) NOT NULL DEFAULT 0,
			PRIMARY KEY `id` (`id`),
			KEY `site_id` (`site_id`)
		)";

        $Q[] = "CREATE TABLE exp_upload_prefs_category_groups (
			upload_location_id int(4) unsigned NOT NULL,
			group_id int(6) unsigned NOT NULL,
			PRIMARY KEY `upload_location_id_group_id` (`upload_location_id`, `group_id`),
			KEY `group_id` (`group_id`)
		)";

        // Upload "no access"
        // We store the member groups that can not access various upload destinations

        $Q[] = "CREATE TABLE exp_upload_prefs_roles (
			role_id int(10) unsigned NOT NULL,
			upload_id int(4) unsigned NOT NULL,
			PRIMARY KEY `upload_id_role_id` (`upload_id`, `role_id`)
		)";

        // Private messaging tables

        $Q[] = "CREATE TABLE exp_message_attachments (
			attachment_id int(10) unsigned NOT NULL auto_increment,
			sender_id int(10) unsigned NOT NULL default 0,
			message_id int(10) unsigned NOT NULL default 0,
			attachment_name varchar(50) NOT NULL default '',
			attachment_hash varchar(40) NOT NULL default '',
			attachment_extension varchar(20) NOT NULL default '',
			attachment_location varchar(150) NOT NULL default '',
			attachment_date int(10) unsigned NOT NULL default 0,
			attachment_size int(10) unsigned NOT NULL default 0,
			is_temp char(1) NOT NULL default 'y',
			PRIMARY KEY `attachment_id` (`attachment_id`)
		)";

        $Q[] = "CREATE TABLE exp_message_copies (
			copy_id int(10) unsigned NOT NULL auto_increment,
			message_id int(10) unsigned NOT NULL default 0,
			sender_id int(10) unsigned NOT NULL default 0,
			recipient_id int(10) unsigned NOT NULL default 0,
			message_received char(1) NOT NULL default 'n',
			message_read char(1) NOT NULL default 'n',
			message_time_read int(10) unsigned NOT NULL default 0,
			attachment_downloaded char(1) NOT NULL default 'n',
			message_folder int(10) unsigned NOT NULL default 1,
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
			sender_id int(10) unsigned NOT NULL default 0,
			message_date int(10) unsigned NOT NULL default 0,
			message_subject varchar(255) NOT NULL default '',
			message_body text NOT NULL,
			message_tracking char(1) NOT NULL default 'y',
			message_attachments char(1) NOT NULL default 'n',
			message_recipients varchar(200) NOT NULL default '',
			message_cc varchar(200) NOT NULL default '',
			message_hide_cc char(1) NOT NULL default 'n',
			message_sent_copy char(1) NOT NULL default 'n',
			total_recipients int(5) unsigned NOT NULL default 0,
			message_status varchar(25) NOT NULL default '',
			PRIMARY KEY `message_id` (`message_id`),
			KEY `sender_id` (`sender_id`)
		)";

        $Q[] = "CREATE TABLE exp_message_folders (
			member_id int(10) unsigned NOT NULL default 0,
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
			PRIMARY KEY `member_id` (`member_id`)
		)";

        $Q[] = "CREATE TABLE exp_message_listed (
			listed_id int(10) unsigned NOT NULL auto_increment,
			member_id int(10) unsigned NOT NULL default 0,
			listed_member int(10) unsigned NOT NULL default 0,
			listed_description varchar(100) NOT NULL default '',
			listed_type varchar(10) NOT NULL default 'blocked',
			PRIMARY KEY `listed_id` (`listed_id`)
		)";

        $Q[] = "CREATE TABLE `exp_extensions` (
			`extension_id` int(10) unsigned NOT NULL auto_increment,
			`class` varchar(50) NOT NULL default '',
			`method` varchar(50) NOT NULL default '',
			`hook` varchar(50) NOT NULL default '',
			`settings` text NOT NULL,
			`priority` int(2) NOT NULL default '10',
			`version` varchar(10) NOT NULL default '',
			`enabled` char(1) NOT NULL default 'y',
			PRIMARY KEY `extension_id` (`extension_id`),
			KEY `enabled` (`enabled`)
		)";

        $Q[] = "CREATE TABLE `exp_member_search`(
			`search_id` varchar(32) NOT NULL,
			`site_id` INT(4) UNSIGNED NOT NULL DEFAULT 1,
			`search_date` int(10) unsigned NOT NULL,
			`keywords` varchar(200) NOT NULL,
			`fields` varchar(200) NOT NULL,
			`member_id` int(10) unsigned NOT NULL,
			`ip_address` varchar(45) default '0' NOT NULL,
			`total_results` int(8) unsigned NOT NULL,
			`query` text NOT NULL,
			PRIMARY KEY `search_id` (`search_id`),
			KEY `member_id` (`member_id`),
			KEY `site_id` (`site_id`)
		)";

        $Q[] = "CREATE TABLE `exp_member_bulletin_board` (
			`bulletin_id` int(10) unsigned NOT NULL auto_increment,
			`sender_id` int(10) unsigned NOT NULL,
			`bulletin_group` int(8) unsigned NOT NULL,
			`bulletin_date` int(10) unsigned NOT NULL,
			`hash` varchar(10) NOT NULL DEFAULT '',
			`bulletin_expires` int(10) unsigned NOT NULL DEFAULT 0,
			`bulletin_message` text NOT NULL,
			PRIMARY KEY `bulletin_id` (`bulletin_id`),
			KEY `sender_id` (`sender_id`),
			KEY `hash` (`hash`)
		)";

        // Entity type table
        $Q[] = "CREATE TABLE `exp_content_types` (
			`content_type_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`name` varchar(50) NOT NULL DEFAULT '',
			PRIMARY KEY (`content_type_id`),
			KEY `name` (`name`)
		)";

        // Fieldtype table
        $Q[] = "CREATE TABLE exp_fieldtypes (
			fieldtype_id int(4) unsigned NOT NULL auto_increment,
			name varchar(50) NOT NULL,
			version varchar(12) NOT NULL,
			settings text NULL,
			has_global_settings char(1) default 'n',
			PRIMARY KEY `fieldtype_id` (`fieldtype_id`)
		)";

        // Field conditions
        $Q[] = "CREATE TABLE `exp_field_conditions` (
			`condition_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`condition_set_id` int(10) unsigned NOT NULL,
			`condition_field_id` int(10) unsigned NOT NULL,
			`evaluation_rule` varchar(100) NOT NULL DEFAULT '',
			`value` varchar(255) DEFAULT NULL,
			`order` int(10) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY `condition_id` (`condition_id`),
			KEY `condition_set_id` (`condition_set_id`),
			KEY `condition_field_id` (`condition_field_id`)
		)";

        $Q[] = "CREATE TABLE `exp_field_condition_sets` (
			`condition_set_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`match` varchar(20) NOT NULL DEFAULT 'all',
			`order` int(10) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY `condition_set_id` (`condition_set_id`)
		)";

        $Q[] = "CREATE TABLE exp_field_condition_sets_channel_fields (
			`condition_set_id` int(10) unsigned NOT NULL,
			`field_id` int(10) unsigned NOT NULL,
			PRIMARY KEY `condition_set_id_field_id` (`condition_set_id`, `field_id`)
		)";

        $Q[] = "CREATE TABLE exp_channel_entry_hidden_fields (
			`entry_id` int(10) unsigned NOT NULL,
			`field_id` int(10) unsigned NOT NULL,
			PRIMARY KEY `entry_id_field_id` (`entry_id`, `field_id`)
		)";


        // Files table
        $Q[] = "CREATE TABLE `exp_files` (
			`file_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`model_type` ENUM('File','Directory') NOT NULL DEFAULT 'File',
			`site_id` int(4) unsigned DEFAULT '1',
			`title` varchar(255) DEFAULT NULL,
			`upload_location_id` int(4) unsigned DEFAULT '0',
			`directory_id` int(10) unsigned DEFAULT '0',
			`mime_type` varchar(255) DEFAULT NULL,
			`file_type` varchar(50) DEFAULT NULL,
			`file_name` varchar(255) DEFAULT NULL,
			`file_size` int(10) DEFAULT '0',
			`description` text,
			`credit` varchar(255) DEFAULT NULL,
			`location` varchar(255) DEFAULT NULL,
			`uploaded_by_member_id` int(10) unsigned DEFAULT '0',
			`upload_date` int(10) DEFAULT NULL,
			`modified_by_member_id` int(10) unsigned DEFAULT '0',
			`modified_date` int(10) DEFAULT NULL,
			`file_hw_original` varchar(20) NOT NULL DEFAULT '',
			`total_records` int(10) unsigned DEFAULT '0',
			PRIMARY KEY (`file_id`),
			KEY `model_type` (`model_type`),
			KEY `upload_location_id` (`upload_location_id`),
			KEY `directory_id` (`directory_id`),
			KEY `file_type` (`file_type`),
			KEY `site_id` (`site_id`)
		)";

        $Q[] = "CREATE TABLE `exp_file_categories` (
			`file_id` int(10) unsigned NOT NULL,
			`cat_id` int(10) unsigned NOT NULL,
			`sort` int(10) unsigned DEFAULT '0',
			`is_cover` char(1) DEFAULT 'n',
			PRIMARY KEY (`file_id`, `cat_id`),
			KEY `cat_id` (`cat_id`)
		)";

        $Q[] = "CREATE TABLE `exp_file_data` (
			`file_id` int(10) unsigned NOT NULL,
			PRIMARY KEY (`file_id`)
		)";

        $Q[] = "CREATE TABLE `exp_file_dimensions` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`site_id` int(4) unsigned NOT NULL DEFAULT '1',
			`upload_location_id` int(4) unsigned DEFAULT NULL,
			`title` varchar(255) DEFAULT '',
			`short_name` varchar(255) DEFAULT '',
			`resize_type` varchar(50) DEFAULT '',
			`width` int(10) DEFAULT '0',
			`height` int(10) DEFAULT '0',
			`quality` tinyint(1) unsigned DEFAULT '90',
			`watermark_id` int(4) unsigned DEFAULT NULL,
			PRIMARY KEY (`id`),
			KEY `upload_location_id` (`upload_location_id`)
		)";

        $Q[] = "CREATE TABLE `exp_file_usage` (
            `file_usage_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`file_id` int(10) unsigned NOT NULL,
			`entry_id` int(10) unsigned NOT NULL DEFAULT '0',
			`cat_id` int(10) unsigned NOT NULL DEFAULT '0',
            PRIMARY KEY (`file_usage_id`),
			KEY `file_id` (`file_id`),
			KEY `entry_id` (`entry_id`),
			KEY `cat_id` (`cat_id`)
		)";

        $Q[] = "CREATE TABLE `exp_file_watermarks` (
			`wm_id` int(4) unsigned NOT NULL AUTO_INCREMENT,
			`wm_name` varchar(80) DEFAULT NULL,
			`wm_type` varchar(10) DEFAULT 'text',
			`wm_image_path` varchar(255) DEFAULT NULL,
			`wm_test_image_path` varchar(255) DEFAULT NULL,
			`wm_use_font` char(1) DEFAULT 'y',
			`wm_font` varchar(30) DEFAULT NULL,
			`wm_font_size` int(3) unsigned DEFAULT NULL,
			`wm_text` varchar(100) DEFAULT NULL,
			`wm_vrt_alignment` varchar(10) DEFAULT 'top',
			`wm_hor_alignment` varchar(10) DEFAULT 'left',
			`wm_padding` int(3) unsigned DEFAULT NULL,
			`wm_opacity` int(3) unsigned DEFAULT NULL,
			`wm_hor_offset` int(4) unsigned DEFAULT NULL,
			`wm_vrt_offset` int(4) unsigned DEFAULT NULL,
			`wm_x_transp` int(4) DEFAULT NULL,
			`wm_y_transp` int(4) DEFAULT NULL,
			`wm_font_color` varchar(7) DEFAULT NULL,
			`wm_use_drop_shadow` char(1) DEFAULT 'y',
			`wm_shadow_distance` int(3) unsigned DEFAULT NULL,
			`wm_shadow_color` varchar(7) DEFAULT NULL,
			PRIMARY KEY (`wm_id`)
		)";

        // Developer log table
        $Q[] = "CREATE TABLE `exp_developer_log` (
			`log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`timestamp` int(10) unsigned NOT NULL,
			`viewed` char(1) NOT NULL DEFAULT 'n',
			`description` text NULL,
			`function` varchar(100) NULL,
			`line` int(10) unsigned NULL,
			`file` varchar(255) NULL,
			`deprecated_since` varchar(10) NULL,
			`use_instead` varchar(100) NULL,
			`template_id` int(10) unsigned NOT NULL default 0,
			`template_name` varchar(100) NULL,
			`template_group` varchar(100) NULL,
			`addon_module` varchar(100) NULL,
			`addon_method` varchar(100) NULL,
			`snippets` text NULL,
			`hash` char(32) NOT NULL,
			PRIMARY KEY (`log_id`)
		)";

        // Remember me table
        $Q[] = "CREATE TABLE `exp_remember_me` (
			`remember_me_id` varchar(40) NOT NULL DEFAULT '0',
			`member_id` int(10) DEFAULT '0',
			`ip_address` varchar(45) DEFAULT '0',
			`user_agent` varchar(120) DEFAULT '',
			`admin_sess` tinyint(1) DEFAULT '0',
			`site_id` int(4) DEFAULT '1',
			`expiration` int(10) DEFAULT '0',
			`last_refresh` int(10) DEFAULT '0',
			PRIMARY KEY (`remember_me_id`),
			KEY `member_id` (`member_id`)
		)";

        $Q[] = "CREATE TABLE `exp_grid_columns` (
			`col_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`field_id` int(10) unsigned DEFAULT NULL,
			`content_type` varchar(50) DEFAULT NULL,
			`col_order` int(3) unsigned DEFAULT NULL,
			`col_type` varchar(50) DEFAULT NULL,
			`col_label` varchar(50) DEFAULT NULL,
			`col_name` varchar(32) DEFAULT NULL,
			`col_instructions` text,
			`col_required` char(1) DEFAULT NULL,
			`col_search` char(1) DEFAULT NULL,
			`col_width` int(3) unsigned DEFAULT NULL,
			`col_settings` text,
			PRIMARY KEY (`col_id`),
			KEY `field_id` (`field_id`)
		)";

        $Q[] = "CREATE TABLE `exp_fluid_field_data` (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`fluid_field_id` int(11) unsigned NOT NULL,
			`entry_id` int(11) unsigned NOT NULL,
			`field_id` int(11) unsigned NOT NULL,
			`field_data_id` int(11) unsigned NOT NULL,
			`field_group_id` int(11) unsigned DEFAULT NULL,
			`order` int(5) unsigned NOT NULL DEFAULT '0',
			`group` int(11) unsigned DEFAULT NULL,
			PRIMARY KEY (`id`),
			KEY `fluid_field_id_entry_id` (`fluid_field_id`,`entry_id`)
		)";

        $Q[] = "CREATE TABLE `exp_menu_sets` (
  			`set_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  			`name` varchar(50) DEFAULT NULL,
  			PRIMARY KEY (`set_id`)
		)";

        $Q[] = "CREATE TABLE `exp_menu_items` (
			`item_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`parent_id` int(10) NOT NULL DEFAULT '0',
			`set_id` int(10) DEFAULT NULL,
			`name` varchar(50) DEFAULT NULL,
			`data` varchar(255) DEFAULT NULL,
			`type` varchar(10) DEFAULT NULL,
			`sort` int(5) NOT NULL DEFAULT '0',
			PRIMARY KEY (`item_id`),
			KEY `set_id` (`set_id`)
	  	)";

        $Q[] = "CREATE TABLE `exp_dashboard_layouts` (
  			`layout_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  			`member_id` int(10) unsigned DEFAULT NULL,
				`role_id` int(10) unsigned DEFAULT NULL,
				`order` varchar(255) DEFAULT NULL,
  			PRIMARY KEY (`layout_id`),
				KEY `member_id` (`member_id`),
				KEY `role_id` (`role_id`)
		)";

        $Q[] = "CREATE TABLE `exp_dashboard_layout_widgets` (
            `dashboard_layout_widgets_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`layout_id` int(10) unsigned NOT NULL,
			`widget_id` int(10) unsigned NOT NULL,
            PRIMARY KEY (`dashboard_layout_widgets_id`),
			KEY `layouts_widgets` (`layout_id`, `widget_id`)
		)";

        $Q[] = "CREATE TABLE `exp_dashboard_widgets` (
			`widget_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`widget_name` varchar(50) DEFAULT NULL,
			`widget_data` mediumtext NULL,
			`widget_type` varchar(10) NOT NULL,
			`widget_source` varchar(50) NOT NULL,
			`widget_file` varchar(100) DEFAULT NULL,
			PRIMARY KEY (`widget_id`)
	  	)";

		$Q[] = "CREATE TABLE `exp_cookie_settings` (
			`cookie_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`cookie_provider` varchar(50) NOT NULL,
			`cookie_name` varchar(50) NOT NULL,
			`cookie_lifetime` int(10) unsigned DEFAULT NULL,
			`cookie_enforced_lifetime` int(10) unsigned DEFAULT NULL,
			`cookie_title` varchar(200) NOT NULL,
			`cookie_description` text NULL,
			PRIMARY KEY (`cookie_id`)
		)";

        $Q[] = "CREATE TABLE `exp_consent_requests` (
			`consent_request_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`consent_request_version_id` int(10) unsigned DEFAULT NULL,
			`user_created` char(1) NOT NULL DEFAULT 'n',
			`title` varchar(200) NOT NULL,
			`consent_name` varchar(50) NOT NULL,
			`double_opt_in` char(1) NOT NULL DEFAULT 'n',
			`retention_period` varchar(32) DEFAULT NULL,
			PRIMARY KEY (`consent_request_id`)
		)";

        $Q[] = "CREATE TABLE `exp_consent_request_versions` (
			`consent_request_version_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`consent_request_id` int(10) unsigned NOT NULL,
			`request` mediumtext,
			`request_format` varchar(255) NOT NULL DEFAULT '',
			`create_date` int(10) NOT NULL DEFAULT '0',
			`author_id` int(10) unsigned NOT NULL DEFAULT '0',
			PRIMARY KEY (`consent_request_version_id`),
			KEY `consent_request_id` (`consent_request_id`)
		)";

        $Q[] = "CREATE TABLE `exp_consent_request_version_cookies` (
            `consent_request_version_cookies_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`consent_request_version_id` int(10) unsigned NOT NULL,
			`cookie_id` int(10) unsigned NOT NULL,
            PRIMARY KEY (`consent_request_version_cookies_id`),
			KEY `consent_request_version_cookies` (`consent_request_version_id`, `cookie_id`)
		)";

        $Q[] = "CREATE TABLE `exp_consents` (
			`consent_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`consent_request_id` int(10) unsigned NOT NULL,
			`consent_request_version_id` int(10) unsigned NOT NULL,
			`member_id` int(10) unsigned NOT NULL,
			`request_copy` mediumtext,
			`request_format` varchar(255) NOT NULL DEFAULT '',
			`consent_given` char(1) NOT NULL DEFAULT 'n',
			`consent_given_via` varchar(32) DEFAULT NULL,
			`expiration_date` int(10) DEFAULT NULL,
			`response_date` int(10) DEFAULT NULL,
			PRIMARY KEY (`consent_id`),
			KEY `consent_request_version_id` (`consent_request_version_id`),
			KEY `member_id` (`member_id`)
		)";

        $Q[] = "CREATE TABLE `exp_consent_audit_log` (
			`consent_audit_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`consent_request_id` int(10) unsigned NOT NULL,
			`consent_request_version_id` int(10) unsigned DEFAULT NULL,
			`member_id` int(10) unsigned NOT NULL,
			`ip_address` varchar(45) default '0' NOT NULL,
			`user_agent` varchar(120) NOT NULL,
			`action` text NOT NULL,
			`log_date` int(10) NOT NULL DEFAULT '0',
			PRIMARY KEY (`consent_audit_id`),
			KEY `consent_request_id` (`consent_request_id`),
			KEY `consent_request_version_id` (`consent_request_version_id`)
		)";

        $Q[] = "CREATE TABLE `exp_config` (
			`config_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`site_id` int(5) unsigned NOT NULL DEFAULT '0',
			`key` varchar(64) NOT NULL DEFAULT '',
			`value` text,
			PRIMARY KEY (`config_id`),
			KEY `site_key` (`site_id`,`key`)
		);";

        // entry manager
        $Q[] = "CREATE TABLE `exp_entry_manager_views` (
			`view_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`channel_id` int(6) unsigned NOT NULL,
			`member_id` int(10) unsigned NOT NULL,
			`name` varchar(128) NOT NULL DEFAULT '',
			`columns` text NOT NULL,
			PRIMARY KEY (`view_id`),
			KEY `channel_id_member_id` (`channel_id`, `member_id`)
		);";

        // file manager
        $Q[] = "CREATE TABLE `exp_file_manager_views` (
			`view_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`viewtype` varchar(10) NOT NULL DEFAULT 'list',
			`upload_id` int(6) unsigned NOT NULL,
			`member_id` int(10) unsigned NOT NULL,
			`name` varchar(128) NOT NULL DEFAULT '',
			`columns` text NOT NULL,
			PRIMARY KEY (`view_id`),
			KEY `viewtype_upload_id_member_id` (`viewtype`, `upload_id`, `member_id`)
		);";

        // member manager
        $Q[] = "CREATE TABLE `exp_member_manager_views` (
			`view_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`role_id` int(6) unsigned NOT NULL,
			`member_id` int(10) unsigned NOT NULL,
			`name` varchar(128) NOT NULL DEFAULT '',
			`columns` text NOT NULL,
			PRIMARY KEY (`view_id`),
			KEY `role_id_member_id` (`role_id`, `member_id`)
		);";

        // Default menu set
        $Q[] = "INSERT INTO exp_menu_sets(name) VALUES ('Default')";

        // --------------------------------------------------------------------
        // --------------------------------------------------------------------
        //  Specialty Templates
        //  - The methods are by default in email_data.php but can be overloaded if there is a
        //	speciality_templates.php file in the chosen Site Theme folder
        // --------------------------------------------------------------------
        // --------------------------------------------------------------------

        $Q[] = "INSERT INTO exp_specialty_templates(template_name, template_type, edit_date, data_title, template_data) VALUES ('offline_template', 'system', " . time() . ", '', '" . addslashes(offline_template()) . "')";
        $Q[] = "INSERT INTO exp_specialty_templates(template_name, template_type, edit_date, data_title, template_data) VALUES ('message_template', 'system', " . time() . ", '', '" . addslashes(message_template()) . "')";
        $Q[] = "INSERT INTO exp_specialty_templates(template_name, template_type, edit_date, data_title, template_data) VALUES ('post_install_message_template', 'system', " . time() . ", '', '" . addslashes(post_install_message_template()) . "')";
		$Q[] = "INSERT INTO exp_specialty_templates(template_name, template_type, edit_date, data_title, template_data) VALUES ('mfa_template', 'system', " . time() . ", '', '" . addslashes(mfa_message_template()) . "')";
        $Q[] = "INSERT INTO exp_specialty_templates(template_name, template_type, template_subtype, edit_date, data_title, template_data) VALUES ('admin_notify_reg', 'email', 'members', " . time() . ", '" . addslashes(trim(admin_notify_reg_title())) . "', '" . addslashes(admin_notify_reg()) . "')";
        $Q[] = "INSERT INTO exp_specialty_templates(template_name, template_type, template_subtype, edit_date, data_title, template_data) VALUES ('admin_notify_entry', 'email', 'content', " . time() . ", '" . addslashes(trim(admin_notify_entry_title())) . "', '" . addslashes(admin_notify_entry()) . "')";
        $Q[] = "INSERT INTO exp_specialty_templates(template_name, template_type, template_subtype, edit_date, data_title, template_data) VALUES ('admin_notify_comment', 'email', 'comments', " . time() . ", '" . addslashes(trim(admin_notify_comment_title())) . "', '" . addslashes(admin_notify_comment()) . "')";
        $Q[] = "INSERT INTO exp_specialty_templates(template_name, template_type, template_subtype, edit_date, data_title, template_data) VALUES ('mbr_activation_instructions', 'email', 'members', " . time() . ", '" . addslashes(trim(mbr_activation_instructions_title())) . "', '" . addslashes(mbr_activation_instructions()) . "')";
        $Q[] = "INSERT INTO exp_specialty_templates(template_name, template_type, template_subtype, edit_date, data_title, template_data) VALUES ('forgot_password_instructions', 'email', 'members', " . time() . ", '" . addslashes(trim(forgot_password_instructions_title())) . "', '" . addslashes(forgot_password_instructions()) . "')";
        $Q[] = "INSERT INTO exp_specialty_templates(template_name, template_type, template_subtype, edit_date, data_title, template_data) VALUES ('password_changed_notification', 'email', 'members', " . time() . ", '" . addslashes(trim(password_changed_notification_title())) . "', '" . addslashes(password_changed_notification()) . "')";
        $Q[] = "INSERT INTO exp_specialty_templates(template_name, template_type, template_subtype, edit_date, data_title, template_data) VALUES ('forgot_username_instructions', 'email', 'members', " . time() . ", '" . addslashes(trim(forgot_username_instructions_title())) . "', '" . addslashes(forgot_username_instructions()) . "')";
        $Q[] = "INSERT INTO exp_specialty_templates(template_name, template_type, template_subtype, edit_date, data_title, template_data) VALUES ('email_changed_notification', 'email', 'members', " . time() . ", '" . addslashes(trim(email_changed_notification_title())) . "', '" . addslashes(email_changed_notification()) . "')";
        $Q[] = "INSERT INTO exp_specialty_templates(template_name, template_type, template_subtype, edit_date, data_title, template_data) VALUES ('validated_member_notify', 'email', 'members', " . time() . ", '" . addslashes(trim(validated_member_notify_title())) . "', '" . addslashes(validated_member_notify()) . "')";
        $Q[] = "INSERT INTO exp_specialty_templates(template_name, template_type, template_subtype, edit_date, data_title, template_data) VALUES ('decline_member_validation', 'email', 'members', " . time() . ", '" . addslashes(trim(decline_member_validation_title())) . "', '" . addslashes(decline_member_validation()) . "')";
        $Q[] = "INSERT INTO exp_specialty_templates(template_name, template_type, template_subtype, edit_date, data_title, template_data) VALUES ('comment_notification', 'email', 'comments', " . time() . ", '" . addslashes(trim(comment_notification_title())) . "', '" . addslashes(comment_notification()) . "')";
        $Q[] = "INSERT INTO exp_specialty_templates(template_name, template_type, template_subtype, edit_date, data_title, template_data) VALUES ('comments_opened_notification', 'email', 'comments', " . time() . ", '" . addslashes(trim(comments_opened_notification_title())) . "', '" . addslashes(comments_opened_notification()) . "')";
        $Q[] = "INSERT INTO exp_specialty_templates(template_name, template_type, template_subtype, edit_date, data_title, template_data) VALUES ('private_message_notification', 'email', 'private_messages', " . time() . ", '" . addslashes(trim(private_message_notification_title())) . "', '" . addslashes(private_message_notification()) . "')";
        $Q[] = "INSERT INTO exp_specialty_templates(template_name, template_type, template_subtype, edit_date, data_title, template_data) VALUES ('pm_inbox_full', 'email', 'private_messages', " . time() . ", '" . addslashes(trim(pm_inbox_full_title())) . "', '" . addslashes(pm_inbox_full()) . "')";

        //  Default Site Data - CANNOT BE CHANGED

        // Register the default admin
        //		$quick_link = 'My Site|'.$this->userdata['site_url'].$this->userdata['site_index'].'|1';
        $quick_link = '';

        $Q[] = "INSERT INTO exp_members (member_id, role_id, username, password, salt, unique_id, email, screen_name, join_date, ip_address, timezone, quick_links, language)
			VALUES (
				'1',
				'1',
				'" . ee()->db->escape_str($this->userdata['username']) . "',
				'" . $this->userdata['password'] . "',
				'" . ee()->db->escape_str($this->userdata['salt']) . "',
				'" . $this->userdata['unique_id'] . "',
				'" . ee()->db->escape_str($this->userdata['email_address']) . "',
				'" . ee()->db->escape_str($this->userdata['screen_name']) . "',
				'" . $this->now . "',
				'" . ee()->input->ip_address() . "',
				'" . $this->userdata['default_site_timezone'] . "',
				'$quick_link',
				'" . ee()->db->escape_str($this->userdata['deft_lang']) . "')";

        $Q[] = "INSERT INTO exp_members_roles (member_id, role_id) VALUES ('1', '1')";

        $Q[] = "INSERT INTO exp_member_data (member_id) VALUES ('1')";

        $Q[] = "INSERT INTO exp_member_news_views (member_id, version) VALUES ('1', '" . $this->version . "')";

        // Default system stats

        $Q[] = "INSERT INTO exp_stats (total_members, total_entries, last_entry_date, recent_member, recent_member_id, last_cache_clear)
			VALUES ('1', '0', '" . $this->now . "', '" . ee()->db->escape_str($this->userdata['screen_name']) . "', '1', '" . $this->now . "')";

        //  Customizable Site Data, Woot!

        // Default Site
        $site = array(
            'site_id' => 1,
            'site_label' => $this->userdata['site_label'],
            'site_name' => $this->userdata['site_name'],
            'site_bootstrap_checksums' => '',
            'site_pages' => '',
        );

        $Q[] = ee()->db->insert_string('sites', $site);

        $add_quotes = function ($value) {
            return (is_string($value)) ? "'{$value}'" : $value;
        };

        foreach ($this->roles as $role) {
            $Q[] = "INSERT INTO exp_roles
				(role_id, name, short_name, is_locked)
				VALUES (" . $role['role_id'] . ", '" . $role['name'] . "', '" . $role['short_name'] . "', '" . $role['is_locked'] . "')";

            unset($role['name']);
            unset($role['short_name']);
            unset($role['is_locked']);

            $Q[] = "INSERT INTO exp_role_settings
				(" . implode(', ', array_keys($role)) . ")
				VALUES (" . implode(', ', array_map($add_quotes, $role)) . ")";
        }

        foreach ($this->role_permissions as $role_id => $permissions) {
            foreach ($permissions as $permission) {
                $Q[] = "INSERT INTO exp_permissions (site_id, role_id, permission) VALUES(1, $role_id, '$permission')";
            }
        }

        // default statuses - these are really always needed
        $Q[] = "INSERT INTO exp_statuses (status, status_order, highlight) VALUES ('open', '1', '009933')";
        $Q[] = "INSERT INTO exp_statuses (status, status_order, highlight) VALUES ('closed', '2', '990000')";
        $Q[] = "INSERT INTO exp_statuses_roles (role_id, status_id) VALUES ('5', '1'), ('5', '2')";

        $button_config = ee()->config->loadFile('html_buttons');

        $installation_defaults = $button_config['defaults'];
        $predefined_buttons = $button_config['buttons'];

        $buttoncount = 1;

        foreach ($installation_defaults as $button) {
            $Q[] = "INSERT INTO exp_html_buttons (site_id, member_id, tag_name, tag_open, tag_close, accesskey, tag_order, tag_row, classname)
				values (1, '0', '" . $predefined_buttons[$button]['tag_name'] . "', '" . $predefined_buttons[$button]['tag_open'] . "', '" . $predefined_buttons[$button]['tag_close'] . "', '" . $predefined_buttons[$button]['accesskey'] . "', '" . $buttoncount++ . "', '1', '" . $predefined_buttons[$button]['classname'] . "')";
        }

        // Default fieldtypes
        $default_fts = array('select', 'text', 'number', 'textarea', 'date', 'duration', 'email_address', 'file', 'fluid_field', 'grid', 'file_grid', 'multi_select', 'checkboxes', 'radio', 'relationship', 'rte', 'slider', 'range_slider', 'toggle', 'url', 'colorpicker', 'selectable_buttons', 'notes', 'member');

        foreach ($default_fts as $name) {
            $addon_setup_path = SYSPATH . '/ee/ExpressionEngine/Addons/' . $name . '/addon.setup.php';
            if (file_exists($addon_setup_path)) {
                $fieldtype = require SYSPATH . '/ee/ExpressionEngine/Addons/' . $name . '/addon.setup.php';
            } else {
                $fieldtype = ['version' => '1.0.0'];
            }

            $Q[] = "INSERT INTO `exp_fieldtypes`
				(`name`,`version`,`settings`,`has_global_settings`)
				VALUES ('" . $name . "','" . $fieldtype['version'] . "','YTowOnt9','n')";
        }

        // Add Grid as a content type
        $Q[] = "INSERT INTO `exp_content_types` (`name`) VALUES ('grid')";

        //  Create DB tables and insert data

        foreach (ee()->db->list_tables(true) as $kill) {
            ee()->db->query('DROP TABLE IF EXISTS ' . $kill);
        }

        foreach ($Q as $sql) {
            if (strncmp($sql, 'CREATE TABLE', 12) == 0) {
                $sql .= 'ENGINE=' . $this->default_engine . ' ';
                $sql .= 'DEFAULT CHARACTER SET ' . ee()->db->escape_str(ee()->db->char_set) . ' COLLATE ' . ee()->db->escape_str(ee()->db->dbcollat);
            }

            if (ee()->db->query($sql) === false) {
                foreach ($this->DB->list_tables(true) as $kill) {
                    ee()->db->query('DROP TABLE IF EXISTS ' . $kill);
                }

                return false;
            }
        }

        return true;
    }
}

// EOF
