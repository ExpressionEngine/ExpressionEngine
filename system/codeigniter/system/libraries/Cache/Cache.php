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
 * ExpressionEngine Caching Class
 *
 * @package		ExpressionEngine
 * @subpackage	Libraries
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class CI_Cache extends CI_Driver_Library {

	/**
	 * These constants specify the scope in which the cache item should
	 * exist; either it should exist in and be accessible only by the
	 * current site, or it should be globally accessible by the EE
	 * installation across MSM sites
	 */
	const CACHE_LOCAL = 1;	// Scoped to the current site
	const CACHE_GLOBAL = 2;	// Scoped to global EE install

	/**
	 * Valid cache drivers
	 *
	 * @var array
	 */
	protected $valid_drivers = array(
		'apc',
		'dummy',
		'file',
		'memcached',
		'redis'
	);

	/**
	 * Reference to the driver
	 *
	 * @var mixed
	 */
	protected $_adapter = 'file';

	/**
	 * Backup driver if main driver isn't available
	 *
	 * @var string
	 */
	protected $_backup_driver = 'file';

	/**
	 * Constructor
	 *
	 * Initialize class properties based on the configuration array.
	 *
	 * @param	string	$driver	Name of cache driver to use
	 * @return	void
	 */
	public function __construct($driver = '')
	{
		/* -------------------------------------------
		/*	Hidden Configuration Variables
		/*	- cache_driver => Name of desired caching driver ('file', 'memcached'...)
		/*	- cache_driver_backup => Failover caching driver name
		/* -------------------------------------------*/
		$driver = ee()->config->item('cache_driver');
		$backup = ee()->config->item('cache_driver_backup');

		if ( ! empty($driver) && in_array($driver, $this->valid_drivers))
		{
			$this->_adapter = $driver;
		}

		if ( ! empty($backup) && in_array($backup, $this->valid_drivers))
		{
			$this->_backup_driver = $backup;
		}

		// If the specified adapter isn't available, check the backup.
		if ( ! $this->is_supported($this->_adapter))
		{
			if ( ! $this->is_supported($this->_backup_driver))
			{
				// Backup isn't supported either. Default to 'Dummy' driver.
				log_message('error', 'Cache adapter "'.$this->_adapter.'" and backup "'.$this->_backup_driver.'" are both unavailable. Cache is now using "Dummy" adapter.');
				$this->_adapter = 'dummy';
			}
			else
			{
				// Backup is supported. Set it to primary.
				log_message('debug', 'Cache adapter "'.$this->_adapter.'" is unavailable. Falling back to "'.$this->_backup_driver.'" backup adapter.');
				$this->_adapter = $this->_backup_driver;
			}
		}

		ee()->load->library('localize');
	}

	// ------------------------------------------------------------------------

	/**
	 * Look for a value in the cache. If it exists, return the data
	 * if not, return FALSE
	 *
	 * @param	string	$key 	Key name
	 * @param	const	$scope	self::CACHE_LOCAL or self::CACHE_GLOBAL for
	 *		local or global scoping of the cache item
	 * @return	mixed	value matching $id or FALSE on failure
	 */
	public function get($key, $scope = self::CACHE_LOCAL)
	{
		return $this->{$this->_adapter}->get($key, $scope);
	}

	// ------------------------------------------------------------------------

	/**
	 * Save value to cache
	 *
	 * @param	string	$key		Key name
	 * @param	mixed	$data		Data to store
	 * @param	int		$ttl = 60	Cache TTL (in seconds)
	 * @param	const	$scope	self::CACHE_LOCAL or self::CACHE_GLOBAL for
	 *		local or global scoping of the cache item
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function save($key, $data, $ttl = 60, $scope = self::CACHE_LOCAL)
	{
		return $this->{$this->_adapter}->save($key, $data, $ttl, $scope);
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
	 * @param	const	$scope	self::CACHE_LOCAL or self::CACHE_GLOBAL for
	 *		local or global scoping of the cache item
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function delete($key, $scope = self::CACHE_LOCAL)
	{
		return $this->{$this->_adapter}->delete($key, $scope);
	}

	// ------------------------------------------------------------------------

	/**
	 * Clean the cache
	 *
	 * @param	const	$scope	self::CACHE_LOCAL or self::CACHE_GLOBAL for
	 *		local or global scoping of the cache item
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function clean($scope = self::CACHE_LOCAL)
	{
		return $this->{$this->_adapter}->clean();
	}

	// ------------------------------------------------------------------------

	/**
	 * Cache Info
	 *
	 * @param	string	$type = 'user'	user/filehits
	 * @return	mixed	array containing cache info on success OR FALSE on failure
	 */
	public function cache_info($type = 'user')
	{
		return $this->{$this->_adapter}->cache_info($type);
	}

	// ------------------------------------------------------------------------

	/**
	 * Get Cache Metadata
	 *
	 * @param	string	$key	Key to get cache metadata on
	 * @param	const	$scope	self::CACHE_LOCAL or self::CACHE_GLOBAL for
	 *		local or global scoping of the cache item
	 * @return	mixed	cache item metadata
	 */
	public function get_metadata($key, $scope = self::CACHE_LOCAL)
	{
		return $this->{$this->_adapter}->get_metadata($key, $scope);
	}

	// ------------------------------------------------------------------------

	/**
	 * Is the requested driver supported in this environment?
	 *
	 * @param	string	$driver	The driver to test
	 * @return	array
	 */
	public function is_supported($driver)
	{
		static $support = array();

		if ( ! isset($support[$driver]))
		{
			$support[$driver] = $this->{$driver}->is_supported();
		}

		return $support[$driver];
	}

	// ------------------------------------------------------------------------

	/**
	 * Returns the name of the adapter currently in use
	 *
	 * @return	string	Name of adapter
	 */
	public function get_adapter()
	{
		return $this->_adapter;
	}

	// ------------------------------------------------------------------------

	/**
	 * Returns a unique key fit for using on a memory-based cache driver
	 *
	 * For storage drivers that can store keys for many sites, we want to make
	 * sure keys are kept unique to the current site, so we'll prefix the key
	 * name with the site URL
	 *
	 * For instances where the cached item is to be globally scoped to the
	 * installation, we'll prefix the key with a hash of the APPPATH and the
	 * server's IP address, so for instances where multiple servers are using
	 * the same Memcached/Redis server, cache items can remain unique but still
	 * globally scoped to the install
	 *
	 * Why not use the hash all the time? Using the raw site URL will provide
	 * more clarity when debugging cache issues
	 *
	 * @param	string	$key	Key to make unique
	 * @param	const	$scope	self::CACHE_LOCAL or self::CACHE_GLOBAL for
	 *		local or global scoping of the cache item
	 * @return	string	Key made unique to this site
	 */
	public function unique_key($key, $scope = self::CACHE_LOCAL)
	{
		$prefix = ee()->config->item('site_url');

		if ($scope == self::CACHE_GLOBAL)
		{
			$prefix = md5(ee()->input->server('SERVER_ADDR').APPPATH);
		}

		return $prefix.':'.$key;
	}

	// ------------------------------------------------------------------------

	/**
	 * Returns the separator character used to separate nested namespace names
	 *
	 * This is a method rather than a property because properties get caught in
	 * CI_Driver_Library's magic __get method
	 *
	 * @return	string	Namespace separator character
	 */
	public function namespace_separator()
	{
		return '/';
	}
}

/* End of file Cache.php */
/* Location: ./system/libraries/Cache/Cache.php */