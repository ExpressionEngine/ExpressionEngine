<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine RSS Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class Rss {

	protected $_debug = FALSE;
	
	/**
	 * RSS feed
	 *
	 * This function fetches the channel metadata used in
	 * the channel section of RSS feeds
	 *
	 * Note: The item elements are generated using the channel class
	 */
	function feed()
	{
		$this->EE =& get_instance();
		
		$this->EE->TMPL->encode_email = FALSE;
		
		if ($this->EE->TMPL->fetch_param('debug') == 'yes')
		{
			$this->_debug = TRUE;
		}
		
		if ( ! $channel = $this->EE->TMPL->fetch_param('channel'))
		{
			$this->EE->lang->loadfile('rss');
			return $this->_empty_feed($this->EE->lang->line('no_weblog_specified'));
		}

		$this->EE->db->select('channel_id');
		$this->EE->functions->ar_andor_string($channel, 'channel_name');
		$query = $this->EE->db->get('channels');

		if ($query->num_rows() === 0)
		{
			$this->EE->lang->loadfile('rss');
			return $this->_empty_feed($this->EE->lang->line('rss_invalid_channel'));
		}
		
		$tmp = $this->_setup_meta_query($query);		
		$query = $tmp[0];
		$last_update = $tmp[1];
		$edit_date = $tmp[2];
		$entry_date = $tmp[3];
		
		if ($query->num_rows() === 0)
		{
			$this->EE->lang->loadfile('rss');
			return $this->_empty_feed($this->EE->lang->line('no_matching_entries'));
		}
		
		$query = $this->_feed_vars_query($query->row('entry_id'));
		
		$request 		= $this->EE->input->request_headers(TRUE);
		$start_on 		= '';
		$diffe_request 	= FALSE;
		$feed_request	= FALSE;
		
		// Check for 'diff -e' request
		if (isset($request['A-IM']) && stristr($request['A-IM'], 'diffe') !== FALSE)
		{
			$items_start = strpos($this->EE->TMPL->tagdata, '{exp:channel:entries');
			
			if ($items_start !== FALSE)
			{
				// We add three, for three line breaks added later in the script
				$diffe_request = count(preg_split("/(\r\n)|(\r)|(\n)/",
										trim(substr($this->EE->TMPL->tagdata, 0, $items_start)))) + 3;
			}
		}

		//  Check for 'feed' request
		if (isset($request['A-IM']) && stristr($request['A-IM'],'feed') !== FALSE)
		{
			$feed_request = TRUE;
			$diffe_request = FALSE;
		}

		//  Check for the 'If-Modified-Since' Header
		if ($this->EE->config->item('send_headers') == 'y' 
			&& isset($request['If-Modified-Since']) 
			&& trim($request['If-Modified-Since']) != '')
		{
			$x				= explode(';',$request['If-Modified-Since']);
			$modify_tstamp	= strtotime($x[0]);

			//  If new content *and* 'feed' or 'diffe', create start on time.
			//  Otherwise, we send back a Not Modified header
			if ($last_update <= $modify_tstamp)
			{
				@header("HTTP/1.0 304 Not Modified");
				@header('HTTP/1.1 304 Not Modified');
				@exit;
			}
			else
			{
				if ($diffe_request !== FALSE OR $feed_request !== FALSE)
				{
					$start_on = gmdate('Y-m-d h:i A',$this->EE->localize->set_localized_time($modify_tstamp));
				}
			}
		}

		$chunks = array();
		$marker = 'H94e99Perdkie0393e89vqpp'; 

		if (preg_match_all("/{exp:channel:entries.+?{".'\/'."exp:channel:entries}/s", 
							$this->EE->TMPL->tagdata, $matches))
		{
			for($i = 0; $i < count($matches[0]); $i++)
			{
				$this->EE->TMPL->tagdata = str_replace($matches[0][$i], $marker.$i, $this->EE->TMPL->tagdata);

				// Remove limit if we have a start_on and dynamic_start
				if ($start_on != '' && stristr($matches[0][$i],'dynamic_start="yes"'))
				{
					$matches[0][$i] = preg_replace("/limit=[\"\'][0-9]{1,5}[\"\']/", '', $matches[0][$i]);
				}

				// Replace dynamic_start="on" parameter with start_on="" param
				$start_on_switch = ($start_on != '') ? 'start_on="'.$start_on.'"' : '';
				$matches[0][$i] = preg_replace("/dynamic_start\s*=\s*[\"|']yes[\"|']/i", $start_on_switch, $matches[0][$i]);

				$chunks[$marker.$i] = $matches[0][$i];
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
				for ($j = 0; $j < count($matches[0]); $j++)
				{
					$matches[0][$j] = str_replace(LD, '', $matches[0][$j]);
					$matches[0][$j] = str_replace(RD, '', $matches[0][$j]);

					switch ($val)
					{
						case 'date' 			: $entry_date_array[$matches[0][$j]] = $this->EE->localize->fetch_date_params($matches[1][$j]);
							break;
						case 'gmt_date'			: $gmt_date_array[$matches[0][$j]] = $this->EE->localize->fetch_date_params($matches[1][$j]);
							break;
						case 'gmt_entry_date'	: $gmt_entry_date_array[$matches[0][$j]] = $this->EE->localize->fetch_date_params($matches[1][$j]);
							break;
						case 'edit_date' 		: $edit_date_array[$matches[0][$j]] = $this->EE->localize->fetch_date_params($matches[1][$j]);
							break;
						case 'gmt_edit_date'	: $gmt_edit_date_array[$matches[0][$j]] = $this->EE->localize->fetch_date_params($matches[1][$j]);
							break;
					}
				}
			}
		}

		foreach ($this->EE->TMPL->var_single as $key => $val)
		{
			//  {date}
			if (isset($entry_date_array[$key]))
			{
				foreach ($entry_date_array[$key] as $dvar)
				{
					$val = str_replace($dvar, $this->EE->localize->convert_timestamp($dvar, $entry_date, TRUE), $val);					
				}

				$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single($key, $val, $this->EE->TMPL->tagdata);					
			}

			//  GMT date - entry date in GMT
			if (isset($gmt_entry_date_array[$key]))
			{
				foreach ($gmt_entry_date_array[$key] as $dvar)
				{
					$val = str_replace($dvar, $this->EE->localize->convert_timestamp($dvar, $entry_date, FALSE), $val);					
				}

				$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single($key, $val, $this->EE->TMPL->tagdata);					
			}

			if (isset($gmt_date_array[$key]))
			{
				foreach ($gmt_date_array[$key] as $dvar)
				{
					$val = str_replace($dvar, $this->EE->localize->convert_timestamp($dvar, $entry_date, FALSE), $val);
				}

				$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single($key, $val, $this->EE->TMPL->tagdata);					
			}

			//  parse "last edit" date
			if (isset($edit_date_array[$key]))
			{
				foreach ($edit_date_array[$key] as $dvar)
				{
					$val = str_replace($dvar, $this->EE->localize->convert_timestamp($dvar, $edit_date, TRUE), $val);					
				}
				$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single($key, $val, $this->EE->TMPL->tagdata);					
			}

			//  "last edit" date as GMT
			if (isset($gmt_edit_date_array[$key]))
			{
				foreach ($gmt_edit_date_array[$key] as $dvar)
				{
					$val = str_replace($dvar, $this->EE->localize->convert_timestamp($dvar, $this->EE->localize->timestamp_to_gmt($edit_date), FALSE), $val);					
				}

				$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single($key, $val, $this->EE->TMPL->tagdata);					
			}			
		}
		
		// Setup {trimmed_url}
		$channel_url = $query->row('channel_url');
		$trimmed_url = (isset($channel_url) AND $channel_url != '') ? $channel_url : '';

		$trimmed_url = str_replace('http://', '', $trimmed_url);
		$trimmed_url = str_replace('www.', '', $trimmed_url);
		$ex = explode("/", $trimmed_url);
		$trimmed_url = current($ex);
		
		$vars = array(
			array(
				'channel_id'			=> $query->row('channel_id'),
				'encoding'				=> $this->EE->config->item('output_charset'),
				'channel_language'		=> $query->row('channel_lang'),
				'channel_description'	=> $query->row('channel_description'),
				'channel_url'			=> $query->row('channel_url'),
				'channel_name'			=> $query->row('channel_title'),
				'email'					=> $query->row('email'),
				'author'				=> $query->row('screen_name'),
				'version'				=> APP_VER,
				'trimmed_url'			=> $trimmed_url,
				''
			)
		);
		
		$this->EE->TMPL->tagdata = $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, $vars);

		if (count($chunks) > 0)
		{			
			$diff_top = ($start_on != '' && $diffe_request !== false) ? "1,".($diffe_request-1)."c\n" : '';

			// Last Update Time
			$this->EE->TMPL->tagdata = '<ee:last_update>'.$last_update."</ee:last_update>\n\n".$diff_top.trim($this->EE->TMPL->tagdata);

			// Diffe stuff before items
			if ($diffe_request !== FALSE)
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
	 * Setup the meta query 
	 *
	 * @todo -- Convert the query to active record
	 * @param 	object		query object
	 * @return 	object
	 */
	protected function _setup_meta_query($query)
	{
		//  Create Meta Query
		//
		// Since UTC_TIMESTAMP() is what we need, but it is not available until
		// MySQL 4.1.1, we have to use this ever so clever SQL to figure it out:
		// DATE_ADD( '1970-01-01', INTERVAL UNIX_TIMESTAMP() SECOND )
		$sql = "SELECT exp_channel_titles.entry_id, exp_channel_titles.entry_date, exp_channel_titles.edit_date, 
				GREATEST((UNIX_TIMESTAMP(exp_channel_titles.edit_date) + 
						 (UNIX_TIMESTAMP(DATE_ADD( '1970-01-01', INTERVAL UNIX_TIMESTAMP() SECOND)) - UNIX_TIMESTAMP())),
						exp_channel_titles.entry_date) AS last_update
				FROM exp_channel_titles
				LEFT JOIN exp_channels ON exp_channel_titles.channel_id = exp_channels.channel_id 
				LEFT JOIN exp_members ON exp_members.member_id = exp_channel_titles.author_id
				WHERE exp_channel_titles.entry_id !=''
				AND exp_channels.site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."') ";
		
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
		$timestamp = ($this->EE->TMPL->cache_timestamp != '') ? $this->EE->TMPL->cache_timestamp : $this->EE->localize->now;

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

		$last_update = '';
		$edit_date = '';
		$entry_date = '';

		if ($query->num_rows() > 0)
		{
			$last_update = $query->row('last_update')  + ($this->EE->localize->set_server_time() - $this->EE->localize->now);
			$edit_date = $query->row('edit_date') ;
			$entry_date = $query->row('entry_date') ;
		}

		$sql .= " ORDER BY exp_channel_titles.entry_date desc LIMIT 1";

		$query = $this->EE->db->query($sql);

		return array($query, $last_update, $edit_date, $entry_date);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Feed Variables Query
	 *
	 * @param 	integer		Entry ID
	 * @return 	object		db result object
	 */
	protected function _feed_vars_query($entry_id)
	{
		return $this->EE->db->select('c.channel_id, c.channel_title, c.channel_url, c.channel_lang, 
									c.channel_description, ct.entry_date, m.email, m.username, 
									m.screen_name, m.url')
							->from('channel_titles ct')
							->join('channels c', 'ct.channel_id = c.channel_id', 'left')
							->join('members m', 'm.member_id = ct.author_id', 'left')
							->where('ct.entry_id', (int) $entry_id)
							->where_in('c.site_id', $this->EE->TMPL->site_ids)
							->get();
	}

	// --------------------------------------------------------------------

	/**
	 *  Empty feed handler
	 */
	private function _empty_feed($error = NULL)
	{
		if ($error !== NULL)
		{
			$this->EE->TMPL->log_item($error);
		}

		$empty_feed = '';

		if (preg_match("/".LD."if empty_feed".RD."(.*?)".LD.'\/'."if".RD."/s", $this->EE->TMPL->tagdata, $match)) 
		{			
			if (stristr($match[1], LD.'if'))
			{
				$match[0] = $this->EE->functions->full_tag($match[0], $this->EE->TMPL->tagdata, LD.'if', LD.'\/'."if".RD);
			}

			$empty_feed = substr($match[0], strlen(LD."if empty_feed".RD), -strlen(LD.'/'."if".RD));

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
	protected function _default_empty_feed($error = '')
	{
		$this->EE->lang->loadfile('rss');

		$encoding	= $this->EE->config->item('charset');
		$title		= $this->EE->config->item('site_name');
		$link		= $this->EE->config->item('site_url');
		$version	= APP_VER;
		$pubdate	= date('D, d M Y H:i:s', $this->EE->localize->now).' GMT';
		$content	= ($this->_debug === TRUE && $error != '') ? $error : $this->EE->lang->line('empty_feed');

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
}
// END CLASS

/* End of file mod.rss.php */
/* Location: ./system/expressionengine/modules/rss/mod.rss.php */