#
# Encoding: Unicode (UTF-8)
#

DROP TABLE IF EXISTS `exp_accessories`;
DROP TABLE IF EXISTS `exp_actions`;
DROP TABLE IF EXISTS `exp_captcha`;
DROP TABLE IF EXISTS `exp_categories`;
DROP TABLE IF EXISTS `exp_category_field_data`;
DROP TABLE IF EXISTS `exp_category_fields`;
DROP TABLE IF EXISTS `exp_category_groups`;
DROP TABLE IF EXISTS `exp_category_posts`;
DROP TABLE IF EXISTS `exp_channel_data`;
DROP TABLE IF EXISTS `exp_channel_entries_autosave`;
DROP TABLE IF EXISTS `exp_channel_fields`;
DROP TABLE IF EXISTS `exp_channel_form_settings`;
DROP TABLE IF EXISTS `exp_channel_member_groups`;
DROP TABLE IF EXISTS `exp_channel_titles`;
DROP TABLE IF EXISTS `exp_channels`;
DROP TABLE IF EXISTS `exp_comment_subscriptions`;
DROP TABLE IF EXISTS `exp_comments`;
DROP TABLE IF EXISTS `exp_content_types`;
DROP TABLE IF EXISTS `exp_cp_log`;
DROP TABLE IF EXISTS `exp_cp_search_index`;
DROP TABLE IF EXISTS `exp_developer_log`;
DROP TABLE IF EXISTS `exp_email_cache`;
DROP TABLE IF EXISTS `exp_email_cache_mg`;
DROP TABLE IF EXISTS `exp_email_cache_ml`;
DROP TABLE IF EXISTS `exp_email_console_cache`;
DROP TABLE IF EXISTS `exp_email_tracker`;
DROP TABLE IF EXISTS `exp_entry_versioning`;
DROP TABLE IF EXISTS `exp_extensions`;
DROP TABLE IF EXISTS `exp_field_formatting`;
DROP TABLE IF EXISTS `exp_field_groups`;
DROP TABLE IF EXISTS `exp_fieldtypes`;
DROP TABLE IF EXISTS `exp_file_categories`;
DROP TABLE IF EXISTS `exp_file_dimensions`;
DROP TABLE IF EXISTS `exp_file_watermarks`;
DROP TABLE IF EXISTS `exp_files`;
DROP TABLE IF EXISTS `exp_global_variables`;
DROP TABLE IF EXISTS `exp_grid_columns`;
DROP TABLE IF EXISTS `exp_html_buttons`;
DROP TABLE IF EXISTS `exp_layout_publish`;
DROP TABLE IF EXISTS `exp_member_bulletin_board`;
DROP TABLE IF EXISTS `exp_member_data`;
DROP TABLE IF EXISTS `exp_member_fields`;
DROP TABLE IF EXISTS `exp_member_groups`;
DROP TABLE IF EXISTS `exp_member_homepage`;
DROP TABLE IF EXISTS `exp_member_search`;
DROP TABLE IF EXISTS `exp_members`;
DROP TABLE IF EXISTS `exp_message_attachments`;
DROP TABLE IF EXISTS `exp_message_copies`;
DROP TABLE IF EXISTS `exp_message_data`;
DROP TABLE IF EXISTS `exp_message_folders`;
DROP TABLE IF EXISTS `exp_message_listed`;
DROP TABLE IF EXISTS `exp_module_member_groups`;
DROP TABLE IF EXISTS `exp_modules`;
DROP TABLE IF EXISTS `exp_online_users`;
DROP TABLE IF EXISTS `exp_password_lockout`;
DROP TABLE IF EXISTS `exp_relationships`;
DROP TABLE IF EXISTS `exp_remember_me`;
DROP TABLE IF EXISTS `exp_reset_password`;
DROP TABLE IF EXISTS `exp_revision_tracker`;
DROP TABLE IF EXISTS `exp_rte_tools`;
DROP TABLE IF EXISTS `exp_rte_toolsets`;
DROP TABLE IF EXISTS `exp_search`;
DROP TABLE IF EXISTS `exp_search_log`;
DROP TABLE IF EXISTS `exp_security_hashes`;
DROP TABLE IF EXISTS `exp_sessions`;
DROP TABLE IF EXISTS `exp_sites`;
DROP TABLE IF EXISTS `exp_snippets`;
DROP TABLE IF EXISTS `exp_specialty_templates`;
DROP TABLE IF EXISTS `exp_stats`;
DROP TABLE IF EXISTS `exp_status_groups`;
DROP TABLE IF EXISTS `exp_status_no_access`;
DROP TABLE IF EXISTS `exp_statuses`;
DROP TABLE IF EXISTS `exp_template_groups`;
DROP TABLE IF EXISTS `exp_template_member_groups`;
DROP TABLE IF EXISTS `exp_template_no_access`;
DROP TABLE IF EXISTS `exp_template_routes`;
DROP TABLE IF EXISTS `exp_templates`;
DROP TABLE IF EXISTS `exp_throttle`;
DROP TABLE IF EXISTS `exp_upload_no_access`;
DROP TABLE IF EXISTS `exp_upload_prefs`;


CREATE TABLE `exp_accessories` (
  `accessory_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `class` varchar(75) NOT NULL DEFAULT '',
  `member_groups` varchar(255) NOT NULL DEFAULT 'all',
  `controllers` text,
  `accessory_version` varchar(12) NOT NULL,
  PRIMARY KEY (`accessory_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_actions` (
  `action_id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `class` varchar(50) NOT NULL,
  `method` varchar(50) NOT NULL,
  `csrf_exempt` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`action_id`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_captcha` (
  `captcha_id` bigint(13) unsigned NOT NULL AUTO_INCREMENT,
  `date` int(10) unsigned NOT NULL,
  `ip_address` varchar(45) NOT NULL DEFAULT '0',
  `word` varchar(20) NOT NULL,
  PRIMARY KEY (`captcha_id`),
  KEY `word` (`word`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_categories` (
  `cat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `group_id` int(6) unsigned NOT NULL,
  `parent_id` int(4) unsigned NOT NULL,
  `cat_name` varchar(100) NOT NULL,
  `cat_url_title` varchar(75) NOT NULL,
  `cat_description` text,
  `cat_image` varchar(120) DEFAULT NULL,
  `cat_order` int(4) unsigned NOT NULL,
  PRIMARY KEY (`cat_id`),
  KEY `group_id` (`group_id`),
  KEY `cat_name` (`cat_name`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_category_field_data` (
  `cat_id` int(4) unsigned NOT NULL,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `group_id` int(4) unsigned NOT NULL,
  PRIMARY KEY (`cat_id`),
  KEY `site_id` (`site_id`),
  KEY `group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_category_fields` (
  `field_id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `group_id` int(4) unsigned NOT NULL,
  `field_name` varchar(32) NOT NULL DEFAULT '',
  `field_label` varchar(50) NOT NULL DEFAULT '',
  `field_type` varchar(12) NOT NULL DEFAULT 'text',
  `field_list_items` text NOT NULL,
  `field_maxl` smallint(3) NOT NULL DEFAULT '128',
  `field_ta_rows` tinyint(2) NOT NULL DEFAULT '8',
  `field_default_fmt` varchar(40) NOT NULL DEFAULT 'none',
  `field_show_fmt` char(1) NOT NULL DEFAULT 'y',
  `field_text_direction` char(3) NOT NULL DEFAULT 'ltr',
  `field_required` char(1) NOT NULL DEFAULT 'n',
  `field_order` int(3) unsigned NOT NULL,
  PRIMARY KEY (`field_id`),
  KEY `site_id` (`site_id`),
  KEY `group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_category_groups` (
  `group_id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `group_name` varchar(50) NOT NULL,
  `sort_order` char(1) NOT NULL DEFAULT 'a',
  `exclude_group` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `field_html_formatting` char(4) NOT NULL DEFAULT 'all',
  `can_edit_categories` text,
  `can_delete_categories` text,
  PRIMARY KEY (`group_id`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_category_posts` (
  `entry_id` int(10) unsigned NOT NULL,
  `cat_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`entry_id`,`cat_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_channel_data` (
  `entry_id` int(10) unsigned NOT NULL,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `channel_id` int(4) unsigned NOT NULL,
  `field_id_1` text,
  `field_ft_1` tinytext,
  `field_id_2` text,
  `field_ft_2` tinytext,
  `field_id_3` text,
  `field_ft_3` tinytext,
  `field_id_4` text,
  `field_ft_4` tinytext,
  `field_id_5` text,
  `field_ft_5` tinytext,
  `field_id_6` text,
  `field_ft_6` tinytext,
  `field_id_7` text,
  `field_ft_7` tinytext,
  PRIMARY KEY (`entry_id`),
  KEY `channel_id` (`channel_id`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_channel_entries_autosave` (
  `entry_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `original_entry_id` int(10) unsigned NOT NULL,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `channel_id` int(4) unsigned NOT NULL,
  `author_id` int(10) unsigned NOT NULL DEFAULT '0',
  `forum_topic_id` int(10) unsigned DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL DEFAULT '0',
  `title` varchar(100) NOT NULL,
  `url_title` varchar(75) NOT NULL,
  `status` varchar(50) NOT NULL,
  `versioning_enabled` char(1) NOT NULL DEFAULT 'n',
  `view_count_one` int(10) unsigned NOT NULL DEFAULT '0',
  `view_count_two` int(10) unsigned NOT NULL DEFAULT '0',
  `view_count_three` int(10) unsigned NOT NULL DEFAULT '0',
  `view_count_four` int(10) unsigned NOT NULL DEFAULT '0',
  `allow_comments` varchar(1) NOT NULL DEFAULT 'y',
  `sticky` varchar(1) NOT NULL DEFAULT 'n',
  `entry_date` int(10) NOT NULL,
  `year` char(4) NOT NULL,
  `month` char(2) NOT NULL,
  `day` char(3) NOT NULL,
  `expiration_date` int(10) NOT NULL DEFAULT '0',
  `comment_expiration_date` int(10) NOT NULL DEFAULT '0',
  `edit_date` bigint(14) DEFAULT NULL,
  `recent_comment_date` int(10) DEFAULT NULL,
  `comment_total` int(4) unsigned NOT NULL DEFAULT '0',
  `entry_data` text,
  PRIMARY KEY (`entry_id`),
  KEY `channel_id` (`channel_id`),
  KEY `author_id` (`author_id`),
  KEY `url_title` (`url_title`),
  KEY `status` (`status`),
  KEY `entry_date` (`entry_date`),
  KEY `expiration_date` (`expiration_date`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_channel_fields` (
  `field_id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `group_id` int(4) unsigned NOT NULL,
  `field_name` varchar(32) NOT NULL,
  `field_label` varchar(50) NOT NULL,
  `field_instructions` text,
  `field_type` varchar(50) NOT NULL DEFAULT 'text',
  `field_list_items` text NOT NULL,
  `field_pre_populate` char(1) NOT NULL DEFAULT 'n',
  `field_pre_channel_id` int(6) unsigned DEFAULT NULL,
  `field_pre_field_id` int(6) unsigned DEFAULT NULL,
  `field_ta_rows` tinyint(2) DEFAULT '8',
  `field_maxl` smallint(3) DEFAULT NULL,
  `field_required` char(1) NOT NULL DEFAULT 'n',
  `field_text_direction` char(3) NOT NULL DEFAULT 'ltr',
  `field_search` char(1) NOT NULL DEFAULT 'n',
  `field_is_hidden` char(1) NOT NULL DEFAULT 'n',
  `field_fmt` varchar(40) NOT NULL DEFAULT 'xhtml',
  `field_show_fmt` char(1) NOT NULL DEFAULT 'y',
  `field_order` int(3) unsigned NOT NULL,
  `field_content_type` varchar(20) NOT NULL DEFAULT 'any',
  `field_settings` text,
  PRIMARY KEY (`field_id`),
  KEY `group_id` (`group_id`),
  KEY `field_type` (`field_type`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_channel_form_settings` (
  `channel_form_settings_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT '0',
  `channel_id` int(6) unsigned NOT NULL DEFAULT '0',
  `default_status` varchar(50) NOT NULL DEFAULT 'open',
  `require_captcha` char(1) NOT NULL DEFAULT 'n',
  `allow_guest_posts` char(1) NOT NULL DEFAULT 'n',
  `default_author` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`channel_form_settings_id`),
  KEY `site_id` (`site_id`),
  KEY `channel_id` (`channel_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_channel_member_groups` (
  `group_id` smallint(4) unsigned NOT NULL,
  `channel_id` int(6) unsigned NOT NULL,
  PRIMARY KEY (`group_id`,`channel_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_channel_titles` (
  `entry_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `channel_id` int(4) unsigned NOT NULL,
  `author_id` int(10) unsigned NOT NULL DEFAULT '0',
  `forum_topic_id` int(10) unsigned DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL DEFAULT '0',
  `title` varchar(100) NOT NULL,
  `url_title` varchar(75) NOT NULL,
  `status` varchar(50) NOT NULL,
  `versioning_enabled` char(1) NOT NULL DEFAULT 'n',
  `view_count_one` int(10) unsigned NOT NULL DEFAULT '0',
  `view_count_two` int(10) unsigned NOT NULL DEFAULT '0',
  `view_count_three` int(10) unsigned NOT NULL DEFAULT '0',
  `view_count_four` int(10) unsigned NOT NULL DEFAULT '0',
  `allow_comments` varchar(1) NOT NULL DEFAULT 'y',
  `sticky` varchar(1) NOT NULL DEFAULT 'n',
  `entry_date` int(10) NOT NULL,
  `year` char(4) NOT NULL,
  `month` char(2) NOT NULL,
  `day` char(3) NOT NULL,
  `expiration_date` int(10) NOT NULL DEFAULT '0',
  `comment_expiration_date` int(10) NOT NULL DEFAULT '0',
  `edit_date` bigint(14) DEFAULT NULL,
  `recent_comment_date` int(10) DEFAULT NULL,
  `comment_total` int(4) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`entry_id`),
  KEY `channel_id` (`channel_id`),
  KEY `author_id` (`author_id`),
  KEY `url_title` (`url_title`),
  KEY `status` (`status`),
  KEY `entry_date` (`entry_date`),
  KEY `expiration_date` (`expiration_date`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_channels` (
  `channel_id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `channel_name` varchar(40) NOT NULL,
  `channel_title` varchar(100) NOT NULL,
  `channel_url` varchar(100) NOT NULL,
  `channel_description` varchar(255) DEFAULT NULL,
  `channel_lang` varchar(12) NOT NULL,
  `total_entries` mediumint(8) NOT NULL DEFAULT '0',
  `total_comments` mediumint(8) NOT NULL DEFAULT '0',
  `last_entry_date` int(10) unsigned NOT NULL DEFAULT '0',
  `last_comment_date` int(10) unsigned NOT NULL DEFAULT '0',
  `cat_group` varchar(255) DEFAULT NULL,
  `status_group` int(4) unsigned DEFAULT NULL,
  `deft_status` varchar(50) NOT NULL DEFAULT 'open',
  `field_group` int(4) unsigned DEFAULT NULL,
  `search_excerpt` int(4) unsigned DEFAULT NULL,
  `deft_category` varchar(60) DEFAULT NULL,
  `deft_comments` char(1) NOT NULL DEFAULT 'y',
  `channel_require_membership` char(1) NOT NULL DEFAULT 'y',
  `channel_max_chars` int(5) unsigned DEFAULT NULL,
  `channel_html_formatting` char(4) NOT NULL DEFAULT 'all',
  `channel_allow_img_urls` char(1) NOT NULL DEFAULT 'y',
  `channel_auto_link_urls` char(1) NOT NULL DEFAULT 'n',
  `channel_notify` char(1) NOT NULL DEFAULT 'n',
  `channel_notify_emails` varchar(255) DEFAULT NULL,
  `comment_url` varchar(80) DEFAULT NULL,
  `comment_system_enabled` char(1) NOT NULL DEFAULT 'y',
  `comment_require_membership` char(1) NOT NULL DEFAULT 'n',
  `comment_use_captcha` char(1) NOT NULL DEFAULT 'n',
  `comment_moderate` char(1) NOT NULL DEFAULT 'n',
  `comment_max_chars` int(5) unsigned DEFAULT '5000',
  `comment_timelock` int(5) unsigned NOT NULL DEFAULT '0',
  `comment_require_email` char(1) NOT NULL DEFAULT 'y',
  `comment_text_formatting` char(5) NOT NULL DEFAULT 'xhtml',
  `comment_html_formatting` char(4) NOT NULL DEFAULT 'safe',
  `comment_allow_img_urls` char(1) NOT NULL DEFAULT 'n',
  `comment_auto_link_urls` char(1) NOT NULL DEFAULT 'y',
  `comment_notify` char(1) NOT NULL DEFAULT 'n',
  `comment_notify_authors` char(1) NOT NULL DEFAULT 'n',
  `comment_notify_emails` varchar(255) DEFAULT NULL,
  `comment_expiration` int(4) unsigned NOT NULL DEFAULT '0',
  `search_results_url` varchar(80) DEFAULT NULL,
  `show_button_cluster` char(1) NOT NULL DEFAULT 'y',
  `rss_url` varchar(80) DEFAULT NULL,
  `enable_versioning` char(1) NOT NULL DEFAULT 'n',
  `max_revisions` smallint(4) unsigned NOT NULL DEFAULT '10',
  `default_entry_title` varchar(100) DEFAULT NULL,
  `url_title_prefix` varchar(80) DEFAULT NULL,
  `live_look_template` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`channel_id`),
  KEY `cat_group` (`cat_group`),
  KEY `status_group` (`status_group`),
  KEY `field_group` (`field_group`),
  KEY `channel_name` (`channel_name`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_comment_subscriptions` (
  `subscription_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned DEFAULT NULL,
  `member_id` int(10) DEFAULT '0',
  `email` varchar(75) DEFAULT NULL,
  `subscription_date` varchar(10) DEFAULT NULL,
  `notification_sent` char(1) DEFAULT 'n',
  `hash` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`subscription_id`),
  KEY `entry_id` (`entry_id`),
  KEY `member_id` (`member_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_comments` (
  `comment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) DEFAULT '1',
  `entry_id` int(10) unsigned DEFAULT '0',
  `channel_id` int(4) unsigned DEFAULT '1',
  `author_id` int(10) unsigned DEFAULT '0',
  `status` char(1) DEFAULT '0',
  `name` varchar(50) DEFAULT NULL,
  `email` varchar(75) DEFAULT NULL,
  `url` varchar(75) DEFAULT NULL,
  `location` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `comment_date` int(10) DEFAULT NULL,
  `edit_date` int(10) DEFAULT NULL,
  `comment` text,
  PRIMARY KEY (`comment_id`),
  KEY `entry_id` (`entry_id`),
  KEY `channel_id` (`channel_id`),
  KEY `author_id` (`author_id`),
  KEY `status` (`status`),
  KEY `site_id` (`site_id`),
  KEY `comment_date_idx` (`comment_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_content_types` (
  `content_type_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`content_type_id`),
  KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_cp_log` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `member_id` int(10) unsigned NOT NULL,
  `username` varchar(32) NOT NULL,
  `ip_address` varchar(45) NOT NULL DEFAULT '0',
  `act_date` int(10) NOT NULL,
  `action` varchar(200) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_cp_search_index` (
  `search_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `controller` varchar(20) DEFAULT NULL,
  `method` varchar(50) DEFAULT NULL,
  `language` varchar(20) DEFAULT NULL,
  `access` varchar(50) DEFAULT NULL,
  `keywords` text,
  PRIMARY KEY (`search_id`),
  FULLTEXT KEY `keywords` (`keywords`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_developer_log` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` int(10) unsigned NOT NULL,
  `viewed` char(1) NOT NULL DEFAULT 'n',
  `description` text,
  `function` varchar(100) DEFAULT NULL,
  `line` int(10) unsigned DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `deprecated_since` varchar(10) DEFAULT NULL,
  `use_instead` varchar(100) DEFAULT NULL,
  `template_id` int(10) unsigned NOT NULL DEFAULT '0',
  `template_name` varchar(100) DEFAULT NULL,
  `template_group` varchar(100) DEFAULT NULL,
  `addon_module` varchar(100) DEFAULT NULL,
  `addon_method` varchar(100) DEFAULT NULL,
  `snippets` text,
  `hash` char(32) NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_email_cache` (
  `cache_id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `cache_date` int(10) unsigned NOT NULL DEFAULT '0',
  `total_sent` int(6) unsigned NOT NULL,
  `from_name` varchar(70) NOT NULL,
  `from_email` varchar(75) NOT NULL,
  `recipient` text NOT NULL,
  `cc` text NOT NULL,
  `bcc` text NOT NULL,
  `recipient_array` mediumtext NOT NULL,
  `subject` varchar(120) NOT NULL,
  `message` mediumtext NOT NULL,
  `plaintext_alt` mediumtext NOT NULL,
  `mailinglist` char(1) NOT NULL DEFAULT 'n',
  `mailtype` varchar(6) NOT NULL,
  `text_fmt` varchar(40) NOT NULL,
  `wordwrap` char(1) NOT NULL DEFAULT 'y',
  `priority` char(1) NOT NULL DEFAULT '3',
  PRIMARY KEY (`cache_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_email_cache_mg` (
  `cache_id` int(6) unsigned NOT NULL,
  `group_id` smallint(4) NOT NULL,
  PRIMARY KEY (`cache_id`,`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_email_cache_ml` (
  `cache_id` int(6) unsigned NOT NULL,
  `list_id` smallint(4) NOT NULL,
  PRIMARY KEY (`cache_id`,`list_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_email_console_cache` (
  `cache_id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `cache_date` int(10) unsigned NOT NULL DEFAULT '0',
  `member_id` int(10) unsigned NOT NULL,
  `member_name` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL DEFAULT '0',
  `recipient` varchar(75) NOT NULL,
  `recipient_name` varchar(50) NOT NULL,
  `subject` varchar(120) NOT NULL,
  `message` mediumtext NOT NULL,
  PRIMARY KEY (`cache_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_email_tracker` (
  `email_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email_date` int(10) unsigned NOT NULL DEFAULT '0',
  `sender_ip` varchar(45) NOT NULL,
  `sender_email` varchar(75) NOT NULL,
  `sender_username` varchar(50) NOT NULL,
  `number_recipients` int(4) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`email_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_entry_versioning` (
  `version_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned NOT NULL,
  `channel_id` int(4) unsigned NOT NULL,
  `author_id` int(10) unsigned NOT NULL,
  `version_date` int(10) NOT NULL,
  `version_data` mediumtext NOT NULL,
  PRIMARY KEY (`version_id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_extensions` (
  `extension_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `class` varchar(50) NOT NULL DEFAULT '',
  `method` varchar(50) NOT NULL DEFAULT '',
  `hook` varchar(50) NOT NULL DEFAULT '',
  `settings` text NOT NULL,
  `priority` int(2) NOT NULL DEFAULT '10',
  `version` varchar(10) NOT NULL DEFAULT '',
  `enabled` char(1) NOT NULL DEFAULT 'y',
  PRIMARY KEY (`extension_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_field_formatting` (
  `formatting_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `field_id` int(10) unsigned NOT NULL,
  `field_fmt` varchar(40) NOT NULL,
  PRIMARY KEY (`formatting_id`)
) ENGINE=MyISAM AUTO_INCREMENT=29 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_field_groups` (
  `group_id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `group_name` varchar(50) NOT NULL,
  PRIMARY KEY (`group_id`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_fieldtypes` (
  `fieldtype_id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `version` varchar(12) NOT NULL,
  `settings` text,
  `has_global_settings` char(1) DEFAULT 'n',
  PRIMARY KEY (`fieldtype_id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_file_categories` (
  `file_id` int(10) unsigned DEFAULT NULL,
  `cat_id` int(10) unsigned DEFAULT NULL,
  `sort` int(10) unsigned DEFAULT '0',
  `is_cover` char(1) DEFAULT 'n',
  KEY `file_id` (`file_id`),
  KEY `cat_id` (`cat_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_file_dimensions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `upload_location_id` int(4) unsigned DEFAULT NULL,
  `title` varchar(255) DEFAULT '',
  `short_name` varchar(255) DEFAULT '',
  `resize_type` varchar(50) DEFAULT '',
  `width` int(10) DEFAULT '0',
  `height` int(10) DEFAULT '0',
  `watermark_id` int(4) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `upload_location_id` (`upload_location_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_file_watermarks` (
  `wm_id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `wm_name` varchar(80) DEFAULT NULL,
  `wm_type` varchar(10) DEFAULT 'text',
  `wm_image_path` varchar(100) DEFAULT NULL,
  `wm_test_image_path` varchar(100) DEFAULT NULL,
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_files` (
  `file_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned DEFAULT '1',
  `title` varchar(255) DEFAULT NULL,
  `upload_location_id` int(4) unsigned DEFAULT '0',
  `rel_path` varchar(255) DEFAULT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
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
  PRIMARY KEY (`file_id`),
  KEY `upload_location_id` (`upload_location_id`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_global_variables` (
  `variable_id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `variable_name` varchar(50) NOT NULL,
  `variable_data` text NOT NULL,
  PRIMARY KEY (`variable_id`),
  KEY `variable_name` (`variable_name`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_grid_columns` (
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_html_buttons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `member_id` int(10) NOT NULL DEFAULT '0',
  `tag_name` varchar(32) NOT NULL,
  `tag_open` varchar(120) NOT NULL,
  `tag_close` varchar(120) NOT NULL,
  `accesskey` varchar(32) NOT NULL,
  `tag_order` int(3) unsigned NOT NULL,
  `tag_row` char(1) NOT NULL DEFAULT '1',
  `classname` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_layout_publish` (
  `layout_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `member_group` int(4) unsigned NOT NULL DEFAULT '0',
  `channel_id` int(4) unsigned NOT NULL DEFAULT '0',
  `field_layout` text,
  PRIMARY KEY (`layout_id`),
  KEY `site_id` (`site_id`),
  KEY `member_group` (`member_group`),
  KEY `channel_id` (`channel_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_member_bulletin_board` (
  `bulletin_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` int(10) unsigned NOT NULL,
  `bulletin_group` int(8) unsigned NOT NULL,
  `bulletin_date` int(10) unsigned NOT NULL,
  `hash` varchar(10) NOT NULL DEFAULT '',
  `bulletin_expires` int(10) unsigned NOT NULL DEFAULT '0',
  `bulletin_message` text NOT NULL,
  PRIMARY KEY (`bulletin_id`),
  KEY `sender_id` (`sender_id`),
  KEY `hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_member_data` (
  `member_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`member_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_member_fields` (
  `m_field_id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `m_field_name` varchar(32) NOT NULL,
  `m_field_label` varchar(50) NOT NULL,
  `m_field_description` text NOT NULL,
  `m_field_type` varchar(12) NOT NULL DEFAULT 'text',
  `m_field_list_items` text NOT NULL,
  `m_field_ta_rows` tinyint(2) DEFAULT '8',
  `m_field_maxl` smallint(3) NOT NULL,
  `m_field_width` varchar(6) NOT NULL,
  `m_field_search` char(1) NOT NULL DEFAULT 'y',
  `m_field_required` char(1) NOT NULL DEFAULT 'n',
  `m_field_public` char(1) NOT NULL DEFAULT 'y',
  `m_field_reg` char(1) NOT NULL DEFAULT 'n',
  `m_field_cp_reg` char(1) NOT NULL DEFAULT 'n',
  `m_field_fmt` char(5) NOT NULL DEFAULT 'none',
  `m_field_order` int(3) unsigned NOT NULL,
  PRIMARY KEY (`m_field_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_member_groups` (
  `group_id` smallint(4) unsigned NOT NULL,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `group_title` varchar(100) NOT NULL,
  `group_description` text NOT NULL,
  `is_locked` char(1) NOT NULL DEFAULT 'y',
  `can_view_offline_system` char(1) NOT NULL DEFAULT 'n',
  `can_view_online_system` char(1) NOT NULL DEFAULT 'y',
  `can_access_cp` char(1) NOT NULL DEFAULT 'y',
  `can_access_content` char(1) NOT NULL DEFAULT 'n',
  `can_access_publish` char(1) NOT NULL DEFAULT 'n',
  `can_access_edit` char(1) NOT NULL DEFAULT 'n',
  `can_access_files` char(1) NOT NULL DEFAULT 'n',
  `can_access_fieldtypes` char(1) NOT NULL DEFAULT 'n',
  `can_access_design` char(1) NOT NULL DEFAULT 'n',
  `can_access_addons` char(1) NOT NULL DEFAULT 'n',
  `can_access_modules` char(1) NOT NULL DEFAULT 'n',
  `can_access_extensions` char(1) NOT NULL DEFAULT 'n',
  `can_access_accessories` char(1) NOT NULL DEFAULT 'n',
  `can_access_plugins` char(1) NOT NULL DEFAULT 'n',
  `can_access_members` char(1) NOT NULL DEFAULT 'n',
  `can_access_admin` char(1) NOT NULL DEFAULT 'n',
  `can_access_sys_prefs` char(1) NOT NULL DEFAULT 'n',
  `can_access_content_prefs` char(1) NOT NULL DEFAULT 'n',
  `can_access_tools` char(1) NOT NULL DEFAULT 'n',
  `can_access_comm` char(1) NOT NULL DEFAULT 'n',
  `can_access_utilities` char(1) NOT NULL DEFAULT 'n',
  `can_access_data` char(1) NOT NULL DEFAULT 'n',
  `can_access_logs` char(1) NOT NULL DEFAULT 'n',
  `can_admin_channels` char(1) NOT NULL DEFAULT 'n',
  `can_admin_upload_prefs` char(1) NOT NULL DEFAULT 'n',
  `can_admin_design` char(1) NOT NULL DEFAULT 'n',
  `can_admin_members` char(1) NOT NULL DEFAULT 'n',
  `can_delete_members` char(1) NOT NULL DEFAULT 'n',
  `can_admin_mbr_groups` char(1) NOT NULL DEFAULT 'n',
  `can_admin_mbr_templates` char(1) NOT NULL DEFAULT 'n',
  `can_ban_users` char(1) NOT NULL DEFAULT 'n',
  `can_admin_modules` char(1) NOT NULL DEFAULT 'n',
  `can_admin_templates` char(1) NOT NULL DEFAULT 'n',
  `can_edit_categories` char(1) NOT NULL DEFAULT 'n',
  `can_delete_categories` char(1) NOT NULL DEFAULT 'n',
  `can_view_other_entries` char(1) NOT NULL DEFAULT 'n',
  `can_edit_other_entries` char(1) NOT NULL DEFAULT 'n',
  `can_assign_post_authors` char(1) NOT NULL DEFAULT 'n',
  `can_delete_self_entries` char(1) NOT NULL DEFAULT 'n',
  `can_delete_all_entries` char(1) NOT NULL DEFAULT 'n',
  `can_view_other_comments` char(1) NOT NULL DEFAULT 'n',
  `can_edit_own_comments` char(1) NOT NULL DEFAULT 'n',
  `can_delete_own_comments` char(1) NOT NULL DEFAULT 'n',
  `can_edit_all_comments` char(1) NOT NULL DEFAULT 'n',
  `can_delete_all_comments` char(1) NOT NULL DEFAULT 'n',
  `can_moderate_comments` char(1) NOT NULL DEFAULT 'n',
  `can_send_email` char(1) NOT NULL DEFAULT 'n',
  `can_send_cached_email` char(1) NOT NULL DEFAULT 'n',
  `can_email_member_groups` char(1) NOT NULL DEFAULT 'n',
  `can_email_mailinglist` char(1) NOT NULL DEFAULT 'n',
  `can_email_from_profile` char(1) NOT NULL DEFAULT 'n',
  `can_view_profiles` char(1) NOT NULL DEFAULT 'n',
  `can_edit_html_buttons` char(1) NOT NULL DEFAULT 'n',
  `can_delete_self` char(1) NOT NULL DEFAULT 'n',
  `mbr_delete_notify_emails` varchar(255) DEFAULT NULL,
  `can_post_comments` char(1) NOT NULL DEFAULT 'n',
  `exclude_from_moderation` char(1) NOT NULL DEFAULT 'n',
  `can_search` char(1) NOT NULL DEFAULT 'n',
  `search_flood_control` mediumint(5) unsigned NOT NULL,
  `can_send_private_messages` char(1) NOT NULL DEFAULT 'n',
  `prv_msg_send_limit` smallint(5) unsigned NOT NULL DEFAULT '20',
  `prv_msg_storage_limit` smallint(5) unsigned NOT NULL DEFAULT '60',
  `can_attach_in_private_messages` char(1) NOT NULL DEFAULT 'n',
  `can_send_bulletins` char(1) NOT NULL DEFAULT 'n',
  `include_in_authorlist` char(1) NOT NULL DEFAULT 'n',
  `include_in_memberlist` char(1) NOT NULL DEFAULT 'y',
  `include_in_mailinglists` char(1) NOT NULL DEFAULT 'y',
  PRIMARY KEY (`group_id`,`site_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_member_homepage` (
  `member_id` int(10) unsigned NOT NULL,
  `recent_entries` char(1) NOT NULL DEFAULT 'l',
  `recent_entries_order` int(3) unsigned NOT NULL DEFAULT '0',
  `recent_comments` char(1) NOT NULL DEFAULT 'l',
  `recent_comments_order` int(3) unsigned NOT NULL DEFAULT '0',
  `recent_members` char(1) NOT NULL DEFAULT 'n',
  `recent_members_order` int(3) unsigned NOT NULL DEFAULT '0',
  `site_statistics` char(1) NOT NULL DEFAULT 'r',
  `site_statistics_order` int(3) unsigned NOT NULL DEFAULT '0',
  `member_search_form` char(1) NOT NULL DEFAULT 'n',
  `member_search_form_order` int(3) unsigned NOT NULL DEFAULT '0',
  `notepad` char(1) NOT NULL DEFAULT 'r',
  `notepad_order` int(3) unsigned NOT NULL DEFAULT '0',
  `bulletin_board` char(1) NOT NULL DEFAULT 'r',
  `bulletin_board_order` int(3) unsigned NOT NULL DEFAULT '0',
  `pmachine_news_feed` char(1) NOT NULL DEFAULT 'n',
  `pmachine_news_feed_order` int(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`member_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_member_search` (
  `search_id` varchar(32) NOT NULL,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `search_date` int(10) unsigned NOT NULL,
  `keywords` varchar(200) NOT NULL,
  `fields` varchar(200) NOT NULL,
  `member_id` int(10) unsigned NOT NULL,
  `ip_address` varchar(45) NOT NULL DEFAULT '0',
  `total_results` int(8) unsigned NOT NULL,
  `query` text NOT NULL,
  PRIMARY KEY (`search_id`),
  KEY `member_id` (`member_id`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_members` (
  `member_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` smallint(4) NOT NULL DEFAULT '0',
  `username` varchar(50) NOT NULL,
  `screen_name` varchar(50) NOT NULL,
  `password` varchar(128) NOT NULL,
  `salt` varchar(128) NOT NULL DEFAULT '',
  `unique_id` varchar(40) NOT NULL,
  `crypt_key` varchar(40) DEFAULT NULL,
  `authcode` varchar(10) DEFAULT NULL,
  `email` varchar(75) NOT NULL,
  `url` varchar(150) DEFAULT NULL,
  `location` varchar(50) DEFAULT NULL,
  `occupation` varchar(80) DEFAULT NULL,
  `interests` varchar(120) DEFAULT NULL,
  `bday_d` int(2) DEFAULT NULL,
  `bday_m` int(2) DEFAULT NULL,
  `bday_y` int(4) DEFAULT NULL,
  `aol_im` varchar(50) DEFAULT NULL,
  `yahoo_im` varchar(50) DEFAULT NULL,
  `msn_im` varchar(50) DEFAULT NULL,
  `icq` varchar(50) DEFAULT NULL,
  `bio` text,
  `signature` text,
  `avatar_filename` varchar(120) DEFAULT NULL,
  `avatar_width` int(4) unsigned DEFAULT NULL,
  `avatar_height` int(4) unsigned DEFAULT NULL,
  `photo_filename` varchar(120) DEFAULT NULL,
  `photo_width` int(4) unsigned DEFAULT NULL,
  `photo_height` int(4) unsigned DEFAULT NULL,
  `sig_img_filename` varchar(120) DEFAULT NULL,
  `sig_img_width` int(4) unsigned DEFAULT NULL,
  `sig_img_height` int(4) unsigned DEFAULT NULL,
  `ignore_list` text,
  `private_messages` int(4) unsigned NOT NULL DEFAULT '0',
  `accept_messages` char(1) NOT NULL DEFAULT 'y',
  `last_view_bulletins` int(10) NOT NULL DEFAULT '0',
  `last_bulletin_date` int(10) NOT NULL DEFAULT '0',
  `ip_address` varchar(45) NOT NULL DEFAULT '0',
  `join_date` int(10) unsigned NOT NULL DEFAULT '0',
  `last_visit` int(10) unsigned NOT NULL DEFAULT '0',
  `last_activity` int(10) unsigned NOT NULL DEFAULT '0',
  `total_entries` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `total_comments` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `total_forum_topics` mediumint(8) NOT NULL DEFAULT '0',
  `total_forum_posts` mediumint(8) NOT NULL DEFAULT '0',
  `last_entry_date` int(10) unsigned NOT NULL DEFAULT '0',
  `last_comment_date` int(10) unsigned NOT NULL DEFAULT '0',
  `last_forum_post_date` int(10) unsigned NOT NULL DEFAULT '0',
  `last_email_date` int(10) unsigned NOT NULL DEFAULT '0',
  `in_authorlist` char(1) NOT NULL DEFAULT 'n',
  `accept_admin_email` char(1) NOT NULL DEFAULT 'y',
  `accept_user_email` char(1) NOT NULL DEFAULT 'y',
  `notify_by_default` char(1) NOT NULL DEFAULT 'y',
  `notify_of_pm` char(1) NOT NULL DEFAULT 'y',
  `display_avatars` char(1) NOT NULL DEFAULT 'y',
  `display_signatures` char(1) NOT NULL DEFAULT 'y',
  `parse_smileys` char(1) NOT NULL DEFAULT 'y',
  `smart_notifications` char(1) NOT NULL DEFAULT 'y',
  `language` varchar(50) NOT NULL,
  `timezone` varchar(50) NOT NULL,
  `time_format` char(2) NOT NULL DEFAULT '12',
  `date_format` varchar(8) NOT NULL DEFAULT '%n/%j/%Y',
  `include_seconds` char(1) NOT NULL DEFAULT 'n',
  `cp_theme` varchar(32) DEFAULT NULL,
  `profile_theme` varchar(32) DEFAULT NULL,
  `forum_theme` varchar(32) DEFAULT NULL,
  `tracker` text,
  `template_size` varchar(2) NOT NULL DEFAULT '28',
  `notepad` text,
  `notepad_size` varchar(2) NOT NULL DEFAULT '18',
  `quick_links` text,
  `quick_tabs` text,
  `show_sidebar` char(1) NOT NULL DEFAULT 'n',
  `pmember_id` int(10) NOT NULL DEFAULT '0',
  `rte_enabled` char(1) NOT NULL DEFAULT 'y',
  `rte_toolset_id` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`member_id`),
  KEY `group_id` (`group_id`),
  KEY `unique_id` (`unique_id`),
  KEY `password` (`password`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_message_attachments` (
  `attachment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` int(10) unsigned NOT NULL DEFAULT '0',
  `message_id` int(10) unsigned NOT NULL DEFAULT '0',
  `attachment_name` varchar(50) NOT NULL DEFAULT '',
  `attachment_hash` varchar(40) NOT NULL DEFAULT '',
  `attachment_extension` varchar(20) NOT NULL DEFAULT '',
  `attachment_location` varchar(150) NOT NULL DEFAULT '',
  `attachment_date` int(10) unsigned NOT NULL DEFAULT '0',
  `attachment_size` int(10) unsigned NOT NULL DEFAULT '0',
  `is_temp` char(1) NOT NULL DEFAULT 'y',
  PRIMARY KEY (`attachment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_message_copies` (
  `copy_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` int(10) unsigned NOT NULL DEFAULT '0',
  `sender_id` int(10) unsigned NOT NULL DEFAULT '0',
  `recipient_id` int(10) unsigned NOT NULL DEFAULT '0',
  `message_received` char(1) NOT NULL DEFAULT 'n',
  `message_read` char(1) NOT NULL DEFAULT 'n',
  `message_time_read` int(10) unsigned NOT NULL DEFAULT '0',
  `attachment_downloaded` char(1) NOT NULL DEFAULT 'n',
  `message_folder` int(10) unsigned NOT NULL DEFAULT '1',
  `message_authcode` varchar(10) NOT NULL DEFAULT '',
  `message_deleted` char(1) NOT NULL DEFAULT 'n',
  `message_status` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`copy_id`),
  KEY `message_id` (`message_id`),
  KEY `recipient_id` (`recipient_id`),
  KEY `sender_id` (`sender_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_message_data` (
  `message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` int(10) unsigned NOT NULL DEFAULT '0',
  `message_date` int(10) unsigned NOT NULL DEFAULT '0',
  `message_subject` varchar(255) NOT NULL DEFAULT '',
  `message_body` text NOT NULL,
  `message_tracking` char(1) NOT NULL DEFAULT 'y',
  `message_attachments` char(1) NOT NULL DEFAULT 'n',
  `message_recipients` varchar(200) NOT NULL DEFAULT '',
  `message_cc` varchar(200) NOT NULL DEFAULT '',
  `message_hide_cc` char(1) NOT NULL DEFAULT 'n',
  `message_sent_copy` char(1) NOT NULL DEFAULT 'n',
  `total_recipients` int(5) unsigned NOT NULL DEFAULT '0',
  `message_status` varchar(25) NOT NULL DEFAULT '',
  PRIMARY KEY (`message_id`),
  KEY `sender_id` (`sender_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_message_folders` (
  `member_id` int(10) unsigned NOT NULL DEFAULT '0',
  `folder1_name` varchar(50) NOT NULL DEFAULT 'InBox',
  `folder2_name` varchar(50) NOT NULL DEFAULT 'Sent',
  `folder3_name` varchar(50) NOT NULL DEFAULT '',
  `folder4_name` varchar(50) NOT NULL DEFAULT '',
  `folder5_name` varchar(50) NOT NULL DEFAULT '',
  `folder6_name` varchar(50) NOT NULL DEFAULT '',
  `folder7_name` varchar(50) NOT NULL DEFAULT '',
  `folder8_name` varchar(50) NOT NULL DEFAULT '',
  `folder9_name` varchar(50) NOT NULL DEFAULT '',
  `folder10_name` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`member_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_message_listed` (
  `listed_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL DEFAULT '0',
  `listed_member` int(10) unsigned NOT NULL DEFAULT '0',
  `listed_description` varchar(100) NOT NULL DEFAULT '',
  `listed_type` varchar(10) NOT NULL DEFAULT 'blocked',
  PRIMARY KEY (`listed_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_module_member_groups` (
  `group_id` smallint(4) unsigned NOT NULL,
  `module_id` mediumint(5) unsigned NOT NULL,
  PRIMARY KEY (`group_id`,`module_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_modules` (
  `module_id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `module_name` varchar(50) NOT NULL,
  `module_version` varchar(12) NOT NULL,
  `has_cp_backend` char(1) NOT NULL DEFAULT 'n',
  `has_publish_fields` char(1) NOT NULL DEFAULT 'n',
  PRIMARY KEY (`module_id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_online_users` (
  `online_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `member_id` int(10) NOT NULL DEFAULT '0',
  `in_forum` char(1) NOT NULL DEFAULT 'n',
  `name` varchar(50) NOT NULL DEFAULT '0',
  `ip_address` varchar(45) NOT NULL DEFAULT '0',
  `date` int(10) unsigned NOT NULL DEFAULT '0',
  `anon` char(1) NOT NULL,
  PRIMARY KEY (`online_id`),
  KEY `date` (`date`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_password_lockout` (
  `lockout_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `login_date` int(10) unsigned NOT NULL,
  `ip_address` varchar(45) NOT NULL DEFAULT '0',
  `user_agent` varchar(120) NOT NULL,
  `username` varchar(50) NOT NULL,
  PRIMARY KEY (`lockout_id`),
  KEY `login_date` (`login_date`),
  KEY `ip_address` (`ip_address`),
  KEY `user_agent` (`user_agent`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_relationships` (
  `relationship_id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `child_id` int(10) unsigned NOT NULL DEFAULT '0',
  `field_id` int(10) unsigned NOT NULL DEFAULT '0',
  `grid_field_id` int(10) unsigned NOT NULL DEFAULT '0',
  `grid_col_id` int(10) unsigned NOT NULL DEFAULT '0',
  `grid_row_id` int(10) unsigned NOT NULL DEFAULT '0',
  `order` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`relationship_id`),
  KEY `parent_id` (`parent_id`),
  KEY `child_id` (`child_id`),
  KEY `field_id` (`field_id`),
  KEY `grid_row_id` (`grid_row_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_remember_me` (
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_reset_password` (
  `reset_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL,
  `resetcode` varchar(12) NOT NULL,
  `date` int(10) NOT NULL,
  PRIMARY KEY (`reset_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_revision_tracker` (
  `tracker_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` int(10) unsigned NOT NULL,
  `item_table` varchar(20) NOT NULL,
  `item_field` varchar(20) NOT NULL,
  `item_date` int(10) NOT NULL,
  `item_author_id` int(10) unsigned NOT NULL,
  `item_data` mediumtext NOT NULL,
  PRIMARY KEY (`tracker_id`),
  KEY `item_id` (`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_rte_tools` (
  `tool_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(75) DEFAULT NULL,
  `class` varchar(75) DEFAULT NULL,
  `enabled` char(1) DEFAULT 'y',
  PRIMARY KEY (`tool_id`),
  KEY `enabled` (`enabled`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_rte_toolsets` (
  `toolset_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) DEFAULT '0',
  `name` varchar(100) DEFAULT NULL,
  `tools` text,
  `enabled` char(1) DEFAULT 'y',
  PRIMARY KEY (`toolset_id`),
  KEY `member_id` (`member_id`),
  KEY `enabled` (`enabled`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_search` (
  `search_id` varchar(32) NOT NULL,
  `site_id` int(4) NOT NULL DEFAULT '1',
  `search_date` int(10) NOT NULL,
  `keywords` varchar(60) NOT NULL,
  `member_id` int(10) unsigned NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `total_results` int(6) NOT NULL,
  `per_page` tinyint(3) unsigned NOT NULL,
  `query` mediumtext,
  `custom_fields` mediumtext,
  `result_page` varchar(70) NOT NULL,
  PRIMARY KEY (`search_id`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_search_log` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `member_id` int(10) unsigned NOT NULL,
  `screen_name` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL DEFAULT '0',
  `search_date` int(10) NOT NULL,
  `search_type` varchar(32) NOT NULL,
  `search_terms` varchar(200) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_security_hashes` (
  `hash_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` int(10) unsigned NOT NULL,
  `session_id` varchar(40) NOT NULL DEFAULT '0',
  `hash` varchar(40) NOT NULL,
  PRIMARY KEY (`hash_id`),
  KEY `session_id` (`session_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_sessions` (
  `session_id` varchar(40) NOT NULL DEFAULT '0',
  `member_id` int(10) NOT NULL DEFAULT '0',
  `admin_sess` tinyint(1) NOT NULL DEFAULT '0',
  `ip_address` varchar(45) NOT NULL DEFAULT '0',
  `user_agent` varchar(120) NOT NULL,
  `login_state` varchar(32) NULL DEFAULT NULL,
  `fingerprint` varchar(40) NOT NULL,
  `sess_start` int(10) unsigned NOT NULL DEFAULT '0',
  `last_activity` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`),
  KEY `member_id` (`member_id`),
  KEY `last_activity_idx` (`last_activity`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_sites` (
  `site_id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `site_label` varchar(100) NOT NULL DEFAULT '',
  `site_name` varchar(50) NOT NULL DEFAULT '',
  `site_description` text,
  `site_system_preferences` mediumtext NOT NULL,
  `site_mailinglist_preferences` text NOT NULL,
  `site_member_preferences` text NOT NULL,
  `site_template_preferences` text NOT NULL,
  `site_channel_preferences` text NOT NULL,
  `site_bootstrap_checksums` text NOT NULL,
  PRIMARY KEY (`site_id`),
  KEY `site_name` (`site_name`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_snippets` (
  `snippet_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) NOT NULL,
  `snippet_name` varchar(75) NOT NULL,
  `snippet_contents` text,
  PRIMARY KEY (`snippet_id`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_specialty_templates` (
  `template_id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `enable_template` char(1) NOT NULL DEFAULT 'y',
  `template_name` varchar(50) NOT NULL,
  `data_title` varchar(80) NOT NULL,
  `template_data` text NOT NULL,
  PRIMARY KEY (`template_id`),
  KEY `template_name` (`template_name`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_stats` (
  `stat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `total_members` mediumint(7) NOT NULL DEFAULT '0',
  `recent_member_id` int(10) NOT NULL DEFAULT '0',
  `recent_member` varchar(50) NOT NULL,
  `total_entries` mediumint(8) NOT NULL DEFAULT '0',
  `total_forum_topics` mediumint(8) NOT NULL DEFAULT '0',
  `total_forum_posts` mediumint(8) NOT NULL DEFAULT '0',
  `total_comments` mediumint(8) NOT NULL DEFAULT '0',
  `last_entry_date` int(10) unsigned NOT NULL DEFAULT '0',
  `last_forum_post_date` int(10) unsigned NOT NULL DEFAULT '0',
  `last_comment_date` int(10) unsigned NOT NULL DEFAULT '0',
  `last_visitor_date` int(10) unsigned NOT NULL DEFAULT '0',
  `most_visitors` mediumint(7) NOT NULL DEFAULT '0',
  `most_visitor_date` int(10) unsigned NOT NULL DEFAULT '0',
  `last_cache_clear` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`stat_id`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_status_groups` (
  `group_id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `group_name` varchar(50) NOT NULL,
  PRIMARY KEY (`group_id`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_status_no_access` (
  `status_id` int(6) unsigned NOT NULL,
  `member_group` smallint(4) unsigned NOT NULL,
  PRIMARY KEY (`status_id`,`member_group`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_statuses` (
  `status_id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `group_id` int(4) unsigned NOT NULL,
  `status` varchar(50) NOT NULL,
  `status_order` int(3) unsigned NOT NULL,
  `highlight` varchar(30) NOT NULL,
  PRIMARY KEY (`status_id`),
  KEY `group_id` (`group_id`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_template_groups` (
  `group_id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `group_name` varchar(50) NOT NULL,
  `group_order` int(3) unsigned NOT NULL,
  `is_site_default` char(1) NOT NULL DEFAULT 'n',
  PRIMARY KEY (`group_id`),
  KEY `site_id` (`site_id`),
  KEY `group_name_idx` (`group_name`),
  KEY `group_order_idx` (`group_order`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_template_member_groups` (
  `group_id` smallint(4) unsigned NOT NULL,
  `template_group_id` mediumint(5) unsigned NOT NULL,
  PRIMARY KEY (`group_id`,`template_group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_template_no_access` (
  `template_id` int(6) unsigned NOT NULL,
  `member_group` smallint(4) unsigned NOT NULL,
  PRIMARY KEY (`template_id`,`member_group`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_template_routes` (
  `route_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int(10) unsigned NOT NULL,
  `route` varchar(512) DEFAULT NULL,
  `route_parsed` varchar(512) DEFAULT NULL,
  `route_required` char(1) NOT NULL DEFAULT 'n',
  PRIMARY KEY (`route_id`),
  KEY `template_id` (`template_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_templates` (
  `template_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `group_id` int(6) unsigned NOT NULL,
  `template_name` varchar(50) NOT NULL,
  `save_template_file` char(1) NOT NULL DEFAULT 'n',
  `template_type` varchar(16) NOT NULL DEFAULT 'webpage',
  `template_data` mediumtext,
  `template_notes` text,
  `edit_date` int(10) NOT NULL DEFAULT '0',
  `last_author_id` int(10) unsigned NOT NULL DEFAULT '0',
  `cache` char(1) NOT NULL DEFAULT 'n',
  `refresh` int(6) unsigned NOT NULL DEFAULT '0',
  `no_auth_bounce` varchar(50) NOT NULL DEFAULT '',
  `enable_http_auth` char(1) NOT NULL DEFAULT 'n',
  `allow_php` char(1) NOT NULL DEFAULT 'n',
  `php_parse_location` char(1) NOT NULL DEFAULT 'o',
  `hits` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`template_id`),
  KEY `group_id` (`group_id`),
  KEY `template_name` (`template_name`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;


CREATE TABLE `exp_throttle` (
  `throttle_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL DEFAULT '0',
  `last_activity` int(10) unsigned NOT NULL DEFAULT '0',
  `hits` int(10) unsigned NOT NULL,
  `locked_out` char(1) NOT NULL DEFAULT 'n',
  PRIMARY KEY (`throttle_id`),
  KEY `ip_address` (`ip_address`),
  KEY `last_activity` (`last_activity`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_upload_no_access` (
  `upload_id` int(6) unsigned NOT NULL,
  `upload_loc` varchar(3) NOT NULL,
  `member_group` smallint(4) unsigned NOT NULL,
  PRIMARY KEY (`upload_id`,`member_group`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `exp_upload_prefs` (
  `id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `name` varchar(50) NOT NULL,
  `server_path` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(100) NOT NULL,
  `allowed_types` varchar(3) NOT NULL DEFAULT 'img',
  `max_size` varchar(16) DEFAULT NULL,
  `max_height` varchar(6) DEFAULT NULL,
  `max_width` varchar(6) DEFAULT NULL,
  `properties` varchar(120) DEFAULT NULL,
  `pre_format` varchar(120) DEFAULT NULL,
  `post_format` varchar(120) DEFAULT NULL,
  `file_properties` varchar(120) DEFAULT NULL,
  `file_pre_format` varchar(120) DEFAULT NULL,
  `file_post_format` varchar(120) DEFAULT NULL,
  `cat_group` varchar(255) DEFAULT NULL,
  `batch_location` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;




SET FOREIGN_KEY_CHECKS = 0;


INSERT INTO `exp_accessories` (`accessory_id`, `class`, `member_groups`, `controllers`, `accessory_version`) VALUES (1, 'Expressionengine_info_acc', '1|5', 'addons|addons_accessories|addons_extensions|addons_fieldtypes|addons_modules|addons_plugins|admin_content|admin_system|content|content_edit|content_files|content_files_modal|content_publish|design|homepage|members|myaccount|tools|tools_communicate|tools_data|tools_logs|tools_utilities', '1.0');


INSERT INTO `exp_actions` (`action_id`, `class`, `method`, `csrf_exempt`) VALUES (1, 'Channel', 'submit_entry', 0), (2, 'Channel', 'filemanager_endpoint', 0), (3, 'Channel', 'smiley_pop', 0), (4, 'Channel', 'combo_loader', 0), (5, 'Member', 'registration_form', 0), (6, 'Member', 'register_member', 0), (7, 'Member', 'activate_member', 0), (8, 'Member', 'member_login', 0), (9, 'Member', 'member_logout', 0), (10, 'Member', 'send_reset_token', 0), (11, 'Member', 'process_reset_password', 0), (12, 'Member', 'send_member_email', 0), (13, 'Member', 'update_un_pw', 0), (14, 'Member', 'member_search', 0), (15, 'Member', 'member_delete', 0), (16, 'Rte', 'get_js', 0), (17, 'Email', 'send_email', 0), (18, 'Comment', 'insert_new_comment', 0), (19, 'Comment_mcp', 'delete_comment_notification', 0), (20, 'Comment', 'comment_subscribe', 0), (21, 'Comment', 'edit_comment', 0), (22, 'Search', 'do_search', 1);




INSERT INTO `exp_categories` (`cat_id`, `site_id`, `group_id`, `parent_id`, `cat_name`, `cat_url_title`, `cat_description`, `cat_image`, `cat_order`) VALUES (1, 1, 1, 0, 'News', 'news', NULL, NULL, 2), (2, 1, 1, 0, 'Bands', 'bands', NULL, NULL, 3), (3, 1, 2, 0, 'Staff Bios', 'staff_bios', NULL, NULL, 2), (4, 1, 2, 0, 'Site Info', 'site_info', NULL, NULL, 1);


INSERT INTO `exp_category_field_data` (`cat_id`, `site_id`, `group_id`) VALUES (1, 1, 1), (2, 1, 1), (3, 1, 2), (4, 1, 2);




INSERT INTO `exp_category_groups` (`group_id`, `site_id`, `group_name`, `sort_order`, `exclude_group`, `field_html_formatting`, `can_edit_categories`, `can_delete_categories`) VALUES (1, 1, 'News Categories', 'a', 0, 'all', NULL, NULL), (2, 1, 'About', 'a', 0, 'all', NULL, NULL);


INSERT INTO `exp_category_posts` (`entry_id`, `cat_id`) VALUES (1, 1), (2, 1), (3, 4), (4, 3), (5, 3), (6, 3), (7, 3), (8, 3), (9, 3), (10, 2);


INSERT INTO `exp_channel_data` (`entry_id`, `site_id`, `channel_id`, `field_id_1`, `field_ft_1`, `field_id_2`, `field_ft_2`, `field_id_3`, `field_ft_3`, `field_id_4`, `field_ft_4`, `field_id_5`, `field_ft_5`, `field_id_6`, `field_ft_6`, `field_id_7`, `field_ft_7`) VALUES (1, 1, 1, 'Thank you for choosing ExpressionEngine! This entry contains helpful resources to help you <a href="http://ellislab.com/expressionengine/user-guide/intro/getting_the_most.html">get the most from ExpressionEngine</a> and the EllisLab Community.

<strong>Learning resources:</strong>

<a href="http://ellislab.com/expressionengine/user-guide/">ExpressionEngine User Guide</a>
<a href="http://ellislab.com/expressionengine/user-guide/intro/the_big_picture.html">The Big Picture</a>
<a href="http://ellislab.com/support">EllisLab Support</a>

If you need to hire a web developer consider our <a href="http://ellislab.com/pro-network/">Professionals Network</a>.

Welcome to our community,

<span style="font-size:16px;">The EllisLab Team</span>', 'xhtml', NULL, 'xhtml', '{filedir_2}ee_banner_120_240.gif', 'none', NULL, 'xhtml', NULL, 'none', NULL, 'none', NULL, 'xhtml'), (2, 1, 1, 'Welcome to Agile Records, our Example Site.  Here you will be able to learn ExpressionEngine through a real site, with real features and in-depth comments to assist you along the way.

', 'xhtml', NULL, 'xhtml', '{filedir_2}map.jpg', 'none', NULL, 'xhtml', NULL, 'none', NULL, 'none', NULL, 'xhtml'), (3, 1, 2, NULL, NULL, NULL, NULL, NULL, NULL, 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Duis congue accumsan tellus. Aliquam diam arcu, suscipit eu, condimentum sed, ultricies accumsan, massa.
', 'xhtml', '{filedir_2}map2.jpg', 'none', NULL, 'none', 'Donec et ante. Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Vestibulum dignissim dolor nec erat dictum posuere. Vivamus lacinia, quam id fringilla dapibus, ante ante bibendum nulla, a ornare nisl est congue purus. Duis pulvinar vehicula diam.

Sed vehicula. Praesent vitae nisi. Phasellus molestie, massa sed varius ultricies, dolor lectus interdum felis, ut porta eros nibh at magna. Cras aliquam vulputate lacus. Nullam tempus vehicula mi. Quisque posuere, erat quis iaculis consequat, tortor ipsum varius mauris, sit amet pulvinar nibh mauris sed lectus. Cras vitae arcu sit amet nunc luctus molestie. Nam neque orci, tincidunt non, semper convallis, sodales fringilla, nulla. Donec non nunc. Sed condimentum urna hendrerit erat. Curabitur in felis in neque fermentum interdum.

Proin magna. In in orci. Curabitur at lectus nec arcu vehicula bibendum. Duis euismod sollicitudin augue. Maecenas auctor cursus odio.
', 'xhtml'), (4, 1, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'xhtml', '{filedir_2}staff_randell.png', 'none', 'Co-Owner/Label Manager', 'none', NULL, 'xhtml'), (5, 1, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'xhtml', '{filedir_2}staff_chloe.png', 'none', 'Co-Owner / Press &amp; Marketing', 'none', NULL, 'xhtml'), (6, 1, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'xhtml', '{filedir_2}staff_howard.png', 'none', 'Tours/Publicity/PR', 'none', NULL, 'xhtml'), (7, 1, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'xhtml', '{filedir_2}staff_jane.png', 'none', 'Sales/Accounts', 'none', NULL, 'xhtml'), (8, 1, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'xhtml', '{filedir_2}staff_josh.png', 'none', 'Product Manager', 'none', NULL, 'xhtml'), (9, 1, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'xhtml', '{filedir_2}staff_jason.png', 'none', 'Graphic/Web Designer', 'none', NULL, 'xhtml'), (10, 1, 1, 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Proin congue mi a sapien. Duis augue erat, fringilla ac, volutpat ut, venenatis vitae, nisl. Phasellus lorem. Praesent mi. Suspendisse imperdiet felis a libero. uspendisse placerat tortor in ligula vestibulum vehicula.
', 'xhtml', NULL, 'xhtml', '{filedir_2}testband300.jpg', 'none', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);




INSERT INTO `exp_channel_fields` (`field_id`, `site_id`, `group_id`, `field_name`, `field_label`, `field_instructions`, `field_type`, `field_list_items`, `field_pre_populate`, `field_pre_channel_id`, `field_pre_field_id`, `field_ta_rows`, `field_maxl`, `field_required`, `field_text_direction`, `field_search`, `field_is_hidden`, `field_fmt`, `field_show_fmt`, `field_order`, `field_content_type`, `field_settings`) VALUES (1, 1, 1, 'news_body', 'Body', NULL, 'textarea', '', 'n', 0, 0, 10, 0, 'n', 'ltr', 'y', 'n', 'xhtml', 'y', 2, 'any', 'YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToieSI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJ5IjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToieSI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToieSI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6InkiO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6InkiO30='), (2, 1, 1, 'news_extended', 'Extended text', NULL, 'textarea', '', 'n', 0, 0, 12, 0, 'n', 'ltr', 'n', 'y', 'xhtml', 'y', 3, 'any', 'YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToieSI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJ5IjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToieSI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToieSI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6InkiO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6InkiO30='), (3, 1, 1, 'news_image', 'News Image', NULL, 'file', '', 'n', 0, 0, 6, 128, 'n', 'ltr', 'n', 'n', 'none', 'n', 3, 'any', 'YTo3OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO3M6MTg6ImZpZWxkX2NvbnRlbnRfdHlwZSI7czo1OiJpbWFnZSI7fQ=='), (4, 1, 2, 'about_body', 'Body', NULL, 'textarea', '', 'n', 0, 0, 6, 128, 'n', 'ltr', 'n', 'n', 'xhtml', 'y', 4, 'any', 'YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToieSI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJ5IjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToieSI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToieSI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6InkiO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6InkiO30='), (5, 1, 2, 'about_image', 'Image', 'URL Only', 'file', '', 'n', 0, 0, 6, 128, 'n', 'ltr', 'n', 'n', 'none', 'n', 5, 'any', 'YTo3OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO3M6MTg6ImZpZWxkX2NvbnRlbnRfdHlwZSI7czo1OiJpbWFnZSI7fQ=='), (6, 1, 2, 'about_staff_title', 'Staff Member\'s Title', 'This is the Title that the staff member has within the company.  Example: CEO', 'text', '', 'n', 0, 0, 6, 128, 'n', 'ltr', 'y', 'n', 'none', 'n', 6, 'any', 'YTo4OntzOjE4OiJmaWVsZF9jb250ZW50X3RleHQiO2I6MDtzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO3M6MTg6ImZpZWxkX2NvbnRlbnRfdHlwZSI7czozOiJhbnkiO30='), (7, 1, 2, 'about_extended', 'Extended', NULL, 'textarea', '', 'n', 0, 0, 6, 128, 'n', 'ltr', 'y', 'y', 'xhtml', 'y', 7, 'any', 'YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToieSI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJ5IjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToieSI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToieSI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6InkiO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6InkiO30=');






INSERT INTO `exp_channel_titles` (`entry_id`, `site_id`, `channel_id`, `author_id`, `forum_topic_id`, `ip_address`, `title`, `url_title`, `status`, `versioning_enabled`, `view_count_one`, `view_count_two`, `view_count_three`, `view_count_four`, `allow_comments`, `sticky`, `entry_date`, `year`, `month`, `day`, `expiration_date`, `comment_expiration_date`, `edit_date`, `recent_comment_date`, `comment_total`) VALUES (1, 1, 1, 1, NULL, '::1', 'Getting to Know ExpressionEngine', 'getting_to_know_expressionengine', 'open', 'n', 0, 0, 0, 0, 'y', 'n', 1394136208, '2014', '03', '06', 0, 0, 20140306200329, NULL, 0), (2, 1, 1, 1, NULL, '::1', 'Welcome to the Example Site!', 'welcome_to_the_example_site', 'open', 'n', 0, 0, 0, 0, 'y', 'n', 1394136209, '2014', '03', '06', 0, 0, 20140306200329, NULL, 0), (3, 1, 2, 1, NULL, '::1', 'About the Label', 'about_the_label', 'open', 'n', 0, 0, 0, 0, 'y', 'n', 1394136209, '2014', '03', '06', 0, 0, 20140306200329, NULL, 0), (4, 1, 2, 1, NULL, '::1', 'Randell', 'randell', 'open', 'n', 0, 0, 0, 0, 'y', 'n', 1394136209, '2014', '03', '06', 0, 0, 20140306200329, NULL, 0), (5, 1, 2, 1, NULL, '::1', 'Chloe', 'chloe', 'open', 'n', 0, 0, 0, 0, 'y', 'n', 1394136209, '2014', '03', '06', 0, 0, 20140306200329, NULL, 0), (6, 1, 2, 1, NULL, '::1', 'Howard', 'howard', 'open', 'n', 0, 0, 0, 0, 'y', 'n', 1394136209, '2014', '03', '06', 0, 0, 20140306200329, NULL, 0), (7, 1, 2, 1, NULL, '::1', 'Jane', 'jane', 'open', 'n', 0, 0, 0, 0, 'y', 'n', 1394136209, '2014', '03', '06', 0, 0, 20140306200329, NULL, 0), (8, 1, 2, 1, NULL, '::1', 'Josh', 'josh', 'open', 'n', 0, 0, 0, 0, 'y', 'n', 1394136209, '2014', '03', '06', 0, 0, 20140306200329, NULL, 0), (9, 1, 2, 1, NULL, '::1', 'Jason', 'jason', 'open', 'n', 0, 0, 0, 0, 'y', 'n', 1394136209, '2014', '03', '06', 0, 0, 20140306200329, NULL, 0), (10, 1, 1, 1, NULL, '::1', 'Band Title', 'band_title', 'Featured', 'n', 0, 0, 0, 0, 'y', 'n', 1394136209, '2014', '03', '06', 0, 0, 20140306200329, NULL, 0);


INSERT INTO `exp_channels` (`channel_id`, `site_id`, `channel_name`, `channel_title`, `channel_url`, `channel_description`, `channel_lang`, `total_entries`, `total_comments`, `last_entry_date`, `last_comment_date`, `cat_group`, `status_group`, `deft_status`, `field_group`, `search_excerpt`, `deft_category`, `deft_comments`, `channel_require_membership`, `channel_max_chars`, `channel_html_formatting`, `channel_allow_img_urls`, `channel_auto_link_urls`, `channel_notify`, `channel_notify_emails`, `comment_url`, `comment_system_enabled`, `comment_require_membership`, `comment_use_captcha`, `comment_moderate`, `comment_max_chars`, `comment_timelock`, `comment_require_email`, `comment_text_formatting`, `comment_html_formatting`, `comment_allow_img_urls`, `comment_auto_link_urls`, `comment_notify`, `comment_notify_authors`, `comment_notify_emails`, `comment_expiration`, `search_results_url`, `show_button_cluster`, `rss_url`, `enable_versioning`, `max_revisions`, `default_entry_title`, `url_title_prefix`, `live_look_template`) VALUES (1, 1, 'news', 'News', 'http://ee2.test/index.php/news', NULL, 'en', 3, 0, 1394136209, 0, '1', 1, 'open', 1, 2, NULL, 'y', 'y', 0, 'all', 'y', 'y', 'n', NULL, 'http://ee2.test/index.php/news/comments', 'y', 'n', 'y', 'n', 0, 0, 'y', 'xhtml', 'safe', 'n', 'y', 'n', 'n', NULL, 0, 'http://ee2.test/index.php/news/comments', 'y', NULL, 'n', 10, NULL, NULL, 0), (2, 1, 'about', 'Information Pages', 'http://ee2.test/index.php/about', NULL, 'en', 7, 0, 1394136209, 0, '2', 1, 'open', 2, 7, NULL, 'y', 'y', 0, 'all', 'y', 'n', 'n', NULL, 'http://ee2.test/index.php/news/comments', 'y', 'n', 'y', 'n', 0, 0, 'y', 'xhtml', 'safe', 'n', 'y', 'n', 'n', NULL, 0, 'http://ee2.test/index.php/news/comments', 'y', NULL, 'n', 10, NULL, NULL, 0);






INSERT INTO `exp_content_types` (`content_type_id`, `name`) VALUES (1, 'grid'), (2, 'channel');




















INSERT INTO `exp_extensions` (`extension_id`, `class`, `method`, `hook`, `settings`, `priority`, `version`, `enabled`) VALUES (1, 'Rte_ext', 'myaccount_nav_setup', 'myaccount_nav_setup', '', 10, '1.0.1', 'y'), (2, 'Rte_ext', 'cp_menu_array', 'cp_menu_array', '', 10, '1.0.1', 'y');


INSERT INTO `exp_field_formatting` (`formatting_id`, `field_id`, `field_fmt`) VALUES (1, 1, 'none'), (2, 1, 'br'), (3, 1, 'xhtml'), (4, 1, 'markdown'), (5, 2, 'none'), (6, 2, 'br'), (7, 2, 'xhtml'), (8, 2, 'markdown'), (9, 3, 'none'), (10, 3, 'br'), (11, 3, 'xhtml'), (12, 3, 'markdown'), (13, 4, 'none'), (14, 4, 'br'), (15, 4, 'xhtml'), (16, 4, 'markdown'), (17, 5, 'none'), (18, 5, 'br'), (19, 5, 'xhtml'), (20, 5, 'markdown'), (21, 6, 'none'), (22, 6, 'br'), (23, 6, 'xhtml'), (24, 6, 'markdown'), (25, 7, 'none'), (26, 7, 'br'), (27, 7, 'xhtml'), (28, 7, 'markdown');


INSERT INTO `exp_field_groups` (`group_id`, `site_id`, `group_name`) VALUES (1, 1, 'News'), (2, 1, 'About');


INSERT INTO `exp_fieldtypes` (`fieldtype_id`, `name`, `version`, `settings`, `has_global_settings`) VALUES (1, 'select', '1.0', 'YTowOnt9', 'n'), (2, 'text', '1.0', 'YTowOnt9', 'n'), (3, 'textarea', '1.0', 'YTowOnt9', 'n'), (4, 'date', '1.0', 'YTowOnt9', 'n'), (5, 'file', '1.0', 'YTowOnt9', 'n'), (6, 'grid', '1.0', 'YTowOnt9', 'n'), (7, 'multi_select', '1.0', 'YTowOnt9', 'n'), (8, 'checkboxes', '1.0', 'YTowOnt9', 'n'), (9, 'radio', '1.0', 'YTowOnt9', 'n'), (10, 'relationship', '1.0', 'YTowOnt9', 'n'), (11, 'rte', '1.0', 'YTowOnt9', 'n');








INSERT INTO `exp_files` (`file_id`, `site_id`, `title`, `upload_location_id`, `rel_path`, `mime_type`, `file_name`, `file_size`, `description`, `credit`, `location`, `uploaded_by_member_id`, `upload_date`, `modified_by_member_id`, `modified_date`, `file_hw_original`) VALUES (1, 1, 'staff_jane.png', 2, 'staff_jane.png', 'image/png', 'staff_jane.png', 51612, NULL, NULL, NULL, 1, 1302889304, 1, 1302889304, ''), (2, 1, 'staff_jason.png', 2, 'staff_jason.png', 'image/png', 'staff_jason.png', 51430, NULL, NULL, NULL, 1, 1302889304, 1, 1302889304, ''), (3, 1, 'staff_josh.png', 2, 'staff_josh.png', 'image/png', 'staff_josh.png', 50638, NULL, NULL, NULL, 1, 1302889304, 1, 1302889304, ''), (4, 1, 'staff_randell.png', 2, 'staff_randell.png', 'image/png', 'staff_randell.png', 51681, NULL, NULL, NULL, 1, 1302889304, 1, 1302889304, ''), (5, 1, 'ee_banner_120_240.gif', 2, 'ee_banner_120_240.gif', 'image/gif', 'ee_banner_120_240.gif', 9257, NULL, NULL, NULL, 1, 1302889304, 1, 1302889304, ''), (6, 1, 'testband300.jpg', 2, 'testband300.jpg', 'image/jpeg', 'testband300.jpg', 23986, NULL, NULL, NULL, 1, 1302889304, 1, 1302889304, ''), (7, 1, 'map.jpg', 2, 'map.jpg', 'image/jpeg', 'map.jpg', 71299, NULL, NULL, NULL, 1, 1302889304, 1, 1302889304, ''), (8, 1, 'map2.jpg', 2, 'map2.jpg', 'image/jpeg', 'map2.jpg', 49175, NULL, NULL, NULL, 1, 1302889304, 1, 1302889304, ''), (9, 1, 'staff_chloe.png', 2, 'staff_chloe.png', 'image/png', 'staff_chloe.png', 50262, NULL, NULL, NULL, 1, 1302889304, 1, 1302889304, ''), (10, 1, 'staff_howard.png', 2, 'staff_howard.png', 'image/png', 'staff_howard.png', 51488, NULL, NULL, NULL, 1, 1302889304, 1, 1302889304, '');


INSERT INTO `exp_global_variables` (`variable_id`, `site_id`, `variable_name`, `variable_data`) VALUES (1, 1, '.htaccess', 'deny from all'), (2, 1, 'branding_begin', '<div id="branding">
	<div id="branding_logo"></div>
	<div id="branding_sub">
		<h1><a href="{site_url}" title="Agile Records Home"></a></h1>'), (3, 1, 'branding_end', '</div> <!-- ending #branding_sub -->
</div> <!-- ending #branding -->'), (4, 1, 'comment_guidelines', '<div id="comment_guidelines">
	<h6>Comment Guidelines</h6>
	<p>Basic HTML formatting permitted - <br />
		<code>&lt;ul&gt;</code>, <code>&lt;li&gt;</code>, <code>&lt;strong&gt;</code>, <code>&lt;em&gt;</code>, <code>&lt;a href&gt;</code>, <code>&lt;blockquote&gt;</code>, <code>&lt;code&gt;</code></p>
</div>'), (5, 1, 'favicon', '<!-- Favicon -->
'), (6, 1, 'html_close', '</body>
</html>'), (7, 1, 'html_head', '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
'), (8, 1, 'html_head_end', '</head>
'), (9, 1, 'js', '<!-- JS -->
<script src="{site_url}themes/site_themes/agile_records/js/jquery.js" type="text/javascript"></script>
<script src="{site_url}themes/site_themes/agile_records/js/onload.js" type="text/javascript"></script>'), (10, 1, 'nav_access', '<ul id="nav_access">
	<li><a href="#navigation">Skip to navigation</a></li>
	<li><a href="#primary_content_wrapper">Skip to content</a></li>
</ul>'), (11, 1, 'rss', '<!-- RSS -->
<link href="{path=news/rss}" rel="alternate" type="application/rss+xml" title="RSS Feed" />'), (12, 1, 'rss_links', '<h5>RSS Feeds <img src="{site_url}themes/site_themes/agile_records/images/rss12.gif" alt="RSS Icon" class="rssicon" /></h5>
		<div id="news_rss">
			<p>Subscribe to our RSS Feeds</p>
			<ul>
				<li><a href="{path=\'news/rss\'}">News RSS Feed</a></li>
				<li><a href="{path=\'news/atom\'}">News ATOM Feed</a></li>
			</ul>
		</div>'), (13, 1, 'wrapper_begin', '<div id="page">
<div id="content_wrapper">'), (14, 1, 'wrapper_close', '</div> <!-- ending #content_wrapper -->
</div> <!-- ending #page -->');




INSERT INTO `exp_html_buttons` (`id`, `site_id`, `member_id`, `tag_name`, `tag_open`, `tag_close`, `accesskey`, `tag_order`, `tag_row`, `classname`) VALUES (1, 1, 0, 'b', '<strong>', '</strong>', 'b', 1, '1', 'btn_b'), (2, 1, 0, 'i', '<em>', '</em>', 'i', 2, '1', 'btn_i'), (3, 1, 0, 'blockquote', '<blockquote>', '</blockquote>', 'q', 3, '1', 'btn_blockquote'), (4, 1, 0, 'a', '<a href="[![Link:!:http://]!]"(!( title="[![Title]!]")!)>', '</a>', 'a', 4, '1', 'btn_a'), (5, 1, 0, 'img', '<img src="[![Link:!:http://]!]" alt="[![Alternative text]!]" />', '', '', 5, '1', 'btn_img');






INSERT INTO `exp_member_data` (`member_id`) VALUES (1);




INSERT INTO `exp_member_groups` (`group_id`, `site_id`, `group_title`, `group_description`, `is_locked`, `can_view_offline_system`, `can_view_online_system`, `can_access_cp`, `can_access_content`, `can_access_publish`, `can_access_edit`, `can_access_files`, `can_access_fieldtypes`, `can_access_design`, `can_access_addons`, `can_access_modules`, `can_access_extensions`, `can_access_accessories`, `can_access_plugins`, `can_access_members`, `can_access_admin`, `can_access_sys_prefs`, `can_access_content_prefs`, `can_access_tools`, `can_access_comm`, `can_access_utilities`, `can_access_data`, `can_access_logs`, `can_admin_channels`, `can_admin_upload_prefs`, `can_admin_design`, `can_admin_members`, `can_delete_members`, `can_admin_mbr_groups`, `can_admin_mbr_templates`, `can_ban_users`, `can_admin_modules`, `can_admin_templates`, `can_edit_categories`, `can_delete_categories`, `can_view_other_entries`, `can_edit_other_entries`, `can_assign_post_authors`, `can_delete_self_entries`, `can_delete_all_entries`, `can_view_other_comments`, `can_edit_own_comments`, `can_delete_own_comments`, `can_edit_all_comments`, `can_delete_all_comments`, `can_moderate_comments`, `can_send_email`, `can_send_cached_email`, `can_email_member_groups`, `can_email_mailinglist`, `can_email_from_profile`, `can_view_profiles`, `can_edit_html_buttons`, `can_delete_self`, `mbr_delete_notify_emails`, `can_post_comments`, `exclude_from_moderation`, `can_search`, `search_flood_control`, `can_send_private_messages`, `prv_msg_send_limit`, `prv_msg_storage_limit`, `can_attach_in_private_messages`, `can_send_bulletins`, `include_in_authorlist`, `include_in_memberlist`, `include_in_mailinglists`) VALUES (1, 1, 'Super Admins', '', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', NULL, 'y', 'y', 'y', 0, 'y', 20, 60, 'y', 'y', 'y', 'y', 'y'), (2, 1, 'Banned', '', 'y', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', NULL, 'n', 'n', 'n', 60, 'n', 20, 60, 'n', 'n', 'n', 'n', 'n'), (3, 1, 'Guests', '', 'y', 'n', 'y', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'y', 'n', 'n', 'n', 'n', NULL, 'y', 'n', 'y', 15, 'n', 20, 60, 'n', 'n', 'n', 'n', 'n'), (4, 1, 'Pending', '', 'y', 'n', 'y', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'y', 'n', 'n', 'n', 'n', NULL, 'y', 'n', 'y', 15, 'n', 20, 60, 'n', 'n', 'n', 'n', 'n'), (5, 1, 'Members', '', 'y', 'n', 'y', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'y', 'y', 'y', 'n', NULL, 'y', 'n', 'y', 10, 'y', 20, 60, 'y', 'n', 'n', 'y', 'y');


INSERT INTO `exp_member_homepage` (`member_id`, `recent_entries`, `recent_entries_order`, `recent_comments`, `recent_comments_order`, `recent_members`, `recent_members_order`, `site_statistics`, `site_statistics_order`, `member_search_form`, `member_search_form_order`, `notepad`, `notepad_order`, `bulletin_board`, `bulletin_board_order`, `pmachine_news_feed`, `pmachine_news_feed_order`) VALUES (1, 'l', 1, 'l', 2, 'n', 0, 'r', 1, 'n', 0, 'r', 2, 'r', 0, 'l', 0);




INSERT INTO `exp_members` (`member_id`, `group_id`, `username`, `screen_name`, `password`, `salt`, `unique_id`, `crypt_key`, `authcode`, `email`, `url`, `location`, `occupation`, `interests`, `bday_d`, `bday_m`, `bday_y`, `aol_im`, `yahoo_im`, `msn_im`, `icq`, `bio`, `signature`, `avatar_filename`, `avatar_width`, `avatar_height`, `photo_filename`, `photo_width`, `photo_height`, `sig_img_filename`, `sig_img_width`, `sig_img_height`, `ignore_list`, `private_messages`, `accept_messages`, `last_view_bulletins`, `last_bulletin_date`, `ip_address`, `join_date`, `last_visit`, `last_activity`, `total_entries`, `total_comments`, `total_forum_topics`, `total_forum_posts`, `last_entry_date`, `last_comment_date`, `last_forum_post_date`, `last_email_date`, `in_authorlist`, `accept_admin_email`, `accept_user_email`, `notify_by_default`, `notify_of_pm`, `display_avatars`, `display_signatures`, `parse_smileys`, `smart_notifications`, `language`, `timezone`, `time_format`, `date_format`, `include_seconds`, `cp_theme`, `profile_theme`, `forum_theme`, `tracker`, `template_size`, `notepad`, `notepad_size`, `quick_links`, `quick_tabs`, `show_sidebar`, `pmember_id`, `rte_enabled`, `rte_toolset_id`) VALUES (1, 1, 'admin', 'Admin', '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8', '', '465e6155e3c56aa5581fa4fefc25423f9e0e66ce', NULL, NULL, 'kevin.cupp@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'y', 0, 0, '::1', 1394136209, 0, 0, 10, 0, 0, 0, 1394136209, 0, 0, 0, 'n', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'english', 'America/New_York', '12', '%n/%j/%Y', 'n', NULL, NULL, NULL, NULL, '28', NULL, '18', NULL, NULL, 'n', 0, 'y', 0);














INSERT INTO `exp_modules` (`module_id`, `module_name`, `module_version`, `has_cp_backend`, `has_publish_fields`) VALUES (1, 'Emoticon', '2.0', 'n', 'n'), (2, 'Jquery', '1.0', 'n', 'n'), (3, 'Channel', '2.0.1', 'n', 'n'), (4, 'Member', '2.1', 'n', 'n'), (5, 'Stats', '2.0', 'n', 'n'), (6, 'Rte', '1.0.1', 'y', 'n'), (7, 'Email', '2.0', 'n', 'n'), (8, 'Rss', '2.0', 'n', 'n'), (9, 'Comment', '2.3.2', 'y', 'n'), (10, 'Search', '2.2.2', 'n', 'n');














INSERT INTO `exp_rte_tools` (`tool_id`, `name`, `class`, `enabled`) VALUES (1, 'Blockquote', 'Blockquote_rte', 'y'), (2, 'Bold', 'Bold_rte', 'y'), (3, 'Headings', 'Headings_rte', 'y'), (4, 'Image', 'Image_rte', 'y'), (5, 'Italic', 'Italic_rte', 'y'), (6, 'Link', 'Link_rte', 'y'), (7, 'Ordered List', 'Ordered_list_rte', 'y'), (8, 'Underline', 'Underline_rte', 'y'), (9, 'Unordered List', 'Unordered_list_rte', 'y'), (10, 'View Source', 'View_source_rte', 'y');


INSERT INTO `exp_rte_toolsets` (`toolset_id`, `member_id`, `name`, `tools`, `enabled`) VALUES (1, 0, 'Default', '3|2|5|1|9|7|6|4|10', 'y');










INSERT INTO `exp_sites` (`site_id`, `site_label`, `site_name`, `site_description`, `site_system_preferences`, `site_mailinglist_preferences`, `site_member_preferences`, `site_template_preferences`, `site_channel_preferences`, `site_bootstrap_checksums`) VALUES (1, 'EE2', 'default_site', NULL, 'YTo4ODp7czoxMDoic2l0ZV9pbmRleCI7czo5OiJpbmRleC5waHAiO3M6ODoic2l0ZV91cmwiO3M6MTY6Imh0dHA6Ly9lZTIudGVzdC8iO3M6MTY6InRoZW1lX2ZvbGRlcl91cmwiO3M6MjM6Imh0dHA6Ly9lZTIudGVzdC90aGVtZXMvIjtzOjE1OiJ3ZWJtYXN0ZXJfZW1haWwiO3M6MjA6ImtldmluLmN1cHBAZ21haWwuY29tIjtzOjE0OiJ3ZWJtYXN0ZXJfbmFtZSI7czowOiIiO3M6MjA6ImNoYW5uZWxfbm9tZW5jbGF0dXJlIjtzOjc6ImNoYW5uZWwiO3M6MTA6Im1heF9jYWNoZXMiO3M6MzoiMTUwIjtzOjExOiJjYXB0Y2hhX3VybCI7czozMjoiaHR0cDovL2VlMi50ZXN0L2ltYWdlcy9jYXB0Y2hhcy8iO3M6MTI6ImNhcHRjaGFfcGF0aCI7czo1NToiL3ByaXZhdGUvdmFyL3d3dy9leHByZXNzaW9uZW5naW5lLXRlc3QvaW1hZ2VzL2NhcHRjaGFzLyI7czoxMjoiY2FwdGNoYV9mb250IjtzOjE6InkiO3M6MTI6ImNhcHRjaGFfcmFuZCI7czoxOiJ5IjtzOjIzOiJjYXB0Y2hhX3JlcXVpcmVfbWVtYmVycyI7czoxOiJuIjtzOjE3OiJlbmFibGVfZGJfY2FjaGluZyI7czoxOiJuIjtzOjE4OiJlbmFibGVfc3FsX2NhY2hpbmciO3M6MToibiI7czoxODoiZm9yY2VfcXVlcnlfc3RyaW5nIjtzOjE6Im4iO3M6MTM6InNob3dfcHJvZmlsZXIiO3M6MToibiI7czoxODoidGVtcGxhdGVfZGVidWdnaW5nIjtzOjE6Im4iO3M6MTU6ImluY2x1ZGVfc2Vjb25kcyI7czoxOiJuIjtzOjEzOiJjb29raWVfZG9tYWluIjtzOjA6IiI7czoxMToiY29va2llX3BhdGgiO3M6MDoiIjtzOjIwOiJ3ZWJzaXRlX3Nlc3Npb25fdHlwZSI7czoxOiJjIjtzOjE1OiJjcF9zZXNzaW9uX3R5cGUiO3M6MjoiY3MiO3M6MjE6ImFsbG93X3VzZXJuYW1lX2NoYW5nZSI7czoxOiJ5IjtzOjE4OiJhbGxvd19tdWx0aV9sb2dpbnMiO3M6MToieSI7czoxNjoicGFzc3dvcmRfbG9ja291dCI7czoxOiJ5IjtzOjI1OiJwYXNzd29yZF9sb2Nrb3V0X2ludGVydmFsIjtzOjE6IjEiO3M6MjA6InJlcXVpcmVfaXBfZm9yX2xvZ2luIjtzOjE6InkiO3M6MjI6InJlcXVpcmVfaXBfZm9yX3Bvc3RpbmciO3M6MToieSI7czoyNDoicmVxdWlyZV9zZWN1cmVfcGFzc3dvcmRzIjtzOjE6Im4iO3M6MTk6ImFsbG93X2RpY3Rpb25hcnlfcHciO3M6MToieSI7czoyMzoibmFtZV9vZl9kaWN0aW9uYXJ5X2ZpbGUiO3M6MDoiIjtzOjE3OiJ4c3NfY2xlYW5fdXBsb2FkcyI7czoxOiJ5IjtzOjE1OiJyZWRpcmVjdF9tZXRob2QiO3M6ODoicmVkaXJlY3QiO3M6OToiZGVmdF9sYW5nIjtzOjc6ImVuZ2xpc2giO3M6ODoieG1sX2xhbmciO3M6MjoiZW4iO3M6MTI6InNlbmRfaGVhZGVycyI7czoxOiJ5IjtzOjExOiJnemlwX291dHB1dCI7czoxOiJuIjtzOjEzOiJsb2dfcmVmZXJyZXJzIjtzOjE6Im4iO3M6MTM6Im1heF9yZWZlcnJlcnMiO3M6MzoiNTAwIjtzOjExOiJkYXRlX2Zvcm1hdCI7czo4OiIlbi8lai8leSI7czoxMToidGltZV9mb3JtYXQiO3M6MjoiMTIiO3M6MTM6InNlcnZlcl9vZmZzZXQiO3M6MDoiIjtzOjIxOiJkZWZhdWx0X3NpdGVfdGltZXpvbmUiO3M6MTY6IkFtZXJpY2EvTmV3X1lvcmsiO3M6MTM6Im1haWxfcHJvdG9jb2wiO3M6NDoibWFpbCI7czoxMToic210cF9zZXJ2ZXIiO3M6MDoiIjtzOjEzOiJzbXRwX3VzZXJuYW1lIjtzOjA6IiI7czoxMzoic210cF9wYXNzd29yZCI7czowOiIiO3M6MTE6ImVtYWlsX2RlYnVnIjtzOjE6Im4iO3M6MTM6ImVtYWlsX2NoYXJzZXQiO3M6NToidXRmLTgiO3M6MTU6ImVtYWlsX2JhdGNobW9kZSI7czoxOiJuIjtzOjE2OiJlbWFpbF9iYXRjaF9zaXplIjtzOjA6IiI7czoxMToibWFpbF9mb3JtYXQiO3M6NToicGxhaW4iO3M6OToid29yZF93cmFwIjtzOjE6InkiO3M6MjI6ImVtYWlsX2NvbnNvbGVfdGltZWxvY2siO3M6MToiNSI7czoyMjoibG9nX2VtYWlsX2NvbnNvbGVfbXNncyI7czoxOiJ5IjtzOjg6ImNwX3RoZW1lIjtzOjc6ImRlZmF1bHQiO3M6MjE6ImVtYWlsX21vZHVsZV9jYXB0Y2hhcyI7czoxOiJuIjtzOjE2OiJsb2dfc2VhcmNoX3Rlcm1zIjtzOjE6InkiO3M6MTk6ImRlbnlfZHVwbGljYXRlX2RhdGEiO3M6MToieSI7czoyNDoicmVkaXJlY3Rfc3VibWl0dGVkX2xpbmtzIjtzOjE6Im4iO3M6MTY6ImVuYWJsZV9jZW5zb3JpbmciO3M6MToibiI7czoxNDoiY2Vuc29yZWRfd29yZHMiO3M6MDoiIjtzOjE4OiJjZW5zb3JfcmVwbGFjZW1lbnQiO3M6MDoiIjtzOjEwOiJiYW5uZWRfaXBzIjtzOjA6IiI7czoxMzoiYmFubmVkX2VtYWlscyI7czowOiIiO3M6MTY6ImJhbm5lZF91c2VybmFtZXMiO3M6MDoiIjtzOjE5OiJiYW5uZWRfc2NyZWVuX25hbWVzIjtzOjA6IiI7czoxMDoiYmFuX2FjdGlvbiI7czo4OiJyZXN0cmljdCI7czoxMToiYmFuX21lc3NhZ2UiO3M6MzQ6IlRoaXMgc2l0ZSBpcyBjdXJyZW50bHkgdW5hdmFpbGFibGUiO3M6MTU6ImJhbl9kZXN0aW5hdGlvbiI7czoyMToiaHR0cDovL3d3dy55YWhvby5jb20vIjtzOjE2OiJlbmFibGVfZW1vdGljb25zIjtzOjE6InkiO3M6MTI6ImVtb3RpY29uX3VybCI7czozMToiaHR0cDovL2VlMi50ZXN0L2ltYWdlcy9zbWlsZXlzLyI7czoxOToicmVjb3VudF9iYXRjaF90b3RhbCI7czo0OiIxMDAwIjtzOjE3OiJuZXdfdmVyc2lvbl9jaGVjayI7czoxOiJ5IjtzOjE3OiJlbmFibGVfdGhyb3R0bGluZyI7czoxOiJuIjtzOjE3OiJiYW5pc2hfbWFza2VkX2lwcyI7czoxOiJ5IjtzOjE0OiJtYXhfcGFnZV9sb2FkcyI7czoyOiIxMCI7czoxMzoidGltZV9pbnRlcnZhbCI7czoxOiI4IjtzOjEyOiJsb2Nrb3V0X3RpbWUiO3M6MjoiMzAiO3M6MTU6ImJhbmlzaG1lbnRfdHlwZSI7czo3OiJtZXNzYWdlIjtzOjE0OiJiYW5pc2htZW50X3VybCI7czowOiIiO3M6MTg6ImJhbmlzaG1lbnRfbWVzc2FnZSI7czo1MDoiWW91IGhhdmUgZXhjZWVkZWQgdGhlIGFsbG93ZWQgcGFnZSBsb2FkIGZyZXF1ZW5jeS4iO3M6MTc6ImVuYWJsZV9zZWFyY2hfbG9nIjtzOjE6InkiO3M6MTk6Im1heF9sb2dnZWRfc2VhcmNoZXMiO3M6MzoiNTAwIjtzOjE3OiJ0aGVtZV9mb2xkZXJfcGF0aCI7czo0NjoiL3ByaXZhdGUvdmFyL3d3dy9leHByZXNzaW9uZW5naW5lLXRlc3QvdGhlbWVzLyI7czoxMDoiaXNfc2l0ZV9vbiI7czoxOiJ5IjtzOjExOiJydGVfZW5hYmxlZCI7czoxOiJ5IjtzOjIyOiJydGVfZGVmYXVsdF90b29sc2V0X2lkIjtzOjE6IjEiO30=', 'YTozOntzOjE5OiJtYWlsaW5nbGlzdF9lbmFibGVkIjtzOjE6InkiO3M6MTg6Im1haWxpbmdsaXN0X25vdGlmeSI7czoxOiJuIjtzOjI1OiJtYWlsaW5nbGlzdF9ub3RpZnlfZW1haWxzIjtzOjA6IiI7fQ==', 'YTo0NDp7czoxMDoidW5fbWluX2xlbiI7czoxOiI0IjtzOjEwOiJwd19taW5fbGVuIjtzOjE6IjUiO3M6MjU6ImFsbG93X21lbWJlcl9yZWdpc3RyYXRpb24iO3M6MToibiI7czoyNToiYWxsb3dfbWVtYmVyX2xvY2FsaXphdGlvbiI7czoxOiJ5IjtzOjE4OiJyZXFfbWJyX2FjdGl2YXRpb24iO3M6NToiZW1haWwiO3M6MjM6Im5ld19tZW1iZXJfbm90aWZpY2F0aW9uIjtzOjE6Im4iO3M6MjM6Im1icl9ub3RpZmljYXRpb25fZW1haWxzIjtzOjA6IiI7czoyNDoicmVxdWlyZV90ZXJtc19vZl9zZXJ2aWNlIjtzOjE6InkiO3M6MjI6InVzZV9tZW1iZXJzaGlwX2NhcHRjaGEiO3M6MToibiI7czoyMDoiZGVmYXVsdF9tZW1iZXJfZ3JvdXAiO3M6MToiNSI7czoxNToicHJvZmlsZV90cmlnZ2VyIjtzOjY6Im1lbWJlciI7czoxMjoibWVtYmVyX3RoZW1lIjtzOjEzOiJhZ2lsZV9yZWNvcmRzIjtzOjE0OiJlbmFibGVfYXZhdGFycyI7czoxOiJ5IjtzOjIwOiJhbGxvd19hdmF0YXJfdXBsb2FkcyI7czoxOiJuIjtzOjEwOiJhdmF0YXJfdXJsIjtzOjMxOiJodHRwOi8vZWUyLnRlc3QvaW1hZ2VzL2F2YXRhcnMvIjtzOjExOiJhdmF0YXJfcGF0aCI7czo1NDoiL3ByaXZhdGUvdmFyL3d3dy9leHByZXNzaW9uZW5naW5lLXRlc3QvaW1hZ2VzL2F2YXRhcnMvIjtzOjE2OiJhdmF0YXJfbWF4X3dpZHRoIjtzOjM6IjEwMCI7czoxNzoiYXZhdGFyX21heF9oZWlnaHQiO3M6MzoiMTAwIjtzOjEzOiJhdmF0YXJfbWF4X2tiIjtzOjI6IjUwIjtzOjEzOiJlbmFibGVfcGhvdG9zIjtzOjE6Im4iO3M6OToicGhvdG9fdXJsIjtzOjM3OiJodHRwOi8vZWUyLnRlc3QvaW1hZ2VzL21lbWJlcl9waG90b3MvIjtzOjEwOiJwaG90b19wYXRoIjtzOjYwOiIvcHJpdmF0ZS92YXIvd3d3L2V4cHJlc3Npb25lbmdpbmUtdGVzdC9pbWFnZXMvbWVtYmVyX3Bob3Rvcy8iO3M6MTU6InBob3RvX21heF93aWR0aCI7czozOiIxMDAiO3M6MTY6InBob3RvX21heF9oZWlnaHQiO3M6MzoiMTAwIjtzOjEyOiJwaG90b19tYXhfa2IiO3M6MjoiNTAiO3M6MTY6ImFsbG93X3NpZ25hdHVyZXMiO3M6MToieSI7czoxMzoic2lnX21heGxlbmd0aCI7czozOiI1MDAiO3M6MjE6InNpZ19hbGxvd19pbWdfaG90bGluayI7czoxOiJuIjtzOjIwOiJzaWdfYWxsb3dfaW1nX3VwbG9hZCI7czoxOiJuIjtzOjExOiJzaWdfaW1nX3VybCI7czo0NToiaHR0cDovL2VlMi50ZXN0L2ltYWdlcy9zaWduYXR1cmVfYXR0YWNobWVudHMvIjtzOjEyOiJzaWdfaW1nX3BhdGgiO3M6Njg6Ii9wcml2YXRlL3Zhci93d3cvZXhwcmVzc2lvbmVuZ2luZS10ZXN0L2ltYWdlcy9zaWduYXR1cmVfYXR0YWNobWVudHMvIjtzOjE3OiJzaWdfaW1nX21heF93aWR0aCI7czozOiI0ODAiO3M6MTg6InNpZ19pbWdfbWF4X2hlaWdodCI7czoyOiI4MCI7czoxNDoic2lnX2ltZ19tYXhfa2IiO3M6MjoiMzAiO3M6MTk6InBydl9tc2dfdXBsb2FkX3BhdGgiO3M6NjE6Ii9wcml2YXRlL3Zhci93d3cvZXhwcmVzc2lvbmVuZ2luZS10ZXN0L2ltYWdlcy9wbV9hdHRhY2htZW50cy8iO3M6MjM6InBydl9tc2dfbWF4X2F0dGFjaG1lbnRzIjtzOjE6IjMiO3M6MjI6InBydl9tc2dfYXR0YWNoX21heHNpemUiO3M6MzoiMjUwIjtzOjIwOiJwcnZfbXNnX2F0dGFjaF90b3RhbCI7czozOiIxMDAiO3M6MTk6InBydl9tc2dfaHRtbF9mb3JtYXQiO3M6NDoic2FmZSI7czoxODoicHJ2X21zZ19hdXRvX2xpbmtzIjtzOjE6InkiO3M6MTc6InBydl9tc2dfbWF4X2NoYXJzIjtzOjQ6IjYwMDAiO3M6MTk6Im1lbWJlcmxpc3Rfb3JkZXJfYnkiO3M6MTE6InRvdGFsX3Bvc3RzIjtzOjIxOiJtZW1iZXJsaXN0X3NvcnRfb3JkZXIiO3M6NDoiZGVzYyI7czoyMDoibWVtYmVybGlzdF9yb3dfbGltaXQiO3M6MjoiMjAiO30=', 'YTo3OntzOjIyOiJlbmFibGVfdGVtcGxhdGVfcm91dGVzIjtzOjE6InkiO3M6MTE6InN0cmljdF91cmxzIjtzOjE6InkiO3M6ODoic2l0ZV80MDQiO3M6OToiYWJvdXQvNDA0IjtzOjE5OiJzYXZlX3RtcGxfcmV2aXNpb25zIjtzOjE6Im4iO3M6MTg6Im1heF90bXBsX3JldmlzaW9ucyI7czoxOiI1IjtzOjE1OiJzYXZlX3RtcGxfZmlsZXMiO3M6MToibiI7czoxODoidG1wbF9maWxlX2Jhc2VwYXRoIjtzOjE6Ii8iO30=', 'YTo5OntzOjIxOiJpbWFnZV9yZXNpemVfcHJvdG9jb2wiO3M6MzoiZ2QyIjtzOjE4OiJpbWFnZV9saWJyYXJ5X3BhdGgiO3M6MDoiIjtzOjE2OiJ0aHVtYm5haWxfcHJlZml4IjtzOjU6InRodW1iIjtzOjE0OiJ3b3JkX3NlcGFyYXRvciI7czo0OiJkYXNoIjtzOjE3OiJ1c2VfY2F0ZWdvcnlfbmFtZSI7czoxOiJuIjtzOjIyOiJyZXNlcnZlZF9jYXRlZ29yeV93b3JkIjtzOjg6ImNhdGVnb3J5IjtzOjIzOiJhdXRvX2NvbnZlcnRfaGlnaF9hc2NpaSI7czoxOiJuIjtzOjIyOiJuZXdfcG9zdHNfY2xlYXJfY2FjaGVzIjtzOjE6InkiO3M6MjM6ImF1dG9fYXNzaWduX2NhdF9wYXJlbnRzIjtzOjE6InkiO30=', '');


INSERT INTO `exp_snippets` (`snippet_id`, `site_id`, `snippet_name`, `snippet_contents`) VALUES (1, 1, '.htaccess', 'deny from all'), (2, 1, 'global_edit_this', '{if author_id == logged_in_member_id OR logged_in_group_id == "1"}&bull; <a href="{cp_url}?S={cp_session_id}&amp;D=cp&amp;C=content_publish&amp;M=entry_form&amp;channel_id={channel_id}&amp;entry_id={entry_id}">Edit This</a>{/if}'), (3, 1, 'global_featured_band', '<div id="featured_band">
    <h2>Featured Band</h2>
    {exp:channel:entries channel="news" limit="1" status="featured" rdf="off" disable="trackbacks" category="2" dynamic="no"}
    <div class="image">
        <h4><a href="{comment_url_title_auto_path}"><span>{title}</span></a></h4>
        {if news_image}
			<img src="{news_image}" alt="{title}"/>
		{/if}
    </div>
    {news_body}
    {/exp:channel:entries}
</div>'), (4, 1, 'global_featured_welcome', '<div id="welcome">
    {exp:channel:entries channel="about" url_title="about_the_label" dynamic="no"  limit="1" disable="pagination|member_date|categories|category_fields|trackbacks"}
    {if about_image != ""}
        <img src="{about_image}" alt="map" width="210" height="170" />
    {/if}
    {about_body}
    <a href="{comment_url_title_auto_path}">Read more about us</a>
    {/exp:channel:entries}
</div>'), (5, 1, 'global_footer', '<div id="siteinfo">
    <p>Copyright @ {exp:channel:entries limit="1" sort="asc" disable="custom_fields|comments|pagination|categories"}

{if "{entry_date format=\'%Y\'}" != "{current_time format=\'%Y\'}"}{entry_date format="%Y"} - {/if}{/exp:channel:entries} {current_time format="%Y"}, powered by <a href="http://expressionengine.com">ExpressionEngine</a></p>
    <p class="logo"><a href="#">Agile Records</a></p>
	{if group_id == "1"}<p>{total_queries} queries in {elapsed_time} seconds</p>{/if}
</div> <!-- ending #siteinfo -->'), (6, 1, 'global_strict_urls', '<!-- Strict URLS: http://ellislab.com/expressionengine/user-guide/cp/templates/global_template_preferences.html -->
{if segment_2 != \'\'}
  {redirect="404"}
{/if}'), (7, 1, 'global_stylesheets', '<!-- CSS -->
<!-- This makes use of the stylesheet= parameter, which automatically appends a time stamp to allow for the browser\'s caching mechanism to cache the stylesheet.  This allows for faster page-loads times.
Stylesheet linking is documented at http://ellislab.com/expressionengine/user-guide/templates/globals/stylesheet.html -->
    <link href="{stylesheet=global_embeds/site_css}" type="text/css" rel="stylesheet" media="screen" />
    <!--[if IE 6]><link href="{stylesheet=global_embeds/css_screen-ie6}" type="text/css" rel="stylesheet" media="screen" /><![endif]-->
    <!--[if IE 7]><link href="{stylesheet=global_embeds/css_screen-ie7}" type="text/css" rel="stylesheet" media="screen" /><![endif]-->
'), (8, 1, 'global_top_member', '<div id="member">

	<!-- Utilized member conditionals: http://ellislab.com/expressionengine/user-guide/templates/globals/conditionals.html-->
            <h4>Hello{if logged_in} {screen_name}{/if}!</h4>
            {if is_core == FALSE}
			<ul>
				{if logged_in}
                <li><a href="{path=\'member/profile\'}">Your Home</a></li>
                <li><a href="{path=LOGOUT}">Log out</a></li>
				{/if}
				{if logged_out}
				<li><a href="{path=\'member/register\'}">Register</a></li>
				<li><a href="{path=\'member/login\'}">Log in</a></li>
				{/if}
            </ul>
			{/if}
        </div> <!-- ending #member -->'), (9, 1, 'global_top_search', '<!-- Simple Search Form: http://ellislab.com/expressionengine/user-guide/modules/search/index.html#simple

The parameters here help to identify what templates to use and where to search:

Results page - result_page: http://ellislab.com/expressionengine/user-guide/modules/search/simple.html#par_result_page

No Results found: no_result_page: http://ellislab.com/expressionengine/user-guide/modules/search/simple.html#par_no_result_page

search_in - search in titles? titles and entries? titles, entries?  http://ellislab.com/expressionengine/user-guide/modules/search/simple.html#par_search_in-->

{exp:search:simple_form channel="news" result_page="search/results" no_result_page="search/no_results" search_in="everywhere"}
<fieldset>
    <label for="search">Search:</label>
    <input type="text" name="keywords" id="search" value=""  />
	<input type="image" id="submit" name="submit" class="submit" src="{site_url}themes/site_themes/agile_records/images/spacer.gif" />
</fieldset>
{/exp:search:simple_form}'), (10, 1, 'news_calendar', '<h5>Calendar</h5>
		<div id="news_calendar">

			<!-- Channel Calendar Tag: http://ellislab.com/expressionengine/user-guide/modules/channel/calendar.html -->

			{exp:channel:calendar switch="calendarToday|calendarCell" channel="news"}
			<table class="calendarBG" border="0" cellpadding="6" cellspacing="1" summary="My Calendar">
			<tr class="calendarHeader">
			<th><div class="calendarMonthLinks"><a href="{previous_path=\'news/archives\'}">&lt;&lt;</a></div></th>
			<th colspan="5">{date format="%F %Y"}</th>
			<th><div class="calendarMonthLinks"><a class="calendarMonthLinks" href="{next_path=\'news/archives\'}">&gt;&gt;</a></div></th>
			</tr>
			<tr>
			{calendar_heading}
			<td class="calendarDayHeading">{lang:weekday_abrev}</td>
			{/calendar_heading}
			</tr>

			{calendar_rows }
			{row_start}<tr>{/row_start}

			{if entries}
			<td class=\'{switch}\' align=\'center\'><a href="{day_path=\'news/archives\'}">{day_number}</a></td>
			{/if}

			{if not_entries}
			<td class=\'{switch}\' align=\'center\'>{day_number}</td>
			{/if}

			{if blank}
			<td class=\'calendarBlank\'>{day_number}</td>
			{/if}

			{row_end}</tr>{/row_end}
			{/calendar_rows}
			</table>
			{/exp:channel:calendar}
		</div> <!-- ending #news_calendar -->'), (11, 1, 'news_categories', '<div id="sidebar_category_archives">
      		<h5>Categories</h5>
  			<ul id="categories">
  				<!-- Weblog Categories tag: http://ellislab.com/expressionengine/user-guide/modules/weblog/categories.html -->

  				{exp:channel:categories channel="news" style="linear"}
  				<li><a href="{path=\'news/archives\'}">{category_name}</a></li>
  				{/exp:channel:categories}
  			</ul>
  		</div>'), (12, 1, 'news_month_archives', '<div id="sidebar_date_archives">
    	    <h5>Date Archives</h5>
    		<ul id="months">
    			{!-- Archive Month Link Tags: http://ellislab.com/expressionengine/user-guide/modules/weblog/archive_month_links.html --}

    			{exp:channel:month_links channel="news" limit="50"}
    			<li><a href="{path=\'news/archives\'}">{month}, {year}</a></li>
    			{/exp:channel:month_links}
    		</ul>
    	</div>'), (13, 1, 'news_popular', '<h5>Popular News Items</h5>

<!-- Channel Entries tag ordered by track views for "popular posts".  See Tracking Entry Views at http://ellislab.com/expressionengine/user-guide/modules/weblog/entry_tracking.html -->

{exp:channel:entries channel="news" limit="4" disable="categories|custom_fields|category_fields|trackbacks|pagination|member_data" dynamic="no"}
	{if count == "1"}<ul>{/if}
		<li><a href="{comment_url_title_auto_path}">{title}</a> </li>
	{if count == total_results}</ul>{/if}
{/exp:channel:entries}');


INSERT INTO `exp_specialty_templates` (`template_id`, `site_id`, `enable_template`, `template_name`, `data_title`, `template_data`) VALUES (1, 1, 'y', 'offline_template', '', '<html>
<head>

<title>System Offline</title>

<style type="text/css">

body {
background-color:	#ffffff;
margin:				50px;
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-size:			11px;
color:				#000;
background-color:	#fff;
}

a {
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-weight:		bold;
letter-spacing:		.09em;
text-decoration:	none;
color:			  #330099;
background-color:	transparent;
}

a:visited {
color:				#330099;
background-color:	transparent;
}

a:hover {
color:				#000;
text-decoration:	underline;
background-color:	transparent;
}

#content  {
border:				#999999 1px solid;
padding:			22px 25px 14px 25px;
}

h1 {
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-weight:		bold;
font-size:			14px;
color:				#000;
margin-top: 		0;
margin-bottom:		14px;
}

p {
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-size: 			12px;
font-weight: 		normal;
margin-top: 		12px;
margin-bottom: 		14px;
color: 				#000;
}
</style>

</head>

<body>

<div id="content">

<h1>System Offline</h1>

<p>This site is currently offline</p>

</div>

</body>

</html>'), (2, 1, 'y', 'message_template', '', '<html>
<head>

<title>{title}</title>

<meta http-equiv=\'content-type\' content=\'text/html; charset={charset}\' />

{meta_refresh}

<style type="text/css">

body {
background-color:	#ffffff;
margin:				50px;
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-size:			11px;
color:				#000;
background-color:	#fff;
}

a {
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
letter-spacing:		.09em;
text-decoration:	none;
color:			  #330099;
background-color:	transparent;
}

a:visited {
color:				#330099;
background-color:	transparent;
}

a:active {
color:				#ccc;
background-color:	transparent;
}

a:hover {
color:				#000;
text-decoration:	underline;
background-color:	transparent;
}

#content  {
border:				#000 1px solid;
background-color: 	#DEDFE3;
padding:			22px 25px 14px 25px;
}

h1 {
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-weight:		bold;
font-size:			14px;
color:				#000;
margin-top: 		0;
margin-bottom:		14px;
}

p {
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-size: 			12px;
font-weight: 		normal;
margin-top: 		12px;
margin-bottom: 		14px;
color: 				#000;
}

ul {
margin-bottom: 		16px;
}

li {
list-style:			square;
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-size: 			12px;
font-weight: 		normal;
margin-top: 		8px;
margin-bottom: 		8px;
color: 				#000;
}

</style>

</head>

<body>

<div id="content">

<h1>{heading}</h1>

{content}

<p>{link}</p>

</div>

</body>

</html>'), (3, 1, 'y', 'admin_notify_reg', 'Notification of new member registration', 'New member registration site: {site_name}

Screen name: {name}
User name: {username}
Email: {email}

Your control panel URL: {control_panel_url}'), (4, 1, 'y', 'admin_notify_entry', 'A new channel entry has been posted', 'A new entry has been posted in the following channel:
{channel_name}

The title of the entry is:
{entry_title}

Posted by: {name}
Email: {email}

To read the entry please visit:
{entry_url}
'), (5, 1, 'y', 'admin_notify_mailinglist', 'Someone has subscribed to your mailing list', 'A new mailing list subscription has been accepted.

Email Address: {email}
Mailing List: {mailing_list}'), (6, 1, 'y', 'admin_notify_comment', 'You have just received a comment', 'You have just received a comment for the following channel:
{channel_name}

The title of the entry is:
{entry_title}

Located at:
{comment_url}

Posted by: {name}
Email: {email}
URL: {url}
Location: {location}

{comment}'), (7, 1, 'y', 'mbr_activation_instructions', 'Enclosed is your activation code', 'Thank you for your new member registration.

To activate your new account, please visit the following URL:

{unwrap}{activation_url}{/unwrap}

Thank You!

{site_name}

{site_url}'), (8, 1, 'y', 'forgot_password_instructions', 'Login information', '{name},

To reset your password, please go to the following page:

{reset_url}

Then log in with your username: {username}

If you do not wish to reset your password, ignore this message. It will expire in 24 hours.

{site_name}
{site_url}'), (9, 1, 'y', 'validated_member_notify', 'Your membership account has been activated', '{name},

Your membership account has been activated and is ready for use.

Thank You!

{site_name}
{site_url}'), (10, 1, 'y', 'decline_member_validation', 'Your membership account has been declined', '{name},

We\'re sorry but our staff has decided not to validate your membership.

{site_name}
{site_url}'), (11, 1, 'y', 'mailinglist_activation_instructions', 'Email Confirmation', 'Thank you for joining the "{mailing_list}" mailing list!

Please click the link below to confirm your email.

If you do not want to be added to our list, ignore this email.

{unwrap}{activation_url}{/unwrap}

Thank You!

{site_name}'), (12, 1, 'y', 'comment_notification', 'Someone just responded to your comment', '{name_of_commenter} just responded to the entry you subscribed to at:
{channel_name}

The title of the entry is:
{entry_title}

You can see the comment at the following URL:
{comment_url}

{comment}

To stop receiving notifications for this comment, click here:
{notification_removal_url}'), (13, 1, 'y', 'comments_opened_notification', 'New comments have been added', 'Responses have been added to the entry you subscribed to at:
{channel_name}

The title of the entry is:
{entry_title}

You can see the comments at the following URL:
{comment_url}

{comments}
{comment}
{/comments}

To stop receiving notifications for this entry, click here:
{notification_removal_url}'), (14, 1, 'y', 'private_message_notification', 'Someone has sent you a Private Message', '
{recipient_name},

{sender_name} has just sent you a Private Message titled ‘{message_subject}’.

You can see the Private Message by logging in and viewing your inbox at:
{site_url}

Content:

{message_content}

To stop receiving notifications of Private Messages, turn the option off in your Email Settings.

{site_name}
{site_url}'), (15, 1, 'y', 'pm_inbox_full', 'Your private message mailbox is full', '{recipient_name},

{sender_name} has just attempted to send you a Private Message,
but your inbox is full, exceeding the maximum of {pm_storage_limit}.

Please log in and remove unwanted messages from your inbox at:
{site_url}');


INSERT INTO `exp_stats` (`stat_id`, `site_id`, `total_members`, `recent_member_id`, `recent_member`, `total_entries`, `total_forum_topics`, `total_forum_posts`, `total_comments`, `last_entry_date`, `last_forum_post_date`, `last_comment_date`, `last_visitor_date`, `most_visitors`, `most_visitor_date`, `last_cache_clear`) VALUES (1, 1, 1, 1, 'Admin', 1, 0, 0, 0, 1394136209, 0, 0, 0, 0, 0, 1394136209);


INSERT INTO `exp_status_groups` (`group_id`, `site_id`, `group_name`) VALUES (1, 1, 'Statuses');




INSERT INTO `exp_statuses` (`status_id`, `site_id`, `group_id`, `status`, `status_order`, `highlight`) VALUES (1, 1, 1, 'open', 1, '009933'), (2, 1, 1, 'closed', 2, '990000'), (3, 1, 1, 'Featured', 3, '000000');


INSERT INTO `exp_template_groups` (`group_id`, `site_id`, `group_name`, `group_order`, `is_site_default`) VALUES (1, 1, 'about', 1, 'n'), (2, 1, 'global_embeds', 2, 'n'), (3, 1, 'news', 3, 'y'), (4, 1, 'search', 4, 'n');




INSERT INTO `exp_template_no_access` (`template_id`, `member_group`) VALUES (1, 2), (2, 2), (3, 2), (4, 2), (4, 3), (4, 4), (4, 5), (5, 2), (6, 2), (7, 2), (8, 2), (9, 2), (10, 2), (11, 2), (12, 2), (13, 2), (14, 2), (15, 2), (16, 2), (16, 3), (16, 4), (16, 5), (17, 2), (18, 2);




INSERT INTO `exp_templates` (`template_id`, `site_id`, `group_id`, `template_name`, `save_template_file`, `template_type`, `template_data`, `template_notes`, `edit_date`, `last_author_id`, `cache`, `refresh`, `no_auth_bounce`, `enable_http_auth`, `allow_php`, `php_parse_location`, `hits`) VALUES (1, 1, 1, 'index', 'n', 'webpage', '{!-- Explanations and learning materials can be found in news/index and the other news template groups.  In-line comments here are only for features not introduced in news/index. --}
{html_head}
	<title>{site_name}: Contact Us</title>
{global_stylesheets}

{rss}
{favicon}
{html_head_end}
	<body>
{nav_access}
	{branding_begin}
			{embed="global_embeds/.top_nav" loc="about"}
			{global_top_search}
			{global_top_member}
	{branding_end}
	{wrapper_begin}
{embed="global_embeds/.page_header" header="About"}


<div id="feature" class="about">
	{exp:channel:entries channel="about" url_title="about_the_label" dynamic="no"  limit="1" disable="pagination|member_data|categories|category_fields"}
		<h3 class="about">{title}</h3>
		{about_body}
	{/exp:channel:entries}
</div> <!-- ending #feature -->

	<div class="feature_end"></div>

<div id="content_pri" class="about"> <!-- This is where all primary content, left column gets entered -->

		<!-- Standard Channel Entries tag, but instead of relying on the URL for what to display, we request a specific entry for display via url-title:
	http://ellislab.com/expressionengine/user-guide/modules/channel/parameters.html#par_url_title

	and we force the channel entries tag to ignore the URL and always deliver the same content by using dynamic="no":

	http://ellislab.com/expressionengine/user-guide/modules/channel/parameters.html#par_dynamic
	-->

		{exp:channel:entries channel="about" dynamic="no" url_title="about_the_label" limit="1" disable="pagination|member_data|categories|category_fields"}
			{about_extended}
		{/exp:channel:entries}
</div>

<div id="content_sec" class="staff_profiles right green40">
		<h3 class="staff">Staff Profiles</h3>
		{exp:channel:entries channel="about" limit="6" category="3" dynamic="off" orderby="date" sort="asc"}
			{if count == "1"}<ul class="staff_member">{/if}
				<li class="{switch="||end"}">
					<h4>{title} <a href="#">i</a></h4>
					<div class="profile">
						{about_staff_title}
					</div>
					<img src="{about_image}" alt="{title}" />
				</li>
			{if count == total_results}</ul>{/if}
		{/exp:channel:entries}

</div>	<!-- ending #content_sec -->



{global_footer}
{wrapper_close}
{js}
{html_close}', NULL, 1394136209, 1, 'n', 0, '', 'n', 'n', 'o', 0), (2, 1, 1, '404', 'n', 'webpage', '{!-- Explanations and learning materials can be found in news/index and the other news template groups.  In-line comments here are only for features not introduced in news/index. --}
{html_head}
	<title>{site_name}: Not Found</title>
{global_stylesheets}

{rss}
{favicon}
{html_head_end}
	<body>
{nav_access}
	{branding_begin}
			{embed="global_embeds/.top_nav" loc="contact"}
			{global_top_search}
			{global_top_member}
	{branding_end}
	{wrapper_begin}
{embed="global_embeds/.page_header" header="Not Found"}


	<div id="content_pri"> <!-- This is where all primary content, left column gets entered -->
		<h4>Not Found</h4>
				 <p>The page you attempted to load was Not Found.  Please try again.</p>
	</div>


		<div id="content_sec" class="right green40">
			<h3 class="oldernews">Browse Older News</h3>
			<div id="news_archives">
				<div id="categories_box">
				{news_categories}
				</div>
				<div id="month_box">
				{news_month_archives}
				</div>
			</div> <!-- ending #news_archives -->

			{news_calendar}

			{news_popular}

		{rss_links}

		</div>	<!-- ending #content_sec -->

	{global_footer}
	{wrapper_close}
	{js}
	{html_close}', NULL, 1394136209, 1, 'n', 0, '', 'n', 'n', 'o', 0), (3, 1, 1, 'contact', 'n', 'webpage', '{!-- Explanations and learning materials can be found in news/index and the other news template groups.  In-line comments here are only for features not introduced in news/index. --}
{html_head}
	<title>{site_name}: Contact Us</title>
{global_stylesheets}

{rss}
{favicon}
{html_head_end}
	<body>
{nav_access}
	{branding_begin}
			{embed="global_embeds/.top_nav" loc="contact"}
			{global_top_search}
			{global_top_member}
	{branding_end}
	{wrapper_begin}
{embed="global_embeds/.page_header" header="Contact Us"}
    <div id="feature" class="contact">
		<h3 class="getintouch">Get in Touch</h3>


<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat .</p>
</div> <!-- ending #feature -->

	<div class="feature_end"></div>

	<div id="content_pri" class="contact"> <!-- This is where all primary content, left column gets entered -->

			<!-- This uses the Email Module\'s Contact Form: http://ellislab.com/expressionengine/user-guide/modules/email/contact_form.html -->
			{exp:email:contact_form user_recipients="false" recipients="admin@example.com" charset="utf-8"}
			<fieldset id="contact_fields">
			<label for="from">
				<span>Your Email:</span>
				<input type="text" id="from" name="from" value="{member_email}" />
			</label>

			<label for="subject">
				<span>Subject:</span>
				<input type="text" id="subject" name="subject" size="40" value="Contact Form" />
			</label>

			<label for="message">
				<span>Message:</span>
				<textarea id="message" name="message" rows="18" cols="40">Email from: {member_name}, Sent at: {current_time format="%Y %m %d"}</textarea>
			</label>
			</fieldset>

			<fieldset id="contact_action">
				<p>We will never pass on your details to third parties.</p>
				<input name="submit" type=\'submit\' value=\'Submit\' id=\'contactSubmit\' />
			</fieldset>
			{/exp:email:contact_form}
	</div>

	<div id="content_sec" class="contact">
		<h3 class="address">Address</h3>
		 <p>
			12343 Valencia Street,<br />
			Mission District,<br />
			San Francisco,<br />
			California,<br />
			ZIP 123
			 </p>
	<p><img src="{site_url}themes/site_themes/agile_records/images/uploads/map2.jpg" alt="" /></p>

	</div>	<!-- ending #content_sec -->

{global_footer}
{wrapper_close}
{js}
{html_close}', NULL, 1394136209, 1, 'n', 0, '', 'n', 'n', 'o', 0), (4, 1, 2, 'index', 'n', 'webpage', NULL, NULL, 1394136209, 1, 'n', 0, '', 'n', 'n', 'o', 0), (5, 1, 2, '.page_header', 'n', 'webpage', '<div id="page_header">
        <h2>{embed:header}</h2>
    </div>
', NULL, 1394136209, 1, 'n', 0, '', 'n', 'n', 'o', 0), (6, 1, 2, '.top_nav', 'n', 'webpage', ' <ul id="navigation_pri">
            <li id="home" {if embed:loc== "home"}class="cur"{/if}><a href="{homepage}">Home</a></li>
            <li id="events" {if embed:loc == "about"}class="cur"{/if}><a href="{path=\'about/index\'}">About</a></li>
            <li id="contact" {if embed:loc=="contact"}class="cur"{/if}><a href="{path=\'about/contact\'}">Contact</a></li>
        </ul>', NULL, 1394136209, 1, 'n', 0, '', 'n', 'n', 'o', 0), (7, 1, 2, 'css_screen-ie6', 'n', 'css', '/*

	AGILE RECORDS, EE2.0 EXAMPLE SITE by ERSKINE DESIGN
	VERSION 1.0
	IE6 OVERRIDE STYLES

	CONTENTS ----------



	-------------------

*/



ul#nav_access { position:static; display:none; }

div#feature { margin-bottom:10px !important; }
hr.legend_start,
hr.feature_end { display:none !important; }

/* TABLES */

table { background-image:none; background-color:#ddd; font-size:12px; }
tr.alt { background-image:none; background-color:#eee; }
th { background-image:none; background-color:#ddd; }



/* LAYOUT */

div#feature { width:950px; overflow:hidden; float:none; padding:30px 0; position:static; margin:0; background:url({site_url}themes/site_themes/agile_records/images/feature_bg.jpg); margin-bottom:30px; }
div#page_header { background-image:none; background-color:#6b5f57; height:40px; z-index:3; position:static; top:0px; margin-bottom:0; padding-bottom:10px; }

div#branding { height:290px; background:url({site_url}themes/site_themes/agile_records/images/ie_branding_bg.gif) repeat-x center top; position:relative; z-index:2; }
div#branding_sub { width:930px; margin:0 auto; position:relative; }

div#page { background:url({site_url}themes/site_themes/agile_records/images/page_bg.jpg); }

div#content_pri { display:inline; }
div#content_sec { }



/* BRANDING/MASTHEAD */

div#branding_logo { background:url({site_url}themes/site_themes/agile_records/images/ie_branding_sub_bg.gif) no-repeat left top; }
div#branding_sub h1 a { position:static; background:url({site_url}themes/site_themes/agile_records/images/logo_bg.jpg) no-repeat bottom left; }
div#branding_sub div#member { background:none; }
div#branding_sub form { background:url({site_url}themes/site_themes/agile_records/images/ie_search_bg.jpg) no-repeat; }




/* NAVIGATION */

ul#navigation_pri { background-image:none; background-color:#2f261d; }
ul#navigation_pri li { height:auto; text-indent:0; font-family:"Cooper Black",Arial; font-weight:bold; }
ul#navigation_pri li a:link,
ul#navigation_pri li a:visited { background:none; text-decoration:none; color:#a09f9d;}
ul#navigation_pri li a:hover,
ul#navigation_pri li a:focus { color:#ccc;}
ul#navigation_pri li.cur a:link,
ul#navigation_pri li.cur a:visited,
ul#navigation_pri li.cur a:hover,
ul#navigation_pri li.cur a:focus { color:#d55401; }
ul#navigation_pri li#home,
ul#navigation_pri li#events,
ul#navigation_pri li#contact { top:8px; }
ul#navigation_pri li#bands,
ul#navigation_pri li#news,
ul#navigation_pri li#forums { top:30px; }
ul#navigation_pri li#releases,
ul#navigation_pri li#about,
ul#navigation_pri li#wiki { top:54px; }



/* HEADINGS */
div#page_header { height:1px; z-index:99; position:static; top:0; margin-bottom:0; }
div#page_header h2 { text-indent:0 !important; background:none !important; color:#e6e6e6 !important; padding-top:15px !important; float:left;}
div#page_header ol#breadcrumbs { margin-top:10px; padding:0; background:none; }
div#page_header ol#breadcrumbs li { margin-left:10px; }

h2,h3 { text-indent:0 !important; background:none !important; width:auto !important; height:auto !important; }



/* HOMEPAGE */

.home div#feature div#featured_band { width:450px; float:left; position:static; margin:0px; }
.home div#feature div#featured_band h2 { margin-bottom:5px; width:auto; height:auto; text-indent:0; background:none; }

.home div#content_sec { display:inline; margin:0 30px 0 10px; }

.home div#feature div#featured_band div.image { width:300px; height:200px; left:0; bottom:-10px; margin:0 10px 0 10px; padding:0; display:inline; }
.home div#feature div#featured_band div.image h4 { height:auto; width:auto; background:none; margin:0; top:auto; bottom:0; }
.home div#feature div#featured_band div.image h4 span { position:static; background:none; }
.home div#feature div#featured_band div.image img { top:0; left:0; }

.home div#homepage_events ul { padding-bottom:30px; }
.home div#homepage_events ul li a { background:none !important; text-indent:0 !important; text-align:center; color:#fff; font-weight:bold; }

.home div#homepage_forums ul,
.home div#homepage_rss p,
.home div#homepage_rss ul { background-image:none; background-color:#eee; }



/* BANDS */

.bands ul#bands1 li.one { width:450px; height:300px; left:-480px; top:0; margin-right:-450px; margin-bottom:30px; }
.bands ul#bands1 li.one img { top:0; left:0; }

.bands ul#bands1 li.two img,
.bands ul#bands1 li.three img { padding:0; background:none; position:static; margin:0; margin:0 10px; }

.band div#band_image { width:450px; height:300px; float:left; position:relative; left:10px; top:0px; margin:0 30px 30px 0; display:inline; }
.band div#band_image img { top:0; left:0; }

div#band_latestrelease { padding:20px; overflow:hidden; color:#d6d6d6; margin-left:10px; }
div#band_latestrelease h3 { padding-top:20px; }

.band div#content_pri { display:inline; }

.band div#band_events ul { padding-bottom:30px; }
.band div#band_events ul li a { background:none !important; text-indent:0 !important; text-align:center; color:#fff; font-weight:bold; }

.band div#band_more ul { background-image:none; background-color:#eee; }



/* RELEASES */

.releases div#content_pri table th { background:none; text-indent:0; color:#fff; }
.releases div#content_pri table th.release_details { width:360px; padding-right:30px; background:none; }
.releases div#content_pri table th.release_catno { width:80px; background:none; }
.releases div#content_pri table th.release_format { width:120px; background:none; text-align:center; }

.releases div#content_pri table tr { background-image:none; background-color:#a3a39c; }
.releases div#content_pri table tr.releases_head { background:none; }
.releases div#content_pri table tr.alt { background-image:none; background-color:#c1c1bc; }

.release div#content_pri { display:inline; padding-top:30px;}
.release div#content_sec { padding:0; padding-top:30px; background:none; position:relative; left:-10px; }

.release div#release_details { border-bottom:1px solid blue; }
.release div#release_details span { font-family:Georgia,serif; font-style:italic; }
.release div#release_details ul { list-style:url({site_url}themes/site_themes/agile_records/images/pixel.gif); }

.release div#release_tracks div.release_format { float:left; padding-bottom:20px; margin-bottom:20px; }



/* EVENTS */

.events div#content_pri { display:inline; }



/* NEWS */

.news div#content_pri { display:inline; padding:30px 0; }
.news div#content_sec { margin:30px 0 ; }

.news div#news_calendar h6 a.prev { position:static; }
.news div#news_calendar h6 a.next { position:static; }

.news div#news_calendar { background-image:none; background-color:#cfcfcb; }
.news div#news_calendar table td.post { background-image:none; background-color:#d7d7d3; }

.news div#news_rss { background-image:none; background-color:#cfcfcb; }

div#news_comments ol li { background-image:none; background-color:#f1f1f1; }
div#news_comments ol li.alt { background-image:none; background-color:#e7e7e7; }

div#news_comments fieldset#comment_fields label { display:block; width:320px; }
div#news_comments fieldset#comment_fields label.comment { width:530px; }
div#news_comments fieldset#comment_fields label span { width:80px; float:none; position:relative; top:20px; }
div#news_comments fieldset#comment_fields label input { float:right; }
div#news_comments fieldset#comment_fields label textarea { float:right; }

div#news_comments fieldset#comment_fields label input,
div#news_comments fieldset#comment_fields label textarea { background-image:none; background-color:#f1f1f1; }



/* FORUMS */

.forums div#content_pri { display:inline; }
.forums div#content_sec { background-image:none; background-color:#f1f1f1; }

.forums #page_header form { position:absolute; left:770px; padding-top:5px; }
.forums #page_header form input.search { padding:1px; margin-right:10px;}
.forums #page_header form input.submit { padding:0; position:relative; top:5px; }

.forums div#content_pri h3 { background-color:#71715f !important; }

div.forum_posts { background-image:none; background-color:#9e9e94; }
div.forum_posts table tr td { background-image:none; background-color:#d0d0cc;}
div.forum_posts table tr.alt td { background-image:none; background-color:#b3b3ab; }

div.forum_posts table tr th { text-indent:0; color:#fff; }
div.forum_posts table tr th.forum_name,
div.forum_posts table tr th.forum_topics,
div.forum_posts table tr th.forum_replies,
div.forum_posts table tr th.forum_latest { background-image:none; }

div.forum_posts table td.forum_newpostindicator img { position:static; }

.forums div#legend div#forum_stats ul.legend { float:left; background-image:none; background-color:#cecec8; }
.forums div#legend div#forum_stats p.most_visitors { background-image:none; background-color:#ecd2c3; }



/* WIKI */

.wiki div#navigation_sec { padding-top:57px; display:inline;  behavior: url(css/iepngfix/iepngfix.htc); }
.wiki div#navigation_sec ul { background:url({site_url}themes/site_themes/agile_records/images/ie_wiki_menubg.jpg) repeat-y 5px top ; }
.wiki div#navigation_sec div.bottom { behavior: url(css/iepngfix/iepngfix.htc); }



/* MEMBERS CONTROL PANEL */

.member_cp div#navigation_sec { display:inline; background-image:none; background-color:#9c9b92; }
.member_cp div#navigation_sec h4 a.expand { display:none; }

.member_cp div#content_pri table tr { background-image:none; background-color:#f1f1f1; }
.member_cp div#content_pri table tr.alt { background-image:none; background-color:#e7e7e7; }
.member_cp div#content_pri table tr th { background-image:none; background-color:#f1f1f1; }
.member_cp div#content_pri table tr.alt th { background-image:none; background-color:#e7e7e7; }



/* MEMBER PROFILE */

.member_profile div#feature div#memberprofile_main { background-image:none; background-color:#cecec8; margin:20px 0 0 10px; }
.member_profile div#feature div#memberprofile_main ul { padding:0 0 10px 0; }

.member_profile div#feature div#memberprofile_photo { float:left; width:210px; height:180px; background:none; position:relative; left:-20px; }
.member_profile div#feature div#memberprofile_photo img { width:206px; height:176px; border:3px solid #6b5f57; position:static; }

.member_profile div#feature div#memberprofile_communicate { background-image:none; background-color:#adada3; margin-top:5px; }
.member_profile div#feature div#memberprofile_communicate table tr { background-image:none; background-color:#cdcdc7; }
.member_profile div#feature div#memberprofile_communicate table tr.alt { background-image:none; background-color:#bebeb7; }

.member_profile div#content_pri table tr,
.member_profile div#content_sec table tr { background-image:none; background-color:#f1f1f1; }
.member_profile div#content_pri table tr.alt,
.member_profile div#content_sec table tr.alt { background-image:none; background-color:#e7e7e7; }

.member_profile div#content_pri table tr th,
.member_profile div#content_sec table tr th { background-image:none; background-color:#f1f1f1; }
.member_profile div#content_pri table tr.alt th,
.member_profile div#content_sec table tr.alt th { background-image:none; background-color:#e7e7e7; }


', NULL, 1394136209, 1, 'n', 0, '', 'n', 'n', 'o', 0), (8, 1, 2, 'css_screen-ie7', 'n', 'css', 'body {position:relative;}
div#branding {margin:0 auto;}


div#content_wrapper {position:relative;}

div.feature_end {margin-top:0; }
div#content_pri {float:left;margin:0 30px 0 10px;width:600px; padding-left:10px;}
div#content_sec {float:left;width:270px; position:relative; z-index:999;}

div#content_pri.contact {width:520px; margin-right:110px;}
div#content_sec.contact {float:right; margin: 0 10px -140px auto; }


div#page_header {position:relative;z-index:1;}

div#feature{top:-10px;float:none;margin-bottom:30px;padding-top:25px;padding-top:10px;position:relative;width:950px;z-index:900;display:block;}

div.feature_end {clear:none;height:35px;margin-bottom:20px;margin-top:-40px;width:950px;}

/*#content_wrapper.member_cp {padding:0 10px;} */
#content_wrapper.member_cp table {width:550px;}

div#navigation_sec.member_cp {
	width:150px;
	left:10px;
}

div#content_wrapper.member_cp form {margin-left:200px;}', NULL, 1394136209, 1, 'n', 0, '', 'n', 'n', 'o', 0), (9, 1, 2, 'site_css', 'n', 'css', 'html,body,div,span,applet,object,iframe,h1,h2,h3,h4,h5,h6,p,blockquote,pre,a,abbr,acronym,address,big,cite,code,del,dfn,em,font,img,ins,kbd,q,s,samp,small,strike,strong,sub,sup,tt,var,dl,dt,dd,ol,ul,li,fieldset,form,label,legend,table,caption,tbody,tfoot,thead,tr,th,td,hr{margin:0;padding:0;border:0;outline:0;font-weight:inherit;font-style:inherit;font-size:100%;font-family:inherit;vertical-align:baseline;}:focus{outline:0;}body{line-height:1;color:black;background:white;}ol,ul{list-style:none;}table{border-collapse:collapse;border-spacing:0;}caption,th,td{text-align:left;font-weight:normal;}blockquote:before,blockquote:after,q:before,q:after{content:"";}blockquote,q{quotes:"""";}@font-face{font-family:\'miso\';src:url(\'{site_url}themes/site_themes/agile_records/fonts/miso-bold.ttf\');}body{background:#ccc url({site_url}themes/site_themes/agile_records/images/body_bg.jpg) top center;font-size:13px;font-family:Arial,sans-serif;}ul#nav_access{position:absolute;top:-9999px;left:-9999px;}p,ul,dl,ol{margin-bottom:22px;line-height:22px;}ul{list-style:url({site_url}themes/site_themes/agile_records/images/bullet.jpg);}ul li{margin-left:12px;}ol{list-style:decimal;list-style-position:inside;}hr{height:0;border-top:1px solid #ccc;margin-bottom:22px;}abbr{border-bottom:1px dotted;}strong{font-weight:bold;}em{font-style:italic;}h1,h2,h3,h4,h5{font-weight:bold;}h2{color:#48482d;font-size:16px;margin-bottom:10px;}h3{margin-bottom:20px;}h4{margin-bottom:10px;}h5{margin-bottom:10px;}h6{text-transform:uppercase;font-size:11px;color:#666;letter-spacing:1px;margin-bottom:10px;}a:link,a:visited{color:#333;text-decoration:underline;}a:hover,a:focus{color:#111;}h2 a:link,h2 a:visited,h3 a:link,h3 a:visited,h4 a:link,h4 a:visited{text-decoration:none;}

/* Tables */
/* site_url explanation: http://ellislab.com/expressionengine/user-guide/templates/globals/single_variables.html#var_site_url */
/* only site_url will be parsed, other variables will not be parsed unless you call the stylesheet using path= instead of stylesheet=:

http://ellislab.com/expressionengine/user-guide/templates/globals/stylesheet.html */

table{background:url({site_url}themes/site_themes/agile_records/images/white_40.png);font-size:12px;}
tr{border-bottom:1px dotted #999;}
tr.alt{background:url({site_url}themes/site_themes/agile_records/images/white_20.png);}
th,td{padding:10px;}
th{background:url({site_url}themes/site_themes/agile_records/images/white_20.png);color:#666;font-weight:bold;font-size:13px;}
.member_table{width:60%; margin:10px;}
.member_console{width:100%;}

/* Page Styles */
div#branding{height:290px;background:url({site_url}themes/site_themes/agile_records/images/branding_bg.png) repeat-x center top;position:relative;z-index:2;}
div#branding_sub{width:930px;margin:0 auto;position:relative;}
div#page{width:950px;padding-top:50px;margin:0 auto;position:relative;top:0px;margin-top:-80px;z-index:1;background:url({site_url}themes/site_themes/agile_records/images/white_40.png);}
div#content_wrapper{padding-top:30px;}
div#feature{width:950px;background:url({site_url}themes/site_themes/agile_records/images/white_70.png);float:left;padding-top:30px;position:relative;bottom:30px;margin-bottom:-30px;}

div.feature_end {background:transparent url({site_url}themes/site_themes/agile_records/images/agile_sprite.png) no-repeat scroll left -747px; border:none;outline:none;clear:both;height:35px;margin-top:-6px;margin-bottom:20px;width:950px;}

div#legend{width:950px;background:url({site_url}themes/site_themes/agile_records/images/white_70.png);overflow:hidden;position:relative;top:30px;margin-top:-30px;padding:10px 0 30px 0;font-size:11px;}
hr.legend_start{width:950px;clear:both;background:url({site_url}themes/site_themes/agile_records/images/white_70_top.png) no-repeat top left;height:35px;margin:0;margin-top:20px;border:none;}
div#content_pri{width:610px;float:left;margin:0 30px 0 10px;}
div#content_sec{width:270px;float:left;}

input.input { border:1px solid #aaa; position:relative; left:5px; background:url({site_url}themes/site_themes/agile_records/images/white_50.png);}
input.input:focus { background:url({site_url}themes/site_themes/agile_records/images/white_70.png); }
textarea { border:1px solid #aaa; background:url({site_url}themes/site_themes/agile_records/images/white_50.png); }
textarea:focus { background:url({site_url}themes/site_themes/agile_records/images/white_70.png); }




/* Branding */
div#branding_logo{background:url({site_url}themes/site_themes/agile_records/images/agile_sprite.png) no-repeat 9px -428px;margin:0 auto;position:relative;left:-80px;margin-bottom:-230px;height:230px;width:950px;}
div#branding_logo img{display:none;}
div#branding_sub h1 a {width:182px;height:196px;display:block;text-indent:-9999em;background:url({site_url}themes/site_themes/agile_records/images/agile_sprite.png) no-repeat -264px 15px;  padding-top:15px;}
div#branding_sub form{position:absolute; right:130px;top:25px;width:240px;height:51px;background:url({site_url}themes/site_themes/agile_records/images/agile_sprite.png) no-repeat -534px -21px;}
div#branding_sub form fieldset{position:relative;}
div#branding_sub form label{text-indent:-9999em;margin-top:10px;width:60px;padding:5px;position:absolute;left:0px;display:inline;}
div#branding_sub form input#search{background:none;border:none;position:absolute;top:13px;left:70px;width:100px;padding:2px 5px;font-size:11px;color:#fff;}

div#branding_sub form input#submit{position:absolute;right:30px;top:6px; background:transparent url({site_url}themes/site_themes/agile_records/images/agile_sprite.png) no-repeat -587px -77px; width:24px; height:24px; display:block; font-size:1px; border:none; outline:none;}

div#branding_sub div#member{position:absolute;right:0;top:20px;background:url({site_url}themes/site_themes/agile_records/images/brown_40.png);border:1px solid #846f65;color:#ccc;font-size:11px;padding:8px;}
div#branding_sub div#member ul{margin:0;line-height:13px;list-style:disc;}
div#branding_sub div#member h4{margin-bottom:4px;}
div#branding_sub div#member a:link, div#branding_sub div#member a:visited{color:#ccc;}
div#branding_sub div#member a:hover, div#branding_sub div#member a:focus{color:#fff;}

/* Navigation */
ul#navigation_pri{list-style:none;margin:0 auto;padding:5px 15px;width:340px;max-height:100px;background:#2f261d;position:absolute;right:0;bottom:20px;}
ul#navigation_pri li{margin:0;float:left;font-size:16px;width:33%;}
ul#navigation_pri li a{font-family:\'Cooper Black\',miso,\'Georgia\',serif;font-weight:bold;color:#999999;text-decoration:none}
ul#navigation_pri li a:hover{color:#efefef;}
ul#navigation_pri li.cur a{color:#f47424}

/* Footer */
div#siteinfo{background:url({site_url}themes/site_themes/agile_records/images/agile_sprite.png) no-repeat left -287px;height:80px;padding-top:40px;position:relative;clear:both;font-size:12px;z-index:3;}
div#siteinfo p{color:#5b5b42;font-weight:bold;margin:0 0 0 10px;}
div#siteinfo p.logo{width:65px;height:70px;background:url({site_url}themes/site_themes/agile_records/images/agile_sprite.png); text-indent:-9999em;position:absolute;left:865px;bottom:15px;}
div#siteinfo a {color:#5b5b42;text-decoration:underline;}
div#siteinfo a:hover {color:#3B3A25;text-decoration:underline;}
div#siteinfo p.logo a{display:block;}


/* 11.PAGEHEADERS
---------------------------------------------------------------------- */

div#page_header { background:url({site_url}themes/site_themes/agile_records/images/agile_sprite.png) no-repeat left -205px; height:72px; z-index:3; position:relative; top:-25px; margin-bottom:-15px; }

div#page_header h2 { float:left; font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; font-weight: normal; text-transform:uppercase; color:#ebebeb; letter-spacing: -0.01em; }
div#page_header h2 a { display:block; }

div#page_header h2 { margin:0; width:400px; height:15px; padding-top:30px; margin-left:10px;}

div#page_header ol#breadcrumbs { float:left; list-style:none; margin:0; margin-left:10px; margin-top:26px; padding:0px 0 0 20px; background:url({site_url}themes/site_themes/agile_records/images/breadcrumbs_bg.png) no-repeat left center; }
div#page_header ol#breadcrumbs li { margin:0; float:left; font-weight:bold; color:#d6d6d6; text-transform:uppercase; font-size:12px; }
div#page_header ol#breadcrumbs li a { color:#d6d6d6; text-decoration:none; }


/*  Featured Band / Welcome
-------------------------------- */
div#featured_band {width:450px; float:left; position:relative; z-index:5; bottom:52px; margin-bottom:-52px;}
div#welcome {width:450px; float:left; margin:0 30px 0 10px;}
div#welcome img {float:left; margin:0 30px 10px 0;}
div#featured_band h2 {margin-bottom:38px; width:135px; height:14px; font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; font-weight: normal; text-transform:uppercase; color:#ebebeb; letter-spacing: -0.01em;}

div#featured_band div.image { float:right; width:323px; height:243px; position:relative; left:50px; bottom:75px; margin: 0 0 -75px -50px; }
div#featured_band div.image h4 { width:324px; height:243px; background:url({site_url}themes/site_themes/agile_records/images/featuredband_border.png) no-repeat top left; position:absolute; top:0; left:0; z-index:2; }
div#featured_band div.image h4 span { position:absolute; top:177px; left:30px; background:url({site_url}themes/site_themes/agile_records/images/white_70.png); font-size:11px; padding:2px; padding-left:60px; }
div#featured_band div.image img { position:absolute; top:20px; left:15px;}
.green40 {background:transparent url({site_url}themes/site_themes/agile_records/images/green_40.png) repeat scroll 0 0; color:#EEEEEE; float:left; padding:10px;}
div#feature p {margin-left:10px;}

/* News
---------------- */
h3.oldernews {}
ul#news_listing { list-style:none; }
ul#news_listing li { margin:0 0 30px 0; overflow:hidden; }
ul#news_listing li img { float:left; margin:0 10px 10px 0;}
ul#news_listing li p { margin-bottom:10px; }

div#news_archives { overflow:hidden; }
div#news_archives div#categories_box {width:120px; float: left;}
div#news_archives div#months_box {width:120px; float: right;}
div#news_archives ul#categories { width:120px; float:left; margin-right:30px; }
div#news_archives ul#months { width:120px; float:left; }

div#news_calendar { padding:10px; background:url({site_url}themes/site_themes/agile_records/images/white_50.png); margin-bottom:40px; }

div#news_calendar a:link,
div#news_calendar a:visited { color:#666; }
div#news_calendar a:hover,
div#news_calendar a:focus { color:#333; }

div#news_calendar h6 { position:relative; text-align:center; text-transform:uppercase; color:#666; padding:0 0 10px 0; }
div#news_calendar h6 a.prev { position:absolute; left:0; top:-3px; font-size:16px; }
div#news_calendar h6 a.next { position:absolute; right:0; top:-3px; font-size:16px; }

div#news_calendar table { background:none; font-size:11px; width:250px; color:#666; }
div#news_calendar table th { background:url({site_url}themes/site_themes/agile_records/images/green_50.png); color:#ccc; }
div#news_calendar table th,
div#news_calendar table td  { padding:5px 0; text-align:center; }
div#news_calendar table tr { border:none; }
div#news_calendar table td.unused { color:#999; }
div#news_calendar table td.post { background:url({site_url}themes/site_themes/agile_records/images/white_20.png); }
div#news_calendar table td.post:hover { background:url({site_url}themes/site_themes/agile_records/images/white_40.png); }

div#news_rss { padding:10px; background:url({site_url}themes/site_themes/agile_records/images/white_50.png); color:#666; }
div#news_rss ul { list-style:url({site_url}themes/site_themes/agile_records/images/bullet.jpg); margin:0; }
div#news_rss a:link,
div#news_rss a:visited { color:#666; }
div#news_rss a:hover,
div#news_rss a:focus { color:#333; }


/* Staff Profiles */
div#content_sec.staff_profiles {
background:transparent url({site_url}themes/site_themes/agile_records/images/staff_bg.jpg) repeat scroll 0 0;float:right;margin-bottom:-110px;padding:10px;position:relative;top:-140px; right:10px; width:430px;}

/* Comments */
div#news_comments { border-top:#bfbebf 1px solid; padding-top:20px; }

div#news_comments ol { list-style:none; border-top:1px dotted #ccc; margin-bottom:30px; }
div#news_comments ol li { border-bottom:1px dotted #ccc; background:url({site_url}themes/site_themes/agile_records/images/white_70.png); padding:20px 10px 0 160px; font-size:12px; line-height:20px; }
div#news_comments ol li.alt { background:url({site_url}themes/site_themes/agile_records/images/white_50.png); }

div#news_comments ol li h5.commentdata { width:120px; float:left; position:relative; left:-150px; margin-right:-150px; font-size:13px; line-height:20px; }
div#news_comments ol li h5.commentdata span { display:block; font-weight:normal; font-size:11px; }
div#news_comments ol li h5.commentdata img { margin-top:10px; }

div#news_comments h3.leavecomment {color:#47472C; font-family:\'Cooper Black\', miso, \'Georgia\', serif; font-size:20px;}
div#news_comments form { position:relative; margin-bottom:30px; }

div#news_comments fieldset#comment_fields label { display:block; overflow:hidden; font-size:12px; margin-bottom:20px; }
div#news_comments fieldset#comment_fields label span { width:80px; float:left; position:relative; top:5px; }
div#news_comments fieldset#comment_fields label input { border:1px solid #aaa; width:228px; float:left; background:url({site_url}themes/site_themes/agile_records/images/white_50.png); }
div#news_comments fieldset#comment_fields label input:focus { background:url({site_url}themes/site_themes/agile_records/images/white_70.png); }
div#news_comments fieldset#comment_fields label textarea { border:1px solid #aaa; float:left; height:150px; width:438px; background:url({site_url}themes/site_themes/agile_records/images/white_50.png); }
div#news_comments fieldset#comment_fields label textarea:focus { background:url({site_url}themes/site_themes/agile_records/images/white_70.png); }

div#news_comments div#comment_guidelines { width:418px; padding:10px; margin:10px 0 10px 80px; color:#fff; background:#9f9995; }
div#news_comments div#comment_guidelines h6 { font-weight:normal; font-size:12px; margin-bottom:0; }
div#news_comments div#comment_guidelines p { margin:10px 0 0 0 ; font-size:11px; line-height:16px; font-style:italic; }

div#news_comments fieldset#comment_action { background:url({site_url}themes/site_themes/agile_records/images/orange_20.png); padding:10px; font-size:11px; position:relative; }
div#news_comments fieldset#comment_action label { display:block; padding:5px 0; }
div#news_comments fieldset#comment_action label input { position:relative; left:5px; }
div#news_comments fieldset#comment_action input#submit_comment { position:absolute; bottom:10px; right:10px; font-size:12px; }

div#captcha_box img {margin-left: 5px;}

input#captcha {display:block; margin: 5px 0 0 0; border:1px solid #aaa; width:228px; background:url({site_url}themes/site_themes/agile_records/images/white_50.png);}
input#captcha:focus {background:url({site_url}themes/site_themes/agile_records/images/white_70.png);}

/* News Archive Page */
div.archive ul#news_listing li img {float:right; margin:auto auto 10px 10px;}
div.archive ul#news_listing li p {margin-bottom:10px; padding-left:0;}

/* About */
div#content_pri.about {width:450px;}
div#feature.about p {color:#666666;font-weight:bold;margin-left:10px;width:450px;}
div#feature h3.about {font-size:22px; font-family:\'Cooper Black\',miso,\'Georgia\',serif;font-weight:bold;color:#47472C;text-decoration:none; margin:10px 0 20px 10px; width:300px;}


div#content_sec ul.staff_member li {float:left;height:180px;margin:0 35px 40px 0;overflow:hidden;position:relative;width:120px;}

div#content_sec ul.staff_member { list-style:none; overflow:hidden; margin-bottom:-20px; }
div#content_sec ul.staff_member li { width:120px; height:180px; overflow:hidden; position:relative; float:left; margin:0 35px 40px 0; }
div#content_sec ul.staff_member li.end { margin-right:0; }
div#content_sec ul.staff_member li h4 { font-size:12px; padding:5px 5px; background:#afafa8; position:absolute; bottom:0; left:0; z-index:3; color:#fff; width:110px; height:20px; cursor:pointer; }
div#content_sec ul.staff_member li h4 a { position:absolute; right:5px; color:#eee; font-family:Georgia, "Times New Roman", Times, serif; font-style:italic; font-weight:bold; }
div#content_sec ul.staff_member li div.profile { position:absolute; bottom:40px; left:0; background:url({site_url}themes/site_themes/agile_records/images/white_50.png); z-index:2; padding:5px; width:110px; }
div#content_sec ul.staff_member li img { position:absolute; top:0; left:0; }
div.profile {color:#000;}


/* Contact */
div#content_pri.contact { width:530px; margin-right:110px; }
div#content_sec.contact {  width:270px; float:left; padding:10px; padding-bottom:0; background:url({site_url}themes/site_themes/agile_records/images/staff_bg.jpg); position:relative; top:-170px; margin-bottom:-140px; color:#eee; }
div#feature.contact p {color:#666666;font-weight:bold;margin-left:10px;width:600px;}

/*div#feature { padding-left:10px; padding-right:410px; width:530px; }*/
div#feature h3.getintouch { width:140px; font-family:\'Cooper Black\',miso,\'Georgia\',serif;font-size:20px; color:#47472C;text-decoration:none; margin-left:10px;}

div#content_pri form { position:relative; margin-bottom:30px; }

div#content_pri fieldset#contact_fields label { display:block; overflow:hidden; font-size:12px; margin-bottom:20px; }
div#content_pri fieldset#contact_fields label span { width:80px; float:left; position:relative; top:5px; }
div#content_pri fieldset#contact_fields label input { border:1px solid #aaa; width:228px; float:left; background:url({site_url}themes/site_themes/agile_records/images/white_50.png); }
div#content_pri fieldset#contact_fields label input:focus { background:url({site_url}themes/site_themes/agile_records/images/white_70.png); }
div#content_pri fieldset#contact_fields label textarea { border:1px solid #aaa; float:left; height:150px; width:438px; background:url({site_url}themes/site_themes/agile_records/images/white_50.png); }
div#content_pri fieldset#contact_fields label textarea:focus { background:url({site_url}themes/site_themes/agile_records/images/white_70.png); }

div#content_pri div#contact_guidelines { position:absolute; top:0; right:0; width:170px; padding:10px; color:#fff; background:#9f9995; }
div#content_pri div#contact_guidelines h6 { font-weight:normal; font-size:12px; margin-bottom:10px; }
div#content_pri div#contact_guidelines p { margin:0; font-size:11px; line-height:16px; font-style:italic; }

div#content_pri fieldset#contact_action { background:url({site_url}themes/site_themes/agile_records/images/orange_20.png); padding:10px; font-size:11px; position:relative; }
div#content_pri fieldset#contact_action label { display:block; padding:5px 0; }
div#content_pri fieldset#contact_action label input { position:relative; left:5px; }
div#content_pri fieldset#contact_action input#contactSubmit { position:absolute; bottom:10px; right:10px; font-size:12px; }




/*  Member Templates */
/* 22.MEMBERS
---------------------------------------------------------------------- */

/* CONTROL PANEL */
div#navigation_sec.member_cp { width:270px; padding:10px; float:left; background:url({site_url}themes/site_themes/agile_records/images/green_40.png); margin:35px 30px 30px 10px; font-size:11px; line-height:16px; }
/*div#content_pri.member_cp  { width:610px; margin:0 0 0 10px; }*/

div#page_header.member_cp  a.viewprofile { display:block; width:182px; height:22px; background:url({site_url}themes/site_themes/agile_records/images/member_viewprofile.jpg) no-repeat left top; text-indent:-9999em; position:absolute; right:10px; top:25px; }
.member_cp div#page_header a.viewprofile:hover,
.member_cp div#page_header a.viewprofile:focus { background-position:left bottom; }

div#navigation_sec.member_cp h4 { color:#fff; border-bottom:1px solid #b1b1a9; font-size:12px; padding-bottom:5px; position:relative; }
div#navigation_sec.member_cp h4 a.expand { position:absolute; right:0; top:0; display:block; height:14px; width:14px; background:url({site_url}themes/site_themes/agile_records/images/controlpanel_expand.jpg) no-repeat bottom left; text-indent:-9999em; }
div#navigation_sec.member_cp h4 a.expand.open { background:url({site_url}themes/site_themes/agile_records/images/controlpanel_expand.jpg) no-repeat top left; }
div#navigation_sec.member_cp a:link,
div#navigation_sec.member_cp a:visited { color:#ddd; }
div#navigation_sec.member_cp a:hover,
div#navigation_sec.member_cp a:focus { color:#fff; }

div#content_pri table { width:610px; background:none;}
div#content_pri table th { background:none; }
div#content_pri table tr { background:url({site_url}themes/site_themes/agile_records/images/white_60.png); }
div#content_pri table tr.alt { background:url({site_url}themes/site_themes/agile_records/images/white_40.png); }

/* PROFILE */
div#content_pri.member_profile, div#content_pri.member_cp  { width:450px; float:left; margin:0 30px 30px 10px; }
div#content_sec.member_profile, div#content_sec.member_cp  { width:450px; float:left; margin:0 0 30px 0; }

h3.statistics {height:11px; font-family:\'Cooper Black\',miso,\'Georgia\',serif; color:#f47424; font-size:18px; }
h3.personalinfo {height:11px; color:#47472C; font-family:\'Cooper Black\',miso,\'Georgia\',serif; font-size:18px;}
h3.biography {height:11px; color:#47472C; font-family:\'Cooper Black\',miso,\'Georgia\',serif; font-size:18px; margin-top:20px;}

div#memberprofile_main { background:url({site_url}themes/site_themes/agile_records/images/green_20.png); width:300px; padding:10px; margin:40px 0 0 10px; float:left; }
div#memberprofile_main img { float:left; margin:0 10px 10px 0; }
div#memberprofile_main h3 { margin:5px 0 10px 0; }
div#memberprofile_main ul { clear:both; margin:0; padding:10px 0; font-size:12px; }
div#memberprofile_main ul a { color:#666; }

div#memberprofile_photo { float:left; width:250px; height:220px; background:url({site_url}themes/site_themes/agile_records/images/memberprofile_photo_bg.png) no-repeat center center; position:relative; left:-20px; }
div#memberprofile_photo img { width:206px; height:176px; border:3px solid #6b5f57; position:absolute; top:20px; left:20px; }

div#memberprofile_communicate { width:270px; padding:10px; margin:20px 10px 0 0; float:right; background:url({site_url}themes/site_themes/agile_records/images/green_40.png); }
div#memberprofile_communicate h3.communicate { width:83px; height:12px; font-family:"Helvetica Neue",Helvetica,Arial,sans-serif; color:#EBEBEB; text-transform: uppercase; margin-bottom:10px; }
div#memberprofile_communicate table { width:270px; font-size:10px; background:none; }
div#memberprofile_communicate table tr { background:url({site_url}themes/site_themes/agile_records/images/white_40.png); }
div#memberprofile_communicate table tr.alt { background:url({site_url}themes/site_themes/agile_records/images/white_20.png); }
div#memberprofile_communicate table th { font-weight:normal; font-size:10px; background:none; padding:4px; }
div#feature div#memberprofile_communicate table td { padding:4px; color:#444;}

div#content_pri.member_cp table,
div#content_sec.member_cp table { width:100%; background:none; margin-bottom:30px; }
div#content_pri.member_cp table th,
div#content_sec.member_cp table th { background:none; }
div#content_pri.member_cp table tr,
div#content_sec.member_cp table tr { background:url({site_url}themes/site_themes/agile_records/images/white_60.png); }
div#content_pri.member_cp table tr.alt,
div#content_sec.member_cp table tr.alt  { background:url({site_url}themes/site_themes/agile_records/images/white_40.png); }

/* Private Messages: Move and Copy pop-up menu control */
#movemenu {position: absolute !important; top: 410px !important; left: 390px !important; border: 0 !important;}
#copymenu {position: absolute !important; top: 410px !important; left: 332px !important; border: 0 !important;}

/* Search Results */
.pagination ul { overflow: auto; }
.pagination li { float: left; list-style: none; background: transparent url(http://expressionengine2/themes/site_themes/agile_records/images/green_40.png) repeat scroll 0 0; padding: 1px 7px; margin: 0 3px; }
.pagination li.active { background: none; }
.pagination li.active a { text-decoration: none; }', NULL, 1394136209, 1, 'n', 0, '', 'n', 'n', 'o', 0), (10, 1, 3, 'index', 'n', 'webpage', '{if segment_2 != \'\'}
  {redirect="404"}
{/if}
{html_head}
	<title>{site_name}</title>
{global_stylesheets}
{rss}
{favicon}
{html_head_end}
	<body>
	{nav_access}
	{branding_begin}
			{embed="global_embeds/.top_nav" loc="home"}
			{global_top_search}
			{global_top_member}
	{branding_end}
	{wrapper_begin}
{embed="global_embeds/.page_header" header="News"}
	<div id="feature" class="news">
			{global_featured_welcome}
			{global_featured_band}
	    </div> <!-- ending #feature -->

        	<div class="feature_end"></div>

	<div id="content_pri"> <!-- This is where all primary content, left column gets entered -->
		<!--  This is the channel entries tag.  Documentation for this parameter can be found at http://ellislab.com/expressionengine/user-guide/modules/channel/channel_entries.html
				 Parameter Explanation:
		channel= which channel to output, multiple channels may be piped in (channel_1|channel_2)
		limit= limits the number of entries output in this instance of the tag
		disable= turns off parsing of un-needed data -->

		{exp:channel:entries channel="news" limit="3" disable="categories|member_data|category_fields|pagination"}

		<!-- if no_results is a conditional variable, it can not be combined with advanced conditionals.  http://ellislab.com/expressionengine/user-guide/modules/channel/conditional_variables.html#cond_if_no_results -->

		{if no_results}<p>Sample No Results Information</p>{/if}
		{if count == "1"}
		<h3 class="recentnews">Recent News</h3>
		<ul id="news_listing">
		{/if}
			<li>
				<h4><a href="{comment_url_title_auto_path}">{title}</a>  //  <!-- entry_date is a variable, and date formatting variables can be found at http://ellislab.com/expressionengine/user-guide/templates/date_variable_formatting.html -->{entry_date format="%F %d %Y"}</h4>

				<!-- the following two lines are custom channel fields. http://ellislab.com/expressionengine/user-guide/cp/admin/channel_administration/custom_channel_fields.html -->

				{if news_image}
					<img src="{news_image}" alt="{title}" />
				{/if}
				{news_body}
				<p><a href="{comment_url_title_auto_path}#news_comments">{comment_total} comments</a> {global_edit_this}
								{if news_extended != ""}  |  <a href="{comment_url_title_auto_path}">Read more</a>{/if}</p>

			</li>
		{if count == total_results}</ul>{/if}
		{/exp:channel:entries}




	</div>

	<div id="content_sec" class="right green40">
		<h3 class="oldernews">Browse Older News</h3>
		<div id="news_archives">
			<div id="categories_box">
			{news_categories}
			</div>
			<div id="month_box">
			{news_month_archives}
			</div>
		</div> <!-- ending #news_archives -->

		{news_calendar}

		{news_popular}

	{rss_links}

	</div>	<!-- ending #content_sec -->

{global_footer}
{wrapper_close}
{js}
{html_close}', NULL, 1394136209, 1, 'n', 0, '', 'n', 'n', 'o', 0), (11, 1, 3, 'archives', 'n', 'webpage', '{html_head}
	<title>{site_name}: News Archives</title>
{global_stylesheets}
{rss}
{favicon}
{html_head_end}
	<body>
	{nav_access}
	{branding_begin}
			{embed="global_embeds/.top_nav" loc="home"}
			{global_top_search}
			{global_top_member}
	{branding_end}
	{wrapper_begin}
{embed="global_embeds/.page_header" header="News"}
	<div id="feature">
			{global_featured_welcome}
			{global_featured_band}
	    </div> <!-- ending #feature -->

        	<div class="feature_end"></div>

	<div id="content_pri" class="archive"> <!-- This is where all primary content, left column gets entered -->

			<!--  This is the channel entries tag.  Documentation for this tag can be found at http://ellislab.com/expressionengine/user-guide/modules/weblog/parameters.html

			channel= which channel to output, multiple channels may be piped in (channel_1|channel_2)
			limit= limits the number of entries output in this instance of the tag
			disable= turns off parsing of un-needed data
			relaxed_categories= allows you use the category indicator in your URLs with an entries tag specifying multiple weblogs that do not share category groups.

			-->

		{exp:channel:entries channel="news" limit="3" disable="member_data|category_fields|pagination" status="open|featured" relaxed_categories="yes"}

		<!-- if no_results is a conditional variable, it can not be combined with advanced conditionals.  http://ellislab.com/expressionengine/user-guide/modules/channel/conditional_variables.html#cond_if_no_results -->

		{if no_results}<p>No Results</p>{/if}
		{if count == "1"}
		<h3 class="recentnews">Recent News</h3>
		<ul id="news_listing">
		{/if}
			<li>
				<h4><a href="{comment_url_title_auto_path}">{title}</a>  //  {!-- entry_date is a variable, and date formatting variables can be found at http://ellislab.com/expressionengine/user-guide/templates/date_variable_formatting.html --}{entry_date format="%F %d %Y"}</h4>

				<!-- the following two lines are custom channel fields. http://ellislab.com/expressionengine/user-guide/cp/admin/channel_administration/custom_channel_fields.html -->

				{if news_image}
					<img src="{news_image}" alt="{title}" />
				{/if}
				{news_body}
				<p><a href="{comment_url_title_auto_path}#news_comments">{comment_total} comments</a> {global_edit_this}
								{if news_extended != ""}  |  <a href="{comment_url_title_auto_path}">Read more</a>{/if}</p>

			</li>
		{if count == total_results}</ul>{/if}
		{/exp:channel:entries}




	</div>

	<div id="content_sec" class="right green40">
		<h3 class="oldernews">Browse Older News</h3>
		<div id="news_archives">
			<div id="categories_box">
			{news_categories}
			</div>
			<div id="month_box">
			{news_month_archives}
			</div>
		</div> <!-- ending #news_archives -->

		{news_calendar}

		{news_popular}

	{rss_links}

	</div>	<!-- ending #content_sec -->

{global_footer}
{wrapper_close}
{js}
{html_close}', NULL, 1394136209, 1, 'n', 0, '', 'n', 'n', 'o', 0), (12, 1, 3, 'atom', 'n', 'feed', '{exp:rss:feed channel="news"}

<?xml version="1.0" encoding="{encoding}"?>
<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="{channel_language}">

	<title type="text">{exp:xml_encode}{channel_name}{/exp:xml_encode}</title>
	<subtitle type="text">{exp:xml_encode}{channel_name}:{channel_description}{/exp:xml_encode}</subtitle>
	<link rel="alternate" type="text/html" href="{channel_url}" />
	<link rel="self" type="application/atom+xml" href="{path={atom_feed_location}}" />
	<updated>{gmt_edit_date format=\'%Y-%m-%dT%H:%i:%sZ\'}</updated>
	<rights>Copyright (c) {gmt_date format="%Y"}, {author}</rights>
	<generator uri="http://ellislab.com/" version="{version}">ExpressionEngine</generator>
	<id>tag:{trimmed_url},{gmt_date format="%Y:%m:%d"}</id>

{exp:channel:entries channel="news" limit="15" dynamic_start="on" disable="member_data"}
	<entry>
	  <title>{exp:xml_encode}{title}{/exp:xml_encode}</title>
	  <link rel="alternate" type="text/html" href="{comment_url_title_auto_path}" />
	  <id>tag:{trimmed_url},{gmt_entry_date format="%Y"}:{relative_url}/{channel_id}.{entry_id}</id>
	  <published>{gmt_entry_date format="%Y-%m-%dT%H:%i:%sZ"}</published>
	  <updated>{gmt_edit_date format=\'%Y-%m-%dT%H:%i:%sZ\'}</updated>
	  <author>
			<name>{author}</name>
			<email>{email}</email>
			{if url}<uri>{url}</uri>{/if}
	  </author>
{categories}
	  <category term="{exp:xml_encode}{category_name}{/exp:xml_encode}"
		scheme="{path=news/index}"
		label="{exp:xml_encode}{category_name}{/exp:xml_encode}" />{/categories}
	  <content type="html"><![CDATA[
		{news_body} {news_extended}
	  ]]></content>
	</entry>
{/exp:channel:entries}

</feed>

{/exp:rss:feed}						', NULL, 1394136209, 1, 'n', 0, '', 'n', 'n', 'o', 0), (13, 1, 3, 'comment_preview', 'n', 'webpage', '{!-- Explanations and learning materials can be found in news/index and the other news template groups.  In-line comments here are only for features not introduced in news/index.
	 NOTE:  This is an ExpressionEngine Comment and it will not appear in the rendered source.
			http://ellislab.com/expressionengine/user-guide/templates/commenting.html
--}
{html_head}
<!-- Below we use a channel entries tag to deliver a dynamic title element. -->
	<title>{site_name}: Comment Preview for
		{exp:channel:entries channel="news|about" limit="1" disable="categories|member_data|category_fields|pagination"}{title}{/exp:channel:entries}</title>
{global_stylesheets}
{rss}
{favicon}
{html_head_end}
	<body>
{nav_access}
	{branding_begin}
			{embed="global_embeds/.top_nav" loc="home"}
			{global_top_search}
			{global_top_member}
	{branding_end}
	{wrapper_begin}
{embed="global_embeds/.page_header" header="News"}
	<div id="feature">
		{global_featured_welcome}
		{global_featured_band}
	    </div> <!-- ending #feature -->

        	<div class="feature_end"></div>

	<div id="content_pri"> <!-- This is where all primary content, left column gets entered -->
		<!--  This is the channel entries tag.  Documentation for this parameter can be found at http://ellislab.com/expressionengine/user-guide/modules/channel/channel_entries.html
				 Parameters are the items inside the opening exp:channel:entries tag that allow limiting, filtering, and sorting. They go in the format item="limiter".  ie: channel="news". Below are links to the parameters used in this particular instance of the channel entries tag.  These are documented here:

				http://ellislab.com/expressionengine/user-guide/channels/weblog/parameters.html

		channel= which channel to output, multiple channels may be piped in (channel_1|channel_2)
		limit= limits the number of entries output in this instance of the tag
		disable= turns off parsing of un-needed data
		require_entry= forces ExpressionEngine to compare Segment 3 to existing URL titles.  If there is no match, then nothing is output.  Use this in combination with if no_results to force a redirect to 404. -->

		{exp:channel:entries channel="news|about" disable="categories|member_data|category_fields|pagination" status="open|featured"}
		<!-- count is a single variable: http://ellislab.com/expressionengine/user-guide/modules/weblog/variables.html#var_count

		In this case we\'ve combined the count single variable with a Conditional Global Variable:

		http://ellislab.com/expressionengine/user-guide/templates/globals/conditionals.html

		to create code that shows up only once, at the top of the list of outputted channel entries and only if there is 1 or more entries -->

		{if count == "1"}
		<h3 class="recentnews">Recent News</h3>
		<ul id="news_listing">

			<!-- Here we close the conditional after all of the conditional data is processed. -->

		{/if}
			<li>
					<!-- comment_url_title_auto_path is a channel entries variable:

					http://ellislab.com/expressionengine/user-guide/modules/channel/variables.html#var_comment_url_title_auto_path

					This allows you to outpt a per-channel link to a single-entry page.  This can be used even if you are not using comments as a way to get a per-channel "permalink" page without writing your own conditional. -->

				<h4><a href="{comment_url_title_auto_path}">{title}</a>  //  <!-- entry_date is a variable, and date formatting variables can be found at http://ellislab.com/expressionengine/user-guide/templates/date_variable_formatting.html -->{entry_date format="%F %d %Y"}</h4>

				<!-- the following two lines are custom channel fields. http://ellislab.com/expressionengine/user-guide/cp/admin/channel_administration/custom_channel_fields.html -->

				{if news_image}
					<img src="{news_image}" alt="{title}" />
				{/if}

				<!-- Here we come a custom field variable with a global conditional to output the HTML only if he custom field is _not_ blank -->

				{if about_image != ""}<img src="{about_image}" alt="{title}"  />{/if}
				{news_body}
				{about_body}
				{news_extended}

				<!-- Here we compare the channel short-name to a predefined word to output some information only if the entry occurs in a particular channel -->
				{if channel_short_name == "news"}<p><a href="{comment_url_title_auto_path}#news_comments">{comment_total} comments</a> <!-- edit_this is a Snippet: http://ellislab.com/expressionengine/user-guide/templates/globals/snippets.html --> {global_edit_this} </p> {/if}
			</li>
		<!-- Comparing two channel entries variables to output data only at the end of the list of outputted channel entries -->
		{if count == total_results}</ul>{/if}
		<!-- Closing the Channel Entries tag -->
		{/exp:channel:entries}

			<div id="news_comments">
			<!-- Comment Entries Tag outputs comments: http://ellislab.com/expressionengine/user-guide/ http://ellislab.com/expressionengine/user-guide/
			Parameters found here: http://ellislab.com/expressionengine/user-guide/modules/comment/entries.html#parameters
			sort= defines in what order to sort the comments
			limit= how many comments to output
			channel= what channels to show comments from
			-->
			{exp:comment:preview channel="news|about"}
			<h3>Comments</h3>
			<ol>
				<li>
					<h5 class="commentdata">
						<!-- Comment Entries variable: http://ellislab.com/expressionengine/user-guide/modules/comment/entries.html#url_as_author
						url_as_author outputs the URL if entered/in the member profile (if registered) or just the name if no URL-->
						{url_as_author}
						<!-- Comment date:
						 http://ellislab.com/expressionengine/user-guide/modules/comment/entries.html#var_comment_date

						Formatted with Date Variable Formatting:

	http://ellislab.com/expressionengine/user-guide//templates/date_variable_formatting.html -->

						<span>{comment_date format="%h:%i%a"}, {comment_date format=" %m/%d/%Y"}</span>
						<!-- Checks if the member has chosen an avatar and displays it if so

	http://ellislab.com/expressionengine/user-guide/modules/comment/entries.html#conditionals
						-->
						{if avatar}
							<img src="{avatar_url}" width="{avatar_image_width}" height="{avatar_image_height}" alt="{author}\'s avatar" />
						{/if}
					</h5>
					{comment}

                    <div style="clear: both;"></div>
				</li>
			</ol>
			{/exp:comment:preview}

			<!-- Comment Submission Form:

			http://ellislab.com/expressionengine/user-guide/ modules/comment/entries.html#submission_form

			channel= parameter says which channel to submit this comment too.  This is very important to include if you use multiple channels that may have the same URL title.  It will stop the comment from being attached to the wrong entry.  channel= should always be included.
			-->


			{exp:comment:form channel="news"}
			<h3 class="leavecomment">Leave a comment</h3>
			<fieldset id="comment_fields">
			<!-- Show inputs only if the member is logged out.  If logged in, this information is pulled from the member\'s account details -->
			{if logged_out}
				<label for="name">
					<span>Name:</span>
					<input type="text" id="name" name="name" value="{name}" size="50" />
				</label>
				<label for="email">
					<span>Email:</span>
					<input type="text" id="email" name="email" value="{email}" size="50" />
				</label>
				<label for="location">
					<span>Location:</span>
					 <input type="text" id="location" name="location" value="{location}" size="50" />
				</label>
				<label for="url">
					<span>URL:</span>
					<input type="text" id="url" name="url" value="{url}" size="50" />
				</label>
			{/if}
				<!-- comment_guidelines is a User Defined Global Variable: http://ellislab.com/expressionengine/user-guide/templates/globals/user_defined.html -->
				{comment_guidelines}
				<label for="comment" class="comment">
					<span>Comment:</span>
					<textarea id="comment" name="comment" rows="10" cols="70">{comment}</textarea>
				</label>
			</fieldset>

				<fieldset id="comment_action">
				{if logged_out}
				<label for="save_info">Remember my personal info? <input type="checkbox" name="save_info" value="yes" {save_info} /> </label>
				{/if}
				<label for="notify_me">Notify me of follow-up comments? <input type="checkbox" id="notify_me" name="notify_me" value="yes" {notify_me} /></label>

				<!-- Insert CAPTCHA.  Will show for those that are not exempt from needing the CAPTCHA as set in the member group preferences

				-->
				{if captcha}
				<div id="captcha_box">
					<span>{captcha}</span>
				</div>
					<label for="captcha">Please enter the word you see in the image above:
<input type="text" id="captcha" name="captcha" value="{captcha_word}" maxlength="20" />
					</label>
				{/if}
				<input type="submit" name="preview" value="Preview Comment" />
				<input type="submit" name="submit" value="Submit" id="submit_comment" />
			</fieldset>
			{/exp:comment:form}

	</div> <!-- ending #news_comments -->
	</div> <!-- ending #content_pri -->

	<div id="content_sec" class="right green40">
		<h3 class="oldernews">Browse Older News</h3>
		<div id="news_archives">
			<!-- The period before the template in this embed indicates a "hidden template".  Hidden templates can not be viewed directly but can only be viewed when embedded in another template: http://ellislab.com/expressionengine/user-guide/templates/hidden_templates.html -->
			<div id="categories_box">
			{news_categories}
			</div>
			<div id="month_box">
			{news_month_archives}
			</div>
		</div> <!-- ending #news_archives -->

		{news_calendar}

		{news_popular}

	{rss_links}

	</div>	<!-- ending #content_sec -->

{global_footer}
{wrapper_close}
{js}
{html_close}', NULL, 1394136209, 1, 'n', 0, '', 'n', 'n', 'o', 0), (14, 1, 3, 'comments', 'n', 'webpage', '{!-- Explanations and learning materials can be found in news/index and the other news template groups.  In-line comments here are only for features not introduced in news/index.
	 NOTE:  This is an ExpressionEngine Comment and it will not appear in the rendered source.
			http://ellislab.com/expressionengine/user-guide/templates/commenting.html
--}
{html_head}
<!-- Below we use a channel entries tag to deliver a dynamic title element. -->
	<title>{site_name}: Comments  on
		{exp:channel:entries channel="news|about" limit="1" disable="categories|member_data|category_fields|pagination"}{title}{/exp:channel:entries}</title>
{global_stylesheets}
{rss}
{favicon}
{html_head_end}
	<body>
{nav_access}
	{branding_begin}
			{embed="global_embeds/.top_nav" loc="home"}
			{global_top_search}
			{global_top_member}
	{branding_end}
	{wrapper_begin}
{embed="global_embeds/.page_header" header="News"}
	<div id="feature">
			{global_featured_welcome}
			{global_featured_band}
	    </div> <!-- ending #feature -->

        	<div class="feature_end"></div>

	<div id="content_pri"> <!-- This is where all primary content, left column gets entered -->
		<!--  This is the channel entries tag.  Documentation for this parameter can be found at http://ellislab.com/expressionengine/user-guide/modules/channel/channel_entries.html
				 Parameters are the items inside the opening exp:channel:entries tag that allow limiting, filtering, and sorting. They go in the format item="limiter".  ie: channel="news". Below are links to the parameters used in this particular instance of the channel entries tag.  These are documented here:

				http://ellislab.com/expressionengine/user-guide/channels/weblog/parameters.html

		channel= which channel to output, multiple channels may be piped in (channel_1|channel_2)
		limit= limits the number of entries output in this instance of the tag
		disable= turns off parsing of un-needed data
		require_entry= forces ExpressionEngine to compare Segment 3 to existing URL titles.  If there is no match, then nothing is output.  Use this in combination with if no_results to force a redirect to 404. -->

		{exp:channel:entries channel="news|about" limit="3" disable="categories|member_data|category_fields|pagination" require_entry="yes" status="open|featured"}

		<!-- if no_results is a conditional variable, it can not be combined with advanced conditionals.  http://ellislab.com/expressionengine/user-guide/modules/channel/conditional_variables.html#cond_if_no_results

		This is used here in combination with the require_entry parameter to ensure correct delivery of information or redirect to a 404 -->

		{if no_results}{redirect="404"}{/if}
		<!-- count is a single variable: http://ellislab.com/expressionengine/user-guide/modules/weblog/variables.html#var_count

		In this case we\'ve combined the count single variable with a Conditional Global Variable:

		http://ellislab.com/expressionengine/user-guide/templates/globals/conditionals.html

		to create code that shows up only once, at the top of the list of outputted channel entries and only if there is 1 or more entries -->

		{if count == "1"}
		<h3 class="recentnews">Recent News</h3>
		<ul id="news_listing">

			<!-- Here we close the conditional after all of the conditional data is processed. -->

		{/if}
			<li>
					<!-- comment_url_title_auto_path is a channel entries variable:

					http://ellislab.com/expressionengine/user-guide/modules/channel/variables.html#var_comment_url_title_auto_path

					This allows you to outpt a per-channel link to a single-entry page.  This can be used even if you are not using comments as a way to get a per-channel "permalink" page without writing your own conditional. -->

				<h4><a href="{comment_url_title_auto_path}">{title}</a>  //  <!-- entry_date is a variable, and date formatting variables can be found at http://ellislab.com/expressionengine/user-guide/templates/date_variable_formatting.html -->{entry_date format="%F %d %Y"}</h4>

				<!-- the following two lines are custom channel fields. http://ellislab.com/expressionengine/user-guide/cp/admin/channel_administration/custom_channel_fields.html -->

				{if news_image}
					<img src="{news_image}" alt="{title}" />
				{/if}

				<!-- Here we come a custom field variable with a global conditional to output the HTML only if he custom field is _not_ blank -->

				{if about_image != ""}<img src="{about_image}" alt="{title}"  />{/if}
				{news_body}
				{about_body}
				{news_extended}

				<!-- Here we compare the channel short-name to a predefined word to output some information only if the entry occurs in a particular channel -->
				{if channel_short_name == "news"}<p><a href="{comment_url_title_auto_path}#news_comments">{comment_total} comments</a> <!-- edit_this is a Snippet: http://ellislab.com/expressionengine/user-guide/templates/globals/snippets.html --> {global_edit_this} </p> {/if}
			</li>
		<!-- Comparing two channel entries variables to output data only at the end of the list of outputted channel entries -->
		{if count == total_results}</ul>{/if}
		<!-- Closing the Channel Entries tag -->
		{/exp:channel:entries}

			<div id="news_comments">
			<!-- Comment Entries Tag outputs comments: http://ellislab.com/expressionengine/user-guide/ http://ellislab.com/expressionengine/user-guide/
			Parameters found here: http://ellislab.com/expressionengine/user-guide/modules/comment/entries.html#parameters
			sort= defines in what order to sort the comments
			limit= how many comments to output
			channel= what channels to show comments from
			-->
			{exp:comment:entries sort="asc" limit="20" channel="news"}
			{if count == "1"}
			<h3>Comments</h3>
			<ol>{/if}
				<li>
					<h5 class="commentdata">
						<!-- Comment Entries variable: http://ellislab.com/expressionengine/user-guide/modules/comment/entries.html#url_as_author
						url_as_author outputs the URL if entered/in the member profile (if registered) or just the name if no URL-->
						{url_as_author}
						<!-- Comment date:
						 http://ellislab.com/expressionengine/user-guide/modules/comment/entries.html#var_comment_date

						Formatted with Date Variable Formatting:

	http://ellislab.com/expressionengine/user-guide//templates/date_variable_formatting.html -->

						<span>{comment_date format="%h:%i%a"}, {comment_date format=" %m/%d/%Y"}</span>
						<!-- Checks if the member has chosen an avatar and displays it if so

	http://ellislab.com/expressionengine/user-guide/modules/comment/entries.html#conditionals
						-->
						{if avatar}
							<img src="{avatar_url}" width="{avatar_image_width}" height="{avatar_image_height}" alt="{author}\'s avatar" />
						{/if}
					</h5>
					{comment}

                    <div style="clear: both;"></div>
				</li>
			{if count == total_results}</ol>{/if}
			{/exp:comment:entries}

			<!-- Comment Submission Form:

			http://ellislab.com/expressionengine/user-guide/ modules/comment/entries.html#submission_form

			channel= parameter says which channel to submit this comment too.  This is very important to include if you use multiple channels that may have the same URL title.  It will stop the comment from being attached to the wrong entry.  channel= should always be included.

			-->

			{exp:comment:form channel="news" preview="news/comment_preview"}
			<h3 class="leavecomment">Leave a comment</h3>
			<fieldset id="comment_fields">
			<!-- Show inputs only if the member is logged out.  If logged in, this information is pulled from the member\'s account details -->
			{if logged_out}
				<label for="name">
					<span>Name:</span>
					<input type="text" id="name" name="name" value="{name}" size="50" />
				</label>
				<label for="email">
					<span>Email:</span>
					<input type="text" id="email" name="email" value="{email}" size="50" />
				</label>
				<label for="location">
					<span>Location:</span>
					 <input type="text" id="location" name="location" value="{location}" size="50" />
				</label>
				<label for="url">
					<span>URL:</span>
					<input type="text" id="url" name="url" value="{url}" size="50" />
				</label>
			{/if}
				<!-- comment_guidelines is a User Defined Global Variable: http://ellislab.com/expressionengine/user-guide/templates/globals/user_defined.html -->
				{comment_guidelines}
				<label for="comment" class="comment">
					<span>Comment:</span>
					<textarea id="comment" name="comment" rows="10" cols="70">{comment}</textarea>
				</label>
			</fieldset>

				<fieldset id="comment_action">
				{if logged_out}
				<label for="save_info">Remember my personal info? <input type="checkbox" name="save_info" value="yes" {save_info} /> </label>
				{/if}
				<label for="notify_me">Notify me of follow-up comments? <input type="checkbox" id="notify_me" name="notify_me" value="yes" {notify_me} /></label>

				<!-- Insert CAPTCHA.  Will show for those that are not exempt from needing the CAPTCHA as set in the member group preferences

				-->
				{if captcha}
				<div id="captcha_box">
					<span>{captcha}</span>
				</div>
					<label for="captcha">Please enter the word you see in the image above:
<input type="text" id="captcha" name="captcha" value="{captcha_word}" maxlength="20" />
					</label>
				{/if}
				<input type="submit" name="preview" value="Preview Comment" />
				<input type="submit" name="submit" value="Submit" id="submit_comment" />
			</fieldset>
			{/exp:comment:form}

	</div> <!-- ending #news_comments -->
	</div> <!-- ending #content_pri -->

	<div id="content_sec" class="right green40">
		<h3 class="oldernews">Browse Older News</h3>
		<div id="news_archives">
			<!-- The period before the template in this embed indicates a "hidden template".  Hidden templates can not be viewed directly but can only be viewed when embedded in another template: http://ellislab.com/expressionengine/user-guide/templates/hidden_templates.html -->
			<div id="categories_box">
			{news_categories}
			</div>
			<div id="month_box">
			{news_month_archives}
			</div>
		</div> <!-- ending #news_archives -->

		{news_calendar}

		{news_popular}

	{rss_links}

	</div>	<!-- ending #content_sec -->

{global_footer}
{wrapper_close}
{js}
{html_close}', NULL, 1394136209, 1, 'n', 0, '', 'n', 'n', 'o', 0), (15, 1, 3, 'rss', 'n', 'feed', '{exp:rss:feed channel="news"}

<?xml version="1.0" encoding="{encoding}"?>
<rss version="2.0"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:admin="http://webns.net/mvcb/"
	xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
	xmlns:content="http://purl.org/rss/1.0/modules/content/">

	<channel>

	<title>{exp:xml_encode}{channel_name}{/exp:xml_encode}</title>
	<link>{channel_url}</link>
	<description>{channel_description}</description>
	<dc:language>{channel_language}</dc:language>
	<dc:creator>{email}</dc:creator>
	<dc:rights>Copyright {gmt_date format="%Y"}</dc:rights>
	<dc:date>{gmt_date format="%Y-%m-%dT%H:%i:%s%Q"}</dc:date>
	<admin:generatorAgent rdf:resource="http://ellislab.com/" />

{exp:channel:entries channel="news" limit="10" dynamic_start="on" disable="member_data"}
	<item>
	  <title>{exp:xml_encode}{title}{/exp:xml_encode}</title>
	  <link>{comment_url_title_auto_path}</link>
	  <guid>{comment_url_title_auto_path}#When:{gmt_entry_date format="%H:%i:%sZ"}</guid>
	  <description><![CDATA[{news_body}]]></description>
	  <dc:subject>{exp:xml_encode}{categories backspace="1"}{category_name}, {/categories}{/exp:xml_encode}</dc:subject>
	  <dc:date>{gmt_entry_date format="%Y-%m-%dT%H:%i:%s%Q"}</dc:date>
	</item>
{/exp:channel:entries}

	</channel>
</rss>

{/exp:rss:feed}						', NULL, 1394136209, 1, 'n', 0, '', 'n', 'n', 'o', 0), (16, 1, 4, 'index', 'n', 'webpage', '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{lang}" lang="{lang}">

<head>
<title>{site_name}{lang:search}</title>

<meta http-equiv="content-type" content="text/html; charset={charset}" />

<link rel=\'stylesheet\' type=\'text/css\' media=\'all\' href=\'{stylesheet=search/search_css}\' />
<style type=\'text/css\' media=\'screen\'>@import "{stylesheet=search/search_css}";</style>

</head>
<body>

<div id=\'pageheader\'>
<div class="heading">{lang:search_engine}</div>
</div>

<div id="content">

<div class=\'breadcrumb\'>
<span class="defaultBold">&nbsp; <a href="{homepage}">{site_name}</a>&nbsp;&#8250;&nbsp;&nbsp;{lang:search}</span>
</div>

<div class=\'outerBorder\'>
<div class=\'tablePad\'>

{exp:search:advanced_form result_page="search/results" cat_style="nested"}

<table cellpadding=\'4\' cellspacing=\'6\' border=\'0\' width=\'100%\'>
<tr>
<td width="50%">

<fieldset class="fieldset">
<legend>{lang:search_by_keyword}</legend>

<input type="text" class="input" maxlength="100" size="40" name="keywords" style="width:100%;" />

<div class="default">
<select name="search_in">
<option value="titles" selected="selected">{lang:search_in_titles}</option>
<option value="entries">{lang:search_in_entries}</option>
<option value="everywhere" >{lang:search_everywhere}</option>
</select>

</div>

<div class="default">
<select name="where">
<option value="exact" selected="selected">{lang:exact_phrase_match}</option>
<option value="any">{lang:search_any_words}</option>
<option value="all" >{lang:search_all_words}</option>
<option value="word" >{lang:search_exact_word}</option>
</select>
</div>

</fieldset>

<div class="default"><br /></div>

<table cellpadding=\'0\' cellspacing=\'0\' border=\'0\'>
<tr>
<td valign="top">

<div class="defaultBold">{lang:channels}</div>

<select id="channel_id" name=\'channel_id[]\' class=\'multiselect\' size=\'12\' multiple=\'multiple\' onchange=\'changemenu(this.selectedIndex);\'>
{channel_names}
</select>

</td>
<td valign="top" width="16">&nbsp;</td>
<td valign="top">

<div class="defaultBold">{lang:categories}</div>

<select name=\'cat_id[]\' size=\'12\'  class=\'multiselect\' multiple=\'multiple\'>
<option value=\'all\' selected="selected">{lang:any_category}</option>
</select>

</td>
</tr>
</table>



</td><td width="50%" valign="top">


<fieldset class="fieldset">
<legend>{lang:search_by_member_name}</legend>

<input type="text" class="input" maxlength="100" size="40" name="member_name" style="width:100%;" />
<div class="default"><input type="checkbox" class="checkbox" name="exact_match" value="y"  /> {lang:exact_name_match}</div>

</fieldset>

<div class="default"><br /></div>


<fieldset class="fieldset">
<legend>{lang:search_entries_from}</legend>

<select name="date" style="width:150px">
<option value="0" selected="selected">{lang:any_date}</option>
<option value="1" >{lang:today_and}</option>
<option value="7" >{lang:this_week_and}</option>
<option value="30" >{lang:one_month_ago_and}</option>
<option value="90" >{lang:three_months_ago_and}</option>
<option value="180" >{lang:six_months_ago_and}</option>
<option value="365" >{lang:one_year_ago_and}</option>
</select>

<div class="default">
<input type=\'radio\' name=\'date_order\' value=\'newer\' class=\'radio\' checked="checked" />&nbsp;{lang:newer}
<input type=\'radio\' name=\'date_order\' value=\'older\' class=\'radio\' />&nbsp;{lang:older}
</div>

</fieldset>

<div class="default"><br /></div>

<fieldset class="fieldset">
<legend>{lang:sort_results_by}</legend>

<select name="orderby">
<option value="date" >{lang:date}</option>
<option value="title" >{lang:title}</option>
<option value="most_comments" >{lang:most_comments}</option>
<option value="recent_comment" >{lang:recent_comment}</option>
</select>

<div class="default">
<input type=\'radio\' name=\'sort_order\' class="radio" value=\'desc\' checked="checked" /> {lang:descending}
<input type=\'radio\' name=\'sort_order\' class="radio" value=\'asc\' /> {lang:ascending}
</div>
</fieldset>

</td>
</tr>
</table>


<div class=\'searchSubmit\'>

<input type=\'submit\' value=\'Search\' class=\'submit\' />

</div>

{/exp:search:advanced_form}

<div class=\'copyright\'><a href="http://ellislab.com/">Powered by ExpressionEngine</a></div>


</div>
</div>
</div>

</body>
</html>', NULL, 1394136209, 1, 'n', 0, '', 'n', 'n', 'o', 0), (17, 1, 4, 'no_results', 'n', 'webpage', '{!-- Explanations and learning materials can be found in news/index and the other news template groups.  In-line comments here are only for features not introduced in news/index. --}
{html_head}
	<title>{site_name}: No Search Results</title>
{global_stylesheets}
{rss}
{favicon}
{html_head_end}
	<body>
{nav_access}
	{branding_begin}
			{embed="global_embeds/.top_nav" loc="not_found"}
			{global_top_search}
			{global_top_member}
	{branding_end}
	{wrapper_begin}
{embed="global_embeds/.page_header" header="Search Results"}


	<div id="content_pri"> <!-- This is where all primary content, left column gets entered -->

		<!-- No search results: http://ellislab.com/expressionengine/user-guide/modules/search/simple.html#par_no_result_page -->
		<!-- This is delivered based on the no_result_page parameter of the search form  -->

				<h3>Search Results</h3>

				<!-- exp:search:keywords: http://ellislab.com/expressionengine/user-guide/modules/search/keywords.html -->
				<!-- exp:search:keywords lets you echo out what search term was used -->
					<p>Sorry, no results were found for "<strong>{exp:search:keywords}</strong>".  Please try again.</p>
	</div>

	<div id="content_sec" class="right green40">
		<h3 class="oldernews">Browse Older News</h3>
		<div id="news_archives">
			<div id="categories_box">
			{news_categories}
			</div>
			<div id="month_box">
			{news_month_archives}
			</div>
		</div> <!-- ending #news_archives -->

		{news_calendar}

		{news_popular}

	{rss_links}

	</div>	<!-- ending #content_sec -->

{global_footer}
{wrapper_close}
{js}
{html_close}', NULL, 1394136209, 1, 'n', 0, '', 'n', 'n', 'o', 0), (18, 1, 4, 'results', 'n', 'webpage', '{!-- Explanations and learning materials can be found in news/index and the other news template groups.  In-line comments here are only for features not introduced in news/index. --}
{html_head}
	<title>{site_name}: {exp:search:search_results}
		{if count == "1"}
			Search Results for "{exp:search:keywords}"
		{/if}
		{/exp:search:search_results}
	</title>
{global_stylesheets}
{rss}
{favicon}
{html_head_end}
	<body>
{nav_access}
	{branding_begin}
		{embed="global_embeds/.top_nav" loc="not_found"}
		{global_top_search}
		{global_top_member}
	{branding_end}
	{wrapper_begin}
	{embed="global_embeds/.page_header" header="Search Results"}

	<div id="content_pri"> <!-- This is where all primary content, left column gets entered -->

		<!-- Search Results tag: http://ellislab.com/expressionengine/user-guide/modules/search/index.html#results -->

		{exp:search:search_results}
			{if count == "1"}
				<!-- exp:search:keywords: http://ellislab.com/expressionengine/user-guide/modules/search/keywords.html -->
				<!-- exp:search:keywords lets you echo out what search term was used -->

				<h3>Search Results for "<strong>{exp:search:keywords}</strong>":</h3>
				<ul id="news_listing">
			{/if}

			<li>
				<h4>
					<a href="{comment_url_title_auto_path}">{title}</a>  //
					<!-- entry_date is a variable, and date formatting variables can be found at http://ellislab.com/expressionengine/user-guide/templates/date_variable_formatting.html -->
					{entry_date format="%F %d %Y"}
				</h4>

				<!-- news_body and news_image are  custom channel fields. http://ellislab.com/expressionengine/user-guide/cp/admin/channel_administration/custom_channel_fields.html -->
				{if news_image}
					<img src="{news_image}" alt="{title}" />
				{/if}
				{news_body}
			</li>
			{if count == total_results}</ul>{/if}

			{paginate}
				<div class="pagination">
					{pagination_links}
						<ul>
							{first_page}
								<li><a href="{pagination_url}" class="page-first">First Page</a></li>
							{/first_page}

							{previous_page}
								<li><a href="{pagination_url}" class="page-previous">Previous Page</a></li>
							{/previous_page}

							{page}
								<li><a href="{pagination_url}" class="page-{pagination_page_number} {if current_page}active{/if}">{pagination_page_number}</a></li>
							{/page}

							{next_page}
								<li><a href="{pagination_url}" class="page-next">Next Page</a></li>
							{/next_page}

							{last_page}
								<li><a href="{pagination_url}" class="page-last">Last Page</a></li>
							{/last_page}
						</ul>
					{/pagination_links}
				</div> <!-- ending .pagination -->
			{/paginate}
		{/exp:search:search_results}
	</div>

	<div id="content_sec" class="right green40">
		<h3 class="oldernews">Browse Older News</h3>
		<div id="news_archives">
			<div id="categories_box">
			{news_categories}
			</div>
			<div id="month_box">
			{news_month_archives}
			</div>
		</div> <!-- ending #news_archives -->

		{news_calendar}

		{news_popular}

	{rss_links}

	</div>	<!-- ending #content_sec -->

{global_footer}
{wrapper_close}
{js}
{html_close}', NULL, 1394136209, 1, 'n', 0, '', 'n', 'n', 'o', 0);






INSERT INTO `exp_upload_prefs` (`id`, `site_id`, `name`, `server_path`, `url`, `allowed_types`, `max_size`, `max_height`, `max_width`, `properties`, `pre_format`, `post_format`, `file_properties`, `file_pre_format`, `file_post_format`, `cat_group`, `batch_location`) VALUES (1, 1, 'Main Upload Directory', '/private/var/www/expressionengine-test/images/uploads/', 'http://ee2.test/images/uploads/', 'all', NULL, NULL, NULL, 'style="border: 0;" alt="image"', NULL, NULL, NULL, NULL, NULL, NULL, NULL), (2, 1, 'About', '/private/var/www/expressionengine-test/themes/site_themes/agile_records/images/uploads/', 'http://ee2.test/themes/site_themes/agile_records/images/uploads/', 'img', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);




SET FOREIGN_KEY_CHECKS = 1;


