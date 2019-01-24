<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once SYSPATH.'ee/legacy/fieldtypes/OptionFieldtype.php';

/**
 * Radio Fieldtype
 */
class Radio_ft extends OptionFieldtype {

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
				if (isset($val['value']) && $data == $val['value'])
				{
					$valid = TRUE;
					break;
				}
				elseif (array_key_exists($data, $val))
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

		// We can't validate based on the fields original options if they've
		// changed via AJAX, so skip if filter_url is defined
		if ( ! $valid && ! $this->get_setting('filter_url', NULL))
		{
			return ee()->lang->line('invalid_selection');
		}
	}

	function display_field($data)
	{
		return $this->_display_field($data);
	}

	function grid_display_field($data)
	{
		return $this->_display_field($data, 'grid');
	}

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
		$extra = ($this->get_setting('field_disabled')) ? 'disabled' : '';

		// Is this new entry?  Set a default
		if ( ! $this->content_id AND is_null($data))
		{
			reset($field_options);
			$data = key($field_options);
		}

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

			return ee('View')->make('ee:_shared/form/fields/select')->render([
				'field_name' => $this->field_name,
				'choices'    => $field_options,
				'value'      => $data,
				'multi'      => FALSE,
				'disabled'   => $this->get_setting('field_disabled'),
				'filter_url' => $this->get_setting('filter_url', NULL),
				'no_results' => $this->get_setting('no_results', NULL),
			]);
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

	function display_settings($data)
	{
		$settings = $this->getSettingsForm(
			'radio',
			$data,
			'radio_options',
			lang('options_field_desc').lang('radio_options_desc')
		);

		return array('field_options_radio' => array(
			'label' => 'field_options',
			'group' => 'radio',
			'settings' => $settings
		));
	}

	public function grid_display_settings($data)
	{
		return $this->getGridSettingsForm(
			'radio',
			$data,
			'radio_options',
			'grid_radio_options_desc'
		);
	}

	/**
	 * :value modifier
	 */
	public function replace_value($data, $params = array(), $tagdata = FALSE)
	{
		return $this->replace_tag($data, $params, $tagdata);
	}

	/**
	 * :label modifier
	 */
	public function replace_label($data, $params = array(), $tagdata = FALSE)
	{
		$pairs = $this->get_setting('value_label_pairs');
		if (isset($pairs[$data]))
		{
			$data = $pairs[$data];
		}

		$data = $this->processTypograpghy($data);

		return $this->replace_tag($data, $params, $tagdata);
	}

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
