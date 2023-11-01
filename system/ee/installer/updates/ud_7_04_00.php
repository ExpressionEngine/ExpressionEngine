<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_4_0;

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
                'addShowFieldNamesSetting',
                'increaseEmailLength',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function increaseEmailLength()
    {
        ee()->smartforge->modify_column(
            'members',
            array(
                'email' => array(
                    'name' => 'email',
                    'type' => 'varchar',
                    'constraint' => 254,
                    'null' => false
                )
            )
        );

        ee()->smartforge->modify_column(
            'email_cache',
            array(
                'from_email' => array(
                    'name' => 'from_email',
                    'type' => 'varchar',
                    'constraint' => 254,
                    'null' => false
                )
            )
        );
    }

    private function addShowFieldNamesSetting()
    {
        if (!ee()->db->field_exists('show_field_names', 'role_settings')) {
            ee()->smartforge->add_column(
                'role_settings',
                [
                    'show_field_names' => [
                        'type' => 'char',
                        'constraint' => 1,
                        'default' => 'y',
                        'null' => false
                    ]
                ]
            );
        }
    }
}

// EOF
