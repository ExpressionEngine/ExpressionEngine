<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
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
            'addMfaMessageTemplate',
            'dropUnusedMemberColumns',
            'addProFieldSettings',
            'addTotalMembersCount',
            'addEnableCliConfig',
            'addMemberValidationAction',
            'setPasswordSecurityPolicy',
            'addPendingRoleToMember',
        ]);

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addPendingRoleToMember() 
    {
        if (!ee()->db->field_exists('pending_role_id', 'members')) {
            ee()->smartforge->add_column(
                'members',
                [
                    'pending_role_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'default' => '0',
                        'null' => false
                    ]
                ]
            );
        }
    }

    private function addMemberValidationAction()
    {

        $action = ee()->db->get_where('actions', array('class' => 'Member', 'method' => 'validate'));

        if ($action->num_rows() > 0) {
            return;
        }

        ee()->db->insert('actions', array(
            'class' => 'Member',
            'method' => 'validate',
        ));
    }

    private function setPasswordSecurityPolicy()
    {
        $require_secure_passwords = ee('Config')->getFile()->get('require_secure_passwords');
        if (!empty($require_secure_passwords)) {
            ee()->config->update_site_prefs(['password_security_policy' => ($require_secure_passwords == 'y' ? 'basic' : 'none')], 'all');
            ee()->config->_update_config([], ['require_secure_passwords' => 'require_secure_passwords']);
        } else {
            $sites = ee()->db->get('sites');
            foreach ($sites->result_array() as $site) {
                $password_security_policy = 'none';
                $configQuery = ee()->db->select('config_id, value')
                    ->from('config')
                    ->where('site_id', $site['site_id'])
                    ->where('key', 'require_secure_passwords')
                    ->get();
                if ($configQuery->num_rows() > 0 && $configQuery->row('value') == 'y') {
                    $password_security_policy = 'basic';
                }
                ee()->config->update_site_prefs(['password_security_policy' => $password_security_policy], $site['site_id']);
            }
        }
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
        if (!ee()->db->field_exists('mfa_flag', 'sessions')) {
            ee()->smartforge->add_column(
                'sessions',
                [
                    'mfa_flag' => [
                        'type' => 'enum',
                        'constraint' => "'skip','show','required'",
                        'default' => 'skip',
                        'null' => false
                    ]
                ]
            );
        }
    }

    protected function addMfaMessageTemplate()
    {
        $sites = ee('db')->select('site_id')->get('sites')->result();
        require_once SYSPATH . 'ee/language/' . (ee()->config->item('deft_lang') ?: 'english') . '/email_data.php';

        foreach ($sites as $site) {
            ee('Model')->make('SpecialtyTemplate')
                ->set([
                    'template_name' => 'mfa_template',
                    'template_type' => 'system',
                    'template_subtype' => null,
                    'data_title' => '',
                    'template_data' => mfa_message_template(),
                    'site_id' => $site->site_id,
                ])->save();
        }
    }

    private function addProFieldSettings()
    {
        if (!ee()->db->field_exists('enable_frontedit', 'channel_fields')) {
            ee()->smartforge->add_column(
                'channel_fields',
                [
                    'enable_frontedit' => [
                        'type' => 'char',
                        'constraint' => 1,
                        'default' => 'y',
                        'null' => false
                    ]
                ]
            );
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

    private function addEnableCliConfig()
    {
        // Enable the CLI by default
        ee()->config->update_site_prefs(['cli_enabled' => 'y'], 'all');
    }

    private function addTotalMembersCount()
    {
        if (!ee()->db->field_exists('total_members', 'roles')) {
            ee()->smartforge->add_column(
                'roles',
                [
                    'total_members' => [
                        'type' => 'mediumint',
                        'constraint' => 8,
                        'null' => false,
                        'unsigned' => true,
                        'default' => 0
                    ]
                ]
            );
        }
    }
}

// EOF
