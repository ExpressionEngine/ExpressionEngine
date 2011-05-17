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
 * ExpressionEngine Text Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Text_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Text Input',
		'version'	=> '1.0'
	);

	// Parser Flag (preparse pairs?)
	var $has_array_data = FALSE;

	
	// --------------------------------------------------------------------
	
	function validate($data)
	{
		if ($data == '')
		{
			return TRUE;
		}
		
		if ( ! isset($this->field_content_types))
		{
			$this->EE->load->model('field_model');
			$this->field_content_types = $this->EE->field_model->get_field_content_types();
		}

		if ( ! isset($this->settings['field_content_type']))
		{
			return TRUE;
		}

		$content_type = $this->settings['field_content_type'];
		
		if (in_array($content_type, $this->field_content_types['text']) && $content_type != 'any')
		{
			
			if ($content_type == 'decimal')
			{
				if ( ! $this->EE->form_validation->numeric($data))
				{
					return $this->EE->lang->line($content_type);
				}
				
				// Check if number exceeds mysql limits
				if ($data >= 999999.9999)
				{
					return $this->EE->lang->line('number_exceeds_limit');
				}
				
				return TRUE;
			}

			if ( ! $this->EE->form_validation->$content_type($data))
			{
				return $this->EE->lang->line($content_type);
			}
			
			// Check if number exceeds mysql limits			
			if ($content_type == 'integer')
			{
				if (($data < -2147483648) OR ($data > 2147483647))
				{
					return $this->EE->lang->line('number_exceeds_limit');
				}
			}
		}
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------
	
	function display_field($data)
	{
		$type = (isset($this->settings['field_content_type'])) ? $this->settings['field_content_type'] : 'all';
		
		$data = $this->_format_number($data, $type);
		
		return form_input(array(
			'name'		=> $this->field_name,
			'id'		=> $this->field_name,
			'value'		=> $data,
			'dir'		=> $this->settings['field_text_direction'],
			'maxlength'	=> $this->settings['field_maxl'], 
			'field_content_type' => $type
		));
	}
	
	// --------------------------------------------------------------------
	
	function replace_tag($data, $params = '', $tagdata = '')
	{
		$type		= isset($this->settings['field_content_type']) ? $this->settings['field_content_type'] : 'all';
		$decimals	= isset($params['decimal_place']) ? (int) $params['decimal_place'] : FALSE;
		
		$data = $this->_format_number($data, $type, $decimals);

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
		$prefix = 'text';
		$extra = '';
		
		if ($data['field_id'] != '')
		{
			$extra .= '<div class="notice update_content_type js_hide">';
			$extra .= '<p>'.sprintf(
								lang('content_type_changed'), 
								$data['field_content_type']).'</p></div>';
		}
		

		$field_maxl = ($data['field_maxl'] == '') ? 128 : $data['field_maxl'];
		
		$field_content_options = array('all' => lang('all'), 'numeric' => lang('type_numeric'), 'integer' => lang('type_integer'), 'decimal' => lang('type_decimal'));
		
		$this->EE->table->add_row(
			lang('field_max_length', 'field_max1'),
			form_input(array('id'=>'field_maxl','name'=>'field_maxl', 'size'=>4,'value'=>$field_maxl))
		);

		$this->field_formatting_row($data, $prefix);
		$this->text_direction_row($data, $prefix);

		$this->EE->table->add_row(
			lang('field_content_text', 'field_content_text'),
			form_dropdown('text_field_content_type', $field_content_options, $data['field_content_type'], 'id="text_field_content_type"').$extra
		);

		$this->field_show_smileys_row($data, $prefix);
		$this->field_show_glossary_row($data, $prefix);
		$this->field_show_spellcheck_row($data, $prefix);
		$this->field_show_file_selector_row($data, $prefix);
		
		$this->EE->javascript->output('
		$("#text_field_content_type").change(function() {
			$(this).nextAll(".update_content_type").show();
		});
		');		
		
		
	}
	
	// --------------------------------------------------------------------

	function save_settings($data)
	{		
		return array(
			'field_maxl'			=> $this->EE->input->post('field_maxl'),
			'field_content_type'	=> $this->EE->input->post('text_field_content_type')
		);
	}
	

	// --------------------------------------------------------------------
	
	function settings_modify_column($data)
	{

		$settings = unserialize(base64_decode($data['field_settings']));

		switch($settings['field_content_type'])
		{
			case 'numeric':
				$fields['field_id_'.$data['field_id']]['type'] = 'FLOAT';
				$fields['field_id_'.$data['field_id']]['default'] = 0;
				break;
			case 'integer':
				$fields['field_id_'.$data['field_id']]['type'] = 'INT';
				$fields['field_id_'.$data['field_id']]['default'] = 0;
				break;
			case 'decimal':
				$fields['field_id_'.$data['field_id']]['type'] = 'DECIMAL(10,4)';
				$fields['field_id_'.$data['field_id']]['default'] = 0;
				break;
			default:
				$fields['field_id_'.$data['field_id']]['type'] = 'text';
				$fields['field_id_'.$data['field_id']]['null'] = TRUE;
		}
		
		return $fields;
	}
	
	// --------------------------------------------------------------------
	
	function _format_number($data, $type = 'all', $decimals = FALSE)
	{
		switch($type)
		{
			case 'numeric':	$data = rtrim(rtrim(sprintf('%F', $data), '0'), '.'); // remove trailing zeros up to decimal point and kill decimal point if no trailing zeros
				break;
			case 'integer': $data = sprintf('%d', $data);
				break;
			case 'decimal':
				$parts = explode('.', sprintf('%F', $data));
				$parts[1] = isset($parts[1]) ? rtrim($parts[1], '0') : '';
				
				$decimals = ($decimals === FALSE) ? 2 : $decimals;
				$data = $parts[0].'.'.str_pad($parts[1], $decimals, '0');
				break;
			default:
				if ($decimals && ctype_digit(str_replace('.', '', $data))) {
					$data = number_format($data, $decimals);
				}
		}
		
		return $data;
	}
}

// END Text_Ft class

/* End of file ft.text.php */
/* Location: ./system/expressionengine/fieldtypes/ft.text.php */