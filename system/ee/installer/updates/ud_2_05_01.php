<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_2_5_1;

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
                '_update_ip_address_length',
            )
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function _update_ip_address_length()
    {
        ee()->load->dbforge();

        $tables = array('sessions', 'throttle', 'online_users',
            'security_hashes', 'captcha', 'password_lockout',
            'email_console_cache', 'members', 'channel_titles',
            'channel_entries_autosave', 'cp_log', 'member_search',
            'remember_me');

        foreach ($tables as $table) {
            $column_settings = array(
                'ip_address' => array(
                    'name' => 'ip_address',
                    'type' => 'varchar',
                    'constraint' => 45,
                    'default' => '0',
                    'null' => false
                )
            );

            if ($table == 'remember_me') {
                unset($column_settings['ip_address']['null']);
            }

            ee()->smartforge->modify_column($table, $column_settings);
        }
    }
}
/* END CLASS */

// EOF
