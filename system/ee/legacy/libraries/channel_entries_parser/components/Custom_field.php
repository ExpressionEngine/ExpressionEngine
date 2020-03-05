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
 * Channel Parser Component (Custom Fields)
 */
class EE_Channel_custom_field_parser implements EE_Channel_parser_component {

	/**
	 * Check if custom fields are enabled.
	 *
	 * @param array		A list of "disabled" features
	 * @return Boolean	Is disabled?
	 */
	public function disabled(array $disabled, EE_Channel_preparser $pre)
	{
		return in_array('custom_fields', $disabled);
	}

	/**
	 * @todo Find all of the tags like the custom date fields?
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The preparser object.
	 * @return Object	Channel fields api, to reduce a lookup (for now)
	 */
	public function pre_process($tagdata, EE_Channel_preparser $pre)
	{
		return ee()->api_channel_fields;
	}

	/**
	 * Replace all of the custom channel fields.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The channel parser object
	 * @param Mixed		The results from the preparse method
	 *
	 * @return String	The processed tagdata
	 */
	public function replace($tagdata, EE_Channel_data_parser $obj, $ft_api)
	{
		$tag = $obj->tag();
		$data = $obj->row();
		$prefix = $obj->prefix();

		$site_id = $data['site_id'];
		$cfields = $obj->channel()->cfields;
		$rfields = $obj->channel()->rfields;
		$gfields = $obj->channel()->gfields;
		$ffields = $obj->channel()->ffields;

		$rfields = isset($rfields[$site_id]) ? $rfields[$site_id] : [];
		$cfields = isset($cfields[$site_id]) ? $cfields[$site_id] : [];
		$gfields = isset($gfields[$site_id]) ? $gfields[$site_id] : [];
		$ffields = isset($ffields[$site_id]) ? $ffields[$site_id] : [];

		$cfields = array_diff_key($cfields, $rfields);

		if (empty($cfields))
		{
			return $tagdata;
		}

		$field = ee('Variables/Parser')->parseVariableProperties($tag, $prefix);

		if (isset($cfields[$field['field_name']]))
		{
			$entry = '';
			$field_id = $cfields[$field['field_name']];

			if ((isset($data['field_id_'.$field_id]) && $data['field_id_'.$field_id] !== '') OR
				array_key_exists($field['field_name'], $gfields) OR // is a Grid single
				array_key_exists($field['field_name'], $ffields)) // is a Fluid single
			{
				$modifier = $field['modifier'];

				$parse_fnc = ($modifier) ? 'replace_'.$modifier : 'replace_tag';

				$obj = $ft_api->setup_handler($field_id, TRUE);

				if ($obj)
				{
					$_ft_path = $ft_api->ft_paths[$ft_api->field_type];
					ee()->load->add_package_path($_ft_path, FALSE);

					$obj->_init(array(
						'row'			=> $data,
						'content_id'	=> $data['entry_id'],
						'content_type'	=> 'channel'
					));

					$data = $ft_api->apply('pre_process', array(
						$data['field_id_'.$field_id]
					));

					if (method_exists($obj, $parse_fnc))
					{
						$entry = $ft_api->apply($parse_fnc, array(
							$data,
							$field['params'],
							FALSE
						));
					}
					elseif (method_exists($obj, 'replace_tag_catchall'))
					{
						$entry = $ft_api->apply('replace_tag_catchall', array(
							$data,
							$field['params'],
							FALSE,
							$field['full_modifier']
						));
					}

					ee()->load->remove_package_path($_ft_path);
				}
				else
				{
					// Couldn't find a fieldtype
					$entry = ee()->typography->parse_type(
						ee()->functions->encode_ee_tags($data['field_id_'.$field_id]),
						array(
							'text_format'	=> $data['field_ft_'.$field_id],
							'html_format'	=> $data['channel_html_formatting'],
							'auto_links'	=> $data['channel_auto_link_urls'],
							'allow_img_url' => $data['channel_allow_img_urls']
						)
					);
				}

				// prevent accidental parsing of other channel variables in custom field data
				if (strpos($entry, '{') !== FALSE)
				{
					$entry = str_replace(
						array('{', '}'),
						array(unique_marker('channel_bracket_open'), unique_marker('channel_bracket_close')),
						$entry
					);
				}

				$tagdata = str_replace(LD.$tag.RD, $entry, $tagdata);
			}

			$tagdata = str_replace(LD.$tag.RD, '', $tagdata);
		}

		return $tagdata;
	}
}
