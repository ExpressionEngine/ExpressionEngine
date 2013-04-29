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

	public function install()
	{
		ee()->load->model('grid_model');
		ee()->grid_model->install();
	}

	// --------------------------------------------------------------------
	
	public function uninstall()
	{
		ee()->load->model('grid_model');
		ee()->grid_model->uninstall();
	}
	
	// --------------------------------------------------------------------

	public function validate($data)
	{
		ee()->lang->loadfile('fieldtypes');
		ee()->load->library('grid_lib');

		return ee()->grid_lib->validate(ee()->input->post($this->field_name), $this->field_id);
	}

	// --------------------------------------------------------------------

	public function save($data)
	{
		ee()->load->library('grid_lib');
		ee()->grid_lib->save(ee()->input->post($this->field_name), $this->field_id);

		// TODO: Return string of searchable columns?

		return NULL;
	}

	// --------------------------------------------------------------------

	public function display_field($data)
	{
		ee()->load->library('grid_lib');
		ee()->lang->loadfile('fieldtypes');

		ee()->cp->add_to_head(ee()->view->head_link('css/grid.css'));

		ee()->cp->add_to_foot(ee()->view->script_tag('cp/sort_helper.js'));
		ee()->cp->add_to_foot(ee()->view->script_tag('cp/grid.js'));

		$settings = array(
			'grid_min_rows' => $this->settings['grid_min_rows'],
			'grid_max_rows' => $this->settings['grid_max_rows']
		);

		ee()->javascript->output('EE.grid("#'.$this->field_name.'", '.json_encode($settings).');');

		return ee()->grid_lib->display_field(
			$this->EE->input->get('entry_id'),
			$data,
			$this->settings
		);
	}

	// --------------------------------------------------------------------

	public function replace_tag($data, $params = '', $tagdata = '')
	{
		
	}
	
	// --------------------------------------------------------------------
	
	public function display_settings($data)
	{
		$field_id = isset($data['field_id']) ? $data['field_id'] : 0;

		ee()->lang->loadfile('fieldtypes');

		ee()->table->set_heading(array(
			'data' => lang('grid_options'),
			'colspan' => 2
		));
		
		// Minimum rows field
		ee()->table->add_row(
			form_input(array(
				'name' => 'grid_min_rows',
				'id' => 'grid_min_rows',
				'value' => (isset($data['grid_min_rows'])) ? $data['grid_min_rows'] : 0,
				'class' => 'grid_input_text_small'
			)).
			'<div class="grid_input_label_group">'.
			form_label(lang('grid_min_rows'), 'grid_min_rows').
			'<br><i class="instruction_text">'.lang('grid_min_rows_desc').'</i></div>'
		);

		// Maximum rows field
		ee()->table->add_row(
			form_input(array(
				'name' => 'grid_max_rows',
				'id' => 'grid_max_rows',
				'value' => (isset($data['grid_max_rows'])) ? $data['grid_max_rows'] : '',
				'class' => 'grid_input_text_small'
			)).
			'<div class="grid_input_label_group">'.
			form_label(lang('grid_max_rows'), 'grid_max_rows').
			'<br><i class="instruction_text">'.lang('grid_max_rows_desc').'</i></div>'
		);

		ee()->load->library('grid_lib');
		ee()->load->model('grid_model');

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
		
		// The big column configuration row, generated from the settings view
		ee()->table->add_row(
			ee()->load->view('settings', $vars, TRUE)
		);

		ee()->cp->add_to_head(ee()->view->head_link('css/grid.css'));

		ee()->cp->add_to_foot(ee()->view->script_tag('cp/sort_helper.js'));
		ee()->cp->add_to_foot(ee()->view->script_tag('cp/grid_settings.js'));
		ee()->javascript->output('EE.grid_settings();');
		
		return ee()->table->generate();
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

	public function post_save_settings($data)
	{
		ee()->load->library('grid_lib');
		
		// Need to get the field ID of the possibly newly-created field, so
		// we'll actually re-save the field settings in the Grid library
		$data['field_id'] = $this->settings['field_id'];
		$data['grid'] = ee()->input->post('grid');

		ee()->grid_lib->apply_settings($data);
	}

	public function settings_modify_column($data)
	{
		if (isset($data['ee_action']) && $data['ee_action'] == 'delete')
		{
			ee()->load->model('grid_model');
			ee()->grid_model->delete_field($data['field_id']);
		}
	}
}

/* End of file ft.grid.php */
/* Location: ./system/expressionengine/modules/ft.grid.php */