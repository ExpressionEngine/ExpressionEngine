<?php

/*
=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 http://expressionengine.com/
-----------------------------------------------------
 Copyright (c) 2004 - 2012 EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 http://expressionengine.com/user_guide/license.html
=====================================================
 File: pi.rss_parser.php
-----------------------------------------------------
 Purpose: RSS Parser plugin
=====================================================

*/

$plugin_info = array(
	'pi_name'			=> 'RSS Parser',
	'pi_version'		=> '1.0',
	'pi_author'			=> 'Wes Baker',
	'pi_author_url'		=> 'http://expressionengine.com/',
	'pi_description'	=> 'Retrieves and Parses RSS/Atom Feeds',
	'pi_usage'			=> Rss_parser::usage()
);
					

Class Rss_parser {	

	var $cache_name		= 'rss_parser_cache';					// Name of cache directory
	var $cache_data		= '';									// Data from cache file
	var $cache_path		= '';									// Path to cache file.
	var $cache_tpath	= '';									// Path to cache file's time file.
	var $items			= array();								// Information about items returned
	var $dates			= array('lastupdate','linkcreated');	// Date elements							
	var $return_data	= ''; 									// Data sent back to Template parser

	function Rss_parser()
	{
	 	include_once('simplepie.inc');

		// Make a local reference of the ExpressionEngine super object
		$this->EE =& get_instance();
	 	
	 	// Retrieve parameters
	 	$cache_refresh 	= $this->EE->TMPL->fetch_param('refresh', 360);
	 	$page_url 		= trim($this->EE->TMPL->fetch_param('url', ''));
	 	$limit			= (int) $this->EE->TMPL->fetch_param('limit', 20);
	 	$offset			= (int) $this->EE->TMPL->fetch_param('offset', 0);
	 	$template		= $this->EE->TMPL->tagdata;
	 	
	 	
	 	if ($page_url == '')
	 	{
	 		return $this->return_data;
	 	}	 	
	 	
	 	if ($this->EE->config->item('debug') == 2 OR ($this->EE->config->item('debug') == 1 && $this->EE->session->userdata['group_id'] == 1))
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
			define('MAGPIE_CACHE_AGE',	$cache_refresh * 60);
		}
		
		$this->RSS = fetch_rss($page_url);
		
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
						$tagdata = $this->EE->functions->prep_conditionals($temp_data, $item, '', 'magpie:');
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
		
		
		foreach ($this->EE->TMPL->var_single as $key => $val)
		{		  			
			
			/** ----------------------------------------
			/**  {feed_version} - Version of RSS/Atom Feed
			/** ----------------------------------------*/
			
			if ($key == "feed_version" OR $key == "magpie:feed_version")
			{
				if ( ! isset($this->RSS->feed_version)) $this->RSS->feed_version = '';
				
				$template = $this->EE->TMPL->swap_var_single($val, $this->RSS->feed_version, $template);
			}
			
			/** ----------------------------------------
			/**  {feed_type}
			/** ----------------------------------------*/
			
			if (($key == "feed_type" OR $key == "magpie:feed_type") && isset($this->RSS->feed_type))
			{
				if ( ! isset($this->RSS->feed_type)) $this->RSS->feed_type = '';
				
				$template = $this->EE->TMPL->swap_var_single($val, $this->RSS->feed_type, $template);
			}
			
			/** ----------------------------------------
			/**  Image related variables
			/** ----------------------------------------*/
			
			foreach ($image_variables as $variable)
			{
				if ($key == 'image_'.$variable OR $key == 'magpie:image_'.$variable)
				{
					if ( ! isset($this->RSS->image[$variable])) $this->RSS->image[$variable] = '';
				
					$template = $this->EE->TMPL->swap_var_single($val, $this->RSS->image[$variable], $template);
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
				
					$template = $this->EE->TMPL->swap_var_single($val, $this->RSS->channel[$variable], $template);
				}
			}			
			
			/** ----------------------------------------
			/**  {page_url}
			/** ----------------------------------------*/
			
			if ($key == 'page_url' OR $key == 'magpie:page_url')
			{
				$template = $this->EE->TMPL->swap_var_single($val, $page_url, $template);
			}	
		}
		
		$this->return_data = &$template;
	}

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


Example opening tag:  {exp:rss_parser url="http://expressionengine.com/feeds/rss/full/" limit="8" refresh="720"}

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

{exp:rss_parser url="http://expressionengine.com/feeds/rss/full/" limit="10" refresh="720"}
<ul>
{items}
<li><a href="{link}">{title}</a></li>
{/items}
</ul>
{/exp:rss_parser}
	<?php
	$buffer = ob_get_contents();
	
	ob_end_clean(); 

	return $buffer;
	}
}
// END Plugin





/* End of file pi.simplepie.php */
/* Location: ./system/expressionengine/plugins/pi.simplepie.php */