<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Field Model
 */
class Field_model extends CI_Model {

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

	/**
	 * Get Field Group
	 *
	 * @access	public
	 * @param	int
	 * @return	mixed
	 */
	function get_fields($group_id = '', $additional_where = array())
	{
		$this->db->from('channel_fields');

		if ($group_id != '')
		{
			$this->db->join('exp_channel_field_groups_fields AS fgf', 'fgf.field_id = channel_fields.field_id', 'inner');
			$this->db->where('fgf.group_id', $group_id);
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

	/**
	 * Get Field Groups
	 *
	 * @access	public
	 * @return	object
	 */
	function get_field_groups()
	{
		$this->db->select('exp_field_groups.group_id, exp_field_groups.group_name,
							COUNT(exp_channel_field_groups_fields.field_id) as count');
		$this->db->from('exp_field_groups');
		$this->db->join('exp_channel_field_groups_fields', 'exp_field_groups.group_id = exp_channel_field_groups_fields.group_id', 'inner');
		$this->db->where('exp_field_groups.site_id', $this->config->item('site_id'));
		$this->db->group_by('exp_field_groups.group_id');
		$this->db->order_by('exp_field_groups.group_name');

		return $this->db->get();
	}

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
		$this->db->from('field_groups');
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('group_id', $field_group_id);
		$this->db->order_by('group_id');

		return $this->db->get();
	}

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

		ee('Model')->make('ChannelFieldGroup', $data)->save();
	}

	/**
	 * Delete Fields
	 *
	 * @access	public
	 * @return	void
	 */
	function delete_fields($field_id)
	{
		ee('Model')->get('ChannelField', $field_id)->delete();
		return array($field_id);
	}

	/**
	 * Delete Field Groups
	 *
	 * @access	public
	 * @return	void
	 */
	function delete_field_groups($group_id)
	{
		ee('Model')->get('ChannelFieldGroup', $group_id)->delete();
		return array();
	}

	/**
	 * Delete Field Groups
	 *
	 * @access	public
	 * @return	void
	 */
	function _remove_fields($results)
	{
		$this->load->library('api');
		$this->legacy_api->instantiate('channel_fields');
		$this->api_channel_fields->fetch_all_fieldtypes();

		$rel_ids = array();
		$deleted_fields = array();

		if ($results->num_rows() > 0)
		{
			foreach ($results->result_array() as $field)
			{

				$this->api_channel_fields->setup_handler($field['field_type']);
				$this->api_channel_fields->delete_datatype($field['field_id'], $field);

				$deleted_fields['field_ids'][] = $field['field_id'];
				$deleted_fields['group_id'] = (isset($field['group_id'])) ? $field['group_id'] : '';
				$deleted_fields['field_label'] = (isset($field['field_label'])) ? $field['field_label'] : '';
			}

			// Make sure a deleted field is not assigned as the search excerpt
			$this->db->where_in('search_excerpt', $deleted_fields['field_ids']);
			$this->db->update('channels', array('search_excerpt' => NULL));

			//  Get rid of any stray relationship data
			if (count($rel_ids) > 0)
			{
				$this->db->where_in('relationship_id', $rel_ids);
				$this->db->delete('relationships');
			}
		}

		return $deleted_fields;
	}

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

	/**
	 * Get all field content types
	 *
	 * @access	public
	 * @return	mixed
	 */
	function get_field_content_types($parent = FALSE)
	{
		$field_types['file'] = array('image');
		$field_types['text'] = array('integer', 'numeric', 'decimal');

		if ($parent)
		{
			return (isset($field_types[$parent])) ? $field_types[$parent] : FALSE;
		}

		return $field_types;
	}

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
		$this->db->join('exp_channels_channel_field_groups AS fg', 'fg.channel_id = channels.channel_id', 'inner');
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('fg.group_id', $group_id);

		return $this->db->get();
	}


}

// EOF
