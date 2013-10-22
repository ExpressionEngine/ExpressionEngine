<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.8
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine RSS Parser Factory Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_RSS_Parser {

	public function __construct()
	{
		// Load in the necessary files
		require_once(APPPATH.'libraries/simplepie/SimplePieAutoloader.php');
		require_once(APPPATH.'libraries/simplepie/idn/idna_convert.class.php');
	}

	// -------------------------------------------------------------------------

	/**
	 * Create a SimplePie object
	 * @param  string  $url        URL of the RSS feed to parse
	 * @param  integer $duration   Length of the cache in minutes
	 * @param  string  $cache_name Name of the cache directory within /cache
	 * @return Object              SimplePie object
	 */
	public function create($url, $duration = 180, $cache_name = '')
	{
		$feed = new SimplePie();
		$feed->set_feed_url($url);

		if (empty($cache_name))
		{
			$cache_name = md5('rss_parser'.$url);
		}

		// Establish the cache
		$this->_check_cache($cache_name);
		$feed->set_cache_location(APPPATH.'cache/'.$cache_name.'/');
		$feed->set_cache_duration($duration * 60); // Get parameter to seconds

		return $feed;
	}

	// -------------------------------------------------------------------------

	/**
	 * Check to make sure the cache exists and create it otherwise
	 * @param string  $cache_name Name of the cache directory within /cache
	 * @return void
	 */
	private function _check_cache($cache_name)
	{
		// Make sure the cache directory exists and is writeable
		if ( ! @is_dir(APPPATH.'cache/'.$cache_name))
		{
			if ( ! @mkdir(APPPATH.'cache/'.$cache_name, DIR_WRITE_MODE))
			{
				ee()->TMPL->log_item("RSS Parser Error: Cache directory unwritable.");
				return ee()->TMPL->no_results();
			}
		}
	}
}
// END CLASS

/* End of file rss_parser.php */
/* Location: ./system/expressionengine/libraries/rss_parser.php */