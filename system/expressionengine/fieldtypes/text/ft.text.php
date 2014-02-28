<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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
			ee()->load->model('field_model');
			$this->field_content_types = ee()->field_model->get_field_content_types();
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
				if ( ! ee()->form_validation->numeric($data))
				{
					return ee()->lang->line($content_type);
				}

				// Check if number exceeds mysql limits
				if ($data >= 999999.9999)
				{
					return ee()->lang->line('number_exceeds_limit');
				}

				return TRUE;
			}

			if ( ! ee()->form_validation->$content_type($data))
			{
				return ee()->lang->line($content_type);
			}

			// Check if number exceeds mysql limits
			if ($content_type == 'integer')
			{
				if (($data < -2147483648) OR ($data > 2147483647))
				{
					return ee()->lang->line('number_exceeds_limit');
				}
			}
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	function display_field($data)
	{
		$type = (isset($this->settings['field_content_type'])) ? $this->settings['field_content_type'] : 'all';

		$field = array(
			'name'		=> $this->field_name,
			'value'		=> $this->_format_number($data, $type),
			'dir'		=> $this->settings['field_text_direction'],
			'field_content_type' => $type
		);

		// maxlength attribute should only appear if its value is > 0
		if ($this->settings['field_maxl'])
		{
			$field['maxlength'] = $this->settings['field_maxl'];
		}

		return form_input($field);
	}

	// --------------------------------------------------------------------

	function replace_tag($data, $params = '', $tagdata = '')
	{
		// Experimental parameter, do not use
		if (isset($params['raw_output']) && $params['raw_output'] == 'yes')
		{
			return ee()->functions->encode_ee_tags($data);
		}

		$type		= isset($this->settings['field_content_type']) ? $this->settings['field_content_type'] : 'all';
		$decimals	= isset($params['decimal_place']) ? (int) $params['decimal_place'] : FALSE;

		$data = $this->_format_number($data, $type, $decimals);

		$field_fmt = ($this->content_type() == 'grid')
			? $this->settings['field_fmt'] : $this->row('field_ft_'.$this->field_id);

		ee()->load->library('typography');

		return ee()->typography->parse_type(
			ee()->functions->encode_ee_tags($data),
			array(
				'text_format'	=> $field_fmt,
				'html_format'	=> $this->row('channel_html_formatting', 'all'),
				'auto_links'	=> $this->row('channel_auto_link_urls', 'n'),
				'allow_img_url' => $this->row('channel_allow_img_urls', 'y')
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

		ee()->table->add_row(
			lang('field_max_length', $prefix.'field_max_length'),
			form_input(array('id'=>$prefix.'field_max_length','name'=>'field_maxl', 'size'=>4,'value'=>set_value('field_maxl', $field_maxl)))
		);

		$this->field_formatting_row($data, $prefix);
		$this->text_direction_row($data, $prefix);

		ee()->table->add_row(
			lang('field_content_text', $prefix.'field_content_type'),
			form_dropdown('text_field_content_type', $this->_get_content_options(), set_value('text_field_content_type', $data['field_content_type']), 'id="'.$prefix.'field_content_type"').$extra
		);

		$this->field_show_smileys_row($data, $prefix);
		$this->field_show_glossary_row($data, $prefix);
		$this->field_show_spellcheck_row($data, $prefix);
		$this->field_show_file_selector_row($data, $prefix);

		ee()->javascript->output('
		$("#text_field_content_type").change(function() {
			$(this).nextAll(".update_content_type").show();
		});
		');
	}

	// --------------------------------------------------------------------

	public function grid_display_settings($data)
	{
		return array(
			$this->grid_field_formatting_row($data),
			$this->grid_dropdown_row(
				lang('field_content_text'),
				'field_content_type',
				$this->_get_content_options(),
				isset($data['field_content_type']) ? $data['field_content_type'] : NULL
			),
			$this->grid_text_direction_row($data),
			$this->grid_max_length_row($data)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Returns allowed content types for the text fieldtype
	 *
	 * @return	array
	 */
	private function _get_content_options()
	{
		return array(
			'all'		=> lang('all'),
			'numeric'	=> lang('type_numeric'),
			'integer'	=> lang('type_integer'),
			'decimal'	=> lang('type_decimal')
		);
	}

	// --------------------------------------------------------------------

	function save_settings($data)
	{
		return array(
			'field_maxl'			=> ee()->input->post('field_maxl'),
			'field_content_type'	=> ee()->input->post('text_field_content_type')
		);
	}

	// --------------------------------------------------------------------

	function grid_save_settings($data)
	{
		return $data;
	}

	// --------------------------------------------------------------------

	function settings_modify_column($data)
	{

		$settings = unserialize(base64_decode($data['field_settings']));

		return $this->_get_column_settings($settings['field_content_type'], $data['field_id']);
	}

	// --------------------------------------------------------------------

	public function grid_settings_modify_column($data)
	{
		$settings = $data;

		if (isset($settings['col_settings']) && ! is_array($settings['col_settings']))
		{
			$settings = json_decode($settings['col_settings'], TRUE);
		}

		return $this->_get_column_settings(
			isset($settings['field_content_type']) ? $settings['field_content_type'] : '',
			$data['col_id'],
			TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Accept all content types.
	 *
	 * @param string  The name of the content type
	 * @return bool   Accepts all content types
	 */
	public function accepts_content_type($name)
	{
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Returns database column setting for a particular text field configuration
	 *
	 * @param	string	Type of data to be stored in this text field
	 * @param	int		Field/column ID to map settings to
	 * @param	bool	Whether or not we're preparing these settings for
	 * 					a Grid field
	 * @return	array	Database column settings for this text field
	 */
	private function _get_column_settings($data_type, $field_id, $grid = FALSE)
	{
		$field_name = ($grid) ? 'col_id_'.$field_id : 'field_id_'.$field_id;

		switch($data_type)
		{
			case 'numeric':
				$fields[$field_name] = array(
					'type'		=> 'FLOAT',
					'default'	=> 0
				);
				break;
			case 'integer':
				$fields[$field_name] = array(
					'type'		=> 'INT',
					'default'	=> 0
				);
				break;
			case 'decimal':
				$fields[$field_name] = array(
					'type'		=> 'DECIMAL(10,4)',
					'default'	=> 0
				);
				break;
			default:
				$fields[$field_name] = array(
					'type'		=> 'text',
					'null'		=> TRUE
				);
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