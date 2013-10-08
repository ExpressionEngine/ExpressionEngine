<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Layout Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Layout_model extends CI_Model {

	/**
	 * Update Layouts
	 *
	 * Adds a new tab and all associated fields to all existing layouts
	 *
	 * @access	public
	 * @param	array	Altered tabs and/or fields
	 * @param	string	Action to take
	 * @param	int		The channel id
	 * @return	bool
	 */
	function update_layouts($layout_info, $action, $channel_id = array())
	{
		if ( ! is_array($channel_id))
		{
			$channel_id = array($channel_id);
		}
		
		$this->db->select('layout_id, field_layout');
		
		if (count($channel_id) > 0)
		{
			$this->db->where_in('channel_id', $channel_id);
		}

		$query = $this->db->get('layout_publish');
		$errors = array();
		
		$valid_actions = array('add_tabs', 'delete_tabs', 'add_fields', 'delete_fields');

		if (! in_array($action, $valid_actions))
		{
			return FALSE;
		}

		if ($query->num_rows() > 0)
		{
			foreach($query->result() as $row)
			{
				$layout = unserialize($row->field_layout);
				
				if ($action == 'add_tabs')
				{
					foreach($layout_info AS $tab => $fields)
					{
						// Check for proper field name
						
						$check_name = (is_array($fields)) ? key($fields) : $fields;
						if ($this->clean_field($check_name) !== TRUE)
						{
							$errors[] = $check_name;
							continue;
						}
						
						if (array_key_exists($tab, $layout) !== TRUE)
						{
							$layout[$tab] = $fields;
						}
						else
						{
							$layout[$tab] = $layout[$tab] + $fields;
						}
					}					
				}
				elseif ($action == 'add_fields')
				{
					foreach($layout_info AS $tab => $fields)
					{
						$check_name = (is_array($fields)) ? key($fields) : $fields;
						if ($this->clean_field($check_name) !== TRUE)
						{
							$errors[] = $check_name;
							continue;
						}

						if (array_key_exists($tab, $layout) !== TRUE)
						{
							$layout[$tab] = $fields;
						}
						else
						{
							$layout[$tab] = $layout[$tab] + $fields;
						}
					}
				}
				elseif ($action == 'delete_tabs')
				{
					foreach($layout_info AS $tab => $fields)
					{					
						$k_field = (is_array($fields)) ? key($fields) : $fields;
						
						if ($action == 'delete_tabs' && array_key_exists($tab, $layout) == TRUE)
						{
							unset($layout[$tab]);
						}

						foreach ($layout AS $existing_tab => $existing_field)
						{
							if (isset($layout[$existing_tab][$k_field]))
							{
								unset($layout[$existing_tab][$k_field]);
							}
						}
					}					

					// Replaced below w/code originally in member model				
					/*
					foreach($layout_info AS $tab => $fields)
					{					
						if (array_key_exists($tab, $layout))
						{
							unset($layout[$tab]);
						}
						
						foreach ($fields as $field_name => $settings)
						{
							foreach ($layout AS $existing_tab => $existing_field)
							{
								if (isset($layout[$existing_tab][$field_name]))
								{
									unset($layout[$existing_tab][$field_name]);
								}
							}
						}
					}
					*/
				}
				elseif ($action == 'delete_fields')
				{
					//  Note- is an array of field names
					foreach($layout_info AS $field_name)
					{
						foreach ($layout AS $existing_tab => $existing_field)
						{
							if (isset($layout[$existing_tab][$field_name]))
							{
								unset($layout[$existing_tab][$field_name]);
							}
						}
					}
				}

				$data = array('field_layout' => serialize($layout));
				$this->db->where('layout_id', $row->layout_id);
				$this->db->update('layout_publish', $data); 
			}
		}

		if ($errors > 0)
		{
			return $errors;
		}

		return TRUE;
	}
	
	function clean_field($name)
	{
		// Check for hinkiness in field names
		if (preg_match('/[^a-z0-9\_\-]/i', $name))
		{
			return FALSE;
		}
		elseif (trim($name) == '')
		{
			return FALSE;
		}
		
		return TRUE;
	}
	
	
	function edit_layout_fields($layout_info, $action, $channel_id = array(), $alter_tab = FALSE)
	{
		if ( ! is_array($channel_id))
		{
			$channel_id = array($channel_id);
		}
		
		$this->db->select('layout_id, field_layout');
		
		if (is_array($channel_id) && count($channel_id) > 0)
		{
			$this->db->where_in('channel_id', $channel_id);
		}
				
		$query = $this->db->get('layout_publish');
		$errors = 0;
		
		$valid_actions = array('show_fields', 'hide_fields', 'edit_fields', 'hide_tab_fields', 'show_tab_fields');

		if (! in_array($action, $valid_actions))
		{
			return FALSE;
		}

		if ($query->num_rows() > 0)
		{
			foreach($query->result() as $row)
			{
				$layout = unserialize($row->field_layout);
				
				if ($action == 'show_fields')
				{
					foreach($layout_info AS $field_name)
					{
						foreach ($layout AS $existing_tab => $existing_field)
						{
							if (isset($layout[$existing_tab][$field_name]['visible']))
							{
								$layout[$existing_tab][$field_name]['visible'] = TRUE;
							}
						}
					}
				}
				elseif ($action == 'hide_fields')
				{
					foreach($layout_info AS $field_name)
					{
						foreach ($layout AS $existing_tab => $existing_field)
						{
							if (isset($layout[$existing_tab][$field_name]['visible']))
							{
								$layout[$existing_tab][$field_name]['visible'] = FALSE;
							}
						}
					}
					
				}
				elseif ($action == 'edit_fields')
				{
					foreach($layout_info AS $field_name => $settings)
					{
						foreach ($layout AS $existing_tab => $existing_field)
						{
							if (isset($layout[$existing_tab][$field_name]))
							{
								$layout[$existing_tab][$field_name] = $settings;
							}
						}
					}
				}
				
				$data = array('field_layout' => serialize($layout));
				$this->db->where('layout_id', $row->layout_id);
				$this->db->update('layout_publish', $data); 
			}
		}
	}
	
	/**
	 * Edit one layout group's layout, as opposed to all of the layouts
	 *
	 * @param Array $layout_info Multidimensional array containing field names as the keys and their settings as the value
	 * @param Integer $layout_group_id The layout you want to change settings for
	 */
	function edit_layout_group_fields($layout_info, $layout_group_id)
	{
		if ( ! ctype_digit($layout_group_id)) {
			return FALSE;
		}
		
		$query = $this->db->get_where('layout_publish', array('layout_id' => $layout_group_id));
		
		if ($query->num_rows() > 0)
		{
			foreach($query->result() as $row)
			{
				$layout = unserialize($row->field_layout);
				
				foreach($layout_info AS $field_name => $settings)
				{
					foreach ($layout AS $existing_tab => $existing_field)
					{
						if (isset($layout[$existing_tab][$field_name]))
						{
							$layout[$existing_tab][$field_name] = $settings;
						}
					}
				}
				
				$data = array('field_layout' => serialize($layout));
				$this->db->where('layout_id', $row->layout_id);
				$this->db->update('layout_publish', $data); 
			}
		}
	}

	/**
	 * Create an array of layout settings for different fields
	 * The array is a flat array, NOT organized by tab
	 * 
	 * @param Array $parameters The parameters you want to do WHERE clauses on
	 * @return datatype description
	 */
	function get_layout_settings($parameters, $flatten = FALSE)
	{
		if (is_array($parameters) AND count($parameters) > 0)
		{
			$this->_where($parameters);
		}
		else
		{
			return FALSE;
		}
		
		$layout_settings = array();
		
		$this->db->select('layout_id, field_layout');
		$layouts = $this->db->get('layout_publish');
		
		if ($layouts->num_rows() > 0) // More than one row
		{
			foreach ($layouts->result() as $layout)
			{
				$field_layout = unserialize($layout->field_layout);
				$layout_settings[$layout->layout_id] = $this->_prep_layout_settings($field_layout, $flatten);
			}
		}
		
		// If there's only one layout, remove the outermost array
		if (count($layout_settings) == 1)
		{
			$layout_settings = array_shift($layout_settings);
		}
		
		return $layout_settings;
	}
	
	/**
	 * Sets the WHERE clauses by checking an associative array against an array of valid columns
	 *
	 * @param Array $parameters Associative array of column names and values for WHERE clauses
	 */
	function _where($parameters)
	{
		$valid_columns = array('layout_id', 'site_id', 'member_group', 'channel_id');
		
		foreach ($parameters as $column_name => $value)
		{
			if (in_array($column_name, $valid_columns))
			{
				$this->db->where($column_name, $value);
			}
		}
	}
	
	/**
	 * Prep layout settings, appending 'field_id_' to numeric keys
	 * Can optionally flatten the layout settings array
	 *
	 * @param Array $field_layout Unserialized array of layout settings, straight from the database
	 * @param Boolean $flatten Whether to flatten the array or not
	 * @return datatype description
	 */
	function _prep_layout_settings($field_layout, $flatten = FALSE)
	{
		$layout_settings = array();
		
		foreach ($field_layout as $layout_tab => $layout_fields)
		{
			foreach ($layout_fields as $field_key => $field_settings)
			{
				// Setup $current_settings as the layout settings with the tab name as the key
				$current_settings =& $layout_settings[$layout_tab];
				
				if ($flatten === TRUE)
				{
					// Check to see if the key starts with an underscore, we don't need those
					if (strncmp($field_key, '_', 1) === 0)
					{
						continue;
					}
					
					// If we're flattening the array, ditch the tab information
					$current_settings =& $layout_settings;
				}
				
				// If it's numeric, then append 'field_id_'
				if (is_numeric($field_key))
				{
					$current_settings['field_id_'.$field_key] = $field_settings;
				}
				else
				{
					$current_settings[$field_key] = $field_settings;
				}
			}
		}
		
		return $layout_settings;
	}
}

/* End of file layout_model.php */
/* Location: ./system/expressionengine/models/layout_model.php */