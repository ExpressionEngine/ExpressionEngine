<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_2_5;

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
                'addTitleInstructions',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addTitleInstructions()
    {
        if (! ee()->db->field_exists('title_field_instructions', 'channels')) {
            ee()->smartforge->add_column(
                'channels',
                array(
                    'title_field_instructions' => array(
                        'type' => 'TEXT',
                        'null' => true
                    )
                )
            );
        }
    }
}

// EOF
