<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_6_1_4;

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
        $steps = new \ProgressIterator (
            [
                'extendOnlineUsersNameColumn'
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function extendOnlineUsersNameColumn()
    {
        ee()->smartforge->modify_column(
            'online_users',
            array(
                'name' => array(
                    'name' => 'name',
                    'type' => 'varchar',
                    'constraint' => USERNAME_MAX_LENGTH,
                    'default' => '0',
                    'null' => false
                )
            )
        );
    }
}

// EOF
