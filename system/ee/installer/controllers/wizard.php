<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Installation and Update Wizard
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Wizard extends CI_Controller {

	public $version           = '3.0.0';	// The version being installed
	public $installed_version = ''; 		// The version the user is currently running (assuming they are running EE)
	public $minimum_php       = '5.3.10';	// Minimum version required to run EE
	public $schema            = NULL;		// This will contain the schema object with our queries
	public $languages         = array(); 	// Available languages the installer supports (set dynamically based on what is in the "languages" folder)
	public $mylang            = 'english';// Set dynamically by the user when they run the installer
	public $image_path        = ''; 		// URL path to the cp_global_images folder.  This is set dynamically
	public $is_installed      = FALSE;	// Does an EE installation already exist?  This is set dynamically.
	public $next_update       = FALSE;	// The next update file that needs to be loaded, when an update is performed.
	public $remaining_updates = 0; 		// Number of updates remaining, in the event the user is updating from several back
	public $refresh           = FALSE;	// Whether to refresh the page for the next update.  Set dynamically
	public $refresh_url       = '';		// The URL where the refresh should go to.  Set dynamically
	public $theme_path        = '';
	public $root_theme_path   = '';

	// Default page content - these are in English since we don't know the user's language choice when we first load the installer
	public $content           = '';
	public $title             = 'ExpressionEngine Installation and Update Wizard';
	public $subtitle          = '';

	private $current_step = 1;
	private $steps        = 3;
	private $addon_step   = FALSE;

	public $now;
	public $year;
	public $month;
	public $day;

	// These are the methods that are allowed to be called via $_GET['m']
	// for either a new installation or an update. Note that the function names
	// are prefixed but we don't include the prefix here.
	public $allowed_methods = array('install_form', 'do_install', 'do_update');

	// Absolutely, positively must always be installed
	public $required_modules = array('channel', 'comment', 'member', 'stats', 'rte');

	public $theme_required_modules = array();

	// Our default installed modules, if there is no "override"
	public $default_installed_modules = array('comment', 'email', 'emoticon',
		'jquery', 'member', 'query', 'rss', 'search', 'stats', 'channel',
		'mailinglist', 'rte');

	// Native First Party ExpressionEngine Modules (everything else is in third party folder)
	public $native_modules = array('blacklist', 'channel', 'comment', 'commerce',
		'email', 'emoticon', 'file', 'forum', 'gallery', 'ip_to_nation',
		'jquery', 'mailinglist', 'member', 'metaweblog_api', 'moblog', 'pages',
		'query', 'referrer', 'rss', 'rte', 'search',
		'simple_commerce', 'stats', 'wiki');

	// Third Party Modules may send error messages if something goes wrong.
	public $module_install_errors = array(); // array that collects all error messages

	// These are the values we need to set during a first time installation
	public $userdata = array(
		'app_version'           => '',
		'doc_url'               => 'http://ellislab.com/expressionengine/user-guide/',
		'ext'                   => '.php',
		'ip'                    => '',
		'database'              => 'mysql',
		'db_conntype'           => '0',
		'dbdriver'              => 'mysqli',
		'db_hostname'           => 'localhost',
		'db_username'           => '',
		'db_password'           => '',
		'db_name'               => '',
		'db_prefix'             => 'exp',
		'site_label'            => '',
		'site_name'             => 'default_site',
		'site_url'              => '',
		'site_index'            => 'index.php',
		'cp_url'                => '',
		'username'              => '',
		'password'              => '',
		'password_confirm'      => '',
		'screen_name'           => '',
		'email_address'         => '',
		'webmaster_email'       => '',
		'deft_lang'             => 'english',
		'theme'                 => '01',
		'default_site_timezone' => 'UTC',
		'redirect_method'       => 'redirect',
		'upload_folder'         => 'uploads/',
		'image_path'            => '',
		'cp_images'             => 'cp_images/',
		'avatar_path'           => '../images/avatars/',
		'avatar_url'            => 'images/avatars/',
		'photo_path'            => '../images/member_photos/',
		'photo_url'             => 'images/member_photos/',
		'signature_img_path'    => '../images/signature_attachments/',
		'signature_img_url'     => 'images/signature_attachments/',
		'pm_path'               => '../images/pm_attachments',
		'captcha_path'          => '../images/captchas/',
		'theme_folder_path'     => '../themes/',
		'modules'               => array(),
		'install_default_theme' => 'n'
	);


	// These are the default values for the CodeIgniter config array.  Since the EE
	// and CI config files are one in the same now we use this data when we write the
	// initial config file using $this->write_config_data()
	public $ci_config = array(
		'uri_protocol'       => 'AUTO',
		'charset'            => 'UTF-8',
		'subclass_prefix'    => 'EE_',
		'log_threshold'      => 0,
		'log_path'           => '',
		'log_date_format'    => 'Y-m-d H:i:s',
		'cache_path'         => '',
		'encryption_key'     => '',

		// Enabled for cleaner view files and compatibility
		'rewrite_short_tags' => TRUE
	);

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 * @return	void
	 */
	public function __construct()
	{
		parent::__construct();

		define('IS_CORE', FALSE);
		define('PASSWORD_MAX_LENGTH', 72);

		// Third party constants
		$addon_path = (ee()->config->item('addons_path'))
			? rtrim(realpath(ee()->config->item('addons_path')), '/').'/'
			: SYSPATH.'user/addons/';
		define('PATH_ADDONS', $addon_path);
		define('PATH_THIRD', $addon_path);

		$req_source = $this->input->server('HTTP_X_REQUESTED_WITH');
		define('AJAX_REQUEST',	($req_source == 'XMLHttpRequest') ? TRUE : FALSE);

		$this->output->enable_profiler(FALSE);

		$this->userdata['app_version'] = $this->version;

 		// Load the helpers we intend to use
 		$this->load->helper(array('form', 'url', 'html', 'directory', 'file', 'email', 'security', 'date', 'string'));

		// Load the language pack.  English is loaded on the installer home
		// page along with some radio buttons for each installed language pack.
		// Based on the users's choice we build the language into our URL string
		// and use that info to load the desired language file on each page

		$this->load->library('logger');

		$this->load->add_package_path(EE_APPPATH);

		$this->load->library('localize');
		$this->load->library('cp');
		$this->load->helper('language');
		$this->lang->loadfile('installer');

		$this->load->model('installer_template_model', 'template_model');

		// Update notices are used to print info at the end of
		// the update
		$this->load->library('update_notices');

		// Set the theme URLs
		$this->image_path = $this->set_path('themes/ee/cp_global_images/');

		// First try the current directory, if they are running the system with an admin.php file
		$this->theme_path = substr($_SERVER['SCRIPT_FILENAME'], 0, -strlen(SELF));

		if (is_dir($this->theme_path.'themes'))
		{
			$this->theme_path .= 'themes/';
		}
		else
		{
			// Must be in a public system folder so try one level back from
			// current folder. Replace only the LAST occurance of the system
			// folder name with nil incase the system folder name appears more
			// than once in the path.
			$this->theme_path = preg_replace('/\b'.preg_quote(SYSDIR).'(?!.*'.preg_quote(SYSDIR).')\b/', '', $this->theme_path).'themes/';
		}

		$this->root_theme_path = $this->theme_path;
		define('PATH_THEMES', $this->root_theme_path.'ee/');
		define('URL_THEMES', $this->root_theme_path.'ee/');
		$this->theme_path .= 'ee/site_themes/';
		$this->theme_path = str_replace('//', '/', $this->theme_path);
		$this->root_theme_path = str_replace('//', '/', $this->root_theme_path);

		// Set the time
		$time = time();
		$this->now   = gmmktime(gmdate("H", $time), gmdate("i", $time), gmdate("s", $time), gmdate("m", $time), gmdate("d", $time), gmdate("Y", $time));
		$this->year  = gmdate('Y', $this->now);
		$this->month = gmdate('m', $this->now);
		$this->day   = gmdate('d', $this->now);
	}

	// --------------------------------------------------------------------

	/**
	 * Remap - Intercepts the request and dynamically determines what we should
	 * do
	 * @return void
	 */
	public function _remap()
	{
		$this->set_base_url();

		// Run our pre-flight tests.
		// This function generates its own error messages so if it returns FALSE
		// we bail out.
		if ( ! $this->preflight())
		{
			return FALSE;
		}

		$action = ee()->input->get('M') ?: FALSE;

		// If we're not at a defined stage, this is the first step.
		if ( ! $action)
		{
			if ($this->is_installed)
			{
				return $this->update_form();
			}
			else
			{
				return $this->install_form();
			}
		}

		// OK, at this point we have determined whether an existing EE
		// installation exists and we've done all our error trapping and
		// connected to the DB if needed

		// Is the action allowed?
		if ( ! in_array($action, $this->allowed_methods)
			OR ! method_exists($this, $action))
		{
			show_error(lang('invalid_action'));
		}

		// Call the action
		$this->$action();
	}

	// --------------------------------------------------------------------

	/**
	 * Pre-flight Tests - Does all of our error checks
	 * @return void
	 */
	private function preflight()
	{
		// Is the config file readable?
		if ( ! include($this->config->config_path))
		{
			$this->set_output('error', array('error' => lang('unreadable_config')));
			return FALSE;
		}

		// Determine current version
		$this->installed_version = ee()->config->item('app_version');
		if (strpos($this->installed_version, '.') == FALSE) {
			$this->installed_version = implode(
				'.',
				str_split($this->installed_version)
			);
		}

		// Check for minimum version of PHP
		// Comes after including the config because that gives us an idea if
		// this is a new install or an update
		if (is_php($this->minimum_php) == FALSE)
		{
			$this->is_installed = isset($config);
			$this->set_output('error', array(
				'error' => sprintf(
					lang('version_warning'),
					$this->minimum_php,
					phpversion()
				)
			));
			return FALSE;
		}

		// Check for PDO
		if ( ! class_exists('PDO'))
		{
			$this->set_output('error', array('error' => lang('database_no_pdo')));
			return FALSE;
		}

		// Check for JSON encode/decode
		$json_errors = array();
		if ( ! function_exists('json_encode') OR ! function_exists('json_decode'))
		{
			$this->set_output('error', array('error' => lang('json_parser_missing')));
			return FALSE;
		}

		// Is the config file writable?
		if ( ! is_really_writable($this->config->config_path))
		{
			$this->set_output('error', array('error' => lang('unwritable_config')));
			return FALSE;
		}

		// Attempt to grab cache_path config if it's set
		$cache_path = (ee()->config->item('cache_path'))
			? ee()->config->item('cache_path')
			: SYSPATH.'user/cache';

		// Is the cache folder writable?
		if ( ! is_really_writable($cache_path))
		{
			$this->set_output('error', array('error' => lang('unwritable_cache_folder')));
			return FALSE;
		}

		// No config? This means it's a first time install...hopefully. There's
		// always a chance that the user nuked their config files. During
		// installation later we'll double check the existence of EE tables once
		// we know the DB connection values
		if ( ! isset($config))
		{
			// Is the email template file available? We'll check since we need
			// this later
			if ( ! file_exists(EE_APPPATH.'/language/'.$this->userdata['deft_lang'].'/email_data.php'))
			{
				$this->set_output('error', array('error' => lang('unreadable_email')));
				return FALSE;
			}

			// Are the DB schemas available?
			if ( ! is_dir(APPPATH.'schema/'))
			{
				$this->set_output('error', array('error' => lang('unreadable_schema')));
				return FALSE;
			}

			// set the image path and theme folder path
			$this->userdata['image_path'] = $this->image_path;
			$this->userdata['theme_folder_path'] = $this->root_theme_path;

			// At this point we are reasonably sure that this is a first time
			// installation. We will set the flag and bail out since we're done
			$this->is_installed = FALSE;
			return TRUE;
		}

		// Before we assume this is an update, let's see if we can connect to
		// the DB. If they are running EE prior to 2.0 the database settings are
		// found in the main config file, if they are running 2.0 or newer, the
		// settings are found in the db file
		$db = ee('Database')->getConfig()->getGroupConfig();

		if ( ! isset($db))
		{
			$this->set_output('error', array('error' => lang('database_no_data')));
			return FALSE;
		}

		// Can we connect?
		if ( ! $this->db_connect($db))
		{
			$this->set_output('error', array('error' => lang('database_no_config')));
			return FALSE;
		}

		// EXCEPTIONS
		// We need to deal with a couple possible issues.

		// In 2.10.0, we started putting .'s in the app_verson config. The rest
		// of the code assumes this to be true, so we need to tweak their old config.
		if (strpos($config['app_version'], '.') === FALSE)
		{
			$cap = $config['app_version'];
			$config['app_version'] = "{$cap[0]}.{$cap[1]}.{$cap[2]}";
		}


		// OK, now let's determine if the update files are available and whether
		// the currently installed version is older then the most recent update

		// If this returns false it means the "updates" folder was not readable
		if ( ! $this->fetch_updates($config['app_version']))
		{
			$this->set_output('error', array('error' => lang('unreadable_update')));
			return FALSE;
		}

		// If this is FALSE it means the user is running the most current
		// version. We will show the "you are running the most current version"
		// template
		if ($this->next_update === FALSE)
		{
			$this->assign_install_values();

			$vars['installer_path'] = '/'.SYSDIR.'/installer';

			// Set the path to the site and CP
			$host = 'http://';
			if (isset($_SERVER['HTTP_HOST']) AND $_SERVER['HTTP_HOST'] != '')
			{
				$host .= $_SERVER['HTTP_HOST'].'/';
			}

			$self = ( ! isset($_SERVER['PHP_SELF']) OR $_SERVER['PHP_SELF'] == '') ? '' : substr($_SERVER['PHP_SELF'], 1);

			// Since the CP access file can be inside or outside of the "system"
			// folder we will do a little test to help us set the site_url item
			$_selfloc = (is_dir('./installer/')) ? SELF.'/'.SYSDIR : SELF;

			$this->userdata['site_url'] = $host.substr($self, 0, - strlen($_selfloc));

			$vars['site_url'] = rtrim($this->userdata['site_url'], '/').'/'.$this->userdata['site_index'];

			$this->logger->updater("Update complete. Now running version {$this->version}.");

			// List any update notices we have
			$vars['update_notices'] = $this->update_notices->get();

			// Did we just install?
			$member_count = ee()->db->count_all_results('members');
			$last_visit = ee()->db->select('last_visit')
				->where('last_visit', 0)
				->count_all_results('members');
			$type = ($member_count == 1 && $last_visit == 1) ? 'install' : 'update';

			$this->show_success($type, $vars);
			return FALSE;
		}

		// Before moving on, let's load the update file to make sure it's readable
		$ud_file = 'ud_'.$this->next_ud_file.'.php';

		if ( ! include(APPPATH.'updates/'.$ud_file))
		{
			$this->set_output('error', array('error' => lang('unreadable_files')));
			return FALSE;
		}

		// Assign the config and DB arrays to class variables so we don't have
		// to reload them.
		$this->_config = $config;
		$this->_db = $db;

		// Set the flag
		$this->is_installed = TRUE;

		// Onward!
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * New installation form
	 * @return void
	 */
	private function install_form($errors = FALSE)
	{
		// Reset current step
		$this->current_step = 1;

		// Assign the _POST array values
		$this->assign_install_values();

		$vars = array();

		// Are there any errors to display? When the user submits the
		// installation form, the $this->do_install() function is called. In
		// the event of errors the form will be redisplayed with the error
		// message
		$vars['errors'] = $errors;

		$vars['action'] = $this->set_qstr('do_install');
		$this->subtitle = lang('required_fields');

		// Display the form and pass the userdata array to it
		$this->title = sprintf(lang('install_title'), $this->version);
		$this->set_output('install_form', array_merge($vars, $this->userdata));
	}

	// --------------------------------------------------------------------

	public function valid_db_prefix($db_prefix)
	{
		// DB Prefix has some character restrictions
		if ( ! preg_match("/^[0-9a-zA-Z\$_]*$/", $db_prefix))
		{
			ee()->form_validation->set_message(
				'valid_db_prefix',
				lang('database_prefix_invalid_characters')
			);
			return FALSE;
		}

		// The DB Prefix should not include "exp_"
		if ( strpos($db_prefix, 'exp_') !== FALSE)
		{
			ee()->form_validation->set_message(
				'valid_db_prefix',
				lang('database_prefix_contains_exp_')
			);
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Perform the installation
	 * @return void
	 */
	private function do_install()
	{
		// Make sure the current step is the correct number
		$this->current_step = 2;

		// Assign the _POST array values
		$this->assign_install_values();
		$this->load->library('javascript');

		// Setup some basic configs for validation
		ee()->config->set_item('un_min_len', 4);
		ee()->config->set_item('pw_min_len', 5);

		// Setup form validation
		ee()->lang->loadfile('myaccount');
		ee()->load->library('form_validation');
		ee()->form_validation->set_error_delimiters('<em>', '</em>');
		ee()->form_validation->set_rules(array(
			array(
				'field' => 'db_hostname',
				'label' => 'lang:db_hostname',
				'rules' => 'required'
			),
			array(
				'field' => 'db_name',
				'label' => 'lang:db_name',
				'rules' => 'required'
			),
			array(
				'field' => 'db_username',
				'label' => 'lang:db_username',
				'rules' => 'required'
			),
			array(
				'field' => 'db_prefix',
				'label' => 'lang:db_prefix',
				'rules' => 'required|max_length[30]|callback_valid_db_prefix'
			),
			array(
				'field' => 'username',
				'label' => 'lang:username',
				'rules' => 'required|valid_username'
			),
			array(
				'field' => 'install_default_theme',
				'label' => 'lang:install_default_theme',
				'rules' => ''
			),
			array(
				'field' => 'password',
				'label' => 'lang:password',
				'rules' => 'required|valid_password[username]'
			),
			array(
				'field' => 'email_address',
				'label' => 'lang:email_address',
				'rules' => 'required|valid_email'
			),
		));

		// Bounce if anything failed
		if ( ! ee()->form_validation->run())
		{
			return $this->install_form();
		}

		// Start our error trapping
		$errors = array();

		// Connect to the database.  We pass a multi-dimensional array since
		// that's what is normally found in the database config file
		$db = array(
			'hostname' => $this->userdata['db_hostname'],
			'username' => $this->userdata['db_username'],
			'password' => $this->userdata['db_password'],
			'database' => $this->userdata['db_name'],
			'dbdriver' => $this->userdata['dbdriver'],
			'pconnect' => ($this->userdata['db_conntype'] == 1) ? TRUE : FALSE,
			'dbprefix' => ($this->userdata['db_prefix'] == '') ? 'exp_' : preg_replace("#([^_])/*$#", "\\1_", $this->userdata['db_prefix']),
			'swap_pre' => 'exp_',
			'db_debug' => TRUE, // We show our own errors
			'cache_on' => FALSE,
			'autoinit' => FALSE, // We'll initialize the DB manually
			'char_set' => 'utf8',
			'dbcollat' => 'utf8_general_ci'
		);

		if ( ! $this->db_connect($db))
		{
			$errors[] = lang('database_no_connect');
		}

		// Does the specified database schema type exist?
		if ( ! file_exists(APPPATH.'schema/'.$this->userdata['dbdriver'].'_schema.php'))
		{
			$errors[] = lang('unreadable_dbdriver');
		}

		// Were there errors?
		// If so we display the form and pass the userdata array to it
		if (count($errors) > 0)
		{
			$this->userdata['errors'] = $errors;
			$this->set_output('install_form', $this->userdata);
			return FALSE;
		}

		// --------------------------------------------------------------------

		// Set the screen name to be the same as the username
		$this->userdata['screen_name'] = $this->userdata['username'];

		// Load the DB schema
		require APPPATH.'schema/'.$this->userdata['dbdriver'].'_schema.php';
		$this->schema = new EE_Schema();

		// Assign the userdata array to the schema class
		$this->schema->userdata   =& $this->userdata;
		$this->schema->theme_path =& $this->theme_path;

		// Time
		$this->schema->now   = $this->now;
		$this->schema->year  = $this->year;
		$this->schema->month = $this->month;
		$this->schema->day   = $this->day;

		// --------------------------------------------------------------------

		// Safety check: Is the user trying to install to an existing installation?
		// This can happen if someone mistakenly nukes their config.php file
		// and then trying to run the installer...

		$query = ee()->db->query($this->schema->sql_find_like());

		if ($query->num_rows() > 0 AND ! isset($_POST['install_override']))
		{
			return $this->set_output('error', array(
				'error' => lang('install_detected_msg')
			));
		}

		// --------------------------------------------------------------------

		// No errors?  Move our tanks to the front line and prepare for battle!

		// We no longer need this:
		unset($this->userdata['password_confirm']);
		unset($_POST['password_confirm']);

		// We assign some values to the Schema class
		$this->schema->default_entry = $this->default_channel_entry();

		// Encrypt the password and unique ID
		ee()->load->library('auth');
		$hashed_password = ee()->auth->hash_password($this->userdata['password']);
		$this->userdata['password']  = $hashed_password['password'];
		$this->userdata['salt']      = $hashed_password['salt'];
		$this->userdata['unique_id'] = random_string('encrypt');

		// --------------------------------------------------------------------

		// This allows one to override the functions in Email Data below, thus allowing custom speciality templates
		if (file_exists($this->theme_path.$this->userdata['theme'].'/speciality_templates.php'))
		{
			require $this->theme_path.$this->userdata['theme'].'/speciality_templates.php';
		}

		// Load the email template
		require_once EE_APPPATH.'/language/'.$this->userdata['deft_lang'].'/email_data.php';

		// Install Database Tables!
		if ( ! $this->schema->install_tables_and_data())
		{
			$this->set_output('error', array('error' => lang('improper_grants')));
			return FALSE;
		}

		// Write the config file
		// it's important to do this first so that our site prefs and config file
		// visible for module and accessory installers
		if ($this->write_config_data() == FALSE)
		{
			$this->set_output('error', array('error' => lang('unwritable_config')));
			return FALSE;
		}

		// Add any modules required by the theme to the required modules array
		if ($this->userdata['theme'] != '' && isset($this->theme_required_modules[$this->userdata['theme']]))
		{
			$this->required_modules = array_merge($this->required_modules, $this->theme_required_modules[$this->userdata['theme']]);
		}

		// Install Modules!
		if ( ! $this->install_modules())
		{
			$this->set_output('error', array('error' => lang('improper_grants')));
			return FALSE;
		}

		// Install Site Theme!
		// This goes last because a custom installer might create Member Groups
		// besides the default five, which might affect the Template Access
		// permissions.
		if ($this->userdata['install_default_theme'] == 'y'
			&& ! $this->install_site_theme())
		{
			$this->set_output('error', array('error' => lang('improper_grants')));
			return FALSE;
		}

		// Build our success links
		$vars['installer_path'] = '/'.SYSDIR.'/installer';
		$vars['site_url'] = rtrim($this->userdata['site_url'], '/').'/'.$this->userdata['site_index'];

		// If errors are thrown, this is were we get the "human" names for those modules
		$vars['module_names'] = $this->userdata['modules'];

		// A flag used to determine if module install errors need to be shown in the view
		$vars['errors'] = count($this->module_install_errors);

		// The list of errors into a variable passed into the view
		$vars['error_messages'] = $this->module_install_errors;

		// Woo hoo! Success!
		$this->show_success('install', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Show installation or upgrade succes page
	 * @param  string $type               'update' or 'install'
	 * @param  array  $template_variables Anything to parse in the template
	 * @return void
	 */
	private function show_success($type = 'update', $template_variables = array())
	{
		// Check to see if there are any errors, if not, bypass this screen
		if (empty($template_variables['error_messages']))
		{
			if ($this->rename_installer())
			{
				ee()->load->helper('url');
				redirect($this->userdata['cp_url'].'?/cp/login&return=&after='.$type);
			}
		}

		// Make sure the title and subtitle are correct, current_step should be
		// the same as the number of steps
		$this->current_step = $this->steps;
		$this->title = sprintf(lang($type.'_success'), $this->version);
		$this->subtitle = lang('completed');

		// Put the version number in the success note
		$template_variables['success_note'] = sprintf(lang($type.'_success_note'), $this->version);

		// Send them to their CP via the form
		$template_variables['action'] = $this->userdata['cp_url'];
		$template_variables['method'] = 'get';

		$this->set_output('success', $template_variables);
	}

	// --------------------------------------------------------------------

	/**
	 * Assigns the values submitted in the settings form
	 * @return void
	 */
	private function assign_install_values()
	{
		// Set the path to the site and CP
		$host = 'http://';
		if (isset($_SERVER['HTTP_HOST']) AND $_SERVER['HTTP_HOST'] != '')
		{
			$host .= $_SERVER['HTTP_HOST'].'/';
		}

		$self = ( ! isset($_SERVER['PHP_SELF']) OR $_SERVER['PHP_SELF'] == '') ? '' : substr($_SERVER['PHP_SELF'], 1);
		$self = htmlspecialchars($self, ENT_QUOTES);

		$this->userdata['cp_url'] = ($self != '') ? $host.$self : $host.SELF;

		// license number
		$this->userdata['license_contact'] = '';
		$this->userdata['license_number'] = (IS_CORE) ? 'CORE LICENSE' : '';

		// Since the CP access file can be inside or outside of the "system" folder
		// we will do a little test to help us set the site_url item
		$_selfloc = (is_dir('./installer/')) ? SELF.'/'.SYSDIR : SELF;

		// Set the site URL
		$this->userdata['site_url'] = $host.substr($self, 0, - strlen($_selfloc));

		// Set the URL for use in the form action
		$this->userdata['action'] = $this->set_qstr('do_install');

		$this->userdata['redirect_method']	= (DIRECTORY_SEPARATOR == '/') ? 'redirect' : 'refresh';

		// Assign the _POST values submitted via the form to our main data array
		foreach ($this->userdata as $key => $val)
		{
			if ($this->input->post($key) !== FALSE)
			{
				// module options is an array of checkboxes, so include all of them
				// but check any that the user submitted checked
				if ($key == 'modules')
				{
					foreach ($this->input->post($key) as $name)
					{
						$this->userdata[$key][$name]['checked'] = TRUE;
					}
				}
				else
				{
					$this->userdata[$key] = $this->input->post($key);

					// Be a bit more friendly by trimming most inputs, but leave passwords as-is
					if (! in_array($key, array('db_password', 'password', 'password_confirm')))
					{
						$this->userdata[$key] = trim($this->userdata[$key]);
					}
				}
			}
		}

		// if 'modules' isn't in the POST data, pre-check the defaults and third
		// party modules
		if ($this->input->post('modules') === FALSE)
		{
			foreach ($this->userdata['modules'] as $name => $info)
			{
				if (in_array($name, $this->default_installed_modules) OR ! in_array($name, $this->native_modules))
				{
					$this->userdata['modules'][$name]['checked'] = TRUE;
				}
			}

		}

		// Make sure the site_url has a trailing slash
		$this->userdata['site_url'] = preg_replace("#([^/])/*$#", "\\1/", $this->userdata['site_url']);

		// Set the checkbox values
		$prefs = array(
			'db_conntype'		=> array(
				'persistent' => array('persistent', 'nonpersistent')
			)
		);


		foreach ($prefs as $name => $value)
		{
			foreach ($value as $k => $v)
			{
				if ($this->userdata[$name] == $k)
				{
					$this->userdata[$v[0]] = 'checked="checked"';
					$this->userdata[$v[1]] = '';
				}
				else
				{
					$this->userdata[$v[0]] = '';
					$this->userdata[$v[1]] = 'checked="checked"';
				}
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Show the update form
	 * @return void
	 */
	private function update_form()
	{
		$this->title = sprintf(lang('update_title'), $this->installed_version, $this->version);
		$vars['action'] = $this->set_qstr('do_update');
		$this->set_output('update_form', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Perform the update
	 * @return void
	 */
	private function do_update()
	{
		// Make sure the current step is the correct number
		$this->current_step = ($this->addon_step) ? 3 : 2;

		$this->load->library('javascript');

		$this->load->library('progress');

		$next_version = $this->next_update;
		$this->progress->prefix = $next_version.': ';

		// Is this a call from the Progress Indicator?
		if ($this->input->get('progress') == 'yes')
		{
			echo $this->progress->get_state();
			exit;
		}
		elseif ($this->input->get('progress') == 'no')	// done with this step, moving on...
		{
			// End URL
			$this->refresh = TRUE;
			$this->refresh_url = $this->set_qstr('do_update&agree=yes');
			$this->title = sprintf(lang('updating_title'), $this->installed_version, $this->version);
			$this->subtitle = lang('processing');
			return $this->set_output(
				'update_msg',
				array(
					'remaining_updates' => $this->remaining_updates,
					'next_version'		=> $this->progress->prefix.lang('version_update_text')
				)
			);
		}

		// Clear any latent status messages still present in the PHP session
		$this->progress->clear_state();

		// Set a liberal execution time limit, some of these updates are pretty
		// big.
		@set_time_limit(0);

		// Instantiate the updater class
		$UD = new Updater;
		$method = 'do_update';

		$this->load->library('smartforge');

		$this->logger->updater("Updating to {$next_version}");

		if ($this->config->item('ud_next_step') != FALSE)
		{
			$method = $this->config->item('ud_next_step');

			if ( ! method_exists($UD, $method))
			{
				$this->set_output('error', array('error' => str_replace('%x', htmlentities($method), lang('update_step_error'))));
				return FALSE;
			}
		}

		// is there a survey for this version?
		$survey_view = 'survey_'.$this->next_ud_file;

		// if (file_exists(APPPATH.'views/surveys/survey_'.$this->next_update.'.php'))
		// {
		// 	$this->load->library('survey');

		// 	// if we have data, send it on to the updater, otherwise, ask
		// 	// permission and show the survey
		// 	if ( ! $this->input->get_post('participate_in_survey'))
		// 	{
		// 		$data = array(
		// 			'action_url'            => $this->set_qstr('do_update&agree=yes'),
		// 			'ee_version'            => $this->next_update,
		// 			'participate_in_survey' => array(
		// 				'name'    => 'participate_in_survey',
		// 				'id'      => 'participate_in_survey',
		// 				'value'   => 'y',
		// 				'checked' => TRUE
		// 			)
		// 		);

		// 		foreach ($this->survey->fetch_anon_server_data() as $key => $val)
		// 		{
		// 			if (in_array($key, array('php_extensions', 'addons')))
		// 			{
		// 				$val = implode(', ', json_decode($val));
		// 			}

		// 			$data['anonymous_server_data'][$key] = $val;
		// 		}

		// 		$this->set_output('surveys/survey_'.$this->next_update, $data);
		// 		return FALSE;
		// 	}
		// 	elseif ($this->input->get_post('participate_in_survey') == 'y')
		// 	{
		// 		// if any preprocessing needs to be done on the POST data, we do
		// 		// it here
		// 		if (method_exists($UD, 'pre_process_survey'))
		// 		{
		// 			$UD->pre_process_survey();
		// 		}

		// 		$this->survey->send_survey($this->next_update);
		// 	}
		// }

		if (($status = $UD->{$method}()) === FALSE)
		{
			$error_msg = lang('update_error');

			if ( ! empty($UD->errors))
			{
				$error_msg .= "</p>\n\n<ul>\n\t<li>" . implode("</li>\n\t<li>", $UD->errors) . "</li>\n</ul>\n\n<p>";
			}

			$this->set_output('error', array('error' => $error_msg));
			return FALSE;
		}

		if ($status !== TRUE)
		{
			$this->config->set_item('ud_next_step', $status);
			$this->next_update = $this->installed_version;
		}
		elseif ($this->remaining_updates == 1)
		{
			// If this is the last application update, run the module updater
			$this->update_modules();
		}

		// Update the config file
		$this->config->_update_config(array('app_version' => $this->next_update.$UD->version_suffix), array('ud_next_step' => ''));

		// EE's application settings are now in the config, so we need to make
		// two on the fly switches for the rest of the wizard to work.
		$this->set_base_url();
		$this->config->set_item('enable_query_strings', TRUE);

		// Set the refresh value
		$this->refresh = TRUE;
		$this->refresh_url = $this->set_qstr('do_update&agree=yes');

		// Kill the refresh if we're progressing with js
		if ($this->input->get('ajax_progress') == 'yes')
		{
			$this->refresh = FALSE;
		}

		// Prep the javascript
		$progress_head = $this->progress->fetch_progress_header(array(
			'process_url'        => $this->refresh_url,
			'progress_container' => '#js_progress',
			'state_url'          => $this->set_qstr('do_update&agree=yes&progress=yes'),
			'end_url'            => $this->set_qstr('do_update&agree=yes&progress=no&ajax_progress=yes')
		));

		$this->title = sprintf(lang('updating_title'), $this->installed_version, $this->version);
		$this->subtitle = lang('processing');
		$this->set_output(
			'update_msg',
			array(
				'remaining_updates' => $this->remaining_updates,
				'extra_header'      => $progress_head,
				'next_version'      => $this->progress->prefix.lang('version_update_text')
			)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Determine which update should be performed - Reads though the "updates"
	 * directory and makes a list of all available updates
	 * @param int $current_version The version we're currently running without
	 *                             dots (e.g. 300 or 292)
	 * @return boolean             TRUE if successful, FALSE if not
	 */
	private function fetch_updates($current_version = 0)
	{
		$next_update = FALSE;
		$next_ud_file = FALSE;

		$remaining_updates = 0;

		$path = APPPATH.'updates/';

		if ( ! is_readable($path))
		{
			return FALSE;
		}

		$files = new FilesystemIterator($path);

		foreach ($files as $file)
		{
			$file_name = $file->getFilename();

			if (preg_match('/^ud_0*(\d+)_0*(\d+)_0*(\d+).php$/', $file_name, $m))
			{
				$file_version = "{$m[1]}.{$m[2]}.{$m[3]}";

				if (version_compare($file_version, $current_version, '>'))
				{
					$remaining_updates++;

					if ( ! $next_update || version_compare($file_version, $next_update, '<'))
					{
						$next_update = $file_version;
						$next_ud_file = substr($file_name, 3, -4);
					}
				}
			}
		}

		$this->next_update = $next_update;
		$this->next_ud_file = $next_ud_file;
		$this->remaining_updates = $remaining_updates;

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Connect to the database
	 *
	 * @param array $db Associative array containing db connection data
	 * @return boolean  TRUE if successful, FALSE if not
	 */
	private function db_connect($db)
	{
		if (count($db) == 0)
		{
			return FALSE;
		}

		ee()->load->database($db, FALSE, TRUE);
		// Force caching off
		ee()->db->save_queries = TRUE;

		// Ask for exceptions so we can show proper errors in the form
		ee()->db->db_exception = TRUE;

		try {
			ee()->db->initialize();
		} catch (Exception $e) {
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Get an actual path to certain items, namely global images, themes, and
	 * javascript.
	 * @param string  $path  The path to determine
	 * @param integer $depth How many levels up we are from the original
	 *                       directory
	 * @return string The realized path
	 */
	private function set_path($path = '', $depth = 0)
	{
		if ( ! is_dir($path) && $depth < 10)
		{
			$path = $this->set_path('../'.$path, ++$depth);
		}

		return $path;
	}

	// --------------------------------------------------------------------

	/**
	 * Set output
	 * Loads the "container" view file and sets the content
	 * @param string $view  The name of the view to load
	 * @param array  $template_variables Associative array to pass to view
	 * @return void
	 */
	private function set_output($view, $template_variables = array())
	{
		ee()->load->library('view');

		if (IS_CORE)
		{
			$this->title = str_replace(
				'ExpressionEngine',
				'ExpressionEngine Core',
				$this->title
			);
		}

		// If we're dealing with an error, change the title to indicate that
		if ($view == "error")
		{
			$this->title = ($this->is_installed)
				? sprintf(lang('error_updating'), $this->installed_version, $this->version)
				: sprintf(lang('error_installing'), $this->version);
			$this->subtitle = lang('stopped');
		}

		// Only show steps during upgrades
		if ($this->is_installed)
		{
			$suffix = sprintf(lang('subtitle_step'), $this->current_step, $this->steps);
			$this->subtitle .= (empty($this->subtitle))
				? $suffix
				: ' <span class="faded">|</span> '.$suffix;
		}

		$version = explode('.', $this->version, 2);
		$data = array(
			'title'             => $this->title,
			'subtitle'          => $this->subtitle,
			'refresh'           => $this->refresh,
			'refresh_url'       => $this->refresh_url,
			'ajax_progress'     => (ee()->input->get('ajax_progress') == 'yes'),
			'image_path'        => $this->image_path,

			// TODO-WB: Change src to compressed before launch
			'javascript_path'   => $this->set_path('themes/ee/javascript/src/'),

			'version'           => $this->version,
			'version_major'     => $version[0],
			'version_minor'     => $version[1],
			'installed_version' => $this->installed_version,

			'next_version'      => substr($this->next_update, 0, 1).'.'.substr($this->next_update, 1, 1).'.'.substr($this->next_update, 2, 1),
			'languages'         => $this->languages,
			'theme_url'         => $this->set_path('themes'),
			'is_core'           => (IS_CORE) ? 'Core' : '',

			'action'            => '',
			'method'            => 'post'
		);

		$data = array_merge($data, $template_variables);

		ee()->load->helper('language');
		ee()->load->view('container', array_merge(
			array('content' => ee()->load->view($view, $data, TRUE)),
			$data
		));
	}

	// --------------------------------------------------------------------

	/**
	 * Set the base URL and index values so our links work properly
	 * @return void
	 */
	private function set_base_url()
	{
		// We completely kill the site URL value.  It's now blank.
		// This enables us to use only the "index.php" part of the URL.
		// Since we do not know where the CP access file is being loaded from
		// we need to use only the relative URL
		$this->config->set_item('site_url', '');
		$this->config->set_item('base_url', ''); // Same with the CI base_url

		// We set the index page to the SELF value.
		// but it might have been renamed by the user
		$this->config->set_item('index_page', SELF);
		$this->config->set_item('site_index', SELF); // Same with the CI site_index
	}

	// --------------------------------------------------------------------

	/**
	 * Create the query string needed for form actions
	 * @param string  $method The method name for the action
	 */
	private function set_qstr($method = '')
	{
		$query_string = 'C=wizard&M='.$method.'&language='.$this->mylang;
		return site_url($query_string);
	}

	// --------------------------------------------------------------------

	/**
	 * Install the default site theme
	 * @return boolean  TRUE if successful, FALSE if not
	 */
	function install_site_theme()
	{
		$this->userdata['theme'] = (IS_CORE)
			? 'agile_records_core'
			: 'agile_records';

		$default_group = 'site';

		$default_template_preferences = array(
			'caching'       => 'n',
			'cache_refresh' => 0,
			'php_parsing'   => 'none', // none, input, output
		);

		// Uses the Labels of the default four groups, as it is easier than the
		// Group IDs, let's be honest
		$default_template_access = array(
			'Banned'  => 'n',
			'Guests'  => 'y',
			'Members' => 'y',
			'Pending' => 'y'
		);

		$template_access = array();
		$template_preferences = array();

		// --------------------------------------------------------------------

		/**
		 * Site Theme Overrides?
		 */

		if (file_exists($this->theme_path.$this->userdata['theme'].'/theme_preferences.php'))
		{
			require $this->theme_path.$this->userdata['theme'].'/theme_preferences.php';
		}

		// --------------------------------------------------------------------

		/**
		 * Get the Default Preferences and Access Ready for Insert
		 */

		$default_preferences = array(
			'allow_php'          => (in_array($default_template_preferences['php_parsing'], array('input', 'output'))) ? 'y' : 'n',
			'php_parse_location' => ($default_template_preferences['php_parsing'] == 'input') ? 'i' : 'o',
			'cache'              => ($default_template_preferences['caching'] == 'y') ? 'y' : 'n',
			'refresh'            => (round((int) $default_template_preferences['cache_refresh']) > 0) ? round( (int) $default_template_preferences['cache_refresh']) : 0
		);

		$group_ids      = array();
		$default_access = array();

		ee()->db->select(array('group_title', 'group_id'));
		$query = ee()->db->get_where('member_groups', array('site_id' => 1));

		foreach($query->result_array() as $row)
		{
			// For use with Template Specific Access from Theme Preferences
			$group_ids[$row['group_title']] = $row['group_id'];

			// Like EE, a group is only denied access if they are specifically
			// denied. Groups not in the list are granted access by default.
			if (isset($default_template_access[$row['group_title']])
				&& $default_template_access[$row['group_title']] == 'n')
			{
				$default_access[$row['group_id']] = $row['group_id'];
			}
		}

		// --------------------------------------------------------------------

		/**
		 * Read and Install Template Groups and Templates
		 */

		$i = 0;
		$template_groups = array();

		$allowed_suffixes = array('html', 'webpage', 'php', 'css', 'xml', 'feed', 'rss', 'atom', 'static', 'txt', 'js');

		$template_type_conversions = array(
			'txt'  => 'static',
			'rss'  => 'feed',
			'atom' => 'feed',
			'html' => 'webpage',
			'php'  => 'webpage',
		);

		if ($this->userdata['theme'] != ''
			&& $this->userdata['theme'] != 'none'
			&& ($fp = opendir($this->theme_path.$this->userdata['theme'])))
		{
			while (FALSE !== ($folder = readdir($fp)))
			{
				if (is_dir($this->theme_path.$this->userdata['theme'].'/'.$folder)
					&& substr($folder, -6) == '.group')
				{
					++$i;

					$group = preg_replace("#[^a-zA-Z0-9_\-/\.]#i", '', substr($folder, 0, -6));

					$data = array(
						'group_id'        => $i,
						'group_name'      => $group,
						'group_order'     => $i,
						'is_site_default' => ($default_group == $group) ? 'y' : 'n'
					);

					ee()->db->insert('template_groups', $data);

					$template_groups[substr($folder, 0, -6)] = array();

					$templates = array('index.html' => 'index.html');  // Required

					if ($tgfp = opendir($this->theme_path.$this->userdata['theme'].'/'.$folder))
					{
						while (FALSE !== ($file = readdir($tgfp)))
						{
							if (@is_file($this->theme_path.$this->userdata['theme'].'/'.$folder.'/'.$file)
								&& $file != '.DS_Store'
								&& $file != '.htaccess')
							{
								$templates[$file] = $file;
							}
						}

						@closedir($tgfp);
					}

					$done = array(); // Prevents duplicates with all of the possible suffixes allowed

					foreach($templates as $file)
					{
						if (strpos($file, '.') === FALSE)
						{
							$name = $file;
							$type = 'webpage';
						}
						else
						{
							$type = strtolower(ltrim(strrchr($file, '.'), '.'));
							$name = preg_replace("#[^a-zA-Z0-9_\-/\.]#i", '', substr($file, 0, -(strlen($type) + 1)));

							if ( ! in_array($type, $allowed_suffixes))
							{
								$type = 'html';
							}

							if (isset($template_type_conversions[$type]))
							{
								$type = $template_type_conversions[$type];;
							}
						}

						if (in_array($name, $done))
						{
							continue;
						}

						$done[] = $name;

						$data = array(
							'group_id'       => $i,
							'template_name'  => $name,
							'template_type'  => $type,
							'template_data'  => file_get_contents($this->theme_path.$this->userdata['theme'].'/'.$folder.'/'.$file),
							'edit_date'      => $this->now,
							'last_author_id' => 1
						);

						$data = array_merge($data, $default_preferences);

						// Specific Template Preferences
						if (isset($template_preferences[$group][$name]))
						{
							foreach($template_preferences[$group][$name] as $type => $value)
							{
								switch($type)
								{
									case 'caching':
										$data['cache'] = ($value == 'y') ? 'y' : 'n';
										break;
									case 'cache_refresh':
										$data['refresh'] = round((int) $value);
										break;
									case 'php_parsing':
										switch($value)
										{
											case 'input':
												$data['allow_php'] = 'y';
												$data['php_parse_location'] = 'i';
												break;
											case 'output':
												$data['allow_php'] = 'y';
												$data['php_parse_location'] = 'o';
												break;
											case 'none':
												$data['allow_php'] = 'n';
												$data['php_parse_location'] = 'o';
												break;
										}
										break;
								}
							}
						}

						ee()->db->insert('templates', $data);

						$template_id = ee()->db->insert_id();

						// Access.  Why, oh, why must this be so complicated?! Ugh...
						$access = $default_access;

						if (isset($template_access[$group][$name]))
						{
							foreach($template_access[$group][$name] as $group_title => $setting)
							{
								if ( ! isset($group_ids[$group_title])) continue;

								if ($setting == 'y')
								{
									unset($access[$group_ids[$group_title]]);
								}
								else
								{
									$access[$group_ids[$group_title]] = $group_ids[$group_title];
								}
							}
						}

						foreach($access as $group_id)
						{
							ee()->db->insert('template_no_access',  array('template_id' => $template_id, 'member_group' => $group_id));
						}
					}
				}
			}

			closedir($fp);

			//
			// read and create snippets and global variables, if they exist
			//

			foreach(array('snippets', 'global_variables') as $type)
			{
				if (is_dir($this->theme_path.$this->userdata['theme'].'/'.$type))
				{
					$this->load->helper('file');
					$dir = rtrim(realpath($this->theme_path.$this->userdata['theme'].'/'.$type), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
					$vars = array();

					// can't use get_filenames() since it doesn't read hidden files
					if ($fp = opendir($dir))
					{
						while (FALSE !== ($file = readdir($fp)))
						{
							if (is_file($dir.$file) && $file != '.DS_Store')
							{
								$vars[$file] = read_file($dir.$file);
							}
						}
					}

					foreach ($vars as $name => $contents)
					{
						if ($type == 'snippets')
						{
							ee()->db->insert('snippets', array('snippet_name' => $name, 'snippet_contents' => $contents, 'site_id' => 1));
						}
						else
						{
							ee()->db->insert('global_variables', array('variable_name' => $name, 'variable_data' => $contents, 'site_id' => 1));
						}
					}
				}
			}

			// Install any default structure and content that the theme may have
			if (file_exists($this->theme_path.$this->userdata['theme'].'/default_content.php'))
			{
				require $this->theme_path.$this->userdata['theme'].'/default_content.php';
			}
		}

		return TRUE;
	}


	// --------------------------------------------------------------------

	/**
	 * Install the Modules
	 * @return boolean  TRUE if successful, FALSE if not
	 */
	private function install_modules()
	{
		$this->load->library('layout');

		// Install required modules
		foreach($this->required_modules as $module)
		{
			$path = SYSPATH.'ee/expressionengine/modules/'.$module.'/';

			if (file_exists($path.'upd.'.$module.'.php'))
			{
				// Add the helper/library load path and temporarily
				$this->load->add_package_path($path, FALSE);

				require $path.'upd.'.$module.'.php';

				$class = ucfirst($module).'_upd';

				$UPD = new $class;
				$UPD->_ee_path = EE_APPPATH;
				$UPD->install_errors = array();

				if (method_exists($UPD, 'install'))
				{
					$UPD->install();
					if (count($UPD->install_errors) > 0)
					{
						// clean and combine
						$this->module_install_errors[$module] = array_map('htmlentities', $UPD->install_errors);
					}
				}

				// remove package path
				$this->load->remove_package_path($path);
			}
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Write the config file
	 * @return boolean  TRUE if successful, FALSE if not
	 */
	private function write_config_data()
	{
		$captcha_url = rtrim($this->userdata['site_url'], '/').'/';
		$captcha_url .= 'images/captchas/';

		foreach (array('avatar_path', 'photo_path', 'signature_img_path', 'pm_path', 'captcha_path', 'theme_folder_path') as $path)
		{
			$prefix = ($path != 'theme_folder_path') ? $this->root_theme_path : '';
			$this->userdata[$path] = rtrim(realpath($prefix.$this->userdata[$path]), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
		}

		$config = array(
			'db_hostname'               => $this->userdata['db_hostname'],
			'db_username'               => $this->userdata['db_username'],
			'db_password'               => $this->userdata['db_password'],
			'db_database'               => $this->userdata['db_name'],
			'db_dbdriver'               => $this->userdata['dbdriver'],
			'db_pconnect'               => ($this->userdata['db_conntype'] == 1) ? TRUE : FALSE,
			'db_dbprefix'               => ($this->userdata['db_prefix'] == '') ? 'exp_' : preg_replace("#([^_])/*$#", "\\1_", $this->userdata['db_prefix']),
			'app_version'               => $this->userdata['app_version'],
			'license_contact'           => $this->userdata['license_contact'],
			'license_number'            => trim($this->userdata['license_number']),
			'debug'                     => '1',
			'cp_url'                    => $this->userdata['cp_url'],
			'site_index'                => $this->userdata['site_index'],
			'site_label'                => $this->userdata['site_label'],
			'site_url'                  => $this->userdata['site_url'],
			'theme_folder_url'          => $this->userdata['site_url'].'themes/',
			'doc_url'                   => $this->userdata['doc_url'],
			'webmaster_email'           => $this->userdata['email_address'],
			'webmaster_name'            => '',
			'channel_nomenclature'      => 'channel',
			'max_caches'                => '150',
			'cache_driver'					=> 'file',
			'captcha_url'               => $captcha_url,
			'captcha_path'              => $this->userdata['captcha_path'],
			'captcha_font'              => 'y',
			'captcha_rand'              => 'y',
			'captcha_require_members'   => 'n',
			'require_captcha'           => 'n',
			'enable_sql_caching'        => 'n',
			'force_query_string'        => 'n',
			'show_profiler'             => 'n',
			'template_debugging'        => 'n',
			'include_seconds'           => 'n',
			'cookie_domain'             => '',
			'cookie_path'               => '',
			'cookie_prefix'             => '',
			'website_session_type'      => 'c',
			'cp_session_type'           => 'c',
			'cookie_httponly'           => 'y',
			'allow_username_change'     => 'y',
			'allow_multi_logins'        => 'y',
			'password_lockout'          => 'y',
			'password_lockout_interval' => '1',
			'require_ip_for_login'      => 'y',
			'require_ip_for_posting'    => 'y',
			'require_secure_passwords'  => 'n',
			'allow_dictionary_pw'       => 'y',
			'name_of_dictionary_file'   => '',
			'xss_clean_uploads'         => 'y',
			'redirect_method'           => $this->userdata['redirect_method'],
			'deft_lang'                 => $this->userdata['deft_lang'],
			'xml_lang'                  => 'en',
			'send_headers'              => 'y',
			'gzip_output'               => 'n',
			'log_referrers'             => 'n',
			'max_referrers'             => '500',
			'is_system_on'              => 'y',
			'allow_extensions'          => 'y',
			'date_format'               => '%n/%j/%y',
			'time_format'               => '12',
			'include_seconds'           => 'n',
			'server_offset'             => '',
			'default_site_timezone'     => date_default_timezone_get(),
			'mail_protocol'             => 'mail',
			'smtp_server'               => '',
			'smtp_username'             => '',
			'smtp_password'             => '',
			'email_debug'               => 'n',
			'email_charset'             => 'utf-8',
			'email_batchmode'           => 'n',
			'email_batch_size'          => '',
			'mail_format'               => 'plain',
			'word_wrap'                 => 'y',
			'email_console_timelock'    => '5',
			'log_email_console_msgs'    => 'y',
			'cp_theme'                  => 'default',
			'log_search_terms'          => 'y',
			'un_min_len'                => '4',
			'pw_min_len'                => '5',
			'allow_member_registration' => 'n',
			'allow_member_localization' => 'y',
			'req_mbr_activation'        => 'email',
			'new_member_notification'   => 'n',
			'mbr_notification_emails'   => '',
			'require_terms_of_service'  => 'y',
			'default_member_group'      => '5',
			'profile_trigger'           => 'member',
			'member_theme'              => 'default',
			'enable_avatars'            => 'y',
			'allow_avatar_uploads'      => 'n',
			'avatar_url'                => $this->userdata['site_url'].$this->userdata['avatar_url'],
			'avatar_path'               => $this->userdata['avatar_path'],
			'avatar_max_width'          => '100',
			'avatar_max_height'         => '100',
			'avatar_max_kb'             => '50',
			'enable_photos'             => 'n',
			'photo_url'                 => $this->userdata['site_url'].$this->userdata['photo_url'],
			'photo_path'                => $this->userdata['photo_path'],
			'photo_max_width'           => '100',
			'photo_max_height'          => '100',
			'photo_max_kb'              => '50',
			'allow_signatures'          => 'y',
			'sig_maxlength'             => '500',
			'sig_allow_img_hotlink'     => 'n',
			'sig_allow_img_upload'      => 'n',
			'sig_img_url'               => $this->userdata['site_url'].$this->userdata['signature_img_url'],
			'sig_img_path'              => $this->userdata['signature_img_path'],
			'sig_img_max_width'         => '480',
			'sig_img_max_height'        => '80',
			'sig_img_max_kb'            => '30',
			'prv_msg_enabled'           => 'y',
			'prv_msg_allow_attachments' => 'y',
			'prv_msg_upload_path'       => $this->userdata['pm_path'],
			'prv_msg_max_attachments'   => '3',
			'prv_msg_attach_maxsize'    => '250',
			'prv_msg_attach_total'      => '100',
			'prv_msg_html_format'       => 'safe',
			'prv_msg_auto_links'        => 'y',
			'prv_msg_max_chars'         => '6000',
			'enable_template_routes'    => 'y',
			'strict_urls'               => 'y',
			'site_404'                  => '',
			'save_tmpl_revisions'       => 'n',
			'max_tmpl_revisions'        => '5',
			'save_tmpl_files'           => 'n',
			'tmpl_file_basepath'        => realpath('./user/templates/').DIRECTORY_SEPARATOR,
			'deny_duplicate_data'       => 'y',
			'redirect_submitted_links'  => 'n',
			'enable_censoring'          => 'n',
			'censored_words'            => '',
			'censor_replacement'        => '',
			'banned_ips'                => '',
			'banned_emails'             => '',
			'banned_usernames'          => '',
			'banned_screen_names'       => '',
			'ban_action'                => 'restrict',
			'ban_message'               => 'This site is currently unavailable',
			'ban_destination'           => 'http://www.yahoo.com/',
			'enable_emoticons'          => 'y',
			'emoticon_url'              => $this->userdata['site_url'].'images/smileys/',
			'recount_batch_total'       => '1000',
			'image_resize_protocol'     => 'gd2',
			'image_library_path'        => '',
			'thumbnail_prefix'          => 'thumb',
			'word_separator'            => 'dash',
			'use_category_name'         => 'n',
			'reserved_category_word'    => 'category',
			'auto_convert_high_ascii'   => 'n',
			'new_posts_clear_caches'    => 'y',
			'auto_assign_cat_parents'   => 'y',
			'new_version_check'         => 'y',
			'enable_throttling'         => 'n',
			'banish_masked_ips'         => 'y',
			'max_page_loads'            => '10',
			'time_interval'             => '8',
			'lockout_time'              => '30',
			'banishment_type'           => 'message',
			'banishment_url'            => '',
			'banishment_message'        => 'You have exceeded the allowed page load frequency.',
			'enable_search_log'         => 'y',
			'max_logged_searches'       => '500',
			'mailinglist_enabled'       => 'y',
			'mailinglist_notify'        => 'n',
			'mailinglist_notify_emails' => '',
			'memberlist_order_by'       => "total_posts",
			'memberlist_sort_order'     => "desc",
			'memberlist_row_limit'      => "20",
			'is_site_on'                => 'y',
			'theme_folder_path'         => $this->userdata['theme_folder_path'],
		);

		// Default Administration Prefs
		$admin_default = array(
			'site_index',
			'site_url',
			'theme_folder_url',
			'webmaster_email',
			'webmaster_name',
			'channel_nomenclature',
			'max_caches',
			'captcha_url',
			'captcha_path',
			'captcha_font',
			'captcha_rand',
			'captcha_require_members',
			'require_captcha',
			'enable_sql_caching',
			'force_query_string',
			'show_profiler',
			'template_debugging',
			'include_seconds',
			'cookie_domain',
			'cookie_path',
			'website_session_type',
			'cp_session_type',
			'allow_username_change',
			'allow_multi_logins',
			'password_lockout',
			'password_lockout_interval',
			'require_ip_for_login',
			'require_ip_for_posting',
			'require_secure_passwords',
			'allow_dictionary_pw',
			'name_of_dictionary_file',
			'xss_clean_uploads',
			'redirect_method',
			'deft_lang',
			'xml_lang',
			'send_headers',
			'gzip_output',
			'log_referrers',
			'max_referrers',
			'date_format',
			'time_format',
			'include_seconds',
			'server_offset',
			'default_site_timezone',
			'mail_protocol',
			'smtp_server',
			'smtp_username',
			'smtp_password',
			'email_debug',
			'email_charset',
			'email_batchmode',
			'email_batch_size',
			'mail_format',
			'word_wrap',
			'email_console_timelock',
			'log_email_console_msgs',
			'cp_theme',
			'log_search_terms',
			'deny_duplicate_data',
			'redirect_submitted_links',
			'enable_censoring',
			'censored_words',
			'censor_replacement',
			'banned_ips',
			'banned_emails',
			'banned_usernames',
			'banned_screen_names',
			'ban_action',
			'ban_message',
			'ban_destination',
			'enable_emoticons',
			'emoticon_url',
			'recount_batch_total',
			'new_version_check',
			'enable_throttling',
			'banish_masked_ips',
			'max_page_loads',
			'time_interval',
			'lockout_time',
			'banishment_type',
			'banishment_url',
			'banishment_message',
			'enable_search_log',
			'max_logged_searches',
			'theme_folder_path',
			'is_site_on'
		);

		$site_prefs = array();

		foreach($admin_default as $value)
		{
			$site_prefs[$value] = $config[$value];
		}

		ee()->db->where('site_id', 1);
		ee()->db->update('sites', array('site_system_preferences' => base64_encode(serialize($site_prefs))));

		// Default Mailinglists Prefs
		$mailinglist_default = array('mailinglist_enabled', 'mailinglist_notify', 'mailinglist_notify_emails');

		$site_prefs = array();

		foreach($mailinglist_default as $value)
		{
			$site_prefs[$value] = $config[$value];
		}

		ee()->db->where('site_id', 1);
		ee()->db->update('sites', array('site_mailinglist_preferences' => base64_encode(serialize($site_prefs))));

		// Default Members Prefs
		$member_default = array(
			'un_min_len',
			'pw_min_len',
			'allow_member_registration',
			'allow_member_localization',
			'req_mbr_activation',
			'new_member_notification',
			'mbr_notification_emails',
			'require_terms_of_service',
			'default_member_group',
			'profile_trigger',
			'member_theme',
			'enable_avatars',
			'allow_avatar_uploads',
			'avatar_url',
			'avatar_path',
			'avatar_max_width',
			'avatar_max_height',
			'avatar_max_kb',
			'enable_photos',
			'photo_url',
			'photo_path',
			'photo_max_width',
			'photo_max_height',
			'photo_max_kb',
			'allow_signatures',
			'sig_maxlength',
			'sig_allow_img_hotlink',
			'sig_allow_img_upload',
			'sig_img_url',
			'sig_img_path',
			'sig_img_max_width',
			'sig_img_max_height',
			'sig_img_max_kb',
			'prv_msg_enabled',
			'prv_msg_allow_attachments',
			'prv_msg_upload_path',
			'prv_msg_max_attachments',
			'prv_msg_attach_maxsize',
			'prv_msg_attach_total',
			'prv_msg_html_format',
			'prv_msg_auto_links',
			'prv_msg_max_chars',
			'memberlist_order_by',
			'memberlist_sort_order',
			'memberlist_row_limit'
		);

		$site_prefs = array();

		foreach($member_default as $value)
		{
			$site_prefs[$value] = $config[$value];
		}

		ee()->db->where('site_id', 1);
		ee()->db->update('sites', array('site_member_preferences' => base64_encode(serialize($site_prefs))));

		// Default Templates Prefs
		$template_default = array(
			'enable_template_routes',
			'strict_urls',
			'site_404',
			'save_tmpl_revisions',
			'max_tmpl_revisions',
			'save_tmpl_files',
			'tmpl_file_basepath'
		);
		$site_prefs = array();

		foreach($template_default as $value)
		{
			$site_prefs[$value] = $config[$value];
		}

		ee()->db->where('site_id', 1);
		ee()->db->update('sites', array('site_template_preferences' => base64_encode(serialize($site_prefs))));

		// Default Channels Prefs
		$channel_default = array(
			'image_resize_protocol',
			'image_library_path',
			'thumbnail_prefix',
			'word_separator',
			'use_category_name',
			'reserved_category_word',
			'auto_convert_high_ascii',
			'new_posts_clear_caches',
			'auto_assign_cat_parents',
			'enable_comments',
			'comment_word_censoring',
			'comment_moderation_override',
			'comment_edit_time_limit'
		);

		$site_prefs = array();

		foreach($channel_default as $value)
		{
			if (isset($config[$value]))
			{
				$site_prefs[$value] = $config[$value];
			}
		}

		ee()->db->where('site_id', 1);
		ee()->db->update('sites', array('site_channel_preferences' => base64_encode(serialize($site_prefs))));

		// Remove Site Prefs from Config
		foreach(array_merge($admin_default, $mailinglist_default, $member_default, $template_default, $channel_default) as $value)
		{
			unset($config[$value]);
		}

		// Write the config file data
		$this->write_config_from_template($config);

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Write config file from the template file
	 * @param array $config Config data to write to the config file
	 * @return boolean  TRUE if successful, FALSE if not
	 */
	private function write_config_from_template($config = array())
	{
		// Grab the existing config file
		if (count($config) == 0)
		{
			require $this->config->config_path;
		}

		// Add the CI config items to the array
		foreach ($this->ci_config as $key => $val)
		{
			$config[$key] = $val;
		}

		// To enable CI's helpers and native functions that deal with URLs
		// to work correctly we make these CI config items identical
		// to the EE counterparts
		if (isset($config['site_url']))
		{
			$config['base_url'] = $config['site_url'];
		}
		if (isset($config['site_index']))
		{
			$config['index_page'] = $config['site_index'];
		}

		// We also add a few other items
		$config['license_number'] = ( ! isset($config['license_number'])) ? '' : $config['license_number'];

		// Fetch the config template
		$data = read_file(APPPATH.'config/config_tmpl.php');

		// Swap out the values
		foreach ($config as $key => $val)
		{
			// go ahead and prep all items here once, so we do not
			// have to do it again for $extra_config items below
			if (is_bool($val))
			{
				$config[$key] = ($val == TRUE) ? 'TRUE' : 'FALSE';
			}
			else
			{
				$val = str_replace("\\\"", "\"", $val);
				$val = str_replace("\\'", "'", $val);
				$val = str_replace('\\\\', '\\', $val);

				$val = str_replace('\\', '\\\\', $val);
				$val = str_replace("'", "\\'", $val);
				$val = str_replace("\"", "\\\"", $val);

				$config[$key] = $val;
			}

			if (strpos($data, '{'.$key.'}') !== FALSE)
			{
				$data = str_replace('{'.$key.'}', $config[$key], $data);
				unset($config[$key]);
			}

		}

		// any unanticipated keys that aren't in our template?
		$extra_config = '';

		// Remove site_label from $config since we don't want
		// it showing up in the config file.
		if ($config['site_label'])
		{
			unset($config['site_label']);
		}

		foreach ($config as $key => $val)
		{
			$extra_config .= "\$config['{$key}'] = '{$val}';\n";
		}

		$data = str_replace('{extra_config}', $extra_config, $data);

		// Did we have any {values} that didn't get replaced?
		// This looks for instances with quotes
		$data = preg_replace("/['\"]\{\S+\}['\"]/", '""', $data);
		// And this looks for instances without quotes
		$data = preg_replace("/\{\S+\}/", '""', $data);

		// Write config file
		if ( ! $fp = fopen($this->config->config_path, FOPEN_WRITE_CREATE_DESTRUCTIVE))
		{
			return FALSE;
		}

		flock($fp, LOCK_EX);
		fwrite($fp, $data, strlen($data));
		flock($fp, LOCK_UN);
		fclose($fp);

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Update modules (first party only)
	 * @return void
	 */
	private function update_modules()
	{
		ee()->db->select('module_name, module_version');
		$query = ee()->db->get('modules');

		foreach ($query->result() as $row)
		{
			$module = strtolower($row->module_name);

			// Only update first-party modules
			if ( ! in_array($module, $this->native_modules))
			{
				continue;
			}

			// Send version to update class and let it do any required work
			if (in_array($module, $this->native_modules))
			{
				$path = EE_APPPATH.'/modules/'.$module.'/';
			}
			else
			{
				$path = PATH_THIRD.$module.'/';
			}

			if (file_exists($path.'upd.'.$module.'.php'))
			{
				$this->load->add_package_path($path);

				$class = ucfirst($module).'_upd';

				if ( ! class_exists($class))
				{
					require $path.'upd.'.$module.'.php';
				}

				$UPD = new $class;
				$UPD->_ee_path = EE_APPPATH;

				if ($UPD->version > $row->module_version && method_exists($UPD, 'update') && $UPD->update($row->module_version) !== FALSE)
				{
					ee()->db->update('modules', array('module_version' => $UPD->version), array('module_name' => ucfirst($module)));
				}

				$this->load->remove_package_path($path);
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Get the default channel entry data
	 * @return string
	 */
	private function default_channel_entry()
	{
		return read_file(APPPATH.'language/'.$this->userdata['deft_lang'].'/channel_entry_lang.php');
	}

	// --------------------------------------------------------------------

	/**
	 * Rename the installer
	 * @return void
	 */
	private function rename_installer()
	{
		// Generate the new path by suffixing a dotless version number
		$new_path = str_replace(
			'installer',
			'installer_'.str_replace('.', '', $this->version),
			APPPATH
		);

		// Move the directory
		return rename(APPPATH, $new_path);
	}
}

/* End of file wizard.php */
/* Location: ./system/expressionengine/installer/controllers/wizard.php */
