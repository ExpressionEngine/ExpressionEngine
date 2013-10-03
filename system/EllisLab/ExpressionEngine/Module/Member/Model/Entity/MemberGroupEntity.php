<?php
namespace EllisLab\ExpressionEngine\Module\Member\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

/**
 * Member Groups table
 */
class MemberGroupEntity extends Entity {
	protected static $meta = array(
		'table_name' => 'member_groups',
		'primary_key' => 'group_id',
		'related_entities' => array(
			'site_id' => array(
				'entity' => 'SiteEntity',
				'key' => 'site_id'
			)
		)
	);

	
	// Properties
	public $group_id;
	public $site_id;
	public $group_title;
	public $group_description;
	public $is_locked;
	public $can_view_offline_system;
	public $can_view_online_system;
	public $can_access_cp;
	public $can_access_content;
	public $can_access_publish;
	public $can_access_edit;
	public $can_access_files;
	public $can_access_fieldtypes;
	public $can_access_design;
	public $can_access_addons;
	public $can_access_modules;
	public $can_access_extensions;
	public $can_access_accessories;
	public $can_access_plugins;
	public $can_access_members;
	public $can_access_admin;
	public $can_access_sys_prefs;
	public $can_access_content_prefs;
	public $can_access_tools;
	public $can_access_comm;
	public $can_access_utilities;
	public $can_access_data;
	public $can_access_logs;
	public $can_admin_channels;
	public $can_admin_upload_prefs;
	public $can_admin_design;
	public $can_admin_members;
	public $can_delete_members;
	public $can_admin_mbr_groups;
	public $can_admin_mbr_templates;
	public $can_ban_users;
	public $can_admin_modules;
	public $can_admin_templates;
	public $can_edit_categories;
	public $can_delete_categories;
	public $can_view_other_entries;
	public $can_edit_other_entries;
	public $can_assign_post_authors;
	public $can_delete_self_entries;
	public $can_delete_all_entries;
	public $can_view_other_comments;
	public $can_edit_own_comments;
	public $can_delete_own_comments;
	public $can_edit_all_comments;
	public $can_delete_all_comments;
	public $can_moderate_comments;
	public $can_send_email;
	public $can_send_cached_email;
	public $can_email_member_groups;
	public $can_email_mailinglist;
	public $can_email_from_profile;
	public $can_view_profiles;
	public $can_edit_html_buttons;
	public $can_delete_self;
	public $mbr_delete_notify_emails;
	public $can_post_comments;
	public $exclude_from_moderation;
	public $can_search;
	public $search_flood_control;
	public $can_send_private_messages;
	public $prv_msg_send_limit;
	public $prv_msg_storage_limit;
	public $can_attach_in_private_messages;
	public $can_send_bulletins;
	public $include_in_authorlist;
	public $include_in_memberlist;
	public $include_in_mailinglists;
}
