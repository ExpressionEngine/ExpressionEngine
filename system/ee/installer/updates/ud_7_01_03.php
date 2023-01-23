<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_1_3;

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
                'modifyRevisionsColumns',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function modifyRevisionsColumns()
    {
        ee()->smartforge->modify_column(
            'revision_tracker',
            [
                'item_table' => [
                    'name' => 'item_table',
                    'type' => 'varchar',
                    'constraint' => 50,
                    'null' => false
                ]
            ]
        );

        ee()->smartforge->modify_column(
            'revision_tracker',
            [
                'item_field' => [
                    'name' => 'item_field',
                    'type' => 'varchar',
                    'constraint' => 32,
                    'null' => false
                ]
            ]
        );
    }
}

// EOF
