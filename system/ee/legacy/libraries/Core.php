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
 * Core, Core. CORE!
 */
class EE_Core {

	var $native_modules		= array();		// List of native modules with EE
	var $native_plugins		= array();		// List of native plugins with EE

	private $bootstrapped = FALSE;
	private $ee_loaded = FALSE;
	private $cp_loaded = FALSE;

	/**
	 * Sets constants, sets paths contants to appropriate directories, loads
	 * the database and generally prepares the system to run.
	 */
	public function bootstrap()
	{
		if ($this->bootstrapped)
		{
			return;
		}

		$this->bootstrapped = TRUE;

		// Define the request type
		// Note: admin.php defines REQ=CP
		if ( ! defined('REQ'))
		{
			define('REQ', ((ee()->input->get_post('ACT') !== FALSE) ? 'ACTION' : 'PAGE'));
		}

		// Set a liberal script execution time limit, making it shorter for front-end requests than CI's default
		if (function_exists("set_time_limit") == TRUE)
		{
			@set_time_limit((REQ == 'CP') ? 300 : 90);
		}

		// If someone's trying to access the CP but EE_APPPATH is defined, it likely
		// means the installer is still active; redirect to clean path
		if (ee()->config->item('subclass_prefix') != 'EE_' && ee()->uri->segment(1) == 'cp')
		{
			header('Location: '.SELF);
			exit;
		}

		// some path constants to simplify things
		define('PATH_ADDONS', SYSPATH . 'ee/EllisLab/Addons/');
		define('PATH_MOD',    SYSPATH . 'ee/EllisLab/Addons/');
		define('PATH_PI',     SYSPATH . 'ee/EllisLab/Addons/');
		define('PATH_EXT',    SYSPATH . 'ee/EllisLab/Addons/');
		define('PATH_FT',     SYSPATH . 'ee/EllisLab/Addons/');
		define('PATH_RTE',    APPPATH . 'rte_tools/');
		define('PATH_THIRD',  SYSPATH . 'user/addons/');
		define('PATH_CACHE',  SYSPATH . 'user/cache/');
		define('PATH_TMPL',   SYSPATH . 'user/templates/');
		define('PATH_JS',     'src');
		define('PATH_DICT',   SYSPATH . 'user/config/');

		// retain in case third-party add-ons expect IS_CORE to be defined
		define('IS_CORE',		FALSE);

		// application constants
		define('APP_NAME',		'ExpressionEngine');
		define('APP_BUILD',		'20200422');
		define('APP_VER',		'5.3.2');
		define('APP_VER_ID',	'');
		define('SLASH',			'&#47;');
		define('LD',			'{');
		define('RD',			'}');
		define('AMP',			'&amp;');
		define('NBS', 			'&nbsp;');
		define('BR', 			'<br />');
		define('NL',			"\n");
		define('AJAX_REQUEST',	ee()->input->is_ajax_request());
		define('USERNAME_MAX_LENGTH', 75);
		define('PASSWORD_MAX_LENGTH', 72);
		define('DOC_URL',       'https://docs.expressionengine.com/v5/');
		define('URL_TITLE_MAX_LENGTH', 200);

		ee()->load->helper('language');
		ee()->load->helper('string');

		// Load the default caching driver
		ee()->load->driver('cache');

		ee()->load->database();
		ee()->db->swap_pre = 'exp_';
		ee()->db->db_debug = FALSE;

		// boot the addons
		ee('App')->setupAddons(SYSPATH . 'ee/EllisLab/Addons/');
		ee('App')->setupAddons(PATH_THIRD);

		// Set ->api on the legacy facade to the model factory
		ee()->set('api', ee()->di->make('Model'));

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

		// earliest point we can apply this, makes sure that PHPSESSID cookies
		// don't leak to JS by setting the httpOnly flag
		$secure = bool_config_item('cookie_secure');
		$httpOnly = (ee()->config->item('cookie_httponly')) ? bool_config_item('cookie_httponly') : TRUE;
		session_set_cookie_params(0, ee()->config->item('cookie_path'), ee()->config->item('cookie_domain'), $secure, $httpOnly);

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
			$cookie_httponly = ee()->config->item('cookie_httponly');

			if ($cookie_prefix)
			{
				$cookie_prefix .= '_';
			}

			if (! empty($last_site_id) && is_numeric($last_site_id) && $last_site_id != ee()->config->item('site_id'))
			{
				ee()->config->site_prefs('', $last_site_id);
			}

			ee()->config->cp_cookie_prefix = $cookie_prefix;
			ee()->config->cp_cookie_path  = $cookie_path;
			ee()->config->cp_cookie_domain =  $cookie_domain;
			ee()->config->cp_cookie_httponly = $cookie_httponly;
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

		// Backwards compatibility for the removed secure forms setting.
		// Developers are still checking against this key, so we'll wait some
		// time before removing it.
		$secure_forms = (bool_config_item('disable_csrf_protection')) ? 'n' : 'y';
		ee()->config->set_item('secure_forms', $secure_forms);

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
		if ( ! is_dir($theme_path.'/ee'))
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

		$theme_url = ee()->config->slash_item('theme_folder_url');

		define('PATH_THEMES', $theme_path.'ee/');
		define('URL_THEMES', $theme_url.'ee/');
		define('PATH_THEMES_GLOBAL_ASSET', PATH_THEMES.'asset/');
		define('URL_THEMES_GLOBAL_ASSET', URL_THEMES.'asset/');
		define('PATH_CP_THEME', PATH_THEMES.'cp/');

		define('PATH_THIRD_THEMES', $theme_path.'user/');
		define('URL_THIRD_THEMES', $theme_url.'user/');

		define('PATH_MBR_THEMES', PATH_THEMES.'member/');
		define('PATH_CP_GBL_IMG', URL_THEMES_GLOBAL_ASSET.'img/');

		define('PATH_THEME_TEMPLATES', SYSPATH . 'ee/templates/_themes/');
		define('PATH_THIRD_THEME_TEMPLATES', SYSPATH . 'user/templates/_themes/');


		unset($theme_path);

		// Load the very, very base classes
		ee()->load->library('functions');
		ee()->load->library('extensions');
		ee()->load->library('api');
	}

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
		if ($this->ee_loaded)
		{
			return;
		}

		$this->ee_loaded = TRUE;

		$this->native_plugins = array('markdown', 'rss_parser', 'xml_encode');
		$this->native_modules = array(
			'blacklist', 'channel', 'comment', 'commerce', 'email', 'emoticon',
			'file', 'filepicker', 'forum', 'ip_to_nation', 'jquery', 'member',
			'metaweblog_api', 'moblog', 'pages', 'query', 'relationship', 'rss',
			 'rte', 'search', 'simple_commerce', 'spam', 'stats'
		);

		// Is this a stylesheet request?  If so, we're done.
		if (isset($_GET['css']) OR (isset($_GET['ACT']) && $_GET['ACT'] == 'css'))
		{
			ee()->load->library('stylesheet');
			ee()->stylesheet->request_css_template();
			exit;
		}

		// Security Checks: Throttle, Blacklist, File Integrity, and iFraming
		if (REQ != 'CP')
		{
			ee()->load->library('throttling');
			ee()->throttling->run();

			ee()->load->library('blacklist');
			ee()->blacklist->_check_blacklist();

			ee()->load->library('file_integrity');
			ee()->file_integrity->create_bootstrap_checksum();

			$this->setFrameHeaders();
		}

		ee()->load->library('remember');
		ee()->load->library('localize');
		ee()->load->library('session');
		ee()->load->library('user_agent');

		// Get timezone to set as PHP timezone
		$timezone = ee()->session->userdata('timezone', ee()->config->item('default_site_timezone'));

		// In case this is a timezone stored in the old format...
		if ( ! in_array($timezone, DateTimeZone::listIdentifiers()))
		{
			$timezone = ee()->localize->get_php_timezone($timezone);
		}

		// Set a timezone for any native PHP date functions being used
		date_default_timezone_set($timezone);

		// Load the "core" language file - must happen after the session is loaded
		ee()->lang->loadfile('core');

		// Now that we have a session we'll enable debugging if the user is a super admin
		if (ee()->config->item('debug') == 1
			&& (ee()->session->userdata('group_id') == 1
				|| ee()->session->userdata('can_debug') == 'y'
				)
			)
		{
			$this->_enable_debugging();
		}

		if ((ee()->session->userdata('group_id') == 1 || ee()->session->userdata('can_debug') == 'y')
			&& ee()->config->item('show_profiler') == 'y')
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

		if (REQ == 'PAGE' && bool_config_item('enable_online_user_tracking'))
		{
			ee()->stats->update_stats();
		}

		// Load up any Snippets
		if (REQ == 'ACTION' OR REQ == 'PAGE')
		{
			$this->loadSnippets();
		}
	}

	/**
	 * Load Snippets into config's _global_vars
	 */
	public function loadSnippets()
	{
		$fresh = ee('Model')->make('Snippet')->loadAll();

		if ($fresh->count() > 0)
		{
			$snippets = $fresh->getDictionary('snippet_name', 'snippet_contents');

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
		if ($this->cp_loaded)
		{
			return;
		}

		$this->cp_loaded = TRUE;

		$this->somebody_set_us_up_the_base();

		// Show the control panel home page in the event that a
		// controller class isn't found in the URL
		if (ee()->router->fetch_class() == ''/* OR
			! isset($_GET['S'])*/)
		{
			ee()->functions->redirect(BASE.AMP.'C=homepage');
		}

		if (ee()->uri->segment(1) == 'cp')
		{
			// new url, restore old style get
			$get = array_filter(array(
				'D' => 'cp',
				'C' => ee()->uri->segment(2),
				'M' => ee()->uri->segment(3)
			));

			$_GET = array_merge($get, $_GET);
		}
		else
		{
			$get = array();
		}


		// Load our view library
		ee()->load->library('view');

		// Fetch control panel language file
		ee()->lang->loadfile('cp');

		// Prevent Pseudo Output variables from being parsed
		ee()->output->parse_exec_vars = FALSE;

		/** ------------------------------------
		/**  Instantiate Admin Log Class
		/** ------------------------------------*/

		ee()->load->library('logger');
		ee()->load->library('cp');

		// Does an admin session exist?
		// Only the "login" class can be accessed when there isn't an admin session
		if (ee()->session->userdata('admin_sess') == 0 &&
			ee()->router->fetch_class(TRUE) != 'login' &&
			ee()->router->fetch_class() != 'css')
		{
			// has their session Timed out and they are requesting a page?
			// Grab the URL, base64_encode it and send them to the login screen.
			$safe_refresh = ee()->cp->get_safe_refresh();
			$return_url = ($safe_refresh == 'C=homepage') ? '' : AMP.'return='.urlencode(ee('Encrypt')->encode($safe_refresh));

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
		ee()->load->helper(array('url', 'form', 'quicktab', 'file'));

		// Certain variables will be included in every page, so we make sure they are set here
		// Prevents possible PHP errors, if a developer forgets to set it explicitly.
		ee()->cp->set_default_view_variables();

		// Load the Super Model
		ee()->load->model('super_model');

		// Laod Menu library
		ee()->load->library('menu');

		$this->set_newrelic_transaction(function () use ($get) {
			$request = $get;
			array_shift($request);
			$request = implode('/', $request);
			return 'CP: '.$request;
		});


		//show them post-update checks, again
		if (ee()->input->get('after') == 'update')
		{
			$advisor = new \EllisLab\ExpressionEngine\Library\Advisor\Advisor();
			$messages = $advisor->postUpdateChecks();
			if (!empty($messages)) {
				ee()->lang->load('utilities');
				$alert = '';
				foreach ($messages as $message) {
					$alert .= $message . BR;
				}
				$alert .= sprintf(lang('debug_tools_instruction'), ee('CP/URL')->make('utilities/debug-tools')->compile());
				ee('CP/Alert')
					->makeBanner()
					->asWarning()
					->addToBody($alert)
					->canClose()
					->now();
			}
		}
	}

	/**
	 * Define the BASE constant
	 * @return void
	 */
	private function somebody_set_us_up_the_base()
	{
		define('BASE', SELF.'?S='.ee()->session->session_id().'&amp;D=cp'); // cp url
	}

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

	/**
	 * Generate Page Request
	 *
	 * @access	public
	 * @return	void
	 */
	final public function generate_action($can_view_system = FALSE)
	{
		require APPPATH.'libraries/Actions.php';

		// @todo remove ridiculous dance when PHP 5.3 is no longer supported
		$that = $this;
		$ACT = new EE_Actions($can_view_system, function($class, $method) use ($that) {
			$that->set_newrelic_transaction('ACT: '.$class.'::'.$method.'()');
		});
	}

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

		if ($forum_trigger &&
			in_array(ee()->uri->segment(1), preg_split('/\|/', $forum_trigger, -1, PREG_SPLIT_NO_EMPTY)))
		{
			require PATH_MOD.'forum/mod.forum.php';
			$FRM = new Forum();
			$this->set_newrelic_transaction($forum_trigger.'/'.$FRM->current_request);
			return;
		}

		if ($profile_trigger && $profile_trigger == ee()->uri->segment(1))
		{
			// We do the same thing with the member profile area.

			if ( ! file_exists(PATH_MOD.'member/mod.member.php'))
			{
				exit();
			}

			require PATH_MOD.'member/mod.member.php';

			// Clean up the URLs to remove unnecessary detail
			$this->set_newrelic_transaction(function() {
				$request = preg_replace('/\/[\d]+$/', '', ee()->uri->uri_string);
				return preg_replace('/search\/.*$/', 'search', $request);
			});

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

			ee()->functions->clear_caching('all');
		}
	}

	/**
	 * Set the New Relic transasction name
	 * @param String/callable $transaction_name Either a string containing the
	 *                                          transaction name or a callable
	 *                                          that returns the transaction
	 *                                          name
	 */
	public function set_newrelic_transaction($transaction_name)
	{
		if (extension_loaded('newrelic'))
		{
			ee()->load->library('newrelic');

			if (ee()->config->item('use_newrelic') == 'n')
			{
				ee()->newrelic->disable_autorum();
			}
			else
			{
				if (is_callable($transaction_name))
				{
					$transaction_name = call_user_func($transaction_name);
				}

				ee()->newrelic->set_appname();
				ee()->newrelic->name_transaction($transaction_name);
			}
		}
	}

	/**
	 * Set iFrame Headers
	 *
	 * A security precaution to prevent iFraming of the site to protect
	 * against clickjacking. By default we use SAMEORIGIN so that iframe
	 * designs are still possible.
	 *
	 * @return	void
	 */
	private function setFrameHeaders()
	{
		$frame_options = ee()->config->item('x_frame_options');
		$frame_options = strtoupper($frame_options);

		// if not specified or invalid value, default to SAMEORIGIN
		if ( ! in_array($frame_options, array('DENY', 'SAMEORIGIN', 'NONE')))
		{
			$frame_options = 'SAMEORIGIN';
		}

		if ($frame_options != 'NONE')
		{
			ee()->output->set_header('X-Frame-Options: '.$frame_options);
		}
	}

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
		if ( ! ee()->security->have_valid_xid($flags))
		{
			ee()->output->set_status_header(403);

			if (REQ == 'CP')
			{
				if (AJAX_REQUEST)
				{
					header('X-EE-Broadcast: modal');
				}

				show_error(lang('csrf_token_expired'));
			}

			ee()->output->show_user_error('general', array(lang('csrf_token_expired')));
		}
	}
}

// EOF
