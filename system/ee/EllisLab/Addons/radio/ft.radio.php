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
 * ExpressionEngine Radio Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Radio_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Radio Buttons',
		'version'	=> '1.0.0'
	);

	var $has_array_data = FALSE;

	// used in display_field() below to set
	// some defaults for third party usage
	var $settings_vars = array(
		'field_text_direction'	=> 'rtl',
		'field_pre_populate'	=> 'n',
		'field_list_items'		=> array(),
		'field_pre_field_id'	=> '',
		'field_pre_channel_id'	=> ''
	);

	function validate($data)
	{
		$valid			= FALSE;
		$field_options	= $this->_get_field_options($data);

		if ($data === FALSE OR $data == '')
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
			elseif ((string) $key === $data)
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
		return $this->_display_field($data);
	}

	// --------------------------------------------------------------------

	function grid_display_field($data)
	{
		return $this->_display_field($data, 'grid');
	}

	// --------------------------------------------------------------------

	/**
	 * Displays the field for the CP or Frontend, and accounts for grid
	 *
	 * @param string $data Stored data for the field
	 * @param string $container What type of container is this field in, 'fieldset' or 'grid'?
	 * @return string Field display
	 */
	private function _display_field($data, $container = 'fieldset')
	{
		$this->settings = array_merge($this->settings_vars, $this->settings);

		$text_direction = (isset($this->settings['field_text_direction']))
			? $this->settings['field_text_direction'] : 'ltr';

		$field_options = $this->_get_field_options($data);
		$extra         = ($this->get_setting('field_disabled')) ? 'disabled' : '';

		if (REQ == 'CP')
		{
			if ($data === TRUE)
			{
				$data = 'y';
			}
			elseif ($data === FALSE)
			{
				$data = 'n';
			}

			return ee('View')->make('radio:publish')->render(array(
				'field_name' => $this->field_name,
				'selected'   => $data,
				'options'    => $field_options,
				'extra'      => $extra
			));
		}

		$selected = $data;

		$r = '';
		$class = 'choice mr';

		foreach($field_options as $key => $value)
		{
			$selected = ($key == $data);

			$r .= '<label>'.form_radio($this->field_name, $value, $selected, $extra).NBS.$key.'</label>';
		}

		switch ($container)
		{
			case 'grid':
				$r = $this->grid_padding_container($r);
				break;

			default:
				$r = form_fieldset('').$r.form_fieldset_close();
				break;
		}

		return $r;
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
				'html_format'	=> $this->row['channel_html_formatting'],
				'auto_links'	=> $this->row['channel_auto_link_urls'],
				'allow_img_url' => $this->row['channel_allow_img_urls']
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
				'title' => 'radio_options',
				'desc' => 'radio_options_desc',
				'fields' => array(
					'field_pre_populate_n' => array(
						'type' => 'radio',
						'name' => 'field_pre_populate',
						'choices' => array(
							'n' => lang('field_populate_manually'),
						),
						'value' => ($data['field_pre_populate']) ? 'y' : 'n'
					),
					'field_list_items' => array(
						'type' => 'textarea',
						'value' => $data['field_list_items']
					),
					'field_pre_populate_y' => array(
						'type' => 'radio',
						'name' => 'field_pre_populate',
						'choices' => array(
							'y' => lang('field_populate_from_channel'),
						),
						'value' => ($data['field_pre_populate']) ? 'y' : 'n'
					),
					'field_pre_populate_id' => array(
						'type' => 'select',
						'choices' => $this->get_channel_field_list(),
						'value' => $data['field_pre_channel_id'] . '_' . $data['field_pre_field_id']
					)
				)
			)
		);

		// Only show the update existing fields note when editing.
		if ( ! $this->field_id)
		{
			unset($settings[0]['fields']['field_fmt']['note']);
		}

		return array('field_options_radio' => array(
			'label' => 'field_options',
			'group' => 'radio',
			'settings' => $settings
		));
	}

	public function grid_display_settings($data)
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
					'title' => 'radio_options',
					'desc' => 'grid_radio_options_desc',
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

// END Radio_ft class

// EOF
