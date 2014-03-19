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
 * ExpressionEngine Referrer Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Referrer {

	/**
	 * Constructor
	 */
	function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Log Referrer data
	 *
	 * @access	public
	 * @return	bool
	 */
	function log_referrer()
	{
		// Is the nation of the user banend?
		if (ee()->config->item('ip2nation') == 'y' &&
			ee()->session->nation_ban_check(FALSE) === FALSE)
		{
			return;
		}

		if (ee()->config->item('log_referrers') == 'n' OR ! isset($_SERVER['HTTP_REFERER']))
		{
			return;
		}

		// Load the typography helper so we can do entity_decode()
		ee()->load->helper('typography');

		$site_url 	= ee()->config->item('site_url');
		$ref 		= ( ! isset($_SERVER['HTTP_REFERER'])) ? '' : ee()->security->xss_clean(entity_decode($_SERVER['HTTP_REFERER']));
		$test_ref	= strtolower($ref); // Yes, a copy, not a reference
		$domain		= ( ! ee()->config->item('cookie_domain')) ? '' : ee()->config->item('cookie_domain');

		// Throttling - Ten hits a minute is the limit
		$query = ee()->db->query("SELECT COUNT(*) AS count
							 FROM exp_referrers
							 WHERE site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'
							 AND (ref_from = '".ee()->db->escape_str($ref)."' OR ref_ip = '".ee()->input->ip_address()."')
							 AND ref_date > '".(ee()->localize->now-60)."'");

		if ($query->row('count')  > 10)
		{
			return FALSE;
		}

		if (stristr($ref, '{') !== FALSE OR stristr($ref, '}') !== FALSE)
		{
			return FALSE;
		}

		if ( ! preg_match("#^http://\w+\.\w+\.\w*#", $ref))
		{
			if (substr($test_ref, 0, 7) == 'http://' AND substr($test_ref, 0, 11) != 'http://www.')
			{
				$test_ref = preg_replace("#^http://(.+?)#", "http://www.\\1", $test_ref);
			}
		}

		if ( ! preg_match("#^http://\w+\.\w+\.\w*#", $site_url))
		{
			if (substr($site_url, 0, 7) == 'http://' AND substr($site_url, 0, 11) != 'http://www.')
			{
				$site_url = preg_replace("#^http://(.+?)#", "http://www.\\1", $site_url);
			}
		}

		if ($test_ref != ''
			&& strncasecmp($test_ref, $site_url, strlen($site_url)) != 0
			&& ($domain == '' OR stristr($test_ref, $domain) === FALSE)
			&& (ee()->blacklist->whitelisted == 'y' OR ee()->blacklist->blacklisted == 'n'))
		{

			// INSERT into database
			$ref_to = ee()->security->xss_clean(ee()->functions->fetch_current_uri());

			if (stristr($ref_to, '{') !== FALSE OR stristr($ref_to, '}') !== FALSE)
			{
				return FALSE;
			}

			$insert_data = array (  'ref_from' 		=> $ref,
									'ref_to'  		=> $ref_to,
									'ref_ip'		=> ee()->input->ip_address(),
									'ref_date'		=> ee()->localize->now,
									'ref_agent'		=> substr(ee()->input->user_agent(), 0, 100), // db field is 100 chararacters, truncate for MySQL strict mode compat
									'site_id'		=> ee()->config->item('site_id')
									);

			ee()->db->query(ee()->db->insert_string('exp_referrers', $insert_data));

			// Prune Database
			srand(time());
			if ((rand() % 100) < 5)
			{
				$max = ( ! is_numeric(ee()->config->item('max_referrers'))) ? 500 : ee()->config->item('max_referrers');

				$query = ee()->db->query("SELECT MAX(ref_id) as ref_id FROM exp_referrers WHERE site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'");

				$row = $query->row_array();

				if (isset($row['ref_id'] ) && $row['ref_id']  > $max)
				{
					ee()->db->query("DELETE FROM exp_referrers WHERE site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."' AND ref_id < ".($row['ref_id'] -$max)."");
				}
			}
		}
	}

}


/* End of file Referrer.php */
/* Location: ./system/expressionengine/libraries/Referrer.php */