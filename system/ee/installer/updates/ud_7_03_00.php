<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
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
                'addWeekStartColForMembers',
                'setWeekStartPreference',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addWeekStartColForMembers()
    {
        if (! ee()->db->field_exists('week_start', 'members')) {
            ee()->smartforge->add_column(
                'members',
                [
                    'week_start' => [
                        'type' => 'varchar',
                        'constraint' => 8,
                        'null' => true
                    ]
                ],
                'date_format'
            );
        }
    }

    private function setWeekStartPreference()
    {
        ee('Model')->make('Config', [
            'site_id' => 0,
            'key' => 'week_start',
            'value' => 'sunday'
        ])->save();
    }
}

// EOF
