<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');


// Default channel preference data

$Q[] = "INSERT INTO exp_channels (cat_group, channel_name, channel_title, channel_url, comment_url, search_results_url, channel_lang, total_entries, last_entry_date, status_group, deft_status, field_group, deft_comments, comment_max_chars, comment_require_email, comment_require_membership, channel_require_membership, comment_text_formatting, search_excerpt)  values ('1', 'default_site', 'Default Site Channel', '".$this->userdata['site_url'].$this->userdata['site_index']."/site/index/', '".$this->userdata['site_url'].$this->userdata['site_index']."/site/comments/', '".$this->userdata['site_url'].$this->userdata['site_index']."/site/comments/', 'en', '1', '$this->now', '1', 'open', '1', 'y', '5000', 'y', 'n', 'y', 'xhtml', '2')";

// Custom field and field group data

$Q[] = "INSERT INTO exp_field_groups(group_name) VALUES ('Default Field Group')";

$Q[] = "INSERT INTO exp_channel_fields(group_id, field_name, field_label, field_type, field_list_items, field_ta_rows, field_search, field_order, field_is_hidden) VALUES ('1', 'summary', 'Summary', 'textarea', '', '6', 'n', '1', 'y')";
$Q[] = "INSERT INTO exp_channel_fields(group_id, field_name, field_label, field_type, field_list_items, field_ta_rows, field_search, field_order, field_is_hidden) VALUES ('1', 'body', 'Body', 'textarea', '', '10', 'y', '2', 'n')";
$Q[] = "INSERT INTO exp_channel_fields(group_id, field_name, field_label, field_type, field_list_items, field_ta_rows, field_search, field_order, field_is_hidden) VALUES ('1', 'extended', 'Extended text', 'textarea', '', '12', 'n', '3', 'y')";

// Add columns to data and formatting tables
foreach (array(1,2,3) as $id)
{
	$Q[] = "ALTER TABLE `exp_channel_data` ADD COLUMN `field_id_{$id}` text NULL";
	$Q[] = "ALTER TABLE `exp_channel_data` ADD COLUMN `field_ft_{$id}` tinytext NULL";
	$Q[] = "INSERT INTO exp_field_formatting (field_id, field_fmt) VALUES ({$id}, 'none')";
	$Q[] = "INSERT INTO exp_field_formatting (field_id, field_fmt) VALUES ({$id}, 'br')";
	$Q[] = "INSERT INTO exp_field_formatting (field_id, field_fmt) VALUES ({$id}, 'xhtml')";
	$Q[] = "INSERT INTO exp_field_formatting (field_id, field_fmt) VALUES ({$id}, 'markdown')";
}

// categories
$Q[] = "INSERT INTO exp_category_groups (group_name) VALUES ('Default Category Group')";

$Q[] = "INSERT INTO exp_categories (cat_id, group_id, parent_id, cat_name, cat_url_title, cat_order) VALUES ('1', '1', '0', 'Blogging', 'Blogging', '1')";
$Q[] = "INSERT INTO exp_categories (cat_id, group_id, parent_id, cat_name, cat_url_title, cat_order) VALUES ('2', '1', '0', 'News', 'News', '2')";
$Q[] = "INSERT INTO exp_categories (cat_id, group_id, parent_id, cat_name, cat_url_title, cat_order) VALUES ('3', '1', '0', 'Personal', 'Personal', '3')";

$Q[] = "INSERT INTO exp_category_field_data (cat_id, group_id, site_id) VALUES ('1', '1', '1')";
$Q[] = "INSERT INTO exp_category_field_data (cat_id, group_id, site_id) VALUES ('2', '1', '1')";
$Q[] = "INSERT INTO exp_category_field_data (cat_id, group_id, site_id) VALUES ('3', '1', '1')";

$Q[] = "INSERT INTO exp_category_posts (entry_id, cat_id) VALUES ('1', '1')";

// Create a default channel entry
$Q[] = "INSERT INTO exp_channel_titles (channel_id, author_id, ip_address, entry_date, edit_date, year, month, day, title, url_title, status) VALUES ('1', '1',  '".$this->input->ip_address()."', '".$this->now."', '".date("YmdHis")."', '".$this->year."', '".$this->month."', '".$this->day."', 'Getting Started with ExpressionEngine', 'getting_started', 'open')";
$Q[] = "INSERT INTO exp_channel_data (entry_id, channel_id, field_id_1, field_ft_1, field_id_2, field_ft_2, field_id_3, field_ft_3) VALUES ('1', '1', '', 'xhtml', '".$this->db->escape_str($this->schema->default_entry)."', 'xhtml', '', 'xhtml')";

if (@realpath(str_replace('../', './', $this->userdata['image_path'])) !== FALSE)
{
	$this->userdata['image_path'] = str_replace('../', './', $this->userdata['image_path']);
	$this->userdata['image_path'] = str_replace("\\", "/", realpath($this->userdata['image_path'])).'/';
}

$props = 'style="border: 0;" alt="image"';
$Q[] = "INSERT INTO exp_upload_prefs (name, server_path, url, allowed_types, properties) 
		VALUES ('Main Upload Directory', '".$this->userdata['image_path'].$this->userdata['upload_folder']."', '".$this->userdata['site_url'].'images/'.$this->userdata['upload_folder']."', 'all', '$props')";		

foreach ($Q as $sql)
{
	$this->db->query($sql);
}

/* End of file default_content.php */
/* Location: ./themes/site_themes/beta_classic
* /default_content.php */
