
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;



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




# Dump of table exp_channel_fields
# ------------------------------------------------------------

INSERT INTO `exp_channel_fields` (`field_id`, `site_id`, `field_name`, `field_label`, `field_instructions`, `field_type`, `field_list_items`, `field_pre_populate`, `field_pre_channel_id`, `field_pre_field_id`, `field_ta_rows`, `field_maxl`, `field_required`, `field_text_direction`, `field_search`, `field_is_hidden`, `field_fmt`, `field_show_fmt`, `field_order`, `field_content_type`, `field_settings`, `legacy_field_data`)
VALUES
	(10,1,'corpse','Corpse','','fluid_field','','n',NULL,NULL,8,NULL,'n','ltr','n','n','xhtml','y',19,'any','YToxOntzOjIwOiJmaWVsZF9jaGFubmVsX2ZpZWxkcyI7YToxMzp7aTowO3M6MToiOSI7aToxO3M6MjoiMTEiO2k6MjtzOjI6IjEyIjtpOjM7czoyOiIxMyI7aTo0O3M6MjoiMTciO2k6NTtzOjI6IjE0IjtpOjY7czoyOiIxNSI7aTo3O3M6MjoiMTYiO2k6ODtzOjI6IjIzIjtpOjk7czoyOiIxOCI7aToxMDtzOjI6IjE5IjtpOjExO3M6MjoiMjEiO2k6MTI7czoyOiIyMiI7fX0=','n'),
	(19,1,'stupid_grid','Stupid Grid','','grid','','n',NULL,NULL,8,NULL,'n','ltr','n','n','xhtml','y',9,'any','YToyOntzOjEzOiJncmlkX21pbl9yb3dzIjtpOjA7czoxMzoiZ3JpZF9tYXhfcm93cyI7czowOiIiO30=','n');

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


INSERT INTO `exp_grid_columns` (`col_id`, `field_id`, `content_type`, `col_order`, `col_type`, `col_label`, `col_name`, `col_instructions`, `col_required`, `col_search`, `col_width`, `col_settings`)
VALUES
	(1,19,'channel',0,'text','Text one','text_one','','n','y',0,'{\"field_maxl\":\"256\",\"field_fmt\":\"none\",\"field_text_direction\":\"ltr\",\"field_content_type\":\"all\",\"field_required\":\"n\"}'),
	(2,19,'channel',1,'text','Text two','text_two','','n','y',0,'{\"field_maxl\":\"256\",\"field_fmt\":\"none\",\"field_text_direction\":\"ltr\",\"field_content_type\":\"all\",\"field_required\":\"n\"}');

/*!40000 ALTER TABLE `exp_grid_columns` ENABLE KEYS */;





/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
