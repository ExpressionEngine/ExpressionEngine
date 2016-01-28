<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.6
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
 * @link		https://ellislab.com
 */
class EE_Channel_relationship_parser implements EE_Channel_parser_component {

	/**
	 * Check if relationships are enabled.
	 *
	 * @param array		A list of "disabled" features
	 * @return Boolean	Is disabled?
	 */
	public function disabled(array $disabled, EE_Channel_preparser $pre)
	{
		return empty($pre->channel()->rfields) OR in_array('relationships', $disabled);
	}

	// --------------------------------------------------------------------

	/**
	 * Set up the relationship parser's tree and data pre-caching.
	 *
	 * The returned object will be passed to replace() as a third parameter.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The preparser object.
	 * @return Array	The relationship parser object
	 */
	public function pre_process($tagdata, EE_Channel_preparser $pre)
	{
		if (empty($pre->channel()->rfields))
		{
			return NULL;
		}

		ee()->load->library('relationships_parser');

		try
		{
			return ee()->relationships_parser->create(
				$pre->channel()->rfields,
				$pre->entry_ids()
			);
		}
		catch (EE_Relationship_exception $e)
		{
			ee()->TMPL->log_item($e->getMessage());
		}

		return NULL;
	}

	// ------------------------------------------------------------------------

	/**
	 * Replace all of the relationship fields in one fell swoop.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The channel parser object
	 * @param Mixed		The results from the preparse method
	 *
	 * @return String	The processed tagdata
	 */
	public function replace($tagdata, EE_Channel_data_parser $obj, $relationship_parser)
	{
		if ( ! isset($relationship_parser))
		{
			return $tagdata;
		}

		$row = $obj->row();
		$channel = $obj->channel();

		try
		{
			return $relationship_parser->parse($row['entry_id'], $tagdata, $channel);
		}
		catch (EE_Relationship_exception $e)
		{
			return $tagdata;
		}
	}
}

// EOF
