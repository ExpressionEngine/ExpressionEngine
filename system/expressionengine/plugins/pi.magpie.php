<?php

/*
=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 http://ellislab.com/
-----------------------------------------------------
 Copyright (c) 2004 - 2013 EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 http://ellislab.com/expressionengine/user-guide/license.html
=====================================================
 File: pi.magpie.php
-----------------------------------------------------
 Purpose: Magpie RSS plugin
=====================================================

*/

$plugin_info = array(
	'pi_name'			=> 'Magpie RSS Parser',
	'pi_version'		=> '1.3.5',
	'pi_author'			=> 'Paul Burdick',
	'pi_author_url'		=> 'http://ellislab.com/',
	'pi_description'	=> 'Retrieves and Parses RSS/Atom Feeds',
	'pi_usage'			=> Magpie::usage()
);
					

Class Magpie {

	var $cache_name		= 'magpie_cache';						// Name of cache directory
	var $cache_refresh	= 360;									// Period between cache refreshes (in minutes)
	var $cache_data		= '';									// Data from cache file
	var $cache_path		= '';									// Path to cache file.
	var $cache_tpath	= '';									// Path to cache file's time file.
	
	var $page_url		= '';									// URL being requested
	
	var $items			= array();								// Information about items returned
	var $dates			= array('lastupdate','linkcreated');	// Date elements							
	var $return_data	= ''; 									// Data sent back to Template parser
	

	 /** -------------------------------------
	 /**  Constructor
	 /** -------------------------------------*/
	 function Magpie()
	 {
		// Make a local reference of the ExpressionEngine super object
		$this->EE =& get_instance();
	 	
	 	/** -------------------------------
	 	/**  Set Parameters 
	 	/** -------------------------------*/
	 	
	 	$this->cache_refresh 	= ( ! ee()->TMPL->fetch_param('refresh')) ? $this->cache_refresh : ee()->TMPL->fetch_param('refresh');
	 	$this->page_url 		= ( ! ee()->TMPL->fetch_param('url')) ? '' : trim(ee()->TMPL->fetch_param('url'));
	 	$limit					= ( ! ee()->TMPL->fetch_param('limit')) ? 20 : ee()->TMPL->fetch_param('limit');
	 	$offset					= ( ! ee()->TMPL->fetch_param('offset')) ? 0 : ee()->TMPL->fetch_param('offset');
	 	$template				= ee()->TMPL->tagdata;
	 	
	 	
	 	if ($this->page_url == '')
	 	{
	 		return $this->return_data;
	 	}	 	
	 	
	 	if (ee()->config->item('debug') == 2 OR (ee()->config->item('debug') == 1 && ee()->session->userdata['group_id'] == 1))
		{
			if ( ! defined('MAGPIE_DEBUG'))
			{
				define('MAGPIE_DEBUG', 1);
			}
		}
		else
		{
			if ( ! defined('MAGPIE_DEBUG'))
			{
				define('MAGPIE_DEBUG', 0);
			}
		}
		
		
	 	
	 	/** -------------------------------
	 	/**  Check and Retrive Cache
	 	/** -------------------------------*/
	 	
	 	if ( ! defined('MAGPIE_CACHE_DIR'))
		{
			define('MAGPIE_CACHE_DIR', APPPATH.'cache/'.$this->cache_name.'/');
		}
		
		if ( ! defined('MAGPIE_CACHE_AGE'))
		{
			define('MAGPIE_CACHE_AGE',	$this->cache_refresh * 60);
		}
		
		$this->RSS = fetch_rss($this->page_url);
		
		if (count($this->RSS->items) == 0)
		{
			return $this->return_data;
		}
		
		/** -----------------------------------
		/**  Parse Template - ITEMS
		/** -----------------------------------*/
		
		if (preg_match("/(".LD."items".RD."(.*?)".LD.'\/'.'items'.RD."|".LD."magpie:items".RD."(.*?)".LD.'\/'.'magpie:items'.RD.")/s", $template, $matches))
		{
			$items_data = '';
			$i = 0;
			
			if (count($this->RSS->items) > 0)
			{		  	
				foreach($this->RSS->items as $item)
		  		{
		  			$i++;
		  			if ($i <= $offset) continue;
		  			
		  			$temp_data = $matches['1'];
		
		  			/** ----------------------------------------
					/**  Quick and Dirty Conditionals
					/** ----------------------------------------*/
					
					if (stristr($temp_data, LD.'if'))
					{
						$tagdata = ee()->functions->prep_conditionals($temp_data, $item, '', 'magpie:');
		  			}
		  			
		  			/** ----------------------------------------
					/**  Single Variables
					/** ----------------------------------------*/
		  			
		  			foreach($item as $key => $value)
		  			{		  				
		  				if ( ! is_array($value))
		  				{
		  					$temp_data = str_replace(LD.$key.RD, $value, $temp_data);
		  					$temp_data = str_replace(LD.'magpie:'.$key.RD, $value, $temp_data);
		  					
		  					if ($key == 'atom_content') 
		  					{
		  						$temp_data = str_replace(LD.'content'.RD, $value, $temp_data);
		  						$temp_data = str_replace(LD.'magpie:content'.RD, $value, $temp_data);
		  					}
		  				}
		  				else
		  				{
		  					foreach ($value as $vk => $vv)
		  					{
		  						$temp_data = str_replace(LD.$key.'_'.$vk.RD, $vv, $temp_data);
		  						$temp_data = str_replace(LD.'magpie:'.$key.'_'.$vk.RD, $vv, $temp_data);
		  						
		  						if ($key == 'dc')
		  						{
		  							$temp_data = str_replace(LD.$vk.RD, $vv, $temp_data);
		  							$temp_data = str_replace(LD.'magpie:'.$vk.RD, $vv, $temp_data);
		  						}
		  					}
		  				}
		  			}
		
		  			$items_data .= $temp_data;
		  			
		  			if ($i >= ($limit + $offset))
		  			{
		  				break;
		  			}
				}
			}
			
		  	/** ----------------------------------------
			/**  Clean up left over variables
			/** ----------------------------------------*/
		  	
		  	$items_data = str_replace(LD.'exp:', 'TgB903He0mnv3dd098', $items_data);
			$items_data = str_replace(LD.'/exp:', 'Mu87ddk2QPoid990iod', $items_data);
		
			$items_data = preg_replace("/".LD."if.*?".RD.".+?".LD.'\/'."if".RD."/s", '', $items_data);
			$items_data = preg_replace("/".LD.".+?".RD."/", '', $items_data);

			$items_data = str_replace('TgB903He0mnv3dd098', LD.'exp:', $items_data);
			$items_data = str_replace('Mu87ddk2QPoid990iod', LD.'/exp:', $items_data);
		  	
			$template = str_replace($matches['0'], $items_data, $template);
		}
					
		/** -----------------------------------
		/**  Parse Template
		/** -----------------------------------*/
		
		$channel_variables = array('title', 'link', 'modified', 'generator',
									'copyright', 'description', 'language',
									'pubdate', 'lastbuilddate', 'generator',
									'tagline', 'creator', 'date', 'rights');
									
		$image_variables = array('title','url', 'link','description', 'width', 'height');
		
		
		foreach (ee()->TMPL->var_single as $key => $val)
		{		  			
			
			/** ----------------------------------------
			/**  {feed_version} - Version of RSS/Atom Feed
			/** ----------------------------------------*/
			
			if ($key == "feed_version" OR $key == "magpie:feed_version")
			{
				if ( ! isset($this->RSS->feed_version)) $this->RSS->feed_version = '';
				
				$template = ee()->TMPL->swap_var_single($val, $this->RSS->feed_version, $template);
			}
			
			/** ----------------------------------------
			/**  {feed_type}
			/** ----------------------------------------*/
			
			if (($key == "feed_type" OR $key == "magpie:feed_type") && isset($this->RSS->feed_type))
			{
				if ( ! isset($this->RSS->feed_type)) $this->RSS->feed_type = '';
				
				$template = ee()->TMPL->swap_var_single($val, $this->RSS->feed_type, $template);
			}
			
			/** ----------------------------------------
			/**  Image related variables
			/** ----------------------------------------*/
			
			foreach ($image_variables as $variable)
			{
				if ($key == 'image_'.$variable OR $key == 'magpie:image_'.$variable)
				{
					if ( ! isset($this->RSS->image[$variable])) $this->RSS->image[$variable] = '';
				
					$template = ee()->TMPL->swap_var_single($val, $this->RSS->image[$variable], $template);
				}
			}
			
			
			/** ----------------------------------------
			/**  Channel related variables
			/** ----------------------------------------*/
			
			foreach ($channel_variables as $variable)
			{
				if ($key == 'channel_'.$variable OR $key == 'magpie:channel_'.$variable)
				{
					if ( ! isset($this->RSS->channel[$variable]))
					{
						$this->RSS->channel[$variable] = ( ! isset($this->RSS->channel['dc'][$variable])) ? '' : $this->RSS->channel['dc'][$variable];
					}
				
					$template = ee()->TMPL->swap_var_single($val, $this->RSS->channel[$variable], $template);
				}
			}			
			
			/** ----------------------------------------
			/**  {page_url}
			/** ----------------------------------------*/
			
			if ($key == 'page_url' OR $key == 'magpie:page_url')
			{
				$template = ee()->TMPL->swap_var_single($val, $this->page_url, $template);
			}	
		}
		
		$this->return_data = &$template;
		
	}

		 
	
	 
	/** ----------------------------------------
	/**  Plugin Usage
	/** ----------------------------------------*/
	function usage()
	{
	ob_start(); 
	?>

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


	<?php
	$buffer = ob_get_contents();
	
	ob_end_clean(); 

	return $buffer;
	}

	

} // END Magpie class




/*

// -------------------------------------------
//  BEGIN MagpieRSS Class
// -------------------------------------------

The MagpieRSS class is used here under a BSD license with the author's permission.

Copyright (c) 2002, Kellan Elliott-McCrea All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

- Redistributions of source code must retain the above copyright notice, this
  list of conditions and the following disclaimer.

- Redistributions in binary form must reproduce the above copyright notice,
  this list of conditions and the following disclaimer in the documentation
  and/or other materials provided with the distribution.
 
THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND
CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A
PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR
CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR
BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER
IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.



 * Project:	  MagpieRSS: a simple RSS integration tool
 * File:		  rss_parse.inc  - parse an RSS or Atom feed
 *				return as a simple object.
 *
 * Handles RSS 0.9x, RSS 2.0, RSS 1.0, and Atom 0.3
 *
 * The lastest version of MagpieRSS can be obtained from:
 * http://magpierss.sourceforge.net
 *
 * For questions, help, comments, discussion, etc., please join the
 * Magpie mailing list:
 * magpierss-general@lists.sourceforge.net
 *
 * Author:		Kellan Elliott-McCrea <kellan@protest.net>
 * Version:		0.6a
 * License:		GPL
 *
 *
 *  ABOUT MAGPIE's APPROACH TO PARSING:
 *	- Magpie is based on expat, an XML parser, and therefore will only parse
 *	  valid XML files.  This includes all properly constructed RSS or Atom.
 *
 *	- Magpie is an inclusive parser.  It will include any elements that 
 *	  it can turn into a key value pair in the parsed feed object it returns. 
 *		
 *	- Magpie supports namespaces, and will return any elements found in a 
 *	  namespace in a sub-array, with the key point to that array being the 
 *	  namespace prefix.  
 *	  (e.g. if an item contains a <dc:date> element, then that date can 
 *	  be accessed at $item['dc']['date']
 *		
 *	- Magpie supports nested elements by combining the names.  If an item 
 *	  includes XML like:
 *		<author>
 *		  <name>Kellan</name>
 *		</author>
 *		
 *	 The name field is accessible at $item['author_name']
 *  
 *	- Magpie makes no attempt validate a feed beyond insuring that it
 *	  is valid XML.	
 *	  RSS validators are readily available on the web at:
 *		 http://feeds.archive.org/validator/
 *		 http://www.ldodds.com/rss_validator/1.0/validator.html
 *
 *
 * EXAMPLE PARSED RSS ITEM:
 *
 * Magpie tries to parse RSS into easy to use PHP datastructures.
 *
 * For example, Magpie on encountering (a rather complex) RSS 1.0 item entry:
 *
 * <item rdf:about="http://protest.net/NorthEast/calendrome.cgi?span=event&#38;ID=210257">
 *	<title>Weekly Peace Vigil</title>
 *	<link>http://protest.net/NorthEast/calendrome.cgi?span=event&#38;ID=210257</link>
 *	<description>Wear a white ribbon</description>
 *	<dc:subject>Peace</dc:subject>
 *	<ev:startdate>2002-06-01T11:00:00</ev:startdate>
 *	<ev:location>Northampton, MA</ev:location>
 *	<ev:type>Protest</ev:type>
 * </item>
 * 
 * Would transform it into the following associative array, and push it
 * onto the array $rss-items
 *
 * array(
 *	title => 'Weekly Peace Vigil',
 *	link => 'http://protest.net/NorthEast/calendrome.cgi?span=event&#38;ID=210257',
 *	description => 'Wear a white ribbon',
 *	dc => array (
 *			subject => 'Peace'
 *		),
 *	ev => array (
 *		startdate => '2002-06-01T11:00:00',
 *		enddate => '2002-06-01T12:00:00',
 *		type => 'Protest',
 *		location => 'Northampton, MA'
 *	)
 * )
 *
 *
 *
 *  A FEW NOTES ON PARSING Atom FEEDS
 *
 *  Atom support is considered alpha.  Atom elements will be often be available
 *  as their RSS equivalent, summary is available as description for example.
 *
 *  Elements of mode=xml, as flattened into a single string, just as if they
 *  had been wrapped in a CDATA container.
 *
 *  See:  http://laughingmeme.org/archives/001676.html
 *
 */
define('RSS', 'RSS');
define('ATOM', 'Atom');


class MagpieRSS {
	/*
	 * Hybrid parser, and object.  (probably a bad idea! :)
	 *
	 * Useage Example:
	 *
	 * $some_rss = "<?xml version="1.0"......
	 *
	 * $rss = new MagpieRSS( $some_rss );
	 *
	 * // print rss chanel title
	 * echo $rss->channel['title'];
	 *
	 * // print the title of each item
	 * foreach ($rss->items as $item )
	 * {
	 *	  echo $item[title];
	 * }
	 *
	 * see: rss_fetch.inc for a simpler interface
	 */
	 
	var $parser;
	
	var $current_item	= array();	// item currently being parsed
	var $items			= array();	// collection of parsed items
	var $channel		= array();	// hash of channel fields
	var $textinput		= array();
	var $image			= array();
	var $feed_type;
	var $feed_version;
	var $encoding;

	// parser variables
	var $stack				= array(); // parser stack
	var $inchannel			= false;
	var $initem 			= false;
	var $incontent			= false; // if in Atom <content mode="xml"> field 
	var $intextinput		= false;
	var $inimage 			= false;
	var $current_field		= '';
	var $current_namespace	= false;
	var $etag				= false;
	
	var $ERROR = "";
	
	var $_CONTENT_CONSTRUCTS = array('content', 'summary', 'info', 'title', 'tagline', 'copyright');
	 var $_KNOWN_ENCODINGS	 = array('UTF-8', 'US-ASCII', 'ISO-8859-1');
	 
/*======================================================================*\
	 Function: MagpieRSS
	 Purpose:  Constructor, sets up XML parser,parses source,
			  and populates object.. 
	Input:	  String containing the RSS to be parsed
\*======================================================================*/
	function MagpieRSS ($source, $output_encoding='ISO-8859-1', 
								$input_encoding=null, $detect_encoding=true)
	{

		// Make a local reference of the ExpressionEngine super object
		$this->EE =& get_instance();
		
		# if PHP xml isn't compiled in, die
		#
		if ( ! function_exists('xml_parser_create'))
		{
			$this->error( "Failed to load PHP's XML Extension. " . 
						  "http://www.php.net/manual/en/ref.xml.php",
							E_USER_ERROR );
		}
		
		list($parser, $source) = $this->create_parser($source, 
					 $output_encoding, $input_encoding, $detect_encoding);
		
		if ( ! is_resource($parser))
		{
			$this->error( "Failed to create an instance of PHP's XML parser. " .
						  "http://www.php.net/manual/en/ref.xml.php",
						  E_USER_ERROR );
		}

		
		# pass in parser, and a reference to this object
		# setup handlers
		#
		xml_set_object( $parser, $this );
		xml_set_element_handler($parser, 
				'feed_start_element', 'feed_end_element' );
						
		xml_set_character_data_handler( $parser, 'feed_cdata' ); 
	
		$status = @xml_parse($parser, $source);
		
		if ( ! $status )
		{
			$errorcode = xml_get_error_code( $parser );
			if ( $errorcode != XML_ERROR_NONE )
			{
				$xml_error = xml_error_string( $errorcode );
				$error_line = xml_get_current_line_number($parser);
				$error_col = xml_get_current_column_number($parser);
				$errormsg = "$xml_error at line $error_line, column $error_col";

				$this->error( $errormsg );
				
				return FALSE;
			}
		}
		
		xml_parser_free( $parser );

		$this->normalize();
	}
	
	function change_key_case($array)
	{
		$new_array = array();
		
		foreach($array as $key => $value)
		{
			$new_array[strtolower($key)] = $value;
		}
		
		return $new_array;
	}
	
	function feed_start_element($p, $element, &$attrs)
	{
		$el = $element = strtolower($element);
		
		if ( ! function_exists('array_change_key_case'))
		{
			$attrs = $this->change_key_case($attrs);
		}
		else
		{
			$attrs = array_change_key_case($attrs, CASE_LOWER);
		}
		
		// check for a namespace, and split if found
		$ns	= false;
		if ( strpos( $element, ':' ) )
		{
			list($ns, $el) = explode( ':', $element, 2); 
		}
		
		if ( $ns and $ns != 'rdf' )
		{
			$this->current_namespace = $ns;
		}
			
		# if feed type isn't set, then this is first element of feed
		# identify feed from root element
		#
		if ( ! isset($this->feed_type) )
		{
			if ( $el == 'rdf' )
			{
				$this->feed_type = RSS;
				$this->feed_version = '1.0';
			}
			elseif ( $el == 'rss' )
			{
				$this->feed_type = RSS;
				$this->feed_version = $attrs['version'];
			}
			elseif ( $el == 'feed' )
			{
				$this->feed_type = ATOM;
				$this->feed_version = $attrs['version'];
				$this->inchannel = true;
			}
			return;
		}
	
		if ( $el == 'channel' ) 
		{
			$this->inchannel = true;
		}
		elseif ($el == 'item' or $el == 'entry' ) 
		{
			$this->initem = true;
			if ( isset($attrs['rdf:about']) )
			{
				$this->current_item['about'] = $attrs['rdf:about'];	
			}
		}
		
		// if we're in the default namespace of an RSS feed,
		//  record textinput or image fields
		elseif ( 
			$this->feed_type == RSS and 
			$this->current_namespace == '' and 
			$el == 'textinput' ) 
		{
			$this->intextinput = true;
		}
		
		elseif (
			$this->feed_type == RSS and 
			$this->current_namespace == '' and 
			$el == 'image' ) 
		{
			$this->inimage = true;
		}
		
		# handle atom content constructs
		elseif ( $this->feed_type == ATOM and in_array($el, $this->_CONTENT_CONSTRUCTS) )
		{
			// avoid clashing w/ RSS mod_content
			if ($el == 'content' )
			{
				$el = 'atom_content';
			}
			
			$this->incontent = $el;
			
			
		}
		
		// if inside an Atom content construct (e.g. content or summary) field treat tags as text
		elseif ($this->feed_type == ATOM and $this->incontent ) 
		{
			// if tags are inlined, then flatten
			$attrs_str = join(' ', 
					array_map('map_attrs', 
					array_keys($attrs), 
					array_values($attrs) ) );
			
			$this->append_content( "<$element $attrs_str>"  );
					
			array_unshift( $this->stack, $el );
		}
		
		// Atom support many links per containging element.
		// Magpie treats link elements of type rel='alternate'
		// as being equivalent to RSS's simple link element.
		//
		elseif ($this->feed_type == ATOM and $el == 'link' ) 
		{
			if ( isset($attrs['rel']) and $attrs['rel'] == 'alternate' ) 
			{
				$link_el = 'link';
			}
			else
			{
				$link_el = 'link_' . $attrs['rel'];
			}
			
			$this->append($link_el, $attrs['href']);
		}
		// set stack[0] to current element
		else
		{
			array_unshift($this->stack, $el);
		}
	}
	

	
	function feed_cdata ($p, $text)
	{
		
		if ($this->feed_type == ATOM and $this->incontent) 
		{
			$this->append_content( $text );
		}
		else
		{
			$current_el = join('_', array_reverse($this->stack));
			$this->append($current_el, $text);
		}
	}
	
	function feed_end_element ($p, $el)
	{
		$el = strtolower($el);
		
		if ( $el == 'item' or $el == 'entry' ) 
		{
			$this->items[] = $this->current_item;
			$this->current_item = array();
			$this->initem = false;
		}
		elseif ($this->feed_type == RSS and $this->current_namespace == '' and $el == 'textinput' ) 
		{
			$this->intextinput = false;
		}
		elseif ($this->feed_type == RSS and $this->current_namespace == '' and $el == 'image' ) 
		{
			$this->inimage = false;
		}
		elseif ($this->feed_type == ATOM and in_array($el, $this->_CONTENT_CONSTRUCTS) )
		{	
			$this->incontent = false;
		}
		elseif ($el == 'channel' or $el == 'feed' ) 
		{
			$this->inchannel = false;
		}
		elseif ($this->feed_type == ATOM and $this->incontent  )
		{
			// balance tags properly
			// note:  i don't think this is actually neccessary
			if ( $this->stack[0] == $el ) 
			{
				$this->append_content("</$el>");
			}
			else
			{
				$this->append_content("<$el />");
			}

			array_shift( $this->stack );
		}
		else
		{
			array_shift( $this->stack );
		}
		
		$this->current_namespace = false;
	}
	
	function concat (&$str1, $str2="")
	{
		if ( ! isset($str1) )
		{
			$str1="";
		}
		$str1 .= $str2;
	}
	
	
	
	function append_content($text)
	{
		if ( $this->initem )
		{
			$this->concat( $this->current_item[ $this->incontent ], $text );
		}
		elseif ( $this->inchannel )
		{
			$this->concat( $this->channel[ $this->incontent ], $text );
		}
	}
	
	// smart append - field and namespace aware
	function append($el, $text)
	{
		if ( ! $el)
		{
			return;
		}
		if ( $this->current_namespace ) 
		{
			if ( $this->initem )
			{
				$this->concat($this->current_item[ $this->current_namespace ][ $el ], $text);
			}
			elseif ($this->inchannel)
			{
				$this->concat($this->channel[ $this->current_namespace][ $el ], $text );
			}
			elseif ($this->intextinput)
			{
				$this->concat($this->textinput[ $this->current_namespace][ $el ], $text );
			}
			elseif ($this->inimage)
			{
				$this->concat($this->image[ $this->current_namespace ][ $el ], $text );
			}
		}
		else
		{
			if ( $this->initem )
			{
				$this->concat($this->current_item[ $el ], $text);
			}
			elseif ($this->intextinput)
			{
				$this->concat($this->textinput[ $el ], $text );
			}
			elseif ($this->inimage)
			{
				$this->concat($this->image[ $el ], $text );
			}
			elseif ($this->inchannel)
			{
				$this->concat($this->channel[ $el ], $text );
			}
			
		}
	}
	
	function normalize ()
	{
		// if atom populate rss fields
		if ( $this->is_atom() )
		{
			$this->channel['descripton'] = ( ! isset($this->channel['tagline'])) ? '' : $this->channel['tagline'];
			for ( $i = 0; $i < count($this->items); $i++)
			{
				$item = $this->items[$i];
				if ( isset($item['summary']) )
				{
					$item['description'] = $item['summary'];	
				}

				if ( isset($item['atom_content']))
				{
					$item['content']['encoded'] = $item['atom_content'];
				}
				
				$this->items[$i] = $item;
			}		
		}
		elseif ( $this->is_rss() )
		{
			$this->channel['tagline'] = ( ! isset($this->channel['description'])) ? '' : $this->channel['description'];
			for ( $i = 0; $i < count($this->items); $i++)
			{
				$item = $this->items[$i];
				if ( isset($item['description']))
				{
					$item['summary'] = $item['description'];
				}	

				if ( isset($item['content']['encoded'] ) )
				{
					$item['atom_content'] = $item['content']['encoded'];
				}
			
				$this->items[$i] = $item;
			}
		}
	}
	
	function error ($errormsg, $lvl=E_USER_WARNING)
	{
	
		// append PHP's error message if track_errors enabled
		if ( isset($php_errormsg) )
		{ 
			$errormsg .= " ($php_errormsg)";
		}
		
		$this->ERROR = $errormsg;
		
		if (MAGPIE_DEBUG)
		{
			trigger_error($errormsg, $lvl);
		}
		else
		{
			error_log($errormsg, 0);
		}
	}
	
	function is_rss ()
	{
		if ( $this->feed_type == RSS )
		{
			return $this->feed_version;	
		}
		else
		{
			return false;
		}
	}
	
	function is_atom()
	{
		if ( $this->feed_type == ATOM )
		{
			return $this->feed_version;
		}
		else
		{
			return false;
		}
	}
	
	
	/**
	 * return XML parser, and possibly re-encoded source
	 *
	 */
	function create_parser($source, $out_enc, $in_enc, $detect)
	{	 	
		if ( substr(phpversion(),0,1) == 5)
		{
			$parser = $this->php5_create_parser($in_enc, $detect);
		}
		else
		{
			list($parser, $source) = $this->php4_create_parser($source, $in_enc, $detect);
		}
		  
		$this->encoding = ee()->config->item('charset');
		  
		  
		if (in_array(strtolower($this->encoding), array('iso-8859-1', 'us-ascii', 'utf-8')))
		{
			xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, $this->encoding);
		}
		  
		return array($parser, $source);
	}
	 
	 /**
	 * Instantiate an XML parser under PHP5
	 *
	 * PHP5 will do a fine job of detecting input encoding
	 * if passed an empty string as the encoding. 
	 *
	 * All hail libxml2!
	 *
	 */
	 function php5_create_parser($in_enc, $detect)
	 {
		  // by default php5 does a fine job of detecting input encodings
		  if ( ! $detect && $in_enc)
		  {
				return xml_parser_create($in_enc);
		  }
		  else
		  {
				return xml_parser_create('');
		  }
	 }
	 
	 /**
	 * Instaniate an XML parser under PHP4
	 *
	 * Unfortunately PHP4's support for character encodings
	 * and especially XML and character encodings sucks.  As
	 * long as the documents you parse only contain characters
	 * from the ISO-8859-1 character set (a superset of ASCII,
	 * and a subset of UTF-8) you're fine.  However once you
	 * step out of that comfy little world things get mad, bad,
	 * and dangerous to know.
	 *
	 * The following code is based on SJM's work with FoF
	 * @see http://minutillo.com/steve/channel/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
	 *
	 */
	 function php4_create_parser($source, $in_enc, $detect)
	 {
		if ( ! $detect )
		{
			return array(xml_parser_create($in_enc), $source);
		}
		  
		if ( ! $in_enc)
		{
			if (preg_match('/<?xml.*encoding=[\'"](.*?)[\'"].*?>/m', $source, $m))
			{
				$in_enc = strtoupper($m[1]);
				$this->source_encoding = $in_enc;
			}
			else
			{
				$in_enc = 'UTF-8';
			}
		}
		  
		if ($this->known_encoding($in_enc))
		{
			return array(xml_parser_create($in_enc), $source);
		}
		  
		// the dectected encoding is not one of the simple encodings PHP knows
		// attempt to use the iconv extension to
		// cast the XML to a known encoding
		// @see http://php.net/iconv
		 
		if (function_exists('iconv'))
		{
			$encoded_source = iconv($in_enc,'UTF-8', $source);
			if ($encoded_source)
			{
				 return array(xml_parser_create('UTF-8'), $encoded_source);
			}
		}
		  
		// iconv didn't work, try mb_convert_encoding
		// @see http://php.net/mbstring
		if (function_exists('mb_convert_encoding'))
		{
			$encoded_source = mb_convert_encoding($source, 'UTF-8', $in_enc );
			if ($encoded_source)
			{
				return array(xml_parser_create('UTF-8'), $encoded_source);
			}
		}
		  
		// else 
		$this->error("Feed is in an unsupported character encoding. ($in_enc) " .
			"You may see strange artifacts, and mangled characters.",
			E_USER_NOTICE
		);
				
		return array(xml_parser_create(), $source);
	}
	 
	function known_encoding($enc)
	{
		$enc = strtoupper($enc);
		if ( in_array($enc, $this->_KNOWN_ENCODINGS) )
		{
			return $enc;
		}
		else
		{
			return false;
		}
	 }
	
	

/*======================================================================*\
	EVERYTHING BELOW HERE IS FOR DEBUGGING PURPOSES
\*======================================================================*/
	function show_list ()
	{
		echo "<ol>\n";
		foreach ($this->items as $item)
		{
			echo "<li>", $this->show_item( $item );
		}
		echo "</ol>";
	}
	
	function show_channel ()
	{
		echo "channel:<br>";
		echo "<ul>";
		while ( list($key, $value) = each( $this->channel ) )
		{
			echo "<li> $key: $value";
		}
		echo "</ul>";
	}
	
	function show_item ($item)
	{
		echo "item: $item[title]";
		echo "<ul>";
		while ( list($key, $value) = each($item) )
		{
			if ( is_array($value) )
			{
				echo "<br><b>$key</b>";
				echo "<ul>";
				while ( list( $ns_key, $ns_value) = each( $value ) )
				{
					echo "<li>$ns_key: $ns_value";
				}
				echo "</ul>";
			}
			else
			{
				echo "<li> $key: $value";
			}
		}
		echo "</ul>";
	}

/*======================================================================*\
	END DEBUGGING FUNCTIONS	
\*======================================================================*/
	
} # end class RSS

function map_attrs($k, $v)
{
	return "$k=\"$v\"";
}


/*
 * Project:	  MagpieRSS: a simple RSS integration tool
 * File:		  rss_fetch.inc, a simple functional interface
 				to fetching and parsing RSS files, via the
				function fetch_rss()
 * Author:		Kellan Elliott-McCrea <kellan@protest.net>
 * License:		GPL
 *
 * The lastest version of MagpieRSS can be obtained from:
 * http://magpierss.sourceforge.net
 *
 * For questions, help, comments, discussion, etc., please join the
 * Magpie mailing list:
 * magpierss-general@lists.sourceforge.net
 *
 */
 
// Setup MAGPIE_DIR for use on hosts that don't include
// the current path in include_path.
// with thanks to rajiv and smarty
if ( ! defined('DIR_SEP'))
{
	define('DIR_SEP', DIRECTORY_SEPARATOR);
}

if ( ! defined('MAGPIE_DIR'))
{
	define('MAGPIE_DIR', dirname(__FILE__) . DIR_SEP);
}


/* 
 * CONSTANTS - redefine these in your script to change the
 * behaviour of fetch_rss() currently, most options effect the cache
 *
 * MAGPIE_CACHE_ON - Should Magpie cache parsed RSS objects? 
 * For me a built in cache was essential to creating a "PHP-like" 
 * feel to Magpie, see rss_cache.inc for rationale
 *
 *
 * MAGPIE_CACHE_DIR - Where should Magpie cache parsed RSS objects?
 * This should be a location that the webserver can write to.	If this 
 * directory does not already exist Mapie will try to be smart and create 
 * it.  This will often fail for permissions reasons.
 *
 *
 * MAGPIE_CACHE_AGE - How long to store cached RSS objects? In seconds.
 *
 *
 * MAGPIE_CACHE_FRESH_ONLY - If remote fetch fails, throw error
 * instead of returning stale object?
 *
 * MAGPIE_DEBUG - Display debugging notices?
 *
*/

/*=======================================================================*\
	Function: fetch_rss: 
	Purpose:  return RSS object for the give url
			  maintain the cache
	Input:	  url of RSS file
	Output:	  parsed RSS object (see rss_parse.inc)

	NOTES ON CACHEING:  
	If caching is on (MAGPIE_CACHE_ON) fetch_rss will first check the cache.
	
	NOTES ON RETRIEVING REMOTE FILES:
	If conditional gets are on (MAGPIE_CONDITIONAL_GET_ON) fetch_rss will
	return a cached object, and touch the cache object upon recieving a
	304.
	
	NOTES ON FAILED REQUESTS:
	If there is an HTTP error while fetching an RSS object, the cached
	version will be return, if it exists (and if MAGPIE_CACHE_FRESH_ONLY is off)
\*=======================================================================*/
define('MAGPIE_VERSION', '0.61');

$MAGPIE_ERROR = "";

function fetch_rss ($url, $cache_age = '')
{
	// initialize constants
	init();
	
	if ( ! isset($url) )
	{
		error("fetch_rss called without a url");
		return false;
	}
	
	// if cache is disabled
	if ( !MAGPIE_CACHE_ON )
	{
		// fetch file, and parse it
		$resp = _fetch_remote_file( $url );
		if ( is_success( $resp->status ) )
		{
			return _response_to_rss( $resp );
		}
		else
		{
			error("Failed to fetch $url and cache is off");
			return false;
		}
	} 
	// else cache is ON
	else
	{
		// Flow
		// 1. check cache
		// 2. if there is a hit, make sure its fresh
		// 3. if cached obj fails freshness check, fetch remote
		// 4. if remote fails, return stale object, or error
		
		$cache = new RSSCache( MAGPIE_CACHE_DIR, ($cache_age != '') ? $cache_age : MAGPIE_CACHE_AGE );
		
		if (MAGPIE_DEBUG and $cache->ERROR)
		{
			debug($cache->ERROR, E_USER_WARNING);
		}
		
		
		$cache_status 	 = 0;		// response of check_cache
		$request_headers = array(); // HTTP headers to send with fetch
		$rss 			 = 0;		// parsed RSS object
		$errormsg		 = 0;		// errors, if any
		
		if ( ! $cache->ERROR)
		{
			// return cache HIT, MISS, or STALE
			$cache_status = $cache->check_cache( $url );
		}
		
		// if object cached, and cache is fresh, return cached obj
		if ( $cache_status == 'HIT' )
		{
			$rss = $cache->get( $url );
			if ( isset($rss) and $rss )
			{
				$rss->from_cache = 1;
				
				if ( MAGPIE_DEBUG > 1)
				{
					debug("MagpieRSS: Cache HIT", E_USER_NOTICE);
				}

				return $rss;
			}
		}
		
		// else attempt a conditional get
		
		// setup headers
		if ( $cache_status == 'STALE' )
		{
			$rss = $cache->get( $url );
			if (isset($rss->etag) && isset($rss->last_modified))
			{
				$request_headers['If-None-Match'] = $rss->etag;
				$request_headers['If-Last-Modified'] = $rss->last_modified;
			}
		}
		
		$resp = _fetch_remote_file( $url, $request_headers );
		
		if (isset($resp) and $resp)
		{
			if ($resp->status == '304' )
			{
				// we have the most current copy
				if ( MAGPIE_DEBUG > 1)
				{
					debug("Got 304 for $url");
				}
				// reset cache on 304 (at minutillo insistent prodding)
				$cache->set($url, $rss);
				return $rss;
			}
			elseif ( is_success( $resp->status ) )
			{
				$rss = _response_to_rss( $resp );
				if ( $rss )
				{
					if (MAGPIE_DEBUG > 1)
					{
						debug("Fetch successful");
					}
					// add object to cache
					$cache->set( $url, $rss );
					return $rss;
				}
			}
			else
			{
				$errormsg = "Failed to fetch $url. ";
				if ( $resp->error )
				{
					# compensate for Snoopy's annoying habbit to tacking
					# on '\n'
					$http_error = substr($resp->error, 0, -2); 
					$errormsg .= "(HTTP Error: $http_error)";
				}
				else
				{
					$errormsg .=  "(HTTP Response: " . $resp->response_code .')';
				}
			}
		}
		else
		{
			$errormsg = "Unable to retrieve RSS file for unknown reasons.";
		}
		
		// else fetch failed
		
		// attempt to return cached object
		if ($rss)
		{
			if ( MAGPIE_DEBUG )
			{
				//debug("Returning STALE object for $url");
			}
			return $rss;
		}
		
		// else we totally failed
		error( $errormsg );	
		
		return false;
		
	} // end if ( !MAGPIE_CACHE_ON )
} // end fetch_rss()

/*=======================================================================*\
	Function:	error
	Purpose:	set MAGPIE_ERROR, and trigger error
\*=======================================================================*/
function error ($errormsg, $lvl=E_USER_WARNING)
{
		global $MAGPIE_ERROR;
		
		// append PHP's error message if track_errors enabled
		if ( isset($php_errormsg) )
		{ 
			$errormsg .= " ($php_errormsg)";
		}
		if ( $errormsg )
		{
			$errormsg = "MagpieRSS: $errormsg";
			$MAGPIE_ERROR = $errormsg;
			
			if (MAGPIE_DEBUG)
			{
				trigger_error($errormsg, $lvl);
			}
			else
			{
				error_log($errormsg, 0);
			}		
		}
}

function debug ($debugmsg, $lvl=E_USER_NOTICE)
{
	trigger_error("MagpieRSS [debug] $debugmsg", $lvl);
}
			
/*=======================================================================*\
	Function:	magpie_error
	Purpose:	accessor for the magpie error variable
\*=======================================================================*/
function magpie_error ($errormsg="")
{
	global $MAGPIE_ERROR;
	
	if ( isset($errormsg) and $errormsg )
	{ 
		$MAGPIE_ERROR = $errormsg;
	}
	
	return $MAGPIE_ERROR;	
}

/*=======================================================================*\
	Function:	_fetch_remote_file
	Purpose:	retrieve an arbitrary remote file
	Input:		url of the remote file
				headers to send along with the request (optional)
	Output:		an HTTP response object (see Snoopy.class.inc)	
\*=======================================================================*/
function _fetch_remote_file ($url, $headers = "" )
{
	// Snoopy is an HTTP client in PHP
	$client = new M_Snoopy();
	$client->agent = MAGPIE_USER_AGENT;
	$client->read_timeout = MAGPIE_FETCH_TIME_OUT;
	$client->use_gzip = MAGPIE_USE_GZIP;

	if (is_array($headers) )
	{
		$client->rawheaders = $headers;
	}
	
	@$client->fetch($url);
	return $client;

}

/*=======================================================================*\
	Function:	_response_to_rss
	Purpose:	parse an HTTP response object into an RSS object
	Input:		an HTTP response object (see Snoopy)
	Output:		parsed RSS object (see rss_parse)
\*=======================================================================*/
function _response_to_rss ($resp)
{
	$rss = new MagpieRSS( $resp->results );
	
	// if RSS parsed successfully		
	if ( $rss and ! $rss->ERROR)
	{
		
		// find Etag, and Last-Modified
		foreach($resp->headers as $h)
		{
			// 2003-03-02 - Nicola Asuni (www.tecnick.com) - fixed bug "Undefined offset: 1"
			if (strpos($h, ": "))
			{
				list($field, $val) = explode(": ", $h, 2);
			}
			else
			{
				$field = $h;
				$val = "";
			}
			
			if ( $field == 'ETag' )
			{
				$rss->etag = $val;
			}
			
			if ( $field == 'Last-Modified' )
			{
				$rss->last_modified = $val;
			}
		}
		
		return $rss;	
	} // else construct error message
	else
	{
		$errormsg = "Failed to parse RSS file.";
		
		if ($rss)
		{
			$errormsg .= " (" . $rss->ERROR . ")";
		}
		error($errormsg);
		
		return false;
	} // end if ($rss and ! $rss->error)
}

/*=======================================================================*\
	Function:	init
	Purpose:	setup constants with default values
				check for user overrides
\*=======================================================================*/
function init ()
{
	if ( defined('MAGPIE_INITALIZED') )
	{
		return;
	}
	else
	{
		define('MAGPIE_INITALIZED', 1);
	}
	
	if ( ! defined('MAGPIE_CACHE_ON') )
	{
		define('MAGPIE_CACHE_ON', 1);
	}

	if ( ! defined('MAGPIE_CACHE_DIR') )
	{
		define('MAGPIE_CACHE_DIR', './cache');
	}

	if ( ! defined('MAGPIE_CACHE_AGE') )
	{
		define('MAGPIE_CACHE_AGE', 60*60); // one hour
	}

	if ( ! defined('MAGPIE_CACHE_FRESH_ONLY') )
	{
		define('MAGPIE_CACHE_FRESH_ONLY', 0);
	}

	if ( ! defined('MAGPIE_DEBUG') )
	{
		define('MAGPIE_DEBUG', 0);
	}
	
	if ( ! defined('MAGPIE_USER_AGENT') )
	{
		$ua = 'MagpieRSS/'. MAGPIE_VERSION . ' (+http://magpierss.sf.net';
		
		if ( MAGPIE_CACHE_ON )
		{
			$ua = $ua . ')';
		}
		else
		{
			$ua = $ua . '; No cache)';
		}
		
		define('MAGPIE_USER_AGENT', $ua);
	}
	
	if ( ! defined('MAGPIE_FETCH_TIME_OUT') )
	{
		define('MAGPIE_FETCH_TIME_OUT', 5);	// 5 second timeout
	}
	
	// use gzip encoding to fetch rss files if supported?
	if ( ! defined('MAGPIE_USE_GZIP') )
	{
		define('MAGPIE_USE_GZIP', true);	
	}
}

// NOTE: the following code should really be in Snoopy, or at least
// somewhere other then rss_fetch!

/*=======================================================================*\
	HTTP STATUS CODE PREDICATES
	These functions attempt to classify an HTTP status code
	based on RFC 2616 and RFC 2518.
	
	All of them take an HTTP status code as input, and return true or false

	All this code is adapted from LWP's HTTP::Status.
\*=======================================================================*/

/*=======================================================================*\
	Function:	is_info
	Purpose:	return true if Informational status code
\*=======================================================================*/
function is_info ($sc)
{ 
	return $sc >= 100 && $sc < 200; 
}

/*=======================================================================*\
	Function:	is_success
	Purpose:	return true if Successful status code
\*=======================================================================*/
function is_success ($sc)
{ 
	return $sc >= 200 && $sc < 300; 
}

/*=======================================================================*\
	Function:	is_redirect
	Purpose:	return true if Redirection status code
\*=======================================================================*/
function is_redirect ($sc)
{ 
	return $sc >= 300 && $sc < 400; 
}

/*=======================================================================*\
	Function:	is_error
	Purpose:	return true if Error status code
\*=======================================================================*/
function is_error ($sc)
{ 
	return $sc >= 400 && $sc < 600; 
}

/*=======================================================================*\
	Function:	is_client_error
	Purpose:	return true if Error status code, and its a client error
\*=======================================================================*/
function is_client_error ($sc)
{ 
	return $sc >= 400 && $sc < 500; 
}

/*=======================================================================*\
	Function:	is_client_error
	Purpose:	return true if Error status code, and its a server error
\*=======================================================================*/
function is_server_error ($sc)
{ 
	return $sc >= 500 && $sc < 600; 
}












/*
 * Project:	  MagpieRSS: a simple RSS integration tool
 * File:		  rss_cache.inc, a simple, rolling(no GC), cache 
 *				for RSS objects, keyed on URL.
 * Author:		Kellan Elliott-McCrea <kellan@protest.net>
 * Version:		0.51
 * License:		GPL
 *
 * The lastest version of MagpieRSS can be obtained from:
 * http://magpierss.sourceforge.net
 *
 * For questions, help, comments, discussion, etc., please join the
 * Magpie mailing list:
 * http://lists.sourceforge.net/lists/listinfo/magpierss-general
 *
 */
class RSSCache {
	var $BASE_CACHE = './cache';	// where the cache files are stored
	var $MAX_AGE	= 3600;  		// when are files stale, default one hour
	var $ERROR 		= "";			// accumulate error messages
	
	function RSSCache ($base='', $age='')
	{
	
		// Make a local reference of the ExpressionEngine super object
		$this->EE =& get_instance();

		if ( $base )
		{
			$this->BASE_CACHE = $base;
		}
		if ( $age )
		{
			$this->MAX_AGE = $age;
		}
		
		// attempt to make the cache directory
		if ( ! file_exists( $this->BASE_CACHE ) )
		{
			$status = @mkdir( $this->BASE_CACHE, DIR_READ_MODE );
			@chmod($this->BASE_CACHE, DIR_WRITE_MODE);
			
			// if make failed 
			if ( ! $status )
			{
				$this->error(
					"Cache couldn't make dir '" . $this->BASE_CACHE . "'."
				);
			}
		}
		else
		{
			// EE - Make sure cache is 777
			@chmod($this->BASE_CACHE, DIR_WRITE_MODE);
		}
	}
	
/*=======================================================================*\
	Function:	set
	Purpose:	add an item to the cache, keyed on url
	Input:		url from wich the rss file was fetched
	Output:		true on sucess	
\*=======================================================================*/
	function set ($url, $rss)
	{
		$this->ERROR = "";
		$cache_file = $this->file_name( $url );
		$fp = @fopen( $cache_file, 'w' );
		
		if ( ! $fp )
		{
			$this->error(
				"Cache unable to open file for writing: $cache_file"
			);
			return 0;
		}
		
		
		$data = serialize( $rss );
		fwrite( $fp, $data );
		fclose( $fp );
		
		@chmod($cache_file, FILE_WRITE_MODE);
		
		return $cache_file;
	}
	
/*=======================================================================*\
	Function:	get
	Purpose:	fetch an item from the cache
	Input:		url from wich the rss file was fetched
	Output:		cached object on HIT, false on MISS	
\*=======================================================================*/	
	function get ($url)
	{
		$this->ERROR = "";
		$cache_file = $this->file_name( $url );
		
		if ( ! file_exists( $cache_file ) )
		{
			$this->debug( 
				"Cache doesn't contain: $url (cache file: $cache_file)"
			);
			return 0;
		}
		
		$fp = @fopen($cache_file, 'r');
		if ( ! $fp )
		{
			$this->error(
				"Failed to open cache file for reading: $cache_file"
			);
			return 0;
		}
		
		if (($file_size = filesize($cache_file)) == 0)
		{
			return 0;
		}
		
		$data = fread( $fp, $file_size );
		$rss = unserialize( $data );
		
		@chmod($cache_file, FILE_WRITE_MODE);
		
		return $rss;
	}

/*=======================================================================*\
	Function:	check_cache
	Purpose:	check a url for membership in the cache
				and whether the object is older then MAX_AGE (ie. STALE)
	Input:		url from wich the rss file was fetched
	Output:		cached object on HIT, false on MISS	
\*=======================================================================*/		
	function check_cache ( $url )
	{
		$this->ERROR = "";
		$filename = $this->file_name( $url );
		
		if ( file_exists( $filename ) )
		{
			// find how long ago the file was added to the cache
			// and whether that is longer then MAX_AGE
			$mtime = filemtime( $filename );
			$age = time() - $mtime;
			if ( $this->MAX_AGE > $age )
			{
				// object exists and is current
				return 'HIT';
			}
			else
			{
				// object exists but is old
				return 'STALE';
			}
		}
		else
		{
			// object does not exist
			return 'MISS';
		}
	}


	
/*=======================================================================*\
	Function:	file_name
	Purpose:	map url to location in cache
	Input:		url from wich the rss file was fetched
	Output:		a file name
\*=======================================================================*/		
	function file_name ($url)
	{		
		$filename = md5( $url );
		return reduce_double_slashes(join( DIRECTORY_SEPARATOR, array( $this->BASE_CACHE, $filename)));
	}

/*=======================================================================*\
	Function:	error
	Purpose:	register error
\*=======================================================================*/			
	function error ($errormsg, $lvl=E_USER_WARNING)
	{
		// append PHP's error message if track_errors enabled
		if ( isset($php_errormsg) )
		{ 
			$errormsg .= " ($php_errormsg)";
		}
		$this->ERROR = $errormsg;
		if ( MAGPIE_DEBUG )
		{
			trigger_error( $errormsg, $lvl);
		}
		else
		{
			error_log( $errormsg, 0);
		}
	}
	
	function debug ($debugmsg, $lvl=E_USER_NOTICE)
	{
		if ( MAGPIE_DEBUG )
		{
			$this->error("MagpieRSS [debug] $debugmsg", $lvl);
		}
	}

}














/*************************************************

Snoopy - the PHP net client
Author: Monte Ohrt <monte@ispi.net>
Copyright (c): 1999-2008 New Digital Group, all rights reserved
Version: 1.2.4

 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

You may contact the author of Snoopy by e-mail at:
monte@ohrt.com

The latest version of Snoopy can be obtained from:
http://snoopy.sourceforge.net/

*************************************************/

class M_Snoopy
{
	/**** Public variables ****/
	
	/* user definable vars */

	var $host			=	"www.php.net";		// host name we are connecting to
	var $port			=	80;					// port we are connecting to
	var $proxy_host		=	"";					// proxy host to use
	var $proxy_port		=	"";					// proxy port to use
	var $proxy_user		=	"";					// proxy user to use
	var $proxy_pass		=	"";					// proxy password to use
	
	var $agent			=	"Snoopy v1.2.4";	// agent we masquerade as
	var	$referer		=	"";					// referer info to pass
	var $cookies		=	array();			// array of cookies to pass
												// $cookies["username"]="joe";
	var	$rawheaders		=	array();			// array of raw headers to send
												// $rawheaders["Content-type"]="text/html";

	var $maxredirs		=	5;					// http redirection depth maximum. 0 = disallow
	var $lastredirectaddr	=	"";				// contains address of last redirected address
	var	$offsiteok		=	true;				// allows redirection off-site
	var $maxframes		=	0;					// frame content depth maximum. 0 = disallow
	var $expandlinks	=	true;				// expand links to fully qualified URLs.
												// this only applies to fetchlinks()
												// submitlinks(), and submittext()
	var $passcookies	=	true;				// pass set cookies back through redirects
												// NOTE: this currently does not respect
												// dates, domains or paths.
	
	var	$user			=	"";					// user for http authentication
	var	$pass			=	"";					// password for http authentication
	
	// http accept types
	var $accept			=	"image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, */*";
	
	var $results		=	"";					// where the content is put
		
	var $error			=	"";					// error messages sent here
	var	$response_code	=	"";					// response code returned from server
	var	$headers		=	array();			// headers returned from server sent here
	var	$maxlength		=	500000;				// max return data length (body)
	var $read_timeout	=	0;					// timeout on read operations, in seconds
												// supported only since PHP 4 Beta 4
												// set to 0 to disallow timeouts
	var $timed_out		=	false;				// if a read operation timed out
	var	$status			=	0;					// http request status

	var $temp_dir		=	"/tmp";				// temporary directory that the webserver
												// has permission to write to.
												// under Windows, this should be C:\temp

	var	$curl_path		=	"/usr/local/bin/curl";
												// Snoopy will use cURL for fetching
												// SSL content if a full system path to
												// the cURL binary is supplied here.
												// set to false if you do not have
												// cURL installed. See http://curl.haxx.se
												// for details on installing cURL.
												// Snoopy does *not* use the cURL
												// library functions built into php,
												// as these functions are not stable
												// as of this Snoopy release.
	
	/**** Private variables ****/	
	
	var	$_maxlinelen	=	4096;				// max line length (headers)
	
	var $_httpmethod	=	"GET";				// default http request method
	var $_httpversion	=	"HTTP/1.0";			// default http request version
	var $_submit_method	=	"POST";				// default submit method
	var $_submit_type	=	"application/x-www-form-urlencoded";	// default submit type
	var $_mime_boundary	=   "";					// MIME boundary for multipart/form-data submit type
	var $_redirectaddr	=	false;				// will be set if page fetched is a redirect
	var $_redirectdepth	=	0;					// increments on an http redirect
	var $_frameurls		= 	array();			// frame src urls
	var $_framedepth	=	0;					// increments on frame depth
	
	var $_isproxy		=	false;				// set if using a proxy server
	var $_fp_timeout	=	30;					// timeout for socket connection

/*======================================================================*\
	Function:	fetch
	Purpose:	fetch the contents of a web page
				(and possibly other protocols in the
				future like ftp, nntp, gopher, etc.)
	Input:		$URI	the location of the page to fetch
	Output:		$this->results	the output text from the fetch
\*======================================================================*/

	function fetch($URI)
	{
	
		//preg_match("|^([^:]+)://([^:/]+)(:[\d]+)*(.*)|",$URI,$URI_PARTS);
		$URI_PARTS = parse_url($URI);
		if ( ! empty($URI_PARTS["user"]))
		{
			$this->user = $URI_PARTS["user"];
		}

		if ( ! empty($URI_PARTS["pass"]))
		{
			$this->pass = $URI_PARTS["pass"];
		}

		if (empty($URI_PARTS["query"]))
		{
			$URI_PARTS["query"] = '';
		}

		if (empty($URI_PARTS["path"]))
		{
			$URI_PARTS["path"] = '';
		}
				
		switch(strtolower($URI_PARTS["scheme"]))
		{
			case "http":
				$this->host = $URI_PARTS["host"];
				
				// Default to 80, cannot connect without a port
				$this->port = empty($URI_PARTS["port"]) ? 80 : $URI_PARTS["port"];
				
				if ($this->_connect($fp))
				{
					// We needed port 80 to connect, but we can now switch to empty
					// for the Host header. Some servers will try to redirect if the
					// host header contains a port, which would previously create a loop.
					
					if (empty($URI_PARTS["port"]))
					{
						$this->port = '';
					}
					
					if ($this->_isproxy)
					{
						// using proxy, send entire URI
						$this->_httprequest($URI,$fp,$URI,$this->_httpmethod);
					}
					else
					{
						$path = $URI_PARTS["path"].($URI_PARTS["query"] ? "?".$URI_PARTS["query"] : "");
						// no proxy, send only the path
						$this->_httprequest($path, $fp, $URI, $this->_httpmethod);
					}
					
					$this->_disconnect($fp);

					if ($this->_redirectaddr)
					{
						/* url was redirected, check if we've hit the max depth */
						if ($this->maxredirs > $this->_redirectdepth)
						{
							// only follow redirect if it's on this site, or offsiteok is true
							if (preg_match("|^http://".preg_quote($this->host)."|i",$this->_redirectaddr) || $this->offsiteok)
							{
								/* follow the redirect */
								$this->_redirectdepth++;
								$this->lastredirectaddr=$this->_redirectaddr;
								$this->fetch($this->_redirectaddr);
							}
						}
					}

					if ($this->_framedepth < $this->maxframes && count($this->_frameurls) > 0)
					{
						$frameurls = $this->_frameurls;
						$this->_frameurls = array();
						
						while(list(,$frameurl) = each($frameurls))
						{
							if ($this->_framedepth < $this->maxframes)
							{
								$this->fetch($frameurl);
								$this->_framedepth++;
							}
							else
							{
								break;	
							}
						}
					}					
				}
				else
				{
					return false;
				}

				return true;					
				break;
			case "https":
				if ( ! $this->curl_path)
				{
					return false;	
				}

				if (function_exists("is_executable") AND ! is_executable($this->curl_path))
				{
					return false;	
				}
				
				$this->host = $URI_PARTS["host"];

				if ( ! empty($URI_PARTS["port"]))
				{
					$this->port = $URI_PARTS["port"];	
				}

				if ($this->_isproxy)
				{
					// using proxy, send entire URI
					$this->_httpsrequest($URI,$URI,$this->_httpmethod);
				}
				else
				{
					$path = $URI_PARTS["path"].($URI_PARTS["query"] ? "?".$URI_PARTS["query"] : "");
					// no proxy, send only the path
					$this->_httpsrequest($path, $URI, $this->_httpmethod);
				}

				if ($this->_redirectaddr)
				{
					/* url was redirected, check if we've hit the max depth */
					if ($this->maxredirs > $this->_redirectdepth)
					{
						// only follow redirect if it's on this site, or offsiteok is true
						if (preg_match("|^http://".preg_quote($this->host)."|i",$this->_redirectaddr) || $this->offsiteok)
						{
							/* follow the redirect */
							$this->_redirectdepth++;
							$this->lastredirectaddr=$this->_redirectaddr;
							$this->fetch($this->_redirectaddr);
						}
					}
				}

				if ($this->_framedepth < $this->maxframes && count($this->_frameurls) > 0)
				{
					$frameurls = $this->_frameurls;
					$this->_frameurls = array();

					while (list(,$frameurl) = each($frameurls))
					{
						if ($this->_framedepth < $this->maxframes)
						{
							$this->fetch($frameurl);
							$this->_framedepth++;
						}
						else
						{
							break;
						}
					}
				}					

				return true;					
				break;
			default:
				// not a valid protocol
				$this->error	=	'Invalid protocol "'.$URI_PARTS["scheme"].'"\n';
				return false;
				break;
		}		
		return true;
	}

/*======================================================================*\
	Private functions
\*======================================================================*/
	
	
/*======================================================================*\
	Function:	_striplinks
	Purpose:	strip the hyperlinks from an html document
	Input:		$document	document to strip.
	Output:		$match		an array of the links
\*======================================================================*/

	function _striplinks($document)
	{	
		/*  1. find <a href=
			2. find single or double quote
			3. if quote found, match up to next matching quote, otherwise match 
			  up to next space
	    */

		preg_match_all(
			"'<\s*a\s.*?href\s*=\s*([\"\'])?(?(1) (.*?)\\1 | ([^\s\>]+))'isx",
			$document,
			$links
		);

		// catenate the non-empty matches from the conditional subpattern

		while (list($key,$val) = each($links[2]))
		{
			if ( ! empty($val))
			{
				$match[] = $val;	
			}
		}				
		
		while (list($key,$val) = each($links[3]))
		{
			if ( ! empty($val))
			{
				$match[] = $val;				
			}
		}		
		
		// return the links
		return $match;
	}

/*======================================================================*\
	Function:	_stripform
	Purpose:	strip the form elements from an html document
	Input:		$document	document to strip.
	Output:		$match		an array of the links
\*======================================================================*/

	function _stripform($document)
	{	
		preg_match_all("'<\/?(FORM|INPUT|SELECT|TEXTAREA|(OPTION))[^<>]*>(?(2)(.*(?=<\/?(option|select)[^<>]*>[\r\n]*)|(?=[\r\n]*))|(?=[\r\n]*))'Usi",$document,$elements);
		
		// catenate the matches
		$match = implode("\r\n",$elements[0]);
				
		// return the links
		return $match;
	}

	
	
/*======================================================================*\
	Function:	_striptext
	Purpose:	strip the text from an html document
	Input:		$document	document to strip.
	Output:		$text		the resulting text
\*======================================================================*/

	function _striptext($document)
	{
		
		// I didn't use preg eval (//e) since that is only available in PHP 4.0.
		// so, list your entities one by one here. I included some of the
		// more common ones.
								
		$search = array(
			"'<script[^>]*?>.*?</script>'si",	// strip out javascript
			"'<[\/\!]*?[^<>]*?>'si",			// strip out html tags
			"'([\r\n])[\s]+'",					// strip out white space
			"'&(quot|#34|#034|#x22);'i",		// replace html entities
			"'&(amp|#38|#038|#x26);'i",			// added hexadecimal values
			"'&(lt|#60|#060|#x3c);'i",
			"'&(gt|#62|#062|#x3e);'i",
			"'&(nbsp|#160|#xa0);'i",
			"'&(iexcl|#161);'i",
			"'&(cent|#162);'i",
			"'&(pound|#163);'i",
			"'&(copy|#169);'i",
			"'&(reg|#174);'i",
			"'&(deg|#176);'i",
			"'&(#39|#039|#x27);'",
			"'&(euro|#8364);'i",				// europe
			"'&a(uml|UML);'",					// german
			"'&o(uml|UML);'",
			"'&u(uml|UML);'",
			"'&A(uml|UML);'",
			"'&O(uml|UML);'",
			"'&U(uml|UML);'",
			"'&szlig;'i",
		);

		$replace = array(
			"",
			"",
			"\\1",
			"\"",
			"&",
			"<",
			">",
			" ",
			chr(161),
			chr(162),
			chr(163),
			chr(169),
			chr(174),
			chr(176),
			chr(39),
			chr(128),
			"ä",
			"ö",
			"ü",
			"Ä",
			"Ö",
			"Ü",
			"ß",
		);
					
		$text = preg_replace($search, $replace, $document);
								
		return $text;
	}

/*======================================================================*\
	Function:	_expandlinks
	Purpose:	expand each link into a fully qualified URL
	Input:		$links			the links to qualify
				$URI			the full URI to get the base from
	Output:		$expandedLinks	the expanded links
\*======================================================================*/

	function _expandlinks($links,$URI)
	{
		
		preg_match("/^[^\?]+/",$URI,$match);

		$match = preg_replace("|/[^\/\.]+\.[^\/\.]+$|","",$match[0]);
		$match = preg_replace("|/$|","",$match);
		$match_part = parse_url($match);
		$match_root = $match_part["scheme"]."://".$match_part["host"];
				
		$search = array(
			"|^http://".preg_quote($this->host)."|i",
			"|^(\/)|i",
			"|^(?!http://)(?!mailto:)|i",
			"|/\./|",
			"|/[^\/]+/\.\./|"
		);
						
		$replace = array(
			"",
			$match_root."/",
			$match."/",
			"/",
			"/"
		);			
				
		$expandedLinks = preg_replace($search, $replace, $links);

		return $expandedLinks;
	}

/*======================================================================*\
	Function:	_httprequest
	Purpose:	go get the http data from the server
	Input:		$url		the url to fetch
				$fp			the current open file pointer
				$URI		the full URI
				$body		body contents to send if any (POST)
	Output:		
\*======================================================================*/
	
	function _httprequest($url,$fp,$URI,$http_method,$content_type="",$body="")
	{
		$cookie_headers = '';
		if ($this->passcookies && $this->_redirectaddr)
		{
			$this->setcookies();	
		}
			
		$URI_PARTS = parse_url($URI);
		if (empty($url))
		{
			$url = "/";	
		}

		$headers = $http_method." ".$url." ".$this->_httpversion."\r\n";		
		
		if ( ! empty($this->agent))
		{
			$headers .= "User-Agent: ".$this->agent."\r\n";			
		}

		if ( ! empty($this->host) && !isset($this->rawheaders['Host']))
		{
			$headers .= "Host: ".$this->host;
		
			if ( ! empty($this->port))
			{
				$headers .= ":".$this->port;	
			}
		
			$headers .= "\r\n";
		}

		if ( ! empty($this->accept))
		{
			$headers .= "Accept: ".$this->accept."\r\n";			
		}

		if ( ! empty($this->referer))
		{
			$headers .= "Referer: ".$this->referer."\r\n";	
		}
		
		if ( ! empty($this->cookies))
		{			
			if ( ! is_array($this->cookies))
			{
				$this->cookies = (array)$this->cookies;	
			}
	
			reset($this->cookies);

			if (count($this->cookies) > 0)
			{
			
				$cookie_headers .= 'Cookie: ';
			
				foreach ($this->cookies as $cookieKey => $cookieVal)
				{
					$cookie_headers .= $cookieKey."=".urlencode($cookieVal)."; ";
				}
			
				$headers .= substr($cookie_headers,0,-2) . "\r\n";
			} 
		}
		
		if ( ! empty($this->rawheaders))
		{
			if ( ! is_array($this->rawheaders))
			{
				$this->rawheaders = (array)$this->rawheaders;	
			}

			while (list($headerKey,$headerVal) = each($this->rawheaders))
			{
				$headers .= $headerKey.": ".$headerVal."\r\n";	
			}
		}
		
		if ( ! empty($content_type))
		{
			$headers .= "Content-type: $content_type";

			if ($content_type == "multipart/form-data")
			{
				$headers .= "; boundary=".$this->_mime_boundary;	
			}

			$headers .= "\r\n";
		}

		if ( ! empty($body))
		{
			$headers .= "Content-length: ".strlen($body)."\r\n";	
		}
		
		if ( ! empty($this->user) || !empty($this->pass))
		{
			$headers .= "Authorization: Basic ".base64_encode($this->user.":".$this->pass)."\r\n";
		}
		
		//add proxy auth headers
		if ( ! empty($this->proxy_user))
		{
			$headers .= 'Proxy-Authorization: ' . 'Basic ' . base64_encode($this->proxy_user . ':' . $this->proxy_pass)."\r\n";
		}

		$headers .= "\r\n";
		
		// set the read timeout if needed
		if ($this->read_timeout > 0)
		{
			socket_set_timeout($fp, $this->read_timeout);	
		}
		
		$this->timed_out = false;
		
		fwrite($fp,$headers.$body,strlen($headers.$body));
		
		$this->_redirectaddr = false;
		unset($this->headers);
						
		while($currentHeader = fgets($fp,$this->_maxlinelen))
		{
			if ($this->read_timeout > 0 && $this->_check_timeout($fp))
			{
				$this->status=-100;
				return false;
			}
				
			if ($currentHeader == "\r\n")
			{
				break;
			}
						
			// if a header begins with Location: or URI:, set the redirect
			if (preg_match("/^(Location:|URI:)/i",$currentHeader))
			{
				// get URL portion of the redirect
				preg_match("/^(Location:|URI:)[ ]+(.*)/i",chop($currentHeader),$matches);
				// look for :// in the Location header to see if hostname is included
				if ( ! preg_match("|\:\/\/|",$matches[2]))
				{
					// no host in the path, so prepend
					$this->_redirectaddr = $URI_PARTS["scheme"]."://".$this->host.":".$this->port;
					// eliminate double slash
					if ( ! preg_match("|^/|",$matches[2]))
					{
						$this->_redirectaddr .= "/".$matches[2];
					}
					else
					{
						$this->_redirectaddr .= $matches[2];						
					}
				}
				else
				{
					$this->_redirectaddr = $matches[2];	
				}
			}
		
			if (preg_match("|^HTTP/|",$currentHeader))
			{
                if (preg_match("|^HTTP/[^\s]*\s(.*?)\s|",$currentHeader, $status))
				{
					$this->status= $status[1];
                }				
				$this->response_code = $currentHeader;
			}
				
			$this->headers[] = $currentHeader;
		}

		$results = '';

		do {
    		$_data = fread($fp, $this->maxlength);
    	
    		if (strlen($_data) == 0)
    		{
        		break;
    		}
    	
    		$results .= $_data;
		} while(true);

		if ($this->read_timeout > 0 && $this->_check_timeout($fp))
		{
			$this->status=-100;
			return false;
		}
		
		// check if there is a a redirect meta tag
		
		if (preg_match("'<meta[\s]*http-equiv[^>]*?content[\s]*=[\s]*[\"\']?\d+;[\s]*URL[\s]*=[\s]*([^\"\']*?)[\"\']?>'i",$results,$match))

		{
			$this->_redirectaddr = $this->_expandlinks($match[1],$URI);	
		}

		// have we hit our frame depth and is there frame src to fetch?
		if (($this->_framedepth < $this->maxframes) && preg_match_all("'<frame\s+.*src[\s]*=[\'\"]?([^\'\"\>]+)'i",$results,$match))
		{
			$this->results[] = $results;
			for($x=0; $x<count($match[1]); $x++)
			{
				$this->_frameurls[] = $this->_expandlinks($match[1][$x],$URI_PARTS["scheme"]."://".$this->host);	
			}
		}
		// have we already fetched framed content?
		elseif (is_array($this->results))
		{
			$this->results[] = $results;	
		}
		// no framed content
		else
		{
			$this->results = $results;	
		}
		
		return true;
	}

/*======================================================================*\
	Function:	_httpsrequest
	Purpose:	go get the https data from the server using curl
	Input:		$url		the url to fetch
				$URI		the full URI
				$body		body contents to send if any (POST)
	Output:		
\*======================================================================*/
	
	function _httpsrequest($url,$URI,$http_method,$content_type="",$body="")
	{  
		if ($this->passcookies && $this->_redirectaddr)
		{
			$this->setcookies();	
		}

		$headers = array();		
					
		$URI_PARTS = parse_url($URI);

		if (empty($url))
		{
			$url = "/";	
		}

		// GET ... header not needed for curl
		//$headers[] = $http_method." ".$url." ".$this->_httpversion;		
		if ( ! empty($this->agent))
		{
			$headers[] = "User-Agent: ".$this->agent;	
		}

		if ( ! empty($this->host))
		{
			if ( ! empty($this->port))
			{
				$headers[] = "Host: ".$this->host.":".$this->port;
			}
			else
			{
				$headers[] = "Host: ".$this->host;
			}	
		}

		if ( ! empty($this->accept))
		{
			$headers[] = "Accept: ".$this->accept;
		}
		if ( ! empty($this->referer))
		{
			$headers[] = "Referer: ".$this->referer;
		}
		if ( ! empty($this->cookies))
		{			
			if ( ! is_array($this->cookies))
			{
				$this->cookies = (array)$this->cookies;
			}
	
			reset($this->cookies);

			if (count($this->cookies) > 0)
			{
				$cookie_str = 'Cookie: ';

				foreach ( $this->cookies as $cookieKey => $cookieVal )
				{
					$cookie_str .= $cookieKey."=".urlencode($cookieVal)."; ";
				}

				$headers[] = substr($cookie_str,0,-2);
			}
		}

		if ( ! empty($this->rawheaders))
		{
			if ( ! is_array($this->rawheaders))
			{
				$this->rawheaders = (array)$this->rawheaders;
			}

			while (list($headerKey,$headerVal) = each($this->rawheaders))
			{
				$headers[] = $headerKey.": ".$headerVal;
			}
		}
		if ( ! empty($content_type))
		{
			if ($content_type == "multipart/form-data")
			{
				$headers[] = "Content-type: $content_type; boundary=".$this->_mime_boundary;
			}
			else
			{
				$headers[] = "Content-type: $content_type";
			}
		}

		if ( ! empty($body))	
		{
			$headers[] = "Content-length: ".strlen($body);
		}

		if ( ! empty($this->user) || !empty($this->pass))	
		{
			$headers[] = "Authorization: BASIC ".base64_encode($this->user.":".$this->pass);
		}
			
		for($curr_header = 0; $curr_header < count($headers); $curr_header++)
		{
			$safer_header = strtr( $headers[$curr_header], "\"", " " );
			$cmdline_params .= " -H \"".$safer_header."\"";
		}
		
		if ( ! empty($body))
		{
			$cmdline_params .= " -d \"$body\"";
		}
		
		if ($this->read_timeout > 0)
		{
			$cmdline_params .= " -m ".$this->read_timeout;
		}
		
		$headerfile = tempnam($temp_dir, "sno");


		exec($this->curl_path." -k -D \"$headerfile\"".$cmdline_params." \"".escapeshellcmd($URI)."\"",$results,$return);
		
		if ($return)
		{
			$this->error = "Error: cURL could not retrieve the document, error $return.";
			return false;
		}
			
		$results = implode("\r\n",$results);
		
		$result_headers = file("$headerfile");
						
		$this->_redirectaddr = false;
		unset($this->headers);
						
		for($currentHeader = 0; $currentHeader < count($result_headers); $currentHeader++)
		{
			// if a header begins with Location: or URI:, set the redirect
			if (preg_match("/^(Location: |URI: )/i",$result_headers[$currentHeader]))
			{
				// get URL portion of the redirect
				preg_match("/^(Location: |URI:)\s+(.*)/",chop($result_headers[$currentHeader]),$matches);
				// look for :// in the Location header to see if hostname is included
				if ( ! preg_match("|\:\/\/|",$matches[2]))
				{
					// no host in the path, so prepend
					$this->_redirectaddr = $URI_PARTS["scheme"]."://".$this->host.":".$this->port;
					// eliminate double slash
					if ( ! preg_match("|^/|",$matches[2]))
					{
						$this->_redirectaddr .= "/".$matches[2];
					}
					else
					{
						$this->_redirectaddr .= $matches[2];
					}
				}
				else
				{
					$this->_redirectaddr = $matches[2];
				}
			}
		
			if (preg_match("|^HTTP/|",$result_headers[$currentHeader]))
			{
				$this->response_code = $result_headers[$currentHeader];
			}

			$this->headers[] = $result_headers[$currentHeader];
		}

		// check if there is a a redirect meta tag
		
		if (preg_match("'<meta[\s]*http-equiv[^>]*?content[\s]*=[\s]*[\"\']?\d+;[\s]*URL[\s]*=[\s]*([^\"\']*?)[\"\']?>'i",$results,$match))
		{
			$this->_redirectaddr = $this->_expandlinks($match[1],$URI);	
		}

		// have we hit our frame depth and is there frame src to fetch?
		if (($this->_framedepth < $this->maxframes) && preg_match_all("'<frame\s+.*src[\s]*=[\'\"]?([^\'\"\>]+)'i",$results,$match))
		{
			$this->results[] = $results;
			for($x=0; $x<count($match[1]); $x++)
			{
				$this->_frameurls[] = $this->_expandlinks($match[1][$x],$URI_PARTS["scheme"]."://".$this->host);
			}
		}
		// have we already fetched framed content?
		elseif (is_array($this->results))
		{
			$this->results[] = $results;
		}
		// no framed content
		else
		{
			$this->results = $results;
		}

		unlink("$headerfile");
		
		return true;
	}

/*======================================================================*\
	Function:	setcookies()
	Purpose:	set cookies for a redirection
\*======================================================================*/
	
	function setcookies()
	{
		for ($x=0; $x<count($this->headers); $x++)
		{
			if (preg_match('/^set-cookie:[\s]+([^=]+)=([^;]+)/i', $this->headers[$x],$match))
			{
				$this->cookies[$match[1]] = urldecode($match[2]);
			}
		}
	}

	
/*======================================================================*\
	Function:	_check_timeout
	Purpose:	checks whether timeout has occurred
	Input:		$fp	file pointer
\*======================================================================*/

	function _check_timeout($fp)
	{
		if ($this->read_timeout > 0)
		{
			$fp_status = socket_get_status($fp);
			
			if ($fp_status["timed_out"])
			{
				$this->timed_out = true;
				return true;
			}
		}
		return false;
	}

/*======================================================================*\
	Function:	_connect
	Purpose:	make a socket connection
	Input:		$fp	file pointer
\*======================================================================*/
	
	function _connect(&$fp)
	{
		if ( ! empty($this->proxy_host) && !empty($this->proxy_port))
		{
			$this->_isproxy = true;
			
			$host = $this->proxy_host;
			$port = $this->proxy_port;
		}
		else
		{
			$host = $this->host;
			$port = $this->port;
		}
	
		$this->status = 0;
		
		$fp = fsockopen($host, $port, $errno, $errstr, $this->_fp_timeout);

		if ($fp)
		{
			// socket connection succeeded

			return true;
		}
		else
		{
			// socket connection failed
			$this->status = $errno;
			switch($errno)
			{
				case -3:
					$this->error="socket creation failed (-3)";
				case -4:
					$this->error="dns lookup failure (-4)";
				case -5:
					$this->error="connection refused or timed out (-5)";
				default:
					$this->error="connection failed (".$errno.")";
			}
			return false;
		}
	}
/*======================================================================*\
	Function:	_disconnect
	Purpose:	disconnect a socket connection
	Input:		$fp	file pointer
\*======================================================================*/
	
	function _disconnect($fp)
	{
		return(fclose($fp));
	}

	
/*======================================================================*\
	Function:	_prepare_post_body
	Purpose:	Prepare post body according to encoding type
	Input:		$formvars  - form variables
				$formfiles - form upload files
	Output:		post body
\*======================================================================*/
	
	function _prepare_post_body($formvars, $formfiles)
	{
		settype($formvars, "array");
		settype($formfiles, "array");
		$postdata = '';

		if (count($formvars) == 0 && count($formfiles) == 0)
		{
			return;
		}
		
		switch ($this->_submit_type)
		{
			case "application/x-www-form-urlencoded":
				reset($formvars);
				while(list($key,$val) = each($formvars))
				{
					if (is_array($val) || is_object($val))
					{
						while (list($cur_key, $cur_val) = each($val))
						{
							$postdata .= urlencode($key)."[]=".urlencode($cur_val)."&";
						}
					} else
					{
						$postdata .= urlencode($key)."=".urlencode($val)."&";
					}
				}
				break;

			case "multipart/form-data":
				$this->_mime_boundary = "Snoopy".md5(uniqid(microtime()));
				
				reset($formvars);
				while(list($key,$val) = each($formvars))
				{
					if (is_array($val) || is_object($val))
					{
						while (list($cur_key, $cur_val) = each($val))
						{
							$postdata .= "--".$this->_mime_boundary."\r\n";
							$postdata .= "Content-Disposition: form-data; name=\"$key\[\]\"\r\n\r\n";
							$postdata .= "$cur_val\r\n";
						}
					}
					else
					{
						$postdata .= "--".$this->_mime_boundary."\r\n";
						$postdata .= "Content-Disposition: form-data; name=\"$key\"\r\n\r\n";
						$postdata .= "$val\r\n";
					}
				}
				
				reset($formfiles);

				while (list($field_name, $file_names) = each($formfiles))
				{
					settype($file_names, "array");
					while (list(, $file_name) = each($file_names))
					{
						if ( ! is_readable($file_name))
						{
							continue;
						}

						$fp = fopen($file_name, "r");
						$file_content = fread($fp, filesize($file_name));
						fclose($fp);
						$base_name = basename($file_name);

						$postdata .= "--".$this->_mime_boundary."\r\n";
						$postdata .= "Content-Disposition: form-data; name=\"$field_name\"; filename=\"$base_name\"\r\n\r\n";
						$postdata .= "$file_content\r\n";
					}
				}

				$postdata .= "--".$this->_mime_boundary."--\r\n";
				break;
		}

		return $postdata;
	}
}




/* End of file pi.magpie.php */
/* Location: ./system/expressionengine/plugins/pi.magpie.php */