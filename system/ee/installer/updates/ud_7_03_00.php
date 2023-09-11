<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
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
                'addChannelProlet',
                'addEnforceAutoUrlTitle',
                'addWeekStartColForMembers',
                'setWeekStartPreference',
                'addFluidFieldGroups',
                'addFieldGroupDescription',
                'addFieldGroupShortname'
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addChannelProlet()
    {
        $channelModule = ee('pro:Addon')->get('channel');
        $channelModule->updateProlets();
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

    private function addWeekStartColForMembers()
    {
        if (! ee()->db->field_exists('week_start', 'members')) {
            ee()->smartforge->add_column(
                'members',
                [
                    'week_start' => [
                        'type' => 'varchar',
                        'constraint' => 8,
                        'null' => true
                    ]
                ],
                'date_format'
            );
        }
    }

    private function addFluidFieldGroups()
    {
        if (!ee()->db->field_exists('field_group_id', 'fluid_field_data')) {
            ee()->smartforge->add_column(
                'fluid_field_data',
                array(
                    'field_group_id' => [
                        'type' => 'int',
                        'unsigned' => true,
                        'null' => true,
                        'default' => null
                    ],
                    'group' => [
                        'type' => 'int',
                        'unsigned' => true,
                        'null' => true,
                        'default' => null
                    ]
                )
            );
        }
    }

    private function setWeekStartPreference()
    {
        ee('Model')->make('Config', [
            'site_id' => 0,
            'key' => 'week_start',
            'value' => 'sunday'
        ])->save();
    }

    private function addFieldGroupDescription()
    {
        if (!ee()->db->field_exists('group_description', 'field_groups')) {
            ee()->smartforge->add_column(
                'field_groups',
                [
                    'group_description' => [
                        'type' => 'text',
                        'null' => true,
                        'default' => null
                    ]
                ]
            );
        }
    }

    private function addFieldGroupShortname()
    {
        if (!ee()->db->field_exists('short_name', 'field_groups')) {
            ee()->smartforge->add_column(
                'field_groups',
                [
                    'short_name' => [
                        'type' => 'text',
                        'null' => false,
                    ]
                ]
            );
        }

        // loop through all field groups and set shortname to group name
        $field_groups = ee('db')->get('field_groups');
        if ($field_groups->num_rows() > 0) {
            foreach ($field_groups->result() as $field_group) {
                if (empty($field_group->short_name)) {
                    // change all spaces to underscores
                    $short_name = substr('field_group_' . preg_replace('/\s+/', '_', strtolower($field_group->group_name)), 0, 50);
                    ee('db')->set(['short_name' => $short_name])->where('group_id', $field_group->group_id)->update('field_groups');
                }
            }
        }
    }
}

// EOF
