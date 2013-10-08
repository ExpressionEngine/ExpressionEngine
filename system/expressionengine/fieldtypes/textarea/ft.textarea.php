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
 * ExpressionEngine Textarea Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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
		return form_textarea(array(
			'name'	=> $this->field_name,
			'id'	=> $this->field_name,
			'value'	=> $data,
			'rows'	=> $this->settings['field_ta_rows'],
			'dir'	=> $this->settings['field_text_direction']
		));
	}

	// --------------------------------------------------------------------

	function replace_tag($data, $params = '', $tagdata = '')
	{
		// Experimental parameter, do not use
		if (isset($params['raw_output']) && $params['raw_output'] == 'yes')
		{
			return ee()->functions->encode_ee_tags($data);
		}

		// Run markdown parsing before typography parsing
		if ($this->row('field_ft_'.$this->field_id) == 'markdown')
		{
			$data = ee()->typography->markdown($data, array('encode_ee_tags' => 'no'));
		}

		$field_fmt = ($this->content_type() == 'grid')
			? $this->settings['field_fmt'] : $this->row('field_ft_'.$this->field_id);

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

	/**
	 * Accept all content types.
	 *
	 * @param string  The name of the content type
	 * @param bool    Accepts all content types
	 */
	public function accepts_content_type($name)
	{
		return TRUE;
	}

	// --------------------------------------------------------------------

	function display_settings($data)
	{
		$prefix = 'textarea';

		$field_rows	= ($data['field_ta_rows'] == '') ? 6 : $data['field_ta_rows'];

		ee()->table->add_row(
			lang('textarea_rows', 'field_ta_rows'),
			form_input(array('id'=>'field_ta_rows','name'=>'field_ta_rows', 'size'=>4,'value'=>set_value('field_ta_rows', $field_rows)))
		);

		$this->field_formatting_row($data, $prefix);
		$this->text_direction_row($data, $prefix);
		$this->field_show_formatting_btns_row($data, $prefix);
		$this->field_show_smileys_row($data, $prefix);
		$this->field_show_glossary_row($data, $prefix);
		$this->field_show_spellcheck_row($data, $prefix);
		$this->field_show_writemode_row($data, $prefix);
		$this->field_show_file_selector_row($data, $prefix);
	}

	// --------------------------------------------------------------------

	public function grid_display_settings($data)
	{
		return array(
			$this->grid_field_formatting_row($data),
			$this->grid_text_direction_row($data),
			$this->grid_textarea_max_rows_row($data)
		);
	}
}

// END Textarea_ft class

/* End of file ft.textarea.php */
/* Location: ./system/expressionengine/fieldtypes/ft.textarea.php */