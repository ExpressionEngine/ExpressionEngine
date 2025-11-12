<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_6_0_0_b_1;

/**
 * Update
 */
class Updater
{
    public $version_suffix = '';

    /**
     * Do Update
     *
     * @return TRUE
     */
    public function do_update()
    {
        $steps = new \ProgressIterator(
            [
                'addConfigTable',
                'addEntryManagerTables',
                'addRoles',
                'addRoleGroups',
                'addAndPopulatePermissionsTable',
                'reassignChannelsToRoles',
                'reassignModulesToRoles',
                'reassignTemplateGroupsToRoles',
                'flipPolarityOnStatusRoleAccess',
                'flipPolarityOnTemplateRoleAccess',
                'flipPolarityOnUploadRoleAccess',
                'renameMemberGroupTable',
                'convertMembersGroupToPrimaryRole',
                'reassignLayoutsToPrimaryRole',
                'reassignEmailCacheToPrimaryRole',
                'removeDefaultAvatars',
                'removeJqueryAddon',
                'removeEmoticonAddon',
                'addColorPickerFieldType',
                'addLivePreview',
                'addWidgetsTable',
                'addDashboardLayoutsTable',
                'addLayoutWidgetsTable',
                'addAddonIcons',
                'migrateRte',
                'addStickyChannelPreference',
                'addMemberModuleActions',
                'runStatsModuleUpdate',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addConfigTable()
    {
        if (ee()->db->table_exists('config')) {
            return;
        }

        // Create table
        ee()->dbforge->add_field(
            [
                'config_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false,
                    'auto_increment' => true
                ],
                'site_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false,
                    'default' => 0
                ],
                'key' => [
                    'type' => 'varchar',
                    'constraint' => 64,
                    'null' => false,
                    'default' => '',
                ],
                'value' => [
                    'type' => 'text',
                ],
            ]
        );
        ee()->dbforge->add_key('config_id', true);
        ee()->dbforge->add_key(['site_id', 'key']);
        ee()->smartforge->create_table('config');

        // Populate table with existing config values
        $sites = ee()->db->get('sites');

        $prefs = [
            'site_channel_preferences',
            'site_member_preferences',
            'site_system_preferences',
            'site_template_preferences'
        ];

        $rename = [
            'default_member_group' => 'default_primary_role',
        ];

        foreach ($sites->result_array() as $site) {
            $site_id = $site['site_id'];
            foreach ($prefs as $pref) {
                $data = unserialize(base64_decode($site[$pref]));
                foreach ($data as $key => $value) {
                    $key = (isset($rename[$key])) ? $rename[$key] : $key;

                    ee('Model')->make('Config', [
                        'site_id' => $site_id,
                        'key' => $key,
                        'value' => $value
                    ])->save();
                }
            }
        }

        // Drop the columns from the sites table
        foreach ($prefs as $pref) {
            ee()->smartforge->drop_column('sites', $pref);
        }

        foreach (ee()->config->divination('install') as $pref) {
            $value = ee()->config->item($pref);

            if ($value) {
                ee('Model')->make('Config', [
                    'site_id' => 0,
                    'key' => $pref,
                    'value' => $value
                ])->save();
            }
        }
    }

    private function addEntryManagerTables()
    {
        if (! ee()->db->table_exists('entry_manager_views')) {
            ee()->dbforge->add_field(
                [
                    'view_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => false,
                        'auto_increment' => true
                    ],
                    'channel_id' => [
                        'type' => 'int',
                        'constraint' => 6,
                        'unsigned' => true,
                        'null' => false,
                    ],
                    'member_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => false,
                    ],
                    'name' => [
                        'type' => 'varchar',
                        'constraint' => 128,
                        'null' => false,
                        'default' => '',
                    ],
                    'columns' => [
                        'type' => 'text',
                        'null' => false
                    ]
                ]
            );
            ee()->dbforge->add_key('view_id', true);
            ee()->smartforge->create_table('entry_manager_views');
        }
    }

    private function removeDefaultAvatars()
    {
        $defaultAvatars = ee('db')->select('id')->where('name', 'Default Avatars')->get('upload_prefs');
        if ($defaultAvatars->num_rows() > 0) {
            foreach ($defaultAvatars->result_array() as $row) {
                ee('db')->where('id', $row['id'])->delete('upload_prefs');

                ee('db')->where('upload_id', $row['id'])->delete('upload_prefs_roles');
            }
        }

        // Remove avatar config items
        ee('Model')->get('Config')->filter('key', 'IN', ['enable_avatars', 'allow_avatar_uploads'])->delete();

        // Remove avatar member preference
        if (ee()->db->field_exists('display_avatars', 'members')) {
            ee()->dbforge->drop_column('members', 'display_avatars');
        }
    }

    private function removeJqueryAddon()
    {
        $addon = ee('db')->select('module_id')->where('module_name', 'Jquery')->get('modules');
        if ($addon->num_rows() > 0) {
            ee('db')->where('module_id', $addon->row('module_id'))->delete('modules');
            ee('db')->where('module_id', $addon->row('module_id'))->delete('module_member_roles');
        }
        ee('db')->where_in('class', ['Jquery', 'Jquery_mcp'])->delete('actions');
    }

    private function removeEmoticonAddon()
    {
        $addon = ee('db')->select('module_id')->where('module_name', 'Emoticon')->get('modules');
        if ($addon->num_rows() > 0) {
            ee('db')->where('module_id', $addon->row('module_id'))->delete('modules');
            ee('db')->where('module_id', $addon->row('module_id'))->delete('module_member_roles');
        }
        ee('db')->where_in('class', ['Emoticon', 'Emoticon_mcp'])->delete('actions');
    }

    private function addRoles()
    {
        if (ee()->db->table_exists('roles')) {
            return;
        }

        // Make exp_roles table
        ee()->dbforge->add_field(
            [
                'role_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false,
                    'auto_increment' => true
                ],
                'name' => [
                    'type' => 'varchar',
                    'constraint' => 100,
                    'null' => false
                ],
                'short_name' => [
                    'type' => 'varchar',
                    'constraint' => 50,
                    'null' => false
                ],
                'description' => [
                    'type' => 'text',
                    'null' => true
                ],
                'is_locked' => [
                    'type' => 'char',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 'n'
                ],
                'highlight' => [
                    'type' => 'varchar',
                    'constraint' => 6,
                    'default' => '',
                    'null' => false
                ]
            ]
        );
        ee()->dbforge->add_key('role_id', true);
        ee()->smartforge->create_table('roles');

        // Populate Roles with existing Groups
        ee()->db->select('group_id, group_title, group_description, is_locked');
        ee()->db->where('site_id', 1);
        $groups = ee()->db->get('member_groups');
        $insert = [];

        foreach ($groups->result() as $group) {
            // Short name conversion
            $short_name = strtolower($group->group_title);
            $short_name = str_replace(' ', '_', $short_name);
            $short_name = preg_replace('/[^a-z0-9\-\_]/iu', '', $short_name);

            $insert[] = [
                'role_id' => $group->group_id,
                'name' => $group->group_title,
                'short_name' => $short_name,
                'description' => $group->group_description,
                'is_locked' => $group->is_locked,
            ];
        }

        if (! empty($insert)) {
            ee()->db->insert_batch('roles', $insert);
        }

        // Add the member->role pivot table
        ee()->dbforge->add_field(
            [
                'member_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false
                ],
                'role_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false
                ]
            ]
        );
        ee()->dbforge->add_key(['member_id', 'role_id'], true);
        ee()->smartforge->create_table('members_roles');

        // Populate the member->role pivot table
        $members = ee()->db->select('member_id, group_id')->get('members');
        $insert = [];

        foreach ($members->result() as $member) {
            $insert[] = [
                'member_id' => $member->member_id,
                'role_id' => $member->group_id
            ];
        }

        if (! empty($insert)) {
            ee()->db->insert_batch('members_roles', $insert);
        }
    }

    private function addRoleGroups()
    {
        // Make exp_role_groups table
        ee()->dbforge->add_field(
            [
                'group_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false,
                    'auto_increment' => true
                ],
                'name' => [
                    'type' => 'varchar',
                    'constraint' => 100,
                    'null' => false
                ]
            ]
        );
        ee()->dbforge->add_key('group_id', true);
        ee()->smartforge->create_table('role_groups');

        // Add the role->role group pivot table
        ee()->dbforge->add_field(
            [
                'role_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false
                ],
                'group_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false
                ]
            ]
        );
        ee()->dbforge->add_key(['role_id', 'group_id'], true);
        ee()->smartforge->create_table('roles_role_groups');

        // Add the member->role group pivot table
        ee()->dbforge->add_field(
            [
                'member_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false
                ],
                'group_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false
                ]
            ]
        );
        ee()->dbforge->add_key(['member_id', 'group_id'], true);
        ee()->smartforge->create_table('members_role_groups');
    }

    private function addAndPopulatePermissionsTable()
    {
        if (ee()->db->table_exists('permissions')) {
            return;
        }

        ee()->dbforge->add_field(
            [
                'permission_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'null' => false,
                    'unsigned' => true,
                    'auto_increment' => true
                ],
                'role_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false
                ],
                'site_id' => [
                    'type' => 'int',
                    'constraint' => 5,
                    'unsigned' => true,
                    'null' => false
                ],
                'permission' => [
                    'type' => 'varchar',
                    'constraint' => 255,
                    'null' => false
                ],
            ]
        );
        ee()->dbforge->add_key('permission_id', true);
        ee()->smartforge->create_table('permissions');

        ee()->db->data_cache = []; // Reset the cache so it will re-fetch a list of tables
        ee()->smartforge->add_key('permissions', ['role_id', 'site_id'], 'role_id_site_id');

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

        $entry_permissions = [
            'can_create_entries',
            'can_edit_self_entries',
            'can_delete_self_entries',
            'can_edit_other_entries',
            'can_delete_all_entries',
            'can_assign_post_authors'
        ];

        $template_permissions = [
            'can_create_new_templates',
            'can_edit_templates',
            'can_delete_templates'
            //'manage_settings_template'
        ];

        $rename = [
            'can_admin_mbr_groups' => 'can_admin_roles',
            'can_email_member_groups' => 'can_email_roles',
            'can_create_member_groups' => 'can_create_roles',
            'can_delete_member_groups' => 'can_delete_roles',
            'can_edit_member_groups' => 'can_edit_roles',
        ];

        $groups = ee()->db->get('member_groups');

        foreach ($groups->result() as $group) {
            $assigned_channels = ee()->db->where('group_id', $group->group_id)->get('channel_member_groups');
            $template_groups = ee()->db->where('group_id', $group->group_id)->get('template_member_groups');
            foreach ($permissions as $permission) {
                // Since we assume "no" we only need to store "yes"
                if ($group->$permission == 'y') {
                    $permission = (array_key_exists($permission, $rename)) ? $rename[$permission] : $permission;
                    $insert[] = [
                        'role_id' => $group->group_id,
                        'site_id' => $group->site_id,
                        'permission' => $permission
                    ];
                }
            }
            foreach ($entry_permissions as $permission) {
                // Since we assume "no" we only need to store "yes"
                if ($group->$permission == 'y') {
                    foreach ($assigned_channels->result() as $row) {
                        $insert[] = [
                            'role_id' => $group->group_id,
                            'site_id' => $group->site_id,
                            'permission' => $permission . '_channel_id_' . $row->channel_id
                        ];
                    }
                }
            }
            foreach ($template_permissions as $permission) {
                // Since we assume "no" we only need to store "yes"
                if ($group->$permission == 'y') {
                    foreach ($template_groups->result() as $row) {
                        if ($permission == 'can_create_new_templates') {
                            $insert[] = [
                                'role_id' => $group->group_id,
                                'site_id' => $group->site_id,
                                'permission' => 'can_manage_settings_template_group_id_' . $row->template_group_id
                            ];
                            $permission = 'can_create_templates';
                        }
                        $insert[] = [
                            'role_id' => $group->group_id,
                            'site_id' => $group->site_id,
                            'permission' => $permission . '_template_group_id_' . $row->template_group_id
                        ];
                    }
                }
            }
        }

        if (! empty($insert)) {
            ee()->db->insert_batch('permissions', $insert);
        }

        ee()->smartforge->drop_column_batch('member_groups', $permissions);
    }

    private function reassignChannelsToRoles()
    {
        if (ee()->db->table_exists('channel_member_roles')) {
            return;
        }

        ee()->smartforge->modify_column('channel_member_groups', [
            'group_id' => [
                'name' => 'role_id',
                'type' => 'int',
                'constraint' => 10
            ]
        ]);

        ee()->smartforge->rename_table('channel_member_groups', 'channel_member_roles');
    }

    private function reassignModulesToRoles()
    {
        if (ee()->db->table_exists('module_member_roles')) {
            return;
        }

        ee()->smartforge->modify_column('module_member_groups', [
            'group_id' => [
                'name' => 'role_id',
                'type' => 'int',
                'constraint' => 10
            ]
        ]);

        ee()->smartforge->rename_table('module_member_groups', 'module_member_roles');
    }

    private function reassignTemplateGroupsToRoles()
    {
        if (ee()->db->table_exists('template_groups_roles')) {
            return;
        }

        ee()->smartforge->modify_column('template_member_groups', [
            'group_id' => [
                'name' => 'role_id',
                'type' => 'int',
                'constraint' => 10
            ]
        ]);

        ee()->smartforge->rename_table('template_member_groups', 'template_groups_roles');
    }

    private function flipPolarityOnStatusRoleAccess()
    {
        if (ee()->db->table_exists('statuses_roles')) {
            return;
        }

        ee()->dbforge->add_field(
            [
                'role_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false
                ],
                'status_id' => [
                    'type' => 'int',
                    'constraint' => 6,
                    'unsigned' => true,
                    'null' => false
                ]
            ]
        );
        ee()->dbforge->add_key(['role_id', 'status_id'], true);
        ee()->smartforge->create_table('statuses_roles');

        $role_ids = [];
        $roles = ee()->db->where_not_in('role_id', [1, 2, 3, 4])->get('roles')->result();
        foreach ($roles as $role) {
            $role_ids[$role->role_id] = $role->role_id;
        }

        $no_access = [];
        foreach (ee()->db->get('status_no_access')->result() as $row) {
            if (! array_key_exists($row->status_id, $no_access)) {
                $no_access[$row->status_id] = [];
            }

            $no_access[$row->status_id][] = $row->member_group;
        }

        $insert = [];

        ee()->db->select('status_id');
        $statuses = ee()->db->get('statuses')->result();
        foreach ($statuses as $status) {
            $status_id = $status->status_id;
            foreach ($role_ids as $role_id) {
                if (! array_key_exists($status_id, $no_access) ||
                     ! in_array($role_id, $no_access[$status_id])) {
                    $insert[] = [
                        'role_id' => $role_id,
                        'status_id' => $status_id
                    ];
                }
            }
        }

        if (! empty($insert)) {
            ee()->db->insert_batch('statuses_roles', $insert);
        }

        ee()->smartforge->drop_table('status_no_access');
    }

    private function flipPolarityOnTemplateRoleAccess()
    {
        if (ee()->db->table_exists('templates_roles')) {
            return;
        }

        ee()->dbforge->add_field(
            [
                'role_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false
                ],
                'template_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false
                ]
            ]
        );
        ee()->dbforge->add_key(['role_id', 'template_id'], true);
        ee()->smartforge->create_table('templates_roles');

        $role_ids = [];
        $roles = ee()->db->where_not_in('role_id', [1])->get('roles')->result();
        foreach ($roles as $role) {
            $role_ids[$role->role_id] = $role->role_id;
        }

        $no_access = [];
        foreach (ee()->db->get('template_no_access')->result() as $row) {
            if (! array_key_exists($row->template_id, $no_access)) {
                $no_access[$row->template_id] = [];
            }

            $no_access[$row->template_id][] = $row->member_group;
        }

        $insert = [];

        ee()->db->select('template_id');
        $templates = ee()->db->get('templates')->result();
        foreach ($templates as $template) {
            $template_id = $template->template_id;
            foreach ($role_ids as $role_id) {
                if (! array_key_exists($template_id, $no_access) ||
                     ! in_array($role_id, $no_access[$template_id])) {
                    $insert[] = [
                        'role_id' => $role_id,
                        'template_id' => $template_id
                    ];
                }
            }
        }

        if (! empty($insert)) {
            ee()->db->insert_batch('templates_roles', $insert);
        }

        ee()->smartforge->drop_table('template_no_access');
    }

    private function flipPolarityOnUploadRoleAccess()
    {
        if (ee()->db->table_exists('upload_prefs_roles')) {
            return;
        }

        ee()->dbforge->add_field(
            [
                'role_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false
                ],
                'upload_id' => [
                    'type' => 'int',
                    'constraint' => 4,
                    'unsigned' => true,
                    'null' => false
                ]
            ]
        );
        ee()->dbforge->add_key(['role_id', 'upload_id'], true);
        ee()->smartforge->create_table('upload_prefs_roles');

        $role_ids = [];
        $roles = ee()->db->where_not_in('role_id', [1, 2, 3, 4])->get('roles')->result();
        foreach ($roles as $role) {
            $role_ids[$role->role_id] = $role->role_id;
        }

        $no_access = [];
        foreach (ee()->db->get('upload_no_access')->result() as $row) {
            if (! array_key_exists($row->upload_id, $no_access)) {
                $no_access[$row->upload_id] = [];
            }

            $no_access[$row->upload_id][] = $row->member_group;
        }

        $insert = [];

        ee()->db->select('id');
        ee()->db->where('module_id', 0);
        $upload_prefs = ee()->db->get('upload_prefs')->result();
        foreach ($upload_prefs as $upload_pref) {
            $upload_pref_id = $upload_pref->id;
            foreach ($role_ids as $role_id) {
                if (! array_key_exists($upload_pref_id, $no_access) ||
                     ! in_array($role_id, $no_access[$upload_pref_id])) {
                    $insert[] = [
                        'role_id' => $role_id,
                        'upload_id' => $upload_pref_id
                    ];
                }
            }
        }

        if (! empty($insert)) {
            ee()->db->insert_batch('upload_prefs_roles', $insert);
        }

        ee()->smartforge->drop_table('upload_no_access');
    }

    private function renameMemberGroupTable()
    {
        if (ee()->db->table_exists('role_settings')) {
            return;
        }

        ee()->smartforge->modify_column('member_groups', [
            'group_id' => [
                'name' => 'role_id',
                'type' => 'int',
                'constraint' => 10
            ]
        ]);
        ee()->smartforge->drop_column('member_groups', 'group_title');
        ee()->smartforge->drop_column('member_groups', 'group_description');
        ee()->smartforge->drop_key('member_groups', 'PRIMARY');
        ee()->smartforge->add_key('member_groups', ['role_id', 'site_id']);
        ee()->smartforge->rename_table('member_groups', 'role_settings');

        ee()->db->query("ALTER TABLE `exp_role_settings` ADD `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`);");
    }

    private function convertMembersGroupToPrimaryRole()
    {
        ee()->smartforge->modify_column('members', [
            'group_id' => [
                'name' => 'role_id',
                'type' => 'int',
                'constraint' => 10
            ]
        ]);
    }

    private function reassignLayoutsToPrimaryRole()
    {
        ee()->smartforge->modify_column('layout_publish_member_groups', [
            'group_id' => [
                'name' => 'role_id',
                'type' => 'int',
                'constraint' => 10
            ]
        ]);

        ee()->smartforge->rename_table('layout_publish_member_groups', 'layout_publish_member_roles');
    }

    private function reassignEmailCacheToPrimaryRole()
    {
        ee()->smartforge->modify_column('email_cache_mg', [
            'group_id' => [
                'name' => 'role_id',
                'type' => 'int',
                'constraint' => 10
            ]
        ]);
    }

    private function addColorPickerFieldType()
    {
        if (ee()->db->where('name', 'colorpicker')->get('fieldtypes')->num_rows() > 0) {
            return;
        }

        ee()->db->insert(
            'fieldtypes',
            [
                'name' => 'colorpicker',
                'version' => '1.0.0',
                'settings' => base64_encode(serialize([])),
                'has_global_settings' => 'n'
            ]
        );
    }

    private function addLivePreview()
    {
        $row_data = array(
            'class' => 'Channel',
            'method' => 'live_preview'
        );

        ee()->db->where($row_data);
        $count = ee()->db->count_all_results('actions');

        if ($count == 0) {
            ee()->db->insert('actions', $row_data);
        }
    }

    private function addWidgetsTable()
    {
        if (ee()->db->table_exists('dashboard_widgets')) {
            return;
        }

        ee()->dbforge->add_field(
            [
                'widget_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'null' => false,
                    'unsigned' => true,
                    'auto_increment' => true
                ],
                'widget_name' => [
                    'type' => 'varchar',
                    'constraint' => 50,
                    'null' => true,
                    'default' => null
                ],
                'widget_data' => [
                    'type' => 'mediumtext',
                    'null' => true
                ],
                'widget_type' => [
                    'type' => 'varchar',
                    'constraint' => 10,
                    'null' => false
                ],
                'widget_source' => [
                    'type' => 'varchar',
                    'constraint' => 50,
                    'null' => false
                ],
                'widget_file' => [
                    'type' => 'varchar',
                    'constraint' => 100,
                    'null' => true,
                    'default' => null
                ]
            ]
        );
        ee()->dbforge->add_key('widget_id', true);
        ee()->smartforge->create_table('dashboard_widgets');

        ee()->db->data_cache = []; // Reset the cache so it will re-fetch a list of tables
    }

    private function addDashboardLayoutsTable()
    {
        if (ee()->db->table_exists('dashboard_layouts')) {
            return;
        }

        ee()->dbforge->add_field(
            [
                'layout_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'null' => false,
                    'unsigned' => true,
                    'auto_increment' => true
                ],
                'member_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => true,
                    'default' => null
                ],
                'role_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => true,
                    'default' => null
                ],
                'order' => [
                    'type' => 'varchar',
                    'constraint' => 255,
                    'null' => true,
                    'default' => null
                ],
            ]
        );
        ee()->dbforge->add_key('layout_id', true);
        ee()->dbforge->add_key('member_id');
        ee()->dbforge->add_key('role_id');
        ee()->smartforge->create_table('dashboard_layouts');

        ee()->db->data_cache = []; // Reset the cache so it will re-fetch a list of tables
    }

    private function addLayoutWidgetsTable()
    {
        if (ee()->db->table_exists('dashboard_layout_widgets')) {
            return;
        }

        ee()->dbforge->add_field(
            [
                'layout_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'null' => false,
                    'unsigned' => true
                ],
                'widget_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'null' => false,
                    'unsigned' => true
                ]
            ]
        );

        ee()->smartforge->create_table('dashboard_layout_widgets');

        ee()->db->data_cache = []; // Reset the cache so it will re-fetch a list of tables
        ee()->smartforge->add_key('dashboard_layout_widgets', ['layout_id', 'widget_id'], 'layouts_widgets');
    }

    private function addAddonIcons()
    {
        $row_data = array(
            'class' => 'File',
            'method' => 'addonIcon',
            'csrf_exempt' => 1
        );

        ee()->db->where($row_data);
        $count = ee()->db->count_all_results('actions');

        if ($count == 0) {
            ee()->db->insert('actions', $row_data);
        }
    }

    private function migrateRte()
    {
        if (ee()->db->table_exists('rte_toolsets')) {
            ee()->smartforge->drop_table('rte_toolsets');
            ee()->smartforge->drop_table('rte_tools');
            ee()->db->data_cache = [];

            require_once PATH_ADDONS . 'rte/upd.rte.php';
            $Rte_upd = new \Rte_upd();
            $Rte_upd->install_rte_toolsets_table();

            $row_data = array(
                'class' => 'Rte',
                'method' => 'pages_autocomplete'
            );
            ee()->db->insert('actions', $row_data);

            ee()->db->where('name', 'Rte')->update('fieldtypes', ['version' => '2.0.0']);

            ee()->db->where('module_name', 'Rte')->update('modules', ['module_version' => '2.0.0']);
        }
    }

    private function addStickyChannelPreference()
    {
        if (!ee()->db->field_exists('sticky_enabled', 'channels')) {
            ee()->smartforge->add_column(
                'channels',
                array(
                    'sticky_enabled' => array(
                        'type' => 'char',
                        'constraint' => 1,
                        'null' => false,
                        'default' => 'n'
                    )
                )
            );

            ee()->db->update('channels', ['sticky_enabled' => 'y']);
        }
    }

    private function addMemberModuleActions()
    {
        $actions = ['send_username', 'update_profile', 'upload_avatar'];
        foreach ($actions as $action) {
            ee('Model')->make('Action', [
                'class' => 'Member',
                'method' => $action
            ])->save();
        }
    }

    private function runStatsModuleUpdate()
    {
        $stats = ee('Addon')->get('stats');
        if ($stats && $stats->hasUpdate()) {
            $class = $stats->getInstallerClass();
            $UPD = new $class();

            if ($UPD->update($stats->getInstalledVersion()) !== false) {
                $module = ee('Model')->get('Module')
                    ->filter('module_name', 'Stats')
                    ->first();

                $module->module_version = $stats->getVersion();
                $module->save();
            }
        }
    }
}

// EOF
