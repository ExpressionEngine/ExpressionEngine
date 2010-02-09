<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Field Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Field_model extends CI_Model {

	function Field_model()
	{
		parent::CI_Model();
	}

	// --------------------------------------------------------------------

	/**
	 * Get Field
	 *
	 * @access	public
	 * @param	int
	 * @return	mixed
	 */
	function get_field($field_id)
	{
		$this->db->from('channel_fields');
		$this->db->where('field_id', $field_id);
		return $this->db->get();
	}

	// --------------------------------------------------------------------

	/**
	 * Get Field Group
	 *
	 * @access	public
	 * @param	int
	 * @return	mixed
	 */
	function get_fields($group_id = '', $additional_where = array())
	{
		$this->db->select('field_id, field_name, field_label, field_type, field_order');
		$this->db->from('channel_fields');

		if ($group_id != '')
		{
			$this->db->where('group_id', $group_id);
		}

		// add additional WHERE clauses
		foreach ($additional_where as $field => $value)
		{
			if (is_array($value))
			{
				$this->db->where_in($field, $value);
			}
			else
			{
				$this->db->where($field, $value);
			}
		}

		$this->db->order_by('field_order');
		return $this->db->get();
	}

	// --------------------------------------------------------------------

	/**
	 * Get Field Group
	 *
	 * @access	public
	 * @param	int
	 * @return	mixed
	 */
	function get_field_group($group_id = '')
	{
		$this->db->where('group_id', $group_id);
		return $this->db->get('field_groups');
	}

	// --------------------------------------------------------------------

	/**
	 * Get Field Groups
	 *
	 * @access	public
	 * @return	object
	 */
	function get_field_groups()
	{
		$this->db->select('exp_field_groups.group_id, exp_field_groups.group_name,
							COUNT(exp_channel_fields.group_id) as count');
		$this->db->from('exp_field_groups');
		$this->db->join('exp_channel_fields', 'exp_field_groups.group_id = exp_channel_fields.group_id', 'left');
		$this->db->where('exp_field_groups.site_id', $this->config->item('site_id'));
		$this->db->group_by('exp_field_groups.group_id');
		$this->db->order_by('exp_field_groups.group_name');
		
		return $this->db->get();		
	}

	// --------------------------------------------------------------------

	/**
	 * Get Field Group Data
	 *
	 * Gets the data for a single field group
	 *
	 * @access	public
	 * @param	int		the field group id
	 * @return	object
	 */
	function get_field_group_data($field_group_id = '')
	{
		// @confirm: This needs to be checked against the original code - DA
		$this->db->from('field_groups');
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('group_id', $field_group_id);
		$this->db->order_by('group_id');
		
		return $this->db->get();
	}

	// --------------------------------------------------------------------

	/**
	 * Insert Field Group
	 *
	 * @access	public
	 * @return	void
	 */
	function insert_field_group($group_name)
	{
		$data = array(
						'group_name' => $group_name,
						'site_id' => $this->config->item('site_id')
		);

		$this->db->insert('field_groups', $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Update Fields
	 *
	 * @access	public
	 * @return	void
	 */
	function update_fields($group_name, $group_id)
	{
		$data = array(
						'group_name' => $group_name,
						'site_id' => $this->config->item('site_id')
		);

		$this->db->where('group_id', $group_id);

		$this->db->update('field_groups', $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Duplicate Field Group Name Check
	 *
	 * @access	public
	 * @return	boolean
	 */
	function is_duplicate_field_group_name($group_name = '', $group_id = '')
	{
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('group_name', $group_name);
		$this->db->from('field_groups');

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
	 * Get all field content types
	 *
	 * @access	public
	 * @return	mixed
	 */
	function get_field_content_types($parent = FALSE)
	{
		$field_types['file'] = array('image');
		$field_types['text'] = array('integer', 'numeric');
		
		if ($parent)
		{
			return (isset($field_types[$parent])) ? $field_types[$parent] : FALSE;
		}
		
		return $field_types;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Get all channels the field group is assigned to
	 *
	 * @access	public
	 * @param	int		the field group id
	 * @return	object
	 */
	function get_assigned_channels($group_id)
	{
		$this->db->select('channel_id');
		$this->db->from('channels');
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('field_group', $group_id);
		
		return $this->db->get();
	}
	

}

/* End of file field_model.php */
/* Location: ./system/expressionengine/models/field_model.php */