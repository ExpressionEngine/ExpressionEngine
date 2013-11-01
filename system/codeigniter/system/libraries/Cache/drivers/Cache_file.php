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
 * ExpressionEngine File Caching Class
 *
 * @package		ExpressionEngine
 * @subpackage	Libraries
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class CI_Cache_file extends CI_Driver {

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
		$CI =& get_instance();
		$CI->load->helper('file');
		$path = $CI->config->item('cache_path');
		$this->_cache_path = ($path === '') ? APPPATH.'cache/' : $path;
	}

	// ------------------------------------------------------------------------

	/**
	 * Fetch from cache
	 *
	 * @param	mixed	unique key id
	 * @param	string	$namespace	Namespace name
	 * @return	mixed	data on success/false on failure
	 */
	public function get($id, $namespace = '')
	{
		$id = $this->_namespaced_key($id, $namespace);

		if ( ! file_exists($this->_cache_path.$id))
		{
			return FALSE;
		}

		$data = unserialize(file_get_contents($this->_cache_path.$id));

		if ($data['ttl'] > 0 && ee()->localize->now > $data['time'] + $data['ttl'])
		{
			unlink($this->_cache_path.$id);
			return FALSE;
		}

		return $data['data'];
	}

	// ------------------------------------------------------------------------

	/**
	 * Save into cache
	 *
	 * @param	string	unique key
	 * @param	mixed	data to store
	 * @param	int	length of time (in seconds) the cache is valid
	 *				- Default is 60 seconds
	 * @param	string	$namespace	Namespace name
	 * @return	bool	true on success/false on failure
	 */
	public function save($id, $data, $ttl = 60, $namespace = '')
	{
		$contents = array(
			'time'		=> ee()->localize->now,
			'ttl'		=> $ttl,
			'data'		=> $data
		);

		$path = $this->_cache_path.$this->_namespaced_key('', $namespace);

		// Create namespace directory if it doesn't exist
		if ( ! file_exists($path) OR ! is_dir($path))
		{
			mkdir($path, DIR_WRITE_MODE);

			// Write an index.html file to ensure no directory indexing
			write_index_html($path);
		}

		$id = $this->_namespaced_key($id, $namespace);

		if (write_file($this->_cache_path.$id, serialize($contents)))
		{
			@chmod($this->_cache_path.$id, 0660);
			return TRUE;
		}

		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete from Cache
	 *
	 * @param	mixed	unique identifier of item in cache
	 * @param	string	$namespace	Namespace name
	 * @return	bool	true on success/false on failure
	 */
	public function delete($id, $namespace = '')
	{
		$id = $this->_namespaced_key($id, $namespace);

		return file_exists($this->_cache_path.$id) ? unlink($this->_cache_path.$id) : FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete keys from cache with a specified prefix
	 *
	 * @param	mixed	Prefix of group of cache files to delete
	 * @return	bool
	 */
	public function clear_namepace($namespace)
	{
		$path = $this->_cache_path.$this->_namespaced_key('', $namespace);

		if (delete_files($path))
		{
			// Write an index.html file to ensure no directory indexing
			write_index_html($path);
		}

		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Clean the Cache
	 *
	 * @return	bool	false on failure/true on success
	 */
	public function clean()
	{
		// Delete all files in cache directory, excluding .htaccess and index.html
		delete_files($this->_cache_path, TRUE, 0, array('.htaccess', 'index.html'));

		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Cache Info
	 *
	 * Not supported by file-based caching
	 *
	 * @param	string	user/filehits
	 * @return	mixed	FALSE
	 */
	public function cache_info($type = NULL)
	{
		return get_dir_file_info($this->_cache_path);
	}

	// ------------------------------------------------------------------------

	/**
	 * Get Cache Metadata
	 *
	 * @param	mixed	key to get cache metadata on
	 * @param	string	$namespace	Namespace name
	 * @return	mixed	FALSE on failure, array on success.
	 */
	public function get_metadata($id, $namespace = '')
	{
		$id = $this->_namespaced_key($id, $namespace);

		if ( ! file_exists($this->_cache_path.$id))
		{
			return FALSE;
		}

		$data = unserialize(file_get_contents($this->_cache_path.$id));

		if (is_array($data))
		{
			$mtime = filemtime($this->_cache_path.$id);

			if ( ! isset($data['ttl']))
			{
				return FALSE;
			}

			return array(
				'expire' => $mtime + $data['ttl'],
				'mtime'	 => $mtime
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
	 * @param	string	$namespace	Namespace name
	 * @return	string	Key prefixed with namespace
	 */
	protected function _namespaced_key($key, $namespace)
	{
		if ( ! empty($namespace))
		{
			$namespace .= '_cache/';
		}

		return $namespace.$key;
	}
}

/* End of file Cache_file.php */
/* Location: ./system/libraries/Cache/drivers/Cache_file.php */