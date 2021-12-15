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
                'addConditionalFieldTables'
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
                    'id' => [
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
                        'constraint' => 50,
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
            ee()->dbforge->add_key('id', true);
            ee()->dbforge->add_key('field_id', true);
            ee()->smartforge->create_table('field_condition_sets');
        }

        if (!ee()->db->table_exists('field_conditions')) {
            ee()->dbforge->add_field(
                [
                    'id' => [
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
                    'field_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => false,
                    ],
                    'operator' => [
                        'type' => 'varchar',
                        'constraint' => 256,
                        'null' => false,
                        'default' => '',
                    ],
                    'value' => [
                        'type' => 'varchar',
                        'constraint' => 256,
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
            ee()->dbforge->add_key('id', true);
            ee()->dbforge->add_key('field_id', true);
            ee()->dbforge->add_key('field_condition_set_id', true);
            ee()->smartforge->create_table('field_conditions');
        }
    }
}

// EOF
