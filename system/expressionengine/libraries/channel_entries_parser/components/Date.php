<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
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
	 * Find all date variables in the template. The return value
	 * will be passed to process for the main parsing loop.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The preparser object.
	 * @return Array	Found date tags.
	 */
	public function pre_process($tagdata, EE_Channel_preparser $pre)
	{
		$prefix = $pre->prefix();

		$entry_date 		= array();
		$gmt_date 			= array();
		$gmt_entry_date		= array();
		$edit_date 			= array();
		$gmt_edit_date		= array();
		$expiration_date	= array();
		$week_date			= array();

		$date_vars = array('entry_date', 'gmt_date', 'gmt_entry_date', 'edit_date', 'gmt_edit_date', 'expiration_date', 'recent_comment_date', 'week_date');

		ee()->load->helper('date');

		foreach ($date_vars as $val)
		{
			if ( ! $pre->has_tag($val))
			{
				continue;
			}

			$full_val = $prefix.$val;

			if (preg_match_all("/".LD.$full_val.".*".RD."/s", $tagdata, $matches))
			{
				for ($j = 0; $j < count($matches[0]); $j++)
				{
					$matches[0][$j] = str_replace(array(LD,RD), '', $matches[0][$j]);

					switch ($val)
					{
						case 'entry_date':
							$entry_date[$matches[0][$j]] = TRUE;
							break;
						case 'gmt_date':
							$gmt_date[$matches[0][$j]] = TRUE;
							break;
						case 'gmt_entry_date':
							$gmt_entry_date[$matches[0][$j]] = TRUE;
							break;
						case 'edit_date':
							$edit_date[$matches[0][$j]] = TRUE;
							break;
						case 'gmt_edit_date':
							$gmt_edit_date[$matches[0][$j]] = TRUE;
							break;
						case 'expiration_date':
							$expiration_date[$matches[0][$j]] = TRUE;
							break;
						case 'recent_comment_date':
							$recent_comment_date[$matches[0][$j]] = TRUE;
							break;
						case 'week_date':
							$week_date[$matches[0][$j]] = TRUE;
							break;
					}
				}
			}
		}

		return call_user_func_array('compact', $date_vars);
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
		$tag = $obj->tag();
		$tag_options = $obj->tag_options();
		$data = $obj->row();
		$prefix = $obj->prefix();

		extract($date_vars);

		//  parse entry date
		if (isset($entry_date[$tag]))
		{
			$tagdata = ee()->TMPL->parse_date_variables($tagdata, array('entry_date' => $data['entry_date']));
		}

		//  Recent Comment Date
		elseif (isset($recent_comment_date[$tag]))
		{
			if ($data['recent_comment_date'] != 0)
			{
				$tagdata = ee()->TMPL->parse_date_variables($tagdata, array('recent_comment_date' => $data['recent_comment_date']));
			}
			else
			{
				$tagdata = str_replace(LD.$tag.RD, '', $tagdata);
			}
		}

		//  GMT date - entry date in GMT
		elseif (isset($gmt_entry_date[$tag]))
		{
			$tagdata = ee()->TMPL->parse_date_variables($tagdata, array('gmt_entry_date' => $data['entry_date']), FALSE);
		}

		elseif (isset($gmt_date[$tag]))
		{
			$tagdata = ee()->TMPL->parse_date_variables($tagdata, array('gmt_date' => $data['entry_date']), FALSE);
		}

		//  parse "last edit" date
		elseif (isset($edit_date[$tag]))
		{
			$tagdata = ee()->TMPL->parse_date_variables($tagdata, array('edit_date' => mysql_to_unix($data['edit_date'])));
		}

		//  "last edit" date as GMT
		elseif (isset($gmt_edit_date[$tag]))
		{
			$tagdata = ee()->TMPL->parse_date_variables($tagdata, array('gmt_edit_date' => mysql_to_unix($data['edit_date'])), FALSE);
		}


		//  parse expiration date
		elseif (isset($expiration_date[$tag]))
		{
			if ($data['expiration_date'] != 0)
			{
				$tagdata = ee()->TMPL->parse_date_variables($tagdata, array('expiration_date' => $data['expiration_date']));
			}
			else
			{
				$tagdata = str_replace(LD.$tag.RD, "", $tagdata);
			}
		}


		//  "week_date"
		elseif (isset($week_date[$tag]))
		{
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

			$week_start_date = $data['entry_date'] - (ee()->localize->format_date('%w', $data['entry_date'], TRUE) * 60 * 60 * 24) + $offset;

			$tagdata = ee()->TMPL->parse_date_variables($tagdata, array('week_date' => $week_start_date));
		}

		return $tagdata;
	}
}