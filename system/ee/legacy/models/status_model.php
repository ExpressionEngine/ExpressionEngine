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
 * Status Model
 */
class Status_model extends CI_Model
{
    /**
     * Get Status
     *
     * @access	public
     * @param	int
     * @return	array
     */
    public function get_status($status_id = '')
    {
        return $this->db->where('status_id', $status_id)
            ->where('site_id', $this->config->item('site_id'))
            ->get('statuses');
    }

    /**
     * Get next Status Order
     *
     * @access	public
     * @param	int
     * @return	mixed
     */
    public function get_next_status_order($group_id = '')
    {
        $this->db->select_max('status_order');
        $this->db->where('group_id', $group_id);

        $status_order = $this->db->get('statuses');

        return ($status_order->num_rows() == 0) ? 1 : $status_order->row('status_order') + 1;
    }

    /**
     * Get Disallowed Statuses
     *
     * @access	public
     * @param	int
     * @return	array
     */
    public function get_disallowed_statuses($group_id = '')
    {
        $this->db->where('statuses.status_id = ' . $this->db->dbprefix('status_no_access.status_id'));
        $this->db->where('status_no_access.member_group', $group_id);

        return $this->db->get('status_no_access, statuses');
    }
}

// EOF
