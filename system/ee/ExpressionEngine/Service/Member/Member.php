<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Member;

/**
 * Member Service
 */
class Member
{
    /* Member role constants */
    const SUPERADMIN = 1;
    const BANNED = 2;
    const GUESTS = 3;
    const PENDING = 4;
    const MEMBERS = 5;
    
    /**
     * Gets array of members who can be authors
     *
     * @param string $search Optional search string to filter members by
     * @param boolean $limited Limit the list to the default 100? Use FALSE sparingly
     * @return array ID => Screen name array of authors
     */
    public function getAuthors($search = null, $limited = true)
    {
        // First, get member groups who should be in the list
        $role_settings = ee('Model')->get('RoleSetting')
            ->with('Role')
            ->filter('include_in_authorlist', 'y')
            ->filter('site_id', ee()->config->item('site_id'))
            ->all();

        $roles = $role_settings->Role;
        $role_ids = $roles->pluck('role_id');

        $member_ids = [];
        foreach ($roles as $role) {
            $member_ids = array_merge($role->getAllMembers()->pluck('member_id'), $member_ids);
        }

        // Then authors who are individually selected to appear in author list
        $authors = ee('Model')->get('Member')
            ->fields('username', 'screen_name')
            ->filter('in_authorlist', 'y');

        if ($limited) {
            $authors->limit(100);
        }

        // Then grab any members that are part of the member groups we found
        if (! empty($member_ids)) {
            $authors->orFilter('member_id', 'IN', $member_ids);
        }

        if ($search) {
            $authors->search(
                ['screen_name', 'username', 'email', 'member_id'],
                $search
            );
        }

        $authors->order('screen_name');
        $authors->order('username');

        $author_options = [];
        foreach ($authors->all() as $author) {
            $author_options[$author->getId()] = $author->getMemberName();
        }

        return $author_options;
    }
}
// EOF
