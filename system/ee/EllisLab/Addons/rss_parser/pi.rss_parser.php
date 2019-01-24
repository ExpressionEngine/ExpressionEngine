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
 * RSS Parser Plugin
 */
Class Rss_parser {

	private $cache_name = 'rss_parser_plugin';

	public function __construct()
	{
		// Fetch Parameters and set defaults
		$url		= ee()->TMPL->fetch_param('url');
		$limit		= (int) ee()->TMPL->fetch_param('limit', 10);
		$offset		= (int) ee()->TMPL->fetch_param('offset', 0);
		$refresh	= (int) ee()->TMPL->fetch_param('refresh', 180);

		// Bring in SimplePie
		ee()->load->library('rss_parser');

		// Attempt to create the feed, if there's an exception try to replace
		// {if feed_error} and {feed_error}
		try
		{
			$feed = ee()->rss_parser->create($url, $refresh, $this->cache_name);
		}
		catch (Exception $e)
		{
			return $this->return_data = ee()->TMPL->exclusive_conditional(
				ee()->TMPL->tagdata,
				'feed_error',
				array(array('feed_error' => $e->getMessage()))
			);
		}

		// Make sure there's at least one item
		if ($feed->get_item_quantity() <= 0)
		{
			$this->return_data = ee()->TMPL->no_results();
		}

		$content = array(
			'feed_items' 		=> $this->_map_feed_items($feed, $limit, $offset),

			// Feed Information
			'feed_title'		=> $feed->get_title(),
			'feed_link'			=> $feed->get_link(),
			'feed_copyright'	=> $feed->get_copyright(),
			'feed_description'	=> $feed->get_description(),
			'feed_language'		=> $feed->get_language(),

			// Feed Logo Information
			'logo_url'			=> $feed->get_image_url(),
			'logo_title'		=> $feed->get_image_title(),
			'logo_link'			=> $feed->get_image_link(),
			'logo_width'		=> $feed->get_image_width(),
			'logo_height'		=> $feed->get_image_height()
		);

		$this->return_data = ee()->TMPL->parse_variables(
			ee()->TMPL->tagdata,
			array($content)
		);
	}

	/**
	 * Map Feed Items to array
	 * @param SimplePie $feed SimplePie feed object
	 * @param int $limit Number of items to return
	 * @param int $offset Number of items to skip
	 * @return mapped associative array containing all feed items
	 */
	private function _map_feed_items($feed, $limit, $offset)
	{
		$items = array();

		foreach ($feed->get_items($offset, $limit) as $index => $item)
		{
			// Get Categories
			$categories = array();
			$item_categories = $item->get_categories();
			if ( ! empty($item_categories))
			{
				foreach ($item_categories as $category)
				{
					$categories[] = array(
						'category_name' => $category->get_term()
					);
				}
			}

			// Get Authors
			$authors = array();
			$item_authors = $item->get_authors();
			if ( ! empty($item_authors))
			{
				foreach ($item_authors as $author)
				{
					$authors[] = array(
						'author_email'	=> $author->get_email(),
						'author_link'	=> $author->get_link(),
						'author_name'	=> $author->get_name()
					);
				}
			}

			$items[] = array(
				'item_title'		=> $item->get_title(),
				'item_link'			=> $item->get_permalink(),
				'item_date'			=> $item->get_date('U'),
				'item_content'		=> $item->get_content(),
				'item_description'	=> $item->get_description(),
				'item_categories'	=> $categories,
				'item_authors'		=> $authors
			);
		}

		return $items;
	}
}

// EOF
