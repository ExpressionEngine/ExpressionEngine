<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Comment Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Comment {

	// Maximum number of comments.  This is a safety valve
	// in case the user doesn't specify a maximum

	var $limit = 100;


	// Show anchor?
	// TRUE/FALSE
	// Determines whether to show the <a name> anchor above each comment

	var $show_anchor = FALSE;

	// Comment Expiration Mode
	// 0 -	Comments only expire if the comment expiration field in the PUBLISH page contains a value.
	// 1 -	If the comment expiration field is blank, comments will still expire if the global preference
	// 		is set in the Channel Preferences page.  Use this option only if you used EE prior to
	//		version 1.1 and you want your old comments to expire.

	var $comment_expiration_mode = 0;


	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function Comment()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		$fields = array('name', 'email', 'url', 'location', 'comment');

		foreach ($fields as $val)
		{
			if (isset($_POST[$val] ))
			{
				$_POST[$val] = ee()->functions->encode_ee_tags($_POST[$val], TRUE);

				if ($val == 'comment')
				{
					$_POST[$val] = ee()->security->xss_clean($_POST[$val]);
				}
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Retrieve the disable parameter from the template and parse it
	 * out into the $enabled_features array.  Return that array. The
	 * $enabled_features array is an array of boolean values keyed to
	 * certain features of tag that can be disabled.  In the case of
	 * the comment module, only pagination may currently be disabled.
	 *
	 * NOTE: This code is virtually identical to Channel::_fetch_disable_param()
     * it should be commonized somehow, but our current program structure
	 * does not make this easy and putting it in a random cache of common
	 * functions does not make sense.
	 *
	 * FIXME Commonlize this code in a logical way.
	 *
	 * @return array An array of enabled features of the form feature_key=>boolean
	 */
	private function _fetch_disable_param()
	{
		$enabled_features = array('pagination' => TRUE);

		// Get the disable parameter from the template.
		if ($disabled = ee()->TMPL->fetch_param('disable'))
		{
			// If we have more than one value, then
			// we need to break them out.
			if (strpos($disabled, '|') !== FALSE)
			{
				foreach (explode('|', $disabled) as $feature)
				{
					if (isset($enabled_features[$feature]))
					{
						$enabled_features[$feature] = FALSE;
					}
				}
			}
			// Otherwise there's just one value and just
			// disable the one.
			elseif (isset($enabled_features[$disabled]))
			{
				$enabled_features[$disabled] = FALSE;
			}
		}

		// Return our array.
		return $enabled_features;
	}


	/**
	 * Get the time in seconds since the epoc at which a comment may
	 * no longer be editted. If current time is past this value then
	 * do not allow the user to edit it.
	 *
	 * @return int The time at which comment editing permission expires in seconds since the epoc.
	 */
	private function _comment_edit_time_limit()
	{
		if (ee()->config->item('comment_edit_time_limit') == 0)
		{
			return 0;
		}

		$time_limit_sec = 60 * ee()->config->item('comment_edit_time_limit');
		return ee()->localize->now - $time_limit_sec;
	}

	/**
	 * Comment Entries
	 *
	 * @access	public
	 * @return	string
	 */
	public function entries()
	{
		$return 		= '';
		$qstring		= ee()->uri->query_string;
		$uristr			= ee()->uri->uri_string;
		$switch 		= array();
		$search_link	= '';
		$enabled 		= $this->_fetch_disable_param();

		if ($enabled['pagination'])
		{
			ee()->load->library('pagination');
			$pagination = new Pagination_object(__CLASS__);
		}

		if (ee()->TMPL->fetch_param('dynamic') == 'no')
		{
			$dynamic = FALSE;
		}
		else
		{
			$dynamic = TRUE;
		}

		//
		$force_entry = FALSE;
		if (ee()->TMPL->fetch_param('author_id') !== FALSE
			OR ee()->TMPL->fetch_param('entry_id') !== FALSE
			OR ee()->TMPL->fetch_param('url_title') !== FALSE
			OR ee()->TMPL->fetch_param('comment_id') !== FALSE)
		{
			$force_entry = TRUE;
		}

		// A note on returns!
		// If dynamic is off - ANY no results triggers no_result template
		// If dynamic is on- we do not want to trigger no_results on a non-single entry page
		// so only trigger if no comment ids- not for no valid entry ids existing
		// Do not vary by force_entry setting


		/** ----------------------------------------------
		/**  Do we allow dynamic POST variables to set parameters?
		/** ----------------------------------------------*/
		if (ee()->TMPL->fetch_param('dynamic_parameters') !== FALSE AND isset($_POST) AND count($_POST) > 0)
		{
			foreach (explode('|', ee()->TMPL->fetch_param('dynamic_parameters')) as $var)
			{
				if (isset($_POST[$var]) AND in_array($var, array('channel', 'limit', 'sort', 'orderby')))
				{
					ee()->TMPL->tagparams[$var] = $_POST[$var];
				}
			}
		}

		/** --------------------------------------
		/**  Parse page number
		/** --------------------------------------*/

		// We need to strip the page number from the URL for two reasons:
		// 1. So we can create pagination links
		// 2. So it won't confuse the query with an improper proper ID

		if ( ! $dynamic)
		{
			if (preg_match("#(^|/)N(\d+)(/|$)#i", $qstring, $match))
			{
				if ($enabled['pagination'])
				{
					$pagination->current_page = $match['2'];
				}
				$uristr  = trim(reduce_double_slashes(str_replace($match['0'], '/', $uristr)), '/');
			}
		}
		else
		{
			if (preg_match("#(^|/)P(\d+)(/|$)#", $qstring, $match))
			{
				if ($enabled['pagination'])
				{
					$pagination->current_page = $match['2'];
				}
				$uristr  = reduce_double_slashes(str_replace($match['0'], '/', $uristr));
				$qstring = trim(reduce_double_slashes(str_replace($match['0'], '/', $qstring)), '/');
			}
		}

		// Fetch channel_ids if appropriate
		$channel_ids = array();
		if ($channel = ee()->TMPL->fetch_param('channel') OR ee()->TMPL->fetch_param('site'))
		{
			ee()->db->select('channel_id');
			ee()->db->where_in('site_id', ee()->TMPL->site_ids);
			if ($channel !== FALSE)
			{
				ee()->functions->ar_andor_string($channel, 'channel_name');
			}

			$channels = ee()->db->get('channels');
			if ($channels->num_rows() == 0)
			{
				if ( ! $dynamic)
				{
					return ee()->TMPL->no_results();
				}
				return false;
			}
			else
			{
				foreach($channels->result_array() as $row)
				{
					$channel_ids[] = $row['channel_id'];
				}
			}
		}

		// Fetch entry ids- we'll use them to make sure comments are to open, etc. entries
		$comment_id_param = FALSE;
		if  ($dynamic == TRUE OR $force_entry == TRUE)
		{
			if ($force_entry == TRUE)
			{
				// Check if an entry_id, url_title or comment_id was specified
				if ($entry_id = ee()->TMPL->fetch_param('entry_id'))
				{
					ee()->functions->ar_andor_string($entry_id, 'entry_id');
				}
				elseif ($url_title = ee()->TMPL->fetch_param('url_title'))
				{
					ee()->functions->ar_andor_string($url_title, 'url_title');
				}
				elseif ($comment_id_param = ee()->TMPL->fetch_param('comment_id'))
				{
					$force_entry_ids = $this->fetch_comment_ids_param($comment_id_param);
					if (count($force_entry_ids) == 0)
					{
						// No entry ids for the comment ids?  How'd they manage that
						if ( ! $dynamic)
						{
							return ee()->TMPL->no_results();
						}
						return false;
					}
					ee()->db->where_in('entry_id', $force_entry_ids);
				}
			}
			else
			{
				// If there is a slash in the entry ID we'll kill everything after it.
				$entry_id = trim($qstring);
				$entry_id = preg_replace("#/.+#", "", $entry_id);

				// Have to choose between id or url title
				if ( ! is_numeric($entry_id))
				{
					ee()->db->where('url_title', $entry_id);
				}
				else
				{
					ee()->db->where('entry_id', $entry_id);
				}
			}

			//  Do we have a valid entry ID number?
			$timestamp = (ee()->TMPL->cache_timestamp != '') ? ee()->TMPL->cache_timestamp : ee()->localize->now;

			ee()->db->select('entry_id, channel_id');
			//ee()->db->where('channel_titles.channel_id = '.ee()->db->dbprefix('channels').'.channel_id');
			ee()->db->where_in('channel_titles.site_id', ee()->TMPL->site_ids);

			if (ee()->TMPL->fetch_param('show_expired') !== 'yes')
			{
				$date_where = "(".ee()->db->protect_identifiers('expiration_date')." = 0 OR "
				.ee()->db->protect_identifiers('expiration_date')." > {$timestamp})";
				ee()->db->where($date_where);
			}

			if ($e_status = ee()->TMPL->fetch_param('entry_status'))
			{
				$e_status = str_replace('Open',	'open',	$e_status);
				$e_status = str_replace('Closed', 'closed', $e_status);

				// If they don't specify closed, it defaults to it
				if ( ! in_array('closed', explode('|', $e_status)))
				{
					ee()->db->where('status !=', 'closed');
				}

				ee()->functions->ar_andor_string($e_status, 'status');
			}
			else
			{
				ee()->db->where('status !=', 'closed');
			}

			//  Limit to/exclude specific channels
			if (count($channel_ids) == 1)
			{
				ee()->db->where('channel_titles.channel_id', $channel_ids['0']);
			}
			elseif (count($channel_ids) > 1)
			{
				ee()->db->where_in('channel_titles.channel_id', $channel_ids);
			}
			ee()->db->from('channel_titles');
			$query = ee()->db->get();

			// Bad ID?  See ya!
			if ($query->num_rows() == 0)
			{
				if ( ! $dynamic)
				{
					return ee()->TMPL->no_results();
				}

				return false;
			}

			// We'll reassign the entry IDs so they're the true numeric ID
			foreach($query->result_array() as $row)
			{
				$entry_ids[] = $row['entry_id'];
			}
		}


		//  Set sorting and limiting
		if ( ! $dynamic)
		{
			if ($enabled['pagination'])
			{
				$pagination->per_page = ee()->TMPL->fetch_param('limit', 100);
			}
			else
			{
				$limit = ee()->TMPL->fetch_param('limit', 100);
			}
			$sort = ee()->TMPL->fetch_param('sort', 'desc');
		}
		else
		{

			if ($enabled['pagination'])
			{
				$pagination->per_page = ee()->TMPL->fetch_param('limit', $this->limit);
			}
			else
			{
				$limit = ee()->TMPL->fetch_param('limit', $this->limit);
			}
			$sort = ee()->TMPL->fetch_param('sort', 'asc');
		}

		$allowed_sorts = array('date', 'email', 'location', 'name', 'url');

		if ($enabled['pagination'])
		{
			// Capture the pagination template
			$pagination->get_template();
		}

		/** ----------------------------------------
		/**  Fetch comment ID numbers
		/** ----------------------------------------*/

		$temp = array();
		$i = 0;

		// Left this here for backward compatibility
		// We need to deprecate the "order_by" parameter

		if (ee()->TMPL->fetch_param('orderby') != '')
		{
			$order_by = ee()->TMPL->fetch_param('orderby');
		}
		else
		{
			$order_by = ee()->TMPL->fetch_param('order_by');
		}

		$random = ($order_by == 'random') ? TRUE : FALSE;
		$order_by  = ($order_by == 'date' OR ! in_array($order_by, $allowed_sorts))  ? 'comment_date' : $order_by;

		// We cache the query in case we need to do a count for dynamic off pagination
		ee()->db->start_cache();

		ee()->db->select('comment_date, comment_id');
		ee()->db->from('comments c');

		if ($status = ee()->TMPL->fetch_param('status'))
		{
			$status = strtolower($status);
			$status = str_replace('open',	'o', $status);
			$status = str_replace('closed', 'c', $status);
			$status = str_replace('pending', 'p', $status);

			ee()->functions->ar_andor_string($status, 'c.status');

			// No custom status for comments, so we can be leaner in check for 'c'
			if (stristr($status, "c") === FALSE)
			{
				ee()->db->where('c.status !=', 'c');
			}
		}
		else
		{
			ee()->db->where('c.status', 'o');
		}

		if ($author_id = ee()->TMPL->fetch_param('author_id'))
		{
			ee()->db->where('c.author_id', $author_id);
		}

		// Note if it's not dynamic and the entry isn't forced?  We don't check on the entry criteria,
		// so this point, dynamic and forced entry will have 'valid' entry ids, dynamic off may not

		if ( ! $dynamic && ! $force_entry)
		{
			//  Limit to/exclude specific channels
			if (count($channel_ids) == 1)
			{
				ee()->db->where('c.channel_id', $channel_ids['0'], FALSE);
			}
			elseif (count($channel_ids) > 1)
			{
				ee()->db->where_in('c.channel_id', $channel_ids);
			}

			ee()->db->join('channel_titles ct', 'ct.entry_id = c.entry_id');

			if ($e_status = ee()->TMPL->fetch_param('entry_status'))
			{
				$e_status = str_replace('Open',	'open',	$e_status);
				$e_status = str_replace('Closed', 'closed', $e_status);

				// If they don't specify closed, it defaults to it
				if ( ! in_array('closed', explode('|', $e_status)))
				{
					ee()->db->where('ct.status !=', 'closed');
				}

				ee()->functions->ar_andor_string($e_status, 'ct.status');
			}
			else
			{
				ee()->db->where('ct.status !=', 'closed');
			}

			// seems redundant given channels
			ee()->db->where_in('c.site_id', ee()->TMPL->site_ids, FALSE);

			if (ee()->TMPL->fetch_param('show_expired') !== 'yes')
			{
				$timestamp = (ee()->TMPL->cache_timestamp != '') ? ee()->TMPL->cache_timestamp : ee()->localize->now;

				$date_where = "(".ee()->db->protect_identifiers('ct.expiration_date')." = 0 OR "
				.ee()->db->protect_identifiers('ct.expiration_date')." > {$timestamp})";
				ee()->db->where($date_where);
			}
		}
		else
		{
			// Force entry may result in multiple entry ids
			if (isset($entry_ids) && count($entry_ids) > 0)
			{
				ee()->db->where_in('c.entry_id', $entry_ids);
			}
			else
			{
				ee()->db->where('c.entry_id', $entry_id);
			}

			if ($comment_id_param)
			{
				ee()->functions->ar_andor_string($comment_id_param, 'comment_id');
			}
		}

		if ($enabled['pagination'])
		{
			$total_rows = ee()->db->count_all_results();
			if ($pagination->paginate === TRUE)
			{
				// When we are only showing comments and it is
				// not based on an entry id or url title
				// in the URL, we can make the query much
				// more efficient and save some work.
				$pagination->total_rows = $total_rows;
			}
		}

		$this_sort = ($random) ? 'random' : strtolower($sort);

		// We're not stripping it out this time, so we can just
		// ignore the check if we're not paginating.
		if ($enabled['pagination'])
		{
			$p = ( ! $dynamic) ? 'N' : 'P';

			// Figure out of we need a pagination offset
			if (preg_match('/'.$p.'(\d+)(?:\/|$)/', ee()->uri->uri_string, $matches))
			{
				$pagination->offset = $matches[1];
			}
			else
			{
				$pagination->offset = 0;
			}
		}

		ee()->db->order_by($order_by, $this_sort);

		if ($enabled['pagination'])
		{
			ee()->db->limit($pagination->per_page, $pagination->offset);
		}
		else
		{
			ee()->db->limit($limit, 0);
		}

		ee()->db->stop_cache();
		$query = ee()->db->get();
		ee()->db->flush_cache();
		$result_ids = array();

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$result_ids[] = $row->comment_id;
			}
		}

		//  No results?  No reason to continue...
		if (count($result_ids) == 0)
		{
			return ee()->TMPL->no_results();
		}

		if ($enabled['pagination'])
		{
			// Build pagination
			$pagination->build($pagination->per_page);
		}

		/** -----------------------------------
		/**  Fetch Comments if necessary
		/** -----------------------------------*/

		$results = $result_ids;
		$mfields = array();

		/** ----------------------------------------
		/**  "Search by Member" link
		/** ----------------------------------------*/
		// We use this with the {member_search_path} variable

		$result_path = (preg_match("/".LD."member_search_path\s*=(.*?)".RD."/s", ee()->TMPL->tagdata, $match)) ? $match['1'] : 'search/results';
		$result_path = str_replace("\"", "", $result_path);
		$result_path = str_replace("'",  "", $result_path);

		$search_link = ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.ee()->functions->fetch_action_id('Search', 'do_search').'&amp;result_path='.$result_path.'&amp;mbr=';

		ee()->db->select('comments.comment_id, comments.entry_id, comments.channel_id, comments.author_id, comments.name, comments.email, comments.url, comments.location AS c_location, comments.ip_address, comments.comment_date, comments.edit_date, comments.comment, comments.site_id AS comment_site_id,
			members.username, members.group_id, members.location, members.occupation, members.interests, members.aol_im, members.yahoo_im, members.msn_im, members.icq, members.group_id, members.member_id, members.signature, members.sig_img_filename, members.sig_img_width, members.sig_img_height, members.avatar_filename, members.avatar_width, members.avatar_height, members.photo_filename, members.photo_width, members.photo_height,
			member_data.*,
			channel_titles.title, channel_titles.url_title, channel_titles.author_id AS entry_author_id,
			channels.comment_text_formatting, channels.comment_html_formatting, channels.comment_allow_img_urls, channels.comment_auto_link_urls, channels.channel_url, channels.comment_url, channels.channel_title, channels.channel_name AS channel_short_name'
		);

		ee()->db->join('channels',			'comments.channel_id = channels.channel_id',	'left');
		ee()->db->join('channel_titles',	'comments.entry_id = channel_titles.entry_id',	'left');
		ee()->db->join('members',			'members.member_id = comments.author_id',		'left');
		ee()->db->join('member_data',		'member_data.member_id = members.member_id',	'left');

		ee()->db->where_in('comments.comment_id', $result_ids);
		ee()->db->order_by($order_by, $this_sort);

		$query = ee()->db->get('comments');

		$total_results = $query->num_rows();

		if ($query->num_rows() > 0)
		{
			$results = $query->result_array();

			// Potentially a lot of information
			$query->free_result();
		}


		/** ----------------------------------------
		/**  Fetch custom member field IDs
		/** ----------------------------------------*/

		ee()->db->select('m_field_id, m_field_name');
		$query = ee()->db->get('member_fields');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$mfields[$row['m_field_name']] = $row['m_field_id'];
			}
		}

		/** ----------------------------------------
		/**  Instantiate Typography class
		/** ----------------------------------------*/

		ee()->load->library('typography');
		ee()->typography->initialize(array(
			'parse_images'		=> FALSE,
			'allow_headings'	=> FALSE,
			'word_censor'		=> (ee()->config->item('comment_word_censoring') == 'y') ? TRUE : FALSE)
		);

		/** ----------------------------------------
		/**  Fetch all the date-related variables
		/** ----------------------------------------*/

		$gmt_comment_date	= array();
		$comment_date		= array();
		$edit_date			= array();

		// We do this here to avoid processing cycles in the foreach loop

		$date_vars = array('gmt_comment_date', 'comment_date', 'edit_date');

		foreach ($date_vars as $val)
		{
			if (preg_match_all("/".LD.$val."\s+format=[\"'](.*?)[\"']".RD."/s", ee()->TMPL->tagdata, $matches))
			{
				for ($j = 0; $j < count($matches['0']); $j++)
				{
					$matches['0'][$j] = str_replace(LD, '', $matches['0'][$j]);
					$matches['0'][$j] = str_replace(RD, '', $matches['0'][$j]);

					switch ($val)
					{
						case 'comment_date':
							$comment_date[$matches['0'][$j]] = $matches['1'][$j];
							break;
						case 'gmt_comment_date':
							$gmt_comment_date[$matches['0'][$j]] = $matches['1'][$j];
							break;
						case 'edit_date':
							$edit_date[$matches['0'][$j]] = $matches['1'][$j];
							break;
					}
				}
			}
		}

		/** ----------------------------------------
		/**  Protected Variables for Cleanup Routine
		/** ----------------------------------------*/

		// Since comments do not necessarily require registration, and since
		// you are allowed to put member variables in comments, we need to kill
		// left-over unparsed junk.  The $member_vars array is all of those
		// member related variables that should be removed.

		$member_vars = array('location', 'occupation', 'interests', 'aol_im', 'yahoo_im', 'msn_im', 'icq',
			'signature', 'sig_img_filename', 'sig_img_width', 'sig_img_height',
			'avatar_filename', 'avatar_width', 'avatar_height',
			'photo_filename', 'photo_width', 'photo_height');

		$member_cond_vars = array();

		foreach($member_vars as $var)
		{
			$member_cond_vars[$var] = '';
		}


		/** ----------------------------------------
		/**  Start the processing loop
		/** ----------------------------------------*/

		$item_count = 0;

		$relative_count = 0;
		if ($enabled['pagination'])
		{
			$absolute_count = ($pagination->current_page == '') ? 0 : ($pagination->current_page - 1) * $pagination->per_page;
		}
		else
		{
			$absolute_count = 0;
		}

		foreach ($results as $id => $row)
		{
			if ( ! is_array($row))
			{
				continue;
			}

			$relative_count++;
			$absolute_count++;

			$row['count']			= $relative_count;
			$row['absolute_count']	= $absolute_count;
			if ($enabled['pagination'])
			{
				$row['total_comments']	= $total_rows;
			}
			$row['total_results']	= $total_results;

			// This lets the {if location} variable work

			if (isset($row['author_id']))
			{
				if ($row['author_id'] == 0)
				{
					$row['location'] = $row['c_location'];
				}
			}

			$tagdata = ee()->TMPL->tagdata;

			// -------------------------------------------
			// 'comment_entries_tagdata' hook.
			//  - Modify and play with the tagdata before everyone else
			//
			if (ee()->extensions->active_hook('comment_entries_tagdata') === TRUE)
			{
				$tagdata = ee()->extensions->call('comment_entries_tagdata', $tagdata, $row);
				if (ee()->extensions->end_script === TRUE) return $tagdata;
			}
			//
			// -------------------------------------------

			/** ----------------------------------------
			/**  Conditionals
			/** ----------------------------------------*/
			$cond = array_merge($member_cond_vars, $row);
			$cond['comments']			= (substr($id, 0, 1) == 't') ? 'FALSE' : 'TRUE';
			$cond['logged_in']			= (ee()->session->userdata('member_id') == 0) ? 'FALSE' : 'TRUE';
			$cond['logged_out']			= (ee()->session->userdata('member_id') != 0) ? 'FALSE' : 'TRUE';
			$cond['allow_comments'] 	= (isset($row['allow_comments']) AND $row['allow_comments'] == 'n') ? 'FALSE' : 'TRUE';
			$cond['signature_image']	= ( ! isset($row['sig_img_filename']) OR $row['sig_img_filename'] == '' OR ee()->config->item('enable_signatures') == 'n' OR ee()->session->userdata('display_signatures') == 'n') ? 'FALSE' : 'TRUE';
			$cond['avatar']				= ( ! isset($row['avatar_filename']) OR $row['avatar_filename'] == '' OR ee()->config->item('enable_avatars') == 'n' OR ee()->session->userdata('display_avatars') == 'n') ? 'FALSE' : 'TRUE';
			$cond['photo']				= ( ! isset($row['photo_filename']) OR $row['photo_filename'] == '' OR ee()->config->item('enable_photos') == 'n' OR ee()->session->userdata('display_photos') == 'n') ? 'FALSE' : 'TRUE';
			$cond['is_ignored']			= ( ! isset($row['member_id']) OR ! in_array($row['member_id'], ee()->session->userdata['ignore_list'])) ? 'FALSE' : 'TRUE';

			$cond['editable'] = FALSE;
			$cond['can_moderate_comment'] = FALSE;

			if (ee()->session->userdata['group_id'] == 1
				OR ee()->session->userdata['can_edit_all_comments'] == 'y'
				OR (ee()->session->userdata['can_edit_own_comments'] == 'y' && $row['entry_author_id'] == ee()->session->userdata['member_id'])
				)
			{
				$cond['editable'] = TRUE;
				$cond['can_moderate_comment'] = TRUE;
			}
			elseif (ee()->session->userdata['member_id'] != '0'
				&& $row['author_id'] == ee()->session->userdata['member_id']
				&& $row['comment_date'] > $this->_comment_edit_time_limit())
			{
					$cond['editable'] = TRUE;
			}

			if ( isset($mfields) && is_array($mfields) && count($mfields) > 0)
			{
				foreach($mfields as $key => $value)
				{
					if (isset($row['m_field_id_'.$value]))
					{
						$cond[$key] = $row['m_field_id_'.$value];
					}
				}
			}

			$tagdata = ee()->functions->prep_conditionals($tagdata, $cond);

			/** ----------------------------------------
			/**  Parse "single" variables
			/** ----------------------------------------*/
			foreach (ee()->TMPL->var_single as $key => $val)
			{

				/** ----------------------------------------
				/**  parse {switch} variable
				/** ----------------------------------------*/

				if (strncmp($key, 'switch', 6) == 0)
				{
					$sparam = ee()->functions->assign_parameters($key);

					$sw = '';

					if (isset($sparam['switch']))
					{
						$sopt = @explode("|", $sparam['switch']);

						$sw = $sopt[($relative_count + count($sopt) - 1) % count($sopt)];
					}

					$tagdata = ee()->TMPL->swap_var_single($key, $sw, $tagdata);
				}

				/** ----------------------------------------
				/**  parse permalink
				/** ----------------------------------------*/

				if ($key == 'permalink' && isset($row['comment_id']))
				{
					$tagdata = ee()->TMPL->swap_var_single(
						$key,
						ee()->functions->create_url($uristr.'#'.$row['comment_id'], FALSE),
						$tagdata
					);
				}

				/** ----------------------------------------
				/**  parse comment_path
				/** ----------------------------------------*/

				if (strncmp($key, 'comment_path', 12) == 0 OR strncmp($key, 'entry_id_path', 13) == 0)
				{
					$tagdata = ee()->TMPL->swap_var_single(
						$key,
						ee()->functions->create_url(ee()->functions->extract_path($key).'/'.$row['entry_id']),
						$tagdata
					);
				}


				/** ----------------------------------------
				/**  parse title permalink
				/** ----------------------------------------*/

				if (strncmp($key, 'title_permalink', 15) == 0 OR strncmp($key, 'url_title_path', 14) == 0)
				{
					$path = (ee()->functions->extract_path($key) != '' AND ee()->functions->extract_path($key) != 'SITE_INDEX') ? ee()->functions->extract_path($key).'/'.$row['url_title'] : $row['url_title'];

					$tagdata = ee()->TMPL->swap_var_single(
						$key,
						ee()->functions->create_url($path, FALSE),
						$tagdata
					);
				}

				/** ----------------------------------------
				/**  parse comment date
				/** ----------------------------------------*/

				if (isset($comment_date[$key]) && isset($row['comment_date']))
				{
					$tagdata = ee()->TMPL->swap_var_single(
						$key,
						ee()->localize->format_date(
							$comment_date[$key],
							$row['comment_date']
						),
						$tagdata
					);
				}

				/** ----------------------------------------
				/**  parse GMT comment date
				/** ----------------------------------------*/

				if (isset($gmt_comment_date[$key]) && isset($row['comment_date']))
				{
					$tagdata = ee()->TMPL->swap_var_single(
						$key,
						ee()->localize->format_date(
							$gmt_comment_date[$key],
							$row['comment_date'],
							FALSE
						),
						$tagdata
					);
				}

				/** ----------------------------------------
				/**  parse "last edit" date
				/** ----------------------------------------*/

				if (isset($edit_date[$key]))
				{
					if (isset($row['edit_date']))
					{
						$tagdata = ee()->TMPL->swap_var_single(
							$key,
							ee()->localize->format_date(
								$edit_date[$key],
								$row['edit_date']
							),
							$tagdata
						);
					}
				}


				/** ----------------------------------------
				/**  {member_search_path}
				/** ----------------------------------------*/

				if (strncmp($key, 'member_search_path', 18) == 0)
				{
					$tagdata = ee()->TMPL->swap_var_single($key, $search_link.$row['author_id'], $tagdata);
				}


				// {member_group_id}
				if ($key == 'member_group_id')
				{
					$tagdata = ee()->TMPL->swap_var_single($key, $row['group_id'], $tagdata);
				}

				// Prep the URL
				if (isset($row['url']))
				{
					ee()->load->helper('url');
					$row['url'] = prep_url($row['url']);
				}

				/** ----------------------------------------
				/**  {username}
				/** ----------------------------------------*/
				if ($key == "username")
				{
					$tagdata = ee()->TMPL->swap_var_single($val, (isset($row['username'])) ? $row['username'] : '', $tagdata);
				}

				/** ----------------------------------------
				/**  {author}
				/** ----------------------------------------*/
				if ($key == "author")
				{
					$tagdata = ee()->TMPL->swap_var_single($val, (isset($row['name'])) ? $row['name'] : '', $tagdata);
				}

				/** ----------------------------------------
				/**  {url_or_email} - Uses Raw Email Address, Like Channel Module
				/** ----------------------------------------*/

				if ($key == "url_or_email" AND isset($row['url']))
				{
					$tagdata = ee()->TMPL->swap_var_single($val, ($row['url'] != '') ? $row['url'] : $row['email'], $tagdata);
				}


				/** ----------------------------------------
				/**  {url_as_author}
				/** ----------------------------------------*/
				if ($key == "url_as_author" AND isset($row['url']))
				{
					if ($row['url'] != '')
					{
						$tagdata = ee()->TMPL->swap_var_single($val, "<a href=\"".$row['url']."\">".$row['name']."</a>", $tagdata);
					}
					else
					{
						$tagdata = ee()->TMPL->swap_var_single($val, $row['name'], $tagdata);
					}
				}

				/** ----------------------------------------
				/**  {url_or_email_as_author}
				/** ----------------------------------------*/

				if ($key == "url_or_email_as_author" AND isset($row['url']))
				{
					if ($row['url'] != '')
					{
						$tagdata = ee()->TMPL->swap_var_single($val, "<a href=\"".$row['url']."\">".$row['name']."</a>", $tagdata);
					}
					else
					{
						if ($row['email'] != '')
						{
							$tagdata = ee()->TMPL->swap_var_single($val, ee()->typography->encode_email($row['email'], $row['name']), $tagdata);
						}
						else
						{
							$tagdata = ee()->TMPL->swap_var_single($val, $row['name'], $tagdata);
						}
					}
				}

				/** ----------------------------------------
				/**  {url_or_email_as_link}
				/** ----------------------------------------*/

				if ($key == "url_or_email_as_link" AND isset($row['url']))
				{
					if ($row['url'] != '')
					{
						$tagdata = ee()->TMPL->swap_var_single($val, "<a href=\"".$row['url']."\">".$row['url']."</a>", $tagdata);
					}
					else
					{
						if ($row['email'] != '')
						{
							$tagdata = ee()->TMPL->swap_var_single($val, ee()->typography->encode_email($row['email']), $tagdata);
						}
						else
						{
							$tagdata = ee()->TMPL->swap_var_single($val, $row['name'], $tagdata);
						}
					}
				}

				/** ----------------------------------------
				/**  {comment_auto_path}
				/** ----------------------------------------*/

				if ($key == "comment_auto_path")
				{
					$path = ($row['comment_url'] == '') ? $row['channel_url'] : $row['comment_url'];

					$tagdata = ee()->TMPL->swap_var_single($key, $path, $tagdata);
				}

				/** ----------------------------------------
				/**  {comment_url_title_auto_path}
				/** ----------------------------------------*/

				if ($key == "comment_url_title_auto_path")
				{
					$path = ($row['comment_url'] == '') ? $row['channel_url'] : $row['comment_url'];

					$tagdata = ee()->TMPL->swap_var_single(
						$key,
						$path.'/'.$row['url_title'],
						$tagdata
					);
				}

				/** ----------------------------------------
				/**  {comment_entry_id_auto_path}
				/** ----------------------------------------*/

				if ($key == "comment_entry_id_auto_path")
				{
					$path = ($row['comment_url'] == '') ? $row['channel_url'] : $row['comment_url'];

					$tagdata = ee()->TMPL->swap_var_single(
						$key,
						$path.'/'.$row['entry_id'],
						$tagdata
					);
				}


				/** ----------------------------------------
				/**  parse comment_stripped field
				/** ----------------------------------------*/

				if ($key == "comment_stripped" AND isset($row['comment']))
				{

					$tagdata = ee()->TMPL->swap_var_single(
						$key,
						ee()->functions->encode_ee_tags($row['comment'], TRUE),
						$tagdata
					);
				}

				/** ----------------------------------------
				/**  parse comment field
				/** ----------------------------------------*/

				if ($key == 'comment' AND isset($row['comment']))
				{
					// -------------------------------------------
					// 'comment_entries_comment_format' hook.
					//  - Play with the tagdata contents of the comment entries
					//
						if (ee()->extensions->active_hook('comment_entries_comment_format') === TRUE)
						{
							$comment = ee()->extensions->call('comment_entries_comment_format', $row);

							if (ee()->extensions->end_script === TRUE) return;
						}
						else
						{
							$comment = ee()->typography->parse_type(
								$row['comment'],
								array(
									'text_format'	=> $row['comment_text_formatting'],
									'html_format'	=> $row['comment_html_formatting'],
									'auto_links'	=> $row['comment_auto_link_urls'],
									'allow_img_url' => $row['comment_allow_img_urls']
								)
							);
						}

					$tagdata = ee()->TMPL->swap_var_single($key, $comment, $tagdata);
				}

				//  {location}

				if ($key == 'location' AND (isset($row['location']) OR isset($row['c_location'])))
				{
					$tagdata = ee()->TMPL->swap_var_single($key, (empty($row['location'])) ? $row['c_location'] : $row['location'], $tagdata);
				}


				/** ----------------------------------------
				/**  {signature}
				/** ----------------------------------------*/

				if ($key == "signature")
				{
					if (ee()->session->userdata('display_signatures') == 'n' OR  ! isset($row['signature']) OR $row['signature'] == '' OR ee()->session->userdata('display_signatures') == 'n')
					{
						$tagdata = ee()->TMPL->swap_var_single($key, '', $tagdata);
					}
					else
					{
						$tagdata = ee()->TMPL->swap_var_single(
							$key,
							ee()->typography->parse_type(
								$row['signature'],
								array(
									'text_format'	=> 'xhtml',
									'html_format'	=> 'safe',
									'auto_links'	=> 'y',
									'allow_img_url' => ee()->config->item('sig_allow_img_hotlink')
								)
							),
							$tagdata
						);
					}
				}


				if ($key == "signature_image_url")
				{
					if (ee()->session->userdata('display_signatures') == 'n' OR $row['sig_img_filename'] == ''  OR ee()->session->userdata('display_signatures') == 'n')
					{
						$tagdata = ee()->TMPL->swap_var_single($key, '', $tagdata);
						$tagdata = ee()->TMPL->swap_var_single('signature_image_width', '', $tagdata);
						$tagdata = ee()->TMPL->swap_var_single('signature_image_height', '', $tagdata);
					}
					else
					{
						$tagdata = ee()->TMPL->swap_var_single($key, ee()->config->slash_item('sig_img_url').$row['sig_img_filename'], $tagdata);
						$tagdata = ee()->TMPL->swap_var_single('signature_image_width', $row['sig_img_width'], $tagdata);
						$tagdata = ee()->TMPL->swap_var_single('signature_image_height', $row['sig_img_height'], $tagdata);
					}
				}

				if ($key == "avatar_url")
				{
					if ( ! isset($row['avatar_filename']))
					{
						$row['avatar_filename'] = '';
					}

					if (ee()->session->userdata('display_avatars') == 'n' OR $row['avatar_filename'] == ''  OR ee()->session->userdata('display_avatars') == 'n')
					{
						$tagdata = ee()->TMPL->swap_var_single($key, '', $tagdata);
						$tagdata = ee()->TMPL->swap_var_single('avatar_image_width', '', $tagdata);
						$tagdata = ee()->TMPL->swap_var_single('avatar_image_height', '', $tagdata);
					}
					else
					{
						$tagdata = ee()->TMPL->swap_var_single($key, ee()->config->slash_item('avatar_url').$row['avatar_filename'], $tagdata);
						$tagdata = ee()->TMPL->swap_var_single('avatar_image_width', $row['avatar_width'], $tagdata);
						$tagdata = ee()->TMPL->swap_var_single('avatar_image_height', $row['avatar_height'], $tagdata);
					}
				}

				if ($key == "photo_url")
				{
					if ( ! isset($row['photo_filename']))
					{
						$row['photo_filename'] = '';
					}

					if (ee()->session->userdata('display_photos') == 'n' OR $row['photo_filename'] == ''  OR ee()->session->userdata('display_photos') == 'n')
					{
						$tagdata = ee()->TMPL->swap_var_single($key, '', $tagdata);
						$tagdata = ee()->TMPL->swap_var_single('photo_image_width', '', $tagdata);
						$tagdata = ee()->TMPL->swap_var_single('photo_image_height', '', $tagdata);
					}
					else
					{
						$tagdata = ee()->TMPL->swap_var_single($key, ee()->config->slash_item('photo_url').$row['photo_filename'], $tagdata);
						$tagdata = ee()->TMPL->swap_var_single('photo_image_width', $row['photo_width'], $tagdata);
						$tagdata = ee()->TMPL->swap_var_single('photo_image_height', $row['photo_height'], $tagdata);
					}
				}


				/** ----------------------------------------
				/**  parse basic fields
				/** ----------------------------------------*/

				if (isset($row[$val]) && $val != 'member_id')
				{
					$tagdata = ee()->TMPL->swap_var_single($val, $row[$val], $tagdata);
				}

				/** ----------------------------------------
				/**  parse custom member fields
				/** ----------------------------------------*/

				if ( isset($mfields[$val]))
				{
					// Since comments do not necessarily require registration, and since
					// you are allowed to put custom member variables in comments,
					// we delete them if no such row exists

					$return_val = (isset($row['m_field_id_'.$mfields[$val]])) ? $row['m_field_id_'.$mfields[$val]] : '';

					$tagdata = ee()->TMPL->swap_var_single(
						$val,
						$return_val,
						$tagdata
					);
				}

				/** ----------------------------------------
				/**  Clean up left over member variables
				/** ----------------------------------------*/

				if (in_array($val, $member_vars))
				{
					$tagdata = str_replace(LD.$val.RD, '', $tagdata);
				}
			}

			if ($this->show_anchor == TRUE)
			{
				$return .= "<a name=\"".$item_count."\"></a>\n";
			}

			$return .= $tagdata;

			$item_count++;
		}

		/** ----------------------------------------
		/**  Parse path variable
		/** ----------------------------------------*/

		$return = preg_replace_callback("/".LD."\s*path=(.+?)".RD."/", array(&ee()->functions, 'create_url'), $return);

		/** ----------------------------------------
		/**  Add pagination to result
		/** ----------------------------------------*/

		if ($enabled['pagination'])
		{
			return $pagination->render($return);
		}
		else
		{
			return $return;
		}
	}


	// --------------------------------------------------------------------


	/**
	 * Fetch comment ids associated entry ids
	 *
	 * @access	public
	 * @return	string
	 */
	function fetch_comment_ids_param($comment_id_param)
	{
		$entry_ids = array();

		ee()->db->distinct();
		ee()->db->select('entry_id');
		ee()->functions->ar_andor_string($comment_id_param, 'comment_id');
		$query = ee()->db->get('comments');

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				$entry_ids[] = $row['entry_id'];
			}
		}

		return $entry_ids;
	}


	// --------------------------------------------------------------------

	/**
	 * Comment Submission Form
	 *
	 * @access	public
	 * @return	string
	 */
	function form($return_form = FALSE, $captcha = '')
	{
		$qstring		 = ee()->uri->query_string;
		$entry_where	 = array();
		$halt_processing = FALSE;

		/** --------------------------------------
		/**  Remove page number
		/** --------------------------------------*/

		if (preg_match("#(^|/)P(\d+)(/|$)#", $qstring, $match))
		{
			$qstring = trim(reduce_double_slashes(str_replace($match['0'], '/', $qstring)), '/');
		}

		// Figure out the right entry ID
		// Order of precedence: POST, entry_id=, url_title=, $qstring
		if (isset($_POST['entry_id']))
		{
			$entry_where = array('entry_id' => $_POST['entry_id']);
		}
		elseif ($entry_id = ee()->TMPL->fetch_param('entry_id'))
		{
			$entry_where = array('entry_id' => $entry_id);
		}
		elseif ($url_title = ee()->TMPL->fetch_param('url_title'))
		{
			$entry_where = array('url_title' => $url_title);
		}
		else
		{
			// If there is a slash in the entry ID we'll kill everything after it.
			$entry_id = trim($qstring);
			$entry_id = preg_replace("#/.+#", "", $entry_id);

			if ( ! is_numeric($entry_id))
			{
				$entry_where = array('url_title' => $entry_id);
			}
			else
			{
				$entry_where = array('entry_id' => $entry_id);
			}
		}


		/** ----------------------------------------
		/**  Are comments allowed?
		/** ----------------------------------------*/

		if ($channel = ee()->TMPL->fetch_param('channel'))
		{
			ee()->db->select('channel_id');
			ee()->functions->ar_andor_string($channel, 'channel_name');
			ee()->db->where_in('site_id', ee()->TMPL->site_ids);
			$query = ee()->db->get('channels');

			if ($query->num_rows() == 0)
			{
				return FALSE;
			}

			if ($query->num_rows() == 1)
			{
				ee()->db->where('channel_titles.channel_id', $query->row('channel_id'));
			}
			else
			{
				$ids = array();

				foreach ($query->result_array() as $row)
				{
					$ids[] = $row['channel_id'];
				}

				ee()->db->where_in('channel_titles.channel_id', $ids);
			}
		}

		// The where clauses above will affect this query - it's below the conditional
		// because AR cannot keep track of two queries at once

		ee()->db->select('channel_titles.entry_id, channel_titles.entry_date, channel_titles.comment_expiration_date, channel_titles.allow_comments, channels.comment_system_enabled, channels.comment_use_captcha, channels.comment_expiration');
		ee()->db->from(array('channel_titles', 'channels'));

		ee()->db->where_in('channel_titles.site_id', ee()->TMPL->site_ids);
		ee()->db->where('channel_titles.channel_id = '.ee()->db->dbprefix('channels').'.channel_id');

		if ($e_status = ee()->TMPL->fetch_param('entry_status'))
		{
			$e_status = str_replace('Open',	'open',	$e_status);
			$e_status = str_replace('Closed', 'closed', $e_status);

			ee()->functions->ar_andor_string($e_status, 'status');

			if (stristr($sql, "'closed'") === FALSE)
			{
				ee()->db->where('status !=', 'closed');
			}
		}
		else
		{
			ee()->db->where('status !=', 'closed');
		}


		ee()->db->where($entry_where);

		$query = ee()->db->get();

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		if ($query->row('allow_comments') == 'n' OR $query->row('comment_system_enabled') == 'n')
		{
			$halt_processing = 'disabled';
		}

		/** ----------------------------------------
		/**  Smart Notifications? Mark comments as read.
		/** ----------------------------------------*/

		if (ee()->session->userdata('smart_notifications') == 'y')
		{
			ee()->load->library('subscription');
			ee()->subscription->init('comment', array('entry_id' => $query->row('entry_id')), TRUE);
			ee()->subscription->mark_as_read();
		}

		/** ----------------------------------------
		/**  Return the "no cache" version of the form
		/** ----------------------------------------*/

		if ($return_form == FALSE)
		{
			if ($query->row('comment_use_captcha')  == 'n')
			{
				ee()->TMPL->tagdata = str_replace(LD.'captcha'.RD, '', ee()->TMPL->tagdata);
			}

			$nc = '';

			if (is_array(ee()->TMPL->tagparams) AND count(ee()->TMPL->tagparams) > 0)
			{
				foreach (ee()->TMPL->tagparams as $key => $val)
				{
					switch ($key)
					{
						case 'form_class':
							$nc .= 'class="'.$val.'" ';
							break;
						case 'form_id':
							$nc .= 'id="'.$val.'" ';
							break;
						default:
							$nc .= ' '.$key.'="'.$val.'" ';
					}
				}
			}

			return '{NOCACHE_COMMENT_FORM="'.$nc.'"}'.ee()->TMPL->tagdata.'{/NOCACHE_FORM}';
		}

		/** ----------------------------------------
		/**  Has commenting expired?
		/** ----------------------------------------*/

		$mode = ( ! isset($this->comment_expiration_mode)) ? 0 : $this->comment_expiration_mode;

		//  First check whether expiration is overriden
		if (ee()->config->item('comment_moderation_override') !== 'y')
		{
			if ($mode == 0)
			{
				if ($query->row('comment_expiration_date')  > 0)
				{
					if (ee()->localize->now > $query->row('comment_expiration_date') )
					{
						$halt_processing = 'expired';
					}
				}
			}
			else
			{
				if ($query->row('comment_expiration')  > 0)
				{
				 	$days = $query->row('entry_date')  + ($query->row('comment_expiration')  * 86400);

					if (ee()->localize->now > $days)
					{
						$halt_processing = 'expired';
					}
				}
			}
		}

		$tagdata = ee()->TMPL->tagdata;

		if ($halt_processing != FALSE)
		{
			foreach (ee()->TMPL->var_cond as $key => $val)
			{
				if ($halt_processing == 'expired')
				{
					if (isset($val['3']) && $val['3'] == 'comments_expired')
					{
						return $val['2'];
					}
				}
				elseif ($halt_processing == 'disabled')
				{
					if (isset($val['3']) && $val['3'] == 'comments_disabled')
					{
						return $val['2'];
					}
				}
			}

			// If there is no conditional- just return the message
			ee()->lang->loadfile('comment');
			return ee()->lang->line('cmt_commenting_has_expired');
		}


		// -------------------------------------------
		// 'comment_form_tagdata' hook.
		//  - Modify, add, etc. something to the comment form
		//
			if (ee()->extensions->active_hook('comment_form_tagdata') === TRUE)
			{
				$tagdata = ee()->extensions->call('comment_form_tagdata', $tagdata);
				if (ee()->extensions->end_script === TRUE) return;
			}
		//
		// -------------------------------------------

		/** ----------------------------------------
		/**  Conditionals
		/** ----------------------------------------*/

		$cond = array();
		$cond['logged_in']	= (ee()->session->userdata('member_id') == 0) ? 'FALSE' : 'TRUE';
		$cond['logged_out']	= (ee()->session->userdata('member_id') != 0) ? 'FALSE' : 'TRUE';

		if ($query->row('comment_use_captcha')  == 'n')
		{
			$cond['captcha'] = 'FALSE';
		}
		elseif ($query->row('comment_use_captcha')  == 'y')
		{
			$cond['captcha'] =  (ee()->config->item('captcha_require_members') == 'y'  OR
								(ee()->config->item('captcha_require_members') == 'n' AND ee()->session->userdata('member_id') == 0)) ? 'TRUE' : 'FALSE';
		}

		$tagdata = ee()->functions->prep_conditionals($tagdata, $cond);

		/** ----------------------------------------
		/**  Single Variables
		/** ----------------------------------------*/

		// Load the form helper
		ee()->load->helper('form');

		foreach (ee()->TMPL->var_single as $key => $val)
		{
			/** ----------------------------------------
			/**  parse {name}
			/** ----------------------------------------*/

			if ($key == 'name')
			{
				$name = (ee()->session->userdata['screen_name'] != '') ? ee()->session->userdata['screen_name'] : ee()->session->userdata['username'];

				$name = ( ! isset($_POST['name'])) ? $name : $_POST['name'];

				$tagdata = ee()->TMPL->swap_var_single($key, form_prep($name), $tagdata);
			}

			/** ----------------------------------------
			/**  parse {email}
			/** ----------------------------------------*/

			if ($key == 'email')
			{
				$email = ( ! isset($_POST['email'])) ? ee()->session->userdata['email'] : $_POST['email'];

				$tagdata = ee()->TMPL->swap_var_single($key, form_prep($email), $tagdata);
			}

			/** ----------------------------------------
			/**  parse {url}
			/** ----------------------------------------*/

			if ($key == 'url')
			{
				$url = ( ! isset($_POST['url'])) ? ee()->session->userdata['url'] : $_POST['url'];

				if ($url == '')
				{
					$url = 'http://';
				}

				$tagdata = ee()->TMPL->swap_var_single($key, form_prep($url), $tagdata);
			}

			/** ----------------------------------------
			/**  parse {location}
			/** ----------------------------------------*/

			if ($key == 'location')
			{
				$location = ( ! isset($_POST['location'])) ? ee()->session->userdata['location'] : $_POST['location'];

				$tagdata = ee()->TMPL->swap_var_single($key, form_prep($location), $tagdata);
			}

			/** ----------------------------------------
			/**  parse {comment}
			/** ----------------------------------------*/

			if ($key == 'comment')
			{
				$comment = ( ! isset($_POST['comment'])) ? '' : $_POST['comment'];

				$tagdata = ee()->TMPL->swap_var_single($key, $comment, $tagdata);
			}

			/** ----------------------------------------
			/**  parse {captcha_word}
			/** ----------------------------------------*/

			if ($key == 'captcha_word')
			{
				$tagdata = ee()->TMPL->swap_var_single($key, '', $tagdata);
			}

			/** ----------------------------------------
			/**  parse {save_info}
			/** ----------------------------------------*/

			if ($key == 'save_info')
			{
				$save_info = ( ! isset($_POST['save_info'])) ? '' : $_POST['save_info'];

				$notify = ( ! isset(ee()->session->userdata['notify_by_default'])) ? ee()->input->cookie('save_info') : ee()->session->userdata['notify_by_default'];

				$checked = ( ! isset($_POST['PRV'])) ? $notify : $save_info;

				$tagdata = ee()->TMPL->swap_var_single($key, ($checked == 'yes') ? "checked=\"checked\"" : '', $tagdata);
			}

			/** ----------------------------------------
			/**  parse {notify_me}
			/** ----------------------------------------*/

			if ($key == 'notify_me')
			{
				$checked = '';

				if ( ! isset($_POST['PRV']))
				{
					if (ee()->input->cookie('notify_me'))
					{
						$checked = ee()->input->cookie('notify_me');
					}

					if (isset(ee()->session->userdata['notify_by_default']))
					{
						$checked = (ee()->session->userdata['notify_by_default'] == 'y') ? 'yes' : '';
					}
				}

				if (isset($_POST['notify_me']))
				{
					$checked = $_POST['notify_me'];
				}

				$tagdata = ee()->TMPL->swap_var_single($key, ($checked == 'yes') ? "checked=\"checked\"" : '', $tagdata);
			}
		}

		/** ----------------------------------------
		/**  Create form
		/** ----------------------------------------*/

		$RET = ee()->functions->fetch_current_uri();

		if (isset($_POST['RET']))
		{
			$RET = ee()->input->post('RET');
		}
		elseif (ee()->TMPL->fetch_param('return') && ee()->TMPL->fetch_param('return') != "")
		{
			$RET =  ee()->TMPL->fetch_param('return');
		}

		$PRV = (isset($_POST['PRV'])) ? $_POST['PRV'] : ee()->TMPL->fetch_param('preview');
		$XID = (isset($_POST['XID'])) ? $_POST['XID'] : '';

		$hidden_fields = array(
			'ACT'	  	=> ee()->functions->fetch_action_id('Comment', 'insert_new_comment'),
			'RET'	  	=> $RET,
			'URI'	  	=> (ee()->uri->uri_string == '') ? 'index' : ee()->uri->uri_string,
			'PRV'	  	=> $PRV,
			'XID'	  	=> $XID,
			'entry_id' 	=> $query->row('entry_id')
		);

		if ($query->row('comment_use_captcha')  == 'y')
		{
			if (preg_match("/({captcha})/", $tagdata))
			{
				$tagdata = preg_replace("/{captcha}/", ee()->functions->create_captcha(), $tagdata);
			}
		}

		// -------------------------------------------
		// 'comment_form_hidden_fields' hook.
		//  - Add/Remove Hidden Fields for Comment Form
		//
			if (ee()->extensions->active_hook('comment_form_hidden_fields') === TRUE)
			{
				$hidden_fields = ee()->extensions->call('comment_form_hidden_fields', $hidden_fields);
				if (ee()->extensions->end_script === TRUE) return;
			}
		//
		// -------------------------------------------

		$uri_string = (ee()->uri->uri_string == '') ? 'index' : ee()->uri->uri_string;
		$url = ee()->functions->fetch_site_index(0,0).'/'.$uri_string;

		$data = array(
			'action'		=> reduce_double_slashes($url),
			'hidden_fields'	=> $hidden_fields,
			'id'			=> ( ! isset(ee()->TMPL->tagparams['id'])) ? 'comment_form' : ee()->TMPL->tagparams['id'],
			'class'			=> ( ! isset(ee()->TMPL->tagparams['class'])) ? NULL : ee()->TMPL->tagparams['class']
		);

		if (ee()->TMPL->fetch_param('name') !== FALSE &&
			preg_match("#^[a-zA-Z0-9_\-]+$#i", ee()->TMPL->fetch_param('name'), $match))
		{
			$data['name'] = ee()->TMPL->fetch_param('name');
		}

		$res  = ee()->functions->form_declaration($data);

		$res .= stripslashes($tagdata);
		$res .= "</form>";

		// -------------------------------------------
		// 'comment_form_end' hook.
		//  - Modify, add, etc. something to the comment form at end of processing
		//
			if (ee()->extensions->active_hook('comment_form_end') === TRUE)
			{
				$res = ee()->extensions->call('comment_form_end', $res);
				if (ee()->extensions->end_script === TRUE) return $res;
			}
		//
		// -------------------------------------------


		return $res;
	}

	// --------------------------------------------------------------------

	/**
	 * Preview
	 *
	 * @access	public
	 * @return	void
	 */
	function preview()
	{
		$entry_id = (isset($_POST['entry_id'])) ? $_POST['entry_id'] : ee()->uri->query_string;

		if ( ! is_numeric($entry_id) OR empty($_POST['comment']))
		{
			return FALSE;
		}

		/** ----------------------------------------
		/**  Instantiate Typography class
		/** ----------------------------------------*/

		ee()->load->library('typography');
		ee()->typography->initialize(array(
			'parse_images'		=> FALSE,
			'allow_headings'	=> FALSE,
			'encode_email'		=> FALSE,
			'word_censor'		=> (ee()->config->item('comment_word_censoring') == 'y') ? TRUE : FALSE)
		);

		ee()->db->select('channels.comment_text_formatting, channels.comment_html_formatting, channels.comment_allow_img_urls, channels.comment_auto_link_urls, channels.comment_max_chars');
		ee()->db->where('channel_titles.channel_id = '.ee()->db->dbprefix('channels').'.channel_id');
		ee()->db->where('channel_titles.entry_id', $entry_id);
		ee()->db->from(array('channels', 'channel_titles'));

		$query = ee()->db->get();

		if ($query->num_rows() == 0)
		{
			return '';
		}

		/** -------------------------------------
		/**  Check size of comment
		/** -------------------------------------*/

		if ($query->row('comment_max_chars')  != '' AND $query->row('comment_max_chars')  != 0)
		{
			if (strlen($_POST['comment']) > $query->row('comment_max_chars') )
			{
				$str = str_replace("%n", strlen($_POST['comment']), ee()->lang->line('cmt_too_large'));

				$str = str_replace("%x", $query->row('comment_max_chars') , $str);

				return ee()->output->show_user_error('submission', $str);
			}
		}

		$formatting = 'none';

		if ($query->num_rows())
		{
			$formatting = $query->row('comment_text_formatting') ;
		}

		$tagdata = ee()->TMPL->tagdata;

		// -------------------------------------------
		// 'comment_preview_tagdata' hook.
		//  - Play with the tagdata contents of the comment preview
		//
			if (ee()->extensions->active_hook('comment_preview_tagdata') === TRUE)
			{
				$tagdata = ee()->extensions->call('comment_preview_tagdata', $tagdata);
				if (ee()->extensions->end_script === TRUE) return;
			}
		//
		// -------------------------------------------

		/** ----------------------------------------
		/**  Fetch all the date-related variables
		/** ----------------------------------------*/

		$comment_date = array();

		if (preg_match_all("/".LD."comment_date\s+format=[\"'](.*?)[\"']".RD."/s", $tagdata, $matches))
		{
			for ($j = 0; $j < count($matches['0']); $j++)
			{
				$matches['0'][$j] = str_replace(LD, '', $matches['0'][$j]);
				$matches['0'][$j] = str_replace(RD, '', $matches['0'][$j]);

				$comment_date[$matches['0'][$j]] = $matches['1'][$j];
			}
		}

        /** ----------------------------------------
        /**  Set defaults based on member data as needed
        /** ----------------------------------------*/

		$name		= ee()->input->post('name', TRUE);
		$email		= ee()->input->post('email', TRUE); // this is just for preview, actual submission will validate the email address
	 	$url		= ee()->input->post('url', TRUE);
	 	$location	= ee()->input->post('location', TRUE);

		if (ee()->session->userdata('member_id') != 0)
		{
			 $name		= ee()->session->userdata('screen_name') ? ee()->session->userdata('screen_name') : ee()->session->userdata('username');
			 $email		= ee()->session->userdata('email');
			 $url		= (string) ee()->session->userdata('url');
			 $location	= (string) ee()->session->userdata('location');
		}

		/** ----------------------------------------
		/**  Conditionals
		/** ----------------------------------------*/

		$cond = $_POST; // Sanitized on input and also in prep_conditionals, so no real worries here
		$cond['logged_in']	= (ee()->session->userdata('member_id') == 0) ? 'FALSE' : 'TRUE';
		$cond['logged_out']	= (ee()->session->userdata('member_id') != 0) ? 'FALSE' : 'TRUE';
		$cond['name']		= $name;
		$cond['email']		= $email;
		$cond['url']		= ($url == 'http://') ? '' : $url;
		$cond['location']	= $location;

		$tagdata = ee()->functions->prep_conditionals($tagdata, $cond);


		// Prep the URL

		if ($url != '')
		{
			ee()->load->helper('url');
			$url = prep_url($url);
		}

		/** ----------------------------------------
		/**  Single Variables
		/** ----------------------------------------*/

		foreach (ee()->TMPL->var_single as $key => $val)
		{
			// Start with the simple ones
			if (in_array($key, array('name', 'email', 'url', 'location')))
			{
				$tagdata = ee()->TMPL->swap_var_single($key, $$key, $tagdata);
			}

			//  {url_or_email}
			elseif ($key == "url_or_email")
			{
				$temp = $url;

				if ($temp == '' AND $email != '')
				{
					$temp = ee()->typography->encode_email($email, '', 0);
				}

				$tagdata = ee()->TMPL->swap_var_single($val, $temp, $tagdata);
			}


			//  {url_or_email_as_author}
			elseif ($key == "url_or_email_as_author")
			{
				if ($url != '')
				{
					$tagdata = ee()->TMPL->swap_var_single($val, "<a href=\"".$url."\">".$name."</a>", $tagdata);
				}
				else
				{
					if ($email != '')
					{
						$tagdata = ee()->TMPL->swap_var_single($val, ee()->typography->encode_email($email, $name), $tagdata);
					}
					else
					{
						$tagdata = ee()->TMPL->swap_var_single($val, $name, $tagdata);
					}
				}
			}

			//  {url_or_email_as_link}
			elseif ($key == "url_or_email_as_link")
			{
				if ($url != '')
				{
					$tagdata = ee()->TMPL->swap_var_single($val, "<a href=\"".$url."\">".$url."</a>", $tagdata);
				}
				else
				{
					if ($email != '')
					{
						$tagdata = ee()->TMPL->swap_var_single($val, ee()->typography->encode_email($email), $tagdata);
					}
					else
					{
						$tagdata = ee()->TMPL->swap_var_single($val, $name, $tagdata);
					}
				}
			}

			//  {url_as_author}

            elseif ($key == 'url_as_author')
            {
                if ($url != '')
                {
                    $tagdata = ee()->TMPL->swap_var_single($val, '<a href="'.$url.'">'.$name.'</a>', $tagdata);
                }
                else
                {
                    $tagdata = ee()->TMPL->swap_var_single($val, $name, $tagdata);
                }
            }

			/** ----------------------------------------
			/**  parse comment field
			/** ----------------------------------------*/

			elseif ($key == 'comment')
			{
				// -------------------------------------------
				// 'comment_preview_comment_format' hook.
				//  - Play with the tagdata contents of the comment preview
				//
					if (ee()->extensions->active_hook('comment_preview_comment_format') === TRUE)
					{
						$data = ee()->extensions->call('comment_preview_comment_format', $query->row());
						if (ee()->extensions->end_script === TRUE) return;
					}
					else
					{
						$data = ee()->typography->parse_type(
							ee()->input->post('comment'),
							array(
								'text_format'	=> $query->row('comment_text_formatting') ,
								'html_format'	=> $query->row('comment_html_formatting') ,
								'auto_links'	=> $query->row('comment_auto_link_urls') ,
								'allow_img_url' => $query->row('comment_allow_img_urls')
							)
						);
					}

				// -------------------------------------------

				$tagdata = ee()->TMPL->swap_var_single($key, $data, $tagdata);
			}

			/** ----------------------------------------
			/**  parse comment date
			/** ----------------------------------------*/

			elseif (isset($comment_date[$key]))
			{
				$tagdata = ee()->TMPL->swap_var_single(
					$key,
					ee()->localize->format_date(
						$comment_date[$key],
						ee()->localize->now
					),
					$tagdata
				);
			}

		}

		return $tagdata;
	}

	// --------------------------------------------------------------------

	/**
	 * Preview Handler
	 *
	 * @access	public
	 * @return	void
	 */
	function preview_handler()
	{
		if (ee()->input->post('PRV') == '')
		{
			$error[] = ee()->lang->line('cmt_no_preview_template_specified');

			return ee()->output->show_user_error('general', $error);
		}

		if ( ! isset($_POST['PRV']) or $_POST['PRV'] == '')
		{
			exit('Preview template not specified in your comment form tag');
		}

		// Clean return value- segments only
		$clean_return = str_replace(ee()->functions->fetch_site_index(), '', $_POST['RET']);

		$_POST['PRV'] = trim_slashes(ee()->security->xss_clean($_POST['PRV']));

		ee()->functions->clear_caching('all', $_POST['PRV']);
		ee()->functions->clear_caching('all', $clean_return);

		require APPPATH.'libraries/Template.php';

		ee()->TMPL = new EE_Template();

		$preview = ( ! ee()->input->post('PRV')) ? '' : ee()->input->get_post('PRV');

		if (strpos($preview, '/') === FALSE)
		{
			$preview = '';
		}
		else
		{
			$ex = explode("/", $preview);

			if (count($ex) != 2)
			{
				$preview = '';
			}
		}

		$group = ($preview = '') ? 'channel' : $ex[0];
		$templ = ($preview = '') ? 'preview' : $ex[1];


		// this makes sure the query string is seen correctly by tags on the template
		ee()->TMPL->parse_template_uri();
		ee()->TMPL->run_template_engine($group, $templ);
	}

	// --------------------------------------------------------------------

	/**
	 * Insert New Comment
	 *
	 * @access	public
	 * @return	string
	 */
	function insert_new_comment()
	{
		$default = array('name', 'email', 'url', 'comment', 'location', 'entry_id');

		foreach ($default as $val)
		{
			if ( ! isset($_POST[$val]))
			{
				$_POST[$val] = '';
			}
		}

		// No entry ID?  What the heck are they doing?
		if ( ! is_numeric($_POST['entry_id']))
		{
			return FALSE;
		}

		/** ----------------------------------------
		/**  Fetch the comment language pack
		/** ----------------------------------------*/

		ee()->lang->loadfile('comment');

		//  No comment- let's end it here
		if (trim($_POST['comment']) == '')
		{
			$error = ee()->lang->line('cmt_missing_comment');
			return ee()->output->show_user_error('submission', $error);
		}

		/** ----------------------------------------
		/**  Is the user banned?
		/** ----------------------------------------*/

		if (ee()->session->userdata['is_banned'] == TRUE)
		{
			return ee()->output->show_user_error('general', array(ee()->lang->line('not_authorized')));
		}

		/** ----------------------------------------
		/**  Is the IP address and User Agent required?
		/** ----------------------------------------*/

		if (ee()->config->item('require_ip_for_posting') == 'y')
		{
			if (ee()->input->ip_address() == '0.0.0.0' OR ee()->session->userdata['user_agent'] == "")
			{
				return ee()->output->show_user_error('general', array(ee()->lang->line('not_authorized')));
			}
		}

		/** ----------------------------------------
		/**  Is the nation of the user banend?
		/** ----------------------------------------*/
		ee()->session->nation_ban_check();

		/** ----------------------------------------
		/**  Can the user post comments?
		/** ----------------------------------------*/

		if (ee()->session->userdata['can_post_comments'] == 'n')
		{
			$error[] = ee()->lang->line('cmt_no_authorized_for_comments');

			return ee()->output->show_user_error('general', $error);
		}

		/** ----------------------------------------
		/**  Blacklist/Whitelist Check
		/** ----------------------------------------*/

		if (ee()->blacklist->blacklisted == 'y' && ee()->blacklist->whitelisted == 'n')
		{
			return ee()->output->show_user_error('general', array(ee()->lang->line('not_authorized')));
		}

		/** ----------------------------------------
		/**  Is this a preview request?
		/** ----------------------------------------*/

		if (isset($_POST['preview']))
		{
			return $this->preview_handler();
		}

		// -------------------------------------------
		// 'insert_comment_start' hook.
		//  - Allows complete rewrite of comment submission routine.
		//  - Or could be used to modify the POST data before processing
		//
			ee()->extensions->call('insert_comment_start');
			if (ee()->extensions->end_script === TRUE) return;
		//
		// -------------------------------------------

		/** ----------------------------------------
		/**  Fetch channel preferences
		/** ----------------------------------------*/

// Bummer, saw the hook after converting the query
/*
		ee()->db->select('channel_titles.title, channel_titles.url_title, channel_titles.channel_id, channel_titles.author_id,
						channel_titles.comment_total, channel_titles.allow_comments, channel_titles.entry_date, channel_titles.comment_expiration_date,
						channels.channel_title, channels.comment_system_enabled, channels.comment_max_chars, channels.comment_use_captcha,
						channels.comment_timelock, channels.comment_require_membership, channels.comment_moderate, channels.comment_require_email,
						channels.comment_notify, channels.comment_notify_authors, channels.comment_notify_emails, channels.comment_expiration'
		);

		ee()->db->from(array('channel_titles', 'channels'));
		ee()->db->where('channel_titles.channel_id = channels.channel_id');
		ee()->db->where('channel_titles.entry_id', $_POST['entry_id']);
		ee()->db->where('channel_titles.status', 'closed');
*/
		$sql = "SELECT exp_channel_titles.title,
				exp_channel_titles.url_title,
				exp_channel_titles.entry_id,
				exp_channel_titles.channel_id,
				exp_channel_titles.author_id,
				exp_channel_titles.allow_comments,
				exp_channel_titles.entry_date,
				exp_channel_titles.comment_expiration_date,
				exp_channels.channel_title,
				exp_channels.comment_system_enabled,
				exp_channels.comment_max_chars,
				exp_channels.comment_use_captcha,
				exp_channels.comment_timelock,
				exp_channels.comment_require_membership,
				exp_channels.comment_moderate,
				exp_channels.comment_require_email,
				exp_channels.comment_notify,
				exp_channels.comment_notify_authors,
				exp_channels.comment_notify_emails,
				exp_channels.comment_expiration,
				exp_channels.channel_url,
				exp_channels.comment_url,
				exp_channels.site_id
			FROM	exp_channel_titles, exp_channels
			WHERE	exp_channel_titles.channel_id = exp_channels.channel_id
			AND	exp_channel_titles.entry_id = '".ee()->db->escape_str($_POST['entry_id'])."'";

				//  Added entry_status param, so it is possible to post to closed title
				//AND	exp_channel_titles.status != 'closed' ";

		// -------------------------------------------
		// 'insert_comment_preferences_sql' hook.
		//  - Rewrite or add to the comment preference sql query
		//  - Could be handy for comment/channel restrictions
		//
			if (ee()->extensions->active_hook('insert_comment_preferences_sql') === TRUE)
			{
				$sql = ee()->extensions->call('insert_comment_preferences_sql', $sql);
				if (ee()->extensions->end_script === TRUE) return;
			}
		//
		// -------------------------------------------

		$query = ee()->db->query($sql);

		unset($sql);


		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		/** ----------------------------------------
		/**  Are comments allowed?
		/** ----------------------------------------*/
		if ($query->row('allow_comments')  == 'n' OR $query->row('comment_system_enabled')  == 'n')
		{
			return ee()->output->show_user_error('submission', ee()->lang->line('cmt_comments_not_allowed'));
		}

		/** ----------------------------------------
		/**  Has commenting expired?
		/** ----------------------------------------*/

		$force_moderation = $query->row('comment_moderate');

		if ($this->comment_expiration_mode == 0)
		{
			if ($query->row('comment_expiration_date')  > 0)
			{
				if (ee()->localize->now > $query->row('comment_expiration_date') )
				{
					if (ee()->config->item('comment_moderation_override') == 'y')
					{
						$force_moderation = 'y';
					}
					else
					{
						return ee()->output->show_user_error('submission', ee()->lang->line('cmt_commenting_has_expired'));
					}
				}
			}
		}
		else
		{
			if ($query->row('comment_expiration') > 0)
			{
			 	$days = $query->row('entry_date') + ($query->row('comment_expiration') * 86400);

				if (ee()->localize->now > $days)
				{
					if (ee()->config->item('comment_moderation_override') == 'y')
					{
						$force_moderation = 'y';
					}
					else
					{
						return ee()->output->show_user_error('submission', ee()->lang->line('cmt_commenting_has_expired'));
					}
				}
			}
		}


		/** ----------------------------------------
		/**  Is there a comment timelock?
		/** ----------------------------------------*/
		if ($query->row('comment_timelock') != '' AND $query->row('comment_timelock') > 0)
		{
			if (ee()->session->userdata['group_id'] != 1)
			{
				$time = ee()->localize->now - $query->row('comment_timelock') ;

				ee()->db->where('comment_date >', $time);
				ee()->db->where('ip_address', ee()->input->ip_address());

				$result = ee()->db->count_all_results('comments');

				if ($result  > 0)
				{
					return ee()->output->show_user_error('submission', str_replace("%s", $query->row('comment_timelock') , ee()->lang->line('cmt_comments_timelock')));
				}
			}
		}

		/** ----------------------------------------
		/**  Do we allow duplicate data?
		/** ----------------------------------------*/
		if (ee()->config->item('deny_duplicate_data') == 'y')
		{
			if (ee()->session->userdata['group_id'] != 1)
			{
				ee()->db->where('comment', $_POST['comment']);
				$result = ee()->db->count_all_results('comments');

				if ($result > 0)
				{
					return ee()->output->show_user_error('submission', ee()->lang->line('cmt_duplicate_comment_warning'));
				}
			}
		}


		/** ----------------------------------------
		/**  Assign data
		/** ----------------------------------------*/
		$author_id				= $query->row('author_id') ;
		$entry_title			= $query->row('title') ;
		$url_title				= $query->row('url_title') ;
		$channel_title		 	= $query->row('channel_title') ;
		$channel_id			  	= $query->row('channel_id') ;
		$require_membership 	= $query->row('comment_require_membership') ;
		$comment_moderate		= (ee()->session->userdata['group_id'] == 1 OR ee()->session->userdata['exclude_from_moderation'] == 'y') ? 'n' : $force_moderation;
		$author_notify			= $query->row('comment_notify_authors') ;

		$comment_url			= $query->row('comment_url');
		$channel_url			= $query->row('channel_url');
		$entry_id				= $query->row('entry_id');
		$comment_site_id		= $query->row('site_id');


		$notify_address = ($query->row('comment_notify')  == 'y' AND $query->row('comment_notify_emails')  != '') ? $query->row('comment_notify_emails')  : '';


		/** ----------------------------------------
		/**  Start error trapping
		/** ----------------------------------------*/

		$error = array();

		if (ee()->session->userdata('member_id') != 0)
		{
			// If the user is logged in we'll reassign the POST variables with the user data

			 $_POST['name']		= (ee()->session->userdata['screen_name'] != '') ? ee()->session->userdata['screen_name'] : ee()->session->userdata['username'];
			 $_POST['email']	=  ee()->session->userdata['email'];
			 $_POST['url']		=  (is_null(ee()->session->userdata['url'])) ? '' : ee()->session->userdata['url'];
			 $_POST['location']	=  (is_null(ee()->session->userdata['location'])) ? '' : ee()->session->userdata['location'];
		}

		/** ----------------------------------------
		/**  Is membership is required to post...
		/** ----------------------------------------*/

		if ($require_membership == 'y')
		{
			// Not logged in

			if (ee()->session->userdata('member_id') == 0)
			{
				return ee()->output->show_user_error('submission', ee()->lang->line('cmt_must_be_member'));
			}

			// Membership is pending

			if (ee()->session->userdata['group_id'] == 4)
			{
				return ee()->output->show_user_error('general', ee()->lang->line('cmt_account_not_active'));
			}

		}
		else
		{
			/** ----------------------------------------
			/**  Missing name?
			/** ----------------------------------------*/

			if (trim($_POST['name']) == '')
			{
				$error[] = ee()->lang->line('cmt_missing_name');
			}

			/** -------------------------------------
			/**  Is name banned?
			/** -------------------------------------*/

			if (ee()->session->ban_check('screen_name', $_POST['name']))
			{
				$error[] = ee()->lang->line('cmt_name_not_allowed');
			}

			// Let's make sure they aren't putting in funky html to bork our screens
			$_POST['name'] = str_replace(array('<', '>'), array('&lt;', '&gt;'), $_POST['name']);

			/** ----------------------------------------
			/**  Missing or invalid email address
			/** ----------------------------------------*/

			if ($query->row('comment_require_email')  == 'y')
			{
				ee()->load->helper('email');

				if ($_POST['email'] == '')
				{
					$error[] = ee()->lang->line('cmt_missing_email');
				}
				elseif ( ! valid_email($_POST['email']))
				{
					$error[] = ee()->lang->line('cmt_invalid_email');
				}
			}
		}

		/** -------------------------------------
		/**  Is email banned?
		/** -------------------------------------*/

		if ($_POST['email'] != '')
		{
			if (ee()->session->ban_check('email', $_POST['email']))
			{
				$error[] = ee()->lang->line('cmt_banned_email');
			}
		}

		/** ----------------------------------------
		/**  Is comment too big?
		/** ----------------------------------------*/

		if ($query->row('comment_max_chars')  != '' AND $query->row('comment_max_chars')  != 0)
		{
			if (strlen($_POST['comment']) > $query->row('comment_max_chars') )
			{
				$str = str_replace("%n", strlen($_POST['comment']), ee()->lang->line('cmt_too_large'));

				$str = str_replace("%x", $query->row('comment_max_chars') , $str);

				$error[] = $str;
			}
		}

		/** ----------------------------------------
		/**  Do we have errors to display?
		/** ----------------------------------------*/

		if (count($error) > 0)
		{
			return ee()->output->show_user_error('submission', $error);
		}

		/** ----------------------------------------
		/**  Do we require CAPTCHA?
		/** ----------------------------------------*/

		if ($query->row('comment_use_captcha')  == 'y')
		{
			if (ee()->config->item('captcha_require_members') == 'y'  OR  (ee()->config->item('captcha_require_members') == 'n' AND ee()->session->userdata('member_id') == 0))
			{
				if ( ! isset($_POST['captcha']) OR $_POST['captcha'] == '')
				{
					return ee()->output->show_user_error('submission', ee()->lang->line('captcha_required'));
				}
				else
				{
					ee()->db->where('word', $_POST['captcha']);
					ee()->db->where('ip_address', ee()->input->ip_address());
					ee()->db->where('date > UNIX_TIMESTAMP()-7200', NULL, FALSE);

					$result = ee()->db->count_all_results('captcha');

					if ($result == 0)
					{
						return ee()->output->show_user_error('submission', ee()->lang->line('captcha_incorrect'));
					}

					// @TODO: AR
					ee()->db->query("DELETE FROM exp_captcha WHERE (word='".ee()->db->escape_str($_POST['captcha'])."' AND ip_address = '".ee()->input->ip_address()."') OR date < UNIX_TIMESTAMP()-7200");
				}
			}
		}

		/** ----------------------------------------
		/**  Build the data array
		/** ----------------------------------------*/

		ee()->load->helper('url');

		$notify = (ee()->input->post('notify_me')) ? 'y' : 'n';

 		$cmtr_name	= ee()->input->post('name', TRUE);
 		$cmtr_email	= ee()->input->post('email');
 		$cmtr_loc	= ee()->input->post('location', TRUE);
 		$cmtr_url	= ee()->input->post('url', TRUE);
		$cmtr_url	= prep_url($cmtr_url);

		$data = array(
			'channel_id'	=> $channel_id,
			'entry_id'		=> $_POST['entry_id'],
			'author_id'		=> ee()->session->userdata('member_id'),
			'name'			=> $cmtr_name,
			'email'			=> $cmtr_email,
			'url'			=> $cmtr_url,
			'location'		=> $cmtr_loc,
			'comment'		=> ee()->security->xss_clean($_POST['comment']),
			'comment_date'	=> ee()->localize->now,
			'ip_address'	=> ee()->input->ip_address(),
			'status'		=> ($comment_moderate == 'y') ? 'p' : 'o',
			'site_id'		=> $comment_site_id
		);

		// -------------------------------------------
		// 'insert_comment_insert_array' hook.
		//  - Modify any of the soon to be inserted values
		//
			if (ee()->extensions->active_hook('insert_comment_insert_array') === TRUE)
			{
				$data = ee()->extensions->call('insert_comment_insert_array', $data);
				if (ee()->extensions->end_script === TRUE) return;
			}
		//
		// -------------------------------------------

		$return_link = ( ! stristr($_POST['RET'],'http://') && ! stristr($_POST['RET'],'https://')) ? ee()->functions->create_url($_POST['RET']) : $_POST['RET'];

		// Secure Forms check
		if (ee()->security->secure_forms_check(ee()->input->post('XID')) == FALSE)
		{
			ee()->functions->redirect(stripslashes($return_link));
		}


		//  Insert data
		$sql = ee()->db->insert_string('exp_comments', $data);
		ee()->db->query($sql);
		$comment_id = ee()->db->insert_id();

		if ($notify == 'y')
		{
			ee()->load->library('subscription');
			ee()->subscription->init('comment', array('entry_id' => $entry_id), TRUE);

			if ($cmtr_id = ee()->session->userdata('member_id'))
			{
				ee()->subscription->subscribe($cmtr_id);
			}
			else
			{
				ee()->subscription->subscribe($cmtr_email);
			}
		}


		if ($comment_moderate == 'n')
		{
			/** ------------------------------------------------
			/**  Update comment total and "recent comment" date
			/** ------------------------------------------------*/

			ee()->db->set('recent_comment_date', ee()->localize->now);
			ee()->db->where('entry_id', $_POST['entry_id']);

			ee()->db->update('channel_titles');

			/** ----------------------------------------
			/**  Update member comment total and date
			/** ----------------------------------------*/

			if (ee()->session->userdata('member_id') != 0)
			{
				ee()->db->select('total_comments');
				ee()->db->where('member_id', ee()->session->userdata('member_id'));

				$query = ee()->db->get('members');

				ee()->db->set('total_comments', $query->row('total_comments') + 1);
				ee()->db->set('last_comment_date', ee()->localize->now);
				ee()->db->where('member_id', ee()->session->userdata('member_id'));

				ee()->db->update('members');
			}

			/** ----------------------------------------
			/**  Update comment stats
			/** ----------------------------------------*/

			ee()->stats->update_comment_stats($channel_id, ee()->localize->now);

			/** ----------------------------------------
			/**  Fetch email notification addresses
			/** ----------------------------------------*/

			ee()->load->library('subscription');
			ee()->subscription->init('comment', array('entry_id' => $entry_id), TRUE);

			// Remove the current user
			$ignore = (ee()->session->userdata('member_id') != 0) ? ee()->session->userdata('member_id') : ee()->input->post('email');

			// Grab them all
			$subscriptions = ee()->subscription->get_subscriptions($ignore);
			ee()->load->model('comment_model');
			ee()->comment_model->recount_entry_comments(array($entry_id));
			$recipients = ee()->comment_model->fetch_email_recipients($_POST['entry_id'], $subscriptions);
		}

		/** ----------------------------------------
		/**  Fetch Author Notification
		/** ----------------------------------------*/

		if ($author_notify == 'y')
		{
			ee()->db->select('email');
			ee()->db->where('member_id', $author_id);

			$result = ee()->db->get('members');

			$notify_address	.= ','.$result->row('email');
		}

		/** ----------------------------------------
		/**  Instantiate Typography class
		/** ----------------------------------------*/

		ee()->load->library('typography');
		ee()->typography->initialize(array(
			'parse_images'		=> FALSE,
			'allow_headings'	=> FALSE,
			'smileys'			=> FALSE,
			'word_censor'		=> (ee()->config->item('comment_word_censoring') == 'y') ? TRUE : FALSE)
		);

		$comment = ee()->security->xss_clean($_POST['comment']);
		$comment = ee()->typography->parse_type(
			$comment,
			array(
				'text_format'	=> 'none',
				'html_format'	=> 'none',
				'auto_links'	=> 'n',
				'allow_img_url' => 'n'
			)
		);

		$path = ($comment_url == '') ? $channel_url : $comment_url;

		$comment_url_title_auto_path = reduce_double_slashes($path.'/'.$url_title);

		/** ----------------------------
		/**  Send admin notification
		/** ----------------------------*/

		if ($notify_address != '')
		{
			$cp_url = ee()->config->item('cp_url').'?S=0&D=cp&C=addons_modules&M=show_module_cp&module=comment';

			$swap = array(
				'name'				=> $cmtr_name,
				'name_of_commenter'	=> $cmtr_name,
				'email'				=> $cmtr_email,
				'url'				=> $cmtr_url,
				'location'			=> $cmtr_loc,
				'channel_name'		=> $channel_title,
				'entry_title'		=> $entry_title,
				'comment_id'		=> $comment_id,
				'comment'			=> $comment,
				'comment_url'		=> reduce_double_slashes(ee()->input->remove_session_id(ee()->functions->fetch_site_index().'/'.$_POST['URI'])),
				'delete_link'		=> $cp_url.'&method=delete_comment_confirm&comment_id='.$comment_id,
				'approve_link'		=> $cp_url.'&method=change_comment_status&comment_id='.$comment_id.'&status=o',
				'close_link'		=> $cp_url.'&method=change_comment_status&comment_id='.$comment_id.'&status=c',
				'channel_id'		=> $channel_id,
				'entry_id'			=> $entry_id,
				'url_title'			=> $url_title,
				'comment_url_title_auto_path' => $comment_url_title_auto_path
			);

			$template = ee()->functions->fetch_email_template('admin_notify_comment');

			$email_tit = ee()->functions->var_swap($template['title'], $swap);
			$email_msg = ee()->functions->var_swap($template['data'], $swap);

			// We don't want to send an admin notification if the person
			// leaving the comment is an admin in the notification list
			// For added security, we only trust the post email if the
			// commenter is logged in.

			if (ee()->session->userdata('member_id') != 0 && $_POST['email'] != '')
			{
				if (strpos($notify_address, $_POST['email']) !== FALSE)
				{
					$notify_address = str_replace($_POST['email'], '', $notify_address);
				}
			}

			// Remove multiple commas
			$notify_address = reduce_multiples($notify_address, ',', TRUE);

			if ($notify_address != '')
			{
				/** ----------------------------
				/**  Send email
				/** ----------------------------*/

				ee()->load->library('email');

				$replyto = ($data['email'] == '') ? ee()->config->item('webmaster_email') : $data['email'];

				$sent = array();

				// Load the text helper
				ee()->load->helper('text');

				foreach (explode(',', $notify_address) as $addy)
				{
					if (in_array($addy, $sent))
					{
						continue;
					}

					ee()->email->EE_initialize();
					ee()->email->wordwrap = false;
					ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
					ee()->email->to($addy);
					ee()->email->reply_to($replyto);
					ee()->email->subject($email_tit);
					ee()->email->message(entities_to_ascii($email_msg));
					ee()->email->send();

					$sent[] = $addy;
				}
			}
		}


		/** ----------------------------------------
		/**  Send user notifications
		/** ----------------------------------------*/

		if ($comment_moderate == 'n')
		{
			$email_msg = '';

			if (count($recipients) > 0)
			{
				$action_id  = ee()->functions->fetch_action_id('Comment_mcp', 'delete_comment_notification');

				$swap = array(
					'name_of_commenter'	=> $cmtr_name,
					'channel_name'		=> $channel_title,
					'entry_title'		=> $entry_title,
					'site_name'			=> stripslashes(ee()->config->item('site_name')),
					'site_url'			=> ee()->config->item('site_url'),
					'comment_url'		=> reduce_double_slashes(ee()->input->remove_session_id(ee()->functions->fetch_site_index().'/'.$_POST['URI'])),
					'comment_id'		=> $comment_id,
					'comment'			=> $comment,
					'channel_id'		=> $channel_id,
					'entry_id'			=> $entry_id,
					'url_title'			=> $url_title,
					'comment_url_title_auto_path' => $comment_url_title_auto_path
				);


				$template = ee()->functions->fetch_email_template('comment_notification');
				$email_tit = ee()->functions->var_swap($template['title'], $swap);
				$email_msg = ee()->functions->var_swap($template['data'], $swap);

				/** ----------------------------
				/**  Send email
				/** ----------------------------*/

				ee()->load->library('email');
				ee()->email->wordwrap = true;

				$cur_email = ($_POST['email'] == '') ? FALSE : $_POST['email'];

				if ( ! isset($sent)) $sent = array();

				// Load the text helper
				ee()->load->helper('text');

				foreach ($recipients as $val)
				{
					// We don't notify the person currently commenting.  That would be silly.

					if ( ! in_array($val['0'], $sent))
					{
						$title	 = $email_tit;
						$message = $email_msg;

						$sub	= $subscriptions[$val['1']];
						$sub_qs	= 'id='.$sub['subscription_id'].'&hash='.$sub['hash'];

						// Deprecate the {name} variable at some point
						$title	 = str_replace('{name}', $val['2'], $title);
						$message = str_replace('{name}', $val['2'], $message);

						$title	 = str_replace('{name_of_recipient}', $val['2'], $title);
						$message = str_replace('{name_of_recipient}', $val['2'], $message);

						$title	 = str_replace('{notification_removal_url}', ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&'.$sub_qs, $title);
						$message = str_replace('{notification_removal_url}', ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&'.$sub_qs, $message);

						ee()->email->EE_initialize();
						ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
						ee()->email->to($val['0']);
						ee()->email->subject($title);
						ee()->email->message(entities_to_ascii($message));
						ee()->email->send();

						$sent[] = $val['0'];
					}
				}
			}

			/** ----------------------------------------
			/**  Clear cache files
			/** ----------------------------------------*/

			ee()->functions->clear_caching('all', ee()->functions->fetch_site_index().$_POST['URI']);

			// clear out the entry_id version if the url_title is in the URI, and vice versa
			if (preg_match("#\/".preg_quote($url_title)."\/#", $_POST['URI'], $matches))
			{
				ee()->functions->clear_caching('all', ee()->functions->fetch_site_index().preg_replace("#".preg_quote($matches['0'])."#", "/{$data['entry_id']}/", $_POST['URI']));
			}
			else
			{
				ee()->functions->clear_caching('all', ee()->functions->fetch_site_index().preg_replace("#{$data['entry_id']}#", $url_title, $_POST['URI']));
			}
		}

		/** ----------------------------------------
		/**  Set cookies
		/** ----------------------------------------*/

		if ($notify == 'y')
		{
			ee()->functions->set_cookie('notify_me', 'yes', 60*60*24*365);
		}
		else
		{
			ee()->functions->set_cookie('notify_me', 'no', 60*60*24*365);
		}

		if (ee()->input->post('save_info'))
		{
			ee()->functions->set_cookie('save_info',	'yes',				60*60*24*365);
			ee()->functions->set_cookie('my_name',		$_POST['name'],		60*60*24*365);
			ee()->functions->set_cookie('my_email',	$_POST['email'],	60*60*24*365);
			ee()->functions->set_cookie('my_url',		$_POST['url'],		60*60*24*365);
			ee()->functions->set_cookie('my_location',	$_POST['location'],	60*60*24*365);
		}
		else
		{
			ee()->functions->set_cookie('save_info',	'no', 60*60*24*365);
			ee()->functions->set_cookie('my_name',		'');
			ee()->functions->set_cookie('my_email',	'');
			ee()->functions->set_cookie('my_url',		'');
			ee()->functions->set_cookie('my_location',	'');
		}

		// -------------------------------------------
		// 'insert_comment_end' hook.
		//  - More emails, more processing, different redirect
		//  - $comment_id added in 1.6.1
		//
			ee()->extensions->call('insert_comment_end', $data, $comment_moderate, $comment_id);
			if (ee()->extensions->end_script === TRUE) return;
		//
		// -------------------------------------------

		/** -------------------------------------------
		/**  Bounce user back to the comment page
		/** -------------------------------------------*/

		if ($comment_moderate == 'y')
		{
			$data = array(
				'title' 	=> ee()->lang->line('cmt_comment_accepted'),
				'heading'	=> ee()->lang->line('thank_you'),
				'content'	=> ee()->lang->line('cmt_will_be_reviewed'),
				'redirect'	=> $return_link,
				'link'		=> array($return_link, ee()->lang->line('cmt_return_to_comments')),
				'rate'		=> 3
			);

			ee()->output->show_message($data);
		}
		else
		{
			ee()->functions->redirect($return_link);
		}
	}


	// --------------------------------------------------------------------

	/**
	 * Comment subscription tag
	 *
	 *
	 * @access	public
	 * @return	string
	 */
	function notification_links()
	{
		// Membership is required
		if (ee()->session->userdata('member_id') == 0)
		{
			return;
		}

		$entry_id = $this->_divine_entry_id();

		// entry_id is required
		if ( ! $entry_id)
		{
			return;
		}

		ee()->load->library('subscription');
		ee()->subscription->init('comment', array('entry_id' => $entry_id), TRUE);
		$subscribed = ee()->subscription->is_subscribed(FALSE);


		$action_id  = ee()->functions->fetch_action_id('Comment', 'comment_subscribe');

		// Bleh- really need a conditional for if they are subscribed

		$sub_link = ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&entry_id='.$entry_id.'&ret='.ee()->uri->uri_string();
		$unsub_link = ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&entry_id='.$entry_id.'&type=unsubscribe'.'&ret='. ee()->uri->uri_string();

		$data[] = array('subscribe_link' => $sub_link, 'unsubscribe_link' => $unsub_link, 'subscribed' => $subscribed);

		$tagdata = ee()->TMPL->tagdata;
		return ee()->TMPL->parse_variables($tagdata, $data);
	}

	// --------------------------------------------------------------------

	/**
	 * List of subscribers to an entry
	 *
	 *
	 * @access	public
	 * @return	string
	 */
	public function subscriber_list()
	{
		$entry_id = $this->_divine_entry_id();

		// entry is required, and this is not the same as "no results for a valid entry"
		// so return nada
		if ( ! $entry_id)
		{
			return;
		}

		$anonymous = TRUE;
		if (ee()->TMPL->fetch_param('exclude_guests') == 'yes')
		{
			$anonymous = FALSE;
		}

		ee()->load->library('subscription');
		ee()->subscription->init('comment', array('entry_id' => $entry_id), $anonymous);
		$subscribed = ee()->subscription->get_subscriptions(FALSE, TRUE);

		if (empty($subscribed))
		{
			return ee()->TMPL->no_results();
		}

		// non-member comments will expose email addresses, so make sure the visitor should
		// be able to see this data before including it
		$expose_emails = (ee()->session->userdata('group_id') == 1) ? TRUE : FALSE;

		$vars = array();
		$total_results = count($subscribed);
		$count = 0;
		$guest_total = 0;
		$member_total = 0;

		foreach ($subscribed as $subscription_id => $subscription)
		{
			$vars[] = array(
				'subscriber_member_id' => $subscription['member_id'],
				'subscriber_screen_name' => $subscription['screen_name'],
				'subscriber_email' => (($expose_emails && $anonymous) ? $subscription['email'] : ''),
				'subscriber_is_member' => (($subscription['member_id'] == 0) ? FALSE : TRUE),
				'subscriber_count' => ++$count,
				'subscriber_total_results' => $total_results
				);

			if ($subscription['member_id'] == 0)
			{
				$guest_total++;
			}
			else
			{
				$member_total++;
			}
		}

		// loop through again to add the final guest/subscribed tallies
		foreach ($vars as $key => $val)
		{
			$vars[$key]['subscriber_guest_total'] = $guest_total;
			$vars[$key]['subscriber_member_total'] = $member_total;
		}

		return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Comment subscription w/out commenting
	 *
	 *
	 * @access	public
	 * @return	string
	 */
	function comment_subscribe()
	{
		// Membership is required
		if (ee()->session->userdata('member_id') == 0)
		{
			return;
		}

		$id		= ee()->input->get('entry_id');
		$type	= (ee()->input->get('type')) ? 'unsubscribe' : 'subscribe';
		$ret	= ee()->input->get('ret');

		if ( ! $id)
		{
			return;
		}

		ee()->lang->loadfile('comment');

		// Does entry exist?

		ee()->db->select('title');
		$query = ee()->db->get_where('channel_titles', array('entry_id' => $id));

		if ($query->num_rows() != 1)
		{
 			return ee()->output->show_user_error('submission', 'invalid_subscription');
		}

  		$row = $query->row();
  		$entry_title = $row->title;

		// Are they currently subscribed

		ee()->load->library('subscription');
		ee()->subscription->init('comment', array('entry_id' => $id), TRUE);
		$subscribed = ee()->subscription->is_subscribed(FALSE);

		if ($type == 'subscribe' && $subscribed == TRUE)
		{
			return ee()->output->show_user_error('submission', ee()->lang->line('already_subscribed'));
		}

		if ($type == 'unsubscribe' && $subscribed == FALSE)
		{
			return ee()->output->show_user_error('submission', ee()->lang->line('not_currently_subscribed'));
		}

		// They check out- let them through
		ee()->subscription->$type();

		// Show success message

		ee()->lang->loadfile('comment');

		$title = ($type == 'unsubscribe') ? 'cmt_unsubscribe' : 'cmt_subscribe';
		$content = ($type == 'unsubscribe') ? 'you_have_been_unsubscribed' : 'you_have_been_subscribed';

		$return_link = ee()->functions->create_url($ret);

		$data = array(
			'title' 	=> ee()->lang->line($title),
			'heading'	=> ee()->lang->line('thank_you'),
			'content'	=> ee()->lang->line($content).' '.$entry_title,
			'redirect'	=> $return_link,
			'link'		=> array($return_link, ee()->lang->line('cmt_return_to_comments')),
			'rate'		=> 3
		);

		ee()->output->show_message($data);
	}

	// --------------------------------------------------------------------

	/**
	 * Frontend comment editing
	 *
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function edit_comment($ajax_request = TRUE)
	{
		@header("Content-type: text/html; charset=UTF-8");

		$unauthorized = ee()->lang->line('not_authorized');

		if (ee()->input->get_post('comment_id') === FALSE OR ((ee()->input->get_post('comment') === FALSE OR ee()->input->get_post('comment') == '') && ee()->input->get_post('status') != 'close'))
		{
			ee()->output->send_ajax_response(array('error' => $unauthorized));
		}

		// Not logged in member- eject
		if (ee()->session->userdata['member_id'] == '0')
		{
			ee()->output->send_ajax_response(array('error' => $unauthorized));
		}

		$xid = ee()->input->get_post('XID');


		// Secure Forms check - do it early due to amount of further data manipulation before insert
		if (ee()->security->secure_forms_check($xid) == FALSE)
		{
		 	ee()->output->send_ajax_response(array('error' => $unauthorized));
		}

		$edited_status = (ee()->input->get_post('status') != 'close') ? FALSE : 'c';
		$edited_comment = ee()->input->get_post('comment');
		$can_edit = FALSE;
		$can_moderate = FALSE;

		ee()->db->from('comments');
		ee()->db->from('channels');
		ee()->db->from('channel_titles');
		ee()->db->select('comments.author_id, comments.comment_date, channel_titles.author_id AS entry_author_id, channel_titles.entry_id, channels.channel_id, channels.comment_text_formatting, channels.comment_html_formatting, channels.comment_allow_img_urls, channels.comment_auto_link_urls');
		ee()->db->where('comment_id', ee()->input->get_post('comment_id'));
		ee()->db->where('comments.channel_id = '.ee()->db->dbprefix('channels').'.channel_id');
		ee()->db->where('comments.entry_id = '.ee()->db->dbprefix('channel_titles').'.entry_id');
		$query = ee()->db->get();

		if ($query->num_rows() > 0)
		{
			// User is logged in and in a member group that can edit this comment.
			if (ee()->session->userdata['group_id'] == 1
				OR ee()->session->userdata['can_edit_all_comments'] == 'y'
				OR (ee()->session->userdata['can_edit_own_comments'] == 'y'
					&& $query->row('entry_author_id') == ee()->session->userdata['member_id']))
			{
				$can_edit = TRUE;
				$can_moderate = TRUE;
			}
			// User is logged in and can still edit this comment.
			elseif (ee()->session->userdata['member_id'] != '0'
				&& $query->row('author_id') == ee()->session->userdata['member_id']
				&& $query->row('comment_date') > $this->_comment_edit_time_limit())
			{
				$can_edit = true;
			}

			$data = array();
			$author_id = $query->row('author_id');
			$channel_id = $query->row('channel_id');
			$entry_id = $query->row('entry_id');

			if ($edited_status != FALSE & $can_moderate != FALSE)
			{
				$data['status'] = 'c';
			}

			if ($edited_comment != FALSE & $can_edit != FALSE)
			{
				$data['comment'] = $edited_comment;
			}

			if (count($data) > 0)
			{
				$data['edit_date'] = ee()->localize->now;

				ee()->db->where('comment_id', ee()->input->get_post('comment_id'));
				ee()->db->update('comments', $data);

				if ($edited_status != FALSE & $can_moderate != FALSE)
				{
					// We closed an entry, update our stats
					$this->_update_comment_stats($entry_id, $channel_id, $author_id);

					// create new security hash and send it back with updated comment.

					$new_hash = $this->_new_hash();
					ee()->output->send_ajax_response(array('moderated' => ee()->lang->line('closed'), 'XID' => $new_hash));
				}

				ee()->load->library('typography');

				$f_comment = ee()->typography->parse_type(
					stripslashes(ee()->input->get_post('comment')),
					array(
						'text_format'   => $query->row('comment_text_formatting'),
						'html_format'   => $query->row('comment_html_formatting'),
						'auto_links'    => $query->row('comment_auto_link_urls'),
						'allow_img_url' => $query->row('comment_allow_img_urls')
					)
				);

				// create new security hash and send it back with updated comment.

				$new_hash = $this->_new_hash();

				ee()->output->send_ajax_response(array('comment' => $f_comment, 'XID' => $new_hash));
			}
		}

		ee()->output->send_ajax_response(array('error' => $unauthorized));
	}

	// --------------------------------------------------------------------

	/**
	 * Edit Comment Script
	 *
	 * Outputs a script tag with an ACT request to the edit comment JavaScript
	 *
	 * @access	public
	 * @return	type
	 */
	function edit_comment_script()
	{
		$src = ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT=comment_editor';
		return $this->return_data = '<script type="text/javascript" charset="utf-8" src="'.$src.'"></script>';
	}

	// --------------------------------------------------------------------

	/**
	 * Comment Editor
	 *
	 * Outputs the JavaScript to edit comments on the front end, with JS headers.
	 * Called via an action request by the exp:comment:edit_comment_script tag
	 *
	 * @access	public
	 * @return	string
	 */
	function comment_editor()
	{
		$ajax_url = $this->ajax_edit_url();

$script = <<<CMT_EDIT_SCR
$.fn.CommentEditor = function(options) {

	var OPT;

	OPT = $.extend({
		url: "{$ajax_url}",
		comment_body: '.comment_body',
		showEditor: '.edit_link',
		hideEditor: '.cancel_edit',
		saveComment: '.submit_edit',
		closeComment: '.mod_link'
	}, options);

	var view_elements = [OPT.comment_body, OPT.showEditor, OPT.closeComment].join(','),
		edit_elements = '.editCommentBox',
		hash = '{XID_HASH}';

	return this.each(function() {
		var id = this.id.replace('comment_', ''),
		parent = $(this);

		parent.find(OPT.showEditor).click(function() { showEditor(id); return false; });
		parent.find(OPT.hideEditor).click(function() { hideEditor(id); return false; });
		parent.find(OPT.saveComment).click(function() { saveComment(id); return false; });
		parent.find(OPT.closeComment).click(function() { closeComment(id); return false; });
	});

	function showEditor(id) {
		$("#comment_"+id)
			.find(view_elements).hide().end()
			.find(edit_elements).show().end();
	}

	function hideEditor(id) {
		$("#comment_"+id)
			.find(view_elements).show().end()
			.find(edit_elements).hide();
	}

	function closeComment(id) {
		var data = {status: "close", comment_id: id, XID: hash};

		$.post(OPT.url, data, function (res) {
			if (res.error) {
				return $.error('Could not moderate comment.');
			}

			hash = res.XID;
			$('input[name=XID]').val(hash);
			$('#comment_' + id).hide();
	   });
	}

	function saveComment(id) {
		var content = $("#comment_"+id).find('.editCommentBox'+' textarea').val(),
			data = {comment: content, comment_id: id, XID: hash};

		$.post(OPT.url, data, function (res) {
			if (res.error) {
				hideEditor(id);
				return $.error('Could not save comment.');
			}

			hash = res.XID;
			$('input[name=XID]').val(hash);
			$("#comment_"+id).find('.comment_body').html(res.comment);
			hideEditor(id);
   		});
	}
};


$(function() { $('.comment').CommentEditor(); });
CMT_EDIT_SCR;

		$script = ee()->functions->add_form_security_hash($script);
		$script = ee()->functions->insert_action_ids($script);

		ee()->output->enable_profiler(FALSE);
		ee()->output->set_header("Content-Type: text/javascript");

		if (ee()->config->item('send_headers') == 'y')
		{
			ee()->output->send_cache_headers(strtotime(APP_BUILD));
			ee()->output->set_header('Content-Length: '.strlen($script));
		}

		exit($script);
	}

	// --------------------------------------------------------------------

	/**
	 * New Hash
	 *
	 * Generates a new secure forms CSRF hash for the current user
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function _new_hash()
	{
		if (ee()->config->item('secure_forms') != 'y')
		{
			return FALSE;
		}

		$db_reset = FALSE;

		if (ee()->db->cache_on == TRUE)
		{
			ee()->db->cache_off();
			$db_reset = TRUE;
		}

		$hash = ee()->security->generate_xid();

		// Re-enable DB caching
		if ($db_reset == TRUE)
		{
			ee()->db->cache_on();
		}

		return $hash;
	}

	// --------------------------------------------------------------------

	/**
	 * AJAX Edit URL
	 *
	 * @access	public
	 * @return	string
	 */
	function ajax_edit_url()
	{
		$url = ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.ee()->functions->fetch_action_id('Comment', 'edit_comment');

		return $url;
	}

	// --------------------------------------------------------------------

	/**
	 * Discover the entry ID for the current entry
	 *
	 *
	 * @access	private
	 * @return	int
	 */
	private function _divine_entry_id()
	{
		$entry_id = FALSE;
		$qstring = ee()->uri->query_string;
		$qstring_hash = md5($qstring);

		if (isset(ee()->session->cache['comment']['entry_id'][$qstring_hash]))
		{
			return ee()->session->cache['comment']['entry_id'][$qstring_hash];
		}

		if (ee()->TMPL->fetch_param('entry_id'))
		{
			return ee()->TMPL->fetch_param('entry_id');
		}
		elseif (ee()->TMPL->fetch_param('url_title'))
		{
			$entry_seg = ee()->TMPL->fetch_param('url_title');
		}
		else
		{
			if (preg_match("#(^|/)P(\d+)(/|$)#", $qstring, $match))
			{
				$qstring = trim(ee()->functions->remove_double_slashes(str_replace($match['0'], '/', $qstring)), '/');
			}

			// Figure out the right entry ID
			// If there is a slash in the entry ID we'll kill everything after it.
			$entry_seg = trim($qstring);
			$entry_seg= preg_replace("#/.+#", "", $entry_seg);
		}

		if (is_numeric($entry_seg))
		{
			$entry_id = $entry_seg;
		}
		else
		{
			ee()->db->select('entry_id');
			$query = ee()->db->get_where('channel_titles', array('url_title' => $entry_seg));

			if ($query->num_rows() == 1)
			{
   				$row = $query->row();
  				$entry_id = $row->entry_id;
			}
		}

		return ee()->session->cache['comment']['entry_id'][$qstring_hash] = $entry_id;
	}

	// --------------------------------------------------------------------

	/**
	 * Update Entry and Channel Stats
	 *
	 * @return	void
	 */
	private function _update_comment_stats($entry_id, $channel_id, $author_id)
	{
		ee()->stats->update_channel_title_comment_stats(array($entry_id));
		ee()->stats->update_comment_stats($channel_id, '', FALSE);
		ee()->stats->update_comment_stats();
		ee()->stats->update_authors_comment_stats(array($author_id));

		return;
	}

}
// END CLASS

/* End of file mod.comment.php */
/* Location: ./system/expressionengine/modules/comment/mod.comment.php */
