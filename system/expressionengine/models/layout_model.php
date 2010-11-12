<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
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
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
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
}

/* End of file layout_model.php */
/* Location: ./system/expressionengine/models/layout_model.php */