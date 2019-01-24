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
 * Multi-Select Fieldtype
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

	function validate($data)
	{
		$selected = decode_multi_field($data);
		$selected = (empty($selected) || $selected == array('')) ? array() : (array) $selected;

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
			return ee('View')->make('ee:_shared/form/fields/select')->render([
				'field_name' => $this->field_name,
				'choices'    => $field_options,
				'value'      => $values,
				'multi'      => TRUE,
				'disabled'   => $this->get_setting('field_disabled')
			]);
		}

		$extra .= ' dir="'.$this->get_setting('field_text_direction', 'ltr').'" class="multiselect_input"';

		return form_multiselect(
			$this->field_name.'[]',
			$field_options,
			$values,
			$extra
		);
	}

	function grid_display_field($data)
	{
		return $this->display_field(form_prep($data));
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

// END Multi_select_ft class

// EOF
