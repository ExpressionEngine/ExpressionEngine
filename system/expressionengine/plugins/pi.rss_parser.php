<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
Copyright (C) 2011 EllisLab, Inc.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
ELLISLAB, INC. BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

Except as contained in this notice, the name of EllisLab, Inc. shall not be
used in advertising or otherwise to promote the sale, use or other dealings
in this Software without prior written authorization from EllisLab, Inc.
*/

$plugin_info = array(
	'pi_name'			=> 'RSS Parser',
	'pi_version'		=> '1.0',
	'pi_author'			=> 'EllisLab Development Team',
	'pi_author_url'		=> 'http://ellislab.com/',
	'pi_description'	=> 'Retrieves and Parses RSS/Atom Feeds',
	'pi_usage'			=> Rss_parser::usage()
);

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

	// -------------------------------------------------------------------------

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

	// -------------------------------------------------------------------------

	/**
	 * Plugin Usage
	 *
	 * @return void
	 */
	public function usage()
	{
		ob_start();
		?>
RSS Parser
===========================

There is only one tag for the RSS Parser:

{exp:rss_parser url="http://ellislab.com/blog/rss-feed/" offset="5" limit="10" refresh="720"}

Parameters
===========================

The tag has the following parameters:

- url - The URL of the RSS or Atom feed.
- limit - Number of items to display from feed.
- offset - Skip a certain number of items in the display of the feed.
- refresh - How often to refresh the cache file in minutes. The plugin default is to refresh the cached file every three hours.


Single Variables
===========================

- feed_title
- feed_link
- feed_copyright
- feed_description
- feed_language

Both RSS 2.0 and Atom 1.0 feeds can have a "feed logo". The following variables
can be used to display the logo:

- logo_title
- logo_url
- logo_link
- logo_width
- logo_height


Pair Variables
===========================

There are three pair variables available: {feed_items}, {item_categories}, and
{item_authors}. Both {item_categories} and {item_authors}, are only available
within {feed_items}.

{feed_items}
---------------------------

The {feed_items} variable contains all of the items found within the feed:

- item_title
- item_link
- item_date: uses standard ExpressionEngine date formatting (e.g. {date format="%F %d %Y"})
- item_description
- item_content

{item_authors}
---------------------------

The {item_authors} variable contains information about all of the authors of a
particular item. Each author has three single variables associated with it:

- author_email
- author_link
- author_name

{item_categories}
---------------------------

The {item_categories} variable contains all of the categories that a feed item
has been assigned. Each category has one useful variable:

- category_name

Example
===========================

{exp:rss_parser url="http://expressionengine.com/feeds/rss/full/" limit="10" refresh="720"}
	<ul>
		{feed_items}
			<li><a href="{item_link}">{item_title}</a></li>
		{/feed_items}
	</ul>
{/exp:rss_parser}


Changelog
===========================


Version 1.0
---------------------------

- Initial release and (mostly) feature parity with MagPie plugin

		<?php
		$buffer = ob_get_contents();

		ob_end_clean();

		return $buffer;
	}
} // END RSS Parser class


/* End of file pi.rss_parser.php */
/* Location: ./system/expressionengine/third_party/rss_parser/pi.rss_parser.php */