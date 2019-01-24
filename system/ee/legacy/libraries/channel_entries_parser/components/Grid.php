<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Channel Parser Component (Grid)
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
		// Run the preprocessor for each site
		foreach ($pre->site_ids() as $site_id)
		{
			$gfields = $pre->channel()->gfields;

			// Skip a site if it has no Grid fields
			if ( ! isset($gfields[$site_id]) OR empty($gfields[$site_id]))
			{
				continue;
			}

			ee()->load->library('grid_parser');
			ee()->grid_parser->pre_process($tagdata, $pre, $gfields[$site_id]);
		}

		return NULL;
	}

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
