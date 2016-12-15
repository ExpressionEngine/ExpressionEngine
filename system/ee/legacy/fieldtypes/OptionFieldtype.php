<?php

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.5.0
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine OptionFieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
abstract class OptionFieldtype extends EE_Fieldtype {

	public function display_field($data)
	{
		return NULL;
	}

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
		// TODO: lang key
		$grid->setNoResultsText('No <b>key/value pairs</b> found.', 'Add');
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
		if ($data['field_pre_populate'] == 'v')
		{
			$pairs = array();

			if (isset($data['value_label_pairs']['rows']))
			{
				$data['value_label_pairs'] = $data['value_label_pairs']['rows'];
			}

			foreach ($data['value_label_pairs'] as $row)
			{
				$pairs[$row['value']] = $row['label'];
			}

			if ($this->content_type() == 'grid')
			{
				return array(
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
				return array(
					'field_pre_populate' => $data['field_pre_populate'],
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
	 * @param	array	$data	Fieldtype settings array
	 * @param	string	$title	Lang key for settings section title
	 * @param	string	$desc	Lang key or string for settings section description
	 * @return	Array in shared form view format for settings form
	 */
	protected function getSettingsForm($data, $title, $desc)
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
				'type' => 'select',
				'choices' => $this->get_channel_field_list(),
				'value' => $data['field_pre_channel_id'] . '_' . $data['field_pre_field_id']
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
	 * Parses a multi-selection field as a single variable
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

	/**
	 * Parses a multi-selection field as a variable pair
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

		$text_format = $this->row('field_ft_'.$this->field_id, 'none');

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
					$vars['item']       = $pairs[$item];
					$vars['item:label'] = $pairs[$item];
				}

				$tmp = ee()->functions->prep_conditionals($tagdata, $vars);
				$raw_chunk .= ee()->functions->var_swap($tmp, $vars);

				$typography_options = array(
					'text_format'	=> $text_format,
					'html_format'	=> $this->row('channel_html_formatting', 'all'),
					'auto_links'	=> $this->row('channel_auto_link_urls', 'n'),
					'allow_img_url' => $this->row('channel_allow_img_urls', 'y')
				);

				$vars['item'] = ee()->typography->parse_type(
					$vars['item'],
					$typography_options
				);
				$vars['item:label'] = ee()->typography->parse_type(
					$vars['item:label'],
					$typography_options
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
			return ee()->functions->encode_ee_tags($raw_chunk);
		}

		return $chunk;
	}
}

// EOF
