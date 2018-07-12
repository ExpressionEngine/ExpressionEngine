<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Updater\Version_5_0_0;

/**
 * Update
 */
class Updater {

	var $version_suffix = '';

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		$steps = new \ProgressIterator(
			[
				'addTheMemberToGroupPivotTable',
				'addAndPopulatePermissionsTable'
			]
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	private function addTheMemberToGroupPivotTable()
	{
		if (ee()->db->table_exists('members_member_groups'))
		{
			return;
		}

		// Add the Many-to-Many tables
		ee()->dbforge->add_field(
			[
				'member_id' => [
					'type'       => 'int',
					'constraint' => 10,
					'unsigned'   => TRUE,
					'null'       => FALSE
				],
				'group_id' => [
					'type'       => 'int',
					'constraint' => 4,
					'unsigned'   => TRUE,
					'null'       => FALSE
				]
			]
		);
		ee()->dbforge->add_key(['member_id', 'group_id'], TRUE);
		ee()->smartforge->create_table('members_member_groups');

		$members = ee()->db->select('member_id, group_id')->get('members');
		$insert = [];

		foreach ($members->result() as $member)
		{
			$insert[] = [
				'member_id' => $member->member_id,
				'group_id' => $member->group_id
			];
		}

		if ( ! empty($insert))
		{
			ee()->db->insert_batch('members_member_groups', $insert);
		}
	}

	private function addAndPopulatePermissionsTable()
	{
		if (ee()->db->table_exists('permissions'))
		{
			return;
		}

		ee()->dbforge->add_field(
			[
				'permission_id' => [
					'type'           => 'int',
					'constraint'     => 10,
					'null'           => FALSE,
					'unsigned'       => TRUE,
					'auto_increment' => TRUE
				],
				'group_id' => [
					'type'       => 'int',
					'constraint' => 4,
					'unsigned'   => TRUE,
					'null'       => FALSE
				],
				'site_id' => [
					'type'       => 'int',
					'constraint' => 5,
					'unsigned'   => TRUE,
					'null'       => FALSE
				],
				'permission' => [
					'type'       => 'varchar',
					'constraint' => 32,
					'null'       => FALSE
				],
			]
		);
		ee()->dbforge->add_key('permission_id', TRUE);
		ee()->dbforge->add_key(['group_id', 'site_id'], TRUE);
		ee()->smartforge->create_table('permissions');

		// Migrate permissions to the new table
		$insert = [];
		$permissions = [
			'can_view_offline_system',
			'can_view_online_system',
			'can_access_cp',
			'can_access_footer_report_bug',
			'can_access_footer_new_ticket',
			'can_access_footer_user_guide',
			'can_view_homepage_news',
			'can_access_files',
			'can_access_design',
			'can_access_addons',
			'can_access_members',
			'can_access_sys_prefs',
			'can_access_comm',
			'can_access_utilities',
			'can_access_data',
			'can_access_logs',
			'can_admin_channels',
			'can_admin_design',
			'can_delete_members',
			'can_admin_mbr_groups',
			'can_admin_mbr_templates',
			'can_ban_users',
			'can_admin_addons',
			'can_edit_categories',
			'can_delete_categories',
			'can_view_other_entries',
			'can_edit_other_entries',
			'can_assign_post_authors',
			'can_delete_self_entries',
			'can_delete_all_entries',
			'can_view_other_comments',
			'can_edit_own_comments',
			'can_delete_own_comments',
			'can_edit_all_comments',
			'can_delete_all_comments',
			'can_moderate_comments',
			'can_send_cached_email',
			'can_email_member_groups',
			'can_email_from_profile',
			'can_view_profiles',
			'can_edit_html_buttons',
			'can_delete_self',
			'can_post_comments',
			'can_search',
			'can_send_private_messages',
			'can_attach_in_private_messages',
			'can_send_bulletins',
			'can_create_entries',
			'can_edit_self_entries',
			'can_upload_new_files',
			'can_edit_files',
			'can_delete_files',
			'can_upload_new_toolsets',
			'can_edit_toolsets',
			'can_delete_toolsets',
			'can_create_upload_directories',
			'can_edit_upload_directories',
			'can_delete_upload_directories',
			'can_create_channels',
			'can_edit_channels',
			'can_delete_channels',
			'can_create_channel_fields',
			'can_edit_channel_fields',
			'can_delete_channel_fields',
			'can_create_statuses',
			'can_delete_statuses',
			'can_edit_statuses',
			'can_create_categories',
			'can_create_member_groups',
			'can_delete_member_groups',
			'can_edit_member_groups',
			'can_create_members',
			'can_edit_members',
			'can_create_new_templates',
			'can_edit_templates',
			'can_delete_templates',
			'can_create_template_groups',
			'can_edit_template_groups',
			'can_delete_template_groups',
			'can_create_template_partials',
			'can_edit_template_partials',
			'can_delete_template_partials',
			'can_create_template_variables',
			'can_delete_template_variables',
			'can_edit_template_variables',
			'can_access_security_settings',
			'can_access_translate',
			'can_access_import',
			'can_access_sql_manager',
			'can_moderate_spam',
			'can_manage_consents'
		];

		$groups = ee()->db->get('member_groups');

		foreach ($groups->result() as $group)
		{
			foreach ($permissions as $permission)
			{
				// Since we assume "no" we only need to store "yes"
				if ($group->$permission == 'y')
				{
					$insert[] = [
						'group_id'   => $group->group_id,
						'site_id'    => $group->site_id,
						'permission' => $permission
					];
				}
			}
		}

		if ( ! empty($insert))
		{
			ee()->db->insert_batch('permissions', $insert);
		}

		foreach ($permissions as $permission)
		{
			ee()->smartforge->drop_column('member_groups', $permission);
		}
	}
}

// EOF
