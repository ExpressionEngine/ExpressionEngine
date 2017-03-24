<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
 */
class Rte_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Rich Text Editor',
		'version'	=> '1.0.1'
	);

	var $has_array_data = FALSE;

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

	function validate($data)
	{
		ee()->load->library('rte_lib');

		if ($this->settings['field_required'] === 'y' && ee()->rte_lib->is_empty($data))
		{
			return lang('required');
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	function display_field($data)
	{
		ee()->load->library('rte_lib');

		return ee()->rte_lib->display_field($data, $this->field_name, $this->settings);
	}

	// --------------------------------------------------------------------

	function grid_display_field($data)
	{
		ee()->load->library('rte_lib');

		return ee()->rte_lib->display_field($data, $this->field_name, $this->settings, 'grid');
	}

	// --------------------------------------------------------------------

	function save($data)
	{
		ee()->load->library('rte_lib');

		return ee()->rte_lib->save_field($data);
	}

	// --------------------------------------------------------------------

	function replace_tag($data, $params = '', $tagdata = '')
	{
		// Experimental parameter, do not use
		if (isset($params['raw_output']) && $params['raw_output'] == 'yes')
		{
			return ee()->functions->encode_ee_tags($data);
		}

		ee()->load->library('typography');
		$str = ee()->typography->parse_type(
			ee()->functions->encode_ee_tags(
				ee()->typography->parse_file_paths($data)
			),
			array(
				'text_format'	=> 'xhtml',
				'html_format'	=> $this->row('channel_html_formatting', 'all'),
				'auto_links'	=> $this->row('channel_auto_link_urls', 'n'),
				'allow_img_url' => $this->row('channel_allow_img_urls', 'y')
			)
		);

		// remove non breaking spaces. typography likes to throw those
		// in when a list is indented.
		return str_replace('&nbsp;', ' ', $str);
	}

	// --------------------------------------------------------------------

	function display_settings($data)
	{
		$settings = array(
			array(
				'title' => 'textarea_height',
				'desc' => 'textarea_height_desc',
				'fields' => array(
					'field_ta_rows' => array(
						'type' => 'text',
						'value' => ( ! isset($data['field_ta_rows']) OR $data['field_ta_rows'] == '') ? 6 : $data['field_ta_rows']
					)
				)
			),
			array(
				'title' => 'field_text_direction',
				'fields' => array(
					'field_text_direction' => array(
						'type' => 'select',
						'choices' => array(
							'ltr' => lang('field_text_direction_ltr'),
							'rtl' => lang('field_text_direction_rtl')
						),
						'value' => isset($data['field_text_direction']) ? $data['field_text_direction'] : 'ltr',
					)
				)
			)
		);

		if ($this->content_type() == 'grid')
		{
			return array('field_options' => $settings);
		}

		return array('field_options_rte' => array(
			'label' => 'field_options',
			'group' => 'rte',
			'settings' => $settings
		));
	}

	// --------------------------------------------------------------------

	function save_settings($data)
	{
		return array(
			'field_show_fmt' => 'n',
			'field_ta_rows' => isset($data['field_ta_rows']) ? $data['field_ta_rows'] : 6
		);
	}

	// --------------------------------------------------------------------

	function grid_save_settings($data)
	{
		return $data;
	}

	// --------------------------------------------------------------------

	/**
	 * Update the fieldtype
	 *
	 * @param string $version The version being updated to
	 * @return boolean TRUE if successful, FALSE otherwise
	 */
	public function update($version)
	{
		return TRUE;
	}
}

// END Rte_ft class

// EOF
