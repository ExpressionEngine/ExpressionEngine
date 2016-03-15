<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.7
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Channel Parser Component (Grid)
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class EE_Channel_grid_parser implements EE_Channel_parser_component {

	/**
	 * Check if Grid is enabled
	 *
	 * @param array		A list of "disabled" features
	 * @return Boolean	Is disabled?
	 */
	public function disabled(array $disabled, EE_Channel_preparser $pre)
	{
		return empty($pre->channel()->gfields) OR in_array('grid', $disabled);
	}

	// --------------------------------------------------------------------

	/**
	 * Gather the data needed to process all Grid field
	 *
	 * The returned object will be passed to replace() as a third parameter.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The preparser object.
	 * @return Object	EE_Grid_field_parser object
	 */
	public function pre_process($tagdata, EE_Channel_preparser $pre)
	{
		$grid_field_names = array();

		// Run the preprocessor for each site
		foreach ($pre->site_ids() as $site_id)
		{
			$gfields = $pre->channel()->gfields;

			// Skip a site if it has no Grid fields
			if ( ! isset($gfields[$site_id]) OR empty($gfields[$site_id]))
			{
				continue;
			}

			$grid_field_names = array_merge($grid_field_names, array_values(array_flip($gfields[$site_id])));

			ee()->load->library('grid_parser');
			ee()->grid_parser->pre_process($tagdata, $pre, $gfields[$site_id]);
		}

		$grid_field_names = $pre->prefix().implode('|'.$pre->prefix(), $grid_field_names);

		// Match all conditionals with these Grid field names so we can
		// make {if grid_field} work
		preg_match_all("/".preg_quote(LD)."((if:(else))*if)\s+($grid_field_names)(?!:)(\s+|".preg_quote(RD).")/s", $tagdata, $matches);

		// For each field found in a conditional, add it to the modified
		// conditionals array to make the conditional evaluate with the
		// :total_rows modifier, otherwise it will evaluate based on what's
		// in channel_data, and only data from searchable fields is there
		if (isset($matches[4]) && ! empty($matches[4]))
		{
			foreach ($matches[4] as $value)
			{
				$pre->modified_conditionals[$value][] = 'total_rows';
			}
		}

		return NULL;
	}

	// ------------------------------------------------------------------------

	/**
	 * Replace all of the Grid fields in one fell swoop.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The channel parser object
	 * @param Mixed		The results from the preparse method
	 *
	 * @return String	The processed tagdata
	 */
	public function replace($tagdata, EE_Channel_data_parser $obj, $grid_parser)
	{
		return $tagdata;
	}
}