<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_5_0;

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
                'makeMenusMsmSpecific',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function makeMenusMsmSpecific()
    {
        if (!ee()->db->field_exists('site_id', 'menu_sets')) {
            ee()->smartforge->add_column(
                'menu_sets',
                array(
                    'site_id' => array(
                        'type' => 'int',
                        'constraint' => 4,
                        'unsigned' => true,
                        'default' => '0',
                        'null' => false
                    )
                ),
                'set_id'
            );
            ee()->smartforge->add_key('menu_sets', 'site_id');
        }

        if (! ee()->db->table_exists('menu_set_roles')) {
            ee()->dbforge->add_field(
                [
                    'set_id' => [
                        'type' => 'int',
                        'constraint' => 4,
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
            ee()->dbforge->add_key(['set_id', 'role_id']);
            ee()->smartforge->create_table('menu_set_roles');

            $roles = ee()->db->select('role_id, menu_set_id')
                ->get('role_settings');
            $insert = [];
            if ($roles->num_rows() > 0) {
                foreach ($roles->result_array() as $row) {
                    $insert[] = [
                        'set_id' => $row['menu_set_id'],
                        'role_id' => $row['role_id']
                    ];
                }
                ee()->db->insert_batch('menu_set_roles', $insert);
            }
        }
    }
}

// EOF
