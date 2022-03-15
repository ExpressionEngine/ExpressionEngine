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
                'installButtonsFieldtype',
                'installNumberFieldtype',
                'installNotesFieldtype',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function installNotesFieldtype()
    {
        if (ee()->db->where('name', 'notes')->get('fieldtypes')->num_rows() == 0) {
            ee()->db->insert(
                'fieldtypes',
                array(
                    'name' => 'notes',
                    'version' => '1.0.0',
                    'settings' => base64_encode(serialize(array())),
                    'has_global_settings' => 'n'
                )
            );
        }
    }

    private function installButtonsFieldtype()
    {
        if (ee()->db->where('name', 'selectable_buttons')->get('fieldtypes')->num_rows() > 0) {
            return;
        }

        ee()->db->insert(
            'fieldtypes',
            array(
                'name' => 'selectable_buttons',
                'version' => '1.0.0',
                'settings' => base64_encode(serialize(array())),
                'has_global_settings' => 'n'
            )
        );
    }

    private function installNumberFieldtype()
    {
        if (ee()->db->where('name', 'number')->get('fieldtypes')->num_rows() > 0) {
            return;
        }

        ee()->db->insert(
            'fieldtypes',
            array(
                'name' => 'number',
                'version' => '1.0.0',
                'settings' => base64_encode(serialize(array())),
                'has_global_settings' => 'n'
            )
        );
    }
}

// EOF
