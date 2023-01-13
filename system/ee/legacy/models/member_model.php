<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Member Model
 */
class Member_model extends CI_Model
{
    /**
     * Get Username
     *
     * Get a username from a member id
     *
     * @access	public
     * @param	int
     * @param	string
     */
    public function get_username($id = '', $field = 'screen_name')
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");
        if ($id == '') {
            // no id, return false
            return false;
        }

        $this->db->select('username, screen_name');
        $this->db->where('member_id', $id);
        $member_info = $this->db->get('members');

        if ($member_info->num_rows() != 1) {
            // no match, return false
            return false;
        } else {
            $member_name = $member_info->row();
            if ($field == 'username') {
                return $member_name->username;
            } else {
                return $member_name->screen_name;
            }
        }
    }

    /**
     * Get Upload Groups
     *
     * @access	public
     * @return	mixed
     */
    public function get_upload_groups()
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        $this->db->select('group_id, group_title');
        $this->db->from('member_groups');
        $this->db->where("group_id != '1' AND group_id != '2' AND group_id != '3' AND group_id != '4'");
        $this->db->where('site_id', $this->config->item('site_id'));
        $this->db->order_by('group_title');

        return $this->db->get();
    }

    /**
     * Get Memmbers
     *
     * Get a collection of members
     *
     * @access	public
     * @param	int
     * @param	int
     * @param	int
     * @param	string
     * @return	mixed
     */
    public function get_members($group_id = '', $limit = '', $offset = '', $search_value = '', $order = array(), $column = 'all')
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        // Is a unique order by specified
        $add_orderby = true;

        $this->db->select("members.username, members.member_id, members.screen_name, members.email, members.join_date, members.last_visit, members.group_id, members.in_authorlist");

        $this->_prep_search_query($group_id, $search_value, $column);

        if ($limit != '') {
            $this->db->limit($limit);
        }

        if ($offset != '') {
            $this->db->offset($offset);
        }

        if (is_array($order) && count($order) > 0) {
            foreach ($order as $key => $val) {
                if ($key == 'member_id') {
                    $add_orderby = false;
                }

                $this->db->order_by($key, $val);
            }
        } else {
            $this->db->order_by('join_date');
        }

        if ($add_orderby) {
            $this->db->order_by('member_id');
        }

        $members = $this->db->get('members');

        if ($members->num_rows() == 0) {
            return false;
        } else {
            return $members;
        }
    }

    /**
     *	Count Members
     *
     *	@access public
     *	@return int
     */
    public function get_member_count($group_id = false)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        $member_ids = array();

        if ($group_id != '') {
            $this->db->select('member_id');
            $this->db->where('group_id', $group_id);
            $query = $this->db->get('members');

            foreach ($query->result() as $member) {
                $member_ids[] = $member->member_id;
            }

            // no member_ids in that group?	 Might as well return now
            if (count($member_ids) < 1) {
                return false;
            }
        }

        // now run the query for the actual results
        if ($group_id) {
            $this->db->where_in("members.member_id", $member_ids);
        }

        $this->db->select("COUNT(*) as count");
        $this->db->from("member_groups");
        $this->db->from("members");
        $this->db->where("members.group_id = " . $this->db->dbprefix("member_groups.group_id"));
        $this->db->where("member_groups.site_id", $this->config->item('site_id'));

        $members = $this->db->get();

        return ($members->num_rows() == 0) ? false : $members->row('count');
    }

    /**
     * Get All Member Fields
     *
     * @access	public
     * @param	array	// associative array of where
     * @param	bool	// restricts to public fields for non-superadmins
     * @return	object
     */
    public function get_all_member_fields($additional_where = array(), $restricted = true)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        // Extended profile fields
        $this->db->from('member_fields');

        if ($restricted == true && ! ee('Permission')->isSuperAdmin()) {
            $this->db->where('m_field_public', 'y');
        }

        foreach ($additional_where as $where) {
            foreach ($where as $field => $value) {
                if (is_array($value)) {
                    $this->db->where_in($field, $value);
                } else {
                    $this->db->where($field, $value);
                }
            }
        }

        $this->db->order_by('m_field_order');

        return $this->db->get();
    }

    /**
     * Get Member Data
     *
     * @access	public
     * @return	object
     */
    public function get_all_member_data($id)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        $this->db->from('member_data');
        $this->db->where('member_id', $id);

        return $this->db->get();
    }

    /**
     * Get Member Data
     *
     * This function retuns author data for a single member
     *
     * @access	public
     * @param	integer		Member Id
     * @param	array		Optional fields to return
     * @return	mixed
     */
    public function get_member_data($member_id = false, $fields = array())
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        if (count($fields) >= 1) {
            $this->db->select($fields);
        }

        $this->db->where('member_id', (int) $member_id);

        return $this->db->get('members');
    }

    /**
     * Get Member Ignore List
     *
     * This function retuns author data for a single member
     *
     * @access	public
     * @param	integer		Member Id
     * @return	object
     */
    public function get_member_ignore_list($member_id = false)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        $query = $this->get_member_data($this->id, array('ignore_list'));

        $ignored = ($query->row('ignore_list') == '') ? array('') : explode('|', $query->row('ignore_list'));

        $this->db->select('screen_name, member_id');
        $this->db->where_in('member_id', $ignored);
        $this->db->order_by('screen_name');

        return $this->db->get('members');
    }

    /**
     * Get Member Quicklinks
     *
     * This function retuns an array of the users quick links
     *
     * @access	public
     * @param	integer		Member Id
     * @return	array
     */
    public function get_member_quicklinks($member_id = false)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member', \$member_id)->first()->getQuicklinks()");

        $quicklinks_query = $this->get_member_data($member_id, array('quick_links'))->row('quick_links');

        $i = 1;

        $quicklinks = array();

        if (! empty($quicklinks_query)) {
            foreach (explode("\n", $quicklinks_query) as $row) {
                $x = explode('|', $row);

                $quicklinks[$i]['title'] = (isset($x['0'])) ? $x['0'] : '';
                $quicklinks[$i]['link'] = (isset($x['1'])) ? $x['1'] : '';
                $quicklinks[$i]['order'] = (isset($x['2'])) ? $x['2'] : '';

                $i++;
            }
        }

        return $quicklinks;
    }

    /**
     * Get Member Emails
     *
     * By default fetches member_id, email, and screen_name.  Additional fields and
     * WHERE clause can be specified by using the array arguments
     *
     * @access	public
     * @param	array
     * @param	array	array of associative field => value arrays
     * @return	object
     */
    public function get_member_emails($additional_fields = array(), $additional_where = array())
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        if (! is_array($additional_fields)) {
            $additional_fields = array($additional_fields);
        }

        if (! isset($additional_where[0])) {
            $additional_where = array($additional_where);
        }

        if (count($additional_fields) > 0) {
            $this->db->select(implode(',', $additional_fields));
        }

        $this->db->select("m.member_id, m.screen_name, m.email");
        $this->db->from("members AS m");
        $this->db->join('roles AS r', 'r.role_id = m.role_id');
        
        foreach ($additional_where as $where) {
            foreach ($where as $field => $value) {
                if (is_array($value)) {
                    $this->db->where_in($field, $value);
                } else {
                    $this->db->where($field, $value);
                }
            }
        }

        $this->db->order_by('member_id');

        return $this->db->get();
    }

    /**
     * Create member
     *
     * This function creates a new member
     *
     * @access	public
     * @param	array
     * @param	mixed // custom member data optional
     * @return	int		member id
     */
    public function create_member($data = array(), $cdata = false)
    {
        // ---------------------------------------------------------------
        // 'member_create_start' hook.
        // - Provides an opportunity for extra code to be executed upon
        // member creation, and also gives the opportunity to modify the
        // member data by altering the arrays of data that we pass to the
        // hook.
        if ($this->extensions->active_hook('member_create_start')) {
            list($data, $cdata) = $this->extensions->call('member_create_start', $data, $cdata);
        }
        //
        // ---------------------------------------------------------------

        // Insert into the main table
        $member = ee('Model')->make('Member');
        $member->set(array_merge($data, $cdata));
        $member->validate();
        $member->save();

        // grab insert id
        $member_id = $member->getId();

        // ---------------------------------------------------------------
        // 'member_create_end' hook.
        // - Provides an opportunity for extra code to be executed after
        // member creation.
        if ($this->extensions->active_hook('member_create_end')) {
            $this->extensions->call('member_create_end', $member_id, $data, $cdata);
        }
        //
        // ---------------------------------------------------------------

        return $member_id;
    }

    /**
     * Update member
     *
     * This function updates a member
     *
     * @access	public
     * @param	int
     * @param	array
     * @return	void
     */
    public function update_member($member_id = '', $data = array(), $additional_where = array())
    {
        // ---------------------------------------------------------------
        // 'member_update_start' hook.
        // - Provides an opportunity for extra code to be executed upon
        // member update, and also gives the opportunity to modify the
        // update for member data by altering the array of data that we
        // pass to the hook.
        //
        if ($this->extensions->active_hook('member_update_start')) {
            $data = $this->extensions->call('member_update_start', $member_id, $data);
        }
        //
        // ---------------------------------------------------------------

        if (! isset($additional_where[0])) {
            $additional_where = array($additional_where);
        }

        foreach ($additional_where as $where) {
            foreach ($where as $field => $value) {
                if (is_array($value)) {
                    $this->db->where_in($field, $value);
                } else {
                    $this->db->where($field, $value);
                }
            }
        }

        // ---------------------------------------------------------------
        // 'member_update_end' hook.
        // - Provides an opportunity for extra code to be executed after
        // member update.
        //
        if ($this->extensions->active_hook('member_update_end')) {
            $this->extensions->call('member_update_end', $member_id, $data);
        }
        //
        // ---------------------------------------------------------------

        $this->db->where('member_id', $member_id);
        $this->db->update('members', $data);
    }

    /**
     * Update Member Group
     *
     * This function updates a member group
     *
     * @access	public
     * @param	int
     * @param	array
     * @return	void
     */
    public function update_member_group($member_group_id = '')
    {
        // for later use
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");
    }

    /**
     * Update member data
     *
     * This function updates a member's data
     *
     * @access	public
     * @param	int
     * @param	array
     * @return	void
     */
    public function update_member_data($member_id = '', $data = array(), $additional_where = array())
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        if (! isset($additional_where[0])) {
            $additional_where = array($additional_where);
        }

        foreach ($additional_where as $where) {
            foreach ($where as $field => $value) {
                if (is_array($value)) {
                    $this->db->where_in($field, $value);
                } else {
                    $this->db->where($field, $value);
                }
            }
        }

        $this->db->where('member_id', $member_id);
        $this->db->update('member_data', $data);
    }

    /**
     * Delete member
     *
     * This function deletes all member data, and all communications from said member
     * stored on the system, and returns the id for further use
     *
     * @access	public
     * @param	mixed	Single member ID as int, or array of member IDs to delete
     * @param	int		Member ID to take over ownership of deleted members' entries
     * @return	void
     */
    public function delete_member($member_ids = array(), $heir_id = null)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        // ---------------------------------------------------------------
        // 'member_delete' hook.
        // - Provides an opportunity for extra code to be executed upon
        // member deletion, and also gives the opportunity to skip
        // deletion for some members all together by altering the array of
        // member IDs we pass to the hook.
        //
        if ($this->extensions->active_hook('member_delete')) {
            $member_ids = $this->extensions->call('member_delete', $member_ids);
        }
        //
        // ---------------------------------------------------------------

        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', '4.3', "Member Model delete()");

        ee('Model')->get('Member', $member_ids)->delete();
    }

    /**
     * Update entry stats for members, specifically total_entries and last_entry_date
     *
     * @param array	Array of member IDs to update stats for
     * @return	void
     */
    public function update_member_entry_stats($member_ids = array())
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member', \$member_id)->first()->updateAuthorStats()");
        // Make $member_ids an array if we need to
        if (! is_array($member_ids)) {
            $member_ids = array($member_ids);
        }

        foreach ($member_ids as $member_id) {
            // Get the number of entries and latest entry date for the member
            $this->db->select('count(entry_id) AS count, MAX(entry_date) as entry_date');
            $this->db->where('author_id', $member_id);
            $new_stats = $this->db->get('channel_titles')->row_array();

            // Default to 0 if there are no entries to pull back
            $entry_date = ($new_stats['entry_date']) ? $new_stats['entry_date'] : 0;

            // Update member stats
            $this->db->where('member_id', $member_id);
            $this->db->update('members', array(
                'total_entries' => $new_stats['count'],
                'last_entry_date' => $entry_date
            ));
        }
    }

    /**
     * Remove From Author List
     *
     * Turns on the preference to make a member part of the authorlist
     *
     * @access	public
     * @param	integer
     * @return	void
     */
    public function delete_from_authorlist($member_ids = array())
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        if (! is_array($member_ids)) {
            $member_ids = array($member_ids);
        }

        $this->db->where_in('member_id', $member_ids);
        $this->db->set('in_authorlist', 'n');
        $this->db->update('members');
    }

    /**
     * Update Author List
     *
     * Turns on the preference to make a member part of the authorlist
     *
     * @access	public
     * @param	array
     * @return	void
     */
    public function update_authorlist($member_ids = array())
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        if (! is_array($member_ids)) {
            $member_ids = array($member_ids);
        }

        $this->db->where_in('member_id', $member_ids);
        $this->db->set('in_authorlist', 'y');
        $this->db->update('members');
    }

    /**
     * Get Author Groups
     *
     * This function retuns an array if group ids for member groups
     * who are listed as authors for a channel
     *
     * @access	public
     * @param	integer
     * @return	array
     */
    public function get_author_groups($channel_id = '')
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        $this->db->select('role_settings.role_id');
        $this->db->join("channel_member_roles", "role_settings.role_id = channel_member_roles.role_id", 'left');
        $this->db->where('role_settings.include_in_authorlist', 'y');
        $this->db->where("channel_member_roles.channel_id", $channel_id);
        $this->db->or_where("role_settings.group_id", 1);
        $results = $this->db->get('member_groups');

        $group_ids = array();

        foreach ($results->result() as $result) {
            $group_ids[] = $result->group_id;
        }

        return $group_ids;
    }

    /**
     * Get Authors
     *
     * This function returns a set of members who are authors in a set channel
     *
     * @access	public
     * @param	integer
     * @return	mixed
     */
    public function get_authors($author_id = false, $limit = false, $offset = false)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Member')->getAuthors()");
        // Please don't combine these two queries. Mysql won't hit an index
        // on any combination that I've tried; except with a subquery which
        // is close enough to what we have here. -pk
        $roles = $this->db
            ->select('role_id')
            ->where('include_in_authorlist', 'y')
            ->where('site_id', $this->config->item('site_id'))
            ->get('role_settings')
            ->result_array();

        $roles = array_map('array_pop', $roles);

        $this->db->select('member_id, role_id, username, screen_name, in_authorlist');

        if ($author_id) {
            $this->db->where('member_id !=', $author_id);
        }

        $this->db->where('in_authorlist', 'y');

        if (count($roles)) {
            $this->db->or_where_in('role_id', $roles);
        }

        $this->db->order_by('screen_name', 'ASC');
        $this->db->order_by('username', 'ASC');

        if ($limit) {
            $this->db->limit($limit, $offset);
        }

        return $this->db->get('members');
    }

    /**
     * Get Member Groups
     *
     * Returns only the title and id by default, but additional fields can be passed
     * and automatically added to the query either as a string, or as an array.
     * This allows the same function to be used for "lean" and for larger queries.
     *
     * @access	public
     * @param	array
     * @param	array	array of associative field => value arrays
     * @return	mixed
     */
    public function get_member_groups($additional_fields = array(), $additional_where = array(), $limit = '', $offset = '')
    {
        ee()->load->library('logger');
        ee()->logger->deprecated("6.0.0", "ee('Model')->get('Member')");

        if (! is_array($additional_fields)) {
            $additional_fields = array($additional_fields);
        }

        if (! isset($additional_where[0])) {
            $additional_where = array($additional_where);
        }

        if (count($additional_fields) > 0) {
            $this->db->select(implode(',', $additional_fields));
        }

        $this->db->select("roles.role_id AS group_id, roles.name AS group_title");
        $this->db->from("roles");
        $this->db->join("role_settings", "roles.role_id = role_settings.role_id", 'inner');
        $this->db->where("role_settings.site_id", $this->config->item('site_id'));

        if ($limit != '') {
            $this->db->limit($limit);
        }

        if ($offset != '') {
            $this->db->offset($offset);
        }

        foreach ($additional_where as $where) {
            foreach ($where as $field => $value) {
                if (is_array($value)) {
                    $this->db->where_in($field, $value);
                } else {
                    $this->db->where($field, $value);
                }
            }
        }

        $this->db->order_by('roles.role_id, roles.name');

        return $this->db->get();
    }

    /**
     * Delete Member Group
     *
     * Deletes a member group, and optionally reassigns its members to another group
     *
     * @access	public
     * @param	int		The group to be deleted
     * @param	int		The group to reassign members to
     * @return	void
     */
    public function delete_member_group($group_id = '', $reassign_group = false)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        if ($reassign_group !== false) {
            // reassign current members to new group
            $this->db->set(array('role_id' => $reassign_group));
            $this->db->where('role_id', $group_id);
            $this->db->update('members');
        }

        ee('Model')->get('Role', $group_id)->delete();
    }

    /**
     * Count Members
     *
     * @access	public
     * @param	int
     * @return	int
     */
    public function count_members($group_id = '', $search_value = '', $search_field = '')
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        $this->_prep_search_query($group_id, $search_value, $search_field);

        return $this->db->count_all_results('members');
    }

    /**
     * Count Recrods
     *
     * @access	public
     * @param	table
     * @return	int
     */
    public function count_records($table = '')
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        return $this->db->count_all($table);
    }

    /**
     * Count Member Entries
     *
     * @access	public
     * @param	array
     * @return	int
     */
    public function count_member_entries($member_ids = array())
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        if (! is_array($member_ids)) {
            $member_ids = array($member_ids);
        }

        $this->db->select('entry_id');
        $this->db->from('channel_titles');
        $this->db->where_in('author_id', $member_ids);

        return $this->db->count_all_results();
    }

    /**
     * Get Members Group Ids
     *
     * Provided a string or an array of member ids, returns an array
     * of unique group ids that they belong to
     *
     * @access	public
     * @param	array
     * @return	mixed
     */
    public function get_members_group_ids($member_ids = array())
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        if (! is_array($member_ids)) {
            $member_ids = array($member_ids);
        }

        $this->db->select("group_id");
        $this->db->from("members");
        $this->db->where_in("member_id", $member_ids);

        $groups = $this->db->get();

        // superadmins are always viable
        $group_ids[] = 1;

        if ($groups->num_rows() > 0) {
            foreach ($groups->result() as $group) {
                $group_ids[] = $group->group_id;
            }
        }

        $group_ids = array_unique($group_ids);

        return $group_ids;
    }

    /**
     * Get Custom Member Fields
     *
     * This function retuns all custom member fields
     *
     * @access	public
     * @param	an optional member id to restrict the search on
     * @return	object
     */
    public function get_custom_member_fields($member_id = '')
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        if ($member_id != '') {
            $this->db->where('m_field_id', $member_id);
        }

        $this->db->select('m_field_id, m_field_order, m_field_label, m_field_name');
        $this->db->from('member_fields');
        $this->db->order_by('m_field_order');

        return $this->db->get();
    }

    /**
     * Get Member By Screen Name
     *
     * @access	public
     * @param	string
     * @return	mixed
     */
    public function get_member_by_screen_name($screen_name = '')
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        $this->db->select('member_id');
        $this->db->from('members');
        $this->db->where('screen_name', $screen_name);

        return $this->db->get();
    }

    /*
     * Get IP Members
     *
     * Used in search of ip addresses within members table
     *
     * @access	public
     * @param	string
     * @return	mixed
     */
    public function get_ip_members($ip_address = '', $limit = 10, $offset = 0)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        $this->db->select('member_id, username, screen_name, ip_address, email, join_date');
        $this->db->like('ip_address', $ip_address, 'both');
        $this->db->from('members');
        $this->db->order_by('screen_name');
        $this->db->limit($limit);
        $this->db->offset($offset);

        return $this->db->get();
    }

    /**
     * Get Group Members
     *
     * Returns members of a group
     *
     * @access	public
     * @param	string
     * @return	mixed
     */
    public function get_group_members($group_id, $order_by = 'join_date')
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        $this->db->select('member_id, username, screen_name, email, join_date');
        $this->db->where('group_id', $group_id);
        $this->db->from('members');
        $this->db->order_by($order_by, 'desc');

        return $this->db->get();
    }

    /**
     * Check Duplicate
     *
     * Checks for duplicated member fields
     *
     * @access	public
     * @param	string
     * @return	mixed
     */
    public function check_duplicate($value = '', $field = 'username')
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        $this->db->like($field, $value);
        $this->db->from('members');

        if ($this->db->count_all_results() == 0) {
            // no duplicates
            return false;
        } else {
            // duplicates found
            return true;
        }
    }

    /**
     * Get Theme List
     *
     * Show file listing as a pull-down
     *
     * @access	public
     * @param	string
     * @param	string
     * @param	string
     * @return	string
     */
    public function get_theme_list($path = '')
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        if ($path == '') {
            return;
        }

        $themes = array();

        if ($fp = @opendir($path)) {
            while (false !== ($file = readdir($fp))) {
                if (@is_dir($path . $file) && strpos($file, '.') === false) {
                    $themes[$file] = ucwords(str_replace("_", " ", $file));
                }
            }

            closedir($fp);
        }

        return $themes;
    }

    /**
     * Get Profile Templates
     *
     * Returns an array of profile themes with the name as key, and the humanized
     * name as the value
     *
     * @access	public
     * @param	string	The path to the themes
     * @return	array
     */
    public function get_profile_templates($path = PATH_MBR_THEMES)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        $themes = array();
        $this->load->helper('directory');

        foreach (directory_map($path, true) as $file) {
            if (is_dir($path . $file) and strncmp('.', $file, 1) != 0) {
                $themes[$file] = ucfirst(str_replace("_", " ", $file));
            }
        }

        return $themes;
    }

    /**
     * Insert Group Layout
     *
     * Inserts layout information for member groups for the publish page, saved as
     * a serialized array.
     *
     * @access	public
     * @param	mixed	Member group
     * @param	int		Field group
     * @param	array	The layout of the fields
     * @return	bool
     */
    public function insert_group_layout($member_groups = array(), $channel_id = '', $layout_info = array())
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        if (! is_array($member_groups)) {
            $member_groups = array($member_groups);
        }

        $error_count = 0; // assume no errors so far

        foreach ($member_groups as $member_group) {
            // remove all data already in there
            $this->delete_group_layout($member_group, $channel_id);

            // Remove layout function on the CP works by passing an empty array
            if (count($layout_info) > 0) {
                $this->db->set("site_id", $this->config->item('site_id'));
                $this->db->set("channel_id", $channel_id);
                $this->db->set("field_layout", serialize($layout_info));
                $this->db->set("member_group", $member_group);

                if (! $this->db->insert('layout_publish')) {
                    $error_count++;
                }
            }
        }

        if ($error_count > 0) {
            return false;
        }

        return true;
    }

    /**
     * Delete Group Layout
     *
     * Removes layout information for member groups for the publish page.
     *
     * @access	public
     * @param	mixed	Member group
     * @param	int		Field group
     * @return	void
     */
    public function delete_group_layout($member_group = '', $channel_id = '')
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        $this->db->where("site_id", $this->config->item('site_id'));
        $this->db->where("channel_id", $channel_id);

        if ($member_group != '') {
            $this->db->where("member_group", $member_group);
        }

        $this->db->delete('layout_publish');
    }

    /**
     * Get Group Layout
     *
     * Gets layout information for member groups for the publish page
     *
     * @access	public
     * @param	int Member group
     * @param	int		Field group
     * @return	array
     */
    public function get_group_layout($member_group = '', $channel_id = '')
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        $this->load->model('layout_model');

        return $this->layout_model->get_layout_settings(array(
            'site_id' => $this->config->item('site_id'),
            'channel_id' => $channel_id,
            'member_group' => $member_group
        ));
    }

    /**
     * Get All Group Layouts
     *
     * Gets layout information for member groups for the publish page
     *
     * @access	public
     * @param	int Member group
     * @param	int		Field group
     * @return	array
     */
    public function get_all_group_layouts($channel_id = array())
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        if (! is_array($channel_id)) {
            $channel_id = array($channel_id);
        }

        if (! empty($channel_id)) {
            $this->db->where_in("channel_id", $channel_id);
        }

        $layout_data = $this->db->get('layout_publish');

        if ($layout_data->num_rows() > 0) {
            $returned_data = $layout_data->result_array();
        } else {
            $returned_data = array();
        }

        return $returned_data;
    }

    /**
     * Get Notepad Content
     *
     * Returns the contents of a user's notepad
     *
     * @access	public
     * @return	array
     */
    public function get_notepad_content($id = '')
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Model')->get('Member')");

        $id = $id ? $id : $this->session->userdata('member_id');

        $this->db->select('notepad');
        $this->db->from('members');
        $this->db->where('member_id', (int) $id);
        $notepad_query = $this->db->get();

        if ($notepad_query->num_rows() > 0) {
            $notepad_result = $notepad_query->row();

            return $notepad_result->notepad;
        }

        return '';
    }

    /**
     * Can Access Module
     *
     * @access	public
     * @return	boolean
     */
    public function can_access_module($module, $group_id = '')
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('6.0.0', "ee('Permission')");

        // Superadmin sees all
        if (ee('Permission')->isSuperAdmin()) {
            return true;
        }

        $assigned_modules = [];

        if (ee()->session->getMember()) {
            foreach (ee()->session->getMember()->getAssignedModules()->pluck('module_name') as $assigned_module) {
                $assigned_modules[] = strtolower($assigned_module);
            }
        }

        return in_array(strtolower($module), $assigned_modules);
    }

    /**
     * Set up the search query which is used by get_members and
     * count_members. Be sure to *run* the query after calling this.
     *
     * @access	private
     * @param	int
     * @return	int
     */
    private function _prep_search_query($group_id = '', $search_value = '', $search_in = '')
    {
        $no_search = array('password', 'salt', 'crypt_key');

        if ($group_id !== '') {
            $this->db->where("members.group_id", $group_id);
        }

        if (is_array($search_value)) {
            foreach ($search_value as $token_name => $token_value) {
                // Check to see if the token is ID
                $token_name = ($token_name === 'id') ? 'member_id' : $token_name;

                // Clean the token name to arrive at a potential column name
                // and prevent any shenanigans
                $token_name = ee()->db->protect_identifiers(
                    preg_replace('/[^\w-.]/', '', $token_name)
                );
                $this->db->like('members.' . $token_name, $token_value);
            }
        } elseif ($search_value != '') {
            if (in_array($search_in, $no_search) or $search_in == 'all') {
                $this->db->where("(`exp_members`.`screen_name` LIKE '%" . $this->db->escape_like_str($search_value) . "%' OR `exp_members`.`username` LIKE '%" . $this->db->escape_like_str($search_value) . "%' OR `exp_members`.`email` LIKE '%" . $this->db->escape_like_str($search_value) . "%' OR `exp_members`.`member_id` LIKE '%" . $this->db->escape_like_str($search_value) . "%')", null, true);
            } else {
                $search_in = ee()->db->protect_identifiers(
                    preg_replace('/[^\w-.]/', '', $search_in)
                );
                $this->db->like('members.' . $search_in, $search_value);
            }
        }
    }
}

// EOF
