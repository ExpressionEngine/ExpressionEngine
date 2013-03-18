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

		$this->EE->load->dbforge();
		$this->EE->dbforge->add_field($columns);
		$this->EE->dbforge->add_key('col_id', TRUE);
		$this->EE->dbforge->create_table('grid_columns');
	}

	// --------------------------------------------------------------------
	
	public function uninstall()
	{
		// TODO: delete stuff
	}
	
	// --------------------------------------------------------------------

	public function validate($data)
	{
		return TRUE;
	}

	// --------------------------------------------------------------------

	public function display_field($data)
	{
		return 'yeah!';
	}

	// --------------------------------------------------------------------

	public function save($data)
	{
		
	}

	// --------------------------------------------------------------------

	public function replace_tag($data, $params = '', $tagdata = '')
	{
		
	}
	
	// --------------------------------------------------------------------
	
	public function display_settings($data)
	{
		$this->EE->lang->loadfile('grid');

		$this->EE->table->set_heading(array(
			'data' => lang('grid_options'),
			'colspan' => 2
		));
		
		// Minimum rows field
		$this->EE->table->add_row(
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
		$this->EE->table->add_row(
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

		$this->EE->load->library('grid_lib');
		$fieldtypes = $this->EE->grid_lib->get_grid_fieldtypes();

		// Create a dropdown-frieldly array of available fieldtypes
		$fieldtypes_dropdown = array();
		foreach ($fieldtypes as $key => $value)
		{
			$fieldtypes_dropdown[$key] = $value['name'];
		}

		$vars = array();

		// Fresh settings forms ready to be used for added columns
		$vars['settings'] = $this->EE->grid_lib->get_grid_fieldtype_settings_forms();

		// Go through each setting change input names to allow building of
		// a POST array upon submission
		foreach ($vars['settings'] as $key => $value)
		{
			// Searches for: name="field_setting_name" (single or double quotes)
			// Replaces with: name="grid[cols][new][][options][field_setting_name]"
			$vars['settings'][$key] = preg_replace(
				'/(<[input|select][^>]*)name=["\']([^"]*)["\']/',
				'$1name="grid[cols][new][0][settings][$2]"',
				$value
			);
		}

		$vars['columns'] = array();

		if ( ! empty($data['field_id']))
		{
			$columns = $this->EE->grid_lib->get_columns_for_field($data['field_id'], TRUE);

			foreach ($columns as $column)
			{
				$vars['columns'][] = $this->EE->load->view(
					'single_col_tmpl',
					array(
						'field_name'	=> '[col_id_'.$column['col_id'].']',
						'column'		=> $column,
						'fieldtypes'	=> $fieldtypes_dropdown
					),
					TRUE
				);
			}
		}
		
		// The big column configuration row, generated from the settings view
		$this->EE->table->add_row(
			$this->EE->load->view('settings', $vars, TRUE)
		);

		$this->EE->cp->add_to_head($this->EE->view->head_link('css/grid.css'));
		
		return $this->EE->table->generate();
	}
	
	// --------------------------------------------------------------------

	public function save_settings($data)
	{
		return $data;
	}

	public function post_save_settings($data)
	{
		$this->EE->load->library('grid_lib');

		// Need to get the field ID of the possibly newly-created field, so
		// we'll actually re-save the field settings in the Grid library
		$data['field_id'] = key($this->EE->api_channel_fields->settings);
		$data['grid'] = $this->EE->input->post('grid');

		$this->EE->grid_lib->apply_settings($data);
	}
}

/* End of file ft.grid.php */
/* Location: ./system/expressionengine/modules/ft.grid.php */