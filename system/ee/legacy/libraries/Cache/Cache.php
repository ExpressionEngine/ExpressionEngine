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
 * ExpressionEngine Caching Class
 *
 * @package		ExpressionEngine
 * @subpackage	Libraries
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Cache extends EE_Driver_Library {

	/**
	 * These constants specify the scope in which the cache item should
	 * exist; either it should exist in and be accessible only by the
	 * current site, or it should be globally accessible by the EE
	 * installation across MSM sites
	 */
	const GLOBAL_SCOPE = 1;	// Scoped to the current site
	const LOCAL_SCOPE = 2;	// Scoped to global EE install

	// separator character used to separate nested namespace names
	const NAMESPACE_SEPARATOR = '/';

	/**
	 * Valid cache drivers
	 *
	 * @var array
	 */
	protected $valid_drivers = array(
		'file',
		'memcached',
		'redis',
		'dummy'
	);

	/**
	 * Valid cache drivers for EE Core
	 *
	 * @var array
	 */
	protected $_core_valid_drivers = array(
		'file',
		'dummy'
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
	 */
	public function __construct()
	{
		// Only allow certain drivers for EE Core
		if (IS_CORE)
		{
			$this->valid_drivers = $this->_core_valid_drivers;
		}

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
				log_message('error', 'Cache adapter "'.$this->_adapter.'" and backup "'.$this->_backup_driver.'" are both unavailable. Cache is using "File" adapter.');
				$this->_adapter = 'file';
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
	 * @param	const	$scope	Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE for
	 *		local or global scoping of the cache item
	 * @return	mixed	value matching $id or FALSE on failure
	 */
	public function get($key, $scope = Cache::LOCAL_SCOPE)
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
	 * @param	const	$scope	Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE for
	 *		local or global scoping of the cache item
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function save($key, $data, $ttl = 60, $scope = Cache::LOCAL_SCOPE)
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
	 * @param	const	$scope	Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE for
	 *		local or global scoping of the cache item
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function delete($key, $scope = Cache::LOCAL_SCOPE)
	{
		return $this->{$this->_adapter}->delete($key, $scope);
	}

	// ------------------------------------------------------------------------

	/**
	 * Clean the cache
	 *
	 * @param	const	$scope	Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE for
	 *		local or global scoping of the cache item
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function clean($scope = Cache::LOCAL_SCOPE)
	{
		return $this->{$this->_adapter}->clean($scope);
	}

	// ------------------------------------------------------------------------

	/**
	 * Cache Info
	 *
	 * @return	mixed	array containing cache info on success OR FALSE on failure
	 */
	public function cache_info()
	{
		return $this->{$this->_adapter}->cache_info();
	}

	// ------------------------------------------------------------------------

	/**
	 * Get Cache Metadata
	 *
	 * @param	string	$key	Key to get cache metadata on
	 * @param	const	$scope	Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE for
	 *		local or global scoping of the cache item
	 * @return	mixed	cache item metadata
	 */
	public function get_metadata($key, $scope = Cache::LOCAL_SCOPE)
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
	 * @param	const	$scope	Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE for
	 *		local or global scoping of the cache item
	 * @return	string	Key made unique to this site
	 */
	public function unique_key($key, $scope = Cache::LOCAL_SCOPE)
	{
		// Using base_url here because some add-ons dynamically change site_url,
		// for multilingual sites for example
		$prefix = ee()->config->item('base_url');

		if ($scope == Cache::GLOBAL_SCOPE)
		{
			$prefix = md5(ee()->input->server('SERVER_ADDR').APPPATH);
		}

		return $prefix.':'.$key;
	}

	// ------------------------------------------------------------------------

	/**
	 * Returns HTML form for the Caching Driver setting on the General
	 * Configuration screen, and also optionally an error message if the driver
	 * selected cannot be used
	 *
	 * @return	string	HTML dropdown and optional error message
	 */
	public function admin_setting()
	{
		$adapter = ee()->config->item('cache_driver');
		$current_adapter = $this->get_adapter();

		if (empty($adapter))
		{
			$adapter = 'file';
		}

		$field = array('type' => 'select');

		// Create options array fit for a dropdown
		foreach ($this->valid_drivers as $driver)
		{
			$field['choices'][$driver] = ucwords($driver);
		}

		// Rename dummy driver for presentation
		$field['choices']['dummy'] = lang('disable_caching');

		// If the driver we want to use isn't what we are using, build an error
		// message
		if ($adapter !== $current_adapter OR ! $this->$current_adapter->is_supported())
		{
			$error_key = ($adapter == 'file')
				? 'caching_driver_file_fail' : 'caching_driver_failover';

			$field['note'] = sprintf(lang($error_key), ucwords($adapter), ucwords($this->get_adapter()));
		}

		return $field;
	}
}

// EOF
