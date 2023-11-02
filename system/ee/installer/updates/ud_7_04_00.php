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
                'addMemberRelationshipTable',
                'addMemberFieldtype',
                'addShowFieldNamesSetting',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
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
