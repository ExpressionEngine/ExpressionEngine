<?php


class EE_Channel_custom_date_parser implements EE_Channel_parser_plugin {

	public function understands($tag)
	{
		return TRUE;
	}

	public function replace($tagdata, EE_Channel_data_parser $obj)
	{
		$tag = $obj->tag();
		$tag_options = $obj->tag_options();
		$data = $obj->row();
		$prefix = $obj->prefix();

		$dfields = $obj->channel()->dfields;
		$custom_date_fields = $obj->preparsed()->custom_date_fields;

		if (isset($custom_date_fields[$tag]) && isset($dfields[$data['site_id']]))
		{
			$prefix = $this->_prefix;

			foreach ($dfields[$data['site_id']] as $dtag => $dval)
			{
				if (strncmp($tag.' ', $dtag.' ', strlen($dtag.' ')) !== 0)
				{
					continue;
				}

				if ($data['field_id_'.$dval] == 0 OR $data['field_id_'.$dval] == '')
				{
					$tagdata = str_replace(LD.$prefix.$tag.RD, '', $tagdata);
					continue;
				}

				// If date is fixed, get timezone to convert timestamp to,
				// otherwise localize it normally
				$localize = (isset($data['field_dt_'.$dval]) AND $data['field_dt_'.$dval] != '')
					? $data['field_dt_'.$dval] : TRUE;

				$tagdata = str_replace(
					LD.$prefix.$tag.RD,
					get_instance()->localize->format_date(
						$custom_date_fields[$tag],
						$data['field_id_'.$dval], 
						$localize
					),
					$tagdata
				);
			}
		}

		return $tagdata;
	}
}

