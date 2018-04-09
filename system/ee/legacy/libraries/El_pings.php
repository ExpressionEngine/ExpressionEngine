<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
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
	public function is_registered($license = NULL)
	{
		$license = ($license) ?: ee('License')->getEELicense();
		if ( ! IS_CORE && ! $license->isValid())
		{
			return FALSE;
		}

		$cached = $this->cache->get('software_registration', Cache::GLOBAL_SCOPE);
		$exp_response = md5($license->getData('license_number').$license->getData('license_contact'));

		if ( ! $cached OR $cached != $exp_response)
		{
			// restrict the call to certain pages for performance and user experience
			$class = ee()->router->fetch_class();
			$method = ee()->router->fetch_method();

			if ($class == 'homepage' OR ($class == 'license' && $method == 'index'))
			{
				$payload = array(
					'contact'			=> $license->getData('license_contact'),
					'license_number'	=> (IS_CORE) ? 'CORE LICENSE' : $license->getData('license_number'),
					'domain'			=> ee()->config->item('site_url'),
					'server_name'		=> (isset($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : '',
					'ee_version'		=> ee()->config->item('app_version'),
					'php_version'		=> PHP_VERSION
				);

				if ( ! $registration = $this->_do_ping('https://ping.ellislab.com/register.php', $payload))
				{
					// save the failed request for a day only
					$this->cache->save('software_registration', $exp_response, 60*60*24, Cache::GLOBAL_SCOPE);
				}
				else
				{
					if ($registration != $exp_response)
					{
						// may have been a server error, save the failed request for a day
						$this->cache->save('software_registration', $exp_response, 60*60*24, Cache::GLOBAL_SCOPE);
					}
					else
					{
						// keep for two weeks
						$this->cache->save('software_registration', $registration, 60*60*24*7*2, Cache::GLOBAL_SCOPE);
					}
				}
			}
		}

		// hard fail only when no valid license is entered or it doesn't even match a valid pattern
		if ( ! $license->isValid())
		{
			return FALSE;
		}

		return TRUE;
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

		// Upgrading form Core to Pro?
		if (IS_CORE && $version_file['license_type'] == 'pro')
		{
			return $version_info;
		}

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
		$response = '';
		while ( ! feof($fp))
		{
			$line = fgets($fp, 4096);

			if ($headers === FALSE)
			{
				$response .= $line;
			}
			elseif (trim($line) == '')
			{
				$headers = FALSE;
			}
		}

		fclose($fp);

		return $response;
	}
}
// END CLASS

// EOF
