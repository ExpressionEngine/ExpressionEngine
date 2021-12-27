<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_6_3_0;

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
                'addConditionalFieldTables',
                'addFieldHideColumns',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addConditionalFieldTables()
    {
        if (!ee()->db->table_exists('field_condition_sets')) {
            ee()->dbforge->add_field(
                [
                    'condition_set_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => false,
                        'auto_increment' => true
                    ],
                    'field_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => false,
                    ],
                    'match' => [
                        'type' => 'varchar',
                        'constraint' => 20,
                        'null' => false,
                        'default' => 'all',
                    ],
                    'order' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => false,
                        'default' => 0
                    ],
                ]
            );
            ee()->dbforge->add_key('condition_set_id', true);
            ee()->dbforge->add_key('field_id');
            ee()->smartforge->create_table('field_condition_sets');
        }

        if (!ee()->db->table_exists('field_conditions')) {
            ee()->dbforge->add_field(
                [
                    'condition_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => false,
                        'auto_increment' => true
                    ],
                    'field_condition_set_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => false,
                    ],
                    'evaluation_rule' => [
                        'type' => 'varchar',
                        'constraint' => 100,
                        'null' => false,
                        'default' => '',
                    ],
                    'value' => [
                        'type' => 'varchar',
                        'constraint' => 255,
                        'null' => true,
                        'default' => null,
                    ],
                    'order' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => false,
                        'default' => 0
                    ],
                ]
            );
            ee()->dbforge->add_key('condition_id', true);
            ee()->dbforge->add_key('field_condition_set_id');
            ee()->smartforge->create_table('field_conditions');
        }

        if (!ee()->db->table_exists('field_conditions')) {
            ee()->dbforge->add_field(
                [
                    'field_condition_set_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => false
                    ],
                    'field_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => false
                    ]
                ]
            );
            ee()->dbforge->add_key(['field_condition_set_id', 'field_id'], true);
            ee()->smartforge->create_table('field_condition_sets_channel_fields');
        }
    }

    private function addFieldHideColumns()
    {
        $fields = ee('db')->select('field_id, legacy_field_data')
            ->from('channel_fields')
            ->get();
        if ($fields->num_rows() > 0) {
            foreach($fields->result_array() as $row) {
                if ($row['legacy_field_data'] == 'n') {
                    $table = 'channel_data_field_' . $row['field_id'];
                } else {
                    $table = 'channel_data';
                }
                $this->addFieldHideColumn($row['field_id'], $table);
            }
        }
    }

    private function addFieldHideColumn($field_id, $table, $prefix = '')
    {
        $field = $prefix . 'field_hide_' . $field_id;
        if (ee()->db->table_exists($table) && !ee()->db->field_exists($field, $table)) {
            ee()->smartforge->add_column(
                $table,
                [
                    $field => [
                        'type' => 'char',
                        'constraint' => 1,
                        'default' => 'n',
                        'null' => false
                    ]
                ],
                $prefix . 'field_ft_' . $field_id
            );
        }
    }
}

// EOF
