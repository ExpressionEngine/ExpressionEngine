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
 * ExpressionEngine Multi-Select Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Multi_select_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Multi Select',
		'version'	=> '1.0'
	);

	var $has_array_data = TRUE;

	function display_field($data)
	{
		ee()->load->helper('custom_field');

		$values = decode_multi_field($data);
		$field_options = $this->_get_field_options($data);

		$text_direction = (isset($this->settings['field_text_direction']))
			? $this->settings['field_text_direction'] : 'ltr';

		if (REQ == 'CP')
		{
			return ee('View')->make('multi_select:publish')->render(array(
				'field_name' => $this->field_name,
				'values' => $values,
				'options' => $field_options
			));
		}

		return form_multiselect($this->field_name.'[]', $field_options, $values, 'dir="'.$text_direction.'" class="multiselect_input"');
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
						'html_format'	=> $this->row['channel_html_formatting'],
						'auto_links'	=> $this->row['channel_auto_link_urls'],
						'allow_img_url' => $this->row['channel_allow_img_urls']
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

		$text_format = (isset($this->row['field_ft_'.$this->field_id]))
			? $this->row['field_ft_'.$this->field_id] : 'none';

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
								'html_format'	=> $this->row['channel_html_formatting'],
								'auto_links'	=> $this->row['channel_auto_link_urls'],
								'allow_img_url' => $this->row['channel_allow_img_urls']
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

	function _get_field_options($data)
	{
		$field_options = array();

		$pre_populate = isset($this->settings['field_pre_populate']) ? get_bool_from_string($this->settings['field_pre_populate']) : FALSE;

		if ( ! $pre_populate)
		{
			if ( ! is_array($this->settings['field_list_items']))
			{
				foreach (explode("\n", trim($this->settings['field_list_items'])) as $v)
				{
					$v = trim($v);
					$field_options[form_prep($v)] = form_prep($v);
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

	public function save($data)
	{
		if (is_array($data))
		{
			ee()->load->helper('custom_field');
			$data = encode_multi_field($data);
		}

		return $data;
	}
}

// END Multi_select_ft class

/* End of file ft.multi_select.php */
/* Location: ./system/expressionengine/fieldtypes/ft.multi_select.php */
