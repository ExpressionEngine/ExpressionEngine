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
 * ExpressionEngine File Caching Class
 *
 * @package		ExpressionEngine
 * @subpackage	Libraries
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class EE_Cache_file extends CI_Driver {

	/**
	 * Directory in which to save cache files
	 *
	 * @var string
	 */
	protected $_cache_path;

	/**
	 * Initialize file-based cache
	 *
	 * @return	void
	 */
	public function __construct()
	{
		ee()->load->helper('file');
		$this->_cache_path = PATH_CACHE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Look for a value in the cache. If it exists, return the data
	 * if not, return FALSE
	 *
	 * @param	string	$key 	Key name
	 * @param	const	$scope	Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE
	 *		 for local or global scoping of the cache item
	 * @return	mixed	Value matching $key or FALSE on failure
	 */
	public function get($key, $scope = Cache::LOCAL_SCOPE)
	{
		$key = $this->_namespaced_key($key, $scope);

		if ( ! file_exists($this->_cache_path.$key))
		{
			return FALSE;
		}

		$data = unserialize(file_get_contents($this->_cache_path.$key));

		if ($data['ttl'] > 0 && ee()->localize->now > $data['time'] + $data['ttl'])
		{
			unlink($this->_cache_path.$key);
			return FALSE;
		}

		return $data['data'];
	}

	// ------------------------------------------------------------------------

	/**
	 * Save value to cache
	 *
	 * @param	string	$key		Key name
	 * @param	mixed	$data		Data to store
	 * @param	int		$ttl = 60	Cache TTL (in seconds)
	 * @param	const	$scope		Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE
	 *		 for local or global scoping of the cache item
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function save($key, $data, $ttl = 60, $scope = Cache::LOCAL_SCOPE)
	{
		$contents = array(
			'time'		=> ee()->localize->now,
			'ttl'		=> $ttl,
			'data'		=> $data
		);

		$key = $this->_namespaced_key($key, $scope);

		// Build file path to this key
		$path = $this->_cache_path . $key;

		// Remove the cache item name to get the path by looking backwards
		// for the directory separator
		$path = substr($path, 0, strrpos($path, DIRECTORY_SEPARATOR) + 1);

		// Create namespace directory if it doesn't exist
		if ( ! file_exists($path) OR ! is_dir($path))
		{
			@mkdir($path, DIR_WRITE_MODE, TRUE);

			// Grab the error if there was one
			$error = error_get_last();

			// If we had trouble creating the directory, it's likely due to a
			// concurrent process already having created it, so we'll check
			// to see if that's the case and if not, something else went wrong
			// and we'll show an error
			if ( ! is_dir($path) OR ! is_really_writable($path))
			{
				trigger_error($error['message'], E_USER_WARNING);
			}
			else
			{
				// Write an index.html file to ensure no directory indexing
				write_index_html($path);
			}
		}

		if (write_file($this->_cache_path.$key, serialize($contents)))
		{
			@chmod($this->_cache_path.$key, 0660);
			return TRUE;
		}

		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete from cache
	 *
	 * To clear a particular namespace, pass in the namespace with a trailing
	 * slash like so:
	 *
	 * ee()->cache->delete('/namespace_name/');
	 *
	 * @param	string	$key	Key name
	 * @param	const	$scope	Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE
	 *		 for local or global scoping of the cache item
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function delete($key, $scope = Cache::LOCAL_SCOPE)
	{
		$path = $this->_cache_path.$this->_namespaced_key($key, $scope);

		// If we are deleting contents of a namespace
		if (strrpos($key, Cache::NAMESPACE_SEPARATOR, strlen($key) - 1) !== FALSE)
		{
			$path .= DIRECTORY_SEPARATOR;

			if (delete_files($path, TRUE))
			{
				// Try to remove the namespace directory; it may not be
				// removeable on some high traffic sites where the cache fills
				// back up quickly
				@rmdir($path);

				return TRUE;
			}

			return FALSE;
		}

		return file_exists($path) ? unlink($path) : FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Clean the cache
	 *
	 * @param	const	$scope	Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE
	 *		 for local or global scoping of the cache item
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function clean($scope = Cache::LOCAL_SCOPE)
	{
		$path = $this->_cache_path.$this->_namespaced_key('', $scope);

		// Delete all files in cache directory, excluding .htaccess and index.html
		$result = delete_files(
			$path,
			TRUE,
			0,
			// Only skip htaccess for the global scope
			($scope == Cache::GLOBAL_SCOPE) ? array('.htaccess') : array()
		);

		// Replace index.html
		write_index_html($path);

		return $result;
	}

	// ------------------------------------------------------------------------

	/**
	 * Cache Info
	 *
	 * @return	mixed	array containing cache info on success OR FALSE on failure
	 */
	public function cache_info()
	{
		return get_dir_file_info($this->_cache_path, FALSE);
	}

	// ------------------------------------------------------------------------

	/**
	 * Get Cache Metadata
	 *
	 * @param	string	$key	Key to get cache metadata on
	 * @param	const	$scope	Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE
	 *		 for local or global scoping of the cache item
	 * @return	mixed	cache item metadata
	 */
	public function get_metadata($key, $scope = Cache::LOCAL_SCOPE)
	{
		$key = $this->_namespaced_key($key, $scope);

		if ( ! file_exists($this->_cache_path.$key))
		{
			return FALSE;
		}

		$data = unserialize(file_get_contents($this->_cache_path.$key));

		if (is_array($data))
		{
			$mtime = filemtime($this->_cache_path.$key);

			if ( ! isset($data['ttl']))
			{
				return FALSE;
			}

			return array(
				'expire'	=> $mtime + $data['ttl'],
				'mtime'		=> $mtime,
				'data'		=> $data['data']
			);
		}

		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Is supported
	 *
	 * In the file driver, check to see that the cache directory is indeed writable
	 *
	 * @return	bool
	 */
	public function is_supported()
	{
		return is_really_writable($this->_cache_path);
	}

	// ------------------------------------------------------------------------

	/**
	 * If a namespace was specified, prefixes the key with it
	 *
	 * For the file driver, namespaces will be actual folders
	 *
	 * @param	string	$key	Key name
	 * @param	const	$scope	Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE
	 *		 for local or global scoping of the cache item
	 * @return	string	Key prefixed with namespace
	 */
	protected function _namespaced_key($key, $scope = Cache::LOCAL_SCOPE)
	{
		// Make sure the key doesn't begin or end with a namespace separator or
		// directory separator to force the last segment of the key to be the
		// file name and so we can prefix a directory reliably
		$key = trim($key, Cache::NAMESPACE_SEPARATOR.DIRECTORY_SEPARATOR);

		// Replace all namespace separators with the system's directory separator
		$key = str_replace(Cache::NAMESPACE_SEPARATOR, DIRECTORY_SEPARATOR, $key);

		// For locally-cached items, separate by site name
		if ($scope == Cache::LOCAL_SCOPE)
		{
			$key = ee()->config->item('site_short_name') . DIRECTORY_SEPARATOR . $key;
		}

		return $key;
	}
}

// EOF
