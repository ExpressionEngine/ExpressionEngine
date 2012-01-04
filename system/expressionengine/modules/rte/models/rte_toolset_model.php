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
	
	public function get_all( $list = FALSE )
	{
		$results = $this->db->get_where(
			'rte_toolsets',
			array(
				'site_id'	=> $this->config->item('site_id')
			)
		)->result_array();
		return $list ? $this->_make_list( $results ) : $results;
	}
	
	public function get_active( $list = FALSE )
	{
		$results = $this->db->get_where(
			'rte_toolsets',
			array(
				'enabled' => 'y',
				'site_id'	=> $this->config->item('site_id')
			)
		)->result_array();
		return $list ? $this->_make_list( $results ) : $results;
	}
	
	public function get_tools( $toolset_id = 0 )
	{
		$result = $this->db
					->select('rte_tools')
					->get_where(
						'rte_toolsets',
						array( 'rte_toolset_id' => $toolset_id ),
						1
					  );
		return $result->num_rows() ? explode( '|', $result->row('rte_tools') ) : array();
	}
	
	public function exists( $toolset_id = FALSE )
	{
		$ret = FALSE;
		if ( !! $toolset_id )
		{
			$ret = ( $this->db
						->get_where(
							'rte_toolsets',
							array( 'rte_toolset_id' => $toolset_id ),
							1
					 	  )
						->num_rows() > 0 );
		}
		return $ret;
	}
	
	public function member_can_access( $toolset_id = FALSE )
	{
		// are you an admin?
		$admin = ( $this->session->userdata('group_id') == '1' );
		if ( ! $admin )
		{
			# get the group_ids with access
			$result = $this->EE->db
						->select('module_member_groups.group_id')
						->from('module_member_groups')
						->join('modules', 'modules.module_id = module_member_groups.module_id')
						->where('modules.module_name',$this->name)
						->get();
			if ( $result->num_rows() )
			{
				foreach ( $result->result_array() as $r )
				{
					if ( $this->EE->session->userdata('group_id') == $r['group_id'] )
					{
						$admin = TRUE;
						break;
					}
				}
			}
		}
		
		// grab the toolset
		$toolset = $this->db->get_where(
			'rte_toolsets',
			array( 'rte_toolset_id' => $toolset_id ),
			1
		)->row();
		
		return ( ( $toolset->member_id != 0 && $toolset->member_id == $this->session->userdata('member_id') ) ||
				 ( $toolset->member_id == 0 && $admin ) );
	}
	
	public function get( $toolset_id = FALSE )
	{
		return $this->db
					->get_where(
						'rte_toolsets',
						array( 'rte_toolset_id' => $toolset_id ),
						1
					  )
					->row();
	}
	
	public function is_private( $toolset_id = FALSE )
	{
		return $this->db
					->select('member_id')
					->get_where(
						'rte_toolsets',
						array( 'rte_toolset_id' => $toolset_id ),
						1
					  )
					->row('member_id') != 0;
	}
	
	public function for_member( $member_id = FALSE )
	{
		return $this->db->get_where(
			'rte_toolsets',
			array(
				'member_id' => $member_id,
				'site_id'	=> $this->config->item('site_id')
			),
			1
		)->row();
	}
	
	public function save( $toolset=array(), $toolset_id=FALSE )
	{
		$toolset['site_id'] =  $this->config->item('site_id');
		$sql = $toolset_id	? $this->db->update_string( 'rte_toolsets', $toolset, array( 'rte_toolset_id' => $toolset_id ) )
							: $this->db->insert_string( 'rte_toolsets', $toolset );
		$this->db->query( $sql );
		return $this->db->affected_rows();
	}
	
	public function load_default_toolsets()
	{
		$this->load->model('rte_tool_model');

		// default toolset
		$tool_ids = $this->rte_tool_model->get_tool_ids(array(
			'view_source'
		));
		$this->db->insert(
			'rte_toolsets',
			array(
				'site_id'	=> $this->config->item('site_id'),
				'name'		=> 'Default',
				'rte_tools'	=> implode( '|', $tool_ids ),
				'enabled'	=> 'y'
			)
		);
		
		// another toolset
		$tool_ids = $this->rte_tool_model->get_tool_ids(array(
			'testing_another', 'view_source', 'test_tool'
		));
		$this->db->insert(
			'rte_toolsets',
			array(
				'site_id'	=> $this->config->item('site_id'),
				'name'		=> 'Another Toolset',
				'rte_tools'	=> implode( '|', $tool_ids ),
				'enabled'	=> 'y'
			)
		);
	}

	private function _make_list( $result )
	{
		$return = array();
		
		foreach ( $result as $r )
		{
			$return[$r['rte_toolset_id']] = $r['name'];
		}
		
		return $return;
	}
	
}
// END CLASS

/* End of file rte_toolset_model.php */
/* Location: ./system/expressionengine/modules/rte/models/rte_toolset_model.php */