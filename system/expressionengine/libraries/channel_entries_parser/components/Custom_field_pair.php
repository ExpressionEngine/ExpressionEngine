<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
 * ExpressionEngine Channel Parser Component (Custom Field Pairs)
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Channel_custom_field_pair_parser implements EE_Channel_parser_component {

	/**
	 * Check if custom fields are enabled.
	 *
	 * @param array		A list of "disabled" features
	 * @return Boolean	Is disabled?
	 */
	public function disabled(array $disabled, EE_Channel_preparser $pre)
	{
		return in_array('custom_fields', $disabled) OR empty($pre->channel()->pfields);
	}

	// --------------------------------------------------------------------

	/**
	 * Find any {field} {/field} tag pair chunks in the template and
	 * extract them for easier parsing in the main loop.
	 *
	 * The returned chunks will be passed to replace() as a third parameter.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The preparser object.
	 * @return Array	The found custom field pair chunks
	 */
	public function pre_process($tagdata, EE_Channel_preparser $pre)
	{
		$pfield_chunk = array();

		$prefix = $pre->prefix();
		$channel = $pre->channel();

		foreach ($channel->pfields as $site_id => $pfields)
		{
			$pfield_names = array_intersect($channel->cfields[$site_id], array_keys($pfields));

			foreach($pfield_names as $field_name => $field_id)
			{
				if ( ! $pre->has_tag_pair($field_name))
				{
					continue;
				}

				$offset = 0;
				$field_name = $prefix.$field_name;
				
				while (($end = strpos($tagdata, LD.'/'.$field_name, $offset)) !== FALSE)
				{
					// This hurts soo much. Using custom fields as pair and single vars in the same
					// channel tags could lead to something like this: {field}...{field}inner{/field}
					// There's no efficient regex to match this case, so we'll find the last nested
					// opening tag and re-cut the chunk.

					if (preg_match("/".LD."{$field_name}((?::\S+)?)(\s.*?)?".RD."(.*?)".LD.'\/'."{$field_name}\\1".RD."/s", $tagdata, $matches, 0, $offset))
					{
						$chunk = $matches[0];
						$modifier = $matches[1];
						$params = $matches[2];
						$content = $matches[3];

						// We might've sandwiched a single tag - no good, check again (:sigh:)
						if ((strpos($chunk, LD.$field_name.$modifier, 1) !== FALSE) && preg_match_all("/".LD."{$field_name}{$modifier}(\s.*?)?".RD."/s", $chunk, $match))
						{
							// Let's start at the end
							$idx = count($match[0]) - 1;
							$tag = $match[0][$idx];
							
							// Reassign the parameter
							$params = $match[1][$idx];

							// Cut the chunk at the last opening tag
							$offset = strrpos($chunk, $tag);
							$chunk = substr($chunk, $offset);
							$chunk = strstr($chunk, LD.$field_name);
							$content = substr($chunk, strlen($tag), -strlen(LD.'/'.$field_name.RD));
						}

						$params = ee()->functions->assign_parameters($params);
						$params = $params ? $params : array();

						$chunk_array = array(
							ltrim($modifier, ':'),
							$content,
							$params,
							$chunk
						);

						$pfield_chunk[$site_id][$field_name][] = $chunk_array;
					}
					
					$offset = $end + 1;
				}
			}
		}

		return $pfield_chunk;
	}

	// ------------------------------------------------------------------------

	/**
	 * Replace all of the custom channel pair fields.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The channel parser object
	 * @param Mixed		The results from the preparse method
	 *
	 * @return String	The processed tagdata
	 */
	public function replace($tagdata, EE_Channel_data_parser $obj, $pfield_chunks)
	{
		$data = $obj->row();
		$prefix = $obj->prefix();

		$site_id = $data['site_id'];

		$cfields = $obj->channel()->cfields;
		$cfields = isset($cfields[$site_id]) ? $cfields[$site_id] : array();

		if (empty($cfields) OR ! isset($pfield_chunks[$site_id]))
		{
			return $tagdata;
		}

		$pfield_chunk = $pfield_chunks[$site_id];
		$ft_api = ee()->api_channel_fields;

		foreach ($pfield_chunk as $tag_name => $chunks)
		{
			$field_name = preg_replace('/^'.$prefix.'/', '', $tag_name);
			$field_name = substr($field_name, strpos($field_name, ' '));
			$field_id = $cfields[$field_name];

			$obj = $ft_api->setup_handler($field_id, TRUE);

			if ($obj)
			{
				$_ft_path = $ft_api->ft_paths[$ft_api->field_type];
				ee()->load->add_package_path($_ft_path, FALSE);

				$obj->_init(array('row' => $data));
				$pre_processed = $obj->pre_process($data['field_id_'.$field_id]);

				foreach($chunks as $chk_data)
				{
					list($modifier, $content, $params, $chunk) = $chk_data;

					$tpl_chunk = '';
					// Set up parse function name based on whether or not
					// we have a modifier
					$parse_fnc = ($modifier) ? 'replace_'.$modifier : 'replace_tag';

					if (method_exists($obj, $parse_fnc))
					{
						$tpl_chunk = $obj->$parse_fnc($pre_processed, $params, $content);
					}
					// Go to catchall and include modifier
					elseif (method_exists($obj, 'replace_tag_catchall') AND $modifier !== '')
					{
						$tpl_chunk = $obj->replace_tag_catchall($pre_processed, $params, $content, $modifier);
					}

					$tagdata = str_replace($chunk, $tpl_chunk, $tagdata);
				}

				ee()->load->remove_package_path($_ft_path);
			}
		}

		return $tagdata;
	}
}