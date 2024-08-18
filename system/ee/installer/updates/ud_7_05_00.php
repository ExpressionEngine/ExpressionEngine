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
                'addLogsViewsTable',
                'updateSpecialtyTemplates',
                'modifyDateColumns',
                'modifyDateFieldColumns'
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
                    ]
                ]
            );
            ee()->dbforge->add_key('log_id', true);
            ee()->dbforge->add_key('site_id');
            ee()->dbforge->add_key('channel');
            ee()->smartforge->create_table('logs');
        }

        // migrate cp_log
        ee('db')->query("INSERT INTO `exp_logs` (
                `site_id`,
                `log_date`,
                `level`,
                `channel`,
                `message`,
                `extra`,
                `ip_address`
            ) 
            SELECT
                `site_id`,
                `act_date`,
                '200',
                'cp',
                `action`,
                CONCAT('{\"current_username\":\"', `username`, '\", \"current_member_id\":\"', `member_id`, '\"}'),
                `ip_address`
            FROM `exp_cp_log`");

        // migrate developer_log
        ee('db')->query("INSERT INTO `exp_logs` (
                `log_date`,
                `level`,
                `channel`,
                `message`)
            SELECT
                `timestamp`,
                '300',
                'developer',
                IFNULL(`description`,
                    CONCAT_WS(' ',
                        CONCAT('Deprecated function ', `function`, ' called'),
                        CONCAT(' in ', `file`, ' on line ', `line`, '.'),
                        CONCAT('From template tag exp:', addon_module, ':', `addon_method`, ' in ', `template_group`, '/', `template_name`, '.'),
                        CONCAT('This tag may have been parsed from one of these snippets: ', `snippets`),
                        CONCAT('Deprecated since ', `deprecated_since`, '.'),
                        CONCAT('Use ', `use_instead`, ' instead.')
                    )
                )
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

    // if the specialty templates are using EE2 HTML, update those
    public function updateSpecialtyTemplates()
    {
        $file = SYSPATH . 'ee/language/' . ee()->lang->getIdiom() . '/email_data.php';
        if (!file_exists($file)) {
            return;
        }
        require_once $file;
        $EE2Hashes = [
            "offline_template" => "68ce4af17fad66887d26be7ec362b39f",
            "message_template" => "478bfbf1cd29a113e992cdeede826739",
            "admin_notify_reg" => "dedcd84fb5a949984f272e7992e76f00",
            "admin_notify_entry" => "e79c055ba45a622b66076a4315cc0691",
            "admin_notify_mailinglist" => "505f2ec45a4dc3fe11422b198975a9fd",
            "admin_notify_comment" => "120dc9265dab4c254711d7c9327c9a44",
            "mbr_activation_instructions" => "231a4da360cf31c1cc43de2abfd11212",
            "forgot_password_instructions" => "d3571a3cf9f4636ba92d9ee2af15bfa3",
            "validated_member_notify" => "6f0224b0dba522b8f9b5bfddcbbd4956",
            "decline_member_validation" => "241fcf2139373b52770710b29f19dda1",
            "mailinglist_activation_instructions" => "5233569223d3a8aff910e95fd690ace6",
            "comment_notification" => "5beaf573c2dac8c1726a7e5bc17a955f",
            "comments_opened_notification" => "5b1b04b5c2ecbb7bad4e93a4323503a4",
            "private_message_notification" => "7fc7632fefbfb49f59e126997654df99",
            "pm_inbox_full" => "3d10bc476f06476335ebd306a651df5b"
        ];
        $templatesQuery = ee()->db->get('specialty_templates');
        foreach ($templatesQuery->result_array() as $row) {
            if (isset($EE2Hashes[$row['template_name']])) {
                $hash = md5($row['template_data']);
                if ($hash === $EE2Hashes[$row['template_name']]) {
                    continue;
                }
                if (function_exists($row['template_name'])) {
                    $fn = $row['template_name'];
                    ee()->db->where('template_id', $row['template_id']);
                    ee()->db->update('specialty_templates', ['template_data' => $fn()]);
                }
            }
        }
    }

    /**
     * Modify built-in date columns to hold greater numbers, so we could allow dates past 2038
     */
    private function modifyDateColumns()
    {
        $dateColumns = [
            "stats" => [
                "last_entry_date" => "bigint(10) unsigned default '0' NOT NULL",
                "last_forum_post_date" => "bigint(10) unsigned default '0' NOT NULL",
                "last_comment_date" => "bigint(10) unsigned default '0' NOT NULL",
                "last_visitor_date" => "bigint(10) unsigned default '0' NOT NULL",
                "most_visitor_date" => "bigint(10) unsigned default '0' NOT NULL",
            ],
            "password_lockout" => [
                "login_date" => "bigint(10) unsigned NOT NULL"
            ],
            "email_cache" => [
                "cache_date" => "bigint(10) unsigned default '0' NOT NULL"
            ],
            "email_console_cache" => [
                "cache_date" => "bigint(10) unsigned default '0' NOT NULL"
            ],
            "members" => [
                "last_bulletin_date" => "bigint(10) NOT NULL default 0",
                "join_date" => "bigint(10) unsigned default '0' NOT NULL",
                "last_entry_date" => "bigint(10) unsigned default '0' NOT NULL",
                "last_comment_date" => "bigint(10) unsigned default '0' NOT NULL",
                "last_forum_post_date" => "bigint(10) unsigned default '0' NOT NULL",
                "last_email_date" => "bigint(10) unsigned default '0' NOT NULL",
            ],
            "channels" => [
                "last_entry_date" => "bigint(10) unsigned default '0' NOT NULL",
                "last_comment_date" => "bigint(10) unsigned default '0' NOT NULL"
            ],
            "channel_titles" => [
                "entry_date" => "bigint(10) NOT NULL",
                "expiration_date" => "bigint(10) NOT NULL default 0",
                "comment_expiration_date" => "bigint(10) NOT NULL default 0",
                "recent_comment_date" => "bigint(10) NULL DEFAULT NULL",
            ],
            "channel_entries_autosave" => [
                "entry_date" => "bigint(10) NOT NULL",
                "expiration_date" => "bigint(10) NOT NULL default 0",
                "comment_expiration_date" => "bigint(10) NOT NULL default 0",
                "recent_comment_date" => "bigint(10) NULL DEFAULT NULL",
            ],
            "entry_versioning" => [
                "version_date" => "bigint(10) NOT NULL",
            ],
            "cp_log" => [
                "act_date" => "bigint(10) NOT NULL",
            ],
            "templates" => [
                "edit_date" => "bigint(10) NOT NULL DEFAULT 0",
            ],
            "specialty_templates" => [
                "edit_date" => "bigint(10) NOT NULL DEFAULT 0"
            ],
            "global_variables" => [
                "edit_date" => "bigint(10) NOT NULL DEFAULT 0"
            ],
            "snippets" => [
                "edit_date" => "bigint(10) NOT NULL DEFAULT 0"
            ],
            "revision_tracker" => [
                "item_date" => "bigint(10) NOT NULL"
            ],
            "message_attachments" => [
                "attachment_date" => "bigint(10) unsigned NOT NULL default 0"
            ],
            "message_data" => [
                "message_date" => "bigint(10) unsigned NOT NULL default 0"
            ],
            "member_search" => [
                "search_date" => "bigint(10) unsigned NOT NULL"
            ],
            "member_bulletin_board" => [
                "bulletin_date" => "bigint(10) unsigned NOT NULL"
            ],
            "files" => [
                "upload_date" => "bigint(10) DEFAULT NULL",
                "modified_date" => "bigint(10) DEFAULT NULL"
            ],
            "consent_request_versions" => [
                "create_date" => "bigint(10) NOT NULL DEFAULT '0'"
            ],
            "consents" => [
                "expiration_date" => "bigint(10) DEFAULT NULL",
                "response_date" => "bigint(10) DEFAULT NULL"
            ],
            "consent_audit_log" => [
                "log_date" => "bigint(10) NOT NULL DEFAULT '0'"
            ],
        ];
        foreach ($dateColumns as $table => $columns) {
            foreach ($columns as $column => $properties) {
                ee()->db->query("ALTER TABLE " . ee()->db->dbprefix($table) . " CHANGE COLUMN `" . $column . "` `" . $column . "` " . $properties);
            }
        }
    }

    private function modifyDateFieldColumns()
    {
        $fieldsQuery = ee('db')->select('field_id, legacy_field_data')
            ->from('channel_fields')
            ->where('field_type', 'date')
            ->get();
        if ($fieldsQuery->num_rows() > 0) {
            foreach ($fieldsQuery->result_array() as $row) {
                $table = ($row['legacy_field_data'] == 'y') ? 'channel_data' : 'channel_data_field_' . $row['field_id'];
                $column = 'field_id_' . $row['field_id'];
                ee()->db->query("ALTER TABLE " . ee()->db->dbprefix($table) . " CHANGE COLUMN `" . $column . "` `" . $column . "` bigint(10) DEFAULT 0");
            }
        }

        $fieldsQuery = ee('db')->select('m_field_id, m_legacy_field_data')
            ->from('member_fields')
            ->where('m_field_type', 'date')
            ->get();
        if ($fieldsQuery->num_rows() > 0) {
            foreach ($fieldsQuery->result_array() as $row) {
                $table = ($row['m_legacy_field_data'] == 'y') ? 'member_data' : 'member_data_field_' . $row['m_field_id'];
                $column = 'm_field_id_' . $row['m_field_id'];
                ee()->db->query("ALTER TABLE " . ee()->db->dbprefix($table) . " CHANGE COLUMN `" . $column . "` `" . $column . "` bigint(10) DEFAULT 0");
            }
        }
    }
}

// EOF
