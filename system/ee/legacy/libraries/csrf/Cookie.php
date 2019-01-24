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
 * ExpressionEngine CSRF Cookie Backend
 *
 * This is a cookie backed csrf token store that is used for guest users.
 * Since all guests have a session id of 0, we actually lose security by
 * trying to match their session to the csrf token as we used to do. Instead
 * we set a cookie and compare this header value to the submitted form data.
 *
 * This class should not be used directly. Use the CSRF library.
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
