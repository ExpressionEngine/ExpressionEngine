<?php

//  parse {switch} variable
class EE_Channel_switch_parser implements EE_Channel_parser_plugin {

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
		$prefix = $obj->prefix();

		if (preg_match("/^".$prefix."switch\s*=.+/i", $tag))
		{
			$count = $obj->count();
			$sparam = get_instance()->functions->assign_parameters($tag);

			$sw = '';

			if (isset($sparam[$prefix.'switch']))
			{
				$sopt = explode("|", $sparam[$prefix.'switch']);

				$sw = $sopt[($count + count($sopt)) % count($sopt)];
			}

			$tagdata = str_replace(LD.$tag.RD, $sw, $tagdata);
		}

		return $tagdata;
	}
}