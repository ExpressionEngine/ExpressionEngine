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
 * Installer Survey Library
 */
class Survey {

	private $_survey_url = 'survey-collector.ellislab.com';

	function __construct()
	{
		// nothing to see here
	}

	/**
	 * Fetch Anonymous Server Data
	 *
	 * @access	public
	 * @return	array
	 */
	function fetch_anon_server_data()
	{
		ee()->db->select('site_system_preferences');
		$query = ee()->db->get_where('sites', array('site_id' => 1));

		$site_url = '';
		$path_info_support = 'n';

		if ($query->num_rows() > 0)
		{
			$prefs = unserialize(base64_decode($query->row('site_system_preferences')));
			$site_url = $prefs['site_url'];
			$path_info_support = ($prefs['force_query_string'] == 'n') ? 'y' : 'n';
		}

		// Get a list of add-ons in their third_party folder
		ee()->load->helper('directory');

		$mysql_info = mysqli_get_server_info(ee()->db->conn_id);

		return array(
			'anon_id'			=> md5($site_url),
			'os'				=> preg_replace("/.*?\((.*?)\).*/", '\\1', $_SERVER['SERVER_SOFTWARE']),
			'server_software'	=> preg_replace("/(.*?)\(.*/", '\\1', $_SERVER['SERVER_SOFTWARE']),
			'php_version'		=> phpversion(),
			'php_extensions'	=> json_encode(get_loaded_extensions()),
			'mysql_version'		=> preg_replace("/(.*?)\-.*/", "\\1", $mysql_info),
			'path_info_support'	=> $path_info_support,
			'addons'			=> json_encode(directory_map(PATH_THIRD, 1)),
			'forums'			=> (ee()->config->item('forum_is_installed') == "y") ? 'y' : 'n',
			'msm'				=> (ee()->config->item('multiple_sites_enabled') == "y") ? 'y' : 'n',
		);
	}

	/**
	 * Send Survey
	 *
	 * @access	public
	 * @return	void
	 */
	function send_survey($version)
	{
		$data = array();

		if (isset($_POST['send_anonymous_server_data']) && $_POST['send_anonymous_server_data'] == 'y')
		{
			$data = $this->fetch_anon_server_data();
		}
		else
		{
			$data['anon_id'] = md5(serialize($_POST));
		}

		unset($_POST['participate_in_survey']);
		unset($_POST['send_anonymous_server_data']);
		unset($_POST['submit']);

		foreach ($_POST as $key => $val)
		{
			$data[$key] = $val;
		}

		$data['ee_version'] = $version;

		$postdata = '';

		foreach ($data as $key => $val)
		{
			$postdata .= "&{$key}=".urlencode(stripslashes($val));
		}

		if ( function_exists('curl_init'))
		{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($ch, CURLOPT_URL, "http://{$this->_survey_url}/");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);

			// silently, please
			ob_start();
			curl_exec($ch);
			curl_close($ch);
			ob_end_clean();
		}
		else
		{
			$fp = @fsockopen($this->_survey_url, 80, $error_num, $error_str, 5);

			if (is_resource($fp))
			{
				fputs($fp, "POST / HTTP/1.0\r\n");
				fputs($fp, "Host: {$this->_survey_url}\r\n");
				fputs($fp, "Content-Length: ".strlen($postdata)."\r\n");
				fputs($fp, "Content-Type: application/x-www-form-urlencoded\r\n");
				fputs($fp, "Connection: close\r\n\r\n");
				fputs($fp, $postdata . "\r\n\r\n");
			}

			@fclose($fp);
		}
	}

}
// END CLASS

// EOF
