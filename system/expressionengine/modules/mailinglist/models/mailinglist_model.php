<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
 * @link		http://expressionengine.com
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
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Mailinglist_model extends CI_Model  {

	function Mailinglist_model()
	{
		parent::CI_Model();
	}

	// ------------------------------------------------------------------------
	
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
	function get_list_by_id($id, $addt_select = NULL)
	{
		if ($addt_select)
		{
			$this->db->select($addt_select);
		}
		
		$this->db->where('list_id', $id);
		
		return $this->db->get('mailing_lists');
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
		if ($list_id === FALSE)
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
		
		if ($this->db->count_all_results('mailing_lists') > 0)
		{
			$this->form_validation->set_message('_unique_short_name', $this->lang->line('ml_short_name_taken'));
			
			return FALSE;
		}
		
		return TRUE;
	}

	// ------------------------------------------------------------------------
	
}
// END CLASS

/* End of file mailinglist_model.php */
/* Location: ./system/expressionengine/modules/mailinglist/models/mailinglist_model.php */