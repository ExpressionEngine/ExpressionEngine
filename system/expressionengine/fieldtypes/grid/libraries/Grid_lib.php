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
	protected $_columns = array();
	protected $_validated = array();
	protected $_table = 'grid_columns';
	protected $_table_prefix = 'grid_field_';

	/**
	 * Performs fieldtype install
	 *
	 * @return	void
	 */
	public function install()
	{
		$columns = array(
			'col_id' => array(
				'type'				=> 'int',
				'constraint'		=> 10,
				'unsigned'			=> TRUE,
				'auto_increment'	=> TRUE
			),
			'field_id' => array(
				'type'				=> 'int',
				'constraint'		=> 10,
				'unsigned'			=> TRUE
			),
			'col_order' => array(
				'type'				=> 'int',
				'constraint'		=> 3,
				'unsigned'			=> TRUE
			),
			'col_type' => array(
				'type'				=> 'varchar',
				'constraint'		=> 50
			),
			'col_label' => array(
				'type'				=> 'varchar',
				'constraint'		=> 50
			),
			'col_name' => array(
				'type'				=> 'varchar',
				'constraint'		=> 32
			),
			'col_instructions' => array(
				'type'				=> 'text'
			),
			'col_required' => array(
				'type'				=> 'char',
				'constraint'		=> 1
			),
			'col_search' => array(
				'type'				=> 'char',
				'constraint'		=> 1
			),
			'col_settings' => array(
				'type'				=> 'text'
			)
		);

		ee()->load->dbforge();
		ee()->dbforge->add_field($columns);
		ee()->dbforge->add_key('col_id', TRUE);
		ee()->dbforge->create_table($this->_table);
	}

	// ------------------------------------------------------------------------

	/**
	 * Performs fieldtype uninstall
	 *
	 * @return	void
	 */
	public function uninstall()
	{
		// Get field IDs to drop corresponding field table
		$grid_fields = ee()->db->distinct('field_id')
			->get($this->_table)
			->result_array();

		// Drop grid_field_n tables
		foreach ($grid_fields as $row)
		{
			$this->delete_field($row['field_id']);
		}

		// Drop grid_columns table
		ee()->load->dbforge();
		ee()->dbforge->drop_table($this->_table);
	}

	// ------------------------------------------------------------------------

	/**
	 * Performs cleanup on our end if a Grid field is deleted from a channel:
	 * drops field's table, removes column settings from grid_columns table
	 *
	 * @param	int		Field ID of field to delete
	 * @return	void
	 */
	public function delete_field($field_id)
	{
		$table_name = $this->_table_prefix . $field_id;

		if (ee()->db->table_exists($table_name))
		{
			ee()->load->dbforge();
			ee()->dbforge->drop_table($table_name);
		}

		ee()->db->delete($this->_table, array('field_id' => $field_id));
	}

	// ------------------------------------------------------------------------
	
	/**
	 * Handles EE_Fieldtype's display_field for displaying the Grid field
	 *
	 * @param	int		Field ID of field to delete
	 * @return	void
	 */
	public function display_field($entry_id, $data, $settings)
	{
		$ft_api = ee()->api_channel_fields;

		$table_name = $this->_table_prefix . $settings['field_id'];

		// Get columns just for this field
		$vars['columns'] = $this->get_columns_for_field($settings['field_id']);

		if (is_array($data))
		{
			$rows = $this->_validated[$settings['field_id']]['value'];
		}
		else
		{
			// TODO: Move DB stuff like this into a model?
			$rows = ee()->db->where('entry_id', $entry_id)
				->order_by('row_order')
				->get($table_name)
				->result_array();
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
				$vars['rows'][$row['row_id']]['col_id_'.$column['col_id']] = $this->_publish_field_cell(
					$settings['field_name'],
					$column,
					$row
				);

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
		$ft_api = ee()->api_channel_fields;

		// Instantiate fieldtype
		$fieldtype = $ft_api->setup_handler($column['col_type'], TRUE);

		// Assign settings to fieldtype manually so they're available like
		// normal field settings
		$fieldtype->field_id = $column['col_id'];
		$fieldtype->field_name = 'col_id_'.$column['col_id'];
		$fieldtype->settings = $column['col_settings'];

		// Developers can optionally implement grid_display_field, otherwise
		// we will try to use display_field
		$method = $ft_api->check_method_exists('grid_display_field')
			? 'grid_display_field' : 'display_publish_field';

		// Call the fieldtype's field display method and capture the output
		$display_field = $ft_api->apply($method, array($row_data['col_id_'.$column['col_id']]));

		// How we'll namespace new and existing rows
		$row_id = ( ! isset($row_data['row_id'])) ? 'new_row_0' : 'row_id_'.$row_data['row_id'];

		// Return the publish field HTML with namespaced form field names
		return preg_replace(
			'/(<[input|select|textarea][^>]*)name=["\']([^"]*)["\']/',
			'$1name="'.$field_name.'[rows]['.$row_id.'][$2]"',
			$display_field
		);
	}

	// ------------------------------------------------------------------------

	public function validate($data, $field_id)
	{
		// Empty field
		if ( ! isset($data['rows']))
		{
			return TRUE;
		}

		$ft_api = ee()->api_channel_fields;

		$columns = $this->get_columns_for_field($field_id);

		$final_values = array();
		$errors = FALSE;

		foreach ($data['rows'] as $row_id => $row)
		{
			foreach ($columns as $column)
			{
				$col_id = 'col_id_'.$column['col_id'];

				if ( ! isset($row[$col_id]))
				{
					$row[$col_id] = NULL;
				}

				foreach ($row as $key => $value)
				{
					$_POST[$key] = $value;
				}

				// Instantiate fieldtype
				$fieldtype = $ft_api->setup_handler($column['col_type'], TRUE);

				// Assign settings to fieldtype manually so they're available like
				// normal field settings
				$fieldtype->field_id = $column['col_id'];
				$fieldtype->field_name = 'col_id_'.$column['col_id'];
				$fieldtype->settings = $column['col_settings'];

				// Developers can optionally implement grid_validate, otherwise we
				// will try to use validate
				$method = $ft_api->check_method_exists('grid_validate')
					? 'grid_validate' : 'validate';

				// Call the fieldtype's validate method and capture the output
				$validate = $ft_api->apply($method, array($row[$col_id]));

				$error = $validate;
				$value = $row[$col_id];

				if (is_array($validate))
				{
					extract($validate, EXTR_OVERWRITE);
				}

				$final_values[$row_id][$col_id] = $value;

				if (is_string($error) && ! empty($error))
				{
					$final_values[$row_id][$col_id] = $row[$col_id];
					$final_values[$row_id][$col_id.'_error'] = $error;
					$errors = lang('grid_validation_error');
				}

				foreach ($row as $key => $value)
				{
					unset($_POST[$key]);
				}
			}
		}

		$this->_validated[$field_id] = array('value' => $final_values, 'error' => $errors);

		return $this->_validated[$field_id];
	}

	// ------------------------------------------------------------------------

	public function save($data, $field_id)
	{
		$validated = (isset($this->_validated[$field_id]))
			? $this->_validated[$field_id] : $this->validate($data, $field_id);

		if (is_array($validated) && $validated['error'] === FALSE)
		{
			$col_type = 'yes';
		}

		return TRUE;
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
		$table_name = $this->_table_prefix . $settings['field_id'];
		$ft_api = ee()->api_channel_fields;

		// Our settings we need to pass along to the channel fields API when
		// working with managing the data columns for our fieldtypes
		$ft_api_settings = array(
			'id_field'				=> 'col_id',
			'type_field'			=> 'col_type',
			'col_settings_method'	=> 'grid_settings_modify_column',
			'col_prefix'			=> 'col',
			'fields_table'			=> $this->_table,
			'data_table'			=> $table_name,
		);

		$modify_field = ee()->db->table_exists($table_name);

		ee()->load->dbforge();

		// Create field table if it doesn't exist
		if ( ! $modify_field)
		{
			// Every field table needs these two rows, we'll start here and
			// add field columns as necessary
			$db_columns = array(
				'row_id' => array(
					'type'				=> 'int',
					'constraint'		=> 10,
					'unsigned'			=> TRUE,
					'auto_increment'	=> TRUE
				),
				'entry_id' => array(
					'type'				=> 'int',
					'constraint'		=> 10,
					'unsigned'			=> TRUE
				),
				'row_order' => array(
					'type'				=> 'int',
					'constraint'		=> 10,
					'unsigned'			=> TRUE
				)
			);

			ee()->dbforge->add_field($db_columns);
			ee()->dbforge->add_key('row_id', TRUE);
			ee()->dbforge->create_table($table_name);
		}

		// We'll use the order of the posted fields to determine the column order
		$count = 0;

		$col_ids = array();

		// Go through ALL posted columns for this field
		foreach ($settings['grid']['cols'] as $col_field => $column)
		{
			// Look for a column field name prefixed with "new_" to see if we're
			// creating a new column here or modifying an existing one
			$modify = (strpos($col_field, 'new_') === FALSE);

			// Handle checkboxes
			$column['required'] = isset($column['required']) ? 'y' : 'n';
			$column['searchable'] = isset($column['searchable']) ? 'y' : 'n';
			$column['settings'] = $this->_save_settings($column);
			$column['settings']['field_required'] = $column['required'];

			$column_data = array(
				'field_id'			=> $settings['field_id'],
				'col_order'			=> $count,
				'col_type'			=> $column['type'],
				'col_label'			=> $column['label'],
				'col_name'			=> $column['name'],
				'col_instructions'	=> $column['instr'],
				'col_required'		=> $column['required'],
				'col_search'		=> $column['searchable'],
				'col_settings'		=> json_encode($column['settings'])
			);

			$col_id = 0;

			// Update existing column with new settings
			if ($modify)
			{
				$col_id = str_replace('col_id_', '', $col_field);

				// Make any column modifications necessary
				$ft_api->edit_datatype(
					$col_id,
					$column['type'],
					$column['settings'],
					$ft_api_settings);

				ee()->db->where('col_id', $col_id);
				ee()->db->update($this->_table, $column_data);
			}
			// This is a new field, insert it into the columns table and get
			// the new column ID
			else
			{
				ee()->db->insert($this->_table, $column_data);
				$col_id = ee()->db->insert_id();

				// Add the fieldtype's columns to our data table
				$ft_api->setup_handler($column['type']);
				$ft_api->set_datatype(
					$col_id,
					$column['settings'],
					array(),
					TRUE,
					FALSE,
					$ft_api_settings);
			}

			$col_ids[] = $col_id;

			$count++;
		}

		// Delete columns that were not including in new field settings
		if ($modify_field)
		{
			// Get current columns to compare to the new list of columns
			$columns = ee()->db->select('col_id, col_type')
				->where('field_id', $settings['field_id'])
				->get($this->_table)
				->result_array();

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
				ee()->db->where_in('col_id', $cols_to_delete);
				ee()->db->delete($this->_table);

				foreach ($cols_to_delete as $col_id)
				{
					// Delete columns from data table
					$ft_api->setup_handler($old_cols[$col_id]);
					$ft_api->delete_datatype($col_id, array(), $ft_api_settings);
				}
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
		$ft_api = ee()->api_channel_fields;

		$ft_api->setup_handler($column['type']);

		if ($ft_api->check_method_exists('grid_save_settings'))
		{
			return $ft_api->apply('grid_save_settings', array($column['settings']));
		}

		return $column['settings'];
	}

	// ------------------------------------------------------------------------
	
	/**
	 * Gets array of all columns and settings for a given field ID
	 *
	 * @param	int		Field ID to get columns for
	 * @return	array	Settings from grid_columns table
	 */
	public function get_columns_for_field($field_id)
	{
		if (isset($this->_columns[$field_id]))
		{
			return $this->_columns[$field_id];
		}

		$columns = ee()->db->where('field_id', $field_id)
			->order_by('col_order')
			->get($this->_table)
			->result_array();

		$this->_columns[$field_id] = array();
		foreach ($columns as &$column)
		{
			$column['col_settings'] = json_decode($column['col_settings'], TRUE);
			$this->_columns[$field_id][$column['col_id']] = $column;
		}
		
		return $this->_columns[$field_id];
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
		$ft_api = ee()->api_channel_fields;

		$ft_api->setup_handler($type);

		// Returns blank settings form for a specific fieldtype
		if (empty($column))
		{
			$ft_api->setup_handler($type);

			return $this->_view_for_col_settings(
				$type,
				$ft_api->apply('grid_display_settings', array(array()))
			);
		}

		// Otherwise, return the prepopulated settings form based on column settings
		return $this->_view_for_col_settings(
			$type,
			$ft_api->apply('grid_display_settings', array($column['col_settings'])),
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
		return preg_replace(
			'/(<[input|select|textarea][^>]*)name=["\']([^"]*)["\']/',
			'$1name="grid[cols]['.$col_id.'][settings][$2]"',
			$settings_view
		);
	}
}

/* End of file Grid_lib.php */
/* Location: ./system/expressionengine/modules/grid/libraries/Grid_lib.php */