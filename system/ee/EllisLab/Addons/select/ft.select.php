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
 * ExpressionEngine Select Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Select_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Select Dropdown',
		'version'	=> '1.0.0'
	);

	var $has_array_data = TRUE;


	function validate($data)
	{
		$valid			= FALSE;
		$field_options	= $this->_get_field_options($data, '--');

		if ($data == '')
		{
			return TRUE;
		}

		foreach($field_options as $key => $val)
		{
			if (is_array($val))
			{
				if (array_key_exists($data, $val))
				{
					$valid = TRUE;
					break;
				}
			}
			elseif ($key == $data)
			{
				$valid = TRUE;
				break;
			}
		}

		if ( ! $valid)
		{
			return ee()->lang->line('invalid_selection');
		}
	}

	// --------------------------------------------------------------------

	function display_field($data)
	{
		$extra = 'dir="'.$this->get_setting('field_text_direction', 'ltr').'"';

		if ($this->get_setting('field_disabled'))
		{
			$extra .= ' disabled';
		}

		$field = form_dropdown(
			$this->field_name,
			$this->_get_field_options($data, '--'),
			$data,
			$extra
		);

		return $field;
	}

	// --------------------------------------------------------------------

	function grid_display_field($data)
	{
		return $this->display_field($data);
	}

	// --------------------------------------------------------------------

	function replace_tag($data, $params = '', $tagdata = '')
	{
		// Experimental parameter, do not use
		if (isset($params['raw_output']) && $params['raw_output'] == 'yes')
		{
			return ee()->functions->encode_ee_tags($data);
		}

		$text_format = ($this->content_type() == 'grid')
			? $this->settings['field_fmt'] : $this->row('field_ft_'.$this->field_id);

		ee()->load->library('typography');

		return ee()->typography->parse_type(
			ee()->functions->encode_ee_tags($data),
			array(
				'text_format'	=> $text_format,
				'html_format'	=> $this->row('channel_html_formatting', 'all'),
				'auto_links'	=> $this->row('channel_auto_link_urls', 'n'),
				'allow_img_url' => $this->row('channel_allow_img_urls', 'y')
			)
		);
	}

	// --------------------------------------------------------------------

	function display_settings($data)
	{
		$format_options = ee()->addons_model->get_plugin_formatting(TRUE);

		$defaults = array(
			'field_fmt' => '',
			'field_pre_populate' => FALSE,
			'field_list_items' => '',
			'field_pre_channel_id' => 0,
			'field_pre_field_id' => 0
		);

		foreach ($defaults as $setting => $value)
		{
			$data[$setting] = isset($data[$setting]) ? $data[$setting] : $value;
		}

		$settings = array(
			array(
				'title' => 'field_fmt',
				'fields' => array(
					'field_fmt' => array(
						'type' => 'select',
						'choices' => $format_options,
						'value' => $data['field_fmt'],
						'note' => form_label(
							form_checkbox('update_formatting', 'y')
							.lang('update_existing_fields')
						)
					)
				)
			),
			array(
				'title' => 'select_options',
				'desc' => 'select_options_desc',
				'fields' => array()
			)
		);

		// Only show the update existing fields note when editing.
		if ( ! $this->field_id)
		{
			unset($settings[0]['fields']['field_fmt']['note']);
		}

		if ($this->content_type() == 'channel')
		{
			$settings[1]['fields']['field_pre_populate_n'] = array(
				'type' => 'radio',
				'name' => 'field_pre_populate',
				'choices' => array(
					'n' => lang('field_populate_manually'),
				),
				'value' => ($data['field_pre_populate']) ? 'y' : 'n'
			);
		}

		$settings[1]['fields']['field_list_items'] = array(
			'type' => 'textarea',
			'value' => $data['field_list_items']
		);

		if ($this->content_type() == 'channel')
		{
			$settings[1]['fields']['field_pre_populate_y'] = array(
				'type' => 'radio',
				'name' => 'field_pre_populate',
				'choices' => array(
					'y' => lang('field_populate_from_channel'),
				),
				'value' => ($data['field_pre_populate']) ? 'y' : 'n'
			);

			$settings[1]['fields']['field_pre_populate_id'] = array(
				'type' => 'select',
				'choices' => $this->get_channel_field_list(),
				'value' => $data['field_pre_channel_id'] . '_' . $data['field_pre_field_id']
			);
		}

		return array('field_options_select' => array(
			'label' => 'field_options',
			'group' => 'select',
			'settings' => $settings
		));
	}

	function grid_display_settings($data)
	{
		$format_options = ee()->addons_model->get_plugin_formatting(TRUE);

		return array(
			'field_options' => array(
				array(
					'title' => 'field_fmt',
					'fields' => array(
						'field_fmt' => array(
							'type' => 'select',
							'choices' => $format_options,
							'value' => isset($data['field_fmt']) ? $data['field_fmt'] : 'none',
						)
					)
				),
				array(
					'title' => 'select_options',
					'desc' => 'grid_select_options_desc',
					'fields' => array(
						'field_list_items' => array(
							'type' => 'textarea',
							'value' => isset($data['field_list_items']) ? $data['field_list_items'] : ''
						)
					)
				)
			)
		);
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

// END Select_ft class

// EOF
