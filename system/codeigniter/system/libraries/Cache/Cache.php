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
	 * @param	string	$key 		Key name
	 * @param	string	$namespace	Namespace name
	 * @return	mixed	value matching $id or FALSE on failure
	 */
	public function get($key, $namespace = '')
	{
		return $this->{$this->_adapter}->get($key, $namespace);
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
		return $this->{$this->_adapter}->save($key, $data, $ttl, $namespace);
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
		return $this->{$this->_adapter}->delete($key, $namespace);
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete keys from cache with a specified prefix
	 *
	 * @param	string	$namespace	Namespace of group of cache keys to delete
	 * @return	bool
	 */
	public function clear_namepace($namespace)
	{
		return $this->{$this->_adapter}->clear_namepace($namespace);
	}

	// ------------------------------------------------------------------------

	/**
	 * Clean the cache
	 *
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function clean()
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
	 * @param	string	$key		Key to get cache metadata on
	 * @param	string	$namespace	Namespace name
	 * @return	mixed	cache item metadata
	 */
	public function get_metadata($key, $namespace = '')
	{
		return $this->{$this->_adapter}->get_metadata($key, $namespace);
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
	 * For storage drivers that can store keys for many sites, we want to make
	 * sure keys are kept unique to the current site, so we'll prefix the key
	 * name with the site URL
	 *
	 * @param	string	$key	Key to make unique
	 * @return	string	Key made unique to this site
	 */
	public function unique_key($key)
	{
		return ee()->config->item('site_url').':'.$key;
	}
}

/* End of file Cache.php */
/* Location: ./system/libraries/Cache/Cache.php */