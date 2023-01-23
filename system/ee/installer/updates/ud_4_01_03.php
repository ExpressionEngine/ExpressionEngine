<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_4_1_3;

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
                'alterLogActionmColumn',
                'alterFluidFieldToMediumText'
            )
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function alterLogActionmColumn()
    {
        ee()->smartforge->modify_column(
            'cp_log',
            [
                'action' => [
                    'name' => 'action',
                    'type' => 'text',
                    'null' => false
                ]
            ]
        );
    }

    private function alterFluidFieldToMediumText()
    {
        $field_ids = ee('Model')->get('ChannelField')
            ->fields('field_id')
            ->filter('field_type', 'fluid_field')
            ->all()
            ->pluck('field_id');

        foreach ($field_ids as $field_id) {
            ee()->smartforge->modify_column(
                'channel_data_field_' . $field_id,
                [
                    'field_id_' . $field_id => [
                        'type' => 'mediumtext'
                    ]
                ]
            );
        }
    }
}

// EOF
