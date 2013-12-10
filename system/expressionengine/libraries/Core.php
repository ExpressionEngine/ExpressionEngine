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
 * ExpressionEngine Config Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Core {

	var $native_modules		= array();		// List of native modules with EE
	var $native_plugins		= array();		// List of native plugins with EE

	/**
	 * Constructor
	 */
	public function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		// Yes, this is silly. No it won't work without it.
		// For some reason PHP won't bind the reference
		// for core to the super object quickly enough.
		// Breaks access to core in the menu lib.
		ee()->core = $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Sets constants, sets paths contants to appropriate directories, loads
	 * the database and generally prepares the system to run.
	 */
	public function bootstrap()
	{
		// Define the request type
		// Note: admin.php defines REQ=CP
		if ( ! defined('REQ'))
		{
			define('REQ', ((ee()->input->get_post('ACT') !== FALSE) ? 'ACTION' : 'PAGE'));
		}

		// Set a liberal script execution time limit, making it shorter for front-end requests than CI's default
		if (function_exists("set_time_limit") == TRUE AND @ini_get("safe_mode") == 0)
		{
			@set_time_limit((REQ == 'CP') ? 300 : 90);
		}

		// some path constants to simplify things
		define('PATH_MOD',		APPPATH.'modules/');
		define('PATH_PI',		APPPATH.'plugins/');
		define('PATH_EXT',		APPPATH.'extensions/');
		define('PATH_ACC',		APPPATH.'accessories/');
		define('PATH_FT',		APPPATH.'fieldtypes/');
		define('PATH_RTE',		APPPATH.'rte_tools/');
		if (ee()->config->item('third_party_path'))
		{
			define(
				'PATH_THIRD',
				rtrim(realpath(ee()->config->item('third_party_path')), '/').'/'
			);
		}
		else
		{
			define('PATH_THIRD',	APPPATH.'third_party/');
		}

		// application constants
		define('IS_CORE',		FALSE);
		define('APP_NAME',		'ExpressionEngine'.(IS_CORE ? ' Core' : ''));
		define('APP_BUILD',		'20131210');
		define('APP_VER',		'2.7.3');
		define('SLASH',			'&#47;');
		define('LD',			'{');
		define('RD',			'}');
		define('AMP',			'&amp;');
		define('NBS', 			'&nbsp;');
		define('BR', 			'<br />');
		define('NL',			"\n");
		define('PATH_DICT', 	APPPATH.'config/');
		define('AJAX_REQUEST',	ee()->input->is_ajax_request());

		// Load DB and set DB preferences
		ee()->load->database();
		ee()->db->swap_pre = 'exp_';
		ee()->db->db_debug = FALSE;

		// Note enable_db_caching is a per site setting specified in EE_Config.php
		// If debug is on we enable the profiler and DB debug
		if (DEBUG == 1 OR ee()->config->item('debug') == 2)
		{
			$this->_enable_debugging();
		}

		// Assign Site prefs now that the DB is fully loaded
		if (ee()->config->item('site_name') != '')
		{
			ee()->config->set_item('site_name', preg_replace('/[^a-z0-9\-\_]/i', '', ee()->config->item('site_name')));
		}

		ee()->config->site_prefs(ee()->config->item('site_name'));

		// force EE's db cache path - do this AFTER site prefs have been assigned
		// Due to CI's DB_cache handling- suffix with site id
		ee()->db->cache_set_path(APPPATH.'cache/db_cache_'.ee()->config->item('site_id'));

		// make sure the DB cache folder exists if we're caching!
		if (ee()->db->cache_on === TRUE &&
			! @is_dir(APPPATH.'cache/db_cache_'.ee()->config->item('site_id')))
		{
			@mkdir(APPPATH.'cache/db_cache_'.ee()->config->item('site_id'), DIR_WRITE_MODE);

			if ($fp = @fopen(APPPATH.'cache/db_cache_'.ee()->config->item('site_id').'/index.html', FOPEN_WRITE_CREATE_DESTRUCTIVE))
			{
				fclose($fp);
			}

			@chmod(APPPATH.'cache/db_cache_'.ee()->config->item('site_id'), DIR_WRITE_MODE);
		}

		// this look backwards, but QUERY_MARKER is only used where we MUST
		// have a ?, and do not want to double up
		// question marks on sites who are forcing query strings
		define('QUERY_MARKER', (ee()->config->item('force_query_string') == 'y') ? '' : '?');

		// Load the settings of the site you're logged into, however use the
		// cookie settings from the site that corresponds to the URL
		// e.g. site1.com/system/ viewing site2
		// $last_site_id = the site that you're viewing
		// config->item('site_id') = the site who's URL is being used

		$last_site_id = ee()->input->cookie('cp_last_site_id');

		if (REQ == 'CP' && ee()->config->item('multiple_sites_enabled') == 'y')
		{
			$cookie_prefix = ee()->config->item('cookie_prefix');
			$cookie_path  = ee()->config->item('cookie_path');
			$cookie_domain =  ee()->config->item('cookie_domain');

			if (! empty($last_site_id) && is_numeric($last_site_id) && $last_site_id != ee()->config->item('site_id'))
			{
				ee()->config->site_prefs('', $last_site_id);
			}

			ee()->config->cp_cookie_prefix = $cookie_prefix;
			ee()->config->cp_cookie_path  = $cookie_path;
			ee()->config->cp_cookie_domain =  $cookie_domain;
		}

		// This allows CI compatibility
		if (ee()->config->item('base_url') == FALSE)
		{
			ee()->config->set_item('base_url', ee()->config->item('site_url'));
		}

		if (ee()->config->item('index_page') == FALSE)
		{
			ee()->config->set_item('index_page', ee()->config->item('site_index'));
		}

		// Set the path to the "themes" folder
		if (ee()->config->item('theme_folder_path') !== FALSE &&
			ee()->config->item('theme_folder_path') != '')
		{
			$theme_path = preg_replace("#/+#", "/", ee()->config->item('theme_folder_path').'/');
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
		define('PATH_CP_GBL_IMG', 	ee()->config->slash_item('theme_folder_url').'cp_global_images/');
		unset($theme_path);

		// Define Third Party Theme Path and URL
		if (ee()->config->item('path_third_themes'))
		{
			define(
				'PATH_THIRD_THEMES',
				rtrim(realpath(ee()->config->item('path_third_themes')), '/').'/'
			);
		}
		else
		{
			define('PATH_THIRD_THEMES',	PATH_THEMES.'third_party/');
		}

		if (ee()->config->item('url_third_themes'))
		{
			define(
				'URL_THIRD_THEMES',
				rtrim(ee()->config->item('url_third_themes'), '/').'/'
			);
		}
		else
		{
			define('URL_THIRD_THEMES',	ee()->config->slash_item('theme_folder_url').'third_party/');
		}

		// Load the very, very base classes
		ee()->load->library('functions');
		ee()->load->library('extensions');

		// Our design is a little dirty. The asset controllers need
		// path_cp_theme. Fix it without loading all the other junk!
		if (REQ == 'CP')
		{
			define('PATH_CP_THEME', PATH_THEMES.'cp_themes/');	// theme path
		}

		if (extension_loaded('newrelic'))
		{
			ee()->load->library('newrelic');

			if (ee()->config->item('use_newrelic') == 'n')
			{
				ee()->newrelic->disable_autorum();
			}
			else
			{
				ee()->newrelic->set_appname();
				ee()->newrelic->name_transaction();
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Initialize EE
	 *
	 * Called from EE_Controller to run EE's front end.
	 *
	 * @access	public
	 * @return	void
	 */
	public function run_ee()
	{
		$this->native_plugins = array('magpie', 'markdown', 'xml_encode');
		$this->native_modules = array(
			'blacklist', 'channel', 'comment', 'commerce', 'email', 'emoticon',
			'file', 'forum', 'ip_to_nation', 'jquery', 'mailinglist', 'member',
			'metaweblog_api', 'moblog', 'pages', 'query', 'referrer', 'rss', 'rte',
			'search', 'simple_commerce', 'stats', 'wiki'
		);
		$this->standard_modules = array(
			'blacklist', 'email', 'forum', 'ip_to_nation', 'mailinglist',
			'member', 'moblog', 'query', 'simple_commerce', 'wiki'
		);

		// Is this a stylesheet request?  If so, we're done.
		if (isset($_GET['css']) OR (isset($_GET['ACT']) && $_GET['ACT'] == 'css'))
		{
			ee()->load->library('stylesheet');
			ee()->stylesheet->request_css_template();
			exit;
		}

		// Throttle and Blacklist Check
		if (REQ != 'CP')
		{
			ee()->load->library('throttling');
			ee()->throttling->run();

			ee()->load->library('blacklist');
			ee()->blacklist->_check_blacklist();

			ee()->load->library('file_integrity');
			ee()->file_integrity->create_bootstrap_checksum();
		}

		ee()->load->library('remember');
		ee()->load->library('localize');
		ee()->load->library('session');
		ee()->load->library('user_agent');

		// Get timezone to set as PHP timezone
		$timezone = ee()->session->userdata('timezone');

		// In case this is a timezone stored in the old format...
		if ( ! in_array($timezone, DateTimeZone::listIdentifiers()))
		{
			$timezone = ee()->localize->get_php_timezone($timezone);
		}

		// Set a timezone for any native PHP date functions being used
		date_default_timezone_set($timezone);

		// Load the "core" language file - must happen after the session is loaded
		ee()->lang->loadfile('core');

		// Compat helper, for those times where php doesn't quite cut it
		ee()->load->helper('compat');

		// Now that we have a session we'll enable debugging if the user is a super admin
		if (ee()->config->item('debug') == 1 AND ee()->session->userdata('group_id') == 1)
		{
			$this->_enable_debugging();
		}

		if (ee()->session->userdata('group_id') == 1 && ee()->config->item('show_profiler') == 'y')
		{
			ee()->output->enable_profiler(TRUE);
		}

		/*
		 * -----------------------------------------------------------------
		 *  Filter GET Data
		 * 		We've preprocessed global data earlier, but since we did
		 * 		not have a session yet, we were not able to determine
		 * 		a condition for filtering
		 * -----------------------------------------------------------------
		 */

		ee()->input->filter_get_data(REQ);

		if (REQ != 'ACTION')
		{
			if (AJAX_REQUEST && ee()->router->fetch_class() == 'login')
			{
				$this->process_secure_forms(EE_Security::CSRF_EXEMPT);
			}
			else
			{
				$this->process_secure_forms();
			}
		}

		// Update system stats
		ee()->load->library('stats');

		if (REQ == 'PAGE' && ee()->config->item('enable_online_user_tracking') != 'n')
		{
			ee()->stats->update_stats();
		}

		// Load up any Snippets
		if (REQ == 'ACTION' OR REQ == 'PAGE')
		{
			// load up any Snippets
			ee()->db->select('snippet_name, snippet_contents');
			ee()->db->where('(site_id = '.ee()->db->escape_str(ee()->config->item('site_id')).' OR site_id = 0)');
			$fresh = ee()->db->get('snippets');

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

				foreach (ee()->config->_global_vars as $k => $v)
				{
					$var_keys[] = LD.$k.RD;
				}

				$snippets = str_replace($var_keys, ee()->config->_global_vars, $snippets);

				ee()->config->_global_vars = ee()->config->_global_vars + $snippets;

				unset($snippets);
				unset($fresh);
				unset($var_keys);
			}
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Generate Control Panel Request
	 *
	 * Called from the EE_Controller to run EE's backend.
	 *
	 * @access public
	 * @return	void
	 */
	public function run_cp()
	{
		$this->_somebody_set_us_up_the_base();

		// Show the control panel home page in the event that a
		// controller class isn't found in the URL
		if (ee()->router->fetch_class() == '' OR
			! isset($_GET['S']))
		{
			ee()->functions->redirect(BASE.AMP.'C=homepage');
		}


		// Check user theme preference, then site theme preference, and fallback
		// to default if none are found.
		$cp_theme		= 'default';

		$theme_options = array(
			ee()->session->userdata('cp_theme'),
			ee()->config->item('cp_theme')
		);

		if (count($theme_options) >= 2)
		{
			if ( ! $theme_options[0])
			{
				unset($theme_options[0]);
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
		ee()->load->library('view');
		ee()->view->set_cp_theme($cp_theme);

		// Fetch control panel language file
		ee()->lang->loadfile('cp');

		// Prevent CodeIgniter Pseudo Output variables from being parsed
		ee()->output->parse_exec_vars = FALSE;

		/** ------------------------------------
		/**  Instantiate Admin Log Class
		/** ------------------------------------*/

		ee()->load->library('logger');
		ee()->load->library('cp');

		// Does an admin session exist?
		// Only the "login" class can be accessed when there isn't an admin session
		if (ee()->session->userdata('admin_sess') == 0 &&
			ee()->router->fetch_class() != 'login' &&
			ee()->router->fetch_class() != 'css')
		{
			// has their session Timed out and they are requesting a page?
			// Grab the URL, base64_encode it and send them to the login screen.
			$safe_refresh = ee()->cp->get_safe_refresh();
			$return_url = ($safe_refresh == 'C=homepage') ? '' : AMP.'return='.base64_encode($safe_refresh);

			ee()->functions->redirect(BASE.AMP.'C=login'.$return_url);
		}

		// Is the user banned or not allowed CP access?
		// Before rendering the full control panel we'll make sure the user isn't banned
		// But only if they are not a Super Admin, as they can not be banned
		if ((ee()->session->userdata('group_id') != 1 && ee()->session->ban_check('ip')) OR
			(ee()->session->userdata('member_id') !== 0 && ! ee()->cp->allowed_group('can_access_cp')))
		{
			return ee()->output->fatal_error(lang('not_authorized'));
		}

		// Load common helper files
		ee()->load->helper(array('url', 'form', 'quicktab'));

		// Certain variables will be included in every page, so we make sure they are set here
		// Prevents possible PHP errors, if a developer forgets to set it explicitly.
		ee()->cp->set_default_view_variables();

		// Load the Super Model
		ee()->load->model('super_model');

		// update documentation URL if site was running the beta and had the old location
		// @todo remove after 2.1.1's release, move to the update script
		if (strncmp(ee()->config->item('doc_url'), 'http://expressionengine.com/docs', 32) == 0)
		{
			ee()->config->update_site_prefs(array('doc_url' => 'http://ellislab.com/expressionengine/user-guide/'));
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Define the BASE constant
	 * @return void
	 */
	private function _somebody_set_us_up_the_base()
	{
		$s = 0;

		switch (ee()->config->item('admin_session_type'))
		{
			case 's'	:
				$s = ee()->session->userdata('session_id', 0);
				break;
			case 'cs'	:
				$s = ee()->session->userdata('fingerprint', 0);
				break;
		}

		define('BASE', SELF.'?S='.$s.'&amp;D=cp'); // cp url
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
		ee()->db->db_debug = TRUE;
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

		if (ee()->uri->uri_string == '' OR ee()->uri->uri_string == '/')
		{
			$template = (string) ee()->config->item('template');
			$template_group = (string) ee()->config->item('template_group');
		}


		// If the forum module is installed and the URI contains the "triggering" word
		// we will override the template parsing class and call the forum class directly.
		// This permits the forum to be more light-weight as the template engine is
		// not needed under normal circumstances.
		$forum_trigger = (ee()->config->item('forum_is_installed') == "y") ? ee()->config->item('forum_trigger') : '';
		$profile_trigger = ee()->config->item('profile_trigger');


		if ( ! IS_CORE && $forum_trigger &&
			in_array(ee()->uri->segment(1), preg_split('/\|/', $forum_trigger, -1, PREG_SPLIT_NO_EMPTY)))
		{
			require PATH_MOD.'forum/mod.forum.php';
			$FRM = new Forum();
			return;
		}

		if ( ! IS_CORE && $profile_trigger && $profile_trigger == ee()->uri->segment(1))
		{
			// We do the same thing with the member profile area.

			if ( ! file_exists(PATH_MOD.'member/mod.member.php'))
			{
				exit();
			}

			require PATH_MOD.'member/mod.member.php';

			$member = new Member();
			$member->_set_properties(array('trigger' => $profile_trigger));

			ee()->output->set_output($member->manager());
			return;
		}

		// -------------------------------------------
		// 'core_template_route' hook.
		//  - Reassign the template group and template loaded for parsing
		//
			if (ee()->extensions->active_hook('core_template_route') === TRUE)
			{
				$edata = ee()->extensions->call('core_template_route', ee()->uri->uri_string);
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
			$pages		= ee()->config->item('site_pages');
			$site_id	= ee()->config->item('site_id');
			$entry_id	= FALSE;

			// If we have pages, we'll look for an entry id
			if ($pages && isset($pages[$site_id]['uris']))
			{
				$match_uri = '/'.trim(ee()->uri->uri_string, '/');	// will result in '/' if uri_string is blank
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
				$qry = ee()->db->select('t.template_name, tg.group_name')
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
					ee()->uri->page_query_string = $entry_id;
				}
			}
		}

		ee()->load->library('template', NULL, 'TMPL');

		// Parse the template
		ee()->TMPL->run_template_engine($template_group, $template);
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
		ee()->db->cache_off();

		if (class_exists('Stats'))
		{
			if (ee()->stats->statdata('last_cache_clear')
				&& ee()->stats->statdata('last_cache_clear') > 1)
			{
				$last_clear = ee()->stats->statdata('last_cache_clear');
			}
		}

		if ( ! isset($last_clear))
		{
			ee()->db->select('last_cache_clear');
			ee()->db->where('site_id', ee()->config->item('site_id'));
			$query = ee()->db->get('stats');

			$last_clear = $query->row('last_cache_clear') ;
		}

		if (isset($last_clear) && ee()->localize->now > $last_clear)
		{
			$data = array(
				'last_cache_clear'	=> ee()->localize->now + (60*60*24*7)
			);

			ee()->db->where('site_id', ee()->config->item('site_id'));
			ee()->db->update('stats', $data);

			if (ee()->config->item('enable_throttling') == 'y')
			{
				$expire = time() - 180;

				ee()->db->where('last_activity <', $expire);
				ee()->db->delete('throttle');
			}

			ee()->functions->clear_spam_hashes();
			ee()->functions->clear_caching('all');
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Process Secure Forms
	 *
	 * Run the secure forms check. Needs to be run once per request.
	 * For actions, this happens from within the actions table so that
	 * we can check for the Strict_XID interface and csrf_exempt field.
	 *
	 * @access	public
	 * @return	void
	 */
	final public function process_secure_forms($flags = EE_Security::CSRF_STRICT)
	{
		// Secure forms stuff
		if( ! ee()->security->have_valid_xid($flags))
		{
			if (REQ == 'CP')
			{
				$this->_somebody_set_us_up_the_base();
				ee()->session->set_flashdata('message_failure', lang('invalid_action'));
				ee()->functions->redirect(BASE);
			}
			else
			{
				ee()->output->show_user_error('general', array(lang('invalid_action')));
			}
		}
	}
}

/* End of file Core.php */
/* Location: ./system/expressionengine/libraries/Core.php */
