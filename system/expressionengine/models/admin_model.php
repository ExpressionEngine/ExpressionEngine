<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Admin Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Admin_model extends CI_Model {

	/**
	 * Get Config Fields
	 *
	 * Fetches the config/preference fields, their types, and their default values
	 *
	 * @access	public
	 * @param	string
	 * @return	array
	 */
	function get_config_fields($type)
	{
		$debug_options = array('1' => 'debug_one', '2' => 'debug_two');

		// If debug is set to 0, make sure it's an option in Output and Debugging
		if ($this->config->item('debug') == 0)
		{
			$debug_options['0'] = 'debug_zero';
			ksort($debug_options);
		}

		$f_data = array(
			'general_cfg'		=>	array(
				'multiple_sites_enabled'	=> array('r', array('y' => 'yes', 'n' => 'no')),
				'is_system_on'				=> array('r', array('y' => 'yes', 'n' => 'no')),
				'is_site_on'				=> array('r', array('y' => 'yes', 'n' => 'no')),
				'license_number'			=> array('i', ''),
				'site_name'					=> array('i', '', 'required'),
				'site_index'				=> array('i', ''),
				'site_url'					=> array('i', '', 'required'),
				'cp_url'					=> array('i', '', 'required'),
				'theme_folder_url'			=> array('i', '', 'required'),
				'theme_folder_path'			=> array('i', '', 'required'),
				'cp_theme'					=> array('f', 'theme_menu'),
				'deft_lang'					=> array('f', 'language_menu'),
				'xml_lang'					=> array('f', 'fetch_encoding'),
				'max_caches'				=> array('i', ''),
				'new_version_check'			=> array('r', array('y' => 'yes', 'n' => 'no')),
				// 'channel_nomenclature'		=> array('i', ''),
				'doc_url'					=> array('i', ''),
			),

			'db_cfg'			=>	array(
				'db_debug'					=> array('r', array('y' => 'yes', 'n' => 'no')),
				'pconnect'					=> array('r', array('y' => 'yes', 'n' => 'no')),
				// 'cache_on'					=> array('r', array('y' => 'yes', 'n' => 'no')),
				// 'enable_db_caching'			=> array('r', array('y' => 'yes', 'n' => 'no')),
			),

			'output_cfg'		=>	array(
				'send_headers'				=> array('r', array('y' => 'yes', 'n' => 'no')),
				'gzip_output'				=> array('r', array('y' => 'yes', 'n' => 'no')),
				'force_query_string'		=> array('r', array('y' => 'yes', 'n' => 'no')),
				'redirect_method'			=> array('s', array('redirect' => 'location_method', 'refresh' => 'refresh_method')),
				'debug'						=> array('s', $debug_options),
				'show_profiler'				=> array('r', array('y' => 'yes', 'n' => 'no')),
				'template_debugging'		=> array('r', array('y' => 'yes', 'n' => 'no'))
			),

			'channel_cfg'		=>	array(
				'use_category_name'			=> array('r', array('y' => 'yes', 'n' => 'no')),
				'reserved_category_word'	=> array('i', ''),
//					'auto_convert_high_ascii'	=> array('r', array('y' => 'yes', 'n' => 'no')),
				'auto_assign_cat_parents'	=> array('r', array('y' => 'yes', 'n' => 'no')),
				'new_posts_clear_caches'	=> array('r', array('y' => 'yes', 'n' => 'no')),
				'enable_sql_caching'		=> array('r', array('y' => 'yes', 'n' => 'no')),
				'word_separator'			=> array('s', array('dash' => 'dash', 'underscore' => 'underscore')),
			),

			'image_cfg'			=>	array(
				'image_resize_protocol'		=> array('s', array('gd' => 'gd', 'gd2' => 'gd2', 'imagemagick' => 'imagemagick', 'netpbm' => 'netpbm')),
				'image_library_path'		=> array('i', ''),
				'thumbnail_prefix'			=> array('i', '')
			),

			'security_cfg'		=>	array(
				'admin_session_type'		=> array('s', array('cs' => 'cs_session', 'c' => 'c_session', 's' => 's_session')),
				'user_session_type'			=> array('s', array('cs' => 'cs_session', 'c' => 'c_session', 's' => 's_session')),
				'secure_forms'				=> array('r', array('y' => 'yes', 'n' => 'no')),
				'deny_duplicate_data'		=> array('r', array('y' => 'yes', 'n' => 'no')),
				'redirect_submitted_links'	=> array('r', array('y' => 'yes', 'n' => 'no')),
				'allow_username_change'		=> array('r', array('y' => 'yes', 'n' => 'no')),
				'allow_multi_logins'		=> array('r', array('y' => 'yes', 'n' => 'no')),
				'require_ip_for_login'		=> array('r', array('y' => 'yes', 'n' => 'no')),
				'require_ip_for_posting'	=> array('r', array('y' => 'yes', 'n' => 'no')),
				'xss_clean_uploads'			=> array('r', array('y' => 'yes', 'n' => 'no')),
				'password_lockout'			=> array('r', array('y' => 'yes', 'n' => 'no')),
				'password_lockout_interval' => array('i', ''),
				'require_secure_passwords'	=> array('r', array('y' => 'yes', 'n' => 'no')),
				'allow_dictionary_pw'		=> array('r', array('y' => 'yes', 'n' => 'no')),
				'name_of_dictionary_file'	=> array('i', ''),
				'un_min_len'				=> array('i', ''),
				'pw_min_len'				=> array('i', '')
			),

			'throttling_cfg'	=>	array(
				'enable_throttling'			=> array('r', array('y' => 'yes', 'n' => 'no')),
				'banish_masked_ips'			=> array('r', array('y' => 'yes', 'n' => 'no')),
				'max_page_loads'			=> array('i', ''),
				'time_interval'				=> array('i', ''),
				'lockout_time'				=> array('i', ''),
				'banishment_type'			=> array('s', array('404' => '404_page', 'redirect' => 'url_redirect', 'message' => 'show_message')),
				'banishment_url'			=> array('i', ''),
				'banishment_message'		=> array('i', '')
			),

			'localization_cfg'	=>	array(
				'default_site_timezone'		=> array('f', 'timezone'),
				'time_format'				=> array('s', array('us' => 'united_states', 'eu' => 'european'))
			),

			'email_cfg'			=>	array(
				'webmaster_email'			=> array('i', '', 'required|valid_email'),
				'webmaster_name'			=> array('i', ''),
				'email_charset'				=> array('i', ''),
				'email_debug'				=> array('r', array('y' => 'yes', 'n' => 'no')),
				'mail_protocol'				=> array('s', array('mail' => 'php_mail', 'sendmail' => 'sendmail', 'smtp' => 'smtp')),
				'smtp_server'				=> array('i', '', 'callback__smtp_required_field'),
				'smtp_port'					=> array('i', '', 'is_natural|callback__smtp_required_field'),
				'smtp_username'				=> array('i', ''),
				'smtp_password'				=> array('p', ''),
				'email_batchmode'			=> array('r', array('y' => 'yes', 'n' => 'no')),
				'email_batch_size'			=> array('i', ''),
				'mail_format'				=> array('s', array('plain' => 'plain_text', 'html' => 'html')),
				'word_wrap'					=> array('r', array('y' => 'yes', 'n' => 'no')),
				'email_console_timelock'	=> array('i', ''),
				'log_email_console_msgs'	=> array('r', array('y' => 'yes', 'n' => 'no')),
				'email_module_captchas'		=> array('r', array('y' => 'yes', 'n' => 'no'))
			),

			'cookie_cfg'		=>	array(
				'cookie_domain'				=> array('i', ''),
				'cookie_path'				=> array('i', ''),
				'cookie_prefix'				=> array('i', '')
			),

			'captcha_cfg'		=>	array(
				'captcha_path'				=> array('i', ''),
				'captcha_url'				=> array('i', ''),
				'captcha_font'				=> array('r', array('y' => 'yes', 'n' => 'no')),
				'captcha_rand'				=> array('r', array('y' => 'yes', 'n' => 'no')),
				'captcha_require_members'	=> array('r', array('y' => 'yes', 'n' => 'no'))
			),

			'search_log_cfg'	=>	array(
				'enable_search_log'			=> array('r', array('y' => 'yes', 'n' => 'no')),
				'max_logged_searches'		=> array('i', '')
			),

			'template_cfg'		=>	array(
				'strict_urls'				=> array('d', array('y' => 'yes', 'n' => 'no')),
				'site_404'					=> array('f', 'site_404'),
				'save_tmpl_revisions'		=> array('r', array('y' => 'yes', 'n' => 'no')),
				'max_tmpl_revisions'		=> array('i', ''),
				'save_tmpl_files'			=> array('r', array('y' => 'yes', 'n' => 'no')),
				'tmpl_file_basepath'		=> array('i', '')
			),

			'censoring_cfg'		=>	array(
				'enable_censoring'			=> array('r', array('y' => 'yes', 'n' => 'no')),
				'censor_replacement'		=> array('i', ''),
				'censored_words'			=> array('t', array('rows' => '20', 'kill_pipes' => TRUE)),
			),

			'mailinglist_cfg'	=>	array(
				'mailinglist_enabled'		=> array('r', array('y' => 'yes', 'n' => 'no')),
				'mailinglist_notify'		=> array('r', array('y' => 'yes', 'n' => 'no')),
				'mailinglist_notify_emails' => array('i', '')
			),

			'emoticon_cfg'		=>	array(
				'enable_emoticons'			=> array('r', array('y' => 'yes', 'n' => 'no')),
				'emoticon_url'				=> array('i', '')
			),

			'tracking_cfg'		=>	array(
				'enable_online_user_tracking'	=> array('r', array('y' => 'yes', 'n' => 'no'), 'y'),
				'enable_hit_tracking'			=> array('r', array('y' => 'yes', 'n' => 'no'), 'y'),
				'enable_entry_view_tracking'	=> array('r', array('y' => 'yes', 'n' => 'no'), 'n'),
				'log_referrers'					=> array('r', array('y' => 'yes', 'n' => 'no')),
				'max_referrers'					=> array('i', ''),
				'dynamic_tracking_disabling'	=> array('i', '')
			),

			'recount_prefs'		=>  array(
				'recount_batch_total'			=> array('i', array('1000')),
			)
		);

		// don't show or edit the CP URL from masked CPs
		if (defined('MASKED_CP') && MASKED_CP === TRUE)
		{
			unset($f_data['general_cfg']['cp_url']);
		}

		if ( ! file_exists(APPPATH.'libraries/Sites.php') OR IS_CORE)
		{
			unset($f_data['general_cfg']['multiple_sites_enabled']);
		}

		if ($this->config->item('multiple_sites_enabled') == 'y')
		{
			unset($f_data['general_cfg']['site_name']);
		}
		else
		{
			unset($f_data['general_cfg']['is_site_on']);
		}

		if ( ! $this->db->table_exists('referrers'))
		{
			unset($f_data['tracking_cfg']['log_referrers']);
		}

		// add New Relic if the extension is installed
		if (extension_loaded('newrelic'))
		{
			$new_relic_cfg = array(
				'newrelic_app_name' => array('i', ''),
				'use_newrelic' => array('r', array('y' => 'yes', 'n' => 'no'), 'y')
			);
			$f_data['output_cfg'] = array_merge($new_relic_cfg, $f_data['output_cfg']);
		}

		return $f_data[$type];
	}

	// --------------------------------------------------------------------

	/**
	 * Get Configuration Subtext
	 *
	 * Secondary lines of text used in configuration pages
	 * This text appears below any given preference definition
	 *
	 * @access	public
	 * @return	array
	 */
	function get_config_field_subtext()
	{
		return array(
			'site_url'					=> array('url_explanation'),
			'is_site_on'				=> array('is_site_on_explanation'),
			'is_system_on'				=> array('is_system_on_explanation'),
			'debug'						=> array('debug_explanation'),
			'show_profiler'				=> array('show_profiler_explanation'),
			'template_debugging'		=> array('template_debugging_explanation'),
			'max_caches'				=> array('max_caches_explanation'),
			'use_newrelic'				=> array('use_newrelic_explanation'),
			'newrelic_app_name'			=> array('newrelic_app_name_explanation'),
			'gzip_output'				=> array('gzip_output_explanation'),
			'server_offset'				=> array('server_offset_explain'),
			'default_member_group'		=> array('group_assignment_defaults_to_two'),
			'smtp_server'				=> array('only_if_smpte_chosen'),
			'smtp_port'					=> array('only_if_smpte_chosen'),
			'smtp_username'				=> array('only_if_smpte_chosen'),
			'smtp_password'				=> array('only_if_smpte_chosen'),
			'email_batchmode'			=> array('batchmode_explanation'),
			'email_batch_size'			=> array('batch_size_explanation'),
			'webmaster_email'			=> array('return_email_explanation'),
			'cookie_domain'				=> array('cookie_domain_explanation'),
			'cookie_prefix'				=> array('cookie_prefix_explain'),
			'cookie_path'				=> array('cookie_path_explain'),
			'secure_forms'				=> array('secure_forms_explanation'),
			'deny_duplicate_data'		=> array('deny_duplicate_data_explanation'),
			'redirect_submitted_links'	=> array('redirect_submitted_links_explanation'),
			'require_secure_passwords'	=> array('secure_passwords_explanation'),
			'allow_dictionary_pw'		=> array('real_word_explanation', 'dictionary_note'),
			'censored_words'			=> array('censored_explanation', 'censored_wildcards'),
			'censor_replacement'		=> array('censor_replacement_info'),
			'password_lockout'			=> array('password_lockout_explanation'),
			'password_lockout_interval' => array('login_interval_explanation'),
			'require_ip_for_login'		=> array('require_ip_explanation'),
			'allow_multi_logins'		=> array('allow_multi_logins_explanation'),
			'name_of_dictionary_file'	=> array('dictionary_explanation'),
			'force_query_string'		=> array('force_query_string_explanation'),
			'image_resize_protocol'		=> array('image_resize_protocol_exp'),
			'image_library_path'		=> array('image_library_path_exp'),
			'thumbnail_prefix'			=> array('thumbnail_prefix_exp'),
			'member_theme'				=> array('member_theme_exp'),
			'require_terms_of_service'	=> array('require_terms_of_service_exp'),
			'email_console_timelock'	=> array('email_console_timelock_exp'),
			'log_email_console_msgs'	=> array('log_email_console_msgs_exp'),
			'use_membership_captcha'	=> array('captcha_explanation'),
			'strict_urls'				=> array('strict_urls_info'),
			'tmpl_display_mode'			=> array('tmpl_display_mode_exp'),
			'save_tmpl_files'			=> array('save_tmpl_files_exp'),
			'tmpl_file_basepath'		=> array('tmpl_file_basepath_exp'),
			'site_404'					=> array('site_404_exp'),
			'channel_nomenclature'		=> array('channel_nomenclature_exp'),
			'enable_sql_caching'		=> array('enable_sql_caching_exp'),
			'email_debug'				=> array('email_debug_exp'),
			'use_category_name'			=> array('use_category_name_exp'),
			'reserved_category_word'	=> array('reserved_category_word_exp'),
			'auto_assign_cat_parents'	=> array('auto_assign_cat_parents_exp'),
			'save_tmpl_revisions'		=> array('template_rev_msg'),
			'max_tmpl_revisions'		=> array('max_revisions_exp'),
			'max_page_loads'			=> array('max_page_loads_exp'),
			'time_interval'				=> array('time_interval_exp'),
			'lockout_time'				=> array('lockout_time_exp'),
			'banishment_type'			=> array('banishment_type_exp'),
			'banishment_url'			=> array('banishment_url_exp'),
			'banishment_message'		=> array('banishment_message_exp'),
			'enable_search_log'			=> array('enable_search_log_exp'),
			'mailinglist_notify_emails' => array('separate_emails'),
			'dynamic_tracking_disabling'=> array('dynamic_tracking_disabling_info')
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Get XML Encodings
	 *
	 * Returns an associative array of XML language keys and values
	 *
	 * @access	public
	 * @return	array
	 */
	function get_xml_encodings()
	{
		static $encodings;

		if ( ! isset($encodings))
		{
			$file = APPPATH.'config/languages.php';

			if ( ! file_exists($file))
			{
				return FALSE;
			}

			require_once $file;

			$encodings = array_flip($languages);
			unset($languages);
		}

		return $encodings;
	}

	// --------------------------------------------------------------------

	/**
	 * Get Installed Language Packs
	 *
	 * Returns an array of installed language packs
	 *
	 * @access	public
	 * @return	array
	 */
	function get_installed_language_packs()
	{
		static $languages;

		if ( ! isset($languages))
		{
			$this->load->helper('directory');

			$source_dir = APPPATH.'language/';

			if (($list = directory_map($source_dir, TRUE)) !== FALSE)
			{
				foreach ($list as $file)
				{
					if (is_dir($source_dir.$file) && $file[0] != '.')
					{
						$languages[$file] = ucfirst($file);
					}
				}

				ksort($languages);
			}
		}

		return $languages;
	}

	// --------------------------------------------------------------------

	/**
	 * Theme List
	 *
	 * Fetch installed CP Theme list
	 *
	 * @access	public
	 * @return	array
	 */
	function get_cp_theme_list()
	{
		$this->load->library('user_agent');

		static $themes;

		if ( ! isset($themes))
		{
			$this->load->helper('directory');

			if (($list = directory_map(PATH_CP_THEME, TRUE)) !== FALSE)
			{
				foreach ($list as $file)
				{
					if (is_dir(PATH_CP_THEME.$file) && $file[0] != '.')
					{
						if (substr($file, 0, 6) == 'mobile' && ! $this->agent->is_mobile())
						{
							continue;
						}
						else
						{
							$themes[$file] = ucfirst(str_replace('_', ' ', $file));

						}
					}
				}
				ksort($themes);
			}
		}

		return $themes;
	}

	// --------------------------------------------------------------------

	/**
	 * Template List
	 *
	 * Generates an array for the site template selection lists
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function get_template_list()
	{
		static $templates;

		if ( ! isset($templates))
		{
			$sql = "SELECT exp_template_groups.group_name, exp_templates.template_name
					FROM	exp_template_groups, exp_templates
					WHERE  exp_template_groups.group_id =  exp_templates.group_id
					AND exp_template_groups.site_id = '".$this->db->escape_str($this->config->item('site_id'))."' ";

			$sql .= " ORDER BY exp_template_groups.group_name, exp_templates.template_name";

			$query = $this->db->query($sql);

			foreach ($query->result_array() as $row)
			{
				$templates[$row['group_name'].'/'.$row['template_name']] = $row['group_name'].'/'.$row['template_name'];
			}
		}

		return $templates;
	}

	// --------------------------------------------------------------------

	/**
	 * Get HTML Buttons
	 *
	 * @access	public
	 * @param	int		member_id
	 * @param	bool	if the default button set should be loaded if user has no buttons
	 * @return	object
	 */
	function get_html_buttons($member_id = 0, $load_default_buttons = TRUE)
	{
		$this->db->from('html_buttons');
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('member_id', $member_id);
		$this->db->order_by('tag_order');
		$buttons = $this->db->get();

		// count the buttons, if there aren't any, return the default button set
		if ($buttons->num_rows() == 0 AND $load_default_buttons === TRUE)
		{
			$this->db->from('html_buttons');
			$this->db->where('site_id', $this->config->item('site_id'));
			$this->db->where('member_id', 0);
			$this->db->order_by('tag_order');
			$buttons = $this->db->get();
		}

		return $buttons;
	}

	// --------------------------------------------------------------------

	/**
	 * Delete HTML Button
	 *
	 * @access	public
	 * @return	NULL
	 */
	function delete_html_button($id)
	{
		$this->db->from('html_buttons');
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('id', $id);
		$this->db->delete();
	}

	// --------------------------------------------------------------------

	/**
	 * Update HTML Buttons
	 *
	 * @access	public
	 * @return	object
	 */
	function update_html_buttons($member_id, $buttons, $remove_buttons = TRUE)
	{
		if ($remove_buttons != FALSE)
		{
			// remove all buttons for this member
			$this->db->where('site_id', $this->config->item('site_id'));
			$this->db->where('member_id', $member_id);
			$this->db->from('html_buttons');
			$this->db->delete();
		}

		// now add in the new buttons
		foreach ($buttons as $button)
		{
			$this->db->insert('html_buttons', $button);
		}

	}

	// --------------------------------------------------------------------

	/**
	 * Unique Upload Name
	 *
	 * @access	public
	 * @return	boolean
	 */
	function unique_upload_name($name, $cur_name, $edit)
	{
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('name', $name);
		$this->db->from('upload_prefs');

		$count = $this->db->count_all_results();

		if (($edit == FALSE OR ($edit == TRUE && $name != $cur_name)) && $count > 0)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
}

/* End of file admin_model.php */
/* Location: ./system/expressionengine/models/admin_model.php */
