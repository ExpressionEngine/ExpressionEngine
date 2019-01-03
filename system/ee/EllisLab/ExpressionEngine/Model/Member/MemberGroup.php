<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Member;

use EllisLab\ExpressionEngine\Model\Content\StructureModel;

/**
 * Member Group Model
 */
class MemberGroup extends StructureModel {

	protected static $_primary_key = 'group_id';
	protected static $_table_name = 'member_groups';

	protected static $_hook_id = 'member_group';

	protected static $_events = array(
		'beforeInsert',
		'afterInsert',
		'afterUpdate',
		'afterDelete'
	);

	protected static $_typed_columns = array(
		'is_locked'                      => 'boolString',
		'can_view_offline_system'        => 'boolString',
		'can_view_online_system'         => 'boolString',
		'can_access_cp'                  => 'boolString',
		'can_access_footer_report_bug'   => 'boolString',
		'can_access_footer_new_ticket'   => 'boolString',
		'can_access_footer_user_guide'   => 'boolString',
		'can_view_homepage_news'         => 'boolString',
		'can_access_files'               => 'boolString',
		'can_access_design'              => 'boolString',
		'can_access_addons'              => 'boolString',
		'can_access_members'             => 'boolString',
		'can_access_sys_prefs'           => 'boolString',
		'can_access_comm'                => 'boolString',
		'can_access_utilities'           => 'boolString',
		'can_access_data'                => 'boolString',
		'can_access_logs'                => 'boolString',
		'can_admin_design'               => 'boolString',
		'can_delete_members'             => 'boolString',
		'can_admin_mbr_groups'           => 'boolString',
		'can_admin_mbr_templates'        => 'boolString',
		'can_ban_users'                  => 'boolString',
		'can_admin_addons'              => 'boolString',
		'can_edit_categories'            => 'boolString',
		'can_delete_categories'          => 'boolString',
		'can_view_other_entries'         => 'boolString',
		'can_edit_other_entries'         => 'boolString',
		'can_assign_post_authors'        => 'boolString',
		'can_create_entries'             => 'boolString',
		'can_edit_self_entries'          => 'boolString',
		'can_delete_self_entries'        => 'boolString',
		'can_delete_all_entries'         => 'boolString',
		'can_view_other_comments'        => 'boolString',
		'can_edit_own_comments'          => 'boolString',
		'can_delete_own_comments'        => 'boolString',
		'can_edit_all_comments'          => 'boolString',
		'can_delete_all_comments'        => 'boolString',
		'can_moderate_comments'          => 'boolString',
		'can_send_cached_email'          => 'boolString',
		'can_email_member_groups'        => 'boolString',
		'can_email_from_profile'         => 'boolString',
		'can_view_profiles'              => 'boolString',
		'can_edit_html_buttons'          => 'boolString',
		'can_delete_self'                => 'boolString',
		'can_post_comments'              => 'boolString',
		'exclude_from_moderation'        => 'boolString',
		'can_search'                     => 'boolString',
		'can_send_private_messages'      => 'boolString',
		'can_attach_in_private_messages' => 'boolString',
		'can_send_bulletins'             => 'boolString',
		'include_in_authorlist'          => 'boolString',
		'include_in_memberlist'          => 'boolString',
		'can_upload_new_files'          => 'boolString',
		'can_edit_files'                => 'boolString',
		'can_delete_files'              => 'boolString',
		'can_upload_new_toolsets'        => 'boolString',
		'can_edit_toolsets'              => 'boolString',
		'can_delete_toolsets'            => 'boolString',
		'can_create_upload_directories'  => 'boolString',
		'can_edit_upload_directories'    => 'boolString',
		'can_delete_upload_directories'  => 'boolString',
		'can_create_channels'            => 'boolString',
		'can_edit_channels'              => 'boolString',
		'can_delete_channels'            => 'boolString',
		'can_create_channel_fields'      => 'boolString',
		'can_edit_channel_fields'        => 'boolString',
		'can_delete_channel_fields'      => 'boolString',
		'can_create_statuses'            => 'boolString',
		'can_delete_statuses'            => 'boolString',
		'can_edit_statuses'              => 'boolString',
		'can_create_categories'          => 'boolString',
		'can_create_member_groups'       => 'boolString',
		'can_delete_member_groups'       => 'boolString',
		'can_edit_member_groups'         => 'boolString',
		'can_create_members'             => 'boolString',
		'can_edit_members'               => 'boolString',
		'can_create_new_templates'       => 'boolString',
		'can_edit_templates'             => 'boolString',
		'can_delete_templates'           => 'boolString',
		'can_create_template_groups'     => 'boolString',
		'can_edit_template_groups'       => 'boolString',
		'can_delete_template_groups'     => 'boolString',
		'can_create_template_partials'   => 'boolString',
		'can_edit_template_partials'     => 'boolString',
		'can_delete_template_partials'   => 'boolString',
		'can_create_template_variables'  => 'boolString',
		'can_delete_template_variables'  => 'boolString',
		'can_edit_template_variables'    => 'boolString',
		'can_access_security_settings'   => 'boolString',
		'can_access_translate'           => 'boolString',
		'can_access_import'	             => 'boolString',
		'can_access_sql_manager'         => 'boolString',
		'can_admin_channels'             => 'boolString',
		'can_manage_consents'            => 'boolString',
	);


	protected static $_relationships = array(
		'Site' => array(
			'type' => 'belongsTo'
		),
		'Members' => array(
			'type' => 'hasMany',
			'model' => 'Member',
			'weak' => TRUE
		),
		'AssignedChannels' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'Channel',
			'pivot' => array(
				'table' => 'channel_member_groups'
			)
		),
		'AssignedTemplateGroups' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'TemplateGroup',
			'pivot' => array(
				'table' => 'template_member_groups',
				'left'  => 'group_id',
				'right' => 'template_group_id'
			)
		),
		'AssignedModules' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'Module',
			'pivot' => array(
				'table' => 'module_member_groups'
			)
		),
		'NoTemplateAccess' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'Template',
			'pivot' => array(
				'table' => 'template_no_access',
				'right'  => 'template_id',
				'left' => 'member_group'
			)
		),
		'NoUploadAccess' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'UploadDestination',
			'pivot' => array(
				'table' => 'upload_no_access',
				'left' => 'member_group',
				'right' => 'upload_id'
			)
		),
		'NoStatusAccess' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'Status',
			'pivot' => array(
				'table' => 'status_no_access',
				'left' => 'member_group',
				'right' => 'status_id'
			)
		),
		'ChannelLayouts' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'ChannelLayout',
			'pivot' => array(
				'table' => 'layout_publish_member_groups',
				'key' => 'layout_id',
			)
		),
		'EmailCache' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'EmailCache',
			'pivot' => array(
				'table' => 'email_cache_mg'
			)
		),
		'MenuSet' => array(
			'type' => 'belongsTo',
			'from_key' => 'menu_set_id'
		),
	);

	protected static $_validation_rules = array(
		'group_id' => 'required|integer',
		'site_id'  => 'required|integer',
	);

	// Properties
	protected $group_id;
	protected $site_id;
	protected $group_title;
	protected $group_description;
	protected $is_locked;
	protected $menu_set_id;
	protected $can_view_offline_system;
	protected $can_view_online_system;
	protected $can_access_cp;
	protected $can_access_footer_report_bug;
	protected $can_access_footer_new_ticket;
	protected $can_access_footer_user_guide;
	protected $can_view_homepage_news;
	protected $can_access_files;
	protected $can_access_design;
	protected $can_access_addons;
	protected $can_access_members;
	protected $can_access_sys_prefs;
	protected $can_access_comm;
	protected $can_access_utilities;
	protected $can_access_data;
	protected $can_access_logs;
	protected $can_admin_design;
	protected $can_delete_members;
	protected $can_admin_mbr_groups;
	protected $can_admin_mbr_templates;
	protected $can_ban_users;
	protected $can_admin_addons;
	protected $can_edit_categories;
	protected $can_delete_categories;
	protected $can_view_other_entries;
	protected $can_edit_other_entries;
	protected $can_assign_post_authors;
	protected $can_create_entries;
	protected $can_edit_self_entries;
	protected $can_delete_self_entries;
	protected $can_delete_all_entries;
	protected $can_view_other_comments;
	protected $can_edit_own_comments;
	protected $can_delete_own_comments;
	protected $can_edit_all_comments;
	protected $can_delete_all_comments;
	protected $can_moderate_comments;
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
	protected $cp_homepage;
	protected $cp_homepage_channel;
	protected $cp_homepage_custom;
	protected $can_upload_new_files;
	protected $can_edit_files;
	protected $can_delete_files;
	protected $can_upload_new_toolsets;
	protected $can_edit_toolsets;
	protected $can_delete_toolsets;
	protected $can_create_upload_directories;
	protected $can_edit_upload_directories;
	protected $can_delete_upload_directories;
	protected $can_create_channels;
	protected $can_edit_channels;
	protected $can_delete_channels;
	protected $can_create_channel_fields;
	protected $can_edit_channel_fields;
	protected $can_delete_channel_fields;
	protected $can_create_statuses;
	protected $can_delete_statuses;
	protected $can_edit_statuses;
	protected $can_create_categories;
	protected $can_create_member_groups;
	protected $can_delete_member_groups;
	protected $can_edit_member_groups;
	protected $can_create_members;
	protected $can_edit_members;
	protected $can_create_new_templates;
	protected $can_edit_templates;
	protected $can_delete_templates;
	protected $can_create_template_groups;
	protected $can_edit_template_groups;
	protected $can_delete_template_groups;
	protected $can_create_template_partials;
	protected $can_edit_template_partials;
	protected $can_delete_template_partials;
	protected $can_create_template_variables;
	protected $can_delete_template_variables;
	protected $can_edit_template_variables;
	protected $can_access_security_settings;
	protected $can_access_translate;
	protected $can_access_import;
	protected $can_access_sql_manager;
	protected $can_admin_channels;
	protected $can_manage_consents;

	/**
	 * Ensure group ID is set for new records
	 * @return void
	 */
	public function onBeforeInsert()
	{
		if ( ! $this->group_id)
		{
			$id = ee('db')->query('SELECT MAX(group_id) as id FROM exp_member_groups')->row('id');
			$this->setRawProperty('group_id', $id + 1);
		}
	}

	public function onAfterDelete()
	{
		$this->prunePivotTables();
	}

	protected function prunePivotTables()
	{
		foreach (self::$_relationships as $name => $info)
		{
			if (array_key_exists('pivot', $info))
			{
				$table = 'exp_' . $info['pivot']['table'];
				$column = (array_key_exists('left', $info['pivot'])) ? $info['pivot']['left'] : 'group_id';

				$sql = "SELECT DISTINCT({$table}.{$column}) AS group_id FROM {$table} LEFT JOIN exp_member_groups ON {$table}.{$column} = exp_member_groups.group_id WHERE exp_member_groups.group_id is NULL;";
				$query = ee('db')->query($sql);

				$groups = array();

				foreach ($query->result_array() as $row)
				{
					$groups[] = $row['group_id'];
				}

				if ( ! empty($groups))
				{
					ee('db')->query("DELETE FROM {$table} WHERE {$column} IN (" . implode(',', $groups) . ")");
				}
			}
		}
	}

	/**
	 * Only set ID if we're being passed a number other than 0 or NULL
	 * @param Integer/String $new_id ID of the record
	 */
	public function setId($new_id)
	{
		if ($new_id !== '0' && $new_id !== 0)
		{
			parent::setId($new_id);
		}
	}

	/**
	 * Ensure member group records are created for each site
	 * @return void
	 */
	public function onAfterInsert()
	{
		$this->setId($this->group_id);

		$sites = $this->getModelFacade()->get('Site')
			->fields('site_id')
			->all()
			->pluck('site_id');

		foreach ($sites as $site_id)
		{
			$group = $this->getModelFacade()->get('MemberGroup')
				->filter('group_id', $this->group_id)
				->filter('site_id', $site_id)
				->first();

			if ( ! $group)
			{
				$data = $this->getValues();
				$data['site_id'] = (int) $site_id;
				$this->getModelFacade()->make('MemberGroup', $data)->save();
			}
		}
	}

	protected function constrainQueryToSelf($query)
	{
		if ($this->isDirty('site_id'))
		{
			throw new \LogicException('Cannot modify site_id.');
		}

		$query->filter('site_id', $this->site_id);
		parent::constrainQueryToSelf($query);
	}

	/**
	 * Update common attributes (group_title, group_description, is_locked)
	 * @return void
	 */
	public function onAfterUpdate()
	{
		ee('db')->update(
			'member_groups',
			array(
				'group_title' => $this->group_title,
				'group_description' => $this->group_description,
				'is_locked' => $this->is_locked,
				'menu_set_id' => $this->menu_set_id
			),
			array('group_id' => $this->group_id)
		);
	}

	/**
	 * Returns array of field models; implements StructureModel interface
	 */
	public function getAllCustomFields()
	{
		$member_cfields = ee()->session->cache('EllisLab::MemberGroupModel', 'getCustomFields');

		// might be empty, so need to be specific
		if ( ! is_array($member_cfields))
		{
			$member_cfields = $this->getModelFacade()->get('MemberField')->all()->asArray();
			ee()->session->set_cache('EllisLab::MemberGroupModel', 'getCustomFields', $member_cfields);
		}

		return $member_cfields;
	}

	/**
	 * Returns name of content type for these fields; implements StructureModel interface
	 */
	public function getContentType()
	{
		return 'member';
	}

	/**
	 * Assigns channels to this group for this site without destroying this
	 * group's channel assignments on the other sites. The pivot table does not
	 * take into account the site_id so we we'll do that here.
	 *
	 * @param  array  $channel_ids An array of channel ids for this group
	 * @return void
	 */
	public function assignChannels(array $channel_ids)
	{
		// First, get the channel ids for all the other sites
		$other_channels = $this->getModelFacade()->get('Channel')
			->fields('channel_id')
			->filter('site_id', '!=', $this->site_id)
			->all()
			->pluck('channel_id');

		// Get all the assignments for the other sites
		$current_assignments = array_values(array_intersect($other_channels, $this->AssignedChannels->pluck('channel_id')));

		// Make the assignment!
		$this->AssignedChannels = $this->getModelFacade()->get('Channel', array_merge($current_assignments, $channel_ids))->all();
	}
}

// EOF
