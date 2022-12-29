<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_3_0;

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
                'addEnforceAutoUrlTitle',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addEnforceAutoUrlTitle()
    {
        if (!ee()->db->field_exists('enforce_auto_url_title', 'channels')) {
            ee()->smartforge->add_column(
                'channels',
                array(
                    'enforce_auto_url_title' => array(
                        'type' => 'CHAR(1)',
                        'null' => false,
                        'default' => 'n'
                    )
                )
            );
        }
    }
}

// EOF
