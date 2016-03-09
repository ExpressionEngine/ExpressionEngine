<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.2.4 or newer
 *
 * @package		CodeIgniter
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2016, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Database Cache Class
 *
 * @category	Database
 * @author		EllisLab Dev Team
 * @link		http://codeigniter.com/user_guide/database/
 */
class CI_DB_Cache {

	// Namespace cache items will be stored in
	private $_cache_namespace = 'db_cache';

	// --------------------------------------------------------------------

	/**
	 * Retrieve a cached query
	 *
	 * @param	string	$sql	SQL as key name to retrieve
	 * @return	mixed	Object from cache
	 */
	public function read($sql)
	{
		return ee()->cache->get('/'.$this->_cache_namespace.'/'.$this->_prefixed_key($sql));
	}

	// --------------------------------------------------------------------

	/**
	 * Write a query to a cache file
	 *
	 * @param	string	$sql	SQL to use as a key name
	 * @param	string	$object	Object to store in cache
	 * @return	bool	Success or failure
	 */
	function write($sql, $object)
	{
		return ee()->cache->save(
			'/'.$this->_cache_namespace.'/'.$this->_prefixed_key($sql),
			$object,
			0 // TTL
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete all existing cache files
	 *
	 * @return	bool	Success or failure
	 */
	public function delete_all()
	{
		return ee()->cache->clear_namespace($this->_cache_namespace);
	}

	// --------------------------------------------------------------------

	/**
	 * Takes a cache key and gets it ready for storage or retrieval, which
	 * includes prefixing the key name with the two URI segments of the
	 * request and MD5ing the key name to shorten it since it's likely a
	 * long SQL query
	 *
	 * @param	string	$key	Cache key name
	 * @return	string	Key prefixed with segments
	 */
	private function _prefixed_key($key)
	{
		$segment_one = (ee()->uri->segment(1) == FALSE)
			? 'default' : ee()->uri->segment(1);

		$segment_two = (ee()->uri->segment(2) == FALSE)
			? 'index' : ee()->uri->segment(2);

		return $segment_one.'+'.$segment_two.'+'.md5($key);
	}
}

// EOF
