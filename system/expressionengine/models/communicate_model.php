<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Communicate Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Communicate_model extends CI_Model {

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
	function get_cached_email($id = '', $limit = 1, $offset = 0, $order = array())
	{
		if (is_array($order) && count($order) > 0)
		{
			foreach ($order as $key => $val)
			{
				$this->db->order_by($key, $val);
			}
		
		}
		
		if (is_array($id))
		{
			$this->db->where_in('cache_id', $id);
		}
		elseif ($id != '')
		{
			$this->db->where('cache_id', $id);			
		}
		
		$this->db->order_by('cache_id', 'desc');
		
		if ($limit === FALSE)
		{
			return $this->db->get('email_cache');			
		}
		else
		{
			return $this->db->get('email_cache', $limit, $offset);	
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Get Cached Member Groups
	 *
	 * Retreives the group id's for a given cached email
	 *
	 * @access	public
	 * @param	int
	 * @return	string
	 */
	function get_cached_member_groups($id)
	{
		$this->db->select('group_id');
		$this->db->where('cache_id', $id);
		return $this->db->get('email_cache_mg');
	}

	// --------------------------------------------------------------------

	/**
	 * Get Cached Mailing Lists
	 *
	 * Retreives the list id's for a given cached email
	 *
	 * @access	public
	 * @param	int
	 * @return	string
	 */
	function get_cached_mailing_lists($id)
	{
		$this->db->select('list_id');
		$this->db->where('cache_id', $id);
		return $this->db->get('email_cache_ml');
	}

	// --------------------------------------------------------------------

	/**
	 * Get Mailing Lists
	 *
	 * Retreives the list id list title for all mailing lists
	 * If the $list_id is given to retreive only one mailing list, the
	 *    list_template is also retrieved
	 *
	 * @access	public
	 * @param	int
	 * @return	string
	 */
	function get_mailing_lists($list_id = '')
	{
		$this->db->select('list_id, list_title');
		
		if (is_array($list_id))
		{
			$this->db->select('list_template');
			$this->db->where_in('list_id', $list_id);			
		}
		elseif ($list_id != '')
		{
			$this->db->select('list_template');
			$this->db->where('list_id', $list_id);
		}
		
		$this->db->order_by('list_title');
		
		return $this->db->get('mailing_lists');
	}

	// --------------------------------------------------------------------
	
	/**
	 * Get Mailing List Emails
	 *
	 * Retreives the authcode, email, and list id for given mailing lists
	 *
	 * @access	public
	 * @param	array
	 * @return	string
	 */
	function get_mailing_list_emails($list_ids = array())
	{
		if ( ! is_array($list_ids))
		{
			$list_ids = array($list_ids);
		}
		
		$this->db->select('authcode, email, list_id');

		if ( ! empty($list_ids))
		{
			$this->db->where_in('list_id', $list_ids);
		}
		
		$this->db->order_by('user_id');
		
		return $this->db->get('mailing_list');
	}

	// --------------------------------------------------------------------
			
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
	function save_cache_data($cache_data, $groups = '', $list_ids = '')
	{
		$this->db->query($this->db->insert_string('exp_email_cache', $cache_data)); 

		$cache_id = $this->db->insert_id();

		if (is_array($groups))
		{
			foreach ($groups as $id)
			{
				$this->db->insert('email_cache_mg', array('cache_id' => $cache_id, 'group_id' => $id));
			}
		}

		if (is_array($list_ids))
		{
			foreach ($list_ids as $id)
			{
				$this->db->insert('email_cache_ml', array('cache_id' => $cache_id, 'list_id' => $id));
			}
		}

		return $cache_id;
	}
	
	// --------------------------------------------------------------------	

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
	function update_email_cache($total_sent, $recipient_array, $id)
	{
		if (is_array($recipient_array))
		{
			$recipient_array = serialize($recipient_array);
		}
		
		$this->db->where('cache_id', $id);
		$this->db->update('email_cache', array('total_sent' => $total_sent, 'recipient_array' => $recipient_array));
		return $this->db->affected_rows();	
	}

	// --------------------------------------------------------------------
	
	/**
	 * Delete Emails
	 *
	 * Deletes cached emails
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	function delete_emails($cache_ids)
	{
		if ( ! is_array($cache_ids))
		{
			$cache_ids = array($cache_ids);
		}

		$this->db->where_in('cache_id', $cache_ids);		
		$this->db->delete(array('email_cache', 'email_cache_mg', 'email_cache_ml'));
	}

	// --------------------------------------------------------------------
	
}
// End class Communicate_model

/* End of file communicate_model.php */
/* Location: ./system/expressionengine/models/communicate_model.php */