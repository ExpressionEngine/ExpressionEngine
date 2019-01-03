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
 * Discussion Forum Module
 */
class Forum {


	public $version				= '3.1.20';
	public $build				= '20150501';
	public $use_site_profile	= FALSE;
	public $search_limit		= 250; // Maximum number of search results (x2 since it can include this number of topics + this number of posts)
	public $return_data 		= '';
	public $body_extra			= '';
	public $theme				= '';
	public $image_url			= '';
	public $forum_trigger		= '';
	public $trigger				= '';
	public $current_page		= 0;
	public $current_id			= '';
	public $return_override		= '';
	public $seg_addition		= 0;
	public $announce_id			= '';
	public $current_request		= '';
	public $current_page_name	= '';
	public $javascript			= '';
	public $head_extra			= '';
	public $submission_error	= '';
	public $error_message		= '';
	public $mimes				= '';
	public $basepath			= '';
	public $keywords			= '';
	public $min_length			= 3;	// Minimum length of search keywords
	public $cache_expire		= 24;	// How many hours should we keep search caches?
	public $max_chars			= 6000;
	public $cur_thread_row		= 0;
	public $thread_post_total	= 0;	// Used for new entry submission to determine redirect page number
	public $trigger_error_page	= FALSE;
	public $is_table_open		= FALSE;
	public $preview_override	= FALSE;
	public $mbr_class_loaded	= FALSE;
	public $read_topics_exist	= FALSE;
	public $SPELL				= FALSE;
	public $spellcheck_enabled 	= FALSE;
	public $feeds_enabled		= NULL;
	public $feed_ids			= '';
	public $realm				= "ExpressionEngine Forums";
	public $auth_attempt		= FALSE;
	public $use_sess_id			= 0;	// Used in calls to ee()->functions->fetch_site_index() in certain URLs, like attachments
	public $forum_ids			= array();
	public $attachments			= array();
	public $forum_metadata		= array();
	public $topic_metadata		= array();
	public $post_metadata		= array();
	public $admin_members		= array();
	public $admin_groups		= array();
	public $moderators			= array();
	public $current_moderator	= array();
	public $preferences			= array();
	public $form_actions		= array();
	public $uri_segments 		= array(
			'viewcategory', 'viewpost', 'viewreply', 'viewforum', 'viewthread',
			'viewannounce', 'newtopic', 'quotetopic', 'quotereply',
			'reporttopic', 'reportreply', 'do_report', 'newreply', 'edittopic',
			'editreply', 'deletetopic', 'deletereply', 'movetopic', 'merge',
			'do_merge', 'split', 'do_split', 'movereply', 'subscribe',
			'unsubscribe', 'smileys', 'member', 'search', 'member_search',
			'new_topic_search', 'active_topic_search', 'view_pending_topics',
			'do_search', 'search_results', 'search_thread', 'ban_member',
			'do_ban_member', 'spellcheck_iframe', 'spellcheck', 'mark_all_read',
			'rss', 'atom', 'ignore_member', 'do_ignore_member'
		);

	public $include_exceptions	= array(
			'head_extra', 'spellcheck_js', 'body_extra');

	protected $forum_core; // the "core" class object. Refactor, ho!

	/**
	 * Constructor
	 */
	public function __construct()
	{
		// Load Base Forum Variables
		$this->_load_base();

		// We use this in some special URLs to determine whether the Session ID
		// needs to be used in ee()->functions->fetch_site_index() or not

		$this->use_sess_id = (ee()->config->item('website_session_type') != 'c') ? 1 : 0;

		// Is the forum enabled?
		// If not, only super admins can view it
		if ($this->preferences['board_enabled'] == 'n'
			&& ee()->session->userdata('group_id') != 1)
		{
			return $this->display_forum('offline_page');
		}

		// first part of this conditional protects when someone happens
		// to set their profile trigger word to nothing...
		if ($this->current_request != '' &&
			$this->current_request == ee()->config->item('profile_trigger'))
		{
			$this->display_forum(ee()->config->item('profile_trigger'));
		}
		else
		{
			require_once PATH_ADDONS.'forum/mod.forum_core.php';

			$this->forum_core = new Forum_Core();

			$vars = get_object_vars($this);

			foreach($vars as $key => $value)
			{
				$this->forum_core->{$key} = $value;
			}

			// Verify Permissions
			// Before serving the page we'll see if the user is authorized

			if ( ! $this->forum_core->_is_authorized())
			{
				$this->forum_core->set_page_title(lang('error'));
				$error = $this->forum_core->display_forum('error_page');

				if ($this->use_trigger() === FALSE)
				{
					$this->return_data = $this->forum_core->return_data;
				}
				else
				{
					return $error;
				}
			}

			// Display Requested Page
			// If the ACT variable is set we know that we are
			// dealing with an action request.
			// Thus, we'll supress the normal course of events.

			if ( ! ee()->input->get_post('ACT'))
			{
				$this->forum_core->display_forum();
			}

			// If Template Parser Request
			$this->return_data = $this->forum_core->return_data;
		}
	}

	/**
	 * Load Base Forum Wrapper Functions
	 */
	protected function _load_base()
	{
		// Is the member area trigger changed?
		if (ee()->config->item('profile_trigger') != 'member' &&
			in_array('member', $this->uri_segments))
		{
			unset($this->uri_segments[array_search('member', $this->uri_segments)]);
			$this->uri_segments[] = ee()->config->item('profile_trigger');
		}

		// Is this a Template Request?  If so, we need to do a check
		// to see how many segments are devoted to the template calling and
		// if it is two, then we need to modify the segments
		if ($this->use_trigger() === FALSE)
		{

			if (isset(ee()->uri->segments['1']) &&
				stristr(ee()->uri->segments['1'], "ACT=") &&
				ee()->config->item('forum_trigger') != '')
			{
				ee()->uri->segments['1'] = ee()->config->item('forum_trigger');
			}

			// We have a template or template group included,
			// since there is no match between the second segment
			// and the valid forum uri segments but there is for the
			// third segment.x
			$i = 1;

			while(TRUE)
			{
				if ($i > 8) break; // Safety

				if (isset(ee()->uri->segments[$i]) &&
				! in_array(ee()->uri->segments[$i], $this->uri_segments))
				{
					if ( ! isset(ee()->uri->segments[$i+1])
						OR (isset(ee()->uri->segments[$i+1]) &&
						! in_array(ee()->uri->segments[$i+1], $this->uri_segments)))
					{
						$this->seg_addition++;
					}

					$i++;
				}
				else
				{
					break;
				}
			}

			if ($i > 1)
			{
				$this->trigger = implode('/', array_slice(ee()->uri->segments, 0, $i-1));
			}
		}

		// Disallow Private Methods
		// Functions are called automatically based on the
		// second segment of the URL. However, we don't want
		// to allow any of the private function to be called directly.
		if (substr(ee()->uri->segment(2+$this->seg_addition), 0, 1) == '_')
		{
			exit;
		}

		// Load Base Resources
		ee()->lang->loadfile('forum');
		$this->_parse_uri();
		$this->_load_preferences();
		$this->_check_theme_path();
	}

	/**
	 * Display Forum Handler
	 *
	 * @param 	string	function to call
	 */
	public function display_forum($function = '')
	{
		// Determine the function call
		// The function is based on the 2nd segment of the URI
		if ($function == '')
		{
			if ( ! ee()->uri->segment(2+$this->seg_addition))
			{
				$function = 'forum_homepage';
			}
			else
			{
				$function = ee()->uri->segment(2+$this->seg_addition);
			}
		}

		// Remap function if needed
		// In certain cases we may want different URI function names
		// to share common methods
		$remap = array(
			ee()->config->item('profile_trigger') => '_load_member_class',
			'ban_member'                          => 'ban_member_form',
			'do_ban_member'                       => 'do_ban_member'
		);
		if (isset($remap[$function]))
		{
			$function = $remap[$function];
		}

		// The output is based on whether we are using the main template parser
		// or not. If the config.php file contains a forum "triggering" word
		// we'll send the output directly to the output class. Otherwise, the
		// output is sent to the template class like normal. The exception to
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

	public function submit_post() { return $this->forum_core->submit_post(); }
	public function delete_post() { return $this->forum_core->delete_post(); }
	public function change_status() { return $this->forum_core->change_status(); }
	public function move_topic() { return $this->forum_core->move_topic(); }
	public function move_reply() { return $this->forum_core->move_reply(); }
	public function do_merge() { return $this->forum_core->do_merge(); }
	public function do_split() { return $this->forum_core->do_split(); }
	public function do_report() { return $this->forum_core->do_report(); }

	public function delete_subscription()
	{
		return $this->forum_core->delete_subscription();
	}

	public function display_attachment()
	{
		return $this->forum_core->display_attachment();
	}

	public function topic_titles()
	{
		if ( ! is_object($this->forum_core)) return;
		return $this->forum_core->topic_titles();
	}

	/**
	 * Parse URI
	 *
	 * The forum URL will typically contain only a few different possibilities:
	 *
	 * The "forum view" will be at:
	 * index.php/forum/viewforum/3/
	 *
	 * The "category view" will be at:
	 * index.php/forum/viewcategory/23/
	 *
	 * The "thread view" will be at:
	 * index.php/forum/viewthread/345/
	 *
	 * The search page will be at:
	 * index.php/forum/search/
	 *
	 * The member profile pages will be at:
	 * index.php/forum/member/some_page/
	 *
	 * In addition, there might be a page indicator:
	 * index.php/forum/viewthread/2456/P20/
	 *
	 * The URLs aren't all that complex.  They typically will have a segment in
	 * the second position indicating a function that is called, with the data
	 * in the third position being the ID number. The ID number is
	 * context-sensitive, depending on the "view" we're looking at. For example,
	 * in this URL:  index.php/forum/viewthread/2456/ The ID represents a
	 * particular thread ID.
	 *
	 * So, the purpose of this function is simply to identify the ID number and
	 * assign it to the $this->current_id variable.  We'll also grab the page
	 * number if there happens to be one.
	 */
	protected function _parse_uri()
	{
		// If we are dealing with an action request it will
		// inadvertenly mess up our forum URL trigger so
		// we'll test for it and reassign the first segment

		if (isset(ee()->uri->segments[1]) &&
			stristr(ee()->uri->segments[1], "ACT=") &&
			ee()->config->item('forum_trigger') != '')
		{
			ee()->uri->segments['1'] = $this->forum_trigger;
		}

		if ($this->use_trigger())
		{
			// preg_quote() is not really necessary here since we currently allow only alphanumeric, _ and -, but
			// I'm adding it for future-proofing sake - D'Jones

			$this->current_id = trim_slashes(preg_replace('/^\/?'.preg_quote($this->forum_trigger, '/').'/', '', ee()->uri->uri_string));

			$this->trigger = $this->forum_trigger;
		}
		else
		{
			$uri = trim_slashes(ee()->uri->uri_string);

			$this->current_id = $uri;

			if ($this->trigger == '')
			{
				$xy = explode("/", $uri);
				$this->trigger = current($xy);
			}

			$this->current_id = trim_slashes(substr($this->current_id, strlen($this->trigger)));
		}

		if (strpos($this->current_id, '/') !== FALSE)
		{
			foreach (explode("/", $this->current_id) as $nix)
			{
				if (in_array($nix, $this->uri_segments))
				{
					$this->current_request = $nix;
					$this->current_id = str_replace($nix.'/', '', $this->current_id);
					break;
				}
			}

			if (preg_match("#/P(\d+)#", $this->current_id, $match))
			{
				$this->current_page = $match['1'];

				$this->current_id = reduce_double_slashes(str_replace($match['0'], '', $this->current_id));
			}
		}


		// This is a special case in which the ID has to be parsed

		if ($this->current_request == 'viewannounce' && strpos($this->current_id, '_') !== FALSE)
		{
			$x = explode("_", $this->current_id);

			$this->current_id	= $x['0'];
			$this->announce_id	= $x['1'];
		}

		// Another special case

		if ($this->current_request == '' AND $this->current_id == 'search')
		{
			$this->current_request	= 'search';
			$this->current_id		= '';
		}

		if ($this->current_request != 'search_results' AND $this->current_request != 'search_thread' AND ! is_numeric($this->current_id))
			$this->current_id = '';
	}

	/**
	 * Fetch the Trigger Status
	 *
	 * If TRUE we are bypassing the template engine
	 *
	 * @return boolean
	 */
	public function use_trigger()
	{
		if (ee()->config->item('forum_is_installed') == "y"
			&& ee()->config->item('forum_trigger') != ''
			&& in_array(
					ee()->uri->segment(1+$this->seg_addition),
					preg_split('/\|/', ee()->config->item('forum_trigger'), -1, PREG_SPLIT_NO_EMPTY)))
		{
			$this->forum_trigger = ee()->uri->segment(1+$this->seg_addition);
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Recursively Fetch Template Elements
	 *
	 * Note:  A "template element" refers to an HTML component used to build
	 * the forum (header, breadcrumb, footer, etc.).
	 * Each "template element" corresponds to a particular function in one
	 * of the theme files.
	 *
	 * This function allows any template element to be embedded within any other
	 * template element.
	 * Template elements can contain "include variables" which call other
	 * template elements.
	 * The include variables look like this: {include:function_name}
 	 *
	 * If an include is found, this function loads that element and recursively
	 * looks for additional includes.
	 *
	 * In some cases, template elements need to be processed rather than simply
	 * returned.
	 * If we need to process the include, THIS file will contain a function
	 * named exactly the same as the template element which will be called.  If
	 * the function does not exist we return the pure data.
	 *
	 * Right now there is no safety to prevent a run-away loop if an include is
	 * put within itself.
	 *
	 * @param 	string	function to call
	 * @return 	string
	 * @access 	private
	 */
	function _include_recursive($function)
	{
		if ($this->return_data == '' AND $this->trigger_error_page === TRUE)
		{
			$function = 'error_page';
		}

		if (method_exists($this, $function))
		{
			$element = $this->$function();
		}
		else
		{
			$element = $this->load_element($function);

			// -------------------------------------------
			// 'forum_include_extras' hook.
			//  - Add more forum theme pages and functions
			//  - Added EE 2.5.0
			//
			if (ee()->extensions->active_hook('forum_include_extras') === TRUE)
			{
				$element = ee()->extensions->call('forum_include_extras', $this, $function, $element);
			}
			//
			// -------------------------------------------

		}

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

	/**
	 * Forum CSS
	 *
	 * @return string
	 */
	public function forum_css()
	{
		$str = $this->load_element('forum_css');
		// Remove comments and spaces from CSS file
		$str = preg_replace("/\/\*.*?\*\//s", '', $str);
		$str = preg_replace("/\}\s+/s", "}\n", $str);
		return $str;
	}

	/**
	 * Load a Theme Element
	 *
	 * @param 	string	element to load
	 * @return 	mixed
	 */
	public function load_element($which)
	{
		$classname = $this->_fetch_filename($which);

		// -------------------------------------------
		// 'forum_add_template' hook.
		//  - Add more forum theme pages and functions
		//  - Added EE 2.5.0
		//
		if (ee()->extensions->active_hook('forum_add_template') === TRUE)
		{
			$classname = ee()->extensions->call('forum_add_template', $which, $classname);
		}
		//
		// -------------------------------------------

		if ( ! $classname)
		{
			$data = array(	'title' 	=> lang('error'),
							'heading'	=> lang('general_error'),
							'content'	=> lang('nonexistent_page'),
							'redirect'	=> '',
							'link'		=> array($this->forum_path(), $this->fetch_pref('board_label'))
						 );

			return ee()->output->show_message($data, 0);
		}

		$path = $this->theme.'/'.$classname.'/'.$which.'.html';
		$full_path = ee('Theme')->getPath('forum/'.$path);

		if ( ! is_file($full_path))
		{
			return ee()->output->fatal_error('Unable to locate the following forum theme file: '.$path);
		}

		if ($this->fetch_pref('board_allow_php') == 'y' AND $this->fetch_pref('board_php_stage') == 'i')
		{
			return $this->parse_template_php($this->_prep_element(
				trim(file_get_contents($full_path))
			));
		}

		return $this->_prep_element(trim(file_get_contents($full_path)));
	}

	/**
	 * Prep Element Data
	 *
	 * Right now we only use this to parse the logged-in/logged-out vars
	 *
	 * @param 	string
	 * @return 	string
	 */
	protected function _prep_element($str)
	{
		if ($str == '')
		{
			return '';
		}

		if (ee()->session->userdata('member_id') == 0)
		{
			$str = $this->deny_if('logged_in', $str);
			$str = $this->allow_if('logged_out', $str);
		}
		else
		{
			$str = $this->allow_if('logged_in', $str);
			$str = $this->deny_if('logged_out', $str);
		}

		return $str;
	}

	/**
	 * Load Forum Preference
	 *
	 * @param 	integer		board id
	 *
	 */
	protected function _load_preferences($board_id='')
	{
		if ($board_id != '')
		{
			ee()->db->where('board_id', $board_id);
		}
		elseif (ee()->input->get_post('ACT') !== FALSE &&
				ee()->input->get_post('board_id') !== FALSE)
		{
			ee()->db->where('board_id',
								 ee()->input->get_post('board_id'));
		}
		elseif ($this->use_trigger() === TRUE)
		{
			ee()->db->where('board_forum_trigger', $this->forum_trigger);
			ee()->db->where('board_site_id',
								 ee()->config->item('site_id'));
		}
		else
		{
			// Means we are in a Template
			// If no board="" parameter, then we automatically
			// use the default board_id of 1
			if (isset(ee()->TMPL) && is_object(ee()->TMPL) &&
				($board_name = ee()->TMPL->fetch_param('board')) !== FALSE)
			{
				ee()->db->where('board_name', $board_name);
			}
			else
			{
				ee()->db->where('board_id', 1);
			}
		}

		ee()->db->select('board_label, board_name, board_id,
							board_alias_id,
							board_forum_url, board_enabled, board_default_theme,
							board_forum_trigger,
							board_upload_path, board_topics_perpage,
							board_posts_perpage, board_topic_order,
							board_post_order, board_display_edit_date,
							board_hot_topic, board_max_attach_perpost,
							board_attach_types, board_max_attach_size,
							board_use_img_thumbs, board_recent_poster,
							board_recent_poster_id, board_notify_emails,
							board_notify_emails_topics, board_allow_php,
							board_php_stage');

		$query = ee()->db->get('forum_boards');

		if ($query->num_rows() == 0)
		{
			ee()->output->show_user_error('general', lang('forum_not_installed'));
		}

		if ($query->row('board_alias_id') != '0')
		{
			$this->_load_preferences($query->row('board_alias_id') );

			foreach(array(
					'board_label', 'board_name',
					'board_enabled', 'board_forum_url') as $val)
			{
				$this->preferences[$val] = $query->row($val);
			}

			$this->preferences['original_board_id'] = $query->row('board_id') ;

			return;
		}

		$this->preferences['original_board_id'] = $query->row('board_id') ;

		foreach ($query->row_array() as $key => $val)
		{
			$this->preferences[$key] = $val;
		}

		// Assign the path the member profile area
		if ($this->use_site_profile == TRUE)
		{
			$this->preferences['member_profile_path'] = ee()->functions->create_url(ee()->config->item('profile_trigger').'/');
		}
		else
		{
			$this->preferences['member_profile_path'] 	= $this->forum_path(ee()->config->item('profile_trigger').'/');
		}
	}

	/**
	 * Instantiates the Member Profile Class
	 *
	 * @return string
	 */
	protected function _load_member_class()
	{
		// This needs to be first!  Don't move it.
		$template = $this->load_element('member_page');

		$this->mbr_class_loaded = TRUE;
		include_once PATH_ADDONS.'member/mod.member.php';

		$this->MBR = new Member();

		$this->MBR->_set_properties(
				array(
						'trigger'			=> ee()->config->item('profile_trigger'),
						'theme_class'		=> 'theme_member',
						'in_forum'			=> TRUE,
						'is_admin'			=> TRUE,
						'enable_breadcrumb'	=> FALSE,
						'basepath'			=> $this->forum_path(ee()->config->item('profile_trigger')),
						'forum_path'		=> $this->forum_path(),
						'image_url'			=> $this->image_url,
						'theme_path'		=> ee('Theme')->getPath('forum/'.$this->theme.'/forum_member/'),
						'css_file_path'		=> '',
						'board_id'			=> $this->fetch_pref('board_id')
					)
			);

		$template = str_replace('{include:member_manager}', $this->MBR->manager(), $template);

		$this->head_extra = $this->MBR->head_extra;

		if ($this->MBR->show_headings == TRUE)
		{
			$template = $this->allow_if('show_headings', $template);
		}
		else
		{
			$template = $this->deny_if('show_headings', $template);
		}

		return $template;
	}

	/**
	 * Fetch Preference item
	 *
	 * @param 	string
	 * @return 	string
	 */
	public function fetch_pref($which)
	{
		return ( ! isset($this->preferences[$which])) ? '' : parse_config_variables($this->preferences[$which]);
	}

	/**
	 * Check the theme folder path
	 *
	 * @return mixed
	 */
	protected function _check_theme_path()
	{
		// Grab theme.  Can be from a cookie or user pref
		$forum_theme = (ee()->session->userdata('member_id') != 0) ? ee()->session->userdata('forum_theme') : '';

		// or overridden with a tag param in the template
		if (isset(ee()->TMPL) && is_object(ee()->TMPL))
		{
			$forum_theme = (ee()->TMPL->fetch_param('theme')) ?: $forum_theme;
		}

		// Maybe the theme is in a cookie?
		if ($forum_theme == '')
		{
			if (ee()->input->cookie('forum_theme') != FALSE)
			{
				$forum_theme = ee()->input->cookie('forum_theme');

				// Security checks.  Only alpha-numeric text
				if ( ! preg_match("/^[a-z0-9\s_-]+$/i", $forum_theme))
				{
					$forum_theme = '';
				}

				// If the user is logged in we'll update their forum selection
				if ($forum_theme != '' &&
					ee()->session->userdata('member_id') != 0)
				{
					ee()->db->where('member_id',
								ee()->session->userdata('member_id'));
					ee()->db->update('members', array(
											'forum_theme' => $forum_theme));
				}
			}
		}

		// Check path to folder containing the requested theme
		$this->theme = ($forum_theme != '' &&
		ee('Filesystem')->exists(ee('Theme')->getPath('forum/'.$forum_theme))) ? $forum_theme : $this->fetch_pref('board_default_theme');

		if ( ! ee('Filesystem')->exists(ee('Theme')->getPath('forum/'.$this->theme)))
		{
			return ee()->output->fatal_error('Unable to locate the forum theme folder.');
		}

		// Set path to the image folder for the particular theme
		$this->image_url = ee('Theme')->getUrl('forum/'.$this->theme.'/images/');
	}

	/**
	 * Build Form Declaration
	 *
	 * @param mixed
	 */
	protected function _form_declaration($form = '')
	{
		list($class, $method) = explode(':', $form);

		$hidden = array(
			'ACT'	=> ee()->functions->fetch_action_id($class, $method),
			'FROM'	=> 'forum',
			'mbase'	=> $this->forum_path(ee()->config->item('profile_trigger')),
			'board_id' => $this->fetch_pref('original_board_id')
		);

		if (isset($this->form_actions[$form]))
		{
			foreach ($this->form_actions[$form] as $key => $val)
			{
				$hidden[$key] = $val;
			}
		}

		// special handling for mini login forms on member profile pages
		if ($this->current_request == ee()->config->item('profile_trigger') && $this->current_id == '')
		{
			$hidden['RET'] = $this->forum_path();
		}

		if ( ! isset($hidden['RET']))
		{
			if ($this->return_override != '')
			{
				$hidden['RET'] = reduce_double_slashes($this->forum_path($this->current_request.'/'.$this->return_override));
			}
			else
			{
				$hidden['RET'] = reduce_double_slashes($this->forum_path($this->current_request.'/'.$this->current_id));
			}
		}

		// If the post submission form is the one being viewed we
		// will use the current URL as the form action, rather than the
		// normal site index.  That way we can show previews

		$action = '';
		if ($method == 'submit_post')
		{
			// If we are using the "fast reply" form we set the path manually
			if ($this->current_request == 'viewthread')
			{
				$action = $this->forum_path('/newreply/'.$this->current_id.'/');
			}
			elseif ($this->current_request == 'quotereply')
			{
				$action = $this->forum_path('/newreply/'.$this->current_id.'/');
			}
			else
			{
				$action = $this->forum_path('/'.$this->current_request.'/'.$this->current_id.'/');
			}
		}
		elseif($method == 'do_split')
		{
			$action = reduce_double_slashes($this->forum_path($this->current_request.'/'.$hidden['topic_id']));
			//print_r(get_object_vars($this));
		}

		return ee()->functions->form_declaration(array(
				'hidden_fields'	=> $hidden,
				'action'		=> $action,
				'name'			=> ($method == 'submit_post') ? $method : '',
				'id'			=> ($method == 'submit_post') ? $method : '',
				'enctype'		=> ($method == 'submit_post' AND $this->current_request != 'viewthread') ? 'multi' : ''
			)
		);
	}

	/**
	 * Build Profile Path with member ID
	 *
	 * @param 	int	id
	 * @return 	string
	 */
	public function profile_path($id)
	{
		return $this->fetch_pref('member_profile_path').$id.'/';
	}

	/**
	 * Build Search Path with sting
	 *
	 * We need to assign an action to this...
	 */
	public function _search_path($id)
	{
		return $this->forum_path('/search/');
	}

	/**
	 * Sets the forum basepath
	 */
	protected function _forum_set_basepath()
	{
		/* -------------------------------------------
		/*	Hidden Configuration Variable
		/*	- use_forum_url => Does the user runs their forum at a different base URL then their main site? (y/n)
		/* -------------------------------------------*/
		if (ee()->config->item('use_forum_url') == 'y')
		{
			$this->basepath = $this->fetch_pref('board_forum_url');
			return;
		}

		// The only reason we set this is so that the session ID gets added to the URL
		// if the user is running their site in session only mode
		ee()->functions->template_type = 'webpage';

		$trigger = (isset($_GET['trigger'])) ? $_GET['trigger'] : $this->trigger;
		$this->basepath = ee()->functions->create_url($trigger).'/';
	}

	/**
	 * Compiles a path string
	 */
	public function forum_path($uri = '')
	{
		if ($this->basepath == '')
		{
			$this->_forum_set_basepath();
		}

		return reduce_double_slashes($this->basepath.$uri.'/');
	}

	/**
	 * Replace variables
	 */
	public function var_swap($str, $data)
	{
		if ( ! is_array($data))
		{
			return FALSE;
		}

		foreach ($data as $key => $val)
		{
			$str = str_replace('{'.$key.'}', $val, $str);
		}

		return $str;
	}

	/**
	 * Helpers for "if" conditions
	 */
	public function deny_if($cond, $str, $replace = '')
	{
		return str_replace("{if {$cond}}", "{if FALSE}", $str);
	}

	public function allow_if($cond, $str)
	{
		return str_replace("{if {$cond}}", "{if TRUE}", $str);
	}

	/**
	 * Convert special characters
	 */
	protected function _convert_special_chars($str, $convert_amp = FALSE)
	{
		// If we convert &'s for strings that have typography performed on them,
		// then they will be double-converted
		if ($convert_amp === TRUE)
		{
			$str = str_replace('&', '&amp;', $str);
		}

		return str_replace(
				array('<', '>', '{', '}', '\'', '"', '?'),
				array('&lt;', '&gt;', '&#123;', '&#125;', '&#146;', '&quot;', '&#63;'),
				$str);
	}

	/**
	 * Convert forum tags
	 */
	public function convert_forum_tags($str)
	{
		$str = str_replace('{include:', '&#123;include:', $str);
		$str = str_replace('{path:', '&#123;path:', $str);
		$str = str_replace('{lang:', '&#123;lang:', $str);

		return $str;
	}

	/**
	 * Final Template Parsing
	 */
	function _final_prep($str)
	{
		// Is the user an admin?
		if ( ! $this->_is_admin())
		{
			$str = $this->deny_if('is_admin', $str);
		}
		else
		{
			$str = $this->allow_if('is_admin', $str);
		}

		if ($this->mbr_class_loaded == TRUE)
		{
			$str = $this->deny_if('in_forum', $str);
		}
		else
		{
			$str = $this->allow_if('in_forum', $str);
		}

		// Parse the language text
		if (preg_match_all("/{lang:(.+?)\}/i", $str, $matches))
		{
			for ($j = 0; $j < count($matches['0']); $j++)
			{
				$line = lang($matches['1'][$j]);

				// Since we're using the pre-existing search language file
				// we might need to add a prefix
				if ($line == '' AND $this->current_request == 'search')
				{
					$line = lang('search_'.$matches['1'][$j]);
				}

				$str = str_replace($matches['0'][$j], $line, $str);
			}
		}

		// Parse form declarations
		if (preg_match_all("/{form_declaration:(.+?)\}/i", $str, $matches))
		{
			for ($j = 0; $j < count($matches['0']); $j++)
			{
				$str = str_replace($matches['0'][$j], $this->_form_declaration($matches['1'][$j]), $str);
			}
		}

		// Parse the last visit date
		if (preg_match_all("/".LD."last_visit_date(.*?)".RD."/i", $str, $matches))
		{
			// One-off: show text instead of a date in case of guests
			if (ee()->session->userdata('member_id') == 0)
			{
				for ($j = 0; $j < count($matches['0']); $j++)
				{
					$str = str_replace($matches['0'][$j], lang('never'), $str);
				}
			}
			else
			{
				$str = ee()->TMPL->parse_date_variables($str, array('last_visit_date' => ee()->session->userdata('last_visit')));
			}
		}

		// If the member class is loaded we'll set the page title based on its page title
		if ($this->mbr_class_loaded == TRUE AND $this->current_page_name == '')
		{
			$this->current_page_name = $this->MBR->page_title;
		}

		if (is_null($this->feeds_enabled) OR $this->feeds_enabled === FALSE)
		{
			$str = $this->deny_if('feeds_enabled', $str);
		}
		else
		{
			$str = $this->allow_if('feeds_enabled', $str);
		}

		// Parse {current_time}
		$str = ee()->TMPL->parse_date_variables($str, array('current_time' => ee()->localize->now));

		if ( ! class_exists('EE_Messages'))
		{
			require APPPATH.'libraries/Messages.php';
		}

		// Parse the forum segments and board prefs
		$conds = array(
			'current_request'	=> $this->current_request,
			'current_id'		=> $this->current_id,
			'current_page'		=> $this->current_page,
			'pm_enabled'		=> EE_Messages::can_send_pm(),
			'logged_in'			=> ee()->session->userdata('member_id') != 0,
			'logged_out'		=> ee()->session->userdata('member_id') == 0
		);

		// parse certain board preferences as well
		foreach (array('original_board_id', 'board_label', 'board_name', 'board_id', 'board_alias_id') as $pref)
		{
			$conds[$pref] = $this->fetch_pref($pref);
		}

		$str = $this->var_swap($str, $conds);

		$str = $this->var_swap(
			$str,
			array(
				'lang'                     => ee()->config->item('xml_lang'),
				'charset'                  => ee()->config->item('output_charset'),
				'include:head_extra'       => $this->head_extra,
				'include:body_extra'       => $this->body_extra,
				'include:spellcheck_js'    => $this->spellcheck_js(),
				'path:spellcheck_iframe'   => $this->forum_path('/spellcheck_iframe/'),
				'screen_name'              => $this->_convert_special_chars(ee()->session->userdata('screen_name')),
				'path:logout'              => ee()->functions->create_url('logout').'&amp;FROM=forum&amp;board_id='.$this->fetch_pref('original_board_id'),
				'path:image_url'           => $this->image_url,
				'path:forum_home'          => $this->forum_path(),
				'path:your_control_panel'  => $this->profile_path('profile'),
				'path:your_profile'        => $this->profile_path(ee()->session->userdata('member_id')),
				'path:login'               => $this->profile_path('login'),
				'path:register'            => $this->profile_path('register'),
				'path:memberlist'          => $this->profile_path('memberlist'),
				'path:forgot'              => $this->profile_path('forgot_password'),
				'path:private_messages'    => $this->profile_path('messages/view_folder/1'),
				'path:recent_poster'       => $this->profile_path($this->fetch_pref('board_recent_poster_id')),
				'path:advanced_search'     => $this->forum_path('/search/'),
				'path:view_new_topics'     => $this->forum_path('/new_topic_search'),
				'path:view_active_topics'  => $this->forum_path('/active_topic_search'),
				'path:view_pending_topics' => $this->forum_path('/view_pending_topics'),
				'path:mark_all_read'       => rtrim($this->forum_path('/mark_all_read/' . CSRF_TOKEN), '/'),
				'path:do_search'           => $this->forum_path('/do_search/'),
				'path:smileys'             => $this->forum_path('/smileys/'),
				'path:rss'                 => $this->forum_path('/rss/'.$this->feed_ids),
				'path:atom'                => $this->forum_path('/atom/'.$this->feed_ids),
				'path:set_theme'           => ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.ee()->functions->fetch_action_id('Forum', 'set_theme').'&board_id='.$this->fetch_pref('original_board_id'),
				'recent_poster'            => $this->fetch_pref('board_recent_poster'),
				'forum_name'               => $this->_convert_special_chars($this->fetch_pref('board_label'), TRUE),
				'forum_url'                => $this->fetch_pref('board_forum_url'),
				'page_title'               => $this->_convert_special_chars($this->current_page_name, TRUE),
				'module_version'           => $this->version,
				'forum_build'              => $this->build,
				'error_message'            => $this->error_message,
				'path:theme_css'           => '',
				'path:theme_js'            => ee('Theme')->getUrl('forum/'.$this->theme.'/theme/javascript/'),
				'site_url'                 => ee()->config->item('site_url'),
				'username_max_length'      => USERNAME_MAX_LENGTH,
				'password_max_length'      => PASSWORD_MAX_LENGTH
			)
		);

		// Evaluate the conditionals
		$str = ee()->functions->prep_conditionals($str, $conds);

		if ($this->fetch_pref('board_allow_php') == 'y' AND $this->fetch_pref('board_php_stage') == 'o')
		{
			return $this->parse_template_php($str);
		}

		if ( ! is_object(ee()->TMPL))
		{
			// cleanup unparsed conditionals and annotations
			$str = preg_replace("/".LD."if\s+.*?".RD.".*?".LD.'\/if'.RD."/s", "", $str);
			$str = preg_replace("/\{!--.*?--\}/s", '', $str);
		}

		return $str;
	}

	/**
	 * Fetch classname
	 *
	 * Given an element (function) name, this function
	 * returns the name of the subfolder folder that contains the
	 * corresponding template file.
	 */
	protected function _fetch_filename($index)
	{
		$matrix = array(
				'forum_css'						=> 'forum_css',
			// --------------------------------------------------------
				'html_header'					=> 'forum_global',
				'meta_tags'						=> 'forum_global',
				'html_footer'					=> 'forum_global',
				'top_bar'						=> 'forum_global',
				'top_bar_spacer'				=> 'forum_global',
				'page_header'					=> 'forum_global',
				'page_header_simple'			=> 'forum_global',
				'page_subheader'				=> 'forum_global',
				'page_subheader_simple'			=> 'forum_global',
				'private_message_box'			=> 'forum_global',
			// --------------------------------------------------------
				'javascript'					=> 'forum_javascript',
				'javascript_show_hide_forums'	=> 'forum_javascript',
				'javascript_forum_array'		=> 'forum_javascript',
				'javascript_set_show_hide'		=> 'forum_javascript',
			// --------------------------------------------------------
				'breadcrumb'					=> 'forum_breadcrumb',
				'breadcrumb_trail'				=> 'forum_breadcrumb',
				'breadcrumb_current_page'		=> 'forum_breadcrumb',
			// --------------------------------------------------------
				'offline_page'					=> 'forum_offline',
			// --------------------------------------------------------
				'forum_homepage'				=> 'forum_index',
				'main_forum_list'				=> 'forum_index',
				'forum_table_heading'			=> 'forum_index',
				'forum_table_rows'				=> 'forum_index',
				'forum_table_footer'			=> 'forum_index',
			// --------------------------------------------------------
				'category_page'					=> 'forum_category',
			// --------------------------------------------------------
				'announcement_page'				=> 'forum_announcements',
				'announcement_topics'			=> 'forum_announcements',
				'announcement_topic_rows'		=> 'forum_announcements',
				'announcement'					=> 'forum_announcements',
			// --------------------------------------------------------
				'topic_page'					=> 'forum_topics',
				'topics'						=> 'forum_topics',
				'topic_rows'					=> 'forum_topics',
				'topic_no_results'				=> 'forum_topics',
			// --------------------------------------------------------
				'thread_page'					=> 'forum_threads',
				'threads'						=> 'forum_threads',
				'thread_rows'					=> 'forum_threads',
				'thread_review'					=> 'forum_threads',
				'thread_review_rows'			=> 'forum_threads',
				'post_attachments'				=> 'forum_threads',
				'thumb_attachments'				=> 'forum_threads',
				'image_attachments'				=> 'forum_threads',
				'file_attachments'				=> 'forum_threads',
				'signature'						=> 'forum_threads',
				'quoted_author'					=> 'forum_threads',
			// --------------------------------------------------------
				'submission_page'				=> 'forum_submission',
				'submission_errors'				=> 'forum_submission',
				'submission_form'				=> 'forum_submission',
				'preview_post'					=> 'forum_submission',
				'form_attachments'				=> 'forum_submission',
				'form_attachment_rows'			=> 'forum_submission',
				'poll_answer_field'				=> 'forum_submission',
				'poll_vote_count_field'			=> 'forum_submission',
				'fast_reply_form'				=> 'forum_submission',
			// --------------------------------------------------------
				'poll_questions'				=> 'forum_poll',
				'poll_question_rows'			=> 'forum_poll',
				'poll_answers'					=> 'forum_poll',
				'poll_answer_rows'				=> 'forum_poll',
				'poll_graph_left'				=> 'forum_poll',
				'poll_graph_middle'				=> 'forum_poll',
				'poll_graph_right'				=> 'forum_poll',
			// --------------------------------------------------------
				'visitor_stats'					=> 'forum_stats',
			// --------------------------------------------------------
				'forum_legend'					=> 'forum_legends',
				'topic_legend'					=> 'forum_legends',
			// --------------------------------------------------------
				'recent_posts'					=> 'forum_archives',
				'most_recent_topics'			=> 'forum_archives',
				'most_popular_posts'			=> 'forum_archives',
			// --------------------------------------------------------
				'member_page'					=> 'forum_member',
			// --------------------------------------------------------
				'user_banning_page'				=> 'forum_user_banning',
				'user_banning_warning'			=> 'forum_user_banning',
				'user_banning_report'			=> 'forum_user_banning',
			// --------------------------------------------------------
				'advanced_search_page'			=> 'forum_search',
				'quick_search_form'				=> 'forum_search',
				'advanced_search_form'			=> 'forum_search',
				'search_results_page'			=> 'forum_search',
				'search_thread_page'			=> 'forum_search',
				'search_results'				=> 'forum_search',
				'thread_search_results'			=> 'forum_search',
				'forum_quick_search_form'		=> 'forum_search',
				'reply_results'					=> 'forum_search',
				'result_rows'					=> 'forum_search',
				'thread_result_rows'			=> 'forum_search',
				'no_search_result'				=> 'forum_search',
			// --------------------------------------------------------
				'login_required_page'			=> 'forum_login',
				'login_form'					=> 'forum_login',
				'login_form_mini'				=> 'forum_login',
			// --------------------------------------------------------
				'move_topic_page'				=> 'forum_move_topic',
				'move_topic_confirmation'		=> 'forum_move_topic',
			// --------------------------------------------------------
				'move_reply_page'				=> 'forum_move_reply',
				'move_reply_confirmation'		=> 'forum_move_reply',
			// --------------------------------------------------------
				'merge_page'					=> 'forum_merge',
				'merge_interface'				=> 'forum_merge',
			// --------------------------------------------------------
				'split_page'					=> 'forum_split',
				'split_data'					=> 'forum_split',
				'split_thread_rows'				=> 'forum_split',
			// --------------------------------------------------------
				'report_page'					=> 'forum_report',
				'report_form'					=> 'forum_report',
			// --------------------------------------------------------
				'ignore_member_page'			=> 'forum_ignore',
				'ignore_member_confirmation'	=> 'forum_ignore',
			// --------------------------------------------------------
				'delete_post_page'				=> 'forum_delete_post',
				'delete_post_warning'			=> 'forum_delete_post',
			// --------------------------------------------------------
				'emoticon_page'					=> 'forum_emoticons',
			// --------------------------------------------------------
				'error_page'					=> 'forum_error',
				'error_message'					=> 'forum_error',
			// --------------------------------------------------------
				'atom_page'						=> 'forum_atom',
				'rss_page'						=> 'forum_rss'
			);

		return ( ! isset($matrix[$index])) ? FALSE : $matrix[$index];
	}

	/**
	 * Is the user authorized for the specfic page?
	 * @return TRUE
	 */
	protected function _is_authorized()
	{
		return TRUE;
	}

	/**
	 * Trigger Error
	 *
	 * @param 	string	error language key
	 * @return 	void
	 */
	public function trigger_error($msg = 'not_authorized')
	{
		$this->return_data = '';
		$this->error_message = lang($msg);
		$this->set_page_title(lang('error'));
		return $this->display_forum('error_page');
	}

	/**
	 * Trigger the log-in page
	 *
	 * This function sets a couple variables which the
	 * $this->_include_recursive() looks for to determine
	 * whether the error page should be shown.
	 */
	protected function _trigger_login_page()
	{
		$this->return_data = '';
		$this->trigger_login_page = TRUE;
		return FALSE;
	}

	/**
	 * Is a particular user an admin?
	 *
	 * @param 	int		member id
	 * @param 	int		member group id
	 * @return 	boolean
	 */
	protected function _is_admin($member_id = 0, $group_id = 0)
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

		// If we know the member ID but not the group
		// we need to look it up

		if ($member_id != 0 AND $group_id == 0)
		{
			ee()->db->select('group_id');
			$query = ee()->db->get_where('members', array(
												'member_id' => $member_id));

			if ($query->num_rows() == 0)
			{
				return FALSE;
			}

			if ($query->row('group_id') == 1)
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Individual Member's Last Visit
	 */
	public function member_post_total()
	{
		return str_replace('%x',
						ee()->session->userdata('total_forum_posts'),
						lang('your_post_total'));
	}

	/**
	 * Quick Search Form
	 */
	public function quick_search_form()
	{
		$form = ee()->functions->form_declaration(array(
					'action' => $this->forum_path('do_search'),
					'hidden_fields' => array('board_id' => $this->fetch_pref('original_board_id'))
				)
			);

		return $this->var_swap($this->load_element('quick_search_form'),
							array(
									'form_declaration' => $form,
									'forum_id' => $this->current_id
								)
							);
	}

	/**
	 * Quick Search Form - restricts to current forum
	 */
	public function forum_quick_search_form()
	{
		$form = ee()->functions->form_declaration(array(
					'action' => $this->forum_path('do_search'),
					'hidden_fields' => array('board_id' => $this->fetch_pref('original_board_id'))
				)
			);

		return $this->var_swap($this->load_element('forum_quick_search_form'),
							array(
									'form_declaration'	=> $form,
									'forum_id' => $this->current_id
								)
							);
	}

	/**
	 * Page subheader
	 */
	public function page_subheader()
	{
		$template = $this->load_element('page_subheader');

		if ($this->current_request == 'search')
		{
			$template = $this->deny_if('not_search_page', $template);
		}
		else
		{
			$template = $this->allow_if('not_search_page', $template);
		}

		return $template;
	}

	/**
	 * Finalize the Crumbs
	 *
	 * @param 	string		page title
	 * @param 	string
	 * @param
	 */
	protected function _build_crumbs($title, $crumbs, $str)
	{
		$this->set_page_title(($title == '') ? lang('home') : $title);

		$crumbs .= str_replace('{crumb_title}', $this->_convert_special_chars($str, TRUE), $this->load_element('breadcrumb_current_page'));

		return str_replace('{breadcrumb_links}', $crumbs, $this->load_element('breadcrumb'));
	}

	/**
	 * Breadcrumb
	 */
	public function breadcrumb()
	{
		// Do we even need any crumbs?
		// If there are no URI segments we'll show the home page text
		if (count(ee()->uri->segments) <= 1 + $this->seg_addition)
		{
			return $this->_build_crumbs('', '', lang('home'));
		}

		// Define the first crumb (forum homepage link)
		$crumbs = $this->_crumb_trail(
							array(
									'link'	=> $this->forum_path('/'),
									'title'	=> lang('home')
								 )
						);


		$request = ee()->uri->segment(2+$this->seg_addition);

		// Is this the search page?
		if ($request == 'search')
		{
			if ($this->current_id == '')
			{
				return $this->_build_crumbs(lang('search'), $crumbs, lang('advanced_search'));
			}
		}

		// Is this a Search Results page?
		if ($request == 'search_results' OR $request == 'search_thread')
		{
				$crumbs .= $this->_crumb_trail(array(
													'link' => $this->forum_path('/search'),
													'title' => lang('advanced_search')
													)
												);

			return $this->_build_crumbs('', $crumbs, lang('search_results'));
		}

		// Is this the member banning page?
		if ($request == 'ban_member' OR $request == 'do_ban_member')
		{
			return $this->_build_crumbs(lang('ban_member'), $crumbs, lang('ban_member'));
		}

		// Is this an ignore member page?
		if ($request == 'ignore_member' OR $request == 'do_ignore_member')
		{
			return $this->_build_crumbs(lang('ignore_member'), $crumbs, lang('ignore_member'));
		}

		// Are we showing the member profile pages?
		if ($request == ee()->config->item('profile_trigger'))
		{
			if (ee()->uri->segment(3+$this->seg_addition) == '')
			{
				return $this->_build_crumbs(lang('member_profile'), $crumbs, lang('member_profile'));
			}

			if (is_numeric(ee()->uri->segment(3+$this->seg_addition)))
			{
				ee()->db->select('screen_name');
				$query = ee()->db->get_where('members',
										array('member_id' => ee()->uri->segment(3+$this->seg_addition))
									);

				$crumbs .= $this->_crumb_trail(array(
													'link' => $this->forum_path('/'.ee()->config->item('profile_trigger').'/memberlist'),
													'title' => lang('memberlist')
													)
												);

				return $this->_build_crumbs($this->_convert_special_chars($query->row('screen_name') ), $crumbs, $this->_convert_special_chars($query->row('screen_name') ));
			}

			if (ee()->uri->segment(3+$this->seg_addition) == 'memberlist')
			{
				return $this->_build_crumbs(lang('mbr_memberlist'), $crumbs, lang('mbr_memberlist'));
			}
			elseif (ee()->uri->segment(3+$this->seg_addition) == 'member_search')
			{
				return $this->_build_crumbs(lang('member_search'), $crumbs, lang('member_search'));
			}

			if (ee()->uri->segment(3+$this->seg_addition) != 'profile')
			{
				$crumbs .= $this->_crumb_trail(array(
													'link' => $this->forum_path('/'.ee()->config->item('profile_trigger').'/profile'),
													'title' => lang('control_panel_home')
													)
												);
			}

			if (FALSE !== ($mbr_crumb = $this->MBR->_fetch_member_crumb(ee()->uri->segment(3+$this->seg_addition))))
			{
				return $this->_build_crumbs(lang($mbr_crumb), $crumbs, lang($mbr_crumb));
			}


			if (ee()->uri->segment(3+$this->seg_addition) == 'messages')
			{
				if (FALSE !== ($mbr_crumb = $this->MBR->_fetch_member_crumb(ee()->uri->segment(4+$this->seg_addition))))
				{
					return $this->_build_crumbs(lang($mbr_crumb), $crumbs, lang($mbr_crumb));
				}
			}
		}

		// No ID?  We're done...
		if ($this->current_id == '' OR ! is_numeric($this->current_id))
		{
			return $this->_build_crumbs('', $crumbs, lang('error'));
		}

		// Is this a category view?
		if ($request == 'viewcategory')
		{
			if (FALSE !== ($meta = $this->_fetch_forum_metadata($this->current_id)))
			{
				return $this->_build_crumbs(
												$meta[$this->current_id]['forum_name'],
												$crumbs,
												$meta[$this->current_id]['forum_name']
											);
			}
		}

		// Is this a forum view?
		if ($request == 'viewforum')
		{
			if (FALSE !== ($meta = $this->_fetch_forum_metadata($this->current_id)))
			{
				$pid	= $meta[$this->current_id]['forum_parent'];
				$meta2	= $this->_fetch_forum_metadata($pid);

				$crumbs .= $this->_crumb_trail(
												array(
													'link' => $this->forum_path('/viewcategory/'.$pid.'/'),
													'title' => $meta2[$pid]['forum_name']
													)
												);

				return $this->_build_crumbs(
												$meta[$this->current_id]['forum_name'],
												$crumbs,
												$meta[$this->current_id]['forum_name']
											);
			}
		}

		// Is this the thread view?
		if ($request == 'viewthread' OR $request == 'split' OR $request == 'merge')
		{
			if (FALSE !== ($meta = $this->_fetch_topic_metadata($this->current_id)))
			{
				$pid 	= $meta[$this->current_id]['forum_parent'];
				$meta2 = $this->_fetch_forum_metadata($pid);

				$crumbs .= $this->_crumb_trail(
									array(
											'link' => $this->forum_path('/viewcategory/'.$meta[$this->current_id]['forum_parent'].'/'),
											'title' => $meta2[$pid]['forum_name']
										)
									);

				$crumbs .= $this->_crumb_trail(
									array(
											'link' => $this->forum_path('/viewforum/'.$meta[$this->current_id]['forum_id'].'/'),
											'title' => $meta[$this->current_id]['forum_name']
										)
									);


				if ($request == 'split' OR $request == 'merge')
				{
					$crumbs .= $this->_crumb_trail(
									array(
											'link' => $this->forum_path('/viewthread/'.$this->current_id.'/'),
											'title' => lang('thread')
										)
									);
					$page = lang($request);
				}
				else
				{
					$page = lang('thread');
				}

				return $this->_build_crumbs(
									$meta[$this->current_id]['title'],
									$crumbs,
									$page
								);
			}
		}

		// Is this the announce view?
		if ($request == 'viewannounce')
		{
			if (FALSE !== ($meta = $this->_fetch_forum_metadata($this->announce_id)))
			{
				$pid 	= $meta[$this->announce_id]['forum_parent'];
				$meta2 = $this->_fetch_forum_metadata($pid);
				$meta3 = $this->_fetch_topic_metadata($this->current_id);

				$crumbs .= $this->_crumb_trail(
								array(
										'link' => $this->forum_path('/viewcategory/'.$meta[$this->announce_id]['forum_parent'].'/'),
										'title' => $meta2[$pid]['forum_name']
									)
								);

				$crumbs .= $this->_crumb_trail(
								array(
										'link' => $this->forum_path('/viewforum/'.$meta[$this->announce_id]['forum_id'].'/'),
										'title' => $meta[$this->announce_id]['forum_name']
									)
								);

				return $this->_build_crumbs(
								$meta3[$this->current_id]['title'],
								$crumbs,
								lang('thread')
							);
			}
		}

		// Is this the submission page view?
		if ($request == 'newtopic')
		{
			if (FALSE !== ($meta = $this->_fetch_forum_metadata($this->current_id)))
			{
				$pid = $meta[$this->current_id]['forum_parent'];
				$meta2 = $this->_fetch_forum_metadata($pid);

				$crumbs .= $this->_crumb_trail(
									array(
											'link'	=> $this->forum_path('/viewcategory/'.$meta[$this->current_id]['forum_parent'].'/'),
											'title' => $meta2[$pid]['forum_name']
										)
									);

				$crumbs .= $this->_crumb_trail(
									array(
											'link'	=> $this->forum_path('/viewforum/'.$this->current_id.'/'),
											'title'	=> $meta[$this->current_id]['forum_name']
										)
									);

				return $this->_build_crumbs(
									lang('post_new_topic'),
									$crumbs,
									lang('post_new_topic')
								);
			}
		}

		// Is this one of the post submission pages?
		$type = array(
				'edittopic'		=> 'edit_topic',
				'quotetopic'	=> 'post_reply',
				'quotereply'	=> 'post_reply',
				'newreply'		=> 'post_reply',
				'editreply'		=> 'edit_reply',
				'movetopic'		=> 'move_topic',
				'movereply'		=> 'move_reply',
				'deletetopic'	=> 'delete_thread',
				'deletereply'	=> 'delete_reply',
				'reporttopic'	=> 'report_topic',
				'reportreply'	=> 'report_reply'
				);

		if (isset($type[$request]))
		{
			if (stristr($request, 'reply') AND $request != 'newreply' && $request != 'quotereply')
			{
				$meta = $this->_fetch_post_metadata($this->current_id);
			}
			else
			{
				$meta = $this->_fetch_topic_metadata($this->current_id);
			}

			if (FALSE !== $meta)
			{
				$pid = $meta[$this->current_id]['forum_parent'];
				$meta2 = $this->_fetch_forum_metadata($pid);

				$crumbs .= $this->_crumb_trail(
								array(
										'link'	=> $this->forum_path('/viewcategory/'.$meta[$this->current_id]['forum_parent'].'/'),
										'title'	=> $meta2[$pid]['forum_name']
									)
								);

				$crumbs .= $this->_crumb_trail(

								array(
										'link'	=> $this->forum_path('/viewforum/'.$meta[$this->current_id]['forum_id'].'/'),
										'title'	=> $meta[$this->current_id]['forum_name']
									)
								);

				$thread_id = (stristr($request, 'reply')
								&& $request != 'newreply'
								&& $request != 'quotereply') ? $meta[$this->current_id]['topic_id'] : $this->current_id;

				$crumbs .= $this->_crumb_trail(
								array(
										'link'	=> $this->forum_path('/viewthread/'.$thread_id.'/'),
										'title'	=> lang('thread')
									)
								);

				return $this->_build_crumbs(
												lang($type[$request]),
												$crumbs,
												lang($type[$request])
											);
			}
		}

		// Generate Error bread-crumb
		// If we got this far it means we don't have a valid page
		// so we'll show a basic error crumb

		return $this->_build_crumbs('', $crumbs, lang('error'));
	}

	/**
	 * Sets Page Title
	 *
	 * @param 	string	page title
	 *
	 */
	public function set_page_title($title)
	{
		if ($this->current_page_name == '')
		{
			$this->current_page_name = $title;
		}
	}

	/**
	 * Breadcrumb trail links
	 *
	 */
	function _crumb_trail($data)
	{
		$trail	= $this->load_element('breadcrumb_trail');

		$crumbs = '';

		$crumbs .= $this->var_swap($trail,
						array(
								'crumb_link'	=> $data['link'],
								'crumb_title'	=> $this->_convert_special_chars($data['title'])
								)
						);
		return $crumbs;
	}

	/**
	 * Theme Option List
	 */
	public function theme_option_list()
	{
		// Load the XML Helper
		ee()->load->helper('xml');

		$str = '';
		foreach ($this->fetch_theme_list() as $val)
		{
			$sel = ($this->theme == $val) ? ' selected="selected"' : '';

			$str .= '<option value="'.xml_convert($val).'"'.$sel.'>'.ucwords(str_replace('_', ' ', $val))."</option>\n";
		}

		return $str;
	}

	/**
	 * Set the theme
	 */
	public function set_theme()
	{
		if (empty($_POST))
		{
			return $this->trigger_error();
		}

		$theme = ee()->input->post('theme');

		if ( ! preg_match("/^[a-z0-9\s_-]+$/i", $theme))
		{
			exit('Forum themes may only contain alpha-numeric characters');
		}

		// If the user is logged in we'll update their member table
		if (ee()->session->userdata('member_id') != 0)
		{
			ee()->db->where('member_id', ee()->session->userdata('member_id'));
			ee()->db->update('members', array('forum_theme' => $theme));
		}

		$this->_load_preferences();
		$this->trigger = $this->fetch_pref('board_forum_trigger');
		$this->_forum_set_basepath();

		// Set a cookie!
		$expire = 60*60*24*365;
		ee()->input->set_cookie('forum_theme', $theme, $expire);

		if (isset(ee()->session->tracker[0]))
		{
			$return = ($this->fetch_pref('board_forum_trigger') != '') ? str_replace($this->fetch_pref('board_forum_trigger'), '', ee()->session->tracker[0]) : ee()->session->tracker[0];


			ee()->functions->redirect($this->forum_path($return));
		}

		ee()->functions->redirect($this->forum_path());
	}

	/**
	 * Fetch installed themes
	 */
	public function fetch_theme_list()
	{
		return ee('ee:Theme')->listThemes('forum');
	}

	/**
	 * Private Message Box in header
	 */
	public function private_message_box()
	{
		$str = $this->load_element('private_message_box');

		$pms = ee()->session->userdata('private_messages');

		if ($pms == '' OR ! is_numeric($pms))
		{
			$pms = 0;
		}

		if ($pms > 0)
		{
			$str = $this->allow_if('private_messages', $str);
			$str = $this->deny_if('no_private_messages', $str);
		}
		else
		{
			$str = $this->deny_if('private_messages', $str);
			$str = $this->allow_if('no_private_messages', $str);
		}

		return $this->var_swap($str,
								array(
										'total_unread_private_messages' => $pms
									)
								);
	}

	/**
	 * Base IFRAME for Spell Check
	 */
	public function spellcheck_iframe()
	{
		if (isset(ee()->session->tracker[0]) && substr(ee()->session->tracker[0], -17) == 'spellcheck_iframe')
		{
			ee()->session->do_not_track();
		}

		if ( ! class_exists('EE_Spellcheck'))
		{
			require APPPATH.'libraries/Spellcheck.php';
		}

		return EE_Spellcheck::iframe();
	}

	/**
	 * Spell Check for Textareas
	 */
	public function spellcheck()
	{
		if ( ! class_exists('EE_Spellcheck'))
		{
			require APPPATH.'libraries/Spellcheck.php';
		}

		return EE_Spellcheck::check();
	}

	/**
	 * SpellCheck - JS
	 */
	public function spellcheck_js()
	{
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

		return $this->SPELL->JavaScript($this->forum_path('/spellcheck/'), TRUE);
	}

	/**
	 * Parse PHP in template
	 */
	public function parse_template_php($str)
	{
		$str = preg_replace("/\<\?xml(.+?)\?\>/", "<XXML\\1/XXML>", $str);

		ob_start();

		echo ee()->functions->evaluate($str);

		$str = ob_get_contents();

		ob_end_clean();

		$str = preg_replace("/\<XXML(.+?)\/XXML\>/", "<?xml\\1?>", $str); // <?

		$this->parse_php = FALSE;

		return $str;
	 }

	/**
	 * Removes slashes from array
	 *
	 * @param 	mixed
	 */
	function array_stripslashes($vals)
	{
		if (is_array($vals))
		{
			foreach ($vals as $key=>$val)
			{
				$vals[$key]=$this->array_stripslashes($val);
			}
		}
		else
		{
			$vals = stripslashes($vals);
		}

		return $vals;
	}
}
// END CLASS

// EOF
