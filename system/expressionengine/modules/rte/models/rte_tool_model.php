<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
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
			$results = $this->db->select(array('tool_id','class'))
				->where_in('class', $tools)
				->get('rte_tools')
				->result_array();

			// extract the ids
			foreach ($results as $row)
			{
				// set the indexes according to the original array
				$tool_ids[array_search($row['class'], $tools)] = $row['tool_id'];
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
		$query = $this->db->where_in('tool_id', $tool_ids)
			->where('enabled', 'y')
			->get('rte_tools');

		// Index them by their position in $tool_ids
		foreach ($query->result() as $row)
		{
			$i = array_search($row->tool_id, $tool_ids);
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
			$this->db->where('tool_id', $tool_id)
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
	 * Convenience method to add a tool, pass it a tool name with or without
	 * spaces
	 * @param String $tool_name The name of the tool to add
	 * @return Integer The ID of the affected row
	 */
	public function add($tool_name)
	{
		return $this->save_tool(array(
			'name' 		=> ucfirst($tool_name),
			'class'		=> $this->_class_name($tool_name),
			'enabled'	=> 'y'
		));
	}

	/**
	 * Deletes a tool
	 * @param  String|integer $tool Either the name of the tool or the id of the tool
	 */
	public function delete($tool)
	{
		if (is_numeric($tool))
		{
			$this->db->where('tool_id', (int) $tool);
		}
		elseif (is_string($tool))
		{
			$this->db->where(array(
				'name'	=> ucfirst($tool),
				'class' => $this->_class_name($tool)
			));
		}
		else
		{
			return FALSE;
		}

		$this->db->delete('rte_tools');
	}

	/**
	 * Deletes tools from the database when the corresponding file is missing
	 */
	public function delete_missing_tools()
	{
		$this->load->library('addons');
		$tools = $this->get_tool_list();
		$files = $this->addons->get_files('rte_tools');

		// Map out the class names and tool_id's of each tool in the database
		$tool_map = array();
		foreach ($tools as $tool)
		{
			$tool_map[$tool['class']] = $tool['tool_id'];
		}

		// Check for RTE tool files and assign their class names as the key of
		// an array to diff later
		$files_map = array();
		foreach ($files as $file)
		{
			$files_map[$file['class']] = '';
		}
		
		// Diff the two arrays to figure out what's orphaned and delete 
		// those tools
		$orphaned_tools = array_diff_key($tool_map, $files_map);
		foreach ($orphaned_tools as $orphaned_tool_id)
		{
			$this->delete($orphaned_tool_id);
		}
	}

	/**
	 * Generates the class name that should be being used (e.g. tool becomes Tool_rte)
	 * @param  String $tool_name The name of the tool to get the class name for
	 * @return String The class name of the tool
	 */
	private function _class_name($tool_name)
	{
		return ucfirst(strtolower(str_replace(' ', '_', $tool_name))).'_rte';
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