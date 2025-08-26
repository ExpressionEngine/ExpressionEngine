<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_5_16;

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
            "sessions" => [
                "sess_start" => "bigint(10) unsigned default '0' NOT NULL",
                "auth_timeout" => "bigint(10) unsigned default '0' NOT NULL",
                "last_activity" => "bigint(10) unsigned default '0' NOT NULL",
            ],
            "throttle" => [
                "last_activity" => "bigint(10) unsigned DEFAULT '0' NOT NULL",
            ],
            "stats" => [
                "last_cache_clear" => "bigint(10) unsigned default '0' NOT NULL",
            ],
            "online_users" => [
                "date" => "bigint(10) unsigned default '0' NOT NULL"
            ],
            "security_hashes" => [
                "date" => "bigint(10) unsigned NOT NULL"
            ],
            "captcha" => [
                "date" => "bigint(10) unsigned NOT NULL"
            ],
            "reset_password" => [
                "date" => "bigint(10) NOT NULL",
            ],
            "members" => [
                "last_view_bulletins" => "bigint(10) NOT NULL default 0",
                "last_visit" => "bigint(10) unsigned default '0' NOT NULL",
                "last_activity" => "bigint(10) unsigned default '0' NOT NULL"
            ],
            "message_copies" => [
                "message_time_read" => "bigint(10) unsigned NOT NULL default 0",
            ],
            "member_bulletin_board" => [
                "bulletin_expires" => "bigint(10) unsigned NOT NULL DEFAULT 0",
            ],
            "developer_log" => [
                "timestamp" => "bigint(10) unsigned NOT NULL",
            ],
            "remember_me" => [
                "expiration" => "bigint(10) DEFAULT '0'",
                "last_refresh" => "bigint(10) DEFAULT '0'",
            ],
            "cookie_settings" => [
                "cookie_lifetime" => "bigint(10) unsigned DEFAULT NULL",
                "cookie_enforced_lifetime" => "bigint(10) unsigned DEFAULT NULL",
            ]
        ];
        foreach ($dateColumns as $table => $columns) {
            foreach ($columns as $column => $properties) {
                ee()->db->query("ALTER TABLE " . ee()->db->dbprefix($table) . " CHANGE COLUMN `" . $column . "` `" . $column . "` " . $properties);
            }
        }
    }
}

// EOF