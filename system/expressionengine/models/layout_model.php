<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
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

	function Layout_model()
	{
		parent::CI_Model();
	}


	// --------------------------------------------------------------------

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
		$this->db->select('layout_id, field_layout');
		
		if (is_array($channel_id) && count($channel_id) > 0)
		{
			$this->db->where_in('channel_id', $channel_id);
		}
				
		$query = $this->db->get('layout_publish');
		$errors = 0;
		
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
						if (array_key_exists($tab, $layout) !== TRUE)
						{
							$layout[$tab] = $fields;
						}
						else
						{
							$errors++;
						}
					}					
				}
				elseif ($action == 'add_fields')
				{
					foreach($layout_info AS $tab => $fields)
					{
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
				}
				elseif ($action == 'delete_fields')
				{

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
			return FALSE;
		}

		return TRUE;
	}
	
	
	function edit_layout_fields($layout_info, $action, $channel_id = array(), $alter_tab = FALSE)
	{
		
	}
}

/* End of file layout_model.php */
/* Location: ./system/expressionengine/models/layout_model.php */