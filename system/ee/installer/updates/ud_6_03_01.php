<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_6_3_1;

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
                'modifyCpHomepageChannelColumnOnMembers',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function modifyCpHomepageChannelColumnOnMembers()
    {
        ee()->smartforge->modify_column(
            'members',
            [
                'cp_homepage_channel' => [
                    'name' => 'cp_homepage_channel',
                    'type' => 'text',
                    'null' => true
                ]
            ]
        );
    }
}

// EOF
