<?php
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Channel Parser Component (Custom Fields)
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Channel_custom_field_parser implements EE_Channel_parser_component {

	public function disabled(array $disabled, EE_Channel_preparser $pre)
	{
		return in_array('custom_fields', $disabled);
	}

	public function pre_process($tagdata, EE_Channel_preparser $pre)
	{
		return NULL;
	}

	public function replace($tagdata, EE_Channel_data_parser $obj, $pre)
	{
		$tag = $obj->tag();
		$data = $obj->row();
		$prefix = $obj->prefix();

		$site_id = $data['site_id'];
		$cfields = $obj->channel()->cfields[$site_id];

		$ft_api = get_instance()->api_channel_fields;

		$unprefixed_tag	= preg_replace('/^'.$prefix.'/', '', $tag);
		$field_name		= substr($unprefixed_tag.' ', 0, strpos($unprefixed_tag.' ', ' '));
		$param_string	= substr($unprefixed_tag.' ', strlen($field_name));

		if (isset($cfields[$field_name]))
		{
			$entry = '';
			$field_id = $cfields[$field_name];

			if (isset($data['field_id_'.$field_id]) && $data['field_id_'.$field_id] != '')
			{
				$params = array();
				$parse_fnc = 'replace_tag';
				$parse_fnc_catchall = 'replace_tag_catchall';

				if ($param_string)
				{
					$params = get_instance()->functions->assign_parameters($param_string);
				}

				if ($ft_api->setup_handler($field_id))
				{
					$ft_api->apply('_init', array(array('row' => $data)));
					$data = $ft_api->apply('pre_process', array($data['field_id_'.$field_id]));

					if ($ft_api->check_method_exists($parse_fnc))
					{
						$entry = $ft_api->apply($parse_fnc, array($data, $params, FALSE));
					}
					elseif ($ft_api->check_method_exists($parse_fnc_catchall))
					{
						$entry = $ft_api->apply($parse_fnc_catchall, array($data, $params, FALSE, $modifier));
					}
				}
				else
				{
					// Couldn't find a fieldtype
					$entry = get_instance()->typography->parse_type(
						get_instance()->functions->encode_ee_tags($data['field_id_'.$field_id]),
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
		}

		return $tagdata;
	}
}