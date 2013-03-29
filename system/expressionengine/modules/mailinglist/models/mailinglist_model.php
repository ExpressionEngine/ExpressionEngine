<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Mailinglist Model
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Mailinglist_model extends CI_Model  {
	
	/**
	 * Get Mailinglists
	 *
	 * @return obj
	 */
	function get_mailinglists()
	{
		$this->db->order_by('list_title');
		return $this->db->get('mailing_lists');
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Get Mailing Lists by Id.
	 *
	 * @param int 	Mailing List ID
	 * @param str 	columns to select -- optional
	 * @return obj
	 */
	function get_list_by_id($id = NULL, $addt_select = NULL)
	{
		if ($addt_select)
		{
			$this->db->select($addt_select);
		}
		
		if ($id)
		{
			if (is_array($id))
			{
				$this->db->where_in('list_id', $id);
			}
			else
			{
				$this->db->where('list_id', $id);	
			}			
		}
		
		return $this->db->get('mailing_lists');
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Get Emails by List Id.
	 *
	 * @param int 	Mailing List ID
	 * @param str 	columns to select -- optional
	 * @return obj
	 */
	function get_emails_by_list($id = NULL, $addt_select = NULL)
	{
		if ($addt_select)
		{
			$this->db->select($addt_select);
		}
		
		if ($id)
		{
			if (is_array($id))
			{
				$this->db->where_in('list_id', $id);
			}
			else
			{
				$this->db->where('list_id', $id);	
			}			
		}
		
		return $this->db->get('mailing_list');
	}
	

	// ------------------------------------------------------------------------
	
	/**
	 * Update Mailinglist
	 *
	 * @param mixed 	FALSE or integer
	 * @param array 	
	 * @return void
	 */
	function update_mailinglist($list_id, $data)
	{
		if ($list_id === FALSE OR $list_id == 0)
		{
			$this->db->insert('mailing_lists', $data);
		}
		else
		{
			$this->db->where('list_id', $list_id);
			$this->db->update('mailing_lists', $data);
		}
		
		return;
	}

	// ------------------------------------------------------------------------

	/**
	 * Update Mailinglist Template
	 *
	 * @param int 	List ID number
	 * @param str 	template data
	 * @return void
	 */
	function update_template($list_id, $data)
	{
		$this->db->set('list_template', $data);
		$this->db->where('list_id', $list_id);
		$this->db->update('mailing_lists');

		return;
	}

	// ------------------------------------------------------------------------
	
	/**
	 * Unique Mailinglist shortname
	 *
	 * @param mixed 	list id
	 * @param str		
	 * @return boolean	
	 */
	function unique_shortname($list_id, $str)
	{
		if ($list_id)
		{
			$this->db->where('list_id !=', $list_id);
		}
		
		$this->db->where('list_name', $str);

		$count = $this->db->count_all_results('mailing_lists');

		if ($count != 0)
		{
			return FALSE;
		}

		return TRUE;
	}

	// ------------------------------------------------------------------------
	
	/**
	 * Delete Mailinglists
	 *
	 * @param array 	Ids of mailinglists to be deleted
	 * @return obj
	 */
	function delete_mailinglist($ids)
	{
		$this->db->where_in('list_id', $ids);
		$this->db->delete(array('mailing_lists', 'mailing_list'));

		return ($this->db->affected_rows() == 1) ? $this->lang->line('ml_list_deleted') : $this->lang->line('ml_lists_deleted');
	}
	
	// ------------------------------------------------------------------------	
	
	/**
	 * Insert new contact
	 *
	 * @param array 
	 */
	function insert_subscription($data)
	{
		return $this->db->insert('mailing_list', $data);
	}

	// ------------------------------------------------------------------------	

	/**
	 * Delete Subscription
	 *
	 * @param int 	list ID
	 * @param str 	email address to delete
	 */
	function delete_subscription($list_id, $email)
	{
		$this->db->where('email', $email);
		$this->db->where('list_id', $list_id);
		$this->db->delete('mailing_list');
		
		return;
	}
	
	// ------------------------------------------------------------------------	

	/**
	 * Delete Email 
	 *
	 * @param int 		user_id
	 * @return string 	
	 */
	function delete_email($user)
	{
		$this->db->where_in('user_id', $user);
		$this->db->delete('mailing_list');

		return ($this->db->affected_rows() == 1) ? $this->lang->line('ml_email_deleted') : $this->lang->line('ml_emails_deleted');
	}

	// ------------------------------------------------------------------------	
	
	/**
	 * Mailinglist Search
	 *
	 * @param int	List id
	 * @param str	email
	 * @param array order
	 * @param int 
	 * @param int
	 * @return object
	 */
	function mailinglist_search($list_id = '', $email = '', $order = array(), $rownum = 0, $perpage = '')
	{
		$do_join = FALSE;
			
		$this->db->select('user_id, mailing_list.list_id, email, ip_address');
		$this->db->from('mailing_list');

		if (is_array($order) && count($order) > 0)
		{
			foreach ($order as $key => $val)
			{
				if ($key != 'list_title')
				{
					$this->db->order_by($key, $val);
				}
				elseif ($key == 'list_title' && $list_id == '')
				{
					$do_join = TRUE;
					$this->db->order_by($key, $val);
				}
			}
		}

		if ($do_join == TRUE)
		{
			$this->db->join('mailing_lists', 'mailing_lists.list_id = mailing_list.list_id', 'left');
		}

		if ($list_id != '')
		{
			$this->db->where('list_id', $list_id);
		}

		if ($email)
		{
			$this->db->like('email', urldecode($email));
		}

		$this->db->limit($perpage, $rownum);

		return $this->db->get();
	}
	
}
// END CLASS

/* End of file mailinglist_model.php */
/* Location: ./system/expressionengine/modules/mailinglist/models/mailinglist_model.php */