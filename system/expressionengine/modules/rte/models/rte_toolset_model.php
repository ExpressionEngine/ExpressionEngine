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