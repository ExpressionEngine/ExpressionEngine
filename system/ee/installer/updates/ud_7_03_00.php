<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_3_0;

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
                'addFluidFieldGroups',
                'addFieldGroupDescription'
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addFluidFieldGroups()
    {
        if (!ee()->db->field_exists('field_group_id', 'fluid_field_data')) {
            ee()->smartforge->add_column(
                'fluid_field_data',
                array(
                    'field_group_id' => [
                        'type' => 'int',
                        'unsigned' => true,
                        'null' => true,
                        'default' => null
                    ],
                    'group' => [
                        'type' => 'int',
                        'unsigned' => true,
                        'null' => true,
                        'default' => null
                    ]
                )
            );
        }
    }

    private function addFieldGroupDescription()
    {
        if (!ee()->db->field_exists('group_description', 'field_groups')) {
            ee()->smartforge->add_column(
                'field_groups',
                [
                    'group_description' => [
                        'type' => 'text',
                        'null' => true,
                        'default' => null
                    ]
                ]
            );
        }
    }
}

// EOF