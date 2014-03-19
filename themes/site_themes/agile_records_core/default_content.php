<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

// Add Channels
$Q[] = "INSERT INTO `exp_channels`
(`channel_id`, `channel_name`, `channel_title`, `channel_url`, `channel_lang`, `total_entries`, `total_comments`, `last_entry_date`, `last_comment_date`, `cat_group`, `status_group`, `deft_status`, `field_group`, `search_excerpt`, `deft_category`, `deft_comments`, `channel_require_membership`, `channel_max_chars`, `channel_html_formatting`, `channel_allow_img_urls`, `channel_auto_link_urls`, `channel_notify`, `channel_notify_emails`, `comment_url`, `comment_system_enabled`, `comment_require_membership`, `comment_use_captcha`, `comment_moderate`, `comment_max_chars`, `comment_timelock`, `comment_require_email`, `comment_text_formatting`, `comment_html_formatting`, `comment_allow_img_urls`, `comment_auto_link_urls`, `comment_notify`, `comment_notify_authors`, `comment_notify_emails`, `comment_expiration`, `search_results_url`, `show_button_cluster`, `rss_url`, `enable_versioning`, `max_revisions`, `default_entry_title`, `url_title_prefix`, `live_look_template`) VALUES
('1', 'news', 'News', '".$this->userdata['site_url'].$this->userdata['site_index']."/news', 'en', 3, 0, '{$this->now}', 0, '1', 1, 'open', 1, 2, '', 'y', 'y', 0, 'all', 'y', 'y', 'n', '', '".$this->userdata['site_url'].$this->userdata['site_index']."/news/comments', 'y', 'n', 'y', 'n', 0, 0, 'y', 'xhtml', 'safe', 'n', 'y', 'n', 'n', '', 0, '".$this->userdata['site_url'].$this->userdata['site_index']."/news/comments', 'y', '', 'n', 10, '', '', 0),
('2', 'about', 'Information Pages', '".$this->userdata['site_url'].$this->userdata['site_index']."/about', 'en', 7, 0, '{$this->now}', 0, '2', 1, 'open', 2, 7, '', 'y', 'y', 0, 'all', 'y', 'n', 'n', '', '".$this->userdata['site_url'].$this->userdata['site_index']."/news/comments', 'y', 'n', 'y', 'n', 0, 0, 'y', 'xhtml', 'safe', 'n', 'y', 'n', 'n', '', 0, '".$this->userdata['site_url'].$this->userdata['site_index']."/news/comments', 'y', '', 'n', 10, '', '', 0)";

// Add Field Groups
$Q[] = "INSERT INTO `exp_field_groups` (`group_id`, `site_id`, `group_name`) VALUES
(1, 1, 'News'),
(2, 1, 'About')";


// Add Custom Fields
$Q[] = "INSERT INTO `exp_channel_fields` (`field_id`, `site_id`, `group_id`, `field_name`, `field_label`, `field_instructions`, `field_type`, `field_list_items`, `field_pre_populate`, `field_pre_channel_id`, `field_pre_field_id`, `field_ta_rows`, `field_maxl`, `field_required`, `field_text_direction`, `field_search`, `field_is_hidden`, `field_fmt`, `field_show_fmt`, `field_order`, `field_settings`, `field_content_type`) VALUES
(1, 1, 1, 'news_body', 'Body', '', 'textarea', '', 'n', 0, 0, 10, 0, 'n', 'ltr', 'y', 'n', 'xhtml', 'y', 2, 'YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToieSI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJ5IjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToieSI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToieSI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6InkiO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6InkiO30=', 'any'),
(2, 1, 1, 'news_extended', 'Extended text', '', 'textarea', '', 'n', 0, 0, 12, 0, 'n', 'ltr', 'n', 'y', 'xhtml', 'y', 3, 'YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToieSI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJ5IjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToieSI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToieSI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6InkiO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6InkiO30=', 'any'),
(3, 1, 1, 'news_image', 'News Image', '', 'file', '', 'n', 0, 0, 6, 128, 'n', 'ltr', 'n', 'n', 'none', 'n', 3, 'YTo3OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO3M6MTg6ImZpZWxkX2NvbnRlbnRfdHlwZSI7czo1OiJpbWFnZSI7fQ==', 'any'),
(4, 1, 2, 'about_body', 'Body', '', 'textarea', '', 'n', 0, 0, 6, 128, 'n', 'ltr', 'n', 'n', 'xhtml', 'y', 4, 'YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToieSI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJ5IjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToieSI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToieSI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6InkiO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6InkiO30=', 'any'),
(5, 1, 2, 'about_image', 'Image', 'URL Only', 'file', '', 'n', 0, 0, 6, 128, 'n', 'ltr', 'n', 'n', 'none', 'n', 5, 'YTo3OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO3M6MTg6ImZpZWxkX2NvbnRlbnRfdHlwZSI7czo1OiJpbWFnZSI7fQ==', 'any'),
(6, 1, 2, 'about_staff_title', 'Staff Member''s Title', 'This is the Title that the staff member has within the company.  Example: CEO', 'text', '', 'n', 0, 0, 6, 128, 'n', 'ltr', 'y', 'n', 'none', 'n', 6, 'YTo4OntzOjE4OiJmaWVsZF9jb250ZW50X3RleHQiO2I6MDtzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO3M6MTg6ImZpZWxkX2NvbnRlbnRfdHlwZSI7czozOiJhbnkiO30=', 'any'),
(7, 1, 2, 'about_extended', 'Extended', '', 'textarea', '', 'n', 0, 0, 6, 128, 'n', 'ltr', 'y', 'y', 'xhtml', 'y', 7, 'YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToieSI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJ5IjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToieSI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToieSI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6InkiO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6InkiO30=', 'any')";

// Add columns to data and formatting tables
foreach (array(1,2,3,4,5,6,7) as $id)
{
	$Q[] = "ALTER TABLE `exp_channel_data` ADD COLUMN `field_id_{$id}` text NULL";
	$Q[] = "ALTER TABLE `exp_channel_data` ADD COLUMN `field_ft_{$id}` tinytext NULL";
	$Q[] = "INSERT INTO exp_field_formatting (field_id, field_fmt) VALUES ({$id}, 'none')";
	$Q[] = "INSERT INTO exp_field_formatting (field_id, field_fmt) VALUES ({$id}, 'br')";
	$Q[] = "INSERT INTO exp_field_formatting (field_id, field_fmt) VALUES ({$id}, 'xhtml')";
	$Q[] = "INSERT INTO exp_field_formatting (field_id, field_fmt) VALUES ({$id}, 'markdown')";
}

// Create default categories
$Q[] = "INSERT INTO `exp_category_groups` (`group_id`, `site_id`, `group_name`, `sort_order`, `field_html_formatting`, `can_edit_categories`, `can_delete_categories`) VALUES
(1, 1, 'News Categories', 'a', 'all', '', ''),
(2, 1, 'About', 'a', 'all', '', '')";


$Q[] = "INSERT INTO `exp_categories` (`cat_id`, `site_id`, `group_id`, `parent_id`, `cat_name`, `cat_url_title`, `cat_description`, `cat_image`, `cat_order`) VALUES
(1, 1, 1, 0, 'News', 'news', '', '', 2),
(2, 1, 1, 0, 'Bands', 'bands', '', '', 3),
(3, 1, 2, 0, 'Staff Bios', 'staff_bios', '', '', 2),
(4, 1, 2, 0, 'Site Info', 'site_info', '', '', 1)";


$Q[] = "INSERT INTO `exp_category_field_data` (`cat_id`, `site_id`, `group_id`) VALUES
(1, 1, 1),
(2, 1, 1),
(3, 1, 2),
(4, 1, 2)";

// Add Agile Specific Custom Status
$Q[] = "INSERT INTO `exp_statuses` (`status_id`, `site_id`, `group_id`, `status`, `status_order`, `highlight`) VALUES
(3, 1, 1, 'Featured', 3, '000000')";

// Add default entries
$Q[] = "INSERT INTO `exp_channel_titles` (`entry_id`, `channel_id`, `author_id`, `ip_address`, `title`, `url_title`, `status`, `entry_date`, `year`, `month`, `day`, `edit_date`) VALUES
(1, 1, 1, '".$this->input->ip_address()."', 'Getting to Know ExpressionEngine', 'getting_to_know_expressionengine', 'open', '".($this->now - 1)."', '".$this->year."', '".$this->month."', '".$this->day."', '".date("YmdHis")."'),
(2, 1, 1, '".$this->input->ip_address()."', 'Welcome to the Example Site!', 'welcome_to_the_example_site', 'open', '".$this->now."', '".$this->year."', '".$this->month."', '".$this->day."', '".date("YmdHis")."'),
(3, 2, 1, '".$this->input->ip_address()."', 'About the Label', 'about_the_label', 'open', '".$this->now."', '".$this->year."', '".$this->month."', '".$this->day."', '".date("YmdHis")."'),
(4, 2, 1, '".$this->input->ip_address()."', 'Randell', 'randell', 'open', '".$this->now."', '".$this->year."', '".$this->month."', '".$this->day."', '".date("YmdHis")."'),
(5, 2, 1, '".$this->input->ip_address()."', 'Chloe', 'chloe', 'open', '".$this->now."', '".$this->year."', '".$this->month."', '".$this->day."', '".date("YmdHis")."'),
(6, 2, 1, '".$this->input->ip_address()."', 'Howard', 'howard', 'open', '".$this->now."', '".$this->year."', '".$this->month."', '".$this->day."', '".date("YmdHis")."'),
(7, 2, 1, '".$this->input->ip_address()."', 'Jane', 'jane', 'open', '".$this->now."', '".$this->year."', '".$this->month."', '".$this->day."', '".date("YmdHis")."'),
(8, 2, 1, '".$this->input->ip_address()."', 'Josh', 'josh', 'open', '".$this->now."', '".$this->year."', '".$this->month."', '".$this->day."', '".date("YmdHis")."'),
(9, 2, 1, '".$this->input->ip_address()."', 'Jason', 'jason', 'open', '".$this->now."', '".$this->year."', '".$this->month."', '".$this->day."', '".date("YmdHis")."'),
(10, 1, 1, '".$this->input->ip_address()."', 'Band Title', 'band_title', 'Featured', '".$this->now."', '".$this->year."', '".$this->month."', '".$this->day."', '".date("YmdHis")."')";

$Q[] = "INSERT INTO `exp_channel_data` (`entry_id`, `site_id`, `channel_id`, `field_id_1`, `field_ft_1`, `field_id_2`, `field_ft_2`, `field_id_3`, `field_ft_3`, `field_id_4`, `field_ft_4`, `field_id_5`, `field_ft_5`, `field_id_6`, `field_ft_6`, `field_id_7`, `field_ft_7`) VALUES
(1, 1, 1, '".$this->db->escape_str($this->schema->default_entry)."', 'xhtml', '', 'xhtml', '{filedir_2}ee_banner_120_240.gif', 'none', '', 'xhtml', '', 'none', '', 'none', '', 'xhtml'),
(2, 1, 1, 'Welcome to Agile Records, our Example Site.  Here you will be able to learn ExpressionEngine through a real site, with real features and in-depth comments to assist you along the way.\n\n', 'xhtml', '', 'xhtml', '{filedir_2}map.jpg', 'none', '', 'xhtml', '', 'none', '', 'none', '', 'xhtml'),
(3, 1, 2, '', NULL, '', NULL, '', NULL, 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Duis congue accumsan tellus. Aliquam diam arcu, suscipit eu, condimentum sed, ultricies accumsan, massa.\n', 'xhtml', '{filedir_2}map2.jpg', 'none', '', 'none', 'Donec et ante. Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Vestibulum dignissim dolor nec erat dictum posuere. Vivamus lacinia, quam id fringilla dapibus, ante ante bibendum nulla, a ornare nisl est congue purus. Duis pulvinar vehicula diam.\n\nSed vehicula. Praesent vitae nisi. Phasellus molestie, massa sed varius ultricies, dolor lectus interdum felis, ut porta eros nibh at magna. Cras aliquam vulputate lacus. Nullam tempus vehicula mi. Quisque posuere, erat quis iaculis consequat, tortor ipsum varius mauris, sit amet pulvinar nibh mauris sed lectus. Cras vitae arcu sit amet nunc luctus molestie. Nam neque orci, tincidunt non, semper convallis, sodales fringilla, nulla. Donec non nunc. Sed condimentum urna hendrerit erat. Curabitur in felis in neque fermentum interdum.\n\nProin magna. In in orci. Curabitur at lectus nec arcu vehicula bibendum. Duis euismod sollicitudin augue. Maecenas auctor cursus odio.\n', 'xhtml'),
(4, 1, 2, '', NULL, '', NULL, '', NULL, '', 'xhtml', '{filedir_2}staff_randell.png', 'none', 'Co-Owner/Label Manager', 'none', '', 'xhtml'),
(5, 1, 2, '', NULL, '', NULL, '', NULL, '', 'xhtml', '{filedir_2}staff_chloe.png', 'none', 'Co-Owner / Press &amp; Marketing', 'none', '', 'xhtml'),
(6, 1, 2, '', NULL, '', NULL, '', NULL, '', 'xhtml', '{filedir_2}staff_howard.png', 'none', 'Tours/Publicity/PR', 'none', '', 'xhtml'),
(7, 1, 2, '', NULL, '', NULL, '', NULL, '', 'xhtml', '{filedir_2}staff_jane.png', 'none', 'Sales/Accounts', 'none', '', 'xhtml'),
(8, 1, 2, '', NULL, '', NULL, '', NULL, '', 'xhtml', '{filedir_2}staff_josh.png', 'none', 'Product Manager', 'none', '', 'xhtml'),
(9, 1, 2, '', NULL, '', NULL, '', NULL, '', 'xhtml', '{filedir_2}staff_jason.png', 'none', 'Graphic/Web Designer', 'none', '', 'xhtml'),
(10, 1, 1, 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Proin congue mi a sapien. Duis augue erat, fringilla ac, volutpat ut, venenatis vitae, nisl. Phasellus lorem. Praesent mi. Suspendisse imperdiet felis a libero. uspendisse placerat tortor in ligula vestibulum vehicula.\n', 'xhtml', '', 'xhtml', '{filedir_2}testband300.jpg', 'none', '', NULL, '', NULL, '', NULL, '', NULL)";

$Q[] = "INSERT INTO `exp_category_posts` (`entry_id`, `cat_id`) VALUES
(1, 1),
(2, 1),
(3, 4),
(4, 3),
(5, 3),
(6, 3),
(7, 3),
(8, 3),
(9, 3),
(10, 2)";

$upload_path = addslashes(realpath($this->userdata['avatar_path'] . '../' . $this->userdata['upload_folder']) . DIRECTORY_SEPARATOR);

$agile_upload_path = addslashes(realpath($this->theme_path."agile_records/images/uploads") . DIRECTORY_SEPARATOR);

// Add upload locations
$Q[] = "INSERT INTO `exp_upload_prefs` (`id`, `site_id`, `name`, `server_path`, `url`, `allowed_types`, `max_size`, `max_height`, `max_width`, `properties`, `pre_format`, `post_format`, `file_properties`, `file_pre_format`, `file_post_format`) VALUES
(1, 1, 'Main Upload Directory', '".$upload_path."', '".$this->userdata['site_url'].'images/'.$this->userdata['upload_folder']."', 'all', '', '', '', 'style=\"border: 0;\" alt=\"image\"', '', '', '', '', ''),
(2, 1, 'About', '{$agile_upload_path}', '".$this->userdata['site_url']."themes/site_themes/agile_records/images/uploads/', 'img', '', '', '', '', '', '', '', '', '')";

@chmod($agile_upload_path, DIR_WRITE_MODE);

// Add files

$Q[] = "INSERT INTO `exp_files` (`file_id`, `site_id`, `title`, `upload_location_id`, `rel_path`, `mime_type`, `file_name`, `file_size`, `uploaded_by_member_id`, `upload_date`, `modified_by_member_id`, `modified_date`, `file_hw_original`)
VALUES(1, 1, 'staff_jane.png', 2, 'staff_jane.png', 'image/png', 'staff_jane.png', 51612, 1, 1302889304, 1, 1302889304, ''),
(2, 1, 'staff_jason.png', 2, 'staff_jason.png', 'image/png', 'staff_jason.png', 51430, 1, 1302889304, 1, 1302889304, ''),
(3, 1, 'staff_josh.png', 2, 'staff_josh.png', 'image/png', 'staff_josh.png', 50638, 1, 1302889304, 1, 1302889304, ''),
(4, 1, 'staff_randell.png', 2, 'staff_randell.png', 'image/png', 'staff_randell.png', 51681, 1, 1302889304, 1, 1302889304, ''),
(5, 1, 'ee_banner_120_240.gif', 2, 'ee_banner_120_240.gif', 'image/gif', 'ee_banner_120_240.gif', 9257, 1, 1302889304, 1, 1302889304, ''),
(6, 1, 'testband300.jpg', 2, 'testband300.jpg', 'image/jpeg', 'testband300.jpg', 23986, 1, 1302889304, 1, 1302889304, ''),
(7, 1, 'map.jpg', 2, 'map.jpg', 'image/jpeg', 'map.jpg', 71299, 1, 1302889304, 1, 1302889304, ''),
(8, 1, 'map2.jpg', 2, 'map2.jpg', 'image/jpeg', 'map2.jpg', 49175, 1, 1302889304, 1, 1302889304, ''),
(9, 1, 'staff_chloe.png', 2, 'staff_chloe.png', 'image/png', 'staff_chloe.png', 50262, 1, 1302889304, 1, 1302889304, ''),
(10, 1, 'staff_howard.png', 2, 'staff_howard.png', 'image/png', 'staff_howard.png', 51488, 1, 1302889304, 1, 1302889304, '')";

$Q[] = "UPDATE exp_members SET total_entries = '10', last_entry_date = '$this->now'";


foreach ($Q as $sql)
{
	$this->db->query($sql);
}

// Set the Member profile theme default, and Strict 404 settings
$this->config->update_site_prefs(array(
	'member_theme'	=> 'agile_records',
	'strict_urls'	=> 'y',
	'site_404'		=> 'about/404'
	),
	1 // site id
);

/* End of file default_content.php */
/* Location: ./themes/site_themes/agile_records/default_content.php */
