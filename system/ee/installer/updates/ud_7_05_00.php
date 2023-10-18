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
                'addConditionColumns'
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addConditionColumns()
    {
        if (! ee()->db->field_exists('model_type', 'field_conditions')) {
            ee()->smartforge->add_column(
                'field_conditions',
                [
                    'model_type' => [
                        'type' => 'enum',
                        'constraint' => "'Field','Property','Category'",
                        'default' => 'Field',
                        'null' => false
                    ]
                ],
                'condition_set_id'
            );
            ee()->smartforge->add_key('field_conditions', 'model_type');
        }

        if (! ee()->db->field_exists('condition_field_name', 'field_conditions')) {
            ee()->smartforge->add_column(
                'field_conditions',
                [
                    'condition_field_name' => [
                        'type' => 'varchar',
                        'constraint' => 32,
                        'null' => true
                    ]
                ],
                'model_type'
            );
        }

        ee()->smartforge->modify_column(
            'field_conditions',
            [
                'condition_field_id' => [
                    'name' => 'condition_field_id',
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => true,
                    'default' => null
                ]
            ]
        );
    }


}

// EOF
