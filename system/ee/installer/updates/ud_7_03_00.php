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
                'normalizeUploadDirectoryCategoryGroups',
                'normalizeChannelCategoryGroups',
                'addUUIDColumns',
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

    private function addUUIDColumns()
    {
        $models = [
            'CategoryGroup' => 'category_groups',
            'Category' => 'categories',
            'CategoryField' => 'category_fields',
            'Channel' => 'channels',
            'ChannelField' => 'channel_fields',
            'ChannelFieldGroup' => 'field_groups',
            'ChannelLayout' => 'layout_publish',
            'Status' => 'statuses',
            'FieldCondition' => 'field_conditions',
            'FieldConditionSet' => 'field_condition_sets',
            'UploadDestination' => 'upload_prefs',
            'FileDimension' => 'file_dimensions',
            'Watermark' => 'file_watermarks',
            'Role' => 'roles',
            'RoleGroup' => 'role_groups',
            'RoleSetting' => 'role_settings',
            'MemberField' => 'member_fields',
            'Site' => 'sites'
        ];
        foreach ($models as $model => $table) {
            $uuidField = ($model == 'MemberField') ? 'm_uuid' : 'uuid';
            if (!ee()->db->field_exists($uuidField, $table)) {
                ee()->db->query("ALTER TABLE exp_" . $table . " ADD " . $uuidField . " varchar(36) NULL DEFAULT NULL");
                ee()->db->query("ALTER TABLE exp_" . $table . " ADD UNIQUE (" . $uuidField . ")");
            }
        }
    }
}

// EOF
