<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Admin Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class File_upload_preferences_model extends CI_Model {

	/**
	 * Get Upload Preferences
	 *
	 * @access	public
	 * @param	int
	 * @return	mixed
	 */
	function get_upload_preferences($group_id = NULL, $id = NULL)
	{
		// for admins, no specific filtering, just give them everything
		if ($group_id == 1)
		{
			// there a specific upload location we're looking for?
			if ($id != '')
			{
				$this->db->where('id', $id);
			}

			$this->db->from('upload_prefs');
			$this->db->where('site_id', $this->config->item('site_id'));
			$this->db->order_by('name');

			$upload_info = $this->db->get();
		}
		else
		{
			// non admins need to first be checked for restrictions
			// we'll add these into a where_not_in() check below
			$this->db->select('upload_id');
			$no_access = $this->db->get_where('upload_no_access', array('member_group'=>$group_id));

			if ($no_access->num_rows() > 0)
			{
				$denied = array();
				foreach($no_access->result() as $result)
				{
					$denied[] = $result->upload_id;
				}
				$this->db->where_not_in('id', $denied);
			}

			// there a specific upload location we're looking for?
			if ($id)
			{
				$this->db->where('id', $id);
			}

			$this->db->from('upload_prefs');
			$this->db->where('site_id', $this->config->item('site_id'));
			$this->db->order_by('name');

			$upload_info = $this->db->get();
		}

		return $upload_info;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Builds an array suitable for dropdown lists
	 * 
	 * @param integer $group_id The group id to get file preferences for
	 * @param integer $id Specific upload directory ID if you just want settings for that
	 * @return array Associative array with ids as the keys and names as the values
	 */
	public function get_dropdown_array($group_id = NULL, $id = NULL)
	{
		$prefs = $this->get_upload_preferences($group_id, $id);
		
		$prefs_array = array();
		
		foreach ($prefs->result() as $pref)
		{
			$prefs_array[$pref->id] = $pref->name;
		}
		
		return $prefs_array;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Delete Upload Preferences
	 *
	 * @access	public
	 * @param	int
	 * @return	string
	 */
	function delete_upload_preferences($id = '')
	{
		// There are no permission checks- I don't really think there should be
		
		$this->db->where('upload_id', $id);
		$this->db->delete('upload_no_access');

		// get the name we're going to delete so that we can return it when we're done
		$this->db->select('name');
		$this->db->where('id', $id);
		$deleting = $this->db->get('upload_prefs');

		// Delete the files associated with this preference
		// Note we aren't doing anything to the files/folders yet
		$this->db->where('upload_location_id', $id);
		$this->db->delete('files');

		// ok, now remove the pref
		$this->db->where('id', $id);
		$this->db->delete('upload_prefs');
		
		return $deleting->row('name');
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Get the category groups for one or more upload directories
	 * 
	 * @param array $id Either an array of upload directory IDs or just one
	 * @return array Array of category group IDs
	 */
	public function get_category_groups($id = array())
	{
		if ( ! is_array($id))
		{
			$id = array($id);
		}
		
		$cat_groups = array();
		
		$this->db->select('cat_group');
		$this->db->where_in('id', $id);
		$upload_pref_query = $this->db->get('upload_prefs');
		
		foreach ($upload_pref_query->result() as $upload_pref) 
		{
			$cat_groups = array_merge($cat_groups, explode('|', $upload_pref->cat_group));
		}
		
		return array_unique($cat_groups);
	}
}

/* End of file file_model.php */
/* Location: ./system/expressionengine/models/file_model.php */
