<?php

namespace EllisLab\ExpressionEngine\Model\Site\Column;

use EllisLab\ExpressionEngine\Service\Model\Column\Base64SerializedComposite;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine System Preferences
 *
 * @package		ExpressionEngine
 * @subpackage	Site\Preferences
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class SystemPreferences extends Base64SerializedComposite {

	/**
	 * The location of the index.php file for this site.
	 *
	 * @type	string
	 */
	protected $site_index;

	/**
	 * The base URL for this site.
	 *
	 * @type	string
	 */
	protected $site_url;

	/**
	 * The base URL of the theme folder for this site.
	 *
	 * @type	string
	 */
	protected $theme_folder_url;

	/**
	 * The email address of this site's webmaster.
	 *
	 * @type	string
	 */
	protected $webmaster_email;

	/**
	 * The name of this site's webmaster.
	 *
	 * @type	string
	 */
	protected $webmaster_name;
	protected $channel_nomenclature;
	protected $max_caches;
	protected $captcha_url;
	protected $captcha_path;
	protected $captcha_font;
	protected $captcha_rand;
	protected $captcha_require_members;
	protected $enable_sql_caching;
	protected $force_query_string;
	protected $show_profiler;
	protected $template_debugging;
	protected $include_seconds;
	protected $cookie_domain;
	protected $cookie_path;
	protected $website_session_type;
	protected $cp_session_type;
	protected $allow_username_change;
	protected $allow_multi_logins;
	protected $password_lockout;
	protected $password_lockout_interval;
	protected $require_ip_for_login;
	protected $require_ip_for_posting;
	protected $require_secure_passwords;
	protected $allow_dictionary_pw;
	protected $name_of_dictionary_file;
	protected $xss_clean_uploads;
	protected $redirect_method;
	protected $deft_lang;
	protected $xml_lang;
	protected $send_headers;
	protected $gzip_output;
	protected $log_referrers;
	protected $max_referrers;
	protected $date_format;
	protected $time_format;
	protected $server_offset;
	protected $default_site_timezone;
	protected $mail_protocol;
	protected $smtp_server;
	protected $smtp_username;
	protected $smtp_password;
	protected $email_debug;
	protected $email_charset;
	protected $email_batchmode;
	protected $email_batch_size;
	protected $mail_format;
	protected $word_wrap;
	protected $email_console_timelock;
	protected $log_email_console_msgs;
	protected $cp_theme;
	protected $log_search_terms;
	protected $deny_duplicate_data;
	protected $redirect_submitted_links;
	protected $enable_censoring;
	protected $censored_words;
	protected $censor_replacement;
	protected $banned_ips;
	protected $banned_emails;
	protected $banned_usernames;
	protected $banned_screen_names;
	protected $ban_action;
	protected $ban_message;
	protected $ban_destination;
	protected $enable_emoticons;
	protected $emoticon_url;
	protected $recount_batch_total;
	protected $new_version_check;
	protected $enable_throttling;
	protected $banish_masked_ips;
	protected $max_page_loads;
	protected $time_interval;
	protected $lockout_time;
	protected $banishment_type;
	protected $banishment_url;
	protected $banishment_message;
	protected $enable_search_log;
	protected $max_logged_searches;
	protected $theme_folder_path;
	protected $is_site_on;
	protected $rte_enabled;
	protected $rte_default_toolset_id;

}
