<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Status Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Status_model extends CI_Model {
	
	/**
	 * Get Statuses
	 *
	 * @access	public
	 * @param	int
	 * @return	array
	 */
	function get_statuses($group_id = '', $channel_id = '')
	{
		if ($group_id != '')
		{
			$this->db->where('group_id', $group_id);
		}
		
		$this->db->from('statuses');
		
		if ($channel_id != '')
		{
			$this->db->select('statuses.status_id, statuses.status');
			$this->db->join('status_groups sg', 'sg.group_id = statuses.group_id', 'left');
			$this->db->join('channels c', 'c.status_group = sg.group_id', 'left');
			$this->db->where('c.channel_id', $channel_id);
		}
		else
		{
			$this->db->where('site_id', $this->config->item('site_id'));
			$this->db->order_by('status_order');
		}

		return $this->db->get();
	}

	// --------------------------------------------------------------------

	/**
	 * Get Status
	 *
	 * @access	public
	 * @param	int
	 * @return	array
	 */
	function get_status($status_id = '')
	{
		$this->db->where('status_id', $status_id);
		$this->db->from('statuses');
		$this->db->where('site_id', $this->config->item('site_id'));

		return $this->db->get();
	}

	// --------------------------------------------------------------------

	/**
	 * Get next Status Order
	 *
	 * @access	public
	 * @param	int
	 * @return	mixed
	 */
	function get_next_status_order($group_id = '')
	{
		$this->db->select_max('status_order');
		$this->db->where('group_id', $group_id);

		$status_order = $this->db->get('statuses');
		
		if ($status_order->num_rows() == 0)
		{
			return 1;
		}
		else
		{
			return $status_order->row('status_order')+1;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Get Status Group
	 *
	 * @access	public
	 * @param	int
	 * @return	mixed
	 */
	function get_status_group($group_id = '')
	{
		$this->db->where('group_id', $group_id);

		return $this->db->get('status_groups');
	}

	// --------------------------------------------------------------------

	/**
	 * Get Status Groups
	 *
	 * @access	public
	 * @return	mixed
	 */
	function get_status_groups()
	{
		$this->db->select('status_groups.group_id, status_groups.group_name');
		$this->db->select("COUNT(".$this->db->dbprefix('statuses.group_id').") as count");
		$this->db->join('statuses', 'status_groups.group_id = statuses.group_id', 'left');
		$this->db->where('status_groups.site_id', $this->config->item('site_id'));
		$this->db->group_by('status_groups.group_id');
		$this->db->order_by('status_groups.group_name');
		
		return $this->db->get('status_groups');
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Status Group
	 *
	 * @access	public
	 * @return	void
	 */
	function delete_status_group($group_id)
	{
		$this->db->delete('status_groups', array('group_id' => $group_id));
		$this->db->delete('statuses', array('group_id' => $group_id));
	}

	// --------------------------------------------------------------------

	/**
	 * Insert Statuses
	 *
	 * @access	public
	 * @return	void
	 */
	function insert_statuses($group_name, $status_color_open, $status_color_closed)
	{
		$data = array(
					'group_name'	=> $group_name,
					'site_id'		=> $this->config->item('site_id')
				);

		$this->db->insert('status_groups', $data);
		$group_id = $this->db->insert_id();

		$open = array(
					'site_id'			=> $this->config->item('site_id'),
					'group_id'			=> $group_id,
					'status'			=> 'open',
					'status_order'		=> '1',
					'highlight'			=> $this->status_color_open
		);
		$closed = array_merge($open,
				array(
					'status'			=> 'closed',
					'status_order'		=> '2',
					'highlight'			=> $this->status_color_closed
					)
		);

		$this->db->insert('statuses', $open);
		$this->db->insert('statuses', $closed);
	}

	// --------------------------------------------------------------------

	/**
	 * Update Statuses
	 *
	 * @access	public
	 * @return	void
	 */
	function update_statuses($group_name, $group_id, $status_color_open, $status_color_closed)
	{
		$data = array(
						'group_name' => $group_name,
						'site_id' => $this->config->item('site_id')
		);

		$this->db->where('group_id', $group_id);

		$this->db->update('status_groups', $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Duplicate Status Group Name Check
	 *
	 * @access	public
	 * @return	boolean
	 */
	function is_duplicate_status_group_name($group_name = '', $group_id = '')
	{
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('group_name', $group_name);
		$this->db->from('status_groups');

		if ($group_id != '')
		{
			$this->db->where('group_id != '.$group_id);
		}

		$count = $this->db->count_all_results();

		if ($count > 0)
		{
			// its a duplicate
			return TRUE;
		}
		else
		{
			// not a duplicate
			return FALSE;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Get Disallowed Statuses
	 *
	 * @access	public
	 * @param	int
	 * @return	array
	 */
	function get_disallowed_statuses($group_id = '')
	{
		$this->db->where('statuses.status_id = '.$this->db->dbprefix('status_no_access.status_id'));
		$this->db->where('status_no_access.member_group', $group_id);

		return $this->db->get('status_no_access, statuses');
	}

}

/* End of file status_model.php */
/* Location: ./system/expressionengine/models/status_model.php */