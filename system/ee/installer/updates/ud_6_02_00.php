<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_6_2_0;

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
        $steps = new \ProgressIterator([
            'addMfaColumns',
            'add2FAMessageTemplate',
            'dropUnusedMemberColumns',
        ]);

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addMfaColumns()
    {
        if (!ee()->db->field_exists('enable_mfa', 'members')) {
            ee()->smartforge->add_column(
                'members',
                [
                    'enable_mfa' => [
                        'type' => 'char',
                        'constraint' => 1,
                        'default' => 'n',
                        'null' => false
                    ]
                ]
            );
        }
        if (!ee()->db->field_exists('backup_mfa_code', 'members')) {
            ee()->smartforge->add_column(
                'members',
                [
                    'backup_mfa_code' => [
                        'type' => 'varchar',
                        'constraint' => 128,
                        'default' => null,
                        'null' => true
                    ]
                ]
            );
        }
        if (!ee()->db->field_exists('require_mfa', 'role_settings')) {
            ee()->smartforge->add_column(
                'role_settings',
                [
                    'require_mfa' => [
                        'type' => 'char',
                        'constraint' => 1,
                        'default' => 'n',
                        'null' => false
                    ]
                ]
            );
        }
        if (!ee()->db->field_exists('skip_mfa', 'sessions')) {
            ee()->smartforge->add_column(
                'sessions',
                [
                    'skip_mfa' => [
                        'type' => 'char',
                        'constraint' => 1,
                        'default' => 'y',
                        'null' => false
                    ]
                ]
            );
        }
        if (!ee()->db->field_exists('require_mfa', 'templates')) {
            ee()->smartforge->add_column(
                'templates',
                [
                    'require_mfa' => [
                        'type' => 'char',
                        'constraint' => 1,
                        'default' => 'n',
                        'null' => false
                    ]
                ]
            );
        }
    }

    protected function add2FAMessageTemplate()
    {
        $sites = ee('Model')->get('Site')->all();
        require_once SYSPATH . 'ee/language/' . (ee()->config->item('deft_lang') ?: 'english') . '/email_data.php';

        foreach ($sites as $site) {
            ee('Model')->make('SpecialtyTemplate')
                ->set([
                    'template_name' => 'two-fa',
                    'template_type' => 'system',
                    'template_subtype' => null,
                    'data_title' => '',
                    'template_data' => mfa_message_template(),
                    'site_id' => $site->site_id,
                ])->save();
        }
    }

    private function dropUnusedMemberColumns()
    {
        if (ee()->db->field_exists('rte_enabled', 'members')) {
            ee()->smartforge->drop_column('members', 'rte_enabled');
        }

        if (ee()->db->field_exists('rte_toolset_id', 'members')) {
            ee()->smartforge->drop_column('members', 'rte_toolset_id');
        }
    }

}

// EOF
