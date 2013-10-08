<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
	  *  Parse ID
	  */
	function parse_id($qstring, $dynamic = TRUE)
	{
		$entry_id = FALSE;
		
		if ($dynamic && preg_match("#^(\d+)(.*)#", $qstring, $match))
		{
			$seg = ( ! isset($match[2])) ? '' : $match[2];

			if (substr($seg, 0, 1) == "/" OR $seg == '')
			{
				$entry_id = $match[1];
				$qstring = trim_slashes(preg_replace("#^".$match[1]."#", '', $qstring));
			}
		}
		
		return array('entry_id' => $entry_id, 'qstring' => $qstring);		
	}
	
	// ------------------------------------------------------------------------

	/**
	  *  Parse Page Number
	  */
	function parse_page_number($qstring, $basepath, $uristr, $dynamic = TRUE)
	{
		$EE =& get_instance();
		
		$p_page = FALSE;
		$basepath = FALSE;
		$uristr = FALSE;
		
		if ($dynamic && preg_match("#^P(\d+)|/P(\d+)#", $qstring, $match)) 
		{
			$p_page = (isset($match[2])) ? $match[2] : $match[1];

			$basepath = reduce_double_slashes(str_replace($match[0], '', $basepath));

			$uristr  = reduce_double_slashes(str_replace($match[0], '', $uristr));

			$qstring = trim_slashes(str_replace($match[0], '', $qstring));

			//$page_marker = TRUE;
		}
		
		return array('p_page' => $p_page, 'basepath' => $basepath, 'uristr' => $uristr, 'qstring' => $qstring);
	}
	
		
	// ------------------------------------------------------------------------

	/**
	  *  Parse N Indicator
	  */
	function parse_n($qstring, $uristr, $dynamic = TRUE)
	{
		$uristr = FALSE;
		
		if (preg_match("#^N(\d+)|/N(\d+)#", $qstring, $match))
		{
			$uristr  = reduce_double_slashes(str_replace($match[0], '', $uristr));

			$qstring = trim_slashes(str_replace($match[0], '', $qstring));
		}
		
		return array('uristr' => $uristr, 'qstring' => $qstring);		
	}	

	/**
	 * Parse category ID from query string
	 *
	 * @param	string	$qstring Query string
	 * @return	string	URL title or ID of category, whichever is present in the URL
	 */
	function parse_category($qstring = '')
	{
		$EE =& get_instance();
		
		$reserved_category_word = (string) $EE->config->item("reserved_category_word");
		
		// Parse out URL title from query string
		if ($EE->config->item("use_category_name") == 'y' 
			&& $reserved_category_word != ''
			&& strpos($qstring, $reserved_category_word) !== FALSE 
		)
		{
			return preg_replace("/(.*?)\/".preg_quote($reserved_category_word)."\//i", '', '/'.$qstring);
		}
		// Parse out category ID in the format of CXX
		else if (preg_match("#(^|\/)C(\d+)#", $qstring, $match))
		{
			return $match[2];
		}

		return '';
	}


/* End of file snippets_helper.php */
/* Location: ./system/expressionengine/helpers/segment_helper.php */
