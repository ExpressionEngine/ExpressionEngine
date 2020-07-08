<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Library\Advisor;

class MemberAdvisor
{

    public function membersWithoutGroup()
    {
        $groups = ee('Model')->get('MemberGroup')->all()->pluck('group_id');
        $sql = ee()->db->select('member_id, username, screen_name, email')
            ->from('members')
            ->where_not_in('group_id', $groups)
            ->get();
        return $sql->result_array();
    }

    public function deleteMembersWithoutGroup($member_ids)
    {

    }

    public function duplicateMembers()
    {

    }

    public function membersWithoutData()
    {

    }

    public function dataWithoutMembers()
    {

    }

    public function cleanOrphanedMembers()
    {
        // Clean up members if they have a group that doesnt exist
        $sql = "DELETE FROM exp_members
                WHERE group_id NOT IN (
                    SELECT DISTINCT group_id
                    FROM exp_member_groups
                    WHERE group_id IS NOT NULL
                );";

        ee()->db->query($sql);

        // Clean up member_data if it is attached to a member that doesnt exist
        $sql = "DELETE FROM exp_member_data
                WHERE member_id NOT IN (
                    SELECT DISTINCT member_id
                    FROM exp_members
                    WHERE member_id IS NOT NULL
                );";

        ee()->db->query($sql);
    }

}

// EOF
