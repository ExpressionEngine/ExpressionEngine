<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 4.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Channel Parser Component (Fluid Block)
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class EE_Channel_fluid_block_parser implements EE_Channel_parser_component {

	/**
	 * Check if Fluid Block is enabled
	 *
	 * @param array		A list of "disabled" features
	 * @return Boolean	Is disabled?
	 */
	public function disabled(array $disabled, EE_Channel_preparser $pre)
	{
		return empty($pre->channel()->fbfields) OR in_array('fluid_blocks', $disabled);
	}

	// --------------------------------------------------------------------

	/**
	 * Gather the data needed to process all Fluid Block fields
	 *
	 * The returned object will be passed to replace() as a third parameter.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The preparser object.
	 * @return NULL
	 */
	public function pre_process($tagdata, EE_Channel_preparser $pre)
	{
		$fluid_block_field_names = array();

		// Run the preprocessor for each site
		foreach ($pre->site_ids() as $site_id)
		{
			$fbfields = $pre->channel()->fbfields;

			// Skip a site if it has no Fluid Block fields
			if ( ! isset($fbfields[$site_id]) OR empty($fbfields[$site_id]))
			{
				continue;
			}

			$fluid_block_field_names = array_merge($fluid_block_field_names, array_values(array_flip($fbfields[$site_id])));

			ee()->load->library('fluid_block_parser');
			ee()->fluid_block_parser->pre_process($tagdata, $pre, $fbfields[$site_id]);
		}

		return NULL;
	}

	// ------------------------------------------------------------------------

	/**
	 * Replace all of the Fliud Block fields in one fell swoop.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The channel parser object
	 * @param Mixed		The results from the preparse method
	 *
	 * @return String	The processed tagdata
	 */
	public function replace($tagdata, EE_Channel_data_parser $obj, $fluid_block_parser)
	{
		return $tagdata;
	}
}