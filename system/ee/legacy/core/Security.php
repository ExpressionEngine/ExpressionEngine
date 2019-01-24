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
 * ExpressionEngine XID Marker Interface
 *
 * Implementing this will enforce strict XID checks on all requests to
 * the class (if secure forms are enabled). Without it, the security model
 * is a little more lax until third parties have time to adapt.
 */
interface Strict_XID {}

/**
 * Core Security
 */
class EE_Security {

	// Flags for have_valid_xid()
	const CSRF_STRICT = 1;	// require single-use token for ajax requests
	const CSRF_EXEMPT = 2;	// opt-out of xid checks

	/**
	 * XSS Clean
	 */
	public function xss_clean($str, $is_image = FALSE)
	{
		ee()->load->library('logger');
		ee()->logger->deprecated('3.0', "ee('Security/XSS')->clean()");

		return ee('Security/XSS')->clean($str, $is_image);
	}

	/**
	 * Filename Security
	 *
	 * @param	string
	 * @return	string
	 */
	public function sanitize_filename($str, $relative_path = FALSE)
	{
		$bad = array(
			"../",
			"<!--",
			"-->",
			"<",
			">",
			"'",
			'"',
			'&',
			'$',
			'#',
			'{',
			'}',
			'[',
			']',
			'=',
			':',
			';',
			'?',
			"%20",
			"%22",
			"%3c",		// <
			"%253c",	// <
			"%3e",		// >
			"%0e",		// >
			"%28",		// (
			"%29",		// )
			"%2528",	// (
			"%26",		// &
			"%24",		// $
			"%3f",		// ?
			"%3b",		// ;
			"%3d"		// =
		);

		if ( ! $relative_path)
		{
			$bad[] = './';
			$bad[] = '/';
		}

		$str = remove_invisible_characters($str, FALSE);
		$str = preg_replace('/\.+[\/\\\]/', '', $str);
		return stripslashes(str_replace($bad, '', $str));
	}

	/**
	 * Check and Validate Form CSRF tokens
	 *
	 * Checks any POST and PUT data for a valid csrf tokens. The main
	 * processing happens in the csrf library which differentiates between
	 * logged in and logged out users.
	 *
	 * @access public
	 * @return boolean FALSE if there is an invalid XID, TRUE if valid or no XID
	 */
	public function have_valid_xid($flags = self::CSRF_STRICT)
	{
		$is_valid = FALSE;

		// Check the token if we must
		ee()->load->library('csrf');

		if (($flags & self::CSRF_EXEMPT) || // exempt trumps all
			(AJAX_REQUEST && REQ != 'CP' && ! ($flags & self::CSRF_STRICT)) || // non-cp ajax only gets checked for strict mode
			bool_config_item('disable_csrf_protection')) // disabled
		{
			$is_valid = TRUE;
		}
		// otherwise, run the check
		else
		{
			$is_valid = ee()->csrf->check();
		}

		// Retrieve the current token
		$csrf_token	= ee()->csrf->get_user_token();

		// Set the constant and the legacy constants. Le sigh.
		define('CSRF_TOKEN', $csrf_token);
		define('REQUEST_XID', $csrf_token);
		define('XID_SECURE_HASH', $csrf_token);

		// Send the header and legacy header for ajax requests
		if (AJAX_REQUEST && ee()->input->server('REQUEST_METHOD') == 'POST')
		{
			header('X-CSRF-TOKEN: '.CSRF_TOKEN);
			header('X-EEXID: '.CSRF_TOKEN);
		}

		return $is_valid;
	}
}
// END CLASS

// EOF
