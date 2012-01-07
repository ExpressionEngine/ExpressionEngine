<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine Textarea Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Textarea_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Textarea',
		'version'	=> '1.0'
	);
	
	var $has_array_data = FALSE;

	// --------------------------------------------------------------------

	function validate($data)
	{
		return TRUE;
	}

	// --------------------------------------------------------------------

	function display_field($data)
	{		
		$field = array(
			'name'	=> $this->field_name,
			'id'	=> $this->field_name,
			'value'	=> $data,
			'rows'	=> $this->settings['field_ta_rows'],
			'dir'	=> $this->settings['field_text_direction']
		);
		
		// RTE?
		if ( ! isset( $this->settings['field_enable_rte'] ) )
		{
			$this->settings['field_enable_rte'] = 'n';
		}
		if ( $this->settings['field_enable_rte'] == 'y' )
		{
			$field['class']	= 'rte';
		}
				
		return form_textarea($field);
	}

	// --------------------------------------------------------------------

	function replace_tag($data, $params = '', $tagdata = '')
	{
		return $this->EE->typography->parse_type(
			$this->EE->functions->encode_ee_tags($data),
			array(
				'text_format'	=> $this->row['field_ft_'.$this->field_id],
				'html_format'	=> $this->row['channel_html_formatting'],
				'auto_links'	=> $this->row['channel_auto_link_urls'],
				'allow_img_url' => $this->row['channel_allow_img_urls']
			)
		);
	}
	
	// --------------------------------------------------------------------
	
	function display_settings($data)
	{
		$prefix = 'textarea';
		
		// RTE setup
		$this->EE->load->model('addons_model');
		if ( ! isset( $data['field_enable_rte'] ) )
		{
			$data['field_enable_rte'] = 'n';
		}
		
		$field_rows	= ($data['field_ta_rows'] == '') ? 6 : $data['field_ta_rows'];
		
		$this->EE->table->add_row(
			lang('textarea_rows', 'field_ta_rows'),
			form_input(array('id'=>'field_ta_rows','name'=>'field_ta_rows', 'size'=>4,'value'=>$field_rows))
		);
		
		$this->field_formatting_row($data, $prefix);
		if ( $this->EE->addons_model->module_installed('rte') )
		{
			$this->EE->lang->loadfile('rte');
			$this->_yes_no_row($data, 'enable_rte_for_field', 'field_enable_rte', $prefix);
		}
		$this->text_direction_row($data, $prefix);
		$this->field_show_formatting_btns_row($data, $prefix);
		$this->field_show_smileys_row($data, $prefix);
		$this->field_show_glossary_row($data, $prefix);
		$this->field_show_spellcheck_row($data, $prefix);
		$this->field_show_writemode_row($data, $prefix);
		$this->field_show_file_selector_row($data, $prefix);
	}
	
	// --------------------------------------------------------------------

	function save_settings($data)
	{		
		return array(
			'field_enable_rte'	=> $this->EE->input->post('textarea_field_enable_rte')
		);
	}	
}

// END Textarea_ft class

/* End of file ft.textarea.php */
/* Location: ./system/expressionengine/fieldtypes/ft.textarea.php */