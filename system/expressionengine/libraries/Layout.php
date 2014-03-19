<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Publish Layout Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Layout {

	var $custom_layout_fields = array();

	/**
	 * Constructor
	 *
	 * Get reference to EE SuperObject
	 */
	function __construct()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------


	function duplicate_layout($dupe_id, $channel_id)
	{
		ee()->load->model('member_model');

		$layouts = ee()->member_model->get_all_group_layouts(array($dupe_id));

		if (empty($layouts))
		{
			return;
		}

		// open each one
		foreach ($layouts as $layout)
		{
			$layout['field_layout'];

			ee()->db->set("site_id", $layout['site_id']);
			ee()->db->set("channel_id", $channel_id);
			ee()->db->set("field_layout", $layout['field_layout']);
			ee()->db->set("member_group", $layout['member_group']);

			ee()->db->insert('layout_publish');
		}
	}

	function delete_channel_layouts($channel_id)
	{
		ee()->load->model('member_model');
		ee()->member_model->delete_group_layout('', $channel_id);
	}

	function edit_layout_fields($field_info, $channel_id)
	{
		ee()->load->model('layout_model');

		if ( ! is_array($channel_id))
		{
			$channel_id = array($channel_id);
		}

		ee()->layout_model->edit_layout_fields($field_info, 'edit_fields', $channel_id);
	}

	// --------------------------------------------------------------------

	/**
	 * Updates saved publish layouts
	 *
	 * @access	public
	 * @param	array
	 * @return	bool
	 */
	function sync_layout($fields = array(), $channel_id = '', $changes_only = TRUE)
	{
		ee()->load->model('layout_model');

		$new_settings = array();
		$changed = array();
		$hide_fields = '';
		$hide_tab_fields = array();
		$show_fields = '';
		$show_tab_fields = array();
		$delete_fields = array();

		$default_settings = array(
			'visible'		=> TRUE,
			'collapse'		=> FALSE,
			'htmlbuttons'	=> FALSE,
			'width'			=> '100%'
		);

		$layout_fields = array('enable_versioning', 'comment_system_enabled');


		foreach ($layout_fields as $field)
		{
			if (isset($fields[$field]))
			{
				$new_settings[$field] = $fields[$field];
			}
		}

		ee()->db->select('enable_versioning, comment_system_enabled');
		ee()->db->where('channel_id', $channel_id);
		$current = ee()->db->get('channels');

		if ($current->num_rows() > 0)
		{
			$row = $current->row_array();

			foreach ($new_settings as $field => $val)
			{
				if ($val != $row[$field]) // Undefined index: show_author_menu
				{
					$changed[$field] = $val;
				}
			}
		}

		if ( ! empty ($changed))
		{
			foreach ($changed as $field => $val)
			{
				switch ($field) {
					case 'enable_versioning':

						if ($val == 'n')
						{
							$hide_tab_fields['revisions'] = array('revisions');
						}
						else
						{
							$show_tab_fields['revisions'] = array('revisions' => $default_settings);
						}

						break;
					case 'comment_system_enabled':

						if ($val == 'n')
						{
							$delete_fields[] = 'comment_expiration_date';
						}
						else
						{
							$show_tab_fields['date'] = array('comment_expiration_date' => $default_settings);
						}

						break;
					}
			}
		}

		if ( ! empty($hide_tab_fields))
		{
			//ee()->layout_model->edit_layout_fields($hide_tab_fields, 'hide_tab_fields', $channel_id, TRUE);
			ee()->layout_model->update_layouts($hide_tab_fields, 'delete_tabs', $channel_id);
		}

		if ( ! empty($show_tab_fields))
		{
			//ee()->layout_model->edit_layout_fields($show_tab_fields, 'show_tab_fields', $channel_id, TRUE);
			ee()->layout_model->update_layouts($show_tab_fields, 'add_tabs', $channel_id);
		}

		if ( ! empty($delete_fields))
		{
			ee()->layout_model->update_layouts($delete_fields, 'delete_fields', $channel_id);
		}

		return;
	}


	// --------------------------------------------------------------------

	/**
	 * Updates saved publish layouts
	 *
	 * @access	public
	 * @param	array
	 * @return	bool
	 */
	function delete_layout_tabs($tabs = array(), $namespace = '', $channel_id = array())
	{
		if ( ! is_array($tabs) OR count($tabs) == 0)
		{
			return FALSE;
		}

		foreach ($tabs as $key => $val)
		{
			if ($namespace != '')
			{
				foreach ($val as $field_name => $data)
				{
					$tabs[$key][$namespace.'__'.$field_name] = $data;
					unset($tabs[$key][$field_name]);
				}
			}

			$clean_tabs[strtolower($key)] = $tabs[$key];
		}

		ee()->load->model('layout_model');

		return ee()->layout_model->update_layouts($clean_tabs, 'delete_tabs', $channel_id);
	}

	// --------------------------------------------------------------------

	/**
	 * Add new tabs and associated fields to saved publish layouts
	 *
	 * @access	public
	 * @param	array
	 * @return	bool
	 */
	function add_layout_tabs($tabs = array(), $namespace = '', $channel_id = array())
	{
		if ( ! is_array($tabs) OR count($tabs) == 0)
		{
			return FALSE;
		}

		foreach ($tabs as $key => $val)
		{
			if ($namespace != '')
			{
				foreach ($val as $field_name => $data)
				{
					$tabs[$key][$namespace.'__'.$field_name] = $data;
					unset($tabs[$key][$field_name]);
				}
			}

			$clean_tabs[strtolower($key)] = $tabs[$key];
		}


		ee()->load->model('layout_model');
		ee()->layout_model->update_layouts($clean_tabs, 'add_tabs', $channel_id);
	}


	// --------------------------------------------------------------------


	/**
	 * Adds new fields to the saved publish layouts, creating the default tab if required
	 *
	 * @access	public
	 * @param	array
	 * @param	int
	 * @return	bool
	 */
	function add_layout_fields($tabs = array(), $channel_id = array())
	{
		if ( ! is_array($channel_id))
		{
			$channel_id = array($channel_id);
		}

		if ( ! is_array($tabs) OR count($tabs) == 0)
		{
			return FALSE;
		}

		foreach ($tabs as $key => $val)
		{
			$clean_tabs[strtolower($key)] = $tabs[$key];
		}

		ee()->load->model('layout_model');

		return ee()->layout_model->update_layouts($clean_tabs, 'add_fields', $channel_id);
	}

	// --------------------------------------------------------------------

	/**
	 * Deletes fields from the saved publish layouts
	 *
	 * @access	public
	 * @param	array or string
	 * @param	int
	 * @return	bool
	 */
	function delete_layout_fields($tabs, $channel_id = array())
	{
		if ( ! is_array($channel_id))
		{
			$channel_id = array($channel_id);
		}

		$clean_tabs = array();

		// note- this is a simple array of field ids- so let's break them down to that before sending them on

		if ( ! is_array($tabs))
		{
			$clean_tabs = array($tabs);
		}
		else
		{
			foreach($tabs as $key => $val)
			{
				// We do this in case they sent the full tab array instead of an array of field names
				if (is_array($val))
				{
					foreach ($val as $k => $v)
					{
						if (isset($tabs[$key][$k]['visible']))
						{
							$clean_tabs[] = $k;
						}
					}
				}
				else
				{
					$clean_tabs[] = $val;
				}
			}
		}

		ee()->load->model('layout_model');

		return ee()->layout_model->update_layouts($clean_tabs, 'delete_fields', $channel_id);
	}
}
// END CLASS

/* End of file layout.php */
/* Location: ./system/expressionengine/libraries/layout.php */