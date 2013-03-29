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
 * ExpressionEngine Channel Parser Plugin (Header and Footer)
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Channel_header_and_footer_parser implements EE_Channel_parser_component {

	public function disabled(array $disabled, EE_Channel_preparser $pre)
	{
		return ! ($pre->has_tag_pair('date_heading') OR $pre->has_tag_pair('date_footer'));
	}

	public function pre_process($tagdata, EE_Channel_preparser $pre)
	{
		return NULL;
	}

	public function replace($tagdata, EE_Channel_data_parser $obj, $pre)
	{
		$tag = $obj->tag();
		$tag_options = $obj->tag_options();
		$data = $obj->row();
		$prefix = $obj->prefix();

		// @todo
		$key = $tag;
		$val = $tag_options;

		//  parse date heading
		if (strncmp($key, 'date_heading', 12) == 0)
		{
			// Set the display preference

			$display = (is_array($val) AND isset($val['display'])) ? $val['display'] : 'daily';

			//  Hourly header
			if ($display == 'hourly')
			{
				$heading_date_hourly = get_instance()->localize->format_date('%Y%m%d%H', $data['entry_date']);

				if ($heading_date_hourly == $heading_flag_hourly)
				{
					$tagdata = get_instance()->TMPL->delete_var_pairs($key, 'date_heading', $tagdata);
				}
				else
				{
					$tagdata = get_instance()->TMPL->swap_var_pairs($key, 'date_heading', $tagdata);

					$heading_flag_hourly = $heading_date_hourly;
				}
			}
			//  Weekly header
			elseif ($display == 'weekly')
			{
				$temp_date = $data['entry_date'];

				// date()'s week variable 'W' starts weeks on Monday per ISO-8601.
				// By default we start weeks on Sunday, so we need to do a little dance for
				// entries made on Sundays to make sure they get placed in the right week heading
				if (strtolower(get_instance()->TMPL->fetch_param('start_day')) != 'monday' && get_instance()->localize->format_date('%w', $data['entry_date']) == 0)
				{
					// add 7 days to toss us into the next ISO-8601 week
					$temp_date = strtotime('+1 week', $temp_date);
				}

				$heading_date_weekly = get_instance()->localize->format_date('%Y%W', $temp_date);

				if ($heading_date_weekly == $heading_flag_weekly)
				{
					$tagdata = get_instance()->TMPL->delete_var_pairs($key, 'date_heading', $tagdata);
				}
				else
				{
					$tagdata = get_instance()->TMPL->swap_var_pairs($key, 'date_heading', $tagdata);

					$heading_flag_weekly = $heading_date_weekly;
				}
			}
			//  Monthly header
			elseif ($display == 'monthly')
			{
				$heading_date_monthly = get_instance()->localize->format_date('%Y%m', $data['entry_date']);

				if ($heading_date_monthly == $heading_flag_monthly)
				{
					$tagdata = get_instance()->TMPL->delete_var_pairs($key, 'date_heading', $tagdata);
				}
				else
				{
					$tagdata = get_instance()->TMPL->swap_var_pairs($key, 'date_heading', $tagdata);

					$heading_flag_monthly = $heading_date_monthly;
				}
			}
			//  Yearly header
			elseif ($display == 'yearly')
			{
				$heading_date_yearly = get_instance()->localize->format_date('%Y', $data['entry_date']);

				if ($heading_date_yearly == $heading_flag_yearly)
				{
					$tagdata = get_instance()->TMPL->delete_var_pairs($key, 'date_heading', $tagdata);
				}
				else
				{
					$tagdata = get_instance()->TMPL->swap_var_pairs($key, 'date_heading', $tagdata);

					$heading_flag_yearly = $heading_date_yearly;
				}
			}
			//  Default (daily) header
			else
			{
	 			$heading_date_daily = get_instance()->localize->format_date('%Y%m%d', $data['entry_date']);

				if ($heading_date_daily == $heading_flag_daily)
				{
					$tagdata = get_instance()->TMPL->delete_var_pairs($key, 'date_heading', $tagdata);
				}
				else
				{
					$tagdata = get_instance()->TMPL->swap_var_pairs($key, 'date_heading', $tagdata);

					$heading_flag_daily = $heading_date_daily;
				}
			}
		}
		// END DATE HEADING

		//  parse date footer
		if (strncmp($key, 'date_footer', 11) == 0)
		{
			// Set the display preference

			$display = (is_array($val) AND isset($val['display'])) ? $val['display'] : 'daily';

			//  Hourly footer
			if ($display == 'hourly')
			{
				if ( ! isset($query_result[$data['count']]) OR
					get_instance()->localize->format_date('%Y%m%d%H', $data['entry_date']) != get_instance()->localize->format_date('%Y%m%d%H', $query_result[$data['count']]['entry_date']))
				{
					$tagdata = get_instance()->TMPL->swap_var_pairs($key, 'date_footer', $tagdata);
				}
				else
				{
					$tagdata = get_instance()->TMPL->delete_var_pairs($key, 'date_footer', $tagdata);
				}
			}
			//  Weekly footer
			elseif ($display == 'weekly')
			{
				if ( ! isset($query_result[$data['count']]) OR
					get_instance()->localize->format_date('%Y%W', $data['entry_date']) != get_instance()->localize->format_date('%Y%W', $query_result[$data['count']]['entry_date']))
				{
					$tagdata = get_instance()->TMPL->swap_var_pairs($key, 'date_footer', $tagdata);
				}
				else
				{
					$tagdata = get_instance()->TMPL->delete_var_pairs($key, 'date_footer', $tagdata);
				}
			}
			//  Monthly footer
			elseif ($display == 'monthly')
			{
				if ( ! isset($query_result[$data['count']]) OR
					get_instance()->localize->format_date('%Y%m', $data['entry_date']) != get_instance()->localize->format_date('%Y%m', $query_result[$data['count']]['entry_date']))
				{
					$tagdata = get_instance()->TMPL->swap_var_pairs($key, 'date_footer', $tagdata);
				}
				else
				{
					$tagdata = get_instance()->TMPL->delete_var_pairs($key, 'date_footer', $tagdata);
				}
			}
			//  Yearly footer
			elseif ($display == 'yearly')
			{
				if ( ! isset($query_result[$data['count']]) OR
					get_instance()->localize->format_date('%Y', $data['entry_date']) != get_instance()->localize->format_date('%Y', $query_result[$data['count']]['entry_date']))
				{
					$tagdata = get_instance()->TMPL->swap_var_pairs($key, 'date_footer', $tagdata);
				}
				else
				{
					$tagdata = get_instance()->TMPL->delete_var_pairs($key, 'date_footer', $tagdata);
				}
			}
			//  Default (daily) footer
			else
			{
				if ( ! isset($query_result[$data['count']]) OR
					get_instance()->localize->format_date('%Y%m%d', $data['entry_date']) != get_instance()->localize->format_date('%Y%m%d', $query_result[$data['count']]['entry_date']))
				{
					$tagdata = get_instance()->TMPL->swap_var_pairs($key, 'date_footer', $tagdata);
				}
				else
				{
					$tagdata = get_instance()->TMPL->delete_var_pairs($key, 'date_footer', $tagdata);
				}
			}
		}
		// END DATE FOOTER

		return $tagdata;
	}
}