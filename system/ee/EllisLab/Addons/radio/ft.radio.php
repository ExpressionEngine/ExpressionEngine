<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
 */
class Radio_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Radio Buttons',
		'version'	=> '1.0'
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

		$data = form_prep($data);

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
		return $this->_display_field(form_prep($data), 'grid');
	}

	// --------------------------------------------------------------------

	private function _display_field($data, $container = 'fieldset')
	{
		array_merge($this->settings, $this->settings_vars);

		$text_direction = (isset($this->settings['field_text_direction']))
			? $this->settings['field_text_direction'] : 'ltr';

		$field_options = $this->_get_field_options($data);

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
				'selected' => $data,
				'options' => $field_options
			));
		}

		$selected = $data;

		$r = '';
		$class = 'choice mr';

		foreach($field_options as $key => $value)
		{
			$selected = ($key == $data);

			$r .= '<label>'.form_radio($this->field_name, $value, $selected).NBS.$key.'</label>';
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

	function _get_field_options($data)
	{
		$field_options = array();

		if ( ! isset($this->settings['field_pre_populate'])
			OR $this->settings['field_pre_populate'] == 'n'
				OR $this->settings['field_pre_populate'] == FALSE)
		{
			if ( ! is_array($this->settings['field_list_items']))
			{
				foreach (explode("\n", trim($this->settings['field_list_items'])) as $v)
				{
					$v = trim($v);
					$field_options[form_prep($v)] = $v;
				}
			}
			else
			{
				$field_options = $this->settings['field_list_items'];
			}
		}
		else
		{
			// We need to pre-populate this menu from an another channel custom field

			ee()->db->select('field_id_'.$this->settings['field_pre_field_id']);
			ee()->db->where('channel_id', $this->settings['field_pre_channel_id']);
			$pop_query = ee()->db->get('channel_data');

			$field_options[''] = '--';

			if ($pop_query->num_rows() > 0)
			{
				foreach ($pop_query->result_array() as $prow)
				{
					$selected = ($prow['field_id_'.$this->settings['field_pre_field_id']] == $data) ? 1 : '';
					$pretitle = substr($prow['field_id_'.$this->settings['field_pre_field_id']], 0, 110);
					$pretitle = str_replace(array("\r\n", "\r", "\n", "\t"), " ", $pretitle);
					$pretitle = form_prep($pretitle);

					$field_options[form_prep($prow['field_id_'.$this->settings['field_pre_field_id']])] = $pretitle;
				}
			}
		}

		return $field_options;
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

/* End of file ft.radio.php */
/* Location: ./system/expressionengine/fieldtypes/ft.radio.php */
