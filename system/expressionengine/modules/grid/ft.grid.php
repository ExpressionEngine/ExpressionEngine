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
		$this->EE->lang->loadfile('fieldtypes');

		$this->EE->table->set_heading(array(
			'data' => lang('grid_options'),
			'colspan' => 2
		));
		
		// Minimum rows field
		$this->EE->table->add_row(
			form_input(array(
				'name' => 'grid_min_rows',
				'id' => 'grid_min_rows',
				'value' => '0'
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
				'value' => ''
			)).
			'<div class="grid_input_label_group">'.
			form_label(lang('grid_max_rows'), 'grid_max_rows').
			'<br><i class="instruction_text">'.lang('grid_max_rows_desc').'</i></div>'
		);

		$this->EE->table->add_row(
			form_label(lang('grid_config')).
			'<br><i class="instruction_text">'.lang('grid_config_desc').'</i>'.
			$this->EE->load->view('settings', NULL, TRUE)
		);

		$this->EE->cp->add_to_head($this->EE->view->head_link('css/grid.css'));
		
		return $this->EE->table->generate();
	}
	
	// --------------------------------------------------------------------

	function save_settings($data)
	{		
		return $data;
	}	
}

/* End of file ft.grid.php */
/* Location: ./system/expressionengine/modules/ft.grid.php */