<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_4_0;

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
                'normalizeUploadDirectoryCategoryGroups',
                'normalizeChannelCategoryGroups',
                'addCategoryGroupSettings',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function normalizeUploadDirectoryCategoryGroups()
    {
        if (ee()->db->table_exists('upload_prefs_category_groups')) {
            return;
        }

        ee()->dbforge->add_field(
            [
                'upload_location_id' => [
                    'type' => 'int',
                    'constraint' => 4,
                    'unsigned' => true,
                    'null' => false
                ],
                'group_id' => [
                    'type' => 'int',
                    'constraint' => 6,
                    'unsigned' => true,
                    'null' => false
                ]
            ]
        );
        ee()->dbforge->add_key(['upload_location_id', 'group_id'], true);
        ee()->dbforge->add_key('group_id');
        ee()->smartforge->create_table('upload_prefs_category_groups');

        $records = ee()->db->select('id, cat_group')->get('upload_prefs')->result();
        foreach ($records as $record) {
            if (!empty($record->cat_group)) {
                $cat_groups = explode('|', $record->cat_group);
                foreach ($cat_groups as $cat_group) {
                    ee('db')->insert('upload_prefs_category_groups', [
                        'upload_location_id' => $record->id,
                        'group_id' => $cat_group
                    ]);
                }
            }
        }
    }

    private function normalizeChannelCategoryGroups()
    {
        if (ee()->db->table_exists('channel_category_groups')) {
            return;
        }

        ee()->dbforge->add_field(
            [
                'channel_id' => [
                    'type' => 'int',
                    'constraint' => 4,
                    'unsigned' => true,
                    'null' => false
                ],
                'group_id' => [
                    'type' => 'int',
                    'constraint' => 6,
                    'unsigned' => true,
                    'null' => false
                ]
            ]
        );
        ee()->dbforge->add_key(['channel_id', 'group_id'], true);
        ee()->dbforge->add_key('group_id');
        ee()->smartforge->create_table('channel_category_groups');

        $records = ee()->db->select('channel_id, cat_group')->get('channels')->result();
        foreach ($records as $record) {
            if (!empty($record->cat_group)) {
                $cat_groups = explode('|', $record->cat_group);
                foreach ($cat_groups as $cat_group) {
                    ee('db')->insert('channel_category_groups', [
                        'channel_id' => $record->channel_id,
                        'group_id' => $cat_group
                    ]);
                }
            }
        }
    }

    private function addCategoryGroupSettings()
    {
        if (ee()->db->table_exists('category_group_settings')) {
            return;
        }

        ee()->dbforge->add_field(
            [
                'category_group_settings_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false,
                    'auto_increment' => true
                ],
                'site_id' => [
                    'type' => 'int',
                    'constraint' => 4,
                    'unsigned' => true,
                    'null' => false
                ],
                'channel_id' => [
                    'type' => 'int',
                    'constraint' => 4,
                    'unsigned' => true,
                    'null' => false
                ],
                'group_id' => [
                    'type' => 'int',
                    'constraint' => 6,
                    'unsigned' => true,
                    'null' => false
                ],
                'cat_required' => [
                    'type' => 'CHAR(1)',
                    'null' => false,
                    'default' => 'n'
                ],
                'cat_allow_multiple' => [
                    'type' => 'CHAR(1)',
                    'null' => false,
                    'default' => 'y'
                ]
            ]
        );
        ee()->dbforge->add_key('category_group_settings_id', true);
        ee()->dbforge->add_key(['channel_id', 'group_id'], true);
        ee()->dbforge->add_key('group_id');
        ee()->dbforge->add_key('site_id');
        ee()->smartforge->create_table('category_group_settings');
    }

}

// EOF
