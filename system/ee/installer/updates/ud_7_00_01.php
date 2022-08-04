<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_0_1;

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
                'addCpThemeToMember'
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addCpThemeToMember()
    {
        if (!ee()->db->field_exists('cp_theme', 'members')) {
            ee()->smartforge->add_column(
                'members',
                [
                    'cp_theme' => [
                        'type' => 'varchar',
                        'constraint' => 20,
                        'default' => null,
                        'null' => true
                    ]
                ]
            );
        }
    }
}

// EOF
