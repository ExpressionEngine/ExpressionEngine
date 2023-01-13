<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_3_5_1;

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
            array(
                'addFieldSettingsColumns'
            )
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addFieldSettingsColumns()
    {
        ee()->smartforge->add_column(
            'category_fields',
            array(
                'field_settings' => array(
                    'type' => 'text',
                    'null' => true
                )
            )
        );

        ee()->smartforge->add_column(
            'member_fields',
            array(
                'm_field_settings' => array(
                    'type' => 'text',
                    'null' => true
                )
            )
        );
    }
}

// EOF
