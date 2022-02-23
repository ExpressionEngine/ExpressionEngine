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
                'installSliderFieldtypes',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function installSliderFieldtypes()
    {
        if (ee()->db->where('name', 'slider')->get('fieldtypes')->num_rows() == 0) {
            ee()->db->insert(
                'fieldtypes',
                array(
                    'name' => 'slider',
                    'version' => '1.0.0',
                    'settings' => base64_encode(serialize(array())),
                    'has_global_settings' => 'n'
                )
            );
        }

        if (ee()->db->where('name', 'range_slider')->get('fieldtypes')->num_rows() == 0) {
            ee()->db->insert(
                'fieldtypes',
                array(
                    'name' => 'range_slider',
                    'version' => '1.0.0',
                    'settings' => base64_encode(serialize(array())),
                    'has_global_settings' => 'n'
                )
            );
        }
    }
}

// EOF
