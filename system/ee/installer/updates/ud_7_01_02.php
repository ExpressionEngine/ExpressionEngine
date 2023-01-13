<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_1_2;

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
                'dropOrphanMemberPivotRecords',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }
    
    private function dropOrphanMemberPivotRecords()
    {
        ee('db')->query('DELETE FROM exp_members_roles WHERE member_id NOT IN (SELECT member_id FROM exp_members)');
        ee('db')->query('DELETE FROM exp_members_role_groups WHERE member_id NOT IN (SELECT member_id FROM exp_members)');
    }
}

// EOF
