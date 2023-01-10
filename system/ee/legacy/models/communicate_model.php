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
 * Communicate Model
 */
class Communicate_model extends CI_Model
{
    /**
     * Get Email Cache
     *
     * Retreives all email cache data, for a given id if supplied
     *
     * @access	public
     * @param	int
     * @param	int
     * @return	string
     */
    public function get_cached_email($id = '', $limit = 1, $offset = 0, $order = array())
    {
        if (is_array($order) && count($order) > 0) {
            foreach ($order as $key => $val) {
                $this->db->order_by($key, $val);
            }
        }

        if (is_array($id)) {
            $this->db->where_in('cache_id', $id);
        } elseif ($id != '') {
            $this->db->where('cache_id', $id);
        }

        $this->db->order_by('cache_id', 'desc');

        if ($limit === false) {
            return $this->db->get('email_cache');
        } else {
            return $this->db->get('email_cache', $limit, $offset);
        }
    }

    /**
     * Get Cached Member Groups
     *
     * Retreives the group id's for a given cached email
     *
     * @access	public
     * @param	int
     * @return	string
     */
    public function get_cached_member_groups($id)
    {
        $this->db->select('group_id');
        $this->db->where('cache_id', $id);

        return $this->db->get('email_cache_mg');
    }

    /**
     * Save Cache Data
     *
     * Saves email cache data
     *
     * @access	public
     * @param	array
     * @param	array
     * @param	array
     * @return	string
     */
    public function save_cache_data($cache_data, $groups = '', $list_ids = '')
    {
        $this->db->query($this->db->insert_string('exp_email_cache', $cache_data));

        $cache_id = $this->db->insert_id();

        if (is_array($groups)) {
            foreach ($groups as $id) {
                $this->db->insert('email_cache_mg', array('cache_id' => $cache_id, 'group_id' => $id));
            }
        }

        if (is_array($list_ids)) {
            foreach ($list_ids as $id) {
                $this->db->insert('email_cache_ml', array('cache_id' => $cache_id, 'list_id' => $id));
            }
        }

        return $cache_id;
    }

    /**
     * Update Email Cache
     *
     * Returns # of affected rows
     *
     * @access	public
     * @param	int
     * @param	array
     * @param	int
     * @return	string
     */
    public function update_email_cache($total_sent, $recipient_array, $id)
    {
        if (is_array($recipient_array)) {
            $recipient_array = serialize($recipient_array);
        }

        $this->db->where('cache_id', $id);
        $this->db->update('email_cache', array('total_sent' => $total_sent, 'recipient_array' => $recipient_array));

        return $this->db->affected_rows();
    }

    /**
     * Delete Emails
     *
     * Deletes cached emails
     *
     * @access	public
     * @param	array
     * @return	void
     */
    public function delete_emails($cache_ids)
    {
        if (! is_array($cache_ids)) {
            $cache_ids = array($cache_ids);
        }

        $this->db->where_in('cache_id', $cache_ids);
        $this->db->delete(array('email_cache', 'email_cache_mg', 'email_cache_ml'));
    }
}
// End class Communicate_model

// EOF
