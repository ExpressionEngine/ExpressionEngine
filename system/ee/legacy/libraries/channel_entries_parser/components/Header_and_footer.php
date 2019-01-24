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
 * Channel Parser Component (Header and Footer)
 */
class EE_Channel_header_and_footer_parser implements EE_Channel_parser_component {

	/**
	 * Check if header/footer is enabled.
	 *
	 * @param array		A list of "disabled" features
	 * @return Boolean	Is disabled?
	 */
	public function disabled(array $disabled, EE_Channel_preparser $pre)
	{
		return ! ($pre->has_tag_pair('date_heading') OR $pre->has_tag_pair('date_footer'));
	}

	/**
	 * Reset flags for this tag chunk. Using an object so that we can
	 * modify it between loops.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The preparser object.
	 * @return Array	Flags for this template
	 */
	public function pre_process($tagdata, EE_Channel_preparser $pre)
	{
		//  Set default date header variables
		$c = new StdClass;
		$c->heading_date_hourly  = 0;
		$c->heading_flag_hourly  = 0;
		$c->heading_flag_weekly  = 1;
		$c->heading_date_daily	 = 0;
		$c->heading_flag_daily	 = 0;
		$c->heading_date_monthly = 0;
		$c->heading_flag_monthly = 0;
		$c->heading_date_yearly  = 0;
		$c->heading_flag_yearly  = 0;
		return $c;
	}

	/**
	 * Replace all of the header/footer chunks.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The channel parser object
	 * @param Mixed		The results from the preparse method
	 *
	 * @return String	The processed tagdata
	 */
	public function replace($tagdata, EE_Channel_data_parser $obj, $flag_obj)
	{
		$tag = $obj->tag();
		$tag_options = $obj->tag_options();
		$data = $obj->row();
		$prefix = $obj->prefix();

		// @todo
		$val = $tag_options;

		//  parse date heading
		if (strncmp($tag, $prefix.'date_heading', strlen($prefix) + 12) == 0)
		{
			// Set the display preference

			$display = (is_array($val) AND isset($val['display'])) ? $val['display'] : 'daily';

			//  Hourly header
			if ($display == 'hourly')
			{
				$flag_obj->heading_date_hourly = ee()->localize->format_date('%Y%m%d%H', $data['entry_date']);

				if ($flag_obj->heading_date_hourly == $flag_obj->heading_flag_hourly)
				{
					$tagdata = ee()->TMPL->delete_var_pairs($tag, $prefix.'date_heading', $tagdata);
				}
				else
				{
					$tagdata = ee()->TMPL->swap_var_pairs($tag, $prefix.'date_heading', $tagdata);

					$flag_obj->heading_flag_hourly = $flag_obj->heading_date_hourly;
				}
			}
			//  Weekly header
			elseif ($display == 'weekly')
			{
				$temp_date = $data['entry_date'];

				// date()'s week variable 'W' starts weeks on Monday per ISO-8601.
				// By default we start weeks on Sunday, so we need to do a little dance for
				// entries made on Sundays to make sure they get placed in the right week heading
				if (strtolower(ee()->TMPL->fetch_param('start_day')) != 'monday' && ee()->localize->format_date('%w', $data['entry_date']) == 0)
				{
					// add 7 days to toss us into the next ISO-8601 week
					$temp_date = strtotime('+1 week', $temp_date);
				}

				$flag_obj->heading_date_weekly = ee()->localize->format_date('%Y%W', $temp_date);

				if ($flag_obj->heading_date_weekly == $flag_obj->heading_flag_weekly)
				{
					$tagdata = ee()->TMPL->delete_var_pairs($tag, $prefix.'date_heading', $tagdata);
				}
				else
				{
					$tagdata = ee()->TMPL->swap_var_pairs($tag, $prefix.'date_heading', $tagdata);

					$flag_obj->heading_flag_weekly = $flag_obj->heading_date_weekly;
				}
			}
			//  Monthly header
			elseif ($display == 'monthly')
			{
				$flag_obj->heading_date_monthly = ee()->localize->format_date('%Y%m', $data['entry_date']);

				if ($flag_obj->heading_date_monthly == $flag_obj->heading_flag_monthly)
				{
					$tagdata = ee()->TMPL->delete_var_pairs($tag, $prefix.'date_heading', $tagdata);
				}
				else
				{
					$tagdata = ee()->TMPL->swap_var_pairs($tag, $prefix.'date_heading', $tagdata);

					$flag_obj->heading_flag_monthly = $flag_obj->heading_date_monthly;
				}
			}
			//  Yearly header
			elseif ($display == 'yearly')
			{
				$flag_obj->heading_date_yearly = ee()->localize->format_date('%Y', $data['entry_date']);

				if ($flag_obj->heading_date_yearly == $flag_obj->heading_flag_yearly)
				{
					$tagdata = ee()->TMPL->delete_var_pairs($tag, $prefix.'date_heading', $tagdata);
				}
				else
				{
					$tagdata = ee()->TMPL->swap_var_pairs($tag, $prefix.'date_heading', $tagdata);

					$flag_obj->heading_flag_yearly = $flag_obj->heading_date_yearly;
				}
			}
			//  Default (daily) header
			else
			{
	 			$flag_obj->heading_date_daily = ee()->localize->format_date('%Y%m%d', $data['entry_date']);

				if ($flag_obj->heading_date_daily == $flag_obj->heading_flag_daily)
				{
					$tagdata = ee()->TMPL->delete_var_pairs($tag, $prefix.'date_heading', $tagdata);
				}
				else
				{
					$tagdata = ee()->TMPL->swap_var_pairs($tag, $prefix.'date_heading', $tagdata);

					$flag_obj->heading_flag_daily = $flag_obj->heading_date_daily;
				}
			}
		}
		// END DATE HEADING

		//  parse date footer
		if (strncmp($tag, $prefix.'date_footer', strlen($prefix) + 11) == 0)
		{
			// Set the display preference

			$display = (is_array($val) AND isset($val['display'])) ? $val['display'] : 'daily';
			$query_result = array_values($obj->data('entries', array()));

			//  Hourly footer
			if ($display == 'hourly')
			{
				if ( ! isset($query_result[$data['count']]) OR
					ee()->localize->format_date('%Y%m%d%H', $data['entry_date']) != ee()->localize->format_date('%Y%m%d%H', $query_result[$data['count']]['entry_date']))
				{
					$tagdata = ee()->TMPL->swap_var_pairs($tag, $prefix.'date_footer', $tagdata);
				}
				else
				{
					$tagdata = ee()->TMPL->delete_var_pairs($tag, $prefix.'date_footer', $tagdata);
				}
			}
			//  Weekly footer
			elseif ($display == 'weekly')
			{
				$temp_date = $data['entry_date'];
				$temp_date_compare = (isset($query_result[$data['count']]['entry_date'])) ? $query_result[$data['count']]['entry_date'] : '';

				// We adjust for date()'s week variable 'W' Monday start
				if (strtolower(ee()->TMPL->fetch_param('start_day')) != 'monday')
				{
					if (ee()->localize->format_date('%w', $temp_date) == 0)
					{
						// add 7 days to toss us into the next ISO-8601 week
						$temp_date = strtotime('+1 week', $temp_date);
					}
					if (ee()->localize->format_date('%w', $temp_date_compare) == 0)
					{
						// add 7 days to toss us into the next ISO-8601 week
						$temp_date_compare = strtotime('+1 week', $temp_date_compare);
					}
				}

				if ( ! isset($query_result[$data['count']]) OR
					ee()->localize->format_date('%Y%W', $temp_date) != ee()->localize->format_date('%Y%W', $temp_date_compare))
				{
					$tagdata = ee()->TMPL->swap_var_pairs($tag, $prefix.'date_footer', $tagdata);
				}
				else
				{
					$tagdata = ee()->TMPL->delete_var_pairs($tag, $prefix.'date_footer', $tagdata);
				}
			}
			//  Monthly footer
			elseif ($display == 'monthly')
			{
				if ( ! isset($query_result[$data['count']]) OR
					ee()->localize->format_date('%Y%m', $data['entry_date']) != ee()->localize->format_date('%Y%m', $query_result[$data['count']]['entry_date']))
				{
					$tagdata = ee()->TMPL->swap_var_pairs($tag, $prefix.'date_footer', $tagdata);
				}
				else
				{
					$tagdata = ee()->TMPL->delete_var_pairs($tag, $prefix.'date_footer', $tagdata);
				}
			}
			//  Yearly footer
			elseif ($display == 'yearly')
			{
				if ( ! isset($query_result[$data['count']]) OR
					ee()->localize->format_date('%Y', $data['entry_date']) != ee()->localize->format_date('%Y', $query_result[$data['count']]['entry_date']))
				{
					$tagdata = ee()->TMPL->swap_var_pairs($tag, $prefix.'date_footer', $tagdata);
				}
				else
				{
					$tagdata = ee()->TMPL->delete_var_pairs($tag, $prefix.'date_footer', $tagdata);
				}
			}
			//  Default (daily) footer
			else
			{
				if ( ! isset($query_result[$data['count']]) OR
					ee()->localize->format_date('%Y%m%d', $data['entry_date']) != ee()->localize->format_date('%Y%m%d', $query_result[$data['count']]['entry_date']))
				{
					$tagdata = ee()->TMPL->swap_var_pairs($tag, $prefix.'date_footer', $tagdata);
				}
				else
				{
					$tagdata = ee()->TMPL->delete_var_pairs($tag, $prefix.'date_footer', $tagdata);
				}
			}
		}
		// END DATE FOOTER

		return $tagdata;
	}
}
