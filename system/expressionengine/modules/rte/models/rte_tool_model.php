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
class Rte_tool_model extends CI_Model {

	private $tools;
	
	public function get_all($list=FALSE)
	{
		$results = $this->db->get('rte_tools')->result_array();
		return $list ? $this->_make_list( $results ) : $results;
	}
	
	public function get_available($list=FALSE)
	{
		$results = $this->db->get_where('rte_tools', array('enabled' => 'y'))->result_array();
		return $list ? $this->_make_list( $results ) : $results;
	}
	
	public function save( $tool=array(), $tool_id=FALSE )
	{
		$sql = $toolset_id	? $this->db->update_string( 'rte_tools', $tool, array( 'rte_tool_id' => $tool_id ) )
							: $this->db->insert_string( 'rte_tools', $tool );
		return $this->db->affected_rows();
	}

	private function _make_list( $result )
	{
		$return = array();
		
		foreach ( $result as $r )
		{
			$return[$r['rte_tool_id']] = $r['name'];
		}
		
		return $return;
	}

}
// END CLASS

/* End of file rte_toolset_model.php */
/* Location: ./system/expressionengine/modules/rte/models/rte_tool_model.php */