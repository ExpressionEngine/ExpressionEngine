<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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

// --------------------------------------------------------------------

/**
 * ExpressionEngine Grid Fieldtype
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Grid_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Grid',
		'version'	=> '1.0'
	);

	var $has_array_data = TRUE;

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
	// TODO: what to do about autosave and revisions?
	public function save($data)
	{
		ee()->session->set_cache(__CLASS__, $this->field_name, $data);

		return NULL;
	}

	public function post_save($data)
	{
		$this->_load_grid_lib();

		ee()->grid_lib->save(ee()->session->cache(__CLASS__, $this->field_name));
	}

	// --------------------------------------------------------------------

	/**
	 * Called when entries are deleted
	 *
	 * @param	array	Entry IDs to delete data for
	 */
	public function delete($entry_ids)
	{
		$entries = ee()->grid_model->get_entry_rows($entry_ids, $this->field_id);

		$row_ids = array();
		foreach ($entries as $rows)
		{
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
		if ( ! ee()->session->cache(__CLASS__, 'grid_assets_loaded'))
		{
			ee()->cp->add_to_head(ee()->view->head_link('css/grid.css'));

			ee()->cp->add_js_script('file', 'cp/sort_helper');
			ee()->cp->add_js_script('file', 'cp/grid');
			
			ee()->session->set_cache(__CLASS__, 'grid_assets_loaded', TRUE);
		}

		$settings = array(
			'grid_min_rows' => $this->settings['grid_min_rows'],
			'grid_max_rows' => $this->settings['grid_max_rows']
		);

		ee()->javascript->output('EE.grid("#'.$this->field_name.'", '.json_encode($settings).');');

		$this->_load_grid_lib();

		return ee()->grid_lib->display_field($data);
	}

	// --------------------------------------------------------------------

	/**
	 * Replace Grid template tags
	 */
	public function replace_tag($data, $params = '', $tagdata = '')
	{
		ee()->load->library('grid_parser');

		return ee()->grid_parser->parse($this->row, $this->field_id, $params, $tagdata);
	}

	// --------------------------------------------------------------------

	/**
	 * :total_rows modifier
	 */
	public function replace_total_rows($data, $params = '', $tagdata = '')
	{
		$entry_id = $this->row['entry_id'];

		ee()->load->model('grid_model');
		$entry_data = ee()->grid_model->get_entry_rows($entry_id, $this->field_id, $params);

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

		$columns = ee()->grid_model->get_columns_for_field($this->field_id);
		$prefix = ee()->grid_parser->grid_field_names[$this->field_id].':';

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
				$this->field_id,
				$params,
				$match[1]
			);

			// Replace the marker section with the parsed data
			$tagdata = str_replace($match[0], $row_data, $tagdata);
		}

		return $tagdata;
	}

	// --------------------------------------------------------------------

	/**
	 * :next_row modifier
	 */
	public function replace_next_row($data, $params = '', $tagdata = '')
	{
		return $this->_parse_prev_next_row($this->row, $this->field_id, $params, $tagdata, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * :prev_row modifier
	 */
	public function replace_prev_row($data, $params = '', $tagdata = '')
	{
		return $this->_parse_prev_next_row($this->row, $this->field_id, $params, $tagdata);
	}

	// --------------------------------------------------------------------
	
	private function _parse_prev_next_row($row, $field_id, $params, $tagdata, $next = FALSE)
	{
		if ( ! isset($params['row_id']))
		{
			return '';
		}

		$params['offset'] = ($next) ? 1 : -1;
		$params['limit'] = 1;

		ee()->load->library('grid_parser');

		return ee()->grid_parser->parse($row, $field_id, $params, $tagdata);
	}
	
	// --------------------------------------------------------------------
	
	public function display_settings($data)
	{
		$field_id = isset($data['field_id']) ? $data['field_id'] : 0;

		ee()->table->set_heading(array(
			'data' => lang('grid_options'),
			'colspan' => 2
		));
		
		// Minimum rows field
		ee()->table->add_row(
			form_input(array(
				'name' => 'grid_min_rows',
				'id' => 'grid_min_rows',
				'value' => set_value('grid_min_rows', (isset($data['grid_min_rows'])) ? $data['grid_min_rows'] : 0),
				'class' => 'grid_input_text_small'
			)).
			'<div class="grid_input_label_group">'.
			form_label(lang('grid_min_rows'), 'grid_min_rows').
			'<br><i class="instruction_text">'.lang('grid_min_rows_desc').'</i></div>'.
			'<div class="grid_validation_error">'.form_error('grid_max_rows').'</div>'
		);

		// Maximum rows field
		ee()->table->add_row(
			form_input(array(
				'name' => 'grid_max_rows',
				'id' => 'grid_max_rows',
				'value' => set_value('grid_max_rows', (isset($data['grid_max_rows'])) ? $data['grid_max_rows'] : ''),
				'class' => 'grid_input_text_small'
			)).
			'<div class="grid_input_label_group">'.
			form_label(lang('grid_max_rows'), 'grid_max_rows').
			'<br><i class="instruction_text">'.lang('grid_max_rows_desc').'</i></div>'.
			'<div class="grid_validation_error">'.form_error('grid_max_rows').'</div>'
		);

		// Settings header
		$settings_html = form_label(lang('grid_config')).'<br>'.
			'<i class="instruction_text">'.lang('grid_config_desc').'</i>';

		// Settings to initialize JS with
		$settings = array();

		// If we're coming from a form validation error, load the previous
		// screen's HTML for the Grid field for easy repopulation
		if ($grid_html = ee()->input->post('grid_html'))
		{
			$settings_html .= form_error('grid_validation');
			$settings_html .= $grid_html;

			// Array of field names that had validation errors, we'll highlight them
			if ($error_fields = ee()->session->cache(__CLASS__, 'grid_settings_field_errors'))
			{
				$settings['error_fields'] = $error_fields;
			}
		}
		// Otherwise load settings from the database
		else
		{
			$this->_load_grid_lib();

			$vars = array();

			// Fresh settings forms ready to be used for added columns
			$vars['settings_forms'] = array();
			foreach (ee()->grid_lib->get_grid_fieldtypes() as $field_name => $data)
			{
				$vars['settings_forms'][$field_name] = ee()->grid_lib->get_settings_form($field_name);
			}

			// Gather columns for current field
			$vars['columns'] = array();
			
			if ( ! empty($field_id))
			{
				$columns = ee()->grid_model->get_columns_for_field($field_id);

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

			$settings_html .= ee()->load->view('settings', $vars, TRUE);
		}

		// The big column configuration row, generated from the settings view
		ee()->table->add_row($settings_html);

		ee()->cp->add_to_head(ee()->view->head_link('css/grid.css'));

		ee()->cp->add_js_script('file', 'cp/sort_helper');
		ee()->cp->add_js_script('file', 'cp/grid');

		ee()->javascript->output('EE.grid_settings('.json_encode($settings).');');
		
		return ee()->table->generate();
	}

	// --------------------------------------------------------------------

	public function validate_settings($data)
	{
		ee()->form_validation->set_rules(
			array(
				array(
					'field' => 'grid_min_rows',
					'label' => 'lang:grid_max_rows',
					'rules' => 'trim|numeric'
				),
				array(
					'field' => 'grid_max_rows',
					'label' => 'lang:grid_max_rows',
					'rules' => 'trim|numeric'
				),
				array(
					// Validate against dummpy field so that the field data
					// isn't sent to Form Validation, otherwise it will cause
					// a loop because of the nested array
					'field' => 'grid_validation',
					'label' => 'Grid',
					'rules' => 'callback__validate_grid'
				),
			)
		);
	}

	// -------------------------------------------------------------------

	/**
	 * Callback for Form Validation
	 *
	 * @param	array	Empty array because we sent a fake field to Form
	 *                  Validation
	 * @return	boolean	Wheather or not the settings passed validation
	 */
	public function _validate_grid($data)
	{
		$this->_load_grid_lib();

		$validate = ee()->grid_lib->validate_settings(array('grid' => ee()->input->post('grid')));

		if ($validate !== TRUE)
		{
			$errors = array();
			$field_names = array();

			// Gather error messages and fields with errors so that we can
			// display the error messages and highlight the fields that
			// have errors
			foreach ($validate as $column => $fields)
			{
				foreach ($fields as $field => $error)
				{
					$errors[] = $error;
					$field_names[] = 'grid[cols]['.$column.']['.$field.']';
				}
			}

			// Make error messages unique and convert to a string to pass
			// to form validaiton library
			$errors = array_unique($errors);
			$error_string = '';
			foreach ($errors as $error)
			{
				$error_string .= lang($error).'<br>';
			}

			ee()->form_validation->set_message('_validate_grid', $error_string);

			// We'll get this later in display_settings()
			ee()->session->set_cache(__CLASS__, 'grid_settings_field_errors', $field_names);

			return FALSE;
		}
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------

	public function save_settings($data)
	{
		// Make sure grid_min_rows is at least zero
		if (empty($data['grid_min_rows']))
		{
			$data['grid_min_rows'] = 0;
		}
		
		return $data;
	}

	// --------------------------------------------------------------------

	public function post_save_settings($data)
	{
		// Need to get the field ID of the possibly newly-created field, so
		// we'll actually re-save the field settings in the Grid library
		$data['field_id'] = $this->settings['field_id'];
		$data['grid'] = ee()->input->post('grid');

		$this->_load_grid_lib();
		ee()->grid_lib->apply_settings($data);
	}

	// --------------------------------------------------------------------

	public function settings_modify_column($data)
	{
		if (isset($data['ee_action']) && $data['ee_action'] == 'delete')
		{
			ee()->grid_model->delete_field($data['field_id']);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Loads Grid library and assigns relevant field information to it
	 */
	private function _load_grid_lib()
	{
		ee()->load->library('grid_lib');

		ee()->grid_lib->entry_id = (isset($this->settings['entry_id']))
			? $this->settings['entry_id'] : ee()->input->get_post('entry_id');
		ee()->grid_lib->field_id = $this->field_id;
		ee()->grid_lib->field_name = $this->field_name;
	}
}

/* End of file ft.grid.php */
/* Location: ./system/expressionengine/modules/ft.grid.php */