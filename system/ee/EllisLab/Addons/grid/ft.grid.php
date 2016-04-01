<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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

// --------------------------------------------------------------------

/**
 * ExpressionEngine Grid Fieldtype
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Grid_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Grid',
		'version'	=> '1.0.0'
	);

	var $has_array_data = TRUE;

	private $error_fields = array();

	public function __construct()
	{
		parent::__construct();

		ee()->lang->loadfile('fieldtypes');
		ee()->load->model('grid_model');
	}

	// --------------------------------------------------------------------

	public function install()
	{
		ee()->grid_model->install();
	}

	// --------------------------------------------------------------------

	public function uninstall()
	{
		ee()->grid_model->uninstall();
	}

	// --------------------------------------------------------------------

	public function validate($data)
	{
		$this->_load_grid_lib();

		return ee()->grid_lib->validate($data);
	}

	// --------------------------------------------------------------------

	// Actual saving takes place in post_save so we have an entry_id
	public function save($data)
	{
		if (is_null($data))
		{
			$data = array();
		}

		ee()->session->set_cache(__CLASS__, $this->name(), $data);

		return ' ';
	}

	public function post_save($data)
	{
		// Prevent saving if save() was never called, happens in Channel Form
		// if the field is missing from the form
		if (($data = ee()->session->cache(__CLASS__, $this->name(), FALSE)) !== FALSE)
		{
			$this->_load_grid_lib();

			ee()->grid_lib->save($data);
		}
	}

	// --------------------------------------------------------------------

	// This fieldtype has been converted, so it accepts all content types
	public function accepts_content_type($name)
	{
		return ($name != 'grid');
	}


	// When a content type is removed, we need to clean up our data
	public function unregister_content_type($name)
	{
		ee()->grid_model->delete_content_of_type($name);
	}

	// --------------------------------------------------------------------

	/**
	 * Called when entries are deleted
	 *
	 * @param	array	Entry IDs to delete data for
	 */
	public function delete($entry_ids)
	{
		$entries = ee()->grid_model->get_entry_rows($entry_ids, $this->id(), $this->content_type());

		// Skip params in the loop
		unset($entries['params']);

		$row_ids = array();
		foreach ($entries as $rows)
		{
			// Continue if entry has no rows
			if (empty($rows))
			{
				continue;
			}

			foreach ($rows as $row)
			{
				$row_ids[] = $row['row_id'];
			}
		}

		$this->_load_grid_lib();

		ee()->grid_lib->delete_rows($row_ids);
	}

	// --------------------------------------------------------------------

	public function display_field($data)
	{
		$grid = ee('CP/GridInput', array(
			'field_name' 	=> $this->name(),
			'lang_cols' 	=> FALSE,
			'grid_min_rows' => $this->settings['grid_min_rows'],
			'grid_max_rows' => $this->settings['grid_max_rows']
		));
		$grid->loadAssets();
		$grid->setNoResultsText('no_rows_created', 'add_new_row');

		$this->_load_grid_lib();

	 	$field = ee()->grid_lib->display_field($grid, $data);

		if (REQ != 'CP')
		{
			// channel form is not guaranteed to have this wrapper class,
			// but the js requires it
			$field = '<div class="grid-publish">'.$field.'</div>';
		}

		return $field;
	}

	// --------------------------------------------------------------------

	/**
	 * Replace Grid template tags
	 */
	public function replace_tag($data, $params = '', $tagdata = '')
	{
		ee()->load->library('grid_parser');

		// not in a channel scope? pre-process may not have been run.
		if ($this->content_type() != 'channel')
		{
			ee()->load->library('api');
			ee()->legacy_api->instantiate('channel_fields');
			ee()->grid_parser->grid_field_names[$this->id()] = $this->name();
		}

		return ee()->grid_parser->parse($this->row, $this->id(), $params, $tagdata, $this->content_type());
	}

	// --------------------------------------------------------------------

	/**
	 * :total_rows modifier
	 */
	public function replace_total_rows($data, $params = '', $tagdata = '')
	{
		$entry_id = $this->row['entry_id'];

		ee()->load->model('grid_model');
		$entry_data = ee()->grid_model->get_entry_rows($entry_id, $this->id(), $this->content_type(), $params);

		if ($entry_data !== FALSE && isset($entry_data[$entry_id]))
		{
			return count($entry_data[$entry_id]);
		}

		return 0;
	}

	// --------------------------------------------------------------------

	/**
	 * :table modifier
	 */
	public function replace_table($data, $params = array(), $tagdata = '')
	{
		ee()->load->library('table');
		ee()->load->library('grid_parser');
		ee()->load->model('grid_model');
		ee()->load->helper('array_helper');

		$columns = ee()->grid_model->get_columns_for_field($this->id(), $this->content_type());
		$prefix = ee()->grid_parser->grid_field_names[$this->id()].':';

		// Parameters
		$set_classes = element('set_classes', $params, 'no');
		$set_widths = element('set_widths', $params, 'no');

		// Gather information we need from each column to build the table
		$column_headings = array();
		$column_cells = array();
		foreach ($columns as $column)
		{
			$column_heading = array('data' => $column['col_label']);
			$column_cell = array('data' => LD.$prefix.$column['col_name'].RD);

			// set_classes parameter; if yes, adds column name as a class
			// to heading cells and data cells
			if ($set_classes == 'yes' || $set_classes == 'y')
			{
				$column_heading['class'] = $column['col_name'];
				$column_cell['class'] = $column['col_name'];
			}

			// set_widths parameter; if yes, sets column widths to those
			// defined in the field's settings
			if (($set_widths == 'yes' || $set_widths == 'y') && $column['col_width'] != 0)
			{
				$column_heading['width'] = $column['col_width'].'%';
			}

			$column_headings[] = $column_heading;
			$column_cells[] = $column_cell;
		}

		// We need a marker to separate the table rows portion from the
		// rest of the table markup so that we only send the row template
		// to the Grid parser for looping; otherwise, the entire table
		// markup will loop
		$row_data_marker = '{!--GRIDTABLEROWS--}';

		$table_attributes = '';

		// Table element attributes that can be set via tag parameters
		foreach (array('border', 'cellspacing', 'cellpadding', 'class', 'id', 'width') as $attribute)
		{
			// Concatenate a string of them together for the table template
			if (isset($params[$attribute]))
			{
				$table_attributes .= ' '.$attribute.'="'.$params[$attribute].'"';
			}
		}

		ee()->table->set_template(array(
			'table_open'	=> '<table'.$table_attributes.'>',
			'tbody_open'	=> '<tbody>'.$row_data_marker,
			'tbody_close'	=> $row_data_marker.'</tbody>'
		));

		ee()->table->set_heading($column_headings);
		ee()->table->add_row($column_cells);

		$tagdata = ee()->table->generate();

		// Match the row data section only
		if (preg_match(
			'/'.preg_quote($row_data_marker).'(.*)'.preg_quote($row_data_marker).'/s',
			$tagdata,
			$match))
		{
			// Parse the loopable portion of the table
			$row_data = ee()->grid_parser->parse(
				$this->row,
				$this->id(),
				$params,
				$match[1],
				$this->content_type()
			);

			// Replace the marker section with the parsed data
			$tagdata = str_replace($match[0], $row_data, $tagdata);
		}

		return $tagdata;
	}

	// --------------------------------------------------------------------

	/**
	 * :sum modifier
	 */
	public function replace_sum($data, $params = array(), $tagdata = '')
	{
		return $this->_get_column_stats($params, 'sum');
	}

	// --------------------------------------------------------------------

	/**
	 * :average modifier
	 */
	public function replace_average($data, $params = array(), $tagdata = '')
	{
		return $this->_get_column_stats($params, 'average');
	}

	// --------------------------------------------------------------------

	/**
	 * :lowest modifier
	 */
	public function replace_lowest($data, $params = array(), $tagdata = '')
	{
		return $this->_get_column_stats($params, 'lowest');
	}

	// --------------------------------------------------------------------

	/**
	 * :highest modifier
	 */
	public function replace_highest($data, $params = array(), $tagdata = '')
	{
		return $this->_get_column_stats($params, 'highest');
	}

	// --------------------------------------------------------------------

	/**
	 * Used in the math modifiers to return stats about numeric columns
	 *
	 * @param	array	Tag parameters
	 * @param	string	Column metric to return
	 * @param	int		Return data for tag
	 */
	private function _get_column_stats($params, $metric)
	{
		$entry_id = $this->row['entry_id'];

		ee()->load->model('grid_model');
		$entry_data = ee()->grid_model->get_entry_rows($entry_id, $this->id(), $this->content_type(), $params);

		// Bail out if no entry data
		if ($entry_data === FALSE OR
			! isset($entry_data[$entry_id]) OR
			! isset($params['column']))
		{
			return '';
		}

		$columns = ee()->grid_model->get_columns_for_field($this->id(), $this->content_type());

		// Find the column that matches the passed column name
		foreach ($columns as $column)
		{
			if ($column['col_name'] == $params['column'])
			{
				break;
			}
		}

		// Gather the numbers needed to make the calculations
		$numbers = array();
		foreach ($entry_data[$entry_id] as $row)
		{
			if (is_numeric($row['col_id_'.$column['col_id']]))
			{
				$numbers[] = $row['col_id_'.$column['col_id']];
			}
		}

		if (empty($numbers))
		{
			return '';
		}

		// These are our supported operations
		switch ($metric)
		{
			case 'sum':
				return array_sum($numbers);
			case 'average':
				return array_sum($numbers) / count($numbers);
			case 'lowest':
				return min($numbers);
			case 'highest':
				return max($numbers);
			default:
				return '';
		}
	}

	// --------------------------------------------------------------------

	/**
	 * :next_row modifier
	 */
	public function replace_next_row($data, $params = '', $tagdata = '')
	{
		return $this->_parse_prev_next_row($params, $tagdata, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * :prev_row modifier
	 */
	public function replace_prev_row($data, $params = '', $tagdata = '')
	{
		return $this->_parse_prev_next_row($params, $tagdata);
	}

	// --------------------------------------------------------------------

	/**
	 * Handles parsing of :next_row and :prev_row modifiers
	 *
	 * @param	array	Tag parameters
	 * @param	string	Tag pair tag data
	 * @param	boolean	TRUE for next row, FALSE for previous row
	 * @param	string	Return data for tag
	 */
	private function _parse_prev_next_row($params, $tagdata, $next = FALSE)
	{
		if ( ! isset($params['row_id']))
		{
			return '';
		}

		$params['offset'] = ($next) ? 1 : -1;
		$params['limit'] = 1;

		ee()->load->library('grid_parser');

		return ee()->grid_parser->parse($this->row, $this->id(), $params, $tagdata, $this->content_type());
	}

	// --------------------------------------------------------------------

	public function display_settings($data)
	{
		$field_id = (int) $this->id();

		$this->_load_grid_lib();

		$vars = array();

		// Fresh settings forms ready to be used for added columns
		$vars['settings_forms'] = array();
		$fieldtypes = ee()->grid_lib->get_grid_fieldtypes();
		foreach (array_keys($fieldtypes['fieldtypes']) as $field_name)
		{
			$vars['settings_forms'][$field_name] = ee()->grid_lib->get_settings_form($field_name);
		}

		// Gather columns for current field
		$vars['columns'] = array();

		// Validation error, repopulate
		if (isset($_POST['grid']))
		{
			$columns = $_POST['grid']['cols'];

			foreach ($columns as $field_name => &$column)
			{
				$column['col_id'] = $field_name;
				$column['col_required'] = isset($column['col_required']) ? 'y' : 'n';
				$column['col_search'] = isset($column['col_search']) ? 'y' : 'n';

				$vars['columns'][] = ee()->grid_lib->get_column_view($column, $this->error_fields);
			}
		}
		elseif ( ! empty($field_id))
		{
			$columns = ee()->grid_model->get_columns_for_field($field_id, $this->content_type());

			foreach ($columns as $column)
			{
				$vars['columns'][] = ee()->grid_lib->get_column_view($column);
			}
		}

		// Will be our template for newly-created columns
		$vars['blank_col'] = ee()->grid_lib->get_column_view();

		if (empty($vars['columns']))
		{
			$vars['columns'][] = $vars['blank_col'];
		}

		$grid_alert = '';
		if ( ! empty($this->error_string))
		{
			$grid_alert = ee('CP/Alert')->makeInline('grid-error')
				->asIssue()
				->addToBody($this->error_string)
				->render();
		}

		// Create a template of the banner we generally use for alerts
		// so we can manipulate it for AJAX validation
		$alert_template = ee('CP/Alert')->makeInline('grid-error')
			->asIssue()
			->render();

		ee()->javascript->set_global('alert.grid_error', $alert_template);

		$settings = array(
			'field_options_grid' => array(
				'label' => 'field_options',
				'group' => 'grid',
				'settings' => array(
					array(
						'title' => 'grid_min_rows',
						'desc' => 'grid_min_rows_desc',
						'fields' => array(
							'grid_min_rows' => array(
								'type' => 'text',
								'value' => isset($data['grid_min_rows']) ? $data['grid_min_rows'] : 0
							)
						)
					),
					array(
						'title' => 'grid_max_rows',
						'desc' => 'grid_max_rows_desc',
						'fields' => array(
							'grid_max_rows' => array(
								'type' => 'text',
								'value' => isset($data['grid_max_rows']) ? $data['grid_max_rows'] : ''
							)
						)
					)
				)
			),
			'grid_fields' => array(
				'label' => 'grid_fields',
				'group' => 'grid',
				'settings' => array($grid_alert, ee()->load->view('settings', $vars, TRUE))
			)
		);

		// Settings to initialize JS with
		$field_settings = array();

		ee()->cp->add_js_script('plugin', 'ee_url_title');
		ee()->cp->add_js_script('ui', 'sortable');
		ee()->cp->add_js_script('file', 'cp/grid');

		ee()->javascript->output('EE.grid_settings();');

		return $settings;
	}

	// --------------------------------------------------------------------

	public function validate_settings($data)
	{
		$validator = ee('Validation')->make(array(
			'grid_min_rows' => 'isNatural',
			'grid_max_rows' => 'isNaturalNoZero',
			'grid' => 'validGridSettings'
		));

		$validator->defineRule('validGridSettings', array($this, '_validate_grid'));

		return $validator->validate($data);
	}

	// -------------------------------------------------------------------

	/**
	 * Callback for validation service
	 *
	 * @return	mixed	Boolean, whether or not the settings passed validation,
	 *   or string of errors
	 */
	public function _validate_grid($key, $value, $params, $rule)
	{
		$this->_load_grid_lib();

		$validate = ee()->grid_lib->validate_settings(array('grid' => ee()->input->post('grid')));

		$this->error_fields = array();

		$ajax_field = ee()->input->post('ee_fv_field');

		if ($validate !== TRUE)
		{
			$errors = array();

			// Gather error messages and fields with errors so that we can
			// display the error messages and highlight the fields that
			// have errors
			foreach ($validate as $column => $fields)
			{
				foreach ($fields as $field => $error)
				{
					$field_name = 'grid[cols]['.$column.']['.$field.']';

					if (AJAX_REQUEST && $ajax_field && $ajax_field == $field_name)
					{
						$rule->stop();
						return lang($error);
					}

					if (is_numeric($column))
					{
						$column = 'col_id_'.$column;
					}

					if ( ! isset($errors[$field]))
					{
						$errors[$field] = array(
							'message' => $error,
							'columns' => array('['.$column.']')
						);
					}
					else
					{
						$errors[$field]['columns'][] = '['.$column.']';
					}

					$this->error_fields[] = $field_name;
				}
			}

			// If we got here on AJAX validation, return that we passed, because the
			// single field we're validating must be valid
			if (AJAX_REQUEST && $ajax_field)
			{
				return TRUE;
			}

			// Make error messages unique and convert to a string to pass
			// to form validaiton library
			$this->error_string = '';
			foreach ($errors as $field => $error)
			{
				// Custom span for use with Grid settings validation callbacks
				$this->error_string .= '<span
					style="display: block"
					data-field="['.$field.']"
					data-columns="'.implode('', $error['columns']).'">'.lang($error['message']).'</span>';
			}

			$rule->stop();
			return $this->error_string;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	public function save_settings($data)
	{
		// Make sure grid_min_rows is at least zero
		return array(
			'grid_min_rows' => empty($data['grid_min_rows']) ? 0 : $data['grid_min_rows'],
			'grid_max_rows' => empty($data['grid_max_rows']) ? '' : $data['grid_max_rows']
		);
	}

	// --------------------------------------------------------------------

	public function post_save_settings($data)
	{
		if ( ! isset($_POST['grid']))
		{
			return;
		}

		// Need to get the field ID of the possibly newly-created field, so
		// we'll actually re-save the field settings in the Grid library
		$data['field_id'] = $this->id();
		$data['grid'] = ee()->input->post('grid');

		$this->_load_grid_lib();
		ee()->grid_lib->apply_settings($data);
	}

	// --------------------------------------------------------------------

	public function settings_modify_column($data)
	{
		if (isset($data['ee_action']) && $data['ee_action'] == 'delete')
		{
			$columns = ee()->grid_model->get_columns_for_field($data['field_id'], $this->content_type(), FALSE);

			$col_types = array();
			foreach ($columns as $column)
			{
				$col_types[$column['col_id']] = $column['col_type'];
			}

			// Give fieldtypes a chance to clean up when its parent Grid
			// field is deleted
			if ( ! empty($col_types))
			{
				ee()->grid_model->delete_columns(
					array_keys($col_types),
					$col_types,
					$data['field_id'],
					$this->content_type()
				);
			}

			ee()->grid_model->delete_field($data['field_id'], $this->content_type());
		}

		return array();
	}

	// --------------------------------------------------------------------

	/**
	 * Loads Grid library and assigns relevant field information to it
	 */
	private function _load_grid_lib()
	{
		ee()->load->library('grid_lib');

		// Attempt to get an entry ID first
		$entry_id = (isset($this->settings['entry_id']))
			? $this->settings['entry_id'] : ee()->input->get_post('entry_id');

		ee()->grid_lib->entry_id = ($this->content_id() == NULL) ? $entry_id : $this->content_id();
		ee()->grid_lib->field_id = $this->id();
		ee()->grid_lib->field_name = $this->name();
		ee()->grid_lib->content_type = $this->content_type();
	}

	// --------------------------------------------------------------------

	/**
	 * Update the fieldtype
	 *
	 * @param string $version The version being updated to
	 * @return boolean TRUE if successful, FALSE otherwise
	 */
	public function update($version)
	{
		return TRUE;
	}
}

// EOF
