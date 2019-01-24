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
 * Option Group Fieldtype
 */
class Checkboxes_ft extends OptionFieldtype {

	var $info = array(
		'name'		=> 'Checkboxes',
		'version'	=> '1.0.0'
	);

	var $has_array_data = TRUE;

	// used in display_field() below to set
	// some defaults for third party usage
	var $settings_vars = array(
		'field_text_direction'	=> 'ltr',
		'field_pre_populate'	=> 'n',
		'field_list_items'		=> array(),
		'field_pre_field_id'	=> '',
		'field_pre_channel_id'	=> ''
	);

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		parent::__construct();
		ee()->load->helper('custom_field');
	}

	function validate($data)
	{
		$selected = decode_multi_field($data);
		$selected = empty($selected) ? array() : (array) $selected;

		// in case another field type was here
		$field_options = $this->_get_field_options($data);
		$field_options = $this->_flatten($field_options);

		if ($selected)
		{
			if ( ! is_array($selected))
			{
				$selected = array($selected);
			}

			$unknown = array_filter(array_diff($selected, array_keys($field_options)));

			if (count($unknown) > 0)
			{
				return 'Invalid Selection';
			}
		}

		return TRUE;
	}

	protected function _flatten($options)
	{
		$out = array();

		foreach ($options as $key => $item)
		{
			if (is_array($item))
			{
				$out[$key] = $item['name'];

				foreach ($this->_flatten($item['children']) as $k => $v)
				{
					$out[$k] = $v;
				}
			}
			else
			{
				$out[$key] = $item;
			}
		}

		return $out;
	}

	function display_field($data)
	{
		return $this->_display_field($data);
	}

	function grid_display_field($data)
	{
		return $this->_display_field(form_prep($data), 'grid');
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

		if (isset($this->settings['string_override']) && $this->settings['string_override'] != '')
		{
			return $this->settings['string_override'];
		}

		$values = decode_multi_field($data);
		$field_options = $this->_get_field_options($data);

		if (REQ == 'CP')
		{
			return ee('View')->make('ee:_shared/form/fields/select')->render([
				'field_name'          => $this->field_name,
				'choices'             => $field_options,
				'value'               => $values,
				'multi'               => TRUE,
				'nested'              => TRUE,
				'nestable_reorder'    => TRUE,
				'manageable'          => $this->get_setting('editable', FALSE)
					&& ! $this->get_setting('in_modal_context'),
				'add_btn_label'       => $this->get_setting('add_btn_label', NULL),
				'editing'             => $this->get_setting('editing', FALSE),
				'manage_label'        => $this->get_setting('manage_toggle_label', lang('manage')),
				'reorder_ajax_url'    => $this->get_setting('reorder_ajax_url', NULL),
				'auto_select_parents' => $this->get_setting('auto_select_parents', FALSE),
				'no_results'          => $this->get_setting('no_results', ['text' => sprintf(lang('no_found'), lang('choices'))])
			]);
		}

		$r = '<div class="scroll-wrap pr">';

		$r .= $this->_display_nested_form($field_options, $values);

		$r .= '</div>';

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

	protected function _display_nested_form($fields, $values, $child = FALSE)
	{
		$out      = '';
		$disabled = ($this->get_setting('field_disabled')) ? 'disabled' : '';

		foreach ($fields as $id => $option)
		{
			$checked = (in_array(form_prep($option), $values)) ? TRUE : FALSE;

			if (is_array($option))
			{
				$out .= '<label>'.form_checkbox($this->field_name.'[]', $id, $checked, $disabled).NBS.$option['name'].'</label>';
				$out .= $this->_display_nested_form($option['children'], $values, TRUE);
			}
			else
			{
				$out .= '<label>'.form_checkbox($this->field_name.'[]', $id, $checked, $disabled).NBS.$option.'</label>';
			}
		}

		return $out;
	}

	function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		ee()->load->helper('custom_field');
		$data = decode_multi_field($data);

		if ($tagdata)
		{
			return $this->_parse_multi($data, $params, $tagdata);
		}
		else
		{
			return $this->_parse_single($data, $params);
		}
	}

	/**
	 * :length modifier
	 */
	public function replace_length($data, $params = array(), $tagdata = FALSE)
	{
		return count(decode_multi_field($data));
	}

	/**
	 * :attr_safe modifier
	 */
	public function replace_attr_safe($data, $params = array(), $tagdata = FALSE)
	{
		return parent::replace_attr_safe($this->replace_tag($data, $params, $tagdata), $params, $tagdata);
	}

	/**
	 * :limit modifier
	 */
	public function replace_limit($data, $params = array(), $tagdata = FALSE)
	{
		return parent::replace_limit($this->replace_tag($data, $params, $tagdata), $params, $tagdata);
	}

	/**
	 * :encrypt modifier
	 */
	public function replace_encrypt($data, $params = array(), $tagdata = FALSE)
	{
		return parent::replace_encrypt($this->replace_tag($data, $params, $tagdata), $params, $tagdata);
	}

	/**
	 * :url_slug modifier
	 */
	public function replace_url_slug($data, $params = array(), $tagdata = FALSE)
	{
		return parent::replace_url_slug($this->replace_tag($data, $params, $tagdata), $params, $tagdata);
	}

	/**
	 * :censor modifier
	 */
	public function replace_censor($data, $params = array(), $tagdata = FALSE)
	{
		return parent::replace_censor($this->replace_tag($data, $params, $tagdata), $params, $tagdata);
	}

	/**
	 * :json modifier
	 */
	public function replace_json($data, $params = array(), $tagdata = FALSE)
	{
		return parent::replace_json($this->replace_tag($data, $params, $tagdata), $params, $tagdata);
	}

	/**
	 * :replace modifier
	 */
	public function replace_replace($data, $params = array(), $tagdata = FALSE)
	{
		return parent::replace_replace($this->replace_tag($data, $params, $tagdata), $params, $tagdata);
	}

	/**
	 * :url_encode modifier
	 */
	public function replace_url_encode($data, $params = array(), $tagdata = FALSE)
	{
		return parent::replace_url_encode($this->replace_tag($data, $params, $tagdata), $params, $tagdata);
	}

	/**
	 * :url_decode modifier
	 */
	public function replace_url_decode($data, $params = array(), $tagdata = FALSE)
	{
		return parent::replace_url_decode($this->replace_tag($data, $params, $tagdata), $params, $tagdata);
	}

	function display_settings($data)
	{
		$settings = $this->getSettingsForm(
			'checkboxes',
			$data,
			'checkbox_options',
			lang('options_field_desc').lang('checkbox_options_desc')
		);

		return array('field_options_checkboxes' => array(
			'label' => 'field_options',
			'group' => 'checkboxes',
			'settings' => $settings
		));
	}

	public function grid_display_settings($data)
	{
		return $this->getGridSettingsForm(
			'checkboxes',
			$data,
			'checkbox_options',
			'grid_checkbox_options_desc'
		);
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

	public function save($data)
	{
		if (is_array($data))
		{
			ee()->load->helper('custom_field');
			$data = encode_multi_field($data);
		}

		return $data;
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

// END Checkboxes_ft class

// EOF
