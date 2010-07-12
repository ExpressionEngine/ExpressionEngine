<?php

/*
=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 http://expressionengine.com/
-----------------------------------------------------
 Copyright (c) 2003 - 2010, EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 http://expressionengine.com/user_guide/license.html
=====================================================
 File: mod.rss.php
-----------------------------------------------------
 Purpose: RSS generating class
=====================================================
*/

if ( ! defined('EXT'))
{
	exit('Invalid file request');
}



class Rss {

	var $debug = FALSE;
	
	/**
	  *  RSS feed
	  *
	  * This function fetches the channel metadata used in
	  * the channel section of RSS feeds
	  *
	  * Note: The item elements are generated using the channel class
	  */
	function feed()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		$this->EE->TMPL->encode_email = FALSE;

		if ($this->EE->TMPL->fetch_param('debug') == 'yes')
		{
			$this->debug = TRUE;
		}
		
		if ( ! $channel = $this->EE->TMPL->fetch_param('channel'))
		{
			$this->EE->lang->loadfile('rss');
			return $this->_empty_feed($this->EE->lang->line('no_weblog_specified'));
		}

		//  Create Meta Query
		//
		// Since UTC_TIMESTAMP() is what we need, but it is not available until
		// MySQL 4.1.1, we have to use this ever so clever SQL to figure it out:
		// DATE_ADD( '1970-01-01', INTERVAL UNIX_TIMESTAMP() SECOND )
		// -Paul

		$sql = "SELECT exp_channel_titles.entry_id, exp_channel_titles.entry_date, exp_channel_titles.edit_date, 
				GREATEST((UNIX_TIMESTAMP(exp_channel_titles.edit_date) + 
						 (UNIX_TIMESTAMP(DATE_ADD( '1970-01-01', INTERVAL UNIX_TIMESTAMP() SECOND)) - UNIX_TIMESTAMP())),
						exp_channel_titles.entry_date) AS last_update
				FROM exp_channel_titles
				LEFT JOIN exp_channels ON exp_channel_titles.channel_id = exp_channels.channel_id 
				LEFT JOIN exp_members ON exp_members.member_id = exp_channel_titles.author_id
				WHERE exp_channel_titles.entry_id !=''
				AND exp_channels.site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."') ";

		$xql = "SELECT channel_id FROM exp_channels WHERE ";
	
		$str = $this->EE->functions->sql_andor_string($channel, 'channel_name');
		
		if (substr($str, 0, 3) == 'AND')
			$str = substr($str, 3);
		
		$xql .= $str;			
			
		$query = $this->EE->db->query($xql);
		
		if ($query->num_rows() == 0)
		{
			$this->EE->lang->loadfile('rss');;
			return $this->_empty_feed($this->EE->lang->line('rss_invalid_weblog'));
		}
		
		if ($query->num_rows() == 1)
		{
			$sql .= "AND exp_channel_titles.channel_id = '".$query->row('channel_id') ."' ";
		}
		else
		{
			$sql .= "AND (";
			
			foreach ($query->result_array() as $row)
			{
				$sql .= "exp_channel_titles.channel_id = '".$row['channel_id']."' OR ";
			}
			
			$sql = substr($sql, 0, - 3);
			
			$sql .= ") ";
		}

		//  We only select entries that have not expired 

		$timestamp = ($this->EE->TMPL->cache_timestamp != '') ? $this->EE->localize->set_gmt($this->EE->TMPL->cache_timestamp) : $this->EE->localize->now;

		if ($this->EE->TMPL->fetch_param('show_future_entries') != 'yes')
		{
			$sql .= " AND exp_channel_titles.entry_date < ".$timestamp." ";
		}
		
		if ($this->EE->TMPL->fetch_param('show_expired') != 'yes')
		{
			$sql .= " AND (exp_channel_titles.expiration_date = 0 OR exp_channel_titles.expiration_date > ".$timestamp.") ";
		}

		//  Add status declaration

		$sql .= "AND exp_channel_titles.status != 'closed' ";
		
		if ($status = $this->EE->TMPL->fetch_param('status'))
		{
			$status = str_replace('Open',	'open',	$status);
			$status = str_replace('Closed', 'closed', $status);
		
			$sql .= $this->EE->functions->sql_andor_string($status, 'exp_channel_titles.status');
		}
		else
		{
			$sql .= "AND exp_channel_titles.status = 'open' ";
		}

		//  Limit to (or exclude) specific users

		if ($username = $this->EE->TMPL->fetch_param('username'))
		{
			// Shows entries ONLY for currently logged in user
		
			if ($username == 'CURRENT_USER')
			{
				$sql .=  "AND exp_members.member_id = '".$this->EE->session->userdata['member_id']."' ";
			}
			elseif ($username == 'NOT_CURRENT_USER')
			{
				$sql .=  "AND exp_members.member_id != '".$this->EE->session->userdata['member_id']."' ";
			}
			else
			{				
				$sql .= $this->EE->functions->sql_andor_string($username, 'exp_members.username');
			}
		}
		
		// Find Edit Date
		$query = $this->EE->db->query($sql." ORDER BY last_update desc LIMIT 1");
		
		if ($query->num_rows() > 0)
		{
			$last_update = $query->row('last_update')  + ($this->EE->localize->set_server_time() - $this->EE->localize->now);
			$edit_date = $query->row('edit_date') ;
			$entry_date = $query->row('entry_date') ;
		}
				  
		$sql .= " ORDER BY exp_channel_titles.entry_date desc LIMIT 1";
		
		$query = $this->EE->db->query($sql);
		
		if ($query->num_rows() == 0)
		{
			$this->EE->lang->loadfile('rss');
			return $this->_empty_feed($this->EE->lang->line('no_matching_entries'));
		}
		
		$entry_id = $query->row('entry_id') ;
		
		$sql = "SELECT 	exp_channels.channel_id, exp_channels.channel_title, exp_channels.channel_url, exp_channels.channel_lang, exp_channels.channel_description,
							exp_channel_titles.entry_date,
					  	exp_members.email, exp_members.username, exp_members.screen_name, exp_members.url
				FROM	exp_channel_titles
				LEFT JOIN exp_channels ON exp_channel_titles.channel_id = exp_channels.channel_id 
				LEFT JOIN exp_members ON exp_members.member_id = exp_channel_titles.author_id
				WHERE exp_channel_titles.entry_id = '$entry_id'
				AND exp_channels.site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."') "; 
				
		$query = $this->EE->db->query($sql);
		
		foreach ($query->row_array() as $key => $val)
		{
			$$key = $val;
		}
		
		$request		= ( ! function_exists('getallheaders')) ? array() : @getallheaders();
		$start_on		= '';
		$diffe_request	= false;
		$feed_request	= false;

		//  Check for 'diff -e' request

		if (isset($request['A-IM']) && stristr($request['A-IM'],'diffe') !== false)
		{
			$items_start = strpos($this->EE->TMPL->tagdata, '{exp:channel:entries');
			
			if ($items_start !== false)
			{
				// We add three, for three line breaks added later in the script
				$diffe_request = count(preg_split("/(\r\n)|(\r)|(\n)/", trim(substr($this->EE->TMPL->tagdata,0,$items_start)))) + 3;
			}
		}

		//  Check for 'feed' request

		if (isset($request['A-IM']) && stristr($request['A-IM'],'feed') !== false)
		{
			$feed_request = true;
			$diffe_request = false;
		}

		//  Check for the 'If-Modified-Since' Header

		if ($this->EE->config->item('send_headers') == 'y' && isset($request['If-Modified-Since']) && trim($request['If-Modified-Since']) != '')
		{
			$x				= explode(';',$request['If-Modified-Since']);
			$modify_tstamp	=  strtotime($x['0']);
			
			// ---------------------------------------------------------
			//  If new content *and* 'feed' or 'diffe', create start on time.
			//  Otherwise, we send back a Not Modified header
			// ---------------------------------------------------------

			if ($last_update <= $modify_tstamp)
				{
					@header("HTTP/1.0 304 Not Modified");
				@header('HTTP/1.1 304 Not Modified');
				@exit;
				}
				else
				{
					if ($diffe_request !== false OR $feed_request !== false)
					{
						//$start_on = $this->EE->localize->set_human_time($this->EE->localize->set_server_time($modify_tstamp), FALSE);
						$start_on = gmdate('Y-m-d h:i A',$this->EE->localize->set_localized_time($modify_tstamp));
					}
				}
		}

		$chunks = array();
		$marker = 'H94e99Perdkie0393e89vqpp'; 
		
		if (preg_match_all("/{exp:channel:entries.+?{".'\/'."exp:channel:entries}/s", $this->EE->TMPL->tagdata, $matches))
		{
			for($i = 0; $i < count($matches['0']); $i++)
			{
				$this->EE->TMPL->tagdata = str_replace($matches['0'][$i], $marker.$i, $this->EE->TMPL->tagdata);
				
				// Remove limit if we have a start_on and dynamic_start
				if ($start_on != '' && stristr($matches['0'][$i],'dynamic_start="on"'))
				{
					$matches['0'][$i] = preg_replace("/limit=[\"\'][0-9]{1,5}[\"\']/", '', $matches['0'][$i]);
				}
				
				// Replace dynamic_start="on" parameter with start_on="" param
				$start_on_switch = ($start_on != '') ? 'start_on="'.$start_on.'"' : '';
				$matches['0'][$i] = preg_replace("/dynamic_start\s*=\s*[\"|']on[\"|']/i", $start_on_switch, $matches['0'][$i]);
				
				$chunks[$marker.$i] = $matches['0'][$i];
			}
		}

		// Fetch all the date-related variables
		// We do this here to avoid processing cycles in the foreach loop
		
		$entry_date_array 		= array();
		$gmt_date_array 		= array();
		$gmt_entry_date_array	= array();
		$edit_date_array 		= array();
		$gmt_edit_date_array	= array();
		
		$date_vars = array('date', 'gmt_date', 'gmt_entry_date', 'edit_date', 'gmt_edit_date');
				
		foreach ($date_vars as $val)
		{					
			if (preg_match_all("/".LD.$val."\s+format=[\"'](.*?)[\"']".RD."/s", $this->EE->TMPL->tagdata, $matches))
			{
				for ($j = 0; $j < count($matches['0']); $j++)
				{
					$matches['0'][$j] = str_replace(LD, '', $matches['0'][$j]);
					$matches['0'][$j] = str_replace(RD, '', $matches['0'][$j]);
					
					switch ($val)
					{
						case 'date' 			: $entry_date_array[$matches['0'][$j]] = $this->EE->localize->fetch_date_params($matches['1'][$j]);
							break;
						case 'gmt_date'			: $gmt_date_array[$matches['0'][$j]] = $this->EE->localize->fetch_date_params($matches['1'][$j]);
							break;
						case 'gmt_entry_date'	: $gmt_entry_date_array[$matches['0'][$j]] = $this->EE->localize->fetch_date_params($matches['1'][$j]);
							break;
						case 'edit_date' 		: $edit_date_array[$matches['0'][$j]] = $this->EE->localize->fetch_date_params($matches['1'][$j]);
							break;
						case 'gmt_edit_date'	: $gmt_edit_date_array[$matches['0'][$j]] = $this->EE->localize->fetch_date_params($matches['1'][$j]);
							break;
					}
				}
			}
		}
	  	
		
		foreach ($this->EE->TMPL->var_single as $key => $val)
		{
			//  {channel_id}

			if ($key == 'channel_id')
			{					 
				$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single(
															$key, 
															$channel_id, 
															$this->EE->TMPL->tagdata
														);
			}


			//  {encoding}

			if ($key == 'encoding')
			{					 
				$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single(
															$key, 
															$this->EE->config->item('output_charset'), 
															$this->EE->TMPL->tagdata
														);
			}


			//  {channel_language}

			if ($key == 'channel_language')
			{					 
				$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single(
															$key, 
															$channel_lang, 
															$this->EE->TMPL->tagdata
														);
			}


			//  {channel_description}

			if ($key == 'channel_description')
			{					 
				$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single(
															$key, 
															$channel_description, 
															$this->EE->TMPL->tagdata
														);
			}


			//  {channel_url}

			if ($key == 'channel_url')
			{					 
				$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single(
															$key, 
															$channel_url, 
															$this->EE->TMPL->tagdata
														);
			}


			//  {channel_name}

			if ($key == 'channel_name')
			{					 
				$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single(
															$key, 
															$channel_title, 
															$this->EE->TMPL->tagdata
														);
			}


			//  {email}

			if ($key == 'email')
			{					 
				$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single(
															$key, 
															$email, 
															$this->EE->TMPL->tagdata
														);
			}


			//  {url}

			if ($key == 'url')
			{					 
				$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single(
															$key, 
															$url, 
															$this->EE->TMPL->tagdata
														);
			}


			//  {date}

			if (isset($entry_date_array[$key]))
			{
				foreach ($entry_date_array[$key] as $dvar)
					$val = str_replace($dvar, $this->EE->localize->convert_timestamp($dvar, $entry_date, TRUE), $val);					

				$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single($key, $val, $this->EE->TMPL->tagdata);					
			}


			//  GMT date - entry date in GMT

			if (isset($gmt_entry_date_array[$key]))
			{
				foreach ($gmt_entry_date_array[$key] as $dvar)
					$val = str_replace($dvar, $this->EE->localize->convert_timestamp($dvar, $entry_date, FALSE), $val);					

				$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single($key, $val, $this->EE->TMPL->tagdata);					
			}
			
			if (isset($gmt_date_array[$key]))
			{
				foreach ($gmt_date_array[$key] as $dvar)
					$val = str_replace($dvar, $this->EE->localize->convert_timestamp($dvar, $entry_date, FALSE), $val);					

				$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single($key, $val, $this->EE->TMPL->tagdata);					
			}


			//  parse "last edit" date

			if (isset($edit_date_array[$key]))
			{
				foreach ($edit_date_array[$key] as $dvar)
					$val = str_replace($dvar, $this->EE->localize->convert_timestamp($dvar, $edit_date, TRUE), $val);					

				$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single($key, $val, $this->EE->TMPL->tagdata);					
			}


			//  "last edit" date as GMT

			if (isset($gmt_edit_date_array[$key]))
			{
				foreach ($gmt_edit_date_array[$key] as $dvar)
					$val = str_replace($dvar, $this->EE->localize->convert_timestamp($dvar, $this->EE->localize->timestamp_to_gmt($edit_date), FALSE), $val);					

				$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single($key, $val, $this->EE->TMPL->tagdata);					
			}


			//  {author}

			if ($key == 'author')
			{					 
				$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single($val, ($screen_name != '') ? $screen_name : $username, $this->EE->TMPL->tagdata);
			}


			//  {version}

			if ($key == 'version')
			{
				$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single($val, APP_VER, $this->EE->TMPL->tagdata);
			}


			//  {trimmed_url} - used by Atom feeds

			if ($key == "trimmed_url")
			{
				$channel_url = (isset($channel_url) AND $channel_url != '') ? $channel_url : '';
			
				$channel_url = str_replace('http://', '', $channel_url);
				$channel_url = str_replace('www.', '', $channel_url);
				$ex = explode("/", $channel_url);
				$channel_url = current($ex);
			
				$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single($val, $channel_url, $this->EE->TMPL->tagdata);
			}			
		}
			 		
		if (count($chunks) > 0)
		{			
			$diff_top = ($start_on != '' && $diffe_request !== false) ? "1,".($diffe_request-1)."c\n" : '';
			
			// Last Update Time
			$this->EE->TMPL->tagdata = '<ee:last_update>'.$last_update."</ee:last_update>\n\n".$diff_top.trim($this->EE->TMPL->tagdata);
			
			// Diffe stuff before items
			if ($diffe_request !== false)
			{
				$this->EE->TMPL->tagdata = str_replace($marker.'0', "\n.\n".$diffe_request."a\n".$marker.'0', $this->EE->TMPL->tagdata);
				$this->EE->TMPL->tagdata = str_replace($marker.(count($chunks)-1), $marker.(count($chunks)-1)."\n.\n$\n-1\n;c\n", $this->EE->TMPL->tagdata);
			}
			
			foreach ($chunks as $key => $val)
			{
				$this->EE->TMPL->tagdata = str_replace($key, $val, $this->EE->TMPL->tagdata);	
			}
		}
		
		// 'ed' input mode is terminated by entering a single period  (.) on a line
		$this->EE->TMPL->tagdata = ($diffe_request !== false) ? trim($this->EE->TMPL->tagdata)."\n.\n" : trim($this->EE->TMPL->tagdata);
		
		return $this->EE->TMPL->tagdata;  
	}

	// --------------------------------------------------------------------

	/**
	  *  Empty feed handler
	  */
	function _empty_feed($error = '')
	{
		if ($error != '')
		{
			$this->EE->TMPL->log_item($error);
		}

		$empty_feed = '';

		if (preg_match("/".LD."if empty_feed".RD."(.*?)".LD.'\/'."if".RD."/s", $this->EE->TMPL->tagdata, $match)) 
		{			
			if (stristr($match['1'], LD.'if'))
			{
				$match['0'] = $this->EE->functions->full_tag($match['0'], $this->EE->TMPL->tagdata, LD.'if', LD.'\/'."if".RD);
			}

			$empty_feed = substr($match['0'], strlen(LD."if empty_feed".RD), -strlen(LD.'/'."if".RD));

			$empty_feed = str_replace(LD.'error'.RD, $error, $empty_feed);
		}

		if ($empty_feed == '')
		{
			$empty_feed = $this->_default_empty_feed($error);
		}

		return $empty_feed;
	}

	// --------------------------------------------------------------------

	/**
	  *  Default empty feed
	  */
	function _default_empty_feed($error = '')
	{
		$this->EE->lang->loadfile('rss');

		$encoding	= $this->EE->config->item('charset');
		$title		= $this->EE->config->item('site_name');
		$link		= $this->EE->config->item('site_url');
		$version	= APP_VER;
		$pubdate	= date('D, d M Y H:i:s', $this->EE->localize->now).' GMT';
		$content	= ($this->debug === TRUE && $error != '') ? $error : $this->EE->lang->line('empty_feed');

		return <<<HUMPTYDANCE
<?xml version="1.0" encoding="{$encoding}"?>
<rss version="2.0">
	<channel>
	<title>{$title}</title>
	<link>{$link}</link>
	<description></description>
	<docs>http://www.rssboard.org/rss-specification</docs>
	<generator>ExpressionEngine v{$version} http://expressionengine.com/</generator>

	<item>
		<title>{$content}</title>
		<description>{$content}</description>
		<pubDate>{$pubdate}</pubDate>
	</item>
	</channel>
</rss>		
HUMPTYDANCE;
	}
	/* END */
	
}
// END CLASS

/* End of file mod.rss.php */
/* Location: ./system/expressionengine/modules/rss/mod.rss.php */