<?php

/*
=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 http://expressionengine.com/
-----------------------------------------------------
 Copyright (c) 2003 - 2009, EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 http://expressionengine.com/docs/license.html
=====================================================
 File: mcp.gallery.php
-----------------------------------------------------
 Purpose: Photo Gallery Module - CP
=====================================================
*/
if ( ! defined('EXT'))
{
	exit('Invalid file request');
}



class Gallery_upd {

	var $version 		= '2.0';
	
	function Gallery_upd()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */	
	function install()
	{
		$sql[] = "INSERT INTO exp_modules (module_name, module_version, has_cp_backend) VALUES ('Gallery', '$this->version', 'y')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Gallery', 'insert_new_comment')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Gallery', 'delete_comment_notification')";
	
		$sql[] = "CREATE TABLE IF NOT EXISTS exp_galleries (
					gallery_id int(4) unsigned NOT NULL auto_increment,
					gallery_full_name varchar(80) NOT NULL,
					gallery_short_name varchar(20) NOT NULL,
					gallery_url varchar(100) NOT NULL,
					gallery_sort_order char(1) NOT NULL default 'a',
					gallery_upload_folder varchar(50) NOT NULL,
					gallery_upload_path varchar(150) NOT NULL,
					gallery_image_protocal varchar(12) NOT NULL,
					gallery_image_lib_path varchar(150) NOT NULL,
					gallery_image_url varchar(100) NOT NULL,
					gallery_batch_folder varchar(80) NOT NULL,
					gallery_batch_path varchar(150) NOT NULL,
					gallery_batch_url varchar(100) NOT NULL,
					gallery_maintain_ratio char(1) NOT NULL default 'y',
					gallery_create_thumb char(1) NOT NULL default 'y',
					gallery_thumb_width int(4) unsigned NOT NULL,
					gallery_thumb_height int(4) unsigned NOT NULL,
					gallery_thumb_quality int(3) unsigned NOT NULL,
					gallery_thumb_prefix varchar(30) NOT NULL,
					gallery_create_medium char(1) NOT NULL default 'y',
					gallery_medium_width int(4) unsigned NOT NULL,
					gallery_medium_height int(4) unsigned NOT NULL,
					gallery_medium_quality int(3) unsigned NOT NULL,
					gallery_medium_prefix varchar(30) NOT NULL,
					gallery_wm_type char(1) NOT NULL default 'n',
					gallery_wm_image_path varchar(150) NOT NULL,
					gallery_wm_test_image_path varchar(150) NOT NULL,
					gallery_wm_use_font char(1) NOT NULL default 'y',
					gallery_wm_font varchar(30) NOT NULL,
					gallery_wm_font_size int(3) unsigned NOT NULL,
					gallery_wm_text varchar(100) NOT NULL,
					gallery_wm_vrt_alignment char(1) NOT NULL default 'T',
					gallery_wm_hor_alignment char(1) NOT NULL default 'L',
					gallery_wm_padding int(3) unsigned NOT NULL,
					gallery_wm_opacity int(3) unsigned NOT NULL,
					gallery_wm_x_offset int(4) unsigned NOT NULL,
					gallery_wm_y_offset int(4) unsigned NOT NULL,
					gallery_wm_x_transp int(4) NOT NULL,
					gallery_wm_y_transp int(4) NOT NULL,
					gallery_wm_text_color varchar(7) NOT NULL,
					gallery_wm_use_drop_shadow char(1) NOT NULL default 'y',
					gallery_wm_shadow_distance int(3) unsigned NOT NULL,
					gallery_wm_shadow_color varchar(7) NOT NULL,
					gallery_wm_apply_to_thumb char(1) NOT NULL default 'n',
					gallery_wm_apply_to_medium char(1) NOT NULL default 'n',
					gallery_text_formatting char(10) NOT NULL default 'xhtml',
					gallery_auto_link_urls char(1) NOT NULL default 'y',
					gallery_comment_url varchar(100) NOT NULL,
					gallery_comment_require_membership char(1) NOT NULL default 'n',
					gallery_comment_use_captcha char(1) NOT NULL default 'n',
					gallery_comment_moderate char(1) NOT NULL default 'n',
					gallery_comment_max_chars int(5) unsigned NOT NULL,
					gallery_comment_timelock int(5) unsigned NOT NULL default '0',
					gallery_comment_require_email char(1) NOT NULL default 'y',
					gallery_comment_text_formatting char(5) NOT NULL default 'xhtml',
					gallery_comment_html_formatting char(4) NOT NULL default 'safe',
					gallery_comment_allow_img_urls char(1) NOT NULL default 'n',
					gallery_comment_auto_link_urls char(1) NOT NULL default 'y',
					gallery_comment_notify char(1) NOT NULL default 'n',
					gallery_comment_notify_authors char(1) NOT NULL default 'n',
					gallery_comment_notify_emails varchar(255) NOT NULL,
					gallery_comment_expiration int(4) unsigned NOT NULL default '0',
					gallery_allow_comments char(1) NOT NULL default 'y',
					gallery_cf_one char(1) NOT NULL default 'n',
					gallery_cf_one_type char(1) NOT NULL default 'i',
					gallery_cf_one_label varchar(80) NOT NULL,
					gallery_cf_one_list text NOT NULL,
					gallery_cf_one_rows tinyint(2) default '8',
					gallery_cf_one_formatting char(10) NOT NULL default 'xhtml',
					gallery_cf_one_auto_link char(1) NOT NULL default 'y',
					gallery_cf_one_searchable char(1) NOT NULL default 'y',
					gallery_cf_two char(1) NOT NULL default 'n',
					gallery_cf_two_label varchar(80) NOT NULL,
					gallery_cf_two_type char(1) NOT NULL default 'i',
					gallery_cf_two_list text NOT NULL,
					gallery_cf_two_rows tinyint(2) default '8',
					gallery_cf_two_formatting char(10) NOT NULL default 'xhtml',
					gallery_cf_two_auto_link char(1) NOT NULL default 'y',
					gallery_cf_two_searchable char(1) NOT NULL default 'y',
					gallery_cf_three char(1) NOT NULL default 'n',
					gallery_cf_three_label varchar(80) NOT NULL,
					gallery_cf_three_type char(1) NOT NULL default 'i',
					gallery_cf_three_list text NOT NULL,
					gallery_cf_three_rows tinyint(2) default '8',
					gallery_cf_three_formatting char(10) NOT NULL default 'xhtml',
					gallery_cf_three_auto_link char(1) NOT NULL default 'y',
					gallery_cf_three_searchable char(1) NOT NULL default 'y',
					gallery_cf_four char(1) NOT NULL default 'n',
					gallery_cf_four_label varchar(80) NOT NULL,
					gallery_cf_four_type char(1) NOT NULL default 'i',
					gallery_cf_four_list text NOT NULL,
					gallery_cf_four_rows tinyint(2) default '8',
					gallery_cf_four_formatting char(10) NOT NULL default 'xhtml',
					gallery_cf_four_auto_link char(1) NOT NULL default 'y',
					gallery_cf_four_searchable char(1) NOT NULL default 'y',
					gallery_cf_five char(1) NOT NULL default 'n',
					gallery_cf_five_label varchar(80) NOT NULL,
					gallery_cf_five_type char(1) NOT NULL default 'i',
					gallery_cf_five_list text NOT NULL,
					gallery_cf_five_rows tinyint(2) default '8',
					gallery_cf_five_formatting char(10) NOT NULL default 'xhtml',
					gallery_cf_five_auto_link char(1) NOT NULL default 'y',
					gallery_cf_five_searchable char(1) NOT NULL default 'y',
					gallery_cf_six char(1) NOT NULL default 'n',
					gallery_cf_six_label varchar(80) NOT NULL,
					gallery_cf_six_type char(1) NOT NULL default 'i',
					gallery_cf_six_list text NOT NULL,
					gallery_cf_six_rows tinyint(2) default '8',
					gallery_cf_six_formatting char(10) NOT NULL default 'xhtml',
					gallery_cf_six_auto_link char(1) NOT NULL default 'y',
					gallery_cf_six_searchable char(1) NOT NULL default 'y',
					PRIMARY KEY `gallery_id` (`gallery_id`)
				)";	
	
		$sql[] = "CREATE TABLE IF NOT EXISTS exp_gallery_entries (
					entry_id int(10) unsigned NOT NULL auto_increment,
					gallery_id int(4) unsigned NOT NULL,
					cat_id int(6) unsigned NOT NULL,
					author_id int(10) unsigned NOT NULL default '0',
					filename varchar(100) NOT NULL,
					extension varchar(20) NOT NULL,
					title varchar(100) NOT NULL,
					caption text NOT NULL,
					custom_field_one text NOT NULL,
					custom_field_two text NOT NULL,
					custom_field_three text NOT NULL,
					custom_field_four text NOT NULL,
					custom_field_five text NOT NULL,
					custom_field_six text NOT NULL,
					width int(5) unsigned NOT NULL,
					height int(5) unsigned NOT NULL,
					t_width int(5) unsigned NOT NULL,
					t_height int(5) unsigned NOT NULL,
					m_width int(5) unsigned NOT NULL,
					m_height int(5) unsigned NOT NULL,
					status char(1) NOT NULL default 'o',
					entry_date int(10) NOT NULL,
					edit_date timestamp(14),
					allow_comments char(1) NOT NULL default 'y',
					recent_comment_date int(10) NOT NULL,
					total_comments int(4) unsigned NOT NULL default '0',
					comment_expiration_date int(10) NOT NULL default '0',
					views int(10) unsigned NOT NULL default '0',
					PRIMARY KEY `entry_id` (`entry_id`),
					KEY `gallery_id` (`gallery_id`),
					KEY `author_id` (`author_id`)
				)";
			
			
		$sql[] = "CREATE TABLE IF NOT EXISTS exp_gallery_categories (
					cat_id int(10) unsigned NOT NULL auto_increment,
					gallery_id int(4) unsigned NOT NULL,
					parent_id int(4) unsigned NOT NULL,
					recent_entry_date int(10) NOT NULL,
					total_files int(8) unsigned NOT NULL default '0',
					total_views int(10) unsigned NOT NULL default '0',
					total_comments mediumint(8) default '0' NOT NULL,
					recent_comment_date int(10) unsigned default '0' NOT NULL,
					cat_name varchar(60) NOT NULL,
					cat_description text NOT NULL,
					cat_folder varchar(60) NOT NULL,
					cat_order int(4) unsigned NOT NULL,
					is_default char(1) NOT NULL default 'n',
					PRIMARY KEY `cat_id` (`cat_id`),
					KEY `gallery_id` (`gallery_id`)
				)"; 
				
		$sql[] = 	"CREATE TABLE IF NOT EXISTS exp_gallery_comments (
					 comment_id int(10) unsigned NOT NULL auto_increment,
					 entry_id int(10) unsigned NOT NULL default '0',
					 gallery_id int(4) unsigned NOT NULL,
					 author_id int(10) unsigned NOT NULL default '0',
					 status char(1) NOT NULL default 'o',
					 name varchar(50) NOT NULL,
					 email varchar(50) NOT NULL,
					 url varchar(75) NOT NULL,
					 location varchar(50) NOT NULL, 
					 ip_address varchar(16) NOT NULL,
					 comment_date int(10) NOT NULL,
					 edit_date timestamp(14),
					 comment text NOT NULL,
					 notify char(1) NOT NULL default 'n',
					 PRIMARY KEY `comment_id` (`comment_id`),
					 KEY `entry_id` (`entry_id`),
					 KEY `author_id` (`author_id`),
					 KEY `status` (`status`)
					)";
				
			
		foreach ($sql as $query)
		{
			$this->EE->db->query($query);
		}
		
		return TRUE;
	}

	
	
	// --------------------------------------------------------------------

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */	
	function uninstall()
	{
		$query = $this->EE->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Gallery'"); 
				
		$sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row('module_id') ."'";		
		$sql[] = "DELETE FROM exp_modules WHERE module_name = 'Gallery'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Gallery'";
		$sql[] = "DROP TABLE IF EXISTS exp_galleries";
		$sql[] = "DROP TABLE IF EXISTS exp_gallery_entries";
		$sql[] = "DROP TABLE IF EXISTS exp_gallery_categories";
		$sql[] = "DROP TABLE IF EXISTS exp_gallery_comments";

		foreach ($sql as $query)
		{
			$this->EE->db->query($query);
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */	
	
	function update($current='')
	{
		if ($current == $this->version)
		{
			return FALSE;
		}
		
		if ($current < 2.0)
		{
			$this->EE->db->query("ALTER TABLE `exp_galleries` DROP COLUMN `is_user_blog`");
			$this->EE->db->query("ALTER TABLE `exp_galleries` DROP COLUMN `user_blog_id`");
			$this->EE->db->query("ALTER TABLE `exp_galleries` CHANGE `gallery_upload_path` `gallery_upload_path` VARCHAR(150) NOT NULL");
			$this->EE->db->query("ALTER TABLE `exp_galleries` CHANGE `gallery_image_lib_path` `gallery_image_lib_path` VARCHAR(150) NOT NULL");
			$this->EE->db->query("ALTER TABLE `exp_galleries` CHANGE `gallery_batch_path` `gallery_batch_path` VARCHAR(150) NOT NULL");
			$this->EE->db->query("ALTER TABLE `exp_galleries` CHANGE `gallery_wm_image_path` `gallery_wm_image_path` VARCHAR(150) NOT NULL");
			$this->EE->db->query("ALTER TABLE `exp_galleries` CHANGE `gallery_wm_test_image_path` `gallery_wm_test_image_path` VARCHAR(150) NOT NULL");
		}
		
		return TRUE;
	}
}
// END CLASS

/* End of file upd.gallery.php */
/* Location: ./system/expressionengine/modules/gallery/upd.gallery.php */