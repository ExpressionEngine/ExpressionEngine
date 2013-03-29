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
 * ExpressionEngine Channel Parser Component (Relationships)
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Channel_relationship_parser implements EE_Channel_parser_component {

	public function disabled(array $disabled, EE_Channel_preparser $pre)
	{
		return empty($pre->channel()->zwfields) OR in_array('relationships', $disabled);
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