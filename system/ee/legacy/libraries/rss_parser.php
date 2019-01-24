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
 * RSS Parser Factory
 */
class EE_RSS_Parser {

	public function __construct()
	{
		// Load in the necessary files
		require_once(APPPATH.'libraries/simplepie/SimplePieAutoloader.php');
		require_once(APPPATH.'libraries/simplepie/idn/idna_convert.class.php');
		require_once(APPPATH.'libraries/SimplePie_cache_driver.php');
	}

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

		// Load our own caching driver for SimplePie
		$feed->registry->call(
			'Cache',
			'register',
			array('ee', 'EE_SimplePie_Cache_driver')
		);

		// Set the timeout lower
		$feed->set_timeout(3);

		// Establish the cache
		$feed->set_cache_location('ee:' . $cache_name);
		$feed->set_cache_duration($duration * 60); // Get parameter to seconds

		// Check to see if the feed was initialized, if so, deal with the type
		$success = $feed->init();
		$feed->handle_content_type();

		if ($success)
		{
			return $feed;
		}

		throw new Exception("RSS Parser Error: ".$feed->error());
	}
}
// END CLASS

// EOF
