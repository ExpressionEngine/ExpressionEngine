<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine XID Marker Interface
 *
 * Implementing this will enforce strict XID checks on all requests to
 * the class (if secure forms are enabled). Without it, the security model
 * is a little more lax until third parties have time to adapt.
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
interface Strict_XID {}

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Core Security Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Security extends CI_Security {

	// Flags for have_valid_xid()
	const CSRF_STRICT = 1;	// require single-use token for ajax requests
	const CSRF_EXEMPT = 2;	// opt-out of xid checks

	// Small note, if you feel the urge to add a constructor,
	// do not call ee() or get_instance(). The CI Security library
	// is sometimes instantiated before the controller is loaded.
	// i.e. when turning CI's csrf_protection on. Which you shouldn't
	// do in EE anywho. -pk

	// --------------------------------------------------------------------

	/**
	 * Check and Validate Form XID in Post
	 *
	 * Checks the post data for a form XID and then validates that XID.
	 * The XID -- regardless of whether or not it checks out as valid
	 * -- will then be deleted and a new one generated.  If the validation
	 * check fails, we'll return false and the caller should then show
	 * an appropriate error.
	 *
	 * @access public
	 * @return boolean FALSE if there is an invalid XID, TRUE if valid or no XID
	 */
	public function have_valid_xid($flags = self::CSRF_STRICT)
	{
		$is_valid = FALSE;
		$run_check = TRUE;

		// Check all of the exceptions
		if (ee()->config->item('secure_forms') == 'n')
		{
			$run_check = FALSE;
		}
		// exempt trumps all
		elseif ($flags & self::CSRF_EXEMPT)
		{
			$run_check = FALSE;
		}
		// for now, only check non-cp ajax in strict mode
		elseif (AJAX_REQUEST && REQ != 'CP' && ! ($flags & self::CSRF_STRICT))
		{
			$run_check = FALSE;
		}

		// Check the token if we must
		ee()->load->library('csrf');

		if ($run_check)
		{
			$is_valid = ee()->csrf->check();
		}
		else
		{
			$is_valid = TRUE;
		}

		// Retrieve the current token
		$csrf_token	= ee()->csrf->get_user_token();

		// Remove csrf information from the post array to make this feature
		// completely transparent to the developer.
		unset($_POST['XID']);
		unset($_POST['CSRF_TOKEN']);

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

	// --------------------------------------------------------------------

	/**
	 * Secure Forms Check
	 *
	 * @param 	string
	 * @return	bool
	 */
	public function secure_forms_check($xid)
	{
		ee()->load->library('logger');
		ee()->logger->deprecated('2.8');

		// third party code request handling code should not run before our
		// check, so if we get here, it's true
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Check for a Valid Security Hash
	 *
	 * This method does not mark the hash as used, you probably want
	 * the secure_forms_check() method instead.
	 *
	 * @param	string
	 * @return	bool
	 */
	public function check_xid($xid = REQUEST_XID)
	{
		ee()->load->library('logger');
		ee()->logger->deprecated('2.8');

		// third party code request handling code should not run before our
		// check, so if we get here, it's true
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Generate Security Hash
	 *
	 * @param	int   number of xids to create
	 * @param	bool  return as array even if $count = 1
	 * @return String XID generated
	 */
	public function generate_xid($count = 1, $array = FALSE)
	{
		ee()->load->library('logger');
		ee()->logger->deprecated('2.8', 'Use the CSRF_TOKEN constant.');

		$hashes = array_fill(0, $count, $count, CSRF_TOKEN);

		return ($count > 1 OR $array) ? $hashes : $hashes[0];
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Security Hash
	 *
	 * @param 	string
	 * @return	void
	 */
	public function delete_xid($xid = REQUEST_XID)
	{
		ee()->load->library('logger');
		ee()->logger->deprecated('2.8');

		return;
	}

	// --------------------------------------------------------------------

	/**
	 * Restore the XID if it was not used.
	 *
	 * This is used when we show an error to the user instead of using
	 * form validation. In some ways that means it's a stopgap measure,
	 * but a necessary one since this is the default behavior on the
	 * frontend.
	 *
	 * @param 	string
	 * @return	void
	 */
	public function restore_xid($xid = REQUEST_XID)
	{
		ee()->load->library('logger');
		ee()->logger->deprecated('2.8');

		return;
	}

	// --------------------------------------------------------------------

	/**
	 * Deletes out of date XIDs
	 */
	public function garbage_collect_xids()
	{
		ee()->load->library('logger');
		ee()->logger->deprecated('2.8');

		return;
	}

}
// END CLASS

/* End of file EE_Security.php */
/* Location: ./system/expressionengine/libraries/EE_Security.php */
