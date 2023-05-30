<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_6_0_0_b_3;

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
            'renameBlacklistModule',
            'addAllowPhpConfig',
            'modifyPagesColumn'
        ]);

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function renameBlacklistModule()
    {
        ee()->db->where('module_name', 'Blacklist')->update(
            'modules',
            [
                'module_name' => 'Block_and_allow',
                'module_version' => '4.0.0'
            ]
        );

        if (ee()->db->table_exists('blacklisted')) {
            $fields = array(
                'blacklisted_id' => array(
                    'name' => 'blockedlist_id',
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'auto_increment' => true
                ),
                'blacklisted_type' => array(
                    'name' => 'blockedlist_type',
                    'type' => 'varchar',
                    'constraint' => '20',
                ),
                'blacklisted_value' => array(
                    'name' => 'blockedlist_value',
                    'type' => 'longtext'
                )
            );
            ee()->smartforge->modify_column('blacklisted', $fields);
            ee()->smartforge->rename_table('blacklisted', 'blockedlist');
        }

        if (ee()->db->table_exists('whitelisted')) {
            $fields = array(
                'whitelisted_id' => array(
                    'name' => 'allowedlist_id',
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'auto_increment' => true
                ),
                'whitelisted_type' => array(
                    'name' => 'allowedlist_type',
                    'type' => 'varchar',
                    'constraint' => '20',
                ),
                'whitelisted_value' => array(
                    'name' => 'allowedlist_value',
                    'type' => 'longtext'
                )
            );

            ee()->smartforge->modify_column('whitelisted', $fields);
            ee()->smartforge->rename_table('whitelisted', 'allowedlist');
        }
    }

    private function addAllowPhpConfig()
    {
        //any of templates use PHP?
        $allow_php = ee('Config')->getFile()->get('allow_php', 'n');
        $query = ee()->db->select('template_id')->where('allow_php', 'y')->get('templates');
        if ($query->num_rows() > 0) {
            $allow_php = 'y';
        }
        ee('Config')->getFile()->set('allow_php', $allow_php, true);
    }

    private function modifyPagesColumn()
    {
        $mod = ee()->smartforge->modify_column('sites', [
            'site_pages' => [
                'name' => 'site_pages',
                'type' => 'MEDIUMTEXT',
                'null' => false
            ]
        ]);
    }
}

// EOF
