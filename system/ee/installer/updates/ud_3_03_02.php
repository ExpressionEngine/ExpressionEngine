<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Updater\Version_3_3_2;

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
			array(
				'update_superadmin_permissions'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	/**
	 * Redo to allow our localized date and time settings to have NULL values
	 * in the db.
	 *
	 * @return void
	 */
	private function update_superadmin_permissions()
	{
		// Redo of 3.2.0 because a few were missing from installer up
		// until 3.3.1
		$permissions = array(
			'can_view_offline_system'        => 'y',
			'can_access_cp'                  => 'y',
			'can_access_footer_report_bug'   => 'y',
			'can_access_footer_new_ticket'   => 'y',
			'can_access_footer_user_guide'   => 'y',
			'can_upload_new_files'           => 'y',
			'can_edit_files'                 => 'y',
			'can_delete_files'               => 'y',
			'can_upload_new_toolsets'        => 'y',
			'can_edit_toolsets'              => 'y',
			'can_delete_toolsets'            => 'y',
			'can_create_upload_directories'  => 'y',
			'can_edit_upload_directories'    => 'y',
			'can_delete_upload_directories'  => 'y',
			'can_access_files'               => 'y',
			'can_access_design'              => 'y',
			'can_access_addons'              => 'y',
			'can_access_members'             => 'y',
			'can_access_sys_prefs'           => 'y',
			'can_access_comm'                => 'y',
			'can_access_utilities'           => 'y',
			'can_access_data'                => 'y',
			'can_access_logs'                => 'y',
			'can_admin_channels'             => 'y',
			'can_create_channels'            => 'y',
			'can_edit_channels'              => 'y',
			'can_delete_channels'            => 'y',
			'can_create_channel_fields'      => 'y',
			'can_edit_channel_fields'        => 'y',
			'can_delete_channel_fields'      => 'y',
			'can_create_statuses'            => 'y',
			'can_delete_statuses'            => 'y',
			'can_edit_statuses'              => 'y',
			'can_create_categories'          => 'y',
			'can_create_member_groups'       => 'y',
			'can_delete_member_groups'       => 'y',
			'can_edit_member_groups'         => 'y',
			'can_admin_design'               => 'y',
			'can_create_members'             => 'y',
			'can_edit_members'               => 'y',
			'can_delete_members'             => 'y',
			'can_admin_mbr_groups'           => 'y',
			'can_admin_mbr_templates'        => 'y',
			'can_ban_users'                  => 'y',
			'can_admin_addons'               => 'y',
			'can_create_new_templates'       => 'y',
			'can_edit_templates'             => 'y',
			'can_delete_templates'           => 'y',
			'can_create_template_groups'     => 'y',
			'can_edit_template_groups'       => 'y',
			'can_delete_template_groups'     => 'y',
			'can_create_template_partials'   => 'y',
			'can_edit_template_partials'     => 'y',
			'can_delete_template_partials'   => 'y',
			'can_create_template_variables'  => 'y',
			'can_delete_template_variables'  => 'y',
			'can_edit_template_variables'    => 'y',
			'can_edit_categories'            => 'y',
			'can_delete_categories'          => 'y',
			'can_view_other_entries'         => 'y',
			'can_edit_other_entries'         => 'y',
			'can_assign_post_authors'        => 'y',
			'can_delete_self_entries'        => 'y',
			'can_delete_all_entries'         => 'y',
			'can_view_other_comments'        => 'y',
			'can_edit_own_comments'          => 'y',
			'can_delete_own_comments'        => 'y',
			'can_edit_all_comments'          => 'y',
			'can_delete_all_comments'        => 'y',
			'can_moderate_comments'          => 'y',
			'can_send_cached_email'          => 'y',
			'can_email_member_groups'        => 'y',
			'can_email_from_profile'         => 'y',
			'can_view_profiles'              => 'y',
			'can_edit_html_buttons'          => 'y',
			'can_post_comments'              => 'y',
			'can_delete_self'                => 'y',
			'exclude_from_moderation'        => 'y',
			'can_send_private_messages'      => 'y',
			'can_attach_in_private_messages' => 'y',
			'can_send_bulletins'             => 'y',
			'can_search'                     => 'y',
			'can_create_entries'             => 'y',
			'can_edit_self_entries'          => 'y',
			'can_access_security_settings'   => 'y',
			'can_access_translate'           => 'y',
			'can_access_import'              => 'y',
			'can_access_sql_manager'         => 'y',
			'search_flood_control'           => '0'
		);

		ee()->db->where('group_id', 1)
			->update('member_groups', $permissions);
	}

}

// EOF
