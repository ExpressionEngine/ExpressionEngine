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
 * Rich Text Editor Module
 */
class Rte_toolset_model extends CI_Model {

	/**
	 * Get member's RTE preferences
	 *
	 * @access	public
	 * @return	array
	 */
	public function get_member_prefs($member_id = 0)
	{
		// get member's toolset preference
		$prefs = $this->db->select('rte_enabled, rte_toolset_id')
			->where('member_id', $member_id)
			->get('members')
			->row_array();

		return $prefs;
	}


	/**
	 * Get all toolsets available for the current member
	 *
	 * @access	public
	 * @param	int $member_id Member ID for whose toolsets we want
	 * @return	array The tools in ID => name format
	 */
	public function get_member_toolsets($member_id = 0)
	{
		// get all available toolsets
		$toolsets = $this->db->where('member_id', $member_id)
			->or_where("member_id = '0' AND enabled = 'y'")
			->get('rte_toolsets')
			->result_array();

		return $toolsets;
	}


	/**
	 * Get all the Toolsets
	 *
	 * @access	public
	 * @param	bool $list Whether or not you want it to be a ID => name list
	 * @return	array The tools
	 */
	public function get_toolset_list($enabled_only = FALSE)
	{
		if ($enabled_only)
		{
			$this->db->where('enabled', 'y');
		}

		$this->db
			->where('member_id', '0') // public toolsets only
			->order_by('name', 'asc');

		$toolsets = $this->db->get('rte_toolsets')->result_array();

		return $toolsets;
	}


	/**
	 * Get the tools for the member’s toolset
	 *
	 * @param	bool
	 * @return	int The ID of the current member’s toolset
	 */
	public function get_member_toolset()
	{
		$result	= $this->db
			->select('members.rte_toolset_id')
			->from('members')
			->where('members.member_id', $this->session->userdata('member_id'))
			->join('rte_toolsets', 'members.rte_toolset_id = rte_toolsets.toolset_id')
			->get();

		// member’s choice?
		if ($result->num_rows())
		{
			$toolset_id	= $result->row('rte_toolset_id');
		}
		else
		{
			// Fallback to default
			$toolset_id	= $this->config->item('rte_default_toolset_id');
		}

		return $toolset_id;
	}


	/**
	 * Check to see if the current member can access the Toolset
	 *
	 * @access	public
	 * @param	int $toolset_id The Toolset ID
	 * @return	bool
	 */
	public function member_can_access($toolset_id = FALSE)
	{
		// Get the toolset
		$toolset = $this->db->where('toolset_id', $toolset_id)
			->get('rte_toolsets')
			->row();

		if ( ! $toolset)
		{
			return FALSE;
		}

		// are you an admin?
		$admin = ($this->session->userdata('group_id') == '1');

		if ( ! $admin)
		{
			// get the group_ids with access
			$result = $this->db
						->select('module_member_groups.group_id')
						->from('module_member_groups')
						->join('modules', 'modules.module_id = module_member_groups.module_id')
						->where('modules.module_name', 'Rte')
						->get();

			if ($result->num_rows())
			{
				foreach ($result->result_array() as $r)
				{
					if ($this->session->userdata('group_id') == $r['group_id'])
					{
						$admin = TRUE;
						break;
					}
				}
			}
		}

		return (($toolset->member_id != 0 && $toolset->member_id == $this->session->userdata('member_id')) ||
				($toolset->member_id == 0 && $admin));
	}


	/**
	 * Get the toolset
	 *
	 * @access	public
	 * @param	int $toolset_id The Toolset ID
	 * @return	obj The Toolset row object
	 */
	public function get($toolset_id)
	{
		// Get the tool ids used by this toolset
		$toolset = $this->db->where('toolset_id', $toolset_id)
			->get('rte_toolsets')
			->row_array();

		if ( ! $toolset)
		{
			return FALSE;
		}

		if ($toolset['tools'])
		{
			$toolset['tools'] = explode('|', $toolset['tools']);
		}
		else
		{
			$toolset['tools'] = array();
		}

		return $toolset;
	}


	/**
	 * Tells you if a toolset is private
	 *
	 * @access	public
	 * @param	int $toolset_id The Toolset ID
	 * @return	bool
	 */
	public function is_private($toolset_id = FALSE)
	{
		return $this->db->select('member_id')
			->get_where(
				'rte_toolsets',
				array( 'toolset_id' => $toolset_id ),
				1
			)
			->row('member_id') != 0;
	}


	/**
	 * Save a toolset
	 *
	 * @access	public
	 * @param	array	toolset row to update/insert
	 * @param	int		ID of the toolset to update
	 * @return	int
	 */
	public function save_toolset($toolset = array(), $toolset_id = FALSE)
	{
		// update or insert?
		if ($toolset_id)
		{
			$this->db->where('toolset_id', $toolset_id)
				->update('rte_toolsets', $toolset);
		}
		else
		{
			$this->db->insert('rte_toolsets', $toolset);
		}

		return $this->db->affected_rows();
	}


	/**
	 * Delete a toolset
	 *
	 * @access	public
	 * @param	int $toolset_id The ID of the toolset to delete
	 * @return	mixed
	 */
	public function delete($toolset_id = FALSE)
	{
		if ($toolset_id)
		{
			$this->db->where('toolset_id', $toolset_id)
				->delete('rte_toolsets');

			return $this->db->affected_rows();
		}

		return FALSE;
	}


	/**
	 * Load the Default Toolsets into the DB
	 */
	public function load_default_toolsets()
	{
		$this->load->model('rte_tool_model');

		$tool_names = array('Blockquote', 'Bold', 'Headings', 'Image', 'Italic',
			'Link', 'Ordered List', 'Underline', 'Unordered List',
			'View Source');

		// Load all available tools
		foreach ($tool_names as $tool)
		{
			$this->db->insert(
				'rte_tools',
				array(
					'name'		=> $tool,
					'class'		=> ucfirst(strtolower(str_replace(' ', '_', $tool))).'_rte',
					'enabled'	=> 'y'
				)
			);
		}

		// default toolset
		$tool_ids = $this->rte_tool_model->get_tool_ids(array(
			'headings', 'bold', 'italic',
			'blockquote', 'unordered_list', 'ordered_list',
			'link', 'image', 'view_source'
		));

		$this->db->insert(
			'rte_toolsets',
			array(
				'name'		=> 'Default',
				'tools'		=> implode('|', $tool_ids),
				'enabled'	=> 'y'
			)
		);
	}


	/**
	 * Check the name of the toolset for uniqueness
	 *
	 * @access	public
	 * @param	string $name The Toolset name
	 * @param	int $toolset_id The ID of the toolset (optional)
	 * @return	bool
	 */
	public function unique_name($name, $toolset_id = FALSE)
	{
		$where = array(
			'name' 		=> $name,
			'member_id' => 0
		);

		if ($toolset_id !== FALSE)
		{
			$where['toolset_id !='] = $toolset_id;
		}

		$query = $this->db->get_where('rte_toolsets', $where);

		return ! $query->num_rows();
	}

}
// END CLASS

// EOF
