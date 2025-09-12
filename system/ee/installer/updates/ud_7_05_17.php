<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_5_17;

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
                'addHtmlButtonsTagIcon'
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addHtmlButtonsTagIcon()
    {
        if (!ee()->db->field_exists('tag_icon', 'html_buttons')) {
            ee()->smartforge->add_column(
                'html_buttons',
                [
                    'tag_icon' => [
                        'type' => 'varchar',
                        'constraint' => 254,
                        'default' => null,
                        'null' => true
                    ]
                ]
            );
        }
    }
}

// EOF
