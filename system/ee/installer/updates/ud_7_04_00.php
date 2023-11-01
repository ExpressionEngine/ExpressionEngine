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
                'addCategoryGroupPermissions',
                'addShowFieldNamesSetting',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    // those that have edit_categories permissions get the new permission automatically
    private function addCategoryGroupPermissions()
    {
        $query = ee()->db->where('permission', 'can_edit_categories')
            ->get('permissions');
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
