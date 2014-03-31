<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.6
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Channel Parser Component (Dates)
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Channel_date_parser implements EE_Channel_parser_component {

	/**
	 * Check if dates are enabled.
	 *
	 * @param array		A list of "disabled" features
	 * @return Boolean	Is disabled?
	 */
	public function disabled(array $disabled, EE_Channel_preparser $pre)
	{
		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Fulfilling the requirements of the abstract class we inherit from.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The preparser object.
	 * @return Array	An empty array.
	 */
	public function pre_process($tagdata, EE_Channel_preparser $pre)
	{
		return array();
	}

	// ------------------------------------------------------------------------

	/**
	 * Replace all of the default date fields.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The channel parser object
	 * @param Mixed		The results from the preparse method
	 *
	 * @return String	The processed tagdata
	 */
	public function replace($tagdata, EE_Channel_data_parser $obj, $date_vars)
	{
		$prefix = $obj->prefix();
		$tag = $obj->tag();
		$data = $obj->row();

		$dates = array(
			$prefix.'entry_date'          => $data['entry_date'],
			$prefix.'edit_date'           => mysql_to_unix($data['edit_date']),
			$prefix.'recent_comment_date' => ($data['recent_comment_date'] != 0) ? $data['recent_comment_date'] : '',
			$prefix.'expiration_date'     => ($data['expiration_date'] != 0) ? $data['expiration_date'] : ''
		);

		// "week_date"
		// Subtract the number of days the entry is "into" the week to get zero (Sunday)
		// If the entry date is for Sunday, and Monday is being used as the week's start day,
		// then we must back things up by six days

		$offset = 0;

		if (strtolower(ee()->TMPL->fetch_param('start_day')) == 'monday')
		{
			$day_of_week = ee()->localize->format_date('%w', $data['entry_date']);

			if ($day_of_week == '0')
			{
				$offset = -518400; // back six days
			}
			else
			{
				$offset = 86400; // plus one day
			}
		}

		$dates['week_date'] = $data['entry_date'] - (ee()->localize->format_date('%w', $data['entry_date'], TRUE) * 60 * 60 * 24) + $offset;

		$tagdata = ee()->TMPL->parse_date_variables($tagdata, $dates);

		$dates = array(
			$prefix.'gmt_date'       => $data['entry_date'],
			$prefix.'gmt_entry_date' => $data['entry_date'],
			$prefix.'gmt_edit_date'  => mysql_to_unix($data['edit_date']),
		);
		$tagdata = ee()->TMPL->parse_date_variables($tagdata, $dates, FALSE);

		return $tagdata;
	}
}