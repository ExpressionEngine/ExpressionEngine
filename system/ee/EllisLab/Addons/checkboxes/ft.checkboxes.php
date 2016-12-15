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
 * ExpressionEngine Option Group Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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

	// --------------------------------------------------------------------

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

			$unknown = array_diff($selected, array_keys($field_options));

			if (count($unknown) > 0)
			{
				return 'Invalid Selection';
			}
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

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
			return ee('View')->make('checkboxes:publish')->render(array(
				'field_name'          => $this->field_name,
				'values'              => $values,
				'options'             => $field_options,
				'editable'            => $this->get_setting('editable'),
				'editing'             => $this->get_setting('editing'),
				'disabled'            => ($this->get_setting('field_disabled')) ? 'disabled' : NULL,
				'deletable'           => $this->get_setting('deletable'),
				'group_id'            => $this->get_setting('group_id', 0),
				'manage_toggle_label' => $this->get_setting('manage_toggle_label', lang('manage')),
				'content_item_label'  => $this->get_setting('content_item_label', '')
			));
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

	// --------------------------------------------------------------------

	function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		ee()->load->helper('custom_field');
		$data = decode_multi_field($data);

		ee()->load->library('typography');

		if ($tagdata)
		{
			return $this->_parse_multi($data, $params, $tagdata);
		}
		else
		{
			return $this->_parse_single($data, $params);
		}
	}

	// --------------------------------------------------------------------

	function display_settings($data)
	{
		$settings = $this->getSettingsForm(
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
		$format_options = ee()->addons_model->get_plugin_formatting(TRUE);

		if ( ! isset($data['field_pre_populate']))
		{
			$data['field_pre_populate'] = 'v';
		}

		$grid = $this->getValueLabelMiniGrid($data);

		ee()->javascript->output("
			Grid.bind('checkboxes', 'displaySettings', function(column) {
				$('.keyvalue', column).miniGrid({grid_min_rows:0,grid_max_rows:''});
			});
		");

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
					'title' => 'checkbox_options',
					'desc' => 'grid_checkbox_options_desc',
					'fields' => array(
						'field_value_label_pairs' => array(
							'type' => 'radio',
							'name' => 'field_pre_populate',
							'choices' => array(
								'v' => lang('field_value_label_pairs'),
							),
							'value' => $data['field_pre_populate'] ?: 'v'
						),
						'value_label_pairs' => array(
							'type' =>'html',
							'content' => ee('View')->make('ee:_shared/form/mini_grid')
								->render($grid->viewData())
						),
						'field_pre_populate_n' => array(
							'type' => 'radio',
							'name' => 'field_pre_populate',
							'choices' => array(
								'n' => lang('field_populate_manually'),
							),
							'value' => $data['field_pre_populate'] ?: 'v'
						),
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

// END Checkboxes_ft class

// EOF
