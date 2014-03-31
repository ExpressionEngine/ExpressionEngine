<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Segment Helper
 *
 * @package		ExpressionEngine
 * @subpackage	Helpers
 * @category	Helpers
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

// ------------------------------------------------------------------------

	/**
	  *  Parse Day
	  */
	function parse_day($qstring, $dynamic = TRUE)
	{
		if ($dynamic && preg_match("#(^|\/)(\d{4}/\d{2}/\d{2})#", $qstring, $match))
		{
			$ex = explode('/', $match[2]);

			$year  = $ex[0];
			$month = $ex[1];
			$day   = $ex[2];

			$qstring = trim_slashes(str_replace($match[0], '', $qstring));

		}

		return array('year' => $year, 'month' => $month, 'day' => $day, 'qstring' => $qstring);
	}

	// ------------------------------------------------------------------------

	/**
	  *  Parse Year and Month
	  */
	function parse_year_month($qstring, $dynamic = TRUE)
	{
		// added (^|\/) to make sure this doesn't trigger with url titles like big_party_2006
		if ($dynamic && preg_match("#(^|\/)(\d{4}/\d{2})(\/|$)#", $qstring, $match))
		{
			$ex = explode('/', $match[2]);

			$year	= $ex[0];
			$month	= $ex[1];

			$qstring = trim_slashes(str_replace($match[2], '', $qstring));
		}

		return array('year' => $year, 'month' => $month, 'qstring' => $qstring);
	}

	// ------------------------------------------------------------------------

	/**
	 * Parse category ID from query string
	 *
	 * @param	string	$qstring Query string
	 * @return	string	ID of the category regardless of type being used
	 */
	function parse_category($query_string)
	{
		$reserved_category_word = (string) ee()->config->item("reserved_category_word");

		// Parse out URL title from query string
		if ($reserved_category_word != ''
			&& strpos($query_string, $reserved_category_word) !== FALSE
		)
		{
			$split = explode('/', $query_string);
			foreach ($split as $index => $value)
			{
				if ($value == $reserved_category_word && isset($split[$index + 1]))
				{
					$category_name = $split[$index + 1];
					break;
				}
			}

			if (empty($category_name))
			{
				return '';
			}

			ee()->load->model('category_model');
			return ee()->category_model->get_category_id($category_name);
		}
		// Parse out category ID in the format of CXX
		else if (preg_match("#(^|\/)C(\d+)#", $query_string, $match))
		{
			return $match[2];
		}

		return '';
	}


/* End of file snippets_helper.php */
/* Location: ./system/expressionengine/helpers/segment_helper.php */
