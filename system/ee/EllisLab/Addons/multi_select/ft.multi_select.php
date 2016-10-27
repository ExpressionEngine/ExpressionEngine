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
 * ExpressionEngine Multi-Select Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Multi_select_ft extends EE_Fieldtype {

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

	function _parse_single($data, $params)
	{
		if (isset($params['limit']))
		{
			$limit = intval($params['limit']);

			if (count($data) > $limit)
			{
				$data = array_slice($data, 0, $limit);
			}
		}

		if (isset($params['markup']) && ($params['markup'] == 'ol' OR $params['markup'] == 'ul'))
		{
			$entry = '<'.$params['markup'].'>';

			foreach($data as $dv)
			{
				$entry .= '<li>';
				$entry .= $dv;
				$entry .= '</li>';
			}

			$entry .= '</'.$params['markup'].'>';
		}
		else
		{
			$entry = implode(', ', $data);
		}

		// Experimental parameter, do not use
		if (isset($params['raw_output']) && $params['raw_output'] == 'yes')
		{
			return ee()->functions->encode_ee_tags($entry);
		}

		$text_format = ($this->content_type() == 'grid')
			? $this->settings['field_fmt'] : $this->row('field_ft_'.$this->field_id);

		return ee()->typography->parse_type(
				ee()->functions->encode_ee_tags($entry),
				array(
						'text_format'	=> $text_format,
						'html_format'	=> $this->row('channel_html_formatting', 'all'),
						'auto_links'	=> $this->row('channel_auto_link_urls', 'n'),
						'allow_img_url' => $this->row('channel_allow_img_urls', 'y')
					  )
		);
	}

	// --------------------------------------------------------------------

	function _parse_multi($data, $params, $tagdata)
	{
		$chunk = '';
		$raw_chunk = '';
		$limit = FALSE;

		// Limit Parameter
		if (is_array($params) AND isset($params['limit']))
		{
			$limit = $params['limit'];
		}

		$text_format = $this->row('field_ft_'.$this->field_id, 'none');

		foreach($data as $key => $item)
		{
			if ( ! $limit OR $key < $limit)
			{
				$vars['item'] = $item;
				$vars['count'] = $key + 1;	// {count} parameter

				$tmp = ee()->functions->prep_conditionals($tagdata, $vars);
				$raw_chunk .= ee()->functions->var_swap($tmp, $vars);

				$vars['item'] = ee()->typography->parse_type(
						$item,
						array(
								'text_format'	=> $text_format,
								'html_format'	=> $this->row('channel_html_formatting', 'all'),
								'auto_links'	=> $this->row('channel_auto_link_urls', 'n'),
								'allow_img_url' => $this->row('channel_allow_img_urls', 'y')
							  )
						);

				$chunk .= ee()->functions->var_swap($tmp, $vars);
			}
			else
			{
				break;
			}
		}

		// Everybody loves backspace
		if (isset($params['backspace']))
		{
			$chunk = substr($chunk, 0, - $params['backspace']);
			$raw_chunk = substr($raw_chunk, 0, - $params['backspace']);
		}

		// Experimental parameter, do not use
		if (isset($params['raw_output']) && $params['raw_output'] == 'yes')
		{
			return ee()->functions->encode_ee_tags($chunk);
		}

		return $chunk;
	}

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
				'title' => 'multiselect_options',
				'desc' => 'multiselect_options_desc',
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

		return array('field_options_multi_select' => array(
			'label' => 'field_options',
			'group' => 'multi_select',
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
					'title' => 'multiselect_options',
					'desc' => 'grid_multiselect_options_desc',
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
