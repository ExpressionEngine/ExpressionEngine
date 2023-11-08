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
                'ensureBuiltinRoles',
                'addShowFieldNamesSetting',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
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
