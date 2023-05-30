<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Service\Addon\Installer;

/**
 * Block and Allow update class
 */
class Block_and_allow_upd extends Installer
{
    public $has_cp_backend = 'y';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Module Installer
     *
     * @access public
     * @return bool
     */
    public function install()
    {
        $installed = parent::install();
        if ($installed) {
            ee()->load->dbforge();

            $fields = array(
                'blockedlist_id' => array(
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'auto_increment' => true
                ),
                'blockedlist_type' => array(
                    'type' => 'varchar',
                    'constraint' => '20',
                ),
                'blockedlist_value' => array(
                    'type' => 'longtext'
                )
            );

            ee()->dbforge->add_field($fields);
            ee()->dbforge->add_key('blockedlist_id', true);
            ee()->dbforge->create_table('blockedlist');

            $fields = array(
                'allowedlist_id' => array(
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'auto_increment' => true
                ),
                'allowedlist_type' => array(
                    'type' => 'varchar',
                    'constraint' => '20',
                ),
                'allowedlist_value' => array(
                    'type' => 'longtext'
                )
            );

            ee()->dbforge->add_field($fields);
            ee()->dbforge->add_key('allowedlist_id', true);
            ee()->dbforge->create_table('allowedlist');
        }

        return $installed;
    }

    /**
     * Module Uninstaller
     *
     * @access public
     * @return bool
     */
    public function uninstall()
    {
        $uninstalled = parent::uninstall();
        if ($uninstalled) {
            ee()->load->dbforge();
            ee()->dbforge->drop_table('blockedlist');
            ee()->dbforge->drop_table('allowedlist');
        }

        return $uninstalled;
    }

    /**
     * Module Updater
     *
     * @access public
     * @return bool
     */
    public function update($current = '')
    {
        if (version_compare($current, '3.0.1', '<')) {
            ee()->load->dbforge();

            foreach (array('blacklisted', 'whitelisted') as $table_name) {
                if (ee()->db->table_exists($table_name)) {
                    $fields = array(
                        $table_name . '_value' => array(
                            'name' => $table_name . '_value',
                            'type' => 'LONGTEXT'
                        )
                    );

                    ee()->dbforge->modify_column($table_name, $fields);
                }
            }
        }

        if (version_compare($current, '3.0', '<')) {
            ee()->load->dbforge();

            $sql = array();

            //if the are using a very old version this table won't exist at all
            if (! ee()->db->table_exists('whitelisted')) {
                $fields = array(
                    'whitelisted_id' => array(
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'auto_increment' => true
                    ),
                    'whitelisted_type' => array(
                        'type' => 'varchar',
                        'constraint' => '20',
                    ),
                    'whitelisted_value' => array(
                        'type' => 'text'
                    )
                );

                ee()->dbforge->add_field($fields);
                ee()->dbforge->add_key('whitelisted_id', true);
                ee()->dbforge->create_table('whitelisted');
            } else {
                $sql[] = "ALTER TABLE `exp_blacklisted` ADD COLUMN `blacklisted_id` int(10) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY FIRST";
                $sql[] = "ALTER TABLE `exp_whitelisted` ADD COLUMN `whitelisted` int(10) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY FIRST";
            }

            foreach ($sql as $query) {
                ee()->db->query($query);
            }
        }

        if (version_compare($current, '4.0', '<')) {
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

        return true;
    }
}

// END CLASS

// EOF
