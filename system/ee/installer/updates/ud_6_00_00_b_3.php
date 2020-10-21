<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_6_0_0_b_3;

/**
 * Update
 */
class Updater
{

    /**
     * Do Update
     *
     * @return TRUE
     */
    public function do_update()
    {
        $steps = new \ProgressIterator([
            'modifyPagesColumn',
            'renameBlacklistModule',
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
                'whitelisted_type'  => array(
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
}

// EOF
