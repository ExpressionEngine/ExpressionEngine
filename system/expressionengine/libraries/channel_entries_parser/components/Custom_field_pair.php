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
 * ExpressionEngine Channel Parser Plugin (Custom Field Pairs)
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Channel_custom_field_pair_parser implements EE_Channel_parser_component {

	public function disabled(array $disabled, EE_Channel_preparser $pre)
	{
		return in_array('custom_fields', $disabled) OR empty($pre->channel()->pfields);
	}

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
				
				while (($end = strpos($tagdata, LD.'/'.$field_name.RD, $offset)) !== FALSE)
				{
					// This hurts soo much. Using custom fields as pair and single vars in the same
					// channel tags could lead to something like this: {field}...{field}inner{/field}
					// There's no efficient regex to match this case, so we'll find the last nested
					// opening tag and re-cut the chunk.

					if (preg_match("/".LD."{$field_name}(.*?)".RD."(.*?)".LD.'\/'."{$field_name}(.*?)".RD."/s", $tagdata, $matches, 0, $offset))
					{
						$chunk = $matches[0];
						$params = $matches[1];
						$inner = $matches[2];

						// We might've sandwiched a single tag - no good, check again (:sigh:)
						if ((strpos($chunk, LD.$field_name, 1) !== FALSE) && preg_match_all("/".LD."{$field_name}(.*?)".RD."/s", $chunk, $match))
						{
							// Let's start at the end
							$idx = count($match[0]) - 1;
							$tag = $match[0][$idx];
							
							// Reassign the parameter
							$params = $match[1][$idx];

							// Cut the chunk at the last opening tag (PHP5 could do this with strrpos :-( )
							while (strpos($chunk, $tag, 1) !== FALSE)
							{
								$chunk = substr($chunk, 1);
								$chunk = strstr($chunk, LD.$field_name);
								$inner = substr($chunk, strlen($tag), -strlen(LD.'/'.$field_name.RD));
							}
						}
						
						$chunk_array = array($inner, get_instance()->functions->assign_parameters($params), $chunk);
						
						// Grab modifier if it exists and add it to the chunk array
						if (substr($params, 0, 1) == ':')
						{
							$chunk_array[] = str_replace(':', '', $params);
						}
						
						$pfield_chunk[$site_id][$field_name][] = $chunk_array;
					}
					
					$offset = $end + 1;
				}
			}
		}

		return $pfield_chunk;
	}

	public function replace($tagdata, EE_Channel_data_parser $obj, $pfield_chunks)
	{
		$tag = $obj->tag();
		$data = $obj->row();
		$prefix = $obj->prefix();

		$site_id = $data['site_id'];

		$cfields = $obj->channel()->cfields[$site_id];
		$pfields = $obj->channel()->pfields[$site_id];

		if ( ! isset($pfield_chunks[$site_id]))
		{
			return $tagdata;
		}

		$pfield_chunk = $pfield_chunks[$site_id];

		$field_name = preg_replace('/^'.$prefix.'/', '', $tag);
		$field_name = substr($field_name, strpos($field_name, ' '));

		$ft_api = get_instance()->api_channel_fields;

		if (isset($cfields[$field_name]) && isset($pfields[$cfields[$field_name]]))
		{
			$field_id = $cfields[$field_name];
			$key_name = $pfields[$field_id];

			if ($ft_api->setup_handler($field_id))
			{
				$ft_api->apply('_init', array(array('row' => $data)));
				$pre_processed = $ft_api->apply('pre_process', array($data['field_id_'.$field_id]));

				// Blast through all the chunks
				if (isset($pfield_chunk[$prefix.$field_name]))
				{
					foreach($pfield_chunk[$prefix.$field_name] as $chk_data)
					{
						$tpl_chunk = '';
						// Set up parse function name based on whether or not
						// we have a modifier
						$parse_fnc = (isset($chk_data[3]))
							? 'replace_'.$chk_data[3] : 'replace_tag';

						if ($ft_api->check_method_exists($parse_fnc))
						{
							$tpl_chunk = $ft_api->apply(
								$parse_fnc,
								array($pre_processed, $chk_data[1], $chk_data[0])
							);
						}
						// Go to catchall and include modifier
						elseif ($ft_api->check_method_exists($parse_fnc_catchall)
							AND isset($chk_data[3]))
						{
							$tpl_chunk = $ft_api->apply(
								$parse_fnc_catchall,
								array($pre_processed, $chk_data[1], $chk_data[0], $chk_data[3])
							);
						}

						$tagdata = str_replace($chk_data[2], $tpl_chunk, $tagdata);
					}
				}
			}
		}

		return $tagdata;
	}
}