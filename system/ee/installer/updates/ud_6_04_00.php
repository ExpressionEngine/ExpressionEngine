<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_6_4_0;

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
                'modifyRevisionsColumns',
                'modifyCpHomepageChannelColumnOnMembers',
                'addLegacyFieldsConfig',
                'addExtensionsEnabledKey',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function modifyRevisionsColumns()
    {
        ee()->smartforge->modify_column(
            'revision_tracker',
            [
                'item_table' => [
                    'name' => 'item_table',
                    'type' => 'varchar',
                    'constraint' => 50,
                    'null' => false
                ]
            ]
        );

        ee()->smartforge->modify_column(
            'revision_tracker',
            [
                'item_field' => [
                    'name' => 'item_field',
                    'type' => 'varchar',
                    'constraint' => 32,
                    'null' => false
                ]
            ]
        );
    }
    private function modifyCpHomepageChannelColumnOnMembers()
    {
        ee()->smartforge->modify_column(
            'members',
            [
                'cp_homepage_channel' => [
                    'name' => 'cp_homepage_channel',
                    'type' => 'text',
                    'null' => true
                ]
            ]
        );
    }
    private function addLegacyFieldsConfig()
    {
        $legacyFieldExists = ee()->db->where('m_legacy_field_data', 'y')->from('member_fields')->count_all_results();
        ee('Model')->make('Config', [
            'site_id' => 0,
            'key' => 'legacy_member_data',
            'value' => $legacyFieldExists > 0 ? 'y' : 'n'
        ])->save();

        $legacyFieldExists = ee()->db->where('legacy_field_data', 'y')->from('channel_fields')->count_all_results();
        ee('Model')->make('Config', [
            'site_id' => 0,
            'key' => 'legacy_channel_data',
            'value' => $legacyFieldExists > 0 ? 'y' : 'n'
        ])->save();

        $legacyFieldExists = ee()->db->where('legacy_field_data', 'y')->from('category_fields')->count_all_results();
        ee('Model')->make('Config', [
            'site_id' => 0,
            'key' => 'legacy_category_field_data',
            'value' => $legacyFieldExists > 0 ? 'y' : 'n'
        ])->save();
    }

    private function addExtensionsEnabledKey()
    {
        ee()->smartforge->add_key('extensions', 'enabled');
    }

}

// EOF
