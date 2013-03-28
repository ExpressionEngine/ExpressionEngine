<?php

class EE_Channel_custom_field_pair_parser implements EE_Channel_parser_plugin {

	public function understands($tag)
	{
		return TRUE;
	}


	public function replace($tagdata, EE_Channel_data_parser $obj)
	{
		$tag = $obj->tag();
		$data = $obj->row();
		$prefix = $obj->prefix();

		$site_id = $data['site_id'];

		$cfields = $obj->channel()->cfields[$site_id];
		$pfields = $obj->channel()->pfields[$site_id];

		$pfield_chunks = $obj->preparsed()->pfield_chunks;

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