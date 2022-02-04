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
                    'condition_set_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => false,
                    ],
                    'condition_field_id' => [
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
            ee()->dbforge->add_key('condition_set_id');
            ee()->dbforge->add_key('condition_field_id');
            ee()->smartforge->create_table('field_conditions');
        }

        if (!ee()->db->table_exists('field_condition_sets_channel_fields')) {
            ee()->dbforge->add_field(
                [
                    'condition_set_id' => [
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
            ee()->dbforge->add_key(['condition_set_id', 'field_id']);
            ee()->smartforge->create_table('field_condition_sets_channel_fields');
        }
        if (! ee()->db->field_exists('field_is_conditional', 'channel_fields')) {
            ee()->smartforge->add_column(
                'channel_fields',
                array(
                    'field_is_conditional' => array(
                        'type' => 'CHAR(1)',
                        'null' => false,
                        'default' => 'n'
                    )
                )
            );
        }
    }

    private function addFieldHideColumns()
    {
        if (!ee()->db->table_exists('channel_data_hidden_fields')) {
            ee()->dbforge->add_field(
                [
                    'entry_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => false
                    ],
                    'site_id' => [
                        'type' => 'int',
                        'constraint' => 4,
                        'unsigned' => true,
                        'null' => false,
                        'default' => 1
                    ],
                    'channel_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => false
                    ],
                ]
            );
            ee()->dbforge->add_key('entry_id', true);
            ee()->dbforge->add_key('channel_id');
            ee()->dbforge->add_key('site_id');
            ee()->smartforge->create_table('channel_data_hidden_fields');
        }
        ee()->db->data_cache = []; // Reset the cache so it will re-fetch a list of tables
        
        $fields = ee('db')->select('field_id, legacy_field_data')
            ->from('channel_fields')
            ->get();
        if ($fields->num_rows() > 0) {
            foreach($fields->result_array() as $row) {
                if ($row['legacy_field_data'] == 'n') {
                    $table = 'channel_data_field_' . $row['field_id'];
                    $after = 'field_ft_' . $row['field_id'];
                } else {
                    $table = 'channel_data_hidden_fields';
                    $after = '';
                }
                $this->addFieldHideColumn($row['field_id'], $table, $after);
            }
        }
    }

    private function addFieldHideColumn($field_id, $table, $after = '', $prefix = '')
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
                ($after != '' ? ($prefix . 'field_ft_' . $field_id) : '')
            );
        }
    }
}

// EOF
