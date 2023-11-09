<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_4_0;

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
                'modifyMemberFieldTypeColumn',
                'addMemberManagerViewsTable',
                'addEditMemberFieldsPermission',
                'addRoleHighlightColumn',
                'addMemberRelationshipTable',
                'addMemberFieldtype',
                'addCategoryGroupPermissions',
                'ensureBuiltinRoles',
                'addShowFieldNamesSetting',
                'increaseEmailLength',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    public function modifyMemberFieldTypeColumn()
    {
        ee()->smartforge->modify_column(
            'member_fields',
            [
                'm_field_type' => [
                    'name' => 'm_field_type',
                    'type' => 'varchar',
                    'constraint' => 50,
                    'null' => false,
                    'default' => 'text'
                ]
            ]
        );
    }

    private function addMemberManagerViewsTable()
    {
        if (! ee()->db->table_exists('member_manager_views')) {
            ee()->dbforge->add_field(
                [
                    'view_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => false,
                        'auto_increment' => true
                    ],
                    'role_id' => [
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
            ee()->dbforge->add_key(['role_id', 'member_id']);
            ee()->smartforge->create_table('member_manager_views');
        }
    }

    private function addEditMemberFieldsPermission()
    {
        $permissions = ee('db')->where('permission', 'can_admin_roles')->get('permissions');
        if ($permissions->num_rows() > 0) {
            foreach ($permissions->result_array() as $row) {
                $insert = [
                    'role_id' => $row['role_id'],
                    'site_id' => $row['site_id'],
                    'permission' => 'can_edit_member_fields'
                ];
                ee('db')->insert('permissions', $insert);
            }
        }
    }

    private function addMemberRelationshipTable()
    {
        if (ee()->db->table_exists('member_relationships')) {
            return;
        }

        $fields = array(
            'relationship_id' => array(
                'type' => 'int',
                'constraint' => 6,
                'unsigned' => true,
                'auto_increment' => true
            ),
            'parent_id' => array(
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0
            ),
            'child_id' => array(
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0
            ),
            'field_id' => array(
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0
            ),
            'fluid_field_data_id' => array(
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0
            ),
            'grid_field_id' => array(
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0,
                'null' => false
            ),
            'grid_col_id' => array(
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0,
                'null' => false
            ),
            'grid_row_id' => array(
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0,
                'null' => false
            ),
            'order' => array(
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0
            )
        );

        ee()->dbforge->add_field($fields);

        // Worthless primary key
        ee()->dbforge->add_key('relationship_id', true);

        // Keyed table is keyed
        ee()->dbforge->add_key('parent_id');
        ee()->dbforge->add_key('child_id');
        ee()->dbforge->add_key('field_id');
        ee()->dbforge->add_key('fluid_field_data_id');
        ee()->dbforge->add_key('grid_row_id');

        ee()->dbforge->create_table('member_relationships');
    }

    private function addMemberFieldtype()
    {
        if (ee()->db->where('name', 'member')->get('fieldtypes')->num_rows() > 0) {
            return;
        }

        ee()->db->insert(
            'fieldtypes',
            array(
                'name' => 'member',
                'version' => '2.4.0',
                'settings' => base64_encode(serialize(array())),
                'has_global_settings' => 'n'
            )
        );
    }

    private function increaseEmailLength()
    {
        ee()->smartforge->modify_column(
            'members',
            array(
                'email' => array(
                    'name' => 'email',
                    'type' => 'varchar',
                    'constraint' => 254,
                    'null' => false
                )
            )
        );

        ee()->smartforge->modify_column(
            'email_cache',
            array(
                'from_email' => array(
                    'name' => 'from_email',
                    'type' => 'varchar',
                    'constraint' => 254,
                    'null' => false
                )
            )
        );
    }

    // those that have edit_categories permissions get the new permission automatically
    private function addCategoryGroupPermissions()
    {
        $query = ee()->db->where('permission', 'can_edit_categories')->get('permissions');

        foreach ($query->result_array() as $row) {
            $data = array(
                'site_id' => $row['site_id'],
                'role_id' => $row['role_id'],
                'permission' => 'can_create_category_groups'
            );
            ee()->db->insert('permissions', $data);
            $data = array(
                'site_id' => $row['site_id'],
                'role_id' => $row['role_id'],
                'permission' => 'can_edit_category_groups'
            );
            ee()->db->insert('permissions', $data);
            $data = array(
                'site_id' => $row['site_id'],
                'role_id' => $row['role_id'],
                'permission' => 'can_delete_category_groups'
            );
            ee()->db->insert('permissions', $data);
        }
    }

    // in some very old EE versions is was possible to delete built-in member groups
    // here we make sure the required roles are in place
    private function ensureBuiltinRoles()
    {
        $rolesQuery = ee('Model')->get('Role')->fields('role_id')->filter('role_id', 'IN', [1, 2, 3, 4, 5]);
        if ($rolesQuery->count() < 5) {
            if (!file_exists(SYSPATH . 'ee/installer/schema/mysqli_schema.php')) {
                return;
            }
            require_once SYSPATH . 'ee/installer/schema/mysqli_schema.php';
            $schema = new \EE_Schema();
            $roles = $schema->roles;
            $role_permissions = $schema->role_permissions;
            foreach ($rolesQuery->all() as $role) {
                unset($roles[$role->role_id]);
                unset($role_permissions[$role->role_id]);
            }

            $add_quotes = function ($value) {
                return (is_string($value)) ? "'{$value}'" : $value;
            };

            $Q = [];
            foreach ($roles as $role) {
                $Q[] = "INSERT INTO exp_roles
                    (role_id, name, short_name, is_locked)
                    VALUES (" . $role['role_id'] . ", '" . $role['name'] . "', '" . $role['short_name'] . "', '" . $role['is_locked'] . "')";

                unset($role['name']);
                unset($role['short_name']);
                unset($role['is_locked']);

                $Q[] = "INSERT INTO exp_role_settings
                    (" . implode(', ', array_keys($role)) . ")
                    VALUES (" . implode(', ', array_map($add_quotes, $role)) . ")";
            }

            $sites = ee()->db->select('site_id')->get('sites')->result_array();

            foreach ($role_permissions as $role_id => $permissions) {
                foreach ($permissions as $permission) {
                    foreach ($sites as $site) {
                        $Q[] = "INSERT INTO exp_permissions (site_id, role_id, permission) VALUES({$site['site_id']}, $role_id, '$permission')";
                    }
                }
            }

            foreach ($Q as $sql) {
                ee()->db->query($sql);
            }
        }
    }

    private function addRoleHighlightColumn()
    {
        if (! ee()->db->field_exists('highlight', 'roles')) {
            ee()->smartforge->add_column(
                'roles',
                array(
                    'highlight' => array(
                        'type' => 'varchar',
                        'constraint' => 6,
                        'default' => '',
                        'null' => false
                    )
                )
            );
        }
        return true;
    }

    private function addShowFieldNamesSetting()
    {
        if (!ee()->db->field_exists('show_field_names', 'role_settings')) {
            ee()->smartforge->add_column(
                'role_settings',
                [
                    'show_field_names' => [
                        'type' => 'char',
                        'constraint' => 1,
                        'default' => 'y',
                        'null' => false
                    ]
                ]
            );
        }
    }
}

// EOF
