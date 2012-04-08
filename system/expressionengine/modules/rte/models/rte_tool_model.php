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
	 * Get Tool List
	 * 
	 * @access	public
	 * @param	bool 	Only include tools that are enabled?
	 * @return	array 	Associative array of tools sorted by (translated) name
	 */
	public function get_tool_list($enabled_only = FALSE)
	{
		// get tools from DB
		if ($enabled_only)
		{
			$this->db->where('enabled', 'y');
		}

		$tools = $this->db->get('rte_tools')->result_array();

		// is there a better (translated) name we can use for any of them?
		foreach ($tools as &$tool)
		{
			$name_key = strtolower($tool['class']);

			$tool['name'] = ($name_key != lang($name_key)) ? lang($name_key) : $tool['name'];
		}

		// alpha sort by final tool name
		if (count($tools))
		{
			usort($tools, array($this, '_sort_by_name'));		
		}

		return $tools;
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
	 * Get Tools
	 * 
	 * @access	public
	 * @param	array	The IDs of the tools to get
	 * @return	array 	An array of tools, each indexed to globals, libraries, styles, and definition
	 */
	public function get_tools($tool_ids = array())
	{
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
			$base_name = str_replace('_rte', '', strtolower($t->class));

			// find the tool file
			foreach (array(PATH_RTE, PATH_THIRD) as $tmp_path)
			{
				$file = $tmp_path.$base_name.'/rte.'.$base_name.'.php';

				if ( ! file_exists($file))
				{
					continue;
				}
				
				// load it in, instantiate the tool & add the definition
				include_once($file);
				$TOOL = new $t->class();
				
				// loop through the pieces and pull them from the object
				foreach ($tool as $component => $default)
				{
					// make sure the method exists
					if (method_exists($t->class, $component))
					{
						$temp = $TOOL->$component();

						// make sure the values are of the same type
						if (gettype($default) === gettype($temp))
						{
							$tool[$component] = $temp;
						}
					}
					elseif (property_exists($t->class, $component))
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


	/**
	 * Helper for sorting tools by name (anonymous functions, please!)
	 * 
	 * @access	private
	 * @param	array 	Tool
	 * @param	array 	Tool
	 * @return	int
	 */
	private function _sort_by_name($a, $b)
	{
		return strcmp($a["name"], $b["name"]);
	}
}
// END CLASS

/* End of file rte_tool_model.php */
/* Location: ./system/expressionengine/modules/rte/models/rte_tool_model.php */