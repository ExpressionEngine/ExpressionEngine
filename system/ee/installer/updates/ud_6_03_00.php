<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_6_3_0;

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
                'addSiteColorColumn'
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addSiteColorColumn()
    {
        if (! ee()->db->field_exists('site_color', 'sites')) {
            ee()->smartforge->add_column(
                'sites',
                array(
                    'site_color' => array(
                        'type' => 'varchar',
                        'constraint' => 6,
                        'default' => '',
                        'null' => false
                    )
                )
            );
        }
        return true;
    }
}

// EOF
