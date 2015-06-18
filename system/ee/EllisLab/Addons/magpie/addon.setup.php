<?php

return array(
	'author'      => 'EllisLab',
	'author_url'  => 'http://ellislab.com/',
	'name'        => 'Magpie RSS Parser',
	'description' => 'Retrieves and Parses RSS/Atom Feeds',
	'version'     => '1.3.50',
	'namespace'   => 'EllisLab\Addons\Magpie',
	'settings_exist' => FALSE,

	'plugin.usage' => <<<'USAGE'
STEP ONE:
Insert plugin tag into your template.  Set parameters and variables.

PARAMETERS:
The tag has three parameters:

1. url - The URL of the RSS or Atom feed.

2. limit - Number of items to display from feed.

3. offset - Skip a certain number of items in the display of the feed.

4. refresh - How often to refresh the cache file in minutes.  The plugin default is to refresh the cached file every three hours.


Example opening tag:  {exp:magpie url="http://expressionengine.com/feeds/rss/full/" limit="8" refresh="720"}

SINGLE VARIABLES:

feed_version - What version of RSS or Atom is this feed
feed_type - What type of feed is this, Atom or RSS
page_url - Page URL of the feed.

image_title - [RSS] The contents of the &lt;title&gt; element contained within the sub-element &lt;channel&gt;
image_url - [RSS] The contents of the &lt;url&gt; element contained within the sub-element &lt;channel&gt;
image_link - [RSS] The contents of the &lt;link&gt; element contained within the sub-element &lt;channel&gt;
image_description - [RSS] The contents of the optional &lt;description&gt; element contained within the sub-element &lt;channel&gt;
image_width - [RSS] The contents of the optional &lt;width&gt; element contained within the sub-element &lt;channel&gt;
image_height - [RSS] The contents of the optional &lt;height&gt; element contained within the sub-element &lt;channel&gt;

channel_title - [ATOM/RSS-0.91/RSS-1.0/RSS-2.0]
channel_link - [ATOM/RSS-0.91/RSS-1.0/RSS-2.0]
channel_modified - [ATOM]
channel_generator - [ATOM]
channel_copyright - [ATOM]
channel_description - [RSS-0.91/ATOM]
channel_language - [RSS-0.91/RSS-1.0/RSS-2.0]
channel_pubdate - [RSS-0.91]
channel_lastbuilddate - [RSS-0.91]
channel_tagline - [RSS-0.91/RSS-1.0/RSS-2.0]
channel_creator - [RSS-1.0/RSS-2.0]
channel_date - [RSS-1.0/RSS-2.0]
channel_rights - [RSS-2.0]


PAIR VARIABLES:

Only one pair variable, {items}, is available, and it is for the entries/items in the RSS/Atom Feeds. This pair
variable allows many different other single variables to be contained within it depending on the type of feed.

title - [ATOM/RSS-0.91/RSS-1.0/RSS-2.0]
link - [ATOM/RSS-0.91/RSS-1.0/RSS-2.0]
description - [RSS-0.91/RSS-1.0/RSS-2.0]
about - [RSS-1.0]
atom_content - [ATOM]
author_name - [ATOM]
author_email - [ATOM]
content - [ATOM/RSS-2.0]
created - [ATOM]
creator - [RSS-1.0]
pubdate/date - (varies by feed design)
description - [ATOM]
id - [ATOM]
issued - [ATOM]
modified - [ATOM]
subject - [ATOM/RSS-1.0]
summary - [ATOM/RSS-1.0/RSS-2.0]


EXAMPLE:

{exp:magpie url="http://expressionengine.com/feeds/rss/full/" limit="10" refresh="720"}
<ul>
{items}
<li><a href="{link}">{title}</a></li>
{/items}
</ul>
{/exp:magpie}


***************************
Version 1.2
***************************
Complete Rewrite That Improved the Caching System Dramatically

***************************
Version 1.2.1 + 1.2.2
***************************
Bug Fixes

***************************
Version 1.2.3
***************************
Modified the code so that one can put 'magpie:' as a prefix on all plugin variables,
which allows the embedding of this plugin in a {exp:channel:entries} tag and using
that tag's variables in this plugin's parameter (url="" parameter, specifically).

{exp:magpie url="http://expressionengine.com/feeds/rss/full/" limit="10" refresh="720"}
<ul>
{magpie:items}
<li><a href="{magpie:link}">{magpie:title}</a></li>
{/magpie:items}
</ul>
{/exp:magpie}

***************************
Version 1.2.4
***************************
Added the ability for the encoding to be parsed out of the XML feed and used to
convert the feed's data into the encoding specified in the preferences.  Requires
that the Multibyte String (mbstring: http://us4.php.net/manual/en/ref.mbstring.php)
library be compiled into PHP.

***************************
Version 1.2.5
***************************
Fixed a bug where the Magpie library was adding slashes to the cache directory
without doing any sort of double slash checking.

***************************
Version 1.3
***************************
Fixed a bug where the channel and image variables were not showing up because of a bug
introuced in 1.2.

***************************
Version 1.3.1
***************************
New parameter convert_entities="y" which will have any entities in the RSS feed converted
before being parsed by the PHP XML parser.  This is helpful because sometimes the XML
Parser converts entities incorrectly. You have to empty your Magpie cache after enabling this setting.

New parameter encoding="ISO-8859-1".  Allows you to specify the encoding of the RSS
feed, which is sometimes helpful when using the convert_encoding="y" parameter.

***************************
Version 1.3.2
***************************
Eliminated all of the darn encoding parameters previously being used and used the
encoding abilities recently added to the Magpie library that attempts to do all of the
converting early on.

***************************
Version 1.3.3
***************************
The Snoopy library that is included with the Magpie plugin by default was causing
problems with the Snoopy library included in the Third Party Linklist module, so
the name was changed to eliminate the conflict.

***************************
Version 1.3.4
***************************
The offset="" parameter was undocumented and had a bug.  Fixed.

***************************
Version 1.3.5
***************************
Added ability to override caching options when using fetch_rss() directly.
USAGE
);