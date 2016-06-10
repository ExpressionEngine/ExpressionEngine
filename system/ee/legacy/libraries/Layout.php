<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
 */
class Layout {

	var $custom_layout_fields = array();

	function duplicate_layout($dupe_id, $channel_id)
	{
		$layouts = ee('Model')->get('ChannelLayout')
			->filter('channel_id', $dupe_id)
			->all();

		if ( ! $layouts)
		{
			return;
		}

		// open each one
		foreach ($layouts as $layout)
		{
			$data = $layout->getValues();
			unset($data['layout_id']);

			$data['channel_id'] = $channel_id;

			ee('Model')->make('ChannelLayout', $data)->save();
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

		$layouts = ee('Model')->get('ChannelLayout')
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		if ( ! $layouts)
		{
			return FALSE;
		}

		$tab_ids = array_keys($tabs);

		foreach ($layouts as $layout)
		{
			$old_field_layout = $layout->field_layout;
			$new_field_layout = array();

			foreach ($old_field_layout as $tab)
			{
				if (in_array($tab['id'], $tab_ids))
				{
					continue;
				}
				$new_field_layout[] = $tab;
			}

			$layout->field_layout = $new_field_layout;
			$layout->save();
		}

		return TRUE;
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

		$layouts = ee('Model')->get('ChannelLayout')
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		if ( ! $layouts)
		{
			return FALSE;
		}

		$new_tabs = array();

		foreach ($tabs as $key => $val)
		{
			$tab = array(
				'id' => strtolower($key),
				'name' => $key,
				'visible' => TRUE,
				'fields' => array()
			);

			foreach ($val as $field_name => $data)
			{
				if ( ! empty($namespace))
				{
					$field_name = $namespace . '__' . $field_name;
					$tab['fields'][] = array(
						'field' => $field_name,
						'visible' => TRUE,
						'collapsed' => FALSE
					);
				}
			}

			$new_tabs[] = $tab;
		}


		foreach ($layouts as $layout)
		{
			$field_layout = $layout->field_layout;

			foreach ($new_tabs as $tab)
			{
				$field_layout[] = $tab;
			}

			$layout->field_layout = $field_layout;
			$layout->save();
		}
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

// EOF
