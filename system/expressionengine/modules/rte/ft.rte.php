<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine Rich Text Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Rte_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Textarea (Rich Text)',
		'version'	=> '1.0'
	);
	
	var $has_array_data = FALSE;
	
	// --------------------------------------------------------------------

	function validate($data)
	{
		if ($this->settings['field_required'] === 'y' && $this->EE->rte_lib->is_empty($data))
		{
			return lang('required');
		}
		
		return TRUE;
	}

	// --------------------------------------------------------------------

	function display_field($data)
	{
		$this->EE->load->library('rte_lib');
		
		return $this->EE->rte_lib->display_field($data, $this->field_name, $this->settings);
	}

	// --------------------------------------------------------------------

	function save($data)
	{
		$this->EE->load->library('rte_lib');
		
		return $this->EE->rte_lib->save_field($data);
	}

	// --------------------------------------------------------------------

	function replace_tag($data, $params = '', $tagdata = '')
	{
		// Experimental parameter, do not use
		if (isset($params['raw_output']) && $params['raw_output'] == 'yes')
		{
			return $this->EE->functions->encode_ee_tags($data);
		}
		
		return $this->EE->typography->parse_type(
			$this->EE->functions->encode_ee_tags(
				$this->EE->typography->parse_file_paths($data)
			),
			array(
				'html_format'	=> $this->row['channel_html_formatting'],
				'auto_links'	=> $this->row['channel_auto_link_urls'],
				'allow_img_url' => $this->row['channel_allow_img_urls']
			)
		);
	}
	
	// --------------------------------------------------------------------
	
	function display_settings($data)
	{
		$prefix = 'rte';

		// Text direction
		$this->text_direction_row($data, $prefix);

		// Textarea rows
		$field_rows	= ($data['field_ta_rows'] == '') ? 10 : $data['field_ta_rows'];

		$this->EE->table->add_row(
			lang('textarea_rows', $prefix.'_ta_rows'),
			form_input(array(
				'id'	=> $prefix.'_ta_rows',
				'name'	=> $prefix.'_ta_rows',
				'size'	=> 4,
				'value'	=> $field_rows
				)
			)
		);
	}
	
	// --------------------------------------------------------------------

	function save_settings($data)
	{		
		$data['field_type'] = 'rte';
		$data['field_show_fmt'] = 'n';
		$data['field_ta_rows'] = $this->EE->input->post('rte_ta_rows');

		return $data;
	}	
}

// END Rte_ft class

/* End of file ft.rte.php */
/* Location: ./system/expressionengine/modules/ft.rte.php */