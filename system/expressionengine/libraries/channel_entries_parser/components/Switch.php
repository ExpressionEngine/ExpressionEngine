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
 * ExpressionEngine Channel Parser Plugin (Switch)
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Channel_switch_parser implements EE_Channel_parser_component {

	public function disabled(array $disabled, EE_Channel_preparser $pre)
	{
		return ! $pre->has_tag('switch');
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