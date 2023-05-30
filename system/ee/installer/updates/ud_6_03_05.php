<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_6_3_5;

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
                'addDismissedBannerToMember',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addDismissedBannerToMember()
    {
        if (!ee()->db->field_exists('dismissed_banner', 'members')) {
            ee()->smartforge->add_column(
                'members',
                [
                    'dismissed_banner' => [
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
