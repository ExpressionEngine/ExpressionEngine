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
	
	// --------------------------------------------------------------------

	function validate($data)
	{
		return TRUE;
	}

	// --------------------------------------------------------------------

	function display_field($data)
	{
		return 'yeah!';
	}

	// --------------------------------------------------------------------

	function save($data)
	{
		
	}

	// --------------------------------------------------------------------

	function replace_tag($data, $params = '', $tagdata = '')
	{
		
	}
	
	// --------------------------------------------------------------------
	
	function display_settings($data)
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
				'value' => '0',
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
				'value' => '',
				'class' => 'grid_input_text_small'
			)).
			'<div class="grid_input_label_group">'.
			form_label(lang('grid_max_rows'), 'grid_max_rows').
			'<br><i class="instruction_text">'.lang('grid_max_rows_desc').'</i></div>'
		);

		$this->EE->load->library('grid_lib');
		$fieldtypes = $this->EE->grid_lib->get_grid_fieldtypes();

		// Create a dropdown-frieldly array of available fieldtypes
		$vars = array();
		foreach ($fieldtypes as $key => $value)
		{
			$vars['fieldtypes'][$key] = $value['name'];
		}

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
				'$1name="grid[cols][new][0][options][$2]"',
				$value
			);
		}
		
		// The big column configuration row, generated from the settings view
		$this->EE->table->add_row(
			$this->EE->load->view('settings', $vars, TRUE)
		);

		$this->EE->cp->add_to_head($this->EE->view->head_link('css/grid.css'));
		
		return $this->EE->table->generate();
	}
	
	// --------------------------------------------------------------------

	function save_settings($data)
	{
		$data['grid'] = $this->EE->input->post('grid');
		$data['field_fmt'] = 'none';
		
		return $data;
	}	
}

/* End of file ft.grid.php */
/* Location: ./system/expressionengine/modules/ft.grid.php */