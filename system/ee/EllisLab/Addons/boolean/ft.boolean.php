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
class Boolean_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Boolean (Yes/No)',
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
		if ($data === FALSE
			|| $data == ''
			|| $data == 'y'
			|| $data == 'n')
		{
			return TRUE;
		}

		return ee()->lang->line('invalid_selection');
	}

	// --------------------------------------------------------------------

	function display_field($data)
	{
		return $this->_display_field($data);
	}

	// --------------------------------------------------------------------

	function grid_display_field($data)
	{
		return $this->_display_field(form_prep($data), 'grid');
	}

	// --------------------------------------------------------------------

	private function _display_field($data, $container = 'fieldset')
	{
		array_merge($this->settings, $this->settings_vars);

		$text_direction = (isset($this->settings['field_text_direction']))
			? $this->settings['field_text_direction'] : 'ltr';

		$field_options = $this->settings['field_list_items'];
		$extra         = ($this->get_setting('field_disabled')) ? 'disabled' : '';

		if (REQ == 'CP')
		{
			$data = ($data == 'y') ? TRUE : FALSE;

			return ee('View')->make('boolean:publish')->render(array(
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

	function display_settings($data)
	{
		$defaults = array(
			'field_fmt' => '',
			'field_pre_populate' => FALSE,
			'field_list_items' => '',
			'field_pre_channel_id' => 0,
			'field_pre_field_id' => 0,
			'field_list_items' => 'yn',
		);

		foreach ($defaults as $setting => $value)
		{
			$data[$setting] = isset($data[$setting]) ? $data[$setting] : $value;
		}

		$settings = array(
			array(
				'title' => 'boolean_options',
				'desc' => 'boolean_options_desc',
				'fields' => array(
					'field_list_items' => array(
						'type' => 'radio',
						'choices' => array(
							'yn' => lang('yes_no'),
							'of' => lang('on_off')
						),
						'value' => $data['field_list_items']
					)
				)
			)
		);

		return array('field_options_boolean' => array(
			'label' => 'field_options',
			'group' => 'boolean',
			'settings' => $settings
		));
	}

	public function grid_display_settings($data)
	{
		$defaults = array(
			'field_list_items' => 'yn',
		);

		foreach ($defaults as $setting => $value)
		{
			$data[$setting] = isset($data[$setting]) ? $data[$setting] : $value;
		}

		return array(
			'field_options' => array(
				array(
					'title' => 'boolean_options',
					'desc' => 'boolean_options_desc',
					'fields' => array(
						'field_list_items' => array(
							'type' => 'radio',
							'choices' => array(
								'yn' => lang('yes_no'),
								'of' => lang('on_off')
							),
							'value' => $data['field_list_items']
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
}

// END Radio_ft class

// EOF
