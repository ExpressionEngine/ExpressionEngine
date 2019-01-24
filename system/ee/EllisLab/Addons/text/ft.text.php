<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use EllisLab\Addons\FilePicker\FilePicker;

/**
 * Text Fieldtype
 */
class Text_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Text Input',
		'version'	=> '1.0.0'
	);

	// Parser Flag (preparse pairs?)
	var $has_array_data = FALSE;


	function validate($data)
	{
		ee()->load->library('form_validation');

		if ($data == '')
		{
			return TRUE;
		}

		if ( ! isset($this->field_content_types))
		{
			ee()->load->model('field_model');
			$this->field_content_types = ee()->field_model->get_field_content_types();
		}

		if ( ! isset($this->settings['field_content_type']))
		{
			return TRUE;
		}

		$content_type = $this->settings['field_content_type'];

		if (in_array($content_type, $this->field_content_types['text']) && $content_type != 'any')
		{

			if ($content_type == 'decimal')
			{
				if ( ! ee()->form_validation->numeric($data))
				{
					return ee()->lang->line($content_type);
				}

				// Check if number exceeds mysql limits
				if ($data >= 999999.9999)
				{
					return ee()->lang->line('number_exceeds_limit');
				}

				return TRUE;
			}

			if ( ! ee()->form_validation->$content_type($data))
			{
				return ee()->lang->line($content_type);
			}

			// Check if number exceeds mysql limits
			if ($content_type == 'integer')
			{
				if (($data < -2147483648) OR ($data > 2147483647))
				{
					return ee()->lang->line('number_exceeds_limit');
				}
			}
		}

		return TRUE;
	}

	/**
	 * Save the field's value
	 *
	 * Mostly used to check it the value is empty when dealing with numeric
	 * field content types
	 *
	 * @param  string $data The data submitted
	 * @return mixed The cleaned data to be saved
	 */
	public function save($data)
	{
		if ( ! isset($this->field_content_types))
		{
			ee()->load->model('field_model');
			$this->field_content_types = ee()->field_model->get_field_content_types();
		}

		$content_type = (isset($this->settings['field_content_type']))
			? $this->settings['field_content_type']
			: 'any';

		if (in_array($content_type, $this->field_content_types['text']) && $content_type != 'any')
		{
			if ($data == '')
			{
				return NULL;
			}
		}

		return $data;
	}

	function display_field($data)
	{
		$type  = $this->get_setting('field_content_type', 'all');
		$field = array(
			'name'               => $this->field_name,
			'value'              => $this->_format_number($data, $type),
			'dir'                => $this->settings['field_text_direction'],
			'field_content_type' => $type
		);

		if ($this->get_setting('field_disabled'))
		{
			$field['disabled'] = 'disabled';
		}

		if (isset($this->settings['field_placeholder']))
		{
			$field['placeholder'] = $this->settings['field_placeholder'];
		}

		// maxlength attribute should only appear if its value is > 0
		if ($this->settings['field_maxl'])
		{
			$field['maxlength'] = $this->settings['field_maxl'];
		}

		if (REQ == 'CP')
		{
			$format_options = array();

			if ($this->get_setting('field_show_fmt'))
			{
				ee()->load->model('addons_model');
				$format_options = ee()->addons_model->get_plugin_formatting(TRUE);
			}

			$vars = array(
				'name'            => $this->name(),
				'field'           => $field,
				'settings'        => $this->settings,
				'format_options'  => $format_options,
			);

			if ($this->get_setting('field_show_file_selector'))
			{
				$fp = new FilePicker();
				$fp->inject(ee()->view);
				$vars['fp_url'] = ee('CP/URL')->make($fp->controller, array('directory' => 'all'));

				ee()->cp->add_js_script(array(
					'file' => array('fields/textarea/cp'),
					'plugin' => array('ee_txtarea')
				));
			}

			return ee('View')->make('text:publish')->render($vars);
		}

		return form_input($field);
	}

	function replace_tag($data, $params = '', $tagdata = '')
	{
		// Experimental parameter, do not use
		if (isset($params['raw_output']) && $params['raw_output'] == 'yes')
		{
			return ee()->functions->encode_ee_tags($data);
		}

		$type		= isset($this->settings['field_content_type']) ? $this->settings['field_content_type'] : 'all';
		$decimals	= isset($params['decimal_place']) ? (int) $params['decimal_place'] : FALSE;

		$data = $this->_format_number($data, $type, $decimals);

		ee()->load->library('typography');

		return ee()->typography->parse_type(
			ee()->functions->encode_ee_tags($data),
			array(
				'text_format'	=> $this->get_format(),
				'html_format'	=> $this->row('channel_html_formatting', 'all'),
				'auto_links'	=> $this->row('channel_auto_link_urls', 'n'),
				'allow_img_url' => $this->row('channel_allow_img_urls', 'y')
			)
		);
	}

	function display_settings($data)
	{
		ee()->load->model('addons_model');
		$format_options = ee()->addons_model->get_plugin_formatting(TRUE);

		$settings = array(
			array(
				'title' => 'field_max_length',
				'fields' => array(
					'field_maxl' => array(
						'type' => 'text',
						'value' => ( ! isset($data['field_maxl']) OR $data['field_maxl'] == '') ? 256 : $data['field_maxl']
					)
				)
			),
			array(
				'title' => 'field_fmt',
				'fields' => array(
					'field_fmt' => array(
						'type' => 'radio',
						'choices' => $format_options,
						'value' => isset($data['field_fmt']) ? $data['field_fmt'] : 'none',
						'note' => form_label(
							form_checkbox('update_formatting', 'y')
							.lang('update_existing_fields')
						)
					)
				)
			)
		);

		// Only show the update existing fields note when editing.
		if ( ! $this->field_id)
		{
			unset($settings[1]['fields']['field_fmt']['note']);
		}

		if ($this->content_type() != 'grid')
		{
			$settings[] = array(
				'title' => 'field_show_fmt',
				'desc' => 'field_show_fmt_desc',
				'fields' => array(
					'field_show_fmt' => array(
						'type' => 'yes_no',
						'value' => isset($data['field_show_fmt']) ? $data['field_show_fmt'] : 'n'
					)
				)
			);
		}

		$settings[] = array(
			'title' => 'field_text_direction',
			'fields' => array(
				'field_text_direction' => array(
					'type' => 'radio',
					'choices' => array(
						'ltr' => lang('field_text_direction_ltr'),
						'rtl' => lang('field_text_direction_rtl')
					),
					'value' => isset($data['field_text_direction']) ? $data['field_text_direction'] : 'ltr'
				)
			)
		);

		if ($this->content_type() != 'category' && $this->content_type() != 'member')
		{
			$settings[] = array(
				'title' => 'field_content_text',
				'desc' => 'field_content_text_desc',
				'fields' => array(
					'field_content_type' => array(
						'type' => 'radio',
						'choices' => $this->_get_content_options(),
						'value' => isset($data['field_content_type']) ? $data['field_content_type'] : ''
					)
				)
			);

			if ($this->content_type() != 'grid')
			{
				$field_tools = array(
					'title' => 'field_tools',
					'desc' => '',
					'fields' => array(
						'field_show_smileys' => array(
							'type' => 'checkbox',
							'scalar' => TRUE,
							'choices' => array(
								'y' => lang('show_smileys'),
							),
							'value' => isset($data['field_show_smileys']) ? $data['field_show_smileys'] : 'n'
						),
						'field_show_file_selector' => array(
							'type' => 'checkbox',
							'scalar' => TRUE,
							'choices' => array(
								'y' => lang('show_file_selector')
							),
							'value' => isset($data['field_show_file_selector']) ? $data['field_show_file_selector'] : 'n'
						)
					)
				);

				if ( ! ee('Addon')->get('emoticon')->isInstalled())
				{
					unset($field_tools['fields']['field_show_smileys']);
				}

				$settings[] = $field_tools;
			}
		}

		if ($this->content_type() == 'grid')
		{
			return array('field_options' => $settings);
		}

		return array('field_options_text' => array(
			'label' => 'field_options',
			'group' => 'text',
			'settings' => $settings
		));
	}

	/**
	 * Returns allowed content types for the text fieldtype
	 *
	 * @return	array
	 */
	private function _get_content_options()
	{
		return array(
			'all'		=> lang('all'),
			'numeric'	=> lang('type_numeric'),
			'integer'	=> lang('type_integer'),
			'decimal'	=> lang('type_decimal')
		);
	}

	function grid_save_settings($data)
	{
		return $data;
	}

	function save_settings($data)
	{
		$defaults = array(
			'field_maxl'               => 256,
			'field_content_type'       => '',
			'field_show_smileys'       => 'n',
			'field_show_file_selector' => 'n'
		);

		$all = array_merge($defaults, $data);

		return array_intersect_key($all, $defaults);
	}

	function settings_modify_column($data)
	{
		if (empty($data['field_settings']))
		{
			return array();
		}

		$settings = $data['field_settings'];
		$field_content_type = isset($settings['field_content_type']) ? $settings['field_content_type'] : 'all';

		return $this->_get_column_settings($field_content_type, $data['field_id']);
	}

	public function grid_settings_modify_column($data)
	{
		$settings = $data;

		if (isset($settings['col_settings']) && ! is_array($settings['col_settings']))
		{
			$settings = json_decode($settings['col_settings'], TRUE);
		}

		return $this->_get_column_settings(
			isset($settings['field_content_type']) ? $settings['field_content_type'] : '',
			$data['col_id'],
			TRUE);
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
	 * Returns database column setting for a particular text field configuration
	 *
	 * @param	string	Type of data to be stored in this text field
	 * @param	int		Field/column ID to map settings to
	 * @param	bool	Whether or not we're preparing these settings for
	 * 					a Grid field
	 * @return	array	Database column settings for this text field
	 */
	private function _get_column_settings($data_type, $field_id, $grid = FALSE)
	{
		$field_name = ($grid) ? 'col_id_'.$field_id : 'field_id_'.$field_id;

		switch($data_type)
		{
			case 'numeric':
				$fields[$field_name] = array(
					'type'		=> 'FLOAT',
					'default'	=> 0
				);
				break;
			case 'integer':
				$fields[$field_name] = array(
					'type'		=> 'INT',
					'default'	=> 0
				);
				break;
			case 'decimal':
				$fields[$field_name] = array(
					'type'		=> 'DECIMAL(10,4)',
					'default'	=> 0
				);
				break;
			default:
				$fields[$field_name] = array(
					'type'		=> 'text',
					'null'		=> TRUE
				);
		}

		return $fields;
	}

	function _format_number($data, $type = 'all', $decimals = FALSE)
	{
		// Numeric fields that have no data are stored as NULL
		if ($data === NULL)
		{
			return '';
		}

		switch($type)
		{
			case 'numeric':	$data = rtrim(rtrim(sprintf('%F', $data), '0'), '.'); // remove trailing zeros up to decimal point and kill decimal point if no trailing zeros
				break;
			case 'integer': $data = sprintf('%d', $data);
				break;
			case 'decimal':
				$parts = explode('.', sprintf('%F', $data));
				$parts[1] = isset($parts[1]) ? rtrim($parts[1], '0') : '';

				$decimals = ($decimals === FALSE) ? 2 : $decimals;
				$data = $parts[0].'.'.str_pad($parts[1], $decimals, '0');
				break;
			default:
				if ($decimals && ctype_digit(str_replace('.', '', $data))) {
					$data = number_format($data, $decimals);
				}
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

// END Text_Ft class

// EOF
