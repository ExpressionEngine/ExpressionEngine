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
 * ExpressionEngine Pings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Pings {

	protected $ping_result;

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Is Registered?
	 *
	 * @return bool
	 **/
	public function is_registered()
	{
		if ( ! IS_CORE && ee()->config->item('license_number') == '')
		{
			return FALSE;
		}

		$cached = ee()->cache->get('software_registration', Cache::GLOBAL_SCOPE);

		if ( ! $cached OR $cached != ee()->config->item('license_number'))
		{
			if ( ! $registration = $this->_do_ping('http://versions.ellislab.com/test.txt'))
			{
				// hard fail only when no valid license is entered
				if (ee()->config->item('license_number') == '')
				{
					return FALSE;
				}

				return TRUE;
			}
			else
			{
				ee()->cache->save('software_registration', $registration, 60*60*24*7, Cache::GLOBAL_SCOPE);
			}
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	public function get_version_info()
	{

	}

	// --------------------------------------------------------------------

	private function _do_ping($url, $payload = null)
	{
		$target = parse_url($url);

		$fp = @fsockopen($target['host'], 80, $errno, $errstr, 3);

		if ( ! $fp)
		{
			return FALSE;
		}

		fputs($fp,"GET {$url} HTTP/1.1\r\n" );
		fputs($fp,"Host: {$target['host']}\r\n");
		fputs($fp,"User-Agent: EE/EllisLab PHP/\r\n");
		fputs($fp,"If-Modified-Since: Fri, 01 Jan 2004 12:24:04\r\n");
		fputs($fp,"Connection: close\r\n\r\n");

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

	// --------------------------------------------------------------------

}
// END CLASS

/* End of file Pings.php */
/* Location: ./system/expressionengine/libraries/Pings.php */
