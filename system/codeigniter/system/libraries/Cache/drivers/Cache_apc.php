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
	 * Get
	 *
	 * Look for a value in the cache. If it exists, return the data
	 * if not, return FALSE
	 *
	 * @param	string	$key	Key name
	 * @param	string	$namespace	Namespace name
	 * @return	mixed	value that is stored/FALSE on failure
	 */
	public function get($id, $namespace = '')
	{
		$success = FALSE;
		$data = apc_fetch($this->_namespaced_key($id, $namespace), $success);

		return ($success === TRUE && is_array($data))
			? unserialize($data[0]) : FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Cache Save
	 *
	 * @param	string	Unique Key
	 * @param	mixed	Data to store
	 * @param	int	Length of time (in seconds) to cache the data
	 * @param	string	$namespace	Namespace name
	 *
	 * @return	bool	true on success/false on failure
	 */
	public function save($id, $data, $ttl = 60, $namespace = '')
	{
		$ttl = (int) $ttl;
		return apc_store(
			$this->_namespaced_key($id, $namespace),
			array(serialize($data), time(), $ttl),
			$ttl
		);
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete from Cache
	 *
	 * @param	mixed	unique identifier of the item in the cache
	 * @param	string	$namespace	Namespace name
	 * @return	bool	true on success/false on failure
	 */
	public function delete($id, $namespace = '')
	{
		return apc_delete($this->_namespaced_key($id, $namespace));
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete keys from cache with a specified prefix
	 *
	 * @param	string	Namepace of group of cache keys to delete
	 * @return	bool
	 */
	public function delete_namespace($namespace)
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
	 * @return	bool	false on failure/true on success
	 */
	public function clean()
	{
		return apc_clear_cache('user');
	}

	// ------------------------------------------------------------------------

	/**
	 * Cache Info
	 *
	 * @param	string	user/filehits
	 * @return	mixed	array on success, false on failure
	 */
	 public function cache_info($type = NULL)
	 {
		 return apc_cache_info($type);
	 }

	// ------------------------------------------------------------------------

	/**
	 * Get Cache Metadata
	 *
	 * @param	mixed	key to get cache metadata on
	 * @param	string	$namespace	Namespace name
	 * @return	mixed	array on success/false on failure
	 */
	public function get_metadata($id, $namespace = '')
	{
		$success = FALSE;
		$stored = apc_fetch($this->_namespaced_key($id, $namespace), $success);

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
	 * is_supported()
	 *
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
	 * @param	string	$key	Key name
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