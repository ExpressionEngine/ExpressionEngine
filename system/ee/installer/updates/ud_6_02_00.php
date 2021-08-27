<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_6_2_0;

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
            'add2faToMembers',
        ]);

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function add2faToMembers()
    {
        if (!ee()->db->field_exists('enable_2fa', 'members')) {
            ee()->smartforge->add_column(
                'members',
                [
                    'enable_2fa' => [
                        'type' => 'char',
                        'constraint' => 1,
                        'default' => 'n',
                        'null' => false
                    ]
                ]
            );
        }
        if (!ee()->db->field_exists('require_2fa', 'role_settings')) {
            ee()->smartforge->add_column(
                'role_settings',
                [
                    'require_2fa' => [
                        'type' => 'char',
                        'constraint' => 1,
                        'default' => 'n',
                        'null' => false
                    ]
                ]
            );
        }
        if (!ee()->db->field_exists('valid_2fa', 'sessions')) {
            ee()->smartforge->add_column(
                'sessions',
                [
                    'valid_2fa' => [
                        'type' => 'char',
                        'constraint' => 1,
                        'default' => 'n',
                        'null' => false
                    ]
                ]
            );
        }
    }

}

// EOF
