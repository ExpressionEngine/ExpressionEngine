<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Rich Text Editor Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		Aaron Gustafson
 * @link		http://easy-designs.net
 */
class Rte_toolset_model extends CI_Model {
	
	/**
	 * Get all the Toolsets
	 * 
	 * @access	public
	 * @param	bool $list Whether or not you want it to be a ID => name list
	 * @return	array The tools
	 */
	public function get_all($list = FALSE)
	{
		$results = $this->db->get_where(
				'rte_toolsets',
				array('member_id'	=> '0') // public toolsets only
			)
			->result_array();
		
		return $list ? $this->_make_list($results) : $results;
	}
	
	/**
	 * Get enabled Toolsets
	 * 
	 * @access	public
	 * @param	bool $list Whether or not you want it to be a ID => name list
	 * @return	array The tools
	 */
	public function get_active($list = FALSE)
	{
		$results = $this->db->get_where(
			'rte_toolsets',
			array(
				'member_id'	=> '0', // public only
				'enabled' 	=> 'y'
			)
		)->result_array();
		return $list ? $this->_make_list($results) : $results;
	}
	
	/**
	 * Get the toolsets available to a given Member
	 * 
	 * @access	public
	 * @return	array The tools in ID => name format
	 */
	public function get_member_options()
	{
		// get the toolsets
		$results = $this->db->where("
				( `member_id` = '{$this->session->userdata('member_id')}'
				  OR
				  ( `member_id` = '0' AND `enabled` = 'y' ) )
				",
				NULL, FALSE )
			->get('rte_toolsets')
			->result_array();

		// has this user made a personal toolset?
		$has_personal = FALSE;
		foreach ($results as $i => $toolset)
		{
			if ($toolset['member_id'] != 0)
			{
				$has_personal = TRUE;
				// move the personal one to the end of the array & rename it
				$tool = $toolset;
				unset($results[$i]);
				$results[] = $tool;
				break;
			}
		}
		// if no personal toolset, create one
		if ( ! $has_personal)
		{
			$results[] = array(
				'rte_toolset_id'	=> 'new',
				'name'				=> 'my_custom_toolset'
			);
		}
		return $this->_make_list($results);
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
			->join('rte_toolsets', 'members.rte_toolset_id = rte_toolsets.rte_toolset_id')
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
		$toolset = $this->db->where('rte_toolset_id', $toolset_id)
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
	public function get($toolset_id = FALSE)
	{
		// Get the tool ids used by this toolset
		$toolset = $this->db->where('rte_toolset_id', $toolset_id)
			->get('rte_toolsets')
			->row();

		if ( ! $toolset)
		{
			return FALSE;
		}

		$toolset->rte_tools = explode('|', $toolset->rte_tools);

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
				array( 'rte_toolset_id' => $toolset_id ),
				1
			)
			->row('member_id') != 0;
	}
	
	/**
	 * Save a toolset
	 * 
	 * @access	public
	 * @param	array $toolset The toolset details
	 * @param	int $toolset_id The ID of the Toolset (so you can update)
	 * @return	mixed
	 */
	public function save($toolset = array(), $toolset_id = FALSE)
	{
		// update
		if ($toolset_id)
		{
			$this->db->where('rte_toolset_id', $toolset_id)
				->update('rte_toolsets', $toolset);
		}
		// insert
		else
		{
			$this->db->insert('rte_toolsets', $toolset);
		}

		return TRUE;
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
			$this->db
				->where(array('rte_toolset_id' => $toolset_id))
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
				'rte_tools'	=> implode('|', $tool_ids),
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
	public function check_name($name, $toolset_id = FALSE)
	{
		$where = array('name' => $name);

		if ($toolset_id !== FALSE)
		{
			$where['rte_toolset_id !='] = $toolset_id;
		}

		$query = $this->db->get_where('rte_toolsets', $where);
		return ! $query->num_rows();
	}

	/**
	 * Make the results array into an <option>-compatible list
	 * 
	 * @access	private
	 * @param	array $result The result array to convert
	 * @return	array An ID => name array
	 */
	private function _make_list($result)
	{
		$return = array();
		
		foreach ($result as $r)
		{
			$return[$r['rte_toolset_id']] = $r['name'];
		}
		
		return $return;
	}
	
}
// END CLASS

/* End of file rte_toolset_model.php */
/* Location: ./system/expressionengine/modules/rte/models/rte_toolset_model.php */