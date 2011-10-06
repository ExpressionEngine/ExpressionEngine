<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
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
class EE_Config Extends CI_Config {
	
	var $config_path 		= ''; // Set in the constructor below
	var $database_path		= ''; // Set in the constructor below
	var $default_ini 		= array();
	var $exceptions	 		= array();	 // path.php exceptions
	var $cp_cookie_domain	= '';
	var $_global_vars 		= array();	// The global vars from path.php (deprecated but usable for other purposes now)
	var $special_tlds 		= array('com', 'edu', 'net', 'org', 'gov', 'mil', 'int');	// seven special TLDs for cookie domains
	var $_config_path_errors = array();

	/**
	 * Constructor
	 */	
	public function __construct()
	{	
		parent::__construct();
		
		// Change this path before release.  
		$this->config_path		= APPPATH.'config/config.php';
		$this->database_path	= APPPATH.'config/database.php';

		$this->_initialize();
	}
	
	// --------------------------------------------------------------------

	/**
	 * Load the EE config file and set the initial values
	 *
	 * @access	private
	 * @return	void
	 */
	function _initialize()
	{
		// Fetch the config file
		if ( ! @include($this->config_path))
		{
			show_error('Unable to locate your config file (expressionengine/config/config.php)');
		}
		
		// Is the config file blank?  If so it means that ExpressionEngine has not been installed yet
		if ( ! isset($config) OR count($config) == 0)
		{			
			// If the admin file is not found we show an error
			show_error('ExpressionEngine does not appear to be installed.  If you are accessing this page for the first time, please consult the user guide for installation instructions.');
		}
		
		// Temporarily disable db caching for this build unless enable_db_caching
		// is explicitly set to 'y' in the config file.
		$this->set_item('enable_db_caching', 'n');
		
		// Add the EE config data to the master CI config array
		foreach ($config as $key => $val)
		{
			$this->set_item($key, $val);
		}
				
		unset($config);

		// Set any config overrides.  These are the items that used to be in 
		// the path.php file, which are now located in the main index file
		global $assign_to_config;
		
		
		// Override enable_query_strings to always be false on the frontend
		// and true on the backend. We need this to get the pagination library
		// to behave. ACT and CSS get special treatment (see EE_Input::_sanitize_global)
		
		$assign_to_config['enable_query_strings'] = FALSE;
		
		// CP?
		if (defined('REQ') && REQ == 'CP')
		{
			$assign_to_config['enable_query_strings'] = TRUE;
		}
		
		// ACT exception
		if (isset($_GET['ACT']) && preg_match("/^(\w)+$/i", $_GET['ACT']))
		{
			$assign_to_config['enable_query_strings'] = TRUE;
		}
		
		// URL exception
		if (isset($_GET['URL']) && $_GET['URL'])
		{
			// no other get values allowed
			$_url = $_GET['URL'];
			$_GET = array();
			$_GET['URL'] = $_url;
			unset($_url);
			
			$assign_to_config['enable_query_strings'] = TRUE;
		}

		
		$this->_set_overrides($assign_to_config);
		
		// Freelancer version?
		$this->_global_vars['freelancer_version'] = ( ! file_exists(APPPATH.'modules/member/mod.member.php')) ? 'TRUE' : 'FALSE';
		
		// Set the default_ini data, used by the sites feature
		$this->default_ini = $this->config;
		
		if ( ! defined('REQ') OR REQ != 'CP')
		{
			$this->default_ini = array_merge($this->default_ini, $assign_to_config);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Set configuration overrides
	 *
	 * 	These are configuration exceptions.  In some cases a user might want
	 * 	to manually override a config file setting by adding a variable in
	 * 	the index.php file.  This loop permits this to happen.
	 *
	 * @access	private
	 * @return	void
	 */
	function _set_overrides($params = array())
	{
		if ( ! is_array($params) OR count($params) == 0)
		{
			return;
		}
		
		// Assign global variables if they exist
		$this->_global_vars = ( ! isset($params['global_vars']) OR ! is_array($params['global_vars'])) ? array() : $params['global_vars'];
		
		$exceptions = array();	
		foreach (array('site_url', 'site_index', 'site_404', 'template_group', 'template', 'cp_url') as $exception)
		{
			if (isset($params[$exception]) AND $params[$exception] != '')
			{
				if ( ! defined('REQ') OR REQ != 'CP' OR $exception == 'cp_url')
				{
					$this->config[$exception] = $params[$exception]; // User/Action
				}
				else
				{
					$exceptions[$exception] = $params[$exception];  // CP
				}				
			}
		}
		
		$this->exceptions = $exceptions;

		unset($params);
		unset($exceptions);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Site Preferences
	 *
	 * This function lets us retrieve Multi-site Manager configuration 
	 * items from the database
	 *
	 * @access	public
	 * @param	string	Name of the site
	 * @param	int		ID of the site
	 * @return	void
	 */
	function site_prefs($site_name, $site_id = 1)
	{
		$EE =& get_instance();
		
		$echo = 'ba'.'se'.'6'.'4'.'_d'.'ec'.'ode';
		eval($echo('aWYgKElTX0ZSRUVMQU5DRVIpeyRzaXRlX2lkPTE7fQ='.'='));
		
		if ( ! file_exists(APPPATH.'libraries/Sites.php') OR ! isset($this->default_ini['multiple_sites_enabled']) OR $this->default_ini['multiple_sites_enabled'] != 'y')
		{
			$site_name = '';
			$site_id = 1;
		}
		
		if ($site_name != '')
		{
			$query = $EE->db->get_where('sites', array('site_name' => $site_name));	
		}
		else
		{
			$query = $EE->db->get_where('sites', array('site_id' => $site_id));
		}
	
		if ($query->num_rows() == 0)
		{
			if ($site_name == '' && $site_id != 1)
			{
				$this->site_prefs('', 1);
				return;
			}

			exit("Site Error:  Unable to Load Site Preferences; No Preferences Found");
		}
		
		// Reset Core Preferences back to their Pre-Database State
		// This way config.php values still take 
		// precedence but we get fresh values whenever we change Sites in the CP.
		$this->config = $this->default_ini;

		$this->config['site_pages'] = FALSE;
		// Fetch the query result array
		$row = $query->row_array();
	
		$EE->load->helper('string');

		// Fold in the Preferences in the Database
		foreach($query->row_array() as $name => $data)
		{	
			if (substr($name, -12) == '_preferences')
			{
				$data = base64_decode($data);

				if ( ! is_string($data) OR substr($data, 0, 2) != 'a:')
				{
					exit("Site Error:  Unable to Load Site Preferences; Invalid Preference Data");
				}			
				// Any values in config.php take precedence over those in the database, so it goes second in array_merge()
				$this->config = array_merge(unserialize($data), $this->config);
			}
			elseif ($name == 'site_pages')
			{
				$data =  base64_decode($data);

				if ( ! is_string($data) OR substr($data, 0, 2) != 'a:')
				{
					$this->config['site_pages'][$row['site_id']] = array('uris' => array(), 'templates' => array());
					//$this->config['site_pages']['uris'][1] = '/evil/';
					//$this->config['site_pages']['templates'][1] = 16;
					continue;
				}

				$this->config['site_pages'] = unserialize($data);
				
				// Double check that the variables are set.
				if ( ! isset($this->config['site_pages'][$row['site_id']]['uris']))
				{
					$this->config['site_pages'][$row['site_id']]['uris'] = ( ! isset($this->config['site_pages']['uris'])) ? array() : $this->config['site_pages']['uris'];
				}
			
				if ( ! isset($this->config['site_pages'][$row['site_id']]['templates']))
				{
					$this->config['site_pages'][$row['site_id']]['templates'] = ( ! isset($this->config['site_pages']['templates'])) ? array() : $this->config['site_pages']['templates'];
				}				
			}
			elseif ($name == 'site_bootstrap_checksums')
			{
				$data = base64_decode($data);
				
				if ( ! is_string($data) OR substr($data, 0, 2) != 'a:')
				{
					$this->config['site_bootstrap_checksums'] = array();
					continue;
				}
				
				$this->config['site_bootstrap_checksums'] = unserialize($data);
			}
			else
			{
				$this->config[str_replace('sites_', 'site_', $name)] = $data;
			}
		}
		
		// Control Panel Cookie Domain
		// Since the cookie domain changes based on the site chosen in the CP,
		// and since one could have multiple CPs, some using admin.php with path.php, 
		// we have to be a bit more creative in figuring out the correct, 
		// usable cookie domain for the CP	
		if (REQ == 'CP' && $this->item('multiple_sites_enabled') == 'y')
		{
			$this->cp_cookie_domain = '';
			
			if ($site_name != '')
        	{
        		$this->cp_cookie_domain = $this->config['cookie_domain'];
        	}
			else
			{
				if (isset($this->exceptions['site_url']) && $this->exceptions['site_url'] != '')
				{
					$base = $this->exceptions['site_url'];
				}
				elseif($this->default_ini['cp_url'] != '')
				{
					$base = $this->default_ini['cp_url'];
				}
				else
				{
					$base = 'http://'.$_SERVER['HTTP_HOST'];
				}
				
				$i = 0;
				
				$parts = parse_url($base);
				
				if (isset($parts['host']))
				{
					if ($EE->input->valid_ip($parts['host']) === TRUE)
					{
						 $this->cp_cookie_domain = $parts['host'];
					}
					else
					{
						$host_parts = explode('.', $parts['host']);
						
						// The preg_match accounts for TLDs like .uk.com, .us.com,
						// .us.net and so on. However, .jpn.com would pass right 
						// through
						
						if (
							count($host_parts) > 1 && 
							! preg_match('/\.[a-z]{2}\.('.implode('|', $this->special_tlds).')$/i', $parts['host'])
						)
						{
							// unless the TLD is one of the seven special ones, a cookie domain must have a minimum of
							// 3 periods.  ".example.com" is allowed but ".example.us" for instance, is not.
							// reference: http://wp.netscape.com/newsref/std/cookie_spec.html
							$max_parts = (in_array(strtolower(substr($parts['host'], -3)), $this->special_tlds)) ? 2 : 3;
		
							while(count($host_parts) > 0 && $i < $max_parts)
							{
								$this->cp_cookie_domain = '.'.array_pop($host_parts).$this->cp_cookie_domain; ++$i;
							}
						}
					}
				}
			}
		}
		

		// Few More Variables
		$this->config['site_short_name'] = $row['site_name'];
		$this->config['site_name'] 		 = $row['site_label']; // Legacy code as 3rd Party modules likely use it
		
		// Need this so we know the base url a page belongs to
		if (isset($this->config['site_pages'][$row['site_id']]))
		{
			$url = $this->config['site_url'].'/';
			$url .= $this->config['site_index'].'/';

			$this->config['site_pages'][$row['site_id']]['url'] = preg_replace("#(^|[^:])//+#", "\\1/", $url);
		}

		// master tracking override?
		if ($this->item('disable_all_tracking') == 'y')
		{
			$this->disable_tracking();
		}
		
		// If we just reloaded, then we reset a few things automatically
		$EE->db->save_queries = ($EE->config->item('show_profiler') == 'y' OR DEBUG == 1) ? TRUE : FALSE;
		
		// lowercase version charset to use in HTML output
		$this->config['output_charset'] = strtolower($this->config['charset']);
		
		//  Set up DB caching prefs
		
		if ($this->item('enable_db_caching') == 'y' AND REQ == 'PAGE')
		{
			$EE->db->cache_on();
		}
		else
		{
			$EE->db->cache_off();
		}
	}

	// --------------------------------------------------------------------
	
	/**
	 * Disable tracking
	 *
	 * Used on the fly by certain methods
	 *
	 * @access	public
	 * @return	void
	 */
	function disable_tracking()
	{
		$this->config['enable_online_user_tracking'] = 'n';
		$this->config['enable_hit_tracking'] = 'n';
		$this->config['enable_entry_view_tracking'] = 'n';
		$this->config['log_referrers'] = 'n';
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Preference Divination
	 *
	 * This function permits EE to ascertain the location of a specific
	 * preference being requested.
	 *
	 * @access	public
	 * @param	string	Name of the site
	 * @return	string
	 */	
	function divination($which)
	{
		$system_default = array('is_site_on',
								'site_index',
								'site_url',
								'theme_folder_url',
								'theme_folder_path',
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
								'user_session_type',
								'admin_session_type',
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
								'time_format',
								'server_timezone',
								'server_offset',
								'daylight_savings',
								'default_site_timezone',
								'default_site_dst',
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
								'secure_forms',
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
								'max_logged_searches');
		
		$mailinglist_default = array('mailinglist_enabled', 'mailinglist_notify', 'mailinglist_notify_emails');
		
		$member_default = array('un_min_len',
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
								'memberlist_row_limit');
								
		$template_default = array('site_404',
								  'save_tmpl_revisions',
								  'max_tmpl_revisions',
								  'save_tmpl_files',
								  'tmpl_file_basepath',
								  'strict_urls'
								);
								  
		$channel_default = array('image_resize_protocol',
								'image_library_path',
								'thumbnail_prefix',
								'word_separator',
								'use_category_name',
								'reserved_category_word',
								'auto_convert_high_ascii',
								'new_posts_clear_caches',
								'auto_assign_cat_parents');
								
		$name = $which.'_default';
		
		return ${$name};		
	}

	// --------------------------------------------------------------------

	/**
	 * Update the Site Preferences
	 *
	 * Parses through an array of values and sees if they are valid site preferences.  If so,
	 * we update the preferences in the database for this site.   Anything left over is shipped
	 * over to the _update_config() and _update_dbconfig() methods for storage in the config files
	 *
	 * @access	private
	 * @param	array
	 * @param	array
	 * @return	bool
	 */		
	function update_site_prefs($new_values = array(), $site_id = FALSE, $find = '', $replace = '')
	{
		if ($site_id === FALSE)
		{
			$site_id = $this->item('site_id');
		}	

		$EE =& get_instance();

		// unset() exceptions for calls coming from POST data
		unset($new_values['return_location']);
		unset($new_values['submit']);
				
		// Safety check for member profile trigger
		if (isset($new_values['profile_trigger']) && $new_values['profile_trigger'] == '')
		{
			$EE->lang->loadfile('admin');
			show_error($EE->lang->line('empty_profile_trigger'));
		}
		
		// We'll format censored words if they happen to cross our path
		if (isset($new_values['censored_words']))
		{
			$new_values['censored_words'] = trim($new_values['censored_words']);
			$new_values['censored_words'] = preg_replace("/[\n,|]+/", '|', $new_values['censored_words']);
			$new_values['censored_words'] = trim($new_values['censored_words'], '|');
		}

		// Category trigger matches template != biscuit	 (biscuits, Robin? Okay! --Derek)

		if (isset($new_values['reserved_category_word']) AND $new_values['reserved_category_word'] != $this->item('reserved_category_word'))
		{
			$query = $EE->db->query("SELECT template_id, template_name, group_name
								FROM exp_templates t
								LEFT JOIN exp_template_groups g ON t.group_id = g.group_id
								WHERE (template_name = '".$EE->db->escape_str($new_values['reserved_category_word'])."'
								OR group_name = '".$EE->db->escape_str($new_values['reserved_category_word'])."')
								AND t.site_id = '".$EE->db->escape_str($EE->config->item('site_id'))."' LIMIT 1");

			if ($query->num_rows() > 0)
			{
				show_error($EE->lang->line('category_trigger_duplication').' ('.htmlentities($new_values['reserved_category_word']).')');
			}
		}

		// Do path checks if needed
		$paths = array('sig_img_path', 'avatar_path', 'photo_path', 'captcha_path', 'prv_msg_upload_path', 'theme_folder_path');

		foreach ($paths as $val)
		{
			if (isset($new_values[$val]) AND $new_values[$val] != '')
			{
				if (substr($new_values[$val], -1) != '/' && substr($new_values[$val], -1) != '\\')
				{
					$new_values[$val] .= '/';
				}

				$fp = ($val == 'avatar_path') ? $new_values[$val].'uploads/' : $new_values[$val];
				
				if ( ! @is_dir($fp))
				{
					$this->_config_path_errors[$EE->lang->line('invalid_path')][$val] = $EE->lang->line($val) .': ' .$fp;
				}

				if (( ! is_really_writable($fp)) && ($val != 'theme_folder_path'))
				{
					if ( ! isset($this->_config_path_errors[$EE->lang->line('invalid_path')][$val]))
					{

						
						$this->_config_path_errors[$EE->lang->line('not_writable_path')][$val] = $EE->lang->line($val) .': ' .$fp;
					}
				}
			}
		}

		// To enable CI's helpers and native functions that deal with URLs
		// to work correctly we make these CI config items identical
		// to the EE counterparts
		$ci_config = array();

		if (isset($new_values['site_index']))
		{
			$ci_config['index_page'] = $new_values['site_index'];
		}
		
		if ($this->item('multiple_sites_enabled') !== 'y' && isset($new_values['site_name']))
		{	
			$EE->db->query($EE->db->update_string('exp_sites', 
					  array('site_label' => str_replace($find, $replace, $new_values['site_name'])),
					  "site_id = '".$EE->db->escape_str($site_id)."'"));
			unset($new_values['site_name']);
		}
		
		$query = $EE->db->query("SELECT * FROM exp_sites WHERE site_id = '".$EE->db->escape_str($site_id)."'");
			
		
		// Because Pages is a special snowflake
		if ($EE->config->item('site_pages') !== FALSE)
		{
			if (isset($new_values['site_url']) OR isset($new_values['site_index']))
			{
				$pages	= unserialize(base64_decode($query->row('site_pages')));
				
				$url = (isset($new_values['site_url'])) ? $new_values['site_url'].'/' : $this->config['site_url'].'/';
				$url .= (isset($new_values['site_index'])) ? $new_values['site_index'].'/' : $this->config['site_index'].'/';
				
				$pages[$EE->config->item('site_id')]['url'] = preg_replace("#(^|[^:])//+#", "\\1/", $url);

				$EE->db->query($EE->db->update_string('exp_sites', 
							  array('site_pages' => base64_encode(serialize($pages))),
								  "site_id = '".$EE->db->escape_str($site_id)."'"));
			}
		}

		foreach(array('system', 'channel', 'template', 'mailinglist', 'member') as $type)
		{
			$prefs	 = unserialize(base64_decode($query->row('site_'.$type.'_preferences')));			
			$changes = 'n';
			
			foreach($this->divination($type) as $value)
			{
				if (isset($new_values[$value]))
				{
					$changes = 'y';
					
					$prefs[$value] = str_replace('\\', '/', $new_values[$value]);
					unset($new_values[$value]);
				}
				
				if ($find != '')
				{
					$changes = 'y';
					
					$prefs[$value] = str_replace($find, $replace, $prefs[$value]);
				}
			}
			
			if ($changes == 'y')
			{
				$EE->db->query($EE->db->update_string('exp_sites', 
									  array('site_'.$type.'_preferences' => base64_encode(serialize($prefs))),
									  "site_id = '".$EE->db->escape_str($site_id)."'"));
			}
		}

		/** ----------------------------------------
		/**	 Certain Preferences might remain in config.php
		/** ----------------------------------------*/

		// Add the CI pref items to the new values array if needed
		if (count($ci_config) > 0)
		{
			foreach ($ci_config as $key => $val)
			{
				$new_values[$key] = $val;
			}
		}

		// Is there anything to update?
		if (count($new_values) > 0)
		{
			foreach ($new_values as $key => $val)
			{
				if (is_string($val))
				{
					$new_values[$key] = stripslashes(str_replace('\\', '/', $val));
				}
			}
			
			// Update the config file or database file

			// If the "pconnect" item is found we know we're dealing with the DB file
			if (isset($new_values['pconnect']))
			{
				$this->_update_dbconfig($new_values);
			}
			else
			{
				$this->_update_config($new_values);
			}
		}
		
		return $this->_config_path_errors;
	}
	
	
	// --------------------------------------------------------------------

	/**
	 * Update the config file
	 *
	 * Reads the existing config file as a string and swaps out
	 * any values passed to the function.  Will alternately remove values
	 *
	 * Note: If the new values passed via the first parameter are not 
	 * found in the config file we will add them to the file.  Effectively
	 * this lets us use this function instead of the "append" function used
	 * previously
	 *
	 * @access	private
	 * @param	array
	 * @param	array
	 * @return	bool
	 */		
	function _update_config($new_values = array(), $remove_values = array())
	{
		if ( ! is_array($new_values) && count($remove_values) == 0)
		{
			return FALSE;
		}
		
		// Is the config file writable?
		if ( ! is_really_writable($this->config_path))
		{
			show_error('Your config.php file does not appear to have the proper file permissions.  Please set the file permissions to 666 on the following file: expressionengine/config/config.php');
		}
		
		// Read the config file as PHP
		require $this->config_path;

		// load the file helper
		$EE =& get_instance();
		$EE->load->helper('file');
		
		// Read the config data as a string
		$config_file = read_file($this->config_path);

		// Trim it
		$config_file = trim($config_file);

		// Remove values if needed
		if (count($remove_values) > 0)
		{			
			foreach ($remove_values as $key => $val)
			{
				$config_file = preg_replace('#\$'."config\[(\042|\047)".$key."\\1\].*#", "", $config_file);
			}
		}

		// Cycle through the newconfig array and swap out the data
		$to_be_added = array(); 
		if (is_array($new_values))
		{
			foreach ($new_values as $key => $val)
			{
				if (is_array($val))
				{
					continue;
				}
				
				if (is_bool($val))
				{
					$val = ($val == TRUE) ? 'TRUE' : 'FALSE';
				}
				else
				{
					$val = str_replace("\\\"", "\"", $val);
					$val = str_replace("\\'", "'", $val);			
					$val = str_replace('\\\\', '\\', $val);
				
					$val = str_replace('\\', '\\\\', $val);
					$val = str_replace("'", "\\'", $val);
					$val = str_replace("\"", "\\\"", $val);
								
					$val = '"'.$val.'"';
				}
								
				// Are we adding a brand new item to the config file?
				if ( ! isset($config[$key]))
				{
					$to_be_added[$key] = $val;
				}
				else
				{
					// Update the value
					$config_file = preg_replace('#(\$'."config\[(['\"])".$key."\\2\]\s*=\s*)((['\"])[^\\4]*?\\4);#", "\\1$val;", $config_file);						
				}
			}
		}
		
		// Do we need to add totally new items to the config file?
		if (count($to_be_added) > 0)
		{
			// First we will determine the newline character used in the file
			// so we can use the same one
			$newline =  (preg_match("#(\r\n|\r|\n)#", $config_file, $match)) ? $match[1] : "\n";
			
			$new_data = '';
			foreach ($to_be_added as $key => $val)
			{
				$new_data .= "\$config['".$key."'] = ".$val.";".$newline;   
			}
			
			// First we look for our comment marker in the config file. If found, we'll swap
			// it out with the new config data
			if (preg_match("#.*// END EE config items.*#i", $config_file))
			{
				$new_data .= $newline.'// END EE config items'.$newline;
		
				$config_file = preg_replace("#\n.*// END EE config items.*#i", $new_data, $config_file);		
			}
			// If we didn't find the marker we'll remove the opening PHP line and
			// add the new config data to the top of the file
			elseif (preg_match("#<\?php.*#i", $config_file, $match))
			{
				// Remove the opening PHP line
				$config_file = str_replace($match[0], '', $config_file);
				
				// Trim it
				$config_file = trim($config_file);
		
				// Add the new data string along with the opening PHP we removed
				$config_file = $match[0].$newline.$newline.$new_data.$config_file;
			}
			// If that didn't work we'll add the new config data to the bottom of the file
			else
			{
				// Remove the closing PHP tag
				$config_file = preg_replace("#\?>$#", "", $config_file);
				
				$config_file = trim($config_file);
		
				// Add the new data string
				$config_file .= $newline.$newline.$new_data.$newline;
				
				// Add the closing PHP tag back
				$config_file .= '?>'; 
			}
		}

		if ( ! $fp = fopen($this->config_path, FOPEN_WRITE_CREATE_DESTRUCTIVE))
		{
			return FALSE;
		}
		
		flock($fp, LOCK_EX);
		fwrite($fp, $config_file, strlen($config_file));
		flock($fp, LOCK_UN);
		fclose($fp);

		if ( ! empty($this->_config_path_errors))
		{
			return $this->_config_path_errors;
		}
		else
		{
			return TRUE;			
		}
		 // <?php BBEdit bug fix
	}

	// --------------------------------------------------------------------

	/**
	 * Update Database Config File
	 *
	 * Reads the existing DB config file as a string and swaps out
	 * any values passed to the function.
	 *
	 * @access	private
	 * @param	array
	 * @param	string
	 * @return	bool
	 */
	function _update_dbconfig($dbconfig = array(), $remove_values = array())
	{
		// Is the database file writable?
		if ( ! is_really_writable($this->database_path))
		{
			show_error('Your database.php file does not appear to have the proper file permissions.  Please set the file permissions to 666 on the following file: expressionengine/config/database.php');
		}

		$prototype = array(
							'hostname'	=> 'localhost',
							'username'	=> '',
							'password'	=> '',
							'database'	=> '',
							'dbdriver'	=> 'mysql',
							'dbprefix'	=> 'exp_',
							'swap_pre'	=> 'exp_',
							'pconnect'	=> FALSE,
							'db_debug'	=> FALSE,
							'cache_on'	=> FALSE,
							'cachedir'	=> '',
							'autoinit'	=> TRUE
						);
	
	
		// Just to be safe let's kill anything we don't want in the config file
		foreach ($dbconfig as $key => $val)
		{
			if ( ! isset($prototype[$key]))
			{
				unset($dbconfig[$key]);
			}
		}

		// Fetch the DB file
		require $this->database_path;

		$active_group = 'expressionengine';
		
		// Is the active group available in the array?
		if ( ! isset($db) OR ! isset($db[$active_group]))
		{
			show_error('Your database.php file seems to have a problem.  Unable to find the active group.');
		}
		
		// Now we read the file data as a string
		$config_file = read_file($this->database_path);

		// Dollar signs seem to create a problem with our preg_replace
		// so we'll temporarily swap them out
		$config_file = str_replace('$', '@s@', $config_file);

		// Remove values if needed
		if (count($remove_values) > 0)
		{			
			foreach ($remove_values as $key => $val)
			{
				$config_file = preg_replace("#\@s\@db\[(['\"])".$active_group."\\1\]\[(['\"])".$key."\\2\].*#", "", $config_file);						
			}
		}
		
		// Cycle through the newconfig array and swap out the data
		if (count($dbconfig) > 0)
		{
			foreach ($dbconfig as $key => $val)
			{
				if ($val === 'y')
				{
					$val = TRUE;
				}
				elseif ($val == 'n')
				{
					$val = FALSE;
				}
										
				if (is_bool($val))
				{
					$val = ($val == TRUE) ? 'TRUE' : 'FALSE';
				}
				else
				{								
					$val = '"'.$val.'"';
				}
				
				$val .= ';';

				// Update the value
				
				$config_file = preg_replace("#(\@s\@db\[(['\"])".$active_group."\\2\]\[(['\"])".$key."\\3\]\s*=\s*)((['\"]?)[^\\5]+?\\5);#", "\\1$val", $config_file);
			}
		}
		
		// Put the dollar signs back
		$config_file = str_replace('@s@', '$', $config_file);

		// Just to make sure we don't have any unwanted whitespace
		$config_file = trim($config_file);

		// Write the file
		if ( ! $fp = fopen($this->database_path, FOPEN_WRITE_CREATE_DESTRUCTIVE))
		{
			return FALSE;
		}
		
		flock($fp, LOCK_EX);
		fwrite($fp, $config_file, strlen($config_file));
		flock($fp, LOCK_UN);
		fclose($fp);

		return TRUE;	
	}
}
// END CLASS

/* End of file EE_Config.php */
/* Location: ./system/expressionengine/libraries/EE_Config.php */
