<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
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

	var $version			= '2.9.2';	// The version being installed
	var $installed_version	= ''; 		// The version the user is currently running (assuming they are running EE)
	var $minimum_php		= '5.3.10';	// Minimum version required to run EE
	var $schema				= NULL;		// This will contain the schema object with our queries
	var $languages			= array(); 	// Available languages the installer supports (set dynamically based on what is in the "languages" folder)
	var $mylang				= 'english';// Set dynamically by the user when they run the installer
	var $image_path			= ''; 		// URL path to the cp_global_images folder.  This is set dynamically
	var $is_installed		= FALSE;	// Does an EE installation already exist?  This is set dynamically.
	var $next_update		= FALSE;	// The next update file that needs to be loaded, when an update is performed.
	var $remaining_updates	= 0; 		// Number of updates remaining, in the event the user is updating from several back
	var $refresh			= FALSE;	// Whether to refresh the page for the next update.  Set dynamically
	var $refresh_url		= '';		// The URL where the refresh should go to.  Set dynamically
	var $theme_path			= '';
	var $root_theme_path	= '';
	var $active_group		= 'expressionengine';

	// Default page content - these are in English since we don't know the user's language choice when we first load the installer
	var $content			= '';
	var $title				= 'ExpressionEngine Installation and Update Wizard';
	var $heading			= 'ExpressionEngine Installation and Update Wizard';
	var $copyright			= 'Copyright 2003 - 2014 EllisLab, Inc. - All Rights Reserved';

	var $now;
	var $year;
	var $month;
	var $day;

	// These are the methods that are allowed to be called via $_GET['m']
	// for either a new installation or an update. Note that the function names
	// are prefixed but we don't include the prefix here.
	var $allowed_methods = array('optionselect', 'license', 'install_form',
	 	'do_install', 'trackback_form', 'do_update');

	// Absolutely, positively must always be installed
	var $required_modules = array('channel', 'member', 'stats', 'rte');

	var $theme_required_modules = array();

	// Our default installed modules, if there is no "override"
	var $default_installed_modules = array('comment', 'email', 'emoticon',
		'jquery', 'member', 'query', 'rss', 'search', 'stats', 'channel',
		'mailinglist', 'rte');

	// Native First Party ExpressionEngine Modules (everything else is in third party folder)
	var $native_modules = array('blacklist', 'channel', 'comment', 'commerce',
		'email', 'emoticon', 'file', 'forum', 'gallery', 'ip_to_nation',
		'jquery', 'mailinglist', 'member', 'metaweblog_api', 'moblog', 'pages',
		'query', 'referrer', 'rss', 'rte', 'search',
		'simple_commerce', 'stats', 'wiki');

	// Third Party Modules may send error messages if something goes wrong.
	var $module_install_errors = array(); // array that collects all error messages

	// These are the values we need to set during a first time installation
	var $userdata = array(
		'app_version'			=> '',
		'doc_url'				=> 'http://ellislab.com/expressionengine/user-guide/',
		'ext'					=> '.php',
		'ip'					=> '',
		'database'				=> 'mysql',
		'db_conntype'			=> '0',
		'databases'				=> array(),
		'dbdriver'				=> 'mysql',
		'db_hostname'			=> 'localhost',
		'db_username'			=> '',
		'db_password'			=> '',
		'db_name'				=> '',
		'db_prefix'				=> 'exp',
		'site_label'			=> '',
		'site_name'				=> 'default_site',
		'site_url'				=> '',
		'site_index'			=> 'index.php',
		'cp_url'				=> '',
		'username'				=> '',
		'password'				=> '',
		'password_confirm'		=> '',
		'screen_name'			=> '',
		'email_address'			=> '',
		'webmaster_email'		=> '',
		'deft_lang'				=> 'english',
		'theme'					=> '01',
		'default_site_timezone'	=> 'UTC',
		'redirect_method'		=> 'redirect',
		'upload_folder'			=> 'uploads/',
		'image_path'			=> '',
		'javascript_path'		=> 'themes/javascript/compressed/',
		'cp_images'				=> 'cp_images/',
		'avatar_path'			=> '../images/avatars/',
		'avatar_url'			=> 'images/avatars/',
		'photo_path'			=> '../images/member_photos/',
		'photo_url'				=> 'images/member_photos/',
		'signature_img_path'	=> '../images/signature_attachments/',
		'signature_img_url'		=> 'images/signature_attachments/',
		'pm_path'				=> '../images/pm_attachments',
		'captcha_path'			=> '../images/captchas/',
		'theme_folder_path'		=> '../themes/',
		'modules'				=> array()
	);


	// These are the default values for the CodeIgniter config array.  Since the EE
	// and CI config files are one in the same now we use this data when we write the
	// initial config file using $this->_write_config_data()
	var $ci_config = array(
		'uri_protocol'			=> 'AUTO',
		'charset' 				=> 'UTF-8',
		'subclass_prefix' 		=> 'EE_',
		'log_threshold' 		=> 0,
		'log_path' 				=> '',
		'log_date_format' 		=> 'Y-m-d H:i:s',
		'cache_path' 			=> '',
		'encryption_key' 		=> '',
		'rewrite_short_tags' 	=> TRUE			// Enabled for cleaner view files and compatibility
	);

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * Sets some base values
	 *
	 * @return	void
	 */
	function __construct()
	{
		parent::__construct();

		define('IS_CORE', FALSE);

		// Third party constants
		if ($this->config->item('third_party_path'))
		{
			define('PATH_THIRD',    rtrim($this->config->item('third_party_path'), '/').'/');
		}
		else
		{
			define('PATH_THIRD',	EE_APPPATH.'third_party/');
		}

		$req_source = $this->input->server('HTTP_X_REQUESTED_WITH');
		define('AJAX_REQUEST',	($req_source == 'XMLHttpRequest') ? TRUE : FALSE);

		$this->output->enable_profiler(FALSE);

		$this->userdata['app_version'] = str_replace('.', '', $this->version);

 		// Load the helpers we intend to use
 		$this->load->helper(array('form', 'url', 'html', 'directory', 'file', 'email', 'security', 'date', 'string'));

		// Load the language pack.  English is loaded on the installer home
		// page along with some radio buttons for each installed language pack.
		// Based on the users's choice we build the language into our URL string
		// and use that info to load the desired language file on each page

		$this->load->library('logger');

		$this->load->add_package_path(EE_APPPATH);
		$this->_load_langauge();

		$this->load->library('localize');
		$this->load->library('cp');

		$this->load->model('installer_template_model', 'template_model');

		// Update notices are used to print info at the end of
		// the update
		$this->load->library('update_notices');

		// Set the image URL
		$this->image_path = $this->_set_image_path();

		// Set the Javascript URL
		$this->javascript_path = $this->_set_javascript_path();

		// First try the current directory, if they are running the system with an admin.php file
		$this->theme_path = substr($_SERVER['SCRIPT_FILENAME'], 0, -strlen(SELF));

		if (is_dir($this->theme_path.'themes'))
		{
			$this->theme_path .= 'themes/';
		}
		else
		{
			// Must be in a public system folder so try one level back from current folder.
			// Replace only the LAST occurance of the system folder name with nil incase the
			// system folder name appears more than once in the path.
			$this->theme_path = preg_replace('/\b'.preg_quote(SYSDIR).'(?!.*'.preg_quote(SYSDIR).')\b/', '', $this->theme_path).'themes/';
		}

		$this->root_theme_path = $this->theme_path;
		define('PATH_THEMES', $this->root_theme_path);
		$this->theme_path .= 'site_themes/';
		$this->theme_path = str_replace('//', '/', $this->theme_path);
		$this->root_theme_path = str_replace('//', '/', $this->root_theme_path);

		// Set the time
		$time = time();
		$this->now		= gmmktime(gmdate("H", $time), gmdate("i", $time), gmdate("s", $time), gmdate("m", $time), gmdate("d", $time), gmdate("Y", $time));
		$this->year		= gmdate('Y', $this->now);
		$this->month	= gmdate('m', $this->now);
		$this->day		= gmdate('d', $this->now);

	}

	// --------------------------------------------------------------------

	/**
	 * Remap
	 *
	 * Intercepts the request and dynamically determines what we should do
	 *
	 * @access	public
	 * @return	void
	 */
	function _remap()
	{
		$this->_set_base_url();

		// Is $_GET['m'] set?  If not we show the welcome page
		if ( ! $this->input->get('M'))
		{
			return $this->_set_output('welcome', array('action' => $this->set_qstr('optionselect')));
		}

		// Run our pre-flight tests.
		// This function generates its own error messages so if it returns FALSE we bail out.
		if ( ! $this->_preflight())
		{
			return FALSE;
		}

		// OK, at this point we have determined whether an existing EE installation exists
		// and we've done all our error trapping and connected to the DB if needed

		// For safety all function names are prefixed with an underscore
		$action = '_'.$this->input->get('M');

		// Is the action allowed?
		if ( ! in_array($this->input->get('M'), $this->allowed_methods) OR  ! method_exists($this, $action))
		{
			show_error($this->lang->line('invalid_action'));
		}

		// Call the action
		$this->$action();
	}

	// --------------------------------------------------------------------

	/**
	 * Pre-flight Tests
	 *
	 * Does all of our error checks
	 *
	 * @access	public
	 * @return	void
	 */
	function _preflight()
	{
		// If the installed version of PHP is not supported we show the "unsupported" view file
		if (is_php($this->minimum_php) == FALSE)
		{
			$this->_set_output('unsupported', array('required_ver' => $this->minimum_php));
			return FALSE;
		}

		// Is the config file readable?
		if ( ! include($this->config->config_path))
		{
			$this->_set_output('error', array('error' => $this->lang->line('unreadable_config')));
			return FALSE;
		}

		// Is the config file writable?
		if ( ! is_really_writable($this->config->config_path))
		{
			$this->_set_output('error', array('error' => $this->lang->line('unwritable_config')));
			return FALSE;
		}

		// Is the database.php file readable?
		if ( ! include($this->config->database_path))
		{
			$this->_set_output('error', array('error' => $this->lang->line('unreadable_database')));
			return FALSE;
		}

		// Is the database.php file writable?  NOTE: Depending on whether we decide to require the database file
		// to always be writable will determine whether this code stays intact
		if ( ! is_really_writable($this->config->database_path))
		{
			$this->_set_output('error', array('error' => $this->lang->line('unreadable_database')));
			return FALSE;
		}

		$cache_path = EE_APPPATH.'cache';

		// Attempt to grab cache_path config if it's set
		if (ee()->config->item('cache_path'))
		{
			$cache_path = ee()->config->item('cache_path');
		}

		// Is the cache folder writable?
		if ( ! is_really_writable($cache_path))
		{
			$this->_set_output('error', array('error' => $this->lang->line('unwritable_cache_folder')));
			return FALSE;
		}

		// Prior to 2.0 the config array was named $conf.  This has changed to $config for 2.0
		if (isset($conf))
		{
			$config = $conf;
		}

		// No config AND db arrays?  This means it's a first time install...hopefully.
		// There's always a chance that the user nuked their config files.  During installation
		// later we'll double check the existence of EE tables once we know the DB connection values
		if ( ! isset($config) AND ! isset($db))
		{
			// Is the email template file available?  We'll check since we need this later
			if ( ! file_exists(EE_APPPATH.'/language/'.$this->userdata['deft_lang'].'/email_data'.EXT))
			{
				$this->_set_output('error', array('error' => $this->lang->line('unreadable_email')));
				return FALSE;
			}

			// Are the DB schemas available?
			if ( ! is_dir(APPPATH.'schema/'))
			{
				$this->_set_output('error', array('error' => $this->lang->line('unreadable_schema')));
				return FALSE;
			}

			// Fetch the database schemas
			$this->_get_supported_dbs();

			// set the image path and theme folder path
			$this->userdata['image_path'] = $this->image_path;
			$this->userdata['theme_folder_path'] = $this->root_theme_path;

			// We'll assign any POST values that exist (this will be the case after the user submits the install form)
			if (count($_POST) > 0)
			{
				foreach ($_POST as $key => $val)
				{
					if (get_magic_quotes_gpc())
					{
						if (is_array($val))
						{
							foreach($val as $k => $v)
							{
								$val[$k] = stripslashes($v);
							}
						}
						else
						{
							$val = stripslashes($val);
						}
					}

					if (isset($this->userdata[$key]))
					{
						if (is_array($this->userdata[$key]))
						{
							foreach ($this->userdata[$key] as $k => $v)
							{
								$this->userdata[$key][$k] = trim($v);
							}
						}
						else
						{
							$this->userdata[$key] = trim($val);
						}
					}
				}
			}

			// We'll switch the default if MySQLi is available
			if (function_exists('mysqli_connect'))
			{
					$this->userdata['dbdriver'] = 'mysqli';
			}

			// At this point we are reasonably sure that this is a first time installation.
			// We will set the flag and bail out since we're done
			$this->is_installed = FALSE;
			return TRUE;
		}

		// Before we assume this is an update, let's see if we can connect to the DB.
		// If they are running EE prior to 2.0 the database settings are found in the main
		// config file, if they are running 2.0 or newer, the settings are found in the db file
		if (isset($active_group))
		{
			$this->active_group = $active_group;
		}

		$move_db_data = FALSE;

		if ( ! isset($db) AND isset($config['db_hostname']))
		{
			$db[$this->active_group] = array(
				'hostname'	=> $config['db_hostname'],
				'username'	=> $config['db_username'],
				'password'	=> $config['db_password'],
				'database'	=> $config['db_name'],
				'dbdriver'	=> $config['db_type'],
				'dbprefix'	=> ($config['db_prefix'] == '') ? 'exp_' : preg_replace("#([^_])/*$#", "\\1_", $config['db_prefix']),
				'pconnect'	=> ($config['db_conntype'] == 1) ? TRUE : FALSE,
				'swap_pre'	=> 'exp_',
				'db_debug'	=> TRUE, // We show our own errors
				'cache_on'	=> FALSE,
				'autoinit'	=> FALSE, // We'll initialize the DB manually
				'char_set'	=> 'utf8',
				'dbcollat'	=> 'utf8_general_ci'
			);
			$move_db_data = TRUE;
		}

		// is correct db_prefix

		// Still not $db array?  Hm... what's going on here?
		if ( ! isset($db))
		{
			$this->_set_output('error', array('error' => $this->lang->line('database_no_data')));
			return FALSE;
		}

		// Can we connect?
		if ( ! $this->_db_connect($db))
		{
			$this->_set_output('error', array('error' => $this->lang->line('database_no_config')));
			return FALSE;
		}

		// EXCEPTIONS
		// We need to deal with a couple possible issues.

		// If the 'app_version' index is not present in the config file we are
		// dealing with EE public beta version released back in 2004.
		// Crazy as it sounds there's a chance someone will surface still running it
		// so we'll write the version to the config file
		if ( ! isset($config['app_version']))
		{
			$this->config->_append_config_1x(array('app_version' => 0));
			$config['app_version'] = 0;  // Update the $config array
		}

		// Fixes a bug in the installation script for 2.0.2, where periods were included
		$config['app_version'] = str_replace('.', '', $config['app_version']);

		// This fixes a bug introduced in the installation script for v 1.3.1
		if ($config['app_version'] == 130)
		{
			if ($this->db->field_exists('accept_messages', 'exp_members') == TRUE)
			{
				$this->config->_append_config_1x(array('app_version' => 131));

				// Update the $config array
				$config['app_version'] = 131;
			}
		}

		// OK, now let's determine if the update files are available and whether
		// the currently installed version is older then the most recent update

		// If this returns false it means the "updates" folder was not readable
		if ( ! $this->_fetch_updates($config['app_version']))
		{
			$this->_set_output('error', array('error' => $this->lang->line('unreadable_update')));
			return FALSE;
		}

		// If this is FALSE it means the user is running the most current version.
		// We will show the "you are running the most current version" template
		if ($this->next_update === FALSE)
		{
			$this->_assign_install_values();

			$vars['installer_path'] = '/'.SYSDIR.'/installer';

			// Set the path to the site and CP
			$host = 'http://';
			if (isset($_SERVER['HTTP_HOST']) AND $_SERVER['HTTP_HOST'] != '')
			{
				$host .= $_SERVER['HTTP_HOST'].'/';
			}

			$self = ( ! isset($_SERVER['PHP_SELF']) OR $_SERVER['PHP_SELF'] == '') ? '' : substr($_SERVER['PHP_SELF'], 1);

			// Since the CP access file can be inside or outside of the "system" folder
			// we will do a little test to help us set the site_url item
			$_selfloc = (is_dir('./installer/')) ? SELF.'/'.SYSDIR : SELF;

			$this->userdata['site_url'] = $host.substr($self, 0, - strlen($_selfloc));

			$vars['site_url'] = rtrim($this->userdata['site_url'], '/').'/'.$this->userdata['site_index'];
			$vars['cp_url'] = $this->userdata['cp_url'];

			$this->logger->updater("Update complete. Now running version {$this->version}.");

			// List any update notices we have
			$vars['update_notices'] = $this->update_notices->get();

			$this->_set_output('uptodate', $vars);
			return FALSE;
		}

		// Check to see if the language pack they are using in 1.6.X is available for the 2.0 upgrade.
		// This will only need to be done during the move from 1.6 to 2, and not for subsequent 2.0
		// updates, so we'll use the $move_db_data flag to determine if we should check for this, as
		// it will only be TRUE during this specific transition.
		if ($move_db_data == TRUE)
		{
			$default_language = $this->config->_get_config_1x('deft_lang');

			if (is_null($default_language))
			{
				// Likely an unserialize error so we go with the default
				$default_language = 'english';
			}

			// Fetch the installed languages
			$languages = directory_map(EE_APPPATH.'/language', TRUE);

			// Check to see if they have the language files needed
			if ( ! in_array($default_language, $languages))
			{
				$this->_set_output('error', array('error' => str_replace('%x', ucfirst($default_language), $this->lang->line('unreadable_language'))));
				return FALSE;
			}
		}

		// Do we need to move the database connection info out of the config file and into the DB file?
		// Prior to 2.0 the main config file contained the DB connection info so we'll move it if needed
		if ($move_db_data == TRUE)
		{
			if ($this->_write_db_config($db) == FALSE)
			{
				$this->_set_output('error', array('error' => $this->lang->line('unwritable_database')));
				return FALSE;
			}

			// Kill the DB connection data from the main config file
			// We also kill "system_folder" as this isn't used anymore
			$unset = array( 'db_hostname', 'db_username', 'db_password', 'db_name', 'db_type', 'db_prefix', 'db_conntype', 'system_folder');
			$this->config->_append_config_1x(array(), $unset);
		}

		// Before moving on, let's load the update file to make sure it's readable
		if ( ! include(APPPATH.'updates/ud_'.$this->next_update.EXT))
		{
			$this->_set_output('error', array('error' => $this->lang->line('unreadable_files')));
			return FALSE;
		}

		// If we got this far we know it's an update and all is well in the universe!

		// Assign the config and DB arrays to class variables so we don't have to reload them.
		$this->_config = $config;
		$this->_db = $db;

		// This is what the user is currently running
		if ($config['app_version'] == 0)
		{
			$this->installed_version = 'Public Beta pb01';
		}
		else
		{
			$this->installed_version = substr($config['app_version'], 0, 1).'.'.substr($config['app_version'], 1, 1).'.'.substr($config['app_version'], 2, 1);
		}

		// Set the flag
		$this->is_installed = TRUE;

		// Onward!
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Displays a page where the user is able to chose to install or update
	 *
	 * @access	private
	 * @return	void
	 */
	function _optionselect()
	{
		$data = array();
		if ($this->is_installed == FALSE)
		{
			$data['link'] = $this->set_qstr('license', $this->lang->line('click_to_install'));
		}
		else
		{
			$data['link'] = $this->set_qstr('license', str_replace('%s', $this->version, $this->lang->line('click_to_update')));
		}

		$data['is_installed'] = $this->is_installed;

		return $this->_set_output('optionselect', $data);

	}

	// --------------------------------------------------------------------

	/**
	 * Displays the license agreement
	 *
	 * @access	private
	 * @param	bool
	 * @return	null
	 */
	function _license($show_error = FALSE)
	{
		$data['show_error'] = $show_error;

		if ($this->is_installed == FALSE)
		{
			$data['action'] = $this->set_qstr('install_form');
		}
		else
		{
			// If they have installed the trackback module, we'll give them
			// the option to backup or convert
			$this->db->where('module_name', 'Trackback');
			$count = $this->db->count_all_results('modules');

			if ($count)
			{
				$data['action'] = $this->set_qstr('trackback_form');
			}
			else
			{
				$data['action'] = $this->set_qstr('do_update');
			}

			// clear the update notices if we have any from last time
			$this->update_notices->clear();

			$this->logger->updater("Preparing to update from {$this->installed_version} to {$this->version}. Awaiting acceptance of license terms.");
		}

		$data['license'] = $this->_license_agreement();

		$this->_set_output('license', $data);
	}

	// --------------------------------------------------------------------

	/**
	 * New installation form
	 *
	 * @access	private
	 * @return	null
	 */
	function _install_form($errors = FALSE)
	{
		// @confirm check if comment module is installed

		// Did they agree to the license?
		if ($this->input->post('agree') != 'yes')
		{
			return $this->_license(TRUE);
		}

		// Assign the _POST array values
		$this->_assign_install_values();

		// Are there any errors to display?
		// When the user submits the installation form, the $this->_do_install() function
		// is called.  In the event of errors the form will be redisplayed with the error message
		$this->userdata['errors'] = $errors;

		$template_module_vars = '';
		$this->load->library('javascript');

		$this->userdata['extra_header'] = $this->_install_form_extra_header(json_encode($this->theme_required_modules));

		$this->load->library('localize');

		// Preload server timezone
		$this->userdata['default_site_timezone'] = date_default_timezone_get();

		// Display the form and pass the userdata array to it
		$this->_set_output('install_form', $this->userdata);
	}

	// --------------------------------------------------------------------

	/**
	 * Install form extra header
	 *
	 * The extra script header used by the install form
	 *
	 * @access	private
	 * @return	string
	 */
	function _install_form_extra_header($theme_modules_jason)
	{
		return <<<PAPAYA
			<script type="text/javascript">
				$(document).ready(function(){
					onSelectChange(); // initialize to correct values in case there was a form error
					$("#theme_select").change(onSelectChange);

					$("#webmaster_email").blur( function() {
						if ($("#email_address").val() == "")
						{
							$("#email_address").val($(this).val());
						}
					});
				});

				$.fn.setChecks = function(v, r){
					return setChecks(this, v, (!r) ? [""] : r);
				};

				var setChecks = function(jq, v, r){
					jq.each(
						function (lc){
							if ($.inArray(this.value, v) > -1 || $.inArray(this.value, r) > -1)
							{
								this.checked = true;
								if ($.inArray(this.value, r) > -1)
								{
									this.disabled = true;
									$("label[for="+this.value+"] > span.req_module").show();
								}
								else
								{
									this.disabled = false;
									$("label[for="+this.value+"] > span.req_module").hide();
								}
							}
							else
							{
								this.checked = false;
								this.disabled = false;
								$("label[for="+this.value+"] > span.req_module").hide();
							}
						}
					);

					return jq;
				}

				function onSelectChange(){
					var selected = $("#theme_select").val();
					var theme_modules_jason = {$theme_modules_jason}
					var base_modules = new Array("comment", "email", "emoticon", "jquery", "rss", "search", "safecracker");

				   $("input[name='modules[]']").setChecks(base_modules, theme_modules_jason[selected]);
				}
			</script>
PAPAYA;
	}

	// --------------------------------------------------------------------

	/**
	 * Trackback conversion form
	 *
	 * @access	private
	 * @return	null
	 */
	function _trackback_form($not_readable = FALSE)
	{
		// Did they agree to the license?

		if ($this->input->get_post('agree') != 'yes')
		{
			return $this->_license(TRUE);
		}

		$convert_to_comments = ($this->input->get_post('convert_to_comments') == 'y') ? 'y' : 'n';
		$archive_trackbacks = ($this->input->get_post('archive_trackbacks') == 'y') ? 'y' : 'n';

		$trackback_zip_path = ($archive_trackbacks == 'n') ? BASEPATH : $this->input->get_post('trackback_zip_path');

		if ($this->input->get('ajax_progress') == 'yes')
		{
			$action = $this->set_qstr('do_update&agree=yes&ajax_progress=yes');
		}
		else
		{
			$action = $this->set_qstr('do_update&agree=yes');
		}


		$vars = array(
			'not_readable'			=> $not_readable,
			'convert_to_comments'	=> $convert_to_comments,
			'archive_trackbacks'	=> $archive_trackbacks,
			'trackback_zip_path'	=> $trackback_zip_path,
			'action'				=> $action
		);

		$vars['extra_header'] = '<script type="text/javascript">
		window.onload = function () {

			var zip_path = document.getElementById("zip_path_container"),
				archive_y = document.getElementById("archive_trackbacks_y"),
				archive_n = document.getElementById("archive_trackbacks_n");

			if (archive_n.checked) {
				zip_path.style.display = "none";
			}

			archive_n.onclick = function() {
				zip_path.style.display = "none";
			}
			archive_y.onclick = function() {
				zip_path.style.display = "block";
			}
		}
		</script>';

		// Display the form and pass the userdata array to it
		$this->_set_output('trackback_form', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Perform the installation
	 *
	 * @access	private
	 * @return	null
	 */
	function _do_install()
	{
		// Assign the _POST array values
		$this->_assign_install_values();
		$this->load->library('javascript');

		// Start our error trapping
		$errors = array();

		// Blank fields?
		foreach (array('license_number', 'db_hostname', 'db_username', 'db_name', 'site_label', 'webmaster_email', 'username', 'password', 'email_address') as $val)
		{
			if ($this->userdata[$val] == '')
			{
				$errors[] = $this->lang->line('empty_fields');
				break;
			}
		}

		// Usernames must be at least 4 chars in length
		if ($this->userdata['username'] != '' AND strlen($this->userdata['username']) < 4)
		{
			$errors[] = $this->lang->line('username_short');
		}

		// Passwords must be at least 5 chars in length
		if ($this->userdata['password'] != '' AND strlen($this->userdata['password']) < 5)
		{
			$errors[] = $this->lang->line('password_short');
		}

		// Passwords must match
		if ($this->userdata['password'] != $this->userdata['password_confirm'])
		{
			$errors[] = $this->lang->line('password_no_match');
		}

		if ( ! valid_license_pattern($this->userdata['license_number']))
		{
			$errors[] = $this->lang->line('invalid_license_number');
		}

		//  Is password the same as username?
		$lc_user = strtolower($this->userdata['username']);
		$lc_pass = strtolower($this->userdata['password']);
		$nm_pass = strtr($lc_pass, 'elos', '3105');

		if ($this->userdata['username'] != '' AND $this->userdata['password'] != '')
		{
			if ($lc_user == $lc_pass OR $lc_user == strrev($lc_pass) OR $lc_user == $nm_pass OR $lc_user == strrev($nm_pass))
			{
				$errors[] = $this->lang->line('password_not_unique');
			}
		}

		// Is email valid?
		if ($this->userdata['email_address'] != '' AND ! valid_email($this->userdata['email_address']))
		{
			$errors[] = "The email address you submitted is not valid";
		}

		// And webmaster email?
		if ($this->userdata['webmaster_email'] != '' AND ! valid_email($this->userdata['webmaster_email']))
		{
			$errors[] = "The webmaster email address you submitted is not valid";
		}

		// Set the screen name
		if ($this->userdata['screen_name'] == '')
		{
			$this->userdata['screen_name'] = $this->userdata['username'];
		}

		// check screen name and username for valid format
		if (strlen($this->userdata['username']) > 50 OR preg_match("/[\|'\"!<>\{\}]/", $this->userdata['username']))
		{
			$errors[] = "Username is invalid. Must be less than 50 characters and cannot include the following characters: ".htmlentities('|\'"!<>{}');
		}

		if (preg_match('/[\{\}<>]/', $this->userdata['screen_name']))
		{
			$errors[] = "Screen Name is invalid. Must not include the following characters: ".htmlentities('{}<>');
		}

		// DB Prefix has some character restrictions
		if ( ! preg_match("/^[0-9a-zA-Z\$_]*$/", $this->userdata['db_prefix']))
		{
			$errors[] = $this->lang->line('database_prefix_invalid_characters');
		}

		// The DB Prefix should not include "exp_"
		if ( strpos($this->userdata['db_prefix'], 'exp_') !== FALSE)
		{
			$errors[] = $this->lang->line('database_prefix_contains_exp_');
		}

		// Table names cannot be longer than 64 characters, our longest is 26
		if ( strlen($this->userdata['db_prefix']) > 30)
		{
			$errors[] = $this->lang->line('database_prefix_too_long');
		}

		// Connect to the database.  We pass a multi-dimensional array since
		// that's what is normally found in the database config file

		$db[$this->active_group] = array(
			'hostname'	=> $this->userdata['db_hostname'],
			'username'	=> $this->userdata['db_username'],
			'password'	=> $this->userdata['db_password'],
			'database'	=> $this->userdata['db_name'],
			'dbdriver'	=> $this->userdata['dbdriver'],
			'pconnect'	=> ($this->userdata['db_conntype'] == 1) ? TRUE : FALSE,
			'dbprefix'	=> ($this->userdata['db_prefix'] == '') ? 'exp_' : preg_replace("#([^_])/*$#", "\\1_", $this->userdata['db_prefix']),
			'swap_pre'	=> 'exp_',
			'db_debug'	=> TRUE, // We show our own errors
			'cache_on'	=> FALSE,
			'autoinit'	=> FALSE, // We'll initialize the DB manually
			'char_set'	=> 'utf8',
			'dbcollat'	=> 'utf8_general_ci'
		);

		if ( ! $this->_db_connect($db, TRUE))
		{
			$errors[] = $this->lang->line('database_no_connect');
		}

		// Does the specified database schema type exist?
		if ( ! file_exists(APPPATH.'schema/'.$this->userdata['dbdriver'].'_schema'.EXT))
		{
			$errors[] = $this->lang->line('unreadable_dbdriver');
		}

		// Were there errors?
		// If so we display the form and pass the userdata array to it
		if (count($errors) > 0)
		{
			$str = '';
			foreach ($errors as $val)
			{
				$str .= '<p>'.$val.'</p>';
			}

			$this->userdata['errors'] = $str;

			$this->userdata['extra_header'] = $this->_install_form_extra_header(json_encode($this->theme_required_modules));

			$this->_set_output('install_form', $this->userdata);
			return FALSE;
		}

		// --------------------------------------------------------------------

		// Load the DB schema
		require APPPATH.'schema/'.$this->userdata['dbdriver'].'_schema'.EXT;
		$this->schema = new EE_Schema();

		// Assign the userdata array to the schema class
		$this->schema->userdata		=& $this->userdata;
		$this->schema->theme_path	=& $this->theme_path;

		// Time
		$this->schema->now			= $this->now;
		$this->schema->year			= $this->year;
		$this->schema->month		= $this->month;
		$this->schema->day			= $this->day;

		// --------------------------------------------------------------------

		// Safety check: Is the user trying to install to an existing installation?
		// This can happen if someone mistakenly nukes their config.php file
		// and then trying to run the installer...

		$query = $this->db->query($this->schema->sql_find_like());

		if ($query->num_rows() > 0 AND ! isset($_POST['install_override']))
		{
			$fields = '';
			foreach($_POST as $key => $value)
			{
				// special handling for optional modules array
				if ($key == 'modules')
				{
					foreach ($value as $k => $v)
					{
						if (get_magic_quotes_gpc())
						{
							$v = stripslashes($v);
						}

						$fields .= '<input type="hidden" name="modules[]" value="'.str_replace("'", "&#39;", htmlspecialchars($v)).'" />'."\n";

					}
				}
				else
				{
					if (get_magic_quotes_gpc())
					{
						$value = stripslashes($value);
					}

					$fields .= '<input type="hidden" name="'.str_replace("'", "&#39;", htmlspecialchars($key)).'" value="'.str_replace("'", "&#39;", htmlspecialchars($value)).'" />'."\n";
				}
			}

			$stuff = array(
				'hidden_fields' => $fields,
				'action'		=> $this->set_qstr('do_install')
			);

			$this->_set_output('install_warning', $stuff);
			return;
		}

		// --------------------------------------------------------------------

		// No errors?  Move our tanks to the front line and prepare for battle!

		// We no longer need this:
		unset($this->userdata['password_confirm']);
		unset($_POST['password_confirm']);

		// We assign some values to the Schema class
		$this->schema->default_entry = $this->_default_channel_entry();

		// Encrypt the password and unique ID
		$this->userdata['unique_id'] = random_string('encrypt');
		$this->userdata['password'] = sha1($this->userdata['password']);

		// --------------------------------------------------------------------

		// This allows one to override the functions in Email Data below, thus allowing custom speciality templates
		if (file_exists($this->theme_path.$this->userdata['theme'].'/speciality_templates'.EXT))
		{
			require $this->theme_path.$this->userdata['theme'].'/speciality_templates'.EXT;
		}

		// Load the email template
		require_once EE_APPPATH.'/language/'.$this->userdata['deft_lang'].'/email_data'.EXT;

		// Install Database Tables!
		if ( ! $this->schema->install_tables_and_data())
		{
			$this->_set_output('error', array('error' => $this->lang->line('improper_grants')));
			return FALSE;
		}

		// Write the config file
		// it's important to do this first so that our site prefs and config file
		// visible for module and accessory installers
		if ($this->_write_config_data() == FALSE)
		{
			$this->_set_output('error', array('error' => $this->lang->line('unwritable_config')));
			return FALSE;
		}

		if ($this->_write_db_config($db) == FALSE)
		{
			$this->_set_output('error', array('error' => $this->lang->line('unwritable_database')));
			return FALSE;
		}

		// Install Accessories! (so exciting an exclaimation mark is needed!)
		if ( ! $this->_install_accessories())
		{
			// This happens if they don't have any accessories - can't scold them for that
		}

		// Add any modules required by the theme to the required modules array
		if ($this->userdata['theme'] != '' && isset($this->theme_required_modules[$this->userdata['theme']]))
		{
			$this->required_modules = array_merge($this->required_modules, $this->theme_required_modules[$this->userdata['theme']]);
		}

		// Install Modules!
		if ( ! $this->_install_modules())
		{
			$this->_set_output('error', array('error' => $this->lang->line('improper_grants')));
			return FALSE;
		}

		// Install Site Theme!
		// This goes last because a custom installer might create Member Groups besides the default five,
		// which might affect the Template Access permissions.
		if ($this->userdata['theme'] != '' && ! $this->_install_site_theme())
		{
			$this->_set_output('error', array('error' => $this->lang->line('improper_grants')));
			return FALSE;
		}

		// Build our success links
		$vars['installer_path'] = '/'.SYSDIR.'/installer';
		$vars['site_url'] = rtrim($this->userdata['site_url'], '/').'/'.$this->userdata['site_index'];
		$vars['cp_url'] = $this->userdata['cp_url'];

		// If errors are thrown, this is were we get the "human" names for those modules
		$vars['module_names'] = $this->userdata['modules'];

		// A flag used to determine if module install errors need to be shown in the view
		$vars['errors'] = count($this->module_install_errors);

		// The list of errors into a variable passed into the view
		$vars['error_messages'] = $this->module_install_errors;

		// Woo hoo! Success!
		$this->_set_output('install_success', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Assigns the values submitted in the settings form
	 *
	 * @access	private
	 * @return	null
	 */
	function _assign_install_values()
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

		// Fetch the themes
		$this->userdata['themes'] = $this->_fetch_themes();

		foreach ($this->userdata['themes'] as $theme => $name)
		{
			$required_modules = array();

			if (file_exists($this->theme_path.$theme.'/theme_preferences'.EXT))
			{
				require $this->theme_path.$theme.'/theme_preferences'.EXT;
				$this->theme_required_modules[$theme] = $required_modules;
			}
			else
			{
				$this->theme_required_modules[$theme] = array();
			}
		}

		// Fetch the modules
		$this->userdata['modules'] = $this->_fetch_modules();

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

		// if 'modules' isn't in the POST data, pre-check the defaults and third party modules
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
	 * Perform the update
	 *
	 * @access	private
	 * @return	null
	 */
	function _do_update()
	{
		// Did they agree to the license?
		if ($this->input->get_post('agree') != 'yes')
		{
			return $this->_license(TRUE);
		}

		$this->load->library('javascript');

		// Do we have to handle trackbacks?
		if ($this->input->get_post('archive_trackbacks'))
		{
			// reset in case they change their mind but we have
			// already written the file. Unlikely? Yes.
			$trackback_config = array(
				'trackbacks_to_comments'	=> 'n',
				'archive_trackbacks'		=> 'n'
			);

			if ($this->input->get_post('convert_to_comments') == 'y')
			{
				$trackback_config['trackbacks_to_comments'] = 'y';
			}

			if ($this->input->get_post('archive_trackbacks') == 'y')
			{
				$trackback_zip_path = rtrim($this->input->get_post('trackback_zip_path'), ' /');

				if (! is_dir($trackback_zip_path) OR
					! is_really_writable($trackback_zip_path) OR
					file_exists($trackback_zip_path.'/trackback.zip'))
				{
					return $this->_trackback_form(TRUE);
				}

				$trackback_config['archive_trackbacks'] = 'y';
				$trackback_config['trackback_zip_path'] = $trackback_zip_path.'/trackback.zip';
			}


			$this->config->_append_config_1x($trackback_config);
		}

		$this->load->library('progress');

		$next_version = $this->next_update[0].'.'.$this->next_update[1].'.'.$this->next_update[2];
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
			return $this->_set_output(
				'update_msg',
				array(
					'remaining_updates' => $this->remaining_updates,
					'next_version'		=> $this->progress->prefix.$this->lang->line('version_update_text')
				)
			);
		}

		// Clear any latent status messages still present in the PHP session
		$this->progress->clear_state();

		// Set a liberal execution time limit, some of these
		// updates are pretty big.
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
				$this->_set_output('error', array('error' => str_replace('%x', htmlentities($method), $this->lang->line('update_step_error'))));
				return FALSE;
			}
		}

		// is there a survey for this version?
		if (file_exists(APPPATH.'views/surveys/survey_'.$this->next_update.EXT))
		{
			$this->load->library('survey');

			// if we have data, send it on to the updater, otherwise, ask permission and show the survey
			if ( ! $this->input->get_post('participate_in_survey'))
			{
				$this->load->helper('language');
				$data = array(
					'action_url'			=> $this->set_qstr('do_update&agree=yes'),
					'participate_in_survey'	=> array(
						'name'		=> 'participate_in_survey',
						'id'		=> 'participate_in_survey',
						'value'		=> 'y',
						'checked'	=> TRUE
					),
					'ee_version'			=> $this->next_update
				);

				foreach ($this->survey->fetch_anon_server_data() as $key => $val)
				{
					if (in_array($key, array('php_extensions', 'addons')))
					{
						$val = implode(', ', json_decode($val));
					}

					$data['anonymous_server_data'][$key] = $val;
				}

				$this->_set_output('surveys/survey_'.$this->next_update, $data);
				return FALSE;
			}
			elseif ($this->input->get_post('participate_in_survey') == 'y')
			{
				// if any preprocessing needs to be done on the POST data, we do it here
				if (method_exists($UD, 'pre_process_survey'))
				{
					$UD->pre_process_survey();
				}

				$this->survey->send_survey($this->next_update);
			}
		}

		if (($status = $UD->{$method}()) === FALSE)
		{
			$error_msg = $this->lang->line('update_error');

			if ( ! empty($UD->errors))
			{
				$error_msg .= "</p>\n\n<ul>\n\t<li>" . implode("</li>\n\t<li>", $UD->errors) . "</li>\n</ul>\n\n<p>";
			}

			$this->_set_output('error', array('error' => $error_msg));
			return FALSE;
		}

		if ($status !== TRUE)
		{
			$this->config->set_item('ud_next_step', $status);
			$this->next_update = str_replace('.', '', $this->installed_version);
		}
		elseif ($this->remaining_updates == 1)
		{
			// If this is the last application update, run the module updater
			$this->_update_modules();
		}

		// Update the config file
		// If we are dealing with an update file that is prior to 2.0 we'll
		// update the config file using the old way.
		if ($this->next_update < 200)
		{
			$this->config->_append_config_1x(array('app_version' => $this->next_update, 'ud_next_step' => ($status !== FALSE && $status !== TRUE) ? $status : ''));
		}
		// If the cycle is version 2.0 we'll switch to the new style config file
		else
		{
			// If we are dealing with 2.0 we need to switch the old style config file to the new version
			if ($this->next_update == 200)
			{
				$this->_write_config_from_template();
			}

			$this->config->_update_config(array('app_version' => $this->next_update.$UD->version_suffix), array('ud_next_step' => ''));
		}

		// EE's application settings are now in the config, so we need to make two on the fly
		// switches for the rest of the wizard to work.
		$this->_set_base_url();
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
			'process_url'			=> $this->refresh_url,
			'progress_container'	=> '#js_progress',
			'state_url'				=> $this->set_qstr('do_update&agree=yes&progress=yes'),
			'end_url'				=> $this->set_qstr('do_update&agree=yes&progress=no&ajax_progress=yes')
		));

		$this->_set_output(
			'update_msg',
			array(
				'remaining_updates' => $this->remaining_updates,
				'extra_header'		=> $progress_head,
				'next_version'		=> $this->progress->prefix.$this->lang->line('version_update_text')
			)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Determine which update should be performed
	 *
	 * Reads though the "updates" directory and makes a list of all available updates
	 *
	 * @access	private
	 * @return	null
	 */
	function _fetch_updates($current_version = 0)
	{
		if ( ! $fp = opendir(APPPATH.'updates/'))
		{
			return FALSE;
		}

		$updates = array();
		while (false !== ($file = readdir($fp)))
		{
			if (substr($file, 0, 3) == 'ud_')
			{
				$file = str_replace(EXT,  '', $file);
				$file = str_replace('ud_', '', $file);
				$file = substr($file, 0, 3);

				if (is_numeric($file) AND $file > $current_version)
				{
					$updates[] = $file;
				}
			}
		}

		closedir($fp);

		sort($updates, SORT_NUMERIC);

		// we don't need this since we have a $version class variable in the wizard that
		// will always contain the latest version number
		// $version = end($updates);
		// $this->version = substr($version, 0, 1).'.'.substr($version, 1, 1).'.'.substr($version, 2);
		$this->remaining_updates = count($updates);

		reset($updates);

		if ($this->remaining_updates > 0)
		{
			$this->next_update = current($updates);
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Connect to the database
	 *
	 * @access	private
	 * @return	bool
	 */
	function _db_connect($db, $create_db = FALSE)
	{
		if (count($db) == 0)
		{
			return FALSE;
		}

		// Does the DB connection data exist?
		if ( ! isset($db[$this->active_group]))
		{
			return FALSE;
		}

		$this->load->database($db[$this->active_group], FALSE, TRUE);

		// Force caching off
		$this->db->cache_off();
		$this->db->save_queries	= TRUE;

		if ( ! $this->db->initialize($create_db))
		{
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Set path to the images used by the installer
	 *
	 * Since we need a relative path for our images, and since we do not
	 * know the location of the "admin" file we'll walk up the directory
	 * tree looking for the proper directory
	 *
	 * @access	public
	 * @return	void
	 */
	function _set_image_path($path = 'themes/cp_global_images/', $n = NULL)
	{
		if ( ! is_dir($path) && $n < 10)
		{
			$path = $this->_set_image_path('../'.$path, ++$n);
		}

		return $path;
	}

	// --------------------------------------------------------------------

	/**
	 * Set path to the javascript directory
	 *
	 * Same functionality as above, but this is for the javascript directory
	 */
	protected function _set_javascript_path($path = 'themes/javascript/compressed/', $n = NULL)
	{
		if ( ! is_dir($path) && $n < 10)
		{
			$path = $this->_set_javascript_path('../'.$path, ++$n);
		}

		return $path;
	}

	// --------------------------------------------------------------------

	/**
	 * Set output
	 *
	 * Loads the "container" view file and sets the content
	 *
	 * @access	private
	 * @return	void
	 */
	function _set_output($content = '', $array = array())
	{
		if (IS_CORE)
		{
			$this->heading = str_replace('ExpressionEngine', 'ExpressionEngine Core', $this->heading);
			$this->title = str_replace('ExpressionEngine', 'ExpressionEngine Core', $this->title);
		}

		$data = array(
			'heading'			=> $this->heading,
			'title'				=> $this->title,
			'refresh'			=> $this->refresh,
			'refresh_url'		=> $this->refresh_url,
			'image_path'		=> $this->image_path,
			'copyright'			=> $this->copyright,
			'version'			=> $this->version,
			'next_version'		=> substr($this->next_update, 0, 1).'.'.substr($this->next_update, 1, 1).'.'.substr($this->next_update, 2, 1),
			'installed_version'	=> $this->installed_version,
			'languages'			=> $this->languages,
			'javascript_path'	=> $this->javascript_path,
			'is_core'			=> (IS_CORE) ? 'Core ' : ''
		);

		$data = array_merge($array, $data);

		$this->load->helper('language');

		if ($content != '')
		{
			$content = $this->load->view($content, $data, TRUE);
		}

		$data['content'] = $content;

		$this->load->view('container', $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Set the base URL and index values so our links work properly
	 *
	 * @access	private
	 * @return	void
	 */
	function _set_base_url()
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
	 * Load the proper language file and set the language pref
	 *
	 * @access	private
	 * @return	void
	 */
	function _load_langauge()
	{
		// Fetch the installed languages
		$map = directory_map(APPPATH.'language', TRUE);

		// If this GET or POST variable doesn't exist we know we're dealing with
		// the welcome page where a user is presented the list of languages
		if ($this->input->get_post('language') == FALSE)
		{
			// build an array containing the languages.
			// This will be used to create the pull-down menu on the welcome page
			foreach ($map as $val)
			{
				$this->languages[$val] = ucfirst($val);
			}
		}
		else
		{
			// For security we only allow the user to chose from installed languages
			if (in_array($this->input->get_post('language'), $map))
			{
				$this->mylang = $this->input->get_post('language');
			}
		}

		// Load the installer language file based on the user preference
		$this->lang->load('installer', $this->mylang);
	}

	// --------------------------------------------------------------------

	/**
	 * Helper function that lets us create links
	 *
	 * @access	public
	 * @return	string
	 */
	function set_qstr($method = '', $text = FALSE)
	{
		$query_string = 'C=wizard&M='.$method.'&language='.$this->mylang;

		if ($text !== FALSE)
		{
			return anchor($query_string, $text);
		}
		else
		{
			return site_url($query_string);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch the available optional modules for installation
	 *
	 * @access	public
	 * @return	array
	 */
	function _fetch_modules()
	{
		$modules = array();

		if ($fp = opendir(EE_APPPATH.'/modules/'))
		{
			while (FALSE !== ($file = readdir($fp)))
			{
				if (strncmp($file, '_', 1) != 0 && strpos($file, '.') === FALSE && ! in_array($file, $this->required_modules))
				{
					$this->lang->load($file, '', FALSE, TRUE, EE_APPPATH.'/');
					$name = ($this->lang->line(strtolower($file).'_module_name') != FALSE) ? $this->lang->line(strtolower($file).'_module_name') : $file;
					$modules[$file] = array('name' => ucfirst($name), 'checked' => FALSE);
				}
			}

			closedir($fp);
		}


		$this->load->helper('directory');
		$ext_len = strlen(EXT);

		if (($map = directory_map(PATH_THIRD)) !== FALSE)
		{
			foreach ($map as $pkg_name => $files)
			{
				if ( ! is_array($files))
				{
					$files = array($files);
				}

				foreach ($files as $file)
				{
					if (is_array($file))
					{
						// we're only interested in the top level files for the addon
						continue;
					}

					// we gots a module?
					if (strncasecmp($file, 'mod.', 4) == 0 && substr($file, -$ext_len) == EXT && strlen($file) > strlen('mod.'.EXT))
					{
						$file = substr($file, 4, -$ext_len);

						if ($file == $pkg_name)
						{
							$this->lang->load($file.'_lang', '', FALSE, FALSE, PATH_THIRD.$pkg_name.'/');
							$name = ($this->lang->line(strtolower($file).'_module_name') != FALSE) ? $this->lang->line(strtolower($file).'_module_name') : $file;
							$modules[$file] = array('name' => ucfirst($name), 'checked' => FALSE);
						}
					}
				}
			}
		}

		asort($modules);

		return $modules;
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch the available themes for installation
	 *
	 * @access	public
	 * @return	array
	 */
	function _fetch_themes()
	{
		$themes = array();

		// Check for directory
		if (is_dir($this->theme_path) && ($fp = opendir($this->theme_path)))
		{
			while (false !== ($folder = readdir($fp)))
			{
				if (is_dir($this->theme_path.$folder) && substr($folder, 0, 1) != '.')
				{
					$themes[$folder] = $folder;
				}
			}
			closedir($fp);
			natcasesort($themes);
		}


		if (count($themes) > 0)
		{
			foreach ($themes as $key => $val)
			{
				$themes[$key] = ucwords(str_replace("_", " ", $val));
			}
		}

		return $themes;
	}

	// --------------------------------------------------------------------

	/**
	 * Get a list of supported database types
	 *
	 * @access	private
	 * @return	array
	 */
	function _get_supported_dbs()
	{
		$names = array('mysqli' => 'MySQLi', 'mysql' => 'MySQL');

		$dbs = array();
		foreach (get_filenames(APPPATH.'schema/') as $val)
		{
			$val = str_replace(array('_schema', EXT), '', $val);

			if (isset($names[$val]))
			{
				if (function_exists($names[$val].'_connect'))
				{
					$dbs[$val] = $names[$val];
				}
			}
		}

		$this->userdata['databases'] = $dbs;
	}

	// --------------------------------------------------------------------

	/**
	 * Install the Site Theme!
	 *
	 * @access	private
	 * @return	bool
	 */
	function _install_site_theme()
	{
		// @todo - redo in kind with how template file syncing works in Design
		// not that these aren't good ideas, but - simplify, simplify, simplify
		// always better to do simple and solid first so you don't paint a feature
		// into a corner until after it's been used and tested by the masses - D'Jones

		// Sanitized for your protection
		$this->userdata['theme'] = $this->security->sanitize_filename($this->userdata['theme']);

		// --------------------------------------------------------------------

		/**
		 * Default Preferences and Access Permissions for all Templates
		 */

		$default_group = 'site';

		$default_template_preferences = array(
			'caching'			=> 'n',
			'cache_refresh'		=> 0,
			'php_parsing'		=> 'none', // none, input, output
		);

		// Uses the Labels of the default four groups, as it is easier than the Group IDs, let's be honest
		$default_template_access = array(
			'Banned' 	=> 'n',
			'Guests'	=> 'y',
			'Members'	=> 'y',
			'Pending'	=> 'y'
		);

		$template_access = array();
		$template_preferences = array();

		// --------------------------------------------------------------------

		/**
		 * Site Theme Overrides?
		 */

		if (file_exists($this->theme_path.$this->userdata['theme'].'/theme_preferences'.EXT))
		{
			require $this->theme_path.$this->userdata['theme'].'/theme_preferences'.EXT;
		}

		// --------------------------------------------------------------------

		/**
		 * Get the Default Preferences and Access Ready for Insert
		 */

		$default_preferences = array(
			'allow_php' 			=> (in_array($default_template_preferences['php_parsing'], array('input', 'output'))) ? 'y' : 'n',
			'php_parse_location'	=> ($default_template_preferences['php_parsing'] == 'input') ? 'i' : 'o',
			'cache'					=> ($default_template_preferences['caching'] == 'y') ? 'y' : 'n',
			'refresh'				=> (round((int) $default_template_preferences['cache_refresh']) > 0) ? round( (int) $default_template_preferences['cache_refresh']) : 0
		);

		$group_ids		= array();
		$default_access	= array();

		$this->db->select(array('group_title', 'group_id'));
		$query = $this->db->get_where('member_groups', array('site_id' => 1));

		foreach($query->result_array() as $row)
		{
			// For use with Template Specific Access from Theme Preferences
			$group_ids[$row['group_title']] = $row['group_id'];

			// Like EE, a group is only denied access if they are specifically denied.  Groups
			// not in the list are granted access by default.
			if (isset($default_template_access[$row['group_title']]) && $default_template_access[$row['group_title']] == 'n')
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

		if ($this->userdata['theme'] != '' && $this->userdata['theme'] != 'none' && ($fp = opendir($this->theme_path.$this->userdata['theme'])))
		{
			while (FALSE !== ($folder = readdir($fp)))
			{
				if (is_dir($this->theme_path.$this->userdata['theme'].'/'.$folder) && substr($folder, -6) == '.group')
				{
					++$i;

					$group = preg_replace("#[^a-zA-Z0-9_\-/\.]#i", '', substr($folder, 0, -6));

					$data = array(
						'group_id' 			=> $i,
						'group_name'		=> $group,
						'group_order'		=> $i,
						'is_site_default'	=> ($default_group == $group) ? 'y' : 'n'
					);

					$this->db->insert('template_groups', $data);

					$template_groups[substr($folder, 0, -6)] = array();

					$templates = array('index.html' => 'index.html');  // Required

					if ($tgfp = opendir($this->theme_path.$this->userdata['theme'].'/'.$folder))
					{
						while (FALSE !== ($file = readdir($tgfp)))
						{
							if (@is_file($this->theme_path.$this->userdata['theme'].'/'.$folder.'/'.$file) && $file != '.DS_Store' && $file != '.htaccess')
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
							$name	= $file;
							$type	= 'webpage';
						}
						else
						{
							$type	= strtolower(ltrim(strrchr($file, '.'), '.'));
							$name	= preg_replace("#[^a-zA-Z0-9_\-/\.]#i", '', substr($file, 0, -(strlen($type) + 1)));

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
							'group_id'			=> $i,
							'template_name'		=> $name,
							'template_type'		=> $type,
							'template_data'		=> file_get_contents($this->theme_path.$this->userdata['theme'].'/'.$folder.'/'.$file),
							'edit_date'			=> $this->now,
							'last_author_id'	=> 1
						);

						$data = array_merge($data, $default_preferences);

						//
						// Specific Template Preferences
						//

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

						$this->db->insert('templates', $data);

						$template_id = $this->db->insert_id();

						//
						// Access.  Why, oh, why must this be so complicated?! Ugh...
						//

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
							$this->db->insert('template_no_access',  array('template_id' => $template_id, 'member_group' => $group_id));
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
							$this->db->insert('snippets', array('snippet_name' => $name, 'snippet_contents' => $contents, 'site_id' => 1));
						}
						else
						{
							$this->db->insert('global_variables', array('variable_name' => $name, 'variable_data' => $contents, 'site_id' => 1));
						}
					}
				}
			}

			// Install any default structure and content that the theme may have
			if (file_exists($this->theme_path.$this->userdata['theme'].'/default_content'.EXT))
			{
				require $this->theme_path.$this->userdata['theme'].'/default_content'.EXT;
			}
		}

		return TRUE;
	}


	// --------------------------------------------------------------------

	/**
	 * Install the Modules
	 *
	 * @access	private
	 * @return	bool
	 */
	function _install_modules()
	{
		$this->load->library('layout');

		$modules = ($this->input->post('modules') !== FALSE) ? $this->input->post('modules') : array();

		$modules = array_unique(array_merge($modules, $this->required_modules));

		// First we deal with native modules, so if a module is native we'll
		// run it, and unset it from the modules array, to run second

		foreach($modules as $module)
		{
			if (in_array($module, $this->native_modules))
			{
				$path = EE_APPPATH.'/modules/'.$module.'/';
				unset($modules[$module]); // remove from modules array

				if (file_exists($path.'upd.'.$module.EXT))
				{
					// Add the helper/library load path and temporarily
					$this->load->add_package_path($path, FALSE);

					require $path.'upd.'.$module.EXT;

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
		}

		// Native modules will now have been removed from the array, so we'll
		// run it again for third party modules going in at install time.

		foreach($modules as $module)
		{
			$path = PATH_THIRD.$module.'/';

			if (file_exists($path.'upd.'.$module.EXT))
			{
				require $path.'upd.'.$module.EXT;

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
			}
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Install default Accessories
	 *
	 * @access	private
	 * @return	bool
	 */
	function _install_accessories()
	{
		// Make sure we have something to install
		$accessories = array('Expressionengine_info' => '1.0');

		foreach($accessories as $acc => $version)
		{
			if ( ! file_exists(EE_APPPATH.'accessories/acc.'.strtolower($acc).EXT))
			{
				unset($accessories[$acc]);
			}
			else
			{
				include EE_APPPATH.'accessories/acc.'.strtolower($acc).EXT;
				$_static_vars = get_class_vars($acc.'_acc');
				// this seems silly since this is a first party accessory, but the Zend Optimizer
				// apparently has problem with get_class_vars() on PHP 5.2.1x, so, safety net
				$accessories[$acc] = (isset($_static_vars['version'])) ? $_static_vars['version'] : $version;
			}
		}

		if ( ! count($accessories) > 0)
		{
			return FALSE;
		}

		// The Accessories library has a list of ignored controllers
		$c_path = EE_APPPATH.'libraries/Accessories'.EXT;

		if ( ! file_exists($c_path) OR ! (include $c_path) OR ! class_exists('EE_Accessories'))
		{
			return FALSE;
		}

		// PHP 4 strikes again...
		// $ignored_controllers = EE_Accessories::$ignored_controllers;

		$_static_vars = get_class_vars('EE_Accessories');
		// again, silly, but the Zend Optimizer apparently has problem with get_class_vars() on PHP 5.2.1x, so, safety net
		$ignored_controllers = (isset ($_static_vars['ignored_controllers'])) ?
								$_static_vars['ignored_controllers'] :
								array('css.php', 'javascript.php', 'login.php', 'search.php', 'index.html');

		$this->load->helper('directory');

		// all controllers by default
		$controllers = array();

		foreach(directory_map(EE_APPPATH.'controllers/cp') as $file)
		{
			if (in_array($file, $ignored_controllers))
			{
				continue;
			}

			$file = str_replace(EXT, '', $file);
			$controllers[] = str_replace(EXT, '', $file);
		}

		// concat and insert
		$data = array();
		$data['member_groups'] = '1|5';
		$data['controllers'] = implode('|', $controllers);

		foreach($accessories as $acc => $version)
		{
			$data['class'] = $acc.'_acc';
			$data['accessory_version'] = $version;
			$this->db->insert('accessories', $data);
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Write the config file
	 *
	 * @access	private
	 * @return	bool
	 */
	function _write_config_data()
	{
		$captcha_url = rtrim($this->userdata['site_url'], '/').'/';
		$captcha_url .= 'images/captchas/';

		foreach (array('avatar_path', 'photo_path', 'signature_img_path', 'pm_path', 'captcha_path', 'theme_folder_path') as $path)
		{
			$prefix = ($path != 'theme_folder_path') ? $this->root_theme_path : '';
			$this->userdata[$path] = rtrim(realpath($prefix.$this->userdata[$path]), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
		}

		$config = array(
			'app_version'					=>	$this->userdata['app_version'],
			'license_contact'				=>	$this->userdata['license_contact'],
			'license_number'				=>	trim($this->userdata['license_number']),
			'debug'							=>	'1',
			'cp_url'						=>	$this->userdata['cp_url'],
			'site_index'					=>	$this->userdata['site_index'],
			'site_label'					=>	$this->userdata['site_label'],
			'site_url'						=>	$this->userdata['site_url'],
			'theme_folder_url'				=>	$this->userdata['site_url'].'themes/',
			'doc_url'						=>	$this->userdata['doc_url'],
			'webmaster_email'				=>	$this->userdata['webmaster_email'],
			'webmaster_name'				=> '',
			'channel_nomenclature'			=> 'channel',
			'max_caches'					=> '150',
			'captcha_url'					=>	$captcha_url,
			'captcha_path'					=> $this->userdata['captcha_path'],
			'captcha_font'					=>	'y',
			'captcha_rand'					=> 'y',
			'captcha_require_members'		=>	'n',
			'enable_db_caching'				=>	'n',
			'enable_sql_caching'			=>	'n',
			'force_query_string'			=>	'n',
			'show_profiler'					=>	'n',
			'template_debugging'			=>	'n',
			'include_seconds'				=>	'n',
			'cookie_domain'					=>	'',
			'cookie_path'					=>	'',
			'cookie_prefix'					=>	'',
			'website_session_type'			=>	'c',
			'cp_session_type'				=>	'cs',
			'cookie_httponly'				=>	'y',
			'allow_username_change'			=>	'y',
			'allow_multi_logins'			=>	'y',
			'password_lockout'				=>	'y',
			'password_lockout_interval'		=>	'1',
			'require_ip_for_login'			=>	'y',
			'require_ip_for_posting'		=>	'y',
			'require_secure_passwords'		=>	'n',
			'allow_dictionary_pw'			=>	'y',
			'name_of_dictionary_file'		=>	'',
			'xss_clean_uploads'				=>	'y',
			'redirect_method'				=>	$this->userdata['redirect_method'],
			'deft_lang'						=>	$this->userdata['deft_lang'],
			'xml_lang'						=>	'en',
			'send_headers'					=>	'y',
			'gzip_output'					=>	'n',
			'log_referrers'					=>	'n',
			'max_referrers'					=>	'500',
			'is_system_on'					=>	'y',
			'allow_extensions'				=>	'y',
			'date_format'					=>	'%n/%j/%y',
			'time_format'					=>	'12',
			'include_seconds'				=>	'n',
			'server_offset'					=>	'',
			'default_site_timezone'			=>	$this->userdata['default_site_timezone'],
			'mail_protocol'					=>	'mail',
			'smtp_server'					=>	'',
			'smtp_username'					=>	'',
			'smtp_password'					=>	'',
			'email_debug'					=>	'n',
			'email_charset'					=>	'utf-8',
			'email_batchmode'				=>	'n',
			'email_batch_size'				=>	'',
			'mail_format'					=>	'plain',
			'word_wrap'						=>	'y',
			'email_console_timelock'		=>	'5',
			'log_email_console_msgs'		=>	'y',
			'cp_theme'						=>	'default',
			'email_module_captchas'			=>	'n',
			'log_search_terms'				=>	'y',
			'un_min_len'					=>	'4',
			'pw_min_len'					=>	'5',
			'allow_member_registration'		=>	'n',
			'allow_member_localization'		=>	'y',
			'req_mbr_activation'			=>	'email',
			'new_member_notification'		=>	'n',
			'mbr_notification_emails'		=>	'',
			'require_terms_of_service'		=>	'y',
			'use_membership_captcha'		=>	'n',
			'default_member_group'			=>	'5',
			'profile_trigger'				=>	'member',
			'member_theme'					=>	'default',
			'enable_avatars'				=> 'y',
			'allow_avatar_uploads'			=> 'n',
			'avatar_url'					=> $this->userdata['site_url'].$this->userdata['avatar_url'],
			'avatar_path'					=> $this->userdata['avatar_path'],
			'avatar_max_width'				=> '100',
			'avatar_max_height'				=> '100',
			'avatar_max_kb'					=> '50',
			'enable_photos'					=> 'n',
			'photo_url'						=> $this->userdata['site_url'].$this->userdata['photo_url'],
			'photo_path'					=> $this->userdata['photo_path'],
			'photo_max_width'				=> '100',
			'photo_max_height'				=> '100',
			'photo_max_kb'					=> '50',
			'allow_signatures'				=> 'y',
			'sig_maxlength'					=> '500',
			'sig_allow_img_hotlink'			=> 'n',
			'sig_allow_img_upload'			=> 'n',
			'sig_img_url'					=> $this->userdata['site_url'].$this->userdata['signature_img_url'],
			'sig_img_path'					=> $this->userdata['signature_img_path'],
			'sig_img_max_width'				=> '480',
			'sig_img_max_height'			=> '80',
			'sig_img_max_kb'				=> '30',
			'prv_msg_upload_path'			=> $this->userdata['pm_path'],
			'prv_msg_max_attachments'		=> '3',
			'prv_msg_attach_maxsize'		=> '250',
			'prv_msg_attach_total'			=> '100',
			'prv_msg_html_format'			=> 'safe',
			'prv_msg_auto_links'			=> 'y',
			'prv_msg_max_chars'				=> '6000',
			'enable_template_routes'		=>	'y',
			'strict_urls'					=>	'y',
			'site_404'						=>	'',
			'save_tmpl_revisions'			=>	'n',
			'max_tmpl_revisions'			=>	'5',
			'save_tmpl_files'				=>	'n',
			'tmpl_file_basepath'			=>	realpath('./expressionengine/templates/').DIRECTORY_SEPARATOR,
			'deny_duplicate_data'			=>	'y',
			'redirect_submitted_links'		=>	'n',
			'enable_censoring'				=>	'n',
			'censored_words'				=>	'',
			'censor_replacement'			=>	'',
			'banned_ips'					=>	'',
			'banned_emails'					=>	'',
			'banned_usernames'				=>	'',
			'banned_screen_names'			=>	'',
			'ban_action'					=>	'restrict',
			'ban_message'					=>	'This site is currently unavailable',
			'ban_destination'				=>	'http://www.yahoo.com/',
			'enable_emoticons'				=>	'y',
			'emoticon_url'					=>	$this->userdata['site_url'].'images/smileys/',
			'recount_batch_total'			=>	'1000',
			'image_resize_protocol'			=>	'gd2',
			'image_library_path'			=>	'',
			'thumbnail_prefix'				=>	'thumb',
			'word_separator'				=>	'dash',
			'use_category_name'				=>	'n',
			'reserved_category_word'		=>	'category',
			'auto_convert_high_ascii'		=>	'n',
			'new_posts_clear_caches'		=>	'y',
			'auto_assign_cat_parents'		=>	'y',
			'new_version_check'				=> 'y',
			'enable_throttling'				=> 'n',
			'banish_masked_ips'				=> 'y',
			'max_page_loads'				=> '10',
			'time_interval'					=> '8',
			'lockout_time'					=> '30',
			'banishment_type'				=> 'message',
			'banishment_url'				=> '',
			'banishment_message'			=> 'You have exceeded the allowed page load frequency.',
			'enable_search_log'				=> 'y',
			'max_logged_searches'			=> '500',
			'mailinglist_enabled'			=> 'y',
			'mailinglist_notify'			=> 'n',
			'mailinglist_notify_emails'		=> '',
			'memberlist_order_by'			=> "total_posts",
			'memberlist_sort_order'			=> "desc",
			'memberlist_row_limit'			=> "20",
			'is_site_on'					=> 'y',
			'theme_folder_path'				=> $this->userdata['theme_folder_path'],
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
			'enable_db_caching',
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
			'email_module_captchas',
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

		$this->db->where('site_id', 1);
		$this->db->update('sites', array('site_system_preferences' => base64_encode(serialize($site_prefs))));

		// Default Mailinglists Prefs
		$mailinglist_default = array('mailinglist_enabled', 'mailinglist_notify', 'mailinglist_notify_emails');

		$site_prefs = array();

		foreach($mailinglist_default as $value)
		{
			$site_prefs[$value] = $config[$value];
		}

		$this->db->where('site_id', 1);
		$this->db->update('sites', array('site_mailinglist_preferences' => base64_encode(serialize($site_prefs))));

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
			'use_membership_captcha',
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

		$this->db->where('site_id', 1);
		$this->db->update('sites', array('site_member_preferences' => base64_encode(serialize($site_prefs))));

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

		$this->db->where('site_id', 1);
		$this->db->update('sites', array('site_template_preferences' => base64_encode(serialize($site_prefs))));

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
			'auto_assign_cat_parents'
		);

		$site_prefs = array();

		foreach($channel_default as $value)
		{
			$site_prefs[$value] = $config[$value];
		}

		$this->db->where('site_id', 1);
		$this->db->update('sites', array('site_channel_preferences' => base64_encode(serialize($site_prefs))));

		// Remove Site Prefs from Config
		foreach(array_merge($admin_default, $mailinglist_default, $member_default, $template_default, $channel_default) as $value)
		{
			unset($config[$value]);
		}

		// Write the config file data
		$this->_write_config_from_template($config);

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Write config file from the template file
	 *
	 * As of EE 2.0 we used a config file that has shared data with CodeIgniter.
	 * This function lets us migrate to the new style
	 *
	 * @access	private
	 * @return	null
	 */
	function _write_config_from_template($config = array())
	{
		// Grab the existing config file
		if (count($config) == 0)
		{
			require $this->config->config_path;
		}

		// Just in case the old variable naming is still present...
		if (isset($conf))
		{
			$config = array_merge($config, $conf);
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
		$data = read_file(APPPATH.'config/config_tmpl'.EXT);

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
				unset($config['site_label']);

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
	 * Write database file
	 *
	 * @access	public
	 * @return	null
	 */
	function _write_db_config($db = array(), $active_group = 'expressionengine')
	{
		$prototype = array(
			'hostname'	=> 'localhost',
			'username'	=> '',
			'password'	=> '',
			'database'	=> '',
			'dbdriver'	=> 'mysql',
			'dbprefix'	=> 'exp_',
			'swap_pre'	=> 'exp_',
			'pconnect'	=> FALSE,
			'db_debug'	=> TRUE,
			'cache_on'	=> FALSE,
			'cachedir'	=> EE_APPPATH.'cache/db_cache/',
			'autoinit'	=> TRUE,
			'char_set'	=> 'utf8',
			'dbcollat'	=> 'utf8_general_ci'
		);

		require $this->config->database_path;

		if ( ! isset($active_group))
		{
			$active_group = 'expressionengine';
		}

		if ( ! isset($db[$active_group]))
		{
			return FALSE;
		}

		// Make sure we have all the required data
		foreach ($prototype as $key => $val)
		{
			if ( ! isset($db[$active_group][$key]))
			{
				$db[$active_group][$key] = $val;
			}
		}

		// Let's redefine swap_pre just in case
		$db[$active_group]['swap_pre'] = 'exp_';

	 	// Build the string
		$str  = '<?php '." if ( ! defined('BASEPATH')) exit('No direct script access allowed');\n\n";

		$str .= "\$active_group = '".$active_group."';\n\$active_record = TRUE;\n\n";

		foreach ($db as $key => $val)
		{
			if (is_array($val))
			{
				foreach ($val as $k => $v)
				{
					if (is_bool($v))
					{
						$v = ($v == TRUE) ? 'TRUE' : 'FALSE';

						$str .= "\$db['".$key."']['".$k."'] = ".$v.";\n";
					}
					else
					{
						$v = str_replace(array('\\', "'"), array('\\\\', "\\'"), $v);
						$str .= "\$db['".$active_group."']['".$k."'] = '".$v."';\n";
					}
				}
			}
			else
			{
				if (is_bool($val))
				{
					$val = ($val == TRUE) ? 'TRUE' : 'FALSE';

					$str .= "\$db['".$active_group."']['".$key."'] = ".$val.";\n";
				}
				else
				{
					$val = str_replace(array('\\', "'"), array('\\\\', "\\'"), $val);
					$str .= "\$db['".$active_group."']['".$key."'] = '".$val."';\n";
				}
			}
		}

		$str .= "\n";
		$str .= '/* End of file database.php */
/* Location: ./system/expressionengine/config/database.php */';


		if ( ! $fp = fopen($this->config->database_path, FOPEN_WRITE_CREATE_DESTRUCTIVE))
		{
			return FALSE;
		}

		flock($fp, LOCK_EX);
		fwrite($fp, $str, strlen($str));
		flock($fp, LOCK_UN);
		fclose($fp);

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Update modules (first party only)
	 *
	 * @access	public
	 * @return	void
	 */
	function _update_modules()
	{
		$this->db->select('module_name, module_version');
		$query = $this->db->get('modules');

		foreach ($query->result() as $row)
		{
			$module = strtolower($row->module_name);

			/*
			 * - Send version to update class and let it do any required work
			 */

			if (in_array($module, $this->native_modules))
			{
				$path = EE_APPPATH.'/modules/'.$module.'/';
			}
			else
			{
				$path = PATH_THIRD.$module.'/';
			}

			if (file_exists($path.'upd.'.$module.EXT))
			{
				$this->load->add_package_path($path);

				$class = ucfirst($module).'_upd';

				if ( ! class_exists($class))
				{
					require $path.'upd.'.$module.EXT;
				}

				$UPD = new $class;
				$UPD->_ee_path = EE_APPPATH;

				if ($UPD->version > $row->module_version && method_exists($UPD, 'update') && $UPD->update($row->module_version) !== FALSE)
				{
					$this->db->update('modules', array('module_version' => $UPD->version), array('module_name' => ucfirst($module)));
				}

				$this->load->remove_package_path($path);
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Get the default channel entry data
	 *
	 * @access	private
	 * @return	string
	 */
	function _license_agreement()
	{
		return read_file(APPPATH.'language/'.$this->userdata['deft_lang'].'/license'.EXT);
	}

	// --------------------------------------------------------------------

	/**
	 * Get the default channel entry data
	 *
	 * @access	private
	 * @return	string
	 */
	function _default_channel_entry()
	{
		return read_file(APPPATH.'language/'.$this->userdata['deft_lang'].'/channel_entry_lang'.EXT);
	}



}

/* End of file wizard.php */
/* Location: ./system/expressionengine/installer/controllers/wizard.php */
