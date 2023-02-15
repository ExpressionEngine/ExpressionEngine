<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_2_10;

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
            'addRolesRoleGroupIndex',
        ]);

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    public function addRolesRoleGroupIndex()
    {
        // Add keys for roles_role_group.group_id
        ee()->smartforge->add_key('roles_role_groups', 'group_id', 'group_id_idx');

        return true;
    }
}

// EOF
