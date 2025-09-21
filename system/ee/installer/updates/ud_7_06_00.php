<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_6_0;

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
                'addFluidFieldFilterTable',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addFluidFieldFilterTable()
    {
        if (ee()->db->table_exists('fluid_field_filters')) {
            return;
        }

        ee()->dbforge->add_field(
            [
                'filter_id' => [
                    'type' => 'int',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                    'auto_increment' => true
                ],
                'fluid_field_id' => [
                    'type' => 'int',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                ],
                'field_group_id' => [
                    'type' => 'int',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'default' => null
                ],
                'field_id' => [
                    'type' => 'int',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'default' => null
                ],
                'label' => [
                    'type' => 'varchar',
                    'constraint' => 100,
                    'null' => true
                ],
                'icon' => [
                    'type' => 'varchar',
                    'constraint' => 255,
                    'null' => true
                ],
                'instructions' => [
                    'type' => 'text',
                    'null' => true
                ],
                'modified_by_member_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 0
                ],
                'modified_date' => array(
                    'type' => 'bigint',
                    'constraint' => 10,
                    'null' => true,
                    'default' => null
                ),
            ]
        );
        ee()->dbforge->add_key('filter_id', true);
        ee()->dbforge->add_key(['fluid_field_id', 'field_id']);
        ee()->dbforge->add_key(['fluid_field_id', 'field_group_id']);
        ee()->smartforge->create_table('fluid_field_filters');
    }
}

// EOF
