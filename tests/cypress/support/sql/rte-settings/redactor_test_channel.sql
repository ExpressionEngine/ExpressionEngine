-- Runtime Redactor fixture for Cypress RTE tests.
-- Idempotent: safe to run multiple times after db:seed.

SET @fixture_site_id := 1;
SET @fixture_author_id := 1;
SET @fixture_channel_name := 'redactor_test';
SET @fixture_channel_title := 'RedactorTest';
SET @fixture_field_group_name := 'Redactor Test Group';
SET @fixture_field_group_short_name := 'redactor_test_group';
SET @fixture_field_name := 'redactor_test_redactor';
SET @fixture_field_label := 'Redactor';
SET @fixture_entry_title := 'Redactor Fixture Entry';
SET @fixture_entry_url_title := 'redactor_test_entry';
SET @fixture_field_settings := 'YTo3OntzOjEwOiJ0b29sc2V0X2lkIjtzOjE6IjQiO3M6NToiZGVmZXIiO3M6MToibiI7czoxNDoiZGJfY29sdW1uX3R5cGUiO3M6NDoidGV4dCI7czoxNDoiZmllbGRfcmVxdWlyZWQiO3M6MToibiI7czoxMDoiZmllbGRfd2lkZSI7YjoxO3M6OToiZmllbGRfZm10IjtzOjQ6Im5vbmUiO3M6MTQ6ImZpZWxkX3Nob3dfZm10IjtzOjE6Im4iO30=';
SET @fixture_field_value := '<p>Redactor runtime fixture content</p>';
SET @fixture_now := UNIX_TIMESTAMP();
SET @fixture_edit_date := DATE_FORMAT(FROM_UNIXTIME(@fixture_now), '%Y%m%d%H%i%s');
SET @fixture_year := DATE_FORMAT(FROM_UNIXTIME(@fixture_now), '%Y');
SET @fixture_month := DATE_FORMAT(FROM_UNIXTIME(@fixture_now), '%m');
SET @fixture_day := DATE_FORMAT(FROM_UNIXTIME(@fixture_now), '%d');

SET @old_field_id := NULL;
SELECT `field_id`
INTO @old_field_id
FROM `exp_channel_fields`
WHERE `field_name` = @fixture_field_name
LIMIT 1;

SET @drop_old_field_table_sql := IF(
    @old_field_id IS NULL,
    'SELECT 1',
    CONCAT('DROP TABLE IF EXISTS `exp_channel_data_field_', @old_field_id, '`')
);
PREPARE stmt FROM @drop_old_field_table_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @old_channel_id := NULL;
SELECT `channel_id`
INTO @old_channel_id
FROM `exp_channels`
WHERE `channel_name` = @fixture_channel_name
LIMIT 1;

DELETE cd
FROM `exp_channel_data` AS cd
INNER JOIN `exp_channel_titles` AS ct
    ON ct.`entry_id` = cd.`entry_id`
WHERE ct.`channel_id` = @old_channel_id;

DELETE FROM `exp_channel_titles`
WHERE `channel_id` = @old_channel_id;

DELETE FROM `exp_channels_channel_field_groups`
WHERE `channel_id` = @old_channel_id;

DELETE FROM `exp_channels_statuses`
WHERE `channel_id` = @old_channel_id;

DELETE FROM `exp_channels`
WHERE `channel_id` = @old_channel_id;

DELETE FROM `exp_channel_field_groups_fields`
WHERE `field_id` = @old_field_id;

DELETE FROM `exp_channel_fields`
WHERE `field_id` = @old_field_id
   OR `field_name` = @fixture_field_name;

SET @old_group_id := NULL;
SELECT `group_id`
INTO @old_group_id
FROM `exp_field_groups`
WHERE `short_name` = @fixture_field_group_short_name
LIMIT 1;

DELETE FROM `exp_channel_field_groups_fields`
WHERE `group_id` = @old_group_id;

DELETE FROM `exp_channels_channel_field_groups`
WHERE `group_id` = @old_group_id;

DELETE FROM `exp_field_groups`
WHERE `group_id` = @old_group_id;

SELECT COALESCE(MAX(`group_id`), 0) + 1
INTO @new_group_id
FROM `exp_field_groups`;

INSERT INTO `exp_field_groups` (`group_id`, `site_id`, `group_name`, `short_name`)
VALUES (@new_group_id, @fixture_site_id, @fixture_field_group_name, @fixture_field_group_short_name);

SELECT COALESCE(MAX(`field_id`), 0) + 1
INTO @new_field_id
FROM `exp_channel_fields`;

INSERT INTO `exp_channel_fields` (
    `field_id`,
    `site_id`,
    `field_name`,
    `field_label`,
    `field_instructions`,
    `field_type`,
    `field_list_items`,
    `field_pre_populate`,
    `field_pre_channel_id`,
    `field_pre_field_id`,
    `field_ta_rows`,
    `field_maxl`,
    `field_required`,
    `field_text_direction`,
    `field_search`,
    `field_is_hidden`,
    `field_fmt`,
    `field_show_fmt`,
    `field_order`,
    `field_content_type`,
    `field_settings`,
    `legacy_field_data`
) VALUES (
    @new_field_id,
    @fixture_site_id,
    @fixture_field_name,
    @fixture_field_label,
    'Runtime fixture Redactor field',
    'rte',
    '',
    'n',
    0,
    0,
    6,
    0,
    'n',
    'ltr',
    'y',
    'n',
    'none',
    'n',
    1,
    'any',
    @fixture_field_settings,
    'n'
);

INSERT INTO `exp_channel_field_groups_fields` (`field_id`, `group_id`)
VALUES (@new_field_id, @new_group_id);

SELECT COALESCE(MAX(`channel_id`), 0) + 1
INTO @new_channel_id
FROM `exp_channels`;

INSERT INTO `exp_channels` (
    `channel_id`,
    `site_id`,
    `channel_name`,
    `channel_title`,
    `channel_url`,
    `channel_description`,
    `channel_lang`,
    `total_entries`,
    `total_records`,
    `total_comments`,
    `last_entry_date`,
    `last_comment_date`,
    `cat_group`,
    `deft_status`,
    `search_excerpt`,
    `deft_category`,
    `deft_comments`,
    `channel_require_membership`,
    `channel_max_chars`,
    `channel_html_formatting`,
    `channel_allow_img_urls`,
    `channel_auto_link_urls`,
    `channel_notify`,
    `channel_notify_emails`,
    `sticky_enabled`,
    `comment_url`,
    `comment_system_enabled`,
    `comment_require_membership`,
    `comment_moderate`,
    `comment_max_chars`,
    `comment_timelock`,
    `comment_require_email`,
    `comment_text_formatting`,
    `comment_html_formatting`,
    `comment_allow_img_urls`,
    `comment_auto_link_urls`,
    `comment_notify`,
    `comment_notify_authors`,
    `comment_notify_emails`,
    `comment_expiration`,
    `search_results_url`,
    `rss_url`,
    `enable_versioning`,
    `max_revisions`,
    `default_entry_title`,
    `title_field_label`,
    `url_title_prefix`,
    `preview_url`,
    `allow_preview`,
    `max_entries`
) VALUES (
    @new_channel_id,
    @fixture_site_id,
    @fixture_channel_name,
    @fixture_channel_title,
    'http://ee2/index.php/redactor-test',
    NULL,
    'en',
    0,
    0,
    0,
    0,
    0,
    '',
    'open',
    0,
    '',
    'y',
    'y',
    0,
    'all',
    'y',
    'n',
    'n',
    '',
    'n',
    'http://ee2/index.php/news/comments',
    'n',
    'n',
    'n',
    0,
    0,
    'y',
    'xhtml',
    'safe',
    'n',
    'y',
    'n',
    'n',
    '',
    0,
    'http://ee2/index.php/news/comments',
    '',
    'n',
    10,
    '',
    'Title',
    '',
    NULL,
    'y',
    0
);

INSERT INTO `exp_channels_channel_field_groups` (`channel_id`, `group_id`)
VALUES (@new_channel_id, @new_group_id);

SET @open_status_id := NULL;
SELECT `status_id`
INTO @open_status_id
FROM `exp_statuses`
WHERE `status` = 'open'
ORDER BY `status_id` ASC
LIMIT 1;
SET @open_status_id := COALESCE(@open_status_id, 1);

INSERT INTO `exp_channels_statuses` (`channel_id`, `status_id`)
SELECT @new_channel_id, @open_status_id
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1
    FROM `exp_channels_statuses`
    WHERE `channel_id` = @new_channel_id
      AND `status_id` = @open_status_id
);

SELECT COALESCE(MAX(`entry_id`), 0) + 1
INTO @new_entry_id
FROM `exp_channel_titles`;

INSERT INTO `exp_channel_titles` (
    `entry_id`,
    `site_id`,
    `channel_id`,
    `author_id`,
    `forum_topic_id`,
    `ip_address`,
    `title`,
    `url_title`,
    `status`,
    `status_id`,
    `versioning_enabled`,
    `view_count_one`,
    `view_count_two`,
    `view_count_three`,
    `view_count_four`,
    `allow_comments`,
    `sticky`,
    `entry_date`,
    `year`,
    `month`,
    `day`,
    `expiration_date`,
    `comment_expiration_date`,
    `edit_date`,
    `recent_comment_date`,
    `comment_total`
) VALUES (
    @new_entry_id,
    @fixture_site_id,
    @new_channel_id,
    @fixture_author_id,
    NULL,
    '127.0.0.1',
    @fixture_entry_title,
    @fixture_entry_url_title,
    'open',
    @open_status_id,
    'n',
    0,
    0,
    0,
    0,
    'y',
    'n',
    @fixture_now,
    @fixture_year,
    @fixture_month,
    @fixture_day,
    0,
    0,
    @fixture_edit_date,
    NULL,
    0
);

INSERT INTO `exp_channel_data` (
    `entry_id`,
    `site_id`,
    `channel_id`,
    `field_id_1`,
    `field_ft_1`,
    `field_id_2`,
    `field_ft_2`,
    `field_id_3`,
    `field_ft_3`,
    `field_id_4`,
    `field_ft_4`,
    `field_id_5`,
    `field_ft_5`,
    `field_id_6`,
    `field_ft_6`,
    `field_id_7`,
    `field_ft_7`
) VALUES (
    @new_entry_id,
    @fixture_site_id,
    @new_channel_id,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL
);

SET @drop_field_table_sql := CONCAT('DROP TABLE IF EXISTS `exp_channel_data_field_', @new_field_id, '`');
PREPARE stmt FROM @drop_field_table_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @create_field_table_sql := CONCAT(
    'CREATE TABLE IF NOT EXISTS `exp_channel_data_field_', @new_field_id, '` (',
    '`id` int(10) unsigned NOT NULL AUTO_INCREMENT,',
    '`entry_id` int(10) unsigned NOT NULL,',
    '`field_id_', @new_field_id, '` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,',
    '`field_ft_', @new_field_id, '` tinytext COLLATE utf8mb4_unicode_ci DEFAULT NULL,',
    'PRIMARY KEY (`id`),',
    'KEY `entry_id` (`entry_id`)',
    ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);
PREPARE stmt FROM @create_field_table_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @insert_field_data_sql := CONCAT(
    'INSERT INTO `exp_channel_data_field_', @new_field_id, '` ',
    '(`entry_id`, `field_id_', @new_field_id, '`, `field_ft_', @new_field_id, '`) VALUES (',
    @new_entry_id, ', ',
    QUOTE(@fixture_field_value), ', ',
    QUOTE('none'),
    ')'
);
PREPARE stmt FROM @insert_field_data_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `exp_channels`
SET
    `total_entries` = 1,
    `total_records` = 1,
    `last_entry_date` = @fixture_now
WHERE `channel_id` = @new_channel_id;
