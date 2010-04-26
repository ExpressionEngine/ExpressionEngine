<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
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
		/*
		 * -----------------------------------------------------------------
		 *  Define the request type
		 * -----------------------------------------------------------------
		 */
		 // Note: The admin.php file defines REQ='CP'
		if ( ! defined('REQ'))
		{
			$CI =& get_instance();
			define('REQ', (($CI->input->get_post('ACT') !== FALSE) ? 'ACTION' : 'PAGE')); 
		}
		
		// We initialize the Core library either from a second
		// autoloaded library, or from the EE controller, depending on if it's
		// a front end or control panel request.  This solves a scope issue
		// for code that would otherwise be ran from the Core constructor
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
		
		// some path constants to simplify things
		define('PATH_MOD',		APPPATH.'modules/');
		define('PATH_PI',		APPPATH.'plugins/');
		define('PATH_EXT',		APPPATH.'extensions/');
		define('PATH_ACC',		APPPATH.'accessories/');
		define('PATH_FT',		APPPATH.'fieldtypes/');
		define('PATH_THIRD',	APPPATH.'third_party/');
		
		// application constants
		define('IS_FREELANCER',	! file_exists(PATH_MOD.'member/mod.member'.EXT));
		define('APP_NAME',		'ExpressionEngine'.((IS_FREELANCER) ? ' Freelancer' : ''));
		define('APP_BUILD',		'20100101');
		define('APP_VER',		substr($this->EE->config->slash_item('app_version'), 0, 1).'.'.substr($this->EE->config->item('app_version'), 1, 1).'.'.substr($this->EE->config->item('app_version'), 2));
		define('SLASH',			'&#47;');
		define('LD',			'{');
		define('RD',			'}');
		define('AMP',			'&amp;');
		define('PATH_DICT', 	APPPATH.'config/');

		$this->native_modules = array('blacklist', 'blogger_api', 'channel', 'comment', 'commerce', 'email', 'emoticon',
									'forum', 'gallery', 'ip_to_nation', 'jquery', 'mailinglist', 'member', 'metaweblog_api',
									'moblog', 'pages', 'query', 'referrer', 'rss', 'search', 'simple_commerce', 'stats', 
									'updated_sites', 'wiki');
									  
		$this->native_plugins = array('magpie', 'xml_encode');

		// Set a liberal script execution time limit, making it shorter for front-end requests than CI's default
		if (function_exists("set_time_limit") == TRUE AND @ini_get("safe_mode") == 0)
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
		
		/*
		 * -----------------------------------------------------------------
		 *  Load Security Library
		 * -----------------------------------------------------------------
		 */		
		$this->EE->load->library('security');

		/*
		 * -----------------------------------------------------------------
		 *  Load DB and set DB preferences
		 * -----------------------------------------------------------------
		 */
		$this->EE->load->database();
		$this->EE->db->swap_pre 	= 'exp_';
		$this->EE->db->db_debug 	= FALSE;
		$this->EE->db->save_queries	= ($this->EE->config->item('show_profiler') == 'y' OR DEBUG == 1) ? TRUE : FALSE;
		$this->EE->db->cache_on 	= ($this->EE->config->item('enable_db_caching') == 'y' AND REQ == 'PAGE') ? TRUE : FALSE;

		// force EE's db cache path
		$this->EE->db->cache_set_path(APPPATH.'cache/db_cache');

		// make sure the DB cache folder exists if we're caching!
		if ($this->EE->db->cache_on === TRUE && ! @is_dir(APPPATH.'cache/db_cache'))
		{
			if ( ! @mkdir(APPPATH.'cache/db_cache', DIR_WRITE_MODE))
			{
				continue;
			}

			if ($fp = @fopen(APPPATH.'cache/db_cache/index.html', FOPEN_WRITE_CREATE_DESTRUCTIVE))
			{
				fclose($fp);
			}

			@chmod(APPPATH.'cache/db_cache', DIR_WRITE_MODE);
		}
				
		/*
		 * -----------------------------------------------------------------
		 *  If debug is on we enable the profiler and DB debug
		 * -----------------------------------------------------------------
		 */
		if (DEBUG == 1 OR $this->EE->config->item('debug') == 2)
		{
			$this->_enable_debugging();
		}
		
		/*
		 * -----------------------------------------------------------------
		 *  Assign Site prefs now that the DB is fully loaded
		 * -----------------------------------------------------------------
		 */
		if ($this->EE->config->item('site_name') != '')
		{
			$this->EE->config->set_item('site_name', preg_replace('/[^a-z0-9\-\_]/i', '', $this->EE->config->item('site_name')));
		}
							
		$this->EE->config->site_prefs($this->EE->config->item('site_name'));

		// this look backwards, but QUERY_MARKER is only used where we MUST have a ?, and do not want to double up
		// question marks on sites who are forcing query strings
		define('QUERY_MARKER', ($this->EE->config->item('force_query_string') == 'y') ? '' : '?');

		$last_site_id = $this->EE->input->cookie('cp_last_site_id');

		if (REQ == 'CP' && ! empty($last_site_id) && is_numeric($last_site_id) && $last_site_id != $this->EE->config->item('site_id'))
		{
			$this->EE->config->site_prefs('', $last_site_id);
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
		 * -------------------------------------------------------------------
		 *  This allows CI compatibility
		 * -------------------------------------------------------------------
		 */
		 
		 if ($this->EE->config->item('base_url') == FALSE)
		 {
		 	$this->EE->config->set_item('base_url', $this->EE->config->item('site_url'));
		 }
		 
		 if ($this->EE->config->item('index_page') == FALSE)
		 {
		 	$this->EE->config->set_item('index_page', $this->EE->config->item('site_index'));
		 }
			
		/*
		 * -------------------------------------------------------------------
		 *  Set the path to the "themes" folder
		 * -------------------------------------------------------------------
		 */
			if ($this->EE->config->item('theme_folder_path') !== FALSE && $this->EE->config->item('theme_folder_path') != '')
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

		/*
		 * -----------------------------------------------------------------
		 *  Is this a stylesheet request?  If so, we're done.
		 * -----------------------------------------------------------------
		 */
		 	
			if (isset($_GET['css']) OR (isset($_GET['ACT']) && $_GET['ACT'] == 'css')) 
			{
				$this->EE->load->library('stylesheet');
				$this->EE->stylesheet->request_css_template();
				exit;
			}

		/*
		 * -----------------------------------------------------------------
		 *  Load the remaining base classes
		 * -----------------------------------------------------------------
		 */
			$this->EE->load->library('functions');
			$this->EE->load->library('extensions');
			
			if (function_exists('date_default_timezone_set'))
			{
				date_default_timezone_set(date_default_timezone_get());
			}
			
			$this->EE->load->library('localize');
			$this->EE->load->library('session');

			// Load the "core" language file - must happen after the session is loaded
			$this->EE->lang->loadfile('core');

			// Remap pMachine Pro URLs if needed
			// deprecated
			// $this->EE->functions->remap_pm_urls();
	
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
			
		/*
		 * -----------------------------------------------------------------
		 *  Update system stats
		 * -----------------------------------------------------------------
		 */
			require PATH_MOD.'stats/mcp.stats'.EXT;    
			
			$this->EE->stats = new Stats_mcp();
		 		
			if (REQ == 'PAGE' && $this->EE->config->item('enable_online_user_tracking') != 'n')
			{
				$this->EE->stats->update_stats();
			}
		
		/*
		 * -----------------------------------------------------------------
		 *  Is the system turned on?
		 * -----------------------------------------------------------------
		 */
			// Note: super-admins can always view the system
				
			if ($this->EE->session->userdata('group_id') != 1  AND REQ != 'CP')
			{	
				if ($this->EE->config->item('is_system_on') == 'y' && ($this->EE->config->item('multiple_sites_enabled') != 'y' OR $this->EE->config->item('is_site_on') == 'y'))
				{
					if ($this->EE->session->userdata('can_view_online_system') == 'n')
					{
						$this->EE->output->system_off_msg();
						exit;
					}
				}
				else
				{
					if ($this->EE->session->userdata('can_view_offline_system') == 'n')
					{
						$this->EE->output->system_off_msg();
						exit;
					}		
				}
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

					$this->EE->config->_global_vars = array_merge($this->EE->config->_global_vars, $snippets);

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
		// set the CP Theme
		define('PATH_CP_THEME', PATH_THEMES.'cp_themes/');
		
		// Define the BASE constant containing the CP URL with the session ID
		$s = ($this->EE->config->item('admin_session_type') != 'c') ? $this->EE->session->userdata('session_id') : 0;
		$req_source = $this->EE->input->server('HTTP_X_REQUESTED_WITH');
		
		define('BASE',			SELF.'?S='.$s.'&amp;D=cp');
		define('AJAX_REQUEST',	($req_source == 'XMLHttpRequest') ? TRUE : FALSE);
		
		// Show the control panel home page in the event that a controller class isn't found in the URL
		if ($this->EE->router->fetch_class() == 'ee' OR $this->EE->router->fetch_class() == '')
		{
			$this->EE->functions->redirect(BASE.'&C=homepage');
		}

		// load the user agent lib to check for mobile
		$this->EE->load->library('user_agent');
		
		/* -------------------------------------------
		/*	Hidden Configuration Variable
		/*	- use_mobile_control_panel => Automatically use mobile cp theme when accessed with a mobile device? (y/n)
		/* -------------------------------------------*/
		
		if ($this->EE->agent->is_mobile() && $this->EE->config->item('use_mobile_control_panel') != 'n')
		{
			// iphone, ipod, blackberry, palm, etc.
			$agent = array_search($this->EE->agent->mobile(), $this->EE->agent->mobiles);
			$agent = $this->EE->security->sanitize_filename($agent);

			$cp_theme = (is_dir(PATH_CP_THEME.'mobile_'.$agent)) ? 'mobile_'.$agent : 'mobile';
		}
		else
		{
			$cp_theme = ( ! $this->EE->session->userdata('cp_theme')) ? $this->EE->config->item('cp_theme') : $this->EE->session->userdata('cp_theme');	
		}
		
		// make sure the theme exists, and shunt to default if it does not
		if ( ! is_dir(PATH_CP_THEME.$cp_theme))
		{
			$cp_theme = 'default';
		}

		// go ahead and set this so we can use it from here on out
		$this->EE->session->userdata['cp_theme'] = $cp_theme;
		
		// We explicitly set the view path since the theme files need to reside
		// above the "system" folder.
		$this->EE->load->_ci_view_path = PATH_CP_THEME.$cp_theme.'/';	
		
		// Fetch control panel language file	
		$this->EE->lang->loadfile('cp');
		
		// Prevent CodeIgniter Pseudo Output variables from being parsed
		$this->EE->output->parse_exec_vars = FALSE;

		/** ------------------------------------ 
		/**  Instantiate Admin Log Class
		/** ------------------------------------*/

		$this->EE->load->library('logger');

		// Load the NEW control panel display class
		$this->EE->load->library('cp');

		// Does an admin session exist?
		// Only the "login" class can be accessed when there isn't an admin session		
		if ($this->EE->session->userdata('admin_sess') == 0 && $this->EE->router->fetch_class() != 'login' && $this->EE->router->fetch_class() != 'css')
		{
			// has their session Timed out and they are requesting a page?
			// Grab the URL, base64_encode it and send them to the login screen.
			
			$safe_refresh = $this->EE->cp->get_safe_refresh();
			
			$return_url = ($this->EE->cp->get_safe_refresh() == 'C=homepage') ? '' : AMP.'return='.base64_encode($this->EE->cp->get_safe_refresh());
			
			$this->EE->functions->redirect(BASE.AMP.'C=login'.AMP.'M=login_form'.$return_url);
		}
		
		// Is the user banned?
		// Before rendering the full control panel we'll make sure the user isn't banned
		// But only if they are not a Super Admin, as they can not be banned
		if ($this->EE->session->userdata('group_id') != 1 AND $this->EE->session->ban_check('ip'))
		{
			return $this->EE->output->fatal_error($this->EE->lang->line('not_authorized'));
		}	
		
		// Request to our css controller don't need any
		// of the expensive prep work below
		if ($this->EE->router->class == 'css' OR $this->EE->router->class == 'javascript')
		{
			return;
		}
		
		/** ------------------------------------
		/**  Instantiate Display Class.
		/** ------------------------------------*/
		// @todo Kill this one before release!
		
		require APPPATH.'controllers/cp/display'.EXT;
		$this->EE->dsp = new Display();
		
		// Load common helper files
		$this->EE->load->helper(array('form', 'quicktab'));

		// Secure forms stuff
		$this->EE->cp->secure_forms();
		
		// Certain variables will be included in every page, so we make sure they are set here
		// Prevents possible PHP errors, if a developer forgets to set it explicitly.
		$this->EE->cp->set_default_view_variables();
		
		// Load the Super Model
		$this->EE->load->model('super_model');
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
	function _generate_action()
	{
		require APPPATH.'libraries/Actions'.EXT;    
		$ACT = new EE_Actions();
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
		
		if ($this->EE->config->item('forum_is_installed') == "y" AND  $this->EE->config->item('forum_trigger') != '' AND in_array($this->EE->uri->segment(1), preg_split('/\|/', $this->EE->config->item('forum_trigger'), -1, PREG_SPLIT_NO_EMPTY)) && ! IS_FREELANCER)
		{
			require PATH_MOD.'forum/mod.forum'.EXT;
			$FRM = new Forum();
		}			
		elseif ($this->EE->config->item('profile_trigger') != "" AND $this->EE->config->item('profile_trigger') == $this->EE->uri->segment(1) && ! IS_FREELANCER)
		{
			// We do the same thing with the member profile area.  
		
			if ( ! file_exists(PATH_MOD.'member/mod.member'.EXT))
			{
				exit;
			}
			else
			{
				require PATH_MOD.'member/mod.member'.EXT;
				
				$MBR = new Member();  			
				$MBR->_set_properties(
										array(
												'trigger' => $this->EE->config->item('profile_trigger')
											)
									);	
	
				$this->EE->output->set_output($MBR->manager());
			}
		}
		else  // Instantiate the template parsing class and parse the requested template/page
		{       		
			if ($this->EE->config->item('template_group') == '' && $this->EE->config->item('template') == '')
			{
				$pages = $this->EE->config->item('site_pages');

				$match_uri = ($this->EE->uri->uri_string == '') ? '/' : '/'.trim($this->EE->uri->uri_string, '/');
				
				if ($pages !== FALSE && isset($pages[$this->EE->config->item('site_id')]['uris']) && (($entry_id = array_search($match_uri, $pages[$this->EE->config->item('site_id')]['uris'])) !== FALSE OR ($entry_id = array_search($match_uri.'/', $pages[$this->EE->config->item('site_id')]['uris'])) !== FALSE))
				{
					$query = $this->EE->db->query("SELECT t.template_name, tg.group_name
										 FROM exp_templates t, exp_template_groups tg 
										 WHERE t.group_id = tg.group_id 
										 AND t.template_id = '".$this->EE->db->escape_str($pages[$this->EE->config->item('site_id')]['templates'][$entry_id])."'");
										 
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
			if (isset($this->EE->stats->statdata['last_cache_clear']) AND $this->EE->stats->statdata['last_cache_clear'] > 1)
			{
				$last_clear = $this->EE->stats->statdata['last_cache_clear'];
			}
		}
	
		if ( ! isset($last_clear) && $this->EE->config->item('enable_online_user_tracking') != 'n')
		{
			$query = $this->EE->db->query("SELECT last_cache_clear FROM exp_stats WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'");
			$last_clear = $query->row('last_cache_clear') ;
		}
			
		if (isset($last_clear) && $this->EE->localize->now > $last_clear)
		{
			$expire = $this->EE->localize->now + (60*60*24*7);
			$this->EE->db->query("UPDATE exp_stats SET last_cache_clear = '{$expire}' WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'");
			
			if ($this->EE->config->item('enable_throttling') == 'y')
			{		
				$expire = time() - 180;
				$this->EE->db->query("DELETE FROM exp_throttle WHERE last_activity < {$expire}");
			}
	
			$this->EE->functions->clear_spam_hashes();
			$this->EE->functions->clear_caching('all');
		}
		
	}	
	
	
}

/* End of file Core.php */
/* Location: ./system/expressionengine/libraries/Core.php */
