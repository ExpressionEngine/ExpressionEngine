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


## Change Log

- 1.1
	- Updated plugin to be 3.0 compatible

- 1.0
	- Updated plugin to be 2.0 compatible

