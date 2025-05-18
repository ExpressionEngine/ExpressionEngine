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
                'addFilesTableColumns'
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addFilesTableColumns()
    {
        if (! ee()->db->field_exists('focal_x', 'files')) {
            ee()->smartforge->add_column(
                'files',
                [
                    'focal_x' => [
                        'type' => 'tinyint',
                        'constraint' => 1,
                        'default' => 50,
                        'unsigned' => true
                    ]
                ]
            );
        }
        if (! ee()->db->field_exists('focal_y', 'files')) {
            ee()->smartforge->add_column(
                'files',
                [
                    'focal_y' => [
                        'type' => 'tinyint',
                        'constraint' => 1,
                        'default' => 50,
                        'unsigned' => true
                    ]
                ]
            );
        }
    }
}

// EOF
