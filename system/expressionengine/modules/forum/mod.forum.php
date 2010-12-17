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

class Forum {


	var $version			= '3.1.2';
	var $build				= '20101217';
	var $use_site_profile	= FALSE;
	var $search_limit		= 250; // Maximum number of search results (x2 since it can include this number of topics + this number of posts)
	var $return_data 		= '';
	var $body_extra			= '';
	var $theme				= '';
	var $image_url			= '';
	var $forum_trigger		= '';
	var $trigger			= '';
	var $current_page		= 0;
	var $current_id			= '';
	var $return_override	= '';
	var $seg_addition		= 0;
	var $announce_id		= '';
	var $current_request	= '';
	var $current_page_name	= '';
	var $javascript			= '';
	var $head_extra			= '';
	var $submission_error	= '';
	var $error_message		= '';
	var $mimes				= '';
	var $basepath			= '';
	var $keywords			= '';
	var	$min_length			= 3;	// Minimum length of search keywords
	var	$cache_expire		= 24;	// How many hours should we keep search caches?
	var $max_chars			= 6000;
	var	$cur_thread_row		= 0;
	var $thread_post_total	= 0;	// Used for new entry submission to determine redirect page number
	var $trigger_error_page	= FALSE;
	var $is_table_open		= FALSE;
	var $preview_override	= FALSE;
	var $mbr_class_loaded	= FALSE;
	var $read_topics_exist	= FALSE;
	var $SPELL				= FALSE;
	var $spellcheck_enabled = FALSE;
	var $feeds_enabled		= NULL;
	var $feed_ids			= '';
	var $realm				= "ExpressionEngine Forums";
	var	$auth_attempt		= FALSE;
	var $use_sess_id		= 0;	// Used in calls to $this->EE->functions->fetch_site_index() in certain URLs, like attachments
	var $forum_ids			= array();
	var $attachments		= array();
	var $forum_metadata		= array();
	var $topic_metadata		= array();
	var $post_metadata		= array();
	var $admin_members		= array();
	var $admin_groups		= array();
	var $moderators			= array();
	var $current_moderator	= array();
	var $preferences		= array();
	var $form_actions		= array();
	var $uri_segments 		= array('viewcategory', 'viewpost', 'viewreply', 'viewforum', 'viewthread', 'viewannounce', 'newtopic', 'quotetopic', 'quotereply', 'reporttopic', 'reportreply', 'do_report', 'newreply', 'edittopic', 'editreply', 'deletetopic', 'deletereply', 'movetopic', 'merge', 'do_merge', 'split', 'do_split', 'movereply', 'subscribe', 'unsubscribe', 'smileys', 'member', 'search', 'member_search', 'new_topic_search', 'active_topic_search', 'view_pending_topics', 'do_search', 'search_results', 'search_thread', 'ban_member', 'do_ban_member', 'spellcheck_iframe', 'spellcheck', 'mark_all_read', 'rss', 'atom', 'ignore_member', 'do_ignore_member');
	
	var $include_exceptions	= array('head_extra', 'spellcheck_js', 'body_extra');

	/**
	 * Constructor
	 */
	function Forum()
	{	
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		$this->EE->db->cache_off();
	
		/** -------------------------------------
		/**  Load Base Forum Variables
		/** -------------------------------------*/
		
		$this->_load_base();

		/** ---------------------------------------
		/**  Running Sessions?
		/** ---------------------------------------*/
		
		// We use this in some special URLs to determine whether the Session ID
		// needs to be used in $this->EE->functions->fetch_site_index() or not
		
		$this->use_sess_id = ($this->EE->config->item('user_session_type') != 'c') ? 1 : 0;
		
		/** -------------------------------------
		/**  Is the forum enabled?
		/** -------------------------------------*/
		
		// If not, only super admins can view it
		
		if ($this->preferences['board_enabled'] == 'n' AND $this->EE->session->userdata('group_id') != 1)
		{
			return $this->_display_forum('offline_page');
		}
		
		// first part of this conditional protects when someone happens to set their profile trigger word to nothing...
		if ($this->current_request != '' && $this->current_request == $this->EE->config->item('profile_trigger'))
		{
			$this->_display_forum($this->EE->config->item('profile_trigger'));
		}
		else
		{
			require_once PATH_MOD.'forum/mod.forum_core'.EXT;

			$this->EE->FRM_CORE = new Forum_Core();
			
			$vars = get_object_vars($this);
			
			foreach($vars as $key => $value)
			{
				$this->EE->FRM_CORE->{$key} = $value;
			}
				
			/** -------------------------------------
			/**  Verify Permissions
			/** -------------------------------------*/
		
			// Before serving the page we'll see if the user is authorized
		
			if ( ! $this->EE->FRM_CORE->_is_authorized())
			{
				$this->EE->FRM_CORE->_set_page_title($this->EE->lang->line('error'));
				$error = $this->EE->FRM_CORE->_display_forum('error_page');
				
				if ($this->_use_trigger() === FALSE)
				{
					$this->return_data = $this->EE->FRM_CORE->return_data;
				}
				else
				{
					return $error;
				}
			}

			/** -------------------------------------
			/**  Display Requested Page
			/** -------------------------------------*/
			// If the ACT variable is set we know that we are 
			// dealing with an action request.  
			// Thus, we'll supress the normal course of events.
			
			if ( ! $this->EE->input->get_post('ACT'))
			{
				$this->EE->FRM_CORE->_display_forum();
			}
			
			/** ---------------------------------
			/**  If Template Parser Request
			/** ---------------------------------*/
			
			$this->return_data = $this->EE->FRM_CORE->return_data;
		}
	}

	// --------------------------------------------------------------------
	
	/**
	 * Load Base Forum Wrapper Functions
	 *
	 * @access private
	 */
	function _load_base()
	{
		// Is the member area trigger changed?
		if ($this->EE->config->item('profile_trigger') != 'member' && in_array('member', $this->uri_segments))
		{
			unset($this->uri_segments[array_search('member', $this->uri_segments)]);
			$this->uri_segments[] = $this->EE->config->item('profile_trigger');
		}
		
		// Is this a Template Request?  If so, we need to do a check
		// to see how many segments are devoted to the template calling and
		// if it is two, then we need to modify the segments
		if ($this->_use_trigger() === FALSE)
		{
			
			if (isset($this->EE->uri->segments['1']) AND stristr($this->EE->uri->segments['1'], "ACT=") AND $this->EE->config->item('forum_trigger') != '')
			{
				$this->EE->uri->segments['1'] = $this->EE->config->item('forum_trigger');
			}
			
			// We have a template or template group included, 
			// since there is no match between the second segment
			// and the valid forum uri segments but there is for the 
			// third segment.x
			$i = 1;
			
			while(TRUE)
			{
				if ($i > 8) break; // Safety
				
				if (isset($this->EE->uri->segments[$i]) &&
				! in_array($this->EE->uri->segments[$i], $this->uri_segments))
				{
					if ( ! isset($this->EE->uri->segments[$i+1]) 
						OR (isset($this->EE->uri->segments[$i+1]) && ! in_array($this->EE->uri->segments[$i+1], $this->uri_segments)))
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
				$this->trigger = implode('/', array_slice($this->EE->uri->segments, 0, $i-1));
			}
		}
		
		/** -------------------------------------
		/**  Disallow Private Methods
		/** -------------------------------------*/
		// Functions are called automatically based on the 
		// second segment of the URL. However, we don't want
		// to allow any of the private function to be called directly.

		if (substr($this->EE->uri->segment(2+$this->seg_addition), 0, 1) == '_')
		{
			exit;
		}
		
		// Load Base Resources
		$this->EE->lang->loadfile('forum');
		$this->_parse_uri();
		$this->_load_preferences();
		$this->_check_theme_path();		
	}

	// --------------------------------------------------------------------
	
	/**
	 * Display Forum Handler
	 *
	 * @param 	string	function to call
	 * @access	private
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
						$this->EE->config->item('profile_trigger')	=> '_load_member_class',
								'ban_member'	=> 'ban_member_form',
								'do_ban_member'	=> 'do_ban_member'
					  );		
		
		if (isset($remap[$function]))
		{
			$function = $remap[$function];
		}
						

		// The output is based on whether we are using the main template parser or not.
		// If the config.php file contains a forum "triggering" word we'll send
		// the output directly to the output class.  Otherwise, the output
		// is sent to the template class like normal.  The exception to this is
		// when action requests are processed
				
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

	// --------------------------------------------------------------------

	function submit_post() 			{ return $this->EE->FRM_CORE->submit_post(); }
	function delete_post() 			{ return $this->EE->FRM_CORE->delete_post(); }
	function change_status() 		{ return $this->EE->FRM_CORE->change_status(); }
	function move_topic() 			{ return $this->EE->FRM_CORE->move_topic(); }
	function move_reply()			{ return $this->EE->FRM_CORE->move_reply(); }
	function delete_subscription()	{ return $this->EE->FRM_CORE->delete_subscription(); }
	function display_attachment()	{ return $this->EE->FRM_CORE->display_attachment(); }
	function topic_titles()			{ if ( ! is_object($this->EE->FRM_CORE)) return; return $this->EE->FRM_CORE->topic_titles(); }
	function do_merge()				{ return $this->EE->FRM_CORE->do_merge(); }
	function do_split()				{ return $this->EE->FRM_CORE->do_split(); }
	function do_report()			{ return $this->EE->FRM_CORE->do_report(); }
	
	// --------------------------------------------------------------------
	
	/**
	 * Parse URI
	 */
	
	// The forum URL will typically contain only a few different possibilities:
	// 
	// The "forum view" will be at:
	// index.php/forum/viewforum/3/
	//
	// The "category view" will be at:
	// index.php/forum/viewcategory/23/
	//
	// The "thread view" will be at:
	// index.php/forum/viewthread/345/
	//
	// The search page will be at:
	// index.php/forum/search/
	//
	// The member profile pages will be at:
	// index.php/forum/member/some_page/
	//
	// In addition, there might be a page indicator:
	// index.php/forum/viewthread/2456/P20/
	//
	// The URLs aren't all that complex.  They typically will have a segment in the second position
	// indicating a function that is called, with the data in the third position being the ID number.
	// The ID number is context-sensitive, depending on the "view" we're looking at.
	// For example, in this URL:  index.php/forum/viewthread/2456/
	// The ID represents a particular thread ID.
	//
	// So, the purpose of this function is simply to identify the ID number and assign it to 
	// the $this->current_id variable.  We'll also grab the page number if there happens to be one.
	
	function _parse_uri()
	{
		// If we are dealing with an action request it will
		// inadvertenly mess up our forum URL trigger so
		// we'll test for it and reassign the first segment
		
		if (isset($this->EE->uri->segments['1']) AND stristr($this->EE->uri->segments['1'], "ACT=") AND $this->EE->config->item('forum_trigger') != '')
		{
			$this->EE->uri->segments['1'] = $this->forum_trigger;
		}

		// Load the string helper
		$this->EE->load->helper('string');
		
		if ($this->_use_trigger())
		{
			// preg_quote() is not really necessary here since we currently allow only alphanumeric, _ and -, but
			// I'm adding it for future-proofing sake - D'Jones
				
			$this->current_id = trim_slashes(preg_replace('/^\/?'.preg_quote($this->forum_trigger, '/').'/', '', $this->EE->uri->uri_string));	

			$this->trigger = $this->forum_trigger;
		}
		else
		{		
			$uri = trim_slashes($this->EE->uri->uri_string);
			
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
					
				$this->current_id = $this->EE->functions->remove_double_slashes(str_replace($match['0'], '', $this->current_id));
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

	// --------------------------------------------------------------------	
	
	/**
	 * Fetch the Trigger Status
	 *
	 * If TRUE we are bypassing the template engine
	 *
	 * @return boolean
	 */
	function _use_trigger()
	{
		if ($this->EE->config->item('forum_is_installed') == "y" AND 
			$this->EE->config->item('forum_trigger') != '' AND 
			in_array(
					$this->EE->uri->segment(1+$this->seg_addition), 
					preg_split('/\|/', $this->EE->config->item('forum_trigger'), -1, PREG_SPLIT_NO_EMPTY))
		)
		{
			$this->forum_trigger = $this->EE->uri->segment(1+$this->seg_addition);
			return TRUE;
		}
		else
		{
			return FALSE;	
		}
	}

	// --------------------------------------------------------------------		

	/**
	 * Recursively Fetch Template Elements
	 *
	 *
	 * Note:  A "template element" refers to an HTML component used to build the forum 
	 * (header, breadcrumb, footer, etc.). 
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
	 * Forum CSS
	 *
	 * @return string
	 */
	function forum_css()
	{
		$str = $this->_load_element('forum_css');
		// Remove comments and spaces from CSS file
		$str = preg_replace("/\/\*.*?\*\//s", '', $str);
		$str = preg_replace("/\}\s+/s", "}\n", $str);
		return $str;
	}

	// --------------------------------------------------------------------	
	
	/** 
	 * Load a Theme Element
	 *
	 * @param 	string	element to load
	 * @return 	mixed
	 */		
	function _load_element($which)
	{
		if ( ! ($classname = $this->_fetch_filename($which)))
		{
			$data = array(	'title' 	=> $this->EE->lang->line('error'),
							'heading'	=> $this->EE->lang->line('general_error'),
							'content'	=> $this->EE->lang->line('nonexistent_page'),
							'redirect'	=> '',
							'link'		=> array($this->_forum_path(), $this->_fetch_pref('board_label'))
						 );

			return $this->EE->output->show_message($data, 0);
		}
		
		$path = $this->theme.'/'.$classname.'/'.$which.'.html';
		
		if ( ! is_file($this->_fetch_pref('board_theme_path').$path))
		{
			return $this->EE->output->fatal_error('Unable to locate the following forum theme file: '.$path);
		}

		if ($this->_fetch_pref('board_allow_php') == 'y' AND $this->_fetch_pref('board_php_stage') == 'i')
		{
			return $this->parse_template_php($this->_prep_element(
				trim(file_get_contents($this->_fetch_pref('board_theme_path').$path))
			));
		}
		
		return $this->_prep_element(trim(file_get_contents($this->_fetch_pref('board_theme_path').$path)));
	}

	// --------------------------------------------------------------------	

	/**
	 * Prep Element Data
	 *
	 * Right now we only use this to parse the logged-in/logged-out vars
	 *
	 * @param 	string
	 * @return 	string
	 */
	function _prep_element($str)
	{
		if ($str == '')
		{
			return '';			
		}
		
		if ($this->EE->session->userdata('member_id') == 0)
		{
			$str = $this->_deny_if('logged_in', $str);
			$str = $this->_allow_if('logged_out', $str);
		}
		else
		{
			$str = $this->_allow_if('logged_in', $str);
			$str = $this->_deny_if('logged_out', $str);
		}
		
		return $str;		
	}

	// --------------------------------------------------------------------	
	
	/**
	 * Load Forum Preference
	 *
	 * @param 	integer		board id
	 *
	 */
	function _load_preferences($board_id='')
	{
		if ($board_id != '')
		{
			$this->EE->db->where('board_id', $board_id);
		}
		elseif ($this->EE->input->get_post('ACT') !== FALSE && $this->EE->input->get_post('board_id') !== FALSE)
		{
			$this->EE->db->where('board_id', $this->EE->input->get_post('board_id'));
		}
		elseif ($this->_use_trigger() === TRUE)
		{
			$this->EE->db->where('board_forum_trigger', $this->forum_trigger);
			$this->EE->db->where('board_site_id', $this->EE->config->item('site_id'));
		}
		else
		{
			// Means we are in a Template
			// If no board="" parameter, then we automatically
			// use the default board_id of 1
			if (isset($this->EE->TMPL) && is_object($this->EE->TMPL) && ($board_name = $this->EE->TMPL->fetch_param('board')) !== FALSE)
			{
				$this->EE->db->where('board_name', $board_name);
			}
			else
			{	
				$this->EE->db->where('board_id', 1);
			}
		}
		
		$this->EE->db->select('board_label, board_name, board_id, board_alias_id, 
							board_forum_url, board_enabled, board_default_theme, board_forum_trigger,
							board_upload_path, board_topics_perpage, board_posts_perpage, board_topic_order, 
							board_post_order, board_display_edit_date, board_hot_topic, board_max_attach_perpost, 
							board_attach_types, board_max_attach_size, board_use_img_thumbs, board_recent_poster, 
							board_recent_poster_id, board_notify_emails, board_notify_emails_topics, board_allow_php,
							board_php_stage');
		$query = $this->EE->db->get('forum_boards');

		if ($query->num_rows() == 0)
		{
			$this->EE->output->show_user_error('general', $this->EE->lang->line('forum_not_installed'));
		}

		if ($query->row('board_alias_id') != '0')
		{
			$this->_load_preferences($query->row('board_alias_id') );
			
			foreach(array('board_label', 'board_name', 'board_enabled', 'board_forum_url') as $val)
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
			$this->preferences['member_profile_path'] = $this->EE->functions->create_url($this->EE->config->item('profile_trigger').'/');
		}
		else
		{
			$this->preferences['member_profile_path'] 	= $this->_forum_path($this->EE->config->item('profile_trigger').'/');	
		}
		
		$this->preferences['board_theme_path'] 	= PATH_THEMES.'forum_themes/';
		$this->preferences['board_theme_url']	= $this->EE->config->slash_item('theme_folder_url').'forum_themes/';
	}

	// --------------------------------------------------------------------	

	/**
	 * Instantiates the Member Profile Class
	 *
	 * @access private
	 * @return string
	 */
	function _load_member_class()
	{
		// This needs to be first!  Don't move it.
		$template = $this->_load_element('member_page');
		
		$this->mbr_class_loaded = TRUE;
		include_once PATH_MOD.'member/mod.member'.EXT;	
		
		$this->EE->MBR = new Member();

		$this->EE->MBR->_set_properties(
								array(
										'trigger'			=> $this->EE->config->item('profile_trigger'),
										'theme_class'		=> 'theme_member',
										'in_forum'			=> TRUE,
										'is_admin'			=> TRUE,
										'enable_breadcrumb'	=> FALSE,
										'basepath'			=> $this->_forum_path($this->EE->config->item('profile_trigger')),
										'forum_path'		=> $this->_forum_path(),
										'image_url'			=> $this->image_url,
										'theme_path'		=> $this->_fetch_pref('board_theme_path').$this->theme.'/forum_member/',
										'css_file_path'		=> $this->_fetch_pref('board_theme_url').$this->theme.'/theme.css',
										'board_id'			=> $this->_fetch_pref('board_id')
									)
							);

		$template = str_replace('{include:member_manager}', $this->EE->MBR->manager(), $template);
		
		$this->head_extra = $this->EE->MBR->head_extra;

		if ($this->EE->MBR->show_headings == TRUE)
		{
			$template = $this->_allow_if('show_headings', $template);
		}
		else
		{
			$template = $this->_deny_if('show_headings', $template);
		}

		return $template;
	}

	// --------------------------------------------------------------------	

	/**
	 * Fetch Preference item
	 *
	 * @param 	string
	 * @return 	string
	 */
	function _fetch_pref($which)
	{
		return ( ! isset($this->preferences[$which])) ? '' : $this->preferences[$which];
	}

	// --------------------------------------------------------------------		
		
	/**
	 * Check the theme folder path
	 *
	 * @return mixed
	 */
	function _check_theme_path()
	{
		// Check path to master folder containing all the themes
		if ( ! is_dir($this->_fetch_pref('board_theme_path')))
		{
			return $this->EE->output->fatal_error('Unable to locate the forum theme folder.');
		}
	
		// Grab theme.  Can be from a cookie or user pref
		$forum_theme = ($this->EE->session->userdata('member_id') != 0) ? $this->EE->session->userdata('forum_theme') : '';
		
		// Maybe the theme is in a cookie?
		if ($forum_theme == '')
		{
			if ($this->EE->input->cookie('forum_theme') != FALSE)
			{			
				$forum_theme = $this->EE->input->cookie('forum_theme');
				
				// Security checks.  Only alpha-numeric text
				if ( ! preg_match("/^[a-z0-9\s_-]+$/i", $forum_theme))
				{ 
					$forum_theme = '';
				}
				
				// If the user is logged in we'll update their forum selection
				if ($forum_theme != '' AND $this->EE->session->userdata('member_id') != 0)
				{
					$this->EE->db->where('member_id', $this->EE->session->userdata('member_id'));
					$this->EE->db->update('members', array('forum_theme' => $forum_theme));					
				}				
			}
		}

		// Check path to folder containing the requested theme		
		$this->theme = ($forum_theme != '' AND @is_dir($this->_fetch_pref('board_theme_path').$forum_theme)) ? $forum_theme : $this->_fetch_pref('board_default_theme');
		
		if ( ! @is_dir($this->_fetch_pref('board_theme_path').$this->theme))
		{
			return $this->EE->output->fatal_error('Unable to locate the forum theme folder.');
		}
	 
		// Set path to the image folder for the particular theme
		$this->image_url = $this->_fetch_pref('board_theme_url').$this->theme.'/images/';
	}

	// --------------------------------------------------------------------	
 
	/**
	 * Build Form Declaration
	 *
	 * @param mixed
	 */
	function _form_declaration($form = '')
	{
		list($class, $method) = explode(':', $form);
				
		$hidden['ACT']	= $this->EE->functions->fetch_action_id($class, $method);
		$hidden['FROM']	= 'forum';
		$hidden['mbase'] = $this->_forum_path($this->EE->config->item('profile_trigger'));
		$hidden['board_id'] = $this->_fetch_pref('original_board_id');

		if (isset($this->form_actions[$form]))
		{
			foreach ($this->form_actions[$form] as $key => $val)
			{
				$hidden[$key] = $val;
			}
		}
		
		// special handling for mini login forms on member profile pages
		if ($this->current_request == $this->EE->config->item('profile_trigger') && $this->current_id == '')
		{
			$hidden['RET'] = $this->_forum_path();
		}
		
		if ( ! isset($hidden['RET']))
		{
			if ($this->return_override != '')
			{
				$hidden['RET'] = $this->EE->functions->remove_double_slashes($this->_forum_path($this->current_request.'/'.$this->return_override));				
			}
			else
			{
				$hidden['RET'] = $this->EE->functions->remove_double_slashes($this->_forum_path($this->current_request.'/'.$this->current_id));
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
				$action = $this->_forum_path('/newreply/'.$this->current_id.'/');
			}
			elseif ($this->current_request == 'quotereply')
			{
				$action = $this->_forum_path('/newreply/'.$this->current_id.'/');
			}
			else
			{
				$action = $this->_forum_path('/'.$this->current_request.'/'.$this->current_id.'/');
			}
		}
		elseif($method == 'do_split')
		{
			$action = $this->EE->functions->remove_double_slashes($this->_forum_path($this->current_request.'/'.$hidden['topic_id']));
			//print_r(get_object_vars($this));
		}
	
		return $this->EE->functions->form_declaration(array(
												'hidden_fields'	=> $hidden,
												'action'		=> $action,
												'name'			=> ($method == 'submit_post') ? $method : '',
												'id'			=> ($method == 'submit_post') ? $method : '',
												'enctype'		=> ($method == 'submit_post' AND $this->current_request != 'viewthread') ? 'multi' : ''
											)
										);  
	}

	// --------------------------------------------------------------------	

	/**
	 * Build Profile Path with member ID
	 *
	 * @param 	int	id
	 * @return 	string
	 */
	function _profile_path($id)
	{
		return $this->_fetch_pref('member_profile_path').$id.'/';
	}

	// --------------------------------------------------------------------	

	/**
	 * Build Search Path with sting
	 *
	 * We need to assign an action to this...
	 *
	 */
	function _search_path($id)
	{	
		return $this->_forum_path('/search/');
	}

	// --------------------------------------------------------------------	

	/**
	 * Sets the forum basepath
	 */
	function _forum_set_basepath()
	{
		/* -------------------------------------------
		/*	Hidden Configuration Variable
		/*	- use_forum_url => Does the user runs their forum at a different base URL then their main site? (y/n)
		/* -------------------------------------------*/
		if ($this->EE->config->item('use_forum_url') == 'y')
		{
			$this->basepath = $this->_fetch_pref('board_forum_url');
			return;
		}
		
		// The only reason we set this is so that the session ID gets added to the URL
		// if the user is running their site in session only mode
		$this->EE->functions->template_type = 'webpage';

		$trigger = (isset($_GET['trigger'])) ? $_GET['trigger'] : $this->trigger;
		$this->basepath = $this->EE->functions->create_url($trigger).'/'; 
	}

	// --------------------------------------------------------------------

	/**
	 * Compiles a path string
	 */
	function _forum_path($uri = '')
	{
		if ($this->basepath == '')
		{
			$this->_forum_set_basepath();
		}

		return $this->EE->functions->remove_double_slashes($this->basepath.$uri.'/');
	}

	// --------------------------------------------------------------------		

	/**
	 * Replace variables
	 */
	function _var_swap($str, $data)
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

	// --------------------------------------------------------------------	

	/**
	 * Helpers for "if" conditions
	 */
	function _deny_if($cond, $str, $replace = '')
	{
		return preg_replace("/\{if\s+".$cond."\}.+?\{\/if\}/si", $replace, $str);
	}

	// --------------------------------------------------------------------	
	
	function _allow_if($cond, $str)
	{
		return preg_replace("/\{if\s+".$cond."\}(.+?)\{\/if\}/si", "\\1", $str);
	}

	// --------------------------------------------------------------------		

	/**
	 * Convert special characters
	 */
	function _convert_special_chars($str, $convert_amp = FALSE)
	{
		// If we convert &'s for strings that have typography performed on them,
		// then they will be double-converted
		if ($convert_amp === TRUE)
		{
			$str = str_replace('&', '&amp;', $str);
		}
		
		return str_replace(array('<', '>', '{', '}', '\'', '"', '?'), array('&lt;', '&gt;', '&#123;', '&#125;', '&#146;', '&quot;', '&#63;'), $str);
	}

	// --------------------------------------------------------------------	

	/**
	 * Convert forum tags
	 */
	function _convert_forum_tags($str)
	{	
		$str = str_replace('{include:', '&#123;include:', $str);
		$str = str_replace('{path:', '&#123;path:', $str);
		$str = str_replace('{lang:', '&#123;lang:', $str);
		
		return $str;
	}

	// --------------------------------------------------------------------	
	
	/**
	 * Final Template Parsing
	 */
	function _final_prep($str)
	{
		// Is the user an admin?	
		if ( ! $this->_is_admin())
		{
			$str = $this->_deny_if('is_admin', $str);
		}
		else
		{
			$str = $this->_allow_if('is_admin', $str);
		}	
		
		if ($this->mbr_class_loaded == TRUE)
		{
			$str = $this->_deny_if('in_forum', $str);
		}
		else
		{
			$str = $this->_allow_if('in_forum', $str);
		}
			// Parse the language text
			if (preg_match_all("/{lang:(.+?)\}/i", $str, $matches))
			{	
			for ($j = 0; $j < count($matches['0']); $j++)
			{
				$line = $this->EE->lang->line($matches['1'][$j]);
				
				// Since we're using the pre-existing search language file
				// we might need to add a prefix
				if ($line == '' AND $this->current_request == 'search')
				{
					$line = $this->EE->lang->line('search_'.$matches['1'][$j]);
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
		if (preg_match_all("/{last_visit_date\s+format=['|\"](.+?)['|\"]\}/i", $str, $matches))
		{
			for ($j = 0; $j < count($matches['0']); $j++)
			{
				if ($this->EE->session->userdata('member_id') == 0)
				{
					$str = str_replace($matches['0'][$j], $this->EE->lang->line('never'), $str);				
				}
				else
				{
					$str = str_replace($matches['0'][$j], $this->EE->localize->decode_date($matches['1'][$j], $this->EE->session->userdata['last_visit']), $str);
				}
			}
		}

		// If the member class is loaded we'll set the page title based on its page title
		if ($this->mbr_class_loaded == TRUE AND $this->current_page_name == '')
		{
			$this->current_page_name = $this->EE->MBR->page_title;
		}
		
		if (is_null($this->feeds_enabled) OR $this->feeds_enabled === FALSE)
		{
			$str = $this->_deny_if('feeds_enabled', $str);
		}
		else
		{
			$str = $this->_allow_if('feeds_enabled', $str);
		}	

		/** ----------------------------------------
		/**  Parse the forum segments and board prefs
		/** ----------------------------------------*/

		$conds = array(
			'current_request'	=> $this->current_request,
			'current_id'		=> $this->current_id,
			'current_page'		=> $this->current_page
		);

		// parse certain board preferences as well
		foreach (array('original_board_id', 'board_label', 'board_name', 'board_id', 'board_alias_id') as $pref)
		{
			$conds[$pref] = $this->_fetch_pref($pref);
		}
		
		$str = $this->_var_swap($str, $conds);

		$str = $this->_var_swap($str,
								array(
										'lang'						=> $this->EE->config->item('xml_lang'),
										'charset'					=> $this->EE->config->item('output_charset'),
										'include:head_extra'		=> $this->head_extra,
										'include:body_extra'		=> $this->body_extra,
										'include:spellcheck_js'		=> $this->spellcheck_js(),
										'path:spellcheck_iframe'	=> $this->_forum_path('/spellcheck_iframe/'),
										'screen_name'				=> $this->_convert_special_chars($this->EE->session->userdata('screen_name')),
										'path:logout'				=> $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->EE->functions->fetch_action_id('Member', 'member_logout').'&amp;FROM=forum&amp;board_id='.$this->_fetch_pref('original_board_id'),
										'path:image_url'			=> $this->image_url,
										'path:forum_home'			=> $this->_forum_path(),
										'path:your_control_panel'	=> $this->_profile_path('profile'),
										'path:your_profile'			=> $this->_profile_path($this->EE->session->userdata('member_id')),
										'path:login'				=> $this->_profile_path('login'),
										'path:register'				=> $this->_profile_path('register'),
										'path:memberlist'			=> $this->_profile_path('memberlist'),
										'path:forgot'				=> $this->_profile_path('forgot_password'),
										'path:private_messages'		=> $this->_profile_path('messages/view_folder/1'),
										'path:recent_poster'		=> $this->_profile_path($this->_fetch_pref('board_recent_poster_id')),
										'path:advanced_search'		=> $this->_forum_path('/search/'),
										'path:view_new_topics'		=> $this->_forum_path('/new_topic_search'),
										'path:view_active_topics'	=> $this->_forum_path('/active_topic_search'),
										'path:view_pending_topics'	=> $this->_forum_path('/view_pending_topics'),
										'path:mark_all_read'		=> $this->_forum_path('/mark_all_read/'),										
										'path:do_search'			=> $this->_forum_path('/do_search/'),
										'path:smileys'				=> $this->_forum_path('/smileys/'),
										'path:rss'					=> $this->_forum_path('/rss/'.$this->feed_ids),
										'path:atom'					=> $this->_forum_path('/atom/'.$this->feed_ids),
										'recent_poster'				=> $this->_fetch_pref('board_recent_poster'),
										'forum_name'				=> $this->_convert_special_chars($this->_fetch_pref('board_label'), TRUE),
										'forum_url'					=> $this->_fetch_pref('board_forum_url'),
										'page_title'				=> $this->_convert_special_chars($this->current_page_name, TRUE),
										'module_version'			=> $this->version,
										'forum_build'				=> $this->build,
										'error_message'				=> $this->error_message,
										'path:theme_css'			=> $this->_fetch_pref('board_theme_url').$this->theme.'/theme.css',
										'path:theme_js'				=> $this->_fetch_pref('board_theme_url').$this->theme.'/theme/javascript/',
										'site_url'					=> $this->EE->config->item('site_url')
									)
						); 

		/** ------------------------------------
		/**  Evaluate the segment conditionals
		/** ------------------------------------*/

		if (preg_match("/".LD."if (".implode('|', array_keys($conds)).").*?".RD.".*?".LD."\/if".RD."/s", $str))
		{
			$str = $this->EE->functions->prep_conditionals($str, $conds, 'y');
	
			// protect PHP tags within the conditional
			// code block PHP tags are already protected, so we must double encode them
			$str = str_replace(array('&lt;?', '?&gt;'), array('&amp;lt;?', '?&amp;gt;'), $str);
			$str = str_replace(array('<?', '?>'), array('&lt;?', '?&gt;'), $str);

			// convert our prepped EE conditionals to PHP
			$str = str_replace(array(LD.'/if'.RD, LD.'if:else'.RD), array('<?php endif; ?'.'>','<?php else : ?'.'>'), $str);
			$str = preg_replace("/".preg_quote(LD)."((if:(else))*if)\s*(.*?)".preg_quote(RD)."/s", '<?php \\3if(\\4) : ?'.'>', $str);

			// Evaluate the php conditionals
			$str = $this->parse_template_php($str);

			// Bring back the old php tags and double encoded
			$str = str_replace(array('&lt;?', '?&gt;'), array('<?', '?>'), $str);
			$str = str_replace(array('&amp;lt;?', '?&amp;gt;'), array('&lt;?', '?&gt;'), $str);
		}
	
		if ($this->_fetch_pref('board_allow_php') == 'y' AND $this->_fetch_pref('board_php_stage') == 'o')
		{
			return $this->parse_template_php($str);
		}
	
		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch classname
	 *
	 * Given an element (function) name, this function
	 * returns the name of the subfolder folder that contains the
	 * corresponding template file.
	 */
	function _fetch_filename($index)
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

	// --------------------------------------------------------------------	

	/** -------------------------------------
	/**  Load Mime-types
	/** -------------------------------------*/
	function _fetch_mimes()
	{
		if ($this->mimes == '')
		{
			include(APPPATH.'config/mimes.php');			
			$this->mimes = $mimes;
		}
	}

	// --------------------------------------------------------------------
	
	/** -------------------------------------
	/**  Is the user authorized for the specfic page?
	/** -------------------------------------*/
	function _is_authorized()
	{
		return TRUE;
	}

	// --------------------------------------------------------------------

	/** --------------------------------
	/**  Generate Error Page
	/** --------------------------------*/
	
	function _trigger_error($msg = 'not_authorized')
	{
		$this->return_data = '';		
		$this->error_message = $this->EE->lang->line($msg);
		$this->_set_page_title($this->EE->lang->line('error'));
		return $this->_display_forum('error_page');			
	}

	// --------------------------------------------------------------------

	/** -------------------------------------
	/**  Trigger the log-in page 
	/** -------------------------------------*/
	
	// This function sets a couple variables which the 
	// $this->_include_recursive() looks for to determine 
	// whether the error page should be shown.
	
	function _trigger_login_page()
	{
		$this->return_data = '';
		$this->trigger_login_page = TRUE;
		return FALSE;
	}

	// --------------------------------------------------------------------
		
	/** -------------------------------------
	/**  Is a particular user an admin?
	/** -------------------------------------*/
	
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
		
		// If we know the member ID but not the group
		// we need to look it up
		
		if ($member_id != 0 AND $group_id == 0)
		{
			$this->EE->db->select('group_id');
			$query = $this->EE->db->get_where('members', array('member_id' => $member_id));
		
			if ($query->num_rows() == 0)
			{
				return FALSE;
			}
			
			if ($query->row('group_id')  == 1)
			{
				return TRUE;
			}			
		}
		
		return FALSE;
	}

	// --------------------------------------------------------------------	
	
	/** -------------------------------------
	/**  Individual Member's Last Visit
	/** -------------------------------------*/
	function member_post_total()
	{
		return str_replace('%x', $this->EE->session->userdata('total_forum_posts'), $this->EE->lang->line('your_post_total'));
	}

	// --------------------------------------------------------------------
	
	/** -------------------------------------
	/**  Quick Search Form
	/** -------------------------------------*/
	function quick_search_form()
	{
		$form = $this->EE->functions->form_declaration(array(
												'action' => $this->_forum_path('do_search'),
												'hidden_fields' => array('board_id' => $this->_fetch_pref('original_board_id'))
											)
										);  

		return $this->_var_swap($this->_load_element('quick_search_form'),
							array(
									'form_declaration' => $form,
									'forum_id' => $this->current_id
								)
							);
	}

	// --------------------------------------------------------------------

	/** -------------------------------------
	/**  Quick Search Form - restricts to current forum
	/** -------------------------------------*/
	function forum_quick_search_form()
	{
		$form = $this->EE->functions->form_declaration(array(
												'action' => $this->_forum_path('do_search'),
												'hidden_fields' => array('board_id' => $this->_fetch_pref('original_board_id'))
											)
										);  

		return $this->_var_swap($this->_load_element('forum_quick_search_form'),
							array(
									'form_declaration'	=> $form,
									'forum_id' => $this->current_id
								)
							);
	}

	// --------------------------------------------------------------------

	/** -------------------------------------
	/**  Page subheader
	/** -------------------------------------*/
	
	function page_subheader()
	{
		$template = $this->_load_element('page_subheader');
	
		if ($this->current_request == 'search')
		{
			$template = $this->_deny_if('not_search_page', $template);
		}
		else
		{
			$template = $this->_allow_if('not_search_page', $template);
		}
			
		return $template;
	}

	// --------------------------------------------------------------------	
	
	/** -------------------------------------
	/**  Finalize the Crumbs
	/** -------------------------------------*/
	function _build_crumbs($title, $crumbs, $str)
	{		
		$this->_set_page_title(($title == '') ? 'Powered By ExpressionEngine' : $title);
	
		$crumbs .= str_replace('{crumb_title}', $this->_convert_special_chars($str, TRUE), $this->_load_element('breadcrumb_current_page'));		
	
		return str_replace('{breadcrumb_links}', $crumbs, $this->_load_element('breadcrumb'));			
	}

	// --------------------------------------------------------------------	

	/** -------------------------------------
	/**  Breadcrumb
	/** -------------------------------------*/
	
	function breadcrumb()
	{
		/** -------------------------------------
		/**  Do we even need any crumbs?
		/** -------------------------------------*/
		
		// If there are no URI segments we'll show the home page text
		
		if (count($this->EE->uri->segments) <= 1 + $this->seg_addition)
		{					
			return $this->_build_crumbs('', '', $this->EE->lang->line('home'));
		}
		
		/** -------------------------------------
		/**  Define the first crumb (forum homepage link)
		/** -------------------------------------*/
		
		$crumbs = $this->_crumb_trail(
										array(
												'link'	=> $this->_forum_path('/'), 
												'title'	=> $this->EE->lang->line('home')
											 )
									);
		
		
		$request = $this->EE->uri->segment(2+$this->seg_addition);
		
		/** -------------------------------------
		/**  Is this the search page?
		/** -------------------------------------*/
		
		if ($request == 'search')
		{
			if ($this->current_id == '')
			{
				return $this->_build_crumbs($this->EE->lang->line('search'), $crumbs, $this->EE->lang->line('advanced_search'));
			}
		}
		
		/** -------------------------------------
		/**  Is this a Search Results page?
		/** -------------------------------------*/
		
		if ($request == 'search_results' OR $request == 'search_thread')
		{
				$crumbs .= $this->_crumb_trail(array(	
													'link' => $this->_forum_path('/search'), 
													'title' => $this->EE->lang->line('advanced_search')
													)
												);
		
			return $this->_build_crumbs('', $crumbs, $this->EE->lang->line('search_results'));
		}
		
		/** -------------------------------------
		/**  Is this the member banning page?
		/** -------------------------------------*/
		
		if ($request == 'ban_member' OR $request == 'do_ban_member')
		{
			return $this->_build_crumbs($this->EE->lang->line('ban_member'), $crumbs, $this->EE->lang->line('ban_member'));
		}
		
		/** -------------------------------------
		/**  Is this an ignore member page?
		/** -------------------------------------*/
		
		if ($request == 'ignore_member' OR $request == 'do_ignore_member')
		{
			return $this->_build_crumbs($this->EE->lang->line('ignore_member'), $crumbs, $this->EE->lang->line('ignore_member'));
		}
		
		/** -------------------------------------
		/**  Are we showing the member profile pages?
		/** -------------------------------------*/
		
		if ($request == $this->EE->config->item('profile_trigger'))
		{		
			if ($this->EE->uri->segment(3+$this->seg_addition) == '')
			{
				return $this->_build_crumbs($this->EE->lang->line('member_profile'), $crumbs, $this->EE->lang->line('member_profile'));
			}
			
			if (is_numeric($this->EE->uri->segment(3+$this->seg_addition)))
			{
				$this->EE->db->select('screen_name');
				$query = $this->EE->db->get_where('members', 
										array('member_id' => $this->EE->uri->segment(3+$this->seg_addition))
									);
				
				$crumbs .= $this->_crumb_trail(array(	
													'link' => $this->_forum_path('/'.$this->EE->config->item('profile_trigger').'/memberlist'), 
													'title' => $this->EE->lang->line('memberlist')
													)
												);
				
				return $this->_build_crumbs($this->_convert_special_chars($query->row('screen_name') ), $crumbs, $this->_convert_special_chars($query->row('screen_name') ));
			}
			
			if ($this->EE->uri->segment(3+$this->seg_addition) == 'memberlist')
			{
				return $this->_build_crumbs($this->EE->lang->line('mbr_memberlist'), $crumbs, $this->EE->lang->line('mbr_memberlist'));
			}
			elseif ($this->EE->uri->segment(3+$this->seg_addition) == 'member_search')
			{
				return $this->_build_crumbs($this->EE->lang->line('member_search'), $crumbs, $this->EE->lang->line('member_search'));
			}
		
			if ($this->EE->uri->segment(3+$this->seg_addition) != 'profile')
			{
				$crumbs .= $this->_crumb_trail(array(	
													'link' => $this->_forum_path('/'.$this->EE->config->item('profile_trigger').'/profile'), 
													'title' => $this->EE->lang->line('control_panel_home')
													)
												);
			}
			
			if (FALSE !== ($mbr_crumb = $this->EE->MBR->_fetch_member_crumb($this->EE->uri->segment(3+$this->seg_addition))))
			{
				return $this->_build_crumbs($this->EE->lang->line($mbr_crumb), $crumbs, $this->EE->lang->line($mbr_crumb));
			}
		

			if ($this->EE->uri->segment(3+$this->seg_addition) == 'messages')
			{			
				if (FALSE !== ($mbr_crumb = $this->EE->MBR->_fetch_member_crumb($this->EE->uri->segment(4+$this->seg_addition))))
				{
					return $this->_build_crumbs($this->EE->lang->line($mbr_crumb), $crumbs, $this->EE->lang->line($mbr_crumb));
				}
			}
		}		
		
			
		/** -------------------------------------
		/**  No ID?  We're done...
		/** -------------------------------------*/
		
		if ($this->current_id == '' OR ! is_numeric($this->current_id))
		{			
			return $this->_build_crumbs('', $crumbs, $this->EE->lang->line('error'));
		}
				
				
		/** -------------------------------------
		/**  Is this a category view?
		/** -------------------------------------*/
		
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
		
		/** -------------------------------------
		/**  Is this a forum view?
		/** -------------------------------------*/
		
		if ($request == 'viewforum')
		{
			if (FALSE !== ($meta = $this->_fetch_forum_metadata($this->current_id)))
			{
				$pid	= $meta[$this->current_id]['forum_parent'];
				$meta2	= $this->_fetch_forum_metadata($pid);
				
				$crumbs .= $this->_crumb_trail(
												array(	
													'link' => $this->_forum_path('/viewcategory/'.$pid.'/'), 
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
		
		/** -------------------------------------
		/**  Is this the thread view?
		/** -------------------------------------*/
	
		if ($request == 'viewthread' OR $request == 'split' OR $request == 'merge')
		{
			if (FALSE !== ($meta = $this->_fetch_topic_metadata($this->current_id)))
			{
				$pid 	= $meta[$this->current_id]['forum_parent'];				
				$meta2 = $this->_fetch_forum_metadata($pid);
																	
				$crumbs .= $this->_crumb_trail(
												array(
														'link' => $this->_forum_path('/viewcategory/'.$meta[$this->current_id]['forum_parent'].'/'), 
														'title' => $meta2[$pid]['forum_name']
													)
												);
				
				$crumbs .= $this->_crumb_trail(
												array(
														'link' => $this->_forum_path('/viewforum/'.$meta[$this->current_id]['forum_id'].'/'), 
														'title' => $meta[$this->current_id]['forum_name']
													)
												);
				
				
				if ($request == 'split' OR $request == 'merge')
				{
					$crumbs .= $this->_crumb_trail(
													array(
															'link' => $this->_forum_path('/viewthread/'.$this->current_id.'/'), 
															'title' => $this->EE->lang->line('thread')
														)
													);				
					$page = $this->EE->lang->line($request);
				}
				else
				{
					$page = $this->EE->lang->line('thread');
				}

				return $this->_build_crumbs(
												$meta[$this->current_id]['title'],
												$crumbs,
												$page
											);	
			}
		}
		
		/** -------------------------------------
		/**  Is this the announce view?
		/** -------------------------------------*/
		
		if ($request == 'viewannounce')
		{
			if (FALSE !== ($meta = $this->_fetch_forum_metadata($this->announce_id)))
			{			
				$pid 	= $meta[$this->announce_id]['forum_parent'];
				$meta2 = $this->_fetch_forum_metadata($pid);
				$meta3 = $this->_fetch_topic_metadata($this->current_id);

				$crumbs .= $this->_crumb_trail(
												array(
														'link' => $this->_forum_path('/viewcategory/'.$meta[$this->announce_id]['forum_parent'].'/'), 
														'title' => $meta2[$pid]['forum_name']
													)
												);
				
				$crumbs .= $this->_crumb_trail(
												array(
														'link' => $this->_forum_path('/viewforum/'.$meta[$this->announce_id]['forum_id'].'/'), 
														'title' => $meta[$this->announce_id]['forum_name']
													)
												);
				
				return $this->_build_crumbs(
												$meta3[$this->current_id]['title'],
												$crumbs,
												$this->EE->lang->line('thread')
											);					
			}
		}
		
		/** -------------------------------------
		/**  Is this the submission page view?
		/** -------------------------------------*/
		
		if ($request == 'newtopic')
		{
			if (FALSE !== ($meta = $this->_fetch_forum_metadata($this->current_id)))
			{ 
				$pid = $meta[$this->current_id]['forum_parent'];
				$meta2 = $this->_fetch_forum_metadata($pid);
				
				$crumbs .= $this->_crumb_trail(
												array(
														'link'	=> $this->_forum_path('/viewcategory/'.$meta[$this->current_id]['forum_parent'].'/'),
														'title' => $meta2[$pid]['forum_name']
													)
												);
				
				$crumbs .= $this->_crumb_trail(
												array(
														'link'	=> $this->_forum_path('/viewforum/'.$this->current_id.'/'), 
														'title'	=> $meta[$this->current_id]['forum_name']
													)
												);
				
				return $this->_build_crumbs(
												$this->EE->lang->line('post_new_topic'),
												$crumbs,
												$this->EE->lang->line('post_new_topic')
											);
			}
		}	
		
		/** -------------------------------------
		/**  Is this one of the post submission pages?
		/** -------------------------------------*/
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
														'link'	=> $this->_forum_path('/viewcategory/'.$meta[$this->current_id]['forum_parent'].'/'), 
														'title'	=> $meta2[$pid]['forum_name']
													)
												);
				
				$crumbs .= $this->_crumb_trail(

												array(
														'link'	=> $this->_forum_path('/viewforum/'.$meta[$this->current_id]['forum_id'].'/'),
														'title'	=> $meta[$this->current_id]['forum_name']
													)
												);
				
				$thread_id = (stristr($request, 'reply') AND $request != 'newreply' && $request != 'quotereply') ?  $meta[$this->current_id]['topic_id'] : $this->current_id;

				$crumbs .= $this->_crumb_trail(
												array(
														'link'	=> $this->_forum_path('/viewthread/'.$thread_id.'/'),
														'title'	=> $this->EE->lang->line('thread')
													)
												);
				
				return $this->_build_crumbs(
												$this->EE->lang->line($type[$request]),
												$crumbs,
												$this->EE->lang->line($type[$request])
											);
			}
		}
				
		/** -------------------------------------
		/**  Generate Error bread-crumb
		/** -------------------------------------*/
		
		// If we got this far it means we don't have a valid page
		// so we'll show a basic error crumb

		return $this->_build_crumbs('', $crumbs, $this->EE->lang->line('error'));
	}

	// --------------------------------------------------------------------	
	
	/** -------------------------------------
	/**  Sets the title of the page
	/** -------------------------------------*/
	function _set_page_title($title)
	{
		if ($this->current_page_name == '')
		{
			$this->current_page_name = $title;
		}
	}

	// --------------------------------------------------------------------
	
	/** -------------------------------------
	/**  Breadcrumb trail links
	/** -------------------------------------*/
	function _crumb_trail($data)
	{
		$trail	= $this->_load_element('breadcrumb_trail');

		$crumbs = '';

		$crumbs .= $this->_var_swap($trail,
									array(
											'crumb_link'	=> $data['link'], 
											'crumb_title'	=> $this->_convert_special_chars($data['title'])
											)
									);		
		return $crumbs;
	}

	// --------------------------------------------------------------------	
	
	/** -------------------------------------
	/**  Theme Option List
	/** -------------------------------------*/
	
	function theme_option_list()
	{
		// Load the XML Helper
		$this->EE->load->helper('xml');
										
		$path = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->EE->functions->fetch_action_id('Forum', 'set_theme').'&board_id='.$this->_fetch_pref('original_board_id').'&theme=';
		
		$str = '';
		foreach ($this->fetch_theme_list() as $val)
		{
			$sel = ($this->theme == $val) ? ' selected="selected"' : '';
		
			$str .= '<option value="'.xml_convert($path.$val).'"'.$sel.'>'.ucwords(str_replace('_', ' ', $val))."</option>\n";
		}
		
		return $str;
	}

	// --------------------------------------------------------------------	
	
	/** -------------------------------------
	/**  Set the theme
	/** -------------------------------------*/
	function set_theme()
	{
		$theme = $this->EE->input->get('theme');

		if ( ! preg_match("/^[a-z0-9\s_-]+$/i", $theme))
		{ 
			exit('Forum themes may only contain alpha-numeric characters');
		}
	
		// If the user is logged in we'll update their member table
		if ($this->EE->session->userdata('member_id') != 0)
		{
			$this->EE->db->where('member_id', $this->EE->session->userdata('member_id'));
			$this->EE->db->update('members', array('forum_theme' => $theme));
		}

		$this->_load_preferences();
		$this->trigger = $this->_fetch_pref('board_forum_trigger');
		$this->_forum_set_basepath();
		
		// Set a cookie!
		$expire = 60*60*24*365;
		$this->EE->functions->set_cookie('forum_theme', $theme, $expire);

		if (isset($this->EE->session->tracker[0]))
		{	
			$return = ($this->_fetch_pref('board_forum_trigger') != '') ? str_replace($this->_fetch_pref('board_forum_trigger'), '', $this->EE->session->tracker[0]) : $this->EE->session->tracker[0];


			$this->EE->functions->redirect($this->_forum_path($return));
		}

		$this->EE->functions->redirect($this->_forum_path());
	}

	// --------------------------------------------------------------------	
	
	/** -------------------------------------
	/**  Fetch installed themes
	/** -------------------------------------*/
	
	function fetch_theme_list()
	{	
		$filelist = array();
	
		if ($fp = @opendir($this->_fetch_pref('board_theme_path'))) 
		{ 
			while (false !== ($file = readdir($fp))) 
			{ 
				if (is_dir($this->_fetch_pref('board_theme_path').$file) AND substr($file, 0, 1) != '.' AND substr($file, 0, 1) != '_')
				{	
					$filelist[] = $file;
				}
			} 
			
			closedir($fp); 
		} 
		
		return $filelist;
	}

	// --------------------------------------------------------------------

	/** -------------------------------------
	/**  Private Message Box in header
	/** -------------------------------------*/
	
	function private_message_box()
	{
		$str = $this->_load_element('private_message_box');
		
		$pms = $this->EE->session->userdata('private_messages');
				
		if ($pms == '' OR ! is_numeric($pms))
		{
			$pms = 0;
		}
		
		if ($pms > 0)
		{
			$str = $this->_allow_if('private_messages', $str);
			$str = $this->_deny_if('no_private_messages', $str);
		}
		else
		{
			$str = $this->_deny_if('private_messages', $str);
			$str = $this->_allow_if('no_private_messages', $str);
		}
		
		return $this->_var_swap($str,
								array(
										'total_unread_private_messages' => $pms
									)
								);
	}

	// --------------------------------------------------------------------
	
	/** -----------------------------------------
	/**  Base IFRAME for Spell Check
	/** -----------------------------------------*/
	/**
	 */
	function spellcheck_iframe()
	{
		if (isset($this->EE->session->tracker[0]) && substr($this->EE->session->tracker[0], -17) == 'spellcheck_iframe')
		{
			array_shift($this->EE->session->tracker);

			$this->EE->functions->set_cookie('tracker', serialize($this->EE->session->tracker), '0');
		}
		
		if ( ! class_exists('EE_Spellcheck'))
		{
			require APPPATH.'libraries/Spellcheck'.EXT; 
		}

		return EE_Spellcheck::iframe();
	}

	// --------------------------------------------------------------------	
	
	/** -----------------------------------------
	/**  Spell Check for Textareas
	/** -----------------------------------------*/
	function spellcheck()
	{
		if ( ! class_exists('EE_Spellcheck'))
		{
			require APPPATH.'libraries/Spellcheck'.EXT; 
		}
		
		return EE_Spellcheck::check();
	}

	// --------------------------------------------------------------------

	/** --------------------------------
	/**  SpellCheck - JS
	/** --------------------------------*/
	
	function spellcheck_js()
	{
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
		
		return $this->SPELL->JavaScript($this->_forum_path('/spellcheck/'), TRUE);
	}

	// --------------------------------------------------------------------
	
	/** -------------------------------------
	/**  Parse PHP in template
	/** -------------------------------------*/
	 function parse_template_php($str)
	 {
	 	$str = preg_replace("/\<\?xml(.+?)\?\>/", "<XXML\\1/XXML>", $str);
	 	
		ob_start();

		echo $this->EE->functions->evaluate($str);
		
		$str = ob_get_contents();
		
		ob_end_clean(); 
		
		$str = preg_replace("/\<XXML(.+?)\/XXML\>/", "<?xml\\1?>", $str); // <?
		
		$this->parse_php = FALSE;
		
		return $str;
	 }

	// -------------------------------------------------------------------- 

	/** -------------------------------------------------
	/**  Removes slashes from array
	/** -------------------------------------------------*/
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

/* End of file mod.forum.php */
/* Location: ./system/expressionengine/modules/forum/mod.forum.php */