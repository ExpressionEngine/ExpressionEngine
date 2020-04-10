<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Discussion Forum Module "Core" class
 */
class Forum_Core extends Forum {

	/**
	 * Construct
	 */
	public function __construct()
	{
		ee()->load->library('template', NULL, 'TMPL');
	}

	/**
	 * Display forum handler
	 *
	 * @param 	string
	 */
	public function display_forum($function = '')
	{

		// Determine the function call
		// The function is based on the 2nd segment of the URI
		if ($function == '')
		{
			if ( ! ee()->uri->segment(2 + $this->seg_addition))
			{
				$function = 'forum_homepage';
			}
			else
			{
				$function = ee()->uri->segment(2 + $this->seg_addition);
			}
		}

		// Remap function if needed
		// In certain cases we may want different URI function names
		// to share common methods
		$remap = array(
			'viewpost'            => 'view_post_redirect',
			'viewreply'           => 'view_post_redirect',
			'viewcategory'        => 'category_page',
			'viewforum'           => 'topic_page',
			'viewthread'          => 'thread_page',
			'viewannounce'        => 'announcement_page',
			'newtopic'            => 'new_topic_page',
			'newreply'            => 'new_reply_page',
			'edittopic'           => 'edit_topic_page',
			'editreply'           => 'edit_reply_page',
			'deletetopic'         => 'delete_post_page',
			'deletereply'         => 'delete_post_page',
			'movetopic'           => 'move_topic_page',
			'movereply'           => 'move_reply_page',
			'quotetopic'          => 'new_reply_page',
			'quotereply'          => 'new_reply_page',
			'reporttopic'         => 'report_page',
			'reportreply'         => 'report_page',
			'merge'               => 'merge_page',
			'split'               => 'split_page',
			'smileys'             => 'emoticon_page',
			'search'              => 'advanced_search_page',
			'member_search'       => 'member_search',
			'new_topic_search'    => 'new_topic_search',
			'active_topic_search' => 'active_topic_search',
			'view_pending_topics' => 'view_pending_topics',
			'search_results'      => 'search_results_page',
			'search_thread'       => 'search_thread_page',
			'ban_member'          => 'ban_member_form',
			'do_ban_member'       => 'do_ban_member',
			'rss'                 => '_feed_builder',
			'atom'                => '_feed_builder'
		);

		if (isset($remap[$function]))
		{
			$function = $remap[$function];
		}


		// The output is based on whether we are using the main template parser
		// or not. If the config.php file contains a forum "triggering" word
		// we'll send the output directly to the output class.  Otherwise, the
		// output is sent to the template class like normal.  The exception to
		// this is when action requests are processed

		if ($this->use_trigger() OR ee()->input->get_post('ACT') !== FALSE)
		{
			ee()->output->set_output(
				ee()->functions->insert_action_ids(
					ee()->functions->add_form_security_hash(
						$this->_final_prep(
							$this->_include_recursive($function)
						)
					)
				)
			);
		}
		else
		{
			ee()->TMPL->disable_caching = TRUE;

			if (stristr(ee()->TMPL->tagproper, 'exp:forum:') === FALSE)
			{
				$this->return_data = ee()->TMPL->simple_conditionals($this->_include_recursive($function), ee()->config->_global_vars);

				// Parse Snippets
				foreach (ee()->config->_global_vars as $key => $val)
				{
					$this->return_data = str_replace(LD.$key.RD, $val, $this->return_data);
				}

				// Parse Global Variables
				foreach (ee()->TMPL->global_vars as $key => $val)
				{
					$this->return_data = str_replace(LD.$key.RD, $val, $this->return_data);
				}

				$this->return_data = $this->_final_prep($this->return_data);
			}
		}
	}

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

		ee()->db->select('group_id, group_title');
		$g_query = ee()->db->get_where('member_groups',
											array('site_id' => ee()->config->item('site_id'))
										);

		foreach ($g_query->result_array() as $row)
		{
			$groups[$row['group_id']] = $row['group_title'];
		}

		ee()->db->select('mod_forum_id, mod_member_id, mod_group_id, mod_member_name');
		$m_query = ee()->db->get_where('forum_moderators',
											array('board_id' => $this->fetch_pref('board_id'))
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
		if (isset(ee()->TMPL) && is_object(ee()->TMPL) && ($forums = ee()->TMPL->fetch_param('forums')) != FALSE)
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

		ee()->db->select('forum_id, forum_name, forum_status, forum_description,
								forum_parent, forum_enable_rss, forum_permissions,
								forum_is_cat, forum_max_post_chars, forum_allow_img_urls,
								forum_notify_emails, forum_notify_emails_topics,
							 	forum_notify_moderators_topics, forum_notify_moderators_replies');
		ee()->db->where('forum_id', $id);
		ee()->db->where('board_id', $this->fetch_pref('board_id'));
		$query = ee()->db->get('forums');

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

		$items = array(
				'forum_id', 'forum_status', 'forum_name', 'forum_parent',
				'forum_description', 'forum_permissions', 'forum_enable_rss',
				'forum_is_cat', 'forum_notify_emails', 'forum_notify_emails_topics',
				'forum_notify_moderators_topics', 'forum_notify_moderators_replies',
				'forum_posts_perpage', 'forum_allow_img_urls', 'forum_max_post_chars',
				'topic_id', 'author_id', 'status', 'sticky', 'announcement',
				'title', 'body', 'topic_date', 'screen_name');

		ee()->db->select('f.forum_id, f.forum_status, f.forum_name, f.forum_parent,
								f.forum_description, f.forum_permissions, f.forum_enable_rss,
								f.forum_is_cat, f.forum_notify_emails, f.forum_notify_emails_topics,
								f.forum_notify_moderators_topics, f.forum_notify_moderators_replies,
								f.forum_allow_img_urls, f.forum_posts_perpage, f.forum_max_post_chars,
								t.author_id, t.status, t.sticky, t.announcement, t.title, t.body,
								t.topic_id, t.topic_date, m.screen_name');

		ee()->db->from(array('forums f', 'forum_topics t', 'members m'));
		ee()->db->where('f.forum_id', 't.forum_id', FALSE);
		ee()->db->where('t.author_id', 'm.member_id', FALSE);
		ee()->db->where('t.topic_id', $id);
		ee()->db->where('t.board_id', $this->fetch_pref('board_id'));
		$query = ee()->db->get();

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

	/**
	 * Fetch Post Meta Data
	 *
	 * This function is identical to the one above except
	 * that the query is run based on a POST ID rather than a topic or forum ID
	 */
	function _fetch_post_metadata($id)
	{
		if (isset($this->post_metadata[$id]))
		{
			return $this->post_metadata;
		}

		$items = array('forum_id', 'forum_status', 'forum_name', 'forum_parent', 'forum_description', 'forum_permissions', 'forum_enable_rss', 'forum_is_cat', 'forum_posts_perpage', 'forum_post_order', 'forum_max_post_chars', 'forum_allow_img_urls', 'author_id', 'title', 'status', 'topic_id', 'post_id', 'body', 'post_date', 'screen_name');

		ee()->db->select('f.forum_id, f.forum_status, f.forum_name, f.forum_parent,
								f.forum_description, f.forum_permissions, f.forum_enable_rss,
								f.forum_is_cat, f.forum_posts_perpage, f.forum_post_order,
								f.forum_max_post_chars, f.forum_allow_img_urls, t.title,
								t.status, p.author_id, p.topic_id, p.post_id, p.body,
								p.post_date, m.screen_name');
		ee()->db->from(array('forums f', 'forum_topics t', 'forum_posts p', 'members m'));
		ee()->db->where('f.forum_id = p.forum_id', '', FALSE);
		ee()->db->where('p.topic_id = t.topic_id', '', FALSE);
		ee()->db->where('p.author_id = m.member_id', '', FALSE);
		ee()->db->where('p.post_id', (int) $id);
		ee()->db->where('p.board_id', $this->fetch_pref('board_id'));
		$query = ee()->db->get();

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

	/**
	 * Topic Tracker
	 */
	function _fetch_read_topics($new_id = FALSE)
	{
			// If the person is not logged in we use the cookie version
			if (ee()->session->userdata('member_id') == 0)
			{
				return $this->_fetch_read_topics_cookie($new_id);
			}

			$query = ee()->db->query("SELECT topics FROM exp_forum_read_topics
								 WHERE member_id = '".ee()->db->escape_str(ee()->session->userdata('member_id'))."'
								 AND board_id = '".$this->fetch_pref('board_id')."'");

			// If there isn't a row yet we'll fetch the cookie version
			if ($query->num_rows() == 0)
			{
				$this->read_topics_exist = FALSE;
				$read_topics = $this->_fetch_read_topics_cookie($new_id);

				if (count($read_topics) > 0)
				{
					$this->read_topics_exist = TRUE;
					ee()->db->query("INSERT INTO exp_forum_read_topics (member_id, board_id, topics, last_visit)
							VALUES ('".ee()->db->escape_str(ee()->session->userdata('member_id'))."', '".$this->fetch_pref('board_id')."', '".serialize($read_topics)."', '".ee()->localize->now."')");
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
			return (count($topics) === 0) ? array() : $topics;
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
		$new[$new_id] = ee()->localize->now+3;

		return $new;
	}

	/**
	 * Fetch Tracker Cookie
	 */
	function _fetch_read_topics_cookie($new_id = FALSE)
	{
		if ( ! ee()->input->cookie('forum_topics'))
		{
			return array();
		}

		$cookie = ee()->input->cookie('forum_topics');
		$length = strlen($cookie);
		$topics = @json_decode(stripslashes($cookie), TRUE);

		if ( ! is_array($topics))
		{
			return array();
		}

		if ($new_id === FALSE)
		{
			return (count($topics) === 0) ? array() : $topics;
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
		$new[$new_id] = ee()->localize->now+3;

		return $new;
	}

	/**
	 * Final Quote Parsing
	 */
	function _quote_decode($str)
	{
		$xtemplate = $this->load_element('quoted_author');

		if (stristr($str, '<blockquote') === FALSE OR trim($xtemplate) == '')
		{
			return $str;
		}

		if (preg_match_all("/\<blockquote\s+(.*?)\>/", $str, $matches))
		{
			for ($i=0, $s = count($matches['0']); $i < $s; ++$i)
			{
				// author, date parameters
				// We do the str_replace because of the XHTML Typography that converts quotes
				$tagparams = ee('Variables/Parser')->parseTagParameters(trim(str_replace(array('&#8220;', '&#8221;'), '"', $matches['1'][$i])));

				$author	= ( ! isset($tagparams['author'])) ? '' : $tagparams['author'];
				$time	= ( ! isset($tagparams['date'])) ? '' : $tagparams['date'];

				$template = str_replace('{quote_author}', $author, $xtemplate);

				$template = ee()->TMPL->parse_date_variables($template, array('quote_date' => $time));

				$str = str_replace($matches['0'][$i], '<blockquote>'.$template, $str);
			}
		}

		return $str;
	}

	/**
	 * Is the user authorized for the page?
	 */
	function _is_authorized()
	{
		if ($this->current_request == '')
		{
			return TRUE;
		}

		// These are exceptions to the normal permissions checks
		$exceptions = array(
				'member', 'smileys', 'search', 'member_search', 'new_topic_search',
				'active_topic_search', 'view_pending_topics', 'search_results',
				'search_thread', 'ban_member', 'do_ban_member', 'spellcheck',
				'spellcheck_iframe', 'rss', 'atom', 'ignore_member', 'do_ignore_member',
				'mark_all_read'
			);

		// Is the member area trigger changed?

		if (ee()->config->item('profile_trigger') != 'member' && in_array('member', $exceptions))
		{
			unset($exceptions[array_search('member', $exceptions)]);
			$exceptions[] = ee()->config->item('profile_trigger');
		}

		if (in_array($this->current_request, $exceptions))
		{
			return TRUE;
		}

		// Is this a subscription request?
		if ($this->current_request == 'subscribe' OR $this->current_request == 'unsubscribe')
		{
			if (ee()->session->userdata('member_id') == 0)
			{
				return $this->trigger_error();
			}

			return TRUE;
		}

		// Fetch the Forums Prefs
		// Depending on what the "current_request" variable contains we'll run the query a little differnt.

		$allowed = array(
				'editreply', 'deletereply', 'quotereply', 'viewpost',
				'viewreply', 'reportreply', 'movereply'
		);

		if (in_array($this->current_request, $allowed))
		{
			if (FALSE === ($meta = $this->_fetch_post_metadata($this->current_id)))
			{
				return $this->trigger_error();
			}
		}
		elseif (in_array($this->current_request, array('viewcategory', 'viewforum',  'newtopic')))
		{
			if (FALSE === ($meta = $this->_fetch_forum_metadata($this->current_id)))
			{
				return $this->trigger_error();
			}
		}
		elseif (in_array($this->current_request, array('viewthread', 'viewannounce', 'newreply', 'quotetopic', 'edittopic', 'reporttopic', 'deletetopic', 'movetopic', 'merge', 'do_merge', 'split', 'do_split')))
		{
			if (FALSE === ($meta = $this->_fetch_topic_metadata($this->current_id)))
			{
				return $this->trigger_error();
			}
		}

		// Unserialize the permissions
		$perms = unserialize(stripslashes($meta[$this->current_id]['forum_permissions']));

		// Can the forum be viewed?
		if ( ! $this->_permission('can_view_forum', $perms))
		{
			return $this->trigger_error('can_not_view_forum');
		}

		// Can hidden forum be viewed?
		// c = hidden
		if ( ! $this->_permission('can_view_hidden', $perms) AND $meta[$this->current_id]['forum_status'] == 'c')
		{
			return $this->trigger_error('can_not_view_forum');
		}

		// Is user trying to post in a read-only forum?
		// a = read only
		if ($meta[$this->current_id]['forum_status'] == 'a' AND in_array($this->current_request, array('newtopic', 'newreply', 'edittopic', 'editreply', 'quotetopic', 'quotereply')))
		{
			return $this->trigger_error('can_not_post_in_forum');
		}

		// Can posts be viewed?
		$pages = array('viewthread', 'newreply', 'edittopic', 'editreply');

		if ( ! $this->_permission('can_view_topics', $perms) AND in_array($this->current_request, $pages))
		{
			return $this->trigger_error('can_not_view_posts');
		}

		// Can the user post messages?
		if ( ! $this->_permission('can_post_topics', $perms) AND in_array($this->current_request, array('newtopic', 'edittopic')))
		{
			return $this->trigger_error('can_not_post_in_forum');
		}

		if ( ! $this->_permission('can_post_reply', $perms) AND in_array($this->current_request, array('newreply', 'editreply', 'quotereply', 'quotetopic')))
		{
			return $this->trigger_error('can_not_post_in_forum');
		}

		return TRUE;	// User is Authorized!!
	}

	/**
	 * Error page
	 */
	function trigger_error($msg = 'not_authorized')
	{
		$this->return_data = '';
		$this->error_message = lang($msg);
		$this->set_page_title(lang('error'));

		// set the current id to 'error' so breadcrumbs and other items are obfuscated
		$this->return_override = $this->current_id;
		$this->current_id = 'error';
		return $this->display_forum('error_page');
	}

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

		$query = ee()->db->select('member_id')->get_where('members',
													array('group_id' => (int) 1));

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$this->admin_members[] = $row['member_id'];
			}
		}

		$query = ee()->db->select('admin_group_id, admin_member_id')
							  ->get_where('forum_administrators',
							  			  array(
							  			  	'board_id' => (int) $this->fetch_pref('board_id')
								  		));

		if ($query->num_rows() === 0 AND count($this->admin_members) == 0)
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

	/**
	 * Is this user an admin?
	 */
	function _is_admin($member_id = 0, $group_id = 0)
	{
		if ($member_id == 0)
		{
			$member_id = ee()->session->userdata('member_id');

			if ($member_id == 0)
			{
				return FALSE;
			}

			if ($group_id == 0)
			{
				$group_id = ee()->session->userdata('group_id');
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
			$query = ee()->db->select('group_id')
								  ->get_where('members',
								  		array('member_id' => (int) $member_id));

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

		$groups = explode('|', $permission_array[$item]);
		return in_array(ee()->session->userdata('group_id'), $groups);
	}

	/**
	 * Fetch moderator permission
	 *
	 * We cache these for reuse
	 */
	function _mod_permission($item, $forum_id)
	{
		if (ee()->session->userdata('member_id') == 0)
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

		// Check the cache for the permission
		$group_id = ee()->session->userdata('group_id');
		$member_id = ee()->session->userdata('member_id');

		if (isset($this->current_moderator[$forum_id][$group_id][$item]))
		{
			return ($this->current_moderator[$forum_id][$group_id][$item] == 'y') ? TRUE : FALSE;
		}
		elseif (isset($this->current_moderator[$forum_id][$member_id][$item]))
		{
			return ($this->current_moderator[$forum_id][$member_id][$item] == 'y') ? TRUE : FALSE;
		}

		// Fetch the permissions from the DB
		$query = ee()->db->query("SELECT * FROM exp_forum_moderators WHERE mod_forum_id = '{$forum_id}' AND (mod_member_id = '{$member_id}' OR mod_group_id = '{$group_id}')");

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
		if ($this->fetch_pref('board_post_order') == 'd')
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

	/**
	 * Update stats
	 */
	function _update_post_stats($forum_id)
	{
		$data = array(
						'forum_last_post_id' 		=> 0,
						'forum_last_post_type'		=> 'p',
						'forum_last_post_title'		=> '',
						'forum_last_post_date'		=> 0,
						'forum_last_post_author_id'	=> 0,
						'forum_last_post_author'	=> ''
					);

		ee()->db->select('COUNT(*) as count');
		$query = ee()->db->get_where('forum_topics', array('forum_id' => $forum_id));
		$data['forum_total_topics'] = $query->row('count') ;

		ee()->db->select('COUNT(*) as count');
		$query = ee()->db->get_where('forum_posts', array('forum_id' => $forum_id));
		$data['forum_total_posts'] = $query->row('count') ;

		ee()->db->select('topic_id, title, topic_date, last_post_date,
								last_post_author_id, screen_name, announcement');
		ee()->db->from(array('forum_topics', 'members'));
		ee()->db->where('member_id', 'last_post_author_id', FALSE);
		ee()->db->where('forum_id', $forum_id);
		ee()->db->order_by('last_post_date', 'DESC');
		ee()->db->limit(1);
		$query = ee()->db->get();

		if ($query->num_rows() > 0)
		{
			$data['forum_last_post_id'] 		= $query->row('topic_id');
			$data['forum_last_post_type'] 		= ($query->row('announcement')  == 'n') ? 'p' : 'a';
			$data['forum_last_post_title'] 		= $query->row('title');
			$data['forum_last_post_date'] 		= $query->row('topic_date');
			$data['forum_last_post_author_id']	= $query->row('last_post_author_id');
			$data['forum_last_post_author']		= $query->row('screen_name');
		}

		ee()->db->select('post_date, author_id, screen_name');
		ee()->db->from(array('forum_posts', 'members'));
		ee()->db->where('member_id', 'author_id', FALSE);
		ee()->db->where('forum_id', $forum_id);
		ee()->db->order_by('post_date', 'DESC');
		ee()->db->limit(1);
		$query = ee()->db->get();

		if ($query->num_rows() > 0)
		{
			if ($query->row('post_date')  > $data['forum_last_post_date'])
			{
				$data['forum_last_post_date'] 		= $query->row('post_date') ;
				$data['forum_last_post_author_id']	= $query->row('author_id') ;
				$data['forum_last_post_author']		= $query->row('screen_name') ;
			}
		}

		ee()->db->query(ee()->db->update_string('exp_forums', $data, "forum_id='{$forum_id}'"));
		unset($data);

		// Update member stats
		ee()->db->select('COUNT(*) as count');
		$query = ee()->db->get_where('forum_topics',
											array('author_id' => ee()->session->userdata('member_id')));
		$total_topics = $query->row('count') ;

		ee()->db->select('COUNT(*) as count');
		$query = ee()->db->get_where('forum_posts',
											array('author_id' => ee()->session->userdata('member_id')));
		$total_posts = $query->row('count') ;

		$d = array(
					'total_forum_topics'	=> $total_topics,
					'total_forum_posts'		=> $total_posts
				);
		ee()->db->where('member_id', ee()->session->userdata('member_id'));
		ee()->db->update('members', $d);
	}

	/**
	 * update global forum stats
	 */
	function _update_global_stats()
	{
		$total_topics = ee()->db->count_all('forum_topics');
		$total_posts  = ee()->db->count_all('forum_posts');

		ee()->db->update('stats', array(
										'total_forum_topics'	=> $total_topics,
										'total_forum_posts'		=> $total_posts));
	}

	/**
	 * Update topic stats
	 */
	function _update_topic_stats($topic_id)
	{
		// Update the thread count and last post date
		ee()->db->select('COUNT(*) as count, MAX(post_date) as last_post');
		$query = ee()->db->get_where('forum_posts', array('topic_id' => $topic_id));

		$this->thread_post_total = $query->row('count') ;
		$total = ($query->row('count')  + 1);

		if ($query->row('count')  > 0)
		{
			$d = array(
					'last_post_date'	=> $query->row('last_post'),
					'thread_total'		=> $total
				);

			ee()->db->where('topic_id', $topic_id);
			ee()->db->update('forum_topics', $d);
		}
		else
		{
			ee()->db->set('last_post_date', 'topic_date', FALSE);
			ee()->db->set('thread_total', $total);
			ee()->db->where('topic_id', $topic_id);
			ee()->db->update('forum_topics');
		}

		// Update the resulting last post author and last post id
		if ($total > 1)
		{
			ee()->db->select('post_id, author_id');
			ee()->db->where('topic_id', $topic_id);
			ee()->db->order_by('post_date', 'DESC');
			ee()->db->limit(1);
			$query = ee()->db->get('forum_posts');

			$d = array(
					'last_post_author_id'	=> $query->row('author_id'),
					'last_post_id'			=> $query->row('post_id')
				);

			ee()->db->where('topic_id', $topic_id);
			ee()->db->update('forum_topics', $d);
		}
		else
		{
			ee()->db->set('last_post_author_id', 'author_id', FALSE);
			ee()->db->set('last_post_id', 0);
			ee()->db->where('topic_id', $topic_id);
			ee()->db->update('forum_topics');
		}
	}

	/**
	 * update member stats
	 */
	function _update_member_stats($member_ids = array())
	{
		if ( ! is_array($member_ids))
		{
			$member_ids = [$member_ids];
		}

		foreach ($member_ids as $member_id)
		{
			ee()->db->select('COUNT(*) as count');
			$res = ee()->db->get_where('forum_topics', array('author_id' => $member_id));
			$total_forum_topics = $res->row('count');

			ee()->db->select('COUNT(*) as count');
			$res = ee()->db->get_where('forum_posts', array('author_id' => $member_id));
			$total_forum_posts = $res->row('count');

			ee()->db->query(ee()->db->update_string('exp_members', array('total_forum_topics' => $total_forum_topics, 'total_forum_posts' => $total_forum_posts), "member_id = '{$member_id}'"));
		}
	}

	/**
	 * Feed Builder
	 */
	function _feed_builder()
	{
		// Grab them prefs
		ee()->db->select('forum_id, forum_is_cat, forum_status,
							   forum_permissions, forum_enable_rss,
							   forum_use_http_auth')
					 ->where('board_id', $this->fetch_pref('board_id'));

		// Are there specific forums being requested?
		$feed_id = ee()->uri->segment(3 + $this->seg_addition);

		if ($feed_id !== FALSE)
		{
			// Trim leading/traiing underscores
			$feed_id = preg_replace("|^_*(.*?)_*$|", "\\1", $feed_id);

			if ( ! preg_match("/^[0-9_]+$/i", $feed_id))
			{
				ee()->db->_reset_select();
				return $this->trigger_error('no_feed_specified');
			}

			$ids = explode('_', $feed_id);
			$ids = array_map('intval', $ids);

			ee()->db->where_in('forum_id', $ids);
		}

		$query = ee()->db->get('forums');

		if ($query->num_rows() == 0)
		{
			return $this->trigger_error('no_feed_specified');
		}

		$enable_cluster = TRUE;
		$ids = array();

		foreach ($query->result_array() as $row)
		{
			// Are feeds enabled for this forum?
			if ($row['forum_enable_rss'] == 'n')
			{
				continue;
			}

			// Unserialize the permissions for this forum
			$row['forum_permissions'] = unserialize(stripslashes($row['forum_permissions']));

			$can_view_forum = explode('|', substr($row['forum_permissions']['can_view_forum'], 1, -1));
			$can_view_hidden = explode('|', substr($row['forum_permissions']['can_view_hidden'], 1, -1));

			// Can the user view the category cluster?
			// If the cluster is not viewable we need to suppress all forums contained within it

			if ($row['forum_is_cat'] == 'y')
			{
				$enable_cluster = ( ! $this->_permission('can_view_forum', $row['forum_permissions'])) ? FALSE : TRUE;
			}

			if ($enable_cluster === FALSE)
			{
				continue;
			}

			// Can the user view the current forum?
			if ($row['forum_status'] != 'c' &&
				! $this->_permission('can_view_forum', $row['forum_permissions']))
			{
				if ($row['forum_use_http_auth'] == 'y')
				{
					ee()->load->library('auth');
					ee()->auth->authenticate_http_basic($can_view_forum,
															 $this->realm);
				}
			}

			// Can the user view the current hidden forum?
			if ($row['forum_status'] == 'c' &&
				! $this->_permission('can_view_hidden', $row['forum_permissions']))
			{
				if ($row['forum_is_cat'] == 'y')
				{
					$enable_cluster = FALSE;
				}

				if ($row['forum_use_http_auth'] == 'y')
				{
					ee()->load->library('auth');
					ee()->auth->authenticate_http_basic($can_view_forum,
															 $this->realm);
				}
				else
				{
					continue;
				}
			}

			// Store the ID
			if ($row['forum_is_cat'] == 'n')
			{
				$ids[] = $row['forum_id'];
			}
		}

		// After all that, are there valid IDs?
		if (count($ids) == 0)
		{
			return $this->trigger_error('no_feed_specified');
		}

		$qry = ee()->db->select('t.topic_id, t.author_id, t.title,
				t.body, t.topic_date, t.thread_total,
				t.last_post_author_id,  t.last_post_date,
				t.topic_edit_date, t.parse_smileys,
				f.forum_text_formatting, f.forum_html_formatting,
				f.forum_auto_link_urls, f.forum_allow_img_urls,
				lp.screen_name AS last_post_author,
				m.screen_name AS author, m.email'
			)
			->from('forum_topics t')
			->join('forums f', 'f.forum_id = t.forum_id', 'left')
			->join('members m', 'm.member_id = t.author_id', 'left')
			->join('members lp', 'lp.member_id = t.last_post_author_id', 'left')
			->where('t.announcement', 'n', '', FALSE)
			->where_in('t.forum_id', $ids)
			->or_where_in('t.moved_forum_id', $ids)
			->where('t.board_id', $this->fetch_pref('board_id'))
			->order_by('t.topic_date', "DESC")
			->limit(10)
			->get();

		if ($qry->num_rows() == 0)
		{
			return $this->trigger_error('no_feed_results');
		}

		// Set the output type
		ee()->output->out_type = 'feed';
		ee()->config->core_ini['send_headers'] = 'y';
		ee()->TMPL->template_type = 'feed';

		// Load the requested theme file
		// What RSS type are they requesting?  Can be "rss" or "atom"

		$type = ee()->uri->segment(2+$this->seg_addition);
		$template = $this->load_element($type.'_page');

		// Separate out the "rows" portion of the feed
		$row_chunk = '';
		if (preg_match_all("/{rows}(.*?){\/rows}/s", $template, $matches))
		{
			for ($j = 0; $j < count($matches['0']); $j++)
			{
				$row_chunk = $matches['1'][$j];
				$template = str_replace($matches['0'][$j], '{{row_chunk}}', $template);
			}
		}

		// Relative URL - used by Atom feeds
		$relative = str_replace('http://', '', $this->forum_path());

		if (($x = strpos($relative, '/')) !== FALSE)
		{
			$relative = substr($relative, $x + 1);
		}

		if (substr($relative, -1) == '/')
		{
			$relative = substr($relative, 0, -1);
		}

		// {trimmed_url} - used by Atom feeds
		$base_url = str_replace('http://', '', $this->forum_path());

		$trimmed_url = str_replace(array('http://','www.'), '', $base_url);
		$xe = explode("/", $trimmed_url);
		$trimmed_url = current($xe);

		// Parse Globals
		$template = str_replace(LD.'app_version'.RD, APP_VER, $template);
		$template = str_replace(LD.'version'.RD, APP_VER, $template);
		$template = str_replace(LD.'webmaster_email'.RD, ee()->config->item('webmaster_email'), $template);
		$template = str_replace(LD.'encoding'.RD, ee()->config->item('output_charset'), $template);
		$template = str_replace(LD.'forum_language'.RD, ee()->config->item('xml_lang'), $template);
		$template = str_replace(LD.'forum_url'.RD, $this->forum_path(), $template);
		$template = str_replace(LD.'trimmed_url'.RD, $trimmed_url, $template);
		$template = str_replace(LD.'forum_rss_url'.RD, $this->forum_path($type), $template);
		$template = str_replace(LD.'forum_name'.RD, $this->fetch_pref('board_label'), $template);

		$dates = array(
		        'gmt_date'      => $qry->row('last_post_date'),
		        'gmt_edit_date' => $qry->row('topic_edit_date')
		);
		$template = ee()->TMPL->parse_date_variables($template, $dates, FALSE);

		// {relative_url} - used by Atom feeds
		$relative_url = str_replace('http://', '', $base_url);

		if (($x = strpos($relative_url, "/")) !== FALSE)
		{
			$relative_url = substr($relative_url, $x + 1);
		}

		if (substr($relative_url, -1) == '/')
		{
			$relative_url = substr($relative_url, 0, -1);
		}

		// Cycle through the results
		ee()->load->library('typography');
		ee()->typography->initialize(array(
			'encode_type' => 'noscript'
		));

		$res = '';

		foreach ($qry->result_array() as $row)
		{
			$temp = $row_chunk;


			if ($row['parse_smileys'] == 'y')
			{
				ee()->typography->parse_smileys = TRUE;
			}
			else
			{
				ee()->typography->parse_smileys = FALSE;
			}

			$title = $row['title'];

			if (bool_config_item('enable_censoring'))
			{
				$title = ee('Format')->make('Text', $title)->censor();
			}

			$title = trim($this->_convert_special_chars(ee()->typography->format_characters($title)));

			$body = $this->_quote_decode(ee()->typography->parse_type($row['body'],
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
			$temp = str_replace('{path:view_thread}', $this->forum_path('/viewthread/'.$row['topic_id'].'/'), $temp);
			$temp = str_replace('{trimmed_url}', $trimmed_url, $temp);
			$temp = str_replace('{relative_url}', $relative_url, $temp);

			$dates = array(
			        'gmt_post_date' => $row['topic_date'],
			        'gmt_edit_date' => $row['topic_edit_date']
			);
			$temp = ee()->TMPL->parse_date_variables($temp, $dates, FALSE);

			$res .= $temp;
		}

		// Put the row chunk back
		$template = str_replace('{{row_chunk}}', $res, $template);

		// XML Encode
		if (preg_match_all("/{exp:xml_encode}(.*?){\/exp:xml_encode}/s", $template, $matches))
		{
			// Load the XML Helper
			ee()->load->helper('xml');

			for ($j = 0; $j < count($matches['0']); $j++)
			{
				$template = str_replace($matches['0'][$j],
										str_replace('&nbsp;', '&#160;', xml_convert($matches['1'][$j])),
										$template);
			}
		}

		return $template;
	}

	/**
	 * View Posts Redirect
	 */
	public function view_post_redirect()
	{
		$topic_id = $this->post_metadata[$this->current_id]['topic_id'];
		$post_limit = $this->post_metadata[$this->current_id]['forum_posts_perpage'];
		$post_number = 0;

		// Find out where in the post order the post is
		$query = ee()->db->select('post_id')->where('topic_id', $topic_id)
							  ->order_by('post_date', 'ASC')
							  ->get('forum_posts');

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

		ee()->functions->redirect($this->forum_path('/viewthread/'.$topic_id.'/'.$pag_seg.'/').'#'.$this->current_id);
		exit;
	}

	/**
	 * Main Forum Display
	 */
	public function main_forum_list()
	{
		$return 		= '';
		$first_row		= FALSE;
		$enable_cluster = TRUE;

		// Fetch the Forums
		// Is the display being limited to a particular category?
		if (is_numeric($this->current_id))
		{
			ee()->db->where("(forum_id = '".$this->current_id."' OR forum_parent =  '".$this->current_id."')");
		}

		$qry = ee()->db->where('board_id', $this->fetch_pref('board_id'))
							->order_by('forum_order')
							->get('forums');

		if ($qry->num_rows() == 0 OR $qry->row('forum_is_cat') != 'y')
		{
			return '';
		}

		// Fetch the "read topics" cookie
		// Each time a topic is read, the ID number for that topic
		// is saved in a cookie as a serialized arrry.
		// This array lets us set topics to "read" status.
		// The array is only good durring the length of the current session.

		$read_topics = $this->_fetch_read_topics();

		// Build the forum
		$markers = $this->_fetch_topic_markers();
		$this->_load_moderators();

		ee()->load->library('typography');
		ee()->typography->initialize();
		$enable_cluster = TRUE;

		$not_these	= array();
		$these		= array();

		if (isset(ee()->TMPL) && is_object(ee()->TMPL) && ($forums = ee()->TMPL->fetch_param('forums')) != FALSE)
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

		foreach ($qry->result_array() as $row)
		{
			if (count($not_these) > 0 && in_array($row['forum_id'], $not_these)) continue;
			if (count($these) > 0 &&  ! in_array($row['forum_id'], $these)) continue;

			// Unserialize the permissions for this forum
			$row['forum_permissions'] = unserialize(stripslashes($row['forum_permissions']));

			// Can the user view the category cluster?
			// If the cluster is not viewable we need to suppress all forums contained within it

			if ($row['forum_is_cat'] == 'y')
			{
				$enable_cluster = ( ! $this->_permission('can_view_forum', $row['forum_permissions'])) ? FALSE : TRUE;
			}

			if ($enable_cluster === FALSE)
			{
				continue;
			}

			// Can the user view the current forum?
			if ($row['forum_status'] != 'c' &&
				! $this->_permission('can_view_forum', $row['forum_permissions']))
			{
				continue;
			}

			// Can the user view the current hidden forum?
			if ($row['forum_status'] == 'c' AND  ! $this->_permission('can_view_hidden', $row['forum_permissions']))
			{
				if ($row['forum_is_cat'] == 'y')
				{
					$enable_cluster = FALSE;
				}

				continue;
			}

			// Build the forum output
			if ($row['forum_is_cat'] == 'y')
			{
				$this->forum_ids[] = $row['forum_id'];
				$return .= $this->main_forum_table_heading($row);
				$this->is_table_open = TRUE;
			}
			else
			{
				if (bool_config_item('enable_censoring'))
				{
					$row['forum_last_post_title'] = ee('Format')->make('Text', $row['forum_last_post_title'])->censor();
				}

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

	/**
	 * Forum Category Heading
	 */
	public function main_forum_table_heading($row)
	{
		// Close the table of the previous cluster if it has not been done so already

		$table_close = ($this->is_table_open == TRUE) ? $this->main_forum_table_close() : '';

		$table_head = $this->load_element('forum_table_heading');

		$table_head = str_replace('{category_name}', $this->_convert_special_chars($row['forum_name'], TRUE), $table_head);
		$table_head = str_replace('{category_id}', $row['forum_id'], $table_head);

		if (preg_match("/\{if\s+category_description}(.*?){\/if\}/s", $table_head, $match))
		{
			$match['1'] = str_replace('{category_description}', $row['forum_description'], $match['1']);

			$table_head = str_replace($match['0'], $match['1'], $table_head);
		}

		return $table_close.$table_head;
	}

	/**
	 * Forum Table Rows
	 */
	public function main_forum_table_rows($row, $markers, $read_topics)
	{
		// Fetch Template
		$table_rows = $this->load_element('forum_table_rows');

		// -------------------------------------------
        // 'main_forum_table_rows_template' hook.
        //  - Allows modifying of the forum_table_rows template
        //
			if (ee()->extensions->active_hook('main_forum_table_rows_template') === TRUE)
			{
				$table_rows = ee()->extensions->call('main_forum_table_rows_template', $this, $table_rows, $row, $markers, $read_topics);
				if (ee()->extensions->end_script === TRUE) return $table_rows;
			}
        //
        // -------------------------------------------

        // Swap a few variables
		$table_rows = str_replace('{forum_name}', 	$this->_convert_special_chars($row['forum_name'], TRUE), $table_rows);
		$table_rows = str_replace('{total_topics}', $row['forum_total_topics'], $table_rows);
		$table_rows = str_replace('{total_replies}',$row['forum_total_posts'], 	$table_rows);
		$table_rows = str_replace('{path:viewforum}', $this->forum_path('viewforum/'.$row['forum_id']), 	$table_rows);

		// Do we have to add pagination to the "last post" link?
		// This allows the link to point to the last page of the thread
		$pquery = ee()->db->select("COUNT(*) as count")
							   ->where('topic_id', $row['forum_last_post_id'])
							   ->get('forum_posts');

		$pagination = '';

		if ($pquery->row('count') > $row['forum_posts_perpage'] AND $row['forum_posts_perpage'] > 0)
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
			$table_rows = str_replace('{path:recent_thread}', $this->forum_path($viewpath.$row['forum_last_post_id'].$ann_id.$pagination), $table_rows);
		}
		else
		{
			$table_rows = str_replace('{path:recent_thread}', $this->forum_path($viewpath.$row['forum_last_post_id'].$ann_id), $table_rows);
		}

		$topic_marker = $markers['old'];

		if (ee()->session->userdata('member_id') == 0)
		{
			$topic_marker = $markers['new'];
		}
		else
		{
			if (ee()->session->userdata('last_visit') > 0)
			{
				$tquery = ee()->db->select('topic_id, last_post_date')
									   ->where('forum_id', $row['forum_id'])
									   ->where('last_post_date >', ee()->session->userdata('last_visit'))
									   ->get('forum_topics');

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
				$topic_marker = ($row['forum_last_post_date'] > ee()->session->userdata('last_visit')) ? $markers['new'] : $markers['old'];
			}
		}

		$table_rows = str_replace('{topic_marker}', 	$topic_marker, 	$table_rows);

		// Is there a description?
		if ($row['forum_description'] == '')
		{
			$table_rows = preg_replace("/\{if\s+forum_description}.*?{\/if\}/s", "", $table_rows);
		}
		elseif (preg_match("/\{if\s+forum_description}(.*?){\/if\}/s", $table_rows, $match))
		{
			$match['1'] = str_replace('{forum_description}', $row['forum_description'], $match['1']);

			$table_rows = str_replace($match['0'], $match['1'], $table_rows);
		}

		// Are there moderators?
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
							$temp = str_replace('{path:member_profile}', $this->profile_path($mod['mod_member_id']), $temp);
							$temp = str_replace('{name}', $mod['mod_member_name'], $temp);
						}
						else
						{
							// member groups are always plural since it describes people
							$plural = TRUE;
							$gid = 'memberlist/'.$mod['mod_group_id'].'-total_posts-desc-20-0';
							$temp = str_replace('{path:member_profile}', $this->profile_path($gid), $temp);
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

		// Fetch recent post stuff
		$recent_chunk = ( ! preg_match("/\{if\s+recent_post}(.*?){\/if\}/s", $table_rows, $match)) ? FALSE : $match;

		if ($recent_chunk != FALSE)
		{
			if ($row['forum_last_post_title'] != '')
			{
				$temp = ee()->TMPL->parse_date_variables($recent_chunk['1'], array('last_post' => $row['forum_last_post_date']));

				$temp = str_replace('{title}', $this->_convert_special_chars($row['forum_last_post_title']), $temp);
				$temp = str_replace('{author}', $row['forum_last_post_author'], $temp);
				$temp = str_replace('{path:member_profile}',  $this->profile_path($row['forum_last_post_author_id']), $temp);

				$table_rows = str_replace($recent_chunk['0'], $temp, $table_rows);
			}
			else
			{
				$table_rows = str_replace($recent_chunk['0'], '', $table_rows);
			}
		}

		return $table_rows;
	}

	/**
	 * Forum Table Close
	 */
	public function main_forum_table_close()
	{
		$this->is_table_open = FALSE;

		return $this->load_element('forum_table_footer');
	}

	/**
	 * Show/hide Javascript
	 */
	public function show_hide_forums()
	{
		if (count($this->forum_ids) == 0)
		{
			return;
		}

		$str = $this->load_element('javascript_show_hide_forums');

		$prefix = ( ! ee()->config->item('cookie_prefix')) ? 'exp_' : ee()->config->item('cookie_prefix').'_';
		$str = str_replace('{cookie_name}', $prefix.'state', $str);

		$arr = $this->load_element('javascript_forum_array');

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
		$this->body_extra = $this->load_element('javascript_set_show_hide');
	}

	/**
	 * Announcement Topics
	 */
	public function announcement_topics()
	{
		$query = ee()->db->select('COUNT(*) as count')
							  ->where('announcement', 'a')
							  ->or_where("(announcement = 't' AND forum_id = '{$this->current_id}')")
							  ->get('forum_topics');

		if ($query->row('count') == 0)
		{
			return '';
		}

		// Fetch the announcements
		$query = ee()->db->query("SELECT t.topic_id, t.author_id, t.title, t.thread_views, t.topic_date, m.screen_name AS author
								FROM exp_forum_topics t, exp_members m
								WHERE m.member_id = t.author_id
								AND t.board_id = '".$this->fetch_pref('board_id')."'
								AND (t.announcement = 'a' OR (announcement = 't' AND forum_id = '{$this->current_id}'))
							");

		if ($query->num_rows() == 0)
		{
			return '';
		}

		// Fetch the templates
		$str = $this->load_element('announcement_topics');
		$template = $this->load_element('announcement_topic_rows');

		// Fetch the topic markers
		$markers = $this->_fetch_topic_markers();
		$topic_marker = $markers['announce'];

		// Render the template
		$topics = '';

		ee()->load->helper('date');

		foreach ($query->result_array() as $row)
		{
			$temp = $this->var_swap($template,
							array(
									'topic_marker'			=>	$topic_marker,
									'topic_title'			=>	$this->_convert_special_chars($row['title']),
									'author'				=>	$row['author'],
									'total_views'			=>	$row['thread_views'],
									'path:member_profile'	=>	$this->profile_path($row['author_id']),
									'path:view_thread'		=>	$this->forum_path('/viewannounce/'.$row['topic_id'].'_'.$this->current_id.'/')
								)
							);

			$temp = ee()->TMPL->parse_date_variables($temp, array('post_date' => $row['topic_date']));

			$topics .= $temp;
		}

		return str_replace('{include:announcement_rows}', $topics, $str);
	}

	/**
	 * View Announcements page
	 */
	public function announcements()
	{


		$tquery = ee()->db->query("SELECT f.forum_text_formatting, f.forum_html_formatting, f.forum_auto_link_urls, f.forum_allow_img_urls, f.forum_hot_topic, f.forum_post_order, f.forum_posts_perpage, f.forum_display_edit_date,
									 t.forum_id, t.topic_id as post_id, t.author_id, t.ip_address, t.title, t.body, t.status, t.announcement, t.thread_views, t.parse_smileys, t.topic_date AS date, t.topic_edit_date AS edit_date, t.topic_edit_author AS edit_author_id, em.screen_name AS edit_author,
							  		 m.group_id, m.screen_name AS author, m.join_date, m.total_forum_topics, m.total_forum_posts, m.email, m.accept_user_email, m.signature, m.sig_img_filename, m.sig_img_width, m.sig_img_height, m.avatar_filename, m.avatar_width, m.avatar_height, m.photo_filename, m.photo_width, m.photo_height
							FROM (exp_forums f, exp_forum_topics t, exp_members m)
							LEFT JOIN exp_members em ON t.topic_edit_author = em.member_id
							WHERE f.forum_id = t.forum_id
							AND t.author_id = m.member_id
							AND t.topic_id = '{$this->current_id}'");

		if ($tquery->num_rows() == 0)
		{
			return $this->trigger_error('thread_no_exists');
		}

		$forum_id	= $tquery->row('forum_id') ;

		$formatting = array(
							'text_format'	=> $tquery->row('forum_text_formatting') ,
							'html_format'	=> $tquery->row('forum_html_formatting') ,
							'auto_links'	=> $tquery->row('forum_auto_link_urls') ,
							'allow_img_url' => $tquery->row('forum_allow_img_urls')
							);

		// Fetch the moderators and ranks
		$rank_query = ee()->db->select('rank_title, rank_min_posts, rank_stars')
								   ->order_by('rank_min_posts')->get('forum_ranks');

		$mod_query = ee()->db->select('mod_member_id, mod_group_id')
								  ->where('mod_forum_id', $forum_id)
								  ->get('forum_moderators');

		//  Fetch the Super Admin IDs
		$super_admins = $this->fetch_superadmins();

		// Fetch attachments
		$attach_query = ee()->db->where('topic_id', $this->current_id)
									 ->where('post_id', 0)
									 ->get('forum_attachments');

		if ($attach_query->num_rows() == 0)
		{
			$attach_query = FALSE;
			$attach_base 	= '';
		}
		else
		{
			$attach_base = ee()->functions->fetch_site_index(0, $this->use_sess_id).((ee()->config->item('force_query_string') == 'y') ? '&amp;' : '?').'ACT='.ee()->functions->fetch_action_id('Forum', 'display_attachment').'&amp;fid='.$forum_id.'&amp;aid=';
		}

		// Update the views
		$views = ($tquery->row('thread_views')  <= 0) ? 1 : $tquery->row('thread_views')  + 1;
		ee()->db->query("UPDATE exp_forum_topics SET thread_views = '{$views}' WHERE topic_id = '{$this->current_id}'");

		// Parse the template with the topic data
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
											'topic_status'	=> $tquery->row('status'),
											'forum_id'      => $tquery->row('forum_id')
											),
										TRUE
									);

		return str_replace('{topic_title}', $this->_convert_special_chars($tquery->row('title') ), $str);
	}

	/**
	 * Topic View Table
	 */
	public function topics()
	{
		$query_limit	= '';

		// Fetch Topic order and per-page count
		$query = ee()->db->query("SELECT forum_topic_order, forum_topics_perpage, forum_posts_perpage, forum_hot_topic, forum_enable_rss FROM exp_forums WHERE forum_id = '{$this->current_id}'");

		if ($query->num_rows() == 0)
		{
			return $this->trigger_error();
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

		$hot_topic	= $query->row('forum_hot_topic');
		$topic_limit = $query->row('forum_topics_perpage');
		$post_limit  = $query->row('forum_posts_perpage');

		if ($query->row('forum_enable_rss')  == 'y')
		{
			$this->feeds_enabled = TRUE;
			$this->feed_ids = $this->current_id;
		}

		if ( ! is_numeric($hot_topic) OR $hot_topic == 0) $hot_topic = '15';

		$topic_limit = ($topic_limit == 0) ? 25 : $topic_limit;
		$post_limit = ($post_limit == 0) ? 15 : $post_limit;

		// Fetch template and meta-data
		$str = $this->load_element('topics');
		$fdata = $this->_fetch_forum_metadata($this->current_id);


		// -------------------------------------------
		// 'forum_topics_start' hook.
		//  - Allows usurping of forum topics display
		//
			if (ee()->extensions->active_hook('forum_topics_start') === TRUE)
			{
				$str = ee()->extensions->call('forum_topics_start', $this, $str);
				if (ee()->extensions->end_script === TRUE) return $str;
			}
		//
		// -------------------------------------------

		// Check to see if the old style pagination exists
		// @deprecated 2.8
		if (stripos($str, LD.'if paginate'.RD) !== FALSE)
		{
			$str = preg_replace("/{if paginate}(.*?){\/if}/uis", "{paginate}$1{/paginate}", $str);
			ee()->load->library('logger');
			ee()->logger->developer('{if paginate} has been deprecated, use normal {paginate} tags in your forum topics template.', TRUE, 604800);
		}

		// Load up pagination and start parsing
		ee()->load->library('pagination');
		$pagination = ee()->pagination->create();
		$pagination->position = 'inline';
		$str = $pagination->prepare($str);

		// Count the topics for pagination
		$query = ee()->db->query("SELECT COUNT(*) AS count
							FROM exp_forum_topics t, exp_members m, exp_members a
							WHERE t.last_post_author_id = m.member_id
							AND a.member_id = t.author_id
							AND t.announcement = 'n'
							AND (t.forum_id = '{$this->current_id}' OR t.moved_forum_id = '{$this->current_id}')
							{$query_limit}");


		if ($query->row('count')  == 0)
		{
			$str = str_replace('{include:topic_rows}', $this->load_element('topic_no_results'), $str);

			$str = $this->deny_if('paginate', $str, '&nbsp;');

			if ( ! $this->_permission('can_post_topics', unserialize(stripslashes($fdata[$this->current_id]['forum_permissions']))) OR ee()->session->userdata('member_id') == 0)
			{
				$str = $this->deny_if('can_post', $str, '&nbsp;');
			}
			else
			{
				$str = $this->allow_if('can_post', $str);
			}

			// Rendering the pagination will remove the {pagination_marker}
			$str = $pagination->render($str);

			return $this->var_swap( $str,
									array(
											'forum_name'		=> $this->_convert_special_chars($fdata[$this->current_id]['forum_name'], TRUE),
											'forum_description'	=> $fdata[$this->current_id]['forum_description'],
											'path:new_topic' 	=> $this->forum_path('/newtopic/'.$this->current_id.'/')
										)
									);
		}

		// No funny business with the page count allowed
		if ($this->current_page > $query->row('count') )
		{
			$this->current_page = 0;
		}

		// We have pagination!
		if ($query->row('count') > $topic_limit
			&& $pagination->paginate === TRUE)
		{
			$pagination->build($query->row('count'), $topic_limit);

			// Set the LIMIT for our query
			$query_limit = 'LIMIT '.$this->current_page.', '.$topic_limit;
		}

		$str = ($pagination == '') ? $this->deny_if('paginate', $str, '&nbsp;') :
										$this->allow_if('paginate', $str);

		// Fetch the topics
		$query = ee()->db->query("SELECT t.topic_id, t.author_id, t.moved_forum_id, t.ip_address, t.title, t.status, t.sticky, t.poll, t.thread_views, t.topic_date, t.thread_total, t.last_post_author_id,  t.last_post_date, t.last_post_id,
								m.screen_name AS last_post_author,
								a.screen_name AS author
							FROM exp_forum_topics t, exp_members m, exp_members a
							WHERE t.last_post_author_id = m.member_id
							AND a.member_id = t.author_id
							AND t.announcement = 'n'
							AND (t.forum_id = '{$this->current_id}' OR t.moved_forum_id = '{$this->current_id}')
							".$order."
							{$query_limit}");


		// Fetch the "row" template
		$template = $this->load_element('topic_rows');

		// Fetch the "read topics" cookie

		// Each time a topic is read, the ID number for that topic
		// is saved in a cookie as a serialized arrry.
		// This array lets us set topics to "read" status.
		// The array is only good durring the length of the current session.

		$read_topics = $this->_fetch_read_topics();

		// Fetch the topic markers
		$markers = $this->_fetch_topic_markers();

		// Parse the results
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
				if (ee()->extensions->active_hook('forum_topics_loop_start') === TRUE)
				{
					$temp = ee()->extensions->call('forum_topics_loop_start', $this, $query->result(), $row, $temp);
					if (ee()->extensions->end_script === TRUE) return;
				}
			/*
			/* -------------------------------------*/

			// Update last post info if needed added 1.3.1
			if ($row['last_post_id'] == 0 && $row['thread_total'] > 1)
			{
				$this->_update_topic_stats($row['topic_id']);
				$pquery = ee()->db->query("SELECT last_post_id FROM exp_forum_topics WHERE topic_id = '".$row['topic_id']."'");
				$row['last_post_id'] = $pquery->row('last_post_id') ;
			}

			// Parse {if is_ignored}
			if (in_array($row['author_id'], ee()->session->userdata['ignore_list']))
			{
				$temp = $this->allow_if('is_ignored', $temp);
			}
			else
			{
				$temp = $this->deny_if('is_ignored', $temp);
			}

			// Assign the post marker (folder image)
			$topic_type = '';
			$topic_class = '';

			if ((isset($read_topics[$row['topic_id']]) AND $read_topics[$row['topic_id']] > $row['last_post_date']) OR ee()->session->userdata('last_visit') > $row['last_post_date'])
			{
				if ($row['poll'] == 'y')
				{
					$topic_marker = $markers['poll_old'];
					$topic_type = "<span class='forumLightLinks'>".lang('poll_marker').'&nbsp;</span>';
					$topic_class = 'poll';
				}
				else
				{
					if ($row['thread_total'] >= $hot_topic )
					{
						$topic_class = 'hot';
						$topic_marker = $markers['hot_old'];
					}
					else
					{
						$topic_marker = $markers['old'];
					}
				}

				$temp = $this->deny_if('is_new', $temp);
			}
			else
			{
				if ($row['poll'] == 'y')
				{
					$topic_marker = $markers['poll_new'];
					$topic_type = "<span class='forumLightLinks'>".lang('poll_marker').'&nbsp;</span>';
					$topic_class = 'poll';
				}
				else
				{
					if ($row['thread_total'] >= $hot_topic )
					{
						$topic_class = 'hot';
						$topic_marker = $markers['hot'];
					}
					else
					{
						$topic_marker = $markers['new'];
					}
				}

				$temp = $this->allow_if('new_topic', $temp);
				$temp = $this->allow_if('is_new', $temp);
			}

			if ($row['status'] == 'c')
			{
				$topic_marker = $markers['closed'];
				$topic_type = "<span class='forumLightLinks'>".lang('closed').'&nbsp;</span>';
				$topic_class = 'closed';
			}

			if ($row['sticky'] == 'y')
			{
				$topic_marker = $markers['sticky'];
				$topic_type = "<span class='forumLightLinks'>".lang('sticky').'&nbsp;</span>';
				$topic_class = 'sticky';
			}

			if ($row['moved_forum_id'] != 0 AND $row['moved_forum_id'] == $this->current_id)
			{
				$topic_marker = $markers['moved'];
				$topic_type = "<span class='forumLightLinks'>".lang('moved').'&nbsp;</span>';
				$topic_class = 'moved';
			}

			// Do we need small pagination links?
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
				$baselink = $this->forum_path('/viewthread/'.$row['topic_id'].'/');

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
				$temp = $this->allow_if('pagelinks', $temp);
			}
			else
			{
				$temp = $this->deny_if('pagelinks', $temp);
			}

			// Swap out the template variables
			$temp = $this->deny_if('new_topic', $temp);

			// is_post / is_topic conditionals
			if ($row['last_post_id'] != 0)
			{
				$temp = $this->allow_if('is_post', $temp);
				$temp = $this->deny_if('is_topic', $temp);
			}
			else
			{
				$temp = $this->deny_if('is_post', $temp);
				$temp = $this->allow_if('is_topic', $temp);
			}

			// Parse {if is_author}
			if (ee()->session->userdata('member_id') == $row['author_id'])
			{
				$temp = $this->allow_if('is_author', $temp);
			}
			else
			{
				$temp = $this->deny_if('is_author', $temp);
			}

			// Replace {switch="foo|bar|..."}
			if ( ! empty($switches))
			{
				$switch = $switches[($count + count($switches) - 1) % count($switches)];
				$temp = str_replace($smatch['0'], $switch, $temp);
			}

			// Swap the <div> id's for the javascript rollovers
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

			// Load the typography class
			ee()->load->library('typography');
			ee()->typography->initialize();

			$title = ' '.$row['title'].' ';

			if (bool_config_item('enable_censoring'))
			{
				$title = ee('Format')->make('Text', $title)->censor();
			}

			// Finalize the result
			$temp = $this->var_swap($temp,
							array(
									'topic_marker'			=>	$topic_marker,
									'topic_type'			=>  $topic_type,
									'topic_class'			=>  $topic_class,
									'topic_title'			=>	trim($this->_convert_special_chars(ee()->typography->format_characters($title))),
									'author'				=>	$row['author'],
									'total_views'			=>	$row['thread_views'],
									'total_replies'			=>	$row['thread_total'] - 1,
									'reply_author'			=>	$row['last_post_author'],
									'path:post_link'		=>  $this->forum_path('/viewreply/'.$row['last_post_id'].'/'),
									'path:member_profile'	=>	$this->profile_path($row['author_id']),
									'path:view_thread'		=>	$this->forum_path('/viewthread/'.$row['topic_id'].'/'),
									'path:reply_member_profile'	=> $this->profile_path($row['last_post_author_id']),
									'path:ignore'			=>	$this->forum_path("ignore_member/{$row['author_id']}"),
								)
							);

			$temp = ee()->TMPL->parse_date_variables($temp, array('last_reply' => $row['last_post_date']));

			/* -------------------------------------
			/*  'forum_topics_loop_end' hook.
			/*  - Modify the processed topic row before it is appended to the template output
			/*  - Added Discussion Forums 1.3.2
			*/
				if (ee()->extensions->active_hook('forum_topics_loop_end') === TRUE)
				{
					$temp = ee()->extensions->call('forum_topics_loop_end', $this, $query->result(), $row, $temp);
					if (ee()->extensions->end_script === TRUE) return;
				}
			/*
			/* -------------------------------------*/

			// Complile the string
			$topics .= $temp;
		}

		$str = str_replace('{include:topic_rows}', $topics, $str);

		// Parse permissions
		if ( ! $this->_permission('can_post_topics', unserialize(stripslashes($fdata[$this->current_id]['forum_permissions']))) OR ee()->session->userdata('member_id') == 0)
		{
			$str = $this->deny_if('can_post', $str, '&nbsp;');
		}
		else
		{
			$str = $this->allow_if('can_post', $str);
		}

		$str = $pagination->render($str);

		// Finalize the template
		$str = $this->var_swap( $str,
								array(
										'forum_name'		=> $this->_convert_special_chars($fdata[$this->current_id]['forum_name'], TRUE),
										'forum_description'	=> $fdata[$this->current_id]['forum_description'],
										'path:new_topic' 	=> $this->forum_path('/newtopic/'.$this->current_id.'/')
									)
								);

		/* -------------------------------------
		/*  'forum_topics_absolute_end' hook.
		/*  - Modify the finalized topics template and do what you wish
		/*  - Added Discussion Forums 1.3.2
		*/
			if (ee()->extensions->active_hook('forum_topics_absolute_end') === TRUE)
			{
				$str = ee()->extensions->call('forum_topics_absolute_end', $this, $query->result(), $str);
				if (ee()->extensions->end_script === TRUE) return $str;
			}
		/*
		/* -------------------------------------*/

		return $str;
	}

	/**
	 * Thread Review for submission page
	 */
	public function thread_review()
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

	/**
	 * Forum Threads
	 *
	 * @param 	boolean
	 * @param	boolean
	 * @param	boolean
	 */
	public function threads($is_announcement = FALSE, $thread_review = FALSE, $is_split = FALSE)
	{
		$posts       = '';
		$query_limit = '';

		// Fetch/Set the "topic tracker" cookie
		$read_topics = $this->_fetch_read_topics($this->current_id);

		if (ee()->session->userdata('member_id') == 0)
		{
			$expire = 60*60*24*365;
			ee()->input->set_cookie('forum_topics', json_encode($read_topics), $expire);
		}
		else
		{
			if ($this->read_topics_exist === FALSE)
			{
				$d = array(
					'member_id'  => ee()->session->userdata('member_id'),
					'board_id'   => $this->fetch_pref('board_id'),
					'topics'     => serialize($read_topics),
					'last_visit' => ee()->localize->now
				);

				ee()->db->insert('forum_read_topics', $d);
			}
			else
			{
				$d = array(
					'topics'     => serialize($read_topics),
					'last_visit' => ee()->localize->now
				);

				ee()->db->where('member_id', ee()->session->userdata('member_id'));
				ee()->db->where('board_id', $this->fetch_pref('board_id'));
				ee()->db->update('forum_read_topics', $d);
			}
		}

		// Fetch The Topic
		ee()->db->select('f.forum_text_formatting, f.forum_html_formatting, f.forum_enable_rss,
			f.forum_auto_link_urls, f.forum_allow_img_urls, f.forum_hot_topic,
			f.forum_post_order, f.forum_posts_perpage, f.forum_display_edit_date,
			t.forum_id, t.topic_id as post_id, t.author_id, t.ip_address, t.title,
			t.body, t.status, t.announcement, t.thread_views, t.parse_smileys,
			t.topic_date AS date, t.topic_edit_date AS edit_date,
			t.topic_edit_author AS edit_author_id, em.screen_name AS edit_author,
			m.group_id, m.screen_name AS author, m.join_date, m.total_forum_topics,
			m.total_forum_posts, m.email, m.accept_user_email,
			m.signature, m.sig_img_filename, m.sig_img_width,
			m.sig_img_height, m.avatar_filename, m.avatar_width, m.avatar_height,
			m.photo_filename, m.photo_width, m.photo_height');
		ee()->db->from(array('forums f', 'forum_topics t', 'members m'));
		ee()->db->join('members em', 't.topic_edit_author = em.member_id', 'left');
		ee()->db->where('f.forum_id', 't.forum_id', FALSE);
		ee()->db->where('t.author_id = m.member_id');
		ee()->db->where('t.topic_id', $this->current_id);
		$tquery = ee()->db->get();

		if ($tquery->num_rows() == 0)
		{
			return $this->trigger_error('thread_no_exists');
		}

		if ($tquery->row('forum_enable_rss')  == 'y')
		{
			$this->feeds_enabled = TRUE;
			$this->feed_ids = $tquery->row('forum_id') ;
		}

		// If it's an announcement, they are barking up the wrong tree
		if ($tquery->row('announcement')  != 'n')
		{
			ee()->functions->redirect($this->forum_path('viewannounce/'.$this->current_id.'_'.$tquery->row('forum_id')));
			exit;
		}

		// Flip the notification flag
		if (ee()->session->userdata('member_id') != 0)
		{
			ee()->db->where('topic_id', $this->current_id);
			ee()->db->where('member_id', ee()->session->userdata('member_id'));
			ee()->db->update('forum_subscriptions', array('notification_sent' => 'n'));
		}

		// Assign a few variables
		$order			= ($tquery->row('forum_post_order') == 'a')
			? 'asc' : 'desc';
		$forum_id		= $tquery->row('forum_id');
		$limit 			= ($is_split == FALSE)
			? $tquery->row('forum_posts_perpage') : 100;
		$attach_base 	= '';

		if ($limit == 0 OR ! is_numeric($limit))
		{
			$limit = 15;
		}

		$formatting = array(
			'text_format'   => $tquery->row('forum_text_formatting'),
			'html_format'   => $tquery->row('forum_html_formatting'),
			'auto_links'    => $tquery->row('forum_auto_link_urls'),
			'allow_img_url' => $tquery->row('forum_allow_img_urls')
		);

		// Load the template
		if ($is_split == TRUE)
		{
			$str = $this->load_element('split_data');
		}
		else if ($thread_review == TRUE)
		{
			$str = $this->load_element('thread_review');
		}
		else
		{
			$str = $this->load_element('threads');
		}

		// parse this early to spare parsing for blocks the user doesn't have permission for
		if ( ! $this->_mod_permission('is_moderator', $tquery->row('forum_id')))
		{
			$str = $this->deny_if('is_moderator', $str);
		}
		else
		{
			$str = $this->allow_if('is_moderator', $str);
		}

		// Check to see if the old style pagination exists
		// @deprecated 2.8
		if (stripos($str, LD.'if paginate'.RD) !== FALSE)
		{
			$str = preg_replace("/{if paginate}(.*?){\/if}/uis", "{paginate}$1{/paginate}", $str);
			ee()->load->library('logger');
			ee()->logger->developer('{if paginate} has been deprecated, use normal {paginate} tags in your forum threads template.', TRUE, 604800);
		}

		// Load up pagination and start parsing
		ee()->load->library('pagination');
		$pagination = ee()->pagination->create();
		$pagination->position = 'inline';
		$str = $pagination->prepare($str);

		// Split post
		if ($is_split === TRUE)
		{
			// Are they allowed to split?
			if ( ! $this->_mod_permission('can_split', $tquery->row('forum_id')))
			{
				return $this->trigger_error();
			}

			// Figure out our offset
			$this->current_page = (int) ee()->input->post('current_page') ?: 0;
			if (ee()->input->post('next_page'))
			{
				$this->current_page += $limit;
			}
			else if (ee()->input->post('previous_page'))
			{
				$this->current_page -= $limit;
			}
			$this->current_page = ($this->current_page < 0)
				? 0
				: $this->current_page;
			$pagination->offset = $this->current_page;

			// Carry over the selected post IDs
			if (isset($_POST['post_id'])
				&& is_array($_POST['post_id'])
				&& (isset($_POST['next_page']) OR isset($_POST['previous_page'])))
			{
				$i = 0;

				// Keep things unique
				$_POST['post_id'] = array_unique($_POST['post_id']);

				foreach($_POST['post_id'] as $id)
				{
					if (is_numeric($id))
					{
						$this->form_actions['forum:do_split']['post_id['.$i.']'] = ee()->db->escape_str($id);
						++$i;
					}
				}
			}

			// Are there any other forums?
			ee()->db->select('forum_name, forum_id');
			ee()->db->where('board_id', $this->fetch_pref('board_id'));
			ee()->db->where('forum_is_cat', 'n');
			ee()->db->order_by('forum_order', 'asc');
			$f_query = ee()->db->get('forums');

			$menu = '';
			if ($f_query->num_rows() == 0)
			{
				$str = $this->deny_if('forums_exist', $str);
			}
			else
			{
				$str = $this->allow_if('forums_exist', $str);

				// Build the menu
				foreach ($f_query->result_array() as $row)
				{
					$selected = ($row['forum_id'] != $tquery->row('forum_id') )
						? ''
						: ' selected="selected"';
					$menu .= '<option value="'.$row['forum_id'].'"'.$selected.'>'.$row['forum_name'].'</option>';
				}
			}

			$str = $this->var_swap($str, array(
				'split_select_options' => $menu,
				'title'                => $this->_convert_special_chars($tquery->row('title') )
			));

			$this->form_actions['forum:do_split']['current_page'] = $this->current_page;
			$this->form_actions['forum:do_split']['topic_id'] = $this->current_id;
			$this->form_actions['forum:do_split']['RET'] = (isset($_POST['RET']))
				? $_POST['RET']
				: $this->forum_path('viewforum');

			if (isset($_POST['mbase']))
			{
				$this->form_actions['forum:do_split']['mbase'] = $_POST['mbase'];
			}
		}

		// Topic Jump
		if (strpos($str, '{next_topic_title}') === FALSE)
		{
			$str = $this->deny_if('next_topic', $str, '');
		}
		else
		{
			// Next topic link
			ee()->db->select('topic_id, title');
			ee()->db->where('forum_id', $tquery->row('forum_id'));
			ee()->db->where('topic_id !=', $this->current_id);
			ee()->db->where('topic_date >', $tquery->row('date'));
			ee()->db->order_by('topic_id', 'ASC');
			ee()->db->limit(1);
			$jquery = ee()->db->get('forum_topics');

			if ($jquery->num_rows() == 0)
			{
				$str = $this->deny_if('next_topic', $str, '');
			}
			else
			{
				$str = $this->allow_if('next_topic', $str);
				$str = $this->var_swap($str, array(
					'next_topic_title'    => trim($this->_convert_special_chars($jquery->row('title') )),
					'path:next_topic_url' => $this->forum_path('/viewthread/'.$jquery->row('topic_id') .'/')
				));
			}
		}

		if (strpos($str, '{previous_topic_title}') === FALSE)
		{
			$str = $this->deny_if('next_topic', $str, '');
		}
		else
		{
			// Previous topic link
			ee()->db->where('forum_id', $tquery->row('forum_id'));
			ee()->db->where('topic_id !=', $this->current_id);
			ee()->db->where('topic_date <', $tquery->row('date'));
			ee()->db->order_by('topic_id', 'DESC');
			ee()->db->limit(1);
			$jquery = ee()->db->get('forum_topics');

			if ($jquery->num_rows() == 0)
			{
				$str = $this->deny_if('previous_topic', $str, '');
			}
			else
			{
				$str = $this->allow_if('previous_topic', $str);
				$str = $this->var_swap($str, array(
					'previous_topic_title'    => trim($this->_convert_special_chars($jquery->row('title') )),
					'path:previous_topic_url' => $this->forum_path('/viewthread/'.$jquery->row('topic_id') .'/')
				));
			}
		}

		// Post reply button
		if ($tquery->row('status') == 'c')
		{
			$str = str_replace('{lang:post_reply}', lang('closed_thread'), $str);
		}

		// -------------------------------------------
		// 'forum_threads_template' hook.
		//  - Allows modifying of threads template before processing
		//
			if (ee()->extensions->active_hook('forum_threads_template') === TRUE)
			{
				$str = ee()->extensions->call('forum_threads_template', $this, $str, $tquery);
				if (ee()->extensions->end_script === TRUE) return $str;
			}
		//
		// -------------------------------------------

		// Fetch the moderators and ranks
		ee()->db->select('rank_title, rank_min_posts, rank_stars');
		ee()->db->order_by('rank_min_posts');
		$rank_query = ee()->db->get('forum_ranks');

		ee()->db->select('mod_member_id, mod_group_id');
		$mod_query = ee()->db->get_where('forum_moderators', array('mod_forum_id' => $forum_id));

		//  Fetch the Super Admin IDs
		$super_admins = $this->fetch_superadmins();

		// Fetch attachments
		ee()->db->where('topic_id', $this->current_id);
		ee()->db->where('post_id', 0);

		$attach_query = ee()->db->get('forum_attachments');

		if ($attach_query->num_rows() == 0)
		{
			$attach_query = FALSE;
		}
		else
		{
			$attach_base = ee()->functions->fetch_site_index(0, $this->use_sess_id).((ee()->config->item('force_query_string') == 'y') ? '&amp;' : '?').'ACT='.ee()->functions->fetch_action_id('Forum', 'display_attachment').'&amp;fid='.$forum_id.'&amp;aid=';
		}

		// Update the views
		$views = ($tquery->row('thread_views') <= 0)
			? 1
			: $tquery->row('thread_views')  + 1;

		ee()->db->where('topic_id', $this->current_id);
		ee()->db->update('forum_topics', array('thread_views' => $views));

		// Is there a poll?
		ee()->db->select('poll_id, poll_question, poll_answers, total_votes');
		$query = ee()->db->get_where('forum_polls', array('topic_id' => $this->current_id));

		if ($query->num_rows() == 0)
		{
			$str = $this->deny_if('poll', $str, '');
			$poll = '';
		}
		else
		{
			$answers = $this->array_stripslashes(unserialize($query->row('poll_answers') ));

			if ( ! is_array($answers))
			{
				$str = $this->deny_if('poll', $str, '');
				$poll = '';
			}
			else
			{
				$str = $this->allow_if('poll', $str);
				$poll = $this->_generate_poll(
					$query->row('poll_id'),
					$query->row('poll_question'),
					$answers,
					$query->row('total_votes')
				);
			}
		}

		// Parse the template with the topic data
		$topic = $this->thread_rows(
			array(
				'query'        => $tquery,
				'rank_query'   => $rank_query,
				'mod_query'    => $mod_query,
				'attach_query' => $attach_query,
				'attach_base'  => $attach_base,
				'formatting'   => $formatting,
				'super_admins' => $super_admins,
				'is_topic'     => TRUE,
				'topic_id'     => $tquery->row('post_id') ,
				'topic_status' => $tquery->row('status') ,
				'is_split'     => $is_split,
				'forum_id'     => $tquery->row('forum_id'),
				'poll'         => $poll
			),
			FALSE,
			$thread_review
		);

		$attach_query = FALSE;

		// Count the total number of posts
		// We do this for purposes of pagination
		// and to see if we even need to show anything
		// other than the topic

		ee()->db->select('COUNT(*) as count');
		$pquery = ee()->db->get_where('forum_posts', array('topic_id' => $this->current_id));

		if ($pquery->row('count') > 0)
		{
			// We have pagination!
			if (($pquery->row('count') > $limit)
				&& $thread_review == FALSE
				&& $pagination->paginate === TRUE)
			{
				$pagination->build($pquery->row('count'), $limit);

				// Set the LIMIT for our query
				$query_limit = 'LIMIT '.$pagination->offset.', '.$limit;
			}

			// Fetch the posts
			if ($thread_review == TRUE)
			{
				$order = 'desc';
				$query_limit = ' LIMIT 10';
			}

			$pquery = ee()->db->query("SELECT p.post_id, p.forum_id, p.author_id, p.ip_address, p.body, p.parse_smileys, p.post_date AS date, p.post_edit_date AS edit_date, p.post_edit_author AS edit_author_id, em.screen_name AS edit_author,
				m.group_id, m.screen_name AS author, m.join_date, m.total_forum_topics, m.total_forum_posts, m.email, m.accept_user_email, m.signature, m.sig_img_filename, m.sig_img_width, m.sig_img_height, m.avatar_filename, m.avatar_width, m.avatar_height, m.photo_filename, m.photo_width, m.photo_height,
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

				$attach_query = ee()->db->query($sql);

				if ($attach_query->num_rows() == 0)
				{
					$attach_query = FALSE;
				}
				else
				{
					if ($attach_base == '')
						$attach_base = ee()->functions->fetch_site_index(0, $this->use_sess_id).((ee()->config->item('force_query_string') == 'y') ? '&amp;' : '?').'ACT='.ee()->functions->fetch_action_id('Forum', 'display_attachment').'&amp;fid='.$forum_id.'&amp;aid=';
				}
			}

			// arse Posts
			$posts = $this->thread_rows(
				array(
					'query'        => $pquery,
					'rank_query'   => $rank_query,
					'mod_query'    => $mod_query,
					'attach_query' => $attach_query,
					'attach_base'  => $attach_base,
					'formatting'   => $formatting,
					'super_admins' => $super_admins,
					'is_topic'     => FALSE,
					'topic_id'     => $this->current_id,
					'topic_status' => $tquery->row('status') ,
					'is_split'     => $is_split,
					'forum_id'     => $tquery->row('forum_id')
				),
				FALSE,
				$thread_review
			);
		}


		// Pagination
		$str = $pagination->render($str);

		// Create Subscription Link
		if (ee()->session->userdata('member_id') == 0)
		{
			$subscription_text = '';
			$subscription_path = '';
		}
		else
		{
			// Is the user subscribed?
			ee()->db->select('COUNT(*) as count');
			$query = ee()->db->get_where(
				'forum_subscriptions',
				array(
					'topic_id'  => $this->current_id,
					'member_id' => ee()->session->userdata('member_id')
				)
			);

			if ($query->row('count')  == 0)
			{
				$subscription_text = lang('subscribe_to_thread');
				$subscription_path = $this->forum_path('/subscribe/'.$this->current_id.'/');
			}
			else
			{
				$subscription_text = lang('unsubscribe_to_thread');
				$subscription_path = $this->forum_path('/unsubscribe/'.$this->current_id.'/');
			}
		}

		// Parse permissions
		$meta = $this->_fetch_forum_metadata($forum_id);
		$perms = unserialize(stripslashes($meta[$forum_id]['forum_permissions']));

		if ( ! $this->_permission('can_post_reply', $perms) OR ($tquery->row('status')  == 'c' AND ee()->session->userdata('group_id') != 1) OR ee()->session->userdata('member_id') == 0)
		{
			$str = $this->deny_if('can_post', $str, '&nbsp;');
		}
		else
		{
			$str = $this->allow_if('can_post', $str);
		}


		if ( ! $this->_permission('can_post_topics', $perms) OR ee()->session->userdata('member_id') == 0)
		{
			$str = $this->deny_if('can_post_topics', $str, '&nbsp;');
		}
		else
		{
			$str = $this->allow_if('can_post_topics', $str);
		}

		// Finalize Template
		if ($thread_review == TRUE)
		{
			if ($pagination->offset > 0)
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
			if ($pagination->offset > 0)
			{
				if ($pagination->current_page == $pagination->total_pages
					AND $order != 'asc')
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
					if ($pagination->current_page < $pagination->total_pages)
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
		ee()->load->library('typography');
		ee()->typography->initialize();

		$title = ' '.$tquery->row('title') .' ';
		$title = $this->convert_forum_tags($title);

		if (bool_config_item('enable_censoring'))
		{
			$title = ee('Format')->make('Text', $title)->censor();
		}

		// Finalize the result
		$thread = ($is_split == FALSE ) ? 'thread_rows' : 'split_thread_rows';

		$str = ee()->TMPL->parse_date_variables($str, array('topic_date' => $tquery->row('date')));

		return $this->var_swap($str, array(
			'topic_title'                => trim($this->_convert_special_chars(ee()->typography->format_characters($title))),
			'include:'.$thread           => $thread_rows,
			'include:thread_review_rows' => $thread_review_rows,
			'path:new_topic'             => $this->forum_path('/newtopic/'.$this->current_id.'/'),
			'path:post_reply'            => $this->forum_path('/newreply/'.$this->current_id.'/'),
			'path:thread_review'         => $this->forum_path('/viewthread/'.$this->current_id.'/'),
			'path:new_topic'             => $this->forum_path('/newtopic/'.$forum_id.'/'),
			'lang:subscribe'             => $subscription_text,
			'path:subscribe'             => $subscription_path,
			'include:poll'               => $poll
		));
	}

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
		// Only members can vote

		// If a user is not logged in we'll treat them
		// as having voted and show the poll graph

		if (ee()->session->userdata('member_id') == 0)
		{
			$has_voted = TRUE;
		}
		else
		{
			/** -------------------------------------
			/**  Has the member already voted?
			/** -------------------------------------*/

			$query = ee()->db->query("SELECT COUNT(*) as count FROM exp_forum_pollvotes WHERE poll_id = '{$poll_id}' AND topic_id = '{$this->current_id}' AND member_id = '".ee()->session->userdata('member_id')."'");
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

					ee()->db->query("UPDATE exp_forum_polls SET poll_answers = '".addslashes(serialize($answers))."', total_votes = '{$total_votes}' WHERE poll_id = '{$poll_id}'");

					$data = array(
									'poll_id'	=> $poll_id,
									'topic_id'	=> $this->current_id,
									'member_id'	=> ee()->session->userdata('member_id'),
									'choice_id' => $_POST['vote']
								);

					ee()->db->query(ee()->db->insert_string('exp_forum_pollvotes', $data));
				}

				$has_voted = TRUE;
			}
		}

		// Load the typography class
		ee()->load->library('typography');
		ee()->typography->initialize();

		// Build the output

		// We build the display based on whether the person has voted or not.
		// People can only vote once.  If a user has not voted we'll show
		// the voting form, otherwise we'll show the stats graph

		if ($has_voted == FALSE)
		{
			$template = $this->load_element('poll_questions');
			$poll_row = $this->load_element('poll_question_rows');

			$rows	= '';
			$checked = FALSE;

			foreach ($answers as $key => $val)
			{
				$temp = $poll_row;

				$temp = str_replace('{value}', $key, $temp);

				// Security fix
				$val['answer'] = $this->convert_forum_tags($val['answer']);

				if (bool_config_item('enable_censoring'))
				{
					$val['answer'] = ee('Format')->make('Text', $val['answer'])->censor();
				}

				$temp = str_replace('{poll_choice}', $this->_convert_special_chars($val['answer']), $temp);

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
			$question = $this->convert_forum_tags($question);

			if (bool_config_item('enable_censoring'))
			{
				$question = ee('Format')->make('Text', $question)->censor();
			}

			$form_declaration = ee()->functions->form_declaration(array(
												'action' => $this->forum_path('viewthread/'.$this->current_id)
											)
										);

			$template = $this->var_swap($template,
									array(
											'poll_question'	=> $this->_convert_special_chars($question),
											'form_declaration'	=> $form_declaration,
											'include:poll_question_rows' => $rows
										)
									);
		}
		else
		{
			$template = $this->load_element('poll_answers');
			$poll_row = $this->load_element('poll_answer_rows');

			$img_l = trim($this->load_element('poll_graph_left'));
			$img_m = trim($this->load_element('poll_graph_middle'));
			$img_r = trim($this->load_element('poll_graph_right'));

			$rows	= '';
			foreach ($answers as $key => $val)
			{
				$temp = $poll_row;

				$img = $img_l;

				if (bool_config_item('enable_censoring'))
				{
					$val['answer'] = ee('Format')->make('Text', $val['answer'])->censor();
				}

				$temp = str_replace('{poll_choice}', $this->_convert_special_chars($val['answer']), $temp);
				$temp = str_replace('{votes}', $val['votes'], $temp);

				$percent = 0;
				if ($val['votes'] > 0)
				{
					$percent = abs($val['votes'] / $total_votes * 100);
					$num = round(abs($percent / 3));
					$img .= str_repeat($img_m, $num);
				}

				$temp = str_replace('{vote_percentage}', $percent, $temp);
				$temp = str_replace('{vote_percentage_factor_ten}', round($percent, -1), $temp);

				$img .= $img_r;

				$temp = str_replace('{vote_graph}', $img, $temp);

				$rows .= $temp;
			}

			// Security fix
			$question = $this->convert_forum_tags($question);

			if (bool_config_item('enable_censoring'))
			{
				$question = ee('Format')->make('Text', $question)->censor();
			}

			$template = $this->var_swap($template,
									array(
											'poll_question'	=> $this->_convert_special_chars($question),
											'include:poll_answer_rows' => $rows,
											'total_votes' => $total_votes,
											'lang:voter_message' => (ee()->session->userdata('member_id') == 0) ? lang('must_be_logged_to_vote') : lang('you_have_voted')
										)
									);
		}

		return $template;
	}

	/**
	 * thread rows
	 *
	 * @param 	array
	 * @param	boolean
	 * @param 	boolean
	 */
	public function thread_rows($data, $is_announcement = FALSE, $thread_review = FALSE)
	{
		// Variabl-ize the array keys
		foreach ($data as $key => $val)
		{
			$$key = $val;
		}

		// Fetch template
		if ($is_announcement == TRUE)
		{
			$template = $this->load_element('announcement');
			$is_split = FALSE;
		}
		else
		{
			if ($is_split === FALSE)
			{
				if ($thread_review === FALSE)
				{
					$template = $this->load_element('thread_rows');
				}
				else
				{
					$template = $this->load_element('thread_review_rows');
				}
			}
			else
			{
				$template = $this->load_element('split_thread_rows');
			}
		}

		// parse this early to spare parsing for blocks the user doesn't have permission for
		if ( ! $this->_mod_permission('is_moderator', $forum_id))
		{
			$template = $this->deny_if('is_moderator', $template);
		}
		else
		{
			$template = $this->allow_if('is_moderator', $template);
		}

		// -------------------------------------------
		// 'forum_thread_rows_start' hook.
		//  - Allows usurping of forum thread rows display
		//
			if (ee()->extensions->active_hook('forum_thread_rows_start') === TRUE)
			{
				$template = ee()->extensions->call('forum_thread_rows_start', $this, $template, $data, $is_announcement, $thread_review);
				if (ee()->extensions->end_script === TRUE) return $template;
			}
		//
		// -------------------------------------------

		$rank_class = 'rankMember';

		$rank_stars = '';

		if (preg_match("/{if\s+rank_stars\}(.+?){\/if\}/i", $template, $matches))
		{
			$rank_stars = $matches['1'];
			$template = str_replace($matches['0'], '{rank_stars}', $template);
		}

		$iif = array('email');

		// Load the typography class
		ee()->load->library('typography');
		ee()->typography->initialize();

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
				if (ee()->extensions->active_hook('forum_thread_rows_loop_start') === TRUE)
				{
					$temp = ee()->extensions->call('forum_thread_rows_loop_start', $this, $data, $row, $temp);
					if (ee()->extensions->end_script === TRUE) return;
				}
			/*
			/* -------------------------------------*/

			// Parse {if is_ignored}
			if (in_array($row['author_id'], ee()->session->userdata['ignore_list']))
			{
				$temp = $this->allow_if('is_ignored', $temp);
			}
			else
			{
				$temp = $this->deny_if('is_ignored', $temp);
			}

			// Parse the member stuff (email, url, etc.)
			if ($row['accept_user_email'] == 'n')
			{
				$row['email'] == '';
				$temp = $this->deny_if('accept_email', $temp);
			}
			else
			{
				$temp = $this->allow_if('accept_email', $temp);
			}

			foreach ($iif as $var)
			{
				if ($row[$var] != '')
				{
					$temp = $this->allow_if($var, $temp);
					$temp = str_replace('{'.$var.'}', $row[$var], $temp);
				}
				else
				{
					$temp = $this->deny_if($var, $temp);
				}
			}

			$consoles = array(
						'email_console'	=> "onclick=\"window.open('".$this->profile_path('email_console/'.$row['author_id'])."', '_blank', 'width=650,height=600,scrollbars=yes,resizable=yes,status=yes,screenx=5,screeny=5');\"",
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

			// Assign the member rank
			if ($this->_is_admin($row['author_id']))
			{
				$rank_class = 'rankAdmin';
				$rank_title = lang('administrator');
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
							$rank_title = lang('moderator');
							break;
						}
					}
				}
			}

			$dates = array(
				'post_date' => $row['date'],
				'join_date' => $row['join_date'],
			);
			$temp = ee()->TMPL->parse_date_variables($temp, $dates);

			// 2 minute window for edits
			if ($row['forum_display_edit_date'] == 'y' AND ($row['edit_date'] - $row['date']) > 120)
			{
				$temp = ee()->TMPL->parse_date_variables($temp, array('edit_date' => $row['edit_date']));

				$temp = str_replace(LD.'edit_author'.RD, $row['edit_author'], $temp);
				$temp = str_replace(LD.'edit_author_id'.RD, $row['edit_author_id'], $temp);
				$temp = str_replace(LD.'path:edit_author_profile'.RD, $this->profile_path($row['edit_author_id']), $temp);

				$temp = $this->allow_if('edited', $temp);
			}
			else
			{
				$temp = $this->deny_if('edited', $temp);
			}

			// Parse the private stuff that only moderators can see

			// If we are showing an annoucement we'll kill the
			// MOVE, and ACTIVE TOPIC buttons since they are not needed

			if ($is_announcement == TRUE)
			{
				$temp = $this->deny_if('can_move', $temp);
				$temp = $this->deny_if('can_merge', $temp);
				$temp = $this->deny_if('can_split', $temp);
			}

			$meta = $this->_fetch_forum_metadata($row['forum_id']);
			$perms = unserialize(stripslashes($meta[$row['forum_id']]['forum_permissions']));

			if ( ! $this->_permission('can_post_reply', $perms) OR ($topic_status == 'c' AND ee()->session->userdata('group_id') != 1) OR ee()->session->userdata('member_id') == 0)
			{
				$temp = $this->deny_if('can_post', $temp, '&nbsp;');
			}
			else
			{
				$temp = $this->allow_if('can_post', $temp);
			}

			foreach (array('can_view_ip', 'can_move', 'can_merge', 'can_split', 'can_change_status') as $val)
			{
				if ($this->_mod_permission($val, $row['forum_id']))
				{
					$temp = $this->allow_if($val, $temp);
				}
				else
				{
					$temp = $this->deny_if($val, $temp);
				}
			}

			// Can they ban users?
			if ($this->_is_admin() && ee()->session->userdata('member_id') != $row['author_id'])
			{
				$temp = $this->allow_if('can_ban', $temp);
			}
			else
			{
				$temp = $this->deny_if('can_ban', $temp);
			}

			// Can they ignore users?
			if (ee()->session->userdata('member_id')
				&& ee()->session->userdata('member_id') != $row['author_id'])
			{
				$temp = $this->allow_if('can_ignore', $temp);
			}
			else
			{
				$temp = $this->deny_if('can_ignore', $temp);
			}

			// Parse the "Delete" Button
			if (ee()->session->userdata('group_id') == 1 OR
				($this->_mod_permission('can_delete', $row['forum_id']) &&
				! in_array($row['author_id'], $super_admins)))
			{
				$temp = $this->allow_if('can_delete', $temp);
			}
			else
			{
				$temp = $this->deny_if('can_delete', $temp);
			}

			// Parse the "Edit" Button

			// Users can edit their own entries, and moderators (with edit privs) can edit other entires.
			// However, no one but super admins can edit their own entries

			$can_edit = FALSE;

			if (ee()->session->userdata('group_id') == 1 OR
				(ee()->session->userdata('member_id') == $row['author_id']))
			{
				$can_edit = TRUE;
			}

			if ($this->_mod_permission('can_edit', $row['forum_id']) &&
				! in_array($row['author_id'], $super_admins) )
			{
				$can_edit = TRUE;
			}

			if ($can_edit)
			{
				$temp = $this->allow_if('can_edit', $temp);
			}
			else
			{
				$temp = $this->deny_if('can_edit', $temp);
			}

			// Parse the avatar
			if (ee()->config->item('enable_avatars') == 'y' &&
				$row['avatar_filename'] != '' &&
				ee()->session->userdata('display_avatars') == 'y' )
			{
				$avatar_url = ee()->config->slash_item('avatar_url');
				$avatar_fs_path = ee()->config->slash_item('avatar_path');

				if (file_exists($avatar_fs_path.'default/'.$row['avatar_filename']))
				{
					$avatar_url .= 'default/';
				}

				$avatar_path	= $avatar_url.$row['avatar_filename'];
				$avatar_width	= $row['avatar_width'];
				$avatar_height	= $row['avatar_height'];

				$temp = $this->allow_if('avatar', $temp);
			}
			else
			{
				$avatar_path	= '';
				$avatar_width	= '';
				$avatar_height	= '';

				$temp = $this->deny_if('avatar', $temp);
			}

			// Parse the photo
			if (ee()->config->item('enable_photos') == 'y' AND $row['photo_filename'] != '' AND ee()->session->userdata('display_photos') == 'y' )
			{
				$photo_path	= ee()->config->slash_item('photo_url').$row['photo_filename'];
				$photo_width	= $row['photo_width'];
				$photo_height	= $row['photo_height'];

				$temp = $this->allow_if('photo', $temp);
			}
			else
			{
				$photo_path	= '';
				$photo_width	= '';
				$photo_height	= '';

				$temp = $this->deny_if('photo', $temp);
			}

			// Are there attachments?
			if ( ! isset($attach_query) OR $attach_query === FALSE)
			{
				$temp = $this->deny_if('attachments', $temp);

				$attachments = '';
			}
			else
			{
				$temp = $this->allow_if('attachments', $temp);

				$attachments = $this->_parse_thread_attachments($attach_query, $attach_base, $data['is_topic'], $row['post_id']);
			}

			// Is there a signature?
			$signature = '';

			if (ee()->session->userdata('display_signatures') == 'y' AND ($row['signature'] != '' OR $row['sig_img_filename'] != ''))
			{
				$temp = $this->allow_if('signature', $temp);

				$signature = $this->load_element('signature');

				if ($row['sig_img_filename'] == '')
				{
					$signature = $this->deny_if('signature_image', $signature);
				}
				else
				{
					$signature = $this->allow_if('signature_image', $signature);

					$signature = $this->var_swap($signature,
													array(
															'path:signature_image'		=> 	ee()->config->slash_item('sig_img_url').$row['sig_img_filename'],
															'signature_image_width'		=> 	$row['sig_img_width'],
															'signature_image_height'	=> 	$row['sig_img_height']
														)
												);
				}

				$row['signature'] = ee()->typography->parse_type($row['signature'], array(
															'text_format'	=> 'xhtml',
															'html_format'	=> 'safe',
															'auto_links'	=> 'y',
															'allow_img_url' => ee()->config->item('sig_allow_img_hotlink')
															)
													);

				$signature = str_replace('{signature}', $row['signature'], $signature);
			}
			else
			{
				$temp = $this->deny_if('signature', $temp);
			}

			// Parse the "Report" Button
			if ( ! $this->_permission('can_report', $perms) OR ee()->session->userdata('member_id') == 0 OR ee()->session->userdata['member_id'] == $row['author_id'])
			{
				$temp = $this->deny_if('can_report', $temp);
			}
			else
			{
				$temp = $this->allow_if('can_report', $temp);
			}

			// Parse {if is_author}
			if (ee()->session->userdata('member_id') == $row['author_id'])
			{
				$temp = $this->allow_if('is_author', $temp);
			}
			else
			{
				$temp = $this->deny_if('is_author', $temp);
			}

			// Parse the topic-specific stuff
			if ($is_topic == TRUE)
			{
				$temp = $this->allow_if('is_topic', $temp);
				$temp = $this->deny_if('is_post', $temp);

				if ($this->_mod_permission('can_change_status', $row['forum_id']))
				{
					$temp = $this->var_swap($temp,
											array(
													'lang:change_status' 	=> ($row['status'] == 'o') ? lang('close_thread') : lang('activate_thread'),
													'path:change_status'	=> ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.ee()->functions->fetch_action_id('Forum', 'change_status').'&amp;topic_id='.$row['post_id'].'&amp;board_id='.$this->fetch_pref('board_id').'&amp;trigger='.$this->trigger,
													'css:status_button'		=> ($row['status'] == 'o') ? 'buttonStatusOff' : 'buttonStatusOn'
												)
											);
				}
				if ($this->_mod_permission('can_move', $row['forum_id']))
				{
					$temp = $this->var_swap($temp, array('path:move_topic' => $this->forum_path('/movetopic/'.$row['post_id'].'/')));
				}

				if ($this->_mod_permission('can_split', $row['forum_id']))
				{
					$temp = $this->var_swap($temp, array('path:split_topic' => $this->forum_path('/split/'.$row['post_id'].'/')));
				}

				if ($this->_mod_permission('can_merge', $row['forum_id']))
				{
					$temp = $this->var_swap($temp, array('path:merge_topic' => $this->forum_path('/merge/'.$row['post_id'].'/')));
				}
			}
			else
			{
				$temp = $this->deny_if('is_topic', $temp);
				$temp = $this->allow_if('is_post', $temp);

				if ($this->_mod_permission('can_move', $row['forum_id']))
				{
					$temp = $this->var_swap($temp, array('path:move_reply' => $this->forum_path('/movereply/'.$row['post_id'].'/')));
				}
			}

			// Swap the <div> id's for the javascript rollovers
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

			// Replace {switch="foo|bar|..."}
			if ( ! empty($switches))
			{
				$switch = $switches[($this->cur_thread_row + count($switches) - 1) % count($switches)];
				$temp = str_replace($smatch['0'], $switch, $temp);
			}

			// Parse the finalized template
			if ($row['parse_smileys'] == 'y')
			{
				ee()->typography->parse_smileys = TRUE;
			}
			else
			{
				ee()->typography->parse_smileys = FALSE;
			}

			// Keep includes from being parsed
			$row['body'] = $this->convert_forum_tags($row['body']);

			$checked = '';

			if ($is_split == TRUE)
			{
				// if we are splitting threads we'll limit the word count
				$row['body'] = ee()->functions->word_limiter($row['body'], 25);

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

			if ( ! empty($poll))
			{
				$temp = $this->allow_if('poll', $temp);
			}
			else
			{
				$temp = $this->deny_if('poll', $temp);
				$poll = '';
			}

			$temp = $this->var_swap($temp,
				array(
						'post_id'					=> $row['post_id'],
						'post_number'				=> $this->current_page + $post_number,
						'path:post_link'			=>  $this->forum_path('/viewreply/'.$row['post_id'].'/'),
						'author'					=> $row['author'],
						'ip_address'				=> $row['ip_address'],
						'include:signature'			=> $signature,
						'include:poll'				=> $poll,
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
						'lang:ban_member'			=> ($row['group_id'] == 2) ? lang('member_is_banned') : lang('ban_member'),
						'path:ban_member'			=> $this->forum_path('ban_member/'.$row['author_id']),
						'path:delete_post'			=> $this->forum_path('/'.(($is_topic == TRUE) ? 'deletetopic' : 'deletereply').'/'.$row['post_id'].'/'),
						'path:edit_post'			=> $this->forum_path('/'.(($is_topic == TRUE) ? 'edittopic' : 'editreply').'/'.$row['post_id'].'/'),
						'path:quote_reply'			=> $this->forum_path('/'.(($is_topic == TRUE) ? 'quotetopic' : 'quotereply').'/'.$row['post_id'].'/'),
						'path:report'				=> $this->forum_path('/'.(($is_topic == TRUE) ? 'reporttopic' : 'reportreply').'/'.$row['post_id'].'/'),
						'path:ignore'				=> $this->forum_path("ignore_member/{$row['author_id']}"),
						'path:member_profile'		=> $this->profile_path($row['author_id']),
						'path:send_private_message'	=> $this->profile_path('messages/pm/'.$row['author_id']),
						'path:send_pm'				=> $this->profile_path($row['author_id']),
						'body'						=> ee()->functions->encode_ee_tags(
														$this->_quote_decode(
															ee()->typography->parse_type(
																$row['body'],
							 									array(
																	'text_format'	=> $formatting['text_format'],
																	'html_format'	=> $formatting['html_format'],
																	'auto_links'	=> $formatting['auto_links'],
																	'allow_img_url' => $formatting['allow_img_url']
																)
										  					)
														),
														TRUE)
					)
				);

				/* -------------------------------------
				/*  'forum_thread_rows_loop_end' hook.
				/*  - Modify the processed row before it is appended to
				/*  	the template output
				/*  - Added Discussion Forums 1.3.2
				*/
					if (ee()->extensions->active_hook('forum_thread_rows_loop_end') === TRUE)
					{
						$temp = ee()->extensions->call('forum_thread_rows_loop_end', $this, $data, $row, $temp);
						if (ee()->extensions->end_script === TRUE) return;
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
			if (ee()->extensions->active_hook('forum_thread_rows_absolute_end') === TRUE)
			{
				$thread_rows = ee()->extensions->call('forum_thread_rows_absolute_end', $this, $data, $thread_rows);
				if (ee()->extensions->end_script === TRUE) return $thread_rows;
			}
		/*
		/* -------------------------------------*/

		return $thread_rows;
	}

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

		$img_path = $this->fetch_pref('board_upload_path');

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
				// Parse Thumbnail
				if (file_exists($img_path.$row['filehash'].'_t'.$row['extension']) AND $this->fetch_pref('board_use_img_thumbs') == 'y')
				{
					$thumb_str .= $this->var_swap($this->load_element('thumb_attachments'),
													array(
															'filename'			=> $row['filename'],
															'thumb_width'		=> $row['t_width'],
															'thumb_height'		=> $row['t_height'],
															'width'				=> $row['width'],
															'height'			=> $row['height'],
															'hits'				=> $row['hits'],
															'file_size'			=> $row['filesize'].'KB',
															'attach_thumb_url'	=> $attach_path.$row['filehash'].'&amp;thumb=1&amp;board_id='.$this->fetch_pref('board_id'),
															'attach_image_url'	=> $attach_path.$row['filehash'].'&amp;board_id='.$this->fetch_pref('board_id')
														)
													);
					continue;
				}

				// Parse Full-size Image
				if (file_exists($img_path.$row['filehash'].$row['extension']))
				{
					$image_str .= $this->var_swap($this->load_element('image_attachments'),
													array(
															'filename'			=> $row['filename'],
															'width'				=> $row['width'],
															'height'			=> $row['height'],
															'hits'				=> $row['hits'],
															'file_size'			=> $row['filesize'].'KB',
															'attach_image_url'	=> $attach_path.$row['filehash'].'&amp;board_id='.$this->fetch_pref('board_id')
														)
													);
					continue;
				}
			}
			else // Is the attachment a file?
			{
				$file_str .= $this->var_swap($this->load_element('file_attachments'),
												array(
														'filename'			=> $row['filename'],
														'hits'				=> $row['hits'],
														'file_size'			=> $row['filesize'].'KB',
														'attach_file_url'	=> $attach_path.$row['filehash'].'&amp;board_id='.$this->fetch_pref('board_id')
													)
												);
				continue;
			}

		}


		$str = $this->load_element('post_attachments');


		if ($thumb_str == '')
		{
			$str = str_replace("{include:thumb_attachments}", '',	$str);
			$str = $this->deny_if('thumb_attach',	$str);
		}
		else
		{
			$str = $this->allow_if('thumb_attach', $str);
			$str = str_replace("{include:thumb_attachments}", $thumb_str,	$str);
		}

		if ($image_str == '')
		{
			$str = str_replace("{include:image_attachments}", '',	$str);
			$str = $this->deny_if('image_attach',	$str);
		}
		else
		{
			$str = $this->allow_if('image_attach', $str);
			$str = str_replace("{include:image_attachments}", $image_str,	$str);
		}

		if ($file_str == '' OR $this->fetch_pref('board_attach_types') == 'img')
		{
			$str = str_replace("{include:file_attachments}", '',	$str);
			$str = $this->deny_if('file_attach', $str);
		}
		else
		{
			$str = $this->allow_if('file_attach', $str);
			$str = str_replace("{include:file_attachments}", $file_str,	$str);
		}

		return $str;
	}

	/**
	 * Display Attachment
	 */
	public function display_attachment()
	{
		$attach_hash = ee()->input->get_post('aid');
		$forum_id  = ee()->input->get_post('fid');

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

		ee()->db->select('filehash, filename, extension, hits, is_image');
		ee()->db->where('filehash', $attach_hash);
		$query = ee()->db->get('forum_attachments');

		if ($query->num_rows() == 0)
		{
			exit;
		}

		$thumb_prefix =  ($query->row('is_image')  == 'y' && $this->fetch_pref('board_use_img_thumbs') == 'y' && ee()->input->get_post('thumb') == 1) ? '_t' : '';

		$filepath = $this->fetch_pref('board_upload_path').$query->row('filehash') .$thumb_prefix.$query->row('extension') ;

		ee()->load->library('mime_type');
		$mime = ee()->mime_type->ofFile($filepath);

		if ( ! file_exists($filepath) OR ! isset($mime))
		{
			exit;
		}

		if ($this->fetch_pref('board_attach_types') == 'img')
		{
			if ( ! ee()->mime_type->isImage($mime) )
			{
				exit;
			}
		}

		$hits = ($query->row('hits')  == 0) ? 1 : ($query->row('hits')  + 1);

		ee()->db->set('hits', $hits);
		ee()->db->where('filehash', $attach_hash);
		ee()->db->update('forum_attachments');

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
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', ee()->localize->now).' GMT');
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

	/**
	 * Submission Page
	 */
	public function submission_page($type = '')
	{
		// Is the user logged-in?
		// If no, show the login page

		if (ee()->session->userdata('member_id') == 0)
		{
			return $this->trigger_error();
		}

		// -------------------------------------------
		// 'forum_submission_page' hook.
		//  - Allows usurping of forum submission forms
		//  - More error checking and permissions too
		//
			$edata = ee()->extensions->call('forum_submission_page', $this, $type);
			if (ee()->extensions->end_script === TRUE) return $edata;
		//
		// -------------------------------------------

		// Fetch the Forums Prefs

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

		// Submitting a New Topic
		if ($this->current_request == 'newtopic')
		{
			if (FALSE === ($meta = $this->_fetch_forum_metadata($this->current_id)))
			{
				return $this->trigger_error();
			}

			if ($meta[$this->current_id]['forum_is_cat'] == 'y')
			{
				return $this->trigger_error('not_authorized');
			}

			$data['forum_id']	 = $this->current_id;
			$data['forum_name']	 = $meta[$this->current_id]['forum_name'];
			$data['permissions'] = $meta[$this->current_id]['forum_permissions'];
			$data['forum_max_post_chars'] = $meta[$this->current_id]['forum_max_post_chars'];
			$data['forum_allow_img_urls'] = $meta[$this->current_id]['forum_allow_img_urls'];
		}
		elseif ($this->current_request == 'newreply' OR $this->current_request == 'quotetopic' OR $this->current_request == 'quotereply')
		{
			// Submitting a New Post
			// We have to fetch the body of the quoted post and wrap it in [quote] tags

			if ($this->current_request == 'quotereply')
			{
				if (FALSE === ($meta = $this->_fetch_post_metadata($this->current_id)))
				{
					return $this->trigger_error();
				}

				// Load the form and string helper
				ee()->load->helper(array('security', 'form'));

				$data['body'] = '[quote author="'.$this->_convert_special_chars($meta[$this->current_id]['screen_name']).'" date="'.$meta[$this->current_id]['post_date'].'"]'.
								str_replace('&amp;#40;', '&#40;', encode_php_tags(form_prep($meta[$this->current_id]['body']))).
								'[/quote]';

				$this->current_id = $meta[$this->current_id]['topic_id'];
			}

			if (FALSE === ($meta = $this->_fetch_topic_metadata($this->current_id)))
			{
				return $this->trigger_error();
			}


			if ($meta[$this->current_id]['forum_is_cat'] == 'y')
			{
				return $this->trigger_error('not_authorized');
			}

			// If a TOPIC is being "quoted" we have to wrap the body in [quote] tags

			if ($this->current_request == 'quotetopic')
			{
				// Load the form helper
				ee()->load->helper(array('security', 'form'));

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
		elseif ($this->current_request == 'edittopic') // Editing a topic
		{
			if (FALSE === ($meta = $this->_fetch_topic_metadata($this->current_id)))
			{
				return $this->trigger_error();
			}

			if ($meta[$this->current_id]['forum_is_cat'] == 'y')
			{
				return $this->trigger_error('not_authorized');
			}

			// If the user performing the edit is not the original author
			// we'll verify that they have the proper permissions

			if ($meta[$this->current_id]['author_id'] != ee()->session->userdata('member_id')  AND ! $this->_mod_permission('can_edit', $meta[$this->current_id]['forum_id']))
			{
				return $this->trigger_error('not_authorized');
			}

			// Load the form helper
			ee()->load->helper(array('security', 'form'));

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
				$query = ee()->db->query("SELECT poll_question, poll_answers FROM exp_forum_polls WHERE topic_id = '{$this->current_id}'");

				if ($query->num_rows() == 1)
				{
					$data['poll_question']	= stripslashes($query->row('poll_question') );
					$data['poll_answers']	= $this->array_stripslashes(unserialize($query->row('poll_answers') ));
				}
			}
		}
		elseif ($this->current_request == 'editreply') // Editing a Post
		{
			if (FALSE === ($meta = $this->_fetch_post_metadata($this->current_id)))
			{
				return $this->trigger_error();
			}

			if ($meta[$this->current_id]['forum_is_cat'] == 'y')
			{
				return $this->trigger_error('not_authorized');
			}

			// If the user performing the edit is not the orginal author we'll verify that they have the proper permissions

			if ($meta[$this->current_id]['author_id'] != ee()->session->userdata('member_id') AND ! $this->_mod_permission('can_edit', $meta[$this->current_id]['forum_id']))
			{
				return $this->trigger_error('not_authorized');
			}

			// Load the form helper
			ee()->load->helper(array('security', 'form'));

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

		// Are RSS Feeds enabled?
		if ($meta[$this->current_id]['forum_enable_rss']== 'y')
		{
			$this->feeds_enabled = TRUE;
			$this->feed_ids = $data['forum_id'];
		}

		// Check the author permmissions
		$data['permissions'] = unserialize(stripslashes($data['permissions']));

		if (in_array($this->current_request, array('newtopic', 'edittopic')) && ! $this->_permission('can_post_topics', $data['permissions']))
		{
			if ( ! $this->_mod_permission('can_edit', $data['forum_id']))
			{
				return $this->trigger_error('not_authorized');
			}
		}

		if (in_array($this->current_request, array('newreply', 'editreply')) && ! $this->_permission('can_post_reply', $data['permissions']))
		{
			if ( ! $this->_mod_permission('can_edit', $data['forum_id']))
			{
				return $this->trigger_error('not_authorized');
			}
		}

		return $this->var_swap($this->load_element('submission_page'),
								array(
										'include:submission_form'	=> $this->_submission_form($data),
										'include:topic_review'		=> $this->thread_review(),
										'lang:max_attach_size'		=> lang('max_attach_size').'&nbsp;'.$this->fetch_pref('board_max_attach_size').'KB'
										)
								);
	}

	/**
	 * Forum Submission Form
	 */
	function _submission_form($data)
	{
		// Load Template
		$str = $this->load_element('submission_form');

		// -------------------------------------------
		// 'forum_submission_form_start' hook.
		//  - Allows usurping of forum submission form
		//
			if (ee()->extensions->active_hook('forum_submission_form_start') === TRUE)
			{
				$str = ee()->extensions->call('forum_submission_form_start', $this, $str);
				if (ee()->extensions->end_script === TRUE) return $str;
			}
		//
		// -------------------------------------------

		// Spell Check
		if ( ! defined('NL'))  define('NL',  "\n");

		if ( ! class_exists('EE_Spellcheck'))
		{
			require APPPATH.'libraries/Spellcheck.php';
		}

		if ($this->SPELL === FALSE)
		{
			$this->SPELL = new EE_Spellcheck();
			$this->spellcheck_enabled = $this->SPELL->enabled;
		}

		if ($this->spellcheck_enabled === TRUE)
		{
			$str = $this->allow_if('spellcheck', $str);
		}
		else
		{
			$str = $this->deny_if('spellcheck', $str);
		}

		ee()->lang->loadfile('spellcheck');

		// Swap the "submit" button for "update" if editing
		if ($this->current_request == 'edittopic' OR $this->current_request == 'editreply')
		{
			$str = str_replace('{lang:submit_post}', '{lang:update_post}', $str);
		}

		// Are they submitting a topic?
		if (in_array($this->current_request, array('newtopic', 'edittopic')))
		{
			$this->form_actions['forum:submit_post']['RET'] = $this->forum_path('/viewforum/'.$this->current_id.'/');

			// If we are editing a topic we'll set the topic_id as a hidden field

			if ($data['topic_id'] != '')
			{
				$this->form_actions['forum:submit_post']['topic_id'] = $data['topic_id'];
			}

			// Can they access posting options?
			$show_more_options = FALSE;

			if ( ! $this->_mod_permission('can_change_status', $data['forum_id']))
			{
				$str = $this->deny_if('can_change_status', $str);
			}
			else
			{
				$show_more_options = TRUE;
			}

			if ( ! $this->_mod_permission('can_announce', $data['forum_id']))
			{
				$str = $this->deny_if('can_announce', $str);
			}
			else
			{
				$show_more_options = TRUE;
			}

			if ( ! $this->_mod_permission('is_moderator', $data['forum_id']))
			{
				$str = $this->deny_if('is_moderator', $str);
			}
			else
			{
				$show_more_options = TRUE;
			}

			if ($show_more_options == TRUE)
			{
				$str = $this->allow_if('show_more_options', $str);
			}
			else
			{
				$str = $this->deny_if('show_more_options', $str);
			}

			// Swap the vars...
			$str = str_replace('{sticky_checked}', (($data['sticky'] == 'y' OR ee()->input->post('sticky') == 'y') ? ' checked="checked" ' : ''), $str);
			$str = str_replace('{status_checked}', (($data['status'] == 'c' OR ee()->input->post('status') == 'c') ? ' checked="checked" ' : ''), $str);
			$str = str_replace('{announce_checked}', (($data['announcement'] != 'n' OR ee()->input->post('announcement') != FALSE) ? ' checked="checked" ' : ''), $str);
			$str = str_replace('{type_all_checked}', (($data['announcement'] == 'n' OR $data['announcement'] == 'a' OR ee()->input->post('ann_type') == 'a') ? ' checked="checked" ' : ''), $str);
			$str = str_replace('{type_one_checked}', (($data['announcement'] == 't' OR ee()->input->post('ann_type') == 't') ? ' checked="checked" ' : ''), $str);

			$str = $this->allow_if('is_topic', $str);
			$str = $this->deny_if('is_post', $str);
			$str = $this->allow_if('is_moderator', $str);
			$str = $this->allow_if('can_announce', $str);
			$str = $this->allow_if('can_change_status', $str);

			// Only moderators with edit privileges or admins can edit polls
			if ($this->current_request == 'edittopic')
			{
				if ($this->_mod_permission('can_edit', $data['forum_id']) OR ! is_array($data['poll_answers']))
				{
					$str = $this->allow_if('can_post_poll', $str);
				}
				else
				{
					$str = $this->deny_if('can_post_poll', $str);

					if (is_array($data['poll_answers']))
					{
						$this->form_actions['forum:submit_post']['poll_exists'] = 1;
					}
				}
			}
			else
			{
				$str = $this->allow_if('can_post_poll', $str);
			}
		}
		else // Are they submitting a post?
		{
			if ($this->current_request == 'quotetopic')
			{
				$res = ee()->db->query("SELECT announcement FROM exp_forum_topics WHERE topic_id = '{$data['topic_id']}'");

				if ($res->row('announcement')  != 'n')
				{
					return $this->trigger_error('cant_quote_an');
				}
			}

			// If we are editing a post we'll set the post as a hidden field
			if ($data['post_id'] != '')
			{
				$this->form_actions['forum:submit_post']['post_id'] = $data['post_id'];
			}

			$this->form_actions['forum:submit_post']['RET'] = $this->forum_path('/viewthread/'.$this->current_id.'/');
			$this->form_actions['forum:submit_post']['topic_id'] = $data['topic_id'];
			$this->form_actions['forum:submit_post']['forum_id'] = $data['forum_id'];

			// Clear out some variables that are only used on New Topics
			$str = str_replace('{type_all_checked}', '', $str);
			$str = str_replace('{type_one_checked}', '', $str);

			$str = $this->deny_if('is_topic', $str);
			$str = $this->deny_if('can_post_poll', $str);
			$str = $this->allow_if('is_post', $str);
			$str = $this->deny_if('is_moderator', $str);
			$str = $this->deny_if('can_announce', $str);
			$str = $this->deny_if('can_change_status', $str);
			$str = $this->deny_if('show_more_options', $str);
		}

		// Can they upload files?
		if ( ! $this->_permission('can_upload_files', $data['permissions']) OR $this->fetch_pref('board_upload_path') == '')
		{
			$str = $this->deny_if('attachments_exist', $str);
			$str = $this->deny_if('can_upload', $str);
		}

		$str = $this->allow_if('can_upload', $str);

		// Create the HTML formatting buttons
		$buttons = '';
		if ( ! class_exists('Html_buttons'))
		{
			if (include_once(APPPATH.'libraries/Html_buttons.php'))
			{
				$BUTT = new EE_Html_buttons();
				$BUTT->allow_img = ($data['forum_allow_img_urls'] == 'y') ? TRUE : FALSE;
				$buttons = $BUTT->create_buttons();
			}
		}

		// Does $_POST['attach'] exist
		// Since we allow multiple attachments, as each one is
		// added we set the attachment ID as a hidden field.
		// This is done by the $this->_attach_file() function.
		// As a first step we'll grab the attachment IDs so
		// we can generate the list of attachments later on.

		if (count($this->attachments) == 0 && ee()->input->post('attach') != '')
		{
			if (strpos(ee()->input->post('attach'), '|') === FALSE)
			{
				$this->attachments[] = ee()->input->post('attach');
			}
			else
			{
				foreach (explode("|", ee()->input->post('attach')) as $val)
				{
					$this->attachments[] = $val;
				}
			}
		}

		// Fetch Previous Attachments if editing
		if ($this->current_request == 'edittopic' OR $this->current_request == 'editreply')
		{
			$pid = ($data['post_id'] == '') ? 0 : $data['post_id'];

			$query = ee()->db->query("SELECT attachment_id FROM exp_forum_attachments WHERE topic_id = '".$data['topic_id']."' AND post_id = '{$pid}'");

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

		// Build Attachment Rows
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

			$str = $this->allow_if('attachments_exist', $str);

			$str = str_replace('{include:form_attachments}', $this->_form_attachments(), $str);
		}
		else
		{
			$str = $this->deny_if('attachments_exist', $str);
		}

		// Parse the poll stuff
		$poll_answer_field = $this->load_element('poll_answer_field');
		$vote_count_field = $this->load_element('poll_vote_count_field');
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

		// Set the "parse smileys" checkbox
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
					$query = ee()->db->query("SELECT parse_smileys FROM exp_forum_topics WHERE topic_id = '".$data['topic_id']."'");
					$smileys = ($query->row('parse_smileys')  == 'y') ? ' checked="checked" ' : '';
				}
				elseif ($this->current_request == 'editreply')
				{
					$query = ee()->db->query("SELECT parse_smileys FROM exp_forum_posts WHERE post_id = '".$data['post_id']."'");
					$smileys = ($query->row('parse_smileys')  == 'y') ? ' checked="checked" ' : '';
				}
				else
				{
					$smileys = ' checked="checked" ';
				}
			}
		}

		// Set the "notify" checkbox
		if (($this->current_request == 'edittopic' OR $this->current_request == 'editreply') &&
			! isset($_POST['notify']))
		{
			$aid = ( ! isset($data['author_id'])) ? ee()->session->userdata('member_id') : $data['author_id'];

			$query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_forum_subscriptions WHERE topic_id = '".$data['topic_id']."' AND member_id = '{$aid}'");

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
			if ( ! isset($_POST['topic_id']) AND ! isset($_POST['post_id']) AND (ee()->session->userdata('notify_by_default') == 'y'))
			{
				$notify = ' checked="checked" ';
			}
		}

		// Parse the template
		ee()->load->helper('form');

		$body = ( ! ee()->input->post('body'))	? $data['body']  : form_prep(ee()->input->post('body'));
		$body = $this->convert_forum_tags(ee()->functions->encode_ee_tags($body, TRUE));

		$title = ( ! ee()->input->post('title'))  ? form_prep($data['title']) : stripslashes(form_prep(ee()->input->post('title')));
		$title = $this->convert_forum_tags(ee()->functions->encode_ee_tags($title, TRUE));

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
			if (ee()->extensions->active_hook('forum_submission_form_end') === TRUE)
			{
				$str = ee()->extensions->call('forum_submission_form_end', $this, $str);
				if (ee()->extensions->end_script === TRUE) return $str;
			}
		//
		// -------------------------------------------


		return $this->var_swap($str,
								array(
										'title'						=> $title,
										'body'						=> $body,
										'lang:submission_heading' 	=> lang($data['type']),
										'forum_name'				=> $data['forum_name'],
										'topic_title'				=> $this->_convert_special_chars($data['title'], TRUE),
										'poll_question'				=> ( ! isset($_POST['poll_question'])) ? $this->_convert_special_chars($data['poll_question']) : $this->_convert_special_chars(stripslashes($_POST['poll_question'])),
										'include:poll_answers'		=> $poll_answers,
										'poll_answer_field'			=> $poll_field,
										'poll_rownum'				=> $poll_rownum,
										'lang:post_poll'			=> ($data['poll_question'] != '' OR isset($_POST['poll_question'])) ? lang('edit_poll') : lang('add_a_poll'),
										'notify_checked'			=> $notify,
										'smileys_checked'			=> $smileys,
										'include:html_formatting_buttons' => $buttons,
										'maxchars'					=> $maxchars,
										'total_characters'			=> $totchars
									)
								);
	}

	/**
	 * Attachemnt Rows
	 *
	 * When previewing or adding attachments in a new post
	 * this function shows all the current attachments before submitting
	 */
	function _form_attachments()
	{
		$template = $this->load_element('form_attachment_rows');

		$str = '';
		$kbs = 0;
		foreach ($this->attachments as $id)
		{
			$query = ee()->db->query("SELECT filename, filesize, extension FROM exp_forum_attachments WHERE attachment_id = '{$id}'");

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

		$size = ($this->fetch_pref('board_max_attach_size') - $kbs);

		return $this->var_swap($this->load_element('form_attachments'),
								array(
										'lang:remaining_space' 			=> str_replace('%x', $size.' KB', lang('remaining_space')),
										'lang:total_attach_allowed'		=> lang('total_attach_allowed').'&nbsp;'.$this->fetch_pref('board_max_attach_perpost'),
										'include:form_attachment_rows'	=> $str
									)
								);
	}

	/**
	 * Fast Reply Form
	 */
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

		if (ee()->session->userdata('member_id') == 0)
		{
			return '';
		}

		$this->form_actions['forum:submit_post']['RET'] = $this->forum_path('/viewthread/'.$this->current_id.'/');
		$this->form_actions['forum:submit_post']['topic_id'] = $this->current_id;
		$this->form_actions['forum:submit_post']['forum_id'] = $meta[$this->current_id]['forum_id'];
		$this->form_actions['forum:submit_post']['smileys'] = 'y';

		$notify = (ee()->session->userdata('notify_by_default') == 'y' OR ee()->input->post('notify') == 'y') ? ' checked="checked" ' : '';

		$template = $this->load_element('fast_reply_form');

		$template = str_replace('{notify_checked}', $notify, $template);

		return $template;
	}

	/**
	 * Fetch Superadmins
	 */
	public function fetch_superadmins()
	{
		$super_admins = array();

		ee()->db->select('member_id');
		$ad_query = ee()->db->get_where('members', array('group_id' => 1));

		foreach ($ad_query->result_array() as $row)
		{
			$super_admins[] = $row['member_id'];
		}

		return $super_admins;
	}

	/**
	 * Moderation method used by the spam module. Takes a query generated from
	 * the submit_post method.
	 */
	public function moderate_post($query)
	{
		ee()->db->query($sql);
	}

	/**
	 * Display errors
	 */
	public function display_errors()
	{
		if (ee()->input->post('preview') !== FALSE OR $this->submission_error != '')
		{
			$type = array(
					'newtopic'		=> 'new_topic_page',
					'edittopic'		=> 'edit_topic_page',
					'newreply'		=> 'new_reply_page',
					'editreply'		=> 'edit_reply_page',
					'quotetopic'	=> 'new_reply_page',
					'quotereply'	=> 'new_reply_page'
					);

			if ($this->use_trigger())
			{
				return $this->display_forum($type[$this->current_request]);
			}

			if (count($this->attachments) > 0)
			{
				$_POST['attach'] = implode('|', $this->attachments);
			}

			// Then we are in a template.  We have to call this template.  Dude.
			// We still have to send the preview information though.  Curious.

			ee()->functions->clear_caching('all');

			unset($_POST['ACT']);

			if ( ! isset(ee()->TMPL))
			{
				ee()->load->library('template', NULL, 'TMPL');
			}

			$x = explode('/',$this->trigger);

			if ( ! isset($x[1]))
			{
				$query = ee()->db->query("SELECT tg.group_name
									 FROM exp_templates t, exp_template_groups tg
									 WHERE t.group_id = tg.group_id
									 AND t.template_name = '".ee()->db->escape_str($x['0'])."'
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
			ee()->session->cache['forum']['submission_error'] = $this->submission_error;

			ee()->TMPL->run_template_engine();
		}
	}

	/**
	 * Submission Error Display
	 */
	function submission_errors()
	{
		if (isset(ee()->session->cache['forum']['submission_error']))
		{
			$this->submission_error = ee()->session->cache['forum']['submission_error'];
		}

		if ($this->submission_error == '')
		{
			return '';
		}

		return $this->var_swap($this->load_element('submission_errors'),
								array(
										'message' => $this->submission_error
									)
								);
	}

	/**
	 * Post Preview
	 */
	function preview_post()
	{
		if (ee()->input->post('preview') === FALSE OR ee()->input->post('body') == '' OR $this->preview_override == TRUE)
		{
			return '';
		}

		ee()->load->library('typography');
		ee()->typography->initialize(array(
			'parse_smileys' => (isset($_POST['smileys'])) ? TRUE : FALSE
		));

		$forum_text_formatting  = 'xhtml';
		$forum_html_formatting 	= 'safe';
		$forum_auto_link_urls  	= 'y';
		$forum_allow_img_urls	= 'y';

		switch($this->current_request)
		{
			case 'newtopic'		:
									$query = ee()->db->query("SELECT f.forum_id, f.forum_text_formatting, f.forum_html_formatting, f.forum_enable_rss, f.forum_auto_link_urls, f.forum_allow_img_urls FROM exp_forums f WHERE f.forum_id = '{$this->current_id}'");

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
									$query = ee()->db->query("SELECT f.forum_id, f.forum_text_formatting, f.forum_html_formatting, f.forum_enable_rss, f.forum_auto_link_urls, f.forum_allow_img_urls FROM exp_forums f, exp_forum_topics t WHERE f.forum_id = t.forum_id AND t.topic_id = '{$this->current_id}'");

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
									$query = ee()->db->query("SELECT f.forum_id, f.forum_text_formatting, f.forum_html_formatting, f.forum_enable_rss, f.forum_auto_link_urls, f.forum_allow_img_urls FROM exp_forums f, exp_forum_posts p WHERE f.forum_id = p.forum_id AND p.post_id = '{$this->current_id}'");

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

		$body = str_replace('{include:', '&#123;include:', ee()->input->post('body'));
		$body = str_replace('{path:', '&#123;path:', $body);
		$body = str_replace('{lang:', '&#123;lang:', $body);

		$body = $this->_quote_decode(
							ee()->typography->parse_type(
														ee('Security/XSS')->clean($body),
									 					array(
																'text_format'	=> $forum_text_formatting,
																'html_format'	=> $forum_html_formatting ,
																'auto_links'	=> $forum_auto_link_urls,
																'allow_img_url' => $forum_allow_img_urls
																	)
										)

								);

		$title = str_replace('{include:', '&#123;include:', ee('Security/XSS')->clean(ee()->input->post('title')));

		return $this->var_swap($this->load_element('preview_post'),
								array(
										'post_title'	=> stripslashes($this->_convert_special_chars($title)),
										'post_body' 	=> $body,
									)
								);
	}

	/**
	 * Upload and Attach File
	 */
	function _attach_file($is_preview = FALSE)
	{
		// Fetch Prefs
		$query = ee()->db->query("SELECT board_upload_path,
									board_max_attach_perpost,
									board_max_attach_size,
									board_max_width,
									board_max_height,
									board_use_img_thumbs,
									board_attach_types,
									board_thumb_width,
									board_thumb_height
								FROM exp_forum_boards
								WHERE board_id = '".$this->fetch_pref('board_id')."'");

		// Check the paths
		if ($query->row('board_upload_path')  == '')
		{
			return $this->submission_error = lang('unable_to_recieve_attach');
		}

		$board_upload_path = parse_config_variables($query->row('board_upload_path'));
		if ( ! @is_dir($board_upload_path) OR ! is_really_writable($board_upload_path))
		{
			return $this->submission_error = lang('unable_to_recieve_attach');
		}

		// Are there previous attachments?

		// Since you can attach more than one attachment per post
		// we look for the $_POST['attach'] variable to see if
		// they have previously attached items

		$attach_ids = array();

		if (ee()->input->post('attach') != '')
		{
			if (strpos(ee()->input->post('attach'), '|') === FALSE)
			{
				$attach_ids[] = ee()->input->post('attach');
			}
			else
			{
				foreach (explode("|", ee()->input->post('attach')) as $val)
				{
					$attach_ids[] = $val;
				}
			}
		}

		// Are they exceeding the allowed total?
		if ((count($attach_ids) + 1) > $query->row('board_max_attach_perpost') )
		{
			return $this->submission_error = str_replace("%x", $query->row('board_max_attach_perpost') , lang('too_many_attachments'));
		}

		// Fetch the size of the previous attachments
		$total = 0;

		if (count($attach_ids) > 0)
		{
			foreach ($attach_ids as $val)
			{
				ee()->db->select('filesize');
				ee()->db->where('attachment_id', $val);
				$result = ee()->db->get('forum_attachments');

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
			return $this->submission_error = str_replace("%x", $query->row('board_max_attach_size') , lang("file_too_big"));
		}

		$filehash = ee()->functions->random('alnum', 20);

		// Upload the image
		$server_path = $board_upload_path;

		// Upload the image
		$config = array(
				'upload_path'	=> $server_path,
				'max_size'		=> $query->row('board_max_attach_size')
		);

		if ($query->row('board_attach_types') !== 'all')
		{
			$config['is_image'] = TRUE;
		}

		if (ee()->config->item('xss_clean_uploads') == 'n')
		{
			$config['xss_clean'] = FALSE;
		}
		else
		{
			$config['xss_clean'] = (ee()->session->userdata('group_id') === 1) ? FALSE : TRUE;
		}

		ee()->load->library('upload', $config);

		if (ee()->upload->do_upload() === FALSE)
		{
			return $this->submission_error = lang(ee()->upload->display_errors());
		}

		$upload_data = ee()->upload->data();

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
				$error = str_replace('%x', $query->row('board_max_width') , lang("dimensions_too_big"));
				$error = str_replace('%y', $query->row('board_max_height') , $error);
				return $this->submission_error = $error;
			}

			if ($query->row('board_use_img_thumbs')  == 'y')
			{
				$res_config = array(
					'image_library'		=> ee()->config->item('image_resize_protocol'),
					'library_path'		=> ee()->config->item('image_library_path'),
					'maintain_ratio'	=> TRUE,
					'new_image'			=> $board_upload_path.$filehash.'_t'.$upload_data['file_ext'],
					'master_dim'		=> 'height',
					'thumb_marker'		=> '_t',
					'source_image'		=> $upload_data['full_path'],
					'quality'			=> 75,
					'width'			=> ($query->row('board_thumb_width')  < $width) ? $query->row('board_thumb_width')  : $width,
					'height'		=> ($query->row('board_thumb_height')  < $height) ? $query->row('board_thumb_height')  : $height				);

				ee()->load->library('image_lib', $res_config);

				if (ee()->image_lib->resize())
				{
					$props = ee()->image_lib->get_image_properties($board_upload_path.$filehash.'_t'.$upload_data['file_ext'], TRUE);

					$t_width  = $props['width'];
					$t_height = $props['height'];
				}
			}
		}

		// Build the column data
		$data = array(
						'topic_id'			=> 0,
						'post_id'			=> 0,
						'board_id'			=> 0,
						'member_id'			=> ee()->session->userdata('member_id'),
						'filename'			=> $upload_data['file_name'],
						'filehash'			=> $filehash,
						'filesize'			=> ceil($upload_data['file_size']),
						'extension'			=> $upload_data['file_ext'],
						'attachment_date'	=> ee()->localize->now,
						'is_temp'			=> ($is_preview == TRUE OR $this->submission_error != '') ? 'y' : 'n',
						'width'				=>  $width,
						'height'			=>  $height,
						't_width'			=>  $t_width,
						't_height'			=>  $t_height,
						'is_image'			=> ($upload_data['is_image']) ? 'y' : 'n'
					);


		ee()->db->insert('forum_attachments', $data);

		$attach_id = ee()->db->insert_id();

		// Change file name with attach ID

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

				ee()->db->set('filehash', $final_name);
				ee()->db->where('attachment_id', $attach_id);
				ee()->db->update('forum_attachments');
			}
		}

		// Are there previous attachments?
		$this->attachments[] = $attach_id;

		if (count($attach_ids) > 0)
		{
			foreach ($attach_ids as $val)
			{
				$this->attachments[] = $val;
			}
		}

		// Is this a preview request
		// If so it means they are manually triggering the upload
		// so we'll disable errors;

		if ($is_preview == TRUE)
		{
			$this->preview_override = TRUE;
			$this->submission_error = '';
		}

		// Delete expired images
		$expire = ee()->localize->now - 10800; // Three hours ago

		ee()->db->select('attachment_id, filehash, extension');
		ee()->db->where('attachment_date < ', $expire);
		ee()->db->where('is_temp', 'y');

		$result = ee()->db->get('forum_attachments');

		if ($result->num_rows() > 0)
		{
			foreach ($result->result_array() as $row)
			{
				@unlink($upload_data['file_path'].$row['attachment_id'].'_'.$row['filehash'].$row['extension']);
				@unlink($upload_data['file_path'].$row['attachment_id'].'_'.$row['filehash'].'_t'.$row['extension']);
			}

			ee()->db->where('attachment_date <', $expire);
			ee()->db->where('is_temp', 'y');
			ee()->db->delete('forum_attachments');
		}

		return TRUE;
	}

	/**
	 * Remove post attachment
	 *
	 * @param	int		Attachment ID to delete
	 * @param	int		Board ID attachment resides in
	 * @param	bool	Whether or not to force the delete and ignore whether
	 *		or not the user has permission to remove attchments; this is
	 *		mainly reserved for member deletion where attachments should be
	 *		deleted no matter what
	 */
	function _remove_attachment($id, $forum_id, $force = FALSE)
	{
		// Load preferences if they're not already there
		if ( ! count($this->preferences))
		{
			$this->_load_preferences();
		}

		ee()->db->select('filehash, extension, member_id');
		ee()->db->where(array('attachment_id' => $id));
		$query = ee()->db->get('forum_attachments');

		// make sure the attachment exists and the user is allowed to remove it
		if ($query->num_rows() == 0
			OR (ee()->session->userdata('member_id') != $query->row('member_id')
				AND $this->_mod_permission('can_edit', $forum_id) === FALSE
				AND $force === FALSE)
			)
		{
			return;
		}

		$file  = $this->fetch_pref('board_upload_path').$query->row('filehash') .$query->row('extension') ;
		$thumb = $this->fetch_pref('board_upload_path').$query->row('filehash') .'_t'.$query->row('extension') ;

		@unlink($file);
		@unlink($thumb);

		$_POST['preview'] = 1;

		$this->preview_override = TRUE;
		$this->submission_error = '';

		ee()->db->query("DELETE FROM exp_forum_attachments WHERE attachment_id = '{$id}'");

		if (ee()->input->get_post('attach') == '' OR strpos(ee()->input->get_post('attach'), '|') === FALSE)
		{
			unset($_POST['attach']);
			return;
		}

		$attach_ids = array();

		foreach (explode("|", ee()->input->get_post('attach')) as $val)
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

	/**
	 * Forum Submission Handler
	 */
	public function submit_post()
	{
		if ($this->current_id == '')
		{
			return;
		}

		$type = (in_array($this->current_request, array('newtopic', 'edittopic'))) ? 'topic' : 'thread';

		// Is the user logged in?
		if (ee()->session->userdata('member_id') == 0)
		{
			return $this->trigger_error();
		}

		// Is the user banned?
		if (ee()->session->userdata['is_banned'] == TRUE)
		{
			return $this->trigger_error();
		}

		// Blacklist/Whitelist Check
		if (ee()->blacklist->blacklisted == 'y' && ee()->blacklist->whitelisted == 'n')
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

	 	// Is the IP or User Agent unavalable?
		if (ee()->config->item('require_ip_for_posting') == 'y')
		{
			if (ee()->input->ip_address() == '0.0.0.0' OR ee()->session->userdata['user_agent'] == "")
			{
				return $this->trigger_error();
			}
		}

		if ($type == 'topic' AND trim(ee()->input->post('title')) == '')
		{
			$this->submission_error = lang('empty_title_field');
		}

		// Is the body blank?
		if (trim(ee()->input->post('body')) == '')
		{
			$this->submission_error = lang('empty_body_field');
		}

		// -------------------------------------------
		// 'forum_submit_post_start' hook.
		//  - Allows usurping of forum submission routine
		//  - More error checking and permissions too
		//
			$edata = ee()->extensions->call('forum_submit_post_start', $this);
			if (ee()->extensions->end_script === TRUE) return $edata;
		//
		// -------------------------------------------

		// Fetch meta-data and do security checks
		if ($this->current_request == 'newtopic')
		{
			if (FALSE === ($meta = $this->_fetch_forum_metadata($this->current_id)))
			{
				return $this->trigger_error();
			}

			if ($meta[$this->current_id]['forum_is_cat'] == 'y' OR $meta[$this->current_id]['forum_status'] == 'a')
			{
				return $this->trigger_error('not_authorized');
			}

			$orig_author_id			= ee()->session->userdata('member_id');
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
				return $this->trigger_error('topic_no_exists');
			}

			if ($meta[$this->current_id]['forum_is_cat'] == 'y' OR ($meta[$this->current_id]['status'] == 'c' AND ee()->session->userdata('group_id') != 1) OR $meta[$this->current_id]['forum_status'] == 'a')
			{
				return $this->trigger_error('not_authorized');
			}

			$orig_author_id			= ee()->session->userdata('member_id');
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
			// no tampering
			if ($this->current_id != ee()->input->post('topic_id'))
			{
				return $this->trigger_error();
			}

			if (FALSE === ($meta = $this->_fetch_topic_metadata($this->current_id)))
			{
				return $this->trigger_error();
			}

			if ($meta[$this->current_id]['forum_is_cat'] == 'y' OR $meta[$this->current_id]['forum_status'] == 'a')
			{
				return $this->trigger_error('not_authorized');
			}

			// If the user performing the edit is not the orginal author
			// we'll verify that they have the proper permissions

			if ($meta[$this->current_id]['author_id'] != ee()->session->userdata('member_id')  AND ! $this->_mod_permission('can_edit', $meta[$this->current_id]['forum_id']))
			{
				return $this->trigger_error('not_authorized');
			}

			$orig_author_id 		= $meta[$this->current_id]['author_id'];
			$fdata['forum_id']		= $meta[$this->current_id]['forum_id'];
			$fdata['permissions']	= $meta[$this->current_id]['forum_permissions'];
			$fdata['forum_max_post_chars']	= $meta[$this->current_id]['forum_max_post_chars'];
		}
		elseif ($this->current_request == 'editreply' OR $this->current_request == 'quotereply')
		{
			// no tampering
			if ($this->current_id != ee()->input->post('post_id'))
			{
				return $this->trigger_error();
			}

			if (FALSE === ($meta = $this->_fetch_post_metadata($this->current_id)))
			{
				return $this->trigger_error();
			}

			if ($meta[$this->current_id]['forum_is_cat'] == 'y' OR $meta[$this->current_id]['forum_status'] == 'a')
			{
				return $this->trigger_error('not_authorized');
			}

			// If the user performing the edit is not the orginal author we'll verify that they have the proper permissions

			if ($this->current_request == 'editreply')
			{
				if ($meta[$this->current_id]['author_id'] != ee()->session->userdata('member_id'))
				{
				 	if (! $this->_mod_permission('can_edit', $meta[$this->current_id]['forum_id']))
					{
						return $this->trigger_error('not_authorized');
					}

					//  Fetch the Super Admin IDs
					$super_admins = $this->fetch_superadmins();

					if (in_array($meta[$this->current_id]['author_id'], $super_admins) && ee()->session->userdata('group_id') != 1)
					{
						//return $this->trigger_error('not_authorized');
					}
				}
			}

			$orig_author_id			= $meta[$this->current_id]['author_id'];
			$fdata['forum_id']		= $meta[$this->current_id]['forum_id'];
			$fdata['permissions']	= $meta[$this->current_id]['forum_permissions'];
			$fdata['forum_max_post_chars']	= $meta[$this->current_id]['forum_max_post_chars'];
			$post_per_page = $meta[$this->current_id]['forum_posts_perpage'];
		}

		// Check the author permissions
		$fdata['permissions'] = unserialize(stripslashes($fdata['permissions']));

		if (in_array($this->current_request, array('newtopic', 'edittopic')) &&
			! $this->_permission('can_post_topics', $fdata['permissions']))
		{
			if ( ! $this->_mod_permission('can_edit', $fdata['forum_id']))
			{
				return $this->trigger_error('not_authorized');
			}
		}

		if (in_array($this->current_request, array('newreply', 'editreply')) &&
			! $this->_permission('can_post_reply', $fdata['permissions']))
		{
			if ( ! $this->_mod_permission('can_edit', $fdata['forum_id']))
			{
				return $this->trigger_error('not_authorized');
			}
		}

		// Throttle check
		if (ee()->session->userdata('group_id') != 1)
		{
			$query = ee()->db->query("SELECT forum_post_timelock FROM exp_forums WHERE forum_id = '{$meta[$this->current_id]['forum_id']}'");

			if ($query->num_rows() == 0)
			{
				return $this->trigger_error();
			}

			if ($query->row('forum_post_timelock')  > 0)
			{
				if ((ee()->session->userdata('last_forum_post_date') + $query->row('forum_post_timelock') ) > ee()->localize->now)
				{
					$this->submission_error = str_replace('%x', $query->row('forum_post_timelock') , lang('post_throttle'));
				}
			}
		}

		// Do we allow duplicate data?
		if ($this->current_request != 'edittopic' AND $this->current_request != 'editreply')
		{
			if (ee()->config->item('deny_duplicate_data') == 'y' AND ee()->session->userdata['group_id'] != 1 AND ee()->input->post('body') != '')
			{
				$query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_forum_topics WHERE body = '".ee()->db->escape_str(ee()->input->post('body'))."'");

				if ($query->row('count')  > 0)
				{
					$this->submission_error = lang('duplicate_data_warning');
				}

				$query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_forum_posts WHERE body = '".ee()->db->escape_str(ee()->input->post('body'))."'");

				if ($query->row('count')  > 0)
				{
					$this->submission_error = lang('duplicate_data_warning');
				}
			}
		}

		// Is the post too big?
		$maxchars = ($fdata['forum_max_post_chars'] == 0) ? $this->max_chars :  $fdata['forum_max_post_chars'];

		if (strlen(ee()->input->post('body')) > $maxchars)
		{
			$this->submission_error = str_replace("%x", $maxchars, lang('post_too_big'));
		}

		// Does the post include a poll?
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
				$this->submission_error = lang('poll_must_have_two_answers');
			}
		}

		// Is this a remove attachment request?
		if (isset($_POST['remove']))
		{
			$id = key($_POST['remove']);

			if (is_numeric($id))
			{
				$this->_remove_attachment($id, $fdata['forum_id']);
			}
		}

		// Do we have an attachment to deal with?
		if ($this->_permission('can_upload_files', $fdata['permissions']) &&
			$this->fetch_pref('board_upload_path') != '' &&
			isset($_FILES['userfile']['name']) && $_FILES['userfile']['name'] != '')
		{
			$preview = (ee()->input->post('preview') !== FALSE) ? TRUE : FALSE;
			$this->_attach_file($preview);
		}

		// Is this a preview request?
		// Or... do we have errors to display?
		ee()->stats->update_stats();

		// Check for spam
		$spam = FALSE;
		if (ee()->input->post('preview') == FALSE && ee()->session->userdata('group_id') != 1)
		{
			$body = ee()->input->post('body');
			$title = ee()->input->post('title');
			$text = "$title $body";
			$spam = ee('Spam')->isSpam($text);
		}

		if (ee()->input->post('preview') !== FALSE OR $this->submission_error != '')
		{
			return $this->display_errors();
		}

		$announcement = 'n';

		if (ee()->input->post('announcement') == 'y')
		{
			unset($_POST['sticky']);
			unset($_POST['status']);

			if (ee()->input->post('ann_type') == 'a')
			{
				$announcement = 'a';
			}
			else
			{
				$announcement = 't';
			}
		}

		// Sumbmit the post
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
					$title = $this->convert_forum_tags(ee()->input->post('title'));
					$body = $this->convert_forum_tags(ee()->input->post('body'));

					$data = array(
									'title'			=> ee('Security/XSS')->clean($title),
									'body'			=> ee('Security/XSS')->clean($body),
									'sticky'		=> (ee()->input->post('sticky') == 'y') ? 'y' : 'n',
									'status'		=> (ee()->input->post('status') == 'c') ? 'c' : 'o',
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

					// Insert a NEW topic
					if ($this->current_request == 'newtopic')
					{
						$data['author_id']				= ee()->session->userdata('member_id');
						$data['ip_address']				= ee()->input->ip_address();
						$data['forum_id'] 				= $this->current_id;
						$data['last_post_date'] 		= ee()->localize->now;
						$data['last_post_author_id']	= ee()->session->userdata('member_id');
						$data['thread_total']			= 1;
						$data['topic_date']				= ee()->localize->now;
						$data['board_id']				= $this->fetch_pref('board_id');

						$sql = ee()->db->insert_string('exp_forum_topics', $data);

						if ( ! $spam)
						{
							ee()->db->query($sql);
						}

						$data['topic_id'] = ee()->db->insert_id();

						// Where should we send the user to?  Normally we'll send them to either
						// the thread or the announcement page, but if they are allowed to post,
						// but not view threads we have to send them to the topic page.
						if ( ! $this->_permission('can_view_topics', $fdata['permissions']) OR $spam)
						{
							$redirect = $this->forum_path('/viewforum/'.$fdata['forum_id'].'/');
						}
						else
						{
							if ($announcement == 'n')
								$redirect = $this->forum_path('/viewthread/'.$data['topic_id'].'/');
							else
								$redirect = $this->forum_path('/viewannounce/'.$data['topic_id'].'_'.$fdata['forum_id'].'/');
						}

						// Update the forum stats

						$this->_update_post_stats($this->current_id);
						$this->_update_global_stats();

						// Update member post total
						ee()->db->where('member_id', ee()->session->userdata('member_id'));
						ee()->db->update('members',
													array('last_forum_post_date' => ee()->localize->now)
													);

						// Submit a poll if we have one
						if (isset($_POST['poll_question']) AND $_POST['poll_question'] != '' AND $announcement == 'n')
						{
							$this->_submit_poll($data['topic_id']);
						}
					}
					else // Update an existing topic
					{
						// Onward....

						$data['topic_edit_author']	= ee()->session->userdata['member_id'];
						$data['topic_edit_date']	= ee()->localize->now;

						$sql = ee()->db->update_string('exp_forum_topics', $data, array('topic_id' => ee()->input->post('topic_id')));
						ee()->db->query($sql);

						$data['topic_id'] = $this->current_id;

						if ($announcement == 'n')
							$redirect = $this->forum_path('/viewthread/'.$this->current_id.'/');
						else
							$redirect = $this->forum_path('/viewannounce/'.$this->current_id.'_'.$fdata['forum_id'].'/');

						// Update a poll if we have one
						if (isset($_POST['poll_question']) AND $_POST['poll_question'] != '' AND $announcement == 'n')
						{
							$this->_submit_poll($data['topic_id']);
						}
						else // Or delete an existing one if needed
						{
							if ( ! isset($_POST['poll_exists']))
							{
								ee()->db->where('topic_id', $data['topic_id']);
								ee()->db->delete('forum_polls');

								ee()->db->where('topic_id', $data['topic_id']);
								ee()->db->delete('forum_pollvotes');
							}
						}

						// Update the recent thread title on the home page if necessary
						ee()->db->update('forums', array('forum_last_post_title' => $data['title']), array('forum_last_post_id' => $data['topic_id'], 'forum_id' => $fdata['forum_id']));
					}

				break;
			case 'newreply'		:
			case 'editreply'	:


					// Security fix
					$body = $this->convert_forum_tags(ee()->input->post('body'));

					$data = array(
									'topic_id'		=> ee()->db->escape_str(ee()->input->post('topic_id')),
									'forum_id'		=> ee()->input->post('forum_id'),
									'body'			=> ee('Security/XSS')->clean($body),
									'parse_smileys'	=> (isset($_POST['smileys'])) ? 'y' : 'n'
								 );

					// Insert a new post
					if ($this->current_request == 'newreply')
					{
						$data['author_id']	= ee()->session->userdata('member_id');
						$data['ip_address']	= ee()->input->ip_address();
						$data['post_date']	= ee()->localize->now;
						$data['board_id']	= $this->fetch_pref('board_id');

						$sql = ee()->db->insert_string('exp_forum_posts', $data);

						if ( ! $spam)
						{
							ee()->db->query($sql);
						}

						$data['post_id'] = ee()->db->insert_id();

						// Update the topic stats (count, last post info)
						$this->_update_topic_stats($data['topic_id']);

						// Update the forum stats
						$this->_update_post_stats(ee()->input->post('forum_id'));
						$this->_update_global_stats();

						// Update member post total
						ee()->db->where('member_id', ee()->session->userdata('member_id'));
						ee()->db->update('members',
												array('last_forum_post_date' => ee()->localize->now)
											);

						// Determine the redirect location
						$page = $this->_fetch_page_number($this->thread_post_total, $post_per_page);
						$redirect = $this->forum_path('/viewthread/'.$data['topic_id'].'/'.$page);
					}
					else // Update an existing post
					{
						$data['post_id'] = ee()->input->post('post_id');
						$data['post_edit_author']	= ee()->session->userdata['member_id'];
						$data['post_edit_date']		= ee()->localize->now;

						$sql = ee()->db->update_string('exp_forum_posts', $data, "post_id='".$data['post_id']."'");
						ee()->db->query($sql);

						// Determine the redirect location
						ee()->db->select('COUNT(*) as count');
						$query = ee()->db->get_where('forum_posts', array('topic_id' => $data['topic_id']));

						$total = ($query->row('count')  + 1);

						$page = $this->_fetch_page_number($query->row('count') , $post_per_page);
						$redirect = $this->forum_path('/viewthread/'.$data['topic_id'].'/'.$page);
					}

				break;
		}

		if ($spam)
		{
			ee('Spam')->moderate('forum', $sql, $text, array('postdata' => $_POST, 'redirect' => $redirect));
			$this->submission_error = lang('spam');

			$data = array(	'title' 	=> lang('post_is_moderated'),
							'heading'	=> lang('thank_you'),
							'content'	=> lang('post_is_moderated'),
							'redirect'	=> $redirect,
							'rate'      => 8,
							'link'		=> array($redirect, '')
						 );

			return ee()->output->show_message($data);
		}

		// Fetch/Set the "topic tracker" cookie
		if ($this->current_request == 'newtopic' OR $this->current_request == 'newreply')
		{
			$read_topics = $this->_fetch_read_topics($data['topic_id']);

			if (ee()->session->userdata('member_id') == 0)
			{
				$expire = 60*60*24*365;
				ee()->input->set_cookie('forum_topics', json_encode($read_topics), $expire);
			}
		}

		// Is there an attachment to finalize
		if (ee()->input->post('attach') != '')
		{
			if (strpos(ee()->input->post('attach'), '|') === FALSE)
			{
				$this->attachments[] = ee()->input->post('attach');
			}
			else
			{
				foreach (explode("|", ee()->input->post('attach')) as $val)
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

				ee()->db->update('forum_attachments', $d, array('attachment_id' => $id));
			}
		}

		// Manage subscriptions
		if (ee()->input->post('notify') == 'y')
		{
			ee()->db->select('COUNT(*) as count');
			ee()->db->where('topic_id', $data['topic_id']);
			ee()->db->where('member_id', $orig_author_id);
			$query = ee()->db->get('forum_subscriptions');

			$row = $query->row_array();

			if ($row['count']  > 1)
			{
				ee()->db->where('topic_id', $data['topic_id']);
				ee()->db->where('member_id', $orig_author_id);
				ee()->db->delete('forum_subscriptions');

				$row['count']  = 0;
			}

			if ($row['count'] == 0)
			{
				$rand = $orig_author_id.ee()->functions->random('alnum', 8);

				$d = array(
						'topic_id'				=> $data['topic_id'],
						'board_id'				=> $this->preferences['board_id'],
						'member_id'				=> $orig_author_id,
						'subscription_date'		=> ee()->localize->now,
						'hash'					=> $rand
					);

				ee()->db->insert('forum_subscriptions', $d);
			}
		}
		else
		{
			ee()->db->where('topic_id', $data['topic_id']);
			ee()->db->where('member_id', $orig_author_id);
			ee()->db->delete('forum_subscriptions');
		}

		// Send them to their post if they have edited
		// Since we don't need to sent notifications when editing, we're done...
		if ($this->current_request == 'edittopic' OR  $this->current_request == 'editreply')
		{
			ee()->functions->redirect($redirect);
			exit;
		}

		// Email Notifications
		$notify_addresses = '';

		if ($this->current_request == 'newtopic')
		{
			$notify_addresses .= ($this->fetch_pref('board_notify_emails_topics') != '') ? ','.$this->fetch_pref('board_notify_emails_topics') : '';
		}
		else
		{
			$notify_addresses .= ($this->fetch_pref('board_notify_emails') != '') ? ','.$this->fetch_pref('board_notify_emails') : '';
		}

		// Fetch forum notification addresses
		if ($this->current_request == 'newtopic')
		{
			$notify_addresses .= ($fdata['forum_notify_emails'] != '') ? ','.$fdata['forum_notify_emails'] : '';
		}
		else
		{
			$notify_addresses .= ($fdata['forum_notify_emails_topics'] != '') ? ','.$fdata['forum_notify_emails_topics'] : '';
		}

		// Category Notification Prefs
		$cmeta = $this->_fetch_forum_metadata($fdata['forum_parent']);

		if (FALSE !== $cmeta)
		{
			if ($this->current_request == 'newtopic')
			{
				if ($cmeta[$fdata['forum_parent']]['forum_notify_emails'] != '')
				{
					$notify_addresses .= ','.$cmeta[$fdata['forum_parent']]['forum_notify_emails'];
				}
			}
			else
			{
				if ($cmeta[$fdata['forum_parent']]['forum_notify_emails_topics'] != '')
				{
					$notify_addresses .= ','.$cmeta[$fdata['forum_parent']]['forum_notify_emails_topics'];
				}
			}
		}

		// Fetch moderator addresses
		if ((isset($fdata['forum_notify_moderators']) && $fdata['forum_notify_moderators'] == 'y') OR
			($this->current_request == 'newtopic' && $cmeta[$fdata['forum_parent']]['forum_notify_moderators_topics'] == 'y') OR
			($this->current_request != 'newtopic' && $cmeta[$fdata['forum_parent']]['forum_notify_moderators_replies'] == 'y')
			)
		{
			ee()->db->select('email');
			ee()->db->from('members, forum_moderators');
			ee()->db->where('(exp_members.member_id = exp_forum_moderators.mod_member_id OR exp_members.group_id =  exp_forum_moderators.mod_group_id)', NULL, FALSE);
			ee()->db->where('exp_forum_moderators.mod_forum_id', $fdata['forum_id']);

			$query = ee()->db->get();

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$notify_addresses .= ','.$row['email'];
				}
			}
		}

		$notify_addresses = str_replace(' ', '', $notify_addresses);

		// Remove Current User Email
		// We don't want to send an admin notification if the person
		// leaving the comment is an admin in the notification list

		if ($notify_addresses != '')
		{
			if (strpos($notify_addresses, ee()->session->userdata('email')) !== FALSE)
			{
				$notify_addresses = str_replace(ee()->session->userdata('email'), "", $notify_addresses);
			}

			// Remove multiple commas
			$notify_addresses = reduce_multiples($notify_addresses, ',', TRUE);
		}

		// Strip duplicate emails
		// And while we're at it, create an array
		if ($notify_addresses != '')
		{
			$notify_addresses = array_unique(explode(",", $notify_addresses));
		}

		ee()->load->library('typography');
		ee()->typography->initialize(array(
			'parse_images' => FALSE
		));

		$query = ee()->db->query("SELECT title FROM exp_forum_topics WHERE topic_id = '".$data['topic_id']."'");

		$title = $query->row('title') ;
		$body = ee()->typography->parse_type($data['body'],
										array(
												'text_format'	=> 'none',
												'html_format'	=> 'none',
												'auto_links'	=> 'n',
												'allow_img_url' => 'n'
											)
									);

		// Send admin notification
		if (is_array($notify_addresses) AND count($notify_addresses) > 0)
		{
			$swap = array(
							'name_of_poster'	=> ee()->session->userdata('screen_name'),
							'forum_name'		=> $this->fetch_pref('board_label'),
							'title'				=> $title,
							'body'				=> $body,
							'topic_id'			=> $data['topic_id'],
							'thread_url'		=> ee()->input->remove_session_id($redirect),
							'post_url'			=> (isset($data['post_id'])) ? $this->forum_path()."viewreply/{$data['post_id']}/" : ee()->input->remove_session_id($redirect)
						 );

			$template = ee()->functions->fetch_email_template('admin_notify_forum_post');
			$email_tit = ee()->functions->var_swap($template['title'], $swap);
			$email_msg = ee()->functions->var_swap($template['data'], $swap);

			// Send email
			ee()->load->library('email');
			ee()->email->wordwrap = TRUE;

			// Load the text helper
			ee()->load->helper('text');

			foreach ($notify_addresses as $val)
			{
				ee()->email->EE_initialize();
				ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
				ee()->email->to($val);
				ee()->email->reply_to($val);
				ee()->email->subject($email_tit);
				ee()->email->message(entities_to_ascii($email_msg));
				ee()->email->send();
			}
		}

		/* -------------------------------------
		/*  'forum_submit_post_end' hook.
		/*  - After the post is submitted, do some more processing
		/* 	- Note that user notifications have not been sent at this point
		/*  - Added Discussion Forums 1.3.2
		/*	- $data added Discussion Forums 2.1.1
		*/
			if (ee()->extensions->active_hook('forum_submit_post_end') === TRUE)
			{
				$edata = ee()->extensions->call('forum_submit_post_end', $this, $data);
				if (ee()->extensions->end_script === TRUE) return $edata;
			}
		/*
		/* -------------------------------------*/

		// Send them to their post
		// Unless we are dealing with a follow up post we're done....
		if ($this->current_request == 'newtopic')
		{
			ee()->functions->redirect($redirect);
			exit;
		}

		// Send User Notifications
		// Fetch the notification addressess

		$query = ee()->db->query("SELECT s.hash, s.notification_sent, m.member_id, m.email, m.screen_name, m.smart_notifications, m.ignore_list  FROM exp_members m, exp_forum_subscriptions s WHERE s.member_id = m.member_id AND s.topic_id = '{$data['topic_id']}'");

		// No addresses?  Bail...

		if ($query->num_rows() == 0)
		{
			ee()->functions->redirect($redirect);
			exit;
		}

		$action_id  = ee()->functions->fetch_action_id('Forum', 'delete_subscription');

		$swap = array(
			'name_of_poster'	=> ee()->session->userdata('screen_name'),
			'forum_name'		=> $this->fetch_pref('board_label'),
			'title'				=> $title,
			'body'				=> $body,
			'topic_id'			=> $data['topic_id'],
			'thread_url'		=> ee()->input->remove_session_id($redirect),
			'post_url'			=> (isset($data['post_id'])) ? $this->forum_path()."viewreply/{$data['post_id']}/" : ee()->input->remove_session_id($redirect)
		 );

		$template = ee()->functions->fetch_email_template('forum_post_notification');
		$email_tit = $template['title'];
		$email_msg = $template['data'];

		foreach(array('email_tit', 'email_msg') as $var)
		{
			if (preg_match_all('/'.LD.'(('.implode('|', array_keys($swap)).')(\s+?)(.*?))'.RD.'/is', $$var, $matches))
			{
				foreach ($matches[1] as $k => $full_tag_inner)
				{
					if (isset($swap[$full_tag_inner]))
					{
						continue;
					}

					$params = ee('Variables/Parser')->parseTagParameters($full_tag_inner);

					if (isset($params['char_limit']) && is_numeric($params['char_limit']))
					{
						$swap[$full_tag_inner] = ee()->functions->char_limiter($swap[$matches[2][$k]], $params['char_limit']);
					}
				}
			}
		}

		$email_tit = ee()->functions->var_swap($email_tit, $swap);
		$email_msg = ee()->functions->var_swap($email_msg, $swap);

		// Send emails
		ee()->load->library('email');

		ee()->email->wordwrap = TRUE;

		$sent = array();

		foreach ($query->result_array() as $row)
		{
			// We don't notify the person currently commenting.  That would be silly.
			if ($row['email'] == ee()->session->userdata('email') OR (is_array($notify_addresses) && in_array($row['email'], $notify_addresses)))
			{
				continue;
			}

			if ($row['smart_notifications'] == 'y' AND $row['notification_sent'] == 'y')
			{
				continue;
			}

			// Ignored?  Don't even think about it, buster.

			if ($row['ignore_list'] != '' &&
				in_array(ee()->session->userdata['member_id'], explode('|', $row['ignore_list'])))
			{
				continue;
			}

			$personalized_swap = array(
				'name_of_recipient'			=> $row['screen_name'],
				'notification_removal_url'	=> ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&id='.$row['hash'].'&board_id='.$this->preferences['board_id']
			);

			$title	 = ee()->functions->var_swap($email_tit, $personalized_swap);
			$message = ee()->functions->var_swap($email_msg, $personalized_swap);

			// Load the text helper
			ee()->load->helper('text');

			ee()->email->EE_initialize();
			ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
			ee()->email->to($row['email']);
			ee()->email->reply_to(ee()->config->item('webmaster_email'));
			ee()->email->subject($title);
			ee()->email->message(entities_to_ascii($message));
			ee()->email->send();

			// Flip notification flag
			ee()->db->set('notification_sent', 'y');
			ee()->db->where(array(
				'topic_id'	=> $data['topic_id'],
				'member_id'	=> $row['member_id']
			));
			ee()->db->update('forum_subscriptions');
		}

		ee()->functions->redirect($redirect);
		exit;
	}

	/**
	 * Submit/update a poll
	 */
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

			$temp['answer']	= ee('Security/XSS')->clean($val);
			$temp['votes']	= (isset($_POST['votes'][$key]) AND is_numeric($_POST['votes'][$key])) ? $_POST['votes'][$key] : 0;

			$answers[]	= $temp;

			$total_votes = $temp['votes'] + $total_votes;
		}


		$data['poll_question']	= ee('Security/XSS')->clean($_POST['poll_question']);
		$data['poll_answers']	= serialize($answers);


		$query = ee()->db->query("SELECT count(*) AS count FROM exp_forum_polls WHERE topic_id = '{$topic_id}'");

		if ($query->row('count')  == 0)
		{
			$data['author_id']		= ee()->session->userdata('member_id');
			$data['poll_date']		= ee()->localize->now;
			$data['topic_id']		= $topic_id;
			$data['total_votes']	= 0;

			ee()->db->query(ee()->db->insert_string('exp_forum_polls', $data));
		}
		else
		{
			$data['total_votes'] = $total_votes;
			ee()->db->query(ee()->db->update_string('exp_forum_polls', $data, "topic_id = '{$topic_id}'"));
		}

		return TRUE;
	}

	/**
	 * Forum Delete Confirmation Page
	 */
	function delete_post_page()
	{
		$post_id = '';
		$title	 = '';

		// Fetch some meta data based on the request
		if ($this->current_request == 'deletereply')
		{
			$query = ee()->db->query("SELECT p.topic_id, p.post_id, p.forum_id, p.body, p.author_id,
								f.forum_text_formatting, f.forum_html_formatting, f.forum_auto_link_urls, f.forum_allow_img_urls
								FROM exp_forum_posts AS p, exp_forums AS f
								WHERE f.forum_id = p.forum_id
								AND p.post_id = '{$this->current_id}'");
		}
		else
		{
			$query = ee()->db->query("SELECT t.topic_id, t.forum_id, t.title, t.body, t.author_id,
								f.forum_text_formatting, f.forum_html_formatting, f.forum_auto_link_urls, f.forum_allow_img_urls
								FROM exp_forum_topics AS t, exp_forums AS f
								WHERE f.forum_id = t.forum_id
								AND t.topic_id = '{$this->current_id}'");
		}

		// No result?  Smack em'
		if ($query->num_rows() == 0)
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		// Create Vars for simplicity
		foreach ($query->row_array() as $key => $val)
		{
			$$key = $val;
		}

		// Is the user allowed to delete?
		if ( ! $this->_mod_permission('can_delete', $forum_id))
		{
			return $this->trigger_error('not_authorized');
		}

		// Only Superadmins can delete other Superadmin posts
		if ($author_id != ee()->session->userdata('member_id'))
		{
			//  Fetch the Super Admin IDs
			$super_admins = $this->fetch_superadmins();

			if (in_array($author_id, $super_admins) && ee()->session->userdata('group_id') != 1)
			{
				return $this->trigger_error('not_authorized');
			}
		}

		// Define the redirect location
		if ($this->current_request == 'deletereply')
		{
			$this->form_actions['forum:delete_post']['RET'] = $this->forum_path('/viewthread/'.$topic_id.'/');
			$this->form_actions['forum:delete_post']['post_id'] = $post_id;
		}
		else
		{
			$this->form_actions['forum:delete_post']['RET'] = $this->forum_path('/viewforum/'.$forum_id.'/');
			$this->form_actions['forum:delete_post']['topic_id'] = $topic_id;
		}

		// Build the warning
		$str = $this->load_element('delete_post_warning');

		if ($this->current_request == 'deletereply')
		{
			$str = $this->deny_if('is_topic', $str);
			$str = $this->allow_if('is_reply', $str);
		}
		else
		{
			$str = $this->allow_if('is_topic', $str);
			$str = $this->deny_if('is_reply', $str);
		}

		ee()->load->library('typography');
		ee()->typography->initialize();

		$str = $this->var_swap($str,
								array(
										'title'	=> $this->_convert_special_chars($title),
										'body'	=> ee()->typography->parse_type($body,
													 array(
																'text_format'	=> $forum_text_formatting,
																'html_format'	=> $forum_html_formatting,
																'auto_links'	=> $forum_auto_link_urls,
																'allow_img_url' => $forum_allow_img_urls
															)
													)
									)
								);

		return str_replace('{include:delete_post_warning}', $str, $this->load_element('delete_post_page'));
	}

	/**
	 * Delete Post
	 */
	function delete_post()
	{
		$id = '';

		// No ID?  Bah....
		if ( ! isset($_POST['post_id']) AND  ! isset($_POST['topic_id']))
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
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
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		// Fetch the forum_id
		if ($type == 'post')
		{
			$query = ee()->db->query("SELECT topic_id, forum_id, post_id, author_id FROM exp_forum_posts WHERE post_id = '{$post_id}'");
		}
		else
		{
			$query = ee()->db->query("SELECT topic_id, forum_id, author_id FROM exp_forum_topics WHERE topic_id = '{$post_id}'");
		}

		// No result?  Smack em'
		if ($query->num_rows() == 0)
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		// Create Vars for simplicity
		foreach ($query->row_array() as $key => $val)
		{
			$$key = $val;
		}

		// Is the user allowed to delete?
		if ( ! $this->_mod_permission('can_delete', $forum_id))
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		// Delete the post(s)
		$authors[$author_id] = $author_id;

		if ($type == 'post')
		{
			ee()->db->query("DELETE FROM exp_forum_posts WHERE post_id = '{$post_id}'");

			// Update the topic stats (count, last post info)
			$this->_update_topic_stats($topic_id);
		}
		else
		{
			// get all affected authors
			$query = ee()->db->query("SELECT author_id FROM exp_forum_posts WHERE topic_id = '{$topic_id}'");

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$authors[$row['author_id']] = $row['author_id'];
				}
			}

			ee()->db->query("DELETE FROM exp_forum_topics WHERE topic_id = '{$topic_id}'");
			ee()->db->query("DELETE FROM exp_forum_posts  WHERE topic_id = '{$topic_id}'");
			ee()->db->query("DELETE FROM exp_forum_subscriptions  WHERE topic_id = '{$topic_id}'");
			ee()->db->query("DELETE FROM exp_forum_polls  WHERE topic_id = '{$topic_id}'");
			ee()->db->query("DELETE FROM exp_forum_pollvotes  WHERE topic_id = '{$topic_id}'");
		}


		// Delete attachments if there are any
		if ($type == 'post')
		{
			$query = ee()->db->query("SELECT attachment_id, filehash, extension FROM exp_forum_attachments WHERE topic_id = '{$topic_id}' AND post_id = '{$post_id}'");
		}
		else
		{
			$query = ee()->db->query("SELECT attachment_id, filehash, extension FROM exp_forum_attachments WHERE topic_id = '{$topic_id}'");
		}

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$file  = $this->fetch_pref('board_upload_path').$row['filehash'].$row['extension'];
				$thumb = $this->fetch_pref('board_upload_path').$row['filehash'].'_t'.$row['extension'];

				@unlink($file);
				@unlink($thumb);

				ee()->db->query("DELETE FROM exp_forum_attachments WHERE attachment_id = '{$row['attachment_id']}'");
			}
		}

		// Update the forum stats
		$this->_update_post_stats($forum_id);
		$this->_update_global_stats();

		// update member stats
		$this->_update_member_stats($authors);

		ee()->functions->redirect(ee()->input->get_post('RET'));
		exit;
	}

	/**
	 * Change Post Status
	 */
	function change_status()
	{
		if ( ! isset($_GET['topic_id']) OR ! is_numeric($_GET['topic_id']))
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		// Fetch the forum_id and status
		$query = ee()->db->query("SELECT status, forum_id, announcement FROM exp_forum_topics WHERE topic_id = '".ee()->db->escape_str($_GET['topic_id'])."'");

		if ($query->num_rows() == 0)
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		// Are they allowed to swap the status?
		$viewpath = ($query->row('announcement')  == 'n') ? 'viewthread/' : 'viewannounce/';
		$viewpath .= ($query->row('announcement')  == 'n') ?  $_GET['topic_id'] : $_GET['topic_id'].'_'.$query->row('forum_id') ;

		if ( ! $this->_mod_permission('can_change_status', $query->row('forum_id') ))
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		// Update the status
		$status = ($query->row('status')  == 'o') ? 'c' : 'o';

		ee()->db->query("UPDATE exp_forum_topics SET status = '{$status}' WHERE topic_id = '".ee()->db->escape_str($_GET['topic_id'])."'");

		// Update edit date
		$data = array();
		$data['topic_edit_author']	= ee()->session->userdata['member_id'];
		$data['topic_edit_date']	= ee()->localize->now;

		ee()->db->query(ee()->db->update_string('exp_forum_topics', $data, "topic_id='".$_GET['topic_id']."'"));

		ee()->functions->redirect($this->forum_path($viewpath));
		exit;
	}

	/**
	 * Move Topic Confirmation
	 */
	function move_topic_confirmation()
	{
		// Fetch the topic title
		$query = ee()->db->query("SELECT title, forum_id FROM exp_forum_topics WHERE topic_id = '{$this->current_id}'");

		if ($query->num_rows() == 0)
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		// Are they allowed to move it?
		if ( ! $this->_mod_permission('can_move', $query->row('forum_id') ))
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		// Are there any other forums?
		$f_query = ee()->db->query("SELECT forum_name, forum_id, forum_status, forum_permissions FROM exp_forums WHERE board_id = '".$this->fetch_pref('board_id')."' AND forum_id != '".$query->row('forum_id') ."' AND forum_is_cat = 'n' ORDER BY forum_order ASC");

		if ($f_query->num_rows() == 0)
		{
			return $this->trigger_error('no_forums_to_move_to');
		}

		// Build the menu
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
			if ($row['forum_status'] == 'c' &&
				! $this->_permission('can_view_hidden', $row['forum_permissions']))
			{
				continue;
			}

			$menu .= '<option value="'.$row['forum_id'].'">'.$row['forum_name'].'</option>';
		}

		$this->form_actions['forum:move_topic']['topic_id'] = $this->current_id;
		$this->form_actions['forum:move_topic']['RET'] = $this->forum_path('/viewthread/'.$this->current_id.'/');

		return $this->var_swap($this->load_element('move_topic_confirmation'),
								array(
										'move_select_options'	=> $menu,
										'title' => $this->_convert_special_chars($query->row('title') )
									)
								);
	}


	/**
	 * Move Topic
	 */
	function move_topic()
	{
		$topic_id = ee()->input->post('topic_id');
		$forum_id = ee()->input->post('forum_id');

		if ( ! is_numeric($topic_id) OR ! is_numeric($forum_id))
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		// Fetch the topic title
		$query = ee()->db->query("SELECT title, forum_id, author_id FROM exp_forum_topics WHERE topic_id = '{$topic_id}'");

		if ($query->num_rows() == 0)
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		$author_id = $query->row('author_id') ;

		// Are they allowed to move it?
		if ( ! $this->_mod_permission('can_move', $query->row('forum_id') ))
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		// Move it!
		$title = (ee()->input->get_post('title') == '') ? $query->row('title')  : ee()->db->escape_str(ee()->input->get_post('title'));
		$title = $this->_convert_special_chars($title);

		if (ee()->input->get_post('redirect'))
		{
			ee()->db->query("UPDATE exp_forum_topics SET forum_id = '{$forum_id}', moved_forum_id = '".$query->row('forum_id')."', title = '{$title}' WHERE topic_id = '{$topic_id}' ");
		}
		else
		{
			ee()->db->query("UPDATE exp_forum_topics SET forum_id = '{$forum_id}',  moved_forum_id = '0', title = '{$title}' WHERE topic_id = '{$topic_id}' ");
		}

		ee()->db->query("UPDATE exp_forum_posts SET forum_id = '{$forum_id}' WHERE topic_id = '{$topic_id}'");

		// Update the stats for old/new forum
		$this->_update_post_stats($forum_id);
		$this->_update_post_stats($query->row('forum_id') );
		$this->_update_global_stats();

		// Get email address of topic author, but only if it's not
		// the moderator doing the moving.  Sheesh.
		if (ee()->input->get_post('notify') AND ee()->session->userdata('member_id') != $author_id)
		{
			$query2 = ee()->db->query("SELECT email, screen_name FROM exp_members where member_id = '{$author_id}'");

			$swap = array(
							'forum_name'		=> $this->fetch_pref('board_label'),
							'title'				=> $title,
							'name_of_recipient'	=> $query2->row('screen_name') ,
							'moderation_action' => lang('moved_action'),
							'thread_url'		=> ee()->input->remove_session_id(ee()->input->post('RET'))
						 );

			$template = ee()->functions->fetch_email_template('forum_moderation_notification');
			$email_tit = ee()->functions->var_swap($template['title'], $swap);
			$email_msg = ee()->functions->var_swap($template['data'], $swap);

			ee()->load->library('email');
			ee()->load->helper('text');

			ee()->email->wordwrap = TRUE;

			ee()->email->EE_initialize();
			ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
			ee()->email->to($query2->row('email') );
			ee()->email->reply_to(ee()->config->item('webmaster_email'));
			ee()->email->subject($email_tit);
			ee()->email->message(entities_to_ascii($email_msg));
			ee()->email->send();
		}

		ee()->functions->redirect(ee()->input->post('RET'));
		exit;
	}

	/**
	 * Move Reply Confirmation
	 */
	function move_reply_confirmation()
	{
		// Fetch the topic title
		$query = ee()->db->query("SELECT exp_forum_posts.topic_id, exp_forum_posts.forum_id, exp_forum_posts.body, exp_forum_posts.post_date, exp_forum_posts.parse_smileys,
							exp_forums.forum_text_formatting, exp_forums.forum_html_formatting, exp_forums.forum_auto_link_urls, exp_forums.forum_allow_img_urls,
							exp_members.screen_name
							FROM exp_forum_posts
							LEFT JOIN exp_forums ON exp_forums.forum_id = exp_forum_posts.forum_id
							LEFT JOIN exp_members ON exp_members.member_id = exp_forum_posts.author_id
							WHERE exp_forum_posts.post_id = '{$this->current_id}'");

		if ($query->num_rows() == 0)
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		// Are they allowed to move it?
		if ( ! $this->_mod_permission('can_move', $query->row('forum_id') ))
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		ee()->load->library('typography');
		ee()->typography->initialize(array(
			'parse_smileys' => ($query->row('parse_smileys') == 'y') ? TRUE : FALSE
		));

		$body = ee()->typography->parse_type($query->row('body') ,
 								  array(
										'text_format'	=> $query->row('forum_text_formatting') ,
										'html_format'	=> $query->row('forum_html_formatting') ,
										'auto_links'	=> $query->row('forum_auto_link_urls') ,
										'allow_img_url' => $query->row('forum_allow_img_urls')
										)
								  );

		$this->form_actions['forum:move_reply']['post_id'] = $this->current_id;
		$this->form_actions['forum:move_reply']['forum_path'] = $this->forum_path();

		$template = $this->load_element('move_reply_confirmation');

		$template = ee()->TMPL->parse_date_variables($template, array('post_date' => $query->row('post_date')));

		return $this->var_swap($template,
								array(
										'body'		=> $body,
										'author'	=> $query->row('screen_name')
									)
								);
	}

	/**
	 * Move a Reply!
	 */
	function move_reply()
	{
		if (ee()->input->post('url') === FALSE OR ee()->input->get_post('post_id') === FALSE OR ! is_numeric(ee()->input->get_post('post_id')))
		{
			ee()->functions->redirect($this->forum_path());
			exit;
		}

		// Fetch the post data
		$query = ee()->db->query("SELECT * FROM exp_forum_posts WHERE post_id = '".ee()->db->escape_str(ee()->input->post('post_id'))."'");

		// No result?  Smack em'
		if ($query->num_rows() == 0)
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		// Is the user allowed to move?
		if ( ! $this->_mod_permission('can_move', $query->row('forum_id') ))
		{
			return $this->trigger_error('not_authorized');
		}

		$post_id		= $query->row('post_id') ;
		$author_id		= $query->row('author_id') ;
		$old_topic_id	= $query->row('topic_id') ;
		$old_forum_id	= $query->row('forum_id') ;

		// Gather the target topic ID

		$new_topic_id = trim(trim_slashes(ee()->input->post('url')));

		if ($new_topic_id == '')
		{
			ee()->functions->redirect($this->forum_path().'movereply/'.$post_id.'/');
			exit;
		}

		if (FALSE !== (strpos($new_topic_id, "/")))
		{
			$new_topic_id = end(explode("/", $new_topic_id));
		}

		if ( ! is_numeric($new_topic_id))
		{
			return ee()->output->show_user_error('general', array(lang('move_reply_requires_id')));
		}

		$tquery = ee()->db->query("SELECT topic_id, forum_id, title FROM exp_forum_topics WHERE topic_id = '".ee()->db->escape_str($new_topic_id)."'");

		if ($tquery->num_rows() == 0)
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		$new_topic_id = $tquery->row('topic_id') ;
		$new_forum_id = $tquery->row('forum_id') ;
		$title = $tquery->row('title') ;

		// You're the boss, move it!
		ee()->db->query(ee()->db->update_string('exp_forum_posts', array('topic_id' => $new_topic_id, 'forum_id' => $new_forum_id), "post_id = '{$post_id}'"));

		// Update attachments
		ee()->db->query("UPDATE exp_forum_attachments SET topic_id = '{$new_topic_id}' WHERE topic_id = '{$old_topic_id}' AND post_id = '{$post_id}'");

		// Update topic stats (count, last post info)
		$this->_update_topic_stats($old_topic_id);
		$this->_update_topic_stats($new_topic_id);

		// Update the forum stats
		$this->_update_post_stats($old_forum_id);
		$this->_update_post_stats($new_forum_id);
		$this->_update_global_stats();

		// Get email address of author of reply unless
		// it's the moderator doing the move.
		if (ee()->input->get_post('notify') AND ee()->session->userdata('member_id') != $author_id)
		{
			$query = ee()->db->query("SELECT email, screen_name FROM exp_members WHERE member_id = '{$author_id}'");

			$swap = array(
							'forum_name'		=> $this->fetch_pref('board_label'),
							'title'				=> $title,
							'name_of_recipient'	=> $query->row('screen_name') ,
							'moderation_action' => lang('moved_reply_action'),
							'thread_url'		=> ee()->input->post('forum_path').'viewreply/'.$post_id.'/'
						 );

			$template = ee()->functions->fetch_email_template('forum_moderation_notification');
			$email_tit = ee()->functions->var_swap($template['title'], $swap);
			$email_msg = ee()->functions->var_swap($template['data'], $swap);

			ee()->load->library('email');
			ee()->load->helper('text');

			ee()->email->wordwrap = TRUE;

			ee()->email->EE_initialize();
			ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
			ee()->email->to($query->row('email') );
			ee()->email->reply_to(ee()->config->item('webmaster_email'));
			ee()->email->subject($email_tit);
			ee()->email->message(entities_to_ascii($email_msg));
			ee()->email->send();
		}

		ee()->functions->redirect(ee()->input->post('forum_path').'viewreply/'.$post_id.'/');
		exit;
	}

	/**
	 * Mark all posts as read
	 *
	 * We're basically cheating here.  All we're doing is updating
	 * the 'last_visit' date for the given user.  Since we determine
	 * which posts have been read based on the date of the last visit,
	 * if we make the last_visit equal to now we will inadvertenly make
	 * all posts read.  I suspect we will need to do this a different
	 * way in the future but for now it works.
	 */
	public function mark_all_read()
	{
		// Check CSRF Token
		$token = end(ee()->uri->segments);

		if ( ! bool_config_item('disable_csrf_protection') && $token != CSRF_TOKEN)
		{
			return $this->trigger_error('not_authorized');
		}

		if (ee()->session->userdata('member_id') != 0)
		{
			ee()->db->query("UPDATE exp_members SET last_visit = '".ee()->localize->now."' WHERE member_id = '".ee()->session->userdata('member_id')."'");
		}

		$data = array(	'title' 	=> lang('post_marked_read'),
						'heading'	=> lang('thank_you'),
						'content'	=> lang('post_marked_read'),
						'redirect'	=> ee()->functions->create_url($this->trigger),
						'link'		=> array(ee()->functions->create_url($this->trigger), '')
					 );

		return ee()->output->show_message($data);
	}

	/**
	 * Subscribe to a post
	 */
	public function subscribe()
	{
		$topic_id = ee()->input->post('topic_id');

		if ( ! $topic_id || ! is_numeric($topic_id))
		{
			return $this->trigger_error();
		}

		$topic_id = (int) $topic_id;

		// Do we have a valid topic ID?
		$query = ee()->db->query("SELECT title FROM exp_forum_topics WHERE topic_id = '{$topic_id}'");

		if ($query->num_rows() == 0)
		{
			return $this->trigger_error();
		}

		$title = $this->_convert_special_chars($query->row('title') );

		$query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_forum_subscriptions WHERE topic_id = '{$topic_id}' AND member_id = '".ee()->session->userdata('member_id')."'");

		if ($query->row('count')  > 1)
		{
			ee()->db->query("DELETE FROM exp_forum_subscriptions WHERE topic_id = '{$topic_id}' AND member_id = '".ee()->session->userdata('member_id')."'");
			$query->set_row('count', 0);
		}
		if ($query->row('count')  == 0)
		{
			$rand = ee()->session->userdata('member_id').ee()->functions->random('alnum', 8);
			ee()->db->query("INSERT INTO exp_forum_subscriptions (topic_id, board_id, member_id, subscription_date, hash) VALUES ('{$topic_id}', '{$this->preferences['board_id']}', '".ee()->session->userdata('member_id')."', '".ee()->localize->now."', '{$rand}')");
		}


		$data = array(	'title' 	=> lang('thank_you'),
						'heading'	=> lang('thank_you'),
						'content'	=> lang('you_have_been_subscribed').'<br /><br /><b>'.$title.'</b>',
						'redirect'	=> $this->forum_path('/viewthread/'.$topic_id.'/'),
						'rate'		=> 3,
						'link'		=> array($this->forum_path('/viewthread/'.$topic_id.'/'), '')
					 );

		return ee()->output->show_message($data);
	}

	/**
	 * Un-subscribe to a post
	 */
	public function unsubscribe()
	{
		$topic_id = ee()->input->post('topic_id');

		if ( ! $topic_id || ! is_numeric($topic_id))
		{
			return $this->trigger_error();
		}

		$topic_id = (int) $topic_id;

		// Do we have a valid topic ID?
		$query = ee()->db->query("SELECT title FROM exp_forum_topics WHERE topic_id = '{$topic_id}'");

		if ($query->num_rows() == 0)

		{
			return $this->trigger_error();
		}

		$title = $this->_convert_special_chars($query->row('title') );

		ee()->db->query("DELETE FROM exp_forum_subscriptions WHERE topic_id = '{$topic_id}' AND member_id = '".ee()->session->userdata('member_id')."'");

		$data = array(	'title' 	=> lang('thank_you'),
						'heading'	=> lang('thank_you'),
						'content'	=> lang('you_have_been_unsubscribed').'<br /><br /><b>'.$title.'</b>',
						'redirect'	=> $this->forum_path('/viewthread/'.$topic_id.'/'),
						'rate'		=> 3,
						'link'		=> array($this->forum_path('/viewthread/'.$topic_id.'/'), '')
					 );

		return ee()->output->show_message($data);
	}

	/**
	 * Remove notification for a posts via email
	 */
	public function delete_subscription()
	{
		if ( ! ($hash = ee()->input->get('id')))
		{
			return $this->trigger_error();
		}

		if (strlen($hash) < 9 OR strlen($hash) > 15 OR preg_match('/[^0-9a-z]/i', $hash))
		{
			return $this->trigger_error('invalid_subscription_id');
		}

		$query = ee()->db->query("SELECT title FROM exp_forum_topics t, exp_forum_subscriptions s WHERE t.topic_id = s.topic_id AND s.hash = '".ee()->db->escape_str($hash)."' ");

		if ($query->num_rows() != 1)
		{
			$title	 = lang('error');
			$heading = lang('error');
			$content = lang('not_subscribed_to_topic');

		}
		else
		{
			// prompt for confirmation
			if ( ! ee()->input->get('confirm'))
			{
				$data['title']	 = lang('confirm_subscription_removal');
				$data['heading'] = lang('confirm_subscription_removal');
				$data['content'] = lang('remove_subscription_question')."<br /><br /><b>".$this->_convert_special_chars($query->row('title') )."</b>";
				$data['link']	 = array(ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.ee()->input->get('ACT').'&id='.$hash.'&board_id='.$this->preferences['board_id'].'&confirm=yes', lang('subscription_confirmation_link'));

				return ee()->output->show_message($data);
			}

			ee()->db->query("DELETE FROM exp_forum_subscriptions WHERE hash = '".ee()->db->escape_str($hash)."'");

			$title	 = lang('subscription_cancelled');
			$heading = lang('thank_you');
			$content = lang('your_subscription_cancelled').'<br /><br /><b>'.$this->_convert_special_chars($query->row('title') ).'</b>';
		}


		$data = array(	'title' 	=> $title,
						'heading'	=> $heading,
						'content'	=> $content,
						'redirect'	=> '',
						'link'		=> array($this->fetch_pref('board_forum_url'), $this->fetch_pref('board_label'))
					 );

		return ee()->output->show_message($data);
	}

	/**
	 * Merge Page
	 */
	function merge_page()
	{
		$post_id = '';
		$title	 = '';

		// Fetch some meta data
		$query = ee()->db->query("SELECT topic_id, forum_id, title FROM exp_forum_topics WHERE topic_id = '{$this->current_id}'");

		// No result?  Smack em'
		if ($query->num_rows() == 0)
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		// Create Vars for simplicity
		foreach ($query->row_array() as $key => $val)
		{
			$$key = $val;
		}

		// Is the user allowed to merge?
		if ( ! $this->_mod_permission('can_merge', $forum_id))
		{
			return $this->trigger_error('not_authorized');
		}

		// Define the redirect location
		$this->form_actions['forum:do_merge']['RET'] = $this->forum_path('/viewthread/'.$topic_id.'/');
		$this->form_actions['forum:do_merge']['forum_path'] = $this->forum_path();
		$this->form_actions['forum:do_merge']['topic_id'] = $topic_id;

		// Build the message
		$str = $this->var_swap($this->load_element('merge_interface'),
								array(
										'title' => $title
									)
								);

		return str_replace('{include:merge_interface}', $str, $this->load_element('merge_page'));
	}

	/**
	 * Perform the merge
	 */
	function do_merge()
	{
		if (ee()->input->post('RET') === FALSE OR ee()->input->get_post('url') === FALSE OR ee()->input->get_post('topic_id') === FALSE OR ! is_numeric(ee()->input->get_post('topic_id')))
		{
			ee()->functions->redirect($this->forum_path());
			exit;
		}

		// Is the title blank?
		if (ee()->input->get_post('title') == '')
		{
			return ee()->output->show_user_error('submission', array(lang('empty_title_field')));
		}

		// Fetch the topic data
		$query = ee()->db->query("SELECT * FROM exp_forum_topics WHERE topic_id = '".ee()->db->escape_str(ee()->input->post('topic_id'))."'");

		// No result?  Smack em'
		if ($query->num_rows() == 0)
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		// Is the user allowed to merge?
		if ( ! $this->_mod_permission('can_merge', $query->row('forum_id') ))
		{
			return $this->trigger_error('not_authorized');
		}

		$topic_id	= $query->row('topic_id') ;
		$forum_id	= $query->row('forum_id') ;
		$topic_fid	= $query->row('forum_id') ;
		$title 		= $query->row('title') ;

		// Gather the merge ID

		$merge_id = trim(trim_slashes(ee()->input->post('url')));

		if ($merge_id == '')
		{
			ee()->functions->redirect(ee()->input->post('forum_path').'merge/'.ee()->input->get_post('topic_id').'/');
			exit;
		}

		if (FALSE !== (strpos($merge_id, "/")))
		{
			$merge_id = end(explode("/", $merge_id));
		}

		if ( ! is_numeric($merge_id))
		{
			return ee()->output->show_user_error('general', array(lang('merge_requires_id')));
		}

		if ($merge_id == $topic_id)
		{
			return ee()->output->show_user_error('general', array(lang('merge_duplicate_id')));
		}

		// Which topic is the earliest?
		// At this point we need to determine which topic of the two being merged came first.
		// We will take the later of the two topics and turn it into a post.
		$result = ee()->db->query("SELECT topic_id, forum_id, topic_date, forum_id FROM exp_forum_topics WHERE topic_id = '".ee()->db->escape_str($merge_id)."'");

		// No result?  Scold them...
		if ($result->num_rows() == 0)
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		// The merged topic is newer.  It will then become a post
		if ($result->row('topic_date')  > $query->row('topic_date') )
		{
			$query = ee()->db->query("SELECT * FROM exp_forum_topics WHERE topic_id = '".ee()->db->escape_str($merge_id)."'");
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

		// Compile and insert the post data
		$data = array(
						'topic_id'		=> $topic_id,
						'forum_id'		=> $forum_id,
						'body'			=> $query->row('body') ,
						'parse_smileys'	=> $query->row('parse_smileys') ,
						'author_id'		=> $query->row('author_id') ,
						'ip_address'	=> $query->row('ip_address') ,
						'post_date'		=> $query->row('topic_date') ,
						'board_id'		=> $this->fetch_pref('board_id')
					 );

			ee()->db->query(ee()->db->insert_string('exp_forum_posts', $data));

		// Update attachments
		ee()->db->query("UPDATE exp_forum_attachments SET topic_id = '{$topic_id}', post_id = '".ee()->db->insert_id()."' WHERE topic_id = '{$merge_id}'");

		// Update the merge posts
		ee()->db->query("UPDATE exp_forum_posts SET topic_id = '{$topic_id}', forum_id = '{$forum_id}' WHERE topic_id = '{$merge_id}'");

		// Update topic stats (count, last post info)
		$this->_update_topic_stats($topic_id);

		// Update the topic ID
		ee()->db->query("UPDATE exp_forum_posts SET topic_id = '{$topic_id}' WHERE topic_id = '{$merge_id}'");
		ee()->db->query("UPDATE exp_forum_polls SET topic_id = '{$topic_id}' WHERE topic_id = '{$merge_id}'");
		ee()->db->query("UPDATE exp_forum_pollvotes SET topic_id = '{$topic_id}' WHERE topic_id = '{$merge_id}'");
		ee()->db->query("UPDATE exp_forum_attachments SET topic_id = '{$topic_id}' WHERE topic_id = '{$merge_id}'");

		// the forum subscription table uses a primary key of topic_id-member_id, so there may already be a record for the
		// original thread if a member was subscribed to both threads in the merge.  So for all members that are subsribed
		// to both, we must first drop the original subscription before updating to the new one
		$query = ee()->db->query("SELECT member_id FROM exp_forum_subscriptions WHERE topic_id = '{$topic_id}'");

		if ($query->num_rows() > 0)
		{
			$member_ids = array();

			foreach ($query->result_array() as $row)
			{
				$member_ids[] = $row['member_id'];
			}

			ee()->db->where('topic_id', $merge_id);
			ee()->db->where_in('member_id', $member_ids);
			ee()->db->delete('forum_subscriptions');
		}

		ee()->db->query("UPDATE exp_forum_subscriptions SET topic_id = '{$topic_id}' WHERE topic_id = '{$merge_id}'");

		// Delete the old topic
		ee()->db->query("DELETE FROM exp_forum_topics WHERE topic_id = '{$merge_id}'");


		// Update the forum stats
		$this->_update_post_stats($forum_id);

		if (isset($merge_fid))
		{
			$this->_update_post_stats($topic_fid);
			$this->_update_post_stats($merge_fid);
		}

		$this->_update_global_stats();

		// Set the new title
		$new_title = ee()->input->post('title');

		if ($new_title != $title)
		{
			$title = $this->convert_forum_tags($new_title);
		}

		ee()->db->query("UPDATE exp_forum_topics SET title = '".ee()->db->escape_str($title)."' WHERE topic_id = '{$topic_id}'");
		ee()->db->query("UPDATE exp_forums SET forum_last_post_title = '".ee()->db->escape_str($title)."' WHERE forum_last_post_id = '{$topic_id}'");

		/* -------------------------------------
		/*  Get email address of author of merged topic unless
		/*  it's the moderator doing the merge.  Sheesh.
		/* -------------------------------------*/
		if (ee()->input->get_post('notify') AND ee()->session->userdata('member_id') != $data['author_id'])
		{
			$query = ee()->db->query("SELECT email, screen_name FROM exp_members WHERE member_id = '{$data['author_id']}'");

			$swap = array(
							'forum_name'		=> $this->fetch_pref('board_label'),
							'title'				=> $title,
							'name_of_recipient'	=> $query->row('screen_name') ,
							'moderation_action' => lang('merged_action'),
							'thread_url'		=> ee()->input->post('forum_path').'viewthread/'.$topic_id.'/'
						 );

			$template = ee()->functions->fetch_email_template('forum_moderation_notification');
			$email_tit = ee()->functions->var_swap($template['title'], $swap);
			$email_msg = ee()->functions->var_swap($template['data'], $swap);

			ee()->load->library('email');
			ee()->load->helper('text');

			ee()->email->wordwrap = TRUE;

			ee()->email->EE_initialize();
			ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
			ee()->email->to($query->row('email') );
			ee()->email->reply_to(ee()->config->item('webmaster_email'));
			ee()->email->subject($email_tit);
			ee()->email->message(entities_to_ascii($email_msg));
			ee()->email->send();
		}

		ee()->functions->redirect(ee()->input->post('forum_path').'viewthread/'.$topic_id.'/');
		exit;
	}

	/**
	 * Split Page
	 */
	function split_data()
	{
		return $this->threads(FALSE, FALSE, TRUE);
	}

	/**
	 * Do the split!  Make sure and stretch first...
	 */
	function do_split()
	{
		if ( isset($_POST['next_page']) OR isset($_POST['previous_page']))
		{
			if ( isset($_POST['topic_id']) && is_numeric($_POST['topic_id']))
			{
				$this->current_id = $_POST['topic_id'];
				$this->current_request = 'split';
				return $this->display_forum('split');
			}
		}

		if ( ! isset($_POST['post_id']))
		{
			return ee()->output->show_user_error('submission', array(lang('split_info')));
		}

		if ( ! is_numeric(ee()->input->post('topic_id')) OR ! is_numeric(ee()->input->get_post('forum_id')))
		{
			return $this->trigger_error();
		}

		// Is the title blank?
		if (ee()->input->get_post('title') == '')
		{
			return ee()->output->show_user_error('submission', array(lang('empty_title_field')));
		}

		// Is the user allowed to split?
		$old_topic_id = ee()->input->post('topic_id');
		$query = ee()->db->query("SELECT forum_id, title FROM exp_forum_topics WHERE topic_id = '".ee()->db->escape_str($old_topic_id)."'");

		if ($query->num_rows() == 0)
		{
			return $this->trigger_error();
		}

		$old_forum_id = $query->row('forum_id') ;
		$new_forum_id = ee()->input->post('forum_id');

		if ( ! $this->_mod_permission('can_split', $old_forum_id) OR ! $this->_mod_permission('can_split', $new_forum_id))
		{
			return $this->trigger_error('can_not_split');
		}

		// Safety check - only numeric IDs allowed
		if ( ! is_array($_POST['post_id']))
		{
			return $this->trigger_error();
		}

		foreach ($_POST['post_id'] as $id)
		{
			if ( ! is_numeric($id))
			{
				return $this->trigger_error();
			}
		}

		// Sort the split IDs
		// The earliest ID will become the topic so just to be
		// safe we'll fetch the post_ids based on date

		$query = ee()->db->select('post_id, post_date, author_id')
			->where_in('post_id', $_POST['post_id'])
			->order_by('post_date', 'asc')
			->get('forum_posts');

		if ($query->num_rows() == 0)
		{
			return $this->trigger_error();
		}

		$i = 1;
		$last_post = ee()->localize->now;
		$last_author = ee()->session->userdata('member_id');
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

		// Grab the post data from the earlist one and create a topic
		$post_id = current($post_ids);
		unset($post_ids['0']);

		$query = ee()->db->query("SELECT * FROM exp_forum_posts WHERE post_id = '".$post_id."'");

		$title = $this->convert_forum_tags(ee()->input->get_post('title'));
		$data = array(
						'forum_id'				=> $new_forum_id,
						'title'					=> ee('Security/XSS')->clean($title),
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
						'board_id'				=> $this->fetch_pref('board_id')
					 );

		ee()->db->query(ee()->db->insert_string('exp_forum_topics', $data));
		$topic_id = ee()->db->insert_id();

		// Delete the old post
		ee()->db->query("DELETE FROM exp_forum_posts WHERE post_id = '{$post_id}'");

		// Update attachments
		ee()->db->query("UPDATE exp_forum_attachments SET topic_id = '{$topic_id}', post_id = '0' WHERE post_id = '{$post_id}'");

		// Are there more posts in the split?
		if (count($post_ids) > 0)
		{
			foreach ($post_ids as $id)
			{
				ee()->db->query("UPDATE exp_forum_posts SET topic_id = '{$topic_id}', forum_id = '{$new_forum_id}' WHERE post_id = '{$id}'");
				ee()->db->query("UPDATE exp_forum_attachments SET topic_id = '{$topic_id}' WHERE post_id = '{$id}'");

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


		// Get email address of new topic author but only if it's not
		// the moderator doing the split.  Sheesh.
		if (ee()->input->get_post('notify') &&
			ee()->session->userdata('member_id') != $data['author_id'])
		{
			$query = ee()->db->query("SELECT email, screen_name FROM exp_members WHERE member_id = '{$data['author_id']}'");

			$swap = array(
							'forum_name'		=> $this->fetch_pref('board_label'),
							'title'				=> $data['title'],
							'name_of_recipient'	=> $query->row('screen_name') ,
							'moderation_action' => lang('split_action'),
							'thread_url'		=> str_replace('viewforum', 'viewthread', $_POST['RET']).$topic_id.'/'
						 );

			$template = ee()->functions->fetch_email_template('forum_moderation_notification');
			$email_tit = ee()->functions->var_swap($template['title'], $swap);
			$email_msg = ee()->functions->var_swap($template['data'], $swap);

			ee()->load->library('email');
			ee()->load->helper('text');

			ee()->email->wordwrap = TRUE;

			ee()->email->EE_initialize();
			ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
			ee()->email->to($query->row('email') );
			ee()->email->reply_to(ee()->config->item('webmaster_email'));
			ee()->email->subject($email_tit);
			ee()->email->message(entities_to_ascii($email_msg));
			ee()->email->send();
		}

		ee()->functions->redirect(reduce_double_slashes($_POST['RET'].$new_forum_id.'/'));
		exit;
	}

	/**
	 * Report Page
	 */
	function report_page()
	{
		if ($this->current_request == 'reporttopic')
		{
			$is_topic = TRUE;
			$query = ee()->db->query("SELECT forum_id, topic_id, title, body, author_id, parse_smileys FROM exp_forum_topics WHERE topic_id = '{$this->current_id}'");
		}
		else
		{
			$is_topic = FALSE;
			$query = ee()->db->query("SELECT forum_id, topic_id, body, author_id, parse_smileys FROM exp_forum_posts WHERE post_id = '{$this->current_id}'");
		}

		// Can't report it iffen it don't exist
		if ($query->num_rows() == 0)
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		// Create some variables
		foreach ($query->row_array() as $key => $val)
		{
			$$key = $val;
		}

		// Multiple personality disorder?
		if (ee()->session->userdata['member_id'] == $author_id)
		{
			return ee()->output->show_user_error('general', array(lang('cannot_report_self')));
		}

		// Allowed to Report?
		$meta = $this->_fetch_forum_metadata($forum_id);
		$perms = unserialize(stripslashes($meta[$forum_id]['forum_permissions']));

		if ( ! $this->_permission('can_report', $perms))
		{
			return $this->trigger_error('not_authorized');
		}

		// Author's screen name
		$query = ee()->db->query("SELECT screen_name FROM exp_members WHERE member_id = '{$author_id}'");

		// If this author doesn't exist, then we have problems, but anyway...
		if ($query->num_rows() == 0)
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		$author = $query->row('screen_name') ;

		// Set up redirect
		$this->form_actions['forum:do_report']['RET'] = $this->forum_path('/viewthread/'.$topic_id.'/');
		$this->form_actions['forum:do_report']['forum_path'] = $this->forum_path();
		$this->form_actions['forum:do_report']['forum_id'] = $forum_id;
		$this->form_actions['forum:do_report']['post_id'] = $this->current_id;
		$this->form_actions['forum:do_report']['is_topic'] = ($is_topic) ? 'y' : 'n';

		// Build the template
		$str = $this->load_element('report_form');

		// Topic or Post?
		if ($is_topic)
		{
			$str = $this->allow_if('is_topic', $str);
			$str = $this->deny_if('is_post', $str);
		}
		else
		{
			$str = $this->deny_if('is_topic', $str);
			$str = $this->allow_if('is_post', $str);
		}

		$query = ee()->db->query("SELECT forum_text_formatting, forum_html_formatting, forum_auto_link_urls, forum_allow_img_urls FROM exp_forums WHERE forum_id = '{$forum_id}'");

		ee()->load->library('typography');
		ee()->typography->initialize(array(
			'parse_smileys' => ($parse_smileys == 'y') ? TRUE : FALSE
		));

		$str = $this->var_swap($str,
				array(
					'body'	 => ee()->typography->parse_type($body,
	 								  array(
											'text_format'	=> $query->row('forum_text_formatting') ,
											'html_format'	=> $query->row('forum_html_formatting') ,
											'auto_links'	=> $query->row('forum_auto_link_urls') ,
											'allow_img_url' => $query->row('forum_allow_img_urls')
											)
									  ),
					'author'				=> $author,
					'title'					=> ($is_topic AND isset($title)) ? $title : '',
					'reporter_name'			=> ee()->session->userdata['screen_name'],
					'path:reporter_profile'	=> $this->profile_path(ee()->session->userdata['member_id']),
					'path:post'				=> $this->forum_path('/'.(($is_topic) ? 'viewtopic' : 'viewreply')."/{$this->current_id}/")
				)
			);

		return str_replace('{include:report_form}', $str, $this->load_element('report_page'));
	}

	/**
	 * Report a post
	 */
	function do_report()
	{
		$hidden = array('RET', 'forum_id', 'forum_path', 'post_id', 'is_topic');

		foreach ($hidden as $val)
		{
			if ( ! ($$val = ee()->input->post($val)))
			{
				ee()->functions->redirect($this->forum_path());
				exit;
			}
		}

		// Could have added this in the conditional above, but this is more legible
		if ( ! is_numeric($forum_id) OR ! is_numeric($post_id))
		{
			ee()->functions->redirect($this->forum_path());
			exit;
		}

		// Allowed to Report?
		$meta = $this->_fetch_forum_metadata($forum_id);
		$perms = unserialize(stripslashes($meta[$forum_id]['forum_permissions']));

		if ( ! $this->_permission('can_report', $perms))
		{
			return $this->trigger_error('not_authorized');
		}

		// Did they choose a reason?
		$reason = array();

		if ( ! ($reason = ee()->input->post('reason')))
		{
			return ee()->output->show_user_error('submission', array(lang('report_missing_reason')));
		}

		$reason_text = '';

		foreach ($reason as $val)
		{
			$reason_text .= ($val !== FALSE) ? lang($val)."\n" : '';
		}

		// Is this a topic being reported?
		$is_topic = ($is_topic == 'y') ? TRUE : FALSE;

		if ($is_topic)
		{
			$query = ee()->db->query("SELECT forum_id, topic_id, title, body, author_id, parse_smileys FROM exp_forum_topics WHERE topic_id = '{$post_id}'");
		}
		else
		{
			$query = ee()->db->query("SELECT forum_id, topic_id, body, author_id, parse_smileys FROM exp_forum_posts WHERE post_id = '{$post_id}'");
		}

		if ($query->num_rows() == 0)
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		// Create some variables
		foreach ($query->row_array() as $key => $val)
		{
			$$key = $val;
		}

		// Can't report yourself
		if (ee()->session->userdata['member_id'] == $author_id)
		{
			return ee()->output->show_user_error('general', array(lang('cannot_report_self')));
		}

		$query = ee()->db->query("SELECT screen_name FROM exp_members WHERE member_id ='{$author_id}'");

		// If this author doesn't exist, then we have problems, but anyway...
		if ($query->num_rows() == 0)
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		$author = $query->row('screen_name') ;

		// Load up email addresses
		$addresses = array();

		ee()->db->select('email');
		ee()->db->from('members, forum_moderators');
		ee()->db->where('(exp_members.member_id = exp_forum_moderators.mod_member_id OR exp_members.group_id =  exp_forum_moderators.mod_group_id)', NULL, FALSE);
		ee()->db->where('exp_forum_moderators.mod_forum_id', $forum_id);
		$mquery = ee()->db->get();

		if ($mquery->num_rows() == 0)
		{
			$addresses[] = ee()->config->item('webmaster_email');
		}
		else
		{
			foreach($mquery->result_array() as $row)
			{
				$addresses[] = $row['email'];
			}
		}

		$addresses = array_unique($addresses);

		// Send the notifications

		$swap = array(
						'forum_name'		=> $this->fetch_pref('board_label'),
						'reporter_name'		=> ee()->session->userdata['screen_name'],
						'author'			=> $author,
						'body'				=> ee('Security/XSS')->clean($body),
						'reasons'			=> $reason_text,
						'notes'				=> (ee()->input->post('notes')) ? ee('Security/XSS')->clean($_POST['notes']) : '',
						'post_url'			=> ($is_topic) ? "{$forum_path}viewthread/{$post_id}/" : "{$forum_path}viewreply/{$post_id}/"
					 );

		$template = ee()->functions->fetch_email_template('forum_report_notification');
		$email_tit = ee()->functions->var_swap($template['title'], $swap);
		$email_msg = ee()->functions->var_swap($template['data'], $swap);

		ee()->load->library('email');
		ee()->load->helper('text');

		ee()->email->wordwrap = TRUE;

		foreach ($addresses as $address)
		{
			ee()->email->EE_initialize();
			ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
			ee()->email->to($address);
			ee()->email->reply_to(ee()->config->item('webmaster_email'));
			ee()->email->subject($email_tit);
			ee()->email->message(entities_to_ascii($email_msg));
			ee()->email->send();
		}

		ee()->functions->redirect($RET);
		exit;
	}

	/**
	 * Member banning form
	 */
	function ban_member_form()
	{
		if ( ! $this->_is_admin() OR ! is_numeric($this->current_id))
		{
			return $this->trigger_error();
		}

		// You can't ban yourself
		if ($this->current_id == ee()->session->userdata('member_id'))
		{
			return $this->trigger_error('can_not_ban_yourself');
		}

		// Fetch the member info
		$query = ee()->db->query("SELECT screen_name, group_id FROM exp_members WHERE member_id = '{$this->current_id}'");

		if ($query->num_rows() == 0)
		{
			return $this->trigger_error();
		}

		// Super-admins can't be banned
		if ($query->row('group_id')  == 1)
		{
			return $this->trigger_error('can_not_ban_super_admins');
		}

		// Admins can not be banned - except by a super admin
		if ($this->_is_admin($this->current_id, $query->row('group_id') ) AND ee()->session->userdata('group_id') != 1)
		{
			return $this->trigger_error('admins_can_not_be_banned');
		}

		// Finalize the template
		$form = ee()->functions->form_declaration(array(
												'action' => $this->forum_path('do_ban_member/'.$this->current_id),
												'hidden_fields' => array('board_id' => $this->fetch_pref('original_board_id'))
											)
										);

		$template = $this->var_swap($this->load_element('user_banning_warning'),
								array(
										'name'	=> $this->_convert_special_chars($query->row('screen_name') ),
										'form_declaration' => $form
									)
								);

		// Is user already banned?
		if ($query->row('group_id')  == 2)
		{
			$template = $this->allow_if('user_is_banned', $template);
			$template = $this->deny_if('user_not_banned', $template);
		}
		else
		{
			$template = $this->deny_if('user_is_banned', $template);
			$template = $this->allow_if('user_not_banned', $template);
		}


		return $this->var_swap($this->load_element('user_banning_page'),
								array(
										'include:user_banning_element'	=> $template
									)
								);
	}

	/**
	 * Ban Member
	 */
	function do_ban_member()
	{
		if ( ! $this->_is_admin() OR ! is_numeric($this->current_id) OR ! isset($_POST['action']) OR ! in_array($_POST['action'], array('suspend', 'delete', 'reinstate')))
		{
			return $this->trigger_error();
		}

		// You can't ban yourself
		if ($this->current_id == ee()->session->userdata('member_id'))
		{
			return $this->trigger_error('can_not_ban_yourself');
		}

		// Fetch the member info
		$query = ee()->db->query("SELECT screen_name, group_id, ip_address FROM exp_members WHERE member_id = '".ee()->db->escape_str($this->current_id)."'");

		if ($query->num_rows() == 0)
		{
			return $this->trigger_error();
		}

		$screen_name = $query->row('screen_name') ;
		$ip_address  = $query->row('ip_address') ;

		// Super-admins can't be banned
		if ($query->row('group_id')  == 1)
		{
			return $this->trigger_error('can_not_ban_super_admins');
		}

		// Admins can not be banned - except by a super admin
		if ($this->_is_admin($this->current_id, $query->row('group_id') ) &&
			ee()->session->userdata('group_id') != 1)
		{
			return $this->trigger_error('admins_can_not_be_banned');
		}

		// Ban IP Addresses
		// If we're banning we need to fetch any IPs used by the member
		$banned_user_ips = '';

		if (isset($_POST['ban_ip']) OR $_POST['action'] == 'reinstate')
		{
			$ips = array();

			$ips[] = $ip_address;

			// Topics
			$query = ee()->db->query("SELECT ip_address FROM exp_forum_topics WHERE author_id = '{$this->current_id}'");

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
			$query = ee()->db->query("SELECT ip_address FROM exp_forum_posts WHERE author_id = '{$this->current_id}'");

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
			$query = ee()->db->query("SELECT ip_address FROM exp_comments WHERE author_id = '{$this->current_id}'");

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

			$banned_ips = ee()->config->item('banned_ips');
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

			ee()->config->_update_config(array('banned_ips' => $ips));
		}

		// Reinstate the user
		if ($_POST['action'] == 'reinstate')
		{
			ee()->db->query("UPDATE exp_members SET group_id = '".ee()->config->item('default_member_group')."' WHERE member_id = '{$this->current_id}'");
			$ban_msg = lang('user_account_reinstated');
			$banned_user_ips = '';
		}
		elseif ($_POST['action'] == 'suspend')
		{
			// Suspend the user
			ee()->db->query("UPDATE exp_members SET group_id = '2' WHERE member_id = '{$this->current_id}'");
			$ban_msg = lang('user_account_suspended');
		}
		else
		{
			ee('Model')->get('Member', $this->current_id)->delete();

			$ban_msg = lang('user_account_deleted');
		}

		// Finalize the template
		$template = $this->var_swap($this->load_element('user_banning_report'),
								array(
										'name'	=> $this->_convert_special_chars($screen_name),
										'lang:member_banned' => $ban_msg
									)
								);



		if ($banned_user_ips == '')
		{
			$template = $this->deny_if('banned_ips', $template);
		}
		else
		{
			$template = $this->allow_if('banned_ips', $template);
		}

		return $this->var_swap($this->load_element('user_banning_page'),
								array(
										'include:user_banning_element' => $template,
										'banned_ips' => $banned_user_ips,
										'lang:member_banning' => ($_POST['action'] == 'reinstate') ? lang('member_reinstating') : lang('member_banning')
									)
								);
	}

	/**
	 * Ignore Member Confirmation
	 */
	public function ignore_member()
	{
		if ($this->current_id == ee()->session->userdata('member_id'))
		{
			return $this->trigger_error('can_not_ignore_yourself');
		}

		// Fetch member info
		$query = ee()->db->query("SELECT screen_name FROM exp_members WHERE member_id = '{$this->current_id}'");

		if ($query->num_rows() == 0)
		{
			return $this->trigger_error();
		}

		// Output the template
		$form = ee()->functions->form_declaration(array(
												'action' => $this->forum_path('do_ignore_member/'.$this->current_id.'/'),
												'hidden_fields' => array('board_id' => $this->fetch_pref('original_board_id'))
											)
										);

		$template = $this->var_swap($this->load_element('ignore_member_confirmation'),
								array(
										'name'	=> $this->_convert_special_chars($query->row('screen_name') ),
										'form_declaration' => $form
									)
								);

		// Already ignoring this member?
		if (in_array($this->current_id, ee()->session->userdata['ignore_list']))
		{
			$template = $this->allow_if('member_is_ignored', $template);
			$template = $this->deny_if('member_not_ignored', $template);
		}
		else
		{
			$template = $this->deny_if('member_is_ignored', $template);
			$template = $this->allow_if('member_not_ignored', $template);
		}


		return $this->var_swap($this->load_element('ignore_member_page'),
								array(
										'include:member_ignore_element'	=> $template
									)
								);
	}

	/**
	 * Do Ignore Member
	 */
	public function do_ignore_member()
	{
		if ($this->current_id == ee()->session->userdata('member_id'))
		{
			return $this->trigger_error('can_not_ignore_yourself');
		}

		if ( ! ($action = ee()->input->post('action')) OR ($action != 'ignore' AND $action != 'unignore'))
		{
			return $this->trigger_error();
		}

		// Fetch member info
		$query = ee()->db->query("SELECT screen_name FROM exp_members WHERE member_id = '{$this->current_id}'");

		if ($query->num_rows() == 0)
		{
			return $this->trigger_error();
		}

		$ignored = ee()->session->userdata['ignore_list'];
		$in_list = in_array($this->current_id, $ignored);

		if (($action == 'ignore' AND $in_list) OR ($action == 'unignore' AND ! $in_list))
		{
			return $this->trigger_error();
		}

		if ($action == 'ignore')
		{
			$ignored[] = $this->current_id;
		}
		else
		{
			$ignored = array_diff($ignored, array($this->current_id));
		}

		ee()->db->query(ee()->db->update_string('exp_members', array('ignore_list' => implode('|', $ignored)), "member_id = '".ee()->session->userdata['member_id']."'"));

		if (isset(ee()->session->tracker[2]))
		{
			$return = reduce_double_slashes(str_replace($this->trigger, '', ee()->session->tracker[2]));
			ee()->functions->redirect($this->forum_path($return));
		}

		ee()->functions->redirect($this->forum_path());
	}

	/**
	 * Parse Visitor Stats
	 */
	public function visitor_stats()
	{
		ee()->stats->load_stats();
		$statdata = ee()->stats->statdata();

		if (empty($statdata))
		{
			return;
		}

		$str = $this->load_element('visitor_stats');

		// Parse Date-based stats
		$dates = array(
		  'last_entry_date'      => ee()->stats->statdata('last_entry_date'),
		  'last_forum_post_date' => ee()->stats->statdata('last_forum_post_date'),
		  'last_comment_date'    => ee()->stats->statdata('last_comment_date'),
		  'last_visitor_date'    => ee()->stats->statdata('last_visitor_date'),
		  'most_visitor_date'    => ee()->stats->statdata('most_visitor_date')
		);
		$str = ee()->TMPL->parse_date_variables($str, $dates);

		// Parse Non-date-based stats
		foreach (array('total_members', 'total_logged_in', 'total_guests',
						'total_anon', 'total_entries', 'total_forum_topics',
						'total_forum_posts', 'total_forum_replies', 'total_comments',
						'most_visitors', 'recent_member') as $stat )
		{
			$str = str_replace('{'.$stat.'}', ee()->stats->statdata($stat), $str);
		}

		// Recent Member Registration List
		if (preg_match("/\{recent_member_names(.*?)\}(.*?){\/recent_member_names\}/s", $str, $match))
		{
			$limit	= (preg_match("/.*limit=[\"|'](.+?)[\"|']/", $match['1'], $match2)) ? $match2['1'] : 10;
			$back	= (preg_match("/.*backspace=[\"|'](.+?)[\"|']/", $match['1'], $match2)) ? $match2['1'] : '';

			ee()->db->select('screen_name, member_id');
			ee()->db->where('group_id != 2');
			ee()->db->where('group_id != 4');
			ee()->db->order_by('member_id', 'DESC');
			ee()->db->limit($limit);
			$query = ee()->db->get('members');

			$names = '';

			foreach ($query->result_array() as $row)
			{
				$temp = $match['2'];
				$temp = str_replace('{path:member_profile}', $this->profile_path($row['member_id']), $temp);
				$temp = str_replace('{name}', $this->_convert_special_chars($row['screen_name']), $temp);
				$names .= $temp;
			}

			if ($back != '')
			{
				$names = substr($names, 0, - $back);
			}

			$str = str_replace($match['0'], $names, $str);
		}

		// Generate the "whos online" list
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

		if (count(ee()->stats->statdata('current_names')) == 0)
		{
			return preg_replace("/\{member_names.*?\}.*?\{\/member_names\}/s", '', $str);
		}

		foreach (ee()->stats->statdata('current_names') as $k => $v)
		{
			$temp = $chunk;

			// Highlight the Moderator

			if (in_array($k, $moderators))
			{
				$v['0'] = "<span class='activeModerator'>".$v['0']."</span>";
			}

			if ($v['1'] == 'y')
			{
				if (ee()->session->userdata('group_id') == 1)
				{
					$temp = preg_replace("/\{name\}/", $v['0'].'*', $temp);
				}
				elseif (ee()->session->userdata('member_id') == $k)
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

			$names .= preg_replace("/\{path:member_profile}/", $this->profile_path($k), $temp);
		}

		if (preg_match("/\{member_names.+?backspace=[\"|'](.+?)[\"|']/", $str, $backspace))
		{
			$names = substr($names, 0, - $backspace['1']);
		}

		$str = preg_replace("/\{member_names.*?\}.*?\{\/member_names\}/s", $names, $str);

		$str = str_replace('{name}', '', $str);

		return $str;
	}

	/**
	 * Individual Member's Last Visit
	 */
	public function member_post_total()
	{
		return str_replace('%x', ee()->session->userdata('total_forum_posts'), lang('your_post_total'));
	}

	/**
	 * Simple Search Form
	 */
	public function login_form_mini()
	{
		$this->form_actions['member:member_login']['anon'] = 1;
		return $this->load_element('login_form_mini');
	}

	/**
	 * Advanced Search Form
	 */
	public function advanced_search_form()
	{
		ee()->lang->loadfile('search');

		// Before doing anything we need to load the permissions for all
		// forums and see which ones the user can search in.
		$forums = $this->_fetch_allowed_search_ids();

		if ($forums === FALSE)
		{
			return $this->trigger_error('search_not_available');
		}

		// No permission?  See ya...
		if (count($forums) == 0)
		{
			return $this->trigger_error('not_allowed_to_search');
		}

		// Build out the <option> list
		$options = "<option value='all' selected='selected'>".lang('search_all_forums')."</option>\n";

		foreach ($forums as $id => $val)
		{
			$pre = ($val['forum_is_cat'] == 'y') ? '' : '&nbsp;- ';
			$options .= "<option value='".$id."'>".$pre.$val['forum_name']."</option>\n";
		}

		// Build the Member Group list
		$groups = "<option value='all' selected='selected'>".lang('search_all_groups')."</option>\n";

		ee()->db->select('group_id, group_title');
		ee()->db->where_not_in('group_id', array('2', '3', '4'));
		ee()->db->where('site_id', ee()->config->item('site_id'));
		ee()->db->where('include_in_memberlist', 'y');
		$query = ee()->db->get('member_groups');

		foreach ($query->result_array() as $row)
		{
			$groups .= "<option value='{$row['group_id']}'>{$row['group_title']}</option>\n";
		}

		// Create form
		$form = ee()->functions->form_declaration(array(
												'action' => $this->forum_path('do_search'),
												'hidden_fields' => array('board_id' => $this->fetch_pref('original_board_id'))
											)
										);

		return $this->var_swap($this->load_element('advanced_search_form'),
							array(
									'forum_select_options'			=> $options,
									'member_group_select_options'	=> $groups,
									'form_declaration'				=> $form
								)
							);
	}

	/**
	 * Fetch the forums that can be searched
	 *
	 * There are four sets of preferences which determine if a user can search:
	 *	- can_view_forum
	 *	- can_view_hidden
	 *	- can_view_topics
	 *	- can_search
	 */
	public function _fetch_allowed_search_ids()
	{
		ee()->db->select('forum_id, forum_name, forum_status, forum_is_cat,
								forum_parent, forum_permissions, forum_enable_rss');
		ee()->db->where('board_id', $this->fetch_pref('board_id'));
		ee()->db->order_by('forum_order');
		$query = ee()->db->get('forums');

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

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

	/**
	 * Swap Date
	 */
	public function _swap_date($replace)
	{
		if ($this->date_limit == '')
		{
			return '';
		}

		return str_replace('{dd}', $replace, $this->date_limit);
	}

	/**
	 * Cache the search result
	 */
	public function _cache_search_result($topic_ids, $post_ids, $keywords, $sort_order)
	{
		$hash = ee()->functions->random('md5');

		$data = array(
						'search_id'		=> $hash,
						'search_date'	=> time(),
						'member_id'		=> ee()->session->userdata('member_id'),
						'keywords'		=> $keywords,
						'sort_order'	=> $sort_order,
						'ip_address'	=> ee()->input->ip_address(),
						'topic_ids'		=> addslashes(serialize($topic_ids)),
						'post_ids'		=> addslashes(serialize($post_ids)),
						'board_id'		=> $this->fetch_pref('board_id')
						);

		ee()->db->query(ee()->db->insert_string('exp_forum_search', $data));

		return $hash;
	}

	/**
	 * Perform Member Search
	 */
	public function member_search()
	{
		return $this->do_search($this->current_id, FALSE);
	}

	/**
	 * Perform New Topic Search
	 */
	public function new_topic_search()
	{
		return $this->do_search('', TRUE);
	}

	/**
	 * Perform Pending Topic Search
	 * This fetches topics that have no threads in them yet
	 */
	public function view_pending_topics()
	{
		return $this->do_search('', FALSE, TRUE);
	}

	/**
	 * Perform Active Topic Search
	 * Fetches topics that are active today
	 */
	public function active_topic_search()
	{
		return $this->do_search('', TRUE, FALSE, TRUE);
	}

	/**
	 * Perform Search
	 */
	public function do_search($member_id = '', $new_topic_search = FALSE, $view_pending_topics = FALSE, $active_topic_search = FALSE)
	{
		ee()->lang->loadfile('search');

		// Flood control

		if (ee()->session->userdata['search_flood_control'] > 0 AND ee()->session->userdata['group_id'] != 1)
		{
			$cutoff = time() - ee()->session->userdata['search_flood_control'];

			$sql = "SELECT search_id FROM exp_forum_search WHERE search_date > '{$cutoff}' AND ";

			if (ee()->session->userdata['member_id'] != 0)
			{
				$sql .= "(member_id='".ee()->db->escape_str(ee()->session->userdata('member_id'))."' OR ip_address='".ee()->db->escape_str(ee()->input->ip_address())."')";
			}
			else
			{
				$sql .= "ip_address='".ee()->db->escape_str(ee()->input->ip_address())."'";
			}

			$query = ee()->db->query($sql);

			if ($query->num_rows() > 0)
			{
				return ee()->output->show_user_error('general', str_replace("%x", ee()->session->userdata['search_flood_control'], lang('search_time_not_expired')));
			}
		}

		// Fetch allowed forums
		// Before doing anything else we'll fetch the forum IDs
		// that the user is allowed to search in.

		$forums = $this->_fetch_allowed_search_ids();

		if ($forums === FALSE OR count($forums) == 0)
		{
			return $this->trigger_error('not_allowed_to_search');
		}

		// Which forums are we searching in?
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

		// Did the user submit any keywords?
		// We only require a keyword if the member name field is blank
		// or if we are not searching by most recent topic
		if ($member_id == '' AND $new_topic_search == FALSE AND $view_pending_topics == FALSE)
		{
			if ( ! isset($_POST['member_name']) OR $_POST['member_name'] == '')
			{
				if ( ! isset($_POST['keywords']) OR $_POST['keywords'] == "")
				{
					$data = array(	'title' 	=> lang('error'),
									'heading'	=> lang('error'),
									'content'	=> lang('search_no_keywords'),
									'link'		=> array($this->forum_path('search'), lang('advanced_search'))
								 );

					return ee()->output->show_message($data);
				}
			}
		}

		// Strip extraneous junk from keywords
		if ( ! isset($_POST['keywords']))
		{
			$_POST['keywords'] = '';
		}

		if ($_POST['keywords'] != "")
		{
			// Load the search helper so we can filter the keywords
			ee()->load->helper('search');

			$this->keywords = sanitize_search_terms($_POST['keywords']);

			// Is the search term long enough?
			if (strlen($this->keywords) < $this->min_length)
			{
				$data = array(	'title' 	=> lang('error'),
								'heading'	=> lang('error'),
								'content'	=> str_replace("%x", $this->min_length, lang('search_min_length')),
								'link'		=> array($this->forum_path('search'), lang('advanced_search'))
							 );

				return ee()->output->show_message($data);
			}

			// Load the text helper
			ee()->load->helper('text');

			$this->keywords = (ee()->config->item('auto_convert_high_ascii') == 'y') ? ascii_to_entities($this->keywords) : $this->keywords;

			$ignore = ee()->config->loadFile('stopwords');

			// Remove "ignored" words
			if (isset($_POST['search_criteria']) &&
				$_POST['search_criteria'] != 'exact')
			{
				foreach ($ignore as $badword)
				{
					$this->keywords = preg_replace("/\b".preg_quote($badword)."\b/i","", $this->keywords);
				}

				if ($this->keywords == '')
				{
					return ee()->output->show_user_error('general', array(lang('search_no_stopwords')));
				}
			}

			// Log Search Terms
			ee()->functions->log_search_terms($this->keywords, 'forum');
		}

		// Searching by Member Group?
		$sql_topic_join = '';
		$sql_post_join = '';
		$groups = '';

		if (isset($_POST['member_group']) AND is_array($_POST['member_group']))
		{
			if ( ! empty($_POST['member_group']) AND ! in_array('all', $_POST['member_group']))
			{
				foreach($_POST['member_group'] AS $key => $value)
				{
					$_POST['member_group'][$key] = ee()->db->escape_str($value);
				}

				$sql_topic_join = "\nLEFT JOIN exp_members ON exp_forum_topics.author_id = exp_members.member_id \n";
				$sql_post_join	= "\nLEFT JOIN exp_members ON p.author_id = exp_members.member_id \n";
				$groups = "'".implode("','", $_POST['member_group'])."'";
			}
		}

		// Are we searching by name?
		// If so, we'll fetch the member_id number

		$author_id		= 0;
		$screen_name	= '';

		if (isset($_POST['member_name']) AND $_POST['member_name'] != "")
		{
			$screen_name = $_POST['member_name'];

			$sql = "SELECT member_id FROM exp_members WHERE ";

			if (isset($_POST['exact_match']) AND $_POST['exact_match'] == 'y')
			{
				$sql .= " screen_name = '".ee()->db->escape_str(ee('Security/XSS')->clean($_POST['member_name']))."' ";
			}
			else
			{
				$sql .= " screen_name LIKE '%".ee()->db->escape_like_str(ee('Security/XSS')->clean($_POST['member_name']))."%' ";
			}

			if ($groups != '')
			{
				$sql .= "AND exp_members.group_id IN ({$groups}) ";
			}

			$query = ee()->db->query($sql);

			if ($query->num_rows() == 0)
			{
				$data = array(	'title' 	=> lang('error'),
								'heading'	=> lang('error'),
								'content'	=> lang('no_name_result'),
								'link'		=> array($this->forum_path('search'), lang('advanced_search'))
							 );

				return ee()->output->show_message($data);
			}

			if ($query->num_rows() > 1)
			{
				$data = array(	'title' 	=> lang('error'),
								'heading'	=> lang('error'),
								'content'	=> lang('too_many_name_results'),
								'link'		=> array($this->forum_path('search'), lang('advanced_search'))
							 );

				return ee()->output->show_message($data);
			}

			$author_id = $query->row('member_id') ;
		}

		if ($member_id != '')
		{
			$author_id = $member_id;
			$res = ee()->db->query("SELECT screen_name FROM exp_members WHERE member_id = '{$author_id}'");
			$screen_name = $res->row('screen_name') ;
		}

		// Set the default preferences
		$search_in		= ( ! isset($_POST['search_in']) OR ! in_array($_POST['search_in'], array('titles', 'posts', 'all'))) ? 'all' : $_POST['search_in'];
		$criteria 		= ( ! isset($_POST['search_criteria']) OR ! in_array($_POST['search_criteria'], array('any', 'all', 'exact'))) ? 'all' : $_POST['search_criteria'];
		$date			= ( ! isset($_POST['date']) OR ! is_numeric($_POST['date'])) ? '0' : $_POST['date'];
		$order_by 		= ( ! isset($_POST['order_by']) OR ! in_array($_POST['order_by'], array('date', 'title', 'most_posts', 'recent_post'))) ? 'date' : $_POST['order_by'];
		$date_order 	= ( ! isset($_POST['date_order']) OR ! in_array($_POST['date_order'], array('newer', 'older'))) ? 'newer' : $_POST['date_order'];
		$sort_order 	= ( ! isset($_POST['sort_order']) OR ! in_array($_POST['sort_order'], array('asc', 'desc'))) ? 'desc' : $_POST['sort_order'];
		$keywords		= $this->keywords;
		$keywords_like	= ee()->db->escape_like_str(trim($keywords));
		$keywords		= ee()->db->escape_str(trim($keywords));

		// Do we have multiple search terms?
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

		// Compile the date/order criteria
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
			$cutoff = ee()->localize->now - (60*60*24*$date);

			if ($date_order == 'older')
			{
				$this->date_limit .= " {dd} < ".$cutoff." ";
			}
			else
			{
				$this->date_limit .= " {dd} > ".$cutoff." ";
			}
		}

		// Build the topic search query

		// Since topics and posts are stored in their
		// own tables we need to build two queries.
		// The first one queries the topic table and the
		// second one does the post table.
		// Each returns a list of topic_id numbers, which
		// we'll compile into one array later.

		// TOPIC QUERY
		$sql = "SELECT topic_id
				FROM exp_forum_topics {$sql_topic_join}
				WHERE board_id = '".$this->fetch_pref('board_id')."'
				AND ";

		// Limit the search to specific forums
		$sql .= ' (';

		foreach ($forums as $id => $val)
		{
			$sql .= " forum_id = '{$id}' OR";
		}

		$sql  = substr($sql, 0, -2);
		$sql .= ') ';

		// Filter by date
		if ($this->_swap_date('topic_date') != '' &&
			$new_topic_search === FALSE && $view_pending_topics === FALSE)
		{
			$sql .= "AND ".$this->_swap_date('topic_date')." ";
		}

		// Limit to topics with no replies
		if ($view_pending_topics == TRUE AND $active_topic_search == FALSE)
		{
			$one_month_ago = time() - (60*60*24*30);
			$sql .= "AND thread_total = 1 AND topic_date > ".$one_month_ago;
		}

		// Filter New Topic Date
		$ignore_ids = array();

		if ($new_topic_search == TRUE)
		{
			$last_visit = (int) ee()->session->userdata('last_visit');
			$sql .= "AND topic_date > ".$last_visit." ";

			// Do we need to igore any recently visited topics?
			if ($last_visit > 0)
			{
				if ($active_topic_search == TRUE)
				{
					$ct = date('H', ee()->localize->now);

					if ($ct < 12)
					{
						$cutoff = ee()->localize->now - (12 * 3600);
					}
					else
					{
						$cutoff = ee()->localize->now - ($ct * 3600);
					}
				}
				else
				{
					$cutoff = $last_visit;
				}

				$tquery = ee()->db->query("SELECT topic_id, last_post_date FROM exp_forum_topics WHERE last_post_date > '".$cutoff."' AND board_id = '".$this->fetch_pref('board_id')."'");

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
					$sql .= " AND topic_id NOT IN('" . implode("','",$ignore_ids) . "')";
				}
			}
		}

		// Filter by author
		if ($author_id > 0)
		{
			$sql .= "AND author_id = '{$author_id}' ";
		}

		// Filter by Member Group
		if ($sql_topic_join != '')
		{
			$sql .= "AND exp_members.group_id IN ({$groups}) ";
		}

		if ($keywords != '')
		{
			// Exact Match Search
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
				// "Any" or "All" Search

				// If we don't have multiple keywords we'll
				// do a simple string search

				if (count($terms_like) == 0)
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
					// Multiple Keyword Searches
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

		// Run the query and compile the topic IDs
		$query = ee()->db->query($sql);

		$topic_ids = array();

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$topic_ids[] = $row['topic_id'];
			}
		}

		// If this is a title search or a "no reply" search we're done
		if ($search_in == 'titles' OR $view_pending_topics == TRUE)
		{
			if (count($topic_ids) == 0)
			{
				return ee()->output->show_user_error('off', array(lang('search_no_result')), lang('search_result_heading'));
			}

			$sql = "SELECT topic_id FROM exp_forum_topics WHERE topic_id IN (".implode(',', array_unique($topic_ids)).')'.$order;

			$query = ee()->db->query($sql);

			$topic_ids = array();
			foreach ($query->result_array() as $row)
			{
				$topic_ids[] = $row['topic_id'];
			}

			// Cache the result and redirect to the result page

			if ($view_pending_topics == TRUE)
			{
				$alt_word = lang('view_pending_topics');
			}
			else
			{
				$alt_word = $screen_name;
			}

			$words = ($keywords != '') ? $keywords : $alt_word;

			$search_id = $this->_cache_search_result($topic_ids, array(), $words, $order);

			$data = array(	'title' 	=> lang('search'),
							'heading'	=> lang('thank_you'),
							'content'	=> lang('search_redirect_msg'),
							'redirect'	=> $this->forum_path('search_results/'.$search_id),
							'link'		=> array($this->forum_path('search_results/'.$search_id), $this->fetch_pref('forum_name'))
						 );

			return ee()->output->show_message($data);
		}

		// POST QUERY
		$sql = "SELECT p.topic_id, p.post_id
				FROM (exp_forum_posts p, exp_forum_topics t) {$sql_post_join}
				WHERE t.topic_id = p.topic_id ";

		// Limit the search to specific forums
		$sql .= 'AND (';

		foreach ($forums as $id => $val)
		{
			$sql .= " p.forum_id = '{$id}' OR";
		}

		$sql  = substr($sql, 0, -2);
		$sql .= ') ';

		// Ignore topics
		if (count($ignore_ids) > 0)
		{
			foreach ($ignore_ids as $ignore)
			{
				$sql .= " AND p.topic_id != '".$ignore."' ";
			}
		}

		// Filter by date
		if ($this->_swap_date('post_date') != '' AND $new_topic_search == FALSE)
		{
			$sql .= "AND ".$this->_swap_date('post_date');
		}

		// Filter New Topic Date
		if ($new_topic_search == TRUE)
		{
			$sql .= "AND p.post_date > ".ee()->session->userdata('last_visit')." ";
		}

		// Filter by author
		if ($author_id > 0)
		{
			$sql .= "AND p.author_id = '{$author_id}' ";
		}

		// Filter by member group
		if ($sql_post_join != '')
		{
			$sql .= "AND exp_members.group_id IN ({$groups}) ";
		}

		if ($keywords != '')
		{
			// Exact Match Search
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
				// "Any" or "All" Search
				// If we don't have multiple keywords we'll do a simple string search
				if (count($terms_like) == 0)
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

		// Run the query and compile the topic IDs
		$query = ee()->db->query($sql);

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
			return ee()->output->show_user_error('off', array(lang('search_no_result')), lang('search_result_heading'));
		}


		$sql = "SELECT topic_id FROM exp_forum_topics WHERE topic_id IN (".implode(',', array_unique($topic_ids)).')'.$order;

		$query = ee()->db->query($sql);

		$topic_ids = array();
		foreach ($query->result_array() as $row)
		{
			$topic_ids[] = $row['topic_id'];
		}

		// Cache the result and redirect to the result page
		$alt_word = ($new_topic_search == FALSE) ? $screen_name : lang('new_topic_search');

		$words = ($keywords != '') ? $keywords : $alt_word;

		$search_id = $this->_cache_search_result($topic_ids, $post_ids, $words, $order);

		$data = array(
			'title' 	=> lang('search'),
			'heading'	=> lang('thank_you'),
			'content'	=> lang('search_redirect_msg'),
			'redirect'	=> $this->forum_path('search_results/'.$search_id),
			'link'		=> array($this->forum_path('search_results/'.$search_id), $this->fetch_pref('forum_name'))
		 );

		return ee()->output->show_message($data);
	}

	/**
	 * Search Results Page
	 */
	public function search_results_page()
	{
		ee()->lang->loadfile('search');

		// If the search ID is less than 32 characters long we don't have a valid search ID number

		if (strlen($this->current_id) < 32)
		{
			return ee()->output->show_user_error('off', array(lang('search_no_result')), lang('search_result_heading'));
		}

		// Clear old search results
		// We cache search results for 2 hours

		$expire = time() - (2 * 3600);

		ee()->db->query("DELETE FROM exp_forum_search WHERE search_date < '$expire'");

		// Fetch the cached search query
		$query = ee()->db->query("SELECT * FROM exp_forum_search WHERE search_id = '".ee()->db->escape_str($this->current_id)."' AND ip_address = '".ee()->input->ip_address()."' AND member_id = '".ee()->session->userdata('member_id')."' ");

		if ($query->num_rows() == 0)
		{
			return ee()->output->show_user_error('off', array(lang('search_no_result')), lang('search_result_heading'));
		}

		$topic_ids = unserialize(stripslashes($query->row('topic_ids') ));
		$post_ids  = unserialize(stripslashes($query->row('post_ids') ));

		// Load the XML Helper
		ee()->load->helper('xml');

		$keywords	= xml_convert($query->row('keywords') );
		$sort_order = $query->row('sort_order');

		// Load the template
		$str = $this->load_element('search_results');

		// Determine the per page count
		ee()->db->select('board_topics_perpage');
		ee()->db->where('board_id', $this->fetch_pref('board_id'));
		$per_page_query = ee()->db->get('forum_boards');

		// Note: yes this could be a ternary, but it'd be hideous
		if ($per_page_query->num_rows() > 0 AND $per_page_query->row('board_topics_perpage') != '')
		{
			$topic_limit = $per_page_query->row('board_topics_perpage');
		}
		else
		{
			$topic_limit = 20;
		}

		// Check to see if the old style pagination exists
		// @deprecated 2.8
		if (stripos($str, LD.'if paginate'.RD) !== FALSE)
		{
			$str = preg_replace("/{if paginate}(.*?){\/if}/uis", "{paginate}$1{/paginate}", $str);
			ee()->load->library('logger');
			ee()->logger->developer('{if paginate} has been deprecated, use normal {paginate} tags in your forum search results template.', TRUE, 604800);
		}

		// Load up pagination and start parsing
		$total_rows = count($topic_ids);
		ee()->load->library('pagination');
		$pagination = ee()->pagination->create();
		$pagination->position = 'inline';
		$str = $pagination->prepare($str);

		if ($total_rows > $topic_limit)
		{
			$pagination->build($total_rows, $topic_limit);

			// Slice our array so we can limit the query properly
			$topic_ids = array_slice($topic_ids, $this->current_page, $topic_limit);
		}

		// Fetch the topics
		$qry = ee()->db->select('t.forum_id, t.topic_id, t.author_id,
									  t.moved_forum_id, t.ip_address, t.title,
									  t.status, t.sticky, t.thread_views,
									  t.topic_date, t.thread_total,
									  t.last_post_author_id,  t.last_post_date,
									  m.screen_name AS author,
									  lp.screen_name AS last_post_author')
							->from('forum_topics t')
							->join('members m', 'm.member_id = t.author_id', 'left')
							->join('members lp', 'lp.member_id = t.last_post_author_id', 'left')
							->where_in('t.topic_id', array_unique($topic_ids))
							->order_by('last_post_date','DESC')
							->get();

		if ($qry->num_rows() == 0)
		{
			return ee()->output->show_user_error('off', array(lang('search_no_result')), lang('search_result_heading'));
		}

		// Load the typography class
		ee()->load->library('typography');
		ee()->typography->initialize();

		// Fetch member info for "reply" results
		$member_info = array();

		if ( ! empty($post_ids))
		{
			$POST_IDS = array();

			// flatten the array for use by Active Record
			foreach ($post_ids as $post_array)
			{
				foreach ($post_array as $pid)
				{
					$POST_IDS[] = $pid;
				}
			}

			$m_query = ee()->db->select('p.post_id, p.body, m.screen_name, m.member_id')
									->from('forum_posts p')
									->join('members m', 'p.author_id = m.member_id', 'left')
									->where_in('p.post_id', $POST_IDS)
									->get();

			// again with the something has gone terribly wrong...
			if ($m_query->num_rows() == 0)
			{
				return ee()->output->show_user_error('off', array(lang('search_no_result')), lang('search_result_heading'));
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
						$match[0] = ee('Variables/Parser')->getFullTag($snippet, $match[0], '[quote', '[/quote]');
					}

					$snippet = str_replace($match['0'], '', $snippet);
				}

				$snippet = substr($snippet, 0, 30);

				if (bool_config_item('enable_censoring'))
				{
					$snippet = ee('Format')->make('Text', $snippet)->censor();
				}

				$reply_info[$row['post_id']] = array(
														'member_id' => $row['member_id'],
														'screen_name' => $row['screen_name'],
														'snippet' => $snippet
													);
			}
		}

		// Fetch the "row" template
		$template = $this->load_element('result_rows');

		// Fetch the topic markers
		$markers = $this->_fetch_topic_markers();

		// Parse the results
		$topics = '';
		$count = 0;

		if (preg_match("/".LD."switch\s*=\s*(\042|\047)([^\\1]*?)\\1".RD."/si", $template, $smatch))
		{
			$switches = explode('|', $smatch['2']);
		}

		// Parse and prep 'reply_results' conditionals
		preg_match_all("/".LD."if reply_results.*?".RD.".*?".LD."\/if".RD."/s", $template, $rconds, PREG_SET_ORDER);

		ee()->load->helper('date');

		foreach ($qry->result_array() as $row)
		{
			$temp = $template;
			$count++;

			// Assign the post marker (folder image)
			$topic_type = '';

			$topic_marker = $markers['new'];
			$temp = $this->allow_if('new_topic', $temp);

			// Do we need small pagination links?
			$tquery = ee()->db->query("SELECT forum_id, forum_posts_perpage, forum_name FROM exp_forums WHERE forum_id = '{$row['forum_id']}'");
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
				$baselink = $this->forum_path('/viewthread/'.$row['topic_id'].'/');

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
				$temp = $this->allow_if('pagelinks', $temp);
			}
			else
			{
				$temp = $this->deny_if('pagelinks', $temp);
			}

			// Replace {switch="foo|bar|..."}
			if ( ! empty($switches))
			{
				$switch = $switches[($count + count($switches) - 1) % count($switches)];
				$temp = str_replace($smatch['0'], $switch, $temp);
			}

			// reply_results conditionals
			if ( ! empty($rconds))
			{
				foreach ($rconds as $rcond)
				{
					$num_replies = (isset($post_ids[$row['topic_id']])) ? count($post_ids[$row['topic_id']]) : 0;
					$rcond[1] = ee()->functions->prep_conditionals($rcond[0], array('reply_results' => $num_replies), 'y');
					$temp = str_replace($rcond[0], $rcond[1], $temp);
				}
			}

			// Swap out the template variables
			$temp = $this->deny_if('new_topic', $temp);

			if (isset($post_ids[$row['topic_id']]))
			{
				$reply_temp = $this->load_element('reply_results');
				$reply_results = '';

				foreach ($post_ids[$row['topic_id']] as $post_id)
				{
					$r_temp = $reply_temp;

					$r_temp = $this->var_swap($r_temp,
									array(
											'author'				=>	$reply_info[$post_id]['screen_name'],
											'path:member_profile'	=>	$this->profile_path($reply_info[$post_id]['member_id']),
											'snippet'				=>	ee()->functions->encode_ee_tags($reply_info[$post_id]['snippet'], TRUE),
											'path:viewreply'		=>	$this->forum_path('/viewreply/'.$post_id.'/')
										)
									);

					$reply_results .= $r_temp;
				}

				$temp = str_replace('{include:reply_results}', $reply_results, $temp);
			}

			$title = $this->_convert_special_chars($row['title']);

			if (bool_config_item('enable_censoring'))
			{
				$title = ee('Format')->make('Text', $title)->censor();
			}

			$temp = $this->var_swap($temp,
					array(
							'topic_marker'			=>	$topic_marker,
							'topic_type'			=>  $topic_type,
							'topic_title'			=>	$title,
							'forum_name'			=>  $tquery->row('forum_name') ,
							'author'				=>	$row['author'],
							'total_views'			=>	$row['thread_views'],
							'total_posts'			=>	$row['thread_total'],
							'reply_author'			=>	$row['last_post_author'],
							'path:member_profile'	=>	$this->profile_path($row['author_id']),
							'path:viewforum'		=>	$this->forum_path('/viewforum/'.$tquery->row('forum_id') .'/'),
							'path:view_thread'		=>	$this->forum_path('/viewthread/'.$row['topic_id'].'/'),
							'path:search_thread'	=>	$this->forum_path('/search_thread/'.$this->current_id.$row['topic_id'].'/'),
							'path:reply_member_profile'	=> $this->profile_path($row['last_post_author_id'])
						)
					);

			$temp = ee()->TMPL->parse_date_variables($temp, array('last_reply' => $row['last_post_date']));

			// Complile the string

			$topics .= $temp;
		}

		$str = $pagination->render($str);
		$str = str_replace('{include:result_rows}', $topics, $str);

		// Parse the template
		return $this->var_swap($this->load_element('search_results_page'),
					array(
						'include:search_results'	=> $str,
						'keywords'					=> ee()->functions->encode_ee_tags($keywords),
						'total_results'				=> $total_rows,
						'path:new_topic' 			=> $this->forum_path('/newtopic/'.$this->current_id.'/')
						)
					);
	}

	/**
	 * Search Thread Page
	 */
	public function search_thread_page()
	{
		ee()->lang->loadfile('search');

		$topic_id = substr($this->current_id, 32);
		$this->current_id = substr($this->current_id, 0, 32);

		if (strlen($this->current_id) < 32 OR $topic_id == '' OR ! is_numeric($topic_id))
		{
			return ee()->output->show_user_error('off', array(lang('search_no_result')), lang('search_result_heading'));
		}

		// Clear old search results
		// We cache search results for 2 hours

		$expire = time() - (2 * 3600);

		ee()->db->where('search_date < ', $expire)->delete('forum_search');

		// Fetch the cached search query
		$query = ee()->db->where('search_id', $this->current_id)->get('forum_search');

		if ($query->num_rows() == 0)
		{
			return ee()->output->show_user_error(
				'off',
				array(lang('search_no_result')),
				lang('search_result_heading')
			);
		}

		$post_ids  = unserialize(stripslashes($query->row('post_ids') ));

		if ( ! isset($post_ids[$topic_id]))
		{
			return ee()->output->show_user_error(
				'off',
				array(lang('search_no_result')),
				lang('search_result_heading')
			);
		}

		// Load the XML Helper
		ee()->load->helper('xml');

		// we are only concerned about posts for this topic
		$post_ids	= $post_ids[$topic_id];
		$keywords	= xml_convert($query->row('keywords') );

		// Load the template
		$str = $this->load_element('thread_search_results');

		// Check to see if the old style pagination exists
		// @deprecated 2.8
		if (stripos($str, LD.'if paginate'.RD) !== FALSE)
		{
			$str = preg_replace("/{if paginate}(.*?){\/if}/uis", "{paginate}$1{/paginate}", $str);
			ee()->load->library('logger');
			ee()->logger->developer('{if paginate} has been deprecated, use normal {paginate} tags in your forum search results template.', TRUE, 604800);
		}

		// Load up pagination and start parsing
		$post_limit	= 20;
		$total_rows	= count($post_ids);
		ee()->load->library('pagination');
		$pagination = ee()->pagination->create();
		$pagination->position = 'inline';
		$str = $pagination->prepare($str);

		if ($total_rows > $post_limit && $pagination->paginate === TRUE)
		{
			$pagination->build($total_rows, $post_limit);

			// Slice our array so we can limit the query properly
			$post_ids = array_slice($post_ids, $this->current_page, $post_limit);
		}

		// Fetch the posts and topic title
		$query = ee()->db->select('title')
			->where('topic_id', $topic_id)
			->get('forum_topics');

		if ($query->num_rows() == 0)
		{
			return ee()->output->show_user_error(
				'off',
				array(lang('search_no_result')),
				lang('search_result_heading')
			);
		}

		$topic_title = $query->row('title') ;

		$qry = ee()->db->select('p.forum_id, p.topic_id, p.post_id,
				p.author_id, p.body, p.post_date, m.screen_name AS author')
			->from('forum_posts p')
			->join('members m', 'p.author_id = m.member_id')
			->where('p.topic_id', $topic_id)
			->where_in('p.post_id', array_unique($post_ids))
			->order_by('post_date', 'DESC')
			->get();

		// No results?  Something has gone terribly wrong!!
		if ($qry->num_rows() == 0)
		{
			return ee()->output->show_user_error(
				'off',
				array(lang('search_no_result')),
				lang('search_result_heading')
			);
		}

		// Fetch the "row" template
		$template = $this->load_element('thread_result_rows');

		// Fetch the topic markers
		$markers = $this->_fetch_topic_markers();

		// Parse the results
		ee()->load->library('typography');
		ee()->typography->initialize();

		$topics = '';
		$count = 0;

		if (preg_match("/".LD."switch\s*=\s*(\042|\047)([^\\1]*?)\\1".RD."/si", $template, $smatch))
		{
			$switches = explode('|', $smatch['2']);
		}

		ee()->load->helper('date');

		foreach ($qry->result_array() as $row)
		{
			$temp = $template;
			$count++;

			// Assign the post marker (folder image)
			$topic_type = '';

			$topic_marker = $markers['new'];
			$temp = $this->allow_if('new_topic', $temp);

			// Replace {switch="foo|bar|..."}
			if ( ! empty($switches))
			{
				$switch = $switches[($count + count($switches) - 1) % count($switches)];
				$temp = str_replace($smatch['0'], $switch, $temp);
			}

			// Swap out the template variables
			$temp = $this->deny_if('new_topic', $temp);

			// get just a few characters from the reply, and strip [quote]s to help prevent redundancy

			$snippet = strip_tags($row['body']);

			if (preg_match('/\[quote.*?\](.*?)\[\/quote\]/si', $snippet, $match))
			{
				// Match the entirety of the quote block
				if (stristr($match['1'], '[quote'))
				{
					$match[0] = ee('Variables/Parser')->getFullTag($snippet, $match[0], '[quote', '[/quote]');
				}

				$snippet = str_replace($match['0'], '', $snippet);
			}

			$snippet = substr($snippet, 0, 30);

			$temp = $this->var_swap(
				$temp,
				array(
					'topic_marker'			=>	$topic_marker,
					'topic_type'			=>  $topic_type,
					'author'				=>	$row['author'],
					'snippet'				=>  ee()->functions->encode_ee_tags($snippet, TRUE),
					'path:member_profile'	=>	$this->profile_path($row['author_id']),
					'path:viewreply'		=>	$this->forum_path('/viewreply/'.$row['post_id'].'/')
				)
			);

			$temp = ee()->TMPL->parse_date_variables($temp, array('post_date' => $row['post_date']));

			// Complile the string

			$topics .= $temp;
		}

		$str = $pagination->render($str);
		$str = str_replace('{include:thread_result_rows}', $topics, $str);

		$topic_title = $this->_convert_special_chars($topic_title);

		if (bool_config_item('enable_censoring'))
		{
			$topic_title = ee('Format')->make('Text', $topic_title)->censor();
		}

		// Parse the template
		return $this->var_swap(
			$this->load_element('search_thread_page'),
			array(
				'include:thread_search_results'	=> $str,
				'keywords'					=> $keywords,
				'total_results'				=> $total_rows,
				'topic_title'				=> $topic_title
			)
		);
	}

	/**
	 * Most Recent Topics
	 */
	public function most_recent_topics()
	{
		$qry = ee()->db->select('t.title, t.body, t.topic_id, t.thread_total,
									  t.thread_views, t.author_id,
									  t.last_post_author_id, t.forum_id,
									  f.forum_status, f.forum_permissions,
									  f.forum_name')
							->from('forum_topics t')
							->join('forums f', 't.forum_id = f.forum_id', 'left')
							->where('t.board_id', $this->fetch_pref('board_id'))
							->order_by('topic_date', 'DESC')
							->limit(30)
							->get();

		if ($qry->num_rows() == 0)
		{
			return '';
		}

		$ids = array();

		foreach ($qry->result_array() as $i => $row)
		{
			$member_ids[] = $row['author_id'];
			$member_ids[] = $row['last_post_author_id'];

			if ($i > 12) continue;

			$ids[] = $row['topic_id'];
		}

		$m_query = ee()->db->select('screen_name, member_id')
								->where_in('member_id', $member_ids)
								->get('members');

		foreach($m_query->result_array() as $row)
		{
			$member_name[$row['member_id']] = $row['screen_name'];
		}


		ee()->load->library('typography');
		ee()->typography->initialize();

		$template = $this->load_element('most_recent_topics');

		// Excerpt Variable Present?
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

			$results = ee()->db->select('body, topic_id')
									->where_in('topic_id', $ids)
									->order_by('post_date', 'DESC')
									->limit(12)
									->get('forum_posts');

			foreach($results->result_array() as $row)
			{
				$excerpts[$row['topic_id']] = preg_replace("/\[.*?\]|\<.*?\>|\s{2}/s", '', ee()->functions->word_limiter($row['body'], $excerpt_limit));
			}
		}

		$str = '';
		$i = 0;

		foreach ($qry->result_array() as $row)
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

			$title = $this->_convert_special_chars($row['title']);

			if (bool_config_item('enable_censoring'))
			{
				$title = ee('Format')->make('Text', $title)->censor();
			}

			$temp = str_replace('{title}', $title, $temp);
			$temp = str_replace('{replies}', $row['thread_total']-1, $temp);
			$temp = str_replace('{views}', $row['thread_views'], $temp);
			$temp = str_replace('{author}', $this->_convert_special_chars($member_name[$row['author_id']]), $temp);
			$temp = str_replace('{path:member_profile}',  $this->profile_path($row['author_id']), $temp);
			$temp = str_replace('{path:view_thread}', $this->forum_path('/viewthread/'.$row['topic_id'].'/'), $temp);

			$temp = str_replace('{forum_name}', $row['forum_name'], $temp);
			$temp = str_replace('{path:viewforum}', $this->forum_path('viewforum/'.$row['forum_id']), $temp);
			$temp = str_replace('{path:last_poster_profile}',  $this->profile_path($row['last_post_author_id']), $temp);
			$temp = str_replace('{last_poster}', $this->_convert_special_chars($member_name[$row['last_post_author_id']]), $temp);

			if (isset($excerpt_match))
			{
				if ( ! isset($excerpts[$row['topic_id']]))
				{
					$results = ee()->db->select('body')
											->where('topic_id', $row['topic_id'])
											->order_by('post_date', 'DESC')
											->limit(1)
											->get('forum_posts');

					if ($results->num_rows() == 0)
					{
						$temp = str_replace($excerpt_match, preg_replace("/\[.*?\]|\<.*?\>|\s{2}/s", '', ee()->functions->word_limiter($row['body'], $excerpt_limit)), $temp);
					}
					else
					{
						$temp = str_replace($excerpt_match, preg_replace("/\[.*?\]|\<.*?\>|\s{2}/s", '', ee()->functions->word_limiter($results->row('body') , $excerpt_limit)), $temp);
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

	/**
	 * Most Popular Posts
	 */
	public function most_popular_posts()
	{
		$qry = ee()->db->select('t.title, t.body, t.topic_id, t.thread_total,
									  t.thread_views, t.author_id, t.last_post_author_id,
									  t.forum_id, f.forum_status, f.forum_permissions,
									  f.forum_name')
							->from('forum_topics t')
							->join('forums f', 't.forum_id = f.forum_id', 'left')
							->where('t.board_id', $this->fetch_pref('board_id'))
							->order_by('thread_total', 'DESC')
							->limit(30)
							->get();

		if ($qry->num_rows() == 0)
		{
			return '';
		}

		$ids = array();

		foreach ($qry->result_array() as $i => $row)
		{
			$member_ids[] = $row['author_id'];
			$member_ids[] = $row['last_post_author_id'];

			if ($i > 12) continue;

			$ids[] = $row['topic_id'];
		}

		$m_query = ee()->db->select('screen_name, member_id')
								->where_in('member_id', $member_ids)
								->get('members');

		foreach($m_query->result_array() as $row)
		{
			$member_name[$row['member_id']] = $row['screen_name'];
		}

		$template = $this->load_element('most_popular_posts');

		// Excerpt Variable Present?
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

			$results = ee()->db->query("SELECT body, topic_id FROM exp_forum_posts WHERE topic_id IN ('".implode("','", $ids)."') ORDER BY post_date DESC LIMIT 12");

			foreach($results->result_array() as $row)
			{
				$excerpts[$row['topic_id']] = preg_replace("/\[.*?\]|\<.*?\>|\s{2}/s", '', ee()->functions->word_limiter($row['body'], $excerpt_limit));
			}
		}

		ee()->load->library('typography');
		ee()->typography->initialize();

		$str = '';

		$i = 0;
		foreach ($qry->result_array() as $row)
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

			$title = $this->_convert_special_chars($row['title']);

			if (bool_config_item('enable_censoring'))
			{
				$title = ee('Format')->make('Text', $title)->censor();
			}

			$temp = str_replace('{title}', $title, $temp);
			$temp = str_replace('{replies}', $row['thread_total']-1, $temp);
			$temp = str_replace('{views}', $row['thread_views'], $temp);
			$temp = str_replace('{path:member_profile}',  $this->profile_path($row['author_id']), $temp);
			$temp = str_replace('{author}', $this->_convert_special_chars($member_name[$row['author_id']]), $temp);
			$temp = str_replace('{path:view_thread}', $this->forum_path('/viewthread/'.$row['topic_id'].'/'), $temp);

			$temp = str_replace('{forum_name}', $row['forum_name'], $temp);
			$temp = str_replace('{path:viewforum}', $this->forum_path('viewforum/'.$row['forum_id']), $temp);
			$temp = str_replace('{path:last_poster_profile}',  $this->profile_path($row['last_post_author_id']), $temp);
			$temp = str_replace('{last_poster}', $this->_convert_special_chars($member_name[$row['last_post_author_id']]), $temp);

			// Fetch Last Post Excerpt
			if (isset($excerpt_match))
			{
				if ( ! isset($excerpts[$row['topic_id']]))
				{
					$results = ee()->db->query("SELECT body FROM exp_forum_posts WHERE topic_id = '".$row['topic_id']."' ORDER BY post_date DESC LIMIT 1");

					if ($results->num_rows() == 0)
					{
						$temp = str_replace($excerpt_match, preg_replace("/\[.*?\]|\<.*?\>|\s{2}/s", '', ee()->functions->word_limiter($row['body'], $excerpt_limit)), $temp);
					}
					else
					{
						$temp = str_replace($excerpt_match, preg_replace("/\[.*?\]|\<.*?\>|\s{2}/s", '', ee()->functions->word_limiter($results->row('body') , $excerpt_limit)), $temp);
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

	/**
	 * Emoticons
	 */
	public function emoticon_page()
	{

		if (ee()->session->userdata('member_id') == 0)
		{
			return ee()->output->fatal_error(lang('must_be_logged_in'));
		}

		$class_path = PATH_ADDONS.'emoticon/emoticons.php';

		if ( ! is_file($class_path) OR ! @include_once($class_path))
		{
			return ee()->output->fatal_error('Unable to locate the smiley images');
		}

		if ( ! is_array($smileys))
		{
			return;
		}


		$path = ee()->config->slash_item('emoticon_url');

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
				el.focus();
				opener.document.selection.createRange().text = smiley;
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

		$this->set_page_title(lang('smileys'));
		return str_replace('{include:smileys}', $r, $this->load_element('emoticon_page'));
	}

	/**
	 * Topic Titles Tag
	 *
	 * This tag is intended to be used in a standard template
	 * so that forum topics can be shown outside the forum
	 */
	public function topic_titles()
	{
		ee()->TMPL->disable_caching = FALSE;

		$sort  = ( ! in_array(ee()->TMPL->fetch_param('sort'), array('asc', 'desc'))) ? 'asc' : ee()->TMPL->fetch_param('sort');
		$limit = ( ! is_numeric(ee()->TMPL->fetch_param('limit'))) ? '10' : ee()->TMPL->fetch_param('limit');

		ee()->db->select('forum_topics.topic_id, forum_topics.author_id, forum_topics.last_post_author_id,
								forum_topics.title, forum_topics.body, forum_topics.topic_date,
								forum_topics.last_post_date, forum_topics.last_post_id,
								forum_topics.thread_total, forum_topics.thread_views,
								forum_topics.parse_smileys, forums.forum_status,
								forums.forum_permissions, forums.forum_name, forums.forum_text_formatting,
								forums.forum_html_formatting, forums.forum_auto_link_urls,
								forums.forum_allow_img_urls, forum_boards.board_label,
								forum_boards.board_name, forum_boards.board_forum_url', FALSE);

		$join = 'LEFT JOIN '.ee()->db->dbprefix('forums').
					' ON '.ee()->db->dbprefix('forum_topics').
					'.forum_id = '.ee()->db->dbprefix('forums').'.forum_id';
		ee()->db->from('forum_topics '.$join);
		ee()->db->join('forum_boards',
			ee()->db->dbprefix('forum_topics').
						'.board_id = '.ee()->db->dbprefix('forum_boards').'.board_id ', 'left');

		if ($forum = ee()->TMPL->fetch_param('forums'))
		{
			if (substr($forum, 0, 4) == 'not ')
			{
				ee()->db->where_not_in('forum_topics.forum_id', explode('|', substr($forum, 4)), FALSE);
			}
			else
			{
				ee()->db->where_in('forum_topics.forum_id', explode('|', $forum));
			}
		}

		if ($board = ee()->TMPL->fetch_param('boards'))
		{
			if (substr($board, 0, 4) == 'not ')
			{
				ee()->db->where_not_in('forum_topics.board_id', explode('|', substr($board, 4)));
			}
			else
			{
				ee()->db->where_in('forum_topics.board_id', explode('|', $board));
			}
		}
		else
		{
			ee()->db->where('forum_topics.board_id', $this->fetch_pref('board_id'));
		}

		switch (ee()->TMPL->fetch_param('orderby'))
		{
			case 'title' 		: ee()->db->order_by('forum_topics.title', $sort);
				break;
			case 'recent_post' 	:
				ee()->db->order_by('last_post_date', $sort);
				ee()->db->order_by('topic_date', $sort);
				break;
			default				: ee()->db->order_by('forum_topics.topic_date', $sort);
				break;
		}

		ee()->db->limit($limit);

		$query = ee()->db->get();

		if ($query->num_rows() == 0)
		{
			return '';
		}

		$post_ids = array();
		$fetch_replies = (stristr(ee()->TMPL->tagdata, 'last_reply')) ? TRUE : FALSE;

		foreach ($query->result_array() as $i => $row)
		{
			$member_ids[] = $row['author_id'];
			$member_ids[] = $row['last_post_author_id'];

			if ($fetch_replies)
			{
				$post_ids[] = $row['last_post_id'];
			}
		}

		$m_query = ee()->db->select('screen_name, member_id')
								->where_in('member_id', $member_ids)
								->get('members');

		foreach($m_query->result_array() as $row)
		{
			$member_name[$row['member_id']] = $row['screen_name'];
		}

		// Fetch reply information, if necessary
		$replies = array();

		if ($fetch_replies)
		{
			$rquery = ee()->db->select('topic_id, body as last_reply, parse_smileys')
									->where_in('post_id', $post_ids)
									->get('forum_posts');


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

		// Blast through the result
		ee()->load->library('typography');
		ee()->typography->initialize();

		ee()->load->helper('date');

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

			if (bool_config_item('enable_censoring'))
			{
				$row['title'] = ee('Format')->make('Text', $row['title'])->censor();
			}

			$tagdata = ee()->TMPL->tagdata;

			// Conditionals
			$cond = $row;
			$cond['logged_in']	= (ee()->session->userdata('member_id') == 0) ? FALSE : TRUE;
			$cond['logged_out']	= (ee()->session->userdata('member_id') != 0) ? FALSE : TRUE;
			$cond['post_total']	= $row['thread_total'];  // this variable makes me want raisin bran
			$cond['views'] = $row['thread_views'];

			$tagdata = ee()->functions->prep_conditionals($tagdata, $cond);

			if (isset($replies[$row['topic_id']]))
			{
				$tagdata = ee()->functions->prep_conditionals($tagdata, $replies[$row['topic_id']]);
			}

			// Single Variables
			foreach (ee()->TMPL->var_single as $key => $val)
			{
				// parse {post_total}
				if ($key == 'post_total')
				{
					$tagdata = ee()->TMPL->swap_var_single($key, $row['thread_total'], $tagdata);
				}

				// parse {author}
				if ($key == 'author')
				{
					$tagdata = ee()->TMPL->swap_var_single($key, $member_name[$row['author_id']], $tagdata);
				}

				// parse {last_author}
				if ($key == 'last_author')
				{
					$tagdata = ee()->TMPL->swap_var_single($key, $member_name[$row['last_post_author_id']], $tagdata);
				}

				// parse {views}
				if ($key == 'views')
				{
					$tagdata = ee()->TMPL->swap_var_single($key, $row['thread_views'], $tagdata);
				}

				// parse {forum_url}
				if ($key == 'forum_url')
				{
					$tagdata = ee()->TMPL->swap_var_single($key, parse_config_variables($row['board_forum_url']), $tagdata);
				}

				// parse profile path
				if (strncmp($key, 'profile_path', 12) == 0)
				{
					$tagdata = ee()->TMPL->swap_var_single(
														$key,
														ee()->functions->create_url(ee()->functions->extract_path($key).'/'.$row['author_id'].'/'),
														$tagdata
													 );
				}

				// parse thread path
				if (strncmp($key, 'thread_path', 11) == 0)
				{
					$tagdata = ee()->TMPL->swap_var_single(
														$key,
														ee()->functions->create_url(ee()->functions->extract_path($key).'/'.$row['topic_id'].'/'),
														$tagdata
													 );
				}

				// parse auto path
				if ($key == 'auto_thread_path')
				{
					$tagdata = ee()->TMPL->swap_var_single(
														$key,
														reduce_double_slashes(parse_config_variables($row['board_forum_url']).'/viewthread/'.$row['topic_id'].'/'),
														$tagdata
													 );
				}

				// parse last author profile path
				if (strncmp($key, 'last_author_profile_path', 24) == 0)
				{
					$tagdata = ee()->TMPL->swap_var_single(
														$key,
														ee()->functions->create_url(ee()->functions->extract_path($key).'/'.$row['last_post_author_id']),
														$tagdata
													 );
				}

				$dates = array(
				  'topic_date'     => $row['topic_date'],
				  'last_post_date' => $row['last_post_date']
				);
				$tagdata = ee()->TMPL->parse_date_variables($tagdata, $dates);

				// {topic_relative_date}
				if ($key == "topic_relative_date")
				{
					$tagdata = ee()->TMPL->swap_var_single($val, timespan($row['topic_date']), $tagdata);
				}

				// {last_post_relative_date}
				if ($key == "last_post_relative_date")
				{
					$tagdata = ee()->TMPL->swap_var_single($val, timespan($row['last_post_date']), $tagdata);
				}

				// Parse {body}
				if ($key == "body")
				{
					ee()->typography->parse_smileys = ($row['parse_smileys'] == 'y') ? TRUE : FALSE;

					$content = $this->_quote_decode(ee()->typography->parse_type($row['body'],
												array(
														'text_format'	=> $formatting['text_format'],
														'html_format'	=> $formatting['html_format'],
														'auto_links'	=> $formatting['auto_links'],
														'allow_img_url' => $formatting['allow_img_url']
									 				 )
												 )
											);

					$tagdata = ee()->TMPL->swap_var_single($key, $content, $tagdata);
				}

				// {last_reply}
				if (isset($replies[$row['topic_id']]))
				{
					ee()->typography->parse_smileys = ($replies[$row['topic_id']]['parse_smileys'] == 'y') ? TRUE : FALSE;

					$content = $this->_quote_decode(ee()->typography->parse_type($replies[$row['topic_id']]['last_reply'],
														array(
																'text_format'	=> $formatting['text_format'],
																'html_format'	=> $formatting['html_format'],
																'auto_links'	=> $formatting['auto_links'],
																'allow_img_url' => $formatting['allow_img_url']
											 				 )
														 )
													);

					$tagdata = ee()->TMPL->swap_var_single('last_reply', $content, $tagdata);
				}
				else
				{
					// no replies for this topic, so wipe variable
					$tagdata = str_replace(LD.'last_reply'.RD, '', $tagdata);
				}

				// Parse 1:1 fields
				if (isset($row[$val]))
				{
					$tagdata = ee()->TMPL->swap_var_single($val, $this->_convert_special_chars($row[$val]), $tagdata);
				}

			}

			$str .= $tagdata;

		}

		return $str;
	}

	/**
	 * HTTP Authentication - Basic
	 */
	public function http_authentication_basic()
	{
		@header('WWW-Authenticate: Basic realm="'.$this->realm.'"');
		ee()->output->set_status_header(401);
		@header("Date: ".gmdate("D, d M Y H:i:s")." GMT");
		exit("HTTP/1.0 401 Unauthorized");
	}

	/**
	 * HTTP Authentication - Digest
	 */
	public function http_authentication_digest()
	{
		@header('WWW-Authenticate: Digest realm="'.$this->realm.'",gop="auth", nonce="'.uniqid('').'", opaque="'.md5($this->realm).'"');
		ee()->output->set_status_header(401);
		@header("Date: ".gmdate("D, d M Y H:i:s")." GMT");
		exit("HTTP/1.0 401 Unauthorized");
	}

	/**
	 * Check HTTP Authentication - Digest
	 */
	public function http_authentication_check_digest($allowed_groups = array())
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

		$params = ee('Variables/Parser')->parseTagParameters($_SERVER['PHP_AUTH_DIGEST']);

		extract($required);
		extract($params);

		/** ----------------------------------------
		/**  Check password lockout status
		/** ----------------------------------------*/

		if (ee()->session->check_password_lockout($username) === TRUE)
		{
			return FALSE;
		}

		/** ----------------------------------
		/**  Validate Username and Password
		/** ----------------------------------*/

		$query = ee()->db->query("SELECT password, group_id FROM exp_members WHERE username = '".ee()->db->escape_str($username)."'");

		if ($query->num_rows() == 0)
		{
			ee()->session->save_password_lockout($username);
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
			ee()->session->save_password_lockout($username);

			return FALSE;
		}
	}

	/**
	 * Check HTTP Authentication - Basic
	 */
	public function http_authentication_check_basic($allowed_groups = array())
	{
		ee()->load->library('auth');
		$auth = ee()->auth->authenticate_http_basic($allowed_groups,
														 $this->realm);

		$this->auth_attempt = TRUE;

		return $auth;
	}
}
// END CLASS

// EOF
