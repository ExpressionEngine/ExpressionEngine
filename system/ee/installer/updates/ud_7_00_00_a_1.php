<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_0_0_a_1;

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
                'addFieldDataTable',
                'addFileManagerViewsTable',
                'addEntryManagerViewsKeys'
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addFieldDataTable()
    {
        if (ee()->db->table_exists('file_data')) {
            return;
        }

        // Create table
        ee()->dbforge->add_field(
            [
                'file_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false,
                ],
            ]
        );
        ee()->dbforge->add_key('file_id', true);
        ee()->smartforge->create_table('file_data');

        ee('db')->query('INSERT INTO exp_file_data (file_id) SELECT file_id FROM exp_files');
    }

    private function addFileManagerViewsTable()
    {
        if (! ee()->db->table_exists('file_manager_views')) {
            ee()->dbforge->add_field(
                [
                    'view_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => false,
                        'auto_increment' => true
                    ],
                    'upload_id' => [
                        'type' => 'int',
                        'constraint' => 6,
                        'unsigned' => true,
                        'null' => false,
                    ],
                    'member_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => false,
                    ],
                    'name' => [
                        'type' => 'varchar',
                        'constraint' => 128,
                        'null' => false,
                        'default' => '',
                    ],
                    'columns' => [
                        'type' => 'text',
                        'null' => false
                    ]
                ]
            );
            ee()->dbforge->add_key('view_id', true);
            ee()->dbforge->add_key(['upload_id', 'member_id']);
            ee()->smartforge->create_table('file_manager_views');
        }
    }

    private function addEntryManagerViewsKeys()
    {
        ee()->smartforge->add_key('entry_manager_views', ['channel_id', 'member_id']);
    }

}

// EOF
