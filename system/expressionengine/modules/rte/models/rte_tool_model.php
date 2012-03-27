<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.5
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Rich Text Editor Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://expressionengine.com
 */
class Rte_tool_model extends CI_Model {

	private $tools;
	
	/**
	 * Gets all RTE tools
	 * 
	 * @access	public
	 * @param	bool $list Whether or not you want it to be a ID => name list
	 * @return	array The tools
	 */
	public function get_all($list = FALSE)
	{
		// get the tools from the DB
		$results = $this->db->get('rte_tools')
			->result_array();

		// decide what to return
		return $list ? $this->_make_list($results) : $results;
	}
	
	/**
	 * Gets all enabled RTE tools
	 * 
	 * @access	public
	 * @param	bool $list Whether or not you want it to be a ID => name list
	 * @return	array The tools
	 */
	public function get_available($list = FALSE)
	{
		// get the tools from the DB
		$results = $this->db->get_where('rte_tools', array('enabled' => 'y'))
			->result_array();
		// decide what to return
		return $list ? $this->_make_list($results) : $results;
	}
	
	/**
	 * Gets the tool IDs for the supplied tools
	 * 
	 * @access	public
	 * @param	array $tools An array of string tool names in the specific order you want them
	 * @return	array The tool IDs
	 */
	public function get_tool_ids($tools = array())
	{
		$tool_ids = array();
		// make sure we have tools
		if (count($tools))
		{
			// make sure the class name is correct
			foreach ($tools as &$tool)
			{
				$tool = ucfirst(strtolower($tool)).'_rte';
			}

			// get the tools
			$results = $this->db->select(array('rte_tool_id','class'))
				->where_in('class', $tools)
				->get('rte_tools')
				->result_array();

			// extract the ids
			foreach ($results as $row)
			{
				// set the indexes according to the original array
				$tool_ids[array_search($row['class'],$tools)] = $row['rte_tool_id'];
			}

			// sort them appropriately
			ksort($tool_ids, SORT_NUMERIC);
		}
		// return the IDs
		return $tool_ids;
	}
	
	/**
	 * Gets all tools in a given toolset
	 * 
	 * @access	public
	 * @param	int		The ID of the toolset
	 * @return	array 	An array of tools, each indexed to globals, libraries, styles, and definition
	 */
	public function get_tools($toolset_id = FALSE)
	{
		// Get the tool ids used by this toolset
		$query = $this->db->where('rte_toolset_id', $toolset_id)
			->get('rte_toolsets');

		$tool_ids = $query->num_rows() ? explode('|', $query->row('rte_tools')) : FALSE;

		if ( ! $tool_ids)
		{
			return FALSE;
		}

		// Grab each tool's row
		$query = $this->db->where_in('rte_tool_id', $tool_ids)
			->where('enabled', 'y')
			->get('rte_tools');

		// Index them by their position in $tool_ids
		foreach ($query->result() as $row)
		{
			$i = array_search($row->rte_tool_id, $tool_ids);
			$tools_sorted[$i] = $row;
		}
		
		// Sort by index
		ksort($tools_sorted);
		
		// Define the components of each tool
		$tool = array(
			'info'			=> array(),
			'globals'		=> array(),
			'libraries'		=> array(),
			'styles'		=> '',
			'definition'	=> ''
		);

		foreach ($tools_sorted as $t)
		{
			$tool_name	= strtolower(str_replace(' ', '_', $t->name));
			$tool_class	= ucfirst($tool_name).'_rte';
			
			// find the tool file
			foreach (array(PATH_RTE, PATH_THIRD) as $tmp_path)
			{
				$file = $tmp_path.$tool_name.'/rte.'.$tool_name.'.php';

				if ( ! file_exists($file))
				{
					continue;
				}
				
				// load it in, instantiate the tool & add the definition
				include_once($file);
				$TOOL = new $tool_class();
				
				// loop through the pieces and pull them from the object
				foreach ($tool as $component => $default)
				{
					// make sure the method exists
					if (method_exists($tool_class, $component))
					{
						$temp = $TOOL->$component();

						// make sure the values are of the same type
						if (gettype($default) === gettype($temp))
						{
							$tool[$component] = $temp;
						}
					}
					elseif (property_exists($tool_class, $component))
					{
						$tool[$component] = $TOOL->$component;
					}
				}

				break;
			}

			$tools[] = $tool;
		}

		return $tools;
	}


	/**
	 * Save a tool
	 * 
	 * @access	public
	 * @param	array $tool Tool row to update/insert
	 * @param	int $tool_id The ID of the tool to update
	 * @return	int
	 */
	public function save_tool($tool = array(), $tool_id = FALSE)
	{
		// update or insert?
		if ($tool_id)
		{
			$this->db->where('rte_tool_id', $tool_id)
				->update('rte_tools', $tool);
		} 
		else
		{
			$this->db->insert('rte_tools', $tool);		
		}

		// return the affected rows
		return $this->db->affected_rows();
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
			$return[$r['rte_tool_id']] = $r['name'];
		}
		
		return $return;
	}
	
	/**
	 * Load tools into the DB
	 */
	public function load_tools_into_db()
	{
		$this->load->library('addons');
		
		// get the file list and the installed tools list
		$files		= $this->addons->get_files('rte_tools');
		$installed	= $this->addons->get_installed('rte_tools');
		$classes	= array();
		
		// add new tools
		foreach ($files as $package => $details)
		{
			$classes[] = $details['class'];
			if ( ! isset($installed[$package]))
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
		
		// cleanup removed tools
		$this->db->where_not_in('class', $classes)
			->delete('rte_tools');
	}
	
}
// END CLASS

/* End of file rte_toolset_model.php */
/* Location: ./system/expressionengine/modules/rte/models/rte_tool_model.php */