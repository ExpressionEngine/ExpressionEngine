<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine Discussion Forum Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class Forum_Core extends Forum {

	/**
	 * Construct
	 */
	function Forum_Core()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Display forum handler
	 *
	 * @param 	string
	 */
	function _display_forum($function = '')
	{
		// Determine the function call
		// The function is based on the 2nd segment of the URI
		if ($function == '')
		{
			if ( ! $this->EE->uri->segment(2+$this->seg_addition))
			{
				$function = 'forum_homepage';
			}
			else
			{
				$function = $this->EE->uri->segment(2+$this->seg_addition);
			}
		}
		
		// Remap function if needed
		// In certain cases we may want different URI function names
		// to share common methods
		
		$remap = array(
						'viewpost'				=> 'view_post_redirect',
						'viewreply'				=> 'view_post_redirect',
						'viewcategory'			=> 'category_page',
						'viewforum'				=> 'topic_page',
						'viewthread'			=> 'thread_page',
						'viewannounce'			=> 'announcement_page',
						'newtopic'				=> 'new_topic_page',
						'newreply'				=> 'new_reply_page',
						'edittopic'				=> 'edit_topic_page',
						'editreply'				=> 'edit_reply_page',
						'deletetopic'			=> 'delete_post_page',
						'deletereply'			=> 'delete_post_page',
						'movetopic'				=> 'move_topic_page',
						'movereply'				=> 'move_reply_page',
						'quotetopic'			=> 'new_reply_page',
						'quotereply'			=> 'new_reply_page',
						'reporttopic'			=> 'report_page',
						'reportreply'			=> 'report_page',
						'merge'					=> 'merge_page',
						'split'					=> 'split_page',
						'smileys'				=> 'emoticon_page',
						'search'				=> 'advanced_search_page',
						'member_search'			=> 'member_search',
						'new_topic_search'		=> 'new_topic_search',
						'active_topic_search'	=> 'active_topic_search',
						'view_pending_topics'	=> 'view_pending_topics',
						'search_results'		=> 'search_results_page',
						'search_thread'			=> 'search_thread_page',
						'ban_member'			=> 'ban_member_form',
						'do_ban_member'			=> 'do_ban_member',
						'rss'					=> '_feed_builder',
						'atom'					=> '_feed_builder'						
					  );		
		
		if (isset($remap[$function]))
		{
			$function = $remap[$function];
		}
		
		/*  
			The output is based on whether we are using the main template parser or not.
			If the config.php file contains a forum "triggering" word we'll send
			the output directly to the output class.  Otherwise, the output
			is sent to the template class like normal.  The exception to this is
			when action requests are processed
		*/
				
		if ($this->_use_trigger() OR $this->EE->input->get_post('ACT') !== FALSE)
		{ 
			$this->EE->output->set_output(
				$this->EE->functions->insert_action_ids(
					$this->EE->functions->add_form_security_hash(
						$this->_final_prep(
							$this->_include_recursive($function)
									))));
		}
		else
		{
			$this->EE->TMPL->disable_caching = TRUE;
			
			if (stristr($this->EE->TMPL->tagproper, 'exp:forum:') === FALSE)
			{
				$this->return_data = $this->EE->TMPL->simple_conditionals($this->_include_recursive($function), $this->EE->config->_global_vars);

				// Parse Snippets
				foreach ($this->EE->config->_global_vars as $key => $val)
				{
					$this->return_data = str_replace(LD.$key.RD, $val, $this->return_data); 
				}
			
				// Parse Global Variables
				foreach ($this->EE->TMPL->global_vars as $key => $val)
				{
					$this->return_data = str_replace(LD.$key.RD, $val, $this->return_data); 
				}

				$this->return_data = $this->_final_prep($this->return_data);
			}
		}
	}

	// --------------------------------------------------------------------


	/**
	 * Recursively Fetch Template Elements
	 *
	 * Note:  A "template element" refers to an HTML component used to build the forum (header, breadcrumb, footer, etc.).
	 * Each "template element" corresponds to a particular function in one of the theme files.
	 *
	 * This function allows any template element to be embedded within any other template element.
	 * Template elements can contain "include variables" which call other template elements.
	 * The include variables look like this: {include:function_name}
	 *
	 * If an include is found, this function loads that element and recursively looks for 
	 * additional includes.  
	 *
	 * In some cases, template elements need to be processed rather than simply returned.
	 * If we need to process the include, THIS file will contain a function named
	 * exactly the same as the template element which will be called.  If the function does 
	 * not exist we return the pure data.
	 *
	 * Right now there is no safety to prevent a run-away loop if an include is put within itself.
	 *
	 * @param
	 */
	function _include_recursive($function)
	{ 
		if ($this->return_data == '' AND $this->trigger_error_page === TRUE)
		{
			$function = 'error_page';
		}
			
		$element = ( ! method_exists($this, $function)) ? $this->_load_element($function) : $this->$function();

		if ($this->return_data == '')
		{
			$this->return_data = $element;
		}
		else
		{
			$this->return_data = str_replace('{include:'.$function.'}', $element, $this->return_data);
		}
			
			if (preg_match_all("/{include:(.+?)\}/i", $this->return_data, $matches))
			{	
			for ($j = 0; $j < count($matches['0']); $j++)
			{	
				if ( ! in_array($matches['1'][$j], $this->include_exceptions))
				{
					return $this->return_data = str_replace($matches['0'][$j], $this->_include_recursive($matches['1'][$j]), $this->return_data);
				}
			}
		}		
				
		return $this->return_data;
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch Forum Moderators
	 */
	function _load_moderators()
	{
		if ($this->moderators === FALSE)
		{
			return;
		}
		
		if (count($this->moderators) > 0)
		{
			return;
		}
		
		$this->EE->db->select('group_id, group_title');
		$g_query = $this->EE->db->get_where('member_groups', 
											array('site_id' => $this->EE->config->item('site_id'))
										);
		
		foreach ($g_query->result_array() as $row)
		{
			$groups[$row['group_id']] = $row['group_title'];
		}

		$this->EE->db->select('mod_forum_id, mod_member_id, mod_group_id, mod_member_name');
		$m_query = $this->EE->db->get_where('forum_moderators', 
											array('board_id' => $this->_fetch_pref('board_id'))
										);
		
		if ($m_query->num_rows() == 0)
		{
			$this->moderators = FALSE;
		}
		
		foreach ($m_query->result_array() as $row)
		{	
			$this->moderators[$row['mod_forum_id']][] = array(
																'mod_member_id' 	=> $row['mod_member_id'], 
																'mod_member_name'	=> $row['mod_member_name'],
																'mod_group_id' 		=> $row['mod_group_id'], 
																'mod_group_name' 	=> (isset($groups[$row['mod_group_id']])) ? $groups[$row['mod_group_id']] : ''
															);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Instantiate Member Profile Class
	 */
	function _load_member_class()
	{
		return parent::_load_member_class();
		return $template;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Fetch Forum Name/Description
	 *
	 * This, and the next two functions let us gather often needed info,
	 * like the forum name, the forum permissions, etc.
	 * Since several forum sub-systems require this info we'll cache it
	 * in a class variable.  Subsequent calls will be served from cache.
	 * The three meta-data functions fetch pretty much the same info, but
	 * the queries are constructed a little different to permit the info
	 * to be fetched with different data in the URL.
	 *
	 * @param 	int
	 */
	function _fetch_forum_metadata($id)
	{
		if (isset($this->EE->TMPL) && is_object($this->EE->TMPL) && ($forums = $this->EE->TMPL->fetch_param('forums')) != FALSE)
		{
			if (substr($forums, 0, 4) == 'not ')
			{
				$x = explode('|', trim(substr($forums, 3)));
				if (in_array($id, $x)) return FALSE;
			}
			else
			{
				$x = explode('|', trim($forums));
				if ( ! in_array($id, $x)) return FALSE;
			}
		}
				
		if (isset($this->forum_metadata[$id]))
		{
			return $this->forum_metadata;
		}
		
		$items = array('forum_id', 'forum_name', 'forum_status', 'forum_description', 'forum_parent', 'forum_permissions', 'forum_enable_rss', 'forum_is_cat', 'forum_max_post_chars', 'forum_allow_img_urls', 'forum_notify_emails', 'forum_notify_emails_topics', 'forum_notify_moderators_topics', 'forum_notify_moderators_replies');
	
		$this->EE->db->select('forum_id, forum_name, forum_status, forum_description, 
								forum_parent, forum_enable_rss, forum_permissions, 
								forum_is_cat, forum_max_post_chars, forum_allow_img_urls, 
								forum_notify_emails, forum_notify_emails_topics,
							 	forum_notify_moderators_topics, forum_notify_moderators_replies');
		$this->EE->db->where('forum_id', $id);
		$this->EE->db->where('board_id', $this->_fetch_pref('board_id'));
		$query = $this->EE->db->get('forums');
		
		if ($query->num_rows() == 0)
		{
			return FALSE;
		}	
		
		$row = $query->row_array();
		
		foreach ($items as $val)
		{
			if ($val == 'forum_max_post_chars')
			{
				if ($row[$val] == 0)
				{
					$row[$val] = $this->max_chars;
				}
			}
		
			$this->forum_metadata[$id][$val] = $row[$val];
		}
				
		return $this->forum_metadata;	
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch Forum Name, etc
	 * This function is identical to the one above except 
	 * that the query is run based on a topic ID rather than
	 * a forum ID
	 *
	 */
	function _fetch_topic_metadata($id)
	{
		if (isset($this->topic_metadata[$id]))
		{
			return $this->topic_metadata;
		}
		
		$items = array('forum_id', 'forum_status', 'forum_name', 'forum_parent', 'forum_description', 'forum_permissions', 'forum_enable_rss', 'forum_is_cat', 'forum_notify_emails', 'forum_notify_emails_topics', 'forum_notify_moderators_topics', 'forum_notify_moderators_replies', 'forum_posts_perpage', 'forum_allow_img_urls', 'forum_max_post_chars', 'topic_id', 'author_id', 'status', 'sticky', 'announcement', 'title', 'body', 'topic_date', 'screen_name');
		
		$this->EE->db->select('f.forum_id, f.forum_status, f.forum_name, f.forum_parent, 
								f.forum_description, f.forum_permissions, f.forum_enable_rss, 
								f.forum_is_cat, f.forum_notify_emails, f.forum_notify_emails_topics, 
								f.forum_notify_moderators_topics, f.forum_notify_moderators_replies, 
								f.forum_allow_img_urls, f.forum_posts_perpage, f.forum_max_post_chars, 
								t.author_id, t.status, t.sticky, t.announcement, t.title, t.body, 
								t.topic_id, t.topic_date, m.screen_name');
		$this->EE->db->from(array('forums f', 'forum_topics t', 'members m'));
		$this->EE->db->where('f.forum_id', 't.forum_id', FALSE);
		$this->EE->db->where('t.author_id', 'm.member_id', FALSE);
		$this->EE->db->where('t.topic_id', $id);
		$this->EE->db->where('t.board_id', $this->_fetch_pref('board_id'));
		$query = $this->EE->db->get();
	
		if ($query->num_rows() == 0)
		{
			return FALSE;
		}	

		$row = $query->row_array();
		
		foreach ($items as $val)
		{
			if ($val == 'forum_max_post_chars')
			{
				if ($row[$val] == 0)
				{
					$row[$val] = $this->max_chars;
				}
			}
		
			$this->topic_metadata[$id][$val] = $row[$val];
		}
				
		return $this->topic_metadata;	
	}

	// --------------------------------------------------------------------
	
	// This function is identical to the one above except 
	// that the query is run based on a POST ID rather than
	// a topic or forum ID
	
	/** -------------------------------------
	/**  Fetch Post Meta Data
	/** -------------------------------------*/
	function _fetch_post_metadata($id)
	{
		if (isset($this->post_metadata[$id]))
		{
			return $this->post_metadata;
		}
		
		$items = array('forum_id', 'forum_status', 'forum_name', 'forum_parent', 'forum_description', 'forum_permissions', 'forum_enable_rss', 'forum_is_cat', 'forum_posts_perpage', 'forum_post_order', 'forum_max_post_chars', 'forum_allow_img_urls', 'author_id', 'title', 'status', 'topic_id', 'post_id', 'body', 'post_date', 'screen_name');
	 
		$this->EE->db->select('f.forum_id, f.forum_status, f.forum_name, f.forum_parent, 
								f.forum_description, f.forum_permissions, f.forum_enable_rss, 
								f.forum_is_cat, f.forum_posts_perpage, f.forum_post_order, 
								f.forum_max_post_chars, f.forum_allow_img_urls, t.title, 
								t.status, p.author_id, p.topic_id, p.post_id, p.body, 
								p.post_date, m.screen_name');
		$this->EE->db->from(array('forums f', 'forum_topics t', 'forum_posts p', 'members m'));
		$this->EE->db->where('f.forum_id', 'p.forum_id', FALSE);
		$this->EE->db->where('p.topic_id', 't.topic_id', FALSE);
		$this->EE->db->where('p.author_id', 'm.member_id', FALSE);
		$this->EE->db->where('p.post_id', $id);
		$this->EE->db->where('p.board_id', $this->_fetch_pref('board_id'));
		$query = $this->EE->db->get();
		
		if ($query->num_rows() == 0)
		{
			return FALSE;
		}	

		$row = $query->row_array();
		
		foreach ($items as $val)
		{
			if ($val == 'forum_max_post_chars')
			{
				if ($row[$val] == 0)
				{
					$row[$val] = $this->max_chars;
				}
			}

			$this->post_metadata[$id][$val] = $row[$val];
		}
				
		return $this->post_metadata;	
	}

	// --------------------------------------------------------------------	
	
	/**
	 * Topic Tracker
	 */
	function _fetch_read_topics($new_id = FALSE)
	{
			// If the person is not logged in we use the cookie version
			if ($this->EE->session->userdata('member_id') == 0)
			{
				return $this->_fetch_read_topics_cookie($new_id);
			}
	
			$query = $this->EE->db->query("SELECT topics FROM exp_forum_read_topics 
								 WHERE member_id = '".$this->EE->db->escape_str($this->EE->session->userdata('member_id'))."' 
								 AND board_id = '".$this->_fetch_pref('board_id')."'");
	
			// If there isn't a row yet we'll fetch the cookie version
			if ($query->num_rows() == 0)
			{
				$this->read_topics_exist = FALSE;
				$read_topics = $this->_fetch_read_topics_cookie($new_id);
				
				if (count($read_topics) > 0)
				{
				$this->read_topics_exist = TRUE;
				$this->EE->db->query("INSERT INTO exp_forum_read_topics (member_id, board_id, topics, last_visit) 
							VALUES ('".$this->EE->db->escape_str($this->EE->session->userdata('member_id'))."', '".$this->_fetch_pref('board_id')."', '".serialize($read_topics)."', '".$this->EE->localize->now."')");
				}
				
				return $read_topics;
			}
	
		$this->read_topics_exist = TRUE;
		$length = strlen($query->row('topics') );		
		$topics = @unserialize(stripslashes($query->row('topics') ));
		
		if ( ! is_array($topics))
		{
			return array();
		}
		
		if ($new_id === FALSE)
		{
			if (count($topics) == 0)
			{
				return array();
			}
			else
			{
				return $topics;
			}
		}
	
			// We don't want the array to get too big
			// so we'll bump off the oldest couple items
			// if they exceed the alloted size.
		// Note: Since the various array functions like array_shift 
		// reset the array keys, we'll instead have to loop through 
		// to preserve them since they contain the topic ID
		unset($topics[$new_id]);
		$i = 0;
		foreach ($topics as $key => $val)
		{
			if ($length > 20000 AND $i < 2)
			{
				$i++;
				continue;
			}
			
			$new[$key] = $val;
			$i++;
		}			
		$new[$new_id] = $this->EE->localize->now+3;

		return $new;
	}	

	// --------------------------------------------------------------------	
	
	/**
	 * Fetch Tracker Cookie
	 */
	function _fetch_read_topics_cookie($new_id = FALSE)
	{
		if ( ! $this->EE->input->cookie('forum_topics'))
		{ 
			return array();
		}		
		
		$cookie = $this->EE->input->cookie('forum_topics');
		$length = strlen($cookie);		
		$topics = @unserialize(stripslashes($cookie));
		
		if ( ! is_array($topics))
		{
			return array();
		}
		
		if ($new_id === FALSE)
		{
			if (count($topics) == 0)
			{
				return array();
			}
			else
			{
				return $topics;
			}
		}
		
			// We don't want the array to get too big
			// so we'll bump off the oldest couple items
			// if they exceed the alloted size (4K of data per the cookie spec). 
		// Note: Since the various array functions like array_shift 
		// reset the array keys, we'll instead have to loop through 
		// to preserve them since they contain the topic ID
		unset($topics[$new_id]);
		$i = 0;
		foreach ($topics as $key => $val)
		{
			if ($length > 3800 AND $i < 2)
			{
				$i++;
				continue;
			}
		
			$new[$key] = $val;
			$i++;
		}			
		$new[$new_id] = $this->EE->localize->now+3;
		
		return $new;
	}

	// --------------------------------------------------------------------

	/**
	 * Final Quote Parsing
	 */
	function _quote_decode($str)
	{
		$xtemplate = $this->_load_element('quoted_author');

		if (stristr($str, '<blockquote') === FALSE OR trim($xtemplate) == '')
		{
			return $str;
		}

		$date = FALSE;

		if (preg_match_all("/\<blockquote\s+(.*?)\>/", $str, $matches))
		{
			if (preg_match("/{quote_date\s+format=['|\"](.+?)['|\"]\}/i", $xtemplate, $dates))
			{
				$date = TRUE;
			}

			for($i=0, $s = count($matches['0']); $i < $s; ++$i)
			{
				// author, date parameters
				// We do the str_replace because of the XHTML Typography that converts quotes
				$tagparams = $this->EE->functions->assign_parameters(trim(str_replace(array('&#8220;', '&#8221;'), '"', $matches['1'][$i])));
				
				$author	= ( ! isset($tagparams['author'])) ? '' : $tagparams['author'];
				$time	= ( ! isset($tagparams['date'])) ? '' : $tagparams['date'];
				
				$template = str_replace('{quote_author}', $author, $xtemplate);
				
				if ($date === TRUE)
				{
					$template = str_replace($dates['0'], $this->EE->localize->decode_date($dates['1'], $time), $template);
				}
				
				$str = str_replace($matches['0'][$i], '<blockquote>'.$template, $str);
			}
		}

		return $str;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Is the user authorized for the page?
	 */
	function _is_authorized()
	{
		if ($this->current_request == '')
		{
			return TRUE;
		}
								
		/** --------------------------------
		/**  These are exceptions to the normal permissions checks
		/** --------------------------------*/
			
		$exceptions = array('member', 'smileys', 'search', 'member_search', 'new_topic_search', 'active_topic_search', 'view_pending_topics', 'search_results', 'search_thread', 'ban_member', 'do_ban_member', 'spellcheck', 'spellcheck_iframe', 'rss', 'atom', 'ignore_member', 'do_ignore_member');

		// Is the member area trigger changed?
		
		if ($this->EE->config->item('profile_trigger') != 'member' && in_array('member', $exceptions))
		{
			unset($exceptions[array_search('member', $exceptions)]);
			$exceptions[] = $this->EE->config->item('profile_trigger');
		}

		if (in_array($this->current_request, $exceptions))
		{
			return TRUE;
		}
		
		/** --------------------------------
		/**  Is this a subscription request?
		/** --------------------------------*/
		
		if ($this->current_request == 'subscribe' OR $this->current_request == 'unsubscribe')
		{
			if ($this->EE->session->userdata('member_id') == 0)
			{
				return $this->_trigger_error();
			}
			
			return TRUE;
		}

		/** --------------------------------
		/**  Fetch the Forums Prefs
		/** --------------------------------*/
		
		// Depending on what the "current_request" variable contains we'll run the query a little differnt.
				
		if (in_array($this->current_request, array('editreply', 'deletereply', 'quotereply', 'viewpost', 'viewreply', 'reportreply', 'movereply')))
		{
			if (FALSE === ($meta = $this->_fetch_post_metadata($this->current_id)))
			{
				return $this->_trigger_error();
			}
		}
		elseif (in_array($this->current_request, array('viewcategory', 'viewforum',  'newtopic')))
		{ 
			if (FALSE === ($meta = $this->_fetch_forum_metadata($this->current_id)))
			{
				return $this->_trigger_error();
			}
		}
		elseif (in_array($this->current_request, array('viewthread', 'viewannounce', 'newreply', 'quotetopic', 'edittopic', 'reporttopic', 'deletetopic', 'movetopic', 'merge', 'do_merge', 'split', 'do_split')))
		{ 
			if (FALSE === ($meta = $this->_fetch_topic_metadata($this->current_id)))
			{
				return $this->_trigger_error();
			}
		}
		/** --------------------------------
		/**  Unserialize the permissions
		/** --------------------------------*/
		$perms = unserialize(stripslashes($meta[$this->current_id]['forum_permissions']));
				
		/** --------------------------------
		/**  Can the forum be viewed?
		/** --------------------------------*/
		if ( ! $this->_permission('can_view_forum', $perms))			
		{
			return $this->_trigger_error('can_not_view_forum');
		}

		/** --------------------------------
		/**  Can hidden forum be viewed?
		/** --------------------------------*/
		// c = hidden

		if ( ! $this->_permission('can_view_hidden', $perms) AND $meta[$this->current_id]['forum_status'] == 'c')				
		{
			return $this->_trigger_error('can_not_view_forum');
		}
		
		/** --------------------------------
		/**  Is user trying to post in a read-only forum?
		/** --------------------------------*/
		// a = read only
		
		if ($meta[$this->current_id]['forum_status'] == 'a' AND in_array($this->current_request, array('newtopic', 'newreply', 'edittopic', 'editreply', 'quotetopic', 'quotereply')))				
		{ 
			return $this->_trigger_error('can_not_post_in_forum');
		}

		/** --------------------------------
		/**  Can posts be viewed?
		/** --------------------------------*/
		$pages = array('viewthread', 'newreply', 'edittopic', 'editreply');

		if ( ! $this->_permission('can_view_topics', $perms) AND in_array($this->current_request, $pages))
		{
			return $this->_trigger_error('can_not_view_posts');
		}

		/** --------------------------------
		/**  Can the user post messages?
		/** --------------------------------*/
		if ( ! $this->_permission('can_post_topics', $perms) AND in_array($this->current_request, array('newtopic', 'edittopic')))
		{		
			return $this->_trigger_error('can_not_post_in_forum');
		}
		
		if ( ! $this->_permission('can_post_reply', $perms) AND in_array($this->current_request, array('newreply', 'editreply', 'quotereply', 'quotetopic')))
		{		
			return $this->_trigger_error('can_not_post_in_forum');
		}

		/** --------------------------------
		/**  User is Authorized!!
		/** --------------------------------*/
		return TRUE;	
	}

	// --------------------------------------------------------------------

	/**
	 * Error page
	 */
	function _trigger_error($msg = 'not_authorized')
	{
		$this->return_data = '';		
		$this->error_message = $this->EE->lang->line($msg);
		$this->_set_page_title($this->EE->lang->line('error'));
		
		// set the current id to 'error' so breadcrumbs and other items are obfuscated
		$this->return_override = $this->current_id;		
		$this->current_id = 'error';
		return $this->_display_forum('error_page');			
	}

	// --------------------------------------------------------------------

	/**
	 * Trigger Login
	 *
	 * This function sets a couple variables which the 
	 * $this->_include_recursive() looks for to determine 
	 * whether the error page should be shown.
	 */
	function _trigger_login_page()
	{
		$this->return_data = '';
		$this->trigger_login_page = TRUE;
		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch admins
	 */
	function _fetch_administrators()
	{
		if ($this->admin_members === FALSE OR $this->admin_groups === FALSE)
		{
			return FALSE;
		}
		
		if (count($this->admin_members) > 0  OR count($this->admin_groups) > 0)
		{
			return TRUE;
		}
		
		$query = $this->EE->db->query("SELECT member_id FROM exp_members WHERE group_id = '1'");
	
		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$this->admin_members[] = $row['member_id'];
			}
		}
		
		$query = $this->EE->db->query("SELECT admin_group_id, admin_member_id FROM exp_forum_administrators WHERE board_id = '".$this->_fetch_pref('board_id')."'");
		
		if ($query->num_rows() == 0 AND count($this->admin_members) == 0)
		{
			$this->admin_members = FALSE;
			$this->admin_groups  = FALSE;
			
			return FALSE;
		}
		
		foreach ($query->result_array() as $row)
		{
			if ($row['admin_member_id'] != 0)
			{
				$this->admin_members[] = $row['admin_member_id'];
			}
			elseif ($row['admin_group_id'] != 0)
			{
				$this->admin_groups[] = $row['admin_group_id'];
			}			
		}
		
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Is this user an admin?
	 */
	function _is_admin($member_id = 0, $group_id = 0)
	{
		if ($member_id == 0)
		{
			$member_id = $this->EE->session->userdata('member_id');
			
			if ($member_id == 0)
			{
				return FALSE;
			}
			
			if ($group_id == 0)
			{
				$group_id = $this->EE->session->userdata('group_id');
			}
			
			if ($group_id == 1)
			{
				return TRUE;
			}			
		}
		
		
		if ( ! $this->_fetch_administrators())
		{
			return FALSE;
		}
		
		if (in_array($member_id, $this->admin_members))
		{
			return TRUE;
		}
		
		if (in_array($group_id, $this->admin_groups))
		{
			return TRUE;
		}
		
		// If we know the member ID but not the group
		// we need to look it up
		
		if ($member_id != 0 AND $group_id == 0)
		{
			$query = $this->EE->db->query("SELECT group_id FROM exp_members WHERE member_id = '{$member_id}'");
		
			if ($query->num_rows() == 0)
			{
				return FALSE;
			}
			
			if (in_array($query->row('group_id') , $this->admin_groups) OR $query->row('group_id')  == 1)
			{
				return TRUE;
			}			
		}
		
		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch permission
	 */
	function _permission($item, $permission_array)
	{
		if ($this->_is_admin())
		{
			return TRUE;
		}
		
		if ( ! isset($permission_array[$item]))
		{
			return FALSE;
		}
	
		return (strpos($permission_array[$item], '|'.$this->EE->session->userdata('group_id').'|') === FALSE) ? FALSE : TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch moderator permission
	 *
	 * We cache these for reuse
	 */
	function _mod_permission($item, $forum_id)
	{
		if ($this->EE->session->userdata('member_id') == 0)
		{
			return FALSE;
		}
				
		if ($this->_is_admin())
		{
			return TRUE;
		}
			
		if ( ! is_array($this->current_moderator))
		{
			return FALSE;
		}
		
		/** -------------------------------------
		/**  Check the cache for the permission
		/** -------------------------------------*/
		$group_id = $this->EE->session->userdata('group_id');
		$member_id = $this->EE->session->userdata('member_id');		
		
		if (isset($this->current_moderator[$forum_id][$group_id][$item]))
		{
			return ($this->current_moderator[$forum_id][$group_id][$item] == 'y') ? TRUE : FALSE;
		}
		elseif (isset($this->current_moderator[$forum_id][$member_id][$item]))
		{
			return ($this->current_moderator[$forum_id][$member_id][$item] == 'y') ? TRUE : FALSE;
		}
		
		/** -------------------------------------
		/**  Fetch the permissions from the DB
		/** -------------------------------------*/
		
		$query = $this->EE->db->query("SELECT * FROM exp_forum_moderators WHERE mod_forum_id = '{$forum_id}' AND (mod_member_id = '{$member_id}' OR mod_group_id = '{$group_id}')");

		if ($query->num_rows() == 0)
		{ 
			return FALSE;
		}
		
		$perms = array('can_edit', 'can_move', 'can_delete', 'can_change_status', 'can_announce', 'can_view_ip', 'can_merge', 'can_split');
				
		foreach ($query->result_array() as $row)
		{
			$id = ($row['mod_group_id'] == 0) ? $row['mod_member_id'] : $row['mod_group_id'];
			
			$this->current_moderator[$row['mod_forum_id']][$id]['is_moderator'] = 'y';
			
			foreach ($perms as $perm)
			{
				$this->current_moderator[$row['mod_forum_id']][$id][$perm] = $row['mod_'.$perm];
			}
		}
		
		if (isset($this->current_moderator[$forum_id][$group_id][$item]))
		{
			return ($this->current_moderator[$forum_id][$group_id][$item] == 'y') ? TRUE : FALSE;
		}
		elseif (isset($this->current_moderator[$forum_id][$member_id][$item]))
		{
			return ($this->current_moderator[$forum_id][$member_id][$item] == 'y') ? TRUE : FALSE;
		}

		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Build Pagination
	 */
	function _create_pagination($data)
	{
		$this->EE->load->library('pagination');
		
		$config['first_page']	= $this->EE->lang->line('first');	
		$config['last_page']	= $this->EE->lang->line('last');	
		$config['next_link']	= $this->EE->lang->line('next');
		$config['prev_link']	= $this->EE->lang->line('previous');
		$config['first_tag_open']	= '<td><div class="paginate">';
		$config['first_tag_close']	= '</div></td>';
		$config['next_tag_open']	= '<td><div class="paginate">';
		$config['next_tag_close']	= '</div></td>';
		$config['prev_tag_open']	= '<td><div class="paginate">';
		$config['prev_tag_close']	= '</div></td>';
		$config['num_tag_open']	= '<td><div class="paginate">';
		$config['num_tag_close']	= '</div></td>';
		$config['cur_tag_open']	= '<td><div class="paginateCur">';
		$config['cur_tag_close']	= '</div></td>';
		$config['last_tag_open']	= '<td><div class="paginate">';
		$config['last_tag_close']	= '</div></td>';
		
		//$config['first_url'] 	= $data['first_url'];
		$config['uri_segment']	= 0;	// pretty hacky, but lets us override CI's cur_page
		$config['base_url']		= $data['path'];
		$config['prefix']		= 'P';
		$config['total_rows'] 	= $data['total_count'];
		$config['per_page']		= $data['per_page'];
		$config['cur_page']		= $data['cur_page'];

		$this->EE->pagination->initialize($config);
		return $this->EE->pagination->create_links();
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch topic marker folder images
	 */
	function _fetch_topic_markers()
	{
		return array(
						'new'		=> $this->image_url.'marker_new_topic.gif',
						'old'		=> $this->image_url.'marker_old_topic.gif',
						'hot'		=> $this->image_url.'marker_hot_topic.gif',
						'hot_old'	=> $this->image_url.'marker_hot_old_topic.gif',
						'moved'		=> $this->image_url.'marker_moved_topic.gif',
						'closed'	=> $this->image_url.'marker_closed_topic.gif',
						'sticky'	=> $this->image_url.'marker_sticky_topic.gif',
						'announce'	=> $this->image_url.'marker_announcements.gif',
						'poll_new'	=> $this->image_url.'marker_new_poll.gif',
						'poll_old'	=> $this->image_url.'marker_old_poll.gif'
					);
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch Pagination Number
	 *
	 * After submitting a new post we need to return the
	 * user to his post.  Since a particular thread might span
	 * several pages we need to send the user back to the exact
	 * page, so we'll calculate the page number.
	 */
	function _fetch_page_number($total, $limit)
	{
		if ($this->_fetch_pref('board_post_order') == 'd')
		{
			return '';
		}
					
		if ( ! is_numeric($limit) OR $limit == 0)
		{
			$limit = 15;
		}
	
		if ($total <= $limit)
		{
			return '';
		}
	
		$num_pages = intval($total / $limit);
		
		if ($num_pages < 1)
		{
			return '';
		}
	  
		if ($total % $limit) 
		{
			$num_pages++;
		}
		
		$num_pages = $num_pages - 1;
		
		if ($num_pages == 0)
		{
			return '';
		}
				
		$tot = ($num_pages * $limit);

		return 'P'.$tot;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Update stats
	 */
	function _update_post_stats($forum_id)
	{
		$cache_off = FALSE;
		
		if ($this->EE->db->cache_on === TRUE)
		{
			$this->EE->db->cache_off();
			$cache_off = TRUE;
		}
		
		$data = array(
						'forum_last_post_id' 		=> 0,
						'forum_last_post_type'		=> 'p',
						'forum_last_post_title'		=> '',
						'forum_last_post_date'		=> 0,
						'forum_last_post_author_id'	=> 0,
						'forum_last_post_author'	=> ''		
					);
		
		$this->EE->db->select('COUNT(*) as count');
		$query = $this->EE->db->get_where('forum_topics', array('forum_id' => $forum_id));	
		$data['forum_total_topics'] = $query->row('count') ;

		$this->EE->db->select('COUNT(*) as count');
		$query = $this->EE->db->get_where('forum_posts', array('forum_id' => $forum_id));
		$data['forum_total_posts'] = $query->row('count') ;
		
		$this->EE->db->select('topic_id, title, topic_date, last_post_date, 
								last_post_author_id, screen_name, announcement');
		$this->EE->db->from(array('forum_topics', 'members'));
		$this->EE->db->where('member_id', 'last_post_author_id', FALSE);
		$this->EE->db->where('forum_id', $forum_id);
		$this->EE->db->order_by('last_post_date', 'DESC');
		$this->EE->db->limit(1);
		$query = $this->EE->db->get();
		
		if ($query->num_rows() > 0)
		{
			$data['forum_last_post_id'] 		= $query->row('topic_id');
			$data['forum_last_post_type'] 		= ($query->row('announcement')  == 'n') ? 'p' : 'a';
			$data['forum_last_post_title'] 		= $query->row('title');
			$data['forum_last_post_date'] 		= $query->row('topic_date');
			$data['forum_last_post_author_id']	= $query->row('last_post_author_id');
			$data['forum_last_post_author']		= $query->row('screen_name');
		}
		
		$this->EE->db->select('post_date, author_id, screen_name');
		$this->EE->db->from(array('forum_posts', 'members'));
		$this->EE->db->where('member_id', 'author_id', FALSE);
		$this->EE->db->where('forum_id', $forum_id);
		$this->EE->db->order_by('post_date', 'DESC');
		$this->EE->db->limit(1);
		$query = $this->EE->db->get();

		if ($query->num_rows() > 0)
		{
			if ($query->row('post_date')  > $data['forum_last_post_date'])
			{
				$data['forum_last_post_date'] 		= $query->row('post_date') ;
				$data['forum_last_post_author_id']	= $query->row('author_id') ;
				$data['forum_last_post_author']		= $query->row('screen_name') ;
			}
		}

		$this->EE->db->query($this->EE->db->update_string('exp_forums', $data, "forum_id='{$forum_id}'"));
		unset($data);
		
		// Update member stats
		$this->EE->db->select('COUNT(*) as count');
		$query = $this->EE->db->get_where('forum_topics', 
											array('author_id' => $this->EE->session->userdata('member_id')));
		$total_topics = $query->row('count') ;
		
		$this->EE->db->select('COUNT(*) as count');
		$query = $this->EE->db->get_where('forum_posts', 
											array('author_id' => $this->EE->session->userdata('member_id')));
		$total_posts = $query->row('count') ;
		
		$d = array(
					'total_forum_topics'	=> $total_topics,
					'total_forum_posts'		=> $total_posts
				);
		$this->EE->db->where('member_id', $this->EE->session->userdata('member_id'));
		$this->EE->db->update('members', $d);

		if ($cache_off)
		{
			$this->EE->db->cache_on();
		}
	}

	// --------------------------------------------------------------------

	/**
	 * update global forum stats
	 */
	function _update_global_stats()		
	{
		$cache_off = FALSE;
		
		if ($this->EE->db->cache_on === TRUE)
		{
			$this->EE->db->cache_off();
			$cache_off = TRUE;
		}

		$total_topics = $this->EE->db->count_all('forum_topics');
		$total_posts  = $this->EE->db->count_all('forum_posts');

		$this->EE->db->update('stats', array(
										'total_forum_topics'	=> $total_topics,
										'total_forum_posts'		=> $total_posts));

		if ($cache_off)
		{
			$this->EE->db->cache_on();
		}
	}

	// --------------------------------------------------------------------
	
	/**
	 * Update topic stats
	 */
	function _update_topic_stats($topic_id)
	{
		$cache_off = FALSE;
		
		if ($this->EE->db->cache_on === TRUE)
		{
			$this->EE->db->cache_off();
			$cache_off = TRUE;
		}

		// Update the thread count and last post date
		$this->EE->db->select('COUNT(*) as count, MAX(post_date) as last_post');
		$query = $this->EE->db->get_where('forum_posts', array('topic_id' => $topic_id));

		$this->thread_post_total = $query->row('count') ;
		$total = ($query->row('count')  + 1);
		
		if ($query->row('count')  > 0)
		{
			$d = array(
					'last_post_date'	=> $query->row('last_post'),
					'thread_total'		=> $total
				);
			
			$this->EE->db->where('topic_id', $topic_id);
			$this->EE->db->update('forum_topics', $d);
		}
		else
		{
			$this->EE->db->set('last_post_date', 'topic_date', FALSE);
			$this->EE->db->set('thread_total', $total);
			$this->EE->db->where('topic_id', $topic_id);
			$this->EE->db->update('forum_topics');
		}

		// Update the resulting last post author and last post id
		if ($total > 1)
		{
			$this->EE->db->select('post_id, author_id');
			$this->EE->db->where('topic_id', $topic_id);
			$this->EE->db->order_by('post_date', 'DESC');
			$this->EE->db->limit(1);
			$query = $this->EE->db->get('forum_posts');

			$d = array(
					'last_post_author_id'	=> $query->row('author_id'),
					'last_post_id'			=> $query->row('post_id')
				);

			$this->EE->db->where('topic_id', $topic_id);
			$this->EE->db->update('forum_topics', $d);
		}
		else
		{
			$this->EE->db->set('last_post_author_id', 'author_id', FALSE);
			$this->EE->db->set('last_post_id', 0);
			$this->EE->db->where('topic_id', $topic_id);
			$this->EE->db->update('forum_topics');			
		}

		if ($cache_off)
		{
			$this->EE->db->cache_on();
		}
	}

	// --------------------------------------------------------------------

	/**
	 * update member stats
	 */
	function _update_member_stats($member_ids = array())
	{		
		$cache_off = FALSE;
		
		if ($this->EE->db->cache_on === TRUE)
		{
			$this->EE->db->cache_off();
			$cache_off = TRUE;
		}

		if ( ! is_array($member_ids))
		{
			$member_ids[$member_ids] = $member_ids;
		}

		foreach ($member_ids as $member_id)
		{
			$this->EE->db->select('COUNT(*) as count');
			$res = $this->EE->db->get_where('forum_topics', array('author_id' => $member_id));
			$total_forum_topics = $res->row('count');

			$this->EE->db->select('COUNT(*) as count');
			$res = $this->EE->db->get_where('forum_posts', array('author_id' => $member_id));
			$total_forum_posts = $res->row('count');

			$this->EE->db->query($this->EE->db->update_string('exp_members', array('total_forum_topics' => $total_forum_topics, 'total_forum_posts' => $total_forum_posts), "member_id = '{$member_id}'"));
		}
		
		if ($cache_off)
		{
			$this->EE->db->cache_on();
		}
	}

	// --------------------------------------------------------------------
		
	/**
	 * Feed Builder
	 */
	function _feed_builder()
	{
		// Grab them prefs
		$sql = "SELECT forum_id, forum_is_cat, forum_status, forum_permissions, forum_enable_rss, forum_use_http_auth FROM exp_forums WHERE board_id = '".$this->_fetch_pref('board_id')."' ";

		// Are there specific forums being requested?
		$feed_id = $this->EE->uri->segment(3+$this->seg_addition);

		if ($feed_id !== FALSE)
		{
			// Trim leading/traiing underscores
			$feed_id = preg_replace("|^_*(.*?)_*$|", "\\1", $feed_id);
			
			if ( ! preg_match("/^[0-9_]+$/i", $feed_id))
			{
				return $this->_trigger_error('no_feed_specified');
			}
							
			$sql .= "AND forum_id IN (".implode(',', explode('_', $feed_id)).") ";
		}
		
		$query = $this->EE->db->query($sql);
		
		if ($query->num_rows() == 0)
		{
			return $this->_trigger_error('no_feed_specified');
		}

		$enable_cluster = TRUE;
		$ids = array();	
		foreach ($query->result_array() as $row)
		{
			/** ------------------------------------------
			/**  Are feeds enabled for this forum?
			/** ------------------------------------------*/
			if ($row['forum_enable_rss'] == 'n')
			{
				continue;
			}
			
			/** ------------------------------------------
			/**  Unserialize the permissions for this forum
			/** ------------------------------------------*/
			$row['forum_permissions'] = unserialize(stripslashes($row['forum_permissions']));
			
			$can_view_forum = explode('|', substr($row['forum_permissions']['can_view_forum'], 1, -1));
			$can_view_hidden = explode('|', substr($row['forum_permissions']['can_view_hidden'], 1, -1));

			/** ------------------------------------------
			/**  Can the user view the category cluster?
			/** ------------------------------------------*/
			
			// If the cluster is not viewable we need to suppress all forums contained within it

			if ($row['forum_is_cat'] == 'y')
			{			
				$enable_cluster = ( ! $this->_permission('can_view_forum', $row['forum_permissions'])) ? FALSE : TRUE;
			}
				
			if ($enable_cluster === FALSE)
			{
				continue;				
			}

			/** ------------------------------------------
			/**  Can the user view the current forum?
			/** ------------------------------------------*/
			if ($row['forum_status'] != 'c' AND ! $this->_permission('can_view_forum', $row['forum_permissions']))				
			{	
				if ($row['forum_use_http_auth'] == 'y')
				{
					$auth = $this->http_authentication_check_basic($can_view_forum);

					if ($this->auth_attempt === FALSE)
					{
						$this->http_authentication_basic();
					}
					elseif($auth === FALSE)
					{
						continue;
					}					
				}
				else
				{
					continue;
				}
			}
			
			/** -------------------------------------------
			/**  Can the user view the current hidden forum?
			/** -------------------------------------------*/
			
			if ($row['forum_status'] == 'c' AND  ! $this->_permission('can_view_hidden', $row['forum_permissions']))
			{ 
				if ($row['forum_is_cat'] == 'y')
				{
					$enable_cluster = FALSE;
				}
				
				if ($row['forum_use_http_auth'] == 'y')
				{
					$auth = $this->http_authentication_check_basic($can_view_hidden);

					if ($this->auth_attempt === FALSE)
					{
						$this->http_authentication_basic();
					}
					elseif($auth === FALSE)
					{
						continue;
					}					
				}
				else
				{
					continue;
				}
			}
				
			/** ------------------------------------------
			/**  Store the ID
			/** ------------------------------------------*/
		
			if ($row['forum_is_cat'] == 'n')
			{ 
				$ids[] = $row['forum_id'];
			}
		}
			
		// After all that, are there valid IDs?
		if (count($ids) == 0)
		{
			return $this->_trigger_error('no_feed_specified');
		}		
		
		/** -------------------------------------
		/**  Fetch the topics
		/** -------------------------------------*/
		
		$idx = implode(',', $ids);								
		$query = $this->EE->db->query("SELECT t.topic_id, t.author_id, t.title, t.body, t.topic_date, t.thread_total, t.last_post_author_id,  t.last_post_date, t.topic_edit_date, t.parse_smileys,
								f.forum_text_formatting, f.forum_html_formatting, f.forum_auto_link_urls, f.forum_allow_img_urls,
								m.screen_name AS last_post_author,
								a.screen_name AS author, a.email, a.url
							FROM exp_forum_topics t, exp_forums f, exp_members m, exp_members a
							WHERE t.last_post_author_id = m.member_id
							AND f.forum_id = t.forum_id
							AND a.member_id = t.author_id
							AND t.announcement = 'n' 
							AND (t.forum_id IN (".$idx.") OR t.moved_forum_id IN (".$idx."))
							AND t.board_id = '".$this->_fetch_pref('board_id')."'
							ORDER BY last_post_date DESC, topic_date DESC
							LIMIT 10");	

		if ($query->num_rows() == 0)
		{
			return $this->_trigger_error('no_feed_results');
		}

		/** -------------------------------------
		/**  Set the output type
		/** -------------------------------------*/
		$this->EE->output->out_type = 'rss';
		$this->EE->config->core_ini['send_headers'] = 'y';
		$this->EE->TMPL->template_type = 'rss';
		
		/** ------------------------------------------
		/**  Load the requested theme file
		/** ------------------------------------------*/
	
		// What RSS type are they requesting?  Can be "rss" or "atom"
		
		$type = $this->EE->uri->segment(2+$this->seg_addition);
		$template = $this->_load_element($type.'_page');
		
		/** ------------------------------------------
		/**  Separate out the "rows" portion of the feed
		/** ------------------------------------------*/
		
		$row_chunk = '';
		if (preg_match_all("/{rows}(.*?){\/rows}/s", $template, $matches))
		{
			for ($j = 0; $j < count($matches['0']); $j++)
			{
				$row_chunk = $matches['1'][$j];
				$template = str_replace($matches['0'][$j], '{{row_chunk}}', $template);
			}
		}
			
		/** --------------------------------------------------
		/**  Relative URL - used by Atom feeds
		/** --------------------------------------------------*/
		$relative = str_replace('http://', '', $this->_forum_path());

		if (($x = strpos($relative, '/')) !== FALSE)
		{
			$relative = substr($relative, $x + 1);
		}
		
		if (substr($relative, -1) == '/')
		{
			$relative = substr($relative, 0, -1);
		}
	
		/** ----------------------------------------
		/**  {trimmed_url} - used by Atom feeds
		/** ----------------------------------------*/
		
		$base_url = str_replace('http://', '', $this->_forum_path());
		
		$trimmed_url = str_replace(array('http://','www.'), '', $base_url);
		$xe = explode("/", $trimmed_url);
		$trimmed_url = current($xe);
			
		/** ------------------------------------------
		/**  Parse Globals
		/** ------------------------------------------*/

		$template = str_replace(LD.'app_version'.RD, APP_VER, $template); 
		$template = str_replace(LD.'version'.RD, APP_VER, $template); 
		$template = str_replace(LD.'webmaster_email'.RD, $this->EE->config->item('webmaster_email'), $template); 
		$template = str_replace(LD.'encoding'.RD, $this->EE->config->item('output_charset'), $template); 
		$template = str_replace(LD.'forum_language'.RD, $this->EE->config->item('xml_lang'), $template); 
		$template = str_replace(LD.'forum_url'.RD, $this->_forum_path(), $template); 
		$template = str_replace(LD.'trimmed_url'.RD, $trimmed_url, $template); 
		$template = str_replace(LD.'forum_rss_url'.RD, $this->_forum_path($type), $template); 
		$template = str_replace(LD.'forum_name'.RD, $this->_fetch_pref('board_label'), $template);
		
		/** --------------------------------------------------
		/**  {gmt_date format="%Y %m %d %H:%i:%s"}
		/** --------------------------------------------------*/
		if (preg_match_all("/".LD."gmt_date\s+format=[\"\'](.+?)[\"\']".RD."/", $template, $matches))
		{	
			for ($j = 0; $j < count($matches['0']); $j++)
			{				
				$template = preg_replace("/".$matches['0'][$j]."/", $this->EE->localize->decode_date($matches['1'][$j], $query->row('last_post_date') ), $template, 1);				
			}
		}  		

		/** --------------------------------------------------
		/**  {gmt_edit_date format="%Y %m %d %H:%i:%s"}
		/** --------------------------------------------------*/
		if (preg_match_all("/".LD."gmt_edit_date\s+format=[\"\'](.+?)[\"\']".RD."/", $template, $matches))
		{	
			for ($j = 0; $j < count($matches['0']); $j++)
			{				
				$template = preg_replace("/".$matches['0'][$j]."/", $this->EE->localize->decode_date($matches['1'][$j], $query->row('topic_edit_date') ), $template, 1);				
			}
		}
		
		/** --------------------------------------------------
		/**  {gmt_edit_date format="%Y %m %d %H:%i:%s"}
		/** --------------------------------------------------*/
		if ( ! preg_match_all("/".LD."gmt_post_date\s+format=[\"\'](.+?)[\"\']".RD."/", $row_chunk, $gmt_post_date))
		{	
			$gmt_post_date = array();
		}
		
		/** --------------------------------------------------
		/**  {gmt_edit_date format="%Y %m %d %H:%i:%s"}
		/** --------------------------------------------------*/
		if ( ! preg_match_all("/".LD."gmt_edit_date\s+format=[\"\'](.+?)[\"\']".RD."/", $row_chunk, $gmt_edit_date))
		{	
			$gmt_edit_date = array();
		}
					
		/** ----------------------------------------
		/**  {relative_url} - used by Atom feeds
		/** ----------------------------------------*/
		
		$relative_url = str_replace('http://', '', $base_url);
		
		if (($x = strpos($relative_url, "/")) !== FALSE)
		{
			$relative_url = substr($relative_url, $x + 1);
		}
		
		if (substr($relative_url, -1) == '/')
		{
			$relative_url = substr($relative_url, 0, -1);
		}

		/** --------------------------------------------------
		/**  Cycle through the results
		/** --------------------------------------------------*/
		
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();
		$this->EE->typography->highlight_code = TRUE;
		$this->EE->typography->encode_type = 'noscript';

		$res = '';
		foreach ($query->result_array() as $row)
		{
			$temp = $row_chunk;
			
			
			if ($row['parse_smileys'] == 'y')
			{
				$this->EE->typography->parse_smileys = TRUE;
			}
			else
			{
				$this->EE->typography->parse_smileys = FALSE;
			}
			
			$title = trim($this->_convert_special_chars($this->EE->typography->format_characters($this->EE->typography->filter_censored_words($row['title']))));
		
			$body = $this->_quote_decode($this->EE->typography->parse_type($row['body'], 
																		  array(
																				'text_format'	=> $row['forum_text_formatting'],
																				'html_format'	=> $row['forum_html_formatting'],
																				'auto_links'	=> $row['forum_auto_link_urls'],
																				'allow_img_url' => $row['forum_allow_img_urls']
																				)
														 )
										);
		
			$temp = str_replace('{topic_id}', $row['topic_id'], $temp);
			$temp = str_replace('{title}', $title, $temp);
			$temp = str_replace('{body}', $body, $temp);
			$temp = str_replace('{author}', $row['author'], $temp);
			$temp = str_replace('{email}', $row['email'], $temp);
			$temp = str_replace('{url}', $row['url'], $temp);
			$temp = str_replace('{path:view_thread}', $this->_forum_path('/viewthread/'.$row['topic_id'].'/'), $temp);
			$temp = str_replace('{trimmed_url}', $trimmed_url, $temp);
			$temp = str_replace('{relative_url}', $relative_url, $temp);

			if (count($gmt_post_date) > 0)
			{
				for ($j = 0; $j < count($gmt_post_date['0']); $j++)
				{				
					$temp = preg_replace("/".$gmt_post_date['0'][$j]."/", $this->EE->localize->decode_date($gmt_post_date['1'][$j], $row['topic_date']), $temp, 1);				
				}
			}
			
			if (count($gmt_edit_date) > 0)
			{
				for ($j = 0; $j < count($gmt_edit_date['0']); $j++)
				{				
					$temp = preg_replace("/".$gmt_edit_date['0'][$j]."/", $this->EE->localize->decode_date($gmt_edit_date['1'][$j], $row['topic_edit_date']), $temp, 1);				
				}
			}			
		
			$res .= $temp;
		}
		
		/** --------------------------------------------------
		/**  Put the row chunk back
		/** --------------------------------------------------*/
		
		$template = str_replace('{{row_chunk}}', $res, $template);
		
		/** ------------------------------------------
		/**  XML Encode
		/** ------------------------------------------*/
		if (preg_match_all("/{exp:xml_encode}(.*?){\/exp:xml_encode}/s", $template, $matches))
		{
			// Load the XML Helper
			$this->EE->load->helper('xml');

			for ($j = 0; $j < count($matches['0']); $j++)
			{
				$template = str_replace($matches['0'][$j], 
										str_replace('&nbsp;', '&#160;', xml_convert($matches['1'][$j])),
										$template);
			}
		}
				
		return $template;
	}

	// --------------------------------------------------------------------

	/**
	 * Remove session ID from string
	 *
	 * This function is used mainly by the Input class to strip
	 * session IDs if they are used in public pages.
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */	
	function remove_session_id($str)
	{
		return preg_replace("#S=.+?/#", "", $str);
	} 

	// --------------------------------------------------------------------
	
	/**
	 * View Posts Redirect
	 */
	function view_post_redirect()
	{
		$topic_id = $this->post_metadata[$this->current_id]['topic_id'];
		$post_limit = $this->post_metadata[$this->current_id]['forum_posts_perpage'];
		$post_number = 0;
		
		// Find out where in the post order the post is
		$query = $this->EE->db->query("SELECT post_id FROM exp_forum_posts WHERE topic_id = '{$topic_id}' ORDER BY post_date ASC");
		
		foreach($query->result_array() as $key => $val)
		{
			if ($val['post_id'] == $this->current_id)
			{
				$post_number = $key;
				break;
			}
		}
		
		// invert the post number if the sort order is descending
		$post_number = ($this->post_metadata[$this->current_id]['forum_post_order'] == 'd') ? $query->num_rows() - $post_number : $post_number;
		
		$page = floor($post_number / $post_limit) * $post_limit;
		$pag_seg = ($page > 0) ? "P".$page."/" : '';

		$this->EE->functions->redirect($this->_forum_path('/viewthread/'.$topic_id.'/'.$pag_seg.'/').'#'.$this->current_id);
		exit;
	}

	// --------------------------------------------------------------------

	/**
	 * Main Forum Display
	 */
	function main_forum_list()
	{
		$return 		= '';
		$first_row		= FALSE;
		$enable_cluster = TRUE;

		/** --------------------------------
		/**  Fetch the Forums
		/** --------------------------------*/
	
		$sql = "SELECT * FROM exp_forums WHERE board_id = '".$this->_fetch_pref('board_id')."' ";
		
		// Is the display being limited to a particular category?
		
		if (is_numeric($this->current_id))
		{
			$sql .= "AND (forum_id = '".$this->current_id."' OR forum_parent =  '".$this->current_id."') ";
		}
		
		$sql .= "ORDER BY forum_order";
						
		$query = $this->EE->db->query($sql);

		if ($query->num_rows() == 0 OR $query->row('forum_is_cat') != 'y')
		{
			return '';
		}
		
		/** -------------------------------------
		/**  Fetch the "read topics" cookie
		/** -------------------------------------*/
		
		// Each time a topic is read, the ID number for that topic
		// is saved in a cookie as a serialized arrry.  
		// This array lets us set topics to "read" status.
		// The array is only good durring the length of the current session.
							
		$read_topics = $this->_fetch_read_topics();
				
		/** --------------------------------
		/**  Build the forum
		/** --------------------------------*/
		
		$markers = $this->_fetch_topic_markers();
		$this->_load_moderators();
		
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();
		$enable_cluster = TRUE;
		
		$not_these	= array();
		$these		= array();
		
		if (isset($this->EE->TMPL) && is_object($this->EE->TMPL) && ($forums = $this->EE->TMPL->fetch_param('forums')) != FALSE)
		{
			if (substr($forums, 0, 4) == 'not ')
			{
				$not_these = explode('|', trim(substr($forums, 3)));
			}
			else
			{
				$these = explode('|', trim($forums));
			}
		}
	
		foreach ($query->result_array() as $row)
		{	
			if (count($not_these) > 0 && in_array($row['forum_id'], $not_these)) continue;
			if (count($these) > 0 &&  ! in_array($row['forum_id'], $these)) continue;
		
			/** ------------------------------------------
			/**  Unserialize the permissions for this forum
			/** ------------------------------------------*/
			$row['forum_permissions'] = unserialize(stripslashes($row['forum_permissions']));
				
			/** ------------------------------------------
			/**  Can the user view the category cluster?
			/** ------------------------------------------*/
			
			// If the cluster is not viewable we need to suppress all forums contained within it

			if ($row['forum_is_cat'] == 'y')
			{			
				$enable_cluster = ( ! $this->_permission('can_view_forum', $row['forum_permissions'])) ? FALSE : TRUE;
			}
				
			if ($enable_cluster === FALSE)
				continue;
			 
			/** ------------------------------------------
			/**  Can the user view the current forum?
			/** ------------------------------------------*/
	
			if ($row['forum_status'] != 'c' AND ! $this->_permission('can_view_forum', $row['forum_permissions']))				
			{
				continue;
			}
			
			/** -------------------------------------------
			/**  Can the user view the current hidden forum?
			/** -------------------------------------------*/
			
			if ($row['forum_status'] == 'c' AND  ! $this->_permission('can_view_hidden', $row['forum_permissions']))
			{
				if ($row['forum_is_cat'] == 'y')
				{
					$enable_cluster = FALSE;
				}
				
				continue;
			}
				
			/** ------------------------------------------
			/**  Build the forum output
			/** ------------------------------------------*/
		
			if ($row['forum_is_cat'] == 'y')
			{ 
				$this->forum_ids[] = $row['forum_id'];
				$return .= $this->main_forum_table_heading($row);
				$this->is_table_open = TRUE;
			}
			else
			{				
				$row['forum_last_post_title'] = $this->EE->typography->filter_censored_words($row['forum_last_post_title']);			
				$return .= $this->main_forum_table_rows($row, $markers, $read_topics);	
				
				if ($row['forum_enable_rss'] == 'y')
				{
					$this->feeds_enabled = TRUE;
				}
			}
		}
		
		if ($this->is_table_open == TRUE)
		{
			$return .= $this->main_forum_table_close();
		}

		$this->show_hide_forums();

		return $return;
	}

	
	
	/** -------------------------------------
	/**  Forum Category Heading
	/** -------------------------------------*/
	
	function main_forum_table_heading($row)
	{
		// Close the table of the previous cluster if it has not been done so already
		
		$table_close = ($this->is_table_open == TRUE) ? $this->main_forum_table_close() : '';
	
		$table_head = $this->_load_element('forum_table_heading');
		
		$table_head = str_replace('{category_name}', $this->_convert_special_chars($row['forum_name'], TRUE), $table_head);
		$table_head = str_replace('{category_id}', $row['forum_id'], $table_head);
		
		if (preg_match("/\{if\s+category_description}(.*?){\/if\}/s", $table_head, $match))
		{
			$match['1'] = str_replace('{category_description}', $row['forum_description'], $match['1']);
		
			$table_head = str_replace($match['0'], $match['1'], $table_head);
		}

		return $table_close.$table_head;
	}

	
	
	/** ----------------------------------------
	/**  Forum Table Rows
	/** ----------------------------------------*/
	function main_forum_table_rows($row, $markers, $read_topics)
	{
		/** ----------------------------------------
		/**  Fetch Template
		/** ----------------------------------------*/
	
		$table_rows = $this->_load_element('forum_table_rows');
		
		// -------------------------------------------
        // 'main_forum_table_rows_template' hook.
        //  - Allows modifying of the forum_table_rows template
        //
			if ($this->EE->extensions->active_hook('main_forum_table_rows_template') === TRUE)
			{
				$table_rows = $this->EE->extensions->universal_call('main_forum_table_rows_template', $this, $table_rows, $row, $markers, $read_topics);
				if ($this->EE->extensions->end_script === TRUE) return $table_rows;
			}
        //
        // -------------------------------------------

		/** ----------------------------------------
		/**  Swap a few variables
		/** ----------------------------------------*/
		$table_rows = str_replace('{forum_name}', 	$this->_convert_special_chars($row['forum_name'], TRUE), $table_rows);
		$table_rows = str_replace('{total_topics}', $row['forum_total_topics'], $table_rows);
		$table_rows = str_replace('{total_replies}',$row['forum_total_posts'], 	$table_rows);
		

		
		$table_rows = str_replace('{path:viewforum}', $this->_forum_path('viewforum/'.$row['forum_id']), 	$table_rows);
		
		// Do we have to add pagination to the "last post" link?
		// This allows the link to point to the last page of the thread
		
		$pquery = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_forum_posts WHERE topic_id = '{$row['forum_last_post_id']}'");
		$pagination = '';

		if ($pquery->row('count')  > $row['forum_posts_perpage'] AND $row['forum_posts_perpage'] > 0)
		{
			$total_posts = $pquery->row('count') ;
			$num_pages = intval($total_posts / $row['forum_posts_perpage']);

			if ($num_pages > 0)
			{
				if ($total_posts %  $row['forum_posts_perpage']) 
				{
					$num_pages++;
				}
			}
			
			$num_pages = $num_pages - 1;
			
			if ($num_pages > 0)
				$pagination = '/P'.($num_pages * $row['forum_posts_perpage']).'/';
		}
		
		// Build the "last post" link
		
		// Since announcements have slightly differen URLs we'll append them if needed
		$viewpath = ($row['forum_last_post_type'] == 'a') ? 'viewannounce/': 'viewthread/';
		$ann_id	  = ($row['forum_last_post_type'] == 'a') ?  '_'.$row['forum_id']  : '';
		
		// Build the link
		if ($row['forum_post_order'] != 'd')
		{
			$table_rows = str_replace('{path:recent_thread}', $this->_forum_path($viewpath.$row['forum_last_post_id'].$ann_id.$pagination), $table_rows);			
		}
		else
		{
			$table_rows = str_replace('{path:recent_thread}', $this->_forum_path($viewpath.$row['forum_last_post_id'].$ann_id), $table_rows);						
		}

		$topic_marker = $markers['old'];

		if ($this->EE->session->userdata('member_id') == 0)
		{
			$topic_marker = $markers['new'];
		}
		else
		{
			if ($this->EE->session->userdata('last_visit') > 0)
			{
				$tquery = $this->EE->db->query("SELECT topic_id, last_post_date FROM exp_forum_topics WHERE forum_id = '{$row['forum_id']}' AND last_post_date > '".$this->EE->session->userdata('last_visit')."'");
						
				if ($tquery->num_rows() > 0)
				{
					foreach ($tquery->result_array() as $trow)
					{
						if ( ! isset($read_topics[$trow['topic_id']]) OR $read_topics[$trow['topic_id']] < $trow['last_post_date'])
						{						
							$topic_marker =  $markers['new'];
							break;
						}
					}
				}
			}
			else
			{
				$topic_marker = ($row['forum_last_post_date'] > $this->EE->session->userdata('last_visit')) ? $markers['new'] : $markers['old'];
			}
		}

		$table_rows = str_replace('{topic_marker}', 	$topic_marker, 	$table_rows);
		
		/** ----------------------------------------
		/**  Is there a description?
		/** ----------------------------------------*/
		if ($row['forum_description'] == '')
		{
			$table_rows = preg_replace("/\{if\s+forum_description}.*?{\/if\}/s", "", $table_rows);
		}
		elseif (preg_match("/\{if\s+forum_description}(.*?){\/if\}/s", $table_rows, $match))
		{
			$match['1'] = str_replace('{forum_description}', $row['forum_description'], $match['1']);
		
			$table_rows = str_replace($match['0'], $match['1'], $table_rows);
		}		

		/** ----------------------------------------
		/**  Are there moderators?
		/** ----------------------------------------*/
		if (preg_match("/\{if\s+forum_moderators}(.*?){\/if\}/s", $table_rows, $match))
		{			
			if (preg_match("/\{moderators.*?\}(.*?){\/moderators\}/s", $match['1'], $match2))
			{	
				$mods = '';
				$plural = FALSE;
								
				if (isset($this->moderators[$row['forum_id']]))
				{
					$i = 0;
					foreach ($this->moderators[$row['forum_id']] as $mod)
					{
						$temp = $match2['1'];
						
						if ($mod['mod_group_name'] == '')
						{
							$plural = ($i++ > 1) ? TRUE : $plural;
							$temp = str_replace('{path:member_profile}', $this->_profile_path($mod['mod_member_id']), $temp);
							$temp = str_replace('{name}', $mod['mod_member_name'], $temp);
						}
						else
						{
							// member groups are always plural since it describes people
							$plural = TRUE;							
							$gid = 'memberlist/'.$mod['mod_group_id'].'-total_posts-desc-20-0';
							$temp = str_replace('{path:member_profile}', $this->_profile_path($gid), $temp);
							$temp = str_replace('{name}', $mod['mod_group_name'], $temp);
						}
						
						$mods .= $temp;
						
					}
				}
				
				if (preg_match("/\{moderators.*?backspace=[\"|'](.+?)[\"|']/", $match['1'], $backspace))
				{
					$mods = substr($mods, 0, - $backspace['1']);
				}
							
				$match['1'] = ($mods != '') ? str_replace($match2['0'], $mods, $match['1']) : '';
							
				$table_rows = str_replace($match['0'], $match['1'], $table_rows);

				if ($plural)
				{
					$table_rows = str_replace('{lang:moderated_by}', '{lang:moderated_by_plural}', $table_rows);
				}
			}
		}

		/** ----------------------------------------
		/**  Fetch recent post stuff
		/** ----------------------------------------*/
		
		$recent_chunk = ( ! preg_match("/\{if\s+recent_post}(.*?){\/if\}/s", $table_rows, $match)) ? FALSE : $match;
		
		if ($recent_chunk != FALSE)
		{
			if ($row['forum_last_post_title'] != '')
			{
				$date = ( ! preg_match("/{last_post\s+format=['|\"](.+?)['|\"]\}/i", $recent_chunk['1'], $match)) ? FALSE : $match;	
				
				$temp = $recent_chunk['1'];
	
				if ($date !== FALSE AND $row['forum_last_post_date'] != 0)
				{
					if (date('Ymd', $row['forum_last_post_date']) == date('Ymd', $this->EE->localize->now))
					{	
						$temp = str_replace($date['0'], str_replace('%x', $this->EE->localize->format_timespan(($this->EE->localize->now - $row['forum_last_post_date'])), $this->EE->lang->line('ago')), $temp);
					}
					else
					{
						$temp = str_replace($date['0'], $this->EE->localize->decode_date($date['1'], $row['forum_last_post_date']), $temp);
					}
				}
			
			
				$temp = str_replace('{title}', $this->_convert_special_chars($row['forum_last_post_title']), $temp);
				$temp = str_replace('{author}', $row['forum_last_post_author'], $temp);
				$temp = str_replace('{path:member_profile}',  $this->_profile_path($row['forum_last_post_author_id']), $temp);
				
				$table_rows = str_replace($recent_chunk['0'], $temp, $table_rows);
			}
			else
			{
				$table_rows = str_replace($recent_chunk['0'], '', $table_rows);
			}
		}

		return $table_rows;
	}


	/** ----------------------------------------
	/**  Forum Table Close
	/** ----------------------------------------*/
	function main_forum_table_close()
	{		
		$this->is_table_open = FALSE;
		
		return $this->_load_element('forum_table_footer');
	}



	/** -------------------------------------
	/**  Show/hide Javascript
	/** -------------------------------------*/
	
	function show_hide_forums()
	{
		if (count($this->forum_ids) == 0)
		{
			return;
		}
	
		$str = $this->_load_element('javascript_show_hide_forums');
		
		$prefix = ( ! $this->EE->config->item('cookie_prefix')) ? 'exp_' : $this->EE->config->item('cookie_prefix').'_';
		$str = str_replace('{cookie_name}', $prefix.'state', $str);
		
		$arr = $this->_load_element('javascript_forum_array');

		$i = 0;
		$s = '';
		foreach ($this->forum_ids as $id)
		{
			$temp = str_replace('{forum_name}', 'forum'.$id, $arr);
			$temp = str_replace('{n}', $i, $temp);		
			$s .= $temp;
			$i++;
		}
		
		$temp = str_replace('{forum_name}', 'forumstats', $arr);
		$temp = str_replace('{n}', $i, $temp);		
		$s .= $temp;
		
		$this->head_extra = str_replace('{include:javascript_forum_array}', $s, $str);
		$this->body_extra = $this->_load_element('javascript_set_show_hide');
	}

	/** -------------------------------------
	/**  Announcement Topics 
	/** -------------------------------------*/
	function announcement_topics()
	{
		$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_forum_topics WHERE announcement = 'a' OR (announcement = 't' AND forum_id = '{$this->current_id}')");

		if ($query->row('count')  == 0)
		{
			return '';
		}
		
		/** -------------------------------------
		/**  Fetch the announcements
		/** -------------------------------------*/
		
		$query = $this->EE->db->query("SELECT t.topic_id, t.author_id, t.title, t.thread_views, t.topic_date, m.screen_name AS author
								FROM exp_forum_topics t, exp_members m
								WHERE m.member_id = t.author_id
								AND t.board_id = '".$this->_fetch_pref('board_id')."'
								AND (t.announcement = 'a' OR (announcement = 't' AND forum_id = '{$this->current_id}'))
							");		

		if ($query->num_rows() == 0)
		{
			return '';
		}
		

		/** -------------------------------------
		/**  Fetch the templates
		/** -------------------------------------*/
		
		$str = $this->_load_element('announcement_topics');
		$template = $this->_load_element('announcement_topic_rows');
		
		/** -------------------------------------
		/**  Fetch the "post_date" date
		/** -------------------------------------*/
				
		$date = ( ! preg_match("/{post_date\s+format=['|\"](.+?)['|\"]\}/i", $template, $match)) ? FALSE : $match;			
		
		/** -------------------------------------
		/**  Fetch the topic markers
		/** -------------------------------------*/
		
		$markers = $this->_fetch_topic_markers();
		$topic_marker = $markers['announce'];
		
		/** -------------------------------------
		/**  Render the template
		/** -------------------------------------*/
		
		$topics = '';						
		foreach ($query->result_array() as $row)
		{
			$temp = $template;
		

			$temp = $this->_var_swap($temp,
							array(
									'topic_marker'			=>	$topic_marker,
									'topic_title'			=>	$this->_convert_special_chars($row['title']),
									'author'				=>	$row['author'],
									'total_views'			=>	$row['thread_views'],
									'path:member_profile'	=>	$this->_profile_path($row['author_id']),
									'path:view_thread'		=>	$this->_forum_path('/viewannounce/'.$row['topic_id'].'_'.$this->current_id.'/')
								)
							);

			/** -------------------------------------
			/**  Parse the "post_date" date
			/** -------------------------------------*/
			if ($date !== FALSE AND $row['topic_date'] != 0)
			{
				if (date('Ymd', $row['topic_date']) == date('Ymd', $this->EE->localize->now))
				{	
					$dt = str_replace('%x', $this->EE->localize->format_timespan(($this->EE->localize->now - $row['topic_date'])), $this->EE->lang->line('ago'));
				}
				else
				{
					$dt = $this->EE->localize->decode_date($date['1'], $row['topic_date']);
				}
			}
			else
			{
				$dt = '-';
			}
			
			$temp = str_replace($date['0'], $dt, $temp);
						
			$topics .= $temp;
		}

		return str_replace('{include:announcement_rows}', $topics, $str);
	}



	/** -------------------------------------
	/**  View Announcement page
	/** -------------------------------------*/
	function announcements()
	{
				
		/** -------------------------------------
		/**  Fetch The Announcement
		/** -------------------------------------*/
		
		
		$tquery = $this->EE->db->query("SELECT f.forum_text_formatting, f.forum_html_formatting, f.forum_auto_link_urls, f.forum_allow_img_urls, f.forum_hot_topic, f.forum_post_order, f.forum_posts_perpage, f.forum_display_edit_date,
									 t.forum_id, t.topic_id as post_id, t.author_id, t.ip_address, t.title, t.body, t.status, t.announcement, t.thread_views, t.parse_smileys, t.topic_date AS date, t.topic_edit_date AS edit_date, t.topic_edit_author AS edit_author_id, em.screen_name AS edit_author,
							  		 m.group_id, m.screen_name AS author, m.join_date, m.total_forum_topics, m.total_forum_posts, m.location, m.email, m.accept_user_email, m.url, m.aol_im, m.yahoo_im, m.msn_im, m.icq, m.signature, m.sig_img_filename, m.sig_img_width, m.sig_img_height, m.avatar_filename, m.avatar_width, m.avatar_height, m.photo_filename, m.photo_width, m.photo_height
							FROM (exp_forums f, exp_forum_topics t, exp_members m)
							LEFT JOIN exp_members em ON t.topic_edit_author = em.member_id
							WHERE f.forum_id = t.forum_id
							AND t.author_id = m.member_id
							AND t.topic_id = '{$this->current_id}'");
		
		if ($tquery->num_rows() == 0)
		{
			return $this->_trigger_error('thread_no_exists');
		}
		
		$forum_id	= $tquery->row('forum_id') ;	
				
		$formatting = array(
							'text_format'	=> $tquery->row('forum_text_formatting') ,
							'html_format'	=> $tquery->row('forum_html_formatting') ,
							'auto_links'	=> $tquery->row('forum_auto_link_urls') ,
							'allow_img_url' => $tquery->row('forum_allow_img_urls') 
							);
							
		/** -------------------------------------
		/**  Fetch the moderators and ranks
		/** -------------------------------------*/
		
		$rank_query	 = $this->EE->db->query("SELECT rank_title, rank_min_posts, rank_stars FROM exp_forum_ranks ORDER BY rank_min_posts");
		$mod_query	 = $this->EE->db->query("SELECT mod_member_id, mod_group_id FROM exp_forum_moderators WHERE mod_forum_id = '{$forum_id}'");
		
		//  Fetch the Super Admin IDs
		$super_admins = $this->fetch_superadmins();
		
		/** -------------------------------------
		/**  Fetch attachments
		/** -------------------------------------*/
		
		$attach_query = $this->EE->db->query("SELECT * FROM exp_forum_attachments WHERE topic_id = '{$this->current_id}' AND post_id = '0'");
		
		if ($attach_query->num_rows() == 0)
		{
			$attach_query = FALSE;
			$attach_base 	= '';
		}
		else
		{
			$attach_base = $this->EE->functions->fetch_site_index(0, $this->use_sess_id).(($this->EE->config->item('force_query_string') == 'y') ? '&amp;' : '?').'ACT='.$this->EE->functions->fetch_action_id('Forum', 'display_attachment').'&amp;fid='.$forum_id.'&amp;aid=';
		}
		
		/** -------------------------------------
		/**  Update the views
		/** -------------------------------------*/
		
		$views = ($tquery->row('thread_views')  <= 0) ? 1 : $tquery->row('thread_views')  + 1;
		$this->EE->db->query("UPDATE exp_forum_topics SET thread_views = '{$views}' WHERE topic_id = '{$this->current_id}'");
		
		/** -------------------------------------
		/**  Parse the template with the topic data
		/** -------------------------------------*/
		$str = $this->thread_rows(
									array (
											'query'			=> $tquery,
											'rank_query'	=> $rank_query,
											'mod_query'		=> $mod_query,
											'attach_query'	=> $attach_query,
											'attach_base'	=> $attach_base,
											'formatting'	=> $formatting,
											'super_admins'	=> $super_admins,
											'is_topic'		=> TRUE,
											'topic_id'		=> $tquery->row('post_id') ,
											'topic_status'	=> $tquery->row('status') 
											),
										TRUE
									);
									
									
									
		return str_replace('{topic_title}', $this->_convert_special_chars($tquery->row('title') ), $str);
	}

	


	/** -------------------------------------
	/**  Topic View Table
	/** -------------------------------------*/
	function topics()
	{
		$pagination 	= '';
		$query_limit	= '';
		$current_page	= 1;
		$total_pages	= 1;
			
		/** -------------------------------------
		/**  Fetch Topic order and per-page count
		/** -------------------------------------*/
		
		$query = $this->EE->db->query("SELECT forum_topic_order, forum_topics_perpage, forum_posts_perpage, forum_hot_topic, forum_enable_rss FROM exp_forums WHERE forum_id = '{$this->current_id}'");
		
		if ($query->num_rows() == 0)
		{
			return $this->_trigger_error();
		}
		
		// Order options:
		// d: desc 
		// a: asc
		// r: recent post
		
		switch ($query->row('forum_topic_order') )
		{
			case 'a' 	: $order = "ORDER BY sticky DESC, topic_date ASC";
				break;
			case 'r' 	: $order = "ORDER BY sticky DESC, last_post_date DESC, topic_date DESC";
				break;
			default		: $order = "ORDER BY sticky DESC, topic_date DESC";
				break;
		}
		
		$hot_topic	= $query->row('forum_hot_topic') ;
		$topic_limit = $query->row('forum_topics_perpage') ;
		$post_limit  = $query->row('forum_posts_perpage') ;
		
		if ($query->row('forum_enable_rss')  == 'y')
		{
			$this->feeds_enabled = TRUE;
			$this->feed_ids = $this->current_id;
		}
		
		if ( ! is_numeric($hot_topic) OR $hot_topic == 0) $hot_topic = '15';
		
		if ($topic_limit == 0)
			$topic_limit = 25;
		if ($post_limit == 0)
			$post_limit = 15;
					
		/** -------------------------------------
		/**  Fetch template and meta-data
		/** -------------------------------------*/
			
		$str = $this->_load_element('topics');
		$fdata = $this->_fetch_forum_metadata($this->current_id);
			
			
		// -------------------------------------------
		// 'forum_topics_start' hook.
		//  - Allows usurping of forum topics display
		//
			if ($this->EE->extensions->active_hook('forum_topics_start') === TRUE)
			{
				$str = $this->EE->extensions->universal_call('forum_topics_start', $this, $str);
				if ($this->EE->extensions->end_script === TRUE) return $str;
			}
		//
		// -------------------------------------------
		
		
		
		/** -------------------------------------
		/**  Count the topics for pagination
		/** -------------------------------------*/
		
		$query = $this->EE->db->query("SELECT COUNT(*) AS count 
							FROM exp_forum_topics t, exp_members m, exp_members a
							WHERE t.last_post_author_id = m.member_id
							AND a.member_id = t.author_id
							AND t.announcement = 'n' 
							AND (t.forum_id = '{$this->current_id}' OR t.moved_forum_id = '{$this->current_id}')
							{$query_limit}");		
				
		
		if ($query->row('count')  == 0)
		{
			$str = str_replace('{include:topic_rows}', $this->_load_element('topic_no_results'), $str);
			
			$str = $this->_deny_if('paginate', $str, '&nbsp;');
			
			if ( ! $this->_permission('can_post_topics', unserialize(stripslashes($fdata[$this->current_id]['forum_permissions']))) OR $this->EE->session->userdata('member_id') == 0)			
			{
				$str = $this->_deny_if('can_post', $str, '&nbsp;');
			}
			else
			{
				$str = $this->_allow_if('can_post', $str);
			}

			return $this->_var_swap( $str,
									array(
											'forum_name'		=> $this->_convert_special_chars($fdata[$this->current_id]['forum_name'], TRUE),
											'forum_description'	=> $fdata[$this->current_id]['forum_description'],
											'path:new_topic' 	=> $this->_forum_path('/newtopic/'.$this->current_id.'/')
										)
									);
		}
		
			
		// No funny business with the page count allowed
	
		if ($this->current_page > $query->row('count') )
		{
			$this->current_page = 0;
		}

		/** -------------------------------------
		/**  We have pagination!
		/** -------------------------------------*/
		
		if ($query->row('count')  > $topic_limit)
		{	
			$pagination = $this->_create_pagination(
										array(
												'first_url'		=> $this->_forum_path('/viewforum/'.$this->current_id.'/'),
												'path'			=> $this->_forum_path('/viewforum/'.$this->current_id.'/'),
												'total_count'	=> $query->row('count') ,
												'per_page'		=> $topic_limit,
												'cur_page'		=> $this->current_page
											)
										);
			
			// Set the LIMIT for our query

			$query_limit = 'LIMIT '.$this->current_page.', '.$topic_limit;
		
			// Set the stats for: {current_page} of {total_pages}
			
			$current_page = floor(($this->current_page / $topic_limit) + 1);
			$total_pages  = intval($query->row('count')  / $topic_limit);		
			
			if ($query->row('count')  % $topic_limit) 
			{
				$total_pages++;
			}
		}
		
		if ($pagination == '')
		{
			$str = $this->_deny_if('paginate', $str, '&nbsp;');
		}
		else
		{
			$str = $this->_allow_if('paginate', $str);
		}
		
		
		/** -------------------------------------
		/**  Fetch the topics
		/** -------------------------------------*/
														
		$query = $this->EE->db->query("SELECT t.topic_id, t.author_id, t.moved_forum_id, t.ip_address, t.title, t.status, t.sticky, t.poll, t.thread_views, t.topic_date, t.thread_total, t.last_post_author_id,  t.last_post_date, t.last_post_id,
								m.screen_name AS last_post_author,
								a.screen_name AS author
							FROM exp_forum_topics t, exp_members m, exp_members a
							WHERE t.last_post_author_id = m.member_id
							AND a.member_id = t.author_id
							AND t.announcement = 'n' 
							AND (t.forum_id = '{$this->current_id}' OR t.moved_forum_id = '{$this->current_id}')
							".$order."
							{$query_limit}");		

	
		/** -------------------------------------
		/**  Fetch the "row" template
		/** -------------------------------------*/
	
		$template = $this->_load_element('topic_rows');
		
		/** -------------------------------------
		/**  Fetch the "last_reply" date
		/** -------------------------------------*/
		
		// We do this here to keep it out of the loop
		
		$date = ( ! preg_match("/{last_reply\s+format=['|\"](.+?)['|\"]\}/i", $template, $match)) ? FALSE : $match;			

		/** -------------------------------------
		/**  Fetch the "read topics" cookie
		/** -------------------------------------*/
		
		// Each time a topic is read, the ID number for that topic
		// is saved in a cookie as a serialized arrry.  
		// This array lets us set topics to "read" status.
		// The array is only good durring the length of the current session.
							
		$read_topics = $this->_fetch_read_topics();			
		
		/** -------------------------------------
		/**  Fetch the topic markers
		/** -------------------------------------*/
		
		$markers = $this->_fetch_topic_markers();
						
		/** -------------------------------------
		/**  Parse the results
		/** -------------------------------------*/
		
		if (preg_match("/".LD."switch\s*=\s*(\042|\047)([^\\1]*?)\\1".RD."/si", $template, $smatch))
		{
			$switches = explode('|', $smatch['2']);
		}
		
		$count = 0;
		$topics = '';						
		foreach ($query->result_array() as $row)
		{
			$temp = $template;
			$count++;
			
			/* -------------------------------------
			/*  'forum_topics_loop_start' hook.
			/*  - Modify the topic row template and data before any processing takes place
			/*  - Added Discussion Forums 1.3.2
			*/  
				if ($this->EE->extensions->active_hook('forum_topics_loop_start') === TRUE)
				{
					$temp = $this->EE->extensions->universal_call('forum_topics_loop_start', $this, $query->result(), $row, $temp);
					if ($this->EE->extensions->end_script === TRUE) return;
				}
			/*
			/* -------------------------------------*/
			
			/* -------------------------------------
			/*  Update last post info if needed
			/*  added 1.3.1
			/* -------------------------------------*/
			
			if ($row['last_post_id'] == 0 && $row['thread_total'] > 1)
			{
				$this->_update_topic_stats($row['topic_id']);
				$pquery = $this->EE->db->query("SELECT last_post_id FROM exp_forum_topics WHERE topic_id = '".$row['topic_id']."'");
				$row['last_post_id'] = $pquery->row('last_post_id') ;
			}
			
			/** -------------------------------------
			/**  Parse {if is_ignored}
			/** -------------------------------------*/
			
			if (in_array($row['author_id'], $this->EE->session->userdata['ignore_list']))
			{
				$temp = $this->_allow_if('is_ignored', $temp);
			}
			else
			{
				$temp = $this->_deny_if('is_ignored', $temp);
			}
			
			/** -------------------------------------
			/**  Assign the post marker (folder image)
			/** -------------------------------------*/
								
			$topic_type = '';
			
			if ((isset($read_topics[$row['topic_id']]) AND $read_topics[$row['topic_id']] > $row['last_post_date']) OR $this->EE->session->userdata('last_visit') > $row['last_post_date'])
			{
				if ($row['poll'] == 'y')
				{
					$topic_marker = $markers['poll_old'];
					$topic_type = "<span class='forumLightLinks'>".$this->EE->lang->line('poll_marker').'&nbsp;</span>';
				}
				else
				{
					$topic_marker = ($row['thread_total'] >= $hot_topic ) ? $markers['hot_old'] : $markers['old'];
				}
				
				$temp = $this->_deny_if('is_new', $temp);			
			}
			else
			{
				if ($row['poll'] == 'y')
				{
					$topic_marker = $markers['poll_new'];
					$topic_type = "<span class='forumLightLinks'>".$this->EE->lang->line('poll_marker').'&nbsp;</span>';
				}
				else
				{
					$topic_marker = ($row['thread_total'] >= $hot_topic ) ? $markers['hot'] : $markers['new'];
				}
			
				$temp = $this->_allow_if('new_topic', $temp);
				$temp = $this->_allow_if('is_new', $temp);			
			}
			
			if ($row['status'] == 'c')
			{
				$topic_marker = $markers['closed'];
				$topic_type = "<span class='forumLightLinks'>".$this->EE->lang->line('closed').'&nbsp;</span>';
			}
			
			if ($row['sticky'] == 'y')
			{
				$topic_marker = $markers['sticky'];
				$topic_type = "<span class='forumLightLinks'>".$this->EE->lang->line('sticky').'&nbsp;</span>';
			}
						
			if ($row['moved_forum_id'] != 0 AND $row['moved_forum_id'] == $this->current_id)
			{
				$topic_marker = $markers['moved'];
				$topic_type = "<span class='forumLightLinks'>".$this->EE->lang->line('moved').'&nbsp;</span>';
			}
			
			/** -------------------------------------
			/**  Do we need small pagination links?
			/** -------------------------------------*/
			
			$total_posts = ($row['thread_total'] - 1);
					
			if ($total_posts > $post_limit )
			{									
				$num_pages = intval($total_posts / $post_limit);

				if ($num_pages > 0)
				{
					if ($total_posts % $post_limit) 
					{
						$num_pages++;
					}
				}
								
				$links = "";
				$baselink = $this->_forum_path('/viewthread/'.$row['topic_id'].'/');
								
				for ($i = 0; $i < $num_pages; $i++)
				{					
					if ($i == 3 AND $num_pages >=5)
					{
						$i = $num_pages - 1;				
						$links .= ' &#8230; ';
					}
					
					$p = ($i == 0) ? '' : 'P'.($i * $post_limit).'/';
										
					$links .= "<a href='".$baselink.$p."'>".($i + 1)."</a> ";
				}
				
				$temp = str_replace('{pagelinks}', rtrim($links), $temp);				
				$temp = $this->_allow_if('pagelinks', $temp);
			}
			else
			{
				$temp = $this->_deny_if('pagelinks', $temp);
			}			
			
			/** -------------------------------------
			/**  Swap out the template variables
			/** -------------------------------------*/
			
			$temp = $this->_deny_if('new_topic', $temp);
			
			/** -------------------------------------
			/**  is_post / is_topic conditionals
			/** -------------------------------------*/
			
			if ($row['last_post_id'] != 0)
			{
				$temp = $this->_allow_if('is_post', $temp);
				$temp = $this->_deny_if('is_topic', $temp);
			}
			else
			{
				$temp = $this->_deny_if('is_post', $temp);
				$temp = $this->_allow_if('is_topic', $temp);
			}
			
			/** -------------------------------------
			/**  Parse {if is_author}
			/** -------------------------------------*/
			
			if ($this->EE->session->userdata('member_id') == $row['author_id'])
			{
				$temp = $this->_allow_if('is_author', $temp);
			}
			else
			{
				$temp = $this->_deny_if('is_author', $temp);
			}
			
			/** -------------------------------------
			/**  Replace {switch="foo|bar|..."}
			/** -------------------------------------*/
			
			if ( ! empty($switches))
			{
				$switch = $switches[($count + count($switches) - 1) % count($switches)];
				$temp = str_replace($smatch['0'], $switch, $temp);
			}
			
			/** ----------------------------------------
			/**  Swap the <div> id's for the javascript rollovers
			/** ----------------------------------------*/
			$this->cur_thread_row++;
			$alpha = array('1'=>'a','2'=>'b','3'=>'c','4'=>'d','5'=>'e','6'=>'f','7'=>'g','8'=>'h','9'=>'i','10'=>'j','11'=>'k','12'=>'l','13'=>'m','14'=>'n','15'=>'o','16'=>'p','17'=>'q','18'=>'r','19'=>'s','20'=>'t','21'=>'u','22'=>'v','23'=>'w','24'=>'x','25'=>'y','26'=>'z');
			$letter = '';
			
			for ($i=1; $i < 10; $i++)
			{
				if ($this->cur_thread_row <= 52)
					$letter = 'b';
				elseif ($this->cur_thread_row <= 78)
					$letter = 'c';
				elseif ($this->cur_thread_row <= 104)
					$letter = 'd';
				elseif ($this->cur_thread_row <= 130)
					$letter = 'e';
				elseif ($this->cur_thread_row <= 156)
					$letter = 'f';
				elseif ($this->cur_thread_row <= 182)
					$letter = 'g';
				elseif ($this->cur_thread_row <= 208)
					$letter = 'h';
				elseif ($this->cur_thread_row <= 234)
					$letter = 'i';
				elseif ($this->cur_thread_row <= 260)
					$letter = 'j';
			
				if ($this->cur_thread_row <= 26)
				{
					$swap = $alpha[$this->cur_thread_row].$i;
				}
				else
				{
					$tr = $this->cur_thread_row % 26 + 1;
					$swap = $letter.$alpha[$tr].$i;
				}
				
				$temp = str_replace('{id'.$i.'}', $swap, $temp);
			}
			
			/** ----------------------------------------
			/**  Load the typography class
			/** ----------------------------------------*/
			
			$this->EE->load->library('typography');
			$this->EE->typography->initialize();
			
			$title = ' '.$row['title'].' ';

			/** ----------------------------------------
			/**  Finalize the result
			/** ----------------------------------------*/
					
			$temp = $this->_var_swap($temp,
							array(
									'topic_marker'			=>	$topic_marker,
									'topic_type'			=>  $topic_type,
									'topic_title'			=>	trim($this->_convert_special_chars($this->EE->typography->format_characters($this->EE->typography->filter_censored_words($title)))),
									'author'				=>	$row['author'],
									'total_views'			=>	$row['thread_views'],
									'total_replies'			=>	$row['thread_total'] - 1,
									'reply_author'			=>	$row['last_post_author'],
									'path:post_link'		=>  $this->_forum_path('/viewreply/'.$row['last_post_id'].'/'),
									'path:member_profile'	=>	$this->_profile_path($row['author_id']),
									'path:view_thread'		=>	$this->_forum_path('/viewthread/'.$row['topic_id'].'/'),
									'path:reply_member_profile'	=> $this->_profile_path($row['last_post_author_id']),
									'path:ignore'			=>	$this->_forum_path("ignore_member/{$row['author_id']}"),
								)
							);
			
			/** -------------------------------------
			/**  Parse the "last_reply" date
			/** -------------------------------------*/
			if ($date !== FALSE AND $row['last_post_date'] != 0)
			{
				if (date('Ymd', $row['last_post_date']) == date('Ymd', $this->EE->localize->now))
				{	
					$dt = str_replace('%x', $this->EE->localize->format_timespan(($this->EE->localize->now - $row['last_post_date'])), $this->EE->lang->line('ago'));
				}
				else
				{
					$dt = $this->EE->localize->decode_date($date['1'], $row['last_post_date']);
				}
			}
			else
			{
				$dt = '-';
			}
			
			$temp = str_replace($date['0'], $dt, $temp);
			
			/* -------------------------------------
			/*  'forum_topics_loop_end' hook.
			/*  - Modify the processed topic row before it is appended to the template output
			/*  - Added Discussion Forums 1.3.2
			*/  
				if ($this->EE->extensions->active_hook('forum_topics_loop_end') === TRUE)
				{
					$temp = $this->EE->extensions->universal_call('forum_topics_loop_end', $this, $query->result(), $row, $temp);
					if ($this->EE->extensions->end_script === TRUE) return;
				}
			/*
			/* -------------------------------------*/
			
			// Complile the string
			
			$topics .= $temp;
		}

		$str = str_replace('{include:topic_rows}', $topics, $str);		
		
		/** -------------------------------------
		/**  Parse permissions
		/** -------------------------------------*/
		
		if ( ! $this->_permission('can_post_topics', unserialize(stripslashes($fdata[$this->current_id]['forum_permissions']))) OR $this->EE->session->userdata('member_id') == 0)			
		{
			$str = $this->_deny_if('can_post', $str, '&nbsp;');
		}
		else
		{
			$str = $this->_allow_if('can_post', $str);
		}
		
		/** -------------------------------------
		/**  Finalize the template
		/** -------------------------------------*/
		
		$str = $this->_var_swap( $str,
								array(
										'pagination_links'	=> $pagination,
										'current_page'		=> $current_page,
										'total_pages'		=> $total_pages,								
										'forum_name'		=> $this->_convert_special_chars($fdata[$this->current_id]['forum_name'], TRUE),
										'forum_description'	=> $fdata[$this->current_id]['forum_description'],
										'path:new_topic' 	=> $this->_forum_path('/newtopic/'.$this->current_id.'/')
									)
								);
		
		/* -------------------------------------
		/*  'forum_topics_absolute_end' hook.
		/*  - Modify the finalized topics template and do what you wish
		/*  - Added Discussion Forums 1.3.2
		*/  
			if ($this->EE->extensions->active_hook('forum_topics_absolute_end') === TRUE)
			{
				$str = $this->EE->extensions->universal_call('forum_topics_absolute_end', $this, $query->result(), $str);
				if ($this->EE->extensions->end_script === TRUE) return $str;
			}
		/*
		/* -------------------------------------*/
		
		return $str;
	}



	/** -------------------------------------
	/**  Thread Review for submission page
	/** -------------------------------------*/
	function thread_review()
	{
		if ( ! in_array($this->current_request, array('newreply', 'quotereply')))
		{
			return '';
		}
		else
		{
			return $this->threads(FALSE, TRUE);
		}
	
	}

	// --------------------------------------------------------------------

	/**
	 * Forum Threads
	 *
	 * @param 	boolean
	 * @param	boolean
	 * @param	boolean
	 */
	function threads($is_announcement = FALSE, $thread_review = FALSE, $is_split = FALSE)
	{
		$posts 			= '';
		$pagination 	= '';
		$query_limit	= '';
		$current_page	= 1;
		$total_pages	= 1;

		// Fetch/Set the "topic tracker" cookie
		$read_topics = $this->_fetch_read_topics($this->current_id);
		
		if ($this->EE->session->userdata('member_id') == 0)
		{
			$expire = 60*60*24*365;
			$this->EE->functions->set_cookie('forum_topics', serialize($read_topics), $expire);
		}
		else
		{
			if ($this->read_topics_exist === FALSE)
			{
				$d = array(
						'member_id'		=> $this->EE->session->userdata('member_id'),
						'board_id'		=> $this->_fetch_pref('board_id'),
						'topics'		=> serialize($read_topics),
						'last_visit'	=> $this->EE->localize->now
					);
				
				$this->EE->db->insert('forum_read_topics', $d);
			}
			else
			{
				$d = array(
						'topics'		=> serialize($read_topics),
						'last_visit'	=> $this->EE->localize->now
					);
				
				$this->EE->db->where('member_id', $this->EE->session->userdata('member_id'));
				$this->EE->db->where('board_id', $this->_fetch_pref('board_id'));
				$this->EE->db->update('forum_read_topics', $d);
			}
		}

		// Fetch The Topic
		$this->EE->db->select('f.forum_text_formatting, f.forum_html_formatting, f.forum_enable_rss,
								f.forum_auto_link_urls, f.forum_allow_img_urls, f.forum_hot_topic, 
								f.forum_post_order, f.forum_posts_perpage, f.forum_display_edit_date,
								t.forum_id, t.topic_id as post_id, t.author_id, t.ip_address, t.title, 
								t.body, t.status, t.announcement, t.thread_views, t.parse_smileys, 
								t.topic_date AS date, t.topic_edit_date AS edit_date, 
								t.topic_edit_author AS edit_author_id, em.screen_name AS edit_author,
								m.group_id, m.screen_name AS author, m.join_date, m.total_forum_topics, 
								m.total_forum_posts, m.location, m.email, m.accept_user_email, m.url, m.aol_im, 
								m.yahoo_im, m.msn_im, m.icq, m.signature, m.sig_img_filename, m.sig_img_width, 
								m.sig_img_height, m.avatar_filename, m.avatar_width, m.avatar_height, 
								m.photo_filename, m.photo_width, m.photo_height');
		$this->EE->db->from(array('forums f', 'forum_topics t', 'members m'));
		$this->EE->db->join('members em', 't.topic_edit_author = em.member_id', 'left');
		$this->EE->db->where('f.forum_id', 't.forum_id', FALSE);
		$this->EE->db->where('t.author_id = m.member_id');
		$this->EE->db->where('t.topic_id', $this->current_id);
		$tquery = $this->EE->db->get();

		if ($tquery->num_rows() == 0)
		{
			return $this->_trigger_error('thread_no_exists');
		}
		
		if ($tquery->row('forum_enable_rss')  == 'y')
		{
			$this->feeds_enabled = TRUE;
			$this->feed_ids = $tquery->row('forum_id') ;
		}
				
		// If it's an announcement, they are barking up the wrong tree
		if ($tquery->row('announcement')  != 'n')
		{
			$this->EE->functions->redirect($this->_forum_path('viewannounce/'.$this->current_id.'_'.$tquery->row('forum_id')));
			exit;
		}
		
		// Flip the notification flag
		if ($this->EE->session->userdata('member_id') != 0)
		{
			$this->EE->db->where('topic_id', $this->current_id);
			$this->EE->db->where('member_id', $this->EE->session->userdata('member_id'));
			$this->EE->db->update('forum_subscriptions', array('notification_sent' => 'n'));			
		}
		
		// Assign a few variables
		$order			= ($tquery->row('forum_post_order')  == 'a') ? 'asc' : 'desc';
		$forum_id		= $tquery->row('forum_id') ;	
		$limit 			= ($is_split == FALSE) ? $tquery->row('forum_posts_perpage')  : 100;
		$attach_base 	= '';
		
		if ($limit == 0)
		{
			$limit = 15;			
		}
		
		if ( ! is_numeric($limit) OR $limit == 0)
		{
			$limit = 15;
		}
				
		$formatting = array(
							'text_format'	=> $tquery->row('forum_text_formatting') ,
							'html_format'	=> $tquery->row('forum_html_formatting') ,
							'auto_links'	=> $tquery->row('forum_auto_link_urls') ,
							'allow_img_url' => $tquery->row('forum_allow_img_urls') 
							);

		// Load the template
		if ($is_split == FALSE)
		{		
			if ($thread_review == FALSE)
			{
				$str = $this->_load_element('threads');
			}
			else
			{
				$str = $this->_load_element('thread_review');
			}
		}
		else
		{
			// Are they allowed to split?
			if ( ! $this->_mod_permission('can_split', $tquery->row('forum_id') ))
			{
				return $this->_trigger_error();
			}
			
			$this->current_page = (isset($_POST['current_page']) && is_numeric($_POST['current_page'])) ? $this->EE->db->escape_str($_POST['current_page']) : 0;
			
			if ( isset($_POST['next_page']))
			{
				$this->current_page = $this->current_page + $limit;
			}
			elseif( isset($_POST['previous_page']) && ($this->current_page - $limit) >= 0)
			{
				$this->current_page = $this->current_page - $limit;
			}
			
			if ( isset($_POST['post_id']) && is_array($_POST['post_id']) && (isset($_POST['next_page']) OR isset($_POST['previous_page'])))
			{
				$i = 0;
				foreach($_POST['post_id'] as $id)
				{
					if (is_numeric($id))
					{
						$this->form_actions['forum:do_split']['post_id['.$i.']'] = $this->EE->db->escape_str($id); ++$i;
					}
				}
			}
		
			$str = $this->_load_element('split_data');

			// Are there any other forums?
			$this->EE->db->select('forum_name, forum_id');
			$this->EE->db->where('board_id', $this->_fetch_pref('board_id'));
			$this->EE->db->where('forum_is_cat', 'n');
			$this->EE->db->order_by('forum_order', 'asc');
			$f_query = $this->EE->db->get('forums');
					
			$menu = '';
			if ($f_query->num_rows() == 0)
			{
				$str = $this->_deny_if('forums_exist', $str);
			}
			else
			{
				$str = $this->_allow_if('forums_exist', $str);

				// Build the menu
				foreach ($f_query->result_array() as $row)
				{
					$selected = ($row['forum_id'] != $tquery->row('forum_id') ) ? '' : ' selected="selected"';
					$menu .= '<option value="'.$row['forum_id'].'"'.$selected.'>'.$row['forum_name'].'</option>';
				}			
			}
			
			$str = $this->_var_swap($str,
									array(
											'split_select_options'	=> $menu,
											'title' => $this->_convert_special_chars($tquery->row('title') )
										)
									);
		
			$this->form_actions['forum:do_split']['current_page'] = $this->current_page;
			$this->form_actions['forum:do_split']['topic_id'] = $this->current_id;
			$this->form_actions['forum:do_split']['RET'] = (isset($_POST['RET'])) ? $_POST['RET'] : $this->_forum_path('viewforum');
			
			if (isset($_POST['mbase']))
			{
				$this->form_actions['forum:do_split']['mbase'] = $_POST['mbase'];
			}
			
		}
		
		// Topic Jump
		if (strpos($str, '{next_topic_title}') === FALSE)
		{
			$str = $this->_deny_if('next_topic', $str, '');
		}
		else
		{
			// Next topic link
			$this->EE->db->select('topic_id, title');
			$this->EE->db->where('forum_id', $tquery->row('forum_id'));
			$this->EE->db->where('topic_id !=', $this->current_id);
			$this->EE->db->where('topic_date >', $tquery->row('date'));
			$this->EE->db->order_by('topic_id', 'ASC');
			$this->EE->db->limit(1);
			$jquery = $this->EE->db->get('forum_topics');
	
			if ($jquery->num_rows() == 0)
			{
				$str = $this->_deny_if('next_topic', $str, '');
			}
			else
			{
				$str = $this->_allow_if('next_topic', $str);
				$str = $this->_var_swap($str,
										array(
												'next_topic_title' 		=> trim($this->_convert_special_chars($jquery->row('title') )),
												'path:next_topic_url'	=> $this->_forum_path('/viewthread/'.$jquery->row('topic_id') .'/')
											)
										);
			}
		}
	
		if (strpos($str, '{previous_topic_title}') === FALSE)
		{
			$str = $this->_deny_if('next_topic', $str, '');
		}
		else
		{
			// Previous topic link
			$this->EE->db->where('forum_id', $tquery->row('forum_id'));
			$this->EE->db->where('topic_id !=', $this->current_id);
			$this->EE->db->where('topic_date <', $tquery->row('date'));
			$this->EE->db->order_by('topic_id', 'DESC');
			$this->EE->db->limit(1);
			$jquery = $this->EE->db->get('forum_topics');
	
			if ($jquery->num_rows() == 0)
			{
				$str = $this->_deny_if('previous_topic', $str, '');
			}
			else
			{
				$str = $this->_allow_if('previous_topic', $str);
				$str = $this->_var_swap($str,
										array(
												'previous_topic_title' 		=> trim($this->_convert_special_chars($jquery->row('title') )),
												'path:previous_topic_url'	=> $this->_forum_path('/viewthread/'.$jquery->row('topic_id') .'/')
											)
										);
			}
		}	

		// Post reply button

		if ($tquery->row('status')  == 'c')
		{
			$str = str_replace('{lang:post_reply}', $this->EE->lang->line('closed_thread'), $str);
		}
		
		// -------------------------------------------
		// 'forum_threads_template' hook.
		//  - Allows modifying of threads template before processing
		//
			if ($this->EE->extensions->active_hook('forum_threads_template') === TRUE)
			{
				$str = $this->EE->extensions->universal_call('forum_threads_template', $this, $str, $tquery);
				if ($this->EE->extensions->end_script === TRUE) return $str;
			}
		//
		// -------------------------------------------
		
		// Fetch the moderators and ranks
		$this->EE->db->select('rank_title, rank_min_posts, rank_stars');
		$this->EE->db->order_by('rank_min_posts');
		$rank_query = $this->EE->db->get('forum_ranks');

		$this->EE->db->select('mod_member_id, mod_group_id');
		$mod_query = $this->EE->db->get_where('forum_moderators', array('mod_forum_id' => $forum_id));

		//  Fetch the Super Admin IDs
		$super_admins = $this->fetch_superadmins();

		// Fetch attachments
		$this->EE->db->where('topic_id', $this->current_id);
		$this->EE->db->where('post_id', 0);
		
		$attach_query = $this->EE->db->get('forum_attachments');
		
		if ($attach_query->num_rows() == 0)
		{
			$attach_query = FALSE;
		}
		else
		{
			$attach_base = $this->EE->functions->fetch_site_index(0, $this->use_sess_id).(($this->EE->config->item('force_query_string') == 'y') ? '&amp;' : '?').'ACT='.$this->EE->functions->fetch_action_id('Forum', 'display_attachment').'&amp;fid='.$forum_id.'&amp;aid=';
		}

		// Update the views
		$views = ($tquery->row('thread_views')  <= 0) ? 1 : $tquery->row('thread_views')  + 1;
		
		$this->EE->db->where('topic_id', $this->current_id);
		$this->EE->db->update('forum_topics', array('thread_views' => $views));
		
		// Parse the template with the topic data
		$topic = $this->thread_rows(
									array (
											'query'			=> $tquery,
											'rank_query'	=> $rank_query,
											'mod_query'		=> $mod_query,
											'attach_query'	=> $attach_query,
											'attach_base'	=> $attach_base,
											'formatting'	=> $formatting,
											'super_admins'	=> $super_admins,
											'is_topic'		=> TRUE,
											'topic_id'		=> $tquery->row('post_id') ,
											'topic_status'	=> $tquery->row('status') ,
											'is_split'		=> $is_split
											),
									FALSE,
									$thread_review
									);

		$attach_query = FALSE;

		/** -------------------------------------
		/**  Count the total number of posts
		/** -------------------------------------*/
		// We do this for purposes of pagination
		// and to see if we even need to show anything
		// other than the topic
			
		$this->EE->db->select('COUNT(*) as count');	
		$pquery = $this->EE->db->get_where('forum_posts', array('topic_id' => $this->current_id));	
				
		if ($pquery->row('count')  > 0)
		{
			// No funny business with the page count allowed
			if ($this->current_page > $pquery->row('count') )
			{
				$this->current_page = 0;
			}

			// We have pagination!
			if (($pquery->row('count')  > $limit) AND $thread_review == FALSE)
			{
				$pagination = $this->_create_pagination(
										 	array(
													'first_url'		=> $this->_forum_path('/viewthread/'.$this->current_id.'/'),
													'path'			=> $this->_forum_path('/viewthread/'.$this->current_id.'/'),
													'total_count'	=> $pquery->row('count') ,
													'per_page'		=> $limit,
													'cur_page'		=> $this->current_page
										 		)
											);
											
				
				// Set the LIMIT for our query
				$query_limit = 'LIMIT '.$this->current_page.', '.$limit;
			
				// Set the stats for: {current_page} of {total_pages}
				$current_page = floor(($this->current_page / $limit) + 1);
				$total_pages = ceil($pquery->row('count')  / $limit);	
			}

			// Fetch the posts
			if ($thread_review == TRUE)
			{
				$order = 'desc';
				$query_limit = ' LIMIT 10';
			}
								
			$pquery = $this->EE->db->query("SELECT p.post_id, p.forum_id, p.author_id, p.ip_address, p.body, p.parse_smileys, p.post_date AS date, p.post_edit_date AS edit_date, p.post_edit_author AS edit_author_id, em.screen_name AS edit_author,
									m.group_id, m.screen_name AS author, m.join_date, m.total_forum_topics, m.total_forum_posts, m.location, m.email, m.accept_user_email, m.url, m.aol_im, m.yahoo_im, m.msn_im, m.icq, m.signature, m.sig_img_filename, m.sig_img_width, m.sig_img_height, m.avatar_filename, m.avatar_width, m.avatar_height, m.photo_filename, m.photo_width, m.photo_height,
									f.forum_display_edit_date
									FROM (exp_forum_posts p, exp_members m, exp_forums f)
									LEFT JOIN exp_members em ON p.post_edit_author = em.member_id
									WHERE p.author_id = m.member_id
									AND f.forum_id = p.forum_id
									AND p.topic_id = '{$this->current_id}'
									ORDER BY date {$order}
									{$query_limit}");	
										
			// Fetch attachments
			if ($pquery->num_rows() == 0)
			{			
				$attach_query = FALSE;
			}
			else
			{
				$sql = "SELECT * FROM exp_forum_attachments WHERE topic_id = '{$this->current_id}' AND post_id IN (";
		
				foreach ($pquery->result_array() as $row)
				{
					$sql .= $row['post_id'].',';
				}
		
				$sql = substr($sql, 0, -1).')';		
			
				$attach_query = $this->EE->db->query($sql);
				
				if ($attach_query->num_rows() == 0)
				{
					$attach_query = FALSE;
				}
				else
				{
					if ($attach_base == '')
						$attach_base = $this->EE->functions->fetch_site_index(0, $this->use_sess_id).(($this->EE->config->item('force_query_string') == 'y') ? '&amp;' : '?').'ACT='.$this->EE->functions->fetch_action_id('Forum', 'display_attachment').'&amp;fid='.$forum_id.'&amp;aid=';
				}
			}

			// arse Posts
			$posts = $this->thread_rows(
										array (
												'query'			=> $pquery,
												'rank_query'	=> $rank_query,
												'mod_query'		=> $mod_query,
												'attach_query'	=> $attach_query,
												'attach_base'	=> $attach_base,
												'formatting'	=> $formatting,
												'super_admins'	=> $super_admins,
												'is_topic'		=> FALSE,
												'topic_id'		=> $this->current_id,
												'topic_status'	=> $tquery->row('status') ,
												'is_split'		=> $is_split
												),
											FALSE,
											$thread_review
										);
		}	


		// Pagination
		if ($pagination == '')
		{
			$str = $this->_deny_if('paginate', $str, '&nbsp;');
			
			if ( $is_split === TRUE)
			{
				$str = $this->_deny_if('next_page', $str);
				$str = $this->_deny_if('previous_page', $str);
			}
		}
		else
		{
			$str = $this->_allow_if('paginate', $str);
			
			if ( $is_split === TRUE)
			{
				if ($current_page < $total_pages)
				{
					$str = $this->_allow_if('next_page', $str);
				}
				else
				{
					$str = $this->_deny_if('next_page', $str);
				}
				
				if ($this->current_page > 0)
				{
					$str = $this->_allow_if('previous_page', $str);
				}
				else
				{
					$str = $this->_deny_if('previous_page', $str);
				}
			}
		}
		
		// Create Subscription Link
		if ($this->EE->session->userdata('member_id') == 0)
		{
			$subscription_text = '';
			$subscription_path = '';
		}
		else
		{
			// Is the user subscribed?
			$this->EE->db->select('COUNT(*) as count');
			$query = $this->EE->db->get_where('forum_subscriptions', 
										array(
												'topic_id'	=> $this->current_id,
												'member_id'	=> $this->EE->session->userdata('member_id')
											));
	
			if ($query->row('count')  == 0)
			{
				$subscription_text = $this->EE->lang->line('subscribe_to_thread');
				$subscription_path = $this->_forum_path('/subscribe/'.$this->current_id.'/');
			}
			else
			{
				$subscription_text = $this->EE->lang->line('unsubscribe_to_thread');
				$subscription_path = $this->_forum_path('/unsubscribe/'.$this->current_id.'/');
			}
		}
		
		// Is there a poll?
		$this->EE->db->select('poll_id, poll_question, poll_answers, total_votes');
		$query = $this->EE->db->get_where('forum_polls', array('topic_id' => $this->current_id));
		
		if ($query->num_rows() == 0)
		{
			$str = $this->_deny_if('poll', $str, '');
			$poll = '';
		}
		else
		{			
			$answers = $this->array_stripslashes(unserialize($query->row('poll_answers') ));
			
			if ( ! is_array($answers))
			{
				$str = $this->_deny_if('poll', $str, '');
				$poll = '';
			}
			else
			{		
				$str = $this->_allow_if('poll', $str);
				$poll = $this->_generate_poll($query->row('poll_id') , 
										  	  $query->row('poll_question') , 
										  	  $answers, 
										  	  $query->row('total_votes') );
			}
		}	
				
		// Parse permissions
		$meta = $this->_fetch_forum_metadata($forum_id);
		$perms = unserialize(stripslashes($meta[$forum_id]['forum_permissions']));

		if ( ! $this->_permission('can_post_reply', $perms) OR ($tquery->row('status')  == 'c' AND $this->EE->session->userdata('group_id') != 1) OR $this->EE->session->userdata('member_id') == 0)			
		{
			$str = $this->_deny_if('can_post', $str, '&nbsp;');
		}
		else
		{
			$str = $this->_allow_if('can_post', $str);
		}


		if ( ! $this->_permission('can_post_topics', $perms) OR $this->EE->session->userdata('member_id') == 0)			
		{
			$str = $this->_deny_if('can_post_topics', $str, '&nbsp;');
		}
		else
		{
			$str = $this->_allow_if('can_post_topics', $str);
		}
		
		// Finalize Template
		if ($thread_review == TRUE)
		{
			if ($this->current_page > 0)
			{
				$thread_review_rows = $posts;
			}
			else
			{
				if ($order == 'asc')
				{
					$thread_review_rows = $topic.$posts;
				}
				else
				{
					$thread_review_rows = $posts.$topic;
				}
			}		
			$thread_rows = '';
		}
		else
		{
			if ($this->current_page > 0)
			{
				if ($current_page == $total_pages AND $order != 'asc')
				{
					$thread_rows = $posts.$topic;
				}
				else
				{
					$thread_rows = $posts;
				}
			}
			else
			{
				if ($order == 'asc')
				{
					$thread_rows = $topic.$posts;
				}
				else
				{
					if ($current_page < $total_pages)
					{
						$thread_rows = $posts;
					}
					else
					{
						$thread_rows = $posts.$topic;
					}
				}
			}
			
			$thread_review_rows = '';
		}
		
		// Load the typography class
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();
		
		$title = ' '.$tquery->row('title') .' ';		
		$title = $this->_convert_forum_tags($title);

		// Finalize the result
		$thread = ($is_split == FALSE ) ? 'thread_rows' : 'split_thread_rows';

		return $this->_var_swap($str,
								array(
										'topic_title'		=> trim($this->_convert_special_chars($this->EE->typography->format_characters($this->EE->typography->filter_censored_words($title)))),
										'pagination_links'	=> $pagination,
										'current_page'		=> $current_page,
										'total_pages'		=> $total_pages,
										'include:'.$thread  => $thread_rows,
										'include:thread_review_rows' => $thread_review_rows,
										'path:new_topic' 	=> $this->_forum_path('/newtopic/'.$this->current_id.'/'),
										'path:post_reply' 	=> $this->_forum_path('/newreply/'.$this->current_id.'/'),
										'path:thread_review' => $this->_forum_path('/viewthread/'.$this->current_id.'/'),
										'path:new_topic' 	=> $this->_forum_path('/newtopic/'.$forum_id.'/'),
										'lang:subscribe'	=> $subscription_text,
										'path:subscribe' 	=> $subscription_path,
										'include:poll'		=> $poll
									)
								);
	}

	// --------------------------------------------------------------------

	/**
	 * Generate Poll
	 *
	 * @param
	 * @param
	 * @param
	 * @param 
	 */
	function _generate_poll($poll_id, $question, $answers, $total_votes)
	{
		/** -------------------------------------
		/**  Only members can vote
		/** -------------------------------------*/
		
		// If a user is not logged in we'll treat them
		// as having voted and show the poll graph
		
		if ($this->EE->session->userdata('member_id') == 0)
		{
			$has_voted = TRUE;
		}
		else
		{
			/** -------------------------------------
			/**  Has the member already voted?
			/** -------------------------------------*/
		
			$query = $this->EE->db->query("SELECT COUNT(*) as count FROM exp_forum_pollvotes WHERE poll_id = '{$poll_id}' AND topic_id = '{$this->current_id}' AND member_id = '".$this->EE->session->userdata('member_id')."'");
			$has_voted = ($query->row('count')  == 0) ? FALSE : TRUE;
			
			/** -------------------------------------
			/**  Is the user casting a vote?
			/** -------------------------------------*/
			
			if  (isset($_POST['cast_vote']) AND isset($_POST['vote']) AND $has_voted == FALSE)
			{
				if (isset($answers[$_POST['vote']]['votes']))
				{
					// Update the vote array
				
					$answers[$_POST['vote']]['votes'] = ($answers[$_POST['vote']]['votes'] == 0) ? 1 : $answers[$_POST['vote']]['votes'] + 1;					
					$total_votes = ($total_votes == 0) ? 1 : ($total_votes + 1);
					
					$this->EE->db->query("UPDATE exp_forum_polls SET poll_answers = '".addslashes(serialize($answers))."', total_votes = '{$total_votes}' WHERE poll_id = '{$poll_id}'");
				
					$data = array(
									'poll_id'	=> $poll_id,
									'topic_id'	=> $this->current_id,
									'member_id'	=> $this->EE->session->userdata('member_id'),
									'choice_id' => $_POST['vote']
								);
				
					$this->EE->db->query($this->EE->db->insert_string('exp_forum_pollvotes', $data));	
				}
			
				$has_voted = TRUE;
			}
		}

		/** ----------------------------------------
		/**  Load the typography class
		/** ----------------------------------------*/
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();
		
		/** -------------------------------------
		/**  Build the output
		/** -------------------------------------*/
		
		// We build the display based on whether the person has voted or not.
		// People can only vote once.  If a user has not voted we'll show
		// the voting form, otherwise we'll show the stats graph
		
		if ($has_voted == FALSE)
		{
			$template = $this->_load_element('poll_questions');
			$poll_row = $this->_load_element('poll_question_rows');
			
			$rows	= '';
			$checked = FALSE;
			foreach ($answers as $key => $val)
			{
				$temp = $poll_row;
				
				$temp = str_replace('{value}', $key, $temp);
				
				// Security fix
				$val['answer'] = $this->_convert_forum_tags($val['answer']);
			
				$temp = str_replace('{poll_choice}', $this->_convert_special_chars($this->EE->typography->filter_censored_words($val['answer'])), $temp);
				
				if ($checked == FALSE)
				{
					$temp = str_replace('{checked}', "checked='checked'", $temp);
					$checked = TRUE;
				}
				else
				{
					$temp = str_replace('{checked}', '', $temp);
				}
				
				$rows .= $temp;
			}
			
			// Security fix
			$question = $this->_convert_forum_tags($question);

			$template = $this->_var_swap($template,
									array(
											'poll_question'	=> $this->_convert_special_chars($this->EE->typography->filter_censored_words($question)),
											'form_declaration'	=> "<form method='post' action='".$this->_forum_path('viewthread/'.$this->current_id)."' >",
											'include:poll_question_rows' => $rows
										)
									);
		}
		else
		{
			$template = $this->_load_element('poll_answers');
			$poll_row = $this->_load_element('poll_answer_rows');

			$img_l = trim($this->_load_element('poll_graph_left'));
			$img_m = trim($this->_load_element('poll_graph_middle'));
			$img_r = trim($this->_load_element('poll_graph_right'));

		
			$rows	= '';
			foreach ($answers as $key => $val)
			{
				$temp = $poll_row;
				
				$img = $img_l;
				
				$temp = str_replace('{poll_choice}', $this->_convert_special_chars($this->EE->typography->filter_censored_words($val['answer'])), $temp);
				$temp = str_replace('{votes}', $val['votes'], $temp);
				
				if ($val['votes'] > 0)
				{
					$num = abs($val['votes'] / $total_votes * 100);					
					$num = round(abs($num / 3));
					$img .= str_repeat($img_m, $num);
				}
				
				$img .= $img_r;
				
				$temp = str_replace('{vote_graph}', $img, $temp);

				$rows .= $temp;
			}
			
			// Security fix
			$question = $this->_convert_forum_tags($question);
			
			$template = $this->_var_swap($template,
									array(
											'poll_question'	=> $this->EE->typography->filter_censored_words($question),
											'include:poll_answer_rows' => $rows,
											'total_votes' => $total_votes,
											'lang:voter_message' => ($this->EE->session->userdata('member_id') == 0) ? $this->EE->lang->line('must_be_logged_to_vote') : $this->EE->lang->line('you_have_voted')
										)
									);
		}
	
		return $template;
	}

	// --------------------------------------------------------------------
	
	/**
	 * thread rows
	 *
	 * @param 	array
	 * @param	boolean
	 * @param 	boolean
	 */
	function thread_rows($data, $is_announcement = FALSE, $thread_review = FALSE)
	{
		// Variabl-ize the array keys
		foreach ($data as $key => $val)
		{
			$$key = $val;			
		}
		
		// Fetch template
		if ($is_announcement == TRUE)
		{
			$template = $this->_load_element('announcement');
			$is_split = FALSE;
		}
		else
		{
			if ($is_split === FALSE)
			{
				if ($thread_review === FALSE)
				{ 
					$template = $this->_load_element('thread_rows');			
				}
				else
				{
					$template = $this->_load_element('thread_review_rows');			
				}
			}
			else
			{
				$template = $this->_load_element('split_thread_rows');			
			}
		}
		
		// -------------------------------------------
		// 'forum_thread_rows_start' hook.
		//  - Allows usurping of forum thread rows display
		//
			if ($this->EE->extensions->active_hook('forum_thread_rows_start') === TRUE)
			{
				$template = $this->EE->extensions->universal_call('forum_thread_rows_start', $this, $template, $data, $is_announcement, $thread_review);
				if ($this->EE->extensions->end_script === TRUE) return $template;
			}
		//
		// -------------------------------------------
		
		$rank_class = 'rankMember';		
				
		//  Grab some variables which we'll use later
		$post_date  = ( ! preg_match_all("/{post_date\s+format=['|\"](.+?)['|\"]\}/i", $template, $matches)) ? FALSE : $matches;		
		$join_date  = ( ! preg_match_all("/{join_date\s+format=['|\"](.+?)['|\"]\}/i", $template, $matches)) ? FALSE : $matches;
		$edit_date  = ( ! preg_match_all("/{edit_date\s+format=['|\"](.+?)['|\"]\}/i", $template, $matches)) ? FALSE : $matches;
		
		$rank_stars = '';
		
		if (preg_match("/{if\s+rank_stars\}(.+?){\/if\}/i", $template, $matches))
		{
			$rank_stars = $matches['1'];
			$template = str_replace($matches['0'], '{rank_stars}', $template);
		}
		
		$iif = array('location', 'email', 'url', 'aol_im', 'yahoo_im', 'msn_im', 'icq');	
				
		// Load the typography class
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();
		$this->EE->typography->highlight_code = TRUE;
		
		// Loop through the result
		$thread_rows  = '';
		$rank_title	= '';
		$stars = '';
		$post_number = 1;
		
		if (preg_match("/".LD."switch\s*=\s*(\042|\047)([^\\1]*?)\\1".RD."/si", $template, $smatch))
		{
			$switches = explode('|', $smatch['2']);
		}
		
		foreach ($query->result_array() as $row)
		{		
			$temp = $template;
			$rank_title = '';
			
			/* -------------------------------------
			/*  'forum_thread_rows_loop_start' hook.
			/*  - Modify the thread row template and data before any processing takes place
			/*  - Added Discussion Forums 1.3.2
			*/  
				if ($this->EE->extensions->active_hook('forum_thread_rows_loop_start') === TRUE)
				{
					$temp = $this->EE->extensions->universal_call('forum_thread_rows_loop_start', $this, $data, $row, $temp);
					if ($this->EE->extensions->end_script === TRUE) return;
				}
			/*
			/* -------------------------------------*/
			
			/** -------------------------------------
			/**  Parse {if is_ignored}
			/** -------------------------------------*/
			
			if (in_array($row['author_id'], $this->EE->session->userdata['ignore_list']))
			{
				$temp = $this->_allow_if('is_ignored', $temp);
			}
			else
			{
				$temp = $this->_deny_if('is_ignored', $temp);
			}
			
			/** ----------------------------------------
			/**  Parse the member stuff (email, url, etc.)
			/** ----------------------------------------*/
			
			if ($row['accept_user_email'] == 'n')
			{
				$row['email'] == '';
				$temp = $this->_deny_if('accept_email', $temp);
			}
			else
			{
				$temp = $this->_allow_if('accept_email', $temp);
			}
			
			foreach ($iif as $var)
			{
				if ($row[$var] != '')
				{
					$temp = $this->_allow_if($var, $temp);
					$temp = str_replace('{'.$var.'}', $row[$var], $temp);
				}
				else
				{
					$temp = $this->_deny_if($var, $temp);
				}
			}
							
			$consoles = array(
						'aim_console'	=> "onclick=\"window.open('".$this->_profile_path('aim_console/'.$row['author_id'])."', '_blank', 'width=240,height=360,scrollbars=yes,resizable=yes,status=yes,screenx=5,screeny=5');\"",
						'icq_console'	=> "onclick=\"window.open('".$this->_profile_path('icq_console/'.$row['author_id'])."', '_blank', 'width=650,height=580,scrollbars=yes,resizable=yes,status=yes,screenx=5,screeny=5');\"",
						'yahoo_console'	=> "http://edit.yahoo.com/config/send_webmesg?.target=".$row['yahoo_im']."&amp;.src=pg",
						'email_console'	=> "onclick=\"window.open('".$this->_profile_path('email_console/'.$row['author_id'])."', '_blank', 'width=650,height=600,scrollbars=yes,resizable=yes,status=yes,screenx=5,screeny=5');\"",
					);
			
			
			foreach ($consoles as $key => $val)
			{
				$temp = str_replace('{'.$key.'}', $val, $temp);
			}

			// Assign the rank stars
			$total_posts = ($row['total_forum_topics'] + $row['total_forum_posts']);
		
			if ($rank_query->num_rows() > 0)
			{
				$num_stars = NULL;
				$rank_title = '';
				
				$i = 1;
				foreach ($rank_query->result_array() as $rank)
				{				
					if ($num_stars === NULL)
					{
						$num_stars	= $rank['rank_stars'];
						$rank_title	= $rank['rank_title']; 
					}
					
					if ($rank['rank_min_posts'] >= $total_posts)
					{ 
						$stars = str_repeat($rank_stars, $num_stars);
						break;
					}
					else
					{
						$num_stars	= $rank['rank_stars'];
						$rank_title = $rank['rank_title']; 
					}	
					
					if ($i++ == $rank_query->num_rows)
					{
						$stars = str_repeat($rank_stars,  $num_stars);
						break;
					}
				}
			}

			/** ----------------------------------------
			/**  Assign the member rank
			/** ----------------------------------------*/
			if ($this->_is_admin($row['author_id']))
			{
				$rank_class = 'rankAdmin';
				$rank_title = $this->EE->lang->line('administrator');
			}
			else
			{
				if ($mod_query->num_rows() > 0)
				{
					foreach ($mod_query->result_array() as $mod)
					{
						if ($mod['mod_member_id'] == $row['author_id'] OR $mod['mod_group_id'] == $row['group_id'])
						{
							$rank_class = 'rankModerator';
							$rank_title = $this->EE->lang->line('moderator');
							break;
						}
					}
				}				
			}
			
			/** ----------------------------------------
			/**  Parse the post date and join date
			/** ----------------------------------------*/
			for ($j = 0; $j < count($post_date[0]); $j++)
			{
				$temp = str_replace($post_date[0][$j], $this->EE->localize->decode_date($post_date['1'][$j], $row['date']), $temp);
			}
											
			for ($j = 0; $j < count($join_date[0]); $j++)
			{
				$temp = str_replace($join_date[0][$j], $this->EE->localize->decode_date($join_date['1'][$j], $row['join_date']), $temp);
			}

			// 2 minute window for edits
			if ($row['forum_display_edit_date'] == 'y' AND ($row['edit_date'] - $row['date']) > 120)
			{
				for ($j = 0; $j < count($edit_date[0]); $j++)
				{
					$temp = str_replace($edit_date[0][$j], $this->EE->localize->decode_date($edit_date[1][$j], $row['edit_date']), $temp);
				}
				
				$temp = str_replace(LD.'edit_author'.RD, $row['edit_author'], $temp);
				$temp = str_replace(LD.'edit_author_id'.RD, $row['edit_author_id'], $temp);
				$temp = str_replace('{path:edit_author_profile}', $this->_profile_path($row['edit_author_id']), $temp);
				
				$temp = $this->_allow_if('edited', $temp);
			}
			else
			{
				$temp = $this->_deny_if('edited', $temp);
			}

			/** ----------------------------------------
			/**  Parse the private stuff that only moderators can see
			/** ----------------------------------------*/
			
			// If we are showing an annoucement we'll kill the
			// MOVE, and ACTIVE TOPIC buttons since they are not needed
			
			if ($is_announcement == TRUE)
			{
				$temp = $this->_deny_if('can_move', $temp);
				$temp = $this->_deny_if('can_merge', $temp);
				$temp = $this->_deny_if('can_split', $temp);
			}
			
			$meta = $this->_fetch_forum_metadata($row['forum_id']);
			$perms = unserialize(stripslashes($meta[$row['forum_id']]['forum_permissions']));
			
			if ( ! $this->_permission('can_post_reply', $perms) OR ($topic_status == 'c' AND $this->EE->session->userdata('group_id') != 1) OR $this->EE->session->userdata('member_id') == 0)			
			{
				$temp = $this->_deny_if('can_post', $temp, '&nbsp;');
			}
			else
			{
				$temp = $this->_allow_if('can_post', $temp);
			}
			
			foreach (array('can_view_ip', 'can_move', 'can_merge', 'can_split', 'can_change_status') as $val)
			{
				if ($this->_mod_permission($val, $row['forum_id']))
				{
					$temp = $this->_allow_if($val, $temp);
				}
				else
				{
					$temp = $this->_deny_if($val, $temp);
				}
			}
			
			/** ----------------------------------------
			/**  Can they ban users?
			/** ----------------------------------------*/
			
			if ($this->_is_admin() && $this->EE->session->userdata('member_id') != $row['author_id'])
			{
				$temp = $this->_allow_if('can_ban', $temp);
			}
			else
			{
				$temp = $this->_deny_if('can_ban', $temp);
			}
			
			/** ----------------------------------------
			/**  Can they ignore users?
			/** ----------------------------------------*/
			
			if ($this->EE->session->userdata('member_id') != $row['author_id'])
			{
				$temp = $this->_allow_if('can_ignore', $temp);
			}
			else
			{
				$temp = $this->_deny_if('can_ignore', $temp);
			}

 			/** ----------------------------------------
			/**  Parse the "Delete" Button
			/** ----------------------------------------*/
												
			if (($this->EE->session->userdata('group_id') == 1) OR 
						$this->_mod_permission('can_delete', $row['forum_id']))
			{
				$temp = $this->_allow_if('can_delete', $temp);
			}
			else
			{
				$temp = $this->_deny_if('can_delete', $temp);
			}
			
			/** ----------------------------------------
			/**  Parse the "Edit" Button
			/** ----------------------------------------*/
			
			// Users can edit their own entries, and moderators (with edit privs) can edit other entires.
			// However, no one but super admins can edit their own entries
			
			$can_edit = FALSE;
			
			if ($this->EE->session->userdata('group_id') == 1 OR ($this->EE->session->userdata('member_id') == $row['author_id'])) 
			{
				$can_edit = TRUE;
			}
			
			if ($this->_mod_permission('can_edit', $row['forum_id']) AND ! in_array($row['author_id'], $super_admins) )
			{
				$can_edit = TRUE;
			}
									
			if ($can_edit)
			{
				$temp = $this->_allow_if('can_edit', $temp);
			}
			else
			{
				$temp = $this->_deny_if('can_edit', $temp);
			}
			
			/** ----------------------------------------
			/**  Parse the avatar
			/** ----------------------------------------*/
						
			if ($this->EE->config->item('enable_avatars') == 'y' AND $row['avatar_filename'] != '' AND $this->EE->session->userdata('display_avatars') == 'y' )
			{
				$avatar_path	= $this->EE->config->slash_item('avatar_url').$row['avatar_filename'];
				$avatar_width	= $row['avatar_width'];
				$avatar_height	= $row['avatar_height'];
				
				$temp = $this->_allow_if('avatar', $temp);
			}
			else
			{
				$avatar_path	= '';
				$avatar_width	= '';
				$avatar_height	= '';
				
				$temp = $this->_deny_if('avatar', $temp);
			}
			
			/** ----------------------------------------
			/**  Parse the photo
			/** ----------------------------------------*/
						
			if ($this->EE->config->item('enable_photos') == 'y' AND $row['photo_filename'] != '' AND $this->EE->session->userdata('display_photos') == 'y' )
			{
				$photo_path	= $this->EE->config->slash_item('photo_url').$row['photo_filename'];
				$photo_width	= $row['photo_width'];
				$photo_height	= $row['photo_height'];
				
				$temp = $this->_allow_if('photo', $temp);
			}
			else
			{
				$photo_path	= '';
				$photo_width	= '';
				$photo_height	= '';
				
				$temp = $this->_deny_if('photo', $temp);
			}
			
			
			/** ----------------------------------------
			/**  Are there attachments?
			/** ----------------------------------------*/
			if ( ! isset($attach_query) OR $attach_query === FALSE)
			{
				$temp = $this->_deny_if('attachments', $temp);
				
				$attachments = '';
			}
			else
			{
				$temp = $this->_allow_if('attachments', $temp);
				
				$attachments = $this->_parse_thread_attachments($attach_query, $attach_base, $data['is_topic'], $row['post_id']);
			}
			
			/** ----------------------------------------
			/**  Is there a signature?
			/** ----------------------------------------*/
			
			$signature = '';
			
			if ($this->EE->session->userdata('display_signatures') == 'y' AND ($row['signature'] != '' OR $row['sig_img_filename'] != ''))
			{			
				$temp = $this->_allow_if('signature', $temp);
				
				$signature = $this->_load_element('signature');
				
				if ($row['sig_img_filename'] == '')
				{
					$signature = $this->_deny_if('signature_image', $signature);
				}
				else
				{
					$signature = $this->_allow_if('signature_image', $signature);
					
					$signature = $this->_var_swap($signature,
													array(
															'path:signature_image'		=> 	$this->EE->config->slash_item('sig_img_url').$row['sig_img_filename'],
															'signature_image_width'		=> 	$row['sig_img_width'],
															'signature_image_height'	=> 	$row['sig_img_height']
														)
												);
				}

				$row['signature'] = $this->EE->typography->parse_type($row['signature'], array(
															'text_format'	=> 'xhtml',
															'html_format'	=> 'safe',
															'auto_links'	=> 'y',
															'allow_img_url' => $this->EE->config->item('sig_allow_img_hotlink')
															)
													);				
				
				$signature = str_replace('{signature}', $row['signature'], $signature);
			}
			else
			{
				$temp = $this->_deny_if('signature', $temp);
			}
			
			/** -------------------------------------
			/**  Parse the "Report" Button
			/** -------------------------------------*/
			
			if ( ! $this->_permission('can_report', $perms) OR $this->EE->session->userdata('member_id') == 0 OR $this->EE->session->userdata['member_id'] == $row['author_id'])
			{
				$temp = $this->_deny_if('can_report', $temp);
			}
			else
			{
				$temp = $this->_allow_if('can_report', $temp);
			}
			
			/** -------------------------------------
			/**  Parse {if is_author}
			/** -------------------------------------*/
			
			if ($this->EE->session->userdata('member_id') == $row['author_id'])
			{
				$temp = $this->_allow_if('is_author', $temp);
			}
			else
			{
				$temp = $this->_deny_if('is_author', $temp);
			}
			
			/** ----------------------------------------
			/**  Parse the topic-specific stuff
			/** ----------------------------------------*/
			
			if ($is_topic == TRUE)
			{
				$temp = $this->_allow_if('is_topic', $temp);
				$temp = $this->_deny_if('is_post', $temp);
				
				if ($this->_mod_permission('can_change_status', $row['forum_id']))
				{											
					$temp = $this->_var_swap($temp,
											array(
													'lang:change_status' 	=> ($row['status'] == 'o') ? $this->EE->lang->line('close_thread') : $this->EE->lang->line('activate_thread'),
													'path:change_status'	=> $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->EE->functions->fetch_action_id('Forum', 'change_status').'&amp;topic_id='.$row['post_id'].'&amp;board_id='.$this->_fetch_pref('board_id').'&amp;trigger='.$this->trigger,
													'css:status_button'		=> ($row['status'] == 'o') ? 'buttonStatusOff' : 'buttonStatusOn'
												)
											);				
				}
				if ($this->_mod_permission('can_move', $row['forum_id']))
				{
					$temp = $this->_var_swap($temp, array('path:move_topic' => $this->_forum_path('/movetopic/'.$row['post_id'].'/')));
				}			

				if ($this->_mod_permission('can_split', $row['forum_id']))
				{
					$temp = $this->_var_swap($temp, array('path:split_topic' => $this->_forum_path('/split/'.$row['post_id'].'/')));				
				}			

				if ($this->_mod_permission('can_merge', $row['forum_id']))
				{
					$temp = $this->_var_swap($temp, array('path:merge_topic' => $this->_forum_path('/merge/'.$row['post_id'].'/')));				
				}
			}
			else
			{
				$temp = $this->_deny_if('is_topic', $temp);
				$temp = $this->_allow_if('is_post', $temp);
				
				if ($this->_mod_permission('can_move', $row['forum_id']))
				{
					$temp = $this->_var_swap($temp, array('path:move_reply' => $this->_forum_path('/movereply/'.$row['post_id'].'/')));
				}
			}
			
			/** ----------------------------------------
			/**  Swap the <div> id's for the javascript rollovers
			/** ----------------------------------------*/
			$this->cur_thread_row++;
			$alpha = array('1'=>'a','2'=>'b','3'=>'c','4'=>'d','5'=>'e','6'=>'f','7'=>'g','8'=>'h','9'=>'i','10'=>'j','11'=>'k','12'=>'l','13'=>'m','14'=>'n','15'=>'o','16'=>'p','17'=>'q','18'=>'r','19'=>'s','20'=>'t','21'=>'u','22'=>'v','23'=>'w','24'=>'x','25'=>'y','26'=>'z');
			$letter = '';
			
			for ($i=1; $i < 11; $i++)
			{
				if ($this->cur_thread_row <= 52)
					$letter = 'b';
				elseif ($this->cur_thread_row <= 78)
					$letter = 'c';
				elseif ($this->cur_thread_row <= 104)
					$letter = 'd';
				elseif ($this->cur_thread_row <= 130)
					$letter = 'e';
				elseif ($this->cur_thread_row <= 156)
					$letter = 'f';
				elseif ($this->cur_thread_row <= 182)
					$letter = 'g';
				elseif ($this->cur_thread_row <= 208)
					$letter = 'h';
				elseif ($this->cur_thread_row <= 234)
					$letter = 'i';
				elseif ($this->cur_thread_row <= 260)
					$letter = 'j';
			
				if ($this->cur_thread_row <= 26)
				{
					$swap = $alpha[$this->cur_thread_row].$i;
				}
				else
				{
					$tr = $this->cur_thread_row % 26 + 1;
					$swap = $letter.$alpha[$tr].$i;
				}
				
				$temp = str_replace('{id'.$i.'}', $swap, $temp);
			}
		
			/** -------------------------------------
			/**  Replace {switch="foo|bar|..."}
			/** -------------------------------------*/
			
			if ( ! empty($switches))
			{
				$switch = $switches[($this->cur_thread_row + count($switches) - 1) % count($switches)];
				$temp = str_replace($smatch['0'], $switch, $temp);
			}
			
			/** ----------------------------------------
			/**  Parse the finalized template
			/** ----------------------------------------*/
			
			if ($row['parse_smileys'] == 'y')
			{
				$this->EE->typography->parse_smileys = TRUE;
			}
			else
			{
				$this->EE->typography->parse_smileys = FALSE;
			}
			
			// Keep includes from being parsed
			$row['body'] = $this->_convert_forum_tags($row['body']);
			
			$checked = '';
			
			if ($is_split == TRUE)
			{
				// if we are splitting threads we'll limit the word count
				$row['body'] = $this->EE->functions->word_limiter($row['body'], 25);
				
				// Make sure we don't innadvertently cut off the closing
				// [quote] or [code] tags -- if they exist
				foreach (array('quote', 'code') as $val)
				{
					if (strpos($row['body'], '['.$val.']') !== FALSE && strpos($row['body'], '[/'.$val.']') === FALSE)
					{
						$row['body'].= '[/'.$val.']';
					}
				}
				
				if ( isset($_POST['post_id']) && in_array($row['post_id'], $_POST['post_id']))
				{
					$checked = 'checked="checked"';
				}
			}

			$temp = $this->_var_swap($temp,
									array(
											'post_id'					=> $row['post_id'],
											'post_number'				=> $this->current_page + $post_number,
											'path:post_link'			=>  $this->_forum_path('/viewreply/'.$row['post_id'].'/'),
											'author'					=> $row['author'],
											'ip_address'				=> $row['ip_address'],
											'include:signature'			=> $signature,
											'total_posts'				=> $total_posts,
											'path:photos'				=> $photo_path,
											'photo_width'				=> $photo_width,
											'photo_height'				=> $photo_height,
											'path:avatars'				=> $avatar_path,
											'avatar_width'				=> $avatar_width,
											'avatar_height'				=> $avatar_height,
											'rank_class'				=> $rank_class,
											'rank_stars'				=> $stars,
											'rank_title'				=> $rank_title,
											'include:post_attachments'	=> $attachments,
											'checked'					=> $checked,
											'lang:ban_member'			=> ($row['group_id'] == 2) ? $this->EE->lang->line('member_is_banned') : $this->EE->lang->line('ban_member'),
											'path:ban_member'			=> $this->_forum_path('ban_member/'.$row['author_id']),
											'path:delete_post'			=> $this->_forum_path('/'.(($is_topic == TRUE) ? 'deletetopic' : 'deletereply').'/'.$row['post_id'].'/'),
											'path:edit_post'			=> $this->_forum_path('/'.(($is_topic == TRUE) ? 'edittopic' : 'editreply').'/'.$row['post_id'].'/'),
											'path:quote_reply'			=> $this->_forum_path('/'.(($is_topic == TRUE) ? 'quotetopic' : 'quotereply').'/'.$row['post_id'].'/'),
											'path:report'				=> $this->_forum_path('/'.(($is_topic == TRUE) ? 'reporttopic' : 'reportreply').'/'.$row['post_id'].'/'),
											'path:ignore'				=> $this->_forum_path("ignore_member/{$row['author_id']}"),
											'path:member_profile'		=> $this->_profile_path($row['author_id']),
											'path:send_private_message'	=> $this->_profile_path('messages/pm/'.$row['author_id']),
											'path:send_pm'				=> $this->_profile_path($row['author_id']),
											'body'						=> $this->_quote_decode($this->EE->typography->parse_type($row['body'], 
																				 								  array(
																														'text_format'	=> $formatting['text_format'],
																														'html_format'	=> $formatting['html_format'],
																														'auto_links'	=> $formatting['auto_links'],
																														'allow_img_url' => $formatting['allow_img_url']
																														)
																												  )
																								)
										)
									);
				
				/* -------------------------------------
				/*  'forum_thread_rows_loop_end' hook.
				/*  - Modify the processed row before it is appended to
				/*  	the template output
				/*  - Added Discussion Forums 1.3.2
				*/  
					if ($this->EE->extensions->active_hook('forum_thread_rows_loop_end') === TRUE)
					{
						$temp = $this->EE->extensions->universal_call('forum_thread_rows_loop_end', $this, $data, $row, $temp);
						if ($this->EE->extensions->end_script === TRUE) return;
					}
				/*
				/* -------------------------------------*/
								
				$rank_class = 'rankMember';		
				$thread_rows .= $temp;
				$post_number++;
			}
		
		/* -------------------------------------
		/*  'forum_thread_rows_absolute_end' hook.
		/*  - Take the processed thread rows and do what you wish
		/*  - Added Discussion Forums 1.3.2
		*/  
			if ($this->EE->extensions->active_hook('forum_thread_rows_absolute_end') === TRUE)
			{
				$thread_rows = $this->EE->extensions->universal_call('forum_thread_rows_absolute_end', $this, $data, $thread_rows);
				if ($this->EE->extensions->end_script === TRUE) return $thread_rows;
			}
		/*
		/* -------------------------------------*/
	
		return $thread_rows;
	}

	// --------------------------------------------------------------------

	/**
	 * New Forum Submission Page
	 *
	 * @param
	 * @param
	 * @param
	 * @param
	 */
	function _parse_thread_attachments($query, $attach_path, $is_topic, $post_id)		
	{
		
		$img_path = $this->_fetch_pref('board_upload_path');
		
		$thumb_str	= '';
		$image_str	= '';
		$file_str	= '';

		foreach ($query->result_array() as $row)
		{
			if ($is_topic == FALSE AND $row['post_id'] != $post_id)
			{
				continue;
			}
		
			if ($row['is_image'] == 'y')
			{
				/** -------------------------------------
				/**  Parse Thumbnail
				/** -------------------------------------*/
					
				if (file_exists($img_path.$row['filehash'].'_t'.$row['extension']) AND $this->_fetch_pref('board_use_img_thumbs') == 'y')
				{
					$thumb_str .= $this->_var_swap($this->_load_element('thumb_attachments'),
													array(
															'filename'			=> $row['filename'],
															'thumb_width'		=> $row['t_width'],
															'thumb_height'		=> $row['t_height'],
															'width'				=> $row['width'],
															'height'			=> $row['height'],
															'hits'				=> $row['hits'],
															'file_size'			=> $row['filesize'].'KB',
															'attach_thumb_url'	=> $attach_path.$row['filehash'].'&amp;thumb=1&amp;board_id='.$this->_fetch_pref('board_id'),
															'attach_image_url'	=> $attach_path.$row['filehash'].'&amp;board_id='.$this->_fetch_pref('board_id')
														)
													);
					continue;
				}

				/** -------------------------------------
				/**  Parse Full-size Image
				/** -------------------------------------*/
				if (file_exists($img_path.$row['filehash'].$row['extension']))
				{
					$image_str .= $this->_var_swap($this->_load_element('image_attachments'),
													array(
															'filename'			=> $row['filename'],
															'width'				=> $row['width'],
															'height'			=> $row['height'],
															'hits'				=> $row['hits'],
															'file_size'			=> $row['filesize'].'KB',
															'attach_image_url'	=> $attach_path.$row['filehash'].'&amp;board_id='.$this->_fetch_pref('board_id')
														)
													);
					continue;
				}
			}
			
			/** -------------------------------------
			/**  Is the attachment a file?
			/** -------------------------------------*/
			
			else
			{
				$file_str .= $this->_var_swap($this->_load_element('file_attachments'),
												array(
														'filename'			=> $row['filename'],
														'hits'				=> $row['hits'],
														'file_size'			=> $row['filesize'].'KB',
														'attach_file_url'	=> $attach_path.$row['filehash'].'&amp;board_id='.$this->_fetch_pref('board_id')
													)
												);
				continue;
			}

		}


		$str = $this->_load_element('post_attachments');
		
		
		if ($thumb_str == '')
		{
			$str = str_replace("{include:thumb_attachments}", '',	$str);
			$str = $this->_deny_if('thumb_attach',	$str);			
		}
		else
		{
			$str = $this->_allow_if('thumb_attach', $str);			
			$str = str_replace("{include:thumb_attachments}", $thumb_str,	$str);				
		}
		
		if ($image_str == '')
		{
			$str = str_replace("{include:image_attachments}", '',	$str);
			$str = $this->_deny_if('image_attach',	$str);			
		}
		else
		{
			$str = $this->_allow_if('image_attach', $str);			
			$str = str_replace("{include:image_attachments}", $image_str,	$str);				
		}
		
		if ($file_str == '' OR $this->_fetch_pref('board_attach_types') == 'img')
		{
			$str = str_replace("{include:file_attachments}", '',	$str);
			$str = $this->_deny_if('file_attach', $str);			
		}
		else
		{
			$str = $this->_allow_if('file_attach', $str);			
			$str = str_replace("{include:file_attachments}", $file_str,	$str);				
		}
				
		return $str;	
	}

	
	
	function display_attachment()
	{
		$attach_hash = $this->EE->input->get_post('aid');
		$forum_id  = $this->EE->input->get_post('fid');

		if ( ! preg_match('/^[a-z0-9_]*$/i', $attach_hash) OR ! is_numeric($forum_id))
		{
			exit;
		}
		
		if (FALSE === ($meta = $this->_fetch_forum_metadata($forum_id)))
		{
			exit;
		}

		if ( ! $this->_permission('can_view_topics', unserialize(stripslashes($meta[$forum_id]['forum_permissions']))))			
		{
			exit;
		}
		
		$this->EE->db->select('filehash, filename, extension, hits, is_image');
		$this->EE->db->where('filehash', $attach_hash);
		$query = $this->EE->db->get('forum_attachments');
		
		if ($query->num_rows() == 0)
		{
			exit;
		}

		$thumb_prefix =  ($query->row('is_image')  == 'y' AND $this->_fetch_pref('board_use_img_thumbs') == 'y' AND $this->EE->input->get_post('thumb') == 1) ? '_t' : '';
	
		$filepath = $this->_fetch_pref('board_upload_path').$query->row('filehash') .$thumb_prefix.$query->row('extension') ;
		
		$extension = strtolower(str_replace('.', '', $query->row('extension') ));
			
		$this->_fetch_mimes();
			
		if ( ! file_exists($filepath) OR ! isset($this->mimes[$extension]))
		{
			exit;
		}
		
		if ($this->_fetch_pref('board_attach_types') == 'img')
		{			
			if ( ! in_array($extension, array('jpg', 'jpeg', 'png', 'gif')))
			{
				exit;
			}
		}		
		
		$hits = ($query->row('hits')  == 0) ? 1 : ($query->row('hits')  + 1);
		
		$this->EE->db->set('hits', $hits);
		$this->EE->db->where('filehash', $attach_hash);
		$this->EE->db->update('forum_attachments');
		
		if ($this->mimes[$extension] == 'html')
		{
			$mime = 'text/html';
		}
		else
		{
			$mime = (is_array($this->mimes[$extension])) ? $this->mimes[$extension][0] : $this->mimes[$extension];
		}

		if ($query->row('is_image')  == 'y')
		{
			$attachment = '';
		}
		else
		{
			$attachment = (isset($_SERVER['HTTP_USER_AGENT']) AND strstr($_SERVER['HTTP_USER_AGENT'], "MSIE")) ? "" : " attachment;";
		}
		
		header('Content-Disposition: '.$attachment.' filename="'.$query->row('filename') .'"');		
		header('Content-Type: '.$mime);
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: '.filesize($filepath));
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', $this->EE->localize->now).' GMT'); 
		header("Cache-Control: public");	

		if ( ! $fp = @fopen($filepath, FOPEN_READ))
		{
			exit;
		}
		
		fpassthru($fp);
		@fclose($fp);
		exit;
		
	}
	

	/** -------------------------------------
	/**  New Forum Submission Page
	/** -------------------------------------*/
	function new_topic_page()  { return $this->submission_page('new_topic');}
	function edit_topic_page() { return $this->submission_page('edit_topic');}
	function new_reply_page()  { return $this->submission_page('new_reply'); }
	function edit_reply_page() { return $this->submission_page('edit_reply');}

	function submission_page($type = '')
	{
		/** --------------------------------
		/**  Is the user logged-in?
		/** --------------------------------*/
		// If no, show the login page
		
		if ($this->EE->session->userdata('member_id') == 0)
		{ 
			return $this->_trigger_error();
		}		
		
		// -------------------------------------------
		// 'forum_submission_page' hook.
		//  - Allows usurping of forum submission forms
		//  - More error checking and permissions too
		//
			$edata = $this->EE->extensions->universal_call('forum_submission_page', $this, $type);
			if ($this->EE->extensions->end_script === TRUE) return $edata;
		//
		// -------------------------------------------
				
		/** --------------------------------
		/**  Fetch the Forums Prefs
		/** --------------------------------*/
		
		// There are four possibilites.  The user is either:
		// Submitting a new topic
		// Submitting a new post
		// Editing a topic
		// Editing a post

		// Furthermore, the user might be an admin or moderator editing
		// someone else's post, so we have to account for all those things.
		
		// In each condition we need to fetch the forum preferences and
		// a few other things in order to determine if the action is allowed.
		// We need to fetch polling data too
		
		$data = array(
						'type'					=> $type,
						'forum_id'				=> '',
						'forum_name'			=> '',
						'topic_id'				=> '',
						'post_id'				=> '',
						'status'				=> 'o',
						'sticky'				=> 'n',
						'announcement'			=> 'n',
						'title'					=> '',
						'body'					=> '',
						'poll_enabled'			=> TRUE,
						'poll_question'			=> '',
						'poll_answers'			=> '',
						'forum_max_post_chars'	=> $this->max_chars,
						'forum_allow_img_urls'	=> 'n'
					);
					
		/** --------------------------------
		/**  Submitting a New Topic
		/** --------------------------------*/
		
		if ($this->current_request == 'newtopic')
		{
			if (FALSE === ($meta = $this->_fetch_forum_metadata($this->current_id)))
			{
				return $this->_trigger_error();
			}
								
			if ($meta[$this->current_id]['forum_is_cat'] == 'y')
			{
				return $this->_trigger_error('not_authorized');
			}
			
			$data['forum_id']	 = $this->current_id;
			$data['forum_name']	 = $meta[$this->current_id]['forum_name'];
			$data['permissions'] = $meta[$this->current_id]['forum_permissions'];
			$data['forum_max_post_chars'] = $meta[$this->current_id]['forum_max_post_chars'];		
			$data['forum_allow_img_urls'] = $meta[$this->current_id]['forum_allow_img_urls'];				
		}
		
		/** --------------------------------
		/**  Submitting a New Post
		/** --------------------------------*/
		elseif ($this->current_request == 'newreply' OR $this->current_request == 'quotetopic' OR $this->current_request == 'quotereply')
		{
			// We have to fetch the body of the quoted post and wrap it in [quote] tags
		
			if ($this->current_request == 'quotereply')
			{
				if (FALSE === ($meta = $this->_fetch_post_metadata($this->current_id)))
				{ 
					return $this->_trigger_error();
				}

				// Load the form and string helper
				$this->EE->load->helper(array('security', 'form'));

				$data['body'] = '[quote author="'.$this->_convert_special_chars($meta[$this->current_id]['screen_name']).'" date="'.$meta[$this->current_id]['post_date'].'"]'.
								str_replace('&amp;#40;', '&#40;', encode_php_tags(form_prep($meta[$this->current_id]['body']))).
								'[/quote]';					
				
				$this->current_id = $meta[$this->current_id]['topic_id'];
			}
			
			if (FALSE === ($meta = $this->_fetch_topic_metadata($this->current_id)))
			{
				return $this->_trigger_error();
			}
				
				
			if ($meta[$this->current_id]['forum_is_cat'] == 'y')
			{
				return $this->_trigger_error('not_authorized');
			}
								
			// If a TOPIC is being "quoted" we have to wrap the body in [quote] tags
		
			if ($this->current_request == 'quotetopic')
			{ 
				// Load the form helper
				$this->EE->load->helper(array('security', 'form'));

				$data['body'] = '[quote author="'.$this->_convert_special_chars($meta[$this->current_id]['screen_name']).'" date="'.$meta[$this->current_id]['topic_date'].'"]'.
								str_replace('&amp;#40;', '&#40;', encode_php_tags(form_prep($meta[$this->current_id]['body']))).
								'[/quote]';
			}
			
			$data['title']		 = $meta[$this->current_id]['title'];
			$data['topic_id']	 = $this->current_id;
			$data['forum_id']	 = $meta[$this->current_id]['forum_id'];
			$data['forum_name']	 = $meta[$this->current_id]['forum_name'];
			$data['permissions'] = $meta[$this->current_id]['forum_permissions'];
			$data['forum_max_post_chars'] = $meta[$this->current_id]['forum_max_post_chars'];
			$data['forum_allow_img_urls'] = $meta[$this->current_id]['forum_allow_img_urls'];			
		}
				
		/** --------------------------------
		/**  Editing a topic
		/** --------------------------------*/
		elseif ($this->current_request == 'edittopic')
		{		
			if (FALSE === ($meta = $this->_fetch_topic_metadata($this->current_id)))
			{
				return $this->_trigger_error();
			}

			if ($meta[$this->current_id]['forum_is_cat'] == 'y')
			{
				return $this->_trigger_error('not_authorized');
			}
	
			// If the user performing the edit is not the original author
			// we'll verify that they have the proper permissions
			
			if ($meta[$this->current_id]['author_id'] != $this->EE->session->userdata('member_id')  AND ! $this->_mod_permission('can_edit', $meta[$this->current_id]['forum_id']))
			{
				return $this->_trigger_error('not_authorized');
			}

			// Load the form helper
			$this->EE->load->helper(array('security', 'form'));
	
			$data['title']		 			= $meta[$this->current_id]['title'];
			$data['body']		 			= encode_php_tags(form_prep($meta[$this->current_id]['body']));
			$data['body'] 					= str_replace('&amp;#40;', '&#40;', $data['body']);
			$data['forum_id']	 			= $meta[$this->current_id]['forum_id'];
			$data['status']	 	 			= $meta[$this->current_id]['status'];
			$data['sticky']	 	 			= $meta[$this->current_id]['sticky'];
			$data['announcement']			= $meta[$this->current_id]['announcement'];
			$data['forum_name']	 			= $meta[$this->current_id]['forum_name'];
			$data['author_id']	 			= $meta[$this->current_id]['author_id'];
			$data['permissions'] 			= $meta[$this->current_id]['forum_permissions'];
			$data['forum_max_post_chars'] 	= $meta[$this->current_id]['forum_max_post_chars'];
			$data['forum_allow_img_urls'] 	= $meta[$this->current_id]['forum_allow_img_urls'];			
			$data['topic_id']	 			= $this->current_id;
			
			// Fetch poll data if it exists
			
			if ( ! isset($_POST['option']))
			{			
				$query = $this->EE->db->query("SELECT poll_question, poll_answers FROM exp_forum_polls WHERE topic_id = '{$this->current_id}'");
				
				if ($query->num_rows() == 1)
				{
					$data['poll_question']	= stripslashes($query->row('poll_question') );
					$data['poll_answers']	= $this->array_stripslashes(unserialize($query->row('poll_answers') ));
				}
			}
		}
		
		/** --------------------------------
		/**  Editing a Post
		/** --------------------------------*/
		elseif ($this->current_request == 'editreply')
		{
			if (FALSE === ($meta = $this->_fetch_post_metadata($this->current_id)))
			{
				return $this->_trigger_error();
			}
			
			if ($meta[$this->current_id]['forum_is_cat'] == 'y')
			{
				return $this->_trigger_error('not_authorized');
			}
			
			// If the user performing the edit is not the orginal author we'll verify that they have the proper permissions

			if ($meta[$this->current_id]['author_id'] != $this->EE->session->userdata('member_id') AND ! $this->_mod_permission('can_edit', $meta[$this->current_id]['forum_id']))
			{
				return $this->_trigger_error('not_authorized');
			}

			// Load the form helper
			$this->EE->load->helper(array('security', 'form'));
			
			$data['title']		 = $meta[$this->current_id]['title'];
			$data['body']		 = encode_php_tags(form_prep($meta[$this->current_id]['body']));
			$data['body'] 		 = str_replace('&amp;#40;', '&#40;', $data['body']);
			$data['topic_id']	 = $meta[$this->current_id]['topic_id'];
			$data['forum_id']	 = $meta[$this->current_id]['forum_id'];
			$data['forum_name']	 = $meta[$this->current_id]['forum_name'];
			$data['permissions'] = $meta[$this->current_id]['forum_permissions'];
			$data['author_id']	 = $meta[$this->current_id]['author_id'];
			$data['forum_max_post_chars'] = $meta[$this->current_id]['forum_max_post_chars'];
			$data['forum_allow_img_urls'] = $meta[$this->current_id]['forum_allow_img_urls'];			
			$data['post_id']	 = $this->current_id;
		}

		/** -------------------------------------
		/**  Are RSS Feeds enabled?
		/** -------------------------------------*/
		
		if ($meta[$this->current_id]['forum_enable_rss']== 'y')
		{
			$this->feeds_enabled = TRUE;
			$this->feed_ids = $data['forum_id'];
		}
		
		/** -------------------------------------
		/**  Check the author permmissions
		/** -------------------------------------*/
		$data['permissions'] = unserialize(stripslashes($data['permissions']));

		if (in_array($this->current_request, array('newtopic', 'edittopic')) && ! $this->_permission('can_post_topics', $data['permissions']))			
		{
			if ( ! $this->_mod_permission('can_edit', $data['forum_id']))
			{
				return $this->_trigger_error('not_authorized');
			}
		}
		
		if (in_array($this->current_request, array('newreply', 'editreply')) && ! $this->_permission('can_post_reply', $data['permissions']))			
		{
			if ( ! $this->_mod_permission('can_edit', $data['forum_id']))
			{
				return $this->_trigger_error('not_authorized');
			}
		}
		
		/** -------------------------------------
		/**  Load the form
		/** -------------------------------------*/
				
		return $this->_var_swap($this->_load_element('submission_page'), 
								array(
										'include:submission_form'	=> $this->_submission_form($data),
										'include:topic_review'		=> $this->thread_review(),
										'lang:max_attach_size'		=> $this->EE->lang->line('max_attach_size').'&nbsp;'.$this->_fetch_pref('board_max_attach_size').'KB'
										)
								);		
	}



	// -------------------------------------

	//  Forum Submission Form
	// -------------------------------------	

	function _submission_form($data)
	{
		/** -------------------------------------
		/**  Load Template
		/** -------------------------------------*/
										
		$str = $this->_load_element('submission_form');
		
		// -------------------------------------------
		// 'forum_submission_form_start' hook.
		//  - Allows usurping of forum submission form
		//
			if ($this->EE->extensions->active_hook('forum_submission_form_start') === TRUE)
			{
				$str = $this->EE->extensions->universal_call('forum_submission_form_start', $this, $str);
				if ($this->EE->extensions->end_script === TRUE) return $str;
			}
		//
		// -------------------------------------------
		
		
		/** -------------------------------------
		/**  Spell Check
		/** -------------------------------------*/
		
		if ( ! defined('NL'))  define('NL',  "\n");
		
		if ( ! class_exists('EE_Spellcheck'))
		{
			require APPPATH.'libraries/Spellcheck'.EXT;
		}
		
		if ($this->SPELL === FALSE)
		{
			$this->SPELL = new EE_Spellcheck();
			$this->spellcheck_enabled = $this->SPELL->enabled;
		}
						
		if ($this->spellcheck_enabled === TRUE)
		{
			$str = $this->_allow_if('spellcheck', $str);
		}
		else
		{
			$str = $this->_deny_if('spellcheck', $str);
		}
		
		$this->EE->lang->loadfile('spellcheck');
		
		/** -------------------------------------
		/**  Swap the "submit" button for "update" if editing
		/** -------------------------------------*/
		if ($this->current_request == 'edittopic' OR $this->current_request == 'editreply')
		{
			$str = str_replace('{lang:submit_post}', '{lang:update_post}', $str);
		}
						
		/** -------------------------------------
		/**  Are they submitting a topic?
		/** -------------------------------------*/
				
		if (in_array($this->current_request, array('newtopic', 'edittopic')))
		{				
			$this->form_actions['forum:submit_post']['RET'] = $this->_forum_path('/viewforum/'.$this->current_id.'/');
						
			// If we are editing a topic we'll set the topic_id as a hidden field
							
			if ($data['topic_id'] != '')
			{			
				$this->form_actions['forum:submit_post']['topic_id'] = $data['topic_id'];
			}
									
			/** -------------------------------------
			/**  Can they access posting options?
			/** -------------------------------------*/
						
			$show_more_options = FALSE;
			
			if ( ! $this->_mod_permission('can_change_status', $data['forum_id']))
			{
				$str = $this->_deny_if('can_change_status', $str);
			}
			else
			{
				$show_more_options = TRUE;
			}

			if ( ! $this->_mod_permission('can_announce', $data['forum_id']))
			{
				$str = $this->_deny_if('can_announce', $str);
			}			
			else
			{
				$show_more_options = TRUE;
			}

			if ( ! $this->_mod_permission('is_moderator', $data['forum_id']))
			{
				$str = $this->_deny_if('is_moderator', $str);
			}
			else
			{
				$show_more_options = TRUE;
			}

			if ($show_more_options == TRUE)
			{
				$str = $this->_allow_if('show_more_options', $str);
			}
			else
			{
				$str = $this->_deny_if('show_more_options', $str);
			}
			
			/** -------------------------------------
			/**  Swap the vars...
			/** -------------------------------------*/
			
			$str = str_replace('{sticky_checked}', (($data['sticky'] == 'y' OR $this->EE->input->get_post('sticky') == 'y') ? ' checked="checked" ' : ''), $str);
			$str = str_replace('{status_checked}', (($data['status'] == 'c' OR $this->EE->input->get_post('status') == 'c') ? ' checked="checked" ' : ''), $str);
			$str = str_replace('{announce_checked}', (($data['announcement'] != 'n' OR $this->EE->input->get_post('announcement') != FALSE) ? ' checked="checked" ' : ''), $str);
			$str = str_replace('{type_all_checked}', (($data['announcement'] == 'n' OR $data['announcement'] == 'a' OR $this->EE->input->get_post('ann_type') == 'a') ? ' checked="checked" ' : ''), $str);
			$str = str_replace('{type_one_checked}', (($data['announcement'] == 't' OR $this->EE->input->get_post('ann_type') == 't') ? ' checked="checked" ' : ''), $str);
			
			$str = $this->_allow_if('is_topic', $str);
			$str = $this->_deny_if('is_post', $str);	
			$str = $this->_allow_if('is_moderator', $str);
			$str = $this->_allow_if('can_announce', $str);
			$str = $this->_allow_if('can_change_status', $str);
			
			
			// Only moderators with edit privileges or admins can edit polls
			
			if ($this->current_request == 'edittopic')
			{
				if ($this->_mod_permission('can_edit', $data['forum_id']) OR ! is_array($data['poll_answers']))
				{
					$str = $this->_allow_if('can_post_poll', $str);	
				}
				else
				{
					$str = $this->_deny_if('can_post_poll', $str);	
				
					if (is_array($data['poll_answers']))
					{
						$this->form_actions['forum:submit_post']['poll_exists'] = 1;
					}				
				}
			}
			else
			{
				$str = $this->_allow_if('can_post_poll', $str);	
			}
		}
		
		/** -------------------------------------
		/**  Are they submitting a post?
		/** -------------------------------------*/
		else
		{	
			if ($this->current_request == 'quotetopic')
			{
				$res = $this->EE->db->query("SELECT announcement FROM exp_forum_topics WHERE topic_id = '{$data['topic_id']}'");
	
				if ($res->row('announcement')  != 'n')
				{
					return $this->_trigger_error('cant_quote_an');
				}
			}
		
			// If we are editing a post we'll set the post as a hidden field

			if ($data['post_id'] != '')
			{			
				$this->form_actions['forum:submit_post']['post_id'] = $data['post_id'];
			}
						
			
		
			$this->form_actions['forum:submit_post']['RET'] = $this->_forum_path('/viewthread/'.$this->current_id.'/');
			$this->form_actions['forum:submit_post']['topic_id'] = $data['topic_id'];
			$this->form_actions['forum:submit_post']['forum_id'] = $data['forum_id'];
			
			// Clear out some variables that are only used on New Topics
			$str = str_replace('{type_all_checked}', '', $str);
			$str = str_replace('{type_one_checked}', '', $str);

			$str = $this->_deny_if('is_topic', $str);
			$str = $this->_deny_if('can_post_poll', $str);
			$str = $this->_allow_if('is_post', $str);
			$str = $this->_deny_if('is_moderator', $str);
			$str = $this->_deny_if('can_announce', $str);
			$str = $this->_deny_if('can_change_status', $str);
			$str = $this->_deny_if('show_more_options', $str);
		}	
			
		/** -------------------------------------
		/**  Can they upload files?
		/** -------------------------------------*/
		
		if ( ! $this->_permission('can_upload_files', $data['permissions']) OR $this->_fetch_pref('board_upload_path') == '')			
		{ 
			$str = $this->_deny_if('attachments_exist', $str);
			$str = $this->_deny_if('can_upload', $str);
		}
		
		$str = $this->_allow_if('can_upload', $str);
			
		/** -------------------------------------
		/**  Create the HTML formatting buttons
		/** -------------------------------------*/
		$buttons = '';
		if ( ! class_exists('Html_buttons'))
		{
			if (include_once(APPPATH.'libraries/Html_buttons'.EXT))
			{
				$BUTT = new EE_Html_buttons();
				$BUTT->allow_img = ($data['forum_allow_img_urls'] == 'y') ? TRUE : FALSE;				
				$buttons = $BUTT->create_buttons();
			}
		}

		/** -------------------------------------
		/**  Does $_POST['attach'] exist
		/** -------------------------------------*/
		// Since we allow multiple attachments, as each one is
		// added we set the attachment ID as a hidden field.
		// This is done by the $this->_attach_file() function.
		// As a first step we'll grab the attachment IDs so
		// we can generate the list of attachments later on.
		
		if (count($this->attachments) == 0 && $this->EE->input->get_post('attach') != '')
		{
			if (strpos($this->EE->input->get_post('attach'), '|') === FALSE)
			{
				$this->attachments[] = $this->EE->input->get_post('attach');
			}
			else
			{				
				foreach (explode("|", $this->EE->input->get_post('attach')) as $val)
				{
					$this->attachments[] = $val;
				}
			}
		}		
		
		/** -------------------------------------
		/**  Fetch Previous Attachments if editing
		/** -------------------------------------*/
		if ($this->current_request == 'edittopic' OR $this->current_request == 'editreply')
		{
			$pid = ($data['post_id'] == '') ? 0 : $data['post_id'];

			$query = $this->EE->db->query("SELECT attachment_id FROM exp_forum_attachments WHERE topic_id = '".$data['topic_id']."' AND post_id = '{$pid}'");
			
			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					if ( ! in_array($row['attachment_id'], $this->attachments))
					{
						$this->attachments[] = $row['attachment_id'];
					}
				}
			}
		}

		/** -------------------------------------
		/**  Build Attachment Rows
		/** -------------------------------------*/
	
		if (count($this->attachments) > 0)
		{
			$at = '';
			
			foreach ($this->attachments as $val)
			{
				$at .= $val.'|';
			}
			
			if (substr($at, strlen($at)-1, 1) == '|')
			{
				$at = substr($at, 0, -1);
			}
			
			$this->form_actions['forum:submit_post']['attach'] = $at;
		
			$str = $this->_allow_if('attachments_exist', $str);
		
			$str = str_replace('{include:form_attachments}', $this->_form_attachments(), $str);
		}
		else
		{
			$str = $this->_deny_if('attachments_exist', $str);
		}
	
		/** -------------------------------------
		/**  Parse the poll stuff
		/** -------------------------------------*/
		
		$poll_answer_field = $this->_load_element('poll_answer_field');
		$vote_count_field = $this->_load_element('poll_vote_count_field');
		$poll_answers = '';
		$poll_rownum  =  4;
		
		// Build the Javascript stuff
		
		$poll_field = "'".$poll_answer_field."'";
		$poll_field = str_replace('{n}', "' + rownum + '", $poll_field);
		$poll_field = str_replace('{answer_number}', "' + rownum + '", $poll_field);
		$poll_field = str_replace('{poll_answer}', '', $poll_field);
		$poll_field	= str_replace('{include:poll_vote_count_field}', '', $poll_field);
		

		// Add the poll rows
		
		if (isset($_POST['option']) AND is_array($_POST['option']))
		{
			$i = 0;
			foreach ($_POST['option'] as $val)
			{
				$val = $this->_convert_special_chars($val);
			
				$temp = $poll_answer_field;
				
				$temp = str_replace('{poll_answer}', $this->_convert_special_chars(stripslashes($val)), $temp);
				$temp = str_replace('{answer_number}', ($i+1), $temp);
				
				if (isset($_POST['votes'][$i]))
				{
					$f = str_replace('{vote_total}', $_POST['votes'][$i], $vote_count_field);
					$temp = str_replace('{include:poll_vote_count_field}', $f, $temp);
				}
				else
				{
					$temp = str_replace('{include:poll_vote_count_field}', '', $temp);
				}
				
				$temp = str_replace('{n}', $i, $temp);
				
				$poll_answers .= $temp;
				$i++;
			}
			
			$poll_rownum = $i;			
		}
		else
		{
			if (is_array($data['poll_answers']))
			{
				for ($i = 0; $i < count($data['poll_answers']); $i++)
				{
					$temp = $poll_answer_field;
					
					$temp = str_replace('{poll_answer}', $this->_convert_special_chars($data['poll_answers'][$i]['answer']), $temp);
					$temp = str_replace('{answer_number}', ($i+1), $temp);
					
					$f = str_replace('{vote_total}', $data['poll_answers'][$i]['votes'], $vote_count_field);
					$temp = str_replace('{include:poll_vote_count_field}', $f, $temp);

					$temp = str_replace('{n}', $i, $temp);
					$poll_answers .= $temp;
				}

				$poll_rownum = $i;			
			}
			else
			{
				for ($i = 0; $i <= 3; $i++)
				{
					$temp = $poll_answer_field;
					
					$temp = str_replace('{poll_answer}', '', $temp);
					$temp = str_replace('{answer_number}', ($i+1), $temp);
					$temp = str_replace('{include:poll_vote_count_field}', '', $temp);
					$temp = str_replace('{n}', $i, $temp);
					$poll_answers .= $temp;
				}
			}
		}


	
		/** -------------------------------------
		/**  Set the "parse smileys" checkbox
		/** -------------------------------------*/
				
		$smileys = '';
		
		if (isset($_POST['smileys']))
		{
			$smileys = ' checked="checked" ';
		}
		else
		{
			if (isset($_POST['preview']) )
			{
				$smileys = '';
			}
			else
			{
				if ($this->current_request == 'edittopic')
				{
					$query = $this->EE->db->query("SELECT parse_smileys FROM exp_forum_topics WHERE topic_id = '".$data['topic_id']."'");
					$smileys = ($query->row('parse_smileys')  == 'y') ? ' checked="checked" ' : '';
				}
				elseif ($this->current_request == 'editreply')
				{
					$query = $this->EE->db->query("SELECT parse_smileys FROM exp_forum_posts WHERE post_id = '".$data['post_id']."'");
					$smileys = ($query->row('parse_smileys')  == 'y') ? ' checked="checked" ' : '';
				}
				else
				{
					$smileys = ' checked="checked" ';
				}
			}
		}
		
		/** -------------------------------------
		/**  Set the "notify" checkbox
		/** -------------------------------------*/
		
		if (($this->current_request == 'edittopic' OR $this->current_request == 'editreply') AND ! isset($_POST['notify']))
		{				
			$aid = ( ! isset($data['author_id'])) ? $this->EE->session->userdata('member_id') : $data['author_id'];
		
			$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_forum_subscriptions WHERE topic_id = '".$data['topic_id']."' AND member_id = '{$aid}'");
			
			$str = ($query->row('count')  == 0) ?
					str_replace('{notify_checked}', '', $str) : 
					str_replace('{notify_checked}',  ' checked="checked" ', $str);
		}
		
		$notify = ''; 
		
		if (isset($_POST['notify']))
		{
			$notify = ' checked="checked" ';		
		}
		else
		{
			if ( ! isset($_POST['topic_id']) AND ! isset($_POST['post_id']) AND ($this->EE->session->userdata('notify_by_default') == 'y'))
			{
				$notify = ' checked="checked" ';
			}
		}
		
		
		/** -------------------------------------
		/**  Parse the template
		/** -------------------------------------*/
		
		// Load the form helper
		$this->EE->load->helper('form');

		$body = ( ! $this->EE->input->get_post('body'))	? $data['body']  : stripslashes(form_prep($this->EE->input->get_post('body')));
		$body = $this->_convert_forum_tags($this->EE->functions->encode_ee_tags($body, TRUE));

		$title = ( ! $this->EE->input->get_post('title'))  ? form_prep($data['title']) : stripslashes(form_prep($this->EE->input->get_post('title')));
		$title = $this->_convert_forum_tags($this->EE->functions->encode_ee_tags($title, TRUE));
		
		$maxchars = $data['forum_max_post_chars'];
		$totchars = $data['forum_max_post_chars'];
		if (isset($_POST['body']))
		{
			$totchars -= strlen($_POST['body']);
		}
		elseif ($body != '')
		{
			$totchars -= strlen($body);
		}
		
		// -------------------------------------------
		// 'forum_submission_form_end' hook.
		//  - Final chance to modify submission form
		//
			if ($this->EE->extensions->active_hook('forum_submission_form_end') === TRUE)
			{
				$str = $this->EE->extensions->universal_call('forum_submission_form_end', $this, $str);
				if ($this->EE->extensions->end_script === TRUE) return $str;
			}
		//
		// -------------------------------------------
		

		return $this->_var_swap($str, 
								array(
										'title'						=> $title,
										'body'						=> $body,
										'lang:submission_heading' 	=> $this->EE->lang->line($data['type']),
										'forum_name'				=> $data['forum_name'],
										'topic_title'				=> $this->_convert_special_chars($data['title'], TRUE),
										'poll_question'				=> ( ! isset($_POST['poll_question'])) ? $this->_convert_special_chars($data['poll_question']) : $this->_convert_special_chars(stripslashes($_POST['poll_question'])),
										'include:poll_answers'		=> $poll_answers,
										'poll_answer_field'			=> $poll_field,
										'poll_rownum'				=> $poll_rownum,
										'lang:post_poll'			=> ($data['poll_question'] != '' OR isset($_POST['poll_question'])) ? $this->EE->lang->line('edit_poll') : $this->EE->lang->line('add_a_poll'),
										'notify_checked'			=> $notify,
										'smileys_checked'			=> $smileys,
										'include:html_formatting_buttons' => $buttons,
										'maxchars'					=> $maxchars,
										'total_characters'			=> $totchars
									)
								);		
	}



	/** -------------------------------------
	/**  Attachemnt Rows
	/** -------------------------------------*/
	// When previewing or adding attachments in a new post
	// this function shows all the current attachments before submitting

	function _form_attachments()
	{
		$template = $this->_load_element('form_attachment_rows');
	
		$str = '';
		$kbs = 0;
		foreach ($this->attachments as $id)
		{	
			$query = $this->EE->db->query("SELECT filename, filesize, extension FROM exp_forum_attachments WHERE attachment_id = '{$id}'");
	
			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$temp = $template;
					$temp = str_replace('{attachment_name}',	$row['filename'], $temp);
					$temp = str_replace('{attachment_size}',	$row['filesize'].' KB', $temp);
					$temp = str_replace('{attachment_id}',		$id, $temp);
					$str .= $temp;
					
					$kbs += $row['filesize'];
				}
			}
		}
		
		$size = ($this->_fetch_pref('board_max_attach_size') - $kbs);
	
		return $this->_var_swap($this->_load_element('form_attachments'), 
								array( 
										'lang:remaining_space' 			=> str_replace('%x', $size.' KB', $this->EE->lang->line('remaining_space')),
										'lang:total_attach_allowed'		=> $this->EE->lang->line('total_attach_allowed').'&nbsp;'.$this->_fetch_pref('board_max_attach_perpost'),
										'include:form_attachment_rows'	=> $str
									)
								);		
	}

	

	/** -------------------------------------
	/**  Fast Reply Form
	/** -------------------------------------*/
	function fast_reply_form()
	{
		if (FALSE === ($meta = $this->_fetch_topic_metadata($this->current_id)))
		{
			return '';
		}
				
		if ($meta[$this->current_id]['forum_is_cat'] == 'y')
		{
			return '';
		}
				
		if ($this->EE->session->userdata('member_id') == 0)
		{ 
			return '';
		}		

		$this->form_actions['forum:submit_post']['RET'] = $this->_forum_path('/viewthread/'.$this->current_id.'/');
		$this->form_actions['forum:submit_post']['topic_id'] = $this->current_id;
		$this->form_actions['forum:submit_post']['forum_id'] = $meta[$this->current_id]['forum_id'];
		$this->form_actions['forum:submit_post']['smileys'] = 'y';

		$notify = ($this->EE->session->userdata('notify_by_default') == 'y' OR $this->EE->input->get_post('notify') == 'y') ? ' checked="checked" ' : '';
		
		$template = $this->_load_element('fast_reply_form');
		
		$template = str_replace('{notify_checked}', $notify, $template);
		
		return $template;
	}



	/** -------------------------------------
	/**  Submission Error Display
	/** -------------------------------------*/
	function submission_errors()
	{
		if (isset($this->EE->session->cache['forum']['submission_error']))
		{
			$this->submission_error = $this->EE->session->cache['forum']['submission_error'];
		}
		
		if ($this->submission_error == '')
		{
			return '';			
		}

		return $this->_var_swap($this->_load_element('submission_errors'), 
								array( 
										'message' => $this->submission_error
									)
								);		
	}

	

	/** -------------------------------------
	/**  Post Preview
	/** -------------------------------------*/
	function preview_post()
	{
		if ($this->EE->input->post('preview') === FALSE OR $this->EE->input->get_post('body') == '' OR $this->preview_override == TRUE)
		{
			return '';
		}
				
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();
		
		$forum_text_formatting  = 'xhtml';
		$forum_html_formatting 	= 'safe';
		$forum_auto_link_urls  	= 'y';
		$forum_allow_img_urls	= 'y';		
		
		switch($this->current_request)
		{
			case 'newtopic'		:
									$query = $this->EE->db->query("SELECT f.forum_id, f.forum_text_formatting, f.forum_html_formatting, f.forum_enable_rss, f.forum_auto_link_urls, f.forum_allow_img_urls FROM exp_forums f WHERE f.forum_id = '{$this->current_id}'");
	
									if ($query->num_rows() > 0)
									{
										$forum_text_formatting	= $query->row('forum_text_formatting') ;
										$forum_html_formatting 	= $query->row('forum_html_formatting') ;
										$forum_auto_link_urls  	= $query->row('forum_auto_link_urls') ;
										$forum_allow_img_urls	= $query->row('forum_allow_img_urls') ;

										if ($query->row('forum_enable_rss')  == 'y')
										{
											$this->feeds_enabled = TRUE;
											$this->feed_ids = $query->row('forum_id') ;
										}
									}
								
				break;
			case 'newreply' 	:
			case 'quotetopic' 	:
			case 'edittopic'	:			
									$query = $this->EE->db->query("SELECT f.forum_id, f.forum_text_formatting, f.forum_html_formatting, f.forum_enable_rss, f.forum_auto_link_urls, f.forum_allow_img_urls FROM exp_forums f, exp_forum_topics t WHERE f.forum_id = t.forum_id AND t.topic_id = '{$this->current_id}'");
		
									if ($query->num_rows() > 0)
									{
										$forum_text_formatting	= $query->row('forum_text_formatting') ;
										$forum_html_formatting 	= $query->row('forum_html_formatting') ;
										$forum_auto_link_urls  	= $query->row('forum_auto_link_urls') ;
										$forum_allow_img_urls	= $query->row('forum_allow_img_urls') ;

										if ($query->row('forum_enable_rss')  == 'y')
										{
											$this->feeds_enabled = TRUE;
											$this->feed_ids = $query->row('forum_id') ;
										}
									}
									
				break;
			case 'quotereply'	:
			case 'editreply'	:
									$query = $this->EE->db->query("SELECT f.forum_id, f.forum_text_formatting, f.forum_html_formatting, f.forum_enable_rss, f.forum_auto_link_urls, f.forum_allow_img_urls FROM exp_forums f, exp_forum_posts p WHERE f.forum_id = p.forum_id AND p.post_id = '{$this->current_id}'");

									if ($query->num_rows() > 0)
									{
										$forum_text_formatting	= $query->row('forum_text_formatting') ;
										$forum_html_formatting 	= $query->row('forum_html_formatting') ;
										$forum_auto_link_urls  	= $query->row('forum_auto_link_urls') ;
										$forum_allow_img_urls	= $query->row('forum_allow_img_urls') ;
	
										if ($query->row('forum_enable_rss')  == 'y')
										{
											$this->feeds_enabled = TRUE;
											$this->feed_ids = $query->row('forum_id') ;
										}
									}
				break;
		}
		
		
		
		$this->EE->typography->highlight_code = TRUE;
		$this->EE->typography->parse_smileys = (isset($_POST['smileys'])) ? TRUE : FALSE;

		$body = str_replace('{include:', '&#123;include:', $this->EE->input->get_post('body'));
		$body = str_replace('{path:', '&#123;path:', $body);
		$body = str_replace('{lang:', '&#123;lang:', $body);
		
		$body = $this->_quote_decode($this->EE->typography->parse_type(stripslashes($this->EE->security->xss_clean($body)), 
									 					array(
																'text_format'	=> $forum_text_formatting,
																'html_format'	=> $forum_html_formatting ,
																'auto_links'	=> $forum_auto_link_urls,
																'allow_img_url' => $forum_allow_img_urls
																	)
										)
															
									);		
	
		$title = str_replace('{include:', '&#123;include:', $this->EE->security->xss_clean($this->EE->input->get_post('title')));

		return $this->_var_swap($this->_load_element('preview_post'), 
								array(
										'post_title'	=> stripslashes($this->_convert_special_chars($title)),
										'post_body' 	=> $body,
									)
								);		
	}




	/** -------------------------------------
	/**  Upload and Attach File
	/** -------------------------------------*/
	function _attach_file($is_preview = FALSE)
	{
		/** -------------------------------------
		/**  Fetch Prefs
		/** -------------------------------------*/
		
		$query = $this->EE->db->query("SELECT board_upload_path,
									board_max_attach_perpost,
									board_max_attach_size, 
									board_max_width,
									board_max_height,
									board_use_img_thumbs,
									board_attach_types,
									board_thumb_width,
									board_thumb_height
								FROM exp_forum_boards
								WHERE board_id = '".$this->_fetch_pref('board_id')."'");
								
		/** -------------------------------------
		/**  Check the paths
		/** -------------------------------------*/
		
		if ($query->row('board_upload_path')  == '')
		{
			return $this->submission_error = $this->EE->lang->line('unable_to_recieve_attach');
		}
	
		if ( ! @is_dir($query->row('board_upload_path') ) OR ! is_really_writable($query->row('board_upload_path') ))
		{
			return $this->submission_error = $this->EE->lang->line('unable_to_recieve_attach');
		}
		
		/** -------------------------------------
		/**  Are there previous attachments?
		/** -------------------------------------*/
		
		// Since you can attach more than one attachment per post
		// we look for the $_POST['attach'] variable to see if
		// they have previously attached items
		
		$attach_ids = array();
		
		if ($this->EE->input->get_post('attach') != '')
		{
			if (strpos($this->EE->input->get_post('attach'), '|') === FALSE)
			{
				$attach_ids[] = $this->EE->input->get_post('attach');
			}
			else
			{				
				foreach (explode("|", $this->EE->input->get_post('attach')) as $val)
				{
					$attach_ids[] = $val;
				}
			}
		}
		
		/** -------------------------------------
		/**  Are they exceeding the allowed total?
		/** -------------------------------------*/
		if ((count($attach_ids) + 1) > $query->row('board_max_attach_perpost') )
		{
			return $this->submission_error = str_replace("%x", $query->row('board_max_attach_perpost') , $this->EE->lang->line('too_many_attachments'));
		}
		
		/** -------------------------------------
		/**  Fetch the size of the previous attachments
		/** -------------------------------------*/
		$total = 0;
		
		if (count($attach_ids) > 0)
		{		
			foreach ($attach_ids as $val)
			{
				$this->EE->db->select('filesize');
				$this->EE->db->where('attachment_id', $val);
				$result = $this->EE->db->get('forum_attachments');
				
				if ($total == 0)
				{
					$total = $result->row('filesize') ;
				}
				else
				{
					$total = $total + $result->row('filesize') ;
				}
			}
		}

		$total = $total + ($_FILES['userfile']['size'] / 1024);
		$total = ceil($total);
			
		// Is the size of the new file (along with the previous ones) too large?
																
		if ($total > $query->row('board_max_attach_size') )
		{
			return $this->submission_error = str_replace("%x", $query->row('board_max_attach_size') , $this->EE->lang->line("file_too_big"));
		}
		
		$filehash = $this->EE->functions->random('alnum', 20);
		
		/** -------------------------------------
		/**  Upload the image
		/** -------------------------------------*/
	
		$server_path = $query->row('board_upload_path');

		// Upload the image
		$config = array(
				'upload_path'	=> $server_path,
				'allowed_types'	=> ($query->row('board_attach_types') == 'all') ? '*' : 'gif|jpg|jpeg|png',
				'max_size'		=> $query->row('board_max_attach_size')
		);
		
		if ($this->EE->config->item('xss_clean_uploads') == 'n')
		{
			$config['xss_clean'] = FALSE;
		}
		else
		{
			$config['xss_clean'] = ($this->EE->session->userdata('group_id') == 1) ? FALSE : TRUE;
		}

		$this->EE->load->library('upload', $config);

		if ($this->EE->upload->do_upload() === FALSE)
		{;
			return $this->submission_error = $this->EE->lang->line($this->EE->upload->display_errors());
		}
		
		$upload_data = $this->EE->upload->data();

		@chmod($upload_data['full_path'], DIR_WRITE_MODE);

		$width		= 0;
		$height		= 0;
		$t_width	= 0;
		$t_height	= 0;

		if ($upload_data['is_image'])
		{
			$width = $upload_data['image_width'];
			$height = $upload_data['image_height'];
			
			if ($width > $query->row('board_max_width')  OR $height > $query->row('board_max_height') )
			{
				@unlink($upload_data['full_path']);
				$error = str_replace('%x', $query->row('board_max_width') , $this->EE->lang->line("dimensions_too_big"));
				$error = str_replace('%y', $query->row('board_max_height') , $error);
				return $this->submission_error = $error;
			}

			if ($query->row('board_use_img_thumbs')  == 'y')
			{
				$res_config = array(
					'image_library'		=> $this->EE->config->item('image_resize_protocol'),
					'library_path'		=> $this->EE->config->item('image_library_path'),
					'maintain_ratio'	=> TRUE,
					'new_image'			=> $query->row('board_upload_path').$filehash.'_t'.$upload_data['file_ext'],
					'master_dim'		=> 'height',
					'thumb_marker'		=> '_t',
					'source_image'		=> $upload_data['full_path'],
					'quality'			=> 75,
					'width'			=> ($query->row('board_thumb_width')  < $width) ? $query->row('board_thumb_width')  : $width,
					'height'		=> ($query->row('board_thumb_height')  < $height) ? $query->row('board_thumb_height')  : $height				);
					
				$this->EE->load->library('image_lib', $res_config); 

				if ($this->EE->image_lib->resize())
				{
					$props = $this->EE->image_lib->get_image_properties($query->row('board_upload_path').$filehash.'_t'.$upload_data['file_ext'], TRUE);
					
					$t_width  = $props['width'];
					$t_height = $props['height'];
				}
			}
		}

		/** -------------------------------------
		/**  Build the column data
		/** -------------------------------------*/

		$data = array(
						'topic_id'			=> 0,
						'post_id'			=> 0,
						'board_id'			=> 0,
						'member_id'			=> $this->EE->session->userdata('member_id'),
						'filename'			=> $upload_data['file_name'],
						'filehash'			=> $filehash,
						'filesize'			=> ceil($upload_data['file_size']),
						'extension'			=> $upload_data['file_ext'],
						'attachment_date'	=> $this->EE->localize->now,
						'is_temp'			=> ($is_preview == TRUE OR $this->submission_error != '') ? 'y' : 'n',
						'width'				=>  $width,
						'height'			=>  $height,
						't_width'			=>  $t_width,
						't_height'			=>  $t_height,
						'is_image'			=> ($upload_data['is_image']) ? 'y' : 'n'
					);	  


		$this->EE->db->insert('forum_attachments', $data);

		$attach_id = $this->EE->db->insert_id();
		
		
		/** -------------------------------------
		/**  Change file name with attach ID
		/** -------------------------------------*/

		// For convenience we use the attachment ID number as the prefix for all files.
		// That way they will be easier to manager.
		if (file_exists($upload_data['full_path']))
		{
			$final_name = $attach_id.'_'.$filehash;
			$final_path = $upload_data['file_path'].$final_name.$upload_data['file_ext'];

			if (rename($upload_data['full_path'], $final_path))
			{
				chmod($final_path, FILE_WRITE_MODE);

				$thumb_name  = $filehash.'_t'.$upload_data['file_ext'];
				$thumb_final = $final_name.'_t'.$upload_data['file_ext'];

				if (file_exists($upload_data['file_path'].$thumb_name))
				{
					if (rename($upload_data['file_path'].$thumb_name, $upload_data['file_path'].$thumb_final))
					{
						chmod($upload_data['file_path'].$thumb_final, FILE_WRITE_MODE);
					}			
				}
				
				$this->EE->db->set('filehash', $final_name);
				$this->EE->db->where('attachment_id', $attach_id);
				$this->EE->db->update('forum_attachments');
			}
		}
		
		/** -------------------------------------
		/**  Are there previous attachments?
		/** -------------------------------------*/

		$this->attachments[] = $attach_id;

		if (count($attach_ids) > 0)
		{
			foreach ($attach_ids as $val)
			{
				$this->attachments[] = $val;
			}
		}

		/** -------------------------------------
		/**  Is this a preview request
		/** -------------------------------------*/

		// If so it means they are manually triggering the upload
		// so we'll disable errors;

		if ($is_preview == TRUE)
		{
			$this->preview_override = TRUE;
			$this->submission_error = '';
		}

		/** -------------------------------------
		/**  Delete expired images
		/** -------------------------------------*/

		$expire = $this->EE->localize->now - 10800; // Three hours ago

		$this->EE->db->select('attachment_id, filehash, extension');
		$this->EE->db->where('attachment_date < ', $expire);
		$this->EE->db->where('is_temp', 'y');
		
		$result = $this->EE->db->get('forum_attachments');

		if ($result->num_rows() > 0)
		{
			foreach ($result->result_array() as $row)
			{
				@unlink($upload_data['file_path'].$row['attachment_id'].'_'.$row['filehash'].$row['extension']);
				@unlink($upload_data['file_path'].$row['attachment_id'].'_'.$row['filehash'].'_t'.$row['extension']);
			}
			
			$this->EE->db->where('attachment_date <', $expire);
			$this->EE->db->where('is_temp', 'y');
			$this->EE->db->delete('forum_attachments');
		}
		
		return TRUE;
	}




	/** -------------------------------------
	/**  Remove post attachment
	/** -------------------------------------*/
	
	function _remove_attachment($id, $forum_id)
	{
		$this->EE->db->select('filehash, extension, member_id');
		$this->EE->db->where(array('attachment_id' => $id));
		$query = $this->EE->db->get('forum_attachments');

		// make sure the attachment exists and the user is allowed to remove it
		if ($query->num_rows() == 0 OR ($this->EE->session->userdata('member_id') != $query->row('member_id') && $this->_mod_permission('can_edit', $forum_id) === FALSE))
		{
			return;
		}
		
		$file  = $this->_fetch_pref('board_upload_path').$query->row('filehash') .$query->row('extension') ;
		$thumb = $this->_fetch_pref('board_upload_path').$query->row('filehash') .'_t'.$query->row('extension') ;
	
		@unlink($file);
		@unlink($thumb);
		
		$_POST['preview'] = 1;			

		$this->preview_override = TRUE;
		$this->submission_error = '';

		$this->EE->db->query("DELETE FROM exp_forum_attachments WHERE attachment_id = '{$id}'");
		
		if ($this->EE->input->get_post('attach') == '' OR strpos($this->EE->input->get_post('attach'), '|') === FALSE)
		{
			unset($_POST['attach']);
			return;
		}
		
		$attach_ids = array();
		
		foreach (explode("|", $this->EE->input->get_post('attach')) as $val)
		{
			if ($val != $id)
			{
				$attach_ids[] = $val;				
			}
		}
		
		$at = '';
		foreach ($attach_ids as $val)
		{
			$at .= $val.'|';
		}
		
		if (substr($at, strlen($at)-1, 1) == '|')
		{
			$at = substr($at, 0, -1);
		}
		
		$_POST['attach'] = $at;	
	}

	/** -------------------------------------
	/**  Forum Submission Handler
	/** -------------------------------------*/
	function submit_post()
	{
		if ($this->current_id == '')
		{
			return;
		}
		
		$type = (in_array($this->current_request, array('newtopic', 'edittopic'))) ? 'topic' : 'thread';

		/** ----------------------------------------
		/**  Is the user logged in?
		/** ----------------------------------------*/
		
		if ($this->EE->session->userdata('member_id') == 0)
		{ 
			return $this->_trigger_error();
		}		

		/** ----------------------------------------
		/**  Is the user banned?
		/** ----------------------------------------*/
		
		if ($this->EE->session->userdata['is_banned'] == TRUE)
		{			
			return $this->_trigger_error();
		}
		
		/** ----------------------------------------
		/**  Blacklist/Whitelist Check
		/** ----------------------------------------*/
		
		if ($this->EE->blacklist->blacklisted == 'y' && $this->EE->blacklist->whitelisted == 'n')
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}
	 
		/** ----------------------------------------
		/**  Is the IP or User Agent unavalable?
		/** ----------------------------------------*/
		if ($this->EE->input->ip_address() == '0.0.0.0' OR $this->EE->session->userdata['user_agent'] == "")
		{			
			return $this->_trigger_error();
		}
		
		if ($type == 'topic' AND $this->EE->input->get_post('title') == '')	
		{
			$this->submission_error = $this->EE->lang->line('empty_title_field');
		}
		
		/** -------------------------------------
		/**  Is the body blank?
		/** -------------------------------------*/
		
		if ($this->EE->input->get_post('body') == '')
		{
			$this->submission_error = $this->EE->lang->line('empty_body_field');
		}	
		
		// -------------------------------------------
		// 'forum_submit_post_start' hook.
		//  - Allows usurping of forum submission routine
		//  - More error checking and permissions too
		//
			$edata = $this->EE->extensions->universal_call('forum_submit_post_start', $this);
			if ($this->EE->extensions->end_script === TRUE) return $edata;
		//
		// -------------------------------------------
		
		
		/** ----------------------------------------
		/**  Fetch meta-data and do security checks
		/** ----------------------------------------*/
		if ($this->current_request == 'newtopic')
		{
			if (FALSE === ($meta = $this->_fetch_forum_metadata($this->current_id)))
			{
				return $this->_trigger_error();
			}
			
			if ($meta[$this->current_id]['forum_is_cat'] == 'y' OR $meta[$this->current_id]['forum_status'] == 'a')
			{
				return $this->_trigger_error('not_authorized');
			}
			
			$orig_author_id			= $this->EE->session->userdata('member_id');
			$fdata['forum_id']		= $meta[$this->current_id]['forum_id'];
			$fdata['forum_parent']	= $meta[$this->current_id]['forum_parent'];
			$fdata['permissions']	= $meta[$this->current_id]['forum_permissions'];	
			$fdata['forum_max_post_chars']	= $meta[$this->current_id]['forum_max_post_chars'];	
			$fdata['forum_notify_emails'] = $meta[$this->current_id]['forum_notify_emails'];
			$fdata['forum_notify_emails_topics'] = $meta[$this->current_id]['forum_notify_emails_topics'];
			$fdata['forum_notify_moderators'] = $meta[$this->current_id]['forum_notify_moderators_topics'];
		}
		elseif ($this->current_request == 'newreply' OR $this->current_request == 'quotetopic')
		{
			if (FALSE === ($meta = $this->_fetch_topic_metadata($this->current_id)))
			{
				return $this->_trigger_error();
			}
						
			if ($meta[$this->current_id]['forum_is_cat'] == 'y' OR ($meta[$this->current_id]['status'] == 'c' AND $this->EE->session->userdata('group_id') != 1) OR $meta[$this->current_id]['forum_status'] == 'a')
			{
				return $this->_trigger_error('not_authorized');
			}
			
			$orig_author_id			= $this->EE->session->userdata('member_id');
			$fdata['forum_id']	 	= $meta[$this->current_id]['forum_id'];
			$fdata['forum_parent']	 = $meta[$this->current_id]['forum_parent'];
			$fdata['permissions'] 	= $meta[$this->current_id]['forum_permissions'];			
			$fdata['forum_notify_emails'] = $meta[$this->current_id]['forum_notify_emails'];
			$fdata['forum_notify_emails_topics'] = $meta[$this->current_id]['forum_notify_emails_topics'];	
			$fdata['forum_notify_moderators'] = $meta[$this->current_id]['forum_notify_moderators_replies'];
			$fdata['forum_max_post_chars']	= $meta[$this->current_id]['forum_max_post_chars'];	
			$post_per_page = $meta[$this->current_id]['forum_posts_perpage'];			
		}
		elseif ($this->current_request == 'edittopic')
		{		
			if (FALSE === ($meta = $this->_fetch_topic_metadata($this->current_id)))
			{
				return $this->_trigger_error();
			}
			
			if ($meta[$this->current_id]['forum_is_cat'] == 'y' OR $meta[$this->current_id]['forum_status'] == 'a')
			{
				return $this->_trigger_error('not_authorized');
			}
			
			// If the user performing the edit is not the orginal author
			// we'll verify that they have the proper permissions
			
			if ($meta[$this->current_id]['author_id'] != $this->EE->session->userdata('member_id')  AND ! $this->_mod_permission('can_edit', $meta[$this->current_id]['forum_id']))
			{
				return $this->_trigger_error('not_authorized');
			}

			$orig_author_id 		= $meta[$this->current_id]['author_id'];
			$fdata['forum_id']		= $meta[$this->current_id]['forum_id'];
			$fdata['permissions']	= $meta[$this->current_id]['forum_permissions'];	
			$fdata['forum_max_post_chars']	= $meta[$this->current_id]['forum_max_post_chars'];	
		}
		elseif ($this->current_request == 'editreply' OR $this->current_request == 'quotereply')
		{
			if (FALSE === ($meta = $this->_fetch_post_metadata($this->current_id)))
			{
				return $this->_trigger_error();
			}	
			
			if ($meta[$this->current_id]['forum_is_cat'] == 'y' OR $meta[$this->current_id]['forum_status'] == 'a')
			{
				return $this->_trigger_error('not_authorized');
			}
			
			// If the user performing the edit is not the orginal author we'll verify that they have the proper permissions

			if ($this->current_request == 'editreply')
			{
				if ($meta[$this->current_id]['author_id'] != $this->EE->session->userdata('member_id'))
				{
				 	if (! $this->_mod_permission('can_edit', $meta[$this->current_id]['forum_id']))
					{
						return $this->_trigger_error('not_authorized');
					}

					//  Fetch the Super Admin IDs
					$super_admins = $this->fetch_superadmins();

					if (in_array($meta[$this->current_id]['author_id'], $super_admins) && $this->EE->session->userdata('group_id') != 1)
					{
						return $this->_trigger_error('not_authorized');
					}
				}
			}

			$orig_author_id			= $meta[$this->current_id]['author_id'];
			$fdata['forum_id']		= $meta[$this->current_id]['forum_id'];
			$fdata['permissions']	= $meta[$this->current_id]['forum_permissions'];	
			$fdata['forum_max_post_chars']	= $meta[$this->current_id]['forum_max_post_chars'];	
			$post_per_page = $meta[$this->current_id]['forum_posts_perpage'];
		}		

		/** -------------------------------------
		/**  Check the author permissions
		/** -------------------------------------*/
		
		$fdata['permissions'] = unserialize(stripslashes($fdata['permissions']));

		if (in_array($this->current_request, array('newtopic', 'edittopic')) && ! $this->_permission('can_post_topics', $fdata['permissions']))				
		{
			if ( ! $this->_mod_permission('can_edit', $fdata['forum_id']))
			{
				return $this->_trigger_error('not_authorized');
			}
		}
		
		if (in_array($this->current_request, array('newreply', 'editreply')) && ! $this->_permission('can_post_reply', $fdata['permissions']))				
		{
			if ( ! $this->_mod_permission('can_edit', $fdata['forum_id']))
			{
				return $this->_trigger_error('not_authorized');
			}
		}

		/** ----------------------------------------
		/**  Throttle check
		/** ----------------------------------------*/
		
		if ($this->EE->session->userdata('group_id') != 1)
		{
			$query = $this->EE->db->query("SELECT forum_post_timelock FROM exp_forums WHERE forum_id = '{$meta[$this->current_id]['forum_id']}'");		
	
			if ($query->num_rows() == 0)
			{
				return $this->_trigger_error();
			}
	
			if ($query->row('forum_post_timelock')  > 0)
			{			
				if (($this->EE->session->userdata('last_forum_post_date') + $query->row('forum_post_timelock') ) > $this->EE->localize->now)
				{
					$this->submission_error = str_replace('%x', $query->row('forum_post_timelock') , $this->EE->lang->line('post_throttle'));
				}
			}
		}

		/** ----------------------------------------
		/**  Do we allow duplicate data?
		/** ----------------------------------------*/
		
		if ($this->current_request != 'edittopic' AND $this->current_request != 'editreply')
		{
			if ($this->EE->config->item('deny_duplicate_data') == 'y' AND $this->EE->session->userdata['group_id'] != 1 AND $this->EE->input->get_post('body') != '')
			{
				$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_forum_topics WHERE body = '".$this->EE->db->escape_str($this->EE->input->get_post('body'))."'");
				
				if ($query->row('count')  > 0)
				{	
					$this->submission_error = $this->EE->lang->line('duplicate_data_warning');
				}
				
				$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_forum_posts WHERE body = '".$this->EE->db->escape_str($this->EE->input->get_post('body'))."'");
				
				if ($query->row('count')  > 0)
				{	
					$this->submission_error = $this->EE->lang->line('duplicate_data_warning');
				}
			}
		}
			
		/** ----------------------------------------
		/**  Is the post too big?
		/** ----------------------------------------*/
		
		$maxchars = ($fdata['forum_max_post_chars'] == 0) ? $this->max_chars :  $fdata['forum_max_post_chars'];
		
		if (strlen($this->EE->input->get_post('body')) > $maxchars)
		{
			$this->submission_error = str_replace("%x", $maxchars, $this->EE->lang->line('post_too_big'));
		}
		
		/** ----------------------------------------
		/**  Does the post include a poll?
		/** ----------------------------------------*/
		// If so, make sure it has at least two answers
		
		if (isset($_POST['poll_question']) AND $_POST['poll_question'] != '')
		{
			if (isset($_POST['option']) AND is_array($_POST['option']))
			{
				$n = 0;
				
				foreach ($_POST['option'] as $opt)
				{
					if ($opt != '')
						$n++;
				}
				
				if ($n < 2)
				$this->submission_error = $this->EE->lang->line('poll_must_have_two_answers');
			}
		}


		/** -------------------------------------
		/**  Is this a remove attachment request?
		/** -------------------------------------*/
		if (isset($_POST['remove']))
		{
			$id = key($_POST['remove']);
			
			if (is_numeric($id))
			{
				$this->_remove_attachment($id, $fdata['forum_id']);
			}
		}
		
		/** -------------------------------------
		/**  Do we have an attachment to deal with?
		/** -------------------------------------*/
	
		if ($this->_permission('can_upload_files', $fdata['permissions']) AND $this->_fetch_pref('board_upload_path') != '' AND isset($_FILES['userfile']['name']) AND $_FILES['userfile']['name'] != '')
		{
			$preview = ($this->EE->input->post('preview') !== FALSE) ? TRUE : FALSE;
			$this->_attach_file($preview);
		}

		/** -------------------------------------
		/**  Is this a preview request?
		/** -------------------------------------*/
		
		// Or... do we have errors to display?

		$this->EE->stats->update_stats();

		if ($this->EE->input->post('preview') !== FALSE OR $this->submission_error != '')
		{			
			$type = array(	
					'newtopic'		=> 'new_topic_page',
					'edittopic'		=> 'edit_topic_page',
					'newreply'		=> 'new_reply_page',
					'editreply'		=> 'edit_reply_page',
					'quotetopic'	=> 'new_reply_page',
					'quotereply'	=> 'new_reply_page'
					);

			if ($this->_use_trigger())
			{
				return $this->_display_forum($type[$this->current_request]);
			}
			
			if (count($this->attachments) > 0)
			{
				$_POST['attach'] = implode('|', $this->attachments);
			}

			// Then we are in a template.  We have to call this template.  Dude.
			// We still have to send the preview information though.  Curious.
			
			$this->EE->functions->clear_caching('all');
			
			unset($_POST['ACT']);
		
			require APPPATH.'libraries/Template'.EXT;

			$this->EE->TMPL = new EE_Template();
			
			$x = explode('/',$this->trigger);
			
			if ( ! isset($x[1]))
			{
				$query = $this->EE->db->query("SELECT tg.group_name 
									 FROM exp_templates t, exp_template_groups tg
									 WHERE t.group_id = tg.group_id
									 AND t.template_name = '".$this->EE->db->escape_str($x['0'])."'
									 AND tg.is_site_default = 'y'");
									 
				if ($query->num_rows() == 1)
				{
					$x['1'] = $x['0'];
					$x['0'] = $query->row('group_name') ;
				}
				else
				{
					$x['1'] = 'index';
				}
			}
			
			// a new copy of the class will be instantiated when it runs the tag in the template
			// so we need to store the submission errors that will be needed for display
			$this->EE->session->cache['forum']['submission_error'] = $this->submission_error;
			
			$this->EE->TMPL->run_template_engine($x['0'], (( ! isset($x['1'])) ? 'index' : $x['1']));
			
			return;
		}

		/** ----------------------------------------
		/**  Secure forms?
		/** ----------------------------------------*/
	  	
	  	// If the hash is not found we'll simply reload the page.
	  
		if ($this->EE->config->item('secure_forms') == 'y')
		{
			$this->EE->db->where('hash', $this->EE->input->post('XID'));
			$this->EE->db->where('ip_address', $this->EE->input->ip_address());
			$this->EE->db->where('date > UNIX_TIMESTAMP()-7200');
			$this->EE->db->select('COUNT(*) as count');
			$query = $this->EE->db->get('security_hashes');

			if ($query->row('count')  == 0)
			{
				$this->EE->functions->redirect($this->EE->functions->fetch_current_uri());
				exit;
			}
						
			$this->EE->db->query("DELETE FROM exp_security_hashes WHERE (hash='".$this->EE->db->escape_str($_POST['XID'])."' AND ip_address = '".$this->EE->input->ip_address()."') OR date < UNIX_TIMESTAMP()-7200");
		}
		

		/** -------------------------------------
		/**  Sumbmit the post
		/** -------------------------------------*/
		
		$announcement = 'n';
		
		if ($this->EE->input->get_post('announcement') == 'y')
		{
			unset($_POST['sticky']);
			unset($_POST['status']);
		
			if ($this->EE->input->get_post('ann_type') == 'a')
			{
				$announcement = 'a';
			}
			else
			{
				$announcement = 't';
			}
		}
		
		/** -------------------------------------
		/**  Sumbmit the post
		/** -------------------------------------*/
		// There are four possible scenarios:
		// 1. A new topic
		// 2. A new post
		// 3. An updated topic
		// 4. An updated post
		
		if ($this->current_request == 'quotetopic' OR $this->current_request == 'quotereply')
		{
			$this->current_request = 'newreply';
		}		
		
		switch ($this->current_request)
		{
			case 'newtopic'		:
			case 'edittopic'	:
			
					// Security fix
					$title = $this->_convert_forum_tags($this->EE->input->get_post('title'));
					$body = $this->_convert_forum_tags($this->EE->input->get_post('body'));
								
					$data = array(
									'title'			=> $this->EE->security->xss_clean($title),
									'body'			=> $this->EE->security->xss_clean($body),
									'sticky'		=> ($this->EE->input->get_post('sticky') == 'y') ? 'y' : 'n',
									'status'		=> ($this->EE->input->get_post('status') == 'c') ? 'c' : 'o',
									'announcement'	=> $announcement,
									'poll'			=> (isset($_POST['poll_question']) AND $_POST['poll_question'] != '' AND $announcement != 'y') ? 'y' : 'n',
									'parse_smileys'	=> (isset($_POST['smileys'])) ? 'y' : 'n'

								 );
					
					// We need to determine if the user creating or updating a post
					// is allowed to change the various moderation prefs like sticky, 
					// status, etc. If not, we do not set them and use either the 
					// default or original post settings
						
					if ( ! $this->_mod_permission('is_moderator', $fdata['forum_id']))
					{
						unset($data['sticky']);
					}
					
					if ( ! $this->_mod_permission('can_change_status', $fdata['forum_id']))
					{
						unset($data['status']);
					}
					
					if ( ! $this->_mod_permission('can_announce', $fdata['forum_id']))
					{
						unset($data['announcement']);
					}
				
					/** -------------------------------------
					/**  Insert a NEW topic
					/** -------------------------------------*/
				
					if ($this->current_request == 'newtopic')
					{
						$data['author_id']				= $this->EE->session->userdata('member_id');
						$data['ip_address']				= $this->EE->input->ip_address();					
						$data['forum_id'] 				= $this->current_id;
						$data['last_post_date'] 		= $this->EE->localize->now;
						$data['last_post_author_id']	= $this->EE->session->userdata('member_id');
						$data['thread_total']			= 1;
						$data['topic_date']				= $this->EE->localize->now;
						$data['board_id']				= $this->_fetch_pref('board_id');

						$this->EE->db->query($this->EE->db->insert_string('exp_forum_topics', $data));	
						$data['topic_id'] = $this->EE->db->insert_id();
						
						// Where should we send the user to?  Normally we'll send them to either
						// the thread or the announcement page, but if they are allowed to post,
						// but not view threads we have to send them to the topic page.
						
						if ( ! $this->_permission('can_view_topics', $fdata['permissions']))				
						{
							$redirect = $this->_forum_path('/viewforum/'.$fdata['forum_id'].'/');						
						}
						else
						{
							if ($announcement == 'n')
								$redirect = $this->_forum_path('/viewthread/'.$data['topic_id'].'/');	
							else
								$redirect = $this->_forum_path('/viewannounce/'.$data['topic_id'].'_'.$fdata['forum_id'].'/');
						}

						// Update the forum stats
						
						$this->_update_post_stats($this->current_id);
						$this->_update_global_stats();
						
						// Update member post total
						$this->EE->db->where('member_id', $this->EE->session->userdata('member_id'));
						$this->EE->db->update('members', 
													array('last_forum_post_date' => $this->EE->localize->now)
													);

						// Submit a poll if we have one
						if (isset($_POST['poll_question']) AND $_POST['poll_question'] != '' AND $announcement == 'n')
						{
							$this->_submit_poll($data['topic_id']);
						}		
					}
					
					/** -------------------------------------
					/**  Update an existing topic
					/** -------------------------------------*/
					
					else
					{
						// Onward....
					
						$data['topic_edit_author']	= $this->EE->session->userdata['member_id'];
						$data['topic_edit_date']	= $this->EE->localize->now;
					
						$this->EE->db->query($this->EE->db->update_string('exp_forum_topics', $data, array('topic_id' => $this->EE->input->get_post('topic_id'))));
						$data['topic_id'] = $this->current_id;
			
						if ($announcement == 'n')
							$redirect = $this->_forum_path('/viewthread/'.$this->current_id.'/');	
						else
							$redirect = $this->_forum_path('/viewannounce/'.$this->current_id.'_'.$fdata['forum_id'].'/');	
							
						// Update a poll if we have one
						if (isset($_POST['poll_question']) AND $_POST['poll_question'] != '' AND $announcement == 'n')
						{
							$this->_submit_poll($data['topic_id']);
						}
						else // Or delete an existing one if needed
						{
							if ( ! isset($_POST['poll_exists']))
							{
								$this->EE->db->where('topic_id', $data['topic_id']);
								$this->EE->db->delete('forum_polls');

								$this->EE->db->where('topic_id', $data['topic_id']);
								$this->EE->db->delete('forum_pollvotes');
							}
						}
						
						// Update the recent thread title on the home page if necessary
						$this->EE->db->update('forums', array('forum_last_post_title' => $data['title']), array('forum_last_post_id' => $data['topic_id'], 'forum_id' => $fdata['forum_id']));
					}
										
				break;
			case 'newreply'		:
			case 'editreply'	:
			
			
					// Security fix
					$body = $this->_convert_forum_tags($this->EE->input->get_post('body'));

					$data = array(
									'topic_id'		=> $this->EE->db->escape_str($this->EE->input->get_post('topic_id')),
									'forum_id'		=> $this->EE->input->get_post('forum_id'),
									'body'			=> $this->EE->security->xss_clean($body),
									'parse_smileys'	=> (isset($_POST['smileys'])) ? 'y' : 'n'
								 );

					/** -------------------------------------
					/**  Insert a new post
					/** -------------------------------------*/
				
					if ($this->current_request == 'newreply')
					{		
						$data['author_id']	= $this->EE->session->userdata('member_id');
						$data['ip_address']	= $this->EE->input->ip_address();
						$data['post_date']	= $this->EE->localize->now;
						$data['board_id']	= $this->_fetch_pref('board_id');
						
						$this->EE->db->query($this->EE->db->insert_string('exp_forum_posts', $data));	
												
						$data['post_id'] = $this->EE->db->insert_id();

						// Update the topic stats (count, last post info)
						$this->_update_topic_stats($data['topic_id']);
									
						// Update the forum stats
						$this->_update_post_stats($this->EE->input->get_post('forum_id'));
						$this->_update_global_stats();
						
						// Update member post total
						$this->EE->db->where('member_id', $this->EE->session->userdata('member_id'));
						$this->EE->db->update('members', 
												array('last_forum_post_date' => $this->EE->localize->now)
											);
												
						// Determine the redirect location
						$page = $this->_fetch_page_number($this->thread_post_total, $post_per_page);
						$redirect = $this->_forum_path('/viewthread/'.$data['topic_id'].'/'.$page);
					}
					
					/** -------------------------------------
					/**  Update an existing post
					/** -------------------------------------*/
					else
					{
						$data['post_id'] = $this->EE->input->get_post('post_id');
						$data['post_edit_author']	= $this->EE->session->userdata['member_id'];
						$data['post_edit_date']		= $this->EE->localize->now;
						
						$this->EE->db->query($this->EE->db->update_string('exp_forum_posts', $data, "post_id='".$data['post_id']."'"));

						// Determine the redirect location
						$this->EE->db->select('COUNT(*) as count');
						$query = $this->EE->db->get_where('forum_posts', array('topic_id' => $data['topic_id']));

						$total = ($query->row('count')  + 1);						

						$page = $this->_fetch_page_number($query->row('count') , $post_per_page);
						$redirect = $this->_forum_path('/viewthread/'.$data['topic_id'].'/'.$page);
					}
			
				break;
		}
		
		/** -------------------------------------
		/**  Fetch/Set the "topic tracker" cookie
		/** -------------------------------------*/
		if ($this->current_request == 'newtopic' OR $this->current_request == 'newreply')
		{
			$read_topics = $this->_fetch_read_topics($data['topic_id']);
			
			if ($this->EE->session->userdata('member_id') == 0)
			{
				$expire = 60*60*24*365;
				$this->EE->functions->set_cookie('forum_topics', serialize($read_topics), $expire);
			}
		}
		
		/** -------------------------------------
		/**  Is there an attachment to finalize
		/** -------------------------------------*/
		if ($this->EE->input->get_post('attach') != '')
		{
			if (strpos($this->EE->input->get_post('attach'), '|') === FALSE)
			{
				$this->attachments[] = $this->EE->input->get_post('attach');
			}
			else
			{		
				foreach (explode("|", $this->EE->input->get_post('attach')) as $val)
				{
					$this->attachments[] = $val;
				}
			}
		}

		if (count($this->attachments) > 0)
		{
			$topic_id = (isset($data['topic_id']) AND $data['topic_id'] > 0) ? $data['topic_id'] : 0;
			$post_id  = (isset($data['post_id'])  AND $data['post_id']  > 0) ? $data['post_id']  : 0;
			$board_id = $this->preferences['board_id'];
			
			foreach ($this->attachments as $id)
			{
				$d = array(
						'topic_id'		=> $topic_id,
						'post_id'		=> $post_id,
						'board_id'		=> $board_id,
						'is_temp'		=> 'n'
					);
				
				$this->EE->db->update('forum_attachments', $d, array('attachment_id' => $id));
			}
		}		
				

		/** -------------------------------------
		/**  Manage subscriptions
		/** -------------------------------------*/
			
		if ($this->EE->input->get_post('notify') == 'y')
		{
			$this->EE->db->select('COUNT(*) as count');
			$this->EE->db->where('topic_id', $data['topic_id']);
			$this->EE->db->where('member_id', $orig_author_id);
			$query = $this->EE->db->get('forum_subscriptions');
	
			$row = $query->row_array();
	
			if ($row['count']  > 1)
			{
				$this->EE->db->where('topic_id', $data['topic_id']);
				$this->EE->db->where('member_id', $orig_author_id);
				$this->EE->db->delete('forum_subscriptions');

				$row['count']  = 0;
			}	
			
			if ($row['count'] == 0)
			{	
				$rand = $orig_author_id.$this->EE->functions->random('alnum', 8);
				
				$d = array(
						'topic_id'				=> $data['topic_id'],
						'board_id'				=> $this->preferences['board_id'],
						'member_id'				=> $orig_author_id,
						'subscription_date'		=> $this->EE->localize->now,
						'hash'					=> $rand
					);
				
				$this->EE->db->insert('forum_subscriptions', $d);
			}
		}
		else
		{
			$this->EE->db->where('topic_id', $data['topic_id']);
			$this->EE->db->where('member_id', $orig_author_id);
			$this->EE->db->delete('forum_subscriptions');
		}

		
		/** -------------------------------------
		/**  Send them to their post if they have edited
		/** -------------------------------------*/
		
		// Since we don't need to sent notifications when editing, we're done...
		
		if ($this->current_request == 'edittopic' OR  $this->current_request == 'editreply')
		{
			$this->EE->functions->redirect($redirect);
			exit;
		}
		
		/** -------------------------------------
		/**  Email Notifications
		/** -------------------------------------*/
		
		$notify_addresses = '';
		
		/** -------------------------------------
		/**  Fetch		/** -------------------------------------*/
		
		if ($this->current_request == 'newtopic')
		{
			$notify_addresses .= ($this->_fetch_pref('board_notify_emails_topics') != '') ? ','.$this->_fetch_pref('board_notify_emails_topics') : '';
		}
		else
		{
			$notify_addresses .= ($this->_fetch_pref('board_notify_emails') != '') ? ','.$this->_fetch_pref('board_notify_emails') : '';
		}
		
		/** -------------------------------------
		/**  Fetch forum notification addresses
		/** -------------------------------------*/
		
		if ($this->current_request == 'newtopic')
		{
			$notify_addresses .= ($fdata['forum_notify_emails_topics'] != '') ? ','.$fdata['forum_notify_emails_topics'] : '';
		}
		else
		{
			$notify_addresses .= ($fdata['forum_notify_emails'] != '') ? ','.$fdata['forum_notify_emails'] : '';
		}

		/** -------------------------------------
		/**  Category Notification Prefs
		/** -------------------------------------*/
		
		$cmeta = $this->_fetch_forum_metadata($fdata['forum_parent']);
		
		if (FALSE !== $cmeta)
		{
			if ($cmeta[$fdata['forum_parent']]['forum_notify_emails'] != '')
			{
				$notify_addresses .= ','.$cmeta[$fdata['forum_parent']]['forum_notify_emails'];
			}
		}
		
		/** -------------------------------------
		/**  Fetch moderator addresses
		/** -------------------------------------*/
		
		if ((isset($fdata['forum_notify_moderators']) && $fdata['forum_notify_moderators'] == 'y') OR 
			($this->current_request == 'newtopic' && $cmeta[$fdata['forum_parent']]['forum_notify_moderators_topics'] == 'y') OR
			($this->current_request != 'newtopic' && $cmeta[$fdata['forum_parent']]['forum_notify_moderators_replies'] == 'y')
			)
		{
			$this->EE->db->select('email');	
			$this->EE->db->from('members, forum_moderators');
			$this->EE->db->where('(exp_members.member_id = exp_forum_moderators.mod_member_id OR exp_members.group_id =  exp_forum_moderators.mod_group_id)', NULL, FALSE); 
			$this->EE->db->where('exp_forum_moderators.mod_forum_id', $fdata['forum_id']);
			
			$query = $this->EE->db->get();	
		
			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$notify_addresses .= ','.$row['email'];
				}
			}
		}
		
		$notify_addresses = str_replace(' ', '', $notify_addresses);
		
		/** ----------------------------------------
		/**  Remove Current User Email
		/** ----------------------------------------*/
		// We don't want to send an admin notification if the person
		// leaving the comment is an admin in the notification list
		
		if ($notify_addresses != '')
		{
			if (strpos($notify_addresses, $this->EE->session->userdata('email')) !== FALSE)
			{
				$notify_addresses = str_replace($this->EE->session->userdata('email'), "", $notify_addresses);				
			}
			
			$this->EE->load->helper('string');
			// Remove multiple commas
			$notify_addresses = reduce_multiples($notify_addresses, ',', TRUE);
		}
		
		/** ----------------------------
		/**  Strip duplicate emails
		/** ----------------------------*/
		
		// And while we're at it, create an array
				
		if ($notify_addresses != '')
		{		 
			$notify_addresses = array_unique(explode(",", $notify_addresses));
		}		
		
		/** ----------------------------------------
		/**  Instantiate Typography class
		/** ----------------------------------------*/
	  
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();
		$this->EE->typography->parse_images = FALSE;
		$this->EE->typography->highlight_code = FALSE;
		
		$query = $this->EE->db->query("SELECT title FROM exp_forum_topics WHERE topic_id = '".$data['topic_id']."'");

		$title = $query->row('title') ;	  
		$body = $this->EE->typography->parse_type($data['body'], 
										array(
												'text_format'	=> 'none',
												'html_format'	=> 'none',
												'auto_links'	=> 'n',
												'allow_img_url' => 'n'
											)
									);

		/** ----------------------------
		/**  Send admin notification
		/** ----------------------------*/
				
		if (is_array($notify_addresses) AND count($notify_addresses) > 0)
		{		 
			$swap = array(
							'name_of_poster'	=> $this->_convert_special_chars($this->EE->session->userdata('screen_name')),
							'forum_name'		=> $this->_fetch_pref('board_label'),
							'title'				=> $title,
							'body'				=> $body,
							'topic_id'			=> $data['topic_id'],
							'thread_url'		=> $this->remove_session_id($redirect),
							'post_url'			=> (isset($data['post_id'])) ? $this->_forum_path()."viewreply/{$data['post_id']}/" : $this->remove_session_id($redirect)
						 );
			
			$template = $this->EE->functions->fetch_email_template('admin_notify_forum_post');
			$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
			$email_msg = $this->EE->functions->var_swap($template['data'], $swap);
								
			/** ----------------------------
			/**  Send email
			/** ----------------------------*/
			
			$this->EE->load->library('email');
			$this->EE->email->wordwrap = TRUE;

			// Load the text helper
			$this->EE->load->helper('text');
					
			foreach ($notify_addresses as $val)
			{			
				$this->EE->email->EE_initialize();
				$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));	
				$this->EE->email->to($val); 
				$this->EE->email->reply_to($val);
				$this->EE->email->subject($email_tit);	
				$this->EE->email->message(entities_to_ascii($email_msg));		
				$this->EE->email->send();
			}
		}
		
		/* -------------------------------------
		/*  'forum_submit_post_end' hook.
		/*  - After the post is submitted, do some more processing
		/* 	- Note that user notifications have not been sent at this point
		/*  - Added Discussion Forums 1.3.2
		/*	- $data added Discussion Forums 2.1.1
		*/  
			if ($this->EE->extensions->active_hook('forum_submit_post_end') === TRUE)
			{
				$edata = $this->EE->extensions->universal_call('forum_submit_post_end', $this, $data);
				if ($this->EE->extensions->end_script === TRUE) return $edata;
			}
		/*
		/* -------------------------------------*/
		
		/** -------------------------------------
		/**  Send them to their post
		/** -------------------------------------*/
		
		// Unless we are dealing with a follow up post we're done....
		
		if ($this->current_request == 'newtopic')
		{
			$this->EE->functions->redirect($redirect);
			exit;
		}
		

		/** ----------------------------
		/**  Send User Notifications
		/** ----------------------------*/
		// Fetch the notification addressess
		
		$query = $this->EE->db->query("SELECT s.hash, s.notification_sent, m.member_id, m.email, m.screen_name, m.smart_notifications, m.ignore_list  FROM exp_members m, exp_forum_subscriptions s WHERE s.member_id = m.member_id AND s.topic_id = '{$data['topic_id']}'");
		
		// No addresses?  Bail...

		if ($query->num_rows() == 0)
		{
			$this->EE->functions->redirect($redirect);
			exit;	
		}
		
		$action_id  = $this->EE->functions->fetch_action_id('Forum', 'delete_subscription');

		$swap = array(
						'forum_name'		=> $this->_fetch_pref('board_label'),
						'title'				=> $title,
						'body'				=> $body,
						'topic_id'			=> $data['topic_id'],
						'thread_url'		=> $this->remove_session_id($redirect),
						'post_url'			=> (isset($data['post_id'])) ? $this->_forum_path()."viewreply/{$data['post_id']}/" : $this->remove_session_id($redirect)
					 );
		
		$template = $this->EE->functions->fetch_email_template('forum_post_notification');
		$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
		$email_msg = $this->EE->functions->var_swap($template['data'], $swap);

		/** ----------------------------
		/**  Send emails
		/** ----------------------------*/
		
		$this->EE->load->library('email');

		$this->EE->email->wordwrap = TRUE;
					
		$sent = array();
		
		foreach ($query->result_array() as $row)
		{
			// We don't notify the person currently commenting.  That would be silly.

			if ($row['email'] == $this->EE->session->userdata('email') OR (is_array($notify_addresses) && in_array($row['email'], $notify_addresses)))
			{
				continue;
			}
			
			if ($row['smart_notifications'] == 'y' AND $row['notification_sent'] == 'y')
			{
				continue;
			}
			
			// Ignored?  Don't even think about it, buster.

			if ($row['ignore_list'] != '' AND in_array($this->EE->session->userdata['member_id'], explode('|', $row['ignore_list'])))
			{
				continue;
			}
							
			$title	 = $email_tit;
			$message = $email_msg;
			$title	 = str_replace('{name_of_recipient}', $row['screen_name'], $title);
			$message = str_replace('{name_of_recipient}', $row['screen_name'], $message);
			$title	 = str_replace('{notification_removal_url}', $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&id='.$row['hash'].'&board_id='.$this->preferences['board_id'], $title);
			$message = str_replace('{notification_removal_url}', $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&id='.$row['hash'].'&board_id='.$this->preferences['board_id'], $message);

			// Load the text helper
			$this->EE->load->helper('text');
						
			$this->EE->email->EE_initialize();
			$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));	
			$this->EE->email->to($row['email']); 
			$this->EE->email->reply_to($this->EE->config->item('webmaster_email'));
			$this->EE->email->subject($title);	
			$this->EE->email->message(entities_to_ascii($message));		
			$this->EE->email->send();		
			
			// Flip notification flag
			$this->EE->db->query("UPDATE exp_forum_subscriptions SET notification_sent = 'y' WHERE topic_id = '{$data['topic_id']}' AND member_id = '{$row['member_id']}'");
		}			

		$this->EE->functions->redirect($redirect);
		exit;	
	}

	/** -------------------------------------
	/**  Submit/update a poll
	/** -------------------------------------*/
	function _submit_poll($topic_id)
	{
		if ( ! isset($_POST['poll_question']) OR $_POST['poll_question'] == '')
		{
			return;
		}
		
		if ( ! isset($_POST['option']) OR ! is_array($_POST['option']))
		{
			return;
		}

		$answers = array();
		$total_votes = 0;
		
		foreach ($_POST['option'] as $key => $val)
		{
			$val = trim($val);
			
			if ($val == '')
				continue;
		
			$temp['answer']	= $val;
			$temp['votes']	= (isset($_POST['votes'][$key]) AND is_numeric($_POST['votes'][$key])) ? $_POST['votes'][$key] : 0; 

			$answers[]	= $temp;
			
			$total_votes = $temp['votes'] + $total_votes;
		}
		
		
		$data['poll_question']	= $_POST['poll_question'];
		$data['poll_answers']	= serialize($answers);	
		

		$query = $this->EE->db->query("SELECT count(*) AS count FROM exp_forum_polls WHERE topic_id = '{$topic_id}'");
	
		if ($query->row('count')  == 0)
		{
			$data['author_id']		= $this->EE->session->userdata('member_id');
			$data['poll_date']		= $this->EE->localize->now;
			$data['topic_id']		= $topic_id;
			$data['total_votes']	= 0;
			
			$this->EE->db->query($this->EE->db->insert_string('exp_forum_polls', $data));
		}
		else
		{
			$data['total_votes'] = $total_votes;
			$this->EE->db->query($this->EE->db->update_string('exp_forum_polls', $data, "topic_id = '{$topic_id}'"));
		}
	
		return TRUE;
	}



	/** -------------------------------------
	/**  Forum Delete Confirmation Page
	/** -------------------------------------*/
	function delete_post_page()
	{
		$post_id = '';
		$title	 = '';
		
		/** -------------------------------------
		/**  Fetch some meta data based on the request
		/** -------------------------------------*/
			
		if ($this->current_request == 'deletereply')
		{
			$query = $this->EE->db->query("SELECT p.topic_id, p.post_id, p.forum_id, p.body, p.author_id,
								f.forum_text_formatting, f.forum_html_formatting, f.forum_auto_link_urls, f.forum_allow_img_urls 
								FROM exp_forum_posts AS p, exp_forums AS f
								WHERE f.forum_id = p.forum_id
								AND p.post_id = '{$this->current_id}'");
		}
		else
		{
			$query = $this->EE->db->query("SELECT t.topic_id, t.forum_id, t.title, t.body, t.author_id,
								f.forum_text_formatting, f.forum_html_formatting, f.forum_auto_link_urls, f.forum_allow_img_urls 
								FROM exp_forum_topics AS t, exp_forums AS f
								WHERE f.forum_id = t.forum_id
								AND t.topic_id = '{$this->current_id}'");
		}

		/** -------------------------------------
		/**  No result?  Smack em'
		/** -------------------------------------*/
		
		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}
		
		/** -------------------------------------
		/**  Create Vars for simplicity
		/** -------------------------------------*/
		
		foreach ($query->row_array() as $key => $val)
		{
			$$key = $val;
		}
			
		/** -------------------------------------
		/**  Is the user allowed to delete?
		/** -------------------------------------*/
			
		if ( ! $this->_mod_permission('can_delete', $forum_id))
		{
			return $this->_trigger_error('not_authorized');
		}

		// Only Superadmins can delete other Superadmin posts
		
		if ($author_id != $this->EE->session->userdata('member_id'))
		{
			//  Fetch the Super Admin IDs
			$super_admins = $this->fetch_superadmins();

			if (in_array($author_id, $super_admins) && $this->EE->session->userdata('group_id') != 1)
			{
				return $this->_trigger_error('not_authorized');
			}
		}
		
		/** -------------------------------------
		/**  Define the redirect location
		/** -------------------------------------*/
		
		if ($this->current_request == 'deletereply')
		{
			$this->form_actions['forum:delete_post']['RET'] = $this->_forum_path('/viewthread/'.$topic_id.'/');
			$this->form_actions['forum:delete_post']['post_id'] = $post_id;
		}
		else
		{
			$this->form_actions['forum:delete_post']['RET'] = $this->_forum_path('/viewforum/'.$forum_id.'/');
			$this->form_actions['forum:delete_post']['topic_id'] = $topic_id;
		}
	
		/** -------------------------------------
		/**  Build the warning
		/** -------------------------------------*/
	
		$str = $this->_load_element('delete_post_warning');
			
		if ($this->current_request == 'deletereply')
		{
			$str = $this->_deny_if('is_topic', $str);
			$str = $this->_allow_if('is_reply', $str);
		}
		else
		{
			$str = $this->_allow_if('is_topic', $str);
			$str = $this->_deny_if('is_reply', $str);
		}
				
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();

		$str = $this->_var_swap($str,
								array(
										'title'	=> $this->_convert_special_chars($title),
										'body'	=> $this->EE->typography->parse_type($body, 
													 array(
																'text_format'	=> $forum_text_formatting,
																'html_format'	=> $forum_html_formatting,
																'auto_links'	=> $forum_auto_link_urls,
																'allow_img_url' => $forum_allow_img_urls
															)
													)
									)
								);		
		
		return str_replace('{include:delete_post_warning}', $str, $this->_load_element('delete_post_page'));
	}




	/** -------------------------------------
	/**  Delete Post
	/** -------------------------------------*/
	function delete_post()
	{
		$id = '';

		/** -------------------------------------
		/**  No ID?  Bah....
		/** -------------------------------------*/
		
		if ( ! isset($_POST['post_id']) AND  ! isset($_POST['topic_id']))
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}

		if (isset($_POST['post_id']) AND is_numeric($_POST['post_id']))
		{
			$type = 'post';
			$post_id = $_POST['post_id'];
		}
		elseif (isset($_POST['topic_id']) AND is_numeric($_POST['topic_id']))
		{
			$type = 'topic';
			$post_id = $_POST['topic_id'];
		}
		
		if ( ! isset($type))
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}
		
		/** -------------------------------------
		/**  Fetch the forum_id
		/** -------------------------------------*/
	
		if ($type == 'post')
		{
			$query = $this->EE->db->query("SELECT topic_id, forum_id, post_id, author_id FROM exp_forum_posts WHERE post_id = '{$post_id}'");
		}
		else
		{
			$query = $this->EE->db->query("SELECT topic_id, forum_id, author_id FROM exp_forum_topics WHERE topic_id = '{$post_id}'");
		}
		
		/** -------------------------------------
		/**  No result?  Smack em'
		/** -------------------------------------*/
		
		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}
		
		/** -------------------------------------
		/**  Create Vars for simplicity
		/** -------------------------------------*/
		
		foreach ($query->row_array() as $key => $val)
		{
			$$key = $val;
		}
			
		/** -------------------------------------
		/**  Is the user allowed to delete?
		/** -------------------------------------*/
			
		if ( ! $this->_mod_permission('can_delete', $forum_id))
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}
		
		/** -------------------------------------
		/**  Delete the post(s)
		/** -------------------------------------*/
		
		$authors[$author_id] = $author_id;
		
		if ($type == 'post')
		{
			$this->EE->db->query("DELETE FROM exp_forum_posts WHERE post_id = '{$post_id}'");
			
			// Update the topic stats (count, last post info)
			$this->_update_topic_stats($topic_id);
		}
		else
		{
			// get all affected authors
			$query = $this->EE->db->query("SELECT author_id FROM exp_forum_posts WHERE topic_id = '{$topic_id}'");
			
			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$authors[$row['author_id']] = $row['author_id'];
				}
			}
			
			$this->EE->db->query("DELETE FROM exp_forum_topics WHERE topic_id = '{$topic_id}'");
			$this->EE->db->query("DELETE FROM exp_forum_posts  WHERE topic_id = '{$topic_id}'");
			$this->EE->db->query("DELETE FROM exp_forum_subscriptions  WHERE topic_id = '{$topic_id}'");
			$this->EE->db->query("DELETE FROM exp_forum_polls  WHERE topic_id = '{$topic_id}'");
			$this->EE->db->query("DELETE FROM exp_forum_pollvotes  WHERE topic_id = '{$topic_id}'");
		}
		
		
		// Delete attachments if there are any
		
		if ($type == 'post')
		{
			$query = $this->EE->db->query("SELECT attachment_id, filehash, extension FROM exp_forum_attachments WHERE topic_id = '{$topic_id}' AND post_id = '{$post_id}'");
		}
		else
		{
			$query = $this->EE->db->query("SELECT attachment_id, filehash, extension FROM exp_forum_attachments WHERE topic_id = '{$topic_id}'");
		}
		
		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$file  = $this->_fetch_pref('board_upload_path').$row['filehash'].$row['extension'];
				$thumb = $this->_fetch_pref('board_upload_path').$row['filehash'].'_t'.$row['extension'];
			
				@unlink($file);
				@unlink($thumb);					
		
				$this->EE->db->query("DELETE FROM exp_forum_attachments WHERE attachment_id = '{$row['attachment_id']}'");
			}				
		}		
				
		// Update the forum stats	
		$this->_update_post_stats($forum_id);
		$this->_update_global_stats();
		
		// update member stats
		$this->_update_member_stats($authors);
		
		$this->EE->functions->redirect($this->EE->input->get_post('RET'));
		exit;
	}



	/** -------------------------------------
	/**  Change Post Status
	/** -------------------------------------*/
	function change_status()
	{
		
		// $this->_forum_path('viewthread/'.$_GET['topic_id']); exit;
		
		/** -------------------------------------
		/**  No ID?  Bah....
		/** -------------------------------------*/
		
		if ( ! isset($_GET['topic_id']) OR ! is_numeric($_GET['topic_id']))
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}
		
		/** -------------------------------------
		/**  Fetch the forum_id and status
		/** -------------------------------------*/
		
		$query = $this->EE->db->query("SELECT status, forum_id, announcement FROM exp_forum_topics WHERE topic_id = '".$this->EE->db->escape_str($_GET['topic_id'])."'");
		
		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}

		/** -------------------------------------
		/**  Are they allowed to swap the status?
		/** -------------------------------------*/
		
		$viewpath = ($query->row('announcement')  == 'n') ? 'viewthread/' : 'viewannounce/';
		$viewpath .= ($query->row('announcement')  == 'n') ?  $_GET['topic_id'] : $_GET['topic_id'].'_'.$query->row('forum_id') ;

		if ( ! $this->_mod_permission('can_change_status', $query->row('forum_id') ))
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}

		/** -------------------------------------
		/**  Update the status
		/** -------------------------------------*/
		$status = ($query->row('status')  == 'o') ? 'c' : 'o';

		$this->EE->db->query("UPDATE exp_forum_topics SET status = '{$status}' WHERE topic_id = '".$this->EE->db->escape_str($_GET['topic_id'])."'");
		
		/** -------------------------------------
		/**  Update edit date
		/** -------------------------------------*/
		
		$data = array();
		$data['topic_edit_author']	= $this->EE->session->userdata['member_id'];
		$data['topic_edit_date']	= $this->EE->localize->now;
	
		$this->EE->db->query($this->EE->db->update_string('exp_forum_topics', $data, "topic_id='".$_GET['topic_id']."'"));
		
		$this->EE->functions->redirect($this->_forum_path($viewpath));
		exit;
	}

	
	
	
	/** -------------------------------------
	/**  Move Topic Confirmation
	/** -------------------------------------*/
	function move_topic_confirmation()
	{
		/** -------------------------------------
		/**  Fetch the topic title
		/** -------------------------------------*/
		$query = $this->EE->db->query("SELECT title, forum_id FROM exp_forum_topics WHERE topic_id = '{$this->current_id}'");	
		
		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}

		/** -------------------------------------
		/**  Are they allowed to move it?
		/** -------------------------------------*/
		if ( ! $this->_mod_permission('can_move', $query->row('forum_id') ))
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}
		
		/** -------------------------------------
		/**  Are there any other forums?
		/** -------------------------------------*/
		$f_query = $this->EE->db->query("SELECT forum_name, forum_id, forum_status, forum_permissions FROM exp_forums WHERE board_id = '".$this->_fetch_pref('board_id')."' AND forum_id != '".$query->row('forum_id') ."' AND forum_is_cat = 'n' ORDER BY forum_order ASC");
		
		if ($f_query->num_rows() == 0)
		{
			return $this->_trigger_error('no_forums_to_move_to');
		}
		
		/** -------------------------------------
		/**  Build the menu
		/** -------------------------------------*/
		
		$menu = '';
		
		foreach ($f_query->result_array() as $row)
		{
			$row['forum_permissions'] = unserialize(stripslashes($row['forum_permissions']));
						 
			// can the user view the forum?
			if ($row['forum_status'] != 'c' && ! $this->_permission('can_view_forum', $row['forum_permissions']))				
			{
				continue;
			}
			
			// can the user view the hidden forum?
			if ($row['forum_status'] == 'c' &&  ! $this->_permission('can_view_hidden', $row['forum_permissions']))
			{			
				continue;
			}
						
			$menu .= '<option value="'.$row['forum_id'].'">'.$row['forum_name'].'</option>';
		}
		
		
		$this->form_actions['forum:move_topic']['topic_id'] = $this->current_id;
		$this->form_actions['forum:move_topic']['RET'] = $this->_forum_path('/viewthread/'.$this->current_id.'/');
				
		return $this->_var_swap($this->_load_element('move_topic_confirmation'),
								array(
										'move_select_options'	=> $menu,
										'title' => $this->_convert_special_chars($query->row('title') )
									)
								);
	}

	
	
	
	/** -------------------------------------
	/**  Move Topic 
	/** -------------------------------------*/
	function move_topic()
	{
		$topic_id = $this->EE->input->post('topic_id');
		$forum_id = $this->EE->input->post('forum_id');
		
		if ( ! is_numeric($topic_id) OR ! is_numeric($forum_id))
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}
		
		/** -------------------------------------
		/**  Fetch the topic title
		/** -------------------------------------*/
		$query = $this->EE->db->query("SELECT title, forum_id, author_id FROM exp_forum_topics WHERE topic_id = '{$topic_id}'");	
		
		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}

		$author_id = $query->row('author_id') ;
		
		/** -------------------------------------
		/**  Are they allowed to move it?
		/** -------------------------------------*/
		if ( ! $this->_mod_permission('can_move', $query->row('forum_id') ))
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}

		/** -------------------------------------
		/**  Move it!
		/** -------------------------------------*/
		
		$title = ($this->EE->input->get_post('title') == '') ? $query->row('title')  : $this->EE->db->escape_str($this->EE->input->get_post('title'));
		$title = $this->_convert_special_chars($title);
		
		if ($this->EE->input->get_post('redirect'))
		{
			$this->EE->db->query("UPDATE exp_forum_topics SET forum_id = '{$forum_id}', moved_forum_id = '".$query->row('forum_id')."', title = '{$title}' WHERE topic_id = '{$topic_id}' ");
		}
		else
		{
			$this->EE->db->query("UPDATE exp_forum_topics SET forum_id = '{$forum_id}',  moved_forum_id = '0', title = '{$title}' WHERE topic_id = '{$topic_id}' ");
		}
		
		$this->EE->db->query("UPDATE exp_forum_posts SET forum_id = '{$forum_id}' WHERE topic_id = '{$topic_id}'");
		
		/** -------------------------------------
		/**  Update the stats for old/new forum
		/** -------------------------------------*/
		$this->_update_post_stats($forum_id);
		$this->_update_post_stats($query->row('forum_id') );
		$this->_update_global_stats();
		
		/* -------------------------------------
		/*  Get email address of topic author, but only if it's not
		/*  the moderator doing the moving.  Sheesh.
		/* -------------------------------------*/
		
		if ($this->EE->input->get_post('notify') AND $this->EE->session->userdata('member_id') != $author_id)
		{
			$query2 = $this->EE->db->query("SELECT email, screen_name FROM exp_members where member_id = '{$author_id}'");

			$swap = array(
							'forum_name'		=> $this->_fetch_pref('board_label'),
							'title'				=> $title,
							'name_of_recipient'	=> $query2->row('screen_name') ,
							'moderation_action' => $this->EE->lang->line('moved_action'),
							'thread_url'		=> $this->remove_session_id($this->EE->input->post('RET'))
						 );

			$template = $this->EE->functions->fetch_email_template('forum_moderation_notification');
			$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
			$email_msg = $this->EE->functions->var_swap($template['data'], $swap);

			/** -------------------------------------
			/**  Send Email
			/** -------------------------------------*/

			$this->EE->load->library('email');
			// Load the text helper
			$this->EE->load->helper('text');

			$this->EE->email->wordwrap = TRUE;

			$this->EE->email->EE_initialize();
			$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));	
			$this->EE->email->to($query2->row('email') ); 
			$this->EE->email->reply_to($this->EE->config->item('webmaster_email'));
			$this->EE->email->subject($email_tit);	
			$this->EE->email->message(entities_to_ascii($email_msg));		
			$this->EE->email->send();			
		}
		
		$this->EE->functions->redirect($this->EE->input->post('RET'));
		exit;	
	}

	
	
	/** -------------------------------------
	/**  Move Reply Confirmation
	/** -------------------------------------*/
	function move_reply_confirmation()
	{
		/** -------------------------------------
		/**  Fetch the topic title
		/** -------------------------------------*/
		$query = $this->EE->db->query("SELECT exp_forum_posts.topic_id, exp_forum_posts.forum_id, exp_forum_posts.body, exp_forum_posts.post_date, exp_forum_posts.parse_smileys,
							exp_forums.forum_text_formatting, exp_forums.forum_html_formatting, exp_forums.forum_auto_link_urls, exp_forums.forum_allow_img_urls,
							exp_members.screen_name
							FROM exp_forum_posts
							LEFT JOIN exp_forums ON exp_forums.forum_id = exp_forum_posts.forum_id
							LEFT JOIN exp_members ON exp_members.member_id = exp_forum_posts.author_id
							WHERE exp_forum_posts.post_id = '{$this->current_id}'");	
		
		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}

		/** -------------------------------------
		/**  Are they allowed to move it?
		/** -------------------------------------*/
		if ( ! $this->_mod_permission('can_move', $query->row('forum_id') ))
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}
		
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();
		$this->EE->typography->highlight_code = TRUE;
		$this->EE->typography->parse_smileys = ($query->row('parse_smileys')  == 'y') ? TRUE : FALSE;

		$body = $this->EE->typography->parse_type($query->row('body') ,
 								  array(
										'text_format'	=> $query->row('forum_text_formatting') ,
										'html_format'	=> $query->row('forum_html_formatting') ,
										'auto_links'	=> $query->row('forum_auto_link_urls') ,
										'allow_img_url' => $query->row('forum_allow_img_urls') 
										)
								  );
		
		$this->form_actions['forum:move_reply']['post_id'] = $this->current_id;
		$this->form_actions['forum:move_reply']['forum_path'] = $this->_forum_path();
		
		$template = $this->_load_element('move_reply_confirmation');
		
		$post_date  = ( ! preg_match_all("/{post_date\s+format=['|\"](.+?)['|\"]\}/i", $template, $matches)) ? FALSE : $matches;
		
		if ($post_date !== FALSE)
		{
			$count = count($post_date['0']);
			
			for ($i = 0; $i < $count; $i++)
			{
				$template = str_replace($post_date['0'][$i], $this->EE->localize->decode_date($post_date['1'][$i], $query->row('post_date') ), $template);
			}			
		}
		
		return $this->_var_swap($template,
								array(
										'body'		=> $body,
										'author'	=> $query->row('screen_name') 
									)
								);
	}

	
	
	/** -------------------------------------
	/**  Move a Reply!
	/** -------------------------------------*/
	function move_reply()
	{
		if ($this->EE->input->post('url') === FALSE OR $this->EE->input->get_post('post_id') === FALSE OR ! is_numeric($this->EE->input->get_post('post_id')))
		{
			$this->EE->functions->redirect($this->_forum_path());
			exit;
		}
				
		/** -------------------------------------
		/**  Fetch the post data
		/** -------------------------------------*/
			
		$query = $this->EE->db->query("SELECT * FROM exp_forum_posts WHERE post_id = '".$this->EE->db->escape_str($this->EE->input->post('post_id'))."'");
		
		/** -------------------------------------
		/**  No result?  Smack em'
		/** -------------------------------------*/
		
		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}
					
		/** -------------------------------------
		/**  Is the user allowed to move?
		/** -------------------------------------*/
			
		if ( ! $this->_mod_permission('can_move', $query->row('forum_id') ))
		{
			return $this->_trigger_error('not_authorized');
		}
		
		$post_id		= $query->row('post_id') ;
		$author_id		= $query->row('author_id') ;
		$old_topic_id	= $query->row('topic_id') ;
		$old_forum_id	= $query->row('forum_id') ;
		
		/** -------------------------------------
		/**  Gather the target topic ID
		/** -------------------------------------*/

		// Load the string helper
		$this->EE->load->helper('string');
	
		$new_topic_id = trim(trim_slashes($this->EE->input->post('url')));
		
		if ($new_topic_id == '')
		{
			$this->EE->functions->redirect($this->_forum_path().'movereply/'.$post_id.'/');
			exit;
		}

		if (FALSE !== (strpos($new_topic_id, "/")))
		{
			$new_topic_id = end(explode("/", $new_topic_id));
		}
		
		if ( ! is_numeric($new_topic_id))
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('move_reply_requires_id')));
		}	
		
		$tquery = $this->EE->db->query("SELECT topic_id, forum_id, title FROM exp_forum_topics WHERE topic_id = '".$this->EE->db->escape_str($new_topic_id)."'");
		
		if ($tquery->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}
		
		$new_topic_id = $tquery->row('topic_id') ;
		$new_forum_id = $tquery->row('forum_id') ;
		$title = $tquery->row('title') ;
		
		/** ---------------------------------------
		/**  You're the boss, move it!
		/** ---------------------------------------*/
		
		$this->EE->db->query($this->EE->db->update_string('exp_forum_posts', array('topic_id' => $new_topic_id, 'forum_id' => $new_forum_id), "post_id = '{$post_id}'"));
		
		// Update attachments
		$this->EE->db->query("UPDATE exp_forum_attachments SET topic_id = '{$new_topic_id}' WHERE topic_id = '{$old_topic_id}' AND post_id = '{$post_id}'");
		
		// Update topic stats (count, last post info)
		$this->_update_topic_stats($old_topic_id);
		$this->_update_topic_stats($new_topic_id);

		// Update the forum stats
		$this->_update_post_stats($old_forum_id);
		$this->_update_post_stats($new_forum_id);
		$this->_update_global_stats();
			
		/* -------------------------------------
		/*  Get email address of author of reply unless
		/*  it's the moderator doing the move.
		/* -------------------------------------*/
		if ($this->EE->input->get_post('notify') AND $this->EE->session->userdata('member_id') != $author_id)
		{
			$query = $this->EE->db->query("SELECT email, screen_name FROM exp_members WHERE member_id = '{$author_id}'");
		
			$swap = array(
							'forum_name'		=> $this->_fetch_pref('board_label'),
							'title'				=> $title,
							'name_of_recipient'	=> $query->row('screen_name') ,
							'moderation_action' => $this->EE->lang->line('moved_reply_action'),
							'thread_url'		=> $this->EE->input->post('forum_path').'viewreply/'.$post_id.'/'
						 );

			$template = $this->EE->functions->fetch_email_template('forum_moderation_notification');
			$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
			$email_msg = $this->EE->functions->var_swap($template['data'], $swap);

			/** -------------------------------------
			/**  Send Email
			/** -------------------------------------*/

			$this->EE->load->library('email');
			// Load the text helper
			$this->EE->load->helper('text');

			$this->EE->email->wordwrap = TRUE;
					
			$this->EE->email->EE_initialize();
			$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));	
			$this->EE->email->to($query->row('email') ); 
			$this->EE->email->reply_to($this->EE->config->item('webmaster_email'));
			$this->EE->email->subject($email_tit);	
			$this->EE->email->message(entities_to_ascii($email_msg));		
			$this->EE->email->send();
		}
	
		$this->EE->functions->redirect($this->EE->input->post('forum_path').'viewreply/'.$post_id.'/');
		exit;
	}

	

	/** -------------------------------------
	/**  Mark all posts as read
	/** -------------------------------------*/
	
	// We're basically cheating here.  All we're doing is updating
	// the 'last_visit' date for the given user.  Since we determine 
	// which posts have been read based on the date of the last visit,
	// if we make the last_visit equal to now we will inadvertenly make
	// all posts read.  I suspect we will need to do this a different
	// way in the future but for now it works.
	
	function mark_all_read()
	{
		if ($this->EE->session->userdata('member_id') != 0)
		{
			$this->EE->db->query("UPDATE exp_members SET last_visit = '".$this->EE->localize->now."' WHERE member_id = '".$this->EE->session->userdata('member_id')."'");
		}
		
			
		$data = array(	'title' 	=> $this->EE->lang->line('post_marked_read'),
						'heading'	=> $this->EE->lang->line('thank_you'),
						'content'	=> $this->EE->lang->line('post_marked_read'),
						'redirect'	=> $this->EE->functions->create_url($this->trigger),
						'link'		=> array($this->EE->functions->create_url($this->trigger), '')
					 );
			
		return $this->EE->output->show_message($data);
	}

	
	
	
	/** -------------------------------------
	/**  Subscribe to a post
	/** -------------------------------------*/
	
	function subscribe()
	{
		// Do we have a valid topic ID?
		$query = $this->EE->db->query("SELECT title FROM exp_forum_topics WHERE topic_id = '{$this->current_id}'");

		if ($query->num_rows() == 0)
		{
			return $this->_trigger_error();
		}

		$title = $this->_convert_special_chars($query->row('title') );

		$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_forum_subscriptions WHERE topic_id = '{$this->current_id}' AND member_id = '".$this->EE->session->userdata('member_id')."'");

		if ($query->row('count')  > 1)
		{
			$this->EE->db->query("DELETE FROM exp_forum_subscriptions WHERE topic_id = '{$this->current_id}' AND member_id = '".$this->EE->session->userdata('member_id')."'");
			$query->set_row('count', 0);
		}	
		if ($query->row('count')  == 0)
		{	
			$rand = $this->EE->session->userdata('member_id').$this->EE->functions->random('alnum', 8);
			$this->EE->db->query("INSERT INTO exp_forum_subscriptions (topic_id, board_id, member_id, subscription_date, hash) VALUES ('{$this->current_id}', '{$this->preferences['board_id']}', '".$this->EE->session->userdata('member_id')."', '{$this->EE->localize->now}', '{$rand}')");
		}
		

		$data = array(	'title' 	=> $this->EE->lang->line('thank_you'),
						'heading'	=> $this->EE->lang->line('thank_you'),
						'content'	=> $this->EE->lang->line('you_have_been_subscribed').'<br /><br /><b>'.$title.'</b>',
						'redirect'	=> $this->_forum_path('/viewthread/'.$this->current_id.'/'),
						'rate'		=> 3,
						'link'		=> array($this->_forum_path('/viewthread/'.$this->current_id.'/'), '')
					 );
			
		return $this->EE->output->show_message($data);
	}

	/** -------------------------------------
	/**  Un-subscribe to a post
	/** -------------------------------------*/
	
	function unsubscribe()
	{
		// Do we have a valid topic ID?
		$query = $this->EE->db->query("SELECT title FROM exp_forum_topics WHERE topic_id = '{$this->current_id}'");

		if ($query->num_rows() == 0)

		{
			return $this->_trigger_error();
		}

		$title = $this->_convert_special_chars($query->row('title') );

		$this->EE->db->query("DELETE FROM exp_forum_subscriptions WHERE topic_id = '{$this->current_id}' AND member_id = '".$this->EE->session->userdata('member_id')."'");

		$data = array(	'title' 	=> $this->EE->lang->line('thank_you'),
						'heading'	=> $this->EE->lang->line('thank_you'),
						'content'	=> $this->EE->lang->line('you_have_been_unsubscribed').'<br /><br /><b>'.$title.'</b>',
						'redirect'	=> $this->_forum_path('/viewthread/'.$this->current_id.'/'),
						'rate'		=> 3,
						'link'		=> array($this->_forum_path('/viewthread/'.$this->current_id.'/'), '')
					 );
			
		return $this->EE->output->show_message($data);
	}



	/** -------------------------------------
	/**  Remove notification for a posts via email
	/** -------------------------------------*/
	
	function delete_subscription()
	{
		if ( ! ($hash = $this->EE->input->get('id')))
		{
			return $this->_trigger_error();
		}
		
		if (strlen($hash) < 9 OR strlen($hash) > 15 OR preg_match('/[^0-9a-z]/i', $hash))
		{
			return $this->_trigger_error('invalid_subscription_id');
		}
		
		$query = $this->EE->db->query("SELECT title FROM exp_forum_topics t, exp_forum_subscriptions s WHERE t.topic_id = s.topic_id AND s.hash = '".$this->EE->db->escape_str($hash)."' ");

		if ($query->num_rows() != 1)
		{
			$title	 = $this->EE->lang->line('error');
			$heading = $this->EE->lang->line('error');
			$content = $this->EE->lang->line('not_subscribed_to_topic');
			
		}
		else
		{
			// prompt for confirmation
			if ( ! $this->EE->input->get('confirm'))
			{	
				$data['title']	 = $this->EE->lang->line('confirm_subscription_removal');
				$data['heading'] = $this->EE->lang->line('confirm_subscription_removal');
				$data['content'] = $this->EE->lang->line('remove_subscription_question')."<br /><br /><b>".$this->_convert_special_chars($query->row('title') )."</b>";
				$data['link']	 = array($this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->EE->input->get('ACT').'&id='.$hash.'&board_id='.$this->preferences['board_id'].'&confirm=yes', $this->EE->lang->line('subscription_confirmation_link'));
				
				return $this->EE->output->show_message($data);
			}

			$this->EE->db->query("DELETE FROM exp_forum_subscriptions WHERE hash = '".$this->EE->db->escape_str($hash)."'");
		
			$title	 = $this->EE->lang->line('subscription_cancelled');
			$heading = $this->EE->lang->line('thank_you');
			$content = $this->EE->lang->line('your_subscription_cancelled').'<br /><br /><b>'.$this->_convert_special_chars($query->row('title') ).'</b>';
		}

			
		$data = array(	'title' 	=> $title,
						'heading'	=> $heading,
						'content'	=> $content,
						'redirect'	=> '',
						'link'		=> array($this->_fetch_pref('board_forum_url'), $this->_fetch_pref('board_label'))
					 );
			
		return $this->EE->output->show_message($data);
	}

	
	
	

	/** -------------------------------------
	/**  Merge Page
	/** -------------------------------------*/
	function merge_page()
	{
		$post_id = '';
		$title	 = '';
		
		/** -------------------------------------
		/**  Fetch some meta data
		/** -------------------------------------*/
			
		$query = $this->EE->db->query("SELECT topic_id, forum_id, title FROM exp_forum_topics WHERE topic_id = '{$this->current_id}'");
		
		/** -------------------------------------
		/**  No result?  Smack em'
		/** -------------------------------------*/
		
		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}
		
		/** -------------------------------------
		/**  Create Vars for simplicity
		/** -------------------------------------*/
		
		foreach ($query->row_array() as $key => $val)
		{
			$$key = $val;
		}
			
		/** -------------------------------------
		/**  Is the user allowed to merge?
		/** -------------------------------------*/
			
		if ( ! $this->_mod_permission('can_merge', $forum_id))
		{
			return $this->_trigger_error('not_authorized');
		}
		
		/** -------------------------------------
		/**  Define the redirect location
		/** -------------------------------------*/
		
		$this->form_actions['forum:do_merge']['RET'] = $this->_forum_path('/viewthread/'.$topic_id.'/');
		$this->form_actions['forum:do_merge']['forum_path'] = $this->_forum_path();
		$this->form_actions['forum:do_merge']['topic_id'] = $topic_id;
	
		/** -------------------------------------
		/**  Build the message
		/** -------------------------------------*/
	
		$str = $this->_var_swap($this->_load_element('merge_interface'),
								array(
										'title' => $title
									)
								);
		
		return str_replace('{include:merge_interface}', $str, $this->_load_element('merge_page'));
	}



	/** -------------------------------------
	/**  Perform the merge
	/** -------------------------------------*/
	function do_merge()
	{
		if ($this->EE->input->post('RET') === FALSE OR $this->EE->input->get_post('url') === FALSE OR $this->EE->input->get_post('topic_id') === FALSE OR ! is_numeric($this->EE->input->get_post('topic_id')))
		{
			$this->EE->functions->redirect($this->_forum_path());
			exit;
		}
		
		/** -------------------------------------
		/**  Is the title blank?
		/** -------------------------------------*/
		
		if ($this->EE->input->get_post('title') == '')
		{
			return $this->EE->output->show_user_error('submission', array($this->EE->lang->line('empty_title_field')));
		}		
				
		/** -------------------------------------
		/**  Fetch the topic data
		/** -------------------------------------*/
			
		$query = $this->EE->db->query("SELECT * FROM exp_forum_topics WHERE topic_id = '".$this->EE->db->escape_str($this->EE->input->post('topic_id'))."'");
		
		/** -------------------------------------
		/**  No result?  Smack em'
		/** -------------------------------------*/
		
		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}
					
		/** -------------------------------------
		/**  Is the user allowed to merge?
		/** -------------------------------------*/
			
		if ( ! $this->_mod_permission('can_merge', $query->row('forum_id') ))
		{
			return $this->_trigger_error('not_authorized');
		}
		
		$topic_id	= $query->row('topic_id') ;
		$forum_id	= $query->row('forum_id') ;
		$topic_fid	= $query->row('forum_id') ;
		$title 		= $query->row('title') ;

		/** -------------------------------------
		/**  Gather the merge ID
		/** -------------------------------------*/

		// Load the string helper
		$this->EE->load->helper('string');
		
		$merge_id = trim(trim_slashes($this->EE->input->post('url')));
		
		if ($merge_id == '')
		{
			$this->EE->functions->redirect($this->EE->input->post('forum_path').'merge/'.$this->EE->input->get_post('topic_id').'/');
			exit;
		}

		if (FALSE !== (strpos($merge_id, "/")))
		{
			$merge_id = end(explode("/", $merge_id));
		}
		
		if ( ! is_numeric($merge_id))
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('merge_requires_id')));
		}	

		if ($merge_id == $topic_id)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('merge_duplicate_id')));
		}
			
		/** -------------------------------------
		/**  Which topic is the earliest?
		/** -------------------------------------*/
		
		// At this point we need to determine which topic of the two being merged came first.
		// We will take the later of the two topics and turn it into a post.
			
		$result = $this->EE->db->query("SELECT topic_id, forum_id, topic_date, forum_id FROM exp_forum_topics WHERE topic_id = '".$this->EE->db->escape_str($merge_id)."'");
		
		/** -------------------------------------
		/**  No result?  Scold them...
		/** -------------------------------------*/
		
		if ($result->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}

		// The merged topic is newer.  It will then become a post
		if ($result->row('topic_date')  > $query->row('topic_date') )
		{
			$query = $this->EE->db->query("SELECT * FROM exp_forum_topics WHERE topic_id = '".$this->EE->db->escape_str($merge_id)."'");
			$merge_fid = $query->row('forum_id') ;
		}
		else
		{
			// If the merge topic is older, the origial topic becomes a post
			$merge_id  = $topic_id;
			$merge_fid = $result->row('forum_id') ;
			$topic_id  = $result->row('topic_id') ;
			$forum_id  = $result->row('forum_id') ;
		}
		
		/** -------------------------------------
		/**  Compile and insert the post data
		/** -------------------------------------*/
		
		$data = array(
						'topic_id'		=> $topic_id,
						'forum_id'		=> $forum_id,
						'body'			=> $query->row('body') ,
						'parse_smileys'	=> $query->row('parse_smileys') ,
						'author_id'		=> $query->row('author_id') ,
						'ip_address'	=> $query->row('ip_address') ,
						'post_date'		=> $query->row('topic_date') ,
						'board_id'		=> $this->_fetch_pref('board_id')
					 );
		 
			$this->EE->db->query($this->EE->db->insert_string('exp_forum_posts', $data));	

		// Update attachments
		$this->EE->db->query("UPDATE exp_forum_attachments SET topic_id = '{$topic_id}', post_id = '".$this->EE->db->insert_id()."' WHERE topic_id = '{$merge_id}'");

		// Update the merge posts
		$this->EE->db->query("UPDATE exp_forum_posts SET topic_id = '{$topic_id}', forum_id = '{$forum_id}' WHERE topic_id = '{$merge_id}'");
		
		// Update topic stats (count, last post info)
		$this->_update_topic_stats($topic_id);
						
		// Update the topic ID
		$this->EE->db->query("UPDATE exp_forum_posts SET topic_id = '{$topic_id}' WHERE topic_id = '{$merge_id}'");
		$this->EE->db->query("UPDATE exp_forum_polls SET topic_id = '{$topic_id}' WHERE topic_id = '{$merge_id}'");
		$this->EE->db->query("UPDATE exp_forum_pollvotes SET topic_id = '{$topic_id}' WHERE topic_id = '{$merge_id}'");
		$this->EE->db->query("UPDATE exp_forum_attachments SET topic_id = '{$topic_id}' WHERE topic_id = '{$merge_id}'");

		// the forum subscription table uses a primary key of topic_id-member_id, so there may already be a record for the
		// original thread if a member was subscribed to both threads in the merge.  So for all members that are subsribed
		// to both, we must first drop the original subscription before updating to the new one
		$query = $this->EE->db->query("SELECT member_id FROM exp_forum_subscriptions WHERE topic_id = '{$topic_id}'");
		
		if ($query->num_rows() > 0)
		{
			$member_ids = array();

			foreach ($query->result_array() as $row)
			{
				$member_ids[] = $row['member_id'];
			}
			
			$this->EE->db->query("DELETE FROM exp_forum_subscriptions WHERE topic_id = '{$merge_id}' AND member_id IN (".implode(',', $member_ids).")");
		}
		
		$this->EE->db->query("UPDATE exp_forum_subscriptions SET topic_id = '{$topic_id}' WHERE topic_id = '{$merge_id}'");
	
		// Delete the old topic	
		$this->EE->db->query("DELETE FROM exp_forum_topics WHERE topic_id = '{$merge_id}'");
		
	
		// Update the forum stats
		$this->_update_post_stats($forum_id);
		
		if (isset($merge_fid))
		{
			$this->_update_post_stats($topic_fid);
			$this->_update_post_stats($merge_fid);
		}

		$this->_update_global_stats();
		
		/** -------------------------------------
		/**  Set the new title
		/** -------------------------------------*/
		
		$new_title = $this->EE->input->post('title');	
		
		if ($new_title != $title)
		{
			$title = $this->_convert_forum_tags($new_title);
		}
		
		$this->EE->db->query("UPDATE exp_forum_topics SET title = '".$this->EE->db->escape_str($title)."' WHERE topic_id = '{$topic_id}'");
		$this->EE->db->query("UPDATE exp_forums SET forum_last_post_title = '".$this->EE->db->escape_str($title)."' WHERE forum_last_post_id = '{$topic_id}'");
		
		/* -------------------------------------
		/*  Get email address of author of merged topic unless
		/*  it's the moderator doing the merge.  Sheesh.
		/* -------------------------------------*/
		if ($this->EE->input->get_post('notify') AND $this->EE->session->userdata('member_id') != $data['author_id'])
		{
			$query = $this->EE->db->query("SELECT email, screen_name FROM exp_members WHERE member_id = '{$data['author_id']}'");
		
			$swap = array(
							'forum_name'		=> $this->_fetch_pref('board_label'),
							'title'				=> $title,
							'name_of_recipient'	=> $query->row('screen_name') ,
							'moderation_action' => $this->EE->lang->line('merged_action'),
							'thread_url'		=> $this->EE->input->post('forum_path').'viewthread/'.$topic_id.'/'
						 );

			$template = $this->EE->functions->fetch_email_template('forum_moderation_notification');
			$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
			$email_msg = $this->EE->functions->var_swap($template['data'], $swap);

			/** -------------------------------------
			/**  Send Email
			/** -------------------------------------*/

			$this->EE->load->library('email');
			// Load the text helper
			$this->EE->load->helper('text');

			$this->EE->email->wordwrap = TRUE;
					
			$this->EE->email->EE_initialize();
			$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));	
			$this->EE->email->to($query->row('email') ); 
			$this->EE->email->reply_to($this->EE->config->item('webmaster_email'));
			$this->EE->email->subject($email_tit);	
			$this->EE->email->message(entities_to_ascii($email_msg));		
			$this->EE->email->send();
		}
	
		$this->EE->functions->redirect($this->EE->input->post('forum_path').'viewthread/'.$topic_id.'/');
		exit;
	}

	

	/** -------------------------------------
	/**  Split Page
	/** -------------------------------------*/
	function split_data()
	{
		return $this->threads(FALSE, FALSE, TRUE);
	}


	/** -------------------------------------
	/**  Do the split!  Make sure and stretch first...
	/** -------------------------------------*/
	function do_split()
	{
		if ( isset($_POST['next_page']) OR isset($_POST['previous_page']))
		{
			if ( isset($_POST['topic_id']) && is_numeric($_POST['topic_id']))
			{
				$this->current_id = $_POST['topic_id'];
				$this->current_request = 'split';
				return $this->_display_forum('split');
			}
		}
		
		if ( ! isset($_POST['post_id']))
		{
			return $this->EE->output->show_user_error('submission', array($this->EE->lang->line('split_info')));
		}
	
		if ( ! is_numeric($this->EE->input->post('topic_id')) OR ! is_numeric($this->EE->input->get_post('forum_id')))
		{
			return $this->_trigger_error();
		}
	
		/** -------------------------------------
		/**  Is the title blank?
		/** -------------------------------------*/
		
		if ($this->EE->input->get_post('title') == '')
		{
			return $this->EE->output->show_user_error('submission', array($this->EE->lang->line('empty_title_field')));
		}		
		
		/** -------------------------------------
		/**  Is the user allowed to split?
		/** -------------------------------------*/
		
		$old_topic_id = $this->EE->input->post('topic_id');
		$query = $this->EE->db->query("SELECT forum_id, title FROM exp_forum_topics WHERE topic_id = '".$this->EE->db->escape_str($old_topic_id)."'");

		if ($query->num_rows() == 0)
		{
			return $this->_trigger_error();
		}
	
		$old_forum_id = $query->row('forum_id') ;
		$new_forum_id = $this->EE->input->post('forum_id');
	
		if ( ! $this->_mod_permission('can_split', $old_forum_id) OR ! $this->_mod_permission('can_split', $new_forum_id))
		{
			return $this->_trigger_error('can_not_split');
		}
		
		/** -------------------------------------
		/**  Safety check - only numeric IDs allowed
		/** -------------------------------------*/
		
		if ( ! is_array($_POST['post_id']))
		{
			return $this->_trigger_error();
		}
		
		foreach ($_POST['post_id'] as $id)
		{
			if ( ! is_numeric($id))
			{
				return $this->_trigger_error();
			}
		}

		/** -------------------------------------
		/**  Sort the split IDs
		/** -------------------------------------*/
		// The earliest ID will become the topic so just to be
		// safe we'll fetch the post_ids based on date
	
		$query = $this->EE->db->query("SELECT post_id, post_date, author_id FROM exp_forum_posts WHERE post_id IN (".implode(', ', $_POST['post_id']).") ORDER BY post_date ASC");

		if ($query->num_rows() == 0)
		{
			return $this->_trigger_error();
		}
		
		$i = 1;
		$last_post = $this->EE->localize->now;
		$last_author = $this->EE->session->userdata('member_id');
		$post_ids = array();
		foreach ($query->result_array() as $row)
		{
			$post_ids[] = $row['post_id'];
			
			if ($query->num_rows() == $i)
			{
				$last_post = $row['post_date'];
				$last_author = $row['author_id'];
			}
			
			$i++;
		}
		
		/** -------------------------------------
		/**  Grab the post data from the earlist one and create a topic
		/** -------------------------------------*/
				
		$post_id = current($post_ids);
		unset($post_ids['0']);

		$query = $this->EE->db->query("SELECT * FROM exp_forum_posts WHERE post_id = '".$post_id."'");
	
		$title = $this->_convert_forum_tags($this->EE->input->get_post('title'));
		$data = array(
						'forum_id'				=> $new_forum_id,
						'title'					=> $this->EE->security->xss_clean($title),
						'body'					=> $query->row('body') ,
						'sticky'				=> 'n',
						'status'				=> 'o',
						'announcement'			=> 'n',
						'poll'					=> 'n',
						'parse_smileys'			=> $query->row('parse_smileys') ,
						'author_id'				=> $query->row('author_id') ,
						'ip_address'			=> $query->row('ip_address') ,
						'parse_smileys'			=> $query->row('parse_smileys') ,
						'topic_date'			=> $query->row('post_date') ,
						'thread_total'			=> count($post_ids) + 1,  // Add back the topic
						'last_post_date' 		=> $last_post,
						'last_post_author_id'	=> $last_author,
						'board_id'				=> $this->_fetch_pref('board_id')
					 );

		$this->EE->db->query($this->EE->db->insert_string('exp_forum_topics', $data));	
		$topic_id = $this->EE->db->insert_id();
		
		// Delete the old post	
		$this->EE->db->query("DELETE FROM exp_forum_posts WHERE post_id = '{$post_id}'");
		
		// Update attachments
		$this->EE->db->query("UPDATE exp_forum_attachments SET topic_id = '{$topic_id}', post_id = '0' WHERE post_id = '{$post_id}'");
		
		/** -------------------------------------
		/**  Are there more posts in the split?
		/** -------------------------------------*/
		if (count($post_ids) > 0)
		{
			foreach ($post_ids as $id)
			{
				$this->EE->db->query("UPDATE exp_forum_posts SET topic_id = '{$topic_id}', forum_id = '{$new_forum_id}' WHERE post_id = '{$id}'");
				$this->EE->db->query("UPDATE exp_forum_attachments SET topic_id = '{$topic_id}' WHERE post_id = '{$id}'");
			
			}
		}
		
		// Update the thread stats (count, last post info)
		$this->_update_topic_stats($old_topic_id);
		
		// Update the forum stats
		$this->_update_post_stats($new_forum_id);

		if ($new_forum_id != $old_forum_id)
		{
			$this->_update_post_stats($old_forum_id);	
		}

		$this->_update_global_stats();
		
		/* -------------------------------------
		/*  Get email address of new topic author but only if it's not
		/*  the moderator doing the split.  Sheesh.
		/* -------------------------------------*/
		
		if ($this->EE->input->get_post('notify') AND $this->EE->session->userdata('member_id') != $data['author_id'])
		{
			$query = $this->EE->db->query("SELECT email, screen_name FROM exp_members WHERE member_id = '{$data['author_id']}'");
		
			$swap = array(
							'forum_name'		=> $this->_fetch_pref('board_label'),
							'title'				=> $data['title'],
							'name_of_recipient'	=> $query->row('screen_name') ,
							'moderation_action' => $this->EE->lang->line('split_action'),
							'thread_url'		=> str_replace('viewforum', 'viewthread', $_POST['RET']).$topic_id.'/'
						 );

			$template = $this->EE->functions->fetch_email_template('forum_moderation_notification');
			$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
			$email_msg = $this->EE->functions->var_swap($template['data'], $swap);

			/** -------------------------------------
			/**  Send Email
			/** -------------------------------------*/

			$this->EE->load->library('email');
			
			// Load the text helper
			$this->EE->load->helper('text');
						
			$this->EE->email->wordwrap = TRUE;
					
			$this->EE->email->EE_initialize();
			$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));	
			$this->EE->email->to($query->row('email') ); 
			$this->EE->email->reply_to($this->EE->config->item('webmaster_email'));
			$this->EE->email->subject($email_tit);	
			$this->EE->email->message(entities_to_ascii($email_msg));		
			$this->EE->email->send();
		}

		$this->EE->functions->redirect($this->EE->functions->remove_double_slashes($_POST['RET'].$new_forum_id.'/'));
		exit;	
	}


	/** -------------------------------------
	/**  Report Page
	/** -------------------------------------*/
	function report_page()
	{
		if ($this->current_request == 'reporttopic')
		{
			$is_topic = TRUE;
			$query = $this->EE->db->query("SELECT forum_id, topic_id, title, body, author_id, parse_smileys FROM exp_forum_topics WHERE topic_id = '{$this->current_id}'");			
		}
		else
		{
			$is_topic = FALSE;
			$query = $this->EE->db->query("SELECT forum_id, topic_id, body, author_id, parse_smileys FROM exp_forum_posts WHERE post_id = '{$this->current_id}'");
		}
		
		/** -------------------------------------
		/**  Can't report it iffen it don't exist
		/** -------------------------------------*/
		
		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}
		
		/** -------------------------------------
		/**  Create some variables
		/** -------------------------------------*/
		
		foreach ($query->row_array() as $key => $val)
		{
			$$key = $val;
		}
		
		/** -------------------------------------
		/**  Multiple personality disorder?
		/** -------------------------------------*/
		
		if ($this->EE->session->userdata['member_id'] == $author_id)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('cannot_report_self')));
		}
		
		/** -------------------------------------
 		/**  Allowed to Report?
 		/** -------------------------------------*/
		
		$meta = $this->_fetch_forum_metadata($forum_id);
		$perms = unserialize(stripslashes($meta[$forum_id]['forum_permissions']));

		if ( ! $this->_permission('can_report', $perms))
		{
			return $this->_trigger_error('not_authorized');
		}
		
		/** -------------------------------------
		/**  Author's screen name
		/** -------------------------------------*/
		
		$query = $this->EE->db->query("SELECT screen_name FROM exp_members WHERE member_id = '{$author_id}'");
		
		// If this author doesn't exist, then we have problems, but anyway...
		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}		
		
		$author = $query->row('screen_name') ;
		
  		/** -------------------------------------
  		/**  Set up redirect
  		/** -------------------------------------*/
		
		$this->form_actions['forum:do_report']['RET'] = $this->_forum_path('/viewthread/'.$topic_id.'/');
		$this->form_actions['forum:do_report']['forum_path'] = $this->_forum_path();
		$this->form_actions['forum:do_report']['forum_id'] = $forum_id;
		$this->form_actions['forum:do_report']['post_id'] = $this->current_id;
		$this->form_actions['forum:do_report']['is_topic'] = ($is_topic) ? 'y' : 'n';
	
 		/** -------------------------------------
 		/**  Build the template
 		/** -------------------------------------*/
		
		$str = $this->_load_element('report_form');
		
		/** -------------------------------------
		/**  Topic or Post?
		/** -------------------------------------*/
		
		if ($is_topic)
		{
			$str = $this->_allow_if('is_topic', $str);
			$str = $this->_deny_if('is_post', $str);
		}
		else
		{
			$str = $this->_deny_if('is_topic', $str);
			$str = $this->_allow_if('is_post', $str);
		}
		
		$query = $this->EE->db->query("SELECT forum_text_formatting, forum_html_formatting, forum_auto_link_urls, forum_allow_img_urls FROM exp_forums WHERE forum_id = '{$forum_id}'");
		
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();
		$this->EE->typography->highlight_code = TRUE;
		$this->EE->typography->parse_smileys = ($parse_smileys == 'y') ? TRUE : FALSE;
		
		$str = $this->_var_swap($str,
								array(
										'body'	 				=> $this->EE->typography->parse_type($body, 
														 								  array(
																								'text_format'	=> $query->row('forum_text_formatting') ,
																								'html_format'	=> $query->row('forum_html_formatting') ,
																								'auto_links'	=> $query->row('forum_auto_link_urls') ,
																								'allow_img_url' => $query->row('forum_allow_img_urls') 
																								)
																						  ),
										'author'				=> $author,
										'title'					=> ($is_topic AND isset($title)) ? $title : '',
										'reporter_name'			=> $this->EE->session->userdata['screen_name'],
										'path:reporter_profile'	=> $this->_profile_path($this->EE->session->userdata['member_id']),
										'path:post'				=> $this->_forum_path('/'.(($is_topic) ? 'viewtopic' : 'viewreply')."/{$this->current_id}/")
									)
								);
		
		return str_replace('{include:report_form}', $str, $this->_load_element('report_page'));	
	}

	
	
	/** -------------------------------------
	/**  Report a post
	/** -------------------------------------*/
	
	function do_report()
	{
		$hidden = array('RET', 'forum_id', 'forum_path', 'post_id', 'is_topic');
		
		foreach ($hidden as $val)
		{
			if ( ! ($$val = $this->EE->input->post($val)))
			{
				$this->EE->functions->redirect($this->_forum_path());
				exit;
			}
		}
		
		// Could have added this in the conditional above, but this is more legible
		if ( ! is_numeric($forum_id) OR ! is_numeric($post_id))
		{
			$this->EE->functions->redirect($this->_forum_path());
			exit;
		}
		
		/** -------------------------------------
 		/**  Allowed to Report?
 		/** -------------------------------------*/
		
		$meta = $this->_fetch_forum_metadata($forum_id);
		$perms = unserialize(stripslashes($meta[$forum_id]['forum_permissions']));

		if ( ! $this->_permission('can_report', $perms))
		{
			return $this->_trigger_error('not_authorized');
		}

		/** -------------------------------------
		/**  Did they choose a reason?
		/** -------------------------------------*/
		
		$reason = array();
		
		if ( ! ($reason = $this->EE->input->post('reason')))
		{
			return $this->EE->output->show_user_error('submission', array($this->EE->lang->line('report_missing_reason')));
		}
		
		$reason_text = '';

		foreach ($reason as $val)
		{
			$reason_text .= ($val !== FALSE) ? $this->EE->lang->line($val)."\n" : '';
		}

		/** -------------------------------------
		/**  Is this a topic being reported?
		/** -------------------------------------*/
		
		$is_topic = ($is_topic == 'y') ? TRUE : FALSE;

		if ($is_topic)
		{
			$query = $this->EE->db->query("SELECT forum_id, topic_id, title, body, author_id, parse_smileys FROM exp_forum_topics WHERE topic_id = '{$post_id}'");			
		}
		else
		{
			$query = $this->EE->db->query("SELECT forum_id, topic_id, body, author_id, parse_smileys FROM exp_forum_posts WHERE post_id = '{$post_id}'");
		}

		/** -------------------------------------
		/**  Smack!
		/** -------------------------------------*/
		
		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}
		
		/** -------------------------------------
		/**  Create some variables
		/** -------------------------------------*/
		
		foreach ($query->row_array() as $key => $val)
		{
			$$key = $val;
		}
		
		/** -------------------------------------
		/**  Can't report yourself
		/** -------------------------------------*/
		
		if ($this->EE->session->userdata['member_id'] == $author_id)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('cannot_report_self')));
		}
		
		$query = $this->EE->db->query("SELECT screen_name FROM exp_members WHERE member_id ='{$author_id}'");
		
		// If this author doesn't exist, then we have problems, but anyway...
		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}		
		
		$author = $query->row('screen_name') ;
		
		/** -------------------------------------
		/**  Load up email addresses
		/** -------------------------------------*/
		
		$addresses = array();
				
		$this->EE->db->select('email');	
		$this->EE->db->from('members, forum_moderators');
		$this->EE->db->where('(exp_members.member_id = exp_forum_moderators.mod_member_id OR exp_members.group_id =  exp_forum_moderators.mod_group_id)', NULL, FALSE); 
		$this->EE->db->where('exp_forum_moderators.mod_forum_id', $forum_id);
		$mquery = $this->EE->db->get();	

		if ($mquery->num_rows() == 0)
		{
			$addresses[] = $this->EE->config->item('webmaster_email');
		}
		else
		{
			foreach($mquery->result_array() as $row)
			{
				$addresses[] = $row['email'];
			}
		}
		
		$addresses = array_unique($addresses);
		
		/** -------------------------------------
		/**  Send the notifications
		/** -------------------------------------*/
		
		$swap = array(
						'forum_name'		=> $this->_fetch_pref('board_label'),
						'reporter_name'		=> $this->EE->session->userdata['screen_name'],
						'author'			=> $author,
						'body'				=> $this->EE->security->xss_clean($body),
						'reasons'			=> $reason_text,
						'notes'				=> ($this->EE->input->post('notes')) ? $this->EE->security->xss_clean($_POST['notes']) : '',
						'post_url'			=> ($is_topic) ? "{$forum_path}viewthread/{$post_id}/" : "{$forum_path}viewreply/{$post_id}/"
					 );

		$template = $this->EE->functions->fetch_email_template('forum_report_notification');
		$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
		$email_msg = $this->EE->functions->var_swap($template['data'], $swap);

		/** -------------------------------------
		/**  Send Email
		/** -------------------------------------*/

		$this->EE->load->library('email');

		// Load the text helper
		$this->EE->load->helper('text');

		$this->EE->email->wordwrap = TRUE;
		
		foreach ($addresses as $address)
		{
			$this->EE->email->EE_initialize();
			$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));	
			$this->EE->email->to($address); 
			$this->EE->email->reply_to($this->EE->config->item('webmaster_email'));
			$this->EE->email->subject($email_tit);	
			$this->EE->email->message(entities_to_ascii($email_msg));		
			$this->EE->email->send();			
		}
		
		$this->EE->functions->redirect($RET);
		exit;
	}

	
	
	
	/** -------------------------------------
	/**  Member banning form
	/** -------------------------------------*/
	function ban_member_form()
	{
		if ( ! $this->_is_admin() OR ! is_numeric($this->current_id))
		{
			return $this->_trigger_error();
		}
		
		/** -------------------------------------
		/**  You can't ban yourself
		/** -------------------------------------*/
		if ($this->current_id == $this->EE->session->userdata('member_id'))
		{
			return $this->_trigger_error('can_not_ban_yourself');
		}
		
		/** -------------------------------------
		/**  Fetch the member info
		/** -------------------------------------*/
		$query = $this->EE->db->query("SELECT screen_name, group_id FROM exp_members WHERE member_id = '{$this->current_id}'");
		
		if ($query->num_rows() == 0)
		{
			return $this->_trigger_error();
		}
		
		/** -------------------------------------
		/**  Super-admins can't be banned
		/** -------------------------------------*/
		if ($query->row('group_id')  == 1)
		{
			return $this->_trigger_error('can_not_ban_super_admins');
		}
		
		/** -------------------------------------
		/**  Admins can not be banned - except by a super admin
		/** -------------------------------------*/
		
		if ($this->_is_admin($this->current_id, $query->row('group_id') ) AND $this->EE->session->userdata('group_id') != 1)
		{
			return $this->_trigger_error('admins_can_not_be_banned');
		}
		

		/** -------------------------------------
		/**  Finalize the template
		/** -------------------------------------*/
		
		$form = $this->EE->functions->form_declaration(array(
												'action' => $this->_forum_path('do_ban_member/'.$this->current_id),
												'hidden_fields' => array('board_id' => $this->_fetch_pref('original_board_id'))
											)
										);  

		$template = $this->_var_swap($this->_load_element('user_banning_warning'),
								array(
										'name'	=> $this->_convert_special_chars($query->row('screen_name') ),
										'form_declaration' => $form
									)
								);
								
								
		/** -------------------------------------
		/**  Is user already banned?
		/** -------------------------------------*/
		
		if ($query->row('group_id')  == 2)
		{
			$template = $this->_allow_if('user_is_banned', $template);
			$template = $this->_deny_if('user_not_banned', $template);
		}
		else
		{
			$template = $this->_deny_if('user_is_banned', $template);
			$template = $this->_allow_if('user_not_banned', $template);
		}
		
				
		return $this->_var_swap($this->_load_element('user_banning_page'),
								array(
										'include:user_banning_element'	=> $template
									)
								);
	}

	


	/** -------------------------------------
	/**  Ban Member
	/** -------------------------------------*/
	function do_ban_member()
	{
		if ( ! $this->_is_admin() OR ! is_numeric($this->current_id) OR ! isset($_POST['action']) OR ! in_array($_POST['action'], array('suspend', 'delete', 'reinstate')))
		{
			return $this->_trigger_error();
		}
				
		/** -------------------------------------
		/**  You can't ban yourself
		/** -------------------------------------*/
		if ($this->current_id == $this->EE->session->userdata('member_id'))
		{
			return $this->_trigger_error('can_not_ban_yourself');
		}
		
		/** -------------------------------------
		/**  Fetch the member info
		/** -------------------------------------*/
		$query = $this->EE->db->query("SELECT screen_name, group_id, ip_address FROM exp_members WHERE member_id = '".$this->EE->db->escape_str($this->current_id)."'");
		
		if ($query->num_rows() == 0)
		{
			return $this->_trigger_error();
		}
		
		$screen_name = $query->row('screen_name') ;
		$ip_address  = $query->row('ip_address') ;
				
		/** -------------------------------------
		/**  Super-admins can't be banned
		/** -------------------------------------*/
		if ($query->row('group_id')  == 1)
		{
			return $this->_trigger_error('can_not_ban_super_admins');
		}
	
		/** -------------------------------------
		/**  Admins can not be banned - except by a super admin
		/** -------------------------------------*/
		
		if ($this->_is_admin($this->current_id, $query->row('group_id') ) AND $this->EE->session->userdata('group_id') != 1)
		{
			return $this->_trigger_error('admins_can_not_be_banned');
		}
		
		/** -------------------------------------
		/**  Ban IP Addresses
		/** -------------------------------------*/
		
		// If we're banning we need to fetch any IPs used by the member
		
		$banned_user_ips = '';

		if (isset($_POST['ban_ip']) OR $_POST['action'] == 'reinstate')
		{
			$ips = array();

			$ips[] = $ip_address;
			
			// Topics
			$query = $this->EE->db->query("SELECT ip_address FROM exp_forum_topics WHERE author_id = '{$this->current_id}'");

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					if ( ! in_array($row['ip_address'], $ips))
					{
						$ips[] = $row['ip_address'];
					}
				}
			}
			
			// Posts
			$query = $this->EE->db->query("SELECT ip_address FROM exp_forum_posts WHERE author_id = '{$this->current_id}'");
			
			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					if ( ! in_array($row['ip_address'], $ips))
					{
						$ips[] = $row['ip_address'];
					}
				}
			}
			
			// Comments
			$query = $this->EE->db->query("SELECT ip_address FROM exp_comments WHERE author_id = '{$this->current_id}'");
			
			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					if ( ! in_array($row['ip_address'], $ips))
					{
						$ips[] = $row['ip_address'];
					}
				}
			}
			
			// We need this for reporting
			
			$banned_user_ips = implode("<br />", $ips);
			
			// Grab any currently banned IPs and add them to the array
						
			$banned_ips = $this->EE->config->item('banned_ips');
			$unbanned_ips = array();
						
			if ($banned_ips != '' && strpos($banned_ips, '|') !== FALSE)
			{
				foreach (explode('|', $banned_ips) as $ip)
				{
					if ($_POST['action'] == 'reinstate')
					{
						if ( ! in_array($ip, $ips))
						{
							$unbanned_ips[] = $ip;
						}
					}
					else
					{
						$ips[] = $ip;
					}
				}			
			}

			// Compile the ip addresses for storage
			
			if ($_POST['action'] == 'reinstate')
			{
				$ips = implode('|', array_unique($unbanned_ips));
			}
			else
			{
				$ips = implode('|', array_unique($ips));
			}
			
			$this->EE->config->_update_config(array('banned_ips' => $ips));		
		}

		/** -------------------------------------
		/**  Reinstate the user
		/** -------------------------------------*/
	
		if ($_POST['action'] == 'reinstate')
		{
			$this->EE->db->query("UPDATE exp_members SET group_id = '".$this->EE->config->item('default_member_group')."' WHERE member_id = '{$this->current_id}'");			
			$ban_msg = $this->EE->lang->line('user_account_reinstated');
			$banned_user_ips = '';
		}
		elseif ($_POST['action'] == 'suspend')
		{
			/** -------------------------------------
			/**  Suspend the user
			/** -------------------------------------*/
		
			$this->EE->db->query("UPDATE exp_members SET group_id = '2' WHERE member_id = '{$this->current_id}'");			
			$ban_msg = $this->EE->lang->line('user_account_suspended');
		}
		else
		{
			/** -------------------------------------
			/**  Delete the user and kill all posts
			/** -------------------------------------*/
			
			// first fetch affected forum topics for stat updating later
			$tquery = $this->EE->db->query("SELECT topic_id FROM exp_forum_topics WHERE author_id ='{$this->current_id}'");
			$pquery = $this->EE->db->query("SELECT topic_id FROM exp_forum_posts WHERE author_id = '{$this->current_id}'");
			$topics = array();
			$ids	= array();
			
			if ($tquery->num_rows() > 0)
			{
				foreach ($tquery->result_array() as $row)
				{
					$topics[] = $row['topic_id'];
					$ids[] = "topic_id = '".$row['topic_id']."'";
				}
			}

			if ($pquery->num_rows() > 0)
			{
				foreach ($pquery->result_array() as $row)
				{
					$topics[] = $row['topic_id'];
				}
			}
			
			$topics = array_unique($topics);			
			$IDS = implode(" OR ", $ids);
			
			// Delete any posts from other users that belong to topics that we will be decimating shortly
			if ($IDS != '')
			{
				$this->EE->db->query("DELETE FROM exp_forum_posts WHERE ".$IDS);				
			}
			
			// Now we can zap the rest
			$this->EE->db->query("DELETE FROM exp_members WHERE member_id = '{$this->current_id}'");
			$this->EE->db->query("DELETE FROM exp_member_data WHERE member_id = '{$this->current_id}'");
			$this->EE->db->query("DELETE FROM exp_member_homepage WHERE member_id = '{$this->current_id}'");
			$this->EE->db->query("DELETE FROM exp_forum_topics WHERE author_id = '{$this->current_id}'");
			$this->EE->db->query("DELETE FROM exp_forum_posts  WHERE author_id = '{$this->current_id}'");
			$this->EE->db->query("DELETE FROM exp_forum_subscriptions  WHERE member_id = '{$this->current_id}'");
			$this->EE->db->query("DELETE FROM exp_forum_polls  WHERE author_id = '{$this->current_id}'");
			$this->EE->db->query("DELETE FROM exp_forum_pollvotes  WHERE member_id = '{$this->current_id}'");
			$this->EE->db->query("DELETE FROM exp_forum_moderators  WHERE mod_member_id = '{$this->current_id}'");
			$this->EE->db->query("DELETE FROM exp_forum_administrators  WHERE admin_member_id = '{$this->current_id}'");
			$this->EE->db->query("DELETE FROM exp_comments WHERE author_id = '{$this->current_id}'");
			
			$message_query = $this->EE->db->query("SELECT DISTINCT recipient_id FROM exp_message_copies WHERE sender_id = '$this->current_id' AND message_read = 'n'");
			$this->EE->db->query("DELETE FROM exp_message_copies WHERE sender_id = '$this->current_id'");
			$this->EE->db->query("DELETE FROM exp_message_data WHERE sender_id = '$this->current_id'");
			$this->EE->db->query("DELETE FROM exp_message_folders WHERE member_id = '$this->current_id'");
			$this->EE->db->query("DELETE FROM exp_message_listed WHERE member_id = '$this->current_id'");
			
			if ($message_query->num_rows() > 0)
			{
				foreach($message_query->result_array() as $row)
				{
					$count_query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_message_copies WHERE recipient_id = '".$row['recipient_id']."' AND message_read = 'n'");
					$this->EE->db->query($this->EE->db->update_string('exp_members', array('private_messages' => $count_query->row('count') ), "member_id = '".$row['recipient_id']."'"));
				}
			}
			
			// Kill any attachments
			$query = $this->EE->db->query("SELECT attachment_id, filehash, extension, board_id FROM exp_forum_attachments WHERE member_id = '{$this->current_id}'");
			
			if ($query->num_rows() > 0)
			{
				// Grab the upload path
				$res = $this->EE->db->query('SELECT board_id, board_upload_path FROM exp_forum_boards');
			
				$paths = array();
				foreach ($res->result_array() as $row)
				{
					$paths[$row['board_id']] = $row['board_upload_path'];
				}
			
				foreach ($query->result_array() as $row)
				{
					if ( ! isset($paths[$row['board_id']]))
					{
						continue;
					}
					
					$file  = $paths[$row['board_id']].$row['filehash'].$row['extension'];
					$thumb = $paths[$row['board_id']].$row['filehash'].'_t'.$row['extension'];
				
					@unlink($file);
					@unlink($thumb);					
			
					$this->EE->db->query("DELETE FROM exp_forum_attachments WHERE attachment_id = '{$row['attachment_id']}'");
				}				
			}
						
			// Update the Channel Stats because comments deleted
			if ($this->EE->db->affected_rows() > 0)
			{		
				$query = $this->EE->db->query("SELECT channel_id FROM exp_channels");
			
				foreach ($query->result_array() as $row)
				{
					$this->EE->stats->update_channel_stats($row['channel_id']);
					$this->EE->stats->update_comment_stats($row['channel_id']);
				}
			}
			
			// Update the forum stats - order is very important.  Topics must be updated first			
			if (count($topics) > 0)
			{
				foreach ($topics as $topic_id)
				{	
					$this->_update_topic_stats($topic_id);
				}
			}
			
			$query = $this->EE->db->query("SELECT forum_id FROM exp_forums WHERE board_id = '".$this->_fetch_pref('board_id')."' AND forum_is_cat = 'n'");
			
			foreach ($query->result_array() as $row)
			{
				$this->_update_post_stats($row['forum_id']);
			}
			
			$this->_update_global_stats();
			
			$this->EE->stats->update_member_stats();
			
			$ban_msg = $this->EE->lang->line('user_account_deleted');
		}
		

		/** -------------------------------------
		/**  Finalize the template
		/** -------------------------------------*/
				
		$template = $this->_var_swap($this->_load_element('user_banning_report'),
								array(
										'name'	=> $this->_convert_special_chars($screen_name),
										'lang:member_banned' => $ban_msg
									)
								);
								
								

		if ($banned_user_ips == '')
		{
			$template = $this->_deny_if('banned_ips', $template);
		}
		else
		{
			$template = $this->_allow_if('banned_ips', $template);
		}
				
		return $this->_var_swap($this->_load_element('user_banning_page'),
								array(
										'include:user_banning_element' => $template,
										'banned_ips' => $banned_user_ips,
										'lang:member_banning' => ($_POST['action'] == 'reinstate') ? $this->EE->lang->line('member_reinstating') : $this->EE->lang->line('member_banning')
									)
								);
	}


	
	/** -------------------------------------
	/**  Ignore Member Confirmation
	/** -------------------------------------*/
	
	function ignore_member()
	{
		if ($this->current_id == $this->EE->session->userdata('member_id'))
		{
			return $this->_trigger_error('can_not_ignore_yourself');
		}
		
		/** -------------------------------------
		/**  Fetch member info
		/** -------------------------------------*/
		
		$query = $this->EE->db->query("SELECT screen_name FROM exp_members WHERE member_id = '{$this->current_id}'");
		
		if ($query->num_rows() == 0)
		{
			return $this->_trigger_error();
		}
		
		/** -------------------------------------
		/**  Output the template
		/** -------------------------------------*/
		
		$form = $this->EE->functions->form_declaration(array(
												'action' => $this->_forum_path('do_ignore_member/'.$this->current_id.'/'),
												'hidden_fields' => array('board_id' => $this->_fetch_pref('original_board_id'))
											)
										);  

		$template = $this->_var_swap($this->_load_element('ignore_member_confirmation'),
								array(
										'name'	=> $this->_convert_special_chars($query->row('screen_name') ),
										'form_declaration' => $form
									)
								);
								
		/** -------------------------------------
		/**  Already ignoring this member?
		/** -------------------------------------*/
		
		if (in_array($this->current_id, $this->EE->session->userdata['ignore_list']))
		{
			$template = $this->_allow_if('member_is_ignored', $template);
			$template = $this->_deny_if('member_not_ignored', $template);
		}
		else
		{
			$template = $this->_deny_if('member_is_ignored', $template);
			$template = $this->_allow_if('member_not_ignored', $template);
		}
		
				
		return $this->_var_swap($this->_load_element('ignore_member_page'),
								array(
										'include:member_ignore_element'	=> $template
									)
								);
	}

	
	
	/** -------------------------------------
	/**  Do Ignore Member
	/** -------------------------------------*/
	
	function do_ignore_member()
	{
		if ($this->current_id == $this->EE->session->userdata('member_id'))
		{
			return $this->_trigger_error('can_not_ignore_yourself');
		}

		if ( ! ($action = $this->EE->input->post('action')) OR ($action != 'ignore' AND $action != 'unignore'))
		{
			return $this->_trigger_error();
		}
		
		/** -------------------------------------
		/**  Fetch member info
		/** -------------------------------------*/
		
		$query = $this->EE->db->query("SELECT screen_name FROM exp_members WHERE member_id = '{$this->current_id}'");
		
		if ($query->num_rows() == 0)
		{
			return $this->_trigger_error();
		}
		
		$ignored = $this->EE->session->userdata['ignore_list'];
		$in_list = in_array($this->current_id, $ignored);
		
		if (($action == 'ignore' AND $in_list) OR ($action == 'unignore' AND ! $in_list))
		{
			return $this->_trigger_error();
		}
		
		if ($action == 'ignore')
		{
			$ignored[] = $this->current_id;
		}
		else
		{
			$ignored = array_diff($ignored, array($this->current_id));
		}
		
		$this->EE->db->query($this->EE->db->update_string('exp_members', array('ignore_list' => implode('|', $ignored)), "member_id = '".$this->EE->session->userdata['member_id']."'"));
		
		if (isset($this->EE->session->tracker[2]))
		{
			$return = str_replace('/'.$this->trigger, '', $this->EE->session->tracker[2]);
			$this->EE->functions->redirect($this->_forum_path($return));
		}

		$this->EE->functions->redirect($this->_forum_path());
	}

	
	
	/** -------------------------------------
	/**  Parse Visitor Stats
	/** -------------------------------------*/
	function visitor_stats()
	{
		$statdata = $this->EE->stats->statdata();
		
		if (empty($statdata))
		{
			return;
		}
		
		$str = $this->_load_element('visitor_stats');
			
		/** -------------------------------------
		/**  Parse Date-based stats
		/** -------------------------------------*/
			
		foreach (array('last_entry_date', 'last_forum_post_date', 'last_comment_date', 'last_visitor_date','most_visitor_date') as $stat)
		{
			if (preg_match_all("/{".$stat."\s+format=['|\"](.+?)['|\"]\}/i", $str, $matches))
			{	
				for ($j = 0; $j < count($matches['0']); $j++)
				{
					$str = str_replace($matches['0'][$j], $this->EE->localize->decode_date($matches['1'][$j], $this->EE->stats->statdata($stat)), $str);
				}
			}
		}
		
		/** -------------------------------------
		/**  Parse Non-date-based stats
		/** -------------------------------------*/
		foreach (array('total_members', 'total_logged_in', 'total_guests', 'total_anon', 'total_entries', 'total_forum_topics', 'total_forum_posts', 'total_forum_replies', 'total_comments', 'most_visitors', 'recent_member') as $stat )
		{
			$str = str_replace('{'.$stat.'}', $this->EE->stats->statdata($stat), $str);
		}
		
		/** -------------------------------------
		/**  Recent Member Registration List
		/** -------------------------------------*/
		if (preg_match("/\{recent_member_names(.*?)\}(.*?){\/recent_member_names\}/s", $str, $match))
		{			
			$limit	= (preg_match("/.*limit=[\"|'](.+?)[\"|']/", $match['1'], $match2)) ? $match2['1'] : 10;
			$back	= (preg_match("/.*backspace=[\"|'](.+?)[\"|']/", $match['1'], $match2)) ? $match2['1'] : '';
			
			$this->EE->db->select('screen_name, member_id');
			$this->EE->db->where('group_id != 2');
			$this->EE->db->where('group_id != 4');
			$this->EE->db->order_by('member_id', 'DESC');
			$this->EE->db->limit($limit);
			$query = $this->EE->db->get('members');
			
			$names = '';
			
			foreach ($query->result_array() as $row)
			{
				$temp = $match['2'];
				$temp = str_replace('{path:member_profile}', $this->_profile_path($row['member_id']), $temp);
				$temp = str_replace('{name}', $this->_convert_special_chars($row['screen_name']), $temp);
				$names .= $temp;
			}
			
			if ($back != '')
			{
				$names = substr($names, 0, - $back);
			}
			
			$str = str_replace($match['0'], $names, $str);
		}			

		/** -------------------------------------
		/**  Generate the "whos online" list
		/** -------------------------------------*/
		if ( ! preg_match("/\{if\s+member_names.*?\}(.*?){\/if\}/s", $str, $match1)
			OR ! preg_match("/\{member_names.*?\}(.*?){\/member_names\}/s", $str, $match2))
		{
			return $str;
		}			

		$names = '';
		$chunk = $match2['1']; 	
 		$str = str_replace($match1['0'], $match1['1'], $str);
 		
		$this->_load_moderators();

 		$moderators = array();
 		
 		if (is_array($this->moderators) AND count($this->moderators) > 0)
 		{
			foreach($this->moderators as $val)
			{
				foreach($val as $v)
				{
					$moderators[] = $v['mod_member_id'];
				}
			}
 		}
 		
		if (count($this->EE->stats->statdata('current_names')) == 0) 		
		{
			return preg_replace("/\{member_names.*?\}.*?\{\/member_names\}/s", '', $str);
		}
				
		foreach ($this->EE->stats->statdata('current_names') as $k => $v)
		{
			$temp = $chunk;
			
			// Highlight the Moderator
			
			if (in_array($k, $moderators))
			{
				$v['0'] = "<span class='activeModerator'>".$v['0']."</span>";
			}
		
			if ($v['1'] == 'y')
			{
				if ($this->EE->session->userdata('group_id') == 1)
				{
					$temp = preg_replace("/\{name\}/", $v['0'].'*', $temp);
				}
				elseif ($this->EE->session->userdata('member_id') == $k)
				{
					$temp = preg_replace("/\{name\}/", $v['0'].'*', $temp);
				}
				else
				{
					continue;
				}
			}
			else
			{
				$temp = preg_replace("/\{name\}/", $v['0'], $temp);
			}
						
			$names .= preg_replace("/\{path:member_profile}/", $this->_profile_path($k), $temp);
		}
				
		if (preg_match("/\{member_names.+?backspace=[\"|'](.+?)[\"|']/", $str, $backspace))
		{
			$names = substr($names, 0, - $backspace['1']);
		}
					
		$str = preg_replace("/\{member_names.*?\}.*?\{\/member_names\}/s", $names, $str);
		
		$str = str_replace('{name}', '', $str);
		
		return $str;
	}

	/** -------------------------------------
	/**  Individual Member's Last Visit
	/** -------------------------------------*/
	function member_post_total()
	{
		return str_replace('%x', $this->EE->session->userdata('total_forum_posts'), $this->EE->lang->line('your_post_total'));
	}

	
	/** -------------------------------------
	/**  Simple Search Form
	/** -------------------------------------*/	
	function login_form_mini()
	{
		$this->form_actions['member:member_login']['anon'] = 1;
		return $this->_load_element('login_form_mini');
	}
	
	
	/** -------------------------------------
	/**  Advanced Search Form
	/** -------------------------------------*/
	
	function advanced_search_form()
	{
		$this->EE->lang->loadfile('search');
		
		/** -------------------------------------
		/**  Can the user search?
		/** -------------------------------------*/
		
		// Before doing anything we need to load the permissions for all
		// forums and see which ones the user can search in.
		
		$forums = $this->_fetch_allowed_search_ids();
		
		if ($forums === FALSE)
		{
			return $this->_trigger_error('search_not_available');		
		}
		
		/** --------------------------------
		/**  No permission?  See ya...
		/** --------------------------------*/
		
		if (count($forums) == 0)
		{
			return $this->_trigger_error('not_allowed_to_search');		
		}
		
		/** --------------------------------
		/**  Build out the <option> list
		/** --------------------------------*/
		
		$options = "<option value='all' selected='selected'>".$this->EE->lang->line('search_all_forums')."</option>\n";
		
		foreach ($forums as $id => $val)
		{
			$pre = ($val['forum_is_cat'] == 'y') ? '' : '&nbsp;- ';
			$options .= "<option value='".$id."'>".$pre.$val['forum_name']."</option>\n";
		}
		
		/** -------------------------------------
		/**  Build the Member Group list
		/** -------------------------------------*/
		
		$groups = "<option value='all' selected='selected'>".$this->EE->lang->line('search_all_groups')."</option>\n";
		
		$this->EE->db->select('group_id, group_title');
		$this->EE->db->where_not_in('group_id', array('2', '3', '4'));
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$this->EE->db->where('include_in_memberlist', 'y');
		$query = $this->EE->db->get('member_groups');
		
		foreach ($query->result_array() as $row)
		{
			$groups .= "<option value='{$row['group_id']}'>{$row['group_title']}</option>\n";
		}
		
		/** ----------------------------------------
		/**  Create form
		/** ----------------------------------------*/
		$form = $this->EE->functions->form_declaration(array(
												'action' => $this->_forum_path('do_search'),
												'hidden_fields' => array('board_id' => $this->_fetch_pref('original_board_id'))
											)
										);  


		/** --------------------------------
		/**  Parse the template
		/** --------------------------------*/
		return $this->_var_swap($this->_load_element('advanced_search_form'),
							array(
									'forum_select_options'			=> $options,
									'member_group_select_options'	=> $groups,
									'form_declaration'				=> $form
								)
							);
	}
		
	/** --------------------------------------
	/**  Fetch the forums that can be searched
	/** --------------------------------------*/
	// There are four sets of preferences which determine if a user can search:
	
	// can_view_forum
	// can_view_hidden
	// can_view_topics
	// can_search
		
	function _fetch_allowed_search_ids()
	{
		$this->EE->db->select('forum_id, forum_name, forum_status, forum_is_cat, 
								forum_parent, forum_permissions, forum_enable_rss');
		$this->EE->db->where('board_id', $this->_fetch_pref('board_id'));
		$this->EE->db->order_by('forum_order');
		$query = $this->EE->db->get('forums');
				
		if ($query->num_rows() == 0)
		{
			return FALSE;
		}
		
		/** --------------------------------
		/**  Check the permissions
		/** --------------------------------*/
		$forums = array();	
	
		foreach ($query->result_array() as $row)
		{	
			$row['forum_permissions'] = unserialize(stripslashes($row['forum_permissions']));

			if ( ! $this->_permission('can_search', $row['forum_permissions']))
			{
				continue;
			}

			if ($row['forum_status'] == 'c' AND ! $this->_permission('can_view_hidden', $row['forum_permissions']))
			{
				continue;
			}

			if ( ! $this->_permission('can_view_forum', $row['forum_permissions']) OR  ! $this->_permission('can_view_topics', $row['forum_permissions']))
			{
				continue;
			}
			
			if ($row['forum_enable_rss'] == 'y')
			{
				$this->feeds_enabled = TRUE;
			}
		
			$forums[$row['forum_id']] = array('forum_name' => $row['forum_name'], 'forum_is_cat' => $row['forum_is_cat'], 'forum_parent' => $row['forum_parent']);
		}	
		
		return $forums;
	}



	function _swap_date($replace)
	{
		if ($this->date_limit == '')
		{
			return '';
		}
	
		return str_replace('{dd}', $replace, $this->date_limit);
	}

	

	/** ----------------------------------------
	/**  Cache the search result
	/** ----------------------------------------*/
	function _cache_search_result($topic_ids, $post_ids, $keywords, $sort_order)
	{
		$hash = $this->EE->functions->random('md5');
				
		$data = array(
						'search_id'		=> $hash,
						'search_date'	=> time(),
						'member_id'		=> $this->EE->session->userdata('member_id'),
						'keywords'		=> $keywords,
						'sort_order'	=> $sort_order,
						'ip_address'	=> $this->EE->input->ip_address(),
						'topic_ids'		=> addslashes(serialize($topic_ids)),
						'post_ids'		=> addslashes(serialize($post_ids)),
						'board_id'		=> $this->_fetch_pref('board_id')
						);
		
		$this->EE->db->query($this->EE->db->insert_string('exp_forum_search', $data));
		
		return $hash;
	}

	/** -------------------------------------
	/**  Perform Member Search
	/** -------------------------------------*/
	
	function member_search()
	{
		return $this->do_search($this->current_id, FALSE);
	}
	
	/** -------------------------------------
	/**  Perform New Topic Search
	/** -------------------------------------*/
	function new_topic_search()
	{
		return $this->do_search('', TRUE);
	}

	/** -------------------------------------
	/**  Perform Pending Topic Search
	/** -------------------------------------*/
	// This fetches topics that have no threads in them yet
	
	function view_pending_topics()
	{
		return $this->do_search('', FALSE, TRUE);
	}


	/** -------------------------------------
	/**  Perform Active Topic Search
	/** -------------------------------------*/
	
	// Fetches topics that are active today

	function active_topic_search()
	{
		return $this->do_search('', TRUE, FALSE, TRUE);
	}	


	/** -------------------------------------
	/**  Perform Search
	/** -------------------------------------*/
	
	function do_search($member_id = '', $new_topic_search = FALSE, $view_pending_topics = FALSE, $active_topic_search = FALSE)
	{
		/** ----------------------------------------
		/**  Fetch language file
		/** ----------------------------------------*/
		$this->EE->lang->loadfile('search');
		
		/** ----------------------------------------
		/**  Flood control
		/** ----------------------------------------*/
		
		if ($this->EE->session->userdata['search_flood_control'] > 0 AND $this->EE->session->userdata['group_id'] != 1)
		{
			$cutoff = time() - $this->EE->session->userdata['search_flood_control'];
			
			$sql = "SELECT search_id FROM exp_forum_search WHERE search_date > '{$cutoff}' AND ";
			
			if ($this->EE->session->userdata['member_id'] != 0)
			{
				$sql .= "(member_id='".$this->EE->db->escape_str($this->EE->session->userdata('member_id'))."' OR ip_address='".$this->EE->db->escape_str($this->EE->input->ip_address())."')";
			}
			else
			{
				$sql .= "ip_address='".$this->EE->db->escape_str($this->EE->input->ip_address())."'";
			}
			
			$query = $this->EE->db->query($sql);
									
			if ($query->num_rows() > 0)
			{
				return $this->EE->output->show_user_error('general', str_replace("%x", $this->EE->session->userdata['search_flood_control'], $this->EE->lang->line('search_time_not_expired')));
			}
		}
		
		/** ----------------------------------------
		/**  Secure forms?
		/** ----------------------------------------*/
	  	
	  	// If the hash is not found we'll simply reload the page.
	  
		if ($this->EE->config->item('secure_forms') == 'y' AND $member_id == '' AND $new_topic_search == FALSE AND $view_pending_topics == FALSE)
		{
			if ( ! isset($_POST['XID']))
			{
				$this->EE->functions->redirect($this->_forum_path('search'));
				exit;
			}
		
			$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_security_hashes WHERE hash='".$this->EE->db->escape_str($_POST['XID'])."' AND ip_address = '".$this->EE->input->ip_address()."' AND date > UNIX_TIMESTAMP()-7200");
		
			if ($query->row('count')  == 0)
			{
				$this->EE->functions->redirect($this->_forum_path('search'));
				exit;
			}
		}

		$terms = array();


		/** --------------------------------
		/**  Fetch allowed forums
		/** --------------------------------*/
		
		// Before doing anything else we'll fetch the forum IDs 
		// that the user is allowed to search in.
		
		$forums = $this->_fetch_allowed_search_ids();
		
		if ($forums === FALSE OR count($forums) == 0)
		{
			return $this->_trigger_error('not_allowed_to_search');		
		}
		

		/** --------------------------------
		/**  Which forums are we searching in?
		/** --------------------------------*/
		
		// The advanced search form passes the forum_id numbers in
		// an array, while the simple search form passes as a simple
		// numeric value.
		
		if (isset($_POST['forum_id']))
		{
			if ( ! is_array($_POST['forum_id']) AND is_numeric($_POST['forum_id']))
			{
				$temp = $_POST['forum_id'];
				unset($_POST['forum_id']);
				$_POST['forum_id'][] = $temp;
			}

			if (is_array($_POST['forum_id']) AND count($_POST['forum_id']) > 0)
			{
				$search_all = FALSE;
				
				foreach ($_POST['forum_id'] as $key => $val)
				{
					if ($val == 'all')
					{
						$search_all = TRUE;
						break;
					}
				}
				
				foreach ($forums as $id => $val)
				{	
					if ($search_all == FALSE)
					{
						if ( ! in_array($id, $_POST['forum_id']))
						{
							if ($val['forum_is_cat'] == 'y')
							{						
								unset($forums[$id]);
							}
							elseif ( ! in_array($val['forum_parent'] , $_POST['forum_id']))
							{
								unset($forums[$id]);
							}
						}
					}
					
					if ($val['forum_is_cat'] == 'y')
					{						
						unset($forums[$id]);
					}
				}
			}
 		}
 		
		/** ----------------------------------------
		/**  Did the user submit any keywords?
		/** ----------------------------------------*/
		
		// We only require a keyword if the member name field is blank
		// or if we are not searching by most recent topic
		
		if ($member_id == '' AND $new_topic_search == FALSE AND $view_pending_topics == FALSE)
		{
			if ( ! isset($_POST['member_name']) OR $_POST['member_name'] == '')
			{		
				if ( ! isset($_POST['keywords']) OR $_POST['keywords'] == "")
				{  
					$data = array(	'title' 	=> $this->EE->lang->line('error'),
									'heading'	=> $this->EE->lang->line('error'),
									'content'	=> $this->EE->lang->line('search_no_keywords'),
									'link'		=> array($this->_forum_path('search'), $this->EE->lang->line('advanced_search'))
								 );
						
					return $this->EE->output->show_message($data);	
				}
			}
		}
		
		/** ----------------------------------------
		/**  Strip extraneous junk from keywords
		/** ----------------------------------------*/
		
		if ( ! isset($_POST['keywords']))
			$_POST['keywords'] = '';

		if ($_POST['keywords'] != "")		
		{
			// Load the search helper so we can filter the keywords
			$this->EE->load->helper('search');

			$this->keywords = sanitize_search_terms($_POST['keywords']);
			
			/** ----------------------------------------
			/**  Is the search term long enough?
			/** ----------------------------------------*/
	
			if (strlen($this->keywords) < $this->min_length)
			{
				$data = array(	'title' 	=> $this->EE->lang->line('error'),
								'heading'	=> $this->EE->lang->line('error'),
								'content'	=> str_replace("%x", $this->min_length, $this->EE->lang->line('search_min_length')),
								'link'		=> array($this->_forum_path('search'), $this->EE->lang->line('advanced_search'))
							 );
					
				return $this->EE->output->show_message($data);	
			}
	
			// Load the text helper
			$this->EE->load->helper('text');
	
			$this->keywords = ($this->EE->config->item('auto_convert_high_ascii') == 'y') ? ascii_to_entities($this->keywords) : $this->keywords;
			
			/** ----------------------------------------
			/**  Remove "ignored" words
			/** ----------------------------------------*/
			
			if (isset($_POST['search_criteria']) && $_POST['search_criteria'] != 'exact' && @include_once(APPPATH.'config/stopwords'.EXT))
			{
				foreach ($ignore as $badword)
				{		
					$this->keywords = preg_replace("/\b".preg_quote($badword)."\b/i","", $this->keywords);
				}
								
				if ($this->keywords == '')
				{
					return $this->EE->output->show_user_error('general', array($this->EE->lang->line('search_no_stopwords')));
				}
			}	
			
			/** ----------------------------------------
			/**  Log Search Terms
			/** ----------------------------------------*/
			
			$this->EE->functions->log_search_terms($this->keywords, 'forum');
		}
		
		/** -------------------------------------
		/**  Searching by Member Group?
		/** -------------------------------------*/
		
		$sql_topic_join = '';
		$sql_post_join = '';
		$groups = '';
		
		if (isset($_POST['member_group']) AND is_array($_POST['member_group']))
		{
			if ( ! empty($_POST['member_group']) AND ! in_array('all', $_POST['member_group']))
			{
				foreach($_POST['member_group'] AS $key => $value)
				{
					$_POST['member_group'][$key] = $this->EE->db->escape_str($value);
				}
			
				$sql_topic_join = "\nLEFT JOIN exp_members ON exp_forum_topics.author_id = exp_members.member_id \n";
				$sql_post_join	= "\nLEFT JOIN exp_members ON p.author_id = exp_members.member_id \n";
				$groups = "'".implode("','", $_POST['member_group'])."'";
			}			
		}
		
		/** ---------------------------------------
		/**  Are we searching by name?
		/** ---------------------------------------*/
		
		// If so, we'll fetch the member_id number
		
		$author_id		= 0;
		$screen_name	= '';
		
		if (isset($_POST['member_name']) AND $_POST['member_name'] != "")
		{
			$screen_name = $_POST['member_name'];
		
			$sql = "SELECT member_id FROM exp_members WHERE ";
		
			if (isset($_POST['exact_match']) AND $_POST['exact_match'] == 'y')
			{
				$sql .= " screen_name = '".$this->EE->db->escape_str($this->EE->security->xss_clean($_POST['member_name']))."' ";
			}
			else
			{
				$sql .= " screen_name LIKE '%".$this->EE->db->escape_like_str($this->EE->security->xss_clean($_POST['member_name']))."%' ";
			}
			
			if ($groups != '')
			{
				$sql .= "AND exp_members.group_id IN ({$groups}) ";
			}
			
			$query = $this->EE->db->query($sql);
			
			if ($query->num_rows() == 0)
			{
				$data = array(	'title' 	=> $this->EE->lang->line('error'),
								'heading'	=> $this->EE->lang->line('error'),
								'content'	=> $this->EE->lang->line('no_name_result'),
								'link'		=> array($this->_forum_path('search'), $this->EE->lang->line('advanced_search'))
							 );
					
				return $this->EE->output->show_message($data);	
			}
			
			if ($query->num_rows() > 1)
			{
				$data = array(	'title' 	=> $this->EE->lang->line('error'),
								'heading'	=> $this->EE->lang->line('error'),
								'content'	=> $this->EE->lang->line('too_many_name_results'),
								'link'		=> array($this->_forum_path('search'), $this->EE->lang->line('advanced_search'))
							 );
					
				return $this->EE->output->show_message($data);	
			}
			
			$author_id = $query->row('member_id') ;
		}
		
		if ($member_id != '')
		{
			$author_id = $member_id;
			$res = $this->EE->db->query("SELECT screen_name FROM exp_members WHERE member_id = '{$author_id}'");
			$screen_name = $res->row('screen_name') ;
		}
		
		/** ---------------------------------------
		/**  Set the default preferences
		/** ---------------------------------------*/
	
		$search_in		= ( ! isset($_POST['search_in']) OR ! in_array($_POST['search_in'], array('titles', 'posts', 'all'))) ? 'all' : $_POST['search_in'];
		$criteria 		= ( ! isset($_POST['search_criteria']) OR ! in_array($_POST['search_criteria'], array('any', 'all', 'exact'))) ? 'all' : $_POST['search_criteria'];	
		$date			= ( ! isset($_POST['date']) OR ! is_numeric($_POST['date'])) ? '0' : $_POST['date'];
		$order_by 		= ( ! isset($_POST['order_by']) OR ! in_array($_POST['order_by'], array('date', 'title', 'most_posts', 'recent_post'))) ? 'date' : $_POST['order_by'];	
		$date_order 	= ( ! isset($_POST['date_order']) OR ! in_array($_POST['date_order'], array('newer', 'older'))) ? 'newer' : $_POST['date_order'];	
		$sort_order 	= ( ! isset($_POST['sort_order']) OR ! in_array($_POST['sort_order'], array('asc', 'desc'))) ? 'desc' : $_POST['sort_order'];	
		$keywords		= $this->keywords;
		$keywords_like	= $this->EE->db->escape_like_str(trim($keywords));
		$keywords		= $this->EE->db->escape_str(trim($keywords));
		
		/** ---------------------------------------
		/**  Do we have multiple search terms?
		/** ---------------------------------------*/
		
		// If so, break them up into discreet words so we can
		// do "any" and "all" searches
		// LIKE query can easily hog resources so eliminate dupes, and limit the number
		// of discrete words to 32 - even Google does this for sanity
		
		$terms_like = array();
		
		if ($keywords_like != '')
		{
			if (preg_match("/\s+/", $keywords_like, $matches))
			{
				$terms_like = preg_split("/\s+/", $keywords_like, -1, PREG_SPLIT_NO_EMPTY);
				$terms_like = array_unique($terms_like);
				$terms_like = array_slice($terms_like, 0, 31);
			}
		}

		/** ---------------------------------------
		/**  Compile the date/order criteria
		/** ---------------------------------------*/
		
		$order = " ORDER BY ";
		
		if ($member_id != '')
		{
			$order_by = 'recent_post';
		}
		
		switch ($order_by)
		{
			case 'date'			: $order .= "topic_date ";
				break;
			case 'title'		: $order .= "title ";
				break;
			case 'most_posts'	: $order .= "thread_total ";
				break;
			case 'recent_post'	: $order .= "last_post_date ";
				break;
		}
		
		$order .= $sort_order." ";
		
		$this->date_limit = '';

		if ($date > 0 AND $member_id == '')
		{
			$cutoff = $this->EE->localize->now - (60*60*24*$date);
			
			if ($date_order == 'older')
			{
				$this->date_limit .= " {dd} < ".$cutoff." ";
			}
			else
			{
				$this->date_limit .= " {dd} > ".$cutoff." ";
			}
		}
		
		/** ---------------------------------------
		/**  Build the topic search query
		/** ---------------------------------------*/
		
		// Since topics and posts are stored in their
		// own tables we need to build two queries.
		// The first one queries the topic table and the
		// second one does the post table.
		// Each returns a list of topic_id numbers, which
		// we'll compile into one array later.
		
		
		// TOPIC QUERY
		// ------------------------------------------------------
		// ------------------------------------------------------
			
		$sql = "SELECT topic_id
				FROM exp_forum_topics {$sql_topic_join}
				WHERE board_id = '".$this->_fetch_pref('board_id')."'
				AND ";
		
		/** ----------------------------------------
		/**  Limit the search to specific forums
		/** ----------------------------------------*/
		
		$sql .= ' (';

		foreach ($forums as $id => $val)
		{
			$sql .= " forum_id = '{$id}' OR";
		}
		
		$sql  = substr($sql, 0, -2);
		$sql .= ') ';
		
		/** ----------------------------------------
		/**  Filter by date
		/** ----------------------------------------*/
		
		if ($this->_swap_date('topic_date') != '' AND $new_topic_search == FALSE AND $view_pending_topics == FALSE)
		{
			$sql .= "AND ".$this->_swap_date('topic_date')." ";
		}
		
		/** ----------------------------------------
		/**  Limit to topics with no replies
		/** ----------------------------------------*/
	
		if ($view_pending_topics == TRUE AND $active_topic_search == FALSE)
		{
			$one_month_ago = time() - (60*60*24*30);
			$sql .= "AND thread_total = 1 AND topic_date > ".$one_month_ago;
		}		
				
		/** ----------------------------------------
		/**  Filter New Topic Date
		/** ----------------------------------------*/
		
		$ignore_ids = array();
		
		if ($new_topic_search == TRUE)
		{
			$sql .= "AND topic_date > ".$this->EE->session->userdata('last_visit')." ";
						
			// Do we need to igore any recently visited topics?
			
			if ($this->EE->session->userdata('last_visit') > 0)
			{
				if ($active_topic_search == TRUE) 
				{
					$ct = date('H', $this->EE->localize->now);
					
					if ($ct < 12)
					{
						$cutoff = $this->EE->localize->now - (12 * 3600);
					}
					else
					{
						$cutoff = $this->EE->localize->now - ($ct * 3600);
					}
				}
				else
				{
					$cutoff = $this->EE->session->userdata('last_visit');
				}
				
				$tquery = $this->EE->db->query("SELECT topic_id, last_post_date FROM exp_forum_topics WHERE last_post_date > '".$cutoff."' AND board_id = '".$this->_fetch_pref('board_id')."'");
						
				if ($tquery->num_rows() > 0)
				{
					$read_topics = $this->_fetch_read_topics();
				
					foreach ($tquery->result_array() as $trow)
					{
						if ( isset($read_topics[$trow['topic_id']]) && $read_topics[$trow['topic_id']] > $trow['last_post_date'])
						{
							$ignore_ids[] = $trow['topic_id'];
						}
					}
				}

				if (count($ignore_ids) > 0)
				{
					foreach ($ignore_ids as $ignore)
					{
						$sql .= " AND topic_id != '".$ignore."' ";
					}
				}
			}
		}

		/** ----------------------------------------
		/**  Filter by author
		/** ----------------------------------------*/
		if ($author_id > 0)
		{
			$sql .= "AND author_id = '{$author_id}' ";
		}
		
		/** -------------------------------------
		/**  Filter by Member Group
		/** -------------------------------------*/
		
		if ($sql_topic_join != '')
		{
			$sql .= "AND exp_members.group_id IN ({$groups}) ";
		}
		
		if ($keywords != '')
		{
			/** ----------------------------------------
			/**  Exact Match Search
			/** ----------------------------------------*/
			
			if ($criteria == 'exact')
			{
				// If the keywords contain a space
				// we'll do an exact phrase match,
				// otherwise we'll do an exact word match
			
				if (preg_match("/\s+/", $keywords))
				{
					if ($search_in == 'titles')
					{
						$sql .= "AND (title = '{$keywords}' OR title LIKE '{$keywords_like}%' OR title LIKE '%{$keywords_like}%') ";
					}		
					elseif ($search_in == 'posts')
					{
						$sql .= "AND (body  = '{$keywords}' OR body  LIKE '{$keywords_like}%' OR body  LIKE '%{$keywords_like}%') ";
					}
					else
					{
						$sql .= "AND (	(title = '{$keywords}' OR title LIKE '{$keywords_like}%' OR title LIKE '%{$keywords_like}%') OR
										(body  = '{$keywords}' OR body  LIKE '{$keywords_like}%' OR body  LIKE '%{$keywords_like}%') )";				
					}
				}
				else // Exact word match
				{
					if ($search_in == 'titles')
					{
						$sql .= "AND (title = '{$keywords}' OR title LIKE '{$keywords_like} %' OR title LIKE '% {$keywords_like} %') ";
					}		
					elseif ($search_in == 'posts')
					{
						$sql .= "AND (body  = '{$keywords}' OR body  LIKE '{$keywords_like} %' OR body  LIKE '% {$keywords_like} %') ";
					}
					else
					{
						$sql .= "AND (	(title = '{$keywords}' OR title LIKE '{$keywords_like} %' OR title LIKE '% {$keywords_like} %') OR
										(body  = '{$keywords}' OR body  LIKE '{$keywords_like} %' OR body  LIKE '% {$keywords_like} %') )";
					}
				}
			}
			else
			{
				/** ----------------------------------------
				/**  "Any" or "All" Search
				/** ----------------------------------------*/
				
				// If we don't have multiple keywords we'll
				// do a simple string search
			
				if (count($terms) == 0)
				{
					if ($search_in == 'titles')
					{
						$sql .= "AND title LIKE '%{$keywords_like}%' ";
					}		
					elseif ($search_in == 'posts')
					{
						$sql .= "AND body LIKE '%{$keywords_like}%' ";
					}
					else
					{
						$sql .= "AND (title LIKE '%{$keywords_like}%' OR body LIKE '%{$keywords_like}%') ";
					}
				}
				else
				{
					/** ----------------------------------------
					/**  Multiple Keyword Searches
					/** ----------------------------------------*/
				
					$sql .= "AND (";
				
					if ($criteria == 'all')
					{				
						$topic = '';
						$tbody = '';
					
						foreach ($terms_like as $term)
						{
							if ($search_in == 'titles')
							{
								$sql .= " title LIKE '%{$term}%' AND";						
							}		
							elseif ($search_in == 'posts')
							{
								$sql .= " body  LIKE '%{$term}%' AND";						
							}
							else
							{
								$topic .= " title LIKE '%{$term}%' AND";						
								$tbody .= " body  LIKE '%{$term}%' AND";						
							}
						}
						
						if ($topic != '')
						{
							$sql .= '('.substr($topic, 0, -3).') OR ';
							$sql .= '('.substr($tbody, 0, -3).') ';
						}
						else
						{
							$sql = substr($sql, 0, -3);
						}
					}
					elseif ($criteria == 'any')
					{
						$topic = '';
						$tbody = '';
					
						foreach ($terms_like as $term)
						{
							if ($search_in == 'titles')
							{
								$sql .= " title LIKE '%{$term}%' OR";
							}		
							elseif ($search_in == 'posts')
							{
								$sql .= " body  LIKE '%{$term}%' OR";						
							}
							else
							{
								$topic .= " title LIKE '%{$term}%' OR";						
								$tbody .= " body  LIKE '%{$term}%' OR";						
							}
						}
						
						if ($topic != '')
						{
							$sql .= '('.substr($topic, 0, -2).') OR ';
							$sql .= '('.substr($tbody, 0, -2).') ';
						}
						else
						{
							$sql = substr($sql, 0, -2);
						}
					}
					
					$sql .= ') ';
				}		
			}
			
		} // end if $keywords		
		$sql .= " LIMIT ".$this->search_limit;


			
		/** ----------------------------------------
		/**  Run the query and compile the topic IDs
		/** ----------------------------------------*/
							
		$query = $this->EE->db->query($sql);
		
		$topic_ids = array();

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$topic_ids[] = $row['topic_id'];
			}
		}

		/** ----------------------------------------
		/**  If this is a title search or a "no reply" search we're done
		/** ----------------------------------------*/
		if ($search_in == 'titles' OR $view_pending_topics == TRUE)
		{
			if (count($topic_ids) == 0)
			{
				return $this->EE->output->show_user_error('off', array($this->EE->lang->line('search_no_result')), $this->EE->lang->line('search_result_heading'));
			}
			
			$sql = "SELECT topic_id FROM exp_forum_topics WHERE topic_id IN (".implode(',', array_unique($topic_ids)).')'.$order;
			
			$query = $this->EE->db->query($sql);
			
			$topic_ids = array();
			foreach ($query->result_array() as $row)
			{
				$topic_ids[] = $row['topic_id'];
			}
			
			// Cache the result and redirect to the result page
			
			if ($view_pending_topics == TRUE)
			{
				$alt_word = $this->EE->lang->line('view_pending_topics');
			}
			else
			{
				$alt_word = $screen_name;
			}
				
			$words = ($keywords != '') ? $keywords : $alt_word;
			
			$search_id = $this->_cache_search_result($topic_ids, array(), $words, $order);
							
			$data = array(	'title' 	=> $this->EE->lang->line('search'),
							'heading'	=> $this->EE->lang->line('thank_you'),
							'content'	=> $this->EE->lang->line('search_redirect_msg'),
							'redirect'	=> $this->_forum_path('search_results/'.$search_id),
							'link'		=> array($this->_forum_path('search_results/'.$search_id), $this->_fetch_pref('forum_name'))
						 );
				
			return $this->EE->output->show_message($data);
		}


		// POST QUERY
		// ------------------------------------------------------
		// ------------------------------------------------------


		$sql = "SELECT p.topic_id, p.post_id
				FROM (exp_forum_posts p, exp_forum_topics t) {$sql_post_join}
				WHERE t.topic_id = p.topic_id ";

		/** ----------------------------------------
		/**  Limit the search to specific forums
		/** ----------------------------------------*/
		
		$sql .= 'AND (';

		foreach ($forums as $id => $val)
		{
			$sql .= " p.forum_id = '{$id}' OR";
		}
		
		$sql  = substr($sql, 0, -2);
		$sql .= ') ';
				
		/** ----------------------------------------
		/**  Ignore topics
		/** ----------------------------------------*/
		if (count($ignore_ids) > 0)
		{
			foreach ($ignore_ids as $ignore)
			{
				$sql .= " AND p.topic_id != '".$ignore."' ";
			}
		}
				
		/** ----------------------------------------
		/**  Filter by date
		/** ----------------------------------------*/
	
		if ($this->_swap_date('post_date') != '' AND $new_topic_search == FALSE)
		{
			$sql .= "AND ".$this->_swap_date('post_date');
		}
		
		/** ----------------------------------------
		/**  Filter New Topic Date
		/** ----------------------------------------*/
		
		if ($new_topic_search == TRUE)
		{
			$sql .= "AND p.post_date > ".$this->EE->session->userdata('last_visit')." ";
		}
		
		/** ----------------------------------------
		/**  Filter by author
		/** ----------------------------------------*/
		if ($author_id > 0)
		{
			$sql .= "AND p.author_id = '{$author_id}' ";
		}
		
		/** -------------------------------------
		/**  Filter by member group
		/** -------------------------------------*/
		
		if ($sql_post_join != '')
		{
			$sql .= "AND exp_members.group_id IN ({$groups}) ";
		}
		
		if ($keywords != '')
		{
			/** ----------------------------------------
			/**  Exact Match Search
			/** ----------------------------------------*/
			
			if ($criteria == 'exact')
			{
				if (preg_match("/\s+/", $keywords)) // Exact Phrase match
				{
					if ($search_in != 'titles')
					{
						$sql .= "AND (p.body = '{$keywords}' OR p.body  LIKE '{$keywords_like}%' OR p.body  LIKE '%{$keywords_like}%') ";
					}
				}
				else // Exact word match
				{
					if ($search_in != 'titles')
					{
						$sql .= "AND (p.body  = '{$keywords}' OR p.body  LIKE '{$keywords_like} %' OR p.body  LIKE '% {$keywords_like} %') ";
					}
				}
			}
			else
			{
				/** ----------------------------------------
				/**  "Any" or "All" Search
				/** ----------------------------------------*/
				
				// If we don't have multiple keywords we'll do a simple string search
			
				if (count($terms) == 0)
				{
					if ($search_in != 'titles')
					{
						$sql .= "AND (p.body LIKE '{$keywords_like} %' OR p.body  LIKE '%{$keywords_like}%') ";
					}
				}
				else
				{
					$sql .= "AND (";
				
					if ($criteria == 'all')
					{								
						foreach ($terms_like as $term)
						{
							if ($search_in != 'titles')
							{
								$sql .= " p.body  LIKE '%{$term}%' AND";						
							}
						}
						
						$sql = substr($sql, 0, -3);
					}
					elseif ($criteria == 'any')
					{				
						foreach ($terms_like as $term)
						{
							if ($search_in != 'titles')
							{
								$sql .= " p.body LIKE '%{$term}%' OR";
							}
						}
	
						$sql = substr($sql, 0, -2);
					}
					
					$sql .= ') ';
	
				}		
			}
		}
		
		if ($member_id != '')
		{
			$sql .= " ORDER BY p.post_date desc ";
		}
		
		$sql .= "LIMIT ".$this->search_limit;

		/** ----------------------------------------
		/**  Run the query and compile the topic IDs
		/** ----------------------------------------*/
							
		$query = $this->EE->db->query($sql);
		
		$topic_p_ids = array();
		$post_ids = array();
		
		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$topic_p_ids[] = $row['topic_id'];
				$post_ids[$row['topic_id']][] = $row['post_id'];
			}
		}
		
		$topic_ids = array_merge($topic_ids, $topic_p_ids);
		
		if (count($topic_ids) == 0)
		{
			return $this->EE->output->show_user_error('off', array($this->EE->lang->line('search_no_result')), $this->EE->lang->line('search_result_heading'));
		}
		

		$sql = "SELECT topic_id FROM exp_forum_topics WHERE topic_id IN (".implode(',', array_unique($topic_ids)).')'.$order;
		
		$query = $this->EE->db->query($sql);
		
		$topic_ids = array();
		foreach ($query->result_array() as $row)
		{
			$topic_ids[] = $row['topic_id'];
		}

		// Cache the result and redirect to the result page
		
		$alt_word = ($new_topic_search == FALSE) ? $screen_name : $this->EE->lang->line('new_topic_search');
		
		$words = ($keywords != '') ? $keywords : $alt_word;
		
		$search_id = $this->_cache_search_result($topic_ids, $post_ids, $words, $order);
						
		$data = array(	'title' 	=> $this->EE->lang->line('search'),
						'heading'	=> $this->EE->lang->line('thank_you'),
						'content'	=> $this->EE->lang->line('search_redirect_msg'),
						'redirect'	=> $this->_forum_path('search_results/'.$search_id),
						'link'		=> array($this->_forum_path('search_results/'.$search_id), $this->_fetch_pref('forum_name'))
					 );
			
		return $this->EE->output->show_message($data);	
	}






	/** -------------------------------------
	/**  Display Search Results
	/** -------------------------------------*/
	
	function search_results_page()
	{
		/** ----------------------------------------
		/**  Fetch language file
		/** ----------------------------------------*/
		$this->EE->lang->loadfile('search');

		/** ----------------------------------------
		/**  Check search ID number
		/** ----------------------------------------*/
		
		// If the search ID is less than 32 characters long we don't have a valid search ID number
		
		if (strlen($this->current_id) < 32)
		{
			return $this->EE->output->show_user_error('off', array($this->EE->lang->line('search_no_result')), $this->EE->lang->line('search_result_heading'));		
		}		
				
		/** ----------------------------------------
		/**  Clear old search results
		/** ----------------------------------------*/
		
		// We cache search results for 2 hours

		$expire = time() - (2 * 3600);
		
		$this->EE->db->query("DELETE FROM exp_forum_search WHERE search_date < '$expire'");

		/** ----------------------------------------
		/**  Fetch the cached search query
		/** ----------------------------------------*/
					
		$query = $this->EE->db->query("SELECT * FROM exp_forum_search WHERE search_id = '".$this->EE->db->escape_str($this->current_id)."' AND ip_address = '".$this->EE->input->ip_address()."' AND member_id = '".$this->EE->session->userdata('member_id')."' ");
		
		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('off', array($this->EE->lang->line('search_no_result')), $this->EE->lang->line('search_result_heading'));		
		}
		
		$topic_ids = unserialize(stripslashes($query->row('topic_ids') ));
		$post_ids  = unserialize(stripslashes($query->row('post_ids') ));

		// Load the XML Helper
		$this->EE->load->helper('xml');

		$keywords	= xml_convert($query->row('keywords') );
		$sort_order = $query->row('sort_order') ;

		/** -------------------------------------
		/**  Load the template
		/** -------------------------------------*/
		
		$str = $this->_load_element('search_results');
		
		/** -------------------------------------
		/**  Do we have pagination?
		/** -------------------------------------*/
		
		$pagination 	= '';
		$current_page	= 0;
		$total_pages	= 1;		
		$topic_limit 	= 20;
		$total_rows	 	= count($topic_ids);
		
		if ($total_rows > $topic_limit)
		{	
			$pagination = $this->_create_pagination(
										array(
												'first_url'		=> $this->_forum_path('/search_results/'.$this->current_id.'/'),
												'path'			=> $this->_forum_path('/search_results/'.$this->current_id.'/'),
												'total_count'	=> $total_rows,
												'per_page'		=> $topic_limit,
												'cur_page'		=> $this->current_page
											)
										);
			
			// Slice our array so we can limit the query properly
		
			$topic_ids = array_slice($topic_ids, $this->current_page, $topic_limit);
		
			// Set the stats for: {current_page} of {total_pages}
			
			$cur_page = ($this->current_page == 0) ? 1 : $this->current_page;			
			$current_page = floor(($cur_page / $topic_limit) + 1);
			$total_pages  = ceil($total_rows / $topic_limit);			
		}
		
		if ($pagination == '')
		{
			$str = $this->_deny_if('paginate', $str, '&nbsp;');
		}
		else
		{
			$str = $this->_allow_if('paginate', $str);
		}
		
		/** -------------------------------------
		/**  Fetch the topics
		/** -------------------------------------*/
														
		$query = $this->EE->db->query("SELECT t.forum_id, t.topic_id, t.author_id, t.moved_forum_id, t.ip_address, t.title, t.status, t.sticky, t.thread_views, t.topic_date, t.thread_total, t.last_post_author_id,  t.last_post_date,
									m.screen_name AS last_post_author,
									a.screen_name AS author
								FROM exp_forum_topics t, exp_members m, exp_members a
								WHERE topic_id IN (".implode(',', array_unique($topic_ids)).")
								AND t.last_post_author_id = m.member_id
								AND a.member_id = t.author_id
								AND t.announcement = 'n' ".$sort_order);
								
	
		/** -------------------------------------
		/**  No results?  Something has gone terribly wrong!!
		/** -------------------------------------*/
		
		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('off', array($this->EE->lang->line('search_no_result')), $this->EE->lang->line('search_result_heading'));		
		}
		
		/** ----------------------------------------
		/**  Load the typography class
		/** ----------------------------------------*/
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();
		
		/** ---------------------------------------
		/**  Fetch member info for "reply" results
		/** ---------------------------------------*/
		
		$member_info = array();

		if ( ! empty($post_ids))
		{
			$POST_IDS = "(";

			foreach ($post_ids as $post_array)
			{
				$POST_IDS .= implode(',', $post_array).',';
			}

			$POST_IDS = substr($POST_IDS, 0, -1).")";

			$m_query = $this->EE->db->query("SELECT p.post_id, p.body, m.screen_name, m.member_id FROM exp_forum_posts AS p
										LEFT JOIN exp_members AS m ON p.author_id = m.member_id
									WHERE p.post_id IN {$POST_IDS}");
			
			// again with the something has gone terribly wrong...
			if ($m_query->num_rows() == 0)
			{
				return $this->EE->output->show_user_error('off', array($this->EE->lang->line('search_no_result')), $this->EE->lang->line('search_result_heading'));	
			}
			
			foreach ($m_query->result_array() as $row)
			{
				// get just a few characters from the reply, and strip [quote]s to help prevent redundancy

				$snippet = strip_tags($row['body']);

				if (preg_match('/\[quote.*?\](.*?)\[\/quote\]/si', $snippet, $match)) 
				{
					// Match the entirety of the quote block
					if (stristr($match['1'], '[quote'))
					{
						$match['0'] = $this->EE->functions->full_tag($match['0'], $snippet, '[quote', '\[\/quote\]');
					}
					
					$snippet = str_replace($match['0'], '', $snippet);
				}
							
				$snippet = substr($snippet, 0, 30);
				
				$reply_info[$row['post_id']] = array(
														'member_id' => $row['member_id'],
														'screen_name' => $row['screen_name'],
														'snippet' => $this->EE->typography->filter_censored_words($snippet)
													);
			}
		}
		
		/** -------------------------------------
		/**  Fetch the "row" template
		/** -------------------------------------*/
	
		$template = $this->_load_element('result_rows');
		
		/** -------------------------------------
		/**  Fetch the "last_reply" date
		/** -------------------------------------*/
		
		// We do this here to keep it out of the loop
		
		$date = ( ! preg_match("/{last_reply\s+format=['|\"](.+?)['|\"]\}/i", $template, $match)) ? FALSE : $match;			
		
		
		/** -------------------------------------
		/**  Fetch the topic markers
		/** -------------------------------------*/
		
		$markers = $this->_fetch_topic_markers();
		
		/** -------------------------------------
		/**  Parse the results
		/** -------------------------------------*/
		
		$topics = '';						
		$count = 0;
		
		if (preg_match("/".LD."switch\s*=\s*(\042|\047)([^\\1]*?)\\1".RD."/si", $template, $smatch))
		{
			$switches = explode('|', $smatch['2']);
		}
		
		/** ---------------------------------------
		/**  Parse and prep 'reply_results' conditionals
		/** ---------------------------------------*/
		
		if(preg_match_all("/".LD."if reply_results.*?".RD.".*?".LD."\/if".RD."/s", $template, $rconds, PREG_SET_ORDER))
		{
			$marker = '4654487c320f2';

			foreach ($rconds as $key => $val)
			{
				// replace 'reply_results' with a marker
				$rconds[$key][1] = $this->EE->functions->prep_conditionals($rconds[$key][0], array('reply_results' => $marker), 'y');

				// protect PHP tags within the conditional since we'll be evaluating this code later
				// and don't want to interfere with their PHP parsing settings
				$rconds[$key][1] = str_replace(array('<?', '?>'), array('&lt;?', '?&gt;'), $rconds[$key][1]);

				// convert our prepped EE conditionals to PHP
				$rconds[$key][1] = str_replace(array(LD.'/if'.RD, LD.'if:else'.RD), array('<?php endif; ?'.'>','<?php else : ?'.'>'), $rconds[$key][1]);
				$rconds[$key][1] = preg_replace("/".preg_quote(LD)."((if:(else))*if)\s*(.*?)".preg_quote(RD)."/s", '<?php \\3if(\\4) : ?'.'>', $rconds[$key][1]);
			}
		}

		foreach ($query->result_array() as $row)
		{
			$temp = $template;
			$count++;
			
			/** -------------------------------------
			/**  Assign the post marker (folder image)
			/** -------------------------------------*/
		
			$topic_type = '';
			
			$topic_marker = $markers['new'];
			$temp = $this->_allow_if('new_topic', $temp);
			
			/** -------------------------------------
			/**  Do we need small pagination links?
			/** -------------------------------------*/
			
			$tquery = $this->EE->db->query("SELECT forum_id, forum_posts_perpage, forum_name FROM exp_forums WHERE forum_id = '{$row['forum_id']}'");
			$thread_limit = ($tquery->num_rows() == 0) ? 20 : $tquery->row('forum_posts_perpage') ;
			
			$total_posts = $row['thread_total'] - 1;
					
			if ($total_posts > $thread_limit )
			{
				$num_pages = intval($total_posts / $thread_limit);

				if ($num_pages > 0)
				{
					if ($total_posts % $thread_limit) 
					{
						$num_pages++;
					}
				}
				
				$links = "";
				$baselink = $this->_forum_path('/viewthread/'.$row['topic_id'].'/');
								
				for ($i = 0; $i < $num_pages; $i++)
				{	
					if ($i == 3 AND $num_pages >=5)
					{
						$i = $num_pages - 1;				
						$links .= ' &#8230; ';
					}
										
					$p = ($i == 0) ? '' : 'P'.($i * $thread_limit).'/';
										
					$links .= "<a href='".$baselink.$p."'>".($i + 1)."</a> ";
				}
				
				$temp = str_replace('{pagelinks}', rtrim($links), $temp);				
				$temp = $this->_allow_if('pagelinks', $temp);
			}
			else
			{
				$temp = $this->_deny_if('pagelinks', $temp);
			}			
			
			/** -------------------------------------
			/**  Replace {switch="foo|bar|..."}
			/** -------------------------------------*/
			
			if ( ! empty($switches))
			{
				$switch = $switches[($count + count($switches) - 1) % count($switches)];
				$temp = str_replace($smatch['0'], $switch, $temp);
			}
			
			/** ---------------------------------------
			/**  reply_results conditionals
			/** ---------------------------------------*/
			if ( ! empty($rconds))
			{
				foreach ($rconds as $rcond)
				{
					$num_replies = (isset($post_ids[$row['topic_id']])) ? count($post_ids[$row['topic_id']]) : 0;
					
					$rcond[1] = str_replace($marker, $num_replies, $rcond[1]);
					
					ob_start();
					
					$this->EE->functions->evaluate($rcond[1]);
					
					$result = ob_get_clean();
					
					// turn PHP tags back to their old wily ways
					$result = str_replace(array('&lt;?', '?&gt;'), array('<?', '?>'), $result);
					
					$temp = str_replace($rcond[0], $result, $temp);
				}
			}

			/** -------------------------------------
			/**  Swap out the template variables
			/** -------------------------------------*/
			
			$temp = $this->_deny_if('new_topic', $temp);
			
			if (isset($post_ids[$row['topic_id']]))
			{
				$reply_temp = $this->_load_element('reply_results');
				$reply_results = '';
				
				foreach ($post_ids[$row['topic_id']] as $post_id)
				{
					$r_temp = $reply_temp;

					$r_temp = $this->_var_swap($r_temp,
									array(
											'author'				=>	$reply_info[$post_id]['screen_name'],
											'path:member_profile'	=>	$this->_profile_path($reply_info[$post_id]['member_id']),
											'snippet'				=>	$this->EE->functions->encode_ee_tags($reply_info[$post_id]['snippet'], TRUE),
											'path:viewreply'		=>	$this->_forum_path('/viewreply/'.$post_id.'/')
										)
									);

					$reply_results .= $r_temp;
				}
				
				$temp = str_replace('{include:reply_results}', $reply_results, $temp);
			}
			
			$temp = $this->_var_swap($temp,
							array(
									'topic_marker'			=>	$topic_marker,
									'topic_type'			=>  $topic_type,
									'topic_title'			=>	$this->EE->typography->filter_censored_words($this->_convert_special_chars($row['title'])),
									'forum_name'			=>  $tquery->row('forum_name') ,
									'author'				=>	$row['author'],
									'total_views'			=>	$row['thread_views'],
									'total_posts'			=>	$row['thread_total'],
									'reply_author'			=>	$row['last_post_author'],
									'path:member_profile'	=>	$this->_profile_path($row['author_id']),
									'path:viewforum'		=>	$this->_forum_path('/viewforum/'.$tquery->row('forum_id') .'/'),
									'path:view_thread'		=>	$this->_forum_path('/viewthread/'.$row['topic_id'].'/'),
									'path:search_thread'	=>	$this->_forum_path('/search_thread/'.$this->current_id.$row['topic_id'].'/'),
									'path:reply_member_profile'	=> $this->_profile_path($row['last_post_author_id'])
								)
							);

			/** -------------------------------------
			/**  Parse the "last_reply" date
			/** -------------------------------------*/
			if ($date !== FALSE AND $row['last_post_date'] != 0)
			{
				if (date('Ymd', $row['last_post_date']) == date('Ymd', $this->EE->localize->now))
				{	
					$dt = str_replace('%x', $this->EE->localize->format_timespan(($this->EE->localize->now - $row['last_post_date'])), $this->EE->lang->line('ago'));
				}
				else
				{
					$dt = $this->EE->localize->decode_date($date['1'], $row['last_post_date']);
				}
			}
			else
			{
				$dt = '-';
			}
			
			$temp = str_replace($date['0'], $dt, $temp);
			
			// Complile the string
			
			$topics .= $temp;
		}

		$str = str_replace('{include:result_rows}', $topics, $str);
			
		/** --------------------------------
		/**  Parse the template
		/** --------------------------------*/
		return $this->_var_swap($this->_load_element('search_results_page'),
							array(
								'include:search_results'	=> $str,
								'pagination_links'			=> $pagination,
								'current_page'				=> $current_page,
								'total_pages'				=> $total_pages,								
								'keywords'					=> $this->EE->functions->encode_ee_tags($keywords),
								'total_results'				=> $total_rows,								
								'path:new_topic' 			=> $this->_forum_path('/newtopic/'.$this->current_id.'/')
								)
							);		
	}



	
	/** ---------------------------------------
	/**  Search Thread Page
	/** ---------------------------------------*/
	
	function search_thread_page()
	{
		/** ----------------------------------------
		/**  Fetch language file
		/** ----------------------------------------*/
		$this->EE->lang->loadfile('search');

		/** ----------------------------------------
		/**  Check search ID number and set topic ID
		/** ----------------------------------------*/
		
		$topic_id = substr($this->current_id, 32);
		$this->current_id = substr($this->current_id, 0, 32);

		if (strlen($this->current_id) < 32 OR $topic_id == '' OR ! is_numeric($topic_id))
		{
			return $this->EE->output->show_user_error('off', array($this->EE->lang->line('search_no_result')), $this->EE->lang->line('search_result_heading'));		
		}		

		/** ----------------------------------------
		/**  Clear old search results
		/** ----------------------------------------*/
		
		// We cache search results for 2 hours

		$expire = time() - (2 * 3600);
		
		$this->EE->db->query("DELETE FROM exp_forum_search WHERE search_date < '$expire'");

		/** ----------------------------------------
		/**  Fetch the cached search query
		/** ----------------------------------------*/
					
		$query = $this->EE->db->query("SELECT * FROM exp_forum_search WHERE search_id = '".$this->EE->db->escape_str($this->current_id)."'");

		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('off', array($this->EE->lang->line('search_no_result')), $this->EE->lang->line('search_result_heading'));		
		}
		
		$post_ids  = unserialize(stripslashes($query->row('post_ids') ));
		
		if ( ! isset($post_ids[$topic_id]))
		{
			return $this->EE->output->show_user_error('off', array($this->EE->lang->line('search_no_result')), $this->EE->lang->line('search_result_heading'));
		}

		// Load the XML Helper
		$this->EE->load->helper('xml');

		// we are only concerned about posts for this topic
		$post_ids	= $post_ids[$topic_id];
		$keywords	= xml_convert($query->row('keywords') );

		/** -------------------------------------
		/**  Load the template
		/** -------------------------------------*/
		
		$str = $this->_load_element('thread_search_results');
		
		/** -------------------------------------
		/**  Do we have pagination?
		/** -------------------------------------*/
		
		$pagination 	= '';
		$current_page	= 0;
		$total_pages	= 1;		
		$post_limit 	= 20;
		$total_rows	 	= count($post_ids);
		
		if ($total_rows > $post_limit)
		{	
			$pagination = $this->_create_pagination(
										array(
												'first_url'		=> $this->_forum_path('/search_thread/'.$this->current_id.$topic_id.'/'),
												'path'			=> $this->_forum_path('/search_thread/'.$this->current_id.$topic_id.'/'),
												'total_count'	=> $total_rows,
												'per_page'		=> 20,
												'cur_page'		=> $this->current_page
											)
										);
			
			// Slice our array so we can limit the query properly
		
			$post_ids = array_slice($post_ids, $this->current_page, $post_limit);
		
			// Set the stats for: {current_page} of {total_pages}
			
			$cur_page = ($this->current_page == 0) ? 1 : $this->current_page;			
			$current_page = floor(($cur_page / $post_limit) + 1);
			$total_pages  = ceil($total_rows / $post_limit);			
		}
		
		if ($pagination == '')
		{
			$str = $this->_deny_if('paginate', $str, '&nbsp;');
		}
		else
		{
			$str = $this->_allow_if('paginate', $str);
		}
		
		/** -------------------------------------
		/**  Fetch the posts and topic title
		/** -------------------------------------*/
		
		$query = $this->EE->db->query("SELECT title FROM exp_forum_topics WHERE topic_id = '{$topic_id}'");
		
		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('off', array($this->EE->lang->line('search_no_result')), $this->EE->lang->line('search_result_heading'));		
		}
		
		$topic_title = $query->row('title') ;
		
		$query = $this->EE->db->query("SELECT p.forum_id, p.topic_id, p.post_id, p.author_id, p.body, p.post_date,
									m.screen_name AS author
								FROM exp_forum_posts AS p, exp_members AS m
								WHERE p.topic_id = '{$topic_id}'
								AND m.member_id = p.author_id
								AND p.post_id IN (".implode(',', array_unique($post_ids)).")
								ORDER BY post_date DESC");					
	
		/** -------------------------------------
		/**  No results?  Something has gone terribly wrong!!
		/** -------------------------------------*/
		
		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('off', array($this->EE->lang->line('search_no_result')), $this->EE->lang->line('search_result_heading'));		
		}
	
		/** -------------------------------------
		/**  Fetch the "row" template
		/** -------------------------------------*/
	
		$template = $this->_load_element('thread_result_rows');
		
		/** -------------------------------------
		/**  Fetch the "last_reply" date
		/** -------------------------------------*/
		
		// We do this here to keep it out of the loop
		
		$date = ( ! preg_match("/{post_date\s+format=['|\"](.+?)['|\"]\}/i", $template, $match)) ? FALSE : $match;			
		
		
		/** -------------------------------------
		/**  Fetch the topic markers
		/** -------------------------------------*/
		
		$markers = $this->_fetch_topic_markers();
		
		/** -------------------------------------
		/**  Parse the results
		/** -------------------------------------*/
		
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();
		
		$topics = '';						
		$count = 0;
		
		if (preg_match("/".LD."switch\s*=\s*(\042|\047)([^\\1]*?)\\1".RD."/si", $template, $smatch))
		{
			$switches = explode('|', $smatch['2']);
		}
						
		foreach ($query->result_array() as $row)
		{
			$temp = $template;
			$count++;
			
			/** -------------------------------------
			/**  Assign the post marker (folder image)
			/** -------------------------------------*/
		
			$topic_type = '';
			
			$topic_marker = $markers['new'];
			$temp = $this->_allow_if('new_topic', $temp);		
			
			/** -------------------------------------
			/**  Replace {switch="foo|bar|..."}
			/** -------------------------------------*/
			
			if ( ! empty($switches))
			{
				$switch = $switches[($count + count($switches) - 1) % count($switches)];
				$temp = str_replace($smatch['0'], $switch, $temp);
			}
			
			/** -------------------------------------
			/**  Swap out the template variables
			/** -------------------------------------*/
			
			$temp = $this->_deny_if('new_topic', $temp);
			
			// get just a few characters from the reply, and strip [quote]s to help prevent redundancy

			$snippet = strip_tags($row['body']);

			if (preg_match('/\[quote.*?\](.*?)\[\/quote\]/si', $snippet, $match)) 
			{
				// Match the entirety of the quote block
				if (stristr($match['1'], '[quote'))
				{
					$match['0'] = $this->EE->functions->full_tag($match['0'], $snippet, '[quote', '\[\/quote\]');
				}
				
				$snippet = str_replace($match['0'], '', $snippet);
			}
						
			$snippet = substr($snippet, 0, 30);
			
			$temp = $this->_var_swap($temp,
							array(
									'topic_marker'			=>	$topic_marker,
									'topic_type'			=>  $topic_type,
									'author'				=>	$row['author'],
									'snippet'				=>  $this->EE->functions->encode_ee_tags($snippet, TRUE),
									'path:member_profile'	=>	$this->_profile_path($row['author_id']),
									'path:viewreply'		=>	$this->_forum_path('/viewreply/'.$row['post_id'].'/')
								)
							);

			/** -------------------------------------
			/**  Parse the post_date
			/** -------------------------------------*/
			if ($date !== FALSE AND $row['post_date'] != 0)
			{
				if (date('Ymd', $row['post_date']) == date('Ymd', $this->EE->localize->now))
				{	
					$dt = str_replace('%x', $this->EE->localize->format_timespan(($this->EE->localize->now - $row['post_date'])), $this->EE->lang->line('ago'));
				}
				else
				{
					$dt = $this->EE->localize->decode_date($date['1'], $row['post_date']);
				}
			}
			else
			{
				$dt = '-';
			}
			
			$temp = str_replace($date['0'], $dt, $temp);
			
			// Complile the string
			
			$topics .= $temp;
		}

		$str = str_replace('{include:thread_result_rows}', $topics, $str);
			
		/** --------------------------------
		/**  Parse the template
		/** --------------------------------*/
		return $this->_var_swap($this->_load_element('search_thread_page'),
							array(
								'include:thread_search_results'	=> $str,
								'pagination_links'			=> $pagination,
								'current_page'				=> $current_page,
								'total_pages'				=> $total_pages,								
								'keywords'					=> $keywords,								
								'total_results'				=> $total_rows,
								'topic_title'				=> $this->EE->typography->filter_censored_words($this->_convert_special_chars($topic_title))
								)
							);
	}

	
	
	
	/** -------------------------------------
	/**  Most Recent Topics
	/** -------------------------------------*/
	function most_recent_topics()
	{
		
		$query = $this->EE->db->query("SELECT t.title, t.body, t.topic_id, t.thread_total, t.thread_views, t.author_id, t.last_post_author_id, t.forum_id, f.forum_status, f.forum_permissions, f.forum_name 
			FROM exp_forum_topics t LEFT JOIN exp_forums f ON t.forum_id = f.forum_id 
			WHERE t.board_id = '".$this->_fetch_pref('board_id')."'
			ORDER BY topic_date DESC LIMIT 30");
	
		if ($query->num_rows() == 0)
		{
			return '';
		}
		
		$ids = array();

		foreach ($query->result_array() as $i => $row)
		{
			$member_ids[] = $row['author_id'];
			$member_ids[] = $row['last_post_author_id'];
			
			if ($i > 12) continue;
			
			$ids[] = $row['topic_id'];					
		}
		

		$m_query = $this->EE->db->query("SELECT m.screen_name, m.member_id 
							 FROM exp_members m
							 WHERE m.member_id IN (".implode(',', $member_ids).")");
	

		foreach($m_query->result_array() as $row)
		{
			$member_name[$row['member_id']] = $row['screen_name'];
		}

		
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();
		
		$template = $this->_load_element('most_recent_topics');
		
		/** -----------------------------
		/**  Excerpt Variable Present?
		/** -----------------------------*/
		
		if (preg_match("/{last_post_excerpt(.*?)}/", $template, $match))
		{
			$excerpt_limit = str_replace(array('limit=', '"', "'"), '', $match['1']);
				
			if ($excerpt_limit == '' OR ! is_numeric($excerpt_limit)) $excerpt_limit = 25; // words
			
			$excerpt_match = $match['0'];
			
			// Create Excerpts by fetching the bodies all at once
			// so that we are only performing one query.
			// There is a small chance that a person might not have
			// permission to view a forum topic so we have a check in the
			// foreach below.
			
			$ids = array_unique($ids);
			
			$results = $this->EE->db->query("SELECT body, topic_id FROM exp_forum_posts WHERE topic_id IN ('".implode("','", $ids)."') ORDER BY post_date DESC LIMIT 12");
			
			foreach($results->result_array() as $row)
			{
				$excerpts[$row['topic_id']] = preg_replace("/\[.*?\]|\<.*?\>|\s{2}/s", '', $this->EE->functions->word_limiter($row['body'], $excerpt_limit)); 
			}
		}
	
		$str = '';
		$i = 0;
		
		foreach ($query->result_array() as $row)
		{
			$row['forum_permissions'] = unserialize(stripslashes($row['forum_permissions']));

			if ($row['forum_status'] == 'c' AND ! $this->_permission('can_view_hidden', $row['forum_permissions']))
			{
				continue;
			}
		
			if ( ! $this->_permission('can_view_forum', $row['forum_permissions']) OR  ! $this->_permission('can_view_topics', $row['forum_permissions']))
			{
				continue;
			}
		
			if ($i == 10)
			{
				break;
			}
		
			$temp = $template;
			
			$temp = str_replace('{title}', $this->EE->typography->filter_censored_words($this->_convert_special_chars($row['title'])), $temp);
			$temp = str_replace('{replies}', $row['thread_total']-1, $temp);
			$temp = str_replace('{views}', $row['thread_views'], $temp);
			$temp = str_replace('{author}', $this->_convert_special_chars($member_name[$row['author_id']]), $temp);
			$temp = str_replace('{path:member_profile}',  $this->_profile_path($row['author_id']), $temp);
			$temp = str_replace('{path:view_thread}', $this->_forum_path('/viewthread/'.$row['topic_id'].'/'), $temp);
			
			$temp = str_replace('{forum_name}', $row['forum_name'], $temp);
			$temp = str_replace('{path:viewforum}', $this->_forum_path('viewforum/'.$row['forum_id']), $temp);
			$temp = str_replace('{path:last_poster_profile}',  $this->_profile_path($row['last_post_author_id']), $temp);
			$temp = str_replace('{last_poster}', $this->_convert_special_chars($member_name[$row['last_post_author_id']]), $temp);
			
			if (isset($excerpt_match))
			{
				if ( ! isset($excerpts[$row['topic_id']]))
				{
					$results = $this->EE->db->query("SELECT body FROM exp_forum_posts WHERE topic_id = '".$row['topic_id']."' ORDER BY post_date DESC LIMIT 1");
					
					if ($results->num_rows() == 0)
					{
						$temp = str_replace($excerpt_match, preg_replace("/\[.*?\]|\<.*?\>|\s{2}/s", '', $this->EE->functions->word_limiter($row['body'], $excerpt_limit)), $temp);
					}
					else
					{
						$temp = str_replace($excerpt_match, preg_replace("/\[.*?\]|\<.*?\>|\s{2}/s", '', $this->EE->functions->word_limiter($results->row('body') , $excerpt_limit)), $temp);
					}
				}
				else
				{
					$temp = str_replace($excerpt_match, $excerpts[$row['topic_id']], $temp);
				}
			}
			
			$str .= $temp;
			
			$i++;
		}
	
		return $str;
	}




	/** -------------------------------------
	/**  Most Popular Posts
	/** -------------------------------------*/
	function most_popular_posts()
	{
		$query = $this->EE->db->query("SELECT t.title, t.body, t.topic_id, t.thread_total, t.thread_views, t.author_id, t.last_post_author_id, t.forum_id, f.forum_status, f.forum_permissions, f.forum_name 
			FROM exp_forum_topics t LEFT JOIN exp_forums f ON t.forum_id = f.forum_id
			WHERE t.board_id = '".$this->_fetch_pref('board_id')."'
			ORDER BY thread_total DESC LIMIT 30");
	
		if ($query->num_rows() == 0)
		{
			return '';
		}
		
		$ids = array();

		foreach ($query->result_array() as $i => $row)
		{
			$member_ids[] = $row['author_id'];
			$member_ids[] = $row['last_post_author_id'];
			
			if ($i > 12) continue;
			
			$ids[] = $row['topic_id'];					
		}
		

		$m_query = $this->EE->db->query("SELECT m.screen_name, m.member_id 
							 FROM exp_members m
							 WHERE m.member_id IN (".implode(',', $member_ids).")");
	

		foreach($m_query->result_array() as $row)
		{
			$member_name[$row['member_id']] = $row['screen_name'];
		}
		
		$template = $this->_load_element('most_popular_posts');
		
		/** -----------------------------
		/**  Excerpt Variable Present?
		/** -----------------------------*/
		
		if (preg_match("/{last_post_excerpt(.*?)}/", $template, $match))
		{
			$excerpt_limit = str_replace(array('limit=', '"', "'"), '', $match['1']);
				
			if ($excerpt_limit == '' OR ! is_numeric($excerpt_limit)) $excerpt_limit = 25; // words
			
			$excerpt_match = $match['0'];
			
			// Create Excerpts by fetching the bodies all at once
			// so that we are only performing one query.
			// There is a small chance that a person might not have
			// permission to view a forum topic so we have a check in the
			// foreach below.
			
			$ids = array_unique($ids);
			
			$results = $this->EE->db->query("SELECT body, topic_id FROM exp_forum_posts WHERE topic_id IN ('".implode("','", $ids)."') ORDER BY post_date DESC LIMIT 12");
			
			foreach($results->result_array() as $row)
			{
				$excerpts[$row['topic_id']] = preg_replace("/\[.*?\]|\<.*?\>|\s{2}/s", '', $this->EE->functions->word_limiter($row['body'], $excerpt_limit)); 
			}
		}
		
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();
	
		$str = '';
		
		$i = 0;
		foreach ($query->result_array() as $row)
		{
			$row['forum_permissions'] = unserialize(stripslashes($row['forum_permissions']));

			if ($row['forum_status'] == 'c' AND ! $this->_permission('can_view_hidden', $row['forum_permissions']))
			{
				continue;
			}
		
			if ( ! $this->_permission('can_view_forum', $row['forum_permissions']) OR  ! $this->_permission('can_view_topics', $row['forum_permissions']))
			{
				continue;
			}
		
			if ($i == 10)
			{
				break;
			}
			
			$temp = $template;
			
			$temp = str_replace('{title}', $this->EE->typography->filter_censored_words($this->_convert_special_chars($row['title'])), $temp);
			$temp = str_replace('{replies}', $row['thread_total']-1, $temp);
			$temp = str_replace('{views}', $row['thread_views'], $temp);
			$temp = str_replace('{path:member_profile}',  $this->_profile_path($row['author_id']), $temp);
			$temp = str_replace('{author}', $this->_convert_special_chars($member_name[$row['author_id']]), $temp);
			$temp = str_replace('{path:view_thread}', $this->_forum_path('/viewthread/'.$row['topic_id'].'/'), $temp);
			
			$temp = str_replace('{forum_name}', $row['forum_name'], $temp);
			$temp = str_replace('{path:viewforum}', $this->_forum_path('viewforum/'.$row['forum_id']), $temp);
			$temp = str_replace('{path:last_poster_profile}',  $this->_profile_path($row['last_post_author_id']), $temp);
			$temp = str_replace('{last_poster}', $this->_convert_special_chars($member_name[$row['last_post_author_id']]), $temp);
			
			/** ------------------------------
			/**  Fetch Last Post Excerpt
			/** -------------------------------*/
			
			if (isset($excerpt_match))
			{
				if ( ! isset($excerpts[$row['topic_id']]))
				{
					$results = $this->EE->db->query("SELECT body FROM exp_forum_posts WHERE topic_id = '".$row['topic_id']."' ORDER BY post_date DESC LIMIT 1");
					
					if ($results->num_rows() == 0)
					{
						$temp = str_replace($excerpt_match, preg_replace("/\[.*?\]|\<.*?\>|\s{2}/s", '', $this->EE->functions->word_limiter($row['body'], $excerpt_limit)), $temp);
					}
					else
					{
						$temp = str_replace($excerpt_match, preg_replace("/\[.*?\]|\<.*?\>|\s{2}/s", '', $this->EE->functions->word_limiter($results->row('body') , $excerpt_limit)), $temp);
					}
				}
				else
				{
					$temp = str_replace($excerpt_match, $excerpts[$row['topic_id']], $temp);
				}
			}
			
			
			
			$str .= $temp;
			
			$i++;
		}
	
		return $str;
	}


  
	/** -----------------------------------------------------------
	/**  Emoticons
	/** -----------------------------------------------------------*/
	function emoticon_page()
	{
		
		if ($this->EE->session->userdata('member_id') == 0)
		{
			return $this->EE->output->fatal_error($this->EE->lang->line('must_be_logged_in'));
		}
		
		$class_path = PATH_MOD.'emoticon/emoticons'.EXT;
		
		if ( ! is_file($class_path) OR ! @include_once($class_path))
		{
			return $this->EE->output->fatal_error('Unable to locate the smiley images');
		}
		
		if ( ! is_array($smileys))
		{
			return;
		}
		
		
		$path = $this->EE->config->slash_item('emoticon_path');
				
		ob_start();
		?>			 
		<script type="text/javascript"> 
		<!--
		function add_smiley(smiley)
		{
			var el = opener.document.getElementById('submit_post').body;

			if ('selectionStart' in el) {
				newStart = el.selectionStart + smiley.length;

				el.value = el.value.substr(0, el.selectionStart) +
								smiley +
								el.value.substr(el.selectionEnd, el.value.length);
				el.setSelectionRange(newStart, newStart);
			}
			else if (opener.document.selection) {
				opener.document.selection.createRange().text = text;
			}
			else {
				el.value += " " + smiley + " ";
			}

			el.focus();
			window.close();
		}
		//-->
		</script>
		
		<?php

		$javascript = ob_get_contents();
		ob_end_clean();		
		$r = $javascript;
				
		
		$i = 1;
		
		$dups = array();
		
		foreach ($smileys as $key => $val)
		{
			if ($i == 1 AND substr($r, -5) != "<tr>\n")
			{
				$r .= "<tr>\n";				
			}
			
			if (in_array($smileys[$key]['0'], $dups))
				continue;
			
			$r .= "<td class='tableCellOne' align='center'><a href=\"#\" onclick=\"return add_smiley('".$key."');\"><img src=\"".$path.$smileys[$key]['0']."\" width=\"".$smileys[$key]['1']."\" height=\"".$smileys[$key]['2']."\" alt=\"".$smileys[$key]['3']."\" border=\"0\" /></a></td>\n";

			$dups[] = $smileys[$key]['0'];

			if ($i == 10)
			{
				$r .= "</tr>\n";				
				
				$i = 1;
			}
			else
			{
				$i++;
			}	  
		}
		
		$r = rtrim($r);
				
		if (substr($r, -5) != "</tr>")
		{
			$r .= "</tr>\n";
		}
		
		$this->_set_page_title($this->EE->lang->line('smileys'));
		return str_replace('{include:smileys}', $r, $this->_load_element('emoticon_page'));
	}




	/** -------------------------------------
	/**  Topic Titles Tag
	/** -------------------------------------*/
	
	// This tag is intended to be used in a standard template
	// so that forum topics can be shown outside the forum

	function topic_titles()
	{
		$this->EE->TMPL->disable_caching = FALSE;
		
		/** -------------------------------------
		/**  Set some defaults
		/** -------------------------------------*/
		
		$sort  = ( ! in_array($this->EE->TMPL->fetch_param('sort'), array('asc', 'desc'))) ? 'asc' : $this->EE->TMPL->fetch_param('sort');
		$limit = ( ! is_numeric($this->EE->TMPL->fetch_param('limit'))) ? '10' : $this->EE->TMPL->fetch_param('limit');
		
		$this->EE->db->select('forum_topics.topic_id, forum_topics.author_id, forum_topics.last_post_author_id, 
								forum_topics.title, forum_topics.body, forum_topics.topic_date, 
								forum_topics.last_post_date, forum_topics.last_post_id, 
								forum_topics.thread_total, forum_topics.thread_views,
								forum_topics.parse_smileys, forums.forum_status,
								forums.forum_permissions, forums.forum_name, forums.forum_text_formatting, 
								forums.forum_html_formatting, forums.forum_auto_link_urls, 
								forums.forum_allow_img_urls, forum_boards.board_label, 
								forum_boards.board_name, forum_boards.board_forum_url', FALSE);

		$join = 'LEFT JOIN '.$this->EE->db->dbprefix('forums').
					' ON '.$this->EE->db->dbprefix('forum_topics').
					'.forum_id = '.$this->EE->db->dbprefix('forums').'.forum_id';
		$this->EE->db->from('forum_topics '.$join);
		$this->EE->db->join('forum_boards', 
			$this->EE->db->dbprefix('forum_topics').
						'.board_id = '.$this->EE->db->dbprefix('forum_boards').'.board_id ', 'left');
		
		if ($forum = $this->EE->TMPL->fetch_param('forums'))
		{
			if (substr($forum, 0, 4) == 'not ')
			{
				$this->EE->db->where_not_in('forum_topics.forum_id', explode('|', substr($forum, 4)), FALSE);
			}
			else
			{
				$this->EE->db->where_in('forum_topics.forum_id', explode('|', $forum));
			}
		}

		if ($board = $this->EE->TMPL->fetch_param('boards'))
		{
			if (substr($board, 0, 4) == 'not ')
			{
				$this->EE->db->where_not_in('forum_topics.board_id', explode('|', substr($board, 4)));
			}
			else
			{
				$this->EE->db->where_in('forum_topics.board_id', explode('|', $board));
			}
		}
		else
		{
			$this->EE->db->where('forum_topics.board_id', $this->_fetch_pref('board_id'));
		}

		switch ($this->EE->TMPL->fetch_param('orderby'))
		{
			case 'title' 		: $this->EE->db->order_by('forum_topics.title', $sort);
				break;
			case 'recent_post' 	:
				$this->EE->db->order_by('last_post_date', $sort); 
				$this->EE->db->order_by('topic_date', $sort);
				break;
			default				: $this->EE->db->order_by('forum_topics.topic_date', $sort);
				break;
		}
		
		$this->EE->db->limit($limit);

		$query = $this->EE->db->get();

		if ($query->num_rows() == 0)
		{
			return '';
		}
		
		$post_ids = array();
		$fetch_replies = (stristr($this->EE->TMPL->tagdata, 'last_reply')) ? TRUE : FALSE;
		
		foreach ($query->result_array() as $i => $row)
		{
			$member_ids[] = $row['author_id'];
			$member_ids[] = $row['last_post_author_id'];
			
			if ($fetch_replies)
			{
				$post_ids[] = $row['last_post_id'];	
			}		
		}
		
		$m_query = $this->EE->db->query("SELECT m.screen_name, m.member_id 
							 FROM exp_members m
							 WHERE m.member_id IN (".implode(',', $member_ids).")");
	

		foreach($m_query->result_array() as $row)
		{
			$member_name[$row['member_id']] = $row['screen_name'];
		}
		
		
		
		/** ---------------------------------------
		/**  Fetch reply information, if necessary
		/** ---------------------------------------*/
		
		$replies = array();
		
		if ($fetch_replies)
		{
			$POSTS = " post_id IN ('".implode("', '", $post_ids)."') ";
			
			$sql = "SELECT topic_id, body AS last_reply, parse_smileys 
					FROM exp_forum_posts
 					WHERE {$POSTS}";
			
			$rquery = $this->EE->db->query($sql);

			if ($rquery->num_rows() > 0)
			{
				foreach($rquery->result_array() as $row)
				{
					// using a loop here so all that's need to add to the $replies array
					// is to add fields to the post query above
					foreach($row as $field => $val)
					{	
						if ($field == 'topic_id')
						{
							continue;
						}
						
						$replies[$row['topic_id']][$field] = $val;
					}
				}
			}			
		}

		$formatting = array(
							'text_format'	=> $query->row('forum_text_formatting') ,
							'html_format'	=> $query->row('forum_html_formatting') ,
							'auto_links'	=> $query->row('forum_auto_link_urls') ,
							'allow_img_url' => $query->row('forum_allow_img_urls') 
							);
	
		/** ----------------------------------
		/**  Fetch date-related variables
		/** ----------------------------------*/
		
		$topic_date 		= array();
		$last_post_date 	= array();

		$date_vars = array('topic_date', 'last_post_date');
				
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
						case 'topic_date'		: $topic_date[$matches['0'][$j]] = $this->EE->localize->fetch_date_params($matches['1'][$j]);
							break;
						case 'last_post_date'	: $last_post_date[$matches['0'][$j]] = $this->EE->localize->fetch_date_params($matches['1'][$j]);
							break;
					}
				}
			}
		}

		/** -----------------------------------
		/**  Blast through the result
		/** -----------------------------------*/
		
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();

		$str = '';
		
		foreach ($query->result_array() as $row)
		{
			$row['forum_permissions'] = unserialize(stripslashes($row['forum_permissions']));

			if ($row['forum_status'] == 'c' AND ! $this->_permission('can_view_hidden', $row['forum_permissions']))
			{
				continue;
			}
		
			if ( ! $this->_permission('can_view_forum', $row['forum_permissions']) OR  ! $this->_permission('can_view_topics', $row['forum_permissions']))
			{
				continue;
			}
		
		
			$row['title'] = $this->EE->typography->filter_censored_words($row['title']);			
		
			$tagdata = $this->EE->TMPL->tagdata;
			
			/** ----------------------------------------
			/**  Conditionals
			/** ----------------------------------------*/
			
			$cond = $row;
			$cond['logged_in']	= ($this->EE->session->userdata('member_id') == 0) ? 'FALSE' : 'TRUE';
			$cond['logged_out']	= ($this->EE->session->userdata('member_id') != 0) ? 'FALSE' : 'TRUE';
			$cond['post_total']	= $row['thread_total'];  // this variable makes me want raisin bran
			$cond['views'] = $row['thread_views'];
						
			$tagdata = $this->EE->functions->prep_conditionals($tagdata, $cond);
			
			if (isset($replies[$row['topic_id']]))
			{
				$tagdata = $this->EE->functions->prep_conditionals($tagdata, $replies[$row['topic_id']]);				
			}

			
			/** ----------------------------------------
			/**  Single Variables
			/** ----------------------------------------*/
			foreach ($this->EE->TMPL->var_single as $key => $val)
			{
				/** ----------------------------------------
				/**  parse {post_total}
				/** ----------------------------------------*/
				
				if ($key == 'post_total')
				{
					$tagdata = $this->EE->TMPL->swap_var_single($key, $row['thread_total'], $tagdata);
				}
				
				/** ----------------------------------------
				/**  parse {author}
				/** ----------------------------------------*/
				
				if ($key == 'author')
				{
					$tagdata = $this->EE->TMPL->swap_var_single($key, $member_name[$row['author_id']], $tagdata);
				}				

				/** ----------------------------------------
				/**  parse {last_author}
				/** ----------------------------------------*/
				
				if ($key == 'last_author')
				{
					$tagdata = $this->EE->TMPL->swap_var_single($key, $member_name[$row['last_post_author_id']], $tagdata);
				}


				/** ----------------------------------------
				/**  parse {views}
				/** ----------------------------------------*/
				
				if ($key == 'views')
				{
					$tagdata = $this->EE->TMPL->swap_var_single($key, $row['thread_views'], $tagdata);
				}
			
				/** ---------------------------------------
				/**  parse {forum_url}
				/** ---------------------------------------*/
				
				if ($key == 'forum_url')
				{
					$tagdata = $this->EE->TMPL->swap_var_single($key, $row['board_forum_url'], $tagdata);
				}
				
				/** ----------------------------------------
				/**  parse profile path
				/** ----------------------------------------*/
				
				if (strncmp($key, 'profile_path', 12) == 0)
				{
					$tagdata = $this->EE->TMPL->swap_var_single(
														$key, 
														$this->EE->functions->create_url($this->EE->functions->extract_path($key).'/'.$row['author_id'].'/'), 
														$tagdata
													 );
				}
	
				/** ----------------------------------------
				/**  parse thread path
				/** ----------------------------------------*/
				
				if (strncmp($key, 'thread_path', 11) == 0)
				{
					$tagdata = $this->EE->TMPL->swap_var_single(
														$key, 
														$this->EE->functions->create_url($this->EE->functions->extract_path($key).'/'.$row['topic_id'].'/'), 
														$tagdata
													 );
				}
				
				/** ---------------------------------------
				/**  parse auto path
				/** ---------------------------------------*/
				
				if ($key == 'auto_thread_path')
				{
					$tagdata = $this->EE->TMPL->swap_var_single(
														$key,
														$this->EE->functions->remove_double_slashes($row['board_forum_url'].'/viewthread/'.$row['topic_id'].'/'),
														$tagdata
													 );
				}
				
				/** ----------------------------------------
				/**  parse last author profile path
				/** ----------------------------------------*/
				
				if (strncmp($key, 'last_author_profile_path', 24) == 0)
				{
					$tagdata = $this->EE->TMPL->swap_var_single(
														$key, 
														$this->EE->functions->create_url($this->EE->functions->extract_path($key).'/'.$row['last_post_author_id']), 
														$tagdata
													 );
				}
		
				/** ----------------------------------------
				/**  parse topic date
				/** ----------------------------------------*/
				
				if (isset($topic_date[$key]))
				{
					foreach ($topic_date[$key] as $dvar)
						$val = str_replace($dvar, $this->EE->localize->convert_timestamp($dvar, $row['topic_date'], TRUE), $val);					
	
					$tagdata = $this->EE->TMPL->swap_var_single($key, $val, $tagdata);					
				}
	
				/** ----------------------------------------
				/**  {topic_relative_date}
				/** ----------------------------------------*/
				
				if ($key == "topic_relative_date")
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, $this->EE->localize->format_timespan($this->EE->localize->now - $row['topic_date']), $tagdata);
				}

				/** ----------------------------------------
				/**  parse last post date
				/** ----------------------------------------*/
				
				if (isset($last_post_date[$key]))
				{
					foreach ($last_post_date[$key] as $dvar)
						$val = str_replace($dvar, $this->EE->localize->convert_timestamp($dvar, $row['last_post_date'], TRUE), $val);					
	
					$tagdata = $this->EE->TMPL->swap_var_single($key, $val, $tagdata);					
				}
			
				/** ----------------------------------------
				/**  {last_post_relative_date}
				/** ----------------------------------------*/
				
				if ($key == "last_post_relative_date")
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, $this->EE->localize->format_timespan($this->EE->localize->now - $row['last_post_date']), $tagdata);
				}

				/** ---------------------------------------
				/**  Parse {body}
				/** ---------------------------------------*/
				
				if ($key == "body")
				{
					$this->EE->typography->parse_smileys = ($row['parse_smileys'] == 'y') ? TRUE : FALSE;
					
					$content = $this->_quote_decode($this->EE->typography->parse_type($row['body'], 
																					array(
																							'text_format'	=> $formatting['text_format'],
																							'html_format'	=> $formatting['html_format'],
																							'auto_links'	=> $formatting['auto_links'],
																							'allow_img_url' => $formatting['allow_img_url']
																		 				 )
																	 )
													);
													
					$tagdata = $this->EE->TMPL->swap_var_single($key, $content, $tagdata);
				}
			
				/** ---------------------------------------
				/**  {last_reply}
				/** ---------------------------------------*/
				
				if (isset($replies[$row['topic_id']]))
				{
					$this->EE->typography->parse_smileys = ($replies[$row['topic_id']]['parse_smileys'] == 'y') ? TRUE : FALSE;
					
					$content = $this->_quote_decode($this->EE->typography->parse_type($replies[$row['topic_id']]['last_reply'], 
																					array(
																							'text_format'	=> $formatting['text_format'],
																							'html_format'	=> $formatting['html_format'],
																							'auto_links'	=> $formatting['auto_links'],
																							'allow_img_url' => $formatting['allow_img_url']
																		 				 )
																	 )
													);

					$tagdata = $this->EE->TMPL->swap_var_single('last_reply', $content, $tagdata);							
				}
				else
				{
					// no replies for this topic, so wipe variable
					$tagdata = str_replace(LD.'last_reply'.RD, '', $tagdata);					
				}
				
				/** ----------------------------------------
				/**  Parse 1:1 fields
				/** ----------------------------------------*/
				
				if (isset($row[$val]))
				{					
					$tagdata = $this->EE->TMPL->swap_var_single($val, $row[$val], $tagdata);
				}
				
			}
			
			
			$str .= $tagdata;
			
		}
	
		return $str;
	}



	/** ----------------------------------
	/**  HTTP Authentication - Basic
	/** ----------------------------------*/
	
	function http_authentication_basic()
	{
		@header('WWW-Authenticate: Basic realm="'.$this->realm.'"');
		$this->EE->output->set_status_header(401);
		@header("Date: ".gmdate("D, d M Y H:i:s")." GMT");
		exit("HTTP/1.0 401 Unauthorized");
	}

	
	/** ----------------------------------
	/**  HTTP Authentication - Digest
	/** ----------------------------------*/
	
	function http_authentication_digest()
	{
		@header('WWW-Authenticate: Digest realm="'.$this->realm.'",gop="auth", nonce="'.uniqid('').'", opaque="'.md5($this->realm).'"');
		$this->EE->output->set_status_header(401);
		@header("Date: ".gmdate("D, d M Y H:i:s")." GMT");
		exit("HTTP/1.0 401 Unauthorized");
	}

	
	
	/** ----------------------------------
	/**  Check HTTP Authentication - Digest
	/** ----------------------------------*/
	
	function http_authentication_check_digest($allowed_groups = array())
	{
		if (empty($_SERVER) OR ! isset($_SERVER['PHP_AUTH_DIGEST']))
		{
			return FALSE;
		}
		
		$this->auth_attempt = TRUE;
		
		$required = array('uri'			=> '', 
						  'response'	=> '', 
						  'realm'		=> $this->realm,
						  'username'	=> '',
						  'nonce'		=> 1,
						  'nc'			=> 1,
						  'cnonce'		=> 1,
						  'qop'			=> 1);
		
		$params = $this->EE->functions->assign_parameters($_SERVER['PHP_AUTH_DIGEST']);
		
		extract($required);
		extract($params);
		
		/** ----------------------------------------
		/**  Check password lockout status
		/** ----------------------------------------*/
		
		if ($this->EE->session->check_password_lockout($username) === TRUE)
		{
			return FALSE;	
		}
		
		/** ----------------------------------
		/**  Validate Username and Password
		/** ----------------------------------*/
		
		$query = $this->EE->db->query("SELECT password, group_id FROM exp_members WHERE username = '".$this->EE->db->escape_str($username)."'");
		
		if ($query->num_rows() == 0)
		{
			$this->EE->session->save_password_lockout($username);
			return FALSE;
		}
		
		// make sure Super Admins are always allowed
		if ( ! in_array($allowed_groups, 1))
		{
			$allowed_groups[] = 1;
		}
		
		if ( ! in_array($query->row('group_id') , $allowed_groups))
		{
			return FALSE;
		}
		
		$parts = array(
						md5($username.':'.$realm.':'.$query->row('password') ),
						md5($_SERVER['REQUEST_METHOD'].':'.$uri)
					  );
					  
		$valid_response = md5($parts['0'].':'.$nonce.':'.$nc.':'.$cnonce.':'.$qop.':'.$parts['1']);
		
		if ($valid_response == $response)
		{
			return TRUE;
		}
		else
		{
			$this->EE->session->save_password_lockout($username);
			
			return FALSE;
		}
	}

	function fetch_superadmins()
	{
		$super_admins = array();

		$this->EE->db->select('member_id');
		$ad_query = $this->EE->db->get_where('members', array('group_id' => 1));

		foreach ($ad_query->result_array() as $row)
		{
			$super_admins[] = $row['member_id'];
		}
		
		return $super_admins;
	}
	
	
	/** ----------------------------------
	/**  Check HTTP Authentication - Basic
	/** ----------------------------------*/
	
	function http_authentication_check_basic($allowed_groups = array())
	{
		/** ----------------------------------
		/**  Find Username, Please
		/** ----------------------------------*/
		if ( ! empty($_SERVER) && isset($_SERVER['PHP_AUTH_USER']))
		{
			$user = $_SERVER['PHP_AUTH_USER'];
		}
		elseif ( ! empty($_ENV) && isset($_ENV['REMOTE_USER']))
		{
			$user = $_ENV['REMOTE_USER'];
		}
		elseif ( @getenv('REMOTE_USER'))
		{
			$user = getenv('REMOTE_USER');
		}
		elseif ( ! empty($_ENV) && isset($_ENV['AUTH_USER']))
		{
			$user = $_ENV['AUTH_USER'];
		}
		elseif ( @getenv('AUTH_USER'))
		{
			$user = getenv('AUTH_USER');
		}
		
		/** ----------------------------------
		/**  Find Password, Please
		/** ----------------------------------*/
		
		if ( ! empty($_SERVER) && isset($_SERVER['PHP_AUTH_PW']))
		{
			$pass = $_SERVER['PHP_AUTH_PW'];
		}
		elseif ( ! empty($_ENV) && isset($_ENV['REMOTE_PASSWORD']))
		{
			$pass = $_ENV['REMOTE_PASSWORD'];
		}
		elseif ( @getenv('REMOTE_PASSWORD'))
		{
			$pass = getenv('REMOTE_PASSWORD');
		}
		elseif ( ! empty($_ENV) && isset($_ENV['AUTH_PASSWORD']))
		{
			$pass = $_ENV['AUTH_PASSWORD'];
		}
		elseif ( @getenv('AUTH_PASSWORD'))
		{
			$pass = getenv('AUTH_PASSWORD');
		}
		
		/** ----------------------------------
		/**  Authentication for IIS
		/** ----------------------------------*/
		
		if ( ! isset ($user) OR ! isset($pass) OR (empty($user) && empty($pass)))
		{
			if ( isset($_SERVER['HTTP_AUTHORIZATION']) && substr($_SERVER['HTTP_AUTHORIZATION'], 0, 6) == 'Basic ')
			{
				list($user, $pass) = explode(':', base64_decode(substr($HTTP_AUTHORIZATION, 6)));
			}
			elseif ( ! empty($_ENV) && isset($_ENV['HTTP_AUTHORIZATION']) && substr($_ENV['HTTP_AUTHORIZATION'], 0, 6) == 'Basic ')
			{
				list($user, $pass) = explode(':', base64_decode(substr($_ENV['HTTP_AUTHORIZATION'], 6)));
			}
			elseif (@getenv('HTTP_AUTHORIZATION') && substr(getenv('HTTP_AUTHORIZATION'), 0, 6) == 'Basic ')
			{
				list($user, $pass) = explode(':', base64_decode(substr(getenv('HTTP_AUTHORIZATION'), 6)));
			}
		}
		
		/** ----------------------------------
		/**  Authentication for FastCGI
		/** ----------------------------------*/
		
		if ( ! isset ($user) OR ! isset($pass) OR (empty($user) && empty($pass)))
		{	
			if ( ! empty($_ENV) && isset($_ENV['Authorization']) && substr($_ENV['Authorization'], 0, 6) == 'Basic ')
			{
				list($user, $pass) = explode(':', base64_decode(substr($_ENV['Authorization'], 6)));
			}
			elseif (@getenv('Authorization') && substr(getenv('Authorization'), 0, 6) == 'Basic ')
			{
				list($user, $pass) = explode(':', base64_decode(substr(getenv('Authorization'), 6)));
			}
		}
		
		if ( ! isset ($user) OR ! isset($pass) OR (empty($user) && empty($pass)))
		{
			return FALSE;
		}
		
		$this->auth_attempt = TRUE;
		
		/** ----------------------------------------
		/**  Check password lockout status
		/** ----------------------------------------*/
		
		if ($this->EE->session->check_password_lockout($user) === TRUE)
		{
			return FALSE;	
		}
		
		/** ----------------------------------
		/**  Validate Username and Password
		/** ----------------------------------*/
		
		$query = $this->EE->db->query("SELECT password, group_id FROM exp_members WHERE username = '".$this->EE->db->escape_str($user)."'");
		
		if ($query->num_rows() == 0)
		{
			$this->EE->session->save_password_lockout($user);
			return FALSE;
		}
		
		// make sure Super Admins are always allowed
		if ( ! in_array($allowed_groups, 1))
		{
			$allowed_groups[] = 1;
		}
		
		if ( ! in_array($query->row('group_id') , $allowed_groups))
		{
			return FALSE;
		}
		
		$this->EE->load->helper('security');
		if ($query->row('password')  == do_hash($pass))
		{
			return TRUE;
		}

		// just in case it's still in the db as MD5 from an old pMachine or EE 1.x install
		if ($query->row('password')  == do_hash($pass, 'md5'))
		{
			return TRUE;
		}
		else
		{
			$this->EE->session->save_password_lockout($user);
			
			return FALSE;
		}
	}


/*
                                         ,r5GABBBHBM##########Bhi:.                                         
                                       :2#@#MBM#@@@@@@@@@@@@@@@@@Hr                                         
                                    .;2#@@@@BAH#@@@@#MMM#@@@@@@@@@@3;.                                      
                                   :5#@@@#HHBMBHAG925issiXAMBAAM@@@@@MS,                                    
                                 .;9@@@#MHBM#MAXir;;;;;;;;riX&HBM@@@@@@A;.                                  
                                ,5#@@#HAHBBA3ir;::::::;;::;rs2A#@@@@@@@@@5,                                 
                               :9@@@@@MBAXSs;,.,,:::::::::;;;;s9M@@##@@@@#s.                                
                              ,S#@@@@M&Xs;:,,,,,::::::::::::;;;ri2&BMM#@@@&r.                               
                             .sH@@@Bh5r;::,,,:::,,::::,;;;;;;;;;;ri9B#B#@@@Ar.                              
                             ;h@@@#hi;::::::::::,,:;;:;;;;;;;;;;rrri9B##@@@@X;.                             
                            :2#@@@MXs;::::;;;::::;;;;;r;::::;rrsssrrihM@@@@@H2:                             
                           .rH@@@@A2srrr;;;;;;;;;;r;;::,,,:;s5XXX332is5&#@@@#&r                             
                           .sB@@@#A2issrrrrr;;;;;;;:,..,;s222SS2GBM&XS2&#@@@#BS,                            
                           ,S#@@@MA35isrrr;;::::,,,...:ihB&S;:;s529AHHA&B@@#HA9s,                           
                           :X@@@#BAh2Ssr;::,,,..,.  .:s233i;:;s22S59&Xrr3#@HX2X5:                           
                           :3@@@@#B&XSisssiSir;;:..:risrri5X252X25525r;;5B#Gis5S:                           
                           ,iB@@@@@MAGhGAM#Mh55XX22hHAS;:;i25issrrsisr;;s&BGSsSS:                           
                            :2#@@@@#@@#A9XG&S:.r#@@AX33s;;;;rrssrriir;;;s3BMGiii:                           
                         .  .rH@@@@@#BAh33A#Hi:;2&2r;s22SisssiSs;,,:;rsrs2ABAX22;                           
                         .   :SH#@@@MA&AH&922SrrSX2s;ri22XSr:,,,. .,:rsii2X2Ssii:                           
                               ;&@@@@H9X25irrssi29Xs:,,;rrrr:...,,:::;rrsS555SSi:                           
                              .r&@@@#AXSiS2X2SS222i;. .,:;;rr;;;rrr;;;;;rsS2GAA2;                           
                         ,::.   .s#@MG3XX5ir;::riis;::;rsrrssr;;;rrrrr;;;;ri3A&S:                           
                       .;9A3i;.  .9@MhSis;:....:sS5Sisssrrrrrr;;rrr;;;::;;;s2&hi:                           
                       .rh3rrsSSs;:sGBGSr;::::::rsiSSs;:,,:;rrsiSir;:,:;;;;r2ABA3Sr,                        
                        ;3X;,:rXA2;r3BB9irr;rrrrrrrrrr;:::;;;;;s22i;:,:;rrrsXMMG3h9S;.                      
                        ;B@Gr..:r2&A2;:sXSsrrrsissrrrrrr;:.,,,:rssr:::;;rrrSGMAirsX&&Xi;:,                  
                       ;h@@@2:..,sA#3, r&3Ss;;;sS225s;:,,..,:;iis;:;;;;rssi2ABAXr;sG##A93Xi;.               
                      ;#@@H2r;,.,rXH&srhMXss;:,:rS9h2s;;rssrrssr;;;;rrrrssi3AHMBXrrS&G25G#@#hi;,.           
                     ;A@@9;,,::,.;X3r.,X#&ir;;:,,:r2XXXX2ir;:,,,:;rrrrsrsi2332hBM&2S5Srr5H@@@#BG2i;,.       
                    ;h#Ai;,,:;::;9#5   ;M@#5rsr;:::rsis;::,,,,,:;siissisi2h3Ssi9B#A2Sir;;iX&AM###MHh2ir;:.:,
                  .r9AS: ..,;;;sh@@S    ;A@#A2srrrrr;;;;:;;;;;rrrrssiiS52992iii533SrriSs;ri2XGG33h93GAHHH39r
                 :SAAi,...,:;;;5B@Bs      i@@@AX5Sisr;rrsrr;;;;rrrsiS52XX2SisiSSisrrrS92ss2X392sri522223GGAX
               .rH@@Xr:::::;;;:;XHhs,     :B@@@@H9225Sissrr;;:,,:;si52X932issrsssrrrssr;:;i55ir:;SAH9r:;r5G9
             .;G@@@&ir;::,,,,,...,rXh2r,  ,rS2&#@@@MBA2i;::::::;ri2333X2Sssrr;;rsirrsr::;rsiSsr;iXh2i:.,risi
.          ,sH@@@@Gir;r;;::,,..   .:;rrrsis;;rsXA#@@@@MAXir;riXhGAA&322Ssrrr;;;;;;;rss;::;;rsiS525r:,,,:srrr
ir;:.,,,;iB@@@@@BXs;;rrr;;:::,,......,::;rrssssrrrssi2&@@@#MMMBAG32Sissrrrrr;;:::;rsir,.,:;s52225ir:,:;rr:;;
A@@@@#BhXG##Air;;;;;rrr;;::::::,,,,,.....:ri5Ssr;:;rrr;;i3H##AXisrrrrrrrrsrr::,.,;is;. .;SX32irrrrr;;;sis;rr
#@@@@@@@@@Ar,.,::;rrrrr;;::::::,,,::,,..:r29Xs;::r2AH3r::;i2992Siiiiisrrrr;,,,,,:;s;,  :5GhS;,,:ris;,,;is;;;
#MM##@@@@As,.,;;rrrr;;;;;;;:::::,,,,,:,:;s3G2;,:r59&hS;;rii;r5h92SSSsrrssr;,.:;;;;:,..;iXXi;,,;5G9S;..;ii;;:
BAAAAM#HXr;:,:rsrrrr;;;;;::,:::::,,,,,,,:;s2Sr;rsiS25r:.:S5;:rGBG5SSrrrssr:,,;rr;::,,;i5isr::;iXXi;,,:rss:;;
BHBHAABAXr:,,;;rrrrr;;::::::;::::::,,,,,,,;S2isr;;;i2S;..rs;:;G@M325iiisr;;;ris;:;;;;;;;;rsr;:rsr:,:;;rsr;sr
BBMMHAAAhi:,,;;r;;;;::::;;;;:::::::::,,..,rXXir;;;rsiissrrr;,,sMMh3hG2r;;;;riSr;;r;:,,::;sSs;:;sirrrr;rrrrSi
BAHMMHA&3i;::;;;rrr;;;;;;;;:::;;::::::;;ri525ir;;rSSr;;i2iss,  sAM&2Ss;;;;;;rsr;rr:. .:;;rsr::rS2SSir;;;;;ss
MHHM##MHhS;:::;;;rrrrrr;;;;;;;:::::,,:;ri25iiSs;:;S5;,.;iiii;  ,G@Hs::rr;;;;rr;;;r,  ,:;rssr;;rSSiss;:;;;:;r
#MMBMM##H2r:,:;;;rrr;r;;;;;;;;;;;;;::,,,:r522Ss;:;ii;,,;rrrir.  r32r,:;r;;;rrr;:;;,  ,:rriir;:;sirrr;::;r:;;
MBBHAAB#MGs::::;;;;;;;rrr;;;;:::::::::,.:s9&Xsrrrris;:;rrrrsr:   ,;;::;;;;;rsr;:;;, .,:;rssr:,:riiis;::;r;;:
MB##MHHM@#ASr::;;;;;;;;rrr;;;;::::::;;;;s23XSsrr;ris;;;rsrrss;. .:;;;;;;;;;rrr;:;;:..,:;riir;:;sSisr;;:;ir;:
#######@@@@@A5sssssrr;;rrrrrrr;;;r;;;rs522SiSir::;iir;;iSsrrr;,.;i;::;;;;;;;;;;:;r:..,;rrssr;:;iir::;;:;irr;
3sr;rssi2&HBH&39&M#@Mh2S5SSisrrrrr;;;;rS39X222r,,rX9Srrri525i;::;r:,;rr;;;r;;;;;rr:,,:;rrsrr;:;sr:,:rs;;srr;
.            ,rS3B@@@@A325iissrrr;;;;;sXHMA9XS;,:s9A3Sr;r592s;;;;::riSr;:rsrrr;;rr:,,:;rsiis;;rir,.:rir;srsr
                    ,,,,............,rh@@BX5is;:;iX35sr;;siisr;;,,;i2S;,.;rrr;;;r;,..:;riSir;;sir,.,rir;rrss
                                    .:iA#Bh2Sirrri22SisssrsSSir;;;sSSs;::;ssrrrrsr;::;rsiSSsrsSSs;::rsssssiS
*/	

}
// END CLASS

/* End of file mod.forum_core.php */
/* Location: ./system/expressionengine/modules/forum/mod.forum_core.php */