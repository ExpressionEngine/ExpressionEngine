<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
 */
class Csrf_database implements Csrf_storage_backend {

	const GC_PROBABILITY = 5;

	public function get_expiration()
	{
		return 60 * 60 * 24; // 2 days
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
		$result = ee()->db->where(array(
				'session_id' 	=> ee()->session->userdata('session_id'),
				'date >' 		=> ee()->localize->now - $this->get_expiration()
			))
			->get('security_hashes')
			->row();

		return empty($result) ? FALSE : $result->hash;
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
			ee()->db->where('session_id', ee()->session->userdata('session_id'))
				->or_where('date <', ee()->localize->now - $this->get_expiration())
				->delete('security_hashes');
		}
	}

}