INSERT INTO `exp_channels_channel_field_groups` (`channel_id`, `group_id`) VALUES
  (2, 3);

INSERT INTO `exp_channel_fields` (`field_id`, `site_id`, `field_name`, `field_label`, `field_instructions`, `field_type`, `field_list_items`, `field_pre_populate`, `field_pre_channel_id`, `field_pre_field_id`, `field_ta_rows`, `field_maxl`, `field_required`, `field_text_direction`, `field_search`, `field_is_hidden`, `field_fmt`, `field_show_fmt`, `field_order`, `field_content_type`, `field_settings`, `legacy_field_data`) VALUES (9, 0, 'file_grid', 'file grid', '', 'file_grid', '', 'n', NULL, NULL, 8, NULL, 'n', 'ltr', 'n', 'n', 'xhtml', 'y', 1, 'image', 'YTo1OntzOjEzOiJncmlkX21pbl9yb3dzIjtpOjA7czoxMzoiZ3JpZF9tYXhfcm93cyI7czowOiIiO3M6MTM6ImFsbG93X3Jlb3JkZXIiO3M6MToieSI7czoxODoiZmllbGRfY29udGVudF90eXBlIjtzOjU6ImltYWdlIjtzOjE5OiJhbGxvd2VkX2RpcmVjdG9yaWVzIjtzOjM6ImFsbCI7fQ==', 'n');

INSERT INTO `exp_channel_field_groups_fields` (`field_id`, `group_id`) VALUES
  (9, 3);

-- Dumping structure for table ee-test.exp_channel_data_field_9
DROP TABLE IF EXISTS `exp_channel_data_field_9`;
CREATE TABLE IF NOT EXISTS `exp_channel_data_field_9` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned NOT NULL,
  `field_id_9` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `field_ft_9` tinytext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table ee-test.exp_channel_data_field_9: ~0 rows (approximately)
DELETE FROM `exp_channel_data_field_9`;
/*!40000 ALTER TABLE `exp_channel_data_field_9` DISABLE KEYS */;
INSERT INTO `exp_channel_data_field_9` (`id`, `entry_id`, `field_id_9`, `field_ft_9`) VALUES
	(1, 5, NULL, 'xhtml');
/*!40000 ALTER TABLE `exp_channel_data_field_9` ENABLE KEYS */;

-- Dumping structure for table ee-test.exp_channel_grid_field_9
DROP TABLE IF EXISTS `exp_channel_grid_field_9`;
CREATE TABLE IF NOT EXISTS `exp_channel_grid_field_9` (
  `row_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned DEFAULT NULL,
  `row_order` int(10) unsigned DEFAULT NULL,
  `fluid_field_data_id` int(10) unsigned DEFAULT 0,
  `col_id_8` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `col_id_9` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `col_id_10` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `col_id_11` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `col_id_12` tinyint(4) NOT NULL DEFAULT 0,
  `col_id_13` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`row_id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Dumping data for table ee-test.exp_channel_grid_field_9: ~0 rows (approximately)
DELETE FROM `exp_channel_grid_field_9`;
/*!40000 ALTER TABLE `exp_channel_grid_field_9` DISABLE KEYS */;
INSERT INTO `exp_channel_grid_field_9` (`row_id`, `entry_id`, `row_order`, `fluid_field_data_id`, `col_id_8`, `col_id_9`, `col_id_10`, `col_id_11`, `col_id_12`, `col_id_13`) VALUES
	(1, 5, 0, 0, '{filedir_2}staff_chloe.png', 'text row 1', 'one', '<p><strong>rte row 1</strong></p>', 0, '1623090660'),
	(2, 5, 1, 0, '{filedir_2}map.jpg', 'text row 2', 'two', '<p><i>rte row 2</i></p>', 1, '1623350040');
/*!40000 ALTER TABLE `exp_channel_grid_field_9` ENABLE KEYS */;


-- Dumping data for table ee-test.exp_grid_columns: ~6 rows (approximately)
DELETE FROM `exp_grid_columns`;
/*!40000 ALTER TABLE `exp_grid_columns` DISABLE KEYS */;
INSERT INTO `exp_grid_columns` (`col_id`, `field_id`, `content_type`, `col_order`, `col_type`, `col_label`, `col_name`, `col_instructions`, `col_required`, `col_search`, `col_width`, `col_settings`) VALUES
	(8, 9, 'channel', 0, 'file', 'File', 'file', '', 'n', 'n', 0, '{"field_content_type":"image","allowed_directories":"all","show_existing":"","num_existing":0,"field_fmt":"none","field_required":"n"}'),
	(9, 9, 'channel', 1, 'text', 'text', 'text', '', 'n', 'n', 0, '{"field_maxl":"256","field_fmt":"none","field_text_direction":"ltr","field_content_type":"all","field_required":"n"}'),
	(10, 9, 'channel', 2, 'checkboxes', 'checkboxes', 'checkboxes', '', 'n', 'n', 0, '{"field_fmt":"none","field_pre_populate":"n","field_pre_channel_id":"0","field_pre_field_id":"0","field_list_items":"one\\ntwo","value_label_pairs":[],"field_required":"n"}'),
	(11, 9, 'channel', 3, 'rte', 'rte', 'rte', '', 'n', 'n', 0, '{"toolset_id":"1","defer":"n","db_column_type":"text","field_required":"n"}'),
	(12, 9, 'channel', 4, 'toggle', 'toggle', 'toggle', '', 'n', 'n', 0, '{"field_default_value":"0","field_required":"n"}'),
	(13, 9, 'channel', 5, 'date', 'date', 'date', '', 'n', 'n', 0, '{"localize":true,"field_required":"n"}');
/*!40000 ALTER TABLE `exp_grid_columns` ENABLE KEYS */;
