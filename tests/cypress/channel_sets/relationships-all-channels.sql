# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 127.0.0.1 (MySQL 5.7.11-4)
# Database: ee.dev
# Generation Time: 2016-04-07 22:24:47 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table exp_channel_data
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_channel_data`;

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
  `field_id_8` varchar(8) DEFAULT NULL,
  `field_ft_8` tinytext,
  PRIMARY KEY (`entry_id`),
  KEY `channel_id` (`channel_id`),
  KEY `site_id` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `exp_channel_data` WRITE;
/*!40000 ALTER TABLE `exp_channel_data` DISABLE KEYS */;

INSERT INTO `exp_channel_data` (`entry_id`, `site_id`, `channel_id`, `field_id_1`, `field_ft_1`, `field_id_2`, `field_ft_2`, `field_id_3`, `field_ft_3`, `field_id_4`, `field_ft_4`, `field_id_5`, `field_ft_5`, `field_id_6`, `field_ft_6`, `field_id_7`, `field_ft_7`, `field_id_8`, `field_ft_8`)
VALUES
	(1,1,1,'Thank you for choosing ExpressionEngine! This entry contains helpful resources to help you <a href=\"https://docs.expressionengine.com/latest/intro/getting_the_most.html\">get the most from ExpressionEngine</a> and the ExpressionEngine Community.\n\n<strong>Learning resources:</strong>\n\n<a href=\"https://docs.expressionengine.com/latest/\">ExpressionEngine User Guide</a>\n<a href=\"https://docs.expressionengine.com/latest/intro/the_big_picture.html\">The Big Picture</a>\n<a href=\"https://expressionengine.com/support\">ExpressionEngine Support</a>\n\nIf you need to hire a web developer consider our <a href=\"https://expressionengine.com/pro-network/\">Professionals Network</a>.\n\nWelcome to our community,\n\n<span style=\"font-size:16px;\">The ExpressionEngine Team</span>','xhtml','','xhtml','{filedir_2}ee_banner_120_240.gif','none','','xhtml','','none','','none','','xhtml',NULL,NULL),
	(2,1,1,'Welcome to Agile Records, our Example Site.  Here you will be able to learn ExpressionEngine through a real site, with real features and in-depth comments to assist you along the way.\n\n','xhtml','','xhtml','{filedir_2}map.jpg','none','','xhtml','','none','','none','','xhtml',NULL,NULL),
	(3,1,2,'',NULL,'',NULL,'',NULL,'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Duis congue accumsan tellus. Aliquam diam arcu, suscipit eu, condimentum sed, ultricies accumsan, massa.\n','xhtml','{filedir_2}map2.jpg','none','','none','Donec et ante. Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Vestibulum dignissim dolor nec erat dictum posuere. Vivamus lacinia, quam id fringilla dapibus, ante ante bibendum nulla, a ornare nisl est congue purus. Duis pulvinar vehicula diam.\n\nSed vehicula. Praesent vitae nisi. Phasellus molestie, massa sed varius ultricies, dolor lectus interdum felis, ut porta eros nibh at magna. Cras aliquam vulputate lacus. Nullam tempus vehicula mi. Quisque posuere, erat quis iaculis consequat, tortor ipsum varius mauris, sit amet pulvinar nibh mauris sed lectus. Cras vitae arcu sit amet nunc luctus molestie. Nam neque orci, tincidunt non, semper convallis, sodales fringilla, nulla. Donec non nunc. Sed condimentum urna hendrerit erat. Curabitur in felis in neque fermentum interdum.\n\nProin magna. In in orci. Curabitur at lectus nec arcu vehicula bibendum. Duis euismod sollicitudin augue. Maecenas auctor cursus odio.\n','xhtml',NULL,NULL),
	(4,1,2,'',NULL,'',NULL,'',NULL,'','xhtml','{filedir_2}staff_randell.png','none','Co-Owner/Label Manager','none','','xhtml',NULL,NULL),
	(5,1,2,'',NULL,'',NULL,'',NULL,'','xhtml','{filedir_2}staff_chloe.png','none','Co-Owner / Press &amp; Marketing','none','','xhtml',NULL,NULL),
	(6,1,2,'',NULL,'',NULL,'',NULL,'','xhtml','{filedir_2}staff_howard.png','none','Tours/Publicity/PR','none','','xhtml',NULL,NULL),
	(7,1,2,'',NULL,'',NULL,'',NULL,'','xhtml','{filedir_2}staff_jane.png','none','Sales/Accounts','none','','xhtml',NULL,NULL),
	(8,1,2,'',NULL,'',NULL,'',NULL,'','xhtml','{filedir_2}staff_josh.png','none','Product Manager','none','','xhtml',NULL,NULL),
	(9,1,2,'',NULL,'',NULL,'',NULL,'','xhtml','{filedir_2}staff_jason.png','none','Graphic/Web Designer','none','','xhtml',NULL,NULL),
	(10,1,1,'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Proin congue mi a sapien. Duis augue erat, fringilla ac, volutpat ut, venenatis vitae, nisl. Phasellus lorem. Praesent mi. Suspendisse imperdiet felis a libero. uspendisse placerat tortor in ligula vestibulum vehicula.\n','xhtml','','xhtml','{filedir_2}testband300.jpg','none','',NULL,'',NULL,'',NULL,'',NULL,NULL,NULL);

/*!40000 ALTER TABLE `exp_channel_data` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table exp_channel_fields
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_channel_fields`;

CREATE TABLE `exp_channel_fields` (
  `field_id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
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
  `legacy_field_data` char(1) NOT NULL default 'n',
  PRIMARY KEY (`field_id`),
  KEY `field_type` (`field_type`),
  KEY `site_id` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `exp_channel_fields` WRITE;
/*!40000 ALTER TABLE `exp_channel_fields` DISABLE KEYS */;

INSERT INTO `exp_channel_fields` (`field_id`, `site_id`, `field_name`, `field_label`, `field_instructions`, `field_type`, `field_list_items`, `field_pre_populate`, `field_pre_channel_id`, `field_pre_field_id`, `field_ta_rows`, `field_maxl`, `field_required`, `field_text_direction`, `field_search`, `field_is_hidden`, `field_fmt`, `field_show_fmt`, `field_order`, `field_content_type`, `field_settings`)
VALUES
	(1,1,'news_body','Body','','textarea','','n',0,0,10,0,'n','ltr','y','n','xhtml','y',2,'any','YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToieSI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJ5IjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToieSI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToieSI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6InkiO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6InkiO30='),
	(2,1,'news_extended','Extended text','','textarea','','n',0,0,12,0,'n','ltr','n','y','xhtml','y',3,'any','YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToieSI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJ5IjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToieSI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToieSI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6InkiO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6InkiO30='),
	(3,1,'news_image','News Image','','file','','n',0,0,6,128,'n','ltr','n','n','none','n',3,'any','YTo3OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO3M6MTg6ImZpZWxkX2NvbnRlbnRfdHlwZSI7czo1OiJpbWFnZSI7fQ=='),
	(4,1,'about_body','Body','','textarea','','n',0,0,6,128,'n','ltr','n','n','xhtml','y',4,'any','YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToieSI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJ5IjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToieSI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToieSI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6InkiO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6InkiO30='),
	(5,1,'about_image','Image','URL Only','file','','n',0,0,6,128,'n','ltr','n','n','none','n',5,'any','YTo3OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO3M6MTg6ImZpZWxkX2NvbnRlbnRfdHlwZSI7czo1OiJpbWFnZSI7fQ=='),
	(6,1,'about_staff_title','Staff Member\'s Title','This is the Title that the staff member has within the company.  Example: CEO','text','','n',0,0,6,128,'n','ltr','y','n','none','n',6,'any','YTo4OntzOjE4OiJmaWVsZF9jb250ZW50X3RleHQiO2I6MDtzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO3M6MTg6ImZpZWxkX2NvbnRlbnRfdHlwZSI7czozOiJhbnkiO30='),
	(7,1,'about_extended','Extended','','textarea','','n',0,0,6,128,'n','ltr','y','y','xhtml','y',7,'any','YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToieSI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJ5IjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToieSI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToieSI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6InkiO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6InkiO30='),
	(8,1,'relationships','Relationships','','relationship','','n',NULL,NULL,8,NULL,'n','ltr','n','n','xhtml','y',4,'any','YToxMDp7czo4OiJjaGFubmVscyI7YTowOnt9czo3OiJleHBpcmVkIjtpOjA7czo2OiJmdXR1cmUiO3M6MToiMSI7czoxMDoiY2F0ZWdvcmllcyI7YTowOnt9czo3OiJhdXRob3JzIjthOjA6e31zOjg6InN0YXR1c2VzIjthOjA6e31zOjU6ImxpbWl0IjtzOjI6IjI1IjtzOjExOiJvcmRlcl9maWVsZCI7czoxMDoiZW50cnlfZGF0ZSI7czo5OiJvcmRlcl9kaXIiO3M6NDoiZGVzYyI7czoxNDoiYWxsb3dfbXVsdGlwbGUiO2k6MDt9');

/*!40000 ALTER TABLE `exp_channel_fields` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `exp_channel_field_groups_fields`;

CREATE TABLE `exp_channel_field_groups_fields` (
  `field_id` int(6) unsigned NOT NULL,
  `group_id` int(4) unsigned NOT NULL,
  PRIMARY KEY (`field_id`,`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `exp_channel_field_groups_fields` WRITE;
/*!40000 ALTER TABLE `exp_channel_field_groups_fields` DISABLE KEYS */;

INSERT INTO `exp_channel_field_groups_fields` (`field_id`, `group_id`)
VALUES
	(1,1),
	(2,1),
	(3,1),
	(4,2),
	(5,2),
	(6,2),
	(7,2),
	(8,1);

/*!40000 ALTER TABLE `exp_channel_field_groups_fields` ENABLE KEYS */;
UNLOCK TABLES;


/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
