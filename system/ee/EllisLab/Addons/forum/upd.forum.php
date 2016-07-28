<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine Discussion Forum Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

class Forum_upd {

	var $version;

	function __construct()
	{
		$addon = ee('Addon')->get('forum');
		$this->version = $addon->getVersion();
	}

	function tabs()
	{
		$tabs['forum'] = array(
			'forum_title'	=> array(
								'visible'		=> TRUE,
								'collapse'		=> FALSE,
								'htmlbuttons'	=> TRUE,
								'width'			=> '100%'
								),
			'forum_body'	=> array(
								'visible'		=> TRUE,
								'collapse'		=> FALSE,
								'htmlbuttons'	=> TRUE,
								'width'			=> '100%'
								),
			'forum_id'	=> array(
								'visible'		=> TRUE,
								'collapse'		=> FALSE,
								'htmlbuttons'	=> TRUE,
								'width'			=> '100%'
								),
			'forum_topic_id'	=> array(
								'visible'		=> TRUE,
								'collapse'		=> FALSE,
								'htmlbuttons'	=> TRUE,
								'width'			=> '100%'
								)
				);

		return $tabs;
	}

	/** ---------------------------------
	/**  Store Trigger Word
	/** ---------------------------------*/
	function update_triggers()
	{
		$query = ee()->db->query("SELECT site_id FROM exp_sites");

		foreach($query->result_array() as $row)
		{
			$tquery = ee()->db->query("SELECT board_forum_trigger FROM exp_forum_boards WHERE board_site_id = '".ee()->db->escape_str($row['site_id'])."'");

			$triggers = array();

			foreach($tquery->result_array() as $trow)
			{
				$triggers[] = $trow['board_forum_trigger'];
			}

			$pquery = ee()->db->query("SELECT site_system_preferences FROM exp_sites WHERE site_id = '".ee()->db->escape_str($row['site_id'])."'");

			$prefs	 = unserialize(base64_decode($pquery->row('site_system_preferences')));

			$prefs['forum_trigger'] = implode('|', $triggers);

			//print_r($prefs);

			ee()->db->query(ee()->db->update_string('exp_sites',
										  array('site_system_preferences' => base64_encode(serialize($prefs))),
										  "site_id = '".ee()->db->escape_str($row['site_id'])."'"));
		}
	}


	/** ----------------------------------------
	/**  Set Base Permissions
	/** ----------------------------------------*/
	// This function fetches all the member group_id numbers except
	// the restricted ones, and buids a base permission array

	function forum_set_base_permissions($is_category = FALSE)
	{
		$query = ee()->db->query("SELECT group_id FROM exp_member_groups WHERE site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."' AND group_id > 4");

		$group_ids = '';

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$group_ids .= '|'.$row['group_id'];
			}
		}

		/** ------------------------------------
		/**  Define the permission array
		/** ------------------------------------*/
		$perms = array(
						'can_view_forum'	=> '|1|3|4'.$group_ids.'|',
						'can_view_hidden'	=> '|1'.$group_ids.'|',
						'can_view_topics'	=> ($is_category == TRUE) ? '' : '|1|3|4'.$group_ids.'|',
						'can_post_topics'	=> ($is_category == TRUE) ? '' : '|1'.$group_ids.'|',
						'can_post_reply'	=> ($is_category == TRUE) ? '' : '|1'.$group_ids.'|',
						'can_report'		=> ($is_category == TRUE) ? '' : '|1'.$group_ids.'|',
						'can_upload_files'	=> ($is_category == TRUE) ? '' : '|1'.$group_ids.'|',
						'can_search'		=> ($is_category == TRUE) ? '' : '|1|3|4'.$group_ids.'|'
						);

		return $perms;
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
		if ( ! is_really_writable(ee()->config->config_path))
		{
			ee()->lang->loadfile('forum_cp');

			return ee()->output->fatal_error(ee()->lang->line('config_not_writable'));
		}

		$sql[] = "INSERT INTO exp_modules (module_name, module_version, has_cp_backend, has_publish_fields) VALUES ('Forum', '$this->version', 'y', 'y')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Forum', 'submit_post')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Forum', 'delete_post')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Forum', 'change_status')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Forum', 'move_topic')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Forum', 'delete_subscription')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Forum', 'display_attachment')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Forum', 'do_merge')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Forum', 'do_split')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Forum', 'set_theme')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Forum', 'do_report')";
		$sql[] = "INSERT INTO exp_actions (class, method) VALUES ('Forum', 'move_reply')";

		$sql[] = "CREATE TABLE exp_forum_boards (
			board_id int(5) unsigned NOT NULL auto_increment,
			board_label varchar(150) NOT NULL,
			board_name varchar(50) NOT NULL,
			board_enabled char(1) NOT NULL default 'y',
			board_forum_trigger varchar(70) NOT NULL default '',
			board_site_id INT(5) unsigned NOT NULL default 1,
			board_alias_id INT(5) unsigned NOT NULL default 0,
			board_allow_php char(1) NOT NULL default 'n',
			board_php_stage char(1) NOT NULL default 'o',
			board_install_date int(10) unsigned default '0' NOT NULL,
			board_forum_url varchar(150) NOT NULL,
			board_default_theme varchar(75) NOT NULL,
			board_upload_path varchar(150) NULL,
			board_topics_perpage SMALLINT(4) NOT NULL default 25,
			board_posts_perpage smallint(4) NOT NULL default 10,
			board_topic_order char(1) NOT NULL default 'r',
			board_post_order char(1) NOT NULL default 'a',
			board_hot_topic smallint(4) NOT NULL default 10,
			board_max_post_chars int(6) unsigned NOT NULL default 6000,
			board_post_timelock int(5) unsigned NOT NULL default '0',
			board_display_edit_date char(1) NOT NULL default 'n',
			board_text_formatting varchar(50) NOT NULL default 'xhtml',
			board_html_formatting char(4) NOT NULL default 'safe',
			board_allow_img_urls char(1) NOT NULL default 'n',
			board_auto_link_urls char(1) NOT NULL default 'y',
			board_notify_emails varchar(255) NULL,
			board_notify_emails_topics varchar(255) NULL,
			board_max_attach_perpost smallint(4) NOT NULL default 3,
			board_max_attach_size int(6) unsigned NOT NULL default 75,
			board_max_width int(4) unsigned NOT NULL default 800,
			board_max_height int(4) unsigned NOT NULL default 600,
			board_attach_types char(3) NOT NULL default 'img',
			board_use_img_thumbs char(1) NOT NULL default 'y',
			board_thumb_width int(4) unsigned NOT NULL default 100,
			board_thumb_height int(4) unsigned NOT NULL default 100,
			board_forum_permissions text NOT NULL,
			board_use_deft_permissions char(1) NOT NULL default 'n',
			board_recent_poster_id int(10) unsigned NOT NULL default '0',
			board_recent_poster varchar(70) NULL DEFAULT NULL,
			board_enable_rss char(1) NOT NULL default 'y',
			board_use_http_auth char(1) NOT NULL default 'n',
			PRIMARY KEY `board_id` (`board_id`)
		) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";

		$sql[] = "CREATE TABLE exp_forums (
			forum_id int(6) unsigned NOT NULL auto_increment,
			board_id int(6) unsigned NOT NULL DEFAULT '1',
			forum_name varchar(100) NOT NULL,
			forum_description text NULL default NULL,
			forum_is_cat char(1) NOT NULL default 'n',
			forum_parent int(6) unsigned NULL default NULL,
			forum_order int(6) unsigned NULL default NULL,
			forum_status char(1) NOT NULL default 'o',
			forum_total_topics mediumint(8) default '0' NOT NULL,
			forum_total_posts mediumint(8) default '0' NOT NULL,
			forum_last_post_id int(6) unsigned NULL default NULL,
			forum_last_post_type char(1) NOT NULL default 'p',
			forum_last_post_title varchar(150) NULL default NULL,
			forum_last_post_date int(10) unsigned default '0' NOT NULL,
			forum_last_post_author_id int(10) unsigned NULL default NULL,
			forum_last_post_author varchar(50) NULL default NULL,
			forum_permissions text NOT NULL,
			forum_topics_perpage smallint(4) NOT NULL,
			forum_posts_perpage smallint(4) NOT NULL,
			forum_topic_order char(1) NOT NULL default 'r',
			forum_post_order char(1) NOT NULL default 'a',
			forum_hot_topic smallint(4) NOT NULL,
			forum_max_post_chars int(6) unsigned NOT NULL,
			forum_post_timelock int(5) unsigned NOT NULL default '0',
			forum_display_edit_date char(1) NOT NULL default 'n',
			forum_text_formatting varchar(50) NOT NULL default 'xhtml',
			forum_html_formatting char(4) NOT NULL default 'safe',
			forum_allow_img_urls char(1) NOT NULL default 'n',
			forum_auto_link_urls char(1) NOT NULL default 'y',
			forum_notify_moderators_topics char(1) NOT NULL default 'n',
			forum_notify_moderators_replies char(1) NOT NULL default 'n',
			forum_notify_emails varchar(255) NULL default NULL,
			forum_notify_emails_topics varchar(255) NULL default NULL,
			forum_enable_rss char(1) NOT NULL default 'n',
			forum_use_http_auth char(1) NOT NULL default 'n',
			PRIMARY KEY `forum_id` (`forum_id`),
			KEY `board_id` (`board_id`)
		) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";

		$sql[] = "CREATE TABLE exp_forum_topics (
			topic_id int(10) unsigned NOT NULL auto_increment,
			forum_id int(6) unsigned NOT NULL,
			board_id int(6) unsigned NOT NULL DEFAULT '1',
			moved_forum_id int(6) unsigned NOT NULL default '0',
			author_id int(10) unsigned NOT NULL default '0',
			ip_address varchar(45) NOT NULL,
			title varchar(150) NOT NULL,
			body text NOT NULL,
			status char(1) NOT NULL default 'o',
			sticky char(1) NOT NULL default 'n',
			poll char(1) NOT NULL default 'n',
			announcement char(1) NOT NULL default 'n',
			topic_date int(10) NOT NULL,
			topic_edit_date int(10) NOT NULL DEFAULT 0,
			topic_edit_author INT(10) UNSIGNED NOT NULL DEFAULT 0,
			thread_total int(5) unsigned NOT NULL default '0',
			thread_views int(6) unsigned NOT NULL default '0',
			last_post_date int(10) unsigned default '0' NOT NULL,
			last_post_author_id int(10) unsigned NOT NULL default '0',
			last_post_id int(10) unsigned NOT NULL default '0',
			notify char(1) NOT NULL default 'n',
			parse_smileys char(1) NOT NULL default 'y',
			PRIMARY KEY `topic_id` (`topic_id`),
			KEY `forum_id` (`forum_id`),
			KEY `board_id` (`board_id`),
			KEY `author_id` (`author_id`),
			KEY `last_post_author_id` (`last_post_author_id`),
			KEY `topic_date` (`topic_date`)
			) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";

		$sql[] = "CREATE TABLE exp_forum_posts (
			post_id int(10) unsigned NOT NULL auto_increment,
			topic_id int(10) unsigned NOT NULL,
			forum_id int(6) unsigned NOT NULL,
			board_id int(6) unsigned NOT NULL DEFAULT '1',
			author_id int(10) unsigned NOT NULL default '0',
			ip_address varchar(45) NOT NULL,
			body text NOT NULL,
			post_date int(10) NOT NULL,
			post_edit_date int(10) NOT NULL DEFAULT 0,
			post_edit_author INT(10) UNSIGNED NOT NULL DEFAULT 0,
			notify char(1) NOT NULL default 'n',
			parse_smileys char(1) NOT NULL default 'y',
			PRIMARY KEY `post_id` (`post_id`),
			KEY `topic_id` (`topic_id`),
			KEY `forum_id` (`forum_id`),
			KEY `board_id` (`board_id`),
			KEY `author_id` (`author_id`)
			) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";

		$sql[] = "CREATE TABLE exp_forum_ranks (
			rank_id int(6) unsigned NOT NULL auto_increment,
			rank_title varchar(100) NOT NULL,
			rank_min_posts int(6) NOT NULL,
  			rank_stars smallint(3) NOT NULL,
			PRIMARY KEY `rank_id` (`rank_id`)
		) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";

		$sql[] = "CREATE TABLE exp_forum_administrators (
			admin_id int(6) unsigned NOT NULL auto_increment,
			board_id int(6) unsigned NOT NULL DEFAULT '1',
			admin_group_id int(10) unsigned NOT NULL default '0',
			admin_member_id int(10) unsigned NOT NULL default '0',
			PRIMARY KEY `admin_id` (`admin_id`),
			KEY `board_id` (`board_id`),
			KEY `admin_group_id` (`admin_group_id`),
			KEY `admin_member_id` (`admin_member_id`)
		) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";

		$sql[] = "CREATE TABLE exp_forum_moderators (
			mod_id int(6) unsigned NOT NULL auto_increment,
			board_id int(6) unsigned NOT NULL DEFAULT '1',
			mod_forum_id int(6) unsigned NOT NULL,
			mod_member_id int(10) unsigned NOT NULL default '0',
			mod_member_name varchar(50) NOT NULL,
			mod_group_id int(10) unsigned NOT NULL default '0',
			mod_can_edit char(1) NOT NULL default 'n',
			mod_can_move char(1) NOT NULL default 'n',
			mod_can_delete char(1) NOT NULL default 'n',
			mod_can_split char(1) NOT NULL default 'n',
			mod_can_merge char(1) NOT NULL default 'n',
			mod_can_change_status char(1) NOT NULL default 'n',
			mod_can_announce char(1) NOT NULL default 'n',
			mod_can_view_ip char(1) NOT NULL default 'n',
			PRIMARY KEY `mod_id` (`mod_id`),
			KEY `board_id` (`board_id`),
			KEY `mod_forum_id` (`mod_forum_id`)
		) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";


		$sql[] = "CREATE TABLE exp_forum_subscriptions (
			topic_id int(10) unsigned NOT NULL,
			board_id int(6) unsigned NOT NULL DEFAULT '1',
			member_id int(10) unsigned NOT NULL default '0',
			subscription_date int(10) NOT NULL,
			notification_sent char(1) NOT NULL default 'n',
			hash varchar(15) NOT NULL,
			PRIMARY KEY `topic_id_member_id` (`topic_id`, `member_id`),
			KEY `board_id` (`board_id`)
			) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";


		$sql[] = "CREATE TABLE exp_forum_attachments (
			attachment_id int(10) unsigned NOT NULL auto_increment,
			topic_id int(10) unsigned NOT NULL default '0',
			post_id int(10) unsigned NOT NULL default '0',
			board_id int(5) unsigned NOT NULL default '1',
			member_id int(10) unsigned NOT NULL default '0',
			filename varchar(200) NOT NULL,
			filehash varchar(40) NOT NULL,
			filesize int(10) NOT NULL default '0',
			extension varchar(20) NOT NULL,
			hits int(10) NOT NULL default '0',
			attachment_date int(10) NOT NULL,
			is_temp char(1) NOT NULL default 'n',
			width int(5) unsigned NOT NULL,
			height int(5) unsigned NOT NULL,
			t_width int(5) unsigned NOT NULL,
			t_height int(5) unsigned NOT NULL,
			is_image char(1) NOT NULL default 'y',
			PRIMARY KEY `attachment_id` (`attachment_id`),
			KEY `topic_id` (`topic_id`),
			KEY `post_id` (`post_id`),
			KEY `board_id` (`board_id`),
			KEY `member_id` (`member_id`)
			) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";

		$sql[] = "CREATE TABLE exp_forum_search (
			 search_id varchar(32) NOT NULL,
			 board_id int(6) unsigned NOT NULL DEFAULT '1',
			 search_date int(10) NOT NULL,
			 keywords varchar(60) NOT NULL,
			 member_id int(10) unsigned NOT NULL,
			 ip_address varchar(45) NOT NULL,
			 topic_ids text NOT NULL,
			 post_ids text NOT NULL,
			 sort_order varchar(200) NOT NULL,
			 PRIMARY KEY `search_id` (`search_id`),
			 KEY `board_id` (`board_id`)
			) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";


		$sql[] = "CREATE TABLE exp_forum_polls (
			poll_id int(10) unsigned NOT NULL auto_increment,
			topic_id int(10) unsigned NOT NULL,
			author_id int(10) unsigned NOT NULL default '0',
			poll_question varchar(150) NOT NULL,
			poll_answers text NOT NULL,
			poll_date int(10) NOT NULL,
			total_votes int(10) unsigned NOT NULL default '0',
			PRIMARY KEY `poll_id` (`poll_id`),
			KEY `topic_id` (`topic_id`)
			) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";

		$sql[] = "CREATE TABLE exp_forum_pollvotes (
			vote_id int(10) unsigned NOT NULL auto_increment,
			poll_id int(10) unsigned NOT NULL,
			topic_id int(10) unsigned NOT NULL,
			member_id int(10) unsigned NOT NULL,
			choice_id  int(10) unsigned NOT NULL,
			PRIMARY KEY `vote_id` (`vote_id`),
			KEY `member_id` (`member_id`),
			KEY `topic_id` (`topic_id`)
			) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";

		$sql[] = "CREATE TABLE exp_forum_read_topics (
		 member_id int(10) unsigned NOT NULL,
		 board_id int(6) unsigned NOT NULL DEFAULT '1',
		 topics text NOT NULL,
		 last_visit int(10) NOT NULL,
		 PRIMARY KEY `member_id_board_id` (`member_id`, `board_id`)
		) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";

		$sql[] = "INSERT INTO exp_forum_ranks (rank_title, rank_min_posts, rank_stars) VALUES ('Newbie', 0, 1)";
		$sql[] = "INSERT INTO exp_forum_ranks (rank_title, rank_min_posts, rank_stars) VALUES ('Jr. Member', 30, 2)";
		$sql[] = "INSERT INTO exp_forum_ranks (rank_title, rank_min_posts, rank_stars) VALUES ('Member', 50, 3)";
		$sql[] = "INSERT INTO exp_forum_ranks (rank_title, rank_min_posts, rank_stars) VALUES ('Sr. Member', 100, 4)";

		foreach ($sql as $query)
		{
			ee()->db->query($query);
		}

		/** ----------------------------------------
		/**  Forum Trigger
		/** ----------------------------------------*/

		// A reserved word must be chosen which, when contained in the URL
		// (at the template group position), will trigger the foum class.
		// The forum doesn't use the main template engine so we need a way to
		// trigger it.  This word can not be one used as a template group
		// so we'll run through the following array until we find a word
		// we can use.

		$forum_triggers = array('forums', 'forum', 'boards', 'discussion_forum', 'myforums', 'myboards');

		$trigger = '';

		foreach ($forum_triggers as $val)
		{
			$query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_template_groups WHERE group_name = '{$val}' AND site_id = 1");

			if ($query->row('count')  == 0)
			{
				$trigger = $val;
				break;
			}
		}

		/** ----------------------------------------
		/**  Add a couple items to the config file
		/** ----------------------------------------*/

		// update the config file based on whether this install is from the CP or the install wizard
		if (method_exists(ee()->config, 'divination'))
		{
			ee()->config->_update_config(array('forum_is_installed' => 'y'));
		}
		else
		{
			ee()->config->set_item('forum_is_installed', 'y');
		}

		ee()->load->library('layout');
		ee()->layout->add_layout_tabs($this->tabs(), 'forum');

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
		$query = ee()->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Forum'");

		$sql[] = "DELETE FROM exp_specialty_templates WHERE template_name = 'admin_notify_forum_post'";
		$sql[] = "DELETE FROM exp_specialty_templates WHERE template_name = 'forum_post_notification'";
		$sql[] = "DELETE FROM exp_specialty_templates WHERE template_name = 'forum_moderation_notification'";
		$sql[] = "DELETE FROM exp_specialty_templates WHERE template_name = 'forum_report_notification'";
		$sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row('module_id') ."'";
		$sql[] = "DELETE FROM exp_modules WHERE module_name = 'Forum'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Forum'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Forum_mcp'";
		$sql[] = "DROP TABLE IF EXISTS exp_forum_boards";
		$sql[] = "DROP TABLE IF EXISTS exp_forums";
		$sql[] = "DROP TABLE IF EXISTS exp_forum_ranks";
		$sql[] = "DROP TABLE IF EXISTS exp_forum_moderators";
		$sql[] = "DROP TABLE IF EXISTS exp_forum_topics";
		$sql[] = "DROP TABLE IF EXISTS exp_forum_posts";
		$sql[] = "DROP TABLE IF EXISTS exp_forum_ranks";
		$sql[] = "DROP TABLE IF EXISTS exp_forum_administrators";
		$sql[] = "DROP TABLE IF EXISTS exp_forum_moderators";
		$sql[] = "DROP TABLE IF EXISTS exp_forum_subscriptions";
		$sql[] = "DROP TABLE IF EXISTS exp_forum_attachments";
		$sql[] = "DROP TABLE IF EXISTS exp_forum_search";
		$sql[] = "DROP TABLE IF EXISTS exp_forum_polls";
		$sql[] = "DROP TABLE IF EXISTS exp_forum_pollvotes";
		$sql[] = "DROP TABLE IF EXISTS exp_forum_read_topics";

		foreach ($sql as $query)
		{
			ee()->db->query($query);
		}

		/** ----------------------------------------
		/**  Remove a couple items from the config file
		/** ----------------------------------------*/

		ee()->config->_update_config(array(), array('forum_is_installed' => '', 'forum_trigger' => ''));

		ee()->load->library('layout');
		ee()->layout->delete_layout_tabs($this->tabs(), 'forum');

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
		if (version_compare($current, $this->version, '=='))
		{
			return FALSE;
		}

		ee()->load->dbforge();
		ee()->load->library('smartforge');

		if (version_compare($current, '1.3', '<'))
		{
			ee()->db->query("ALTER TABLE exp_forum_moderators ADD COLUMN mod_can_split char(1) NOT NULL default 'n'");
			ee()->db->query("ALTER TABLE exp_forum_moderators ADD COLUMN mod_can_merge char(1) NOT NULL default 'n'");
			ee()->db->query("ALTER TABLE exp_forums ADD COLUMN forum_enable_rss char(1) NOT NULL default 'n'");
			ee()->db->query("ALTER TABLE exp_forum_prefs ADD COLUMN pref_enable_rss char(1) NOT NULL default 'y'");
			ee()->db->query("INSERT INTO exp_actions (class, method) VALUES ('Forum', 'do_merge')");
			ee()->db->query("INSERT INTO exp_actions (class, method) VALUES ('Forum', 'do_split')");
			ee()->db->query("CREATE TABLE exp_forum_read_topics (member_id int(10) unsigned NOT NULL, topics text NOT NULL, last_visit int(10) NOT NULL, KEY `member_id` (`member_id`))");
		}

		if (version_compare($current, '1.3.1', '<'))
		{
			ee()->db->query("INSERT INTO exp_actions (class, method) VALUES ('Forum', 'set_theme')");

			ee()->db->query("INSERT INTO exp_specialty_templates(template_name, template_type, template_subtype, edit_date, data_title, template_data) VALUES ('forum_moderation_notification', 'email', 'forums', " . time() . ", '".addslashes(trim(forum_moderation_notification_title()))."', '".addslashes(forum_moderation_notification())."')");

			ee()->db->query("ALTER TABLE `exp_forum_topics` ADD `last_post_id` int(10) unsigned NOT NULL default '0'");

			/* -------------------------------------
			/*  Update topics for the new field
			/*  We only handle those active in 6 months here, and
			/*  will handle any others in the module itself if and
			/*  when it is necessary.
			/* -------------------------------------*/
			$tquery = ee()->db->query("SELECT topic_id FROM exp_forum_topics WHERE last_post_date > (UNIX_TIMESTAMP() - 15778463) AND thread_total > 1");

			if ($tquery->num_rows() > 0)
			{
				foreach ($tquery->result_array() as $row)
				{
					$pquery = ee()->db->query("SELECT post_id FROM exp_forum_posts WHERE topic_id = '".$row['topic_id']."' ORDER BY post_date DESC LIMIT 1");
					ee()->db->query("UPDATE exp_forum_topics SET last_post_id = '".$pquery->row('post_id') ."' WHERE topic_id = '".$row['topic_id']."'");
				}
			}
		}

		if (version_compare($current, '1.3.2', '<'))
		{
			ee()->db->query("ALTER TABLE `exp_forums` ADD `forum_display_edit_date` CHAR(1) NOT NULL DEFAULT 'n' AFTER `forum_post_timelock`");
			ee()->db->query("ALTER TABLE `exp_forums` CHANGE `forum_notify_moderators` `forum_notify_moderators_topics` CHAR(1) NOT NULL DEFAULT 'n'");
			ee()->db->query("ALTER TABLE `exp_forums` ADD `forum_notify_moderators_replies` CHAR(1) NOT NULL DEFAULT 'n' AFTER `forum_notify_moderators_topics`");

			/* Original edit date update code
			*
			$query = ee()->db->query("SELECT UNIX_TIMESTAMP() as mysql_timestamp");

			$diff = ee()->localize->now - $query->row('mysql_timestamp') ;

			$insert_diff = ($diff > 0) ? "+ ".$diff : "- ".($diff * -1);

			ee()->db->query("ALTER TABLE `exp_forum_posts` CHANGE `post_edit_date` `post_edit_date` VARCHAR( 25 ) NOT NULL");
			ee()->db->query("UPDATE `exp_forum_posts` SET `post_edit_date` = (UNIX_TIMESTAMP(`post_edit_date`) {$insert_diff})");
			ee()->db->query("ALTER TABLE `exp_forum_posts` CHANGE `post_edit_date` `post_edit_date` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'");

			ee()->db->query("ALTER TABLE `exp_forum_topics` CHANGE `topic_edit_date` `topic_edit_date` VARCHAR( 25 ) NOT NULL");
			ee()->db->query("UPDATE `exp_forum_topics` SET `topic_edit_date` = (UNIX_TIMESTAMP(`topic_edit_date`) {$insert_diff})");
			ee()->db->query("ALTER TABLE `exp_forum_topics` CHANGE `topic_edit_date` `topic_edit_date` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'");
			*
			*/

			ee()->db->query("UPDATE `exp_forum_posts` SET `post_edit_date` = '0'");
			ee()->db->query("ALTER TABLE `exp_forum_posts` CHANGE `post_edit_date` `post_edit_date` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'");

			ee()->db->query("UPDATE `exp_forum_topics` SET `topic_edit_date` = '0'");
			ee()->db->query("ALTER TABLE `exp_forum_topics` CHANGE `topic_edit_date` `topic_edit_date` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'");


			ee()->db->query("ALTER TABLE `exp_forum_posts` ADD `post_edit_author` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `post_edit_date`");
			ee()->db->query("ALTER TABLE `exp_forum_topics` ADD `topic_edit_author` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `topic_edit_date`");

			ee()->db->query("ALTER TABLE `exp_forum_prefs` ADD `pref_display_edit_date` CHAR(1) NOT NULL DEFAULT 'n' AFTER `pref_post_timelock`");

			ee()->db->query("INSERT INTO exp_actions (class, method) VALUES ('Forum', 'do_report')");

			ee()->db->query("INSERT INTO exp_specialty_templates(template_name, data_title, template_data) VALUES ('forum_report_notification', '".addslashes(trim(forum_report_notification_title()))."', '".addslashes(forum_report_notification())."')");

			/** -------------------------------------
			/**  Load up group id array
			/** -------------------------------------*/

			$query = ee()->db->query("SELECT group_id FROM exp_member_groups WHERE group_id > 4");

			$group_ids = '';

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$group_ids .= '|'.$row['group_id'];
				}
			}

			$query = ee()->db->query("SELECT pref_forum_permissions FROM exp_forum_prefs WHERE pref_id = '1'");
			$perms = ($query->row('pref_forum_permissions')  != '') ? unserialize(stripslashes($query->row('pref_forum_permissions') )) : $this->forum_set_base_permissions();

			if ( ! isset($perms['can_report']))
			{
				$perms['can_report'] = '|1'.$group_ids.'|';
			}

			ee()->db->query("UPDATE exp_forum_prefs SET pref_forum_permissions = '".addslashes(serialize($perms))."' WHERE pref_id = '1'");

			$query = ee()->db->query("SELECT forum_permissions, forum_id FROM exp_forums");

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$perms = ($row['forum_permissions'] != '') ? unserialize(stripslashes($row['forum_permissions'])) : $this->forum_set_base_permissions();

					if ( ! isset($perms['can_report']))
					{
						$perms['can_report'] = '|1'.$group_ids.'|';
					}

					ee()->db->query("UPDATE exp_forums SET forum_permissions = '".addslashes(serialize($perms))."' WHERE forum_id = '".$row['forum_id']."'");
				}
			}
		}

		/** -------------------------------------------
		/**  Version 2.0 Update Code
		/** -------------------------------------------*/

		if (version_compare($current, '2.0', '<'))
		{
			ee()->db->query("CREATE TABLE exp_forum_boards (
						board_id int(5) unsigned NOT NULL auto_increment,
						board_label varchar(150) NOT NULL,
						board_name varchar(50) NOT NULL,
						board_enabled char(1) NOT NULL default 'y',
						board_forum_trigger varchar(70) NOT NULL default '',
						board_site_id INT(5) unsigned NOT NULL default 1,
						board_alias_id INT(5) unsigned NOT NULL default 0,
						board_allow_php char(1) NOT NULL default 'n',
						board_php_stage char(1) NOT NULL default 'o',
						board_install_date int(10) unsigned default '0' NOT NULL,
						board_forum_url varchar(150) NOT NULL,
						board_default_theme varchar(75) NOT NULL,
						board_upload_path varchar(150) NOT NULL,
						board_topics_perpage smallint(4) NOT NULL,
						board_posts_perpage smallint(4) NOT NULL,
						board_topic_order char(1) NOT NULL default 'r',
						board_post_order char(1) NOT NULL default 'a',
						board_hot_topic smallint(4) NOT NULL,
						board_max_post_chars int(6) unsigned NOT NULL,
						board_post_timelock int(5) unsigned NOT NULL default '0',
						board_display_edit_date char(1) NOT NULL default 'n',
						board_text_formatting varchar(50) NOT NULL default 'xhtml',
						board_html_formatting char(4) NOT NULL default 'safe',
						board_allow_img_urls char(1) NOT NULL default 'n',
						board_auto_link_urls char(1) NOT NULL default 'y',
						board_notify_emails varchar(255) NOT NULL,
						board_notify_emails_topics varchar(255) NOT NULL,
						board_max_attach_perpost smallint(4) NOT NULL,
						board_max_attach_size int(6) unsigned NOT NULL,
						board_max_width int(4) unsigned NOT NULL,
						board_max_height int(4) unsigned NOT NULL,
						board_attach_types char(3) NOT NULL default 'img',
						board_use_img_thumbs char(1) NOT NULL default 'y',
						board_thumb_width int(4) unsigned NOT NULL,
						board_thumb_height int(4) unsigned NOT NULL,
						board_forum_permissions text NOT NULL,
						board_use_deft_permissions char(1) NOT NULL default 'n',
						board_recent_poster_id int(10) unsigned NOT NULL default '0',
						board_recent_poster varchar(70) NOT NULL,
						board_enable_rss char(1) NOT NULL default 'y',
						board_use_http_auth char(1) NOT NULL default 'n',
						PRIMARY KEY `board_id` (`board_id`))");

			$query = ee()->db->query("SELECT * FROM exp_forum_prefs");
			$data = array();

			foreach($query->row_array() as $key => $value)
			{
				$data['board_'.substr($key, 5)] = $value;
			}

			$word_separator = ee()->config->item('word_separator');

			$data['board_label']			= $query->row('pref_forum_name') ;
			$data['board_name']				= url_title($query->row('pref_forum_name'), $word_separator);
			$data['board_enabled']			= $query->row('pref_forum_enabled') ;
			$data['board_forum_trigger']	= ee()->config->item('forum_trigger');

			unset($data['board_forum_name']);
			unset($data['board_forum_enabled']);

			ee()->db->query(ee()->db->insert_string("exp_forum_boards", $data));

			// Tables need board_id added

			$adjust = array('exp_forums' => 'forum_id',
							'exp_forum_topics' => 'forum_id',
							'exp_forum_posts' => 'forum_id',
							'exp_forum_administrators' => 'admin_id',
							'exp_forum_moderators' => 'mod_id',
							'exp_forum_subscriptions' => 'topic_id',
							'exp_forum_search' => 'search_id',
							'exp_forum_read_topics' => 'member_id',
							'exp_forum_attachments' => 'post_id');

			foreach($adjust as $table => $after)
			{
				ee()->db->query("ALTER TABLE `".ee()->db->escape_str($table)."` ADD board_id INT(5) UNSIGNED NOT NULL DEFAULT 1 AFTER `".ee()->db->escape_str($after)."`");
				ee()->db->query("ALTER TABLE `".ee()->db->escape_str($table)."` ADD INDEX (`board_id`)");
			}

			// Add Text Formatting to Forum Prefs
			ee()->db->query("ALTER TABLE `exp_forums` ADD `forum_text_formatting` varchar(50) NOT NULL default 'xhtml' AFTER `forum_display_edit_date`");

			// Add HTTP Auth to Forum Prefs
			ee()->db->query("ALTER TABLE `exp_forums` ADD `forum_use_http_auth` char(1) NOT NULL default 'n' AFTER `forum_enable_rss`");

			// Add separate list for Topic email notification emails
			ee()->db->query("ALTER TABLE `exp_forums` ADD `forum_notify_emails_topics` varchar(255) NOT NULL AFTER `forum_notify_emails`");
			ee()->db->query("UPDATE `exp_forums` SET `forum_notify_emails_topics` = `forum_notify_emails`");

			// Add action for Move Reply
			ee()->db->query("INSERT INTO exp_actions (class, method) VALUES ('Forum', 'move_reply')");

			// Add field for post_ids in searches, and empty array to prevent errors on existing search data
			ee()->db->query("ALTER TABLE `exp_forum_search` ADD `post_ids` TEXT NOT NULL AFTER `topic_ids`");
			ee()->db->query("UPDATE `exp_forum_search` SET `post_ids` = 'a:0:{}'");

			// Remove forum_trigger and put in system prefs for site_id 1

			ee()->config->_update_config(array(), array('forum_trigger'));

			$this->update_triggers();

			// Remove old, no longer needed table
			ee()->db->query("DROP TABLE exp_forum_prefs");
		}

		if (version_compare($current, '2.1', '<'))
		{
			// nothing to see here
		}

		if (version_compare($current, '2.1.1', '<'))
		{
			// nothing to see here either
		}

		if (version_compare($current, '3.0', '<'))
		{
			// the forum subscription table now uses a primary key of topic_id-member_id, so there may be
			// multiple identical rows if a member was subscribed to two or more threads that were later merged.
			// Find them.  Eliminate them!
			$query = ee()->db->query("SELECT COUNT(*) AS count, topic_id, member_id
											FROM exp_forum_subscriptions
											GROUP BY topic_id, member_id
											HAVING count > 1
											ORDER BY count DESC");


			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					// delete all but one subscription matching this topic_id-member_id combo
					ee()->db->query("DELETE FROM exp_forum_subscriptions WHERE topic_id = '{$row['topic_id']}' AND member_id = '{$row['member_id']}' LIMIT ".($row['count'] - 1));
				}
			}

			ee()->db->query("ALTER TABLE `exp_forum_subscriptions` DROP KEY `topic_id`");
			ee()->db->query("ALTER TABLE `exp_forum_subscriptions` DROP KEY `member_id`");
			ee()->db->query("ALTER TABLE `exp_forum_subscriptions` ADD PRIMARY KEY `topic_id_member_id` (`topic_id`, `member_id`)");


			// Remove any duplicates from exp_forum_read_topics before we set a primary key of member_id-board_id
			$query = ee()->db->query("SELECT COUNT(*) AS count, member_id, board_id
											FROM exp_forum_read_topics
											GROUP BY member_id, board_id
											HAVING count > 1
											ORDER BY count DESC");

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					// delete all but one matching this member_id-board_id combo
					ee()->db->query("DELETE FROM exp_forum_read_topics WHERE member_id = '{$row['member_id']}' AND board_id = '{$row['board_id']}' LIMIT ".($row['count'] - 1));
				}
			}

			ee()->db->query("ALTER TABLE `exp_forum_read_topics` DROP KEY `member_id`");
			ee()->db->query("ALTER TABLE `exp_forum_read_topics` ADD PRIMARY KEY `member_id_board_id` (`member_id`, `board_id`)");


			// Carry on my wayward son
			ee()->db->query("ALTER TABLE `exp_forum_polls` MODIFY COLUMN `poll_id` int(10) unsigned NOT NULL PRIMARY KEY auto_increment");
			ee()->db->query("ALTER TABLE `exp_forum_pollvotes` MODIFY COLUMN `vote_id` int(10) unsigned NOT NULL PRIMARY KEY auto_increment");
			ee()->db->query("ALTER TABLE `exp_forum_boards` CHANGE `board_recent_poster` `board_recent_poster` VARCHAR(70) NULL DEFAULT NULL");
			ee()->db->query("ALTER TABLE `exp_forums` CHANGE `forum_description` `forum_description` TEXT NULL DEFAULT NULL");
			ee()->db->query("ALTER TABLE `exp_forums` CHANGE `forum_parent` `forum_parent` INT(6) unsigned NULL DEFAULT NULL");
			ee()->db->query("ALTER TABLE `exp_forums` CHANGE `forum_last_post_id` `forum_last_post_id` INT(6) unsigned NULL DEFAULT NULL");
			ee()->db->query("ALTER TABLE `exp_forums` CHANGE `forum_last_post_title` `forum_last_post_title` VARCHAR(150) NULL DEFAULT NULL");
			ee()->db->query("ALTER TABLE `exp_forums` CHANGE `forum_last_post_author_id` `forum_last_post_author_id` INT(10) unsigned NULL DEFAULT NULL");
			ee()->db->query("ALTER TABLE `exp_forums` CHANGE `forum_last_post_author` `forum_last_post_author` VARCHAR(50) NULL DEFAULT NULL");
			ee()->db->query("ALTER TABLE `exp_forums` CHANGE `forum_notify_emails` `forum_notify_emails` VARCHAR(255) NULL DEFAULT NULL");
			ee()->db->query("ALTER TABLE `exp_forums` CHANGE `forum_notify_emails_topics` `forum_notify_emails_topics` VARCHAR(255) NULL DEFAULT NULL");
			ee()->db->query("ALTER TABLE `exp_forums` CHANGE `forum_order` `forum_order` INT(6) NULL DEFAULT NULL");
			ee()->db->query("ALTER TABLE `exp_forum_moderators` CHANGE `mod_member_name` `mod_member_name` VARCHAR(50) NULL DEFAULT NULL");
		}

		if (version_compare($current, '3.0.1', '<'))
		{
			$Q = array();

			$Q[] = 'ALTER TABLE `exp_forum_boards` MODIFY `board_upload_path` VARCHAR(150) NULL';
			$Q[] = 'ALTER TABLE `exp_forum_boards` MODIFY `board_topics_perpage` SMALLINT(4) NOT NULL DEFAULT 25';
			$Q[] = 'ALTER TABLE `exp_forum_boards` MODIFY `board_posts_perpage` SMALLINT(4) NOT NULL DEFAULT 10';
			$Q[] = 'ALTER TABLE `exp_forum_boards` MODIFY `board_hot_topic` SMALLINT(4) NOT NULL DEFAULT 10';
			$Q[] = 'ALTER TABLE `exp_forum_boards` MODIFY `board_max_post_chars` INT(6) UNSIGNED NOT NULL DEFAULT 6000';
			$Q[] = 'ALTER TABLE `exp_forum_boards` MODIFY `board_notify_emails` VARCHAR(255) NULL';
			$Q[] = 'ALTER TABLE `exp_forum_boards` MODIFY `board_notify_emails_topics` VARCHAR(255) NULL';
			$Q[] = 'ALTER TABLE `exp_forum_boards` MODIFY `board_max_attach_perpost` SMALLINT(4) NOT NULL DEFAULT 3';
			$Q[] = 'ALTER TABLE `exp_forum_boards` MODIFY `board_max_attach_size` INT(6) UNSIGNED NOT NULL DEFAULT 75';
			$Q[] = 'ALTER TABLE `exp_forum_boards` MODIFY `board_max_width` INT(4) UNSIGNED NOT NULL DEFAULT 800';
			$Q[] = 'ALTER TABLE `exp_forum_boards` MODIFY `board_max_height` INT(4) UNSIGNED NOT NULL DEFAULT 600';
			$Q[] = 'ALTER TABLE `exp_forum_boards` MODIFY `board_thumb_width` INT(4) UNSIGNED NOT NULL DEFAULT 100';
			$Q[] = 'ALTER TABLE `exp_forum_boards` MODIFY `board_thumb_height` INT(4) UNSIGNED NOT NULL DEFAULT 100';


			foreach ($Q as $query)
			{
				ee()->db->query($query);
			}
		}


		if (version_compare($current, '3.1', '<'))
		{
			// this ALTER appears in 3.0 update as well, but did not in the initial release of the Public Beta.  So let's do it again in 3.1
			// to ensure everyone's tables are fine.  At that point, the code in forum_update_moderator() of mcp.forum.php can remove the
			// setting of 'mod_member_name' to an empty string. (done - 20100625 - dj)
			ee()->db->query("ALTER TABLE `exp_forum_moderators` CHANGE `mod_member_name` `mod_member_name` VARCHAR(50) NULL DEFAULT NULL");
		}

		if (version_compare($current, '3.1.1', '<'))
		{
			// Add the publish tab.  wootage!
			$data = array('has_publish_fields' => 'y');
			ee()->db->where('module_name', 'Forum');
			ee()->db->update('modules', $data);
		}

		if (version_compare($current, '3.1.2', '<'))
		{
			$this->_do_312_update();
		}

		if (version_compare($current, '3.1.3', '<')) { }

		if (version_compare($current, '3.1.4', '<')) { }

		if (version_compare($current, '3.1.5', '<')) { }

		if (version_compare($current, '3.1.6', '<')) { }

		if (version_compare($current, '3.1.9', '<'))
		{
			// Update ip_address column
			$tables = array('forum_topics', 'forum_posts', 'forum_search');

			foreach ($tables as $table)
			{
				ee()->dbforge->modify_column(
					$table,
					array(
						'ip_address' => array(
							'name' 			=> 'ip_address',
							'type' 			=> 'varchar',
							'constraint'	=> '45',
							'null'			=> FALSE
						)
					)
				);
			}
		}

		if (version_compare($current, '3.1.11', '<'))
		{
			ee()->smartforge->drop_column('forum_topics', 'pentry_id');
		}

		if (version_compare($current, '3.1.16', '<'))
		{
			// No default on mod_member_name resulted in some strict errors

			$fields = array(
				'mod_member_name' => array(
					'mod_member_name' => 'mod_member_name',
					'type' => 'varchar',
					'constraint'	=> '50',
					'null'			=> TRUE)
					);

			ee()->smartforge->modify_column('forum_moderators', $fields);
		}

		if (version_compare($current, '3.1.20', '<'))
		{

			// There was bug in 3.0 where any new forum triggers were not being
			// saved into the site_system_preferences column. This will update
			// those.

			ee('Model')->get('forum:Board')
				->all()
				->save();
		}

		return TRUE;

	}

	// --------------------------------------------------------------------

	/**
	 * The publish page rewrite messed a few things up since we moved the
	 * forum/pages tabs into their own proper tab files.  This will correct
	 * said issues with page layouts.
	 *
	 * @return void
	 */
	private function _do_312_update()
	{
		ee()->load->library('layout');

		$layouts = ee()->db->get('layout_publish');

		if ($layouts->num_rows() === 0)
		{
			return;
		}

		$layouts = $layouts->result_array();

		$old_forum_fields = array(
							'forum_title',
							'forum_body',
							'forum_id',
							'forum_topic_id',
						);

		foreach ($layouts as &$layout)
		{
			$old_layout = unserialize($layout['field_layout']);

			foreach ($old_layout as $tab => &$fields)
			{
				$field_keys = array_keys($fields);

				foreach ($field_keys as &$key)
				{
					if (in_array($key, $old_forum_fields))
					{
						$key = 'forum__'.$key;
					}
				}

				$fields = array_combine($field_keys, $fields);
			}

			$layout['field_layout'] = serialize($old_layout);

		}

		ee()->db->update_batch('layout_publish', $layouts, 'layout_id');

		return TRUE;
	}


}
// END CLASS

// EOF
