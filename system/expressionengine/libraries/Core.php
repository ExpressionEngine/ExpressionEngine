<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Config Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class EE_Core {

	var $native_modules		= array();		// List of native modules with EE
	var $native_plugins		= array();		// List of native plugins with EE

	/**
	 * Constructor
	 *
	 */	
	function EE_Core()
	{
		// We initialize the Core library either from a second
		// autoloaded library, or from the EE controller, depending on if it's
		// a front end or control panel request.  This solves a scope issue
		// for code that would otherwise be ran from the Core constructor
		$CI =& get_instance();
		
		// system/index.php defines REQ='CP', so we only
		// deal with action and page requests here
		if ( ! defined('REQ'))	
		{
			define('REQ', (($CI->input->get_post('ACT') !== FALSE) ? 'ACTION' : 'PAGE')); 
		}
		
		// Lots of popular js libs send this header,
		// including jQuery - use it!
		$req_source = $CI->input->server('HTTP_X_REQUESTED_WITH');
		define('AJAX_REQUEST', ($req_source == 'XMLHttpRequest') ? TRUE : FALSE);
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
		
		$app_version = $this->EE->config->item('app_version');
				
		// some path constants to simplify things
		define('PATH_MOD',		APPPATH.'modules/');
		define('PATH_PI',		APPPATH.'plugins/');
		define('PATH_EXT',		APPPATH.'extensions/');
		define('PATH_ACC',		APPPATH.'accessories/');
		define('PATH_FT',		APPPATH.'fieldtypes/');
		define('PATH_THIRD',	APPPATH.'third_party/');
				
		// application constants
		define('IS_FREELANCER',	FALSE);
		define('APP_NAME',		'ExpressionEngine'.((IS_FREELANCER) ? ' Freelancer' : ''));
		define('APP_BUILD',		'20100101');
		define('APP_VER',		$app_version[0].'.'.$app_version[1].'.'.substr($app_version, 2));
		define('SLASH',			'&#47;');
		define('LD',			'{');
		define('RD',			'}');
		define('AMP',			'&amp;');
		define('NBS', 			'&nbsp;');
		define('BR', 			'<br />');
		define('NL',			"\n");
		define('PATH_DICT', 	APPPATH.'config/');
		
		$this->native_modules = array(
			'blacklist', 'blogger_api', 'channel', 'comment', 'commerce', 'email', 'emoticon',
			'forum', 'gallery', 'ip_to_nation', 'jquery', 'mailinglist', 'member', 'metaweblog_api',
			'moblog', 'pages', 'query', 'referrer', 'rss', 'search', 'simple_commerce', 'stats', 
			'updated_sites', 'wiki'
		);
									  
		$this->native_plugins = array('magpie', 'xml_encode');


		// Set a liberal script execution time limit, making it shorter
		// for front-end requests than CI's default
		if (function_exists("set_time_limit") && @ini_get("safe_mode") == 0)
		{
			if (REQ == 'CP')
			{
				@set_time_limit(300);
			}
			else
			{
				@set_time_limit(90);
			}
		}
		

		$this->EE->load->library('security');
		$this->EE->load->database();
		
		// Set db preferences
		// Note: enable_db_caching is a per site setting specified in EE_Config.php
		$this->EE->db->swap_pre 	= 'exp_';
		$this->EE->db->db_debug 	= FALSE;
		
		
		// Enable the profiler and show db queries
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
		$db_cache_path = APPPATH.'cache/db_cache_'.$this->EE->config->item('site_id');
		$this->EE->db->cache_set_path($db_cache_path);

		// make sure the DB cache folder exists if we're caching!
		if ($this->EE->db->cache_on === TRUE && ! @is_dir($db_cache_path))
		{
			@mkdir($db_cache_path, DIR_WRITE_MODE);

			if ($fp = @fopen($db_cache_path.'/index.html', FOPEN_WRITE_CREATE_DESTRUCTIVE))
			{
				fclose($fp);
			}

			@chmod($db_cache_path, DIR_WRITE_MODE);
		}
		
		unset($db_cache_path);
		
		
		
		$last_site_id = $this->EE->input->cookie('cp_last_site_id');

		if (REQ == 'CP' && ! empty($last_site_id) && is_numeric($last_site_id) && $last_site_id != $this->EE->config->item('site_id'))
		{
			$this->EE->config->site_prefs('', $last_site_id);
		}
		
		/*
		 * -------------------------------------------------------------------
		 *  This allows CI compatibility
		 * -------------------------------------------------------------------
		 */
		 
		 if ( ! $this->EE->config->item('base_url'))
		 {
		 	$this->EE->config->set_item('base_url', $this->EE->config->item('site_url'));
		 }
		 
		 if ( ! $this->EE->config->item('index_page'))
		 {
		 	$this->EE->config->set_item('index_page', $this->EE->config->item('site_index'));
		 }
		
		
		// Set the theme path, and do some e basic autodiscovery if config items are set but
		// the directory does not exist. The site may have moved.
		
		if ( ! $theme_path = $this->EE->config->item('theme_folder_path'))
		{
			$theme_path = substr(APPPATH, 0, - strlen(SYSDIR.'/expressionengine/')).'themes';
		}
		
		$theme_path = preg_replace("#/+#", "/", $theme_path.'/');
		
		
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
		
		// this looks backwards, but QUERY_MARKER is only used where we MUST have a ?, and
		// do not want to double up question marks on sites who are forcing query strings
		define('QUERY_MARKER', ($this->EE->config->item('force_query_string') == 'y') ? '' : '?');
		
		unset($theme_path);

		/*
		 * -----------------------------------------------------------------
		 *  Is this a stylesheet request?  If so, we're done.
		 * -----------------------------------------------------------------
		 */
		if (isset($_GET['css']) OR $this->EE->input->get('ACT') == 'css') 
		{
			$this->EE->load->library('stylesheet');
			$this->EE->stylesheet->request_css_template();
			exit;
		}

		/*
		 * -------------------------------------------------------------------
		 *  Throttle and Blacklist Check
		 * -------------------------------------------------------------------
		 */
		if (REQ != 'CP')
		{
			$this->EE->load->library('throttling');
			$this->EE->throttling->run();

			$this->EE->load->library('blacklist');
			$this->EE->blacklist->_check_blacklist();

			$this->EE->load->library('file_integrity');
			$this->EE->file_integrity->create_bootstrap_checksum();
		}

		/*
		 * -----------------------------------------------------------------
		 *  Load the remaining base classes
		 * -----------------------------------------------------------------
		 */
		if (function_exists('date_default_timezone_set'))
		{
			date_default_timezone_set(date_default_timezone_get());
		}
		
		$this->EE->load->library('functions');
		$this->EE->load->library('extensions');		
		$this->EE->load->library('localize');
		$this->EE->load->library('session');
		
		// Load the "core" language file - must happen after the session is loaded
		$this->EE->lang->loadfile('core');


		// Now that we have a session we'll enable
		// debugging if the user is a super admin
		
		if ($this->EE->session->userdata('group_id') == 1)
		{
			if ($this->EE->config->item('debug') == 1)
			{
				$this->_enable_debugging();
			}
			
			if ($this->EE->config->item('show_profiler') == 'y')
			{
				$this->EE->output->enable_profiler(TRUE);
			}
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
			
		/*
		 * -----------------------------------------------------------------
		 *  Update system stats
		 * -----------------------------------------------------------------
		 */
		$this->EE->load->library('stats');
	 		
		if (REQ == 'PAGE' && $this->EE->config->item('enable_online_user_tracking') != 'n')
		{
			$this->EE->stats->update_stats();
		}
		
		/*
		 * -----------------------------------------------------------------
		 *  Load up any Snippets
		 * -----------------------------------------------------------------
		 */
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

				$this->EE->config->_global_vars = $this->EE->config->_global_vars + $snippets; 

				unset($snippets);
				unset($fresh);
			}				
		}
			
		/*
		 * -----------------------------------------------------------------
		 *  If it's a CP request we will initialize it
		 * -----------------------------------------------------------------
		 */
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
		// Prevent CodeIgniter Pseudo Output variables from being parsed
		$this->EE->output->parse_exec_vars = FALSE;
		
		// We need a session ID for our BASE constant
		$s = ($this->EE->config->item('admin_session_type') != 'c') ? $this->EE->session->userdata('session_id') : 0;
		
		
		// set the CP Theme Path and the CP URL
		define('BASE',			SELF.'?S='.$s.'&amp;D=cp');
		define('PATH_CP_THEME', PATH_THEMES.'cp_themes/');
		
		
		$requested_class = $this->EE->router->fetch_class();
		
		// Show the control panel home page in the event that
		// a controller class isn't found in the URL
		if ( ! $requested_class OR $requested_class == 'ee')
		{
			$this->EE->functions->redirect(BASE.'&C=homepage');
		}


		$cp_theme = $this->EE->config->item('cp_theme');
		
		if ($this->EE->session->userdata('cp_theme'))
		{
			$cp_theme = $this->EE->session->userdata('cp_theme');	
		}
		
		/* -------------------------------------------
		/*	Hidden Configuration Variable
		/*	- use_mobile_control_panel => Automatically use mobile cp theme when accessed with a mobile device? (y/n)
		/* -------------------------------------------*/
		
		if ($this->EE->config->item('use_mobile_control_panel') != 'n')
		{
			// load the user agent lib to check for mobile
			$this->EE->load->library('user_agent');
			
			if ($this->EE->agent->is_mobile())
			{
				$agent = array_search($this->EE->agent->mobile(), $this->EE->agent->mobiles);
				$agent = $this->EE->security->sanitize_filename($agent);

				$cp_theme = (is_dir(PATH_CP_THEME.'mobile_'.$agent)) ? 'mobile_'.$agent : 'mobile';
			}
		}
		
		// shunt to default if the theme doesn't exist
		if ( ! is_dir(PATH_CP_THEME.$cp_theme))
		{
			$cp_theme = 'default';
		}

		// We explicitly set the view path since the theme files need to reside
		// above the "system" folder
		$this->EE->session->userdata['cp_theme'] = $cp_theme;
		$this->EE->load->_ci_view_path = PATH_CP_THEME.$cp_theme.'/';
		
		
		// Fetch control panel language file
		$this->EE->lang->loadfile('cp');

		$this->EE->load->library('logger');
		$this->EE->load->library('cp');
		
		
		// Request to our css controller don't need any
		// of the expensive prep work below
		if ($requested_class == 'css' OR $requested_class == 'javascript')
		{
			return;
		}
		
		
		// Does an admin session exist?
		// Only the "login" class can be accessed when there isn't an admin session		
		if ($this->EE->session->userdata('admin_sess') == 0 && $requested_class != 'login')
		{
			// has their session Timed out and they are requesting a page?
			// Grab the URL, base64_encode it and send them to the login screen.
			
			$safe_refresh = $this->EE->cp->get_safe_refresh();
						
			$return_url = ($safe_refresh == 'C=homepage') ? '' : AMP.'return='.base64_encode($safe_refresh);
			
			$this->EE->functions->redirect(BASE.AMP.'C=login'.AMP.'M=login_form'.$return_url);
		}
		
		// Make sure the user isn't banned, superadmins cannot be banned.
		if ($this->EE->session->userdata('group_id') != 1 AND $this->EE->session->ban_check('ip'))
		{
			return $this->EE->output->fatal_error($this->EE->lang->line('not_authorized'));
		}
		
		$this->EE->load->helper(array('form', 'quicktab'));
		$this->EE->load->model('super_model');

		// Secure forms stuff
		$this->EE->cp->secure_forms();
		
		// Certain variables will be included in every page, so we make sure they are set here
		// Prevents possible PHP errors, if a developer forgets to set it explicitly.
		$this->EE->cp->set_default_view_variables();
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
	 * @access	private
	 * @return	void
	 */	
	function _generate_action($can_view_system = FALSE)
	{
		require APPPATH.'libraries/Actions'.EXT;    
		$ACT = new EE_Actions($can_view_system);
	}	
	
	// ------------------------------------------------------------------------
	
	/**
	 * Generate Page Request
	 *
	 * @access	private
	 * @return	void
	 */	
	function _generate_page()
	{
		// If the forum module is installed and the URI contains the "triggering" word
		// we will override the template parsing class and call the forum class directly.
		// This permits the forum to be more light-weight as the template engine is 
		// not needed under normal circumstances. 
		
		$template_group = '';
		$template = '';
		
		$profile_trigger = $this->EE->config->item('profile_trigger');
		
		if ($this->EE->config->item('forum_is_installed') == "y" AND $this->EE->config->item('forum_trigger') != '' AND in_array($this->EE->uri->segment(1), preg_split('/\|/', $this->EE->config->item('forum_trigger'), -1, PREG_SPLIT_NO_EMPTY)) && ! IS_FREELANCER)
		{
			require PATH_MOD.'forum/mod.forum'.EXT;
			$FRM = new Forum();
		}			
		elseif ($profile_trigger && $profile_trigger == $this->EE->uri->segment(1) && ! IS_FREELANCER)
		{
			// We do the same thing with the member profile area.  
		
			if ( ! file_exists(PATH_MOD.'member/mod.member'.EXT))
			{
				exit;
			}

			require PATH_MOD.'member/mod.member'.EXT;
			
			$MBR = new Member();  			
			$MBR->_set_properties( array('trigger' => $profile_trigger) );

			$this->EE->output->set_output($MBR->manager());
		}
		else  // Instantiate the template parsing class and parse the requested template/page
		{       		
			if ($this->EE->config->item('template_group') == '' && $this->EE->config->item('template') == '')
			{
				$pages = $this->EE->config->item('site_pages');

				$match_uri = ($this->EE->uri->uri_string == '') ? '/' : '/'.trim($this->EE->uri->uri_string, '/');
				
				if ($pages !== FALSE && isset($pages[$this->EE->config->item('site_id')]['uris']) && (($entry_id = array_search($match_uri, $pages[$this->EE->config->item('site_id')]['uris'])) !== FALSE OR ($entry_id = array_search($match_uri.'/', $pages[$this->EE->config->item('site_id')]['uris'])) !== FALSE))
				{
					$this->EE->db->select('t.template_name, tg.group_name');
					$this->EE->db->from(array('templates t', 'template_groups tg'));
					$this->EE->db->where('t.group_id', 'tg.group_id', FALSE);
					$this->EE->db->where('t.template_id',
								$pages[$this->EE->config->item('site_id')]['templates'][$entry_id]);
					$query = $this->EE->db->get();

										 
					if ($query->num_rows() > 0)
					{
						/* 
							We do it this way so that we are not messing with any of the segment variables,
							which should reflect the actual URL and not our Pages redirect. We also
							set a new QSTR variable so that we are not interfering with other module's 
							besides the Channel module (which will use the new Pages_QSTR when available).
						*/
						
						$template_group = $query->row('group_name') ;
						$template = $query->row('template_name') ;
						$this->EE->uri->page_query_string = $entry_id;
					}
				}

			}
			
			require APPPATH.'libraries/Template'.EXT;
			
			$this->EE->TMPL = new EE_Template();
						
			// Legacy, unsupported, but still functional
			// Templates and Template Groups can be hard-coded
			// within either the main triggering file or via an include.
			if ($template_group == '')
			{
				$template_group = (string) $this->EE->config->item('template_group');	
			}

			if ($template == '')
			{
				$template = (string)$this->EE->config->item('template');
			}

			// if there's a URI, disable let the override
			if (( ! $template_group && ! $template) && $this->EE->uri->uri_string != '' && $this->EE->uri->uri_string != '/')
			{
				$template_group = '';
				$template = '';
			}
			
			// Parse the template
			$this->EE->TMPL->run_template_engine($template_group, $template);
		}
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
			$last_clear = $this->EE->stats->statdata('last_cache_clear');
			
			if ( ! $last_clear OR ! $last_clear > 1)
			{
				unset($last_clear);
			}
		}
		
		if ( ! isset($last_clear) && $this->EE->config->item('enable_online_user_tracking') != 'n')
		{
			$this->EE->db->select('last_cache_clear');
			$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
			$query = $this->EE->db->get('stats');

			$last_clear = $query->row('last_cache_clear');
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