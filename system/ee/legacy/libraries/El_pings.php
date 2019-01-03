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
 * EllisLab Pings
 */

class El_pings {

	protected $ping_result;
	protected $cache;
	private $error;

	public function __construct()
	{
		// License and version pings should still be cached if caching is disabled
		if (ee()->config->item('cache_driver') == 'dummy')
		{
			$this->cache = ee()->cache->file;
		}
		else
		{
			$this->cache = ee()->cache;
		}
	}

	/**
	 * Is Registered?
	 *
	 * @return bool
	 **/
	public function shareAnalytics()
	{
		$cached = $this->cache->get('analytics_sent', Cache::GLOBAL_SCOPE);

		$server_name = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
		$exp_response = md5($server_name.PHP_VERSION);

		if ( ! $cached OR $cached != $exp_response)
		{
			$payload = array(
				'domain'           => ee()->config->item('site_url'),
				'server_name'      => (isset($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : '',
				'ee_version'       => APP_VER,
				'php_version'      => PHP_VERSION,
				'mysql_version'    => ee('Database')->getConnection()->getNative()->getAttribute(PDO::ATTR_SERVER_VERSION),
				'installed_addons' => json_encode($this->getInstalledAddons())
			);

			if ( ! $response = $this->_do_ping('https://ping.expressionengine.com/analytics/'.APP_VER, $payload))
			{
				// save the failed request for a day only
				$this->cache->save('analytics_sent', $response, 60*60*24, Cache::GLOBAL_SCOPE);
			}
			else
			{
				if ($response != $exp_response)
				{
					// may have been a server error, save the failed request for a day
					$this->cache->save('analytics_sent', $response, 60*60*24, Cache::GLOBAL_SCOPE);
				}
				else
				{
					// keep for two weeks
					$this->cache->save('analytics_sent', $response, 60*60*24*7*2, Cache::GLOBAL_SCOPE);
				}
			}
		}

		return TRUE;
	}

	/**
	 * Returns an array of installed add-on names
	 *
	 * @return array[string] Names of installed add-ons
	 */
	private function getInstalledAddons()
	{
		$installed_addons = ee('Addon')->installed();

		$third_party = array_filter($installed_addons, function($addon) {
			return $addon->getAuthor() != 'EllisLab';
		});

		return array_map(function($addon) {
			return $addon->getName();
		}, array_values($third_party));
	}

	/**
	 * EE Version Check function
	 *
	 * Checks the current version of ExpressionEngine available from EllisLab
	 *
	 * @param boolean $force_update Use the force, update regardless of cache
	 * @return array
	 */
	public function get_version_info($force_update = FALSE)
	{
		// Attempt to grab the local cached file
		$cached = $this->cache->get('current_version', Cache::GLOBAL_SCOPE);

		if ( ! $cached || $force_update)
		{
			try
			{
				$version_file = ee('Curl')->post(
					'https://update.expressionengine.com',
					[
						'action' => 'check_new_version',
						'license' => ee('License')->getEELicense()->getRawLicense(),
						'version' => ee()->config->item('app_version'),
					]
				)->exec();

				$version_file = json_decode($version_file, TRUE);
			}
			catch (\Exception $e)
			{
				// don't scare the user with whatever random error, but store it for debugging
				$version_file = $e->getMessage();
			}

			// Cache version information for a day
			$this->cache->save(
				'current_version',
				$version_file,
				60 * 60 * 24,
				Cache::GLOBAL_SCOPE
			);
		}
		else
		{
			$version_file = $cached;
		}

		if (isset($version_file['error']) && $version_file['error'] == TRUE)
		{
			if (isset($version_file['error_msg']))
			{
				$this->error = $version_file['error_msg'];
			}

			return FALSE;
		}

		// one final check for good measure
		if ( ! $this->_is_valid_version_file($version_file))
		{
			return FALSE;
		}

		return $version_file;
	}

	/**
	 * Get information about the available upgrade, or FALSE if no upgrade path available
	 *
	 * @param boolean $force_update Use the force, update regardless of cache
	 * @return array or FALSE if no upgrade path available
	 */
	public function getUpgradeInfo($force_update = FALSE)
	{
		$version_file = $this->get_version_info($force_update);

		$version_info = array(
			'version' => $version_file['latest_version'],
			'build' => $version_file['build_date'],
			'security' => $version_file['severity'] == 'high'
		);

		if (version_compare($version_info['version'], ee()->config->item('app_version')) < 1)
		{
			return FALSE;
		}

		return $version_info;
	}

	public function getError()
	{
		return $this->error;
	}

	/**
	 * Validate version file
	 * Prototype:
	 *  0 =>
	 *    array
	 *      0 => string '2.1.0' (length=5)
	 *      1 => string '20100805' (length=8)
	 *      2 => string 'normal' (length=6)
	 *
	 * @access	private
	 * @return	bool
	 */
	private function _is_valid_version_file($version_file)
	{
		if ( ! is_array($version_file) OR ! isset($version_file['latest_version']))
		{
			return FALSE;
		}

		foreach ($version_file as $val)
		{
			if ( ! is_string($val))
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Do the Ping
	 *
	 * @param string		$url		The URL to ping
	 * @param array			$payload	The POST payload, if any
	 * @return string|bool	The response from the web server or FALSE on failure to connect
	 **/
	private function _do_ping($url, $payload = null)
	{
		$target = parse_url($url);

		$fp = @fsockopen($target['host'], 80, $errno, $errstr, 3);

		if ( ! $fp)
		{
			return FALSE;
		}

		if ( ! empty($payload))
		{
			$postdata = http_build_query($payload);

			fputs($fp, "POST {$target['path']} HTTP/1.1\r\n");
			fputs($fp, "Host: {$target['host']}\r\n");
			fputs($fp, "User-Agent: EE/EllisLab PHP/\r\n");
			fputs($fp, "Content-Type: application/x-www-form-urlencoded\r\n");
			fputs($fp, "Content-Length: ".strlen($postdata)."\r\n");
			fputs($fp, "Connection: close\r\n\r\n");
			fputs($fp, "{$postdata}\r\n\r\n");
		}
		else
		{
			fputs($fp,"GET {$url} HTTP/1.1\r\n" );
			fputs($fp,"Host: {$target['host']}\r\n");
			fputs($fp,"User-Agent: EE/EllisLab PHP/\r\n");
			fputs($fp,"If-Modified-Since: Fri, 01 Jan 2004 12:24:04\r\n");
			fputs($fp,"Connection: close\r\n\r\n");
		}

		$headers = TRUE;
		$chunked = FALSE;
		$response = '';
		while ( ! feof($fp))
		{
			$line = fgets($fp, 4096);

			if ($headers === FALSE)
			{
				$response .= $line;
			}
			elseif (strstr($line, 'Transfer-Encoding: chunked') !== FALSE)
			{
				$chunked = TRUE;
			}
			elseif (trim($line) == '')
			{
				$headers = FALSE;
			}
		}

		fclose($fp);

		if ($chunked)
		{
			return $this->decodeChunked($response);
		}

		return $response;
	}

	/**
	 * Decode chunk-encoded response, thanks https://stackoverflow.com/questions/10793017
	 *
	 * @param string $response Chunk-encoded response
	 * @return string De-chunked response
	 **/
	private function decodeChunked($str) {
		for ($res = ''; !empty($str); $str = trim($str)) {
			$pos = strpos($str, "\r\n");
			$len = hexdec(substr($str, 0, $pos));
			$res.= substr($str, $pos + 2, $len);
			$str = substr($str, $pos + 2 + $len);
		}
		return $res;
	}
}
// END CLASS

// EOF
