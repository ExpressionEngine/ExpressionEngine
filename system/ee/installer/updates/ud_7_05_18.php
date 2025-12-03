<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_5_18;

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
        $steps = new \ProgressIterator([
            'addCacheTable',
            'addFilesIndexes',
        ]);

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addCacheTable()
    {
        if (ee()->db->table_exists('cache')) {
            return;
        }

        ee()->dbforge->add_field([
            'cache_key' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => false
            ],
            'data' => [
                'type' => 'longtext',
                'null' => false
            ],
            'ttl' => [
                'type' => 'int',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0
            ],
            'created_at' => [
                'type' => 'int',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false
            ]
        ]);

        ee()->dbforge->add_key('cache_key', true);
        ee()->dbforge->add_key('created_at');
        ee()->smartforge->create_table('cache');
    }

    public function addFilesIndexes()
    {
        // Add index for files.file_name
        ee()->smartforge->add_key('files', 'file_name', 'file_name');

        // Add index for files.title
        ee()->smartforge->add_key('files', 'title', 'title');

        return true;
    }
}

// EOF
