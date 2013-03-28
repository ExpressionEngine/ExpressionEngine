<?php

class EE_Channel_relationship_parser implements EE_Channel_parser_plugin {

	public function understands($tag)
	{
		return TRUE;
	}
	
	public function pre_process($tagdata, EE_Channel_preparser $pre)
	{
		$channel = $pre->channel();

		$zwfields = $channel->zwfields;
		$cfields = $channel->cfields;

		$site_id = config_item('site_id');

		if (isset($zwfields[$site_id]) && ! empty($zwfields[$site_id]))
		{
			get_instance()->load->library('relationships');
			$relationship_parser = get_instance()->relationships->get_relationship_parser(get_instance()->TMPL, $zwfields[$site_id], $cfields[$site_id]);
			$relationship_parser->query_for_entries($pre->entry_ids());

			return $relationship_parser;
		}

		return NULL;
	}

	public function replace($tagdata, EE_Channel_data_parser $obj, $relationship_parser)
	{
		if ( ! isset($relationship_parser))
		{
			return $tagdata;
		}

		$row = $obj->row();
		$channel = $obj->channel();

		return $relationship_parser->parse_relationships($row['entry_id'], $tagdata, $channel);
	}
}