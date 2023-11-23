<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
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
                'modifyDateColumns',
                'modifyDateFieldColumns'
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
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
