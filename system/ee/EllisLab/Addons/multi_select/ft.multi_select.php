<?php

require_once SYSPATH.'ee/legacy/fieldtypes/OptionFieldtype.php';

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
 * ExpressionEngine Multi-Select Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Multi_select_ft extends OptionFieldtype {

	var $info = array(
		'name'		=> 'Multi Select',
		'version'	=> '1.0.0'
	);

	var $has_array_data = TRUE;

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

	// --------------------------------------------------------------------

	function validate($data)
	{
		$selected = decode_multi_field($data);
		$selected = empty($selected) ? array() : (array) $selected;

		// in case another field type was here
		$field_options = $this->_get_field_options($data);

		if ($selected)
		{
			if ( ! is_array($selected))
			{
				$selected = array($selected);
			}

			$unknown = array_diff($selected, array_keys($field_options));

			if (count($unknown) > 0)
			{
				return 'Invalid Selection';
			}
		}

		return TRUE;
	}


	function display_field($data)
	{
		ee()->load->helper('custom_field');

		$values = decode_multi_field($data);
		$field_options = $this->_get_field_options($data);

		$extra = ($this->get_setting('field_disabled')) ? 'disabled' : '';

		if (REQ == 'CP')
		{
			return ee('View')->make('multi_select:publish')->render(array(
				'field_name' => $this->field_name,
				'values'     => $values,
				'options'    => $field_options,
				'extra'      => $extra
			));
		}

		$extra .= ' dir="'.$this->get_setting('field_text_direction', 'ltr').'" class="multiselect_input"';

		return form_multiselect(
			$this->field_name.'[]',
			$field_options,
			$values,
			$extra
		);
	}

	// --------------------------------------------------------------------

	function grid_display_field($data)
	{
		return $this->display_field(form_prep($data));
	}

	// --------------------------------------------------------------------

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

	function display_settings($data)
	{
		$settings = $this->getSettingsForm(
			'multi_select',
			$data,
			'multiselect_options',
			lang('options_field_desc').lang('multiselect_options_desc')
		);

		return array('field_options_multi_select' => array(
			'label' => 'field_options',
			'group' => 'multi_select',
			'settings' => $settings
		));
	}

	function grid_display_settings($data)
	{
		return $this->getGridSettingsForm(
			'multi_select',
			$data,
			'multiselect_options',
			'grid_multiselect_options_desc'
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

	public function save($data)
	{
		if (is_array($data))
		{
			ee()->load->helper('custom_field');
			$data = encode_multi_field($data);
		}

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

// END Multi_select_ft class

// EOF
