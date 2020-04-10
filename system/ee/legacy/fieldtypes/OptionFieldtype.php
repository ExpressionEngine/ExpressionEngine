<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Option Field type
 */
abstract class OptionFieldtype extends EE_Fieldtype {

	/**
	 * Creates a mini Grid field based on the data in the 'value_label_pairs' key
	 *
	 * @return MiniGridInput object
	 */
	protected function getValueLabelMiniGrid($data)
	{
		$grid = ee('CP/MiniGridInput', array(
			'field_name' => 'value_label_pairs'
		));
		$grid->loadAssets();
		$grid->setColumns(array(
			'Value',
			'Label'
		));
		$grid->setNoResultsText(lang('no_value_label_pairs'), lang('add_new'));
		$grid->setBlankRow(array(
			array('html' => form_input('value', '')),
			array('html' => form_input('label', ''))
		));
		$grid->setData(array());

		if (isset($data['value_label_pairs']))
		{
			if (isset($data['value_label_pairs']['rows']))
			{
				$data['value_label_pairs'] = $data['value_label_pairs']['rows'];
			}

			$pairs = array();
			$i = 1;
			foreach ($data['value_label_pairs'] as $value => $label)
			{
				$pairs[] = array(
					'attrs' => array('row_id' => $i),
					'columns' => array(
						array('html' => form_input('value', $value)),
						array('html' => form_input('label', $label))
					)
				);
				$i++;
			}

			$grid->setData($pairs);
		}

		return $grid;
	}

	/**
	 * Saves settings for a field that allows its options to be specified in
	 * a mini Grid field
	 *
	 * @return Settings to be returned from save_settings()
	 */
	public function save_settings($data)
	{
		if (isset($data['field_pre_populate']) && $data['field_pre_populate'] == 'v')
		{
			$pairs = array();

			if (isset($data['value_label_pairs']['rows']))
			{
				$data['value_label_pairs'] = $data['value_label_pairs']['rows'];

				foreach ($data['value_label_pairs'] as $row)
				{
					$pairs[$row['value']] = $row['label'];
				}
			}
			elseif (isset($data['value_label_pairs']))
			{
				$pairs = $data['value_label_pairs'];
			}

			if ($this->content_type() == 'grid')
			{
				return array(
					'field_fmt' => $data['field_fmt'],
					'field_pre_populate' => $data['field_pre_populate'],
					'field_list_items' => '',
					'value_label_pairs' => $pairs
				);
			}

			return array(
				'value_label_pairs' => $pairs
			);
		}
		else
		{
			if ($this->content_type() == 'grid')
			{
				if (empty($data['field_pre_populate_id']))
				{
					$data['field_pre_populate_id'] = '0_0';
				}

				list($channel_id, $field_id) = explode('_', $data['field_pre_populate_id']);

				$field_pre_channel_id = $channel_id;
				$field_pre_field_id = $field_id;

				return array(
					'field_fmt' => $data['field_fmt'],
					'field_pre_populate' => isset($data['field_pre_populate']) ? $data['field_pre_populate'] : 'n',
					'field_pre_channel_id' => $field_pre_channel_id,
					'field_pre_field_id' => $field_pre_field_id,
					'field_list_items' => $data['field_list_items'],
					'value_label_pairs' => array()
				);
			}

			return array();
		}
	}

	/**
	 * Constructs a settings form array for multi-option fields
	 *
	 * @param	string	$field_type	Fieldtype short name
	 * @param	array	$data		Fieldtype settings array
	 * @param	string	$title		Lang key for settings section title
	 * @param	string	$desc		Lang key or string for settings section description
	 * @return	Array in shared form view format for settings form
	 */
	protected function getSettingsForm($field_type, $data, $title, $desc)
	{
		$format_options = ee()->addons_model->get_plugin_formatting(TRUE);

		$defaults = array(
			'field_fmt' => 'none',
			'field_pre_populate' => FALSE,
			'field_list_items' => '',
			'field_pre_channel_id' => 0,
			'field_pre_field_id' => 0
		);

		foreach ($defaults as $setting => $value)
		{
			$data[$setting] = isset($data[$setting]) ? $data[$setting] : $value;
		}

		// Load from validation error
		if (isset($_POST['value_label_pairs']['rows']) &&
			((isset($_POST['field_type']) && $_POST['field_type'] == $field_type) OR (isset($_POST['m_field_type']) && $_POST['m_field_type'] == $field_type)))
		{
			foreach ($_POST['value_label_pairs']['rows'] as $row)
			{
				$data['value_label_pairs'][$row['value']] = $row['label'];
			}
		}

		if ((isset($data['value_label_pairs']) && ! empty($data['value_label_pairs'])) OR ! $this->field_id)
		{
			$data['field_pre_populate'] = 'v';
		}
		else
		{
			$data['field_pre_populate'] = $data['field_pre_populate'] ? 'y' : 'n';
		}

		$grid = $this->getValueLabelMiniGrid($data);

		$settings = array(
			array(
				'title' => 'field_fmt',
				'fields' => array(
					'field_fmt' => array(
						'type' => 'radio',
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
				'title' => $title,
				'desc' => $desc,
				'fields' => array(
					'field_value_label_pairs' => array(
						'type' => 'radio',
						'name' => 'field_pre_populate',
						'choices' => array(
							'v' => lang('field_value_label_pairs'),
						),
						'value' => $data['field_pre_populate']
					),
					'value_label_pairs' => array(
						'type' =>'html',
						'margin_left' => TRUE,
						'content' => ee('View')->make('ee:_shared/form/mini_grid')
							->render($grid->viewData())
					),
					'field_pre_populate_n' => array(
						'type' => 'radio',
						'name' => 'field_pre_populate',
						'choices' => array(
							'n' => lang('field_populate_manually'),
						),
						'value' => $data['field_pre_populate']
					),
					'field_list_items' => array(
						'type' => 'textarea',
						'margin_left' => TRUE,
						'value' => $data['field_list_items']
					)
				)
			)
		);

		if ($this->content_type() == 'channel')
		{
			$settings[1]['fields']['field_pre_populate_y'] = array(
				'type' => 'radio',
				'name' => 'field_pre_populate',
				'choices' => array(
					'y' => lang('field_populate_from_channel'),
				),
				'value' => $data['field_pre_populate']
			);

			$settings[1]['fields']['field_pre_populate_id'] = array(
				'type' => 'radio',
				'margin_left' => TRUE,
				'choices' => $this->get_channel_field_list(),
				'value' => ($data['field_pre_channel_id'] != 0)
					? $data['field_pre_channel_id'] . '_' . $data['field_pre_field_id'] : NULL,
				'no_results' => [
					'text' => sprintf(lang('no_found'), lang('fields'))
				]
			);
		}

		// Only show the update existing fields note when editing.
		if ( ! $this->field_id)
		{
			unset($settings[0]['fields']['field_fmt']['note']);
		}

		return $settings;
	}

	/**
	 * Constructs a Grid settings form array for multi-option fields
	 *
	 * @param	string	$field_type	Fieldtype short name
	 * @param	array	$data		Fieldtype settings array
	 * @param	string	$title		Lang key for settings section title
	 * @param	string	$desc		Lang key or string for settings section description
	 * @return	Array in shared form view format for Grid settings form
	 */
	protected function getGridSettingsForm($field_type, $data, $title, $desc)
	{
		$format_options = ee()->addons_model->get_plugin_formatting(TRUE);

		if ( ! isset($data['field_pre_populate']))
		{
			// Old Grid columns without this setting need to be set to 'n'
			$data['field_pre_populate'] = empty($this->field_id) ? 'v' : 'n';
		}

		if ( ! isset($data['field_pre_channel_id']))
		{
			$data['field_pre_channel_id'] = 0;
			$data['field_pre_field_id'] = 0;
		}

		// Load from validation error
		if (isset($data['value_label_pairs']['rows']))
		{
			foreach ($data['value_label_pairs']['rows'] as $key => $row)
			{
				$data['value_label_pairs'][$row['value']] = $row['label'];
			}
			unset($data['value_label_pairs']['rows']);
		}

		$grid = $this->getValueLabelMiniGrid($data);

		ee()->javascript->output("
			var miniGridInit = function(context) {
				$('.fields-keyvalue', context).miniGrid({grid_min_rows:0,grid_max_rows:''});
			}
			Grid.bind('".$field_type."', 'displaySettings', function(column) {
				miniGridInit(column);
				SelectField.renderFields(column)
			});
			FieldManager.on('fieldModalDisplay', function(modal) {
				miniGridInit(modal);
			});
		");

		return array(
			'field_options' => array(
				array(
					'title' => 'field_fmt',
					'fields' => array(
						'field_fmt' => array(
							'type' => 'radio',
							'choices' => $format_options,
							'value' => isset($data['field_fmt']) ? $data['field_fmt'] : 'none',
						)
					)
				),
				array(
					'title' => $title,
					'desc' => $desc,
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
							'margin_left' => TRUE,
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
							'margin_left' => TRUE,
							'value' => isset($data['field_list_items']) ? $data['field_list_items'] : ''
						),
						'field_pre_populate_y' => array(
							'type' => 'radio',
							'name' => 'field_pre_populate',
							'choices' => array(
								'y' => lang('field_populate_from_channel'),
							),
							'value' => $data['field_pre_populate'] ?: 'v'
						),
						'field_pre_populate_id' => array(
							'type' => 'radio',
							'margin_left' => TRUE,
							'choices' => $this->get_channel_field_list(),
							'value' => ($data['field_pre_channel_id'] != 0)
								? $data['field_pre_channel_id'] . '_' . $data['field_pre_field_id'] : NULL,
							'no_results' => [
								'text' => sprintf(lang('no_found'), lang('fields'))
							]
						)
					)
				)
			)
		);
	}

	/**
	 * Validate settings
	 */
	public function validate_settings($data)
	{
		// AJAX validation is currently a little greedy when tabbing across an
		// incompleted form, so just ignore AJAX validation for now and let
		// regular validation take care of it
		if (AJAX_REQUEST)
		{
			return TRUE;
		}

		$validator = ee('Validation')->make(array(
			'value_label_pairs' => 'validateValueLabelPairs'
		));

		$validator->defineRule('validateValueLabelPairs', function($key, $pairs)
		{
			if (isset($pairs['rows']) OR isset($_POST['value_label_pairs']['rows']))
			{
				$values = array();
				$posted = isset($pairs['rows']) ? $pairs['rows'] : $_POST['value_label_pairs']['rows'];

				foreach($posted as $row)
				{
					// Duplicate values
					if (in_array($row['value'], $values))
					{
						return 'value_label_duplicate_values';
					}

					$values[] = $row['value'];
				}
			}

			return TRUE;
		});

		return $validator->validate($data);
	}

	/**
	 * Default replace_tag implementation
	 */
	public function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		// Experimental parameter, do not use
		if (isset($params['raw_output']) && $params['raw_output'] == 'yes')
		{
			return ee()->functions->encode_ee_tags($data);
		}

		return $data;
	}

	/**
	 * Process text through default typography options
	 *
	 * @param	string	$string	String to process
	 * @return	Processed string
	 */
	protected function processTypograpghy($string)
	{
		ee()->load->library('typography');

		return ee()->typography->parse_type(
			ee()->functions->encode_ee_tags($string),
			array(
				'text_format'	=> $this->get_format(),
				'html_format'	=> $this->row('channel_html_formatting', 'all'),
				'auto_links'	=> $this->row('channel_auto_link_urls', 'n'),
				'allow_img_url' => $this->row('channel_allow_img_urls', 'y')
			)
		);
	}

	/**
	 * Parses a multi-selection field as a single variable
	 *
	 * @param	string	$data	Entry field data
	 * @param	array	$params	Params passed to the field via the template
	 * @return	Parsed template string
	 */
	protected function _parse_single($data, $params)
	{
		if (isset($params['limit']))
		{
			$limit = intval($params['limit']);

			if (count($data) > $limit)
			{
				$data = array_slice($data, 0, $limit);
			}
		}

		$pairs = $this->get_setting('value_label_pairs');

		if ( ! empty($pairs))
		{
			foreach ($data as $key => $value)
			{
				if (isset($pairs[$value]))
				{
					$data[$key] = $pairs[$value];
				}
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

		return $this->processTypograpghy($entry);
	}

	 /**
 	 * Parses a multi-selection field as a variable pair
 	 *
 	 * @param	string	$data		Entry field data
 	 * @param	array	$params		Params passed to the field via the template
 	 * @param	string	$tagdata	String between the variable pair
 	 * @return	Parsed template string
 	 */
	protected function _parse_multi($data, $params, $tagdata)
	{
		$chunk = '';
		$raw_chunk = '';
		$limit = FALSE;

		// Limit Parameter
		if (is_array($params) AND isset($params['limit']))
		{
			$limit = $params['limit'];
		}

		$pairs = $this->get_setting('value_label_pairs');

		foreach($data as $key => $item)
		{
			if ( ! $limit OR $key < $limit)
			{
				$vars['item'] = $item;
				$vars['item:label'] = $item;
				$vars['item:value'] = $item;
				$vars['count'] = $key + 1;	// {count} parameter

				if (isset($pairs[$item]))
				{
					$vars['item:label'] = $pairs[$item];
				}

				$tmp = ee()->functions->prep_conditionals($tagdata, $vars);
				$raw_chunk .= ee()->functions->var_swap($tmp, $vars);

				$vars['item:label'] = $this->processTypograpghy($vars['item:label']);

				$chunk .= ee()->TMPL->parse_variables_row($tmp, $vars);
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
			return ee()->functions->encode_ee_tags($raw_chunk);
		}

		return $chunk;
	}
}

// EOF
