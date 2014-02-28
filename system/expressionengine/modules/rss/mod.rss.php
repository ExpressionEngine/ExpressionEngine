<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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

		ee()->TMPL->encode_email = FALSE;

		if (ee()->TMPL->fetch_param('debug') == 'yes')
		{
			$this->_debug = TRUE;
		}

		if ( ! $channel = ee()->TMPL->fetch_param('channel'))
		{
			ee()->lang->loadfile('rss');
			return $this->_empty_feed(ee()->lang->line('no_weblog_specified'));
		}

		ee()->db->select('channel_id');
		ee()->functions->ar_andor_string($channel, 'channel_name');
		$query = ee()->db->get('channels');

		if ($query->num_rows() === 0)
		{
			ee()->lang->loadfile('rss');
			return $this->_empty_feed(ee()->lang->line('rss_invalid_channel'));
		}

		$tmp = $this->_setup_meta_query($query);
		$query = $tmp[0];
		$last_update = $tmp[1];
		$edit_date = $tmp[2];
		$entry_date = $tmp[3];

		if ($query->num_rows() === 0)
		{
			ee()->lang->loadfile('rss');
			return $this->_empty_feed(ee()->lang->line('no_matching_entries'));
		}

		$query = $this->_feed_vars_query($query->row('entry_id'));

		$request 		= ee()->input->request_headers(TRUE);
		$start_on 		= '';
		$diffe_request 	= FALSE;
		$feed_request	= FALSE;

		// Check for 'diff -e' request
		if (isset($request['A-IM']) && stristr($request['A-IM'], 'diffe') !== FALSE)
		{
			$items_start = strpos(ee()->TMPL->tagdata, '{exp:channel:entries');

			if ($items_start !== FALSE)
			{
				// We add three, for three line breaks added later in the script
				$diffe_request = count(preg_split("/(\r\n)|(\r)|(\n)/",
										trim(substr(ee()->TMPL->tagdata, 0, $items_start)))) + 3;
			}
		}

		//  Check for 'feed' request
		if (isset($request['A-IM']) && stristr($request['A-IM'],'feed') !== FALSE)
		{
			$feed_request = TRUE;
			$diffe_request = FALSE;
		}

		//  Check for the 'If-Modified-Since' Header
		if (ee()->config->item('send_headers') == 'y'
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
					$start_on = ee()->localize->format_date('%Y-%m-%d %h:%i %A', $modify_tstamp);
				}
			}
		}

		$chunks = array();
		$marker = 'H94e99Perdkie0393e89vqpp';

		if (preg_match_all("/{exp:channel:entries.+?{".'\/'."exp:channel:entries}/s",
							ee()->TMPL->tagdata, $matches))
		{
			for($i = 0; $i < count($matches[0]); $i++)
			{
				ee()->TMPL->tagdata = str_replace($matches[0][$i], $marker.$i, ee()->TMPL->tagdata);

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

		ee()->load->helper('date');

		$dates = array(
			'date'      => $entry_date,
			'edit_date' => mysql_to_unix($edit_date)
		);
		ee()->TMPL->tagdata = ee()->TMPL->parse_date_variables(ee()->TMPL->tagdata, $dates);

		$dates = array(
			'gmt_date'       => $entry_date,
			'gmt_entry_date' => $entry_date,
			'gmt_edit_date'  => mysql_to_unix($edit_date)
		);
		ee()->TMPL->tagdata = ee()->TMPL->parse_date_variables(ee()->TMPL->tagdata, $dates, FALSE);

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
				'encoding'				=> ee()->config->item('output_charset'),
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

		ee()->TMPL->tagdata = ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $vars);

		if (count($chunks) > 0)
		{
			$diff_top = ($start_on != '' && $diffe_request !== false) ? "1,".($diffe_request-1)."c\n" : '';

			// Last Update Time
			ee()->TMPL->tagdata = '<ee:last_update>'.$last_update."</ee:last_update>\n\n".$diff_top.trim(ee()->TMPL->tagdata);

			// Diffe stuff before items
			if ($diffe_request !== FALSE)
			{
				ee()->TMPL->tagdata = str_replace($marker.'0', "\n.\n".$diffe_request."a\n".$marker.'0', ee()->TMPL->tagdata);
				ee()->TMPL->tagdata = str_replace($marker.(count($chunks)-1), $marker.(count($chunks)-1)."\n.\n$\n-1\n;c\n", ee()->TMPL->tagdata);
			}

			foreach ($chunks as $key => $val)
			{
				ee()->TMPL->tagdata = str_replace($key, $val, ee()->TMPL->tagdata);
			}
		}

		// 'ed' input mode is terminated by entering a single period  (.) on a line
		ee()->TMPL->tagdata = ($diffe_request !== false) ? trim(ee()->TMPL->tagdata)."\n.\n" : trim(ee()->TMPL->tagdata);

		return ee()->TMPL->tagdata;
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
				AND exp_channels.site_id IN ('".implode("','", ee()->TMPL->site_ids)."') ";

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
		$timestamp = (ee()->TMPL->cache_timestamp != '') ? ee()->TMPL->cache_timestamp : ee()->localize->now;

		if (ee()->TMPL->fetch_param('show_future_entries') != 'yes')
		{
			$sql .= " AND exp_channel_titles.entry_date < ".$timestamp." ";
		}

		if (ee()->TMPL->fetch_param('show_expired') != 'yes')
		{
			$sql .= " AND (exp_channel_titles.expiration_date = 0 OR exp_channel_titles.expiration_date > ".$timestamp.") ";
		}

		//  Add status declaration
		if ($status = ee()->TMPL->fetch_param('status'))
		{
			$status = str_replace('Open',	'open',	$status);
			$status = str_replace('Closed', 'closed', $status);

			$status_str = ee()->functions->sql_andor_string($status, 'exp_channel_titles.status');

			if (stristr($status_str, "'closed'") === FALSE)
			{
				$status_str .= " AND exp_channel_titles.status != 'closed' ";
			}

			$sql .= $status_str;
		}
		else
		{
			$sql .= "AND exp_channel_titles.status = 'open' ";
		}

		//  Limit to (or exclude) specific users
		if ($username = ee()->TMPL->fetch_param('username'))
		{
			// Shows entries ONLY for currently logged in user
			if ($username == 'CURRENT_USER')
			{
				$sql .=  "AND exp_members.member_id = '".ee()->session->userdata['member_id']."' ";
			}
			elseif ($username == 'NOT_CURRENT_USER')
			{
				$sql .=  "AND exp_members.member_id != '".ee()->session->userdata['member_id']."' ";
			}
			else
			{
				$sql .= ee()->functions->sql_andor_string($username, 'exp_members.username');
			}
		}

		// Find Edit Date
		$query = ee()->db->query($sql." ORDER BY last_update desc LIMIT 1");

		$last_update = '';
		$edit_date = '';
		$entry_date = '';

		if ($query->num_rows() > 0)
		{
			$last_update = $query->row('last_update');
			$edit_date = $query->row('edit_date') ;
			$entry_date = $query->row('entry_date') ;
		}

		$sql .= " ORDER BY exp_channel_titles.entry_date desc LIMIT 1";

		$query = ee()->db->query($sql);

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
		return ee()->db->select('c.channel_id, c.channel_title, c.channel_url, c.channel_lang,
									c.channel_description, ct.entry_date, m.email, m.username,
									m.screen_name, m.url')
							->from('channel_titles ct')
							->join('channels c', 'ct.channel_id = c.channel_id', 'left')
							->join('members m', 'm.member_id = ct.author_id', 'left')
							->where('ct.entry_id', (int) $entry_id)
							->where_in('c.site_id', ee()->TMPL->site_ids)
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
			ee()->TMPL->log_item($error);
		}

		$empty_feed = '';

		if (preg_match("/".LD."if empty_feed".RD."(.*?)".LD.'\/'."if".RD."/s", ee()->TMPL->tagdata, $match))
		{
			if (stristr($match[1], LD.'if'))
			{
				$match[0] = ee()->functions->full_tag($match[0], ee()->TMPL->tagdata, LD.'if', LD.'\/'."if".RD);
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
		ee()->lang->loadfile('rss');

		$encoding	= ee()->config->item('charset');
		$title		= ee()->config->item('site_name');
		$link		= ee()->config->item('site_url');
		$version	= APP_VER;
		$pubdate	= ee()->localize->format_date('%D, %d %M %Y %H:%i:%s GMT', NULL, FALSE);
		$content	= ($this->_debug === TRUE && $error != '') ? $error : ee()->lang->line('empty_feed');

		return <<<HUMPTYDANCE
<?xml version="1.0" encoding="{$encoding}"?>
<rss version="2.0">
	<channel>
	<title>{$title}</title>
	<link>{$link}</link>
	<description></description>
	<docs>http://www.rssboard.org/rss-specification</docs>
	<generator>ExpressionEngine v{$version} http://ellislab.com/expressionengine</generator>

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