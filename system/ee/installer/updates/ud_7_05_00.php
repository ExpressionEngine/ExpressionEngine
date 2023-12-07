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
                'addChannelOrderColumn',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addChannelOrderColumn()
    {
        if (!ee()->db->field_exists('channel_order', 'channels')) {
            ee()->smartforge->add_column(
                'channels',
                array(
                    'channel_order' => array(
                        'type' => 'INT',
                        'constraint' => 3,
                        'null' => false,
                        'unsigned' => true,
                        'default' => 0
                    )
                ),
                'channel_lang'
            );
        }
    }
}

// EOF
