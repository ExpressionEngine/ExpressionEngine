<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
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
                'addChannelIconColumn',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addChannelIconColumn()
    {
        if (! ee()->db->field_exists('channel_icon', 'channels')) {
            ee()->smartforge->add_column(
                'channels',
                array(
                    'channel_icon' => array(
                        'type' => 'VARCHAR',
                        'constraint' => 255,
                        'null' => true
                    )
                )
            );
        }
    }
}

// EOF
