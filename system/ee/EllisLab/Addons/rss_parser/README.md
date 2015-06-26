## Example
<textarea>
	{exp:rss_parser url="http://expressionengine.com/feeds/rss/full/" limit="10" refresh="720"}
	<ul>
		{feed_items}
			<li><a href="{item_link}">{item_title}</a></li>
		{/feed_items}
	</ul>
	{/exp:rss_parser}
</textarea>

## Parameters

- url
  - The URL of the RSS or Atom feed.
- limit
  - Number of items to display from feed.
- offset
  - Skip a certain number of items in the display of the feed.
- refresh
  - How often to refresh the cache file in minutes. The plugin default is to refresh the cached file every three hours.


## Single Variables

- feed_title
- feed_link
- feed_copyright
- feed_description
- feed_language
- logo_title
- logo_url
- logo_link
- logo_width
- logo_height


## Pair Variables

- feed_items

  The `{feed_items}` variable contains all of the items found within the feed.

		{feed_items}
	{/feed_items}
  - Variables available to the tag pair:
	- item_title
	- item_link
	- item_date: uses standard ExpressionEngine date formatting (e.g. `{date format="%F %d %Y"}`)
	- item_description
	- item_content

- item_authors

  The `{item_authors}` variable contains information about all of the authors of a
particular item.

		{item_authors}
	{/item_authors}

  Each author has three single variables associated with it:
	- author_email
	- author_link
	- author_name

-  item_categories

  The `{item_categories}` variable contains all of the categories that a feed item
has been assigned. Each category has one variable:
  <textarea>
  {item_categories}
		{category_name}
  {/tem_categories}
  </textarea>

  Available variables:
	-  category_name


## Change Log

- 1.1
    - Updated plugin to be 3.0 compatible
- 1.0
    - Updated plugin to be 2.0 compatible

