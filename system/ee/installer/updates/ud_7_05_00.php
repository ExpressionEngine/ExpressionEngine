<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2024, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_5_0;

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
                'migrateLogsTable',
                'addLogsViewsTable'
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function migrateLogsTable()
    {
        if (! ee()->db->table_exists('logs')) {
            // create the table
            ee()->dbforge->add_field(
                [
                    'log_id' => [
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
                        'null' => false,
                        'default' => '0'
                    ],
                    'member_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => true,
                    ],
                    'log_date' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => false,
                        'default' => '0'
                    ],
                    'level' => [
                        'type' => 'int',
                        'constraint' => 3,
                        'null' => false,
                    ],
                    'channel' => [
                        'type' => 'varchar',
                        'constraint' => 45,
                        'null' => false,
                    ],
                    'message' => [
                        'type' => 'text',
                        'null' => false
                    ],
                    'context' => [
                        'type' => 'text',
                        'null' => true
                    ],
                    'extra' => [
                        'type' => 'text',
                        'null' => true
                    ],
                    'ip_address' => [
                        'type' => 'varchar',
                        'constraint' => 45,
                        'null' => false,
                        'default' => '0'
                    ],
                    'viewed' => [
                        'type' => 'char',
                        'constraint' => 1,
                        'null' => false,
                        'default' => 'n'
                    ],
                    'hash' => [
                        'type' => 'char',
                        'constraint' => 32,
                        'null' => true,
                    ],
                ]
            );
            ee()->dbforge->add_key('log_id', true);
            ee()->dbforge->add_key('site_id');
            ee()->dbforge->add_key('member_id');
            ee()->dbforge->add_key('channel');
            ee()->smartforge->create_table('logs');
        }

        // migrate cp_log
        ee('db')->query("INSERT INTO `exp_logs` (
                `site_id`,
                `member_id`,
                `log_date`,
                `level`,
                `channel`,
                `message`,
                `context`,
                `ip_address`
            ) 
            SELECT
                `site_id`,
                `member_id`,
                `act_date`,
                '200',
                'cp',
                `action`,
                CONCAT('{\"username\":\"', `username`, 'admin\"}'),
                `ip_address`
            FROM `exp_cp_log`");

        // migrate developer_log
        ee('db')->query("INSERT INTO `exp_logs` (
                `log_date`,
                `level`,
                `channel`,
                `message`,
                `viewed`)
            SELECT
                `timestamp`,
                '300',
                'developer',
                CONCAT_WS(' ',
                    `description`,
                    CONCAT('Deprecated function ', `function`, ' called'),
                    CONCAT(' in ', `file`, ' on line ', `line`, '.'),
                    CONCAT('From template tag exp:', addon_module, ':', `addon_method`, ' in ', `template_group`, '/', `template_name`, '.'),
                    CONCAT('This tag may have been parsed from one of these snippets: ', `snippets`),
                    CONCAT('Deprecated since ', `deprecated_since`, '.'),
                    CONCAT('Use ', `use_instead`, ' instead.')
                ),
                `viewed`
            FROM exp_developer_log");
    }

    private function addLogsViewsTable()
    {
        if (!ee()->db->table_exists('log_manager_views')) {
            ee()->dbforge->add_field(
                [
                    'view_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => false,
                        'auto_increment' => true
                    ],
                    'channel' => [
                        'type' => 'varchar',
                        'constraint' => 45,
                        'default' => null,
                        'null' => true,
                    ],
                    'member_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => false,
                    ],
                    'name' => [
                        'type' => 'varchar',
                        'constraint' => 128,
                        'null' => false,
                        'default' => '',
                    ],
                    'columns' => [
                        'type' => 'text',
                        'null' => false
                    ]
                ]
            );
            ee()->dbforge->add_key('view_id', true);
            ee()->dbforge->add_key(['member_id', 'channel']);
            ee()->smartforge->create_table('log_manager_views');
        }
    }
}
// EOF
