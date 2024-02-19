# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: localhost (MySQL 5.7.19)
# Database: ee-rspec
# Generation Time: 2017-09-18 18:16:40 +0000
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
  PRIMARY KEY (`entry_id`),
  KEY `channel_id` (`channel_id`),
  KEY `site_id` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `exp_channel_data` WRITE;
/*!40000 ALTER TABLE `exp_channel_data` DISABLE KEYS */;

INSERT INTO `exp_channel_data` (`entry_id`, `site_id`, `channel_id`, `field_id_1`, `field_ft_1`, `field_id_2`, `field_ft_2`, `field_id_3`, `field_ft_3`, `field_id_4`, `field_ft_4`, `field_id_5`, `field_ft_5`, `field_id_6`, `field_ft_6`, `field_id_7`, `field_ft_7`)
VALUES
	(1,1,1,'Thank you for choosing ExpressionEngine! This entry contains helpful resources to help you <a href=\"https://docs.expressionengine.com/latest/intro/getting_the_most.html\">get the most from ExpressionEngine</a> and the ExpressionEngine Community.\n\n<strong>Learning resources:</strong>\n\n<a href=\"https://docs.expressionengine.com/latest/\">ExpressionEngine User Guide</a>\n<a href=\"https://docs.expressionengine.com/latest/intro/the_big_picture.html\">The Big Picture</a>\n<a href=\"https://expressionengine.com/support\">ExpressionEngine Support</a>\n\nIf you need to hire a web developer consider our <a href=\"https://expressionengine.com/pro-network/\">Professionals Network</a>.\n\nWelcome to our community,\n\n<span style=\"font-size:16px;\">The ExpressionEngine Team</span>','xhtml','','xhtml','{filedir_2}ee_banner_120_240.gif','none','','xhtml','','none','','none','','xhtml'),
	(2,1,1,'Welcome to Agile Records, our Example Site.  Here you will be able to learn ExpressionEngine through a real site, with real features and in-depth comments to assist you along the way.\n\n','xhtml','','xhtml','{filedir_2}map.jpg','none','','xhtml','','none','','none','','xhtml'),
	(3,1,2,'',NULL,'',NULL,'',NULL,'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Duis congue accumsan tellus. Aliquam diam arcu, suscipit eu, condimentum sed, ultricies accumsan, massa.\n','xhtml','{filedir_2}map2.jpg','none','','none','Donec et ante. Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Vestibulum dignissim dolor nec erat dictum posuere. Vivamus lacinia, quam id fringilla dapibus, ante ante bibendum nulla, a ornare nisl est congue purus. Duis pulvinar vehicula diam.\n\nSed vehicula. Praesent vitae nisi. Phasellus molestie, massa sed varius ultricies, dolor lectus interdum felis, ut porta eros nibh at magna. Cras aliquam vulputate lacus. Nullam tempus vehicula mi. Quisque posuere, erat quis iaculis consequat, tortor ipsum varius mauris, sit amet pulvinar nibh mauris sed lectus. Cras vitae arcu sit amet nunc luctus molestie. Nam neque orci, tincidunt non, semper convallis, sodales fringilla, nulla. Donec non nunc. Sed condimentum urna hendrerit erat. Curabitur in felis in neque fermentum interdum.\n\nProin magna. In in orci. Curabitur at lectus nec arcu vehicula bibendum. Duis euismod sollicitudin augue. Maecenas auctor cursus odio.\n','xhtml'),
	(4,1,2,'',NULL,'',NULL,'',NULL,'','xhtml','{filedir_2}staff_randell.png','none','Co-Owner/Label Manager','none','','xhtml'),
	(5,1,2,'',NULL,'',NULL,'',NULL,'','xhtml','{filedir_2}staff_chloe.png','none','Co-Owner / Press &amp; Marketing','none','','xhtml'),
	(6,1,2,'',NULL,'',NULL,'',NULL,'','xhtml','{filedir_2}staff_howard.png','none','Tours/Publicity/PR','none','','xhtml'),
	(7,1,2,'',NULL,'',NULL,'',NULL,'','xhtml','{filedir_2}staff_jane.png','none','Sales/Accounts','none','','xhtml'),
	(8,1,2,'',NULL,'',NULL,'',NULL,'','xhtml','{filedir_2}staff_josh.png','none','Product Manager','none','','xhtml'),
	(9,1,2,'',NULL,'',NULL,'',NULL,'','xhtml','{filedir_2}staff_jason.png','none','Graphic/Web Designer','none','','xhtml'),
	(10,1,1,'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Proin congue mi a sapien. Duis augue erat, fringilla ac, volutpat ut, venenatis vitae, nisl. Phasellus lorem. Praesent mi. Suspendisse imperdiet felis a libero. uspendisse placerat tortor in ligula vestibulum vehicula.\n','xhtml','','xhtml','{filedir_2}testband300.jpg','none','',NULL,'',NULL,'',NULL,'',NULL);

/*!40000 ALTER TABLE `exp_channel_data` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table exp_channel_data_field_10
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_channel_data_field_10`;

CREATE TABLE `exp_channel_data_field_10` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned NOT NULL,
  `field_id_10` text COLLATE utf8_unicode_ci,
  `field_ft_10` tinytext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table exp_channel_data_field_11
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_channel_data_field_11`;

CREATE TABLE `exp_channel_data_field_11` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned NOT NULL,
  `field_id_11` text COLLATE utf8_unicode_ci,
  `field_ft_11` tinytext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table exp_channel_data_field_12
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_channel_data_field_12`;

CREATE TABLE `exp_channel_data_field_12` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned NOT NULL,
  `field_id_12` text COLLATE utf8_unicode_ci,
  `field_ft_12` tinytext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table exp_channel_data_field_13
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_channel_data_field_13`;

CREATE TABLE `exp_channel_data_field_13` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned NOT NULL,
  `field_id_13` text COLLATE utf8_unicode_ci,
  `field_ft_13` tinytext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table exp_channel_data_field_14
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_channel_data_field_14`;

CREATE TABLE `exp_channel_data_field_14` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned NOT NULL,
  `field_id_14` text COLLATE utf8_unicode_ci,
  `field_ft_14` tinytext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table exp_channel_data_field_15
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_channel_data_field_15`;

CREATE TABLE `exp_channel_data_field_15` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned NOT NULL,
  `field_id_15` text COLLATE utf8_unicode_ci,
  `field_ft_15` tinytext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table exp_channel_data_field_16
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_channel_data_field_16`;

CREATE TABLE `exp_channel_data_field_16` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned NOT NULL,
  `field_id_16` text COLLATE utf8_unicode_ci,
  `field_ft_16` tinytext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table exp_channel_data_field_17
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_channel_data_field_17`;

CREATE TABLE `exp_channel_data_field_17` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned NOT NULL,
  `field_id_17` varchar(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  `field_ft_17` tinytext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `exp_channel_data_field_17` (`id`, `entry_id`, `field_id_17`, `field_ft_17`) VALUES
	(3, 1, NULL, 'xhtml');


# Dump of table exp_channel_data_field_18
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_channel_data_field_18`;

CREATE TABLE `exp_channel_data_field_18` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned NOT NULL,
  `field_id_18` text COLLATE utf8_unicode_ci,
  `field_ft_18` tinytext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table exp_channel_data_field_19
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_channel_data_field_19`;

CREATE TABLE `exp_channel_data_field_19` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned NOT NULL,
  `field_id_19` text COLLATE utf8_unicode_ci,
  `field_ft_19` tinytext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table exp_channel_data_field_20
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_channel_data_field_20`;

CREATE TABLE `exp_channel_data_field_20` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned NOT NULL,
  `field_id_20` text COLLATE utf8_unicode_ci,
  `field_ft_20` tinytext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table exp_channel_data_field_21
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_channel_data_field_21`;

CREATE TABLE `exp_channel_data_field_21` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned NOT NULL,
  `field_id_21` tinyint(4) NOT NULL DEFAULT '0',
  `field_ft_21` tinytext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table exp_channel_data_field_22
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_channel_data_field_22`;

CREATE TABLE `exp_channel_data_field_22` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned NOT NULL,
  `field_id_22` text COLLATE utf8_unicode_ci,
  `field_ft_22` tinytext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table exp_channel_data_field_8
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_channel_data_field_8`;

CREATE TABLE `exp_channel_data_field_8` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned NOT NULL,
  `field_id_8` int(10) DEFAULT '0',
  `field_dt_8` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `field_ft_8` tinytext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table exp_channel_data_field_9
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_channel_data_field_9`;

CREATE TABLE `exp_channel_data_field_9` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned NOT NULL,
  `field_id_9` text COLLATE utf8_unicode_ci,
  `field_ft_9` tinytext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


# Dump of table exp_channel_data_field_23
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_channel_data_field_23`;

CREATE TABLE `exp_channel_data_field_23` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned NOT NULL,
  `field_id_23` text COLLATE utf8_unicode_ci,
  `field_ft_23` tinytext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


# Dump of table exp_channel_field_groups_fields
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_channel_field_groups_fields`;

CREATE TABLE `exp_channel_field_groups_fields` (
  `field_id` int(6) unsigned NOT NULL,
  `group_id` int(4) unsigned NOT NULL,
  PRIMARY KEY (`field_id`,`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `exp_channel_field_groups_fields` WRITE;
/*!40000 ALTER TABLE `exp_channel_field_groups_fields` DISABLE KEYS */;

INSERT INTO `exp_channel_field_groups_fields` (`field_id`, `group_id`)
VALUES
	(1,1),
	(2,1),
	(3,1),
  (17,1),
	(4,2),
	(5,2),
	(6,2),
	(7,2);

/*!40000 ALTER TABLE `exp_channel_field_groups_fields` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table exp_channel_fields
# ------------------------------------------------------------

TRUNCATE `exp_channel_fields`;

INSERT INTO `exp_channel_fields` (`field_id`, `site_id`, `field_name`, `field_label`, `field_instructions`, `field_type`, `field_list_items`, `field_pre_populate`, `field_pre_channel_id`, `field_pre_field_id`, `field_ta_rows`, `field_maxl`, `field_required`, `field_text_direction`, `field_search`, `field_is_hidden`, `field_fmt`, `field_show_fmt`, `field_order`, `field_content_type`, `field_settings`, `legacy_field_data`)
VALUES
	(1,1,'news_body','Body','','textarea','','n',0,0,10,0,'n','ltr','y','n','xhtml','y',2,'any','YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToieSI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJ5IjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToieSI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToieSI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6InkiO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6InkiO30=','y'),
	(2,1,'news_extended','Extended text','','textarea','','n',0,0,12,0,'n','ltr','n','y','xhtml','y',3,'any','YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToieSI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJ5IjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToieSI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToieSI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6InkiO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6InkiO30=','y'),
	(3,1,'news_image','News Image','','file','','n',0,0,6,128,'n','ltr','n','n','none','n',3,'any','YTo3OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO3M6MTg6ImZpZWxkX2NvbnRlbnRfdHlwZSI7czo1OiJpbWFnZSI7fQ==','y'),
	(4,1,'about_body','Body','','textarea','','n',0,0,6,128,'n','ltr','n','n','xhtml','y',4,'any','YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToieSI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJ5IjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToieSI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToieSI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6InkiO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6InkiO30=','y'),
	(5,1,'about_image','Image','URL Only','file','','n',0,0,6,128,'n','ltr','n','n','none','n',5,'any','YTo3OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO3M6MTg6ImZpZWxkX2NvbnRlbnRfdHlwZSI7czo1OiJpbWFnZSI7fQ==','y'),
	(6,1,'about_staff_title','Staff Member\'s Title','This is the Title that the staff member has within the company.  Example: CEO','text','','n',0,0,6,128,'n','ltr','y','n','none','n',6,'any','YTo4OntzOjE4OiJmaWVsZF9jb250ZW50X3RleHQiO2I6MDtzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO3M6MTg6ImZpZWxkX2NvbnRlbnRfdHlwZSI7czozOiJhbnkiO30=','y'),
	(7,1,'about_extended','Extended','','textarea','','n',0,0,6,128,'n','ltr','y','y','xhtml','y',7,'any','YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToieSI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJ5IjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToieSI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToieSI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6InkiO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6InkiO30=','y'),
	(8,1,'a_date','A Date','','date','','n',NULL,NULL,8,NULL,'n','ltr','n','n','xhtml','y',11,'any','YTowOnt9','n'),
	(9,1,'checkboxes','Checkboxes','','checkboxes','One\nUno\nEin','n',NULL,NULL,8,NULL,'n','ltr','n','n','none','y',10,'any','YTowOnt9','n'),
	(10,1,'corpse','Corpse','','fluid_field','','n',NULL,NULL,8,NULL,'n','ltr','n','n','xhtml','y',19,'any','YToxOntzOjIwOiJmaWVsZF9jaGFubmVsX2ZpZWxkcyI7YToxNTp7aTowO3M6MToiOCI7aToxO3M6MToiOSI7aToyO3M6MjoiMTEiO2k6MztzOjI6IjEyIjtpOjQ7czoyOiIxMyI7aTo1O3M6MjoiMTciO2k6NjtzOjI6IjE0IjtpOjc7czoyOiIxNSI7aTo4O3M6MjoiMTYiO2k6OTtzOjI6IjIzIjtpOjEwO3M6MjoiMTgiO2k6MTE7czoyOiIxOSI7aToxMjtzOjI6IjIwIjtpOjEzO3M6MjoiMjEiO2k6MTQ7czoyOiIyMiI7fX0=','n'),
	(11,1,'electronic_mail_address','Electronic-Mail Address','','email_address','','n',NULL,NULL,8,NULL,'n','ltr','n','n','xhtml','y',12,'any','YTowOnt9','n'),
	(12,1,'home_page','Home Page','','url','','n',NULL,NULL,8,NULL,'n','ltr','n','n','xhtml','y',18,'any','YToyOntzOjE5OiJhbGxvd2VkX3VybF9zY2hlbWVzIjthOjI6e2k6MDtzOjc6Imh0dHA6Ly8iO2k6MTtzOjg6Imh0dHBzOi8vIjt9czoyMjoidXJsX3NjaGVtZV9wbGFjZWhvbGRlciI7czo3OiJodHRwOi8vIjt9','n'),
	(13,1,'image','Image','','file','','n',NULL,NULL,8,NULL,'n','ltr','n','n','none','y',2,'image','YTo1OntzOjE4OiJmaWVsZF9jb250ZW50X3R5cGUiO3M6NToiaW1hZ2UiO3M6MTk6ImFsbG93ZWRfZGlyZWN0b3JpZXMiO3M6MzoiYWxsIjtzOjEzOiJzaG93X2V4aXN0aW5nIjtzOjE6InkiO3M6MTI6Im51bV9leGlzdGluZyI7czoyOiI1MCI7czo5OiJmaWVsZF9mbXQiO3M6NDoibm9uZSI7fQ==','n'),
	(14,1,'middle_class_text','Middle Class Text','','rte','','n',NULL,NULL,6,NULL,'n','ltr','n','n','xhtml','n',15,'any','YToyOntzOjE0OiJmaWVsZF9zaG93X2ZtdCI7czoxOiJuIjtzOjEzOiJmaWVsZF90YV9yb3dzIjtpOjY7fQ==','n'),
	(15,1,'multi_select','Multi Select','','multi_select','Happy\nSad\nAngry\nConfused\nWorries\nScared','n',NULL,NULL,8,NULL,'n','ltr','n','n','none','y',13,'any','YTowOnt9','n'),
	(16,1,'radio','Radio','','radio','101.1 FM\n660 AM\n97.5 FM','n',NULL,NULL,8,NULL,'n','ltr','n','n','none','y',14,'any','YTowOnt9','n'),
	(17,1,'rel_item','Item','','relationship','','n',NULL,NULL,8,NULL,'n','ltr','n','n','xhtml','y',5,'any','YToxMDp7czo4OiJjaGFubmVscyI7YTowOnt9czo3OiJleHBpcmVkIjtpOjA7czo2OiJmdXR1cmUiO2k6MDtzOjEwOiJjYXRlZ29yaWVzIjthOjA6e31zOjc6ImF1dGhvcnMiO2E6MDp7fXM6ODoic3RhdHVzZXMiO2E6MDp7fXM6NToibGltaXQiO2k6MTAwO3M6MTE6Im9yZGVyX2ZpZWxkIjtzOjU6InRpdGxlIjtzOjk6Im9yZGVyX2RpciI7czozOiJhc2MiO3M6MTQ6ImFsbG93X211bHRpcGxlIjtpOjA7fQ==','n'),
	(18,1,'selection','Selection','','select','Burger\nBurrito\nCorndog','n',NULL,NULL,8,NULL,'n','ltr','n','n','none','y',16,'any','YTowOnt9','n'),
	(19,1,'stupid_grid','Stupid Grid','','grid','','n',NULL,NULL,8,NULL,'n','ltr','n','n','xhtml','y',9,'any','YToyOntzOjEzOiJncmlkX21pbl9yb3dzIjtpOjA7czoxMzoiZ3JpZF9tYXhfcm93cyI7czowOiIiO30=','n'),
	(20,1,'text','Text','','textarea','','n',NULL,NULL,6,NULL,'n','ltr','n','n','markdown','y',3,'any','YTozOntzOjI0OiJmaWVsZF9zaG93X2ZpbGVfc2VsZWN0b3IiO3M6MToibiI7czoxODoiZmllbGRfc2hvd19zbWlsZXlzIjtzOjE6Im4iO3M6MjY6ImZpZWxkX3Nob3dfZm9ybWF0dGluZ19idG5zIjtzOjE6Im4iO30=','n'),
	(21,1,'truth_or_dare','Truth or Dare?','','toggle','','n',NULL,NULL,8,NULL,'n','ltr','n','n','xhtml','y',17,'any','YToxOntzOjE5OiJmaWVsZF9kZWZhdWx0X3ZhbHVlIjtzOjE6IjAiO30=','n'),
	(22,1,'youtube_url','YouTube URL','','text','','n',NULL,NULL,8,256,'n','ltr','n','n','none','n',1,'all','YTo0OntzOjEwOiJmaWVsZF9tYXhsIjtzOjM6IjI1NiI7czoxODoiZmllbGRfY29udGVudF90eXBlIjtzOjM6ImFsbCI7czoxODoiZmllbGRfc2hvd19zbWlsZXlzIjtzOjE6Im4iO3M6MjQ6ImZpZWxkX3Nob3dfZmlsZV9zZWxlY3RvciI7czoxOiJuIjt9','n'),
  (23,1,'selectable_buttons','Selectable Buttons','','selectable_buttons','One\nUno\nEin','n',NULL,NULL,8,NULL,'n','ltr','n','n','none','y',10,'any','YTowOnt9','n');

/*!40000 ALTER TABLE `exp_channel_fields` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table exp_channel_grid_field_19
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_channel_grid_field_19`;

CREATE TABLE `exp_channel_grid_field_19` (
  `row_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned DEFAULT NULL,
  `row_order` int(10) unsigned DEFAULT NULL,
  `fluid_field_data_id` int(10) unsigned DEFAULT '0',
  `col_id_1` text COLLATE utf8_unicode_ci,
  `col_id_2` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`row_id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table exp_channels
# ------------------------------------------------------------

DELETE FROM `exp_channels`;

INSERT INTO `exp_channels` (`channel_id`, `site_id`, `channel_name`, `channel_title`, `channel_url`, `channel_description`, `channel_lang`, `total_entries`, `total_records`, `total_comments`, `last_entry_date`, `last_comment_date`, `cat_group`, `deft_status`, `search_excerpt`, `deft_category`, `deft_comments`, `channel_require_membership`, `channel_max_chars`, `channel_html_formatting`,  `channel_allow_img_urls`, `channel_auto_link_urls`, `channel_notify`, `channel_notify_emails`, `comment_url`, `comment_system_enabled`, `comment_require_membership`, `comment_moderate`, `comment_max_chars`, `comment_timelock`, `comment_require_email`, `comment_text_formatting`, `comment_html_formatting`, `comment_allow_img_urls`, `comment_auto_link_urls`, `comment_notify`, `comment_notify_authors`, `comment_notify_emails`, `comment_expiration`, `search_results_url`, `rss_url`, `enable_versioning`, `max_revisions`, `default_entry_title`, `title_field_label`, `url_title_prefix`, `preview_url`, `max_entries`, `conditional_sync_required`)
VALUES
	(1,1,'news','News','http://ee2/index.php/news',NULL,'en',3,0,0,1409242030,0,'1','open',2,'2','y','y',0,'all','y','y','n','','http://ee2/index.php/news/comments','y','n','n',0,0,'y','xhtml','safe','n','y','n','n','',0,'http://ee2/index.php/news/comments','','n',10,'','Title','','',0,'n'),
	(2,1,'about','Information Pages','http://ee2/index.php/about',NULL,'en',7,0,0,1409242030,0,'2','open',7,'','y','y',0,'all','y','n','n','','http://ee2/index.php/news/comments','n','n','n',0,0,'y','xhtml','safe','n','y','n','n','',0,'http://ee2/index.php/news/comments','','n',10,'','Title','','',0,'n'),
	(3,1,'fluid_fields','Fluid Fields','',NULL,'en',0,0,0,0,0,NULL,'open',NULL,NULL,'y','y',NULL,'all','y','n','n',NULL,NULL,'y','n','n',5000,0,'y','xhtml','safe','n','y','n','n',NULL,0,NULL,NULL,'n',10,NULL,'Title',NULL,'',0,'n');


# Dump of table exp_channels_channel_field_groups
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_channels_channel_field_groups`;

CREATE TABLE `exp_channels_channel_field_groups` (
  `channel_id` int(4) unsigned NOT NULL,
  `group_id` int(4) unsigned NOT NULL,
  PRIMARY KEY (`channel_id`,`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `exp_channels_channel_field_groups` WRITE;
/*!40000 ALTER TABLE `exp_channels_channel_field_groups` DISABLE KEYS */;

INSERT INTO `exp_channels_channel_field_groups` (`channel_id`, `group_id`)
VALUES
	(1,1),
	(2,2);

/*!40000 ALTER TABLE `exp_channels_channel_field_groups` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table exp_channels_channel_fields
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_channels_channel_fields`;

CREATE TABLE `exp_channels_channel_fields` (
  `channel_id` int(4) unsigned NOT NULL,
  `field_id` int(6) unsigned NOT NULL,
  PRIMARY KEY (`channel_id`,`field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `exp_channels_channel_fields` WRITE;
/*!40000 ALTER TABLE `exp_channels_channel_fields` DISABLE KEYS */;

INSERT INTO `exp_channels_channel_fields` (`channel_id`, `field_id`)
VALUES
	(3,10),
	(3,17),
	(3,19);

/*!40000 ALTER TABLE `exp_channels_channel_fields` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table exp_field_groups
# ------------------------------------------------------------

TRUNCATE `exp_field_groups`;

INSERT INTO `exp_field_groups` (`group_id`, `site_id`, `group_name`, `short_name`)
VALUES
	(1,1,'News','news'),
	(2,1,'About','about');


# Dump of table exp_grid_columns
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_grid_columns`;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `exp_grid_columns` WRITE;
/*!40000 ALTER TABLE `exp_grid_columns` DISABLE KEYS */;

INSERT INTO `exp_grid_columns` (`col_id`, `field_id`, `content_type`, `col_order`, `col_type`, `col_label`, `col_name`, `col_instructions`, `col_required`, `col_search`, `col_width`, `col_settings`)
VALUES
	(1,19,'channel',0,'text','Text one','text_one','','n','y',0,'{\"field_maxl\":\"256\",\"field_fmt\":\"none\",\"field_text_direction\":\"ltr\",\"field_content_type\":\"all\",\"field_required\":\"n\"}'),
	(2,19,'channel',1,'text','Text two','text_two','','n','y',0,'{\"field_maxl\":\"256\",\"field_fmt\":\"none\",\"field_text_direction\":\"ltr\",\"field_content_type\":\"all\",\"field_required\":\"n\"}');

/*!40000 ALTER TABLE `exp_grid_columns` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table exp_relationships
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_relationships`;

CREATE TABLE `exp_relationships` (
  `relationship_id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `child_id` int(10) unsigned NOT NULL DEFAULT '0',
  `field_id` int(10) unsigned NOT NULL DEFAULT '0',
  `grid_field_id` int(10) unsigned NOT NULL DEFAULT '0',
  `grid_col_id` int(10) unsigned NOT NULL DEFAULT '0',
  `grid_row_id` int(10) unsigned NOT NULL DEFAULT '0',
  `order` int(10) unsigned NOT NULL DEFAULT '0',
  `fluid_field_data_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`relationship_id`),
  KEY `parent_id` (`parent_id`),
  KEY `child_id` (`child_id`),
  KEY `field_id` (`field_id`),
  KEY `grid_row_id` (`grid_row_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `exp_relationships` (`relationship_id`, `parent_id`, `child_id`, `field_id`, `grid_field_id`, `grid_col_id`, `grid_row_id`, `order`, `fluid_field_data_id`) VALUES
	(3, 1, 11, 17, 0, 0, 0, 1, 0);


# Dump of table exp_upload_prefs
# ------------------------------------------------------------

DELETE FROM `exp_upload_prefs`;

LOCK TABLES `exp_upload_prefs` WRITE;
/*!40000 ALTER TABLE `exp_upload_prefs` DISABLE KEYS */;

INSERT INTO `exp_upload_prefs` (`id`, `site_id`, `name`, `server_path`, `url`, `allowed_types`, `default_modal_view`, `max_size`, `max_height`, `max_width`, `properties`, `pre_format`, `post_format`, `file_properties`, `file_pre_format`, `file_post_format`, `cat_group`, `batch_location`, `module_id`)
VALUES
	(1,1,'Main Upload Directory','{base_path}/images/uploads/','/images/uploads/','all','list','','','','style=\"border: 0;\" alt=\"image\"','','','','','',NULL,NULL,0),
	(2,1,'About','{base_path}/images/about/','/images/about/','img','list','','','','','','','','','',NULL,NULL,0),
	(3,1,'Avatars','{base_path}/images/avatars/','/images/avatars/','img','list','50','100','100',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,4),
	(4,1,'Default Avatars','{base_path}/images/avatars/default/','/images/avatars/default/','img','list','50','100','100',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,4),
	(5,1,'Signature Attachments','{base_path}/images/signature_attachments/','/images/signature_attachments/','img','list','30','80','480',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,4),
	(6,1,'PM Attachments','{base_path}/images/pm_attachments/','/images/pm_attachments/','img','list','250',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,4),
	(7,1,'Images','{base_path}/','{base_url}/','img','list',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0);

/*!40000 ALTER TABLE `exp_upload_prefs` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
