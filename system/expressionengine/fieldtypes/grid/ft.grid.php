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

	public function __construct()
	{
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
		$rows = ee()->grid_model->get_entry_rows($entry_ids, $this->field_id);

		$row_ids = array();
		foreach ($rows as $row)
		{
			$row_ids[] = $row['row_id'];
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

	public function replace_tag($data, $params = '', $tagdata = '')
	{
		
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
		
		// The big column configuration row, generated from the settings view
		ee()->table->add_row(
			ee()->load->view('settings', $vars, TRUE)
		);

		ee()->cp->add_to_head(ee()->view->head_link('css/grid.css'));

		ee()->cp->add_js_script('file', 'cp/sort_helper');
		ee()->cp->add_js_script('file', 'cp/grid_settings');

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
		// Need to get the field ID of the possibly newly-created field, so
		// we'll actually re-save the field settings in the Grid library
		$data['field_id'] = $this->settings['field_id'];
		$data['grid'] = ee()->input->post('grid');

		$this->_load_grid_lib();
		ee()->grid_lib->apply_settings($data);
	}

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