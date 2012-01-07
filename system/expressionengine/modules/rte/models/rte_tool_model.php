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
	
	public function __construct()
	{
		$this->_load_tools_into_db();
	}
	
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
	
	public function get_tool_ids( $tools=array() )
	{
		// make sure the class name is correct
		foreach ( $tools as &$tool )
		{
			$tool = ucfirst( strtolower( $tool ) ).'_rte';
		}
		// get the tools
		$results = $this->db
						->select(array('rte_tool_id','class'))
						->where_in('class', $tools)
						->get('rte_tools')
						->result_array();
		// extract the ids
		$tool_ids = array();
		foreach ( $results as $row )
		{
			$tool_ids[array_search($row['class'],$tools)] = $row['rte_tool_id'];
		}
		ksort($tool_ids, SORT_NUMERIC);
		return $tool_ids;
	}
	
	public function get_tool_js( $tool_id = FALSE )
	{
		$js = '';
		$results = $this->db->get_where(
			'rte_tools',
			array(
				'rte_tool_id'	=> $tool_id,
				'enabled'		=> 'y'
			)
		);
		if ( $results->num_rows() > 0 )
		{
			$tool		= $results->row();
			$tool_name	= strtolower( str_replace( ' ', '_', $tool->name ) );
			$tool_class	= ucfirst( $tool_name ).'_rte';
			foreach ( array(PATH_RTE, PATH_THIRD) as $tmp_path )
			{
				$file = $tmp_path.$tool_name.'/rte.'.$tool_name.'.php';
				if ( file_exists($file) )
				{
					//print_r($file); exit;
					include_once( $file );
					$TOOL = new $tool_class();
					$js = $TOOL->definition();
					break;
				}
			}
		}
		return $js;
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
	
	private function _load_tools_into_db()
	{
		$this->load->library('addons');
		
		$files		= $this->addons->get_files('rte_tools');
		$installed	= $this->addons->get_installed('rte_tools');

		foreach ( $files as $package => $details )
		{
			if ( ! isset($installed[$package]) )
			{
				// make a record of the add-on in the DB
				$this->db->insert(
					'rte_tools',
					array(
						'name'		=> $details['name'],
						'class'		=> $details['class'],
						'enabled'	=> 'y'
					)
				);
			}
		}
	}
	

}
// END CLASS

/* End of file rte_toolset_model.php */
/* Location: ./system/expressionengine/modules/rte/models/rte_tool_model.php */