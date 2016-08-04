<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Service\Validation\Result;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.7
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
 * @link		https://ellislab.com
 */

class Grid_lib {

	public $field_id;
	public $field_name;
	public $content_type;
	public $entry_id;

	protected $_fieldtypes = array();
	protected $_validated = array();

	public function __construct()
	{
		ee()->load->model('grid_model');
		ee()->load->library('grid_parser');
	}

	// ------------------------------------------------------------------------

	/**
	 * Handles EE_Fieldtype's display_field for displaying the Grid field
	 *
	 * @param	array	Grid input object
	 * @param	array	Field data to display prepopulated in publish field
	 * @return	string	HTML of publish field
	 */
	public function display_field($grid, $data)
	{
		// Get columns just for this field
		$columns = ee()->grid_model->get_columns_for_field($this->field_id, $this->content_type);

		// If validation data is set, we're likely coming back to the form on a
		// validation error
		if (isset($this->_validated[$this->field_id]['value']))
		{
			$rows = $this->_validated[$this->field_id]['value'];
		}
		// Load autosaved/revision data
		elseif (is_array($data))
		{
			$rows = isset($data['rows']) ? $data['rows'] : $data;
		}
		// Otherwise, we're editing or creating a new entry
		else
		{
			$rows = ee()->grid_model->get_entry_rows($this->entry_id, $this->field_id, $this->content_type);
			$rows = (isset($rows[$this->entry_id])) ? $rows[$this->entry_id] : array();
		}

		if (AJAX_REQUEST)
		{
			$column_id = ee()->input->post('column_id');
			$row_id = ee()->input->post('row_id');

			if ($column_id)
			{
				if ($row_id)
				{
					if ( ! array_key_exists($row_id, $rows))
					{
						$row_id = 'row_id_'.$row_id;
					}

					if (array_key_exists($row_id, $rows))
					{
						$html = $this->_publish_field_cell($columns[$column_id], $rows[$row_id]);
					}
				}
				else
				{
					$html = $this->_publish_field_cell($columns[$column_id]);
				}

				return $grid->namespaceForGrid($html, $row_id);
			}
		}

		$column_headings = array();
		$blank_column = array();
		foreach ($columns as $column)
		{
			$column_headings[] = array(
				'label' => $column['col_label'],
				'desc' => $column['col_instructions'],
				'required' => ($column['col_required'] == 'y')
			);

			$blank_column[] = array(
				'html' => $this->_publish_field_cell($column),
				'attrs' => array(
					'class' => $this->get_class_for_column($column),
					'data-fieldtype' => $column['col_type'],
					'data-column-id' => $column['col_id'],
					'width' => $column['col_width'].'%',
				)
			);
		}
		$grid->setColumns($column_headings);
		$grid->setBlankRow($blank_column);

		$data = array();

		foreach ($rows as $row_id => $row)
		{
			if ( ! is_numeric($row_id))
			{
				$row['row_id'] = $row_id;

				// We want to reserve the row-id data attribute for real row IDs, not
				// the string placeholders, in case folks are relying on having a real
				// number there or are using it to determine if a row is new or not
				$data_row_id_attr = 'data-new-row-id';
			}
			else
			{
				$data_row_id_attr = 'data-row-id';
			}

			$field_columns = array();

			foreach ($columns as $column)
			{
				$col = array(
					'html' => $this->_publish_field_cell($column, $row),
					'error' => isset($row['col_id_'.$column['col_id'].'_error']) ? $row['col_id_'.$column['col_id'].'_error'] : NULL,
					'attrs' => array(
						'class' => $this->get_class_for_column($column),
						'data-fieldtype' => $column['col_type'],
						'data-column-id' => $column['col_id'],
						$data_row_id_attr => $row_id,
						'width' => $column['col_width'].'%',
					)
				);

				if ($column['col_required'] == 'y')
				{
					$col['attrs']['class'] = 'required';
				}

				$field_columns[] = $col;
			}
			$data[] = array(
				'attrs' => array('row_id' => $row_id),
				'columns' => $field_columns
			);
		}

		$grid->setData($data);

		return ee('View')->make('ee:_shared/table')->render($grid->viewData());
	}

	protected function get_class_for_column($column)
	{
		switch ($column['col_type']) {
			case 'rte':
				$class = 'grid-rte';
				break;
			case 'relationship':
				$class = $column['col_settings']['allow_multiple'] ? 'grid-multi-relate' : 'grid-relate';
				break;
			case 'textarea':
				$class = 'grid-textarea';
				break;
			case 'toggle':
				$class = 'grid-toggle';
				break;
			default:
				$class = '';
				break;
		}

		return $class;
	}

	// ------------------------------------------------------------------------

	/**
	 * Returns publish field HTML for a given cell
	 *
	 * @param	array	Column data
	 * @param	array	Data for current row
	 * @return	string	HTML for specified cell's publish field
	 */
	protected function _publish_field_cell($column, $row = NULL)
	{
		$fieldtype = ee()->grid_parser->instantiate_fieldtype(
			$column,
			NULL,
			$this->field_id,
			$this->entry_id,
			$this->content_type
		);

		$row_data = (isset($row['col_id_'.$column['col_id']]))
			? $row['col_id_'.$column['col_id']] : '';

		if (isset($row['row_id']))
		{
			$fieldtype->settings['grid_row_id'] = $row['row_id'];
		}

		// Call the fieldtype's field display method and capture the output
		$display_field = ee()->grid_parser->call('display_field', $row_data);

		// Default name for new rows
		$row_id = 'new_row_0';

		// If row_id is set, perform an extra check before assigning it in case
		// we are coming back from a validation error
		if (isset($row['row_id']))
		{
			$row_id = (is_numeric($row['row_id']))
				? 'row_id_'.$row['row_id'] : $row['row_id'];
		}

		// Return the publish field HTML with namespaced form field names
		return $display_field;
	}

	// ------------------------------------------------------------------------

	/**
	 * Interface for Grid fieldtype validation
	 *
	 * @param	array	POST data from publish form
	 * @return	array	Validated field data
	 */
	public function validate($data)
	{
		// Get row data for this entry
		$rows = ee()->grid_model->get_entry($this->entry_id, $this->field_id, $this->content_type);

		// Check that we're editing a row that actually belongs to this entry
		$valid_rows = array();

		foreach($rows as $row)
		{
			$valid_rows[] = $row['row_id'];
		}

		if (isset($data['rows']))
		{
			foreach ($data['rows'] as $key => $row)
			{
				if (substr($key, 0, 6) == 'row_id')
				{
					$row_key = str_replace('row_id_', '', $key);

					if ( ! in_array($row_key, $valid_rows))
					{
						if (ee()->session->userdata['group_id'] == 1)
						{
							return array('value' => '', 'error' => lang('not_authorized'));
						}
						else
						{
							unset($data['rows'][$key]);
						}
					}
				}
			}
		}

		// Empty field
		if ( ! isset($data['rows']))
		{
			return TRUE;
		}

		// Return from cache if exists
		if (isset($this->_validated[$this->field_id]))
		{
			return $this->_validated[$this->field_id];
		}

		// Process the posted data and cache
		$this->_validated[$this->field_id] = $this->_process_field_data('validate', $data);

		return $this->_validated[$this->field_id];
	}

	// ------------------------------------------------------------------------

	/**
	 * Interface for Grid fieldtype saving
	 *
	 * @param	array	Validated Grid publish form data
	 * @return	boolean
	 */
	public function save($data)
	{
		$field_data = $this->_process_field_data('save', $data);

		$deleted_rows = ee()->grid_model->save_field_data(
			$field_data['value'],
			$this->field_id,
			$this->content_type,
			$this->entry_id
		);

		$columns = ee()->grid_model->get_columns_for_field($this->field_id, $this->content_type);

		// We'll keep track of searchable data for columns marked as searchable here
		$searchable_data = array();

		// Get row data to send back to fieldtypes with new row IDs
		$rows = ee()->grid_model->get_entry_rows($this->entry_id, $this->field_id, $this->content_type, array(), TRUE);
		$rows = $rows[$this->entry_id];

		// Remove deleted rows from $rows
		foreach ($deleted_rows as $deleted_row)
		{
			unset($rows[$deleted_row['row_id']]);
		}

		$i = 0;
		$rows = array_values($rows);

		// Call post_save callback for fieldtypes
		foreach ($field_data['value'] as $row_name => $data)
		{
			foreach ($columns as $col_id => $column)
			{
				$cell_data = isset($data['col_id_'.$col_id]) ? $data['col_id_'.$col_id] : '';

				$fieldtype = ee()->grid_parser->instantiate_fieldtype(
					$column,
					$row_name,
					$this->field_id,
					$this->entry_id,
					$this->content_type
				);

				if ( ! empty($rows[$i]['row_id']))
				{
					$fieldtype->settings['grid_row_id'] = $rows[$i]['row_id'];
				}

				ee()->grid_parser->call('post_save', $cell_data);

				// Add to searchable array if searchable
				if ($column['col_search'] == 'y')
				{
					$searchable_data[] = $cell_data;
				}
			}

			$i++;
		}

		// Collect row IDs of deleted rows to send to fieldtypes
		$row_ids = array();
		foreach ($deleted_rows as $row)
		{
			$row_ids[] = $row['row_id'];
		}

		$this->delete_rows($row_ids);

		if ( ! empty($searchable_data))
		{
			ee()->load->helper('custom_field_helper');

			// Update row in channel_data with searchable data string
			ee()->db->where('entry_id', $this->entry_id)
				->update('channel_data', array(
					'field_id_'.$this->field_id => encode_multi_field($searchable_data)
				)
			);
		}

		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Notifies fieldtypes of impending deletion of their Grid rows, and then
	 * deletes those rows
	 *
	 * @param	array	Validated Grid publish form data
	 * @return	boolean
	 */
	public function delete_rows($row_ids)
	{
		if (empty($row_ids))
		{
			return;
		}

		$columns = ee()->grid_model->get_columns_for_field($this->field_id, $this->content_type);

		// Call delete/grid_delete on each affected fieldtype and send along
		// the row IDs
		foreach ($columns as $column)
		{
			ee()->grid_parser->instantiate_fieldtype($column, NULL, $this->field_id, 0, $this->content_type);
			ee()->grid_parser->call('delete', $row_ids);
		}

		ee()->grid_model->delete_rows($row_ids, $this->field_id, $this->content_type);
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
	 * @param	int		Entry ID of entry being saved
	 * @return	boolean
	 */
	protected function _process_field_data($method, $data)
	{
		ee()->load->helper('custom_field_helper');

		// Get column data for the current field
		$columns = ee()->grid_model->get_columns_for_field($this->field_id, $this->content_type);

		// We'll store our final values and errors here
		$final_values = array();
		$errors = FALSE;

		if ( ! is_array($data))
		{
			$data = array();
		}
		// Rows key may not be set if we're at the saving stage
		elseif (isset($data['rows']))
		{
			$data = $data['rows'];
		}

		// In EE3, Channel Form populates the field_name property of its
		// fieldtypes with the actual field_name on field display, but on
		// submit, field_name is actually the field ID. Trouble is, POST is
		// organized by field name instead of field ID, but we don't know our
		// field name, so we need to get it so we can properly traverse POST
		// and FILES. Hopefully will be back to consistent in EE4.
		if (REQ !== 'CP' && $this->field_name == 'field_id_'.$this->field_id)
		{
			$this->field_name = ee('Model')->get('ChannelField', $this->field_id)->first()->field_name;
		}

		// Make a copy of the files array so we can spoof it per field below
		$grid_field_name = $this->field_name;
		$files_backup = $_FILES;

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

				$fieldtype = ee()->grid_parser->instantiate_fieldtype(
					$column,
					$row_id,
					$this->field_id,
					$this->entry_id,
					$this->content_type
				);

				// Pass Grid row ID to fieldtype if it's an existing row
				if (strpos($row_id, 'row_id_') !== FALSE)
				{
					$fieldtype->settings['grid_row_id'] = str_replace('row_id_', '', $row_id);
				}

				// Inside grid our files arrays end up being deeply nested. Since
				// the fields access these arrays directly, we set the FILES array
				// to what is expected by the field for each iteration.
				$_FILES = array();

				if (isset($files_backup[$grid_field_name]))
				{
					foreach ($files_backup[$grid_field_name] as $files_key => $value)
					{
						if (isset($value['rows'][$row_id][$col_id]))
						{
							$_FILES[$col_id][$files_key] = $value['rows'][$row_id][$col_id];
						}
					}

					// Hack for File fields in Channel form to get uploading
					// working properly. This is the same hack done in
					// Channel_form_lib for regular File fields
					if (REQ !== 'CP' && $column['col_type'] === 'file')
					{
						$img = ee()->file_field->validate($_FILES[$col_id]['name'], $col_id);
						$row[$col_id] = isset($img['value']) ?  $img['value'] : '';
					}
				}

				// Call the fieldtype's validate/save method and capture the output
				$result = ee()->grid_parser->call($method, $row[$col_id]);

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

					// If column is required and the value from validation is empty,
					// throw an error, except if the value is 0 because that can be
					// a legitimate data entry
					if ($column['col_required'] == 'y' && empty($value) && $value !== 0 && $value !== '0')
					{
						$error = lang('grid_field_required');
					}

					// Is this AJAX validation? If so, just return the result for the field
					// we're validating
					if (ee()->input->is_ajax_request() && $field = ee()->input->post('ee_fv_field'))
					{
						if ($field == 'field_id_'.$this->field_id.'[rows]['.$row_id.']['.$col_id.']')
						{
							return $error;
						}
					}

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

		// reset $_FILES in case it's used in other code
		$_FILES = $files_backup;

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

		$fieldtypes = array();
		$compatibility = array();

		foreach (ee('Addon')->installed() as $addon)
		{
			if ($addon->hasFieldtype())
			{
				foreach ($addon->get('fieldtypes', array()) as $fieldtype => $metadata)
				{
					if (isset($metadata['compatibility']))
					{
						$compatibility[$fieldtype] = $metadata['compatibility'];
					}
				}

				$fieldtypes = array_merge($fieldtypes, $addon->getFieldtypeNames());
			}
		}

		unset($fieldtypes['grid'], $compatibility['grid']);

		ee()->load->library('api');
		ee()->legacy_api->instantiate('channel_fields');

		// Shorten some line lengths
		$ft_api = ee()->api_channel_fields;

		foreach ($fieldtypes as $field_short_name => $field_name)
		{
			$fieldtype = $ft_api->setup_handler($field_short_name, TRUE);

			// Check to see if the fieldtype accepts Grid as a content type;
			// also, temporarily exlcude Relationships for content types
			// other than channel
			if ( ! $fieldtype->accepts_content_type('grid') ||
				($this->content_type != 'channel' && $field_short_name == 'relationship'))
			{
				unset($fieldtypes[$field_short_name], $compatibility[$field_short_name]);
			}
		}

		asort($fieldtypes);

		$this->_fieldtypes = array(
			'fieldtypes' => $fieldtypes,
			'compatibility' => $compatibility
		);

		return $this->_fieldtypes;
	}

	// ------------------------------------------------------------------------

	/**
	 * Validates settings before form is saved
	 *
	 * @param	array	POSTed column settings from field settings page
	 * @return	mixed	Array of errors or TRUE for successful validation
	 */
	public function validate_settings($settings)
	{
		$errors = array();
		$col_labels = array();
		$col_names = array();

		// Create an array of column names and labels for counting to see if
		//  there are duplicates; they should be unique
		foreach ($settings['grid']['cols'] as $col_field => $column)
		{
			$col_labels[] = $column['col_label'];
			$col_names[] = $column['col_name'];
		}

		$col_label_count = array_count_values($col_labels);
		$col_name_count = array_count_values($col_names);

		ee()->load->library('grid_parser');

		foreach ($settings['grid']['cols'] as $col_field => $column)
		{
			// Column labels are required
			if (empty($column['col_label']))
			{
				$errors[$col_field]['col_label'] = 'grid_col_label_required';
			}
			// There cannot be duplicate column labels
			elseif ($col_label_count[$column['col_label']] > 1)
			{
				$errors[$col_field]['col_label'] = 'grid_duplicate_col_label';
			}

			// Column names are required
			if (empty($column['col_name']))
			{
				$errors[$col_field]['col_name'] = 'grid_col_name_required';
			}
			// Columns cannot be the same name as our protected modifiers
			elseif (in_array($column['col_name'], ee()->grid_parser->reserved_names))
			{
				$errors[$col_field]['col_name'] = 'grid_col_name_reserved';
			}
			// There cannot be duplicate column names
			elseif ($col_name_count[$column['col_name']] > 1)
			{
				$errors[$col_field]['col_name'] = 'grid_duplicate_col_name';
			}

			// Column names must contain only alpha-numeric characters and no spaces
			if (preg_match('/[^a-z0-9\-\_]/i', $column['col_name']))
			{
				$errors[$col_field]['col_name'] = 'grid_invalid_column_name';
			}

			// Column widths, if specified, must be numeric
			if ( ! empty($column['col_width']) &&
				 ! is_numeric(str_replace('%', '', $column['col_width'])))
			{
				$errors[$col_field]['col_width'] = 'grid_numeric_percentage';
			}

			$column['col_id'] = (strpos($col_field, 'new_') === FALSE)
				? str_replace('col_id_', '', $col_field) : FALSE;
			$column['col_required'] = isset($column['col_required']) ? 'y' : 'n';
			$column['col_settings']['field_required'] = $column['col_required'];

			ee()->grid_parser->instantiate_fieldtype($column, NULL, $this->field_id, 0, $this->content_type);

			// Let fieldtypes validate their Grid column settings
			$ft_validate = ee()->grid_parser->call('validate_settings', $column['col_settings']);

			if ($ft_validate instanceof Result && $ft_validate->isNotValid())
			{
				foreach ($ft_validate->getAllErrors() as $field => $field_errors)
				{
					foreach ($field_errors as $rule => $error)
					{
						$errors[$col_field][$field.'_'.$rule] = $error;
					}
				}
			}
		}

		return (empty($errors)) ? TRUE : $errors;
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
		$new_field = ee()->grid_model->create_field($settings['field_id'], $this->content_type);

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
				'content_type'		=> $this->content_type,
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

			$col_ids[] = ee()->grid_model->save_col_settings($column_data, $column['col_id'], $this->content_type);

			$count++;
		}

		// Delete columns that were not including in new field settings
		if ( ! $new_field)
		{
			$columns = ee()->grid_model->get_columns_for_field($settings['field_id'], $this->content_type, FALSE);

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
				ee()->grid_model->delete_columns(
					$cols_to_delete,
					$old_cols,
					$settings['field_id'],
					$this->content_type
				);
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
		if ( ! isset($column['col_settings']))
		{
			$column['col_settings'] = array();
		}

		ee()->grid_parser->instantiate_fieldtype($column, NULL, $this->field_id, 0, $this->content_type);

		if ( ! ($settings = ee()->grid_parser->call('save_settings', $column['col_settings'])))
		{
			return $column['col_settings'];
		}

		return $settings;
	}

	// ------------------------------------------------------------------------

	/**
	 * Returns rendered HTML for a column on the field settings page
	 *
	 * @param	array	Array of single column settings from the grid_columns table
	 * @param	array	Array of string names representing the namespace field names
	 *	that have validation errors
	 * @return	string	Rendered column view for settings page
	 */
	public function get_column_view($column = NULL, $error_fields = array())
	{
		$fieldtype_data = $this->get_grid_fieldtypes();
		$fieldtypes = $fieldtype_data['fieldtypes'];
		$compatibility = $fieldtype_data['compatibility'];

		// Create a dropdown-frieldly array of available fieldtypes based on
		// compatibility if this column already has a type.
		if (isset($column['col_type']))
		{
			$type = $column['col_type'];

			if ( ! isset($compatibility[$type]))
			{
				$fieldtypes_dropdown = array($type => $fieldtypes[$type]);
			}
			else
			{
				$my_type = $compatibility[$type];

				$compatible = array_filter($compatibility, function($v) use($my_type)
				{
					return $v == $my_type;
				});

				$fieldtypes_dropdown = array_intersect_key($fieldtypes, $compatible);
			}
		}
		else
		{
			$fieldtypes_dropdown = $fieldtypes;
		}

		// Column ID could be a string if we're coming back from a valdiation error
		// in order to preserve original namespacing
		if ( ! empty($column['col_id']) && is_string($column['col_id']))
		{
			$field_name = $column['col_id'];
		}
		else
		{
			$field_name = (empty($column)) ? 'new_0' : 'col_id_'.$column['col_id'];
		}

		$column['settings_form'] = (empty($column))
			? $this->get_settings_form('text') : $this->get_settings_form($column['col_type'], $column);

		if (isset($column['col_width']) && $column['col_width'] == 0)
		{
			$column['col_width'] = '';
		}

		return ee('View')->make('grid:col_tmpl')
			->render(
			array(
				'field_name'	=> $field_name,
				'column'		=> $column,
				'fieldtypes'	=> $fieldtypes_dropdown,
				'error_fields'  => $error_fields
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
		$ft_api = ee()->api_channel_fields;
		$settings = NULL;

		// Returns blank settings form for a specific fieldtype
		if (empty($column))
		{
			$column = array(
				'col_id' => NULL,
				'col_type' => $type,
				'col_label' => '',
				'col_required' => 'n',
				'col_name' => 'n',
				'col_settings' => array()
			);
		}

		ee()->grid_parser->instantiate_fieldtype($column, NULL, $this->field_id, 0, $this->content_type);

		$settings = ee()->grid_parser->call(
			'display_settings',
			(isset($column['col_settings'])) ? $column['col_settings'] : array()
		);

		return $this->_view_for_col_settings($type, $settings, $column['col_id']);
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
		// shared form does a set_value() by default, but since we're dealing with
		// un-namespaced fields here and namespaced fields in POST that can lead
		// to extremely unexpected behavior. So we'll kill $_POST and thus rely on
		// the value in col_settings.
		$post = $_POST;
		$_POST = array();

		$settings_view = ee('View')
			->make('grid:col_settings_tmpl')
			->render(
			array(
				'col_type'		=> $col_type,
				'col_settings'	=> (empty($col_settings)) ? array() : $col_settings
			)
		);

		if ( ! is_string($col_id))
		{
			$col_id = (empty($col_id)) ? 'new_0' : 'col_id_'.$col_id;
		}

		$_POST = $post;

		// Namespace form field names
		return $this->_namespace_inputs(
			$settings_view,
			'$1name="grid[cols]['.$col_id.'][col_settings][$2]$3"'
		);
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

// EOF
