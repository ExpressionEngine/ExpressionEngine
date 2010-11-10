<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Blogger API Model
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Blogger_api_model extends CI_Model  {
	
	/**
	 * Get Blogger Preferences
	 *
	 * @return obj
	 */
	function get_blogger_prefs()
	{
		$this->db->select('blogger_pref_name, blogger_id');
		return $this->db->get('blogger');
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Get Prefs by id.
	 *
	 * @param int	id
	 * @return object
	 */
	function get_prefs_by_id($id)
	{
		return $this->db->get_where('blogger', array('blogger_id' => $id));
	}
		
	// ------------------------------------------------------------------------

	/**
	 * Get Channel Fields
	 *
	 * @return obj
	 */
	function get_channel_fields()
	{
		$this->db->select('field_id, group_id, field_name');
		$this->db->order_by('field_name');

		return $this->db->get_where('channel_fields', array('field_type' => 'textarea'));		
	}

	// ------------------------------------------------------------------------
	
	/**
	 * Save configuration
	 *
	 * Saves blogger API configuration
	 *
	 * @param mixed 	string (new) or integer (id)
	 * @param array 	data being saved
	 * @return array 	id & message
	 */
	function save_configuration($which, $data)
	{
		$return_data = array();
		
		if ($which == 'new')
		{
			unset($data['blogger_id']);
			
			$this->db->insert('blogger', $data);
			
			$return_data['id'] = $this->db->insert_id();
			$return_data['message'] = $this->lang->line('configuration_created');
		}
		else
		{
			$this->db->where('blogger_id', $which);
			$this->db->update('blogger', $data);
			
			$return_data['id'] = $which;
			$return_data['message'] = $this->lang->line('configuration_updated');
		}
		
		return $return_data;	
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete Configuration
	 *
	 * @param array 	configuration id's to delete
	 * @return string 	success message
	 */
	function delete_configuration($config_to_delete)
	{
		foreach($config_to_delete as $item)
		{
			$this->db->or_where('blogger_id', $item);
		}		

		$this->db->delete('blogger');

		return ($this->db->affected_rows() == 1) ? $this->lang->line('blogger_deleted') : $this->lang->line('bloggers_deleted');
	}
	
	// ------------------------------------------------------------------------
}
// END CLASS

/* End of file blogger_api_model.php */
/* Location: ./system/expressionengine/modules/blogger_api/models/blogger_api_model.php */