<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
	public function save($data)
	{
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
		if ( ! ee()->session->cache(__CLASS__, 'grid_assets_loaded'))
		{
			if (REQ == 'CP')
			{
				$css_link = ee()->view->head_link('css/grid.css');
			}
			// Channel Form
			else
			{
				$css_link = '<link rel="stylesheet" href="'.ee()->config->slash_item('theme_folder_url').'cp_themes/default/css/grid.css" type="text/css" media="screen" />'.PHP_EOL;
			}

			ee()->cp->add_to_head($css_link);

			ee()->cp->add_js_script('ui', 'sortable');
			ee()->cp->add_js_script('file', 'cp/sort_helper');
			ee()->cp->add_js_script('file', 'cp/grid');

			ee()->session->set_cache(__CLASS__, 'grid_assets_loaded', TRUE);
		}

		$settings = array(
			'grid_min_rows' => $this->settings['grid_min_rows'],
			'grid_max_rows' => $this->settings['grid_max_rows']
		);

		if (REQ == 'CP')
		{
			// Set settings as a global for easy reinstantiation of field
			// by third parties
			ee()->javascript->set_global('grid_field_settings.'.$this->name(), $settings);

			// getElementById instead of $('#...') for field names that have
			// brackets in them
			ee()->javascript->output('EE.grid(document.getElementById("'.$this->name().'"));');
		}
		// Channel Form
		else
		{
			ee()->javascript->output('EE.grid(document.getElementById("'.$this->name().'"), '.json_encode($settings).');');
		}

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

		// not in a channel scope? pre-process may not have been run.
		if ($this->content_type() != 'channel')
		{
			ee()->load->library('api');
			ee()->api->instantiate('channel_fields');
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

			$settings_html .= ee()->load->view('settings', $vars, TRUE);
		}

		// The big column configuration row, generated from the settings view
		ee()->table->add_row($settings_html);

		ee()->cp->add_to_head(ee()->view->head_link('css/grid.css'));

		ee()->cp->add_js_script('plugin', 'ee_url_title');
		ee()->cp->add_js_script('ui', 'sortable');
		ee()->cp->add_js_script('file', 'cp/sort_helper');
		ee()->cp->add_js_script('file', 'cp/grid');

		ee()->javascript->output('EE.grid_settings('.json_encode($settings).');');

		return ee()->table->generate();
	}

	// --------------------------------------------------------------------

	public function validate_settings($data)
	{
		$this->_load_grid_lib();

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
		return array(
			'grid_min_rows' => empty($data['grid_min_rows']) ? 0 : $data['grid_min_rows'],
			'grid_max_rows' => empty($data['grid_max_rows']) ? '' : $data['grid_max_rows']
		);
	}

	// --------------------------------------------------------------------

	public function post_save_settings($data)
	{
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
		ee()->grid_lib->field_id = $this->id();
		ee()->grid_lib->field_name = $this->name();
		ee()->grid_lib->content_type = $this->content_type();
	}
}

/* End of file ft.grid.php */
/* Location: ./system/expressionengine/modules/ft.grid.php */