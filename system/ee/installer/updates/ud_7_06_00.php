<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_6_0;

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
                'addDataPermissions'
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    public function addDataPermissions()
    {
        ee()->db->query("INSERT INTO exp_permissions (role_id, site_id, permission)
            SELECT role_id, site_id, 'can_clear_cache'
            FROM exp_permissions
            WHERE permission = 'can_access_data'");
        ee()->db->query("INSERT INTO exp_permissions (role_id, site_id, permission)
            SELECT role_id, site_id, 'can_sync_and_reindex'
            FROM exp_permissions
            WHERE permission = 'can_access_data'");
        ee()->db->query("INSERT INTO exp_permissions (role_id, site_id, permission)
            SELECT role_id, site_id, 'can_sandr'
            FROM exp_permissions
            WHERE permission = 'can_access_data'");
    }
}

// EOF
