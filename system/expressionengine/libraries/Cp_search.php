<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine CP Search Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Cp_search {

	var $map;
	var $_c_cache = array();

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		$this->EE =& get_instance();
		$this->_search_map();

		ee()->lang->loadfile('cp_search');
	}

	// --------------------------------------------------------------------

	/**
	 * Generate Search Results
	 *
	 * Grabs the results for the cp search function and loads the
	 * proper cp variables
	 *
	 * @access	public
	 * @param	type
	 * @return	void
	 */
	function generate_results($search)
	{
		$result = array();

		$sql = "SELECT *, MATCH(keywords) AGAINST (?) AS relevance
				FROM ".ee()->db->dbprefix('cp_search_index')."
				WHERE MATCH(keywords) AGAINST (?)
				ORDER BY relevance DESC";

		$query = ee()->db->query($sql, array($search, $search));

		foreach($query->result() as $row)
		{
			// Don't show things they cannot use
			if ($row->access != 'all' && ee()->session->userdata['group_id'] != 1 && ee()->session->userdata[$row->access] != 'y')
			{
				continue;
			}

			$url = BASE.AMP.'C='.$row->controller.AMP.'M='.$row->method;
			$name = $this->_get_description($row->controller, $row->method);

			$result[] = array('url' => $url, 'name' => $name);
		}

		return $result;
	}

	// --------------------------------------------------------------------

	/**
	 * Get Result Name
	 *
	 * Creates a proper language key for a given controller+method
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	function _get_description($controller, $method)
	{
		$options = $this->map[$controller][$method];

		if ( ! is_array($options) && substr($options, -4) == '_cfg')
		{
			return ee()->lang->line($options);
		}

		if (is_array($options))
		{
			unset($options['access'], $options['keywords']);
			$options = array_pop($options);
		}

		if (substr($options, -4) == '_cfg')
		{
			return ee()->lang->line($options);
		}
		else
		{
			$prefix = $controller.'_';

			if ($start = strpos($controller, '_'))
			{
				$prefix = substr($controller, $start + 1, 4).'_';
			}

			if (strpos($method, $prefix) === 0)
			{
				return ee()->lang->line($method);
			}

			return ee()->lang->line($prefix.$method);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Checks for an existing index
	 *
	 * If no index exists it will return false, redirect to rebuild
	 *
	 * @access	private
	 * @return	void
	 */
	function _check_index($language = 'english')
	{
		ee()->db->where('language', $language);
		$count = ee()->db->count_all_results('cp_search_index');

		return ($count > 0);
	}

	// --------------------------------------------------------------------

	/**
	 * Build Index
	 *
	 * Builds a controller and method lookup based on relevant
	 * language keys used by these methods
	 *
	 * @access	public
	 * @param	type
	 * @return	void
	 */
	function _build_index($language)
	{
		// PHP 4 redundancy dept. of redundancy
		$this->EE =& get_instance();

		ee()->load->model('admin_model');
		ee()->lang->loadfile('admin');

		$data = array();

		$subtext = ee()->config->get_config_field_subtext();

		foreach($this->map as $controller => $method_map)
		{
			foreach($method_map as $method => $val)
			{
				$values = array();
				$options = $val;

				$access = 'all';

				if (is_array($options))
				{
					if (isset($options['access']))
					{
						$access = $options['access'];
						unset($options['access']);
					}

					if (isset($options['keywords']))
					{
						$values[] = $options['keywords'];
						unset($options['keywords']);
					}

					// Flatten! Flatten!
					$options = array_pop($options);
				}

				if (is_array($options))
				{
					foreach($options as $keyword)
					{
						$values[] = $keyword;
					}
				}
				elseif (is_string($options) && substr($options, -4) == '_cfg')
				{
					$config = ee()->config->get_config_fields($options);

					foreach($config as $lang_key => $whatever)
					{
						$values[] = ee()->lang->line($lang_key);
					}

					if (isset($subtext[$options]))
					{
						foreach($subtext[$options] as $lang_keys)
						{
							foreach($lang_keys as $key)
							{
								$values[] = $key;
							}
						}
					}
				}
				else
				{
					if ($options)
					{
						$values = array_merge($values, $this->_parse_controller($controller, $method, TRUE));
					}
					else
					{
						$values = array_merge($values, $this->_parse_controller($controller, $method));
					}

					$values[] = $this->_get_description($controller, $method);
				}

				if ( ! count($values) > 0)
				{
					continue;
				}

				$data = array('controller'		=> $controller,
								'method'		=> $method,
								'keywords'		=> implode(' ', $values),
								'access'		=> $access,
								'language'		=> $language
								);

				ee()->db->insert('cp_search_index', $data);
			}
		}

		return TRUE;
	}


	// --------------------------------------------------------------------

	/**
	 * Parse Controller
	 *
	 * Walks through a given controller function looking
	 * for language keys
	 *
	 * @access	public
	 * @param	type
	 * @return	void
	 */
	function _parse_controller($controller, $method, $process_views = FALSE)
	{
		$nonsense = array('unauthorized_access', 'none', 'all', 'open', 'closed', 'and_more', 'install', 'uninstall', 'add', 'edit', 'delete');
		$map = array();
		$lang_files = array();

		if ( ! file_exists(APPPATH.'controllers/cp/'.$controller.'.php'))
		{
			return array();
		}

		// Cache controller info so we don't parse the same file a bajilion times
		if ( ! isset($this->_c_cache['name']) OR $this->_c_cache['name'] != $controller)
		{
			// Grab the file contents
			$this->_c_cache['source'] = file_get_contents(APPPATH.'controllers/cp/'.$controller.'.php');

			// Initialize arrays
			$this->_c_cache['methods'] = array();
			$this->_c_cache['lang_files'] = array();

			$lang_files[] = current(explode('_', $controller, 2));

			// Language files used by this class
			if (preg_match_all('#'.preg_quote('$this->lang->loadfile(').'(\042|\047)([^\\1]*?)\\1#', $this->_c_cache['source'], $matches))
			{
				foreach($matches[2] as $match)
				{
					$lang_files[] = $match;
				}
			}

			// Methods used by this class
			if (preg_match_all('#function\s+(\w+)\(#i', $this->_c_cache['source'], $functions))
			{
				foreach($functions[0] as $key => $func)
				{
					$name = $functions[1][$key];

					$start = strpos($this->_c_cache['source'], $functions[0][$key]);
					$end = isset($functions[0][$key+1]) ? strpos($this->_c_cache['source'], $functions[0][$key+1]) : strlen($this->_c_cache['source']);

					$this->_c_cache['methods'][$name] = array($start, $end);
				}
			}
		}

		// We're only here for one thing
		if ( ! isset($this->_c_cache['methods'][$method]))
		{
			return array();
		}

		// Grab the function source
		$start = $this->_c_cache['methods'][$method][0];
		$end = $this->_c_cache['methods'][$method][1];

		$chunk = substr($this->_c_cache['source'], $start, $end - $start);

		$views = array();
		$langs = array();

		// Views loaded by this function
		if (preg_match_all('#'.preg_quote('$this->load->view(').'(\042|\047)([^\\1]*?)\\1#', $chunk, $matches))
		{
			foreach($matches[2] as $match)
			{
				$views[] = $match;

				if ($process_views)
				{
					$langs = $this->_process_view($match);
				}
			}
		}
		else
		{
			// No views - no output - no point in giving them a link!
			return array();
		}

		// Language keys used by the function
		if (preg_match_all('#'.preg_quote('$this->lang->line(').'(\042|\047)([^\\1]*?)\\1#', $chunk, $matches))
		{
			foreach($matches[2] as $match)
			{
				// Skip the ridiculously common ones
				if (in_array($match, $nonsense))
				{
					continue;
				}

				$langs[] = $match;
			}
		}

		$language = 'english';

		$lang_files = array_unique($lang_files);
		$langs = array_unique($langs);

		$values = array();

		foreach($lang_files as $langfile)
		{
			include(APPPATH.'language/'.$language.'/'.$langfile.'_lang.php');

			if (isset($lang))
			{
				foreach($langs as $lang_key)
				{
					if (isset($lang[$lang_key]))
					{
						$values[] = $lang[$lang_key];
					}
				}
			}
		}

		return $values;
	}

	// --------------------------------------------------------------------

	/**
	 * Process View
	 *
	 * Finds language keys in a view
	 *
	 * @access	public
	 * @param	type
	 * @return	void
	 */
	function _process_view($view)
	{
		$nonsense = array('unauthorized_access', 'none', 'all', 'open', 'closed', 'and_more', 'install', 'uninstall', 'add', 'edit', 'delete');

		$langs = array();
		$path = PATH_CP_THEME.ee()->config->item('cp_theme').'/'.$view.'.php';

		if ( ! file_exists($path))
		{
			return $langs;
		}

		$view = str_replace('.php', '', $view);
		$view = file_get_contents($path);

		if (preg_match_all('#'.preg_quote('lang(').'(\042|\047)([^\\1]*?)\\1#', $view, $matches))
		{
			foreach($matches[2] as $match)
			{
				// Skip the ridiculously common ones
				if (in_array($match, $nonsense))
				{
					continue;
				}

				$langs[] = $match;
			}
		}

		return $langs;
	}

	// --------------------------------------------------------------------

	function _search_map()
	{
		// How it works:
		// The array contains controllers and method names that are indexed
		// by the search function.  The value for every method element can
		// take on three values:
		// 1. something_cfg	- uses the language arrays in the admin_model
		// 2. custom keywords - a custom array of keywords (not multilingual - use with care)
		// 3. TRUE/FALSE - parse called view files for language keys?
		//			if false, only the function will be searched for lang keys
		// 			Set to false to improve indexing performance, but only do it
		//			on methods that use lots of language keys or you'll neuter the index
		//
		// By using a multidimensional array we can add access control:
		// array('access' => 'can_access_accessories', <regular options>)
		//
		// As well as keywords to increase result relevance
		// array('keywords' => 'cookies', <regular options>)
		//
		$this->map = array(
			'admin_system'		=> array(
					'general_configuration'			=> array('access' => 'can_access_sys_prefs', 'general_cfg'),
					'output_debugging_preferences'	=> array('access' => 'can_access_sys_prefs', 'output_cfg'),
					'database_settings'				=> array('access' => 'can_access_sys_prefs', 'db_cfg'),
					'security_session_preferences'	=> array('access' => 'can_access_sys_prefs', 'keywords' => 'cookie cookies', 'security_cfg'),
					'throttling_configuration'		=> array('access' => 'can_access_sys_prefs', 'throttling_cfg'),
					'localization_settings'			=> array('access' => 'can_access_sys_prefs', 'localization_cfg'),
					'email_configuration'			=> array('access' => 'can_access_sys_prefs', 'email_cfg'),
					'cookie_settings'				=> array('access' => 'can_access_sys_prefs', 'keywords' => 'cookies', 'cookie_cfg'),
					'image_resizing_preferences'	=> array('access' => 'can_access_sys_prefs', 'image_cfg'),
					'captcha_preferences'			=> array('access' => 'can_access_sys_prefs', 'captcha_cfg'),
					'word_censoring'				=> array('access' => 'can_access_sys_prefs', 'censoring_cfg'),
					'mailing_list_preferences'		=> array('access' => 'can_access_sys_prefs', 'mailinglist_cfg'),
					'emoticon_preferences'			=> array('access' => 'can_access_sys_prefs', 'emoticon_cfg'),
					'tracking_preferences'			=> array('access' => 'can_access_sys_prefs', 'tracking_cfg'),
					'mailing_list_preferences'		=> array('access' => 'can_access_sys_prefs', 'mailinglist_cfg'),
					'search_log_configuration'		=> array('access' => 'can_access_sys_prefs', 'search_log_cfg')
			),
			'admin_content'		=> array(
					'global_channel_preferences'	=> array('access' => 'can_admin_channels', 'channel_cfg'),
					'field_group_management'		=> array('access' => 'can_admin_channels', TRUE),
					'category_management'			=> array('access' => 'can_admin_categories', TRUE)
			),
			'addons_accessories'=> array(
					'index'							=> array('access' => 'can_access_accessories', TRUE)
			),
			'addons_extensions'	=> array(
					'index'							=> array('access' => 'can_access_extensions', TRUE)
			),
			'addons_fieldtypes'	=> array(
					'index'							=> array('access' => 'can_access_fieldtypes', TRUE)
			),
			'addons_modules'	=> array(
					'index'							=> array('access' => 'can_access_modules', TRUE)
			),
			'addons_plugins'	=> array(
					'index'							=> array('access' => 'can_access_plugins', TRUE)
			),
			'content_publish'		=> array(
					'index'							=> array('keywords' => 'publish new entry', TRUE)
			),
			'content_files'		=> array(
					'index'							=> array('access' => 'can_access_files', TRUE)
			),
			'design'			=> array(
					'user_message'					=> array('access' => 'can_admin_design', TRUE),
					'global_template_preferences'	=> array('access' => 'can_admin_design', 'template_cfg'),
					'system_offline'				=> array('access' => 'can_admin_design', TRUE),
					'email_notification'			=> array('access' => 'can_admin_templates', TRUE),
					'member_profile_templates'		=> array('access' => 'can_admin_mbr_templates', TRUE)
			),
			'members'			=> array(
					'register_member'				=> array('access' => 'can_admin_members', TRUE),
					'member_validation'				=> array('access' => 'can_admin_members', TRUE),
					'view_members'					=> array('access' => 'can_access_members', TRUE),
					'ip_search'						=> array('access' => 'can_admin_members', 'keywords' => 'ip IP', TRUE),
					'custom_profile_fields'			=> array('access' => 'can_admin_members', TRUE),
					'member_group_manager'			=> array('access' => 'can_admin_mbr_groups', TRUE),
					'member_config'					=> array('access' => 'can_admin_members', TRUE),
					'member_banning'				=> array('access' => 'can_ban_users', TRUE),
					'member_search'					=> TRUE
			),
			'tools_data'		=> array(
					'sql_manager'					=> array('access' => 'can_access_data', TRUE),
					'search_and_replace'			=> array('access' => 'can_access_data', TRUE),
					'recount_stats'					=> array('access' => 'can_access_data', TRUE),
					'php_info'						=> array('access' => 'can_access_data', TRUE),
					'clear_caching'					=> array('access' => 'can_access_data', TRUE)
			),
			'tools_logs'		=> array(
					'view_cp_log'					=> array('access' => 'can_access_logs', TRUE),
					'view_throttle_log'				=> array('access' => 'can_access_logs', TRUE),
					'view_search_log'				=> array('access' => 'can_access_logs', TRUE),
					'view_email_log'				=> array('access' => 'can_access_logs', TRUE),
					'view_developer_log'			=> array('access' => 'can_access_logs', TRUE)
			),
			'tools_utilities'	=> array(
					'member_import'					=> array('access' => 'can_access_utilities', TRUE),
					'import_from_xml'				=> array('access' => 'can_access_utilities', TRUE),
					'translation_tool'				=> array('access' => 'can_access_utilities', TRUE)
			),
		);
	}
}

// END Cp_search class

/* End of file Cp_search.php */
/* Location: ./system/expressionengine/libraries/Cp_search.php */