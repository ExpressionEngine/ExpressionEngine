<?php

class EE_Channel_custom_member_field_parser implements EE_Channel_parser_plugin {

	public function understands($tag)
	{
		return TRUE;
	}

	public function pre_process($tagdata, EE_Channel_preparser $pre)
	{
		return NULL;
	}

	public function replace($tagdata, EE_Channel_data_parser $obj, $pre)
	{
		$tag = $obj->tag();
		$tag_options = $obj->tag_options();
		$data = $obj->row();
		$prefix = $obj->prefix();

		// @todo
		$key = $tag;
		$val = $tag_options;

		$mfields = $obj->channel()->mfields;

		//  parse custom member fields
		if (isset($mfields[$val]) && array_key_exists('m_field_id_'.$value[0], $data))
		{
			if ( ! isset($processed_member_fields[$data['member_id']]['m_field_id_'.$mfields[$val][0]]))
			{
				$processed_member_fields[$data['member_id']]['m_field_id_'.$mfields[$val][0]] =

				get_instance()->typography->parse_type(
					$data['m_field_id_'.$mfields[$val][0]],
					array(
						'text_format'	=> $mfields[$val][1],
						'html_format'	=> 'safe',
						'auto_links'	=> 'y',
						'allow_img_url' => 'n'
					)
				);
			}

			$tagdata = str_replace(
				LD.$prefix.$val.RD,
				$processed_member_fields[$data['member_id']]['m_field_id_'.$mfields[$val][0]],
				$tagdata
			);
		}


		return $tagdata;
	}
}
