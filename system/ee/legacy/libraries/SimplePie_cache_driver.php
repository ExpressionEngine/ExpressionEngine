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
 * Caching Driver for SimplePie
 */
class EE_SimplePie_Cache_Driver implements SimplePie_Cache_Base
{
	/**
	 * Unique ID for the cache item
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Create a new cache object
	 *
	 * @param string $location Location string (from SimplePie::$cache_location)
	 * @param string $name Unique ID for the cache
	 * @param string $type Either TYPE_FEED for SimplePie data, or TYPE_IMAGE for image data
	 */
	public function __construct($location, $name, $type)
	{
		// Separate string to get the namespace
		$location = explode(':', $location, 2);

		if (isset($location[1]) && ! empty($location[1]))
		{
			$this->name = $location[1] . '/';
		}

		$this->name .= $name;
	}

	/**
	 * Save data to the cache
	 *
	 * @param array|SimplePie $data Data to store in the cache. If passed a SimplePie object, only cache the $data property
	 * @return bool Success status
	 */
	public function save($data)
	{
		if ($data instanceof SimplePie)
		{
			$data = $data->data;
		}

		return ee()->cache->save('/rss_parser/'.$this->name, $data, 0, Cache::GLOBAL_SCOPE);
	}

	/**
	 * Retrieve the data saved to the cache
	 *
	 * @return array Data for SimplePie::$data
	 */
	public function load()
	{
		return ee()->cache->get('/rss_parser/'.$this->name, Cache::GLOBAL_SCOPE);
	}

	/**
	 * Retrieve the last modified time for the cache
	 *
	 * @return int Timestamp
	 */
	public function mtime()
	{
		$info = ee()->cache->get_metadata('/rss_parser/'.$this->name, Cache::GLOBAL_SCOPE);

		if (is_array($info))
		{
			return $info['mtime'];
		}

		return FALSE;
	}

	/**
	 * Set the last modified time to the current time
	 *
	 * @return bool Success status
	 */
	public function touch()
	{
		$data = $this->load();

		if ($data !== FALSE)
		{
			return $this->save($data);
		}

		return FALSE;
	}

	/**
	 * Remove the cache
	 *
	 * @return bool Success status
	 */
	public function unlink()
	{
		return ee()->cache->delete('/rss_parser/'.$this->name, Cache::GLOBAL_SCOPE);
	}
}

// EOF
