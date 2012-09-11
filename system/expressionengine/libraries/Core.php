<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Config Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://expressionengine.com
 */
class EE_Core {
	
	var $native_modules		= array();		// List of native modules with EE
	var $native_plugins		= array();		// List of native plugins with EE

	/**
	 * Constructor
	 */	
	function __construct()
	{
		// Call initialize to do the heavy lifting
		$this->_initialize_core();
	}

	// --------------------------------------------------------------------

	/**
	 * Initialize EE
	 *
	 * @access	private
	 * @return	void
	 */
	function _initialize_core()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		
		// Yes, this is silly. No it won't work without it.
		// For some reason PHP won't bind the reference
		// for core to the super object quickly enough.
		// Breaks access to core in the menu lib.
		$this->EE->core = $this;
		
		// Define the request type
		// Note: admin.php defines REQ=CP
		if ( ! defined('REQ'))
		{
			define('REQ', (($this->EE->input->get_post('ACT') !== FALSE) ? 'ACTION' : 'PAGE')); 
		}
		
		// some path constants to simplify things
		define('PATH_MOD',		APPPATH.'modules/');
		define('PATH_PI',		APPPATH.'plugins/');
		define('PATH_EXT',		APPPATH.'extensions/');
		define('PATH_ACC',		APPPATH.'accessories/');
		define('PATH_FT',		APPPATH.'fieldtypes/');
		define('PATH_RTE',		APPPATH.'rte_tools/');
		if ($this->EE->config->item('third_party_path'))
		{
			define(
				'PATH_THIRD',
				rtrim(realpath($this->EE->config->item('third_party_path')), '/').'/'
			);
		}
		else
		{
			define('PATH_THIRD',	APPPATH.'third_party/');
		}
		
		// application constants
		define('IS_FREELANCER',	FALSE);
		define('APP_NAME',		'ExpressionEngine'.(IS_FREELANCER ? ' Freelancer' : ''));
		define('APP_BUILD',		'20120911');
		define('APP_VER',		'2.5.3');
		define('SLASH',			'&#47;');
		define('LD',			'{');
		define('RD',			'}');
		define('AMP',			'&amp;');
		define('NBS', 			'&nbsp;');
		define('BR', 			'<br />');
		define('NL',			"\n");
		define('PATH_DICT', 	APPPATH.'config/');
		define('AJAX_REQUEST',	$this->EE->input->is_ajax_request());

		$this->native_plugins = array('magpie', 'xml_encode');
		$this->native_modules = array(
			'blacklist', 'channel', 'comment', 'commerce', 'email', 'emoticon',
			'file', 'forum', 'ip_to_nation', 'jquery', 'mailinglist', 'member',
			'metaweblog_api', 'moblog', 'pages', 'query', 'referrer', 'rss', 'rte',
			'safecracker', 'search', 'simple_commerce', 'stats',
			'updated_sites', 'wiki'
		);
		
		
		// Set a liberal script execution time limit, making it shorter for front-end requests than CI's default
		if (function_exists("set_time_limit") == TRUE AND @ini_get("safe_mode") == 0)
		{
			@set_time_limit((REQ == 'CP') ? 300 : 90);
		}
		
		// Load DB and set DB preferences
		$this->EE->load->database();
		$this->EE->db->swap_pre 	= 'exp_';
		$this->EE->db->db_debug 	= FALSE;
	
		// Note enable_db_caching is a per site setting specified in EE_Config.php
		// If debug is on we enable the profiler and DB debug
		if (DEBUG == 1 OR $this->EE->config->item('debug') == 2)
		{
			$this->_enable_debugging();
		}
		
		// Assign Site prefs now that the DB is fully loaded
		if ($this->EE->config->item('site_name') != '')
		{
			$this->EE->config->set_item('site_name', preg_replace('/[^a-z0-9\-\_]/i', '', $this->EE->config->item('site_name')));
		}
							
		$this->EE->config->site_prefs($this->EE->config->item('site_name'));
		
		// force EE's db cache path - do this AFTER site prefs have been assigned
		// Due to CI's DB_cache handling- suffix with site id
		$this->EE->db->cache_set_path(APPPATH.'cache/db_cache_'.$this->EE->config->item('site_id'));

		// make sure the DB cache folder exists if we're caching!
		if ($this->EE->db->cache_on === TRUE && 
			! @is_dir(APPPATH.'cache/db_cache_'.$this->EE->config->item('site_id')))
		{
			@mkdir(APPPATH.'cache/db_cache_'.$this->EE->config->item('site_id'), DIR_WRITE_MODE);

			if ($fp = @fopen(APPPATH.'cache/db_cache_'.$this->EE->config->item('site_id').'/index.html', FOPEN_WRITE_CREATE_DESTRUCTIVE))
			{
				fclose($fp);
			}

			@chmod(APPPATH.'cache/db_cache_'.$this->EE->config->item('site_id'), DIR_WRITE_MODE);
		}
		
		// this look backwards, but QUERY_MARKER is only used where we MUST 
		// have a ?, and do not want to double up
		// question marks on sites who are forcing query strings
		define('QUERY_MARKER', ($this->EE->config->item('force_query_string') == 'y') ? '' : '?');
		
		// Load the settings of the site you're logged into, however use the 
		// cookie settings from the site that corresponds to the URL
		// e.g. site1.com/system/ viewing site2
		// $last_site_id = the site that you're viewing
		// config->item('site_id') = the site who's URL is being used
		
		$last_site_id = $this->EE->input->cookie('cp_last_site_id');
		
		if (REQ == 'CP' && ! empty($last_site_id) && is_numeric($last_site_id) &&
			$last_site_id != $this->EE->config->item('site_id'))
		{
			// If they are already setting cookies with a specified domain, keep using it in this backend
			$current_cookie_domain = $this->EE->config->item('cookie_domain');

			$this->EE->config->site_prefs('', $last_site_id);

			if ($current_cookie_domain != FALSE && $current_cookie_domain != '')
			{
				$this->EE->config->cp_cookie_domain = $current_cookie_domain;
			}
		}
		
		// This allows CI compatibility
		if ($this->EE->config->item('base_url') == FALSE)
		{
			$this->EE->config->set_item('base_url', $this->EE->config->item('site_url'));
		}

		if ($this->EE->config->item('index_page') == FALSE)
		{
			$this->EE->config->set_item('index_page', $this->EE->config->item('site_index'));
		}
			
		// Set the path to the "themes" folder
		if ($this->EE->config->item('theme_folder_path') !== FALSE && 
			$this->EE->config->item('theme_folder_path') != '')
		{
			$theme_path = preg_replace("#/+#", "/", $this->EE->config->item('theme_folder_path').'/');
		}
		else
		{
			$theme_path = substr(APPPATH, 0, - strlen(SYSDIR.'/expressionengine/')).'themes/';
			$theme_path = preg_replace("#/+#", "/", $theme_path);
		}

		// Maybe the site has been moved.  
		// Let's try some basic autodiscovery if config items are set
		// But the directory does not exist.  
		if ( ! is_dir($theme_path))
		{
			if (is_dir(FCPATH.'../themes/')) // We're in the system directory
			{
				$theme_path = FCPATH.'../themes/';
			}
			elseif (is_dir(FCPATH.'themes/')) // Front end.
			{
				$theme_path = FCPATH.'themes/';
			}
		}
		
		define('PATH_THEMES', 		$theme_path);	
		define('PATH_MBR_THEMES',	PATH_THEMES.'profile_themes/'); 
		define('PATH_CP_GBL_IMG', 	$this->EE->config->slash_item('theme_folder_url').'cp_global_images/');
		unset($theme_path);
		
		// Define Third Party Theme Path and URL
		if ($this->EE->config->item('path_third_themes'))
		{
			define(
				'PATH_THIRD_THEMES',
				rtrim(realpath($this->EE->config->item('path_third_themes')), '/').'/'
			);
		}
		else
		{
			define('PATH_THIRD_THEMES',	PATH_THEMES.'third_party/');
		}
		
		if ($this->EE->config->item('url_third_themes'))
		{
			define(
				'URL_THIRD_THEMES',
				rtrim($this->EE->config->item('url_third_themes'), '/').'/'
			);
		}
		else
		{
			define('URL_THIRD_THEMES',	$this->EE->config->slash_item('theme_folder_url').'third_party/');
		}
		
		// Is this a stylesheet request?  If so, we're done.
		if (isset($_GET['css']) OR (isset($_GET['ACT']) && $_GET['ACT'] == 'css')) 
		{
			$this->EE->load->library('stylesheet');
			$this->EE->stylesheet->request_css_template();
			exit;
		}

		// Throttle and Blacklist Check
		if (REQ != 'CP')
		{
			$this->EE->load->library('throttling');
			$this->EE->throttling->run();

			$this->EE->load->library('blacklist');
			$this->EE->blacklist->_check_blacklist();

			$this->EE->load->library('file_integrity');
			$this->EE->file_integrity->create_bootstrap_checksum();
		}

		// Load the remaining base classes
		$this->EE->load->library('functions');
		$this->EE->load->library('extensions');
		
		if (function_exists('date_default_timezone_set'))
		{
			date_default_timezone_set(date_default_timezone_get());
		}
		
		$this->EE->load->library('remember');
		$this->EE->load->library('localize');
		$this->EE->load->library('session');

		// Load the "core" language file - must happen after the session is loaded
		$this->EE->lang->loadfile('core');

		// Compat helper, for those times where php doesn't quite cut it
		$this->EE->load->helper('compat');

		// Now that we have a session we'll enable debugging if the user is a super admin
		if ($this->EE->config->item('debug') == 1 AND $this->EE->session->userdata('group_id') == 1)
		{
			$this->_enable_debugging();
		}
		
		if ($this->EE->session->userdata('group_id') == 1 && $this->EE->config->item('show_profiler') == 'y')
		{
			$this->EE->output->enable_profiler(TRUE);
		}
	
		/*
		 * -----------------------------------------------------------------
		 *  Filter GET Data
		 * 		We've preprocessed global data earlier, but since we did
		 * 		not have a session yet, we were not able to determine
		 * 		a condition for filtering
		 * -----------------------------------------------------------------
		 */

		$this->EE->input->filter_get_data(REQ);
		
		// Update system stats
		$this->EE->load->library('stats');
	 	
		if (REQ == 'PAGE' && $this->EE->config->item('enable_online_user_tracking') != 'n')
		{
			$this->EE->stats->update_stats();
		}
		
		// Load up any Snippets
		if (REQ == 'ACTION' OR REQ == 'PAGE')
		{
			// load up any Snippets
			$this->EE->db->select('snippet_name, snippet_contents');
			$this->EE->db->where('(site_id = '.$this->EE->db->escape_str($this->EE->config->item('site_id')).' OR site_id = 0)');
			$fresh = $this->EE->db->get('snippets');

			if ($fresh->num_rows() > 0)
			{
				$snippets = array();

				foreach ($fresh->result() as $var)
				{
					$snippets[$var->snippet_name] = $var->snippet_contents;
				}

				// Thanks to @litzinger for the code suggestion to parse 
				// global vars in snippets...here we go.

				$var_keys = array();

				foreach ($this->EE->config->_global_vars as $k => $v)
				{
					$var_keys[] = LD.$k.RD;
				}

				$snippets = str_replace($var_keys, $this->EE->config->_global_vars, $snippets);

				$this->EE->config->_global_vars = $this->EE->config->_global_vars + $snippets; 

				unset($snippets);
				unset($fresh);
				unset($var_keys);
			}
		}
		
		// If it's a CP request we will initialize it
		if (REQ == 'CP')
		{
			$this->_initialize_cp();
		}
	}	

	// ------------------------------------------------------------------------
	
	/**
	 * Generate Control Panel Request
	 *
	 * @access	private
	 * @return	void
	 */	
	function _initialize_cp()
	{
		$s = 0;
		
		if ($this->EE->config->item('admin_session_type') != 'c')
		{
			$s = $this->EE->session->userdata('session_id', 0);
		}
		
		define('BASE', SELF.'?S='.$s.'&amp;D=cp');			// cp url
		define('PATH_CP_THEME', PATH_THEMES.'cp_themes/');	// theme path
		
		// Show the control panel home page in the event that a
		// controller class isn't found in the URL
		if ($this->EE->router->fetch_class() == 'ee' OR
			$this->EE->router->fetch_class() == '')
		{
			$this->EE->functions->redirect(BASE.'&C=homepage');
		}

		// load the user agent lib to check for mobile
		$this->EE->load->library('user_agent');
		
		/* -------------------------------------------
		/*	Hidden Configuration Variable
		/*	- use_mobile_control_panel => Automatically use mobile cp theme when accessed with a mobile device? (y/n)
		/* -------------------------------------------*/
		
		$cp_theme		= 'default';
		$theme_options	= array();
		
		if ($this->EE->agent->is_mobile() && $this->EE->config->item('use_mobile_control_panel') != 'n')
		{
			// iphone, ipod, blackberry, palm, etc.
			$agent = array_search($this->EE->agent->mobile(), $this->EE->agent->mobiles);
			$agent = $this->EE->security->sanitize_filename($agent);
			
			$theme_options = array('mobile_'.$agent, 'mobile');
		}
		else
		{
			$theme_options = array(
				$this->EE->session->userdata('cp_theme'),
				$this->EE->config->item('cp_theme')
			);

			if (count($theme_options) >= 2)
			{
				if ( ! $theme_options[0])
				{
					unset($theme_options[0]);
				}
			}
		}

		// Try them all, if none work it'll use default
		foreach ($theme_options as $_theme_name)
		{
			if (is_dir(PATH_CP_THEME.$_theme_name))
			{
				$cp_theme = $_theme_name;
				break;
			}
		}
		
		// Load our view library
		$this->EE->load->library('view');
		$this->EE->view->set_cp_theme($cp_theme);
		
		// Fetch control panel language file
		$this->EE->lang->loadfile('cp');
		
		// Prevent CodeIgniter Pseudo Output variables from being parsed
		$this->EE->output->parse_exec_vars = FALSE;

		/** ------------------------------------ 
		/**  Instantiate Admin Log Class
		/** ------------------------------------*/

		$this->EE->load->library('logger');
		$this->EE->load->library('cp');

		// Does an admin session exist?
		// Only the "login" class can be accessed when there isn't an admin session
		if ($this->EE->session->userdata('admin_sess') == 0 &&
			$this->EE->router->fetch_class() != 'login' &&
			$this->EE->router->fetch_class() != 'css')
		{
			// has their session Timed out and they are requesting a page?
			// Grab the URL, base64_encode it and send them to the login screen.
			
			$safe_refresh = $this->EE->cp->get_safe_refresh();
			$return_url = ($safe_refresh == 'C=homepage') ? '' : AMP.'return='.base64_encode($safe_refresh);
			
			$this->EE->functions->redirect(BASE.AMP.'C=login'.$return_url);
		}
		
		// Is the user banned?
		// Before rendering the full control panel we'll make sure the user isn't banned
		// But only if they are not a Super Admin, as they can not be banned
		if ($this->EE->session->userdata('group_id') != 1 AND $this->EE->session->ban_check('ip'))
		{
			return $this->EE->output->fatal_error(lang('not_authorized'));
		}
		
		// Request to our css controller don't need any
		// of the expensive prep work below
		if ($this->EE->router->class == 'css' OR $this->EE->router->class == 'javascript')
		{
			return;
		}
		
		// Load common helper files
		$this->EE->load->helper(array('url', 'form', 'quicktab'));

		// Secure forms stuff
		$this->EE->cp->secure_forms();
		
		// Certain variables will be included in every page, so we make sure they are set here
		// Prevents possible PHP errors, if a developer forgets to set it explicitly.
		$this->EE->cp->set_default_view_variables();
		
		// Load the Super Model
		$this->EE->load->model('super_model');
		
		// update documentation URL if site was running the beta and had the old location
		// @todo remove after 2.1.1's release, move to the update script
		if (strncmp($this->EE->config->item('doc_url'), 'http://expressionengine.com/docs', 32) == 0)
		{
			$this->EE->config->update_site_prefs(array('doc_url' => 'http://expressionengine.com/user_guide/'));
		}
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Enable Debugging
	 *
	 * @access	private
	 * @return	void
	 */	
	function _enable_debugging()
	{
		$this->EE->db->db_debug = TRUE;
		error_reporting(E_ALL);
		@ini_set('display_errors', 1);
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Generate Page Request
	 *
	 * @access	public 
	 * @return	void
	 */	
	final public function generate_action($can_view_system = FALSE)
	{
		require APPPATH.'libraries/Actions.php';
		$ACT = new EE_Actions($can_view_system);
	}	
	
	// ------------------------------------------------------------------------
	
	/**
	 * Generate Page Request
	 *
	 * @access	public
	 * @return	void
	 */	
	final public function generate_page()
	{
		// Legacy, unsupported, but still more or less functional
		// Templates and Template Groups can be hard-coded
		// in $assign_to_config in the bootstrap file
		$template = '';
		$template_group = '';
		
		if ($this->EE->uri->uri_string == '' OR $this->EE->uri->uri_string == '/')
		{
			$template = (string) $this->EE->config->item('template');
			$template_group = (string) $this->EE->config->item('template_group');
		}
		
		
		// If the forum module is installed and the URI contains the "triggering" word
		// we will override the template parsing class and call the forum class directly.
		// This permits the forum to be more light-weight as the template engine is 
		// not needed under normal circumstances.
		$forum_trigger = ($this->EE->config->item('forum_is_installed') == "y") ? $this->EE->config->item('forum_trigger') : '';
		$profile_trigger = $this->EE->config->item('profile_trigger');
		
		
		if ( ! IS_FREELANCER && $forum_trigger && 
			in_array($this->EE->uri->segment(1), preg_split('/\|/', $forum_trigger, -1, PREG_SPLIT_NO_EMPTY)))
		{
			require PATH_MOD.'forum/mod.forum.php';
			$FRM = new Forum();
			return;
		}
		
		if ( ! IS_FREELANCER && $profile_trigger && $profile_trigger == $this->EE->uri->segment(1))
		{
			// We do the same thing with the member profile area.  
		
			if ( ! file_exists(PATH_MOD.'member/mod.member.php'))
			{
				exit();
			}
			
			require PATH_MOD.'member/mod.member.php';
			
			$member = new Member();
			$member->_set_properties(array('trigger' => $profile_trigger));
			
			$this->EE->output->set_output($member->manager());
			return;
		}
		
		// -------------------------------------------
		// 'core_template_route' hook.
		//  - Reassign the template group and template loaded for parsing
		//
			if ($this->EE->extensions->active_hook('core_template_route') === TRUE)
			{
				$edata = $this->EE->extensions->call('core_template_route', $this->EE->uri->uri_string);
				if (is_array($edata) && count($edata) == 2)
				{
					list($template_group, $template) = $edata;
				}
			}
		//
		// -------------------------------------------

		// Look for a page in the pages module
		if ($template_group == '' && $template == '')
		{
			$pages		= $this->EE->config->item('site_pages');
			$site_id	= $this->EE->config->item('site_id');
			$entry_id	= FALSE;
			
			// If we have pages, we'll look for an entry id
			if ($pages && isset($pages[$site_id]['uris']))
			{
				$match_uri = '/'.trim($this->EE->uri->uri_string, '/');	// will result in '/' if uri_string is blank
				$page_uris = $pages[$site_id]['uris'];
				
				// trim page uris in case there's a trailing slash on any of them
				foreach ($page_uris as $index => $value)
				{
					$page_uris[$index] = '/'.trim($value, '/');
				}

				// case insensitive URI comparison
				$entry_id = array_search(strtolower($match_uri), array_map('strtolower', $page_uris));
				
				if ( ! $entry_id AND $match_uri != '/')
				{
					$entry_id = array_search($match_uri.'/', $page_uris);
				}
			}
			
			// Found an entry - grab related template
			if ($entry_id)
			{
				$qry = $this->EE->db->select('t.template_name, tg.group_name')
					->from(array('templates t', 'template_groups tg'))
					->where('t.group_id', 'tg.group_id', FALSE)
					->where('t.template_id', $pages[$site_id]['templates'][$entry_id])
					->get();
				
				if ($qry->num_rows() > 0)
				{
					/* 
						We do it this way so that we are not messing with 
						any of the segment variables, which should reflect 
						the actual URL and not our Pages redirect. We also
						set a new QSTR variable so that we are not 
						interfering with other module's besides the Channel 
						module (which will use the new Pages_QSTR when available).
					*/
					$template = $qry->row('template_name');
					$template_group = $qry->row('group_name');
					$this->EE->uri->page_query_string = $entry_id;
				}
			}
		}

		$this->EE->load->library('template', NULL, 'TMPL');

		// Parse the template
		$this->EE->TMPL->run_template_engine($template_group, $template);
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Garbage Collection
	 *
	 * Every 7 days we'll run our garbage collection
	 *
	 * @access	private
	 * @return	void
	 */	
	function _garbage_collection()
	{	
		$this->EE->db->cache_off();
	
		if (class_exists('Stats'))
		{
			if ($this->EE->stats->statdata('last_cache_clear') 
				&& $this->EE->stats->statdata('last_cache_clear') > 1)
			{
				$last_clear = $this->EE->stats->statdata('last_cache_clear');
			}
		}
	
		if ( ! isset($last_clear))
		{
			$this->EE->db->select('last_cache_clear');
			$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
			$query = $this->EE->db->get('stats');

			$last_clear = $query->row('last_cache_clear') ;
		}
		
		if (isset($last_clear) && $this->EE->localize->now > $last_clear)
		{
			$data = array(
				'last_cache_clear'	=> $this->EE->localize->now + (60*60*24*7)
			);

			$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
			$this->EE->db->update('stats', $data);
			
			if ($this->EE->config->item('enable_throttling') == 'y')
			{
				$expire = time() - 180;
				
				$this->EE->db->where('last_activity <', $expire);
				$this->EE->db->delete('throttle');
			}
	
			$this->EE->functions->clear_spam_hashes();
			$this->EE->functions->clear_caching('all');
		}
	}	
}

/* End of file Core.php */
/* Location: ./system/expressionengine/libraries/Core.php */
