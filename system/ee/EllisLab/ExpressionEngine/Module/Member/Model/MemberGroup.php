<?php

namespace EllisLab\ExpressionEngine\Module\Member\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class MemberGroup extends Model {

	protected static $_primary_key = 'group_id';
	protected static $_table_name = 'member_groups';

	protected static $_relationships = array(
		'Site' => array(
			'type' => 'belongsTo'
		),
		'Members' => array(
			'type' => 'hasMany',
			'model' => 'Member'
		),
		'AssignedChannels' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'Channel',
			'pivot' => array(
				'table' => 'channel_member_groups'
			)
		)
	);

	// Properties
	protected $group_id;
	protected $site_id;
	protected $group_title;
	protected $group_description;
	protected $is_locked;
	protected $can_view_offline_system;
	protected $can_view_online_system;
	protected $can_access_cp;
	protected $can_access_content;
	protected $can_access_publish;
	protected $can_access_edit;
	protected $can_access_files;
	protected $can_access_fieldtypes;
	protected $can_access_design;
	protected $can_access_addons;
	protected $can_access_modules;
	protected $can_access_extensions;
	protected $can_access_plugins;
	protected $can_access_members;
	protected $can_access_admin;
	protected $can_access_sys_prefs;
	protected $can_access_content_prefs;
	protected $can_access_tools;
	protected $can_access_comm;
	protected $can_access_utilities;
	protected $can_access_data;
	protected $can_access_logs;
	protected $can_admin_channels;
	protected $can_admin_upload_prefs;
	protected $can_admin_design;
	protected $can_admin_members;
	protected $can_delete_members;
	protected $can_admin_mbr_groups;
	protected $can_admin_mbr_templates;
	protected $can_ban_users;
	protected $can_admin_modules;
	protected $can_admin_templates;
	protected $can_edit_categories;
	protected $can_delete_categories;
	protected $can_view_other_entries;
	protected $can_edit_other_entries;
	protected $can_assign_post_authors;
	protected $can_delete_self_entries;
	protected $can_delete_all_entries;
	protected $can_view_other_comments;
	protected $can_edit_own_comments;
	protected $can_delete_own_comments;
	protected $can_edit_all_comments;
	protected $can_delete_all_comments;
	protected $can_moderate_comments;
	protected $can_send_email;
	protected $can_send_cached_email;
	protected $can_email_member_groups;
	protected $can_email_from_profile;
	protected $can_view_profiles;
	protected $can_edit_html_buttons;
	protected $can_delete_self;
	protected $mbr_delete_notify_emails;
	protected $can_post_comments;
	protected $exclude_from_moderation;
	protected $can_search;
	protected $search_flood_control;
	protected $can_send_private_messages;
	protected $prv_msg_send_limit;
	protected $prv_msg_storage_limit;
	protected $can_attach_in_private_messages;
	protected $can_send_bulletins;
	protected $include_in_authorlist;
	protected $include_in_memberlist;

}
