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
            'addProFieldSettings',
            'addTotalMembersCount',
            'addEnableCliConfig',
        ]);

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addProFieldSettings()
    {
        if (!ee()->db->field_exists('enable_frontedit', 'channel_fields')) {
            ee()->smartforge->add_column(
                'channel_fields',
                [
                    'enable_frontedit' => [
                        'type' => 'char',
                        'constraint' => 1,
                        'default' => 'y',
                        'null' => false
                    ]
                ]
            );
        }
    }

    private function addEnableCliConfig()
    {
        // Enable the CLI by default
        ee()->config->update_site_prefs(['enable_cli' => 'y'], 'all');
    }

    private function addTotalMembersCount()
    {
        if (!ee()->db->field_exists('total_members', 'roles')) {
            ee()->smartforge->add_column(
                'roles',
                [
                    'total_members' => [
                        'type' => 'mediumint',
                        'constraint' => 8,
                        'null' => false,
                        'unsigned' => true,
                        'default' => 0
                    ]
                ]
            );
        }
    }
}

// EOF
