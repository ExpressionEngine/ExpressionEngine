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

	private $_xid_ttl = 7200;
	private $_checked_xids = array();

	// Small note, if you feel the urge to add a constructor,
	// do not call get_instance(). The CI Security library
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
		$hash = '';
		$request_xid = '';

		if (ee()->config->item('secure_forms') == 'y')
		{
			if (count($_POST) > 0)
			{
				$run_check = TRUE;
				$request_xid = FALSE;

				// exempt trumps all
				if ($flags & self::CSRF_EXEMPT)
				{
					$run_check = FALSE;
				}
				// only check ajax in strict mode
				elseif (AJAX_REQUEST && ! ($flags & self::CSRF_STRICT))
				{
					$run_check = FALSE;
				}

				// A class is only passed when the check is optional (currently, ajax actions)

				if (isset($class) && ! ($class instanceOf Strict_XID))
				{
					$run_check = FALSE;
				}

				// ajax requests use a header
				if (AJAX_REQUEST)
				{
					$request_xid = ee()->input->server('HTTP_X_EEXID');
				}

				// the ajax header trumps post, but for backwards compat we will
				// fall back to post. Also here for non-ajax, obviously.
				if ( ! $request_xid)
				{
					$request_xid = ee()->input->post('XID');
				}

				if ($run_check)
				{
					if ( ! $request_xid OR ! $this->secure_forms_check($request_xid))
					{
						return FALSE;
					}
				}

				// @deprecated since 2.7
				if (REQ == 'CP')
				{
					unset($_POST['XID']);
				}
			}

			$hash = $this->generate_xid();
		}

		define('REQUEST_XID', $request_xid);
		define('XID_SECURE_HASH', $hash);

		if (AJAX_REQUEST && count($_POST))
		{
			header('X-EEXID: '.XID_SECURE_HASH);
		}

		return TRUE;
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
		if (ee()->config->item('secure_forms') != 'y' OR $xid === FALSE)
		{
			return TRUE;
		}

		if ( ! array_key_exists($xid, $this->_checked_xids))
		{
			ee()->db->update(
				'security_hashes',
				array('used' => 1),
				array(
					'used'			=> 0,
					'hash' 			=> $xid,
					'session_id' 	=> (string) ee()->session->userdata('session_id'),
					'date >' 		=> ee()->localize->now - $this->_xid_ttl
				)
			);

			$this->_checked_xids[$xid] = (ee()->db->affected_rows() != 0);
		}

		return $this->_checked_xids[$xid];
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
		if (ee()->config->item('secure_forms') != 'y' OR $xid === FALSE)
		{
			return TRUE;
		}

		$total = ee()->db->where(array(
				'used'			=> 0,
				'hash' 			=> $xid,
				'session_id' 	=> ee()->session->userdata('session_id'),
				'date >' 		=> ee()->localize->now - $this->_xid_ttl
			))
			->from('security_hashes')
			->count_all_results();

		if ($total === 0)
		{
			return FALSE;
		}

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
		$hashes = array();
		$inserts = array();

		for ($i = 0; $i < $count; $i++)
		{
			$hash = ee()->functions->random('encrypt');
			$inserts[] = array(
				'date' 			=> ee()->localize->now,
				'session_id'	=> ee()->session->userdata('session_id'),
				'hash' 			=> $hash
			);
			$hashes[] = $hash;
		}

		ee()->db->insert_batch('security_hashes', $inserts);

		return (count($hashes) > 1 OR $array) ? $hashes : $hashes[0];
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
		if (ee()->config->item('secure_forms') != 'y' OR $xid === FALSE)
		{
			return;
		}

		ee()->db->where('hash', $xid)
			->or_where('date <', ee()->localize->now - $this->_xid_ttl)
			->delete('security_hashes');
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
		if (ee()->config->item('secure_forms') != 'y' OR $xid === FALSE)
		{
			return;
		}

		ee()->db->update(
			'security_hashes',
			array('used' => 0),
			array('hash' => $xid)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Deletes out of date XIDs
	 */
	public function garbage_collect_xids()
	{
		ee()->db->delete(
			'security_hashes',
			array(
				'date <' => (ee()->localize->now - $this->_xid_ttl)
			)
		);
	}

}
// END CLASS

/* End of file EE_Security.php */
/* Location: ./system/expressionengine/libraries/EE_Security.php */
