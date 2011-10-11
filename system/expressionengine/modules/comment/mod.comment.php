<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Comment Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
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
				$_POST[$val] = $this->EE->functions->encode_ee_tags($_POST[$val], TRUE);

				if ($val == 'comment')
				{
					$_POST[$val] = $this->EE->security->xss_clean($_POST[$val]);
				}
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Comment Entries
	 *
	 * @access	public
	 * @return	string
	 */
	function entries()
	{
		$return 		= '';
		$current_page	= '';
		$qstring		= $this->EE->uri->query_string;
		$uristr			= $this->EE->uri->uri_string;
		$switch 		= array();
		$search_link	= '';

		// Pagination variables

		$paginate			= FALSE;
		$paginate_data		= '';
		$pagination_links	= '';
		$pagination_array	= '';
		$page_next			= '';
		$page_previous		= '';
		$current_page		= 0;
		$t_current_page		= '';
		$total_pages		= 1;
		
		if ($this->EE->TMPL->fetch_param('dynamic') == 'no')
		{
			$dynamic = FALSE;
		}
		else
		{
			$dynamic = TRUE;
		}
		
		$force_entry = FALSE;
		
		if ($this->EE->TMPL->fetch_param('author_id') !== FALSE OR $this->EE->TMPL->fetch_param('entry_id') !== FALSE OR $this->EE->TMPL->fetch_param('url_title') !== FALSE OR $this->EE->TMPL->fetch_param('comment_id') !== FALSE)
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
		if ($this->EE->TMPL->fetch_param('dynamic_parameters') !== FALSE AND isset($_POST) AND count($_POST) > 0)
		{
			foreach (explode('|', $this->EE->TMPL->fetch_param('dynamic_parameters')) as $var)
			{
				if (isset($_POST[$var]) AND in_array($var, array('channel', 'limit', 'sort', 'orderby')))
				{
					$this->EE->TMPL->tagparams[$var] = $_POST[$var];
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
				$current_page = $match['2'];
				$uristr  = trim($this->EE->functions->remove_double_slashes(str_replace($match['0'], '/', $uristr)), '/');
			}
		}
		else
		{
			if (preg_match("#(^|/)P(\d+)(/|$)#", $qstring, $match))
			{
				$current_page = $match['2'];
				$uristr  = $this->EE->functions->remove_double_slashes(str_replace($match['0'], '/', $uristr));
				$qstring = trim($this->EE->functions->remove_double_slashes(str_replace($match['0'], '/', $qstring)), '/');
			}
		}
		
		// Fetch channel_ids if appropriate
		$channel_ids = array();
		
		if ($channel = $this->EE->TMPL->fetch_param('channel') OR $this->EE->TMPL->fetch_param('site'))
		{
			$this->EE->db->select('channel_id');
			$this->EE->db->where_in('site_id', $this->EE->TMPL->site_ids);

			if ($channel !== FALSE)
			{
				$this->EE->functions->ar_andor_string($channel, 'channel_name');
			}

			$channels = $this->EE->db->get('channels');
				
			if ($channels->num_rows() == 0)
			{
				if ( ! $dynamic)
				{
					return $this->EE->TMPL->no_results();
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

		$comment_id_param = FALSE;

		// Fetch entry ids- we'll use them to make sure comments are to open, etc. entries
		
		if  ($dynamic == TRUE OR $force_entry == TRUE)
		{
			if ($force_entry == TRUE)
			{
				// Check if an entry_id, url_title or comment_id was specified
				if ($entry_id = $this->EE->TMPL->fetch_param('entry_id'))
				{
					$this->EE->functions->ar_andor_string($entry_id, 'entry_id');
				}
				elseif ($url_title = $this->EE->TMPL->fetch_param('url_title'))
				{
					$this->EE->functions->ar_andor_string($url_title, 'url_title');
				}
				elseif ($comment_id_param = $this->EE->TMPL->fetch_param('comment_id'))
				{
					$force_entry_ids = $this->fetch_comment_ids_param($comment_id_param);
					
					if (count($force_entry_ids) == 0)
					{
						// No entry ids for the comment ids?  How'd they manage that
						if ( ! $dynamic)
						{
							return $this->EE->TMPL->no_results();
						}

						return false;
					}
					
					$this->EE->db->where_in('entry_id', $force_entry_ids);
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
					$this->EE->db->where('url_title', $entry_id);
				}
				else
				{
					$this->EE->db->where('entry_id', $entry_id);
				}
			}

			//  Do we have a valid entry ID number?
			$timestamp = ($this->EE->TMPL->cache_timestamp != '') ? $this->EE->TMPL->cache_timestamp : $this->EE->localize->now;

			$this->EE->db->select('entry_id, channel_id');
			//$this->EE->db->where('channel_titles.channel_id = '.$this->EE->db->dbprefix('channels').'.channel_id');
			$this->EE->db->where_in('channel_titles.site_id', $this->EE->TMPL->site_ids);

			if ($this->EE->TMPL->fetch_param('show_expired') !== 'yes')
			{
				$date_where = "(".$this->EE->db->protect_identifiers('expiration_date')." = 0 OR "
				.$this->EE->db->protect_identifiers('expiration_date')." > {$timestamp})";
				$this->EE->db->where($date_where);
			}
			
			if ($e_status = $this->EE->TMPL->fetch_param('entry_status'))
			{
				$e_status = str_replace('Open',	'open',	$e_status);
				$e_status = str_replace('Closed', 'closed', $e_status);

				// If they don't specify closed, it defaults to it
				if ( ! in_array('closed', explode('|', $e_status)))
				{
					$this->EE->db->where('status !=', 'closed');
				}

				$this->EE->functions->ar_andor_string($e_status, 'status');
			}
			else
			{
				$this->EE->db->where('status !=', 'closed');
			}
			
			//  Limit to/exclude specific channels
			if (count($channel_ids) == 1)
			{
				$this->EE->db->where('channel_titles.channel_id', $channel_ids['0']);
			}
			elseif (count($channel_ids) > 1)
			{
				$this->EE->db->where_in('channel_titles.channel_id', $channel_ids);
			}

			$this->EE->db->from('channel_titles');

			$query = $this->EE->db->get();

			// Bad ID?  See ya!
			if ($query->num_rows() == 0)
			{
				if ( ! $dynamic)
				{
					return $this->EE->TMPL->no_results();
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
			$limit = $this->EE->TMPL->fetch_param('limit', 100);
			$sort = $this->EE->TMPL->fetch_param('sort', 'desc');
		}
		else
		{
			$limit = $this->EE->TMPL->fetch_param('limit', $this->limit);
			$sort = $this->EE->TMPL->fetch_param('sort', 'asc');
		}

		$allowed_sorts = array('date', 'email', 'location', 'name', 'url');

		// We can skip the count in non-dynamic if we already know this
		if (preg_match("/".LD."paginate(.*?)".RD."(.+?)".LD.'\/'."paginate".RD."/s", 
			$this->EE->TMPL->tagdata, $match))
		{
			$paginate = TRUE;
			$paginate_data	= $match[2];
		}

		/** ----------------------------------------
		/**  Fetch comment ID numbers
		/** ----------------------------------------*/

		$temp = array();
		$i = 0;

		// Left this here for backward compatibility
		// We need to deprecate the "order_by" parameter

		if ($this->EE->TMPL->fetch_param('orderby') != '')
		{
			$order_by = $this->EE->TMPL->fetch_param('orderby');
		}
		else
		{
			$order_by = $this->EE->TMPL->fetch_param('order_by');
		}

		$random = ($order_by == 'random') ? TRUE : FALSE;
		$order_by  = ($order_by == 'date' OR ! in_array($order_by, $allowed_sorts))  ? 'comment_date' : $order_by;

		// We cache the query in case we need to do a count for dynamic off pagination
		$this->EE->db->start_cache();

		$this->EE->db->select('comment_date, comment_id');
		$this->EE->db->from('comments c');
		
		if ($status = $this->EE->TMPL->fetch_param('status'))
		{
			$status = strtolower($status);
			$status = str_replace('open',	'o', $status);
			$status = str_replace('closed', 'c', $status);
			$status = str_replace('pending', 'p', $status);

			$this->EE->functions->ar_andor_string($status, 'c.status');

			// No custom status for comments, so we can be leaner in check for 'c'
			if (stristr($status, "'c'") === FALSE)
			{
				$this->EE->db->where('c.status !=', 'c');
			}
		}
		else
		{
			$this->EE->db->where('c.status', 'o');
		}

		if ($author_id = $this->EE->TMPL->fetch_param('author_id'))
		{
			$this->EE->db->where('c.author_id', $author_id);
		}
		
		// Note if it's not dynamic and the entry isn't forced?  We don't check on the entry criteria,
		// so this point, dynamic and forced entry will have 'valid' entry ids, dynamic off may not

		if ( ! $dynamic && ! $force_entry)
		{			
			//  Limit to/exclude specific channels
			if (count($channel_ids) == 1)
			{
				$this->EE->db->where('c.channel_id', $channel_ids['0'], FALSE);
			}
			elseif (count($channel_ids) > 1)
			{
				$this->EE->db->where_in('c.channel_id', $channel_ids);
			}
			
			$this->EE->db->join('channel_titles ct', 'ct.entry_id = c.entry_id');

			if ($e_status = $this->EE->TMPL->fetch_param('entry_status'))
			{
				$e_status = str_replace('Open',	'open',	$e_status);
				$e_status = str_replace('Closed', 'closed', $e_status);

				// If they don't specify closed, it defaults to it
				if ( ! in_array('closed', explode('|', $e_status)))
				{
					$this->EE->db->where('ct.status !=', 'closed');
				}

				$this->EE->functions->ar_andor_string($e_status, 'ct.status');
			}
			else
			{
				$this->EE->db->where('ct.status !=', 'closed');
			}
			
			// seems redundant given channels
			$this->EE->db->where_in('c.site_id', $this->EE->TMPL->site_ids, FALSE);

			if ($this->EE->TMPL->fetch_param('show_expired') !== 'yes')
			{
				$timestamp = ($this->EE->TMPL->cache_timestamp != '') ? $this->EE->TMPL->cache_timestamp : $this->EE->localize->now;

				$date_where = "(".$this->EE->db->protect_identifiers('ct.expiration_date')." = 0 OR "
				.$this->EE->db->protect_identifiers('ct.expiration_date')." > {$timestamp})";
				$this->EE->db->where($date_where);
			}

			if ($paginate === TRUE)
			{
				// When we are only showing comments and it is 
				// not based on an entry id or url title
				// in the URL, we can make the query much 
				// more efficient and save some work.
				$total_rows = $this->EE->db->count_all_results();
			}
			
			$this_sort = ($random) ? 'random' : strtolower($sort);
			$this_page = ($current_page == '' OR ($limit > 1 AND $current_page == 1)) ? 0 : $current_page;

			$this->EE->db->order_by($order_by, $this_sort);
			$this->EE->db->limit($limit, $this_page);
		}
		else
		{
			// Force entry may result in multiple entry ids
			if (isset($entry_ids) && count($entry_ids) > 0)
			{
				$this->EE->db->where_in('c.entry_id', $entry_ids);
			}
			else
			{
				$this->EE->db->where('c.entry_id', $entry_id);
			}
			
			if ($comment_id_param)
			{
				$this->EE->functions->ar_andor_string($comment_id_param, 'comment_id');
			}
			
			$this_sort = ($random) ? 'random' : strtolower($sort);

			$this->EE->db->order_by($order_by, $this_sort);
		}

		$this->EE->db->stop_cache();
		$query = $this->EE->db->get();
		// var_dump($this->EE->db->last_query()); exit;
		$this->EE->db->flush_cache();
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
			return $this->EE->TMPL->no_results();
		}

		//  Pagination bits if required
		
		// If we had entry ids or no pagination?  We don't have the count yet
		if ($dynamic OR $force_entry OR $paginate == FALSE)
		{
			$total_rows = count($result_ids);
		}

		if ($paginate == TRUE)
		{
			$anchor = '';

			if ($match['1'] != '')
			{
				if (preg_match("/anchor.*?=[\"|\'](.+?)[\"|\']/", $match['1'], $amatch))
				{
					$anchor = '#'.$amatch['1'];
				}
			}

			$this->EE->TMPL->tagdata = preg_replace("/".LD."paginate.*?".RD.".+?".LD.'\/'."paginate".RD."/s", "", $this->EE->TMPL->tagdata);

			$current_page = ($current_page == '' OR ($limit > 1 AND $current_page == 1)) ? 0 : $current_page;

			if ($current_page > $total_rows)
			{
				$current_page = 0;
			}

			$t_current_page = floor(($current_page / $limit) + 1);
			$total_pages	= intval(floor($total_rows / $limit));

			if ($total_rows % $limit)
				$total_pages++;

			if ($total_rows > $limit)
			{
				$this->EE->load->library('pagination');

				$deft_tmpl = '';

				if ($uristr == '')
				{
					if ($this->EE->config->item('template_group') == '')
					{
						$this->EE->db->select('group_name');
						$query = $this->EE->db->get_where('template_groups', array('is_site_default' => 'y'));
						
						$deft_tmpl = $query->row('group_name') .'/index';
					}
					else
					{
						$deft_tmpl  = $this->EE->config->item('template_group').'/';
						$deft_tmpl .= ($this->EE->config->item('template') == '') ? 'index' : $this->EE->config->item('template');
					}
				}

				$basepath = $this->EE->functions->remove_double_slashes($this->EE->functions->create_url($uristr, FALSE).'/'.$deft_tmpl);

				if ($this->EE->TMPL->fetch_param('paginate_base'))
				{
					// Load the string helper
					$this->EE->load->helper('string');

					$pbase = trim_slashes($this->EE->TMPL->fetch_param('paginate_base'));

					$pbase = str_replace("/index", "/", $pbase);

					if ( ! strstr($basepath, $pbase))
					{
						$basepath = $this->EE->functions->remove_double_slashes($basepath.'/'.$pbase);
					}
				}

				$config['first_url'] 	= rtrim($basepath, '/').$anchor;
				$config['base_url']		= $basepath;
				$config['prefix']		= ( ! $dynamic) ? 'N' : 'P';
				$config['total_rows'] 	= $total_rows;
				$config['per_page']		= $limit;
				$config['cur_page']		= $current_page;
				$config['suffix']		= $anchor;
				$config['first_link'] 	= $this->EE->lang->line('pag_first_link');
				$config['last_link'] 	= $this->EE->lang->line('pag_last_link');
				
				// Allows $config['cur_page'] to override
				$config['uri_segment'] = 0;

				$this->EE->pagination->initialize($config);
				$pagination_links = $this->EE->pagination->create_links();
				$this->EE->pagination->initialize($config); // Re-initialize to reset config
				$pagination_array = $this->EE->pagination->create_link_array();

				if ((($total_pages * $limit) - $limit) > $current_page)
				{
					$page_next = $basepath.$config['prefix'].($current_page + $limit).'/';
				}

				if (($current_page - $limit ) >= 0)
				{
					$page_previous = $basepath.$config['prefix'].($current_page - $limit).'/';
				}
			}
			else
			{
				$current_page = '';
			}
		}

		// When only non-dynamic comments are shown, all results are valid as the
		// query is restricted with a LIMIT clause
		if ($dynamic OR $force_entry)
		{
			if ($current_page == '')
			{
				$result_ids = array_slice($result_ids, 0, $limit);
			}
			else
			{
				$result_ids = array_slice($result_ids, $current_page, $limit);
			}
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

		$result_path = (preg_match("/".LD."member_search_path\s*=(.*?)".RD."/s", $this->EE->TMPL->tagdata, $match)) ? $match['1'] : 'search/results';
		$result_path = str_replace("\"", "", $result_path);
		$result_path = str_replace("'",  "", $result_path);

		$search_link = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->EE->functions->fetch_action_id('Search', 'do_search').'&amp;result_path='.$result_path.'&amp;mbr=';

		$this->EE->db->select('comments.comment_id, comments.entry_id, comments.channel_id, comments.author_id, comments.name, comments.email, comments.url, comments.location AS c_location, comments.ip_address, comments.comment_date, comments.edit_date, comments.comment, comments.site_id AS comment_site_id,
										members.username, members.group_id, members.location, members.occupation, members.interests, members.aol_im, members.yahoo_im, members.msn_im, members.icq, members.group_id, members.member_id, members.signature, members.sig_img_filename, members.sig_img_width, members.sig_img_height, members.avatar_filename, members.avatar_width, members.avatar_height, members.photo_filename, members.photo_width, members.photo_height,
										member_data.*,
										channel_titles.title, channel_titles.url_title, channel_titles.author_id AS entry_author_id,
										channels.comment_text_formatting, channels.comment_html_formatting, channels.comment_allow_img_urls, channels.comment_auto_link_urls, channels.channel_url, channels.comment_url, channels.channel_title'
				);
				
		$this->EE->db->join('channels',			'comments.channel_id = channels.channel_id',	'left');
		$this->EE->db->join('channel_titles',	'comments.entry_id = channel_titles.entry_id',	'left');
		$this->EE->db->join('members',			'members.member_id = comments.author_id',		'left');
		$this->EE->db->join('member_data',		'member_data.member_id = members.member_id',	'left');
				
		$this->EE->db->where_in('comments.comment_id', $result_ids);
		$this->EE->db->order_by($order_by, $this_sort);
				
		$query = $this->EE->db->get('comments');
		
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

		$this->EE->db->select('m_field_id, m_field_name');
		$query = $this->EE->db->get('member_fields');

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

		$this->EE->load->library('typography');
		$this->EE->typography->initialize(array(
				'parse_images'		=> FALSE,
				'allow_headings'	=> FALSE,
				'word_censor'		=> ($this->EE->config->item('comment_word_censoring') == 'y') ? TRUE : FALSE)
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
			if (preg_match_all("/".LD.$val."\s+format=[\"'](.*?)[\"']".RD."/s", $this->EE->TMPL->tagdata, $matches))
			{
				for ($j = 0; $j < count($matches['0']); $j++)
				{
					$matches['0'][$j] = str_replace(LD, '', $matches['0'][$j]);
					$matches['0'][$j] = str_replace(RD, '', $matches['0'][$j]);

					switch ($val)
					{
						case 'comment_date' 	: $comment_date[$matches['0'][$j]] = $this->EE->localize->fetch_date_params($matches['1'][$j]);
							break;
						case 'gmt_comment_date' : $gmt_comment_date[$matches['0'][$j]] = $this->EE->localize->fetch_date_params($matches['1'][$j]);
							break;
						case 'edit_date'		: $edit_date[$matches['0'][$j]] = $this->EE->localize->fetch_date_params($matches['1'][$j]);
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
		$absolute_count = ($current_page == '') ? 0 : $current_page;

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
			$row['total_comments']	= $total_rows;
			$row['total_results']	= $total_results;

			// This lets the {if location} variable work

			if (isset($row['author_id']))
			{
				if ($row['author_id'] == 0)
					$row['location'] = $row['c_location'];
			}

			$tagdata = $this->EE->TMPL->tagdata;

			// -------------------------------------------
			// 'comment_entries_tagdata' hook.
			//  - Modify and play with the tagdata before everyone else
			//
				if ($this->EE->extensions->active_hook('comment_entries_tagdata') === TRUE)
				{
					$tagdata = $this->EE->extensions->call('comment_entries_tagdata', $tagdata, $row);
					if ($this->EE->extensions->end_script === TRUE) return $tagdata;
				}
			//
			// -------------------------------------------

			/** ----------------------------------------
			/**  Conditionals
			/** ----------------------------------------*/
			$cond = array_merge($member_cond_vars, $row);
			$cond['comments']			= (substr($id, 0, 1) == 't') ? 'FALSE' : 'TRUE';
			$cond['logged_in']			= ($this->EE->session->userdata('member_id') == 0) ? 'FALSE' : 'TRUE';
			$cond['logged_out']			= ($this->EE->session->userdata('member_id') != 0) ? 'FALSE' : 'TRUE';
			$cond['allow_comments'] 	= (isset($row['allow_comments']) AND $row['allow_comments'] == 'n') ? 'FALSE' : 'TRUE';
			$cond['signature_image']	= ( ! isset($row['sig_img_filename']) OR $row['sig_img_filename'] == '' OR $this->EE->config->item('enable_signatures') == 'n' OR $this->EE->session->userdata('display_signatures') == 'n') ? 'FALSE' : 'TRUE';
			$cond['avatar']				= ( ! isset($row['avatar_filename']) OR $row['avatar_filename'] == '' OR $this->EE->config->item('enable_avatars') == 'n' OR $this->EE->session->userdata('display_avatars') == 'n') ? 'FALSE' : 'TRUE';
			$cond['photo']				= ( ! isset($row['photo_filename']) OR $row['photo_filename'] == '' OR $this->EE->config->item('enable_photos') == 'n' OR $this->EE->session->userdata('display_photos') == 'n') ? 'FALSE' : 'TRUE';
			$cond['is_ignored']			= ( ! isset($row['member_id']) OR ! in_array($row['member_id'], $this->EE->session->userdata['ignore_list'])) ? 'FALSE' : 'TRUE';
			
			$cond['editable'] = FALSE;
			$cond['can_moderate_comment'] = FALSE;
			
			if ($this->EE->session->userdata['group_id'] == 1 OR 
				$this->EE->session->userdata['can_edit_all_comments'] == 'y' OR 
				($this->EE->session->userdata['can_edit_own_comments'] == 'y' && $row['entry_author_id'] == $this->EE->session->userdata['member_id'])
				)
			{
				$cond['editable'] = TRUE;
				$cond['can_moderate_comment'] = TRUE;								
			}
			elseif ($this->EE->session->userdata['member_id'] != '0'  && $row['author_id'] == $this->EE->session->userdata['member_id'])
			{
				$cond['editable'] = TRUE;
			}			

			if ( isset($mfields) && is_array($mfields) && count($mfields) > 0)
			{
				foreach($mfields as $key => $value)
				{
					if (isset($row['m_field_id_'.$value]))
						$cond[$key] = $row['m_field_id_'.$value];
				}
			}

			$tagdata = $this->EE->functions->prep_conditionals($tagdata, $cond);

			/** ----------------------------------------
			/**  Parse "single" variables
			/** ----------------------------------------*/
			foreach ($this->EE->TMPL->var_single as $key => $val)
			{

				/** ----------------------------------------
				/**  parse {switch} variable
				/** ----------------------------------------*/

				if (strncmp($key, 'switch', 6) == 0)
				{
					$sparam = $this->EE->functions->assign_parameters($key);

					$sw = '';

					if (isset($sparam['switch']))
					{
						$sopt = @explode("|", $sparam['switch']);

						$sw = $sopt[($relative_count + count($sopt) - 1) % count($sopt)];
					}

					$tagdata = $this->EE->TMPL->swap_var_single($key, $sw, $tagdata);
				}



				/** ----------------------------------------
				/**  parse permalink
				/** ----------------------------------------*/

				if ($key == 'permalink' && isset($row['comment_id']))
				{
						$tagdata = $this->EE->TMPL->swap_var_single(
															$key,
															$this->EE->functions->create_url($uristr.'#'.$row['comment_id'], FALSE),
															$tagdata
														 );
				}

				/** ----------------------------------------
				/**  parse comment_path
				/** ----------------------------------------*/

				if (strncmp($key, 'comment_path', 12) == 0 OR strncmp($key, 'entry_id_path', 13) == 0)
				{
					$tagdata = $this->EE->TMPL->swap_var_single(
														$key,
														$this->EE->functions->create_url($this->EE->functions->extract_path($key).'/'.$row['entry_id']),
														$tagdata
													 );
				}


				/** ----------------------------------------
				/**  parse title permalink
				/** ----------------------------------------*/

				if (strncmp($key, 'title_permalink', 15) == 0 OR strncmp($key, 'url_title_path', 14) == 0)
				{
					$path = ($this->EE->functions->extract_path($key) != '' AND $this->EE->functions->extract_path($key) != 'SITE_INDEX') ? $this->EE->functions->extract_path($key).'/'.$row['url_title'] : $row['url_title'];

					$tagdata = $this->EE->TMPL->swap_var_single(
														$key,
														$this->EE->functions->create_url($path, FALSE),
														$tagdata
													 );
				}

				/** ----------------------------------------
				/**  parse comment date
				/** ----------------------------------------*/

				if (isset($comment_date[$key]) && isset($row['comment_date']))
				{
					foreach ($comment_date[$key] as $dvar)
					{
						$val = str_replace($dvar, $this->EE->localize->convert_timestamp($dvar, $row['comment_date'], TRUE), $val);
					}

					$tagdata = $this->EE->TMPL->swap_var_single($key, $val, $tagdata);
				}

				/** ----------------------------------------
				/**  parse GMT comment date
				/** ----------------------------------------*/

				if (isset($gmt_comment_date[$key]) && isset($row['comment_date']))
				{
					foreach ($gmt_comment_date[$key] as $dvar)
					{
						$val = str_replace($dvar, $this->EE->localize->convert_timestamp($dvar, $row['comment_date'], FALSE), $val);
					}

					$tagdata = $this->EE->TMPL->swap_var_single($key, $val, $tagdata);
				}

				/** ----------------------------------------
				/**  parse "last edit" date
				/** ----------------------------------------*/

				if (isset($edit_date[$key]))
				{
					if (isset($row['edit_date']))
					{
						foreach ($edit_date[$key] as $dvar)
							$val = str_replace($dvar, $this->EE->localize->convert_timestamp($dvar, $this->EE->localize->timestamp_to_gmt($row['edit_date']), TRUE), $val);

						$tagdata = $this->EE->TMPL->swap_var_single($key, $val, $tagdata);
					}
				}


				/** ----------------------------------------
				/**  {member_search_path}
				/** ----------------------------------------*/

				if (strncmp($key, 'member_search_path', 18) == 0)
				{
					$tagdata = $this->EE->TMPL->swap_var_single($key, $search_link.$row['author_id'], $tagdata);
				}

				
				// {member_group_id}
				if ($key == 'member_group_id')
				{
					$tagdata = $this->EE->TMPL->swap_var_single($key, $row['group_id'], $tagdata);
				}

				// Prep the URL
				if (isset($row['url']))
				{
					$this->EE->load->helper('url');
					$row['url'] = prep_url($row['url']);
				}

				/** ----------------------------------------
				/**  {username}
				/** ----------------------------------------*/
				if ($key == "username")
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, (isset($row['username'])) ? $row['username'] : '', $tagdata);
				}

				/** ----------------------------------------
				/**  {author}
				/** ----------------------------------------*/
				if ($key == "author")
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, (isset($row['name'])) ? $row['name'] : '', $tagdata);
				}

				/** ----------------------------------------
				/**  {url_or_email} - Uses Raw Email Address, Like Channel Module
				/** ----------------------------------------*/

				if ($key == "url_or_email" AND isset($row['url']))
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, ($row['url'] != '') ? $row['url'] : $row['email'], $tagdata);
				}


				/** ----------------------------------------
				/**  {url_as_author}
				/** ----------------------------------------*/
				if ($key == "url_as_author" AND isset($row['url']))
				{
					if ($row['url'] != '')
					{
						$tagdata = $this->EE->TMPL->swap_var_single($val, "<a href=\"".$row['url']."\">".$row['name']."</a>", $tagdata);
					}
					else
					{
						$tagdata = $this->EE->TMPL->swap_var_single($val, $row['name'], $tagdata);
					}
				}

				/** ----------------------------------------
				/**  {url_or_email_as_author}
				/** ----------------------------------------*/

				if ($key == "url_or_email_as_author" AND isset($row['url']))
				{
					if ($row['url'] != '')
					{
						$tagdata = $this->EE->TMPL->swap_var_single($val, "<a href=\"".$row['url']."\">".$row['name']."</a>", $tagdata);
					}
					else
					{
						if ($row['email'] != '')
						{
							$tagdata = $this->EE->TMPL->swap_var_single($val, $this->EE->typography->encode_email($row['email'], $row['name']), $tagdata);
						}
						else
						{
							$tagdata = $this->EE->TMPL->swap_var_single($val, $row['name'], $tagdata);
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
						$tagdata = $this->EE->TMPL->swap_var_single($val, "<a href=\"".$row['url']."\">".$row['url']."</a>", $tagdata);
					}
					else
					{
						if ($row['email'] != '')
						{
							$tagdata = $this->EE->TMPL->swap_var_single($val, $this->EE->typography->encode_email($row['email']), $tagdata);
						}
						else
						{
							$tagdata = $this->EE->TMPL->swap_var_single($val, $row['name'], $tagdata);
						}
					}
				}

				/** ----------------------------------------
				/**  {comment_auto_path}
				/** ----------------------------------------*/

				if ($key == "comment_auto_path")
				{
					$path = ($row['comment_url'] == '') ? $row['channel_url'] : $row['comment_url'];

					$tagdata = $this->EE->TMPL->swap_var_single($key, $path, $tagdata);
				}

				/** ----------------------------------------
				/**  {comment_url_title_auto_path}
				/** ----------------------------------------*/

				if ($key == "comment_url_title_auto_path")
				{
					$path = ($row['comment_url'] == '') ? $row['channel_url'] : $row['comment_url'];

					$tagdata = $this->EE->TMPL->swap_var_single(
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

					$tagdata = $this->EE->TMPL->swap_var_single(
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

					$tagdata = $this->EE->TMPL->swap_var_single(
															$key,
															$this->EE->functions->encode_ee_tags($row['comment'], TRUE),
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
						if ($this->EE->extensions->active_hook('comment_entries_comment_format') === TRUE)
						{
							$comment = $this->EE->extensions->call('comment_entries_comment_format', $row);
				
							if ($this->EE->extensions->end_script === TRUE) return;
						}
						else
						{
							$comment = $this->EE->typography->parse_type( $row['comment'],
															array(
																	'text_format'	=> $row['comment_text_formatting'],
																	'html_format'	=> $row['comment_html_formatting'],
																	'auto_links'	=> $row['comment_auto_link_urls'],
																	'allow_img_url' => $row['comment_allow_img_urls']
																)
														);
						}

					$tagdata = $this->EE->TMPL->swap_var_single($key, $comment, $tagdata);
				}

				//  {location}

				if ($key == 'location' AND (isset($row['location']) OR isset($row['c_location'])))
				{
					$tagdata = $this->EE->TMPL->swap_var_single($key, (empty($row['location'])) ? $row['c_location'] : $row['location'], $tagdata);
				}


				/** ----------------------------------------
				/**  {signature}
				/** ----------------------------------------*/

				if ($key == "signature")
				{
					if ($this->EE->session->userdata('display_signatures') == 'n' OR  ! isset($row['signature']) OR $row['signature'] == '' OR $this->EE->session->userdata('display_signatures') == 'n')
					{
						$tagdata = $this->EE->TMPL->swap_var_single($key, '', $tagdata);
					}
					else
					{
						$tagdata = $this->EE->TMPL->swap_var_single($key,
														$this->EE->typography->parse_type($row['signature'], array(
																					'text_format'	=> 'xhtml',
																					'html_format'	=> 'safe',
																					'auto_links'	=> 'y',
																					'allow_img_url' => $this->EE->config->item('sig_allow_img_hotlink')
																				)
																			), $tagdata);
					}
				}


				if ($key == "signature_image_url")
				{
					if ($this->EE->session->userdata('display_signatures') == 'n' OR $row['sig_img_filename'] == ''  OR $this->EE->session->userdata('display_signatures') == 'n')
					{
						$tagdata = $this->EE->TMPL->swap_var_single($key, '', $tagdata);
						$tagdata = $this->EE->TMPL->swap_var_single('signature_image_width', '', $tagdata);
						$tagdata = $this->EE->TMPL->swap_var_single('signature_image_height', '', $tagdata);
					}
					else
					{
						$tagdata = $this->EE->TMPL->swap_var_single($key, $this->EE->config->slash_item('sig_img_url').$row['sig_img_filename'], $tagdata);
						$tagdata = $this->EE->TMPL->swap_var_single('signature_image_width', $row['sig_img_width'], $tagdata);
						$tagdata = $this->EE->TMPL->swap_var_single('signature_image_height', $row['sig_img_height'], $tagdata);
					}
				}

				if ($key == "avatar_url")
				{
					if ( ! isset($row['avatar_filename']))
						$row['avatar_filename'] = '';

					if ($this->EE->session->userdata('display_avatars') == 'n' OR $row['avatar_filename'] == ''  OR $this->EE->session->userdata('display_avatars') == 'n')
					{
						$tagdata = $this->EE->TMPL->swap_var_single($key, '', $tagdata);
						$tagdata = $this->EE->TMPL->swap_var_single('avatar_image_width', '', $tagdata);
						$tagdata = $this->EE->TMPL->swap_var_single('avatar_image_height', '', $tagdata);
					}
					else
					{
						$tagdata = $this->EE->TMPL->swap_var_single($key, $this->EE->config->slash_item('avatar_url').$row['avatar_filename'], $tagdata);
						$tagdata = $this->EE->TMPL->swap_var_single('avatar_image_width', $row['avatar_width'], $tagdata);
						$tagdata = $this->EE->TMPL->swap_var_single('avatar_image_height', $row['avatar_height'], $tagdata);
					}
				}

				if ($key == "photo_url")
				{
					if ( ! isset($row['photo_filename']))
						$row['photo_filename'] = '';

					if ($this->EE->session->userdata('display_photos') == 'n' OR $row['photo_filename'] == ''  OR $this->EE->session->userdata('display_photos') == 'n')
					{
						$tagdata = $this->EE->TMPL->swap_var_single($key, '', $tagdata);
						$tagdata = $this->EE->TMPL->swap_var_single('photo_image_width', '', $tagdata);
						$tagdata = $this->EE->TMPL->swap_var_single('photo_image_height', '', $tagdata);
					}
					else
					{
						$tagdata = $this->EE->TMPL->swap_var_single($key, $this->EE->config->slash_item('photo_url').$row['photo_filename'], $tagdata);
						$tagdata = $this->EE->TMPL->swap_var_single('photo_image_width', $row['photo_width'], $tagdata);
						$tagdata = $this->EE->TMPL->swap_var_single('photo_image_height', $row['photo_height'], $tagdata);
					}
				}


				/** ----------------------------------------
				/**  parse basic fields
				/** ----------------------------------------*/

				if (isset($row[$val]) && $val != 'member_id')
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, $row[$val], $tagdata);
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

					$tagdata = $this->EE->TMPL->swap_var_single(
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

		$return = preg_replace_callback("/".LD."\s*path=(.+?)".RD."/", array(&$this->EE->functions, 'create_url'), $return);

		/** ----------------------------------------
		/**  Add pagination to result
		/** ----------------------------------------*/
		if ($paginate == TRUE)
		{
			// Check to see if pagination_links is being used as a single 
			// variable or as a variable pair
			if (preg_match_all("/".LD."pagination_links".RD."(.+?)".LD.'\/'."pagination_links".RD."/s", $paginate_data, $matches))
			{
				$pagination_links = array($pagination_array);
			}
			else
			{
				$pagination_links = $pagination_links;
			}

			// Parse current_page and total_pages by default
			$parse_array = array(
				'current_page' => $t_current_page,
				'total_pages' => $total_pages,
			);

			// Check to see if pagination_links is being used as a single 
			// variable or as a variable pair
			if (preg_match_all("/".LD."pagination_links".RD."(.+?)".LD.'\/'."pagination_links".RD."/s", $paginate_data, $matches))
			{
				$parse_array['pagination_links'] = array($pagination_array);
			}
			else
			{
				$parse_array['pagination_links'] = $pagination_links;
			}
			
			// Parse current_page and total_pages
			$paginate_data = $this->EE->TMPL->parse_variables(
				$paginate_data,
				array($parse_array)
			);
			
			if (preg_match("/".LD."if previous_page".RD."(.+?)".LD.'\/'."if".RD."/s", $paginate_data, $match))
			{
				if ($page_previous == '')
				{
					 $paginate_data = preg_replace("/".LD."if previous_page".RD.".+?".LD.'\/'."if".RD."/s", '', $paginate_data);
				}
				else
				{
					$match['1'] = str_replace(array(LD.'path'.RD, LD.'auto_path'.RD), $page_previous, $match['1']);

					$paginate_data = str_replace($match['0'], $match['1'], $paginate_data);
				}
			}

			if (preg_match("/".LD."if next_page".RD."(.+?)".LD.'\/'."if".RD."/s", $paginate_data, $match))
			{
				if ($page_next == '')
				{
					 $paginate_data = preg_replace("/".LD."if next_page".RD.".+?".LD.'\/'."if".RD."/s", '', $paginate_data);
				}
				else
				{
					$match['1'] = str_replace(array(LD.'path'.RD, LD.'auto_path'.RD), $page_next, $match['1']);

					$paginate_data = str_replace($match['0'], $match['1'], $paginate_data);
				}
			}

			$position = ( ! $this->EE->TMPL->fetch_param('paginate')) ? '' : $this->EE->TMPL->fetch_param('paginate');

			switch ($position)
			{
				case "top"	: $return  = $paginate_data.$return;
					break;
				case "both"	: $return  = $paginate_data.$return.$paginate_data;
					break;
				default		: $return .= $paginate_data;
					break;
			}
		}

		return $return;
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
		
		$this->EE->db->distinct();
		$this->EE->db->select('entry_id');
		$this->EE->functions->ar_andor_string($comment_id_param, 'comment_id');
		$query = $this->EE->db->get('comments');

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
		$qstring		 = $this->EE->uri->query_string;
		$entry_where	 = array();
		$halt_processing = FALSE;

		/** --------------------------------------
		/**  Remove page number
		/** --------------------------------------*/
		
		if (preg_match("#(^|/)P(\d+)(/|$)#", $qstring, $match))
		{
			$qstring = trim($this->EE->functions->remove_double_slashes(str_replace($match['0'], '/', $qstring)), '/');
		}		

		// Figure out the right entry ID
		// Order of precedence: POST, entry_id=, url_title=, $qstring
		if (isset($_POST['entry_id']))
		{
			$entry_where = array('entry_id' => $_POST['entry_id']);
		}
		elseif ($entry_id = $this->EE->TMPL->fetch_param('entry_id'))
		{
			$entry_where = array('entry_id' => $entry_id);
		}
		elseif ($url_title = $this->EE->TMPL->fetch_param('url_title'))
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

		if ($channel = $this->EE->TMPL->fetch_param('channel'))
		{
			$this->EE->db->select('channel_id');
			$this->EE->functions->ar_andor_string($channel, 'channel_name');
			$this->EE->db->where_in('site_id', $this->EE->TMPL->site_ids);
			$query = $this->EE->db->get('channels');

			if ($query->num_rows() == 0)
			{
				return FALSE;
			}
			
			if ($query->num_rows() == 1)
			{
				$this->EE->db->where('channel_titles.channel_id', $query->row('channel_id'));
			}
			else
			{
				$ids = array();
				
				foreach ($query->result_array() as $row)
				{
					$ids[] = $row['channel_id'];
				}
				
				$this->EE->db->where_in('channel_titles.channel_id', $ids);
			}
		}

		// The where clauses above will affect this query - it's below the conditional
		// because AR cannot keep track of two queries at once

		$this->EE->db->select('channel_titles.entry_id, channel_titles.entry_date, channel_titles.comment_expiration_date, channel_titles.allow_comments, channels.comment_system_enabled, channels.comment_use_captcha, channels.comment_expiration');
		$this->EE->db->from(array('channel_titles', 'channels'));
		
		$this->EE->db->where_in('channel_titles.site_id', $this->EE->TMPL->site_ids);
		$this->EE->db->where('channel_titles.channel_id = '.$this->EE->db->dbprefix('channels').'.channel_id');
		
		if ($e_status = $this->EE->TMPL->fetch_param('entry_status'))
		{
			$e_status = str_replace('Open',	'open',	$e_status);
			$e_status = str_replace('Closed', 'closed', $e_status);

			$this->EE->functions->ar_andor_string($e_status, 'status');

			if (stristr($sql, "'closed'") === FALSE)
			{
				$this->EE->db->where('status !=', 'closed');
			}
		}
		else
		{
			$this->EE->db->where('status !=', 'closed');
		}
		
		
		$this->EE->db->where($entry_where);
	
		$query = $this->EE->db->get();

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
		
		if ($this->EE->session->userdata('smart_notifications') == 'y')
		{
			$this->EE->load->library('subscription');
			$this->EE->subscription->init('comment', array('entry_id' => $query->row('entry_id')), TRUE);
			$this->EE->subscription->mark_as_read();
		}

		/** ----------------------------------------
		/**  Return the "no cache" version of the form
		/** ----------------------------------------*/

		if ($return_form == FALSE)
		{
			if ($query->row('comment_use_captcha')  == 'n')
			{
				$this->EE->TMPL->tagdata = str_replace(LD.'captcha'.RD, '', $this->EE->TMPL->tagdata);
			}

			$nc = '';

			if (is_array($this->EE->TMPL->tagparams) AND count($this->EE->TMPL->tagparams) > 0)
			{
				foreach ($this->EE->TMPL->tagparams as $key => $val)
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
			
			return '{NOCACHE_COMMENT_FORM="'.$nc.'"}'.$this->EE->TMPL->tagdata.'{/NOCACHE_FORM}';
		}

		/** ----------------------------------------
		/**  Has commenting expired?
		/** ----------------------------------------*/

		$mode = ( ! isset($this->comment_expiration_mode)) ? 0 : $this->comment_expiration_mode;

		//  First check whether expiration is overriden
		if ($this->EE->config->item('comment_moderation_override') !== 'y')
		{
			if ($mode == 0)
			{
				if ($query->row('comment_expiration_date')  > 0)
				{
					if ($this->EE->localize->now > $query->row('comment_expiration_date') )
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

					if ($this->EE->localize->now > $days)
					{
						$halt_processing = 'expired';
					}
				}
			}
		}

		$tagdata = $this->EE->TMPL->tagdata;

		if ($halt_processing != FALSE)
		{
			foreach ($this->EE->TMPL->var_cond as $key => $val) 
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
			$this->EE->lang->loadfile('comment');
			return $this->EE->lang->line('cmt_commenting_has_expired');				
		}


		// -------------------------------------------
		// 'comment_form_tagdata' hook.
		//  - Modify, add, etc. something to the comment form
		//
			if ($this->EE->extensions->active_hook('comment_form_tagdata') === TRUE)
			{
				$tagdata = $this->EE->extensions->call('comment_form_tagdata', $tagdata);
				if ($this->EE->extensions->end_script === TRUE) return;
			}
		//
		// -------------------------------------------

		/** ----------------------------------------
		/**  Conditionals
		/** ----------------------------------------*/

		$cond = array();
		$cond['logged_in']			= ($this->EE->session->userdata('member_id') == 0) ? 'FALSE' : 'TRUE';
		$cond['logged_out']			= ($this->EE->session->userdata('member_id') != 0) ? 'FALSE' : 'TRUE';

		if ($query->row('comment_use_captcha')  == 'n')
		{
			$cond['captcha'] = 'FALSE';
		}
		elseif ($query->row('comment_use_captcha')  == 'y')
		{
			$cond['captcha'] =  ($this->EE->config->item('captcha_require_members') == 'y'  OR
								($this->EE->config->item('captcha_require_members') == 'n' AND $this->EE->session->userdata('member_id') == 0)) ? 'TRUE' : 'FALSE';
		}

		$tagdata = $this->EE->functions->prep_conditionals($tagdata, $cond);

		/** ----------------------------------------
		/**  Single Variables
		/** ----------------------------------------*/

		// Load the form helper
		$this->EE->load->helper('form');

		foreach ($this->EE->TMPL->var_single as $key => $val)
		{
			/** ----------------------------------------
			/**  parse {name}
			/** ----------------------------------------*/

			if ($key == 'name')
			{
				$name = ($this->EE->session->userdata['screen_name'] != '') ? $this->EE->session->userdata['screen_name'] : $this->EE->session->userdata['username'];

				$name = ( ! isset($_POST['name'])) ? $name : $_POST['name'];

				$tagdata = $this->EE->TMPL->swap_var_single($key, form_prep($name), $tagdata);
			}

			/** ----------------------------------------
			/**  parse {email}
			/** ----------------------------------------*/

			if ($key == 'email')
			{
				$email = ( ! isset($_POST['email'])) ? $this->EE->session->userdata['email'] : $_POST['email'];

				$tagdata = $this->EE->TMPL->swap_var_single($key, form_prep($email), $tagdata);
			}

			/** ----------------------------------------
			/**  parse {url}
			/** ----------------------------------------*/

			if ($key == 'url')
			{
				$url = ( ! isset($_POST['url'])) ? $this->EE->session->userdata['url'] : $_POST['url'];

				if ($url == '')
				{
					$url = 'http://';					
				}

				$tagdata = $this->EE->TMPL->swap_var_single($key, form_prep($url), $tagdata);
			}

			/** ----------------------------------------
			/**  parse {location}
			/** ----------------------------------------*/

			if ($key == 'location')
			{
				$location = ( ! isset($_POST['location'])) ? $this->EE->session->userdata['location'] : $_POST['location'];

				$tagdata = $this->EE->TMPL->swap_var_single($key, form_prep($location), $tagdata);
			}

			/** ----------------------------------------
			/**  parse {comment}
			/** ----------------------------------------*/

			if ($key == 'comment')
			{
				$comment = ( ! isset($_POST['comment'])) ? '' : $_POST['comment'];

				$tagdata = $this->EE->TMPL->swap_var_single($key, $comment, $tagdata);
			}

			/** ----------------------------------------
			/**  parse {captcha_word}
			/** ----------------------------------------*/

			if ($key == 'captcha_word')
			{
				$tagdata = $this->EE->TMPL->swap_var_single($key, '', $tagdata);
			}

			/** ----------------------------------------
			/**  parse {save_info}
			/** ----------------------------------------*/

			if ($key == 'save_info')
			{
				$save_info = ( ! isset($_POST['save_info'])) ? '' : $_POST['save_info'];

				$notify = ( ! isset($this->EE->session->userdata['notify_by_default'])) ? $this->EE->input->cookie('save_info') : $this->EE->session->userdata['notify_by_default'];

				$checked = ( ! isset($_POST['PRV'])) ? $notify : $save_info;

				$tagdata = $this->EE->TMPL->swap_var_single($key, ($checked == 'yes') ? "checked=\"checked\"" : '', $tagdata);
			}

			/** ----------------------------------------
			/**  parse {notify_me}
			/** ----------------------------------------*/

			if ($key == 'notify_me')
			{
				$checked = '';

				if ( ! isset($_POST['PRV']))
				{
					if ($this->EE->input->cookie('notify_me'))
					{
						$checked = $this->EE->input->cookie('notify_me');
					}

					if (isset($this->EE->session->userdata['notify_by_default']))
					{
						$checked = ($this->EE->session->userdata['notify_by_default'] == 'y') ? 'yes' : '';
					}
				}

				if (isset($_POST['notify_me']))
				{
					$checked = $_POST['notify_me'];
				}

				$tagdata = $this->EE->TMPL->swap_var_single($key, ($checked == 'yes') ? "checked=\"checked\"" : '', $tagdata);
			}
		}

		/** ----------------------------------------
		/**  Create form
		/** ----------------------------------------*/
		
		$RET = $this->EE->functions->fetch_current_uri();
		
		if (isset($_POST['RET']))
		{
			$RET = $this->EE->input->post('RET');
		}
		elseif ($this->EE->TMPL->fetch_param('return') && $this->EE->TMPL->fetch_param('return') != "")
		{
			$RET =  $this->EE->TMPL->fetch_param('return');
		}
		
		$PRV = (isset($_POST['PRV'])) ? $_POST['PRV'] : $this->EE->TMPL->fetch_param('preview');
		$XID = (isset($_POST['XID'])) ? $_POST['XID'] : '';

		$hidden_fields = array(
								'ACT'	  	=> $this->EE->functions->fetch_action_id('Comment', 'insert_new_comment'),
								'RET'	  	=> $RET,
								'URI'	  	=> ($this->EE->uri->uri_string == '') ? 'index' : $this->EE->uri->uri_string,
								'PRV'	  	=> $PRV,
								'XID'	  	=> $XID,
								'entry_id' 	=> $query->row('entry_id')
							  );

		if ($query->row('comment_use_captcha')  == 'y')
		{
			if (preg_match("/({captcha})/", $tagdata))
			{
				$tagdata = preg_replace("/{captcha}/", $this->EE->functions->create_captcha(), $tagdata);
			}
		}

		// -------------------------------------------
		// 'comment_form_hidden_fields' hook.
		//  - Add/Remove Hidden Fields for Comment Form
		//
			if ($this->EE->extensions->active_hook('comment_form_hidden_fields') === TRUE)
			{
				$hidden_fields = $this->EE->extensions->call('comment_form_hidden_fields', $hidden_fields);
				if ($this->EE->extensions->end_script === TRUE) return;
			}
		//
		// -------------------------------------------

		$uri_string = ($this->EE->uri->uri_string == '') ? 'index' : $this->EE->uri->uri_string;
		$url = $this->EE->functions->fetch_site_index(0,0).'/'.$uri_string;

		$data = array(
						'action'		=> $this->EE->functions->remove_double_slashes($url),
						'hidden_fields'	=> $hidden_fields,
						'id'			=> ( ! isset($this->EE->TMPL->tagparams['id'])) ? 'comment_form' : $this->EE->TMPL->tagparams['id'],
						'class'			=> ( ! isset($this->EE->TMPL->tagparams['class'])) ? NULL : $this->EE->TMPL->tagparams['class']
					);

		if ($this->EE->TMPL->fetch_param('name') !== FALSE &&
			preg_match("#^[a-zA-Z0-9_\-]+$#i", $this->EE->TMPL->fetch_param('name'), $match))
		{
			$data['name'] = $this->EE->TMPL->fetch_param('name');
		}

		$res  = $this->EE->functions->form_declaration($data);

		$res .= stripslashes($tagdata);
		$res .= "</form>";

		// -------------------------------------------
		// 'comment_form_end' hook.
		//  - Modify, add, etc. something to the comment form at end of processing
		//
			if ($this->EE->extensions->active_hook('comment_form_end') === TRUE)
			{
				$res = $this->EE->extensions->call('comment_form_end', $res);
				if ($this->EE->extensions->end_script === TRUE) return $res;
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
		$entry_id = (isset($_POST['entry_id'])) ? $_POST['entry_id'] : $this->EE->uri->query_string;

		if ( ! is_numeric($entry_id) OR empty($_POST['comment']))
		{
			return FALSE;
		}

		/** ----------------------------------------
		/**  Instantiate Typography class
		/** ----------------------------------------*/

		$this->EE->load->library('typography');		
		$this->EE->typography->initialize(array(
				'parse_images'		=> FALSE,
				'allow_headings'	=> FALSE,
				'encode_email'		=> FALSE,
				'word_censor'		=> ($this->EE->config->item('comment_word_censoring') == 'y') ? TRUE : FALSE)
				);

		$this->EE->db->select('channels.comment_text_formatting, channels.comment_html_formatting, channels.comment_allow_img_urls, channels.comment_auto_link_urls, channels.comment_max_chars');
		$this->EE->db->where('channel_titles.channel_id = '.$this->EE->db->dbprefix('channels').'.channel_id');
		$this->EE->db->where('channel_titles.entry_id', $entry_id);
		$this->EE->db->from(array('channels', 'channel_titles'));
		
		$query = $this->EE->db->get();

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
				$str = str_replace("%n", strlen($_POST['comment']), $this->EE->lang->line('cmt_too_large'));

				$str = str_replace("%x", $query->row('comment_max_chars') , $str);

				return $this->EE->output->show_user_error('submission', $str);
			}
		}

		$formatting = 'none';

		if ($query->num_rows())
		{
			$formatting = $query->row('comment_text_formatting') ;
		}

		$tagdata = $this->EE->TMPL->tagdata;

		// -------------------------------------------
		// 'comment_preview_tagdata' hook.
		//  - Play with the tagdata contents of the comment preview
		//
			if ($this->EE->extensions->active_hook('comment_preview_tagdata') === TRUE)
			{
				$tagdata = $this->EE->extensions->call('comment_preview_tagdata', $tagdata);
				if ($this->EE->extensions->end_script === TRUE) return;
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

				$comment_date[$matches['0'][$j]] = $this->EE->localize->fetch_date_params($matches['1'][$j]);
			}
		}

        /** ----------------------------------------
        /**  Set defaults based on member data as needed
        /** ----------------------------------------*/		
		
		$name		= $this->EE->input->post('name', TRUE);
		$email		= $this->EE->input->post('email', TRUE); // this is just for preview, actual submission will validate the email address
	 	$url		= $this->EE->input->post('url', TRUE);
	 	$location	= $this->EE->input->post('location', TRUE);
		
		if ($this->EE->session->userdata('member_id') != 0)
		{
			 $name		= $this->EE->session->userdata('screen_name') ? $this->EE->session->userdata('screen_name') : $this->EE->session->userdata('username');
			 $email		= $this->EE->session->userdata('email');
			 $url		= (string) $this->EE->session->userdata('url');
			 $location	= (string) $this->EE->session->userdata('location');
		}
		
		/** ----------------------------------------
		/**  Conditionals
		/** ----------------------------------------*/

		$cond = $_POST; // Sanitized on input and also in prep_conditionals, so no real worries here
		$cond['logged_in']			= ($this->EE->session->userdata('member_id') == 0) ? 'FALSE' : 'TRUE';
		$cond['logged_out']			= ($this->EE->session->userdata('member_id') != 0) ? 'FALSE' : 'TRUE';
		$cond['name']				= $name;	
		$cond['email']				= $email;		
		$cond['url']				= ($url == 'http://') ? '' : $url;
		$cond['location']			= $location;
		
		$tagdata = $this->EE->functions->prep_conditionals($tagdata, $cond);


		// Prep the URL

		if ($url != '')
		{
			$this->EE->load->helper('url');
			$url = prep_url($url);
		}

		/** ----------------------------------------
		/**  Single Variables
		/** ----------------------------------------*/

		foreach ($this->EE->TMPL->var_single as $key => $val)
		{
			// Start with the simple ones
			if (in_array($key, array('name', 'email', 'url', 'location')))
			{
				$tagdata = $this->EE->TMPL->swap_var_single($key, $$key, $tagdata);
			}

			//  {url_or_email}
			elseif ($key == "url_or_email")
			{
				$temp = $url;

				if ($temp == '' AND $email != '')
				{
					$temp = $this->EE->typography->encode_email($email, '', 0);
				}

				$tagdata = $this->EE->TMPL->swap_var_single($val, $temp, $tagdata);
			}


			//  {url_or_email_as_author}
			elseif ($key == "url_or_email_as_author")
			{
				if ($url != '')
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, "<a href=\"".$url."\">".$name."</a>", $tagdata);
				}
				else
				{
					if ($email != '')
					{
						$tagdata = $this->EE->TMPL->swap_var_single($val, $this->EE->typography->encode_email($email, $name), $tagdata);
					}
					else
					{
						$tagdata = $this->EE->TMPL->swap_var_single($val, $name, $tagdata);
					}
				}
			}

			//  {url_or_email_as_link}
			elseif ($key == "url_or_email_as_link")
			{
				if ($url != '')
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, "<a href=\"".$url."\">".$url."</a>", $tagdata);
				}
				else
				{
					if ($email != '')
					{
						$tagdata = $this->EE->TMPL->swap_var_single($val, $this->EE->typography->encode_email($email), $tagdata);
					}
					else
					{
						$tagdata = $this->EE->TMPL->swap_var_single($val, $name, $tagdata);
					}
				}
			}

			//  {url_as_author}

            elseif ($key == 'url_as_author')
            {
                if ($url != '')
                {
                    $tagdata = $this->EE->TMPL->swap_var_single($val, '<a href="'.$url.'">'.$name.'</a>', $tagdata);
                }
                else
                {
                    $tagdata = $this->EE->TMPL->swap_var_single($val, $name, $tagdata);
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
					if ($this->EE->extensions->active_hook('comment_preview_comment_format') === TRUE)
					{
						$data = $this->EE->extensions->call('comment_preview_comment_format', $query->row());
						if ($this->EE->extensions->end_script === TRUE) return;
					}
					else
					{
						$data = $this->EE->typography->parse_type(
							$this->EE->input->post('comment'),
							array(
								'text_format'	=> $query->row('comment_text_formatting') ,
								'html_format'	=> $query->row('comment_html_formatting') ,
								'auto_links'	=> $query->row('comment_auto_link_urls') ,
								'allow_img_url' => $query->row('comment_allow_img_urls')
							)
						);
					}

				// -------------------------------------------

				$tagdata = $this->EE->TMPL->swap_var_single($key, $data, $tagdata);
			}

			/** ----------------------------------------
			/**  parse comment date
			/** ----------------------------------------*/

			elseif (isset($comment_date[$key]))
			{
				foreach ($comment_date[$key] as $dvar)
				{
					$val = str_replace($dvar, $this->EE->localize->convert_timestamp($dvar, $this->EE->localize->now, TRUE), $val);
				}

				$tagdata = $this->EE->TMPL->swap_var_single($key, $val, $tagdata);
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
		if ($this->EE->input->post('PRV') == '')
		{
			$error[] = $this->EE->lang->line('cmt_no_preview_template_specified');

			return $this->EE->output->show_user_error('general', $error);
		}

		if ( ! isset($_POST['PRV']) or $_POST['PRV'] == '')
		{
			exit('Preview template not specified in your comment form tag');
		}
		
		// Clean return value- segments only
		$clean_return = str_replace($this->EE->functions->fetch_site_index(), '', $_POST['RET']);

		// Load the string helper
		$this->EE->load->helper('string');

		$_POST['PRV'] = trim_slashes($this->EE->security->xss_clean($_POST['PRV']));

		$this->EE->functions->clear_caching('all', $_POST['PRV']);
		$this->EE->functions->clear_caching('all', $clean_return);

		require APPPATH.'libraries/Template.php';

		$this->EE->TMPL = new EE_Template();

		$preview = ( ! $this->EE->input->post('PRV')) ? '' : $this->EE->input->get_post('PRV');

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
		$this->EE->TMPL->parse_template_uri();
		$this->EE->TMPL->run_template_engine($group, $templ);
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

		$this->EE->lang->loadfile('comment');
		
		//  No comment- let's end it here
		if ($_POST['comment'] == '')
		{
			$error = $this->EE->lang->line('cmt_missing_comment');
			return $this->EE->output->show_user_error('submission', $error);
		}		

		/** ----------------------------------------
		/**  Is the user banned?
		/** ----------------------------------------*/

		if ($this->EE->session->userdata['is_banned'] == TRUE)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}

		/** ----------------------------------------
		/**  Is the IP address and User Agent required?
		/** ----------------------------------------*/

		if ($this->EE->config->item('require_ip_for_posting') == 'y')
		{
			if ($this->EE->input->ip_address() == '0.0.0.0' OR $this->EE->session->userdata['user_agent'] == "")
			{
				return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
			}
		}

		/** ----------------------------------------
		/**  Is the nation of the user banend?
		/** ----------------------------------------*/
		$this->EE->session->nation_ban_check();

		/** ----------------------------------------
		/**  Can the user post comments?
		/** ----------------------------------------*/

		if ($this->EE->session->userdata['can_post_comments'] == 'n')
		{
			$error[] = $this->EE->lang->line('cmt_no_authorized_for_comments');

			return $this->EE->output->show_user_error('general', $error);
		}

		/** ----------------------------------------
		/**  Blacklist/Whitelist Check
		/** ----------------------------------------*/

		if ($this->EE->blacklist->blacklisted == 'y' && $this->EE->blacklist->whitelisted == 'n')
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
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
			$edata = $this->EE->extensions->call('insert_comment_start');
			if ($this->EE->extensions->end_script === TRUE) return;
		//
		// -------------------------------------------

		/** ----------------------------------------
		/**  Fetch channel preferences
		/** ----------------------------------------*/

// Bummer, saw the hook after converting the query
/*
		$this->EE->db->select('channel_titles.title, channel_titles.url_title, channel_titles.channel_id, channel_titles.author_id,
						channel_titles.comment_total, channel_titles.allow_comments, channel_titles.entry_date, channel_titles.comment_expiration_date,
						channels.channel_title, channels.comment_system_enabled, channels.comment_max_chars, channels.comment_use_captcha,
						channels.comment_timelock, channels.comment_require_membership, channels.comment_moderate, channels.comment_require_email,
						channels.comment_notify, channels.comment_notify_authors, channels.comment_notify_emails, channels.comment_expiration'
		);
		
		$this->EE->db->from(array('channel_titles', 'channels'));
		$this->EE->db->where('channel_titles.channel_id = channels.channel_id');
		$this->EE->db->where('channel_titles.entry_id', $_POST['entry_id']);
		$this->EE->db->where('channel_titles.status', 'closed');
*/
		$sql = "SELECT exp_channel_titles.title,
						exp_channel_titles.url_title,
						exp_channel_titles.entry_id,
						exp_channel_titles.channel_id,
						exp_channel_titles.author_id,
						exp_channel_titles.comment_total,
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
				AND	exp_channel_titles.entry_id = '".$this->EE->db->escape_str($_POST['entry_id'])."'";
				
				//  Added entry_status param, so it is possible to post to closed title
				//AND	exp_channel_titles.status != 'closed' ";

		// -------------------------------------------
		// 'insert_comment_preferences_sql' hook.
		//  - Rewrite or add to the comment preference sql query
		//  - Could be handy for comment/channel restrictions
		//
			if ($this->EE->extensions->active_hook('insert_comment_preferences_sql') === TRUE)
			{
				$sql = $this->EE->extensions->call('insert_comment_preferences_sql', $sql);
				if ($this->EE->extensions->end_script === TRUE) return;
			}
		//
		// -------------------------------------------

		$query = $this->EE->db->query($sql);

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
			return $this->EE->output->show_user_error('submission', $this->EE->lang->line('cmt_comments_not_allowed'));
		}

		/** ----------------------------------------
		/**  Has commenting expired?
		/** ----------------------------------------*/

		$force_moderation = $query->row('comment_moderate');

		if ($this->comment_expiration_mode == 0)
		{
			if ($query->row('comment_expiration_date')  > 0)
			{
				if ($this->EE->localize->now > $query->row('comment_expiration_date') )
				{
					if ($this->EE->config->item('comment_moderation_override') == 'y')
					{
						$force_moderation = 'y';
					}
					else
					{
						return $this->EE->output->show_user_error('submission', $this->EE->lang->line('cmt_commenting_has_expired'));
					}
				}
			}
		}
		else
		{
			if ($query->row('comment_expiration') > 0)
			{
			 	$days = $query->row('entry_date') + ($query->row('comment_expiration') * 86400);

				if ($this->EE->localize->now > $days)
				{
					if ($this->EE->config->item('comment_moderation_override') == 'y')
					{
						$force_moderation = 'y';
					}
					else
					{
						return $this->EE->output->show_user_error('submission', $this->EE->lang->line('cmt_commenting_has_expired'));
					}
				}
			}
		}


		/** ----------------------------------------
		/**  Is there a comment timelock?
		/** ----------------------------------------*/
		if ($query->row('comment_timelock') != '' AND $query->row('comment_timelock') > 0)
		{
			if ($this->EE->session->userdata['group_id'] != 1)
			{
				$time = $this->EE->localize->now - $query->row('comment_timelock') ;

				$this->EE->db->where('comment_date >', $time);
				$this->EE->db->where('ip_address', $this->EE->input->ip_address());
				
				$result = $this->EE->db->count_all_results('comments');

				if ($result  > 0)
				{
					return $this->EE->output->show_user_error('submission', str_replace("%s", $query->row('comment_timelock') , $this->EE->lang->line('cmt_comments_timelock')));
				}
			}
		}

		/** ----------------------------------------
		/**  Do we allow duplicate data?
		/** ----------------------------------------*/
		if ($this->EE->config->item('deny_duplicate_data') == 'y')
		{
			if ($this->EE->session->userdata['group_id'] != 1)
			{
				$this->EE->db->where('comment', $_POST['comment']);
				$result = $this->EE->db->count_all_results('comments');

				if ($result > 0)
				{
					return $this->EE->output->show_user_error('submission', $this->EE->lang->line('cmt_duplicate_comment_warning'));
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
		$comment_total	 	 	= $query->row('comment_total')  + 1;
		$require_membership 	= $query->row('comment_require_membership') ;
		$comment_moderate		= ($this->EE->session->userdata['group_id'] == 1 OR $this->EE->session->userdata['exclude_from_moderation'] == 'y') ? 'n' : $force_moderation;
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

		if ($this->EE->session->userdata('member_id') != 0)
		{
			// If the user is logged in we'll reassign the POST variables with the user data

			 $_POST['name']		= ($this->EE->session->userdata['screen_name'] != '') ? $this->EE->session->userdata['screen_name'] : $this->EE->session->userdata['username'];
			 $_POST['email']	=  $this->EE->session->userdata['email'];
			 $_POST['url']		=  (is_null($this->EE->session->userdata['url'])) ? '' : $this->EE->session->userdata['url'];
			 $_POST['location']	=  (is_null($this->EE->session->userdata['location'])) ? '' : $this->EE->session->userdata['location'];
		}

		/** ----------------------------------------
		/**  Is membership is required to post...
		/** ----------------------------------------*/

		if ($require_membership == 'y')
		{
			// Not logged in

			if ($this->EE->session->userdata('member_id') == 0)
			{
				return $this->EE->output->show_user_error('submission', $this->EE->lang->line('cmt_must_be_member'));
			}

			// Membership is pending

			if ($this->EE->session->userdata['group_id'] == 4)
			{
				return $this->EE->output->show_user_error('general', $this->EE->lang->line('cmt_account_not_active'));
			}

		}
		else
		{
			/** ----------------------------------------
			/**  Missing name?
			/** ----------------------------------------*/

			if ($_POST['name'] == '')
			{
				$error[] = $this->EE->lang->line('cmt_missing_name');
			}

			/** -------------------------------------
			/**  Is name banned?
			/** -------------------------------------*/

			if ($this->EE->session->ban_check('screen_name', $_POST['name']))
			{
				$error[] = $this->EE->lang->line('cmt_name_not_allowed');
			}
			
			// Let's make sure they aren't putting in funky html to bork our screens
			$_POST['name'] = str_replace(array('<', '>'), array('&lt;', '&gt;'), $_POST['name']);			

			/** ----------------------------------------
			/**  Missing or invalid email address
			/** ----------------------------------------*/

			if ($query->row('comment_require_email')  == 'y')
			{
				$this->EE->load->helper('email');

				if ($_POST['email'] == '')
				{
					$error[] = $this->EE->lang->line('cmt_missing_email');
				}
				elseif ( ! valid_email($_POST['email']))
				{
					$error[] = $this->EE->lang->line('cmt_invalid_email');
				}
			}
		}

		/** -------------------------------------
		/**  Is email banned?
		/** -------------------------------------*/

		if ($_POST['email'] != '')
		{
			if ($this->EE->session->ban_check('email', $_POST['email']))
			{
				$error[] = $this->EE->lang->line('cmt_banned_email');
			}
		}

		/** ----------------------------------------
		/**  Is comment too big?
		/** ----------------------------------------*/

		if ($query->row('comment_max_chars')  != '' AND $query->row('comment_max_chars')  != 0)
		{
			if (strlen($_POST['comment']) > $query->row('comment_max_chars') )
			{
				$str = str_replace("%n", strlen($_POST['comment']), $this->EE->lang->line('cmt_too_large'));

				$str = str_replace("%x", $query->row('comment_max_chars') , $str);

				$error[] = $str;
			}
		}

		/** ----------------------------------------
		/**  Do we have errors to display?
		/** ----------------------------------------*/

		if (count($error) > 0)
		{
			return $this->EE->output->show_user_error('submission', $error);
		}

		/** ----------------------------------------
		/**  Do we require CAPTCHA?
		/** ----------------------------------------*/

		if ($query->row('comment_use_captcha')  == 'y')
		{
			if ($this->EE->config->item('captcha_require_members') == 'y'  OR  ($this->EE->config->item('captcha_require_members') == 'n' AND $this->EE->session->userdata('member_id') == 0))
			{
				if ( ! isset($_POST['captcha']) OR $_POST['captcha'] == '')
				{
					return $this->EE->output->show_user_error('submission', $this->EE->lang->line('captcha_required'));
				}
				else
				{
					$this->EE->db->where('word', $_POST['captcha']);
					$this->EE->db->where('ip_address', $this->EE->input->ip_address());
					$this->EE->db->where('date > UNIX_TIMESTAMP()-7200', NULL, FALSE);
					
					$result = $this->EE->db->count_all_results('captcha');

					if ($result == 0)
					{
						return $this->EE->output->show_user_error('submission', $this->EE->lang->line('captcha_incorrect'));
					}

					// @TODO: AR
					$this->EE->db->query("DELETE FROM exp_captcha WHERE (word='".$this->EE->db->escape_str($_POST['captcha'])."' AND ip_address = '".$this->EE->input->ip_address()."') OR date < UNIX_TIMESTAMP()-7200");
				}
			}
		}

		/** ----------------------------------------
		/**  Build the data array
		/** ----------------------------------------*/

		$this->EE->load->helper('url');

		$notify = ($this->EE->input->post('notify_me')) ? 'y' : 'n';

 		$cmtr_name	= $this->EE->input->post('name', TRUE);
 		$cmtr_email	= $this->EE->input->post('email');
 		$cmtr_loc	= $this->EE->input->post('location', TRUE);
 		$cmtr_url	= $this->EE->input->post('url', TRUE);
		$cmtr_url	= prep_url($cmtr_url);

		$data = array(
						'channel_id'	=> $channel_id,
						'entry_id'		=> $_POST['entry_id'],
						'author_id'		=> $this->EE->session->userdata('member_id'),
						'name'			=> $cmtr_name,
						'email'			=> $cmtr_email,
						'url'			=> $cmtr_url,
						'location'		=> $cmtr_loc,
						'comment'		=> $this->EE->security->xss_clean($_POST['comment']),
						'comment_date'	=> $this->EE->localize->now,
						'ip_address'	=> $this->EE->input->ip_address(),
						'status'		=> ($comment_moderate == 'y') ? 'p' : 'o',
						'site_id'		=> $comment_site_id
					 );

		// -------------------------------------------
		// 'insert_comment_insert_array' hook.
		//  - Modify any of the soon to be inserted values
		//
			if ($this->EE->extensions->active_hook('insert_comment_insert_array') === TRUE)
			{
				$data = $this->EE->extensions->call('insert_comment_insert_array', $data);
				if ($this->EE->extensions->end_script === TRUE) return;
			}
		//
		// -------------------------------------------

		$return_link = ( ! stristr($_POST['RET'],'http://') && ! stristr($_POST['RET'],'https://')) ? $this->EE->functions->create_url($_POST['RET']) : $_POST['RET'];

		// Secure Forms check
		if ($this->EE->security->secure_forms_check($this->EE->input->post('XID')) == FALSE)
		{
			$this->EE->functions->redirect(stripslashes($return_link));
		}


		//  Insert data
		$sql = $this->EE->db->insert_string('exp_comments', $data);
		$this->EE->db->query($sql);
		$comment_id = $this->EE->db->insert_id();			
		
		if ($notify == 'y')
		{
			$this->EE->load->library('subscription');
			$this->EE->subscription->init('comment', array('entry_id' => $entry_id), TRUE);

			if ($cmtr_id = $this->EE->session->userdata('member_id'))
			{
				$this->EE->subscription->subscribe($cmtr_id);
			}
			else
			{
				$this->EE->subscription->subscribe($cmtr_email);
			}
		}
		

		if ($comment_moderate == 'n')
		{
			/** ------------------------------------------------
			/**  Update comment total and "recent comment" date
			/** ------------------------------------------------*/

			$this->EE->db->set('comment_total', $comment_total);
			$this->EE->db->set('recent_comment_date', $this->EE->localize->now);
			$this->EE->db->where('entry_id', $_POST['entry_id']);
			
			$this->EE->db->update('channel_titles');

			/** ----------------------------------------
			/**  Update member comment total and date
			/** ----------------------------------------*/

			if ($this->EE->session->userdata('member_id') != 0)
			{
				$this->EE->db->select('total_comments');
				$this->EE->db->where('member_id', $this->EE->session->userdata('member_id'));
				
				$query = $this->EE->db->get('members');
				
				$this->EE->db->set('total_comments', $query->row('total_comments') + 1);
				$this->EE->db->set('last_comment_date', $this->EE->localize->now);
				$this->EE->db->where('member_id', $this->EE->session->userdata('member_id'));
				
				$this->EE->db->update('members');
			}

			/** ----------------------------------------
			/**  Update comment stats
			/** ----------------------------------------*/
		
			$this->EE->stats->update_comment_stats($channel_id, $this->EE->localize->now);

			/** ----------------------------------------
			/**  Fetch email notification addresses
			/** ----------------------------------------*/
			
			$this->EE->load->library('subscription');
			$this->EE->subscription->init('comment', array('entry_id' => $entry_id), TRUE);
			
			// Remove the current user
			$ignore = ($this->EE->session->userdata('member_id') != 0) ? $this->EE->session->userdata('member_id') : $this->EE->input->post('email');
			
			// Grab them all
			$subscriptions = $this->EE->subscription->get_subscriptions($ignore);
			$this->EE->load->model('comment_model');
			$recipients = $this->EE->comment_model->fetch_email_recipients($_POST['entry_id'], $subscriptions);
		}

		/** ----------------------------------------
		/**  Fetch Author Notification
		/** ----------------------------------------*/

		if ($author_notify == 'y')
		{
			$this->EE->db->select('email');
			$this->EE->db->where('member_id', $author_id);

			$result = $this->EE->db->get('members');

			$notify_address	.= ','.$result->row('email');
		}

		/** ----------------------------------------
		/**  Instantiate Typography class
		/** ----------------------------------------*/

		$this->EE->load->library('typography');
		$this->EE->typography->initialize(array(
				'parse_images'		=> FALSE,
				'allow_headings'	=> FALSE,
				'smileys'			=> FALSE,
				'word_censor'		=> ($this->EE->config->item('comment_word_censoring') == 'y') ? TRUE : FALSE)
				);

		$comment = $this->EE->security->xss_clean($_POST['comment']);
		$comment = $this->EE->typography->parse_type( $comment,
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
			$cp_url = $this->EE->config->item('cp_url').'?S=0&D=cp&C=addons_modules&M=show_module_cp&module=comment';


			
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
							'comment_url'		=> $this->EE->functions->remove_double_slashes($this->EE->input->remove_session_id($this->EE->functions->fetch_site_index().'/'.$_POST['URI'])),
							'delete_link'		=> $cp_url.'&method=delete_comment_confirm&comment_id='.$comment_id, 
							'approve_link'		=> $cp_url.'&method=change_comment_status&comment_id='.$comment_id.'&status=o', 
							'close_link'		=> $cp_url.'&method=change_comment_status&comment_id='.$comment_id.'&status=c', 
							'channel_id'		=> $channel_id,
							'entry_id'			=> $entry_id,
							'url_title'			=> $url_title,
							'comment_url_title_auto_path' => $comment_url_title_auto_path
						 );

			$template = $this->EE->functions->fetch_email_template('admin_notify_comment');

			$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
			$email_msg = $this->EE->functions->var_swap($template['data'], $swap);

			// We don't want to send an admin notification if the person
			// leaving the comment is an admin in the notification list

			if ($_POST['email'] != '')
			{
				if (strpos($notify_address, $_POST['email']) !== FALSE)
				{
					$notify_address = str_replace($_POST['email'], '', $notify_address);
				}
			}

			$this->EE->load->helper('string');
			// Remove multiple commas
			$notify_address = reduce_multiples($notify_address, ',', TRUE);

			if ($notify_address != '')
			{
				/** ----------------------------
				/**  Send email
				/** ----------------------------*/

				$this->EE->load->library('email');

				$replyto = ($data['email'] == '') ? $this->EE->config->item('webmaster_email') : $data['email'];
					 
				$sent = array();

				// Load the text helper
				$this->EE->load->helper('text');

				foreach (explode(',', $notify_address) as $addy)
				{
					if (in_array($addy, $sent)) continue;

					$this->EE->email->EE_initialize();
					$this->EE->email->wordwrap = false;
					$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
					$this->EE->email->to($addy);
					$this->EE->email->reply_to($replyto);
					$this->EE->email->subject($email_tit);
					$this->EE->email->message(entities_to_ascii($email_msg));
					$this->EE->email->send();

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
				$action_id  = $this->EE->functions->fetch_action_id('Comment_mcp', 'delete_comment_notification');

				$swap = array(
								'name_of_commenter'	=> $cmtr_name,
								'channel_name'		=> $channel_title,
								'entry_title'		=> $entry_title,
								'site_name'			=> stripslashes($this->EE->config->item('site_name')),
								'site_url'			=> $this->EE->config->item('site_url'),
								'comment_url'		=> $this->EE->functions->remove_double_slashes($this->EE->input->remove_session_id($this->EE->functions->fetch_site_index().'/'.$_POST['URI'])),
								'comment_id'		=> $comment_id,
								'comment'			=> $comment,
								'channel_id'		=> $channel_id,
								'entry_id'			=> $entry_id,
								'url_title'			=> $url_title,
								'comment_url_title_auto_path' => $comment_url_title_auto_path 
							 );


				$template = $this->EE->functions->fetch_email_template('comment_notification');
				$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
				$email_msg = $this->EE->functions->var_swap($template['data'], $swap);

				/** ----------------------------
				/**  Send email
				/** ----------------------------*/

				$this->EE->load->library('email');
				$this->EE->email->wordwrap = true;

				$cur_email = ($_POST['email'] == '') ? FALSE : $_POST['email'];

				if ( ! isset($sent)) $sent = array();

				// Load the text helper
				$this->EE->load->helper('text');

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

						$title	 = str_replace('{notification_removal_url}', $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&'.$sub_qs, $title);
						$message = str_replace('{notification_removal_url}', $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&'.$sub_qs, $message);

						$this->EE->email->EE_initialize();
						$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
						$this->EE->email->to($val['0']);
						$this->EE->email->subject($title);
						$this->EE->email->message(entities_to_ascii($message));
						$this->EE->email->send();

						$sent[] = $val['0'];
					}
				}
			}

			/** ----------------------------------------
			/**  Clear cache files
			/** ----------------------------------------*/

			$this->EE->functions->clear_caching('all', $this->EE->functions->fetch_site_index().$_POST['URI']);

			// clear out the entry_id version if the url_title is in the URI, and vice versa
			if (preg_match("#\/".preg_quote($url_title)."\/#", $_POST['URI'], $matches))
			{
				$this->EE->functions->clear_caching('all', $this->EE->functions->fetch_site_index().preg_replace("#".preg_quote($matches['0'])."#", "/{$data['entry_id']}/", $_POST['URI']));
			}
			else
			{
				$this->EE->functions->clear_caching('all', $this->EE->functions->fetch_site_index().preg_replace("#{$data['entry_id']}#", $url_title, $_POST['URI']));
			}
		}

		/** ----------------------------------------
		/**  Set cookies
		/** ----------------------------------------*/

		if ($notify == 'y')
		{
			$this->EE->functions->set_cookie('notify_me', 'yes', 60*60*24*365);
		}
		else
		{
			$this->EE->functions->set_cookie('notify_me', 'no', 60*60*24*365);
		}

		if ($this->EE->input->post('save_info'))
		{
			$this->EE->functions->set_cookie('save_info',	'yes',				60*60*24*365);
			$this->EE->functions->set_cookie('my_name',		$_POST['name'],		60*60*24*365);
			$this->EE->functions->set_cookie('my_email',	$_POST['email'],	60*60*24*365);
			$this->EE->functions->set_cookie('my_url',		$_POST['url'],		60*60*24*365);
			$this->EE->functions->set_cookie('my_location',	$_POST['location'],	60*60*24*365);
		}
		else
		{
			$this->EE->functions->set_cookie('save_info',	'no', 60*60*24*365);
			$this->EE->functions->set_cookie('my_name',		'');
			$this->EE->functions->set_cookie('my_email',	'');
			$this->EE->functions->set_cookie('my_url',		'');
			$this->EE->functions->set_cookie('my_location',	'');
		}

		// -------------------------------------------
		// 'insert_comment_end' hook.
		//  - More emails, more processing, different redirect
		//  - $comment_id added in 1.6.1
		//
			$edata = $this->EE->extensions->call('insert_comment_end', $data, $comment_moderate, $comment_id);
			if ($this->EE->extensions->end_script === TRUE) return;
		//
		// -------------------------------------------

		/** -------------------------------------------
		/**  Bounce user back to the comment page
		/** -------------------------------------------*/

		if ($comment_moderate == 'y')
		{
			$data = array(	'title' 	=> $this->EE->lang->line('cmt_comment_accepted'),
							'heading'	=> $this->EE->lang->line('thank_you'),
							'content'	=> $this->EE->lang->line('cmt_will_be_reviewed'),
							'redirect'	=> $return_link,
							'link'		=> array($return_link, $this->EE->lang->line('cmt_return_to_comments')),
							'rate'		=> 3
						 );

			$this->EE->output->show_message($data);
		}
		else
		{
			$this->EE->functions->redirect($return_link);
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
		if ($this->EE->session->userdata('member_id') == 0)
		{
			return;
		}
		
		$entry_id = FALSE;
		$qstring = $this->EE->uri->query_string;
		
		if (preg_match("#(^|/)P(\d+)(/|$)#", $qstring, $match))
		{
			$qstring = trim($this->EE->functions->remove_double_slashes(str_replace($match['0'], '/', $qstring)), '/');
		}

		// Figure out the right entry ID
		// If there is a slash in the entry ID we'll kill everything after it.
		$entry_seg = trim($qstring); 
		$entry_seg= preg_replace("#/.+#", "", $entry_seg);

		if (is_numeric($entry_seg))
		{
			$entry_id = $entry_seg;
		}
		else
		{
			$this->EE->db->select('entry_id');
			$query = $this->EE->db->get_where('channel_titles', array('url_title' => $entry_seg));
			
			if ($query->num_rows() == 1)
			{
   				$row = $query->row();
  				$entry_id = $row->entry_id;
			}
		}

		// entry_id is required
		if ( ! $entry_id)
		{
			return;
		}
		
				
		$this->EE->load->library('subscription');
		$this->EE->subscription->init('comment', array('entry_id' => $entry_id), TRUE);
		$subscribed = $this->EE->subscription->is_subscribed(FALSE);


		$action_id  = $this->EE->functions->fetch_action_id('Comment', 'comment_subscribe');

		// Bleh- really need a conditional for if they are subscribed

		$sub_link = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&entry_id='.$entry_id.'&ret='.$this->EE->uri->uri_string();
		$unsub_link = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&entry_id='.$entry_id.'&type=unsubscribe'.'&ret='. $this->EE->uri->uri_string();
 		
		$data[] = array('subscribe_link' => $sub_link, 'unsubscribe_link' => $unsub_link, 'subscribed' => $subscribed);
		
		$tagdata = $this->EE->TMPL->tagdata;
		return $this->EE->TMPL->parse_variables($tagdata, $data); 		
		
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
		if ($this->EE->session->userdata('member_id') == 0)
		{
			return;
		}

		$id		= $this->EE->input->get('entry_id');
		$type	= ($this->EE->input->get('type')) ? 'unsubscribe' : 'subscribe';
		$ret	= $this->EE->input->get('ret');

		if ( ! $id)
		{
			return;
		}

		$this->EE->lang->loadfile('comment');
		
		// Does entry exist?
		
		$this->EE->db->select('title');
		$query = $this->EE->db->get_where('channel_titles', array('entry_id' => $id));
			
		if ($query->num_rows() != 1)
		{
 			return $this->EE->output->show_user_error('submission', 'invalid_subscription');
		}

  		$row = $query->row();
  		$entry_title = $row->title;

		// Are they currently subscribed
		
		$this->EE->load->library('subscription');
		$this->EE->subscription->init('comment', array('entry_id' => $id), TRUE);
		$subscribed = $this->EE->subscription->is_subscribed(FALSE);	
		
		if ($type == 'subscribe' && $subscribed == TRUE)
		{
			return $this->EE->output->show_user_error('submission', $this->EE->lang->line('already_subscribed'));
		}	
		
		if ($type == 'unsubscribe' && $subscribed == FALSE)
		{
			return $this->EE->output->show_user_error('submission', $this->EE->lang->line('not_currently_subscribed'));
		}

		// They check out- let them through
		$this->EE->subscription->$type();

		// Show success message
		
		$this->EE->lang->loadfile('comment');
		
		$title = ($type == 'unsubscribe') ? 'cmt_unsubscribe' : 'cmt_subscribe';
		$content = ($type == 'unsubscribe') ? 'you_have_been_unsubscribed' : 'you_have_been_subscribed';
		
		
		$return_link = $this->EE->functions->create_url($ret);
		

		$data = array(	'title' 	=> $this->EE->lang->line($title),
						'heading'	=> $this->EE->lang->line('thank_you'),
						'content'	=> $this->EE->lang->line($content).' '.$entry_title,
						'redirect'	=> $return_link,
						'link'		=> array($return_link, $this->EE->lang->line('cmt_return_to_comments')),
						'rate'		=> 3
					 );		

			$this->EE->output->show_message($data);
		
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
		
		$unauthorized = $this->EE->lang->line('not_authorized');

		if ($this->EE->input->get_post('comment_id') === FALSE OR (($this->EE->input->get_post('comment') === FALSE OR $this->EE->input->get_post('comment') == '') && $this->EE->input->get_post('status') != 'close'))
		{
			$this->EE->output->send_ajax_response(array('error' => $unauthorized));
		}
		
		// Not logged in member- eject
		if ($this->EE->session->userdata['member_id'] == '0')
		{
			$this->EE->output->send_ajax_response(array('error' => $unauthorized));
		}
		
		$xid = $this->EE->input->get_post('XID');
		
		
		// Secure Forms check - do it early due to amount of further data manipulation before insert
		if ($this->EE->security->check_xid($xid) == FALSE) 
		{ 
		 	$this->EE->output->send_ajax_response(array('error' => $unauthorized));
		}
		
		$edited_status = ($this->EE->input->get_post('status') != 'close') ? FALSE : 'c';
		$edited_comment = $this->EE->input->get_post('comment');
		$can_edit = FALSE;
		$can_moderate = FALSE;
				
		$this->EE->db->from('comments');
		$this->EE->db->from('channels');
		$this->EE->db->from('channel_titles');
		$this->EE->db->select('comments.author_id, comments.comment_date, channel_titles.author_id AS entry_author_id, channels.comment_text_formatting, channels.comment_html_formatting, channels.comment_allow_img_urls, channels.comment_auto_link_urls');
		$this->EE->db->where('comment_id', $this->EE->input->get_post('comment_id'));
		$this->EE->db->where('comments.channel_id = '.$this->EE->db->dbprefix('channels').'.channel_id');
		$this->EE->db->where('comments.entry_id = '.$this->EE->db->dbprefix('channel_titles').'.entry_id');
		$query = $this->EE->db->get();
		
		if ($query->num_rows() > 0)
		{
			if ($this->EE->session->userdata['group_id'] == 1 OR 
				$this->EE->session->userdata['can_edit_all_comments'] == 'y' OR 
				($this->EE->session->userdata['can_edit_own_comments'] == 'y' && $query->row('entry_author_id') == $this->EE->session->userdata['member_id']))
				{
					$can_edit = TRUE;
					$can_moderate = TRUE;								
				}
				elseif ($this->EE->session->userdata['member_id'] != '0'  && $query->row('author_id') == $this->EE->session->userdata['member_id'])
				{
					// Check for time limit
					if ($this->EE->config->item('comment_edit_time_limit') > 0)
					{
						if ($query->row('comment_date') > $this->EE->localize->now - 60*$this->EE->config->item('edit_time_limit'))
						{
							$can_edit = TRUE;
						}
					}
					else
					{
						$can_edit = TRUE;
					}
				}

			$data = array();

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
				//  Clear security hash
				$this->EE->security->delete_xid($xid);

				$this->EE->db->where('comment_id', $this->EE->input->get_post('comment_id'));
				$this->EE->db->update('comments', $data); 
					
				if ($edited_status != FALSE & $can_moderate != FALSE)
				{
					// create new security hash and send it back with updated comment.
				
					$new_hash = $this->_new_hash();

					$this->EE->output->send_ajax_response(array('moderated' => $this->EE->lang->line('closed'), 'XID' => $new_hash));
				}				

				$this->EE->load->library('typography'); 
				
				$f_comment = $this->EE->typography->parse_type(stripslashes($this->EE->input->get_post('comment')), 
										   array(
													'text_format'   => $query->row('comment_text_formatting'),
													'html_format'   => $query->row('comment_html_formatting'),
													'auto_links'    => $query->row('comment_auto_link_urls'),
													'allow_img_url' => $query->row('comment_allow_img_urls')
												));
												
				// create new security hash and send it back with updated comment.
				
				$new_hash = $this->_new_hash();

				$this->EE->output->send_ajax_response(array('comment' => $f_comment, 'XID' => $new_hash));
			}
		}

		$this->EE->output->send_ajax_response(array('error' => $unauthorized));
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
		$src = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT=comment_editor';
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

		$script = $this->EE->functions->add_form_security_hash($script);
		$script = $this->EE->functions->insert_action_ids($script);
		
		$this->EE->output->enable_profiler(FALSE);		
		$this->EE->output->set_header("Content-Type: text/javascript");

		if ($this->EE->config->item('send_headers') == 'y')
		{
			$this->EE->output->send_cache_headers(strtotime(APP_BUILD));
			$this->EE->output->set_header('Content-Length: '.strlen($script));
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
		if ($this->EE->config->item('secure_forms') != 'y')
		{
			return FALSE;
		}
		
		$db_reset = FALSE;

		if ($this->EE->db->cache_on == TRUE)
		{
			$this->EE->db->cache_off();
			$db_reset = TRUE;
		}

		$this->EE->load->helper('string');
		$hash = random_string('encrypt', 8);

		/*
		$data = array(
               'date' => UNIX_TIMESTAMP(),
               'ip_address' => $this->EE->input->ip_address(),
               'hash' => $hash
            );

		$this->EE->db->insert('security_hashes', $data); 
		*/
		
		$this->EE->db->query("INSERT INTO exp_security_hashes (date, ip_address, hash) VALUES (UNIX_TIMESTAMP(), '".$this->EE->input->ip_address()."', '".$this->EE->db->escape_str($hash)."')");

		// Re-enable DB caching
				
		if ($db_reset == TRUE)
		{
			$this->EE->db->cache_on();			
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
		$url = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->EE->functions->fetch_action_id('Comment', 'edit_comment');
		
		return $url;
	}
	
	// --------------------------------------------------------------------
	
}
// END CLASS

/* End of file mod.comment.php */
/* Location: ./system/expressionengine/modules/comment/mod.comment.php */
