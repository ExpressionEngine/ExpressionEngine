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
 * ExpressionEngine CSRF Database Backend
 *
 * This is a database backed csrf token store that is used for logged in users
 * and uses their session id to retrieve the stored csrf token.
 *
 * This class should not be used directly. Use the CSRF library.
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Csrf_database implements Csrf_storage_backend {

	const GC_PROBABILITY = 5;

	public function get_expiration()
	{
		return 0; // never - times out with session
	}

	/**
	 * Save the token in the db
	 *
	 * @param String $token New token value
	 * @return void
	 */
	public function store_token($token)
	{
		ee()->db->insert('security_hashes', array(
			'date'			=> ee()->localize->now,
			'hash'			=> $token,
			'session_id'	=> ee()->session->userdata('session_id')
		));
	}

	/**
	 * Delete the current session token.
	 *
	 * This also occassionally runs garbage collection for expired tokens when
	 * it is used.
	 *
	 * @return void
	 */
	public function delete_token()
	{
		ee()->db->where('session_id', ee()->session->userdata('session_id'))
			->delete('security_hashes');

		$this->collect_garbage();
	}

	/**
	 * Fetch the current token from the db.
	 *
	 * Will only return tokens that are within the token timeout.
	 *
	 * @return string Stored token
	 */
	public function fetch_token()
	{
		$result = ee()->db->where('session_id', ee()->session->userdata('session_id'))
			->get('security_hashes')
			->row();

		return empty($result) ? FALSE : $result->hash;
	}

	/**
	 * Refresh the token
	 *
	 * Not used in the Database CSRF provider
	 *
	 * @return bool TRUE
	 */
	public function refresh_token()
	{
		return TRUE;
	}

	/**
	 * Run garbage collection for old tokens on 5% of requests
	 *
	 * We no longer have anonymous data in this table, so the most that can
	 * build up is users that logged in in the last 2 days that did not come
	 * back or log out. Previously garbage collection only ran every 7 days
	 * and the table was so big that it could take down a site.
	 */
	private function collect_garbage()
	{
		srand(time());

		if ((rand() % 100) < self::GC_PROBABILITY)
		{
			$s = ee()->db->dbprefix('sessions');
			$sh = ee()->db->dbprefix('security_hashes');

			// active record cannot do this query
			// delete any csrf tokens whose associated session cannot be
			// found in the session table.
			ee()->db->query("DELETE sh FROM ${sh} sh LEFT JOIN ${s} s ON s.session_id = sh.session_id WHERE s.session_id IS NULL");
		}
	}

}

// EOF
