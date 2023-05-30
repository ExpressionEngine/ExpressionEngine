<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
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
                'installSliderFieldtypes',
                'addSiteColorColumn',
                'installButtonsFieldtype',
                'installNumberFieldtype',
                'installNotesFieldtype',
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

        if (!ee()->db->table_exists('channel_entry_hidden_fields')) {
            ee()->dbforge->add_field(
                [
                    'entry_id' => [
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
            ee()->dbforge->add_key(['entry_id', 'field_id']);
            ee()->smartforge->create_table('channel_entry_hidden_fields');
        }
    }

    private function installSliderFieldtypes()
    {
        if (ee()->db->where('name', 'slider')->get('fieldtypes')->num_rows() == 0) {
            ee()->db->insert(
                'fieldtypes',
                array(
                    'name' => 'slider',
                    'version' => '1.0.0',
                    'settings' => base64_encode(serialize(array())),
                    'has_global_settings' => 'n'
                )
            );
        }

        if (ee()->db->where('name', 'range_slider')->get('fieldtypes')->num_rows() == 0) {
            ee()->db->insert(
                'fieldtypes',
                array(
                    'name' => 'range_slider',
                    'version' => '1.0.0',
                    'settings' => base64_encode(serialize(array())),
                    'has_global_settings' => 'n'
                )
            );
        }
    }

    private function addSiteColorColumn()
    {
        if (! ee()->db->field_exists('site_color', 'sites')) {
            ee()->smartforge->add_column(
                'sites',
                array(
                    'site_color' => array(
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

    private function installNotesFieldtype()
    {
        if (ee()->db->where('name', 'notes')->get('fieldtypes')->num_rows() == 0) {
            ee()->db->insert(
                'fieldtypes',
                array(
                    'name' => 'notes',
                    'version' => '1.0.0',
                    'settings' => base64_encode(serialize(array())),
                    'has_global_settings' => 'n'
                )
            );
        }
    }

    private function installButtonsFieldtype()
    {
        if (ee()->db->where('name', 'selectable_buttons')->get('fieldtypes')->num_rows() > 0) {
            return;
        }

        ee()->db->insert(
            'fieldtypes',
            array(
                'name' => 'selectable_buttons',
                'version' => '1.0.0',
                'settings' => base64_encode(serialize(array())),
                'has_global_settings' => 'n'
            )
        );
    }

    private function installNumberFieldtype()
    {
        if (ee()->db->where('name', 'number')->get('fieldtypes')->num_rows() > 0) {
            return;
        }

        ee()->db->insert(
            'fieldtypes',
            array(
                'name' => 'number',
                'version' => '1.0.0',
                'settings' => base64_encode(serialize(array())),
                'has_global_settings' => 'n'
            )
        );
    }
}

// EOF
