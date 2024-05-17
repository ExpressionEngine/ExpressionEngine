-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.3.29-MariaDB-log - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             11.1.0.6116
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping data for table ee-test.exp_captcha: ~0 rows (approximately)
DELETE FROM `exp_captcha`;
/*!40000 ALTER TABLE `exp_captcha` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_captcha` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_categories: ~4 rows (approximately)
DELETE FROM `exp_categories`;
/*!40000 ALTER TABLE `exp_categories` DISABLE KEYS */;
INSERT INTO `exp_categories` (`cat_id`, `site_id`, `group_id`, `parent_id`, `cat_name`, `cat_url_title`, `cat_description`, `cat_image`, `cat_order`) VALUES
	(1, 1, 1, 0, 'News', 'news', '', '', 2),
	(2, 1, 1, 0, 'Bands', 'bands', '', '', 3),
	(3, 1, 2, 0, 'Staff Bios', 'staff_bios', '', '', 2),
	(4, 1, 2, 0, 'Site Info', 'site_info', '', '', 1);
/*!40000 ALTER TABLE `exp_categories` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_category_fields: ~0 rows (approximately)
DELETE FROM `exp_category_fields`;
/*!40000 ALTER TABLE `exp_category_fields` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_category_fields` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_category_field_data: ~4 rows (approximately)
DELETE FROM `exp_category_field_data`;
/*!40000 ALTER TABLE `exp_category_field_data` DISABLE KEYS */;
INSERT INTO `exp_category_field_data` (`cat_id`, `site_id`, `group_id`) VALUES
	(1, 1, 1),
	(2, 1, 1),
	(3, 1, 2),
	(4, 1, 2);
/*!40000 ALTER TABLE `exp_category_field_data` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_category_groups: ~2 rows (approximately)
DELETE FROM `exp_category_groups`;
/*!40000 ALTER TABLE `exp_category_groups` DISABLE KEYS */;
INSERT INTO `exp_category_groups` (`group_id`, `site_id`, `group_name`, `sort_order`, `exclude_group`, `field_html_formatting`, `can_edit_categories`, `can_delete_categories`) VALUES
	(1, 1, 'News Categories', 'v', 0, 'all', '', ''),
	(2, 1, 'About', 'v', 0, 'all', '', '');
/*!40000 ALTER TABLE `exp_category_groups` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_category_posts: ~10 rows (approximately)
DELETE FROM `exp_category_posts`;
/*!40000 ALTER TABLE `exp_category_posts` DISABLE KEYS */;
INSERT INTO `exp_category_posts` (`entry_id`, `cat_id`) VALUES
	(1, 1),
	(2, 1),
	(3, 4),
	(4, 3),
	(5, 3),
	(6, 3),
	(7, 3),
	(8, 3),
	(9, 3),
	(10, 2);
/*!40000 ALTER TABLE `exp_category_posts` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_channels: ~2 rows (approximately)
DELETE FROM `exp_channels`;
/*!40000 ALTER TABLE `exp_channels` DISABLE KEYS */;
INSERT INTO `exp_channels` (`channel_id`, `site_id`, `channel_name`, `channel_title`, `channel_url`, `channel_description`, `channel_lang`, `total_entries`, `total_records`, `total_comments`, `last_entry_date`, `last_comment_date`, `cat_group`, `deft_status`, `search_excerpt`, `deft_category`, `deft_comments`, `channel_require_membership`, `channel_max_chars`, `channel_html_formatting`, `channel_allow_img_urls`, `channel_auto_link_urls`, `channel_notify`, `channel_notify_emails`, `sticky_enabled`, `comment_url`, `comment_system_enabled`, `comment_require_membership`, `comment_moderate`, `comment_max_chars`, `comment_timelock`, `comment_require_email`, `comment_text_formatting`, `comment_html_formatting`, `comment_allow_img_urls`, `comment_auto_link_urls`, `comment_notify`, `comment_notify_authors`, `comment_notify_emails`, `comment_expiration`, `search_results_url`, `rss_url`, `enable_versioning`, `max_revisions`, `default_entry_title`, `title_field_label`, `url_title_prefix`, `preview_url`, `allow_preview`, `max_entries`) VALUES
	(1, 1, 'news', 'News', 'http://ee2/index.php/news', NULL, 'en', 3, 0, 0, 1409242030, 0, '1', 'open', 2, '2', 'y', 'y', 0, 'all', 'y', 'y', 'n', '', 'n', 'http://ee2/index.php/news/comments', 'y', 'n', 'n', 0, 0, 'y', 'xhtml', 'safe', 'n', 'y', 'n', 'n', '', 0, 'http://ee2/index.php/news/comments', '', 'n', 10, '', 'Title', '', NULL, 'y', 0),
	(2, 1, 'about', 'Information Pages', 'http://ee2/index.php/about', NULL, 'en', 7, 0, 0, 1409242030, 0, '2', 'open', 7, '', 'y', 'y', 0, 'all', 'y', 'n', 'n', '', 'n', 'http://ee2/index.php/news/comments', 'n', 'n', 'n', 0, 0, 'y', 'xhtml', 'safe', 'n', 'y', 'n', 'n', '', 0, 'http://ee2/index.php/news/comments', '', 'n', 10, '', 'Title', '', NULL, 'y', 0);
/*!40000 ALTER TABLE `exp_channels` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_channels_channel_fields: ~0 rows (approximately)
DELETE FROM `exp_channels_channel_fields`;
/*!40000 ALTER TABLE `exp_channels_channel_fields` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_channels_channel_fields` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_channels_channel_field_groups: ~2 rows (approximately)
DELETE FROM `exp_channels_channel_field_groups`;
/*!40000 ALTER TABLE `exp_channels_channel_field_groups` DISABLE KEYS */;
INSERT INTO `exp_channels_channel_field_groups` (`channel_id`, `group_id`) VALUES
	(1, 1),
	(2, 2);
/*!40000 ALTER TABLE `exp_channels_channel_field_groups` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_channels_statuses: ~3 rows (approximately)
DELETE FROM `exp_channels_statuses`;
/*!40000 ALTER TABLE `exp_channels_statuses` DISABLE KEYS */;
INSERT INTO `exp_channels_statuses` (`channel_id`, `status_id`) VALUES
	(1, 1),
	(1, 2),
	(1, 3);
/*!40000 ALTER TABLE `exp_channels_statuses` ENABLE KEYS */;

-- Dumping structure for table ee-test.exp_channel_data
DROP TABLE IF EXISTS `exp_channel_data`;
CREATE TABLE IF NOT EXISTS `exp_channel_data` (
  `entry_id` int(10) unsigned NOT NULL,
  `site_id` int(4) unsigned NOT NULL DEFAULT 1,
  `channel_id` int(4) unsigned NOT NULL,
  `field_id_1` text DEFAULT NULL,
  `field_ft_1` tinytext DEFAULT NULL,
  `field_id_2` text DEFAULT NULL,
  `field_ft_2` tinytext DEFAULT NULL,
  `field_id_3` text DEFAULT NULL,
  `field_ft_3` tinytext DEFAULT NULL,
  `field_id_4` text DEFAULT NULL,
  `field_ft_4` tinytext DEFAULT NULL,
  `field_id_5` text DEFAULT NULL,
  `field_ft_5` tinytext DEFAULT NULL,
  `field_id_6` text DEFAULT NULL,
  `field_ft_6` tinytext DEFAULT NULL,
  `field_id_7` text DEFAULT NULL,
  `field_ft_7` tinytext DEFAULT NULL,
  PRIMARY KEY (`entry_id`),
  KEY `channel_id` (`channel_id`),
  KEY `site_id` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Dumping data for table ee-test.exp_channel_data: ~10 rows (approximately)
DELETE FROM `exp_channel_data`;
/*!40000 ALTER TABLE `exp_channel_data` DISABLE KEYS */;
INSERT INTO `exp_channel_data` (`entry_id`, `site_id`, `channel_id`, `field_id_1`, `field_ft_1`, `field_id_2`, `field_ft_2`, `field_id_3`, `field_ft_3`, `field_id_4`, `field_ft_4`, `field_id_5`, `field_ft_5`, `field_id_6`, `field_ft_6`, `field_id_7`, `field_ft_7`) VALUES
	(1, 1, 1, 'Thank you for choosing ExpressionEngine! This entry contains helpful resources to help you <a href="https://docs.expressionengine.com/latest/intro/getting_the_most.html">get the most from ExpressionEngine</a> and the ExpressionEngine Community.\n\n<strong>Learning resources:</strong>\n\n<a href="https://docs.expressionengine.com/latest/">ExpressionEngine User Guide</a>\n<a href="https://docs.expressionengine.com/latest/intro/the_big_picture.html">The Big Picture</a>\n<a href="https://expressionengine.com/support">ExpressionEngine Support</a>\n\nIf you need to hire a web developer consider our <a href="https://expressionengine.com/pro-network/">Professionals Network</a>.\n\nWelcome to our community,\n\n<span style="font-size:16px;">The ExpressionEngine Team</span>', 'xhtml', '', 'xhtml', '{filedir_2}ee_banner_120_240.gif', 'none', '', 'xhtml', '', 'none', '', 'none', '', 'xhtml'),
	(2, 1, 1, 'Welcome to Agile Records, our Example Site.  Here you will be able to learn ExpressionEngine through a real site, with real features and in-depth comments to assist you along the way.\n\n', 'xhtml', '', 'xhtml', '{filedir_2}map.jpg', 'none', '', 'xhtml', '', 'none', '', 'none', '', 'xhtml'),
	(3, 1, 2, '', NULL, '', NULL, '', NULL, 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Duis congue accumsan tellus. Aliquam diam arcu, suscipit eu, condimentum sed, ultricies accumsan, massa.\n', 'xhtml', '{filedir_2}map2.jpg', 'none', '', 'none', 'Donec et ante. Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Vestibulum dignissim dolor nec erat dictum posuere. Vivamus lacinia, quam id fringilla dapibus, ante ante bibendum nulla, a ornare nisl est congue purus. Duis pulvinar vehicula diam.\n\nSed vehicula. Praesent vitae nisi. Phasellus molestie, massa sed varius ultricies, dolor lectus interdum felis, ut porta eros nibh at magna. Cras aliquam vulputate lacus. Nullam tempus vehicula mi. Quisque posuere, erat quis iaculis consequat, tortor ipsum varius mauris, sit amet pulvinar nibh mauris sed lectus. Cras vitae arcu sit amet nunc luctus molestie. Nam neque orci, tincidunt non, semper convallis, sodales fringilla, nulla. Donec non nunc. Sed condimentum urna hendrerit erat. Curabitur in felis in neque fermentum interdum.\n\nProin magna. In in orci. Curabitur at lectus nec arcu vehicula bibendum. Duis euismod sollicitudin augue. Maecenas auctor cursus odio.\n', 'xhtml'),
	(4, 1, 2, '', NULL, '', NULL, '', NULL, '', 'xhtml', '{filedir_2}staff_randell.png', 'none', 'Co-Owner/Label Manager', 'none', '', 'xhtml'),
	(5, 1, 2, '', NULL, '', NULL, '', NULL, '', 'xhtml', '{filedir_2}staff_chloe.png', 'none', 'Co-Owner / Press &amp; Marketing', 'none', '', 'xhtml'),
	(6, 1, 2, '', NULL, '', NULL, '', NULL, '', 'xhtml', '{filedir_2}staff_howard.png', 'none', 'Tours/Publicity/PR', 'none', '', 'xhtml'),
	(7, 1, 2, '', NULL, '', NULL, '', NULL, '', 'xhtml', '{filedir_2}staff_jane.png', 'none', 'Sales/Accounts', 'none', '', 'xhtml'),
	(8, 1, 2, '', NULL, '', NULL, '', NULL, '', 'xhtml', '{filedir_2}staff_josh.png', 'none', 'Product Manager', 'none', '', 'xhtml'),
	(9, 1, 2, '', NULL, '', NULL, '', NULL, '', 'xhtml', '{filedir_2}staff_jason.png', 'none', 'Graphic/Web Designer', 'none', '', 'xhtml'),
	(10, 1, 1, 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Proin congue mi a sapien. Duis augue erat, fringilla ac, volutpat ut, venenatis vitae, nisl. Phasellus lorem. Praesent mi. Suspendisse imperdiet felis a libero. uspendisse placerat tortor in ligula vestibulum vehicula.\n', 'xhtml', '', 'xhtml', '{filedir_2}testband300.jpg', 'none', '', NULL, '', NULL, '', NULL, '', NULL);
/*!40000 ALTER TABLE `exp_channel_data` ENABLE KEYS */;

-- Dumping structure for table ee-test.exp_channel_data_field_8
DROP TABLE IF EXISTS `exp_channel_data_field_8`;
CREATE TABLE IF NOT EXISTS `exp_channel_data_field_8` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned NOT NULL,
  `field_id_8` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `field_ft_8` tinytext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table ee-test.exp_channel_data_field_8: ~1 rows (approximately)
/*!40000 ALTER TABLE `exp_channel_data_field_8` DISABLE KEYS */;
INSERT INTO `exp_channel_data_field_8` (`id`, `entry_id`, `field_id_8`, `field_ft_8`) VALUES
	(1, 2, NULL, 'xhtml');
/*!40000 ALTER TABLE `exp_channel_data_field_8` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_channel_entries_autosave: ~0 rows (approximately)
DELETE FROM `exp_channel_entries_autosave`;
/*!40000 ALTER TABLE `exp_channel_entries_autosave` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_channel_entries_autosave` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_channel_fields: ~7 rows (approximately)
DELETE FROM `exp_channel_fields`;
/*!40000 ALTER TABLE `exp_channel_fields` DISABLE KEYS */;
INSERT INTO `exp_channel_fields` (`field_id`, `site_id`, `field_name`, `field_label`, `field_instructions`, `field_type`, `field_list_items`, `field_pre_populate`, `field_pre_channel_id`, `field_pre_field_id`, `field_ta_rows`, `field_maxl`, `field_required`, `field_text_direction`, `field_search`, `field_is_hidden`, `field_fmt`, `field_show_fmt`, `field_order`, `field_content_type`, `field_settings`, `legacy_field_data`) VALUES
	(1, 1, 'news_body', 'Body', '', 'textarea', '', 'n', 0, 0, 10, 0, 'n', 'ltr', 'y', 'n', 'xhtml', 'y', 2, 'any', 'YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToieSI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJ5IjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToieSI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToieSI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6InkiO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6InkiO30=', 'y'),
	(2, 1, 'news_extended', 'Extended text', '', 'textarea', '', 'n', 0, 0, 12, 0, 'n', 'ltr', 'n', 'y', 'xhtml', 'y', 3, 'any', 'YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToieSI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJ5IjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToieSI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToieSI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6InkiO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6InkiO30=', 'y'),
	(3, 1, 'news_image', 'News Image', '', 'file', '', 'n', 0, 0, 6, 128, 'n', 'ltr', 'n', 'n', 'none', 'n', 3, 'any', 'YTo3OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO3M6MTg6ImZpZWxkX2NvbnRlbnRfdHlwZSI7czo1OiJpbWFnZSI7fQ==', 'y'),
	(4, 1, 'about_body', 'Body', '', 'textarea', '', 'n', 0, 0, 6, 128, 'n', 'ltr', 'n', 'n', 'xhtml', 'y', 4, 'any', 'YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToieSI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJ5IjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToieSI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToieSI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6InkiO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6InkiO30=', 'y'),
	(5, 1, 'about_image', 'Image', 'URL Only', 'file', '', 'n', 0, 0, 6, 128, 'n', 'ltr', 'n', 'n', 'none', 'n', 5, 'any', 'YTo3OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO3M6MTg6ImZpZWxkX2NvbnRlbnRfdHlwZSI7czo1OiJpbWFnZSI7fQ==', 'y'),
	(6, 1, 'about_staff_title', 'Staff Member\'s Title', 'This is the Title that the staff member has within the company.  Example: CEO', 'text', '', 'n', 0, 0, 6, 128, 'n', 'ltr', 'y', 'n', 'none', 'n', 6, 'any', 'YTo4OntzOjE4OiJmaWVsZF9jb250ZW50X3RleHQiO2I6MDtzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO3M6MTg6ImZpZWxkX2NvbnRlbnRfdHlwZSI7czozOiJhbnkiO30=', 'y'),
	(7, 1, 'about_extended', 'Extended', '', 'textarea', '', 'n', 0, 0, 6, 128, 'n', 'ltr', 'y', 'y', 'xhtml', 'y', 7, 'any', 'YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToieSI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJ5IjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToieSI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToieSI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6InkiO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6InkiO30=', 'y'),
	(8, 0, 'related_news', 'Related news', '', 'relationship', '', 'n', NULL, NULL, 8, NULL, 'n', 'ltr', 'n', 'n', 'xhtml', 'y', 1, 'any', 'YToxMDp7czo4OiJjaGFubmVscyI7YToxOntpOjA7czoxOiIxIjt9czo3OiJleHBpcmVkIjtzOjA6IiI7czo2OiJmdXR1cmUiO3M6MDoiIjtzOjEwOiJjYXRlZ29yaWVzIjthOjA6e31zOjc6ImF1dGhvcnMiO2E6MDp7fXM6ODoic3RhdHVzZXMiO2E6MDp7fXM6NToibGltaXQiO3M6MzoiMTAwIjtzOjExOiJvcmRlcl9maWVsZCI7czo1OiJ0aXRsZSI7czo5OiJvcmRlcl9kaXIiO3M6MzoiYXNjIjtzOjE0OiJhbGxvd19tdWx0aXBsZSI7YjoxO30=', 'n');
/*!40000 ALTER TABLE `exp_channel_fields` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_channel_field_groups_fields: ~7 rows (approximately)
DELETE FROM `exp_channel_field_groups_fields`;
/*!40000 ALTER TABLE `exp_channel_field_groups_fields` DISABLE KEYS */;
INSERT INTO `exp_channel_field_groups_fields` (`field_id`, `group_id`) VALUES
	(1, 1),
	(2, 1),
	(3, 1),
	(4, 2),
	(5, 2),
	(6, 2),
	(7, 2),
  (8, 1);
/*!40000 ALTER TABLE `exp_channel_field_groups_fields` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_channel_form_settings: ~0 rows (approximately)
DELETE FROM `exp_channel_form_settings`;
/*!40000 ALTER TABLE `exp_channel_form_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_channel_form_settings` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_channel_member_roles: ~0 rows (approximately)
DELETE FROM `exp_channel_member_roles`;
/*!40000 ALTER TABLE `exp_channel_member_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_channel_member_roles` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_channel_titles: ~10 rows (approximately)
DELETE FROM `exp_channel_titles`;
/*!40000 ALTER TABLE `exp_channel_titles` DISABLE KEYS */;
INSERT INTO `exp_channel_titles` (`entry_id`, `site_id`, `channel_id`, `author_id`, `forum_topic_id`, `ip_address`, `title`, `url_title`, `status`, `status_id`, `versioning_enabled`, `view_count_one`, `view_count_two`, `view_count_three`, `view_count_four`, `allow_comments`, `sticky`, `entry_date`, `year`, `month`, `day`, `expiration_date`, `comment_expiration_date`, `edit_date`, `recent_comment_date`, `comment_total`) VALUES
	(1, 1, 1, 1, NULL, '127.0.0.1', 'Getting to Know ExpressionEngine', 'getting_to_know_expressionengine', 'open', 1, 'n', 0, 0, 0, 0, 'y', 'n', 1409242029, '2014', '08', '28', 0, 0, 20140828160710, NULL, 0),
	(2, 1, 1, 1, NULL, '127.0.0.1', 'Welcome to the Example Site!', 'welcome_to_the_example_site', 'open', 1, 'n', 0, 0, 0, 0, 'y', 'n', 1409242030, '2014', '08', '28', 0, 0, 20140828160710, NULL, 0),
	(3, 1, 2, 1, NULL, '127.0.0.1', 'About the Label', 'about_the_label', 'open', 1, 'n', 0, 0, 0, 0, 'y', 'n', 1409242030, '2014', '08', '28', 0, 0, 20140828160710, NULL, 0),
	(4, 1, 2, 1, NULL, '127.0.0.1', 'Randell', 'randell', 'open', 1, 'n', 0, 0, 0, 0, 'y', 'n', 1409242030, '2014', '08', '28', 0, 0, 20140828160710, NULL, 0),
	(5, 1, 2, 1, NULL, '127.0.0.1', 'Chloe', 'chloe', 'open', 1, 'n', 0, 0, 0, 0, 'y', 'n', 1409242030, '2014', '08', '28', 0, 0, 20140828160710, NULL, 0),
	(6, 1, 2, 1, NULL, '127.0.0.1', 'Howard', 'howard', 'open', 1, 'n', 0, 0, 0, 0, 'y', 'n', 1409242030, '2014', '08', '28', 0, 0, 20140828160710, NULL, 0),
	(7, 1, 2, 1, NULL, '127.0.0.1', 'Jane', 'jane', 'open', 1, 'n', 0, 0, 0, 0, 'y', 'n', 1409242030, '2014', '08', '28', 0, 0, 20140828160710, NULL, 0),
	(8, 1, 2, 1, NULL, '127.0.0.1', 'Josh', 'josh', 'open', 1, 'n', 0, 0, 0, 0, 'y', 'n', 1409242030, '2014', '08', '28', 0, 0, 20140828160710, NULL, 0),
	(9, 1, 2, 1, NULL, '127.0.0.1', 'Jason', 'jason', 'open', 1, 'n', 0, 0, 0, 0, 'y', 'n', 1409242030, '2014', '08', '28', 0, 0, 20140828160710, NULL, 0),
	(10, 1, 1, 1, NULL, '127.0.0.1', 'Band Title', 'band_title', 'Featured', 3, 'n', 0, 0, 0, 0, 'y', 'n', 1409242030, '2014', '08', '28', 0, 0, 20140828160710, NULL, 0);
/*!40000 ALTER TABLE `exp_channel_titles` ENABLE KEYS */;

-- Dumping structure for table ee-test.exp_comments
DROP TABLE IF EXISTS `exp_comments`;
CREATE TABLE IF NOT EXISTS `exp_comments` (
  `comment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) DEFAULT 1,
  `entry_id` int(10) unsigned DEFAULT 0,
  `channel_id` int(4) unsigned DEFAULT 1,
  `author_id` int(10) unsigned DEFAULT 0,
  `status` char(1) DEFAULT '0',
  `name` varchar(50) DEFAULT NULL,
  `email` varchar(75) DEFAULT NULL,
  `url` varchar(75) DEFAULT NULL,
  `location` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `comment_date` int(10) DEFAULT NULL,
  `edit_date` int(10) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  PRIMARY KEY (`comment_id`),
  KEY `entry_id` (`entry_id`),
  KEY `channel_id` (`channel_id`),
  KEY `author_id` (`author_id`),
  KEY `status` (`status`),
  KEY `site_id` (`site_id`),
  KEY `comment_date_idx` (`comment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Dumping data for table ee-test.exp_comments: ~0 rows (approximately)
DELETE FROM `exp_comments`;
/*!40000 ALTER TABLE `exp_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_comments` ENABLE KEYS */;

-- Dumping structure for table ee-test.exp_comment_subscriptions
DROP TABLE IF EXISTS `exp_comment_subscriptions`;
CREATE TABLE IF NOT EXISTS `exp_comment_subscriptions` (
  `subscription_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned DEFAULT NULL,
  `member_id` int(10) DEFAULT 0,
  `email` varchar(75) DEFAULT NULL,
  `subscription_date` varchar(10) DEFAULT NULL,
  `notification_sent` char(1) DEFAULT 'n',
  `hash` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`subscription_id`),
  KEY `entry_id` (`entry_id`),
  KEY `member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Dumping data for table ee-test.exp_comment_subscriptions: ~0 rows (approximately)
DELETE FROM `exp_comment_subscriptions`;
/*!40000 ALTER TABLE `exp_comment_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_comment_subscriptions` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_config: ~159 rows (approximately)
DELETE FROM `exp_config`;

/*!40000 ALTER TABLE `exp_config` DISABLE KEYS */;
INSERT INTO `exp_config` (`config_id`, `site_id`, `key`, `value`) VALUES
	(1, 1, 'image_resize_protocol', 'gd2'),
	(2, 1, 'image_library_path', ''),
	(3, 1, 'thumbnail_prefix', 'thumb'),
	(4, 1, 'word_separator', 'dash'),
	(5, 1, 'use_category_name', 'n'),
	(6, 1, 'reserved_category_word', 'category'),
	(7, 1, 'auto_convert_high_ascii', 'n'),
	(8, 1, 'new_posts_clear_caches', 'y'),
	(9, 1, 'auto_assign_cat_parents', 'y'),
	(10, 1, 'enable_comments', 'y'),
	(11, 1, 'comment_word_censoring', 'n'),
	(12, 1, 'comment_moderation_override', 'n'),
	(13, 1, 'comment_edit_time_limit', '0'),
	(14, 1, 'un_min_len', '4'),
	(15, 1, 'pw_min_len', '5'),
	(16, 1, 'allow_member_registration', 'n'),
	(17, 1, 'allow_member_localization', 'y'),
	(18, 1, 'req_mbr_activation', 'email'),
	(19, 1, 'new_member_notification', 'n'),
	(20, 1, 'mbr_notification_emails', ''),
	(21, 1, 'require_terms_of_service', 'y'),
	(22, 1, 'default_primary_role', '5'),
	(23, 1, 'profile_trigger', 'member'),
	(24, 1, 'member_theme', 'default'),
	(27, 1, 'avatar_url', '{base_url}/images/avatars/'),
	(28, 1, 'avatar_path', '{base_path}/images/avatars/'),
	(29, 1, 'avatar_max_width', '100'),
	(30, 1, 'avatar_max_height', '100'),
	(31, 1, 'avatar_max_kb', '50'),
	(32, 1, 'enable_photos', 'n'),
	(33, 1, 'photo_url', '{base_url}/images/member_photos/'),
	(34, 1, 'photo_path', '{base_path}/images/member_photos/'),
	(35, 1, 'photo_max_width', '100'),
	(36, 1, 'photo_max_height', '100'),
	(37, 1, 'photo_max_kb', '50'),
	(38, 1, 'allow_signatures', 'y'),
	(39, 1, 'sig_maxlength', '500'),
	(40, 1, 'sig_allow_img_hotlink', 'n'),
	(41, 1, 'sig_allow_img_upload', 'n'),
	(42, 1, 'sig_img_url', '{base_url}/images/signature_attachments/'),
	(43, 1, 'sig_img_path', '{base_path}/images/signature_attachments/'),
	(44, 1, 'sig_img_max_width', '480'),
	(45, 1, 'sig_img_max_height', '80'),
	(46, 1, 'sig_img_max_kb', '30'),
	(47, 1, 'prv_msg_upload_path', '../images/pm_attachments/'),
	(48, 1, 'prv_msg_max_attachments', '3'),
	(49, 1, 'prv_msg_attach_maxsize', '250'),
	(50, 1, 'prv_msg_attach_total', '100'),
	(51, 1, 'prv_msg_html_format', 'safe'),
	(52, 1, 'prv_msg_auto_links', 'y'),
	(53, 1, 'prv_msg_max_chars', '6000'),
	(54, 1, 'memberlist_order_by', 'member_id'),
	(55, 1, 'memberlist_sort_order', 'desc'),
	(56, 1, 'memberlist_row_limit', '20'),
	(57, 1, 'site_index', 'index.php'),
	(58, 1, 'site_url', 'http://localhost:8888/'),
	(59, 1, 'theme_folder_url', '{base_url}/themes/'),
	(60, 1, 'webmaster_email', 'support@expressionengine.com'),
	(61, 1, 'webmaster_name', ''),
	(62, 1, 'channel_nomenclature', 'channel'),
	(63, 1, 'max_caches', '150'),
	(64, 1, 'captcha_url', '{base_url}/images/captchas/'),
	(65, 1, 'captcha_path', '{base_path}/images/captchas/'),
	(66, 1, 'captcha_font', 'y'),
	(67, 1, 'captcha_rand', 'y'),
	(68, 1, 'captcha_require_members', 'n'),
	(69, 1, 'enable_db_caching', 'n'),
	(70, 1, 'enable_sql_caching', 'n'),
	(71, 1, 'force_query_string', 'n'),
	(72, 1, 'show_profiler', 'n'),
	(73, 1, 'template_debugging', 'n'),
	(74, 1, 'include_seconds', 'n'),
	(75, 1, 'cookie_domain', ''),
	(76, 1, 'cookie_path', ''),
	(77, 1, 'website_session_type', 'c'),
	(78, 1, 'cp_session_type', 'c'),
	(79, 1, 'allow_username_change', 'y'),
	(80, 1, 'allow_multi_logins', 'y'),
	(81, 1, 'password_lockout', 'y'),
	(82, 1, 'password_lockout_interval', '1'),
	(83, 1, 'require_ip_for_login', 'y'),
	(84, 1, 'require_ip_for_posting', 'y'),
	(85, 1, 'password_security_policy', 'basic'),
	(86, 1, 'allow_dictionary_pw', 'y'),
	(87, 1, 'name_of_dictionary_file', ''),
	(88, 1, 'xss_clean_uploads', 'y'),
	(89, 1, 'redirect_method', 'redirect'),
	(90, 1, 'deft_lang', 'english'),
	(91, 1, 'xml_lang', 'en'),
	(92, 1, 'send_headers', 'y'),
	(93, 1, 'gzip_output', 'n'),
	(94, 1, 'log_referrers', 'n'),
	(95, 1, 'max_referrers', '500'),
	(96, 1, 'date_format', '%n/%j/%Y'),
	(97, 1, 'time_format', '12'),
	(98, 1, 'server_offset', ''),
	(99, 1, 'default_site_timezone', 'America/New_York'),
	(100, 1, 'mail_protocol', 'mail'),
	(101, 1, 'smtp_server', ''),
	(102, 1, 'smtp_username', ''),
	(103, 1, 'smtp_password', ''),
	(104, 1, 'email_debug', 'n'),
	(105, 1, 'email_charset', 'utf-8'),
	(106, 1, 'email_batchmode', 'n'),
	(107, 1, 'email_batch_size', ''),
	(108, 1, 'mail_format', 'plain'),
	(109, 1, 'word_wrap', 'y'),
	(110, 1, 'email_console_timelock', '5'),
	(111, 1, 'log_email_console_msgs', 'y'),
	(112, 1, 'cp_theme', 'default'),
	(113, 1, 'log_search_terms', 'y'),
	(114, 1, 'deny_duplicate_data', 'y'),
	(115, 1, 'redirect_submitted_links', 'n'),
	(116, 1, 'enable_censoring', 'n'),
	(117, 1, 'censored_words', ''),
	(118, 1, 'censor_replacement', ''),
	(119, 1, 'banned_ips', ''),
	(120, 1, 'banned_emails', ''),
	(121, 1, 'banned_usernames', ''),
	(122, 1, 'banned_screen_names', ''),
	(123, 1, 'ban_action', 'restrict'),
	(124, 1, 'ban_message', 'This site is currently unavailable'),
	(125, 1, 'ban_destination', 'http://www.yahoo.com/'),
	(126, 1, 'enable_emoticons', 'y'),
	(127, 1, 'emoticon_url', '{base_url}/images/smileys/'),
	(128, 1, 'recount_batch_total', '1000'),
	(129, 1, 'new_version_check', 'y'),
	(130, 1, 'enable_throttling', 'n'),
	(131, 1, 'banish_masked_ips', 'y'),
	(132, 1, 'max_page_loads', '10'),
	(133, 1, 'time_interval', '8'),
	(134, 1, 'lockout_time', '30'),
	(135, 1, 'banishment_type', 'message'),
	(136, 1, 'banishment_url', ''),
	(137, 1, 'banishment_message', 'You have exceeded the allowed page load frequency.'),
	(138, 1, 'enable_search_log', 'y'),
	(139, 1, 'max_logged_searches', '500'),
	(140, 1, 'theme_folder_path', '{base_path}/themes/'),
	(141, 1, 'is_site_on', 'y'),
	(142, 1, 'rte_enabled', 'y'),
	(143, 1, 'rte_default_toolset_id', '1'),
	(144, 1, 'cookie_httponly', 'y'),
	(145, 1, 'cookie_secure', 'n'),
	(146, 1, 'require_captcha', 'n'),
	(147, 1, 'email_newline', '\\n'),
	(148, 1, 'email_smtp_crypto', 'ssl'),
	(149, 1, 'enable_template_routes', 'y'),
	(150, 1, 'strict_urls', 'y'),
	(151, 1, 'site_404', 'about/404'),
	(152, 1, 'save_tmpl_revisions', 'n'),
	(153, 1, 'max_tmpl_revisions', '5'),
	(154, 1, 'tmpl_file_basepath', '/'),
	(155, 0, 'cache_driver', 'dummy'),
	(156, 0, 'debug', '1'),
	(157, 0, 'is_system_on', 'y'),
	(158, 1, 'base_url', 'http://localhost:8888/'),
	(159, 1, 'base_path', '/home/runner/work/ExpressionEngine/ExpressionEngine'),
	(160, 1, 'cp_url', '{base_url}admin.php');
/*!40000 ALTER TABLE `exp_config` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_consents: ~0 rows (approximately)
DELETE FROM `exp_consents`;
/*!40000 ALTER TABLE `exp_consents` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_consents` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_consent_audit_log: ~0 rows (approximately)
DELETE FROM `exp_consent_audit_log`;
/*!40000 ALTER TABLE `exp_consent_audit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_consent_audit_log` ENABLE KEYS */;


-- Dumping data for table ee-test.exp_consent_request_version_cookies: ~0 rows (approximately)
DELETE FROM `exp_consent_request_version_cookies`;
/*!40000 ALTER TABLE `exp_consent_request_version_cookies` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_consent_request_version_cookies` ENABLE KEYS */;


-- Dumping data for table ee-test.exp_content_types: ~2 rows (approximately)
DELETE FROM `exp_content_types`;
/*!40000 ALTER TABLE `exp_content_types` DISABLE KEYS */;
INSERT INTO `exp_content_types` (`content_type_id`, `name`) VALUES
	(2, 'channel'),
	(1, 'grid');
/*!40000 ALTER TABLE `exp_content_types` ENABLE KEYS */;

-- Dumping structure for table ee-test.exp_cookie_settings
DROP TABLE IF EXISTS `exp_cookie_settings`;
CREATE TABLE IF NOT EXISTS `exp_cookie_settings` (
  `cookie_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cookie_provider` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cookie_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cookie_lifetime` int(10) unsigned DEFAULT NULL,
  `cookie_enforced_lifetime` int(10) unsigned DEFAULT NULL,
  `cookie_title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cookie_description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`cookie_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping structure for table ee-test.exp_cp_log
DROP TABLE IF EXISTS `exp_cp_log`;
CREATE TABLE IF NOT EXISTS `exp_cp_log` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT 1,
  `member_id` int(10) unsigned NOT NULL,
  `username` varchar(32) NOT NULL,
  `ip_address` varchar(45) NOT NULL DEFAULT '0',
  `act_date` int(10) NOT NULL,
  `action` varchar(200) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `site_id` (`site_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

-- Dumping data for table ee-test.exp_cp_log: ~5 rows (approximately)
/*!40000 ALTER TABLE `exp_cp_log` DISABLE KEYS */;
INSERT INTO `exp_cp_log` (`id`, `site_id`, `member_id`, `username`, `ip_address`, `act_date`, `action`) VALUES
	(1, 1, 0, '0', '127.0.0.1', 1588591743, 'Hash algorithm changed for "admin" (1)'),
	(2, 1, 1, 'admin', '127.0.0.1', 1588591743, 'Logged in'),
	(3, 1, 1, 'admin', '127.0.0.1', 1588591844, 'Logged in');
/*!40000 ALTER TABLE `exp_cp_log` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_developer_log: ~0 rows (approximately)
DELETE FROM `exp_developer_log`;
/*!40000 ALTER TABLE `exp_developer_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_developer_log` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_email_cache: ~0 rows (approximately)
DELETE FROM `exp_email_cache`;
/*!40000 ALTER TABLE `exp_email_cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_email_cache` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_email_cache_mg: ~0 rows (approximately)
DELETE FROM `exp_email_cache_mg`;
/*!40000 ALTER TABLE `exp_email_cache_mg` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_email_cache_mg` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_email_cache_ml: ~0 rows (approximately)
DELETE FROM `exp_email_cache_ml`;
/*!40000 ALTER TABLE `exp_email_cache_ml` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_email_cache_ml` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_email_console_cache: ~0 rows (approximately)
DELETE FROM `exp_email_console_cache`;
/*!40000 ALTER TABLE `exp_email_console_cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_email_console_cache` ENABLE KEYS */;

-- Dumping structure for table ee-test.exp_email_tracker
DROP TABLE IF EXISTS `exp_email_tracker`;
CREATE TABLE IF NOT EXISTS `exp_email_tracker` (
  `email_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email_date` int(10) unsigned NOT NULL DEFAULT 0,
  `sender_ip` varchar(45) NOT NULL,
  `sender_email` varchar(75) NOT NULL,
  `sender_username` varchar(50) NOT NULL,
  `number_recipients` int(4) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`email_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Dumping data for table ee-test.exp_email_tracker: ~0 rows (approximately)
DELETE FROM `exp_email_tracker`;
/*!40000 ALTER TABLE `exp_email_tracker` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_email_tracker` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_entry_manager_views: ~0 rows (approximately)
DELETE FROM `exp_entry_manager_views`;
/*!40000 ALTER TABLE `exp_entry_manager_views` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_entry_manager_views` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_entry_versioning: ~0 rows (approximately)
DELETE FROM `exp_entry_versioning`;
/*!40000 ALTER TABLE `exp_entry_versioning` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_entry_versioning` ENABLE KEYS */;


-- Dumping data for table ee-test.exp_field_groups: ~2 rows (approximately)
DROP TABLE IF EXISTS `exp_field_groups`;

CREATE TABLE IF NOT EXISTS `exp_field_groups` (
  `group_id` int unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int unsigned DEFAULT '1',
  `group_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `short_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `group_description` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`group_id`),
  KEY `site_id` (`site_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8_unicode_ci;
/*!40000 ALTER TABLE `exp_field_groups` DISABLE KEYS */;
INSERT INTO `exp_field_groups` (`group_id`, `site_id`, `group_name`, `short_name`) VALUES
	(1, 1, 'News', 'news'),
	(2, 1, 'About', 'about');
/*!40000 ALTER TABLE `exp_field_groups` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_files: ~10 rows (approximately)
DELETE FROM `exp_files`;
/*!40000 ALTER TABLE `exp_files` DISABLE KEYS */;
INSERT INTO `exp_files` (`file_id`, `site_id`, `title`, `upload_location_id`, `mime_type`, `file_type`, `file_name`, `file_size`, `description`, `credit`, `location`, `uploaded_by_member_id`, `upload_date`, `modified_by_member_id`, `modified_date`, `file_hw_original`) VALUES
	(1, 1, 'staff_jane.png', 2, 'image/png', 'img', 'staff_jane.png', 51612, NULL, NULL, NULL, 1, 1302889304, 1, 1302889304, ''),
	(2, 1, 'staff_jason.png', 2, 'image/png', 'img', 'staff_jason.png', 51430, NULL, NULL, NULL, 1, 1302888304, 1, 1302888304, ''),
	(3, 1, 'staff_josh.png', 2, 'image/png', 'img', 'staff_josh.png', 50638, NULL, NULL, NULL, 1, 1302887304, 1, 1302887304, ''),
	(4, 1, 'staff_randell.png', 2, 'image/png', 'img', 'staff_randell.png', 51681, NULL, NULL, NULL, 1, 1302886304, 1, 1302886304, ''),
	(5, 1, 'ee_banner_120_240.gif', 2, 'image/gif', 'img', 'ee_banner_120_240.gif', 9257, NULL, NULL, NULL, 1, 1302885304, 1, 1302885304, ''),
	(6, 1, 'testband300.jpg', 2, 'image/jpeg', 'img', 'testband300.jpg', 23986, NULL, NULL, NULL, 1, 1302884304, 1, 1302884304, ''),
	(7, 1, 'map.jpg', 2, 'image/jpeg', 'img', 'map.jpg', 71299, NULL, NULL, NULL, 1, 1302883304, 1, 1302883304, ''),
	(8, 1, 'map2.jpg', 2, 'image/jpeg', 'img', 'map2.jpg', 49175, NULL, NULL, NULL, 1, 1302882304, 1, 1302882304, ''),
	(9, 1, 'staff_chloe.png', 2, 'image/png', 'img', 'staff_chloe.png', 50262, NULL, NULL, NULL, 1, 1302881304, 1, 1302881304, ''),
	(10, 1, 'staff_howard.png', 2, 'image/png', 'img', 'staff_howard.png', 51488, NULL, NULL, NULL, 1, 1302880304, 1, 1302880304, '');
/*!40000 ALTER TABLE `exp_files` ENABLE KEYS */;

DELETE FROM `exp_file_data`;
/*!40000 ALTER TABLE `exp_file_data` DISABLE KEYS */;
INSERT INTO `exp_file_data` (`file_id`) VALUES
	(1),
	(2),
	(3),
	(4),
	(5),
	(6),
	(7),
	(8),
	(9),
	(10);
/*!40000 ALTER TABLE `exp_file_data` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_file_categories: ~0 rows (approximately)
DELETE FROM `exp_file_categories`;
/*!40000 ALTER TABLE `exp_file_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_file_categories` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_file_dimensions: ~0 rows (approximately)
DELETE FROM `exp_file_dimensions`;
/*!40000 ALTER TABLE `exp_file_dimensions` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_file_dimensions` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_file_watermarks: ~0 rows (approximately)
DELETE FROM `exp_file_watermarks`;
/*!40000 ALTER TABLE `exp_file_watermarks` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_file_watermarks` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_fluid_field_data: ~0 rows (approximately)
DELETE FROM `exp_fluid_field_data`;
/*!40000 ALTER TABLE `exp_fluid_field_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_fluid_field_data` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_global_variables: ~14 rows (approximately)
DELETE FROM `exp_global_variables`;
/*!40000 ALTER TABLE `exp_global_variables` DISABLE KEYS */;
INSERT INTO `exp_global_variables` (`variable_id`, `site_id`, `variable_name`, `variable_data`, `edit_date`) VALUES
	(1, 1, '.htaccess', 'deny from all', 0),
	(2, 1, 'branding_begin', '<div id="branding">\n	<div id="branding_logo"></div>\n	<div id="branding_sub">\n		<h1><a href="{site_url}" title="Agile Records Home"></a></h1>', 0),
	(3, 1, 'branding_end', '</div> <!-- ending #branding_sub -->\n</div> <!-- ending #branding -->', 0),
	(4, 1, 'comment_guidelines', '<div id="comment_guidelines">\n	<h6>Comment Guidelines</h6>\n	<p>Basic HTML formatting permitted - <br />\n		<code>&lt;ul&gt;</code>, <code>&lt;li&gt;</code>, <code>&lt;strong&gt;</code>, <code>&lt;em&gt;</code>, <code>&lt;a href&gt;</code>, <code>&lt;blockquote&gt;</code>, <code>&lt;code&gt;</code></p>\n</div>', 0),
	(5, 1, 'favicon', '<!-- Favicon -->\n', 0),
	(6, 1, 'html_close', '<p id="elapsed_time">Elapsed time: <em>{elapsed_time}</em></p><p id="memory_usage">Memory usage: <em>{memory_usage}</em></p></body>\n</html>', 0),
	(7, 1, 'html_head', '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">\n<html xmlns="http://www.w3.org/1999/xhtml">\n<head>\n<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />\n', 0),
	(8, 1, 'html_head_end', '</head>\n', 0),
	(9, 1, 'js', '<!-- JS -->\n<script src="http://code.jquery.com/jquery-1.12.1.min.js" type="text/javascript"></script>\n<script src="{site_url}themes/site/default/js/onload.js" type="text/javascript"></script>', 0),
	(10, 1, 'nav_access', '<ul id="nav_access">\n	<li><a href="#navigation">Skip to navigation</a></li>\n	<li><a href="#primary_content_wrapper">Skip to content</a></li>\n</ul>', 0),
	(11, 1, 'rss', '<!-- RSS -->\n<link href="{path=news/rss}" rel="alternate" type="application/rss+xml" title="RSS Feed" />', 0),
	(12, 1, 'rss_links', '<h5>RSS Feeds <img src="{site_url}themes/site/default/images/rss12.gif" alt="RSS Icon" class="rssicon" /></h5>\n		<div id="news_rss">\n			<p>Subscribe to our RSS Feeds</p>\n			<ul>\n				<li><a href="{path=\'news/rss\'}">News RSS Feed</a></li>\n				<li><a href="{path=\'news/atom\'}">News ATOM Feed</a></li>\n			</ul>\n		</div>', 0),
	(13, 1, 'wrapper_begin', '<div id="page">\n<div id="content_wrapper">', 0),
	(14, 1, 'wrapper_close', '</div> <!-- ending #content_wrapper -->\n</div> <!-- ending #page -->', 0);
/*!40000 ALTER TABLE `exp_global_variables` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_grid_columns: ~0 rows (approximately)
DELETE FROM `exp_grid_columns`;
/*!40000 ALTER TABLE `exp_grid_columns` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_grid_columns` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_html_buttons: ~5 rows (approximately)
DELETE FROM `exp_html_buttons`;
/*!40000 ALTER TABLE `exp_html_buttons` DISABLE KEYS */;
INSERT INTO `exp_html_buttons` (`id`, `site_id`, `member_id`, `tag_name`, `tag_open`, `tag_close`, `accesskey`, `tag_order`, `tag_row`, `classname`) VALUES
	(1, 1, 0, 'Bold text', '<strong>', '</strong>', 'b', 1, '1', 'html-bold'),
	(2, 1, 0, 'Italic text', '<em>', '</em>', 'i', 2, '1', 'html-italic'),
	(3, 1, 0, 'Blockquote', '<blockquote>', '</blockquote>', 'q', 3, '1', 'html-quote'),
	(4, 1, 0, 'Link', '<a href="[![Link:!:http://]!]"(!( title="[![Title]!]")!)>', '</a>', 'a', 4, '1', 'html-link'),
	(5, 1, 0, 'Image', '<img src="[![Link:!:http://]!]" alt="[![Alternative text]!]" />', '', '', 5, '1', 'html-upload');
/*!40000 ALTER TABLE `exp_html_buttons` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_layout_publish: ~0 rows (approximately)
DELETE FROM `exp_layout_publish`;
/*!40000 ALTER TABLE `exp_layout_publish` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_layout_publish` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_layout_publish_member_roles: ~0 rows (approximately)
DELETE FROM `exp_layout_publish_member_roles`;
/*!40000 ALTER TABLE `exp_layout_publish_member_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_layout_publish_member_roles` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_members: ~7 rows (approximately)
DELETE FROM `exp_members`;
/*!40000 ALTER TABLE `exp_members` DISABLE KEYS */;
INSERT INTO `exp_members` (`member_id`, `role_id`, `username`, `screen_name`, `password`, `salt`, `unique_id`, `crypt_key`, `authcode`, `email`, `signature`, `avatar_filename`, `avatar_width`, `avatar_height`, `photo_filename`, `photo_width`, `photo_height`, `sig_img_filename`, `sig_img_width`, `sig_img_height`, `ignore_list`, `private_messages`, `accept_messages`, `last_view_bulletins`, `last_bulletin_date`, `ip_address`, `join_date`, `last_visit`, `last_activity`, `total_entries`, `total_comments`, `total_forum_topics`, `total_forum_posts`, `last_entry_date`, `last_comment_date`, `last_forum_post_date`, `last_email_date`, `in_authorlist`, `accept_admin_email`, `accept_user_email`, `notify_by_default`, `notify_of_pm`, `display_signatures`, `parse_smileys`, `smart_notifications`, `language`, `timezone`, `time_format`, `date_format`, `include_seconds`, `profile_theme`, `forum_theme`, `tracker`, `template_size`, `notepad`, `notepad_size`, `bookmarklets`, `quick_links`, `quick_tabs`, `show_sidebar`, `pmember_id`, `cp_homepage`, `cp_homepage_channel`, `cp_homepage_custom`) VALUES
	(1, 1, 'admin', 'Admin', '0d4b0184ac57f2233b7a7ccb8c844b3121ddffcc67d0144d7b5ef0594eb64f96fb6ee53f9d23bb4e9ed4d1b7433eb848eee2cfca030ebd15461ca8a68ff4dcc5', '3Vlfi\'R1tviY"`hA+4~h2#TGO:m;ePK`E~\\$G[%C;+W_<Cr\\fA9~v{U&x^6Ts9.!,BT1OjSl}hFdV6FPO4bZX-=`pa_8_UPrFIU@XbTN]5?9vAJ>E~Qn`2)Zo]I$=d<t', 'bc62f762437a95f19b722924b85f76bc19fb6430', '4da80648d120e6ce5c9886df6ae5de8f1fa3c3d5', NULL, 'cypress@expressionengine.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'y', 0, 0, '127.0.0.1', 1409242030, 1588591744, 1588592390, 10, 0, 0, 0, 1409242030, 0, 0, 0, 'n', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'english', 'America/New_York', '12', '%n/%j/%Y', 'n', NULL, NULL, NULL, '28', NULL, '18', NULL, '', NULL, 'n', 0, NULL, NULL, NULL),
	(2, 1, 'robin', 'Robin Screen', '5zaa61e4c9b93f3f0682250b6cf8331b7ee68fd8', '', 'bz62f762437a95f19b722924b85f76bc19fb6430', NULL, NULL, 'mediacow@localhost', NULL, '8bit_kevin.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '4', 1, 'y', 0, 0, '127.0.0.1', 1465853984, 1491259180, 1491324114, 83, 10, 7, 4, 0, 1469650137, 1487020433, 0, 'n', 'y', 'y', 'y', 'y', 'y', 'n', 'y', 'english', 'America/New_York', '12', '%n/%j/%Y', 'n', NULL, 'Shares', NULL, '28', NULL, '18', NULL, 'Query Results|index.php?/cp/utilities/query|1\nOffsite 2|http://test.com|4', NULL, 'n', 0,'entries_edit', '{"1":"1","2":"14","3":"8"}', ''),
	(3, 2, 'banned1', 'Banned 1', '5aaa61e4c9b93f3f0682250b6cf8331b7ee68fd8', '', 'by62f762437a95f19b722924b85f76bc19fb6430', NULL, NULL, 'edit2@localhost', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'y', 0, 0, '127.0.0.1', 1484840926, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 'n', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'english', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '28', NULL, '18', NULL, NULL, NULL, 'n', 0, NULL, NULL, NULL),
	(4, 4, 'pending1', 'Pending 1', '5daa61e4c9b93f3f0682250b6cf8331b7ee68fd8', '', 'bx62f762437a95f19b722924b85f76bc19fb6430', NULL, NULL, 'edit5@localhost', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'y', 0, 0, '127.0.0.1', 1484841088, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 'n', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'english', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '28', NULL, '18', NULL, NULL, NULL, 'n', 0, NULL, NULL, NULL),
	(5, 4, 'pending2', 'Pending 2', '5eaa61e4c9b93f3f0682250b6cf8331b7ee68fd8', '', 'bm62f762437a95f19b722924b85f76bc19fb6430', NULL, NULL, 'edit6@localhost', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'y', 0, 0, '127.0.0.1', 1485306436, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'n', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'english', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '28', NULL, '18', NULL, NULL, NULL, 'n', 0, NULL, NULL, NULL),
	(6, 5, 'member1', 'Member 1', '5faa61e4c9b93f3f0682250b6cf8331b7ee68fd8', '', 'bn62f762437a95f19b722924b85f76bc19fb6430', NULL, NULL, 'editor7@localhost', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'y', 0, 0, '127.0.0.1', 1485306584, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'n', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'english', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '28', NULL, '18', NULL, NULL, NULL, 'n', 0, NULL, NULL, NULL),
	(7, 5, 'member2', 'Member 2', '5gaa61e4c9b93f3f0682250b6cf8331b7ee68fd8', '', 'bo62f762437a95f19b722924b85f76bc19fb6430', NULL, NULL, 'editor8@localhost', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'y', 0, 0, '127.0.0.1', 1485307720, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'n', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'english', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '28', NULL, '18', NULL, NULL, NULL, 'n', 0, NULL, NULL, NULL);
/*!40000 ALTER TABLE `exp_members` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_members_roles: ~7 rows (approximately)
DELETE FROM `exp_members_roles`;
/*!40000 ALTER TABLE `exp_members_roles` DISABLE KEYS */;
INSERT INTO `exp_members_roles` (`member_id`, `role_id`) VALUES
	(1, 1),
	(2, 1),
	(3, 2),
	(4, 4),
	(5, 4),
	(6, 5),
	(7, 5);
/*!40000 ALTER TABLE `exp_members_roles` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_members_role_groups: ~0 rows (approximately)
DELETE FROM `exp_members_role_groups`;
/*!40000 ALTER TABLE `exp_members_role_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_members_role_groups` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_member_bulletin_board: ~0 rows (approximately)
DELETE FROM `exp_member_bulletin_board`;
/*!40000 ALTER TABLE `exp_member_bulletin_board` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_member_bulletin_board` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_member_data: ~7 rows (approximately)
DELETE FROM `exp_member_data`;
/*!40000 ALTER TABLE `exp_member_data` DISABLE KEYS */;
INSERT INTO `exp_member_data` (`member_id`) VALUES
	(1),
	(2),
	(3),
	(4),
	(5),
	(6),
	(7);
/*!40000 ALTER TABLE `exp_member_data` ENABLE KEYS */;

-- Dumping structure for table ee-test.exp_member_data_field_1
DROP TABLE IF EXISTS `exp_member_data_field_1`;
CREATE TABLE IF NOT EXISTS `exp_member_data_field_1` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL,
  `m_field_id_1` int(10) DEFAULT 0,
  `m_field_dt_1` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `m_field_ft_1` tinytext COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table ee-test.exp_member_data_field_1: ~7 rows (approximately)
DELETE FROM `exp_member_data_field_1`;
/*!40000 ALTER TABLE `exp_member_data_field_1` DISABLE KEYS */;
INSERT INTO `exp_member_data_field_1` (`id`, `member_id`, `m_field_id_1`, `m_field_dt_1`, `m_field_ft_1`) VALUES
	(1, 1, 0, NULL, NULL),
	(2, 2, -24966000, NULL, NULL),
	(3, 3, 0, NULL, NULL),
	(4, 4, 0, NULL, NULL),
	(5, 5, 0, NULL, NULL),
	(6, 6, 0, NULL, NULL),
	(7, 7, 0, NULL, NULL);
/*!40000 ALTER TABLE `exp_member_data_field_1` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_member_fields: ~0 rows (approximately)
DELETE FROM `exp_member_fields`;
/*!40000 ALTER TABLE `exp_member_fields` DISABLE KEYS */;
INSERT INTO `exp_member_fields` (`m_field_id`, `m_field_name`, `m_field_label`, `m_field_description`, `m_field_type`, `m_field_list_items`, `m_field_ta_rows`, `m_field_maxl`, `m_field_width`, `m_field_search`, `m_field_required`, `m_field_public`, `m_field_reg`, `m_field_cp_reg`, `m_field_fmt`, `m_field_show_fmt`, `m_field_exclude_from_anon`, `m_field_order`, `m_field_text_direction`, `m_field_settings`, `m_legacy_field_data`) VALUES
	(1, 'birthday', 'Birthday', '', 'date', '', 8, NULL, NULL, 'y', 'n', 'y', 'n', 'n', 'none', 'y', 'n', 1, 'ltr', NULL, 'n');
/*!40000 ALTER TABLE `exp_member_fields` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_member_news_views: ~0 rows (approximately)
DELETE FROM `exp_member_news_views`;
/*!40000 ALTER TABLE `exp_member_news_views` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_member_news_views` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_member_search: ~0 rows (approximately)
DELETE FROM `exp_member_search`;
/*!40000 ALTER TABLE `exp_member_search` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_member_search` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_menu_items: ~0 rows (approximately)
DELETE FROM `exp_menu_items`;
/*!40000 ALTER TABLE `exp_menu_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_menu_items` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_menu_sets: ~0 rows (approximately)
DELETE FROM `exp_menu_sets`;
/*!40000 ALTER TABLE `exp_menu_sets` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_menu_sets` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_message_attachments: ~0 rows (approximately)
DELETE FROM `exp_message_attachments`;
/*!40000 ALTER TABLE `exp_message_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_message_attachments` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_message_copies: ~0 rows (approximately)
DELETE FROM `exp_message_copies`;
/*!40000 ALTER TABLE `exp_message_copies` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_message_copies` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_message_data: ~0 rows (approximately)
DELETE FROM `exp_message_data`;
/*!40000 ALTER TABLE `exp_message_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_message_data` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_message_folders: ~0 rows (approximately)
DELETE FROM `exp_message_folders`;
/*!40000 ALTER TABLE `exp_message_folders` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_message_folders` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_message_listed: ~0 rows (approximately)
DELETE FROM `exp_message_listed`;
/*!40000 ALTER TABLE `exp_message_listed` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_message_listed` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_modules: ~11 rows (approximately)
/*!40000 ALTER TABLE `exp_modules` DISABLE KEYS */;
INSERT INTO `exp_modules` (`module_id`, `module_name`, `module_version`, `has_cp_backend`, `has_publish_fields`) VALUES
	(17, 'Email', '2.1.0', 'n', 'n'),
	(18, 'Rss', '2.0.0', 'n', 'n');
/*!40000 ALTER TABLE `exp_modules` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_module_member_roles: ~0 rows (approximately)
DELETE FROM `exp_module_member_roles`;
/*!40000 ALTER TABLE `exp_module_member_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_module_member_roles` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_online_users: ~0 rows (approximately)
DELETE FROM `exp_online_users`;
/*!40000 ALTER TABLE `exp_online_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_online_users` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_password_lockout: ~0 rows (approximately)
DELETE FROM `exp_password_lockout`;
/*!40000 ALTER TABLE `exp_password_lockout` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_password_lockout` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_relationships: ~0 rows (approximately)
DELETE FROM `exp_relationships`;
/*!40000 ALTER TABLE `exp_relationships` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_relationships` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_relationships: ~1 rows (approximately)
/*!40000 ALTER TABLE `exp_relationships` DISABLE KEYS */;
INSERT INTO `exp_relationships` (`relationship_id`, `parent_id`, `child_id`, `field_id`, `grid_field_id`, `grid_col_id`, `grid_row_id`, `order`, `fluid_field_data_id`) VALUES
	(1, 2, 1, 8, 0, 0, 0, 1, 0);
/*!40000 ALTER TABLE `exp_relationships` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_remember_me: ~0 rows (approximately)
DELETE FROM `exp_remember_me`;
/*!40000 ALTER TABLE `exp_remember_me` DISABLE KEYS */;
INSERT INTO `exp_remember_me` (`remember_me_id`, `member_id`, `ip_address`, `user_agent`, `admin_sess`, `site_id`, `expiration`, `last_refresh`) VALUES
	('e55a8d01e4d7bc423388ea41e463f13ae03a0b86', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36', 0, 1, 1589801343, 1588591743);
/*!40000 ALTER TABLE `exp_remember_me` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_reset_password: ~0 rows (approximately)
DELETE FROM `exp_reset_password`;
/*!40000 ALTER TABLE `exp_reset_password` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_reset_password` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_revision_tracker: ~0 rows (approximately)
DELETE FROM `exp_revision_tracker`;
/*!40000 ALTER TABLE `exp_revision_tracker` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_revision_tracker` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_search: ~0 rows (approximately)
DELETE FROM `exp_search`;
/*!40000 ALTER TABLE `exp_search` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_search` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_search_log: ~0 rows (approximately)
DELETE FROM `exp_search_log`;
/*!40000 ALTER TABLE `exp_search_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_search_log` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_security_hashes: ~2 rows (approximately)
DELETE FROM `exp_security_hashes`;
/*!40000 ALTER TABLE `exp_security_hashes` DISABLE KEYS */;
INSERT INTO `exp_security_hashes` (`hash_id`, `date`, `session_id`, `hash`) VALUES
	(1, 1588591744, 'f2a904e290e6d7f11670d005dbd5020ee5cad37a', 'fa13719243186990ca7cf873bdd734b1db955877'),
	(2, 1588591844, '22f12e37632a7094d83fde9c22c3016f6b861ff6', 'eeed380ee21f26cc4a8d3fe5aeed844561b64fff');
/*!40000 ALTER TABLE `exp_security_hashes` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_sessions: ~2 rows (approximately)
DELETE FROM `exp_sessions`;
/*!40000 ALTER TABLE `exp_sessions` DISABLE KEYS */;
INSERT INTO `exp_sessions` (`session_id`, `member_id`, `admin_sess`, `ip_address`, `user_agent`, `fingerprint`, `login_state`, `sess_start`, `auth_timeout`, `last_activity`, `can_debug`) VALUES
	('22f12e37632a7094d83fde9c22c3016f6b861ff6', 1, 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36', '8620fc7bbc4339e82dbdfbed935b630b', NULL, 1588591844, 0, 1588592422, '0'),
	('f2a904e290e6d7f11670d005dbd5020ee5cad37a', 1, 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36', '8620fc7bbc4339e82dbdfbed935b630b', NULL, 1588591743, 0, 1588591785, 'n');
/*!40000 ALTER TABLE `exp_sessions` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_sites: ~0 rows (approximately)
DELETE FROM `exp_sites`;
/*!40000 ALTER TABLE `exp_sites` DISABLE KEYS */;
INSERT INTO `exp_sites` (`site_id`, `site_label`, `site_name`, `site_description`, `site_bootstrap_checksums`, `site_pages`) VALUES
	(1, 'EE6', 'default_site', NULL, 'YToxOntzOjc6ImVtYWlsZWQiO2E6MDp7fX0=', 'YToxOntpOjE7YToxOntzOjM6InVybCI7czozMjoiaHR0cDovL3ByaXZhdGU2MC50ZXN0L2luZGV4LnBocC8iO319');
/*!40000 ALTER TABLE `exp_sites` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_snippets: ~13 rows (approximately)
DELETE FROM `exp_snippets`;
/*!40000 ALTER TABLE `exp_snippets` DISABLE KEYS */;
INSERT INTO `exp_snippets` (`snippet_id`, `site_id`, `snippet_name`, `snippet_contents`, `edit_date`) VALUES
	(1, 1, '.htaccess', 'deny from all', 0),
	(2, 1, 'global_edit_this', '{if author_id == logged_in_member_id OR logged_in_group_id == "1"}&bull; <a href="{cp_url}?S={cp_session_id}&amp;D=cp&amp;C=content_publish&amp;M=entry_form&amp;channel_id={channel_id}&amp;entry_id={entry_id}">Edit This</a>{/if}', 0),
	(3, 1, 'global_featured_band', '<div id="featured_band">\n    <h2>Featured Band</h2>\n    {exp:channel:entries channel="news" limit="1" status="featured" rdf="off" disable="trackbacks" category="2" dynamic="no"}\n    <div class="image">\n        <h4><a href="{comment_url_title_auto_path}"><span>{title}</span></a></h4>\n        {if news_image}\n			<img src="{news_image}" alt="{title}"/>\n		{/if}\n    </div>\n    {news_body}\n    {/exp:channel:entries}\n</div>', 0),
	(4, 1, 'global_featured_welcome', '<div id="welcome">\n    {exp:channel:entries channel="about" url_title="about_the_label" dynamic="no"  limit="1" disable="pagination|member_date|categories|category_fields|trackbacks"}\n    {if about_image != ""}\n        <img src="{about_image}" alt="map" width="210" height="170" />\n    {/if}\n    {about_body}\n    <a href="{comment_url_title_auto_path}">Read more about us</a>\n    {/exp:channel:entries}\n</div>', 0),
	(5, 1, 'global_footer', '<div id="siteinfo">\n    <p>Copyright @ {exp:channel:entries limit="1" sort="asc" disable="custom_fields|comments|pagination|categories"}\n\n{if "{entry_date format=\'%Y\'}" != "{current_time format=\'%Y\'}"}{entry_date format="%Y"} - {/if}{/exp:channel:entries} {current_time format="%Y"}, powered by <a href="http://expressionengine.com">ExpressionEngine</a></p>\n    <p class="logo"><a href="#">Agile Records</a></p>\n	{if group_id == "1"}<p>{total_queries} queries in {elapsed_time} seconds</p>{/if}\n</div> <!-- ending #siteinfo -->', 0),
	(6, 1, 'global_strict_urls', '<!-- Strict URLS: https://docs.expressionengine.com/latest/cp/templates/global_template_preferences.html -->\n{if segment_2 != \'\'}\n  {redirect="404"}\n{/if}', 0),
	(7, 1, 'global_stylesheets', '<!-- CSS -->\n<!-- This makes use of the stylesheet= parameter, which automatically appends a time stamp to allow for the browser\'s caching mechanism to cache the stylesheet.  This allows for faster page-loads times.\nStylesheet linking is documented at https://docs.expressionengine.com/latest/templates/globals/stylesheet.html -->\n    <link href="{stylesheet=global_embeds/site_css}" type="text/css" rel="stylesheet" media="screen" />\n    <!--[if IE 6]><link href="{stylesheet=global_embeds/css_screen-ie6}" type="text/css" rel="stylesheet" media="screen" /><![endif]-->\n    <!--[if IE 7]><link href="{stylesheet=global_embeds/css_screen-ie7}" type="text/css" rel="stylesheet" media="screen" /><![endif]-->\n', 0),
	(8, 1, 'global_top_member', '<div id="member">\n\n	<!-- Utilized member conditionals: https://docs.expressionengine.com/latest/templates/globals/conditionals.html-->\n            <h4>Hello{if logged_in} {screen_name}{/if}!</h4>\n            			<ul>\n				{if logged_in}\n                <li><a href="{path=\'member/profile\'}">Your Home</a></li>\n                <li><a href="{path=LOGOUT}">Log out</a></li>\n				{/if}\n				{if logged_out}\n				<li><a href="{path=\'member/register\'}">Register</a></li>\n				<li><a href="{path=\'member/login\'}">Log in</a></li>\n				{/if}\n            </ul>\n        </div> <!-- ending #member -->', 0),
	(9, 1, 'global_top_search', '<!-- Simple Search Form: https://docs.expressionengine.com/latest/modules/search/index.html#simple \n\nThe parameters here help to identify what templates to use and where to search:\n\nResults page - result_page: https://docs.expressionengine.com/latest/modules/search/simple.html#par_result_page\n\nNo Results found: no_result_page: https://docs.expressionengine.com/latest/modules/search/simple.html#par_no_result_page\n\nsearch_in - search in titles? titles and entries? titles, entries?  https://docs.expressionengine.com/latest/modules/search/simple.html#par_search_in-->\n\n{exp:search:simple_form channel="news" result_page="search/results" no_result_page="search/no_results" search_in="everywhere"}\n<fieldset>\n    <label for="search">Search:</label>\n    <input type="text" name="keywords" id="search" value=""  />\n	<input type="image" id="submit" name="submit" class="submit" src="{site_url}themes/site/default/images/spacer.gif" />\n</fieldset>\n{/exp:search:simple_form}', 0),
	(10, 1, 'news_calendar', '<h5>Calendar</h5>\n		<div id="news_calendar">\n			\n			<!-- Channel Calendar Tag: https://docs.expressionengine.com/latest/modules/channel/calendar.html -->\n			\n			{exp:channel:calendar switch="calendarToday|calendarCell" channel="news"}\n			<table class="calendarBG" border="0" cellpadding="6" cellspacing="1" summary="My Calendar">\n			<tr class="calendarHeader">\n			<th><div class="calendarMonthLinks"><a href="{previous_path=\'news/archives\'}">&lt;&lt;</a></div></th>\n			<th colspan="5">{date format="%F %Y"}</th>\n			<th><div class="calendarMonthLinks"><a class="calendarMonthLinks" href="{next_path=\'news/archives\'}">&gt;&gt;</a></div></th>\n			</tr>\n			<tr>\n			{calendar_heading}\n			<td class="calendarDayHeading">{lang:weekday_abrev}</td>\n			{/calendar_heading}\n			</tr>\n\n			{calendar_rows }\n			{row_start}<tr>{/row_start}\n\n			{if entries}\n			<td class=\'{switch}\' align=\'center\'><a href="{day_path=\'news/archives\'}">{day_number}</a></td>\n			{/if}\n\n			{if not_entries}\n			<td class=\'{switch}\' align=\'center\'>{day_number}</td>\n			{/if}\n\n			{if blank}\n			<td class=\'calendarBlank\'>{day_number}</td>\n			{/if}\n\n			{row_end}</tr>{/row_end}\n			{/calendar_rows}\n			</table>\n			{/exp:channel:calendar}\n		</div> <!-- ending #news_calendar -->', 0),
	(11, 1, 'news_categories', '<div id="sidebar_category_archives">\n      		<h5>Categories</h5>\n  			<ul id="categories">\n  				<!-- Weblog Categories tag: https://docs.expressionengine.com/latest/modules/weblog/categories.html -->\n				\n  				{exp:channel:categories channel="news" style="linear"}\n  				<li><a href="{path=\'news/archives\'}">{category_name}</a></li>\n  				{/exp:channel:categories}\n  			</ul>\n  		</div>', 0),
	(12, 1, 'news_month_archives', '<div id="sidebar_date_archives">\n    	    <h5>Date Archives</h5>\n    		<ul id="months">\n    			{!-- Archive Month Link Tags: https://docs.expressionengine.com/latest/modules/weblog/archive_month_links.html --}\n		\n    			{exp:channel:month_links channel="news" limit="50"}\n    			<li><a href="{path=\'news/archives\'}">{month}, {year}</a></li>\n    			{/exp:channel:month_links}\n    		</ul>\n    	</div>', 0),
	(13, 1, 'news_popular', '<h5>Popular News Items</h5>\n\n<!-- Channel Entries tag ordered by track views for "popular posts".  See Tracking Entry Views at https://docs.expressionengine.com/latest/modules/weblog/entry_tracking.html -->\n\n{exp:channel:entries channel="news" limit="4" disable="categories|custom_fields|category_fields|trackbacks|pagination|member_data" dynamic="no"}\n	{if count == "1"}<ul>{/if}\n		<li><a href="{comment_url_title_auto_path}">{title}</a> </li>\n	{if count == total_results}</ul>{/if}\n{/exp:channel:entries}', 0);
/*!40000 ALTER TABLE `exp_snippets` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_stats: ~0 rows (approximately)
DELETE FROM `exp_stats`;
/*!40000 ALTER TABLE `exp_stats` DISABLE KEYS */;
INSERT INTO `exp_stats` (`stat_id`, `site_id`, `total_members`, `recent_member_id`, `recent_member`, `total_entries`, `total_forum_topics`, `total_forum_posts`, `total_comments`, `last_entry_date`, `last_forum_post_date`, `last_comment_date`, `last_visitor_date`, `most_visitors`, `most_visitor_date`, `last_cache_clear`) VALUES
	(1, 1, 1, 1, 'Admin', 1, 0, 0, 0, 1409242030, 0, 0, 0, 0, 0, 1409242030);
/*!40000 ALTER TABLE `exp_stats` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_statuses: ~0 rows (approximately)
DELETE FROM `exp_statuses`;
/*!40000 ALTER TABLE `exp_statuses` DISABLE KEYS */;
INSERT INTO `exp_statuses` (`status_id`, `status`, `status_order`, `highlight`) VALUES
	(1, 'open', 1, '009933'),
	(2, 'closed', 2, '990000'),
	(3, 'Featured', 3, '000000');
/*!40000 ALTER TABLE `exp_statuses` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_statuses_roles: ~0 rows (approximately)
DELETE FROM `exp_statuses_roles`;
/*!40000 ALTER TABLE `exp_statuses_roles` DISABLE KEYS */;
INSERT INTO `exp_statuses_roles` (`role_id`, `status_id`) VALUES
	(5, 1),
	(5, 2),
	(5, 3);
/*!40000 ALTER TABLE `exp_statuses_roles` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_templates: ~0 rows (approximately)
DELETE FROM `exp_templates`;
/*!40000 ALTER TABLE `exp_templates` DISABLE KEYS */;
INSERT INTO `exp_templates` (`template_id`, `site_id`, `group_id`, `template_name`, `template_type`, `template_data`, `template_notes`, `edit_date`, `last_author_id`, `cache`, `refresh`, `no_auth_bounce`, `enable_http_auth`, `allow_php`, `php_parse_location`, `hits`, `protect_javascript`, `enable_frontedit`) VALUES
	(1, 1, 1, 'index', 'webpage', '{!-- Explanations and learning materials can be found in news/index and the other news template groups.  In-line comments here are only for features not introduced in news/index. --}\n{html_head}\n	<title>{site_name}: Contact Us</title>\n{global_stylesheets}\n\n{rss}\n{favicon}\n{html_head_end}\n	<body>\n{nav_access}\n	{branding_begin}\n			{embed="global_embeds/_top_nav" loc="about"}\n			{global_top_search}\n			{global_top_member}\n	{branding_end}\n	{wrapper_begin}\n{embed="global_embeds/_page_header" header="About"}\n\n\n<div id="feature" class="about">\n	{exp:channel:entries channel="about" url_title="about_the_label" dynamic="no"  limit="1" disable="pagination|member_data|categories|category_fields"}\n		<h3 class="about">{title}</h3>\n		{about_body}\n	{/exp:channel:entries}\n</div> <!-- ending #feature -->\n\n	<div class="feature_end"></div>\n\n<div id="content_pri" class="about"> <!-- This is where all primary content, left column gets entered -->\n\n		<!-- Standard Channel Entries tag, but instead of relying on the URL for what to display, we request a specific entry for display via url-title:\n	https://docs.expressionengine.com/latest/modules/channel/parameters.html#par_url_title\n\n	and we force the channel entries tag to ignore the URL and always deliver the same content by using dynamic="no":\n\n	https://docs.expressionengine.com/latest/modules/channel/parameters.html#par_dynamic\n	-->\n\n		{exp:channel:entries channel="about" dynamic="no" url_title="about_the_label" limit="1" disable="pagination|member_data|categories|category_fields"}\n			{about_extended}\n		{/exp:channel:entries}\n</div>\n\n<div id="content_sec" class="staff_profiles right green40">\n		<h3 class="staff">Staff Profiles</h3>\n		{exp:channel:entries channel="about" limit="6" category="3" dynamic="off" orderby="date" sort="asc"}\n			{if count == "1"}<ul class="staff_member">{/if}\n				<li class="{switch="||end"}">\n					<h4>{title} <a href="#">i</a></h4>\n					<div class="profile">\n						{about_staff_title}\n					</div>\n					<img src="{about_image}" alt="{title}" />\n				</li>\n			{if count == total_results}</ul>{/if}\n		{/exp:channel:entries}\n\n</div>	<!-- ending #content_sec -->\n\n\n\n{global_footer}\n{wrapper_close}\n{js}\n{html_close}\n', NULL, 1409242030, 1, 'n', 0, '', 'n', 'n', 'o', 0, 'n', 'y'),
	(2, 1, 1, '404', 'webpage', '{!-- Explanations and learning materials can be found in news/index and the other news template groups.  In-line comments here are only for features not introduced in news/index. --}\n{html_head}\n	<title>{site_name}: Not Found</title>\n{global_stylesheets}\n\n{rss}\n{favicon}\n{html_head_end}\n	<body>\n{nav_access}\n	{branding_begin}\n			{embed="global_embeds/_top_nav" loc="contact"}\n			{global_top_search}\n			{global_top_member}\n	{branding_end}\n	{wrapper_begin}\n{embed="global_embeds/_page_header" header="Not Found"}\n\n\n	<div id="content_pri"> <!-- This is where all primary content, left column gets entered -->\n		<h4>Not Found</h4>\n				 <p>The page you attempted to load was Not Found.  Please try again.</p>\n	</div>\n\n\n		<div id="content_sec" class="right green40">\n			<h3 class="oldernews">Browse Older News</h3>\n			<div id="news_archives">\n				<div id="categories_box">\n				{news_categories}\n				</div>\n				<div id="month_box">\n				{news_month_archives}\n				</div>\n			</div> <!-- ending #news_archives -->\n\n			{news_calendar}\n\n			{news_popular}\n\n		{rss_links}\n\n		</div>	<!-- ending #content_sec -->\n\n	{global_footer}\n	{wrapper_close}\n	{js}\n	{html_close}\n', NULL, 1409242030, 1, 'n', 0, '', 'n', 'n', 'o', 0, 'n', 'y'),
	(3, 1, 1, 'contact', 'webpage', '{!-- Explanations and learning materials can be found in news/index and the other news template groups.  In-line comments here are only for features not introduced in news/index. --}\n{html_head}\n	<title>{site_name}: Contact Us</title>\n{global_stylesheets}\n\n{rss}\n{favicon}\n{html_head_end}\n	<body>\n{nav_access}\n	{branding_begin}\n			{embed="global_embeds/_top_nav" loc="contact"}\n			{global_top_search}\n			{global_top_member}\n	{branding_end}\n	{wrapper_begin}\n{embed="global_embeds/_page_header" header="Contact Us"}\n    <div id="feature" class="contact">\n		<h3 class="getintouch">Get in Touch</h3>\n\n\n<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat .</p>\n</div> <!-- ending #feature -->\n\n	<div class="feature_end"></div>\n\n	<div id="content_pri" class="contact"> <!-- This is where all primary content, left column gets entered -->\n\n			<!-- This uses the Email Module\'s Contact Form: https://docs.expressionengine.com/latest/modules/email/contact_form.html -->\n			{exp:email:contact_form user_recipients="false" recipients="admin@example.com" charset="utf-8"}\n			<fieldset id="contact_fields">\n			<label for="from">\n				<span>Your Email:</span>\n				<input type="text" id="from" name="from" value="{member_email}" />\n			</label>\n\n			<label for="subject">\n				<span>Subject:</span>\n				<input type="text" id="subject" name="subject" size="40" value="Contact Form" />\n			</label>\n\n			<label for="message">\n				<span>Message:</span>\n				<textarea id="message" name="message" rows="18" cols="40">Email from: {member_name}, Sent at: {current_time format="%Y %m %d"}</textarea>\n			</label>\n			</fieldset>\n\n			<fieldset id="contact_action">\n				<p>We will never pass on your details to third parties.</p>\n				<input name="submit" type=\'submit\' value=\'Submit\' id=\'contactSubmit\' />\n			</fieldset>\n			{/exp:email:contact_form}\n	</div>\n\n	<div id="content_sec" class="contact">\n		<h3 class="address">Address</h3>\n		 <p>\n			12343 Valencia Street,<br />\n			Mission District,<br />\n			San Francisco,<br />\n			California,<br />\n			ZIP 123\n			 </p>\n	<p><img src="{site_url}images/about/map2.jpg" alt="" /></p>\n\n	</div>	<!-- ending #content_sec -->\n\n{global_footer}\n{wrapper_close}\n{js}\n{html_close}\n', NULL, 1409242030, 1, 'n', 0, '', 'n', 'n', 'o', 0, 'n', 'y'),
	(4, 1, 2, 'index', 'webpage', '', NULL, 1409242030, 1, 'n', 0, '', 'n', 'n', 'o', 0, 'n', 'y'),
	(5, 1, 2, '_page_header', 'webpage', '<div id="page_header">\n        <h2>{embed:header}</h2>\n    </div>\n', NULL, 1409242030, 1, 'n', 0, '', 'n', 'n', 'o', 0, 'n', 'y'),
	(6, 1, 2, '_top_nav', 'webpage', ' <ul id="navigation_pri">\n            <li id="home" {if embed:loc== "home"}class="cur"{/if}><a href="{homepage}">Home</a></li>\n            <li id="events" {if embed:loc == "about"}class="cur"{/if}><a href="{path=\'about/index\'}">About</a></li>\n            <li id="contact" {if embed:loc=="contact"}class="cur"{/if}><a href="{path=\'about/contact\'}">Contact</a></li>\n        </ul>', NULL, 1409242030, 1, 'n', 0, '', 'n', 'n', 'o', 0, 'n', 'y'),
	(7, 1, 2, 'css_screen-ie6', 'css', '/*\n\n	AGILE RECORDS, EE2.0 EXAMPLE SITE by ERSKINE DESIGN\n	VERSION 1.0\n	IE6 OVERRIDE STYLES\n	\n	CONTENTS ----------\n	\n	\n	\n	-------------------\n	\n*/\n\n\n\nul#nav_access { position:static; display:none; }\n\ndiv#feature { margin-bottom:10px !important; }\nhr.legend_start,\nhr.feature_end { display:none !important; }\n\n/* TABLES */\n\ntable { background-image:none; background-color:#ddd; font-size:12px; }\ntr.alt { background-image:none; background-color:#eee; }\nth { background-image:none; background-color:#ddd; }\n\n\n\n/* LAYOUT */\n\ndiv#feature { width:950px; overflow:hidden; float:none; padding:30px 0; position:static; margin:0; background:url({site_url}themes/site/default/images/feature_bg.jpg); margin-bottom:30px; }\ndiv#page_header { background-image:none; background-color:#6b5f57; height:40px; z-index:3; position:static; top:0px; margin-bottom:0; padding-bottom:10px; }\n\ndiv#branding { height:290px; background:url({site_url}themes/site/default/images/ie_branding_bg.gif) repeat-x center top; position:relative; z-index:2; }\ndiv#branding_sub { width:930px; margin:0 auto; position:relative; }\n\ndiv#page { background:url({site_url}themes/site/default/images/page_bg.jpg); }\n\ndiv#content_pri { display:inline; }\ndiv#content_sec { }\n\n\n\n/* BRANDING/MASTHEAD */\n\ndiv#branding_logo { background:url({site_url}themes/site/default/images/ie_branding_sub_bg.gif) no-repeat left top; }\ndiv#branding_sub h1 a { position:static; background:url({site_url}themes/site/default/images/logo_bg.jpg) no-repeat bottom left; }\ndiv#branding_sub div#member { background:none; }\ndiv#branding_sub form { background:url({site_url}themes/site/default/images/ie_search_bg.jpg) no-repeat; }\n\n\n\n\n/* NAVIGATION */\n\nul#navigation_pri { background-image:none; background-color:#2f261d; }\nul#navigation_pri li { height:auto; text-indent:0; font-family:"Cooper Black",Arial; font-weight:bold; }\nul#navigation_pri li a:link,\nul#navigation_pri li a:visited { background:none; text-decoration:none; color:#a09f9d;}\nul#navigation_pri li a:hover,\nul#navigation_pri li a:focus { color:#ccc;}\nul#navigation_pri li.cur a:link,\nul#navigation_pri li.cur a:visited,\nul#navigation_pri li.cur a:hover,\nul#navigation_pri li.cur a:focus { color:#d55401; }\nul#navigation_pri li#home,\nul#navigation_pri li#events,\nul#navigation_pri li#contact { top:8px; }\nul#navigation_pri li#bands,\nul#navigation_pri li#news,\nul#navigation_pri li#forums { top:30px; }\nul#navigation_pri li#releases,\nul#navigation_pri li#about,\nul#navigation_pri li#wiki { top:54px; }\n\n\n\n/* HEADINGS */\ndiv#page_header { height:1px; z-index:99; position:static; top:0; margin-bottom:0; }\ndiv#page_header h2 { text-indent:0 !important; background:none !important; color:#e6e6e6 !important; padding-top:15px !important; float:left;}\ndiv#page_header ol#breadcrumbs { margin-top:10px; padding:0; background:none; }\ndiv#page_header ol#breadcrumbs li { margin-left:10px; }\n\nh2,h3 { text-indent:0 !important; background:none !important; width:auto !important; height:auto !important; }\n\n\n\n/* HOMEPAGE */\n\n.home div#feature div#featured_band { width:450px; float:left; position:static; margin:0px; }\n.home div#feature div#featured_band h2 { margin-bottom:5px; width:auto; height:auto; text-indent:0; background:none; }\n\n.home div#content_sec { display:inline; margin:0 30px 0 10px; }\n\n.home div#feature div#featured_band div.image { width:300px; height:200px; left:0; bottom:-10px; margin:0 10px 0 10px; padding:0; display:inline; }\n.home div#feature div#featured_band div.image h4 { height:auto; width:auto; background:none; margin:0; top:auto; bottom:0; }\n.home div#feature div#featured_band div.image h4 span { position:static; background:none; }\n.home div#feature div#featured_band div.image img { top:0; left:0; }\n\n.home div#homepage_events ul { padding-bottom:30px; }\n.home div#homepage_events ul li a { background:none !important; text-indent:0 !important; text-align:center; color:#fff; font-weight:bold; }\n\n.home div#homepage_forums ul,\n.home div#homepage_rss p,\n.home div#homepage_rss ul { background-image:none; background-color:#eee; }\n\n\n\n/* BANDS */\n\n.bands ul#bands1 li.one { width:450px; height:300px; left:-480px; top:0; margin-right:-450px; margin-bottom:30px; }\n.bands ul#bands1 li.one img { top:0; left:0; }\n \n.bands ul#bands1 li.two img,\n.bands ul#bands1 li.three img { padding:0; background:none; position:static; margin:0; margin:0 10px; }\n\n.band div#band_image { width:450px; height:300px; float:left; position:relative; left:10px; top:0px; margin:0 30px 30px 0; display:inline; }\n.band div#band_image img { top:0; left:0; }\n\ndiv#band_latestrelease { padding:20px; overflow:hidden; color:#d6d6d6; margin-left:10px; }\ndiv#band_latestrelease h3 { padding-top:20px; }\n\n.band div#content_pri { display:inline; }\n\n.band div#band_events ul { padding-bottom:30px; }\n.band div#band_events ul li a { background:none !important; text-indent:0 !important; text-align:center; color:#fff; font-weight:bold; }\n\n.band div#band_more ul { background-image:none; background-color:#eee; }\n\n\n\n/* RELEASES */\n\n.releases div#content_pri table th { background:none; text-indent:0; color:#fff; }\n.releases div#content_pri table th.release_details { width:360px; padding-right:30px; background:none; }\n.releases div#content_pri table th.release_catno { width:80px; background:none; }\n.releases div#content_pri table th.release_format { width:120px; background:none; text-align:center; }\n\n.releases div#content_pri table tr { background-image:none; background-color:#a3a39c; }\n.releases div#content_pri table tr.releases_head { background:none; }\n.releases div#content_pri table tr.alt { background-image:none; background-color:#c1c1bc; }\n\n.release div#content_pri { display:inline; padding-top:30px;}\n.release div#content_sec { padding:0; padding-top:30px; background:none; position:relative; left:-10px; }\n\n.release div#release_details { border-bottom:1px solid blue; }\n.release div#release_details span { font-family:Georgia,serif; font-style:italic; }\n.release div#release_details ul { list-style:url({site_url}themes/site/default/images/pixel.gif); }\n\n.release div#release_tracks div.release_format { float:left; padding-bottom:20px; margin-bottom:20px; }\n\n\n\n/* EVENTS */\n\n.events div#content_pri { display:inline; }\n\n\n\n/* NEWS */\n\n.news div#content_pri { display:inline; padding:30px 0; }\n.news div#content_sec { margin:30px 0 ; }\n\n.news div#news_calendar h6 a.prev { position:static; }\n.news div#news_calendar h6 a.next { position:static; }\n\n.news div#news_calendar { background-image:none; background-color:#cfcfcb; }\n.news div#news_calendar table td.post { background-image:none; background-color:#d7d7d3; }\n\n.news div#news_rss { background-image:none; background-color:#cfcfcb; }\n\ndiv#news_comments ol li { background-image:none; background-color:#f1f1f1; }\ndiv#news_comments ol li.alt { background-image:none; background-color:#e7e7e7; }\n\ndiv#news_comments fieldset#comment_fields label { display:block; width:320px; }\ndiv#news_comments fieldset#comment_fields label.comment { width:530px; }\ndiv#news_comments fieldset#comment_fields label span { width:80px; float:none; position:relative; top:20px; }\ndiv#news_comments fieldset#comment_fields label input { float:right; }\ndiv#news_comments fieldset#comment_fields label textarea { float:right; }\n\ndiv#news_comments fieldset#comment_fields label input,\ndiv#news_comments fieldset#comment_fields label textarea { background-image:none; background-color:#f1f1f1; }\n\n\n\n/* FORUMS */\n\n.forums div#content_pri { display:inline; }\n.forums div#content_sec { background-image:none; background-color:#f1f1f1; }\n\n.forums #page_header form { position:absolute; left:770px; padding-top:5px; }\n.forums #page_header form input.search { padding:1px; margin-right:10px;}\n.forums #page_header form input.submit { padding:0; position:relative; top:5px; }\n\n.forums div#content_pri h3 { background-color:#71715f !important; }\n\ndiv.forum_posts { background-image:none; background-color:#9e9e94; }\ndiv.forum_posts table tr td { background-image:none; background-color:#d0d0cc;}\ndiv.forum_posts table tr.alt td { background-image:none; background-color:#b3b3ab; }\n\ndiv.forum_posts table tr th { text-indent:0; color:#fff; }\ndiv.forum_posts table tr th.forum_name,\ndiv.forum_posts table tr th.forum_topics,\ndiv.forum_posts table tr th.forum_replies,\ndiv.forum_posts table tr th.forum_latest { background-image:none; }\n\ndiv.forum_posts table td.forum_newpostindicator img { position:static; }\n\n.forums div#legend div#forum_stats ul.legend { float:left; background-image:none; background-color:#cecec8; }\n.forums div#legend div#forum_stats p.most_visitors { background-image:none; background-color:#ecd2c3; }\n\n\n\n/* WIKI */\n\n.wiki div#navigation_sec { padding-top:57px; display:inline;  behavior: url(css/iepngfix/iepngfix.htc); }\n.wiki div#navigation_sec ul { background:url({site_url}themes/site/default/images/ie_wiki_menubg.jpg) repeat-y 5px top ; }\n.wiki div#navigation_sec div.bottom { behavior: url(css/iepngfix/iepngfix.htc); }\n\n\n\n/* MEMBERS CONTROL PANEL */\n\n.member_cp div#navigation_sec { display:inline; background-image:none; background-color:#9c9b92; }\n.member_cp div#navigation_sec h4 a.expand { display:none; }\n\n.member_cp div#content_pri table tr { background-image:none; background-color:#f1f1f1; }\n.member_cp div#content_pri table tr.alt { background-image:none; background-color:#e7e7e7; }\n.member_cp div#content_pri table tr th { background-image:none; background-color:#f1f1f1; }\n.member_cp div#content_pri table tr.alt th { background-image:none; background-color:#e7e7e7; }\n\n\n\n/* MEMBER PROFILE */\n\n.member_profile div#feature div#memberprofile_main { background-image:none; background-color:#cecec8; margin:20px 0 0 10px; }\n.member_profile div#feature div#memberprofile_main ul { padding:0 0 10px 0; }\n\n.member_profile div#feature div#memberprofile_photo { float:left; width:210px; height:180px; background:none; position:relative; left:-20px; }\n.member_profile div#feature div#memberprofile_photo img { width:206px; height:176px; border:3px solid #6b5f57; position:static; }\n\n.member_profile div#feature div#memberprofile_communicate { background-image:none; background-color:#adada3; margin-top:5px; } \n.member_profile div#feature div#memberprofile_communicate table tr { background-image:none; background-color:#cdcdc7; }\n.member_profile div#feature div#memberprofile_communicate table tr.alt { background-image:none; background-color:#bebeb7; }\n\n.member_profile div#content_pri table tr,\n.member_profile div#content_sec table tr { background-image:none; background-color:#f1f1f1; }\n.member_profile div#content_pri table tr.alt,\n.member_profile div#content_sec table tr.alt { background-image:none; background-color:#e7e7e7; }\n\n.member_profile div#content_pri table tr th,\n.member_profile div#content_sec table tr th { background-image:none; background-color:#f1f1f1; }\n.member_profile div#content_pri table tr.alt th,\n.member_profile div#content_sec table tr.alt th { background-image:none; background-color:#e7e7e7; }\n\n\n', NULL, 1409242030, 1, 'n', 0, '', 'n', 'n', 'o', 0, 'n', 'y'),
	(8, 1, 2, 'css_screen-ie7', 'css', 'body {position:relative;}\ndiv#branding {margin:0 auto;}\n\n\ndiv#content_wrapper {position:relative;}\n\ndiv.feature_end {margin-top:0; }\ndiv#content_pri {float:left;margin:0 30px 0 10px;width:600px; padding-left:10px;}\ndiv#content_sec {float:left;width:270px; position:relative; z-index:999;}\n\ndiv#content_pri.contact {width:520px; margin-right:110px;}\ndiv#content_sec.contact {float:right; margin: 0 10px -140px auto; }\n\n\ndiv#page_header {position:relative;z-index:1;}\n\ndiv#feature{top:-10px;float:none;margin-bottom:30px;padding-top:25px;padding-top:10px;position:relative;width:950px;z-index:900;display:block;}\n\ndiv.feature_end {clear:none;height:35px;margin-bottom:20px;margin-top:-40px;width:950px;}\n\n/*#content_wrapper.member_cp {padding:0 10px;} */\n#content_wrapper.member_cp table {width:550px;}\n\ndiv#navigation_sec.member_cp { \n	width:150px; \n	left:10px;\n}\n\ndiv#content_wrapper.member_cp form {margin-left:200px;}', NULL, 1409242030, 1, 'n', 0, '', 'n', 'n', 'o', 0, 'n', 'y'),
	(9, 1, 2, 'site_css', 'css', 'html,body,div,span,applet,object,iframe,h1,h2,h3,h4,h5,h6,p,blockquote,pre,a,abbr,acronym,address,big,cite,code,del,dfn,em,font,img,ins,kbd,q,s,samp,small,strike,strong,sub,sup,tt,var,dl,dt,dd,ol,ul,li,fieldset,form,label,legend,table,caption,tbody,tfoot,thead,tr,th,td,hr{margin:0;padding:0;border:0;outline:0;font-weight:inherit;font-style:inherit;font-size:100%;font-family:inherit;vertical-align:baseline;}:focus{outline:0;}body{line-height:1;color:black;background:white;}ol,ul{list-style:none;}table{border-collapse:collapse;border-spacing:0;}caption,th,td{text-align:left;font-weight:normal;}blockquote:before,blockquote:after,q:before,q:after{content:"";}blockquote,q{quotes:"""";}@font-face{font-family:\'miso\';src:url(\'{site_url}themes/site/default/fonts/miso-bold.ttf\');}body{background:#ccc url({site_url}themes/site/default/images/body_bg.jpg) top center;font-size:13px;font-family:Arial,sans-serif;}ul#nav_access{position:absolute;top:-9999px;left:-9999px;}p,ul,dl,ol{margin-bottom:22px;line-height:22px;}ul{list-style:url({site_url}themes/site/default/images/bullet.jpg);}ul li{margin-left:12px;}ol{list-style:decimal;list-style-position:inside;}hr{height:0;border-top:1px solid #ccc;margin-bottom:22px;}abbr{border-bottom:1px dotted;}strong{font-weight:bold;}em{font-style:italic;}h1,h2,h3,h4,h5{font-weight:bold;}h2{color:#48482d;font-size:16px;margin-bottom:10px;}h3{margin-bottom:20px;}h4{margin-bottom:10px;}h5{margin-bottom:10px;}h6{text-transform:uppercase;font-size:11px;color:#666;letter-spacing:1px;margin-bottom:10px;}a:link,a:visited{color:#333;text-decoration:underline;}a:hover,a:focus{color:#111;}h2 a:link,h2 a:visited,h3 a:link,h3 a:visited,h4 a:link,h4 a:visited{text-decoration:none;}\n\n/* Tables */\n/* site_url explanation: https://docs.expressionengine.com/latest/templates/globals/single_variables.html#var_site_url */\n/* only site_url will be parsed, other variables will not be parsed unless you call the stylesheet using path= instead of stylesheet=:\n\nhttps://docs.expressionengine.com/latest/templates/globals/stylesheet.html */\n\ntable{background:url({site_url}themes/site/default/images/white_40.png);font-size:12px;}\ntr{border-bottom:1px dotted #999;}\ntr.alt{background:url({site_url}themes/site/default/images/white_20.png);}\nth,td{padding:10px;}\nth{background:url({site_url}themes/site/default/images/white_20.png);color:#666;font-weight:bold;font-size:13px;}\n.member_table{width:60%; margin:10px;}\n.member_console{width:100%;}\n\n/* Page Styles */\ndiv#branding{height:290px;background:url({site_url}themes/site/default/images/branding_bg.png) repeat-x center top;position:relative;z-index:2;}\ndiv#branding_sub{width:930px;margin:0 auto;position:relative;}\ndiv#page{width:950px;padding-top:50px;margin:0 auto;position:relative;top:0px;margin-top:-80px;z-index:1;background:url({site_url}themes/site/default/images/white_40.png);}\ndiv#content_wrapper{padding-top:30px;}\ndiv#feature{width:950px;background:url({site_url}themes/site/default/images/white_70.png);float:left;padding-top:30px;position:relative;bottom:30px;margin-bottom:-30px;}\n\ndiv.feature_end {background:transparent url({site_url}themes/site/default/images/agile_sprite.png) no-repeat scroll left -747px; border:none;outline:none;clear:both;height:35px;margin-top:-6px;margin-bottom:20px;width:950px;}\n\ndiv#legend{width:950px;background:url({site_url}themes/site/default/images/white_70.png);overflow:hidden;position:relative;top:30px;margin-top:-30px;padding:10px 0 30px 0;font-size:11px;}\nhr.legend_start{width:950px;clear:both;background:url({site_url}themes/site/default/images/white_70_top.png) no-repeat top left;height:35px;margin:0;margin-top:20px;border:none;}\ndiv#content_pri{width:610px;float:left;margin:0 30px 0 10px;}\ndiv#content_sec{width:270px;float:left;}\n\ninput.input { border:1px solid #aaa; position:relative; left:5px; background:url({site_url}themes/site/default/images/white_50.png);}\ninput.input:focus { background:url({site_url}themes/site/default/images/white_70.png); }\ntextarea { border:1px solid #aaa; background:url({site_url}themes/site/default/images/white_50.png); }\ntextarea:focus { background:url({site_url}themes/site/default/images/white_70.png); }\n\n\n\n\n/* Branding */\ndiv#branding_logo{background:url({site_url}themes/site/default/images/agile_sprite.png) no-repeat 9px -428px;margin:0 auto;position:relative;left:-80px;margin-bottom:-230px;height:230px;width:950px;}\ndiv#branding_logo img{display:none;}\ndiv#branding_sub h1 a {width:182px;height:196px;display:block;text-indent:-9999em;background:url({site_url}themes/site/default/images/agile_sprite.png) no-repeat -264px 15px;  padding-top:15px;}\ndiv#branding_sub form{position:absolute; right:130px;top:25px;width:240px;height:51px;background:url({site_url}themes/site/default/images/agile_sprite.png) no-repeat -534px -21px;}\ndiv#branding_sub form fieldset{position:relative;}\ndiv#branding_sub form label{text-indent:-9999em;margin-top:10px;width:60px;padding:5px;position:absolute;left:0px;display:inline;}\ndiv#branding_sub form input#search{background:none;border:none;position:absolute;top:13px;left:70px;width:100px;padding:2px 5px;font-size:11px;color:#fff;}\n\ndiv#branding_sub form input#submit{position:absolute;right:30px;top:6px; background:transparent url({site_url}themes/site/default/images/agile_sprite.png) no-repeat -587px -77px; width:24px; height:24px; display:block; font-size:1px; border:none; outline:none;}\n\ndiv#branding_sub div#member{position:absolute;right:0;top:20px;background:url({site_url}themes/site/default/images/brown_40.png);border:1px solid #846f65;color:#ccc;font-size:11px;padding:8px;}\ndiv#branding_sub div#member ul{margin:0;line-height:13px;list-style:disc;}\ndiv#branding_sub div#member h4{margin-bottom:4px;}\ndiv#branding_sub div#member a:link, div#branding_sub div#member a:visited{color:#ccc;}\ndiv#branding_sub div#member a:hover, div#branding_sub div#member a:focus{color:#fff;}\n\n/* Navigation */\nul#navigation_pri{list-style:none;margin:0 auto;padding:5px 15px;width:340px;max-height:100px;background:#2f261d;position:absolute;right:0;bottom:20px;}\nul#navigation_pri li{margin:0;float:left;font-size:16px;width:33%;}\nul#navigation_pri li a{font-family:\'Cooper Black\',miso,\'Georgia\',serif;font-weight:bold;color:#999999;text-decoration:none}\nul#navigation_pri li a:hover{color:#efefef;}\nul#navigation_pri li.cur a{color:#f47424}\n\n/* Footer */\ndiv#siteinfo{background:url({site_url}themes/site/default/images/agile_sprite.png) no-repeat left -287px;height:80px;padding-top:40px;position:relative;clear:both;font-size:12px;z-index:3;}\ndiv#siteinfo p{color:#5b5b42;font-weight:bold;margin:0 0 0 10px;}\ndiv#siteinfo p.logo{width:65px;height:70px;background:url({site_url}themes/site/default/images/agile_sprite.png); text-indent:-9999em;position:absolute;left:865px;bottom:15px;}\ndiv#siteinfo a {color:#5b5b42;text-decoration:underline;}\ndiv#siteinfo a:hover {color:#3B3A25;text-decoration:underline;}\ndiv#siteinfo p.logo a{display:block;}\n\n\n/* 11.PAGEHEADERS\n---------------------------------------------------------------------- */\n\ndiv#page_header { background:url({site_url}themes/site/default/images/agile_sprite.png) no-repeat left -205px; height:72px; z-index:3; position:relative; top:-25px; margin-bottom:-15px; }\n\ndiv#page_header h2 { float:left; font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; font-weight: normal; text-transform:uppercase; color:#ebebeb; letter-spacing: -0.01em; }\ndiv#page_header h2 a { display:block; }\n\ndiv#page_header h2 { margin:0; width:400px; height:15px; padding-top:30px; margin-left:10px;}\n\ndiv#page_header ol#breadcrumbs { float:left; list-style:none; margin:0; margin-left:10px; margin-top:26px; padding:0px 0 0 20px; background:url({site_url}themes/site/default/images/breadcrumbs_bg.png) no-repeat left center; }\ndiv#page_header ol#breadcrumbs li { margin:0; float:left; font-weight:bold; color:#d6d6d6; text-transform:uppercase; font-size:12px; }\ndiv#page_header ol#breadcrumbs li a { color:#d6d6d6; text-decoration:none; }\n\n\n/*  Featured Band / Welcome\n-------------------------------- */\ndiv#featured_band {width:450px; float:left; position:relative; z-index:5; bottom:52px; margin-bottom:-52px;}\ndiv#welcome {width:450px; float:left; margin:0 30px 0 10px;}\ndiv#welcome img {float:left; margin:0 30px 10px 0;}\ndiv#featured_band h2 {margin-bottom:38px; width:135px; height:14px; font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; font-weight: normal; text-transform:uppercase; color:#ebebeb; letter-spacing: -0.01em;}\n\ndiv#featured_band div.image { float:right; width:323px; height:243px; position:relative; left:50px; bottom:75px; margin: 0 0 -75px -50px; }\ndiv#featured_band div.image h4 { width:324px; height:243px; background:url({site_url}themes/site/default/images/featuredband_border.png) no-repeat top left; position:absolute; top:0; left:0; z-index:2; }\ndiv#featured_band div.image h4 span { position:absolute; top:177px; left:30px; background:url({site_url}themes/site/default/images/white_70.png); font-size:11px; padding:2px; padding-left:60px; }\ndiv#featured_band div.image img { position:absolute; top:20px; left:15px;}\n.green40 {background:transparent url({site_url}themes/site/default/images/green_40.png) repeat scroll 0 0; color:#EEEEEE; float:left; padding:10px;}\ndiv#feature p {margin-left:10px;}\n\n/* News\n---------------- */\nh3.oldernews {}\nul#news_listing { list-style:none; }\nul#news_listing li { margin:0 0 30px 0; overflow:hidden; }\nul#news_listing li img { float:left; margin:0 10px 10px 0;}\nul#news_listing li p { margin-bottom:10px; }\n\ndiv#news_archives { overflow:hidden; }\ndiv#news_archives div#categories_box {width:120px; float: left;}\ndiv#news_archives div#months_box {width:120px; float: right;}\ndiv#news_archives ul#categories { width:120px; float:left; margin-right:30px; }\ndiv#news_archives ul#months { width:120px; float:left; }\n\ndiv#news_calendar { padding:10px; background:url({site_url}themes/site/default/images/white_50.png); margin-bottom:40px; }\n\ndiv#news_calendar a:link,\ndiv#news_calendar a:visited { color:#666; }\ndiv#news_calendar a:hover,\ndiv#news_calendar a:focus { color:#333; }\n\ndiv#news_calendar h6 { position:relative; text-align:center; text-transform:uppercase; color:#666; padding:0 0 10px 0; }\ndiv#news_calendar h6 a.prev { position:absolute; left:0; top:-3px; font-size:16px; }\ndiv#news_calendar h6 a.next { position:absolute; right:0; top:-3px; font-size:16px; }\n\ndiv#news_calendar table { background:none; font-size:11px; width:250px; color:#666; }\ndiv#news_calendar table th { background:url({site_url}themes/site/default/images/green_50.png); color:#ccc; }\ndiv#news_calendar table th,\ndiv#news_calendar table td  { padding:5px 0; text-align:center; }\ndiv#news_calendar table tr { border:none; }\ndiv#news_calendar table td.unused { color:#999; }\ndiv#news_calendar table td.post { background:url({site_url}themes/site/default/images/white_20.png); }\ndiv#news_calendar table td.post:hover { background:url({site_url}themes/site/default/images/white_40.png); }\n\ndiv#news_rss { padding:10px; background:url({site_url}themes/site/default/images/white_50.png); color:#666; }\ndiv#news_rss ul { list-style:url({site_url}themes/site/default/images/bullet.jpg); margin:0; }\ndiv#news_rss a:link,\ndiv#news_rss a:visited { color:#666; }\ndiv#news_rss a:hover,\ndiv#news_rss a:focus { color:#333; }\n\n\n/* Staff Profiles */\ndiv#content_sec.staff_profiles {\nbackground:transparent url({site_url}themes/site/default/images/staff_bg.jpg) repeat scroll 0 0;float:right;margin-bottom:-110px;padding:10px;position:relative;top:-140px; right:10px; width:430px;}\n\n/* Comments */\ndiv#news_comments { border-top:#bfbebf 1px solid; padding-top:20px; }\n\ndiv#news_comments ol { list-style:none; border-top:1px dotted #ccc; margin-bottom:30px; }\ndiv#news_comments ol li { border-bottom:1px dotted #ccc; background:url({site_url}themes/site/default/images/white_70.png); padding:20px 10px 0 160px; font-size:12px; line-height:20px; }\ndiv#news_comments ol li.alt { background:url({site_url}themes/site/default/images/white_50.png); }\n\ndiv#news_comments ol li h5.commentdata { width:120px; float:left; position:relative; left:-150px; margin-right:-150px; font-size:13px; line-height:20px; }\ndiv#news_comments ol li h5.commentdata span { display:block; font-weight:normal; font-size:11px; }\ndiv#news_comments ol li h5.commentdata img { margin-top:10px; }\n\ndiv#news_comments h3.leavecomment {color:#47472C; font-family:\'Cooper Black\', miso, \'Georgia\', serif; font-size:20px;}\ndiv#news_comments form { position:relative; margin-bottom:30px; }\n\ndiv#news_comments fieldset#comment_fields label { display:block; overflow:hidden; font-size:12px; margin-bottom:20px; }\ndiv#news_comments fieldset#comment_fields label span { width:80px; float:left; position:relative; top:5px; }\ndiv#news_comments fieldset#comment_fields label input { border:1px solid #aaa; width:228px; float:left; background:url({site_url}themes/site/default/images/white_50.png); }\ndiv#news_comments fieldset#comment_fields label input:focus { background:url({site_url}themes/site/default/images/white_70.png); }\ndiv#news_comments fieldset#comment_fields label textarea { border:1px solid #aaa; float:left; height:150px; width:438px; background:url({site_url}themes/site/default/images/white_50.png); }\ndiv#news_comments fieldset#comment_fields label textarea:focus { background:url({site_url}themes/site/default/images/white_70.png); }\n\ndiv#news_comments div#comment_guidelines { width:418px; padding:10px; margin:10px 0 10px 80px; color:#fff; background:#9f9995; }\ndiv#news_comments div#comment_guidelines h6 { font-weight:normal; font-size:12px; margin-bottom:0; }\ndiv#news_comments div#comment_guidelines p { margin:10px 0 0 0 ; font-size:11px; line-height:16px; font-style:italic; }\n\ndiv#news_comments fieldset#comment_action { background:url({site_url}themes/site/default/images/orange_20.png); padding:10px; font-size:11px; position:relative; }\ndiv#news_comments fieldset#comment_action label { display:block; padding:5px 0; }\ndiv#news_comments fieldset#comment_action label input { position:relative; left:5px; }\ndiv#news_comments fieldset#comment_action input#submit_comment { position:absolute; bottom:10px; right:10px; font-size:12px; }\n\ndiv#captcha_box img {margin-left: 5px;}\n\ninput#captcha {display:block; margin: 5px 0 0 0; border:1px solid #aaa; width:228px; background:url({site_url}themes/site/default/images/white_50.png);}\ninput#captcha:focus {background:url({site_url}themes/site/default/images/white_70.png);}\n\n/* News Archive Page */\ndiv.archive ul#news_listing li img {float:right; margin:auto auto 10px 10px;}\ndiv.archive ul#news_listing li p {margin-bottom:10px; padding-left:0;}\n\n/* About */\ndiv#content_pri.about {width:450px;}\ndiv#feature.about p {color:#666666;font-weight:bold;margin-left:10px;width:450px;}\ndiv#feature h3.about {font-size:22px; font-family:\'Cooper Black\',miso,\'Georgia\',serif;font-weight:bold;color:#47472C;text-decoration:none; margin:10px 0 20px 10px; width:300px;}\n\n\ndiv#content_sec ul.staff_member li {float:left;height:180px;margin:0 35px 40px 0;overflow:hidden;position:relative;width:120px;}\n\ndiv#content_sec ul.staff_member { list-style:none; overflow:hidden; margin-bottom:-20px; }\ndiv#content_sec ul.staff_member li { width:120px; height:180px; overflow:hidden; position:relative; float:left; margin:0 35px 40px 0; }\ndiv#content_sec ul.staff_member li.end { margin-right:0; }\ndiv#content_sec ul.staff_member li h4 { font-size:12px; padding:5px 5px; background:#afafa8; position:absolute; bottom:0; left:0; z-index:3; color:#fff; width:110px; height:20px; cursor:pointer; }\ndiv#content_sec ul.staff_member li h4 a { position:absolute; right:5px; color:#eee; font-family:Georgia, "Times New Roman", Times, serif; font-style:italic; font-weight:bold; }\ndiv#content_sec ul.staff_member li div.profile { position:absolute; bottom:40px; left:0; background:url({site_url}themes/site/default/images/white_50.png); z-index:2; padding:5px; width:110px; }\ndiv#content_sec ul.staff_member li img { position:absolute; top:0; left:0; }\ndiv.profile {color:#000;}\n\n\n/* Contact */\ndiv#content_pri.contact { width:530px; margin-right:110px; }\ndiv#content_sec.contact {  width:270px; float:left; padding:10px; padding-bottom:0; background:url({site_url}themes/site/default/images/staff_bg.jpg); position:relative; top:-170px; margin-bottom:-140px; color:#eee; }\ndiv#feature.contact p {color:#666666;font-weight:bold;margin-left:10px;width:600px;}\n\n/*div#feature { padding-left:10px; padding-right:410px; width:530px; }*/\ndiv#feature h3.getintouch { width:140px; font-family:\'Cooper Black\',miso,\'Georgia\',serif;font-size:20px; color:#47472C;text-decoration:none; margin-left:10px;}\n\ndiv#content_pri form { position:relative; margin-bottom:30px; }\n\ndiv#content_pri fieldset#contact_fields label { display:block; overflow:hidden; font-size:12px; margin-bottom:20px; }\ndiv#content_pri fieldset#contact_fields label span { width:80px; float:left; position:relative; top:5px; }\ndiv#content_pri fieldset#contact_fields label input { border:1px solid #aaa; width:228px; float:left; background:url({site_url}themes/site/default/images/white_50.png); }\ndiv#content_pri fieldset#contact_fields label input:focus { background:url({site_url}themes/site/default/images/white_70.png); }\ndiv#content_pri fieldset#contact_fields label textarea { border:1px solid #aaa; float:left; height:150px; width:438px; background:url({site_url}themes/site/default/images/white_50.png); }\ndiv#content_pri fieldset#contact_fields label textarea:focus { background:url({site_url}themes/site/default/images/white_70.png); }\n\ndiv#content_pri div#contact_guidelines { position:absolute; top:0; right:0; width:170px; padding:10px; color:#fff; background:#9f9995; }\ndiv#content_pri div#contact_guidelines h6 { font-weight:normal; font-size:12px; margin-bottom:10px; }\ndiv#content_pri div#contact_guidelines p { margin:0; font-size:11px; line-height:16px; font-style:italic; }\n\ndiv#content_pri fieldset#contact_action { background:url({site_url}themes/site/default/images/orange_20.png); padding:10px; font-size:11px; position:relative; }\ndiv#content_pri fieldset#contact_action label { display:block; padding:5px 0; }\ndiv#content_pri fieldset#contact_action label input { position:relative; left:5px; }\ndiv#content_pri fieldset#contact_action input#contactSubmit { position:absolute; bottom:10px; right:10px; font-size:12px; }\n\n\n\n\n/*  Member Templates */\n/* 22.MEMBERS\n---------------------------------------------------------------------- */\n\n/* CONTROL PANEL */\ndiv#navigation_sec.member_cp { width:270px; padding:10px; float:left; background:url({site_url}themes/site/default/images/green_40.png); margin:35px 30px 30px 10px; font-size:11px; line-height:16px; }\n/*div#content_pri.member_cp  { width:610px; margin:0 0 0 10px; }*/\n\ndiv#page_header.member_cp  a.viewprofile { display:block; width:182px; height:22px; background:url({site_url}themes/site/default/images/member_viewprofile.jpg) no-repeat left top; text-indent:-9999em; position:absolute; right:10px; top:25px; }\n.member_cp div#page_header a.viewprofile:hover,\n.member_cp div#page_header a.viewprofile:focus { background-position:left bottom; }\n\ndiv#navigation_sec.member_cp h4 { color:#fff; border-bottom:1px solid #b1b1a9; font-size:12px; padding-bottom:5px; position:relative; }\ndiv#navigation_sec.member_cp h4 a.expand { position:absolute; right:0; top:0; display:block; height:14px; width:14px; background:url({site_url}themes/site/default/images/controlpanel_expand.jpg) no-repeat bottom left; text-indent:-9999em; }\ndiv#navigation_sec.member_cp h4 a.expand.open { background:url({site_url}themes/site/default/images/controlpanel_expand.jpg) no-repeat top left; }\ndiv#navigation_sec.member_cp a:link,\ndiv#navigation_sec.member_cp a:visited { color:#ddd; }\ndiv#navigation_sec.member_cp a:hover,\ndiv#navigation_sec.member_cp a:focus { color:#fff; }\n\ndiv#content_pri table { width:610px; background:none;}\ndiv#content_pri table th { background:none; }\ndiv#content_pri table tr { background:url({site_url}themes/site/default/images/white_60.png); }\ndiv#content_pri table tr.alt { background:url({site_url}themes/site/default/images/white_40.png); }\n\n/* PROFILE */\ndiv#content_pri.member_profile, div#content_pri.member_cp  { width:450px; float:left; margin:0 30px 30px 10px; }\ndiv#content_sec.member_profile, div#content_sec.member_cp  { width:450px; float:left; margin:0 0 30px 0; }\n\nh3.statistics {height:11px; font-family:\'Cooper Black\',miso,\'Georgia\',serif; color:#f47424; font-size:18px; }\nh3.personalinfo {height:11px; color:#47472C; font-family:\'Cooper Black\',miso,\'Georgia\',serif; font-size:18px;}\nh3.biography {height:11px; color:#47472C; font-family:\'Cooper Black\',miso,\'Georgia\',serif; font-size:18px; margin-top:20px;}\n\ndiv#memberprofile_main { background:url({site_url}themes/site/default/images/green_20.png); width:300px; padding:10px; margin:40px 0 0 10px; float:left; }\ndiv#memberprofile_main img { float:left; margin:0 10px 10px 0; }\ndiv#memberprofile_main h3 { margin:5px 0 10px 0; }\ndiv#memberprofile_main ul { clear:both; margin:0; padding:10px 0; font-size:12px; }\ndiv#memberprofile_main ul a { color:#666; }\n\ndiv#memberprofile_photo { float:left; width:250px; height:220px; background:url({site_url}themes/site/default/images/memberprofile_photo_bg.png) no-repeat center center; position:relative; left:-20px; }\ndiv#memberprofile_photo img { width:206px; height:176px; border:3px solid #6b5f57; position:absolute; top:20px; left:20px; }\n\ndiv#memberprofile_communicate { width:270px; padding:10px; margin:20px 10px 0 0; float:right; background:url({site_url}themes/site/default/images/green_40.png); }\ndiv#memberprofile_communicate h3.communicate { width:83px; height:12px; font-family:"Helvetica Neue",Helvetica,Arial,sans-serif; color:#EBEBEB; text-transform: uppercase; margin-bottom:10px; }\ndiv#memberprofile_communicate table { width:270px; font-size:10px; background:none; }\ndiv#memberprofile_communicate table tr { background:url({site_url}themes/site/default/images/white_40.png); }\ndiv#memberprofile_communicate table tr.alt { background:url({site_url}themes/site/default/images/white_20.png); }\ndiv#memberprofile_communicate table th { font-weight:normal; font-size:10px; background:none; padding:4px; }\ndiv#feature div#memberprofile_communicate table td { padding:4px; color:#444;}\n\ndiv#content_pri.member_cp table,\ndiv#content_sec.member_cp table { width:100%; background:none; margin-bottom:30px; }\ndiv#content_pri.member_cp table th,\ndiv#content_sec.member_cp table th { background:none; }\ndiv#content_pri.member_cp table tr,\ndiv#content_sec.member_cp table tr { background:url({site_url}themes/site/default/images/white_60.png); }\ndiv#content_pri.member_cp table tr.alt,\ndiv#content_sec.member_cp table tr.alt  { background:url({site_url}themes/site/default/images/white_40.png); }\n\n/* Private Messages: Move and Copy pop-up menu control */\n#movemenu {position: absolute !important; top: 410px !important; left: 390px !important; border: 0 !important;}\n#copymenu {position: absolute !important; top: 410px !important; left: 332px !important; border: 0 !important;}\n\n/* Search Results */\n.pagination ul { overflow: auto; }\n.pagination li { float: left; list-style: none; background: transparent url(http://expressionengine2/themes/site/default/images/green_40.png) repeat scroll 0 0; padding: 1px 7px; margin: 0 3px; }\n.pagination li.active { background: none; }\n.pagination li.active a { text-decoration: none; }', NULL, 1409242030, 1, 'n', 0, '', 'n', 'n', 'o', 0, 'n', 'y'),
	(10, 1, 3, 'index', 'webpage', '{if segment_2 != \'\'}\n  {redirect="404"}\n{/if}\n{html_head}\n	<title>{site_name}</title>\n{global_stylesheets}\n{rss}\n{favicon}\n{html_head_end}\n	<body>\n	{nav_access}\n	{branding_begin}\n			{embed="global_embeds/_top_nav" loc="home"}\n			{global_top_search}\n			{global_top_member}\n	{branding_end}\n	{wrapper_begin}\n{embed="global_embeds/_page_header" header="News"}\n	<div id="feature" class="news">\n			{global_featured_welcome}\n			{global_featured_band}\n	    </div> <!-- ending #feature -->\n\n        	<div class="feature_end"></div>\n\n	<div id="content_pri"> <!-- This is where all primary content, left column gets entered -->\n		<!--  This is the channel entries tag.  Documentation for this parameter can be found at https://docs.expressionengine.com/latest/modules/channel/channel_entries.html\n				 Parameter Explanation:\n		channel= which channel to output, multiple channels may be piped in (channel_1|channel_2)\n		limit= limits the number of entries output in this instance of the tag\n		disable= turns off parsing of un-needed data -->\n\n		{exp:channel:entries channel="news" limit="3" disable="categories|member_data|category_fields|pagination"}\n\n		<!-- if no_results is a conditional variable, it can not be combined with advanced conditionals.  https://docs.expressionengine.com/latest/modules/channel/conditional_variables.html#cond_if_no_results -->\n\n		{if no_results}<p>Sample No Results Information</p>{/if}\n		{if count == "1"}\n		<h3 class="recentnews">Recent News</h3>\n		<ul id="news_listing">\n		{/if}\n			<li>\n				<h4><a href="{comment_url_title_auto_path}">{title}</a>  //  <!-- entry_date is a variable, and date formatting variables can be found at https://docs.expressionengine.com/latest/templates/date_variable_formatting.html -->{entry_date format="%F %d %Y"}</h4>\n\n				<!-- the following two lines are custom channel fields. https://docs.expressionengine.com/latest/cp/admin/channel_administration/custom_channel_fields.html -->\n\n				{if news_image}\n					<img src="{news_image}" alt="{title}" />\n				{/if}\n				{news_body}\n				<p><a href="{comment_url_title_auto_path}#news_comments">{comment_total} comments</a> {global_edit_this}\n								{if news_extended != ""}  |  <a href="{comment_url_title_auto_path}">Read more</a>{/if}</p>\n\n			</li>\n		{if count == total_results}</ul>{/if}\n		{/exp:channel:entries}\n\n\n\n\n	</div>\n\n	<div id="content_sec" class="right green40">\n		<h3 class="oldernews">Browse Older News</h3>\n		<div id="news_archives">\n			<div id="categories_box">\n			{news_categories}\n			</div>\n			<div id="month_box">\n			{news_month_archives}\n			</div>\n		</div> <!-- ending #news_archives -->\n\n		{news_calendar}\n\n		{news_popular}\n\n	{rss_links}\n\n	</div>	<!-- ending #content_sec -->\n\n{global_footer}\n{wrapper_close}\n{js}\n{html_close}\n', NULL, 1409242030, 1, 'n', 0, '', 'n', 'n', 'o', 0, 'n', 'y'),
	(11, 1, 3, 'archives', 'webpage', '{html_head}\n	<title>{site_name}: News Archives</title>\n{global_stylesheets}\n{rss}\n{favicon}\n{html_head_end}\n	<body>\n	{nav_access}\n	{branding_begin}\n			{embed="global_embeds/_top_nav" loc="home"}\n			{global_top_search}\n			{global_top_member}\n	{branding_end}\n	{wrapper_begin}\n{embed="global_embeds/_page_header" header="News"}\n	<div id="feature">\n			{global_featured_welcome}\n			{global_featured_band}\n	    </div> <!-- ending #feature -->\n\n        	<div class="feature_end"></div>\n\n	<div id="content_pri" class="archive"> <!-- This is where all primary content, left column gets entered -->\n\n			<!--  This is the channel entries tag.  Documentation for this tag can be found at https://docs.expressionengine.com/latest/modules/weblog/parameters.html\n\n			channel= which channel to output, multiple channels may be piped in (channel_1|channel_2)\n			limit= limits the number of entries output in this instance of the tag\n			disable= turns off parsing of un-needed data\n			relaxed_categories= allows you use the category indicator in your URLs with an entries tag specifying multiple weblogs that do not share category groups.\n\n			-->\n\n		{exp:channel:entries channel="news" limit="3" disable="member_data|category_fields|pagination" status="open|featured" relaxed_categories="yes"}\n\n		<!-- if no_results is a conditional variable, it can not be combined with advanced conditionals.  https://docs.expressionengine.com/latest/modules/channel/conditional_variables.html#cond_if_no_results -->\n\n		{if no_results}<p>No Results</p>{/if}\n		{if count == "1"}\n		<h3 class="recentnews">Recent News</h3>\n		<ul id="news_listing">\n		{/if}\n			<li>\n				<h4><a href="{comment_url_title_auto_path}">{title}</a>  //  {!-- entry_date is a variable, and date formatting variables can be found at https://docs.expressionengine.com/latest/templates/date_variable_formatting.html --}{entry_date format="%F %d %Y"}</h4>\n\n				<!-- the following two lines are custom channel fields. https://docs.expressionengine.com/latest/cp/admin/channel_administration/custom_channel_fields.html -->\n\n				{if news_image}\n					<img src="{news_image}" alt="{title}" />\n				{/if}\n				{news_body}\n				<p><a href="{comment_url_title_auto_path}#news_comments">{comment_total} comments</a> {global_edit_this}\n								{if news_extended != ""}  |  <a href="{comment_url_title_auto_path}">Read more</a>{/if}</p>\n\n			</li>\n		{if count == total_results}</ul>{/if}\n		{/exp:channel:entries}\n\n\n\n\n	</div>\n\n	<div id="content_sec" class="right green40">\n		<h3 class="oldernews">Browse Older News</h3>\n		<div id="news_archives">\n			<div id="categories_box">\n			{news_categories}\n			</div>\n			<div id="month_box">\n			{news_month_archives}\n			</div>\n		</div> <!-- ending #news_archives -->\n\n		{news_calendar}\n\n		{news_popular}\n\n	{rss_links}\n\n	</div>	<!-- ending #content_sec -->\n\n{global_footer}\n{wrapper_close}\n{js}\n{html_close}\n', NULL, 1409242030, 1, 'n', 0, '', 'n', 'n', 'o', 0, 'n', 'y'),
	(12, 1, 3, 'atom', 'feed', '{exp:rss:feed channel="news"}\n\n<?xml version="1.0" encoding="{encoding}"?>\n<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="{channel_language}">\n\n	<title type="text">{exp:xml_encode}{channel_name}{/exp:xml_encode}</title>\n	<subtitle type="text">{exp:xml_encode}{channel_name}:{channel_description}{/exp:xml_encode}</subtitle>\n	<link rel="alternate" type="text/html" href="{channel_url}" />\n	<link rel="self" type="application/atom+xml" href="{path={atom_feed_location}}" />\n	<updated>{gmt_edit_date format=\'%Y-%m-%dT%H:%i:%sZ\'}</updated>\n	<rights>Copyright (c) {gmt_date format="%Y"}, {author}</rights>\n	<generator uri="https://expressionengine.com/" version="{version}">ExpressionEngine</generator>\n	<id>tag:{trimmed_url},{gmt_date format="%Y:%m:%d"}</id>\n\n{exp:channel:entries channel="news" limit="15" dynamic_start="on" disable="member_data"}\n	<entry>\n	  <title>{exp:xml_encode}{title}{/exp:xml_encode}</title>\n	  <link rel="alternate" type="text/html" href="{comment_url_title_auto_path}" />\n	  <id>tag:{trimmed_url},{gmt_entry_date format="%Y"}:{relative_url}/{channel_id}.{entry_id}</id>\n	  <published>{gmt_entry_date format="%Y-%m-%dT%H:%i:%sZ"}</published>\n	  <updated>{gmt_edit_date format=\'%Y-%m-%dT%H:%i:%sZ\'}</updated>\n	  <author>\n			<name>{author}</name>\n			<email>{email}</email>\n			{if url}<uri>{url}</uri>{/if}\n	  </author>\n{categories}\n	  <category term="{exp:xml_encode}{category_name}{/exp:xml_encode}"\n		scheme="{path=news/index}"\n		label="{exp:xml_encode}{category_name}{/exp:xml_encode}" />{/categories}\n	  <content type="html"><![CDATA[\n		{news_body} {news_extended}\n	  ]]></content>\n	</entry>\n{/exp:channel:entries}\n\n</feed>\n\n{/exp:rss:feed}						', NULL, 1409242030, 1, 'n', 0, '', 'n', 'n', 'o', 0, 'n', 'y'),
	(13, 1, 3, 'comment_preview', 'webpage', '{!-- Explanations and learning materials can be found in news/index and the other news template groups.  In-line comments here are only for features not introduced in news/index.\n	 NOTE:  This is an ExpressionEngine Comment and it will not appear in the rendered source.\n			https://docs.expressionengine.com/latest/templates/commenting.html\n--}\n{html_head}\n<!-- Below we use a channel entries tag to deliver a dynamic title element. -->\n	<title>{site_name}: Comment Preview for\n		{exp:channel:entries channel="news|about" limit="1" disable="categories|member_data|category_fields|pagination"}{title}{/exp:channel:entries}</title>\n{global_stylesheets}\n{rss}\n{favicon}\n{html_head_end}\n	<body>\n{nav_access}\n	{branding_begin}\n			{embed="global_embeds/_top_nav" loc="home"}\n			{global_top_search}\n			{global_top_member}\n	{branding_end}\n	{wrapper_begin}\n{embed="global_embeds/_page_header" header="News"}\n	<div id="feature">\n		{global_featured_welcome}\n		{global_featured_band}\n	    </div> <!-- ending #feature -->\n\n        	<div class="feature_end"></div>\n\n	<div id="content_pri"> <!-- This is where all primary content, left column gets entered -->\n		<!--  This is the channel entries tag.  Documentation for this parameter can be found at https://docs.expressionengine.com/latest/modules/channel/channel_entries.html\n				 Parameters are the items inside the opening exp:channel:entries tag that allow limiting, filtering, and sorting. They go in the format item="limiter".  ie: channel="news". Below are links to the parameters used in this particular instance of the channel entries tag.  These are documented here:\n\n				https://docs.expressionengine.com/latest/channels/weblog/parameters.html\n\n		channel= which channel to output, multiple channels may be piped in (channel_1|channel_2)\n		limit= limits the number of entries output in this instance of the tag\n		disable= turns off parsing of un-needed data\n		require_entry= forces ExpressionEngine to compare Segment 3 to existing URL titles.  If there is no match, then nothing is output.  Use this in combination with if no_results to force a redirect to 404. -->\n\n		{exp:channel:entries channel="news|about" disable="categories|member_data|category_fields|pagination" status="open|featured"}\n		<!-- count is a single variable: https://docs.expressionengine.com/latest/modules/weblog/variables.html#var_count\n\n		In this case we\'ve combined the count single variable with a Conditional Global Variable:\n\n		https://docs.expressionengine.com/latest/templates/globals/conditionals.html\n\n		to create code that shows up only once, at the top of the list of outputted channel entries and only if there is 1 or more entries -->\n\n		{if count == "1"}\n		<h3 class="recentnews">Recent News</h3>\n		<ul id="news_listing">\n\n			<!-- Here we close the conditional after all of the conditional data is processed. -->\n\n		{/if}\n			<li>\n					<!-- comment_url_title_auto_path is a channel entries variable:\n\n					https://docs.expressionengine.com/latest/modules/channel/variables.html#var_comment_url_title_auto_path\n\n					This allows you to outpt a per-channel link to a single-entry page.  This can be used even if you are not using comments as a way to get a per-channel "permalink" page without writing your own conditional. -->\n\n				<h4><a href="{comment_url_title_auto_path}">{title}</a>  //  <!-- entry_date is a variable, and date formatting variables can be found at https://docs.expressionengine.com/latest/templates/date_variable_formatting.html -->{entry_date format="%F %d %Y"}</h4>\n\n				<!-- the following two lines are custom channel fields. https://docs.expressionengine.com/latest/cp/admin/channel_administration/custom_channel_fields.html -->\n\n				{if news_image}\n					<img src="{news_image}" alt="{title}" />\n				{/if}\n\n				<!-- Here we come a custom field variable with a global conditional to output the HTML only if he custom field is _not_ blank -->\n\n				{if about_image != ""}<img src="{about_image}" alt="{title}"  />{/if}\n				{news_body}\n				{about_body}\n				{news_extended}\n\n				<!-- Here we compare the channel short-name to a predefined word to output some information only if the entry occurs in a particular channel -->\n				{if channel_short_name == "news"}<p><a href="{comment_url_title_auto_path}#news_comments">{comment_total} comments</a> <!-- edit_this is a Snippet: https://docs.expressionengine.com/latest/templates/globals/snippets.html --> {global_edit_this} </p> {/if}\n			</li>\n		<!-- Comparing two channel entries variables to output data only at the end of the list of outputted channel entries -->\n		{if count == total_results}</ul>{/if}\n		<!-- Closing the Channel Entries tag -->\n		{/exp:channel:entries}\n\n			<div id="news_comments">\n			<!-- Comment Entries Tag outputs comments: https://docs.expressionengine.com/latest/ https://docs.expressionengine.com/latest/\n			Parameters found here: https://docs.expressionengine.com/latest/modules/comment/entries.html#parameters\n			sort= defines in what order to sort the comments\n			limit= how many comments to output\n			channel= what channels to show comments from\n			-->\n			{exp:comment:preview channel="news|about"}\n			<h3>Comments</h3>\n			<ol>\n				<li>\n					<h5 class="commentdata">\n						<!-- Comment Entries variable: https://docs.expressionengine.com/latest/modules/comment/entries.html#url_as_author\n						url_as_author outputs the URL if entered/in the member profile (if registered) or just the name if no URL-->\n						{url_as_author}\n						<!-- Comment date:\n						 https://docs.expressionengine.com/latest/modules/comment/entries.html#var_comment_date\n\n						Formatted with Date Variable Formatting:\n\n	https://docs.expressionengine.com/latest//templates/date_variable_formatting.html -->\n\n						<span>{comment_date format="%h:%i%a"}, {comment_date format=" %m/%d/%Y"}</span>\n						<!-- Checks if the member has chosen an avatar and displays it if so\n\n	https://docs.expressionengine.com/latest/modules/comment/entries.html#conditionals\n						-->\n						{if avatar}\n							<img src="{avatar_url}" width="{avatar_image_width}" height="{avatar_image_height}" alt="{author}\'s avatar" />\n						{/if}\n					</h5>\n					{comment}\n\n                    <div style="clear: both;"></div>\n				</li>\n			</ol>\n			{/exp:comment:preview}\n\n			<!-- Comment Submission Form:\n\n			https://docs.expressionengine.com/latest/ modules/comment/entries.html#submission_form\n\n			channel= parameter says which channel to submit this comment too.  This is very important to include if you use multiple channels that may have the same URL title.  It will stop the comment from being attached to the wrong entry.  channel= should always be included.\n			-->\n\n\n			{exp:comment:form channel="news"}\n			<h3 class="leavecomment">Leave a comment</h3>\n			<fieldset id="comment_fields">\n			<!-- Show inputs only if the member is logged out.  If logged in, this information is pulled from the member\'s account details -->\n			{if logged_out}\n				<label for="name">\n					<span>Name:</span>\n					<input type="text" id="name" name="name" value="{name}" size="50" />\n				</label>\n				<label for="email">\n					<span>Email:</span>\n					<input type="text" id="email" name="email" value="{email}" size="50" />\n				</label>\n				<label for="location">\n					<span>Location:</span>\n					 <input type="text" id="location" name="location" value="{location}" size="50" />\n				</label>\n				<label for="url">\n					<span>URL:</span>\n					<input type="text" id="url" name="url" value="{url}" size="50" />\n				</label>\n			{/if}\n				<!-- comment_guidelines is a User Defined Global Variable: https://docs.expressionengine.com/latest/templates/globals/user_defined.html -->\n				{comment_guidelines}\n				<label for="comment" class="comment">\n					<span>Comment:</span>\n					<textarea id="comment" name="comment" rows="10" cols="70">{comment}</textarea>\n				</label>\n			</fieldset>\n\n				<fieldset id="comment_action">\n				{if logged_out}\n				<label for="save_info">Remember my personal info? <input type="checkbox" name="save_info" value="yes" {save_info} /> </label>\n				{/if}\n				<label for="notify_me">Notify me of follow-up comments? <input type="checkbox" id="notify_me" name="notify_me" value="yes" {notify_me} /></label>\n\n				<!-- Insert CAPTCHA.  Will show for those that are not exempt from needing the CAPTCHA as set in the member group preferences\n\n				-->\n				{if captcha}\n				<div id="captcha_box">\n					<span>{captcha}</span>\n				</div>\n					<label for="captcha">Please enter the word you see in the image above:\n<input type="text" id="captcha" name="captcha" value="{captcha_word}" maxlength="20" />\n					</label>\n				{/if}\n				<input type="submit" name="preview" value="Preview Comment" />\n				<input type="submit" name="submit" value="Submit" id="submit_comment" />\n			</fieldset>\n			{/exp:comment:form}\n\n	</div> <!-- ending #news_comments -->\n	</div> <!-- ending #content_pri -->\n\n	<div id="content_sec" class="right green40">\n		<h3 class="oldernews">Browse Older News</h3>\n		<div id="news_archives">\n			<!-- The period before the template in this embed indicates a "hidden template".  Hidden templates can not be viewed directly but can only be viewed when embedded in another template: https://docs.expressionengine.com/latest/templates/hidden_templates.html -->\n			<div id="categories_box">\n			{news_categories}\n			</div>\n			<div id="month_box">\n			{news_month_archives}\n			</div>\n		</div> <!-- ending #news_archives -->\n\n		{news_calendar}\n\n		{news_popular}\n\n	{rss_links}\n\n	</div>	<!-- ending #content_sec -->\n\n{global_footer}\n{wrapper_close}\n{js}\n{html_close}\n', NULL, 1409242030, 1, 'n', 0, '', 'n', 'n', 'o', 0, 'n', 'y'),
	(14, 1, 3, 'comments', 'webpage', '{!-- Explanations and learning materials can be found in news/index and the other news template groups.  In-line comments here are only for features not introduced in news/index.\n	 NOTE:  This is an ExpressionEngine Comment and it will not appear in the rendered source.\n			https://docs.expressionengine.com/latest/templates/commenting.html\n--}\n{html_head}\n<!-- Below we use a channel entries tag to deliver a dynamic title element. -->\n	<title>{site_name}: Comments  on\n		{exp:channel:entries channel="news|about" limit="1" disable="categories|member_data|category_fields|pagination"}{title}{/exp:channel:entries}</title>\n{global_stylesheets}\n{rss}\n{favicon}\n{html_head_end}\n	<body>\n{nav_access}\n	{branding_begin}\n			{embed="global_embeds/_top_nav" loc="home"}\n			{global_top_search}\n			{global_top_member}\n	{branding_end}\n	{wrapper_begin}\n{embed="global_embeds/_page_header" header="News"}\n	<div id="feature">\n			{global_featured_welcome}\n			{global_featured_band}\n	    </div> <!-- ending #feature -->\n\n        	<div class="feature_end"></div>\n\n	<div id="content_pri"> <!-- This is where all primary content, left column gets entered -->\n		<!--  This is the channel entries tag.  Documentation for this parameter can be found at https://docs.expressionengine.com/latest/modules/channel/channel_entries.html\n				 Parameters are the items inside the opening exp:channel:entries tag that allow limiting, filtering, and sorting. They go in the format item="limiter".  ie: channel="news". Below are links to the parameters used in this particular instance of the channel entries tag.  These are documented here:\n\n				https://docs.expressionengine.com/latest/channels/weblog/parameters.html\n\n		channel= which channel to output, multiple channels may be piped in (channel_1|channel_2)\n		limit= limits the number of entries output in this instance of the tag\n		disable= turns off parsing of un-needed data\n		require_entry= forces ExpressionEngine to compare Segment 3 to existing URL titles.  If there is no match, then nothing is output.  Use this in combination with if no_results to force a redirect to 404. -->\n\n		{exp:channel:entries channel="news|about" limit="3" disable="categories|member_data|category_fields|pagination" require_entry="yes" status="open|featured"}\n\n		<!-- if no_results is a conditional variable, it can not be combined with advanced conditionals.  https://docs.expressionengine.com/latest/modules/channel/conditional_variables.html#cond_if_no_results\n\n		This is used here in combination with the require_entry parameter to ensure correct delivery of information or redirect to a 404 -->\n\n		{if no_results}{redirect="404"}{/if}\n		<!-- count is a single variable: https://docs.expressionengine.com/latest/modules/weblog/variables.html#var_count\n\n		In this case we\'ve combined the count single variable with a Conditional Global Variable:\n\n		https://docs.expressionengine.com/latest/templates/globals/conditionals.html\n\n		to create code that shows up only once, at the top of the list of outputted channel entries and only if there is 1 or more entries -->\n\n		{if count == "1"}\n		<h3 class="recentnews">Recent News</h3>\n		<ul id="news_listing">\n\n			<!-- Here we close the conditional after all of the conditional data is processed. -->\n\n		{/if}\n			<li>\n					<!-- comment_url_title_auto_path is a channel entries variable:\n\n					https://docs.expressionengine.com/latest/modules/channel/variables.html#var_comment_url_title_auto_path\n\n					This allows you to outpt a per-channel link to a single-entry page.  This can be used even if you are not using comments as a way to get a per-channel "permalink" page without writing your own conditional. -->\n\n				<h4><a href="{comment_url_title_auto_path}">{title}</a>  //  <!-- entry_date is a variable, and date formatting variables can be found at https://docs.expressionengine.com/latest/templates/date_variable_formatting.html -->{entry_date format="%F %d %Y"}</h4>\n\n				<!-- the following two lines are custom channel fields. https://docs.expressionengine.com/latest/cp/admin/channel_administration/custom_channel_fields.html -->\n\n				{if news_image}\n					<img src="{news_image}" alt="{title}" />\n				{/if}\n\n				<!-- Here we come a custom field variable with a global conditional to output the HTML only if he custom field is _not_ blank -->\n\n				{if about_image != ""}<img src="{about_image}" alt="{title}"  />{/if}\n				{news_body}\n				{about_body}\n				{news_extended}\n\n				<!-- Here we compare the channel short-name to a predefined word to output some information only if the entry occurs in a particular channel -->\n				{if channel_short_name == "news"}<p><a href="{comment_url_title_auto_path}#news_comments">{comment_total} comments</a> <!-- edit_this is a Snippet: https://docs.expressionengine.com/latest/templates/globals/snippets.html --> {global_edit_this} </p> {/if}\n			</li>\n		<!-- Comparing two channel entries variables to output data only at the end of the list of outputted channel entries -->\n		{if count == total_results}</ul>{/if}\n		<!-- Closing the Channel Entries tag -->\n		{/exp:channel:entries}\n\n			<div id="news_comments">\n			<!-- Comment Entries Tag outputs comments: https://docs.expressionengine.com/latest/ https://docs.expressionengine.com/latest/\n			Parameters found here: https://docs.expressionengine.com/latest/modules/comment/entries.html#parameters\n			sort= defines in what order to sort the comments\n			limit= how many comments to output\n			channel= what channels to show comments from\n			-->\n			{exp:comment:entries sort="asc" limit="20" channel="news"}\n			{if count == "1"}\n			<h3>Comments</h3>\n			<ol>{/if}\n				<li>\n					<h5 class="commentdata">\n						<!-- Comment Entries variable: https://docs.expressionengine.com/latest/modules/comment/entries.html#url_as_author\n						url_as_author outputs the URL if entered/in the member profile (if registered) or just the name if no URL-->\n						{url_as_author}\n						<!-- Comment date:\n						 https://docs.expressionengine.com/latest/modules/comment/entries.html#var_comment_date\n\n						Formatted with Date Variable Formatting:\n\n	https://docs.expressionengine.com/latest//templates/date_variable_formatting.html -->\n\n						<span>{comment_date format="%h:%i%a"}, {comment_date format=" %m/%d/%Y"}</span>\n						<!-- Checks if the member has chosen an avatar and displays it if so\n\n	https://docs.expressionengine.com/latest/modules/comment/entries.html#conditionals\n						-->\n						{if avatar}\n							<img src="{avatar_url}" width="{avatar_image_width}" height="{avatar_image_height}" alt="{author}\'s avatar" />\n						{/if}\n					</h5>\n					{comment}\n\n                    <div style="clear: both;"></div>\n				</li>\n			{if count == total_results}</ol>{/if}\n			{/exp:comment:entries}\n\n			<!-- Comment Submission Form:\n\n			https://docs.expressionengine.com/latest/ modules/comment/entries.html#submission_form\n\n			channel= parameter says which channel to submit this comment too.  This is very important to include if you use multiple channels that may have the same URL title.  It will stop the comment from being attached to the wrong entry.  channel= should always be included.\n\n			-->\n\n			{exp:comment:form channel="news" preview="news/comment_preview"}\n			<h3 class="leavecomment">Leave a comment</h3>\n			<fieldset id="comment_fields">\n			<!-- Show inputs only if the member is logged out.  If logged in, this information is pulled from the member\'s account details -->\n			{if logged_out}\n				<label for="name">\n					<span>Name:</span>\n					<input type="text" id="name" name="name" value="{name}" size="50" />\n				</label>\n				<label for="email">\n					<span>Email:</span>\n					<input type="text" id="email" name="email" value="{email}" size="50" />\n				</label>\n				<label for="location">\n					<span>Location:</span>\n					 <input type="text" id="location" name="location" value="{location}" size="50" />\n				</label>\n				<label for="url">\n					<span>URL:</span>\n					<input type="text" id="url" name="url" value="{url}" size="50" />\n				</label>\n			{/if}\n				<!-- comment_guidelines is a User Defined Global Variable: https://docs.expressionengine.com/latest/templates/globals/user_defined.html -->\n				{comment_guidelines}\n				<label for="comment" class="comment">\n					<span>Comment:</span>\n					<textarea id="comment" name="comment" rows="10" cols="70">{comment}</textarea>\n				</label>\n			</fieldset>\n\n				<fieldset id="comment_action">\n				{if logged_out}\n				<label for="save_info">Remember my personal info? <input type="checkbox" name="save_info" value="yes" {save_info} /> </label>\n				{/if}\n				<label for="notify_me">Notify me of follow-up comments? <input type="checkbox" id="notify_me" name="notify_me" value="yes" {notify_me} /></label>\n\n				<!-- Insert CAPTCHA.  Will show for those that are not exempt from needing the CAPTCHA as set in the member group preferences\n\n				-->\n				{if captcha}\n				<div id="captcha_box">\n					<span>{captcha}</span>\n				</div>\n					<label for="captcha">Please enter the word you see in the image above:\n<input type="text" id="captcha" name="captcha" value="{captcha_word}" maxlength="20" />\n					</label>\n				{/if}\n				<input type="submit" name="preview" value="Preview Comment" />\n				<input type="submit" name="submit" value="Submit" id="submit_comment" />\n			</fieldset>\n			{/exp:comment:form}\n\n	</div> <!-- ending #news_comments -->\n	</div> <!-- ending #content_pri -->\n\n	<div id="content_sec" class="right green40">\n		<h3 class="oldernews">Browse Older News</h3>\n		<div id="news_archives">\n			<!-- The period before the template in this embed indicates a "hidden template".  Hidden templates can not be viewed directly but can only be viewed when embedded in another template: https://docs.expressionengine.com/latest/templates/hidden_templates.html -->\n			<div id="categories_box">\n			{news_categories}\n			</div>\n			<div id="month_box">\n			{news_month_archives}\n			</div>\n		</div> <!-- ending #news_archives -->\n\n		{news_calendar}\n\n		{news_popular}\n\n	{rss_links}\n\n	</div>	<!-- ending #content_sec -->\n\n{global_footer}\n{wrapper_close}\n{js}\n{html_close}\n', NULL, 1409242030, 1, 'n', 0, '', 'n', 'n', 'o', 0, 'n', 'y'),
	(15, 1, 3, 'rss', 'feed', '{exp:rss:feed channel="news"}\n\n<?xml version="1.0" encoding="{encoding}"?>\n<rss version="2.0"\n	xmlns:dc="http://purl.org/dc/elements/1.1/"\n	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"\n	xmlns:admin="http://webns.net/mvcb/"\n	xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"\n	xmlns:content="http://purl.org/rss/1.0/modules/content/">\n\n	<channel>\n	\n	<title>{exp:xml_encode}{channel_name}{/exp:xml_encode}</title>\n	<link>{channel_url}</link>\n	<description>{channel_description}</description>\n	<dc:language>{channel_language}</dc:language>\n	<dc:creator>{email}</dc:creator>\n	<dc:rights>Copyright {gmt_date format="%Y"}</dc:rights>\n	<dc:date>{gmt_date format="%Y-%m-%dT%H:%i:%s%Q"}</dc:date>\n	<admin:generatorAgent rdf:resource="https://expressionengine.com/" />\n	\n{exp:channel:entries channel="news" limit="10" dynamic_start="on" disable="member_data"}\n	<item>\n	  <title>{exp:xml_encode}{title}{/exp:xml_encode}</title>\n	  <link>{comment_url_title_auto_path}</link>\n	  <guid>{comment_url_title_auto_path}#When:{gmt_entry_date format="%H:%i:%sZ"}</guid>\n	  <description><![CDATA[{news_body}]]></description> \n	  <dc:subject>{exp:xml_encode}{categories backspace="1"}{category_name}, {/categories}{/exp:xml_encode}</dc:subject>\n	  <dc:date>{gmt_entry_date format="%Y-%m-%dT%H:%i:%s%Q"}</dc:date>\n	</item>\n{/exp:channel:entries}\n	\n	</channel>\n</rss>\n\n{/exp:rss:feed}						', NULL, 1409242030, 1, 'n', 0, '', 'n', 'n', 'o', 0, 'n', 'y'),
	(16, 1, 4, 'index', 'webpage', '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"\n"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">\n<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{lang}" lang="{lang}">\n\n<head>\n<title>{site_name}{lang:search}</title>\n\n<meta http-equiv="content-type" content="text/html; charset={charset}" />\n\n<link rel=\'stylesheet\' type=\'text/css\' media=\'all\' href=\'{stylesheet=search/search_css}\' />\n<style type=\'text/css\' media=\'screen\'>@import "{stylesheet=search/search_css}";</style>\n\n</head>\n<body>\n\n<div id=\'pageheader\'>\n<div class="heading">{lang:search_engine}</div>\n</div>\n\n<div id="content">\n\n<div class=\'breadcrumb\'>\n<span class="defaultBold">&nbsp; <a href="{homepage}">{site_name}</a>&nbsp;&#8250;&nbsp;&nbsp;{lang:search}</span>\n</div>\n\n<div class=\'outerBorder\'>\n<div class=\'tablePad\'>\n\n{exp:search:advanced_form result_page="search/results" cat_style="nested"}\n\n<table cellpadding=\'4\' cellspacing=\'6\' border=\'0\' width=\'100%\'>\n<tr>\n<td width="50%">\n\n<fieldset class="fieldset">\n<legend>{lang:search_by_keyword}</legend>\n\n<input type="text" class="input" maxlength="100" size="40" name="keywords" style="width:100%;" />\n\n<div class="default">\n<select name="search_in">\n<option value="titles" selected="selected">{lang:search_in_titles}</option>\n<option value="entries">{lang:search_in_entries}</option>\n<option value="everywhere" >{lang:search_everywhere}</option>\n</select>\n\n</div>\n\n<div class="default">\n<select name="where">\n<option value="exact" selected="selected">{lang:exact_phrase_match}</option>\n<option value="any">{lang:search_any_words}</option>\n<option value="all" >{lang:search_all_words}</option>\n<option value="word" >{lang:search_exact_word}</option>\n</select>\n</div>\n\n</fieldset>\n\n<div class="default"><br /></div>\n\n<table cellpadding=\'0\' cellspacing=\'0\' border=\'0\'>\n<tr>\n<td valign="top">\n\n<div class="defaultBold">{lang:channels}</div>\n\n<select id="channel_id" name=\'channel_id[]\' class=\'multiselect\' size=\'12\' multiple=\'multiple\' onchange=\'changemenu(this.selectedIndex);\'>\n{channel_names}\n</select>\n\n</td>\n<td valign="top" width="16">&nbsp;</td>\n<td valign="top">\n\n<div class="defaultBold">{lang:categories}</div>\n\n<select name=\'cat_id[]\' size=\'12\'  class=\'multiselect\' multiple=\'multiple\'>\n<option value=\'all\' selected="selected">{lang:any_category}</option>\n</select>\n\n</td>\n</tr>\n</table>\n\n\n\n</td><td width="50%" valign="top">\n\n\n<fieldset class="fieldset">\n<legend>{lang:search_by_member_name}</legend>\n\n<input type="text" class="input" maxlength="100" size="40" name="member_name" style="width:100%;" />\n<div class="default"><input type="checkbox" class="checkbox" name="exact_match" value="y"  /> {lang:exact_name_match}</div>\n\n</fieldset>\n\n<div class="default"><br /></div>\n\n\n<fieldset class="fieldset">\n<legend>{lang:search_entries_from}</legend>\n\n<select name="date" style="width:150px">\n<option value="0" selected="selected">{lang:any_date}</option>\n<option value="1" >{lang:today_and}</option>\n<option value="7" >{lang:this_week_and}</option>\n<option value="30" >{lang:one_month_ago_and}</option>\n<option value="90" >{lang:three_months_ago_and}</option>\n<option value="180" >{lang:six_months_ago_and}</option>\n<option value="365" >{lang:one_year_ago_and}</option>\n</select>\n\n<div class="default">\n<input type=\'radio\' name=\'date_order\' value=\'newer\' class=\'radio\' checked="checked" />&nbsp;{lang:newer}\n<input type=\'radio\' name=\'date_order\' value=\'older\' class=\'radio\' />&nbsp;{lang:older}\n</div>\n\n</fieldset>\n\n<div class="default"><br /></div>\n\n<fieldset class="fieldset">\n<legend>{lang:sort_results_by}</legend>\n\n<select name="orderby">\n<option value="date" >{lang:date}</option>\n<option value="title" >{lang:title}</option>\n<option value="most_comments" >{lang:most_comments}</option>\n<option value="recent_comment" >{lang:recent_comment}</option>\n</select>\n\n<div class="default">\n<input type=\'radio\' name=\'sort_order\' class="radio" value=\'desc\' checked="checked" /> {lang:descending}\n<input type=\'radio\' name=\'sort_order\' class="radio" value=\'asc\' /> {lang:ascending}\n</div>\n</fieldset>\n\n</td>\n</tr>\n</table>\n\n\n<div class=\'searchSubmit\'>\n\n<input type=\'submit\' value=\'Search\' class=\'submit\' />\n\n</div>\n\n{/exp:search:advanced_form}\n\n<div class=\'copyright\'><a href="https://expressionengine.com/">Powered by ExpressionEngine</a></div>\n\n\n</div>\n</div>\n</div>\n\n</body>\n</html>', NULL, 1409242030, 1, 'n', 0, '', 'n', 'n', 'o', 0, 'n', 'y'),
	(17, 1, 4, 'no_results', 'webpage', '{!-- Explanations and learning materials can be found in news/index and the other news template groups.  In-line comments here are only for features not introduced in news/index. --}\n{html_head}\n	<title>{site_name}: No Search Results</title>\n{global_stylesheets}\n{rss}\n{favicon}\n{html_head_end}\n	<body>\n{nav_access}\n	{branding_begin}\n			{embed="global_embeds/_top_nav" loc="not_found"}\n			{global_top_search}\n			{global_top_member}\n	{branding_end}\n	{wrapper_begin}\n{embed="global_embeds/_page_header" header="Search Results"}\n\n\n	<div id="content_pri"> <!-- This is where all primary content, left column gets entered -->\n\n		<!-- No search results: https://docs.expressionengine.com/latest/modules/search/simple.html#par_no_result_page -->\n		<!-- This is delivered based on the no_result_page parameter of the search form  -->\n\n				<h3>Search Results</h3>\n\n				<!-- exp:search:keywords: https://docs.expressionengine.com/latest/modules/search/keywords.html -->\n				<!-- exp:search:keywords lets you echo out what search term was used -->\n					<p>Sorry, no results were found for "<strong>{exp:search:keywords}</strong>".  Please try again.</p>\n	</div>\n\n	<div id="content_sec" class="right green40">\n		<h3 class="oldernews">Browse Older News</h3>\n		<div id="news_archives">\n			<div id="categories_box">\n			{news_categories}\n			</div>\n			<div id="month_box">\n			{news_month_archives}\n			</div>\n		</div> <!-- ending #news_archives -->\n\n		{news_calendar}\n\n		{news_popular}\n\n	{rss_links}\n\n	</div>	<!-- ending #content_sec -->\n\n{global_footer}\n{wrapper_close}\n{js}\n{html_close}\n', NULL, 1409242030, 1, 'n', 0, '', 'n', 'n', 'o', 0, 'n', 'y'),
	(18, 1, 4, 'results', 'webpage', '{!-- Explanations and learning materials can be found in news/index and the other news template groups.  In-line comments here are only for features not introduced in news/index. --}\n{html_head}\n	<title>{site_name}: {exp:search:search_results}\n		{if count == "1"}\n			Search Results for "{exp:search:keywords}"\n		{/if}\n		{/exp:search:search_results}\n	</title>\n{global_stylesheets}\n{rss}\n{favicon}\n{html_head_end}\n	<body>\n{nav_access}\n	{branding_begin}\n		{embed="global_embeds/_top_nav" loc="not_found"}\n		{global_top_search}\n		{global_top_member}\n	{branding_end}\n	{wrapper_begin}\n	{embed="global_embeds/_page_header" header="Search Results"}\n\n	<div id="content_pri"> <!-- This is where all primary content, left column gets entered -->\n\n		<!-- Search Results tag: https://docs.expressionengine.com/latest/modules/search/index.html#results -->\n\n		{exp:search:search_results}\n			{if count == "1"}\n				<!-- exp:search:keywords: https://docs.expressionengine.com/latest/modules/search/keywords.html -->\n				<!-- exp:search:keywords lets you echo out what search term was used -->\n\n				<h3>Search Results for "<strong>{exp:search:keywords}</strong>":</h3>\n				<ul id="news_listing">\n			{/if}\n\n			<li>\n				<h4>\n					<a href="{comment_url_title_auto_path}">{title}</a>  //\n					<!-- entry_date is a variable, and date formatting variables can be found at https://docs.expressionengine.com/latest/templates/date_variable_formatting.html -->\n					{entry_date format="%F %d %Y"}\n				</h4>\n\n				<!-- news_body and news_image are  custom channel fields. https://docs.expressionengine.com/latest/cp/admin/channel_administration/custom_channel_fields.html -->\n				{if news_image}\n					<img src="{news_image}" alt="{title}" />\n				{/if}\n				{news_body}\n			</li>\n			{if count == total_results}</ul>{/if}\n\n			{paginate}\n				<div class="pagination">\n					{pagination_links}\n						<ul>\n							{first_page}\n								<li><a href="{pagination_url}" class="page-first">First Page</a></li>\n							{/first_page}\n\n							{previous_page}\n								<li><a href="{pagination_url}" class="page-previous">Previous Page</a></li>\n							{/previous_page}\n\n							{page}\n								<li><a href="{pagination_url}" class="page-{pagination_page_number} {if current_page}active{/if}">{pagination_page_number}</a></li>\n							{/page}\n\n							{next_page}\n								<li><a href="{pagination_url}" class="page-next">Next Page</a></li>\n							{/next_page}\n\n							{last_page}\n								<li><a href="{pagination_url}" class="page-last">Last Page</a></li>\n							{/last_page}\n						</ul>\n					{/pagination_links}\n				</div> <!-- ending .pagination -->\n			{/paginate}\n		{/exp:search:search_results}\n	</div>\n\n	<div id="content_sec" class="right green40">\n		<h3 class="oldernews">Browse Older News</h3>\n		<div id="news_archives">\n			<div id="categories_box">\n			{news_categories}\n			</div>\n			<div id="month_box">\n			{news_month_archives}\n			</div>\n		</div> <!-- ending #news_archives -->\n\n		{news_calendar}\n\n		{news_popular}\n\n	{rss_links}\n\n	</div>	<!-- ending #content_sec -->\n\n{global_footer}\n{wrapper_close}\n{js}\n{html_close}\n', NULL, 1409242030, 1, 'n', 0, '', 'n', 'n', 'o', 0, 'n', 'y');
/*!40000 ALTER TABLE `exp_templates` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_templates_roles: ~0 rows (approximately)
DELETE FROM `exp_templates_roles`;
/*!40000 ALTER TABLE `exp_templates_roles` DISABLE KEYS */;
INSERT INTO `exp_templates_roles` (`role_id`, `template_id`) VALUES
	(3, 1),
	(3, 2),
	(3, 3),
	(3, 5),
	(3, 6),
	(3, 7),
	(3, 8),
	(3, 9),
	(3, 10),
	(3, 11),
	(3, 12),
	(3, 13),
	(3, 14),
	(3, 15),
	(3, 17),
	(3, 18),
	(4, 1),
	(4, 2),
	(4, 3),
	(4, 5),
	(4, 6),
	(4, 7),
	(4, 8),
	(4, 9),
	(4, 10),
	(4, 11),
	(4, 12),
	(4, 13),
	(4, 14),
	(4, 15),
	(4, 17),
	(4, 18),
	(5, 1),
	(5, 2),
	(5, 3),
	(5, 5),
	(5, 6),
	(5, 7),
	(5, 8),
	(5, 9),
	(5, 10),
	(5, 11),
	(5, 12),
	(5, 13),
	(5, 14),
	(5, 15),
	(5, 17),
	(5, 18);
/*!40000 ALTER TABLE `exp_templates_roles` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_template_groups: ~0 rows (approximately)
DELETE FROM `exp_template_groups`;
/*!40000 ALTER TABLE `exp_template_groups` DISABLE KEYS */;
INSERT INTO `exp_template_groups` (`group_id`, `site_id`, `group_name`, `group_order`, `is_site_default`) VALUES
	(1, 1, 'about', 1, 'n'),
	(2, 1, 'global_embeds', 2, 'n'),
	(3, 1, 'news', 3, 'y'),
	(4, 1, 'search', 4, 'n');
/*!40000 ALTER TABLE `exp_template_groups` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_template_groups_roles: ~0 rows (approximately)
DELETE FROM `exp_template_groups_roles`;
/*!40000 ALTER TABLE `exp_template_groups_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_template_groups_roles` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_template_routes: ~0 rows (approximately)
DELETE FROM `exp_template_routes`;
/*!40000 ALTER TABLE `exp_template_routes` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_template_routes` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_throttle: ~0 rows (approximately)
DELETE FROM `exp_throttle`;
/*!40000 ALTER TABLE `exp_throttle` DISABLE KEYS */;
/*!40000 ALTER TABLE `exp_throttle` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_upload_prefs: ~0 rows (approximately)
DELETE FROM `exp_upload_prefs`;
/*!40000 ALTER TABLE `exp_upload_prefs` DISABLE KEYS */;
INSERT INTO `exp_upload_prefs` (`id`, `site_id`, `name`, `server_path`, `url`, `allowed_types`, `default_modal_view`, `max_size`, `max_height`, `max_width`, `properties`, `pre_format`, `post_format`, `file_properties`, `file_pre_format`, `file_post_format`, `cat_group`, `batch_location`, `module_id`) VALUES
	(1, 1, 'Main Upload Directory', '{base_path}/images/uploads/', '/images/uploads/', 'all', 'list', '', '', '', 'style="border: 0;" alt="image"', '', '', '', '', '', NULL, NULL, 0),
	(2, 1, 'About', '{base_path}/images/about/', '/images/about/', 'img', 'list', '', '', '', '', '', '', '', '', '', NULL, NULL, 0),
	(3, 1, 'Avatars', '{base_path}/images/avatars/', '{base_url}/images/avatars/', 'img', 'list', '50', '100', '100', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4),
	(5, 1, 'Signature Attachments', '{base_path}/images/signature_attachments/', '/images/signature_attachments/', 'img', 'list', '30', '80', '480', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4),
	(6, 1, 'PM Attachments', '{base_path}/images/pm_attachments/', '/images/pm_attachments/', 'img', 'list', '250', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4);
/*!40000 ALTER TABLE `exp_upload_prefs` ENABLE KEYS */;

-- Dumping data for table ee-test.exp_upload_prefs_roles: ~2 rows (approximately)
DELETE FROM `exp_upload_prefs_roles`;
/*!40000 ALTER TABLE `exp_upload_prefs_roles` DISABLE KEYS */;
INSERT INTO `exp_upload_prefs_roles` (`role_id`, `upload_id`) VALUES
	(5, 1),
	(5, 2);
/*!40000 ALTER TABLE `exp_upload_prefs_roles` ENABLE KEYS */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
