<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.8
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CSRF Cookie Backend
 *
 * This is a cookie backed csrf token store that is used for guest users.
 * Since all guests have a session id of 0, we actually lose security by
 * trying to match their session to the csrf token as we used to do. Instead
 * we set a cookie and compare this header value to the submitted form data.
 *
 * This class should not be used directly. Use the CSRF library.
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Csrf_cookie implements Csrf_storage_backend {

	const COOKIE_NAME = 'csrf_token';

	/**
	 * Get the expiration value
	 *
	 * @return Integer expiration in seconds
	 */
	public function get_expiration()
	{
		return 60 * 60 * 2; // 2 hours
	}

	/**
	 * Set the token cookie
	 *
	 * @param String $token New token value
	 * @return void
	 */
	public function store_token($token)
	{
		ee()->input->set_cookie(self::COOKIE_NAME, $token, $this->get_expiration());
	}

	/**
	 * Delete the token cookie
	 *
	 * @return void
	 */
	public function delete_token()
	{
		ee()->input->delete_cookie(self::COOKIE_NAME);
	}

	/**
	 * Fetch the current session token from the cookie.
	 *
	 * @return string Stored token
	 */
	public function fetch_token()
	{
		return ee()->input->cookie(self::COOKIE_NAME);
	}

	/**
	 * Refresh the current token
	 * @return void
	 */
	public function refresh_token()
	{
		if ($token = $this->fetch_token())
		{
			$this->store_token($token);
		}
	}
}

// EOF
