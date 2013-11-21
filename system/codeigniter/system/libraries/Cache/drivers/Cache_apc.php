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
 * ExpressionEngine APC Caching Class
 *
 * @package		ExpressionEngine
 * @subpackage	Libraries
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class CI_Cache_apc extends CI_Driver {

	/**
	 * Look for a value in the cache. If it exists, return the data
	 * if not, return FALSE
	 *
	 * @param	string	$key 		Key name
	 * @param	string	$namespace	Namespace name
	 * @return	mixed	value matching $id or FALSE on failure
	 */
	public function get($key, $namespace = '')
	{
		$success = FALSE;
		$data = apc_fetch($this->_namespaced_key($key, $namespace), $success);

		return ($success === TRUE && is_array($data))
			? unserialize($data[0]) : FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Save value to cache
	 *
	 * @param	string	$key		Key name
	 * @param	mixed	$data		Data to store
	 * @param	int		$ttl = 60	Cache TTL (in seconds)
	 * @param	string	$namespace	Namespace name
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function save($key, $data, $ttl = 60, $namespace = '')
	{
		$ttl = (int) $ttl;

		return apc_store(
			$this->_namespaced_key($key, $namespace),
			array(serialize($data), ee()->localize->now, $ttl),
			$ttl
		);
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete from cache
	 *
	 * @param	string	$key		Key name
	 * @param	string	$namespace	Namespace name
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function delete($key, $namespace = '')
	{
		return apc_delete($this->_namespaced_key($key, $namespace));
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete keys from cache in a specified namespace
	 *
	 * @param	string	$namespace	Namespace of group of cache keys to delete
	 * @return	bool
	 */
	public function clear_namepace($namespace)
	{
		$cached = new APCIterator('user', '/^'.preg_quote($this->_namespaced_key('', $namespace), '/').'/');

		foreach ($cached as $item)
		{
			apc_delete($item['key']);
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Clean the cache
	 *
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function clean()
	{
		return apc_clear_cache('user');
	}

	// ------------------------------------------------------------------------

	/**
	 * Cache Info
	 *
	 * @param	string	$type = 'user'	User/filehits
	 * @return	mixed	Array containing cache info on success OR FALSE on failure
	 */
	 public function cache_info($type = NULL)
	 {
		 return apc_cache_info($type);
	 }

	// ------------------------------------------------------------------------

	/**
	 * Get Cache Metadata
	 *
	 * @param	string	$key		Key to get cache metadata on
	 * @param	string	$namespace	Namespace name
	 * @return	mixed	Cache item metadata
	 */
	public function get_metadata($key, $namespace = '')
	{
		$success = FALSE;
		$stored = apc_fetch($this->_namespaced_key($key, $namespace), $success);

		if ($success === FALSE OR count($stored) !== 3)
		{
			return FALSE;
		}

		list($data, $time, $ttl) = $stored;

		return array(
			'expire'	=> $time + $ttl,
			'mtime'		=> $time,
			'data'		=> unserialize($data)
		);
	}

	// ------------------------------------------------------------------------

	/**
	 * Check to see if APC is available on this system, bail if it isn't.
	 *
	 * @return	bool
	 */
	public function is_supported()
	{
		if ( ! extension_loaded('apc') OR ! (bool) @ini_get('apc.enabled'))
		{
			log_message('debug', 'The APC PHP extension must be loaded to use APC Cache.');
			return FALSE;
		}

		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * If a namespace was specified, prefixes the key with it
	 *
	 * @param	string	$key		Key name
	 * @param	string	$namespace	Namespace name
	 * @return	string	Key prefixed with namespace
	 */
	protected function _namespaced_key($key, $namespace)
	{
		if ( ! empty($namespace))
		{
			$namespace .= ':';
		}

		return $this->unique_key($namespace.$key);
	}
}

/* End of file Cache_apc.php */
/* Location: ./system/libraries/Cache/drivers/Cache_apc.php */