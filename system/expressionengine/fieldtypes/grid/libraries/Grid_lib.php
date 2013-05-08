<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
 * ExpressionEngine Grid Field Library 
 *
 * @package		ExpressionEngine
 * @subpackage	Libraries
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Grid_lib {

	protected $_fieldtypes = array();
	protected $_validated = array();

	/**
	 * Handles EE_Fieldtype's display_field for displaying the Grid field
	 *
	 * @param	int		Field ID of field to delete
	 * @return	void
	 */
	public function display_field($entry_id, $data, $settings)
	{
		ee()->load->model('grid_model');
		ee()->load->helper('form_helper');

		// Get columns just for this field
		$vars['columns'] = ee()->grid_model->get_columns_for_field($settings['field_id']);

		// If validation data is set, we're likely coming back to the form on a
		// validation error
		if (isset($this->_validated[$settings['field_id']]['value']))
		{
			$rows = $this->_validated[$settings['field_id']]['value'];
		}
		// Load autosaved data
		elseif (isset($data['rows']))
		{
			$rows = $data['rows'];
		}
		// Otherwise, we're editing or creating a new entry
		else
		{
			$rows = ee()->grid_model->get_entry_rows($entry_id, $settings['field_id']);
		}

		$vars['rows'] = array();

		// Loop through row data and construct an array of publish field HTML
		// for the supplied field data
		foreach ($rows as $row_id => $row)
		{
			if ( ! is_numeric($row_id))
			{
				$row['row_id'] = $row_id;
			}
			
			foreach ($vars['columns'] as $column)
			{
				// Construct the HTML for this particular row and column
				$vars['rows'][$row['row_id']]['col_id_'.$column['col_id']] = $this->_publish_field_cell(
					$settings['field_name'],
					$column,
					$row
				);

				$vars['rows'][$row['row_id']]['row_id'] = $row_id;

				// If we're coming back from a validation error, make sure the
				// error message is set
				if (isset($row['col_id_'.$column['col_id'].'_error']))
				{
					$vars['rows'][$row['row_id']]['col_id_'.$column['col_id'].'_error'] = $row['col_id_'.$column['col_id'].'_error'];
				}
			}
		}

		// Create a blank row for cloning to enter more data
		foreach ($vars['columns'] as $column)
		{
			$vars['blank_row']['col_id_'.$column['col_id']] = $this->_publish_field_cell(
				$settings['field_name'],
				$column
			);
		}

		$vars['field_id'] = $settings['field_name'];

		return ee()->load->view('publish', $vars, TRUE);
	}

	// ------------------------------------------------------------------------
	
	/**
	 * Returns publish field HTML for a given cell
	 *
	 * @param	string	Field name for input field namespacing
	 * @param	array	Column data
	 * @param	array	Data for current row
	 * @return	string	HTML for specified cell's publish field
	 */
	protected function _publish_field_cell($field_name, $column, $row_data = NULL)
	{
		$this->_instantiate_fieldtype($column);

		// Developers can optionally implement grid_display_field, otherwise
		// we will try to use display_field
		$method = ee()->api_channel_fields->check_method_exists('grid_display_field')
			? 'grid_display_field' : 'display_publish_field';

		// Call the fieldtype's field display method and capture the output
		$display_field = ee()->api_channel_fields->apply(
			$method,
			form_prep(array($row_data['col_id_'.$column['col_id']]))
		);

		// How we'll namespace new and existing rows
		$row_id = ( ! isset($row_data['row_id'])) ? 'new_row_0' : 'row_id_'.$row_data['row_id'];

		// Return the publish field HTML with namespaced form field names
		return $this->_namespace_inputs(
			$display_field,
			'$1name="'.$field_name.'[rows]['.$row_id.'][$2]$3"'
		);
	}

	// ------------------------------------------------------------------------

	/**
	 * Interface for Grid fieldtype validation
	 * 
	 * @param	array	POST data from publish form
	 * @param	int		Field ID of field being validated
	 * @return	array	Validated field data
	 */
	public function validate($data, $field_id)
	{
		// Empty field
		if ( ! isset($data['rows']))
		{
			return TRUE;
		}

		// Return from cache if exists
		if (isset($this->_validated[$field_id]))
		{
			return $this->_validated[$field_id];
		}

		// Process the posted data and cache
		$this->_validated[$field_id] = $this->_process_field_data('validate', $data, $field_id);

		return $this->_validated[$field_id];
	}

	// ------------------------------------------------------------------------

	/**
	 * Interface for Grid fieldtype saving
	 * 
	 * @param	array	Validated Grid publish form data
	 * @param	int		Field ID of field being saved
	 * @return	boolean
	 */
	public function save($data, $field_id, $entry_id)
	{
		$field_data = $this->_process_field_data(
			'save',
			$data,
			$field_id
		);

		ee()->load->model('grid_model');

		ee()->grid_model->save_field_data(
			$field_data['value'],
			$field_id,
			$entry_id
		);

		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Processes a POSTed Grid field for validation for saving
	 *
	 * The main point of the validation method is, of course, to validate the
	 * data in each cell and collect any errors. But it also reconstructs
	 * the post data in a way that display_field can take it if there is a
	 * validation error. The validation routine also keeps track of any other
	 * input fields and carries them through to the save method so that those
	 * values are available to fieldtypes while they run their save methods.
	 *
	 * The save method takes the validated data and gives it to the fieldtype's
	 * save method for further processing, in which the fieldtype can specify
	 * other columns that need to be filled.
	 * 
	 * @param	string	Method to process, 'save' or 'validate'
	 * @param	array	Grid publish form data
	 * @param	int		Field ID of field being saved
	 * @return	boolean
	 */
	protected function _process_field_data($method, $data, $field_id)
	{
		ee()->load->helper('custom_field_helper');

		// Get column data for the current field
		ee()->load->model('grid_model');
		$columns = ee()->grid_model->get_columns_for_field($field_id);

		// We'll store our final values and errors here
		$final_values = array();
		$errors = FALSE;

		// Rows key may not be set if we're at the saving stage
		$data = (isset($data['rows'])) ? $data['rows'] : $data;

		foreach ($data as $row_id => $row)
		{
			foreach ($columns as $column)
			{
				$col_id = 'col_id_'.$column['col_id'];

				// Handle empty data for default input name
				if ( ! isset($row[$col_id]))
				{
					$row[$col_id] = NULL;
				}

				// Assign any other input fields to POST data for normal access
				foreach ($row as $key => $value)
				{
					$_POST[$key] = $value;

					// If we're validating, keep these extra values around so
					// fieldtypes can access them on save
					if ($method == 'validate' && ! isset($final_values[$row_id][$key]))
					{
						$final_values[$row_id][$key] = $value;
					}
				}
				
				$this->_instantiate_fieldtype($column);

				// Developers can optionally implement grid_validate/grid_save,
				// otherwise we will try to use validate/save
				$ft_method = ee()->api_channel_fields->check_method_exists('grid_'.$method)
					? 'grid_'.$method : $method;

				// Call the fieldtype's validate/save method and capture the output
				$result = ee()->api_channel_fields->apply($ft_method, array($row[$col_id]));

				// For validation, gather errors and validated data
				if ($method == 'validate')
				{
					$error = $result;

					// First, assign the row data as the final value
					$value = $row[$col_id];

					// Here we extract possible $value and $error variables to
					// overwrite the assumptions we've made, this is a chance for
					// fieldtypes to correct input data or show an error message
					if (is_array($result))
					{
						extract($result, EXTR_OVERWRITE);
					}

					// Assign the final value to the array
					$final_values[$row_id][$col_id] = $value;

					// If there's an error, assign the old row data back so the
					// user can see the error, and set the error message
					if (is_string($error) && ! empty($error))
					{
						$final_values[$row_id][$col_id] = $row[$col_id];
						$final_values[$row_id][$col_id.'_error'] = $error;
						$errors = lang('grid_validation_error');
					}
				}
				// 'save' method
				elseif ($method == 'save')
				{
					// Flatten array
					if (is_array($result))
					{
						$result = encode_multi_field($result);
					}
					
					$final_values[$row_id][$col_id] = $result;
				}

				// Remove previous input fields from POST
				foreach ($row as $key => $value)
				{
					unset($_POST[$key]);
				}
			}
		}

		return array('value' => $final_values, 'error' => $errors);
	}

	// ------------------------------------------------------------------------

	/**
	 * Gets a list of installed fieldtypes and filters them for ones enabled
	 * for Grid
	 *
	 * @return	array	Array of Grid-enabled fieldtypes
	 */
	public function get_grid_fieldtypes()
	{
		if ( ! empty($this->_fieldtypes))
		{
			return $this->_fieldtypes;
		}

		// Shorten some line lengths
		$ft_api = ee()->api_channel_fields;

		$this->_fieldtypes = $ft_api->fetch_installed_fieldtypes();

		foreach ($this->_fieldtypes as $field_name => $data)
		{
			$ft_api->setup_handler($field_name);

			// We'll check the existence of certain methods to determine whether
			// or not this fieldtype is ready for Grid
			if ( ! $ft_api->check_method_exists('grid_display_settings'))
			{
				unset($this->_fieldtypes[$field_name]);
			}
		}

		ksort($this->_fieldtypes);

		return $this->_fieldtypes;
	}

	// ------------------------------------------------------------------------
	
	/**
	 * Given POSTed column settings, adds new columns to the database and
	 * figures out if any columns need deleting
	 *
	 * @param	array	POSTed column settings from field settings page
	 * @return	void
	 */
	public function apply_settings($settings)
	{
		ee()->load->model('grid_model');
		$new_field = ee()->grid_model->create_field($settings['field_id']);

		// We'll use the order of the posted fields to determine the column order
		$count = 0;

		// Keep track of column IDs that exist so we can compare it against
		// other columns in the DB to see which we should delete
		$col_ids = array();

		// Go through ALL posted columns for this field
		foreach ($settings['grid']['cols'] as $col_field => $column)
		{
			// Attempt to get the column ID; if the field name contains 'new_',
			// it's a new field, otherwise extract column ID
			$column['col_id'] = (strpos($col_field, 'new_') === FALSE)
				? str_replace('col_id_', '', $col_field) : FALSE;

			$column['col_required'] = isset($column['col_required']) ? 'y' : 'n';
			$column['col_settings'] = $this->_save_settings($column);
			$column['col_settings']['field_required'] = $column['col_required'];

			// Default width to zero
			if (empty($column['col_width']))
			{
				$column['col_width'] = 0;
			}

			$column_data = array(
				'field_id'			=> $settings['field_id'],
				'col_order'			=> $count,
				'col_type'			=> $column['col_type'],
				'col_label'			=> $column['col_label'],
				'col_name'			=> $column['col_name'],
				'col_instructions'	=> $column['col_instructions'],
				'col_required'		=> $column['col_required'],
				'col_search'		=> isset($column['col_search']) ? 'y' : 'n',
				'col_width'			=> str_replace('%', '', $column['col_width']),
				'col_settings'		=> json_encode($column['col_settings'])
			);

			$col_ids[] = ee()->grid_model->save_col_settings($column_data, $column['col_id']);

			$count++;
		}

		// Delete columns that were not including in new field settings
		if ( ! $new_field)
		{
			$columns = ee()->grid_model->get_columns_for_field($settings['field_id'], FALSE);

			$old_cols = array();
			foreach ($columns as $column)
			{
				$old_cols[$column['col_id']] = $column['col_type'];
			}

			// Compare columns in DB to ones we gathered from the settings array
			$cols_to_delete = array_diff(array_keys($old_cols), $col_ids);

			// If any columns are missing from the new settings, delete them
			if ( ! empty($cols_to_delete))
			{
				ee()->grid_model->delete_columns($cols_to_delete, $old_cols, $settings['field_id']);
			}
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Calls grid_save_settings() on fieldtypes to do any extra processing on
	 * saved field settings
	 *
	 * @param	array	Column settings data
	 * @return	array	Processed settings
	 */
	protected function _save_settings($column)
	{
		$this->_instantiate_fieldtype($column);

		if ( ! isset($column['col_settings']))
		{
			$column['col_settings'] = array();
		}

		if (ee()->api_channel_fields->check_method_exists('grid_save_settings'))
		{
			return ee()->api_channel_fields->apply('grid_save_settings', array($column['col_settings']));
		}

		return $column['col_settings'];
	}

	// ------------------------------------------------------------------------
	
	/**
	 * Returns rendered HTML for a column on the field settings page
	 *
	 * @param	array	Array of single column settings from the grid_columns table
	 * @return	string	Rendered column view for settings page
	 */
	public function get_column_view($column = NULL)
	{
		$fieldtypes = $this->get_grid_fieldtypes();

		// Create a dropdown-frieldly array of available fieldtypes
		$fieldtypes_dropdown = array();
		foreach ($fieldtypes as $key => $value)
		{
			$fieldtypes_dropdown[$key] = $value['name'];
		}

		$field_name = (empty($column)) ? 'new_0' : 'col_id_'.$column['col_id'];

		$column['settings_form'] = (empty($column))
			? $this->get_settings_form('text') : $this->get_settings_form($column['col_type'], $column);

		if (isset($column['col_width']) && $column['col_width'] == 0)
		{
			$column['col_width'] = '';
		}

		return ee()->load->view(
			'col_tmpl',
			array(
				'field_name'	=> $field_name,
				'column'		=> $column,
				'fieldtypes'	=> $fieldtypes_dropdown
			),
			TRUE
		);
	}

	// ------------------------------------------------------------------------

	/**
	 * Returns rendered HTML for the custom settings form of a grid column type
	 *
	 * @param	string	Name of fieldtype to get settings form for
	 * @param	array	Column data from database to populate settings form
	 * @return	array	Rendered HTML settings form for given fieldtype and
	 * 					column data
	 */
	public function get_settings_form($type, $column = NULL)
	{
		// Returns blank settings form for a specific fieldtype
		if (empty($column))
		{
			ee()->api_channel_fields->setup_handler($type);

			return $this->_view_for_col_settings(
				$type,
				ee()->api_channel_fields->apply('grid_display_settings', array(array()))
			);
		}

		$this->_instantiate_fieldtype($column);

		// Otherwise, return the prepopulated settings form based on column settings
		return $this->_view_for_col_settings(
			$type,
			ee()->api_channel_fields->apply('grid_display_settings', array($column['col_settings'])),
			$column['col_id']
		);
	}

	// ------------------------------------------------------------------------

	/**
	 * Returns rendered HTML for the custom settings form of a grid column type,
	 * helper method for Grid_lib::get_settings_form
	 *
	 * @param	string	Name of fieldtype to get settings form for
	 * @param	array	Column data from database to populate settings form
	 * @param	int		Column ID for field naming
	 * @return	array	Rendered HTML settings form for given fieldtype and
	 * 					column data
	 */
	protected function _view_for_col_settings($col_type, $col_settings, $col_id = NULL)
	{
		$settings_view = ee()->load->view(
			'col_settings_tmpl',
			array(
				'col_type'		=> $col_type,
				'col_settings'	=> (empty($col_settings)) ? array() : $col_settings
			),
			TRUE
		);
		
		$col_id = (empty($col_id)) ? 'new_0' : 'col_id_'.$col_id;

		// Namespace form field names
		return $this->_namespace_inputs(
			$settings_view,
			'$1name="grid[cols]['.$col_id.'][col_settings][$2]$3"'
		);
	}

	// ------------------------------------------------------------------------

	/**
	 * Instantiates fieldtype handler and assigns information to the object
	 *
	 * @param	array	Column information
	 * @return	object	Fieldtype object
	 */
	protected function _instantiate_fieldtype($column)
	{
		// Instantiate fieldtype
		$fieldtype = ee()->api_channel_fields->setup_handler($column['col_type'], TRUE);

		// Assign settings to fieldtype manually so they're available like
		// normal field settings
		$fieldtype->field_id = $column['col_id'];
		$fieldtype->field_name = 'col_id_'.$column['col_id'];
		$fieldtype->settings = $column['col_settings'];
		$fieldtype->settings['field_required'] = $column['col_required'];

		return $fieldtype;
	}

	// ------------------------------------------------------------------------

	/**
	 * Performes find and replace for input names in order to namespace them
	 * for a POST array
	 *
	 * @param	string	String to search
	 * @param	string	String to use for replacement
	 * @return	string	String with namespaced inputs
	 */
	protected function _namespace_inputs($search, $replace)
	{
		return preg_replace(
			'/(<[input|select|textarea][^>]*)name=["\']([^"\'\[\]]+)([^"\']*)["\']/',
			$replace,
			$search
		);
	}
}

/* End of file Grid_lib.php */
/* Location: ./system/expressionengine/modules/grid/libraries/Grid_lib.php */