<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Site\Column;

use EllisLab\ExpressionEngine\Service\Model\Column\Serialized\Base64Native;
use EllisLab\ExpressionEngine\Service\Model\Column\CustomType;

/**
 * System Preferences Column
 */
class SystemPreferences extends CustomType {

	protected $is_site_on;
	protected $base_url;
	protected $base_path;
	protected $site_index;
	protected $site_url;
	protected $cp_url;
	protected $theme_folder_url;
	protected $theme_folder_path;
	protected $webmaster_email;
	protected $webmaster_name;
	protected $channel_nomenclature;
	protected $max_caches;
	protected $captcha_url;
	protected $captcha_path;
	protected $captcha_font;
	protected $captcha_rand;
	protected $captcha_require_members;
	protected $require_captcha;
	protected $enable_sql_caching;
	protected $force_query_string;
	protected $show_profiler;
	protected $include_seconds;
	protected $cookie_domain;
	protected $cookie_path;
	protected $cookie_httponly;
	protected $cookie_secure;
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
	protected $default_site_timezone;
	protected $date_format;
	protected $time_format;
	protected $mail_protocol;
	protected $email_newline;
	protected $smtp_server;
	protected $smtp_port;
	protected $smtp_username;
	protected $smtp_password;
	protected $email_smtp_crypto;
	protected $email_debug;
	protected $email_charset;
	protected $email_batchmode;
	protected $email_batch_size;
	protected $mail_format;
	protected $word_wrap;
	protected $email_console_timelock;
	protected $log_email_console_msgs;
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
	protected $rte_enabled;
	protected $rte_default_toolset_id;
	protected $forum_trigger;

	/**
	* Called when the column is fetched from db
	*/
	public function unserialize($db_data)
	{
		return Base64Native::unserialize($db_data);
	}

	/**
	* Called before the column is written to the db
	*/
	public function serialize($data)
	{
		return Base64Native::serialize($data);
	}

	/**
	 * Custom getter to parse path variables
	 */
	public function __get($name)
	{
		$value = parent::__get($name);

		$config = ee('Config')->getFile();
		$overrides = [];

		// If not explicitly overridden in config.php, use the config vars for
		// this particular site
		foreach (['base_path', 'base_url'] as $variable)
		{
			$overrides[$variable] = $config->get($variable) ?: $this->$variable;
		}

		$value = parse_config_variables($value, $overrides);

		return $value;
	}
}

// EOF
