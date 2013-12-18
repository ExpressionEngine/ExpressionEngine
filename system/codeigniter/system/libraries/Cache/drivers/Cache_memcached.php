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
 * ExpressionEngine Memcached Caching Class
 *
 * @package		ExpressionEngine
 * @subpackage	Libraries
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class CI_Cache_memcached extends CI_Driver {

	/**
	 * Holds the memcached object
	 *
	 * @var object
	 */
	protected $_memcached = NULL;

	/**
	 * Keeps current namespaces in memory
	 *
	 * @var array
	 */
	protected $_namespaces = array();

	/**
	 * Look for a value in the cache. If it exists, return the data
	 * if not, return FALSE
	 *
	 * @param	string	$key 		Key name
	 * @param	const	$scope		Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE
	 *		 for local or global scoping of the cache item
	 * @param	bool	$namespace 	Whether or not to namespace the key
	 * @return	mixed	value matching $id or FALSE on failure
	 */
	public function get($key, $scope = Cache::LOCAL_SCOPE, $namespace = TRUE)
	{
		$key = ($namespace) ? $this->_namespaced_key($key, $scope) : $this->unique_key($key, $scope);

		$data = $this->_memcached->get($key);

		return is_array($data) ? $data[0] : FALSE;
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
	 * @param	bool	$namespace 	Whether or not to namespace the key
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function save($key, $data, $ttl = 60, $scope = Cache::LOCAL_SCOPE, $namespace = TRUE)
	{
		$key = ($namespace) ? $this->_namespaced_key($key, $scope) : $this->unique_key($key, $scope);

		// Memcache does not allow a TTL more than 30 days, anything over will
		// cause set() to return FALSE; and Memcached interprets any TTL over
		// 30 days to be a Unix timestamp, so we'll cap the TTL at 30 days
		if ($ttl > 2592000)
		{
			$ttl = 2592000;
		}

		$data = array($data, ee()->localize->now, $ttl);

		if (get_class($this->_memcached) === 'Memcached')
		{
			return $this->_memcached->set($key, $data, $ttl);
		}
		elseif (get_class($this->_memcached) === 'Memcache')
		{
			return $this->_memcached->set($key, $data, 0, $ttl);
		}

		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete from cache
	 *
	 * @param	string	$key	Key name
	 * @param	const	$scope	Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE
	 *		 for local or global scoping of the cache item
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function delete($key, $scope = Cache::LOCAL_SCOPE)
	{
		// Delete namespace contents
		if (strrpos($key, $this->namespace_separator(), -1) !== FALSE)
		{
			$this->_create_new_namespace($key, $scope);

			return TRUE;
		}

		// Delete specific key
		return $this->_memcached->delete($this->_namespaced_key($key, $scope));
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
		$this->_create_new_namespace('', $scope, TRUE);

		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Cache Info
	 *
	 * @param	string	$type = 'user'	user/filehits (not used in this driver)
	 * @return	mixed	array on success, false on failure
	 */
	public function cache_info($type = NULL)
	{
		return $this->_memcached->getStats();
	}

	// ------------------------------------------------------------------------

	/**
	 * Get Cache Metadata
	 *
	 * @param	string	$key		Key to get cache metadata on
	 * @param	const	$scope	Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE
	 *		 for local or global scoping of the cache item
	 * @return	mixed	Cache item metadata
	 */
	public function get_metadata($key, $scope = Cache::LOCAL_SCOPE)
	{
		$stored = $this->_memcached->get($this->_namespaced_key($key, $scope));

		if (count($stored) !== 3)
		{
			return FALSE;
		}

		list($data, $time, $ttl) = $stored;

		return array(
			'expire'	=> $time + $ttl,
			'mtime'		=> $time,
			'data'		=> $data
		);
	}

	// ------------------------------------------------------------------------

	/**
	 * Setup memcached.
	 *
	 * @return	bool
	 */
	protected function _setup_memcached()
	{
		$defaults = array(
			'host' => '127.0.0.1',
			'port' => 11211,
			'weight' => 1
		);

		// Try to use user-configured Memcache config, otherwise we'll try
		// to use the defaults
		if (is_array(ee()->config->item('memcached')))
		{
			$memcache_config = ee()->config->item('memcached');
		}
		else
		{
			$memcache_config = array($defaults);
		}

		// We prefer to use Memcached
		if (class_exists('Memcached', FALSE))
		{
			$this->_memcached = new Memcached();
		}
		elseif (class_exists('Memcache', FALSE))
		{
			$this->_memcached = new Memcache();
		}
		else
		{
			return FALSE;
		}

		ee()->load->helper('array');

		// Add servers to Memcache
		foreach ($memcache_config as $server)
		{
			$host = element('host', $server, $defaults['host']);
			$port = element('port', $server, $defaults['port']);
			$weight = element('weight', $server, $defaults['weight']);

			if (get_class($this->_memcached) === 'Memcached')
			{
				$this->_memcached->addServer($host, $port, $weight);
			}
			else
			{
				// Third parameter is persistance and defaults to TRUE.
				$this->_memcached->addServer($host, $port, TRUE, $weight);
			}
		}

		// Attempt to get previously-created namespaces and assign to class variable
		$this->_namespaces = $this->get('namespaces', Cache::GLOBAL_SCOPE, FALSE);

		// Check each server to see if it's reporting the time; if at least
		// one server reports the time, we'll consider this driver ok to use
		foreach ($this->cache_info() as $server)
		{
			if ($server['time'] != 0)
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Is supported
	 *
	 * Returns FALSE if memcached is not supported on the system.
	 * If it is, we setup the memcached object & return TRUE
	 *
	 * @return	bool
	 */
	public function is_supported()
	{
		if ( ! extension_loaded('memcached') && ! extension_loaded('memcache'))
		{
			log_message('debug', 'The Memcached Extension must be loaded to use Memcached Cache.');
			return FALSE;
		}

		// If already instantiated, don't reinstantiate Memcache just to tell
		// the caller if Memcache is supported
		if ( ! is_null($this->_memcached))
		{
			return TRUE;
		}

		return $this->_setup_memcached();
	}

	// ------------------------------------------------------------------------

	/**
	 * Create a new namespace, which essentially invalidates an old/expired
	 * namespace.
	 *
	 * Memcache(d) does not provide a way to delete or invalidate cache items
	 * based on a wildcard pattern, and we do not way to iterate over every key
	 * stored in numerous Memcached servers to figure out what to delete. So
	 * we're using Memcached's recommended namespacing method of prefixing keys
	 * with a random number.
	 *
	 * For example, for page caches, our namespace may be "/page". We cannot
	 * tell Memcached to delete all keys beginning with "/page", so we prefix
	 * the key with a number, like so: "12345:/page". If we want to clear the
	 * page cache, we change the number ("12346:/page") so that items
	 * requested from the cache are effectively not existent, thus acting like
	 * a cleared cache.
	 *
	 * It may be easiest to think of it like versioning. All past versions
	 * continue to live in the cache store until automatically purged by
	 * Memcached, but as we clear namespaces, we basically create new versions
	 * of them for new things to be stored in.
	 *
	 * @param	string	$namespace	Name of the namespace, eg. "page", "tag"
	 * @param	const	$scope		Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE
	 *		 for local or global scoping of the cache item
	 * @param	bool	$clear_scope	Whether or not to clear the current scope
	 * @return	string	New namespace/prefix for keys to be stored in this
	 *					namespace
	 */
	protected function _create_new_namespace($namespace, $scope = Cache::LOCAL_SCOPE, $clear_scope = FALSE)
	{
		// Get the current unique scope string
		$root_key = $this->unique_key('', $scope);

		// We'll use the current time concatendated with a random number, that
		// way we're pretty much guaranteed not to use an existing namespace
		$unique_key = ee()->localize->now.rand(1,10000);

		// If no root namespace exists for the current scope, create one and
		// wipe out all other namespaces; or if the $clear_scope parameter is
		// set, in which case we are clearing the cache for the current scope
		if ( ! isset($this->_namespaces[$root_key]['root_namespace']) || $clear_scope)
		{
			$this->_namespaces[$root_key] = array('root_namespace' => $unique_key);
		}

		// If we're not creating a new cache for the current scope, we must be
		// creating a new namespace under the current scope
		if ( ! $clear_scope)
		{
			$this->_namespaces[$root_key][$namespace] = $unique_key;
		}

		// Save our class namespaces array to Memcached so we can access it on
		// subsequent page loads
		$this->save(
			'namespaces',
			$this->_namespaces,
			0,
			Cache::GLOBAL_SCOPE,
			FALSE // Don't namespace this key
		);

		return $unique_key;
	}

	// ------------------------------------------------------------------------

	/**
	 * Creates a properly namespaced key ready for storage or retreval of any
	 * cache item.
	 *
	 * For example, given a key of "/page/contact" and a scope of CACHE_LOCAL,
	 * would return a key similar to:
	 *
	 *     http://site.com:12345678:/page/contact
	 *
	 * This is so that a Memcached server serving many EE installs can use the
	 * same keys, conflict free. But it also serves to create namespaces within
	 * the cache store that we can indiviaully manange. For more information on
	 * this, see the doc block for _create_new_namespace above.
	 *
	 * @param	string	$key	Key name
	 * @param	const	$scope	Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE
	 *		 for local or global scoping of the cache item
	 * @return	string	Key that has been prefixed with the proper namespace
	 *					and make unique to this site
	 */
	protected function _namespaced_key($key, $scope = Cache::LOCAL_SCOPE)
	{
		$root_key = $this->unique_key('', $scope);

		// Make sure this scope has a root namespace
		if (isset($this->_namespaces[$root_key]['root_namespace']))
		{
			// If the key contains our namespace separator character...
			if (strpos($key, $this->namespace_separator()) !== FALSE)
			{
				// Separate the namespace from the cache key name
				$namespace = substr($key, 0, strrpos($key, $this->namespace_separator()) + 1);

				// If this namespace is already set, get it from local cache
				if (isset($this->_namespaces[$root_key][$namespace]))
				{
					$namespace = $this->_namespaces[$root_key][$namespace];
				}
				// Otherwise, create the namespace
				else
				{
					$namespace = $this->_create_new_namespace($namespace, $scope);
				}
			}
			// Key is not namespaced, give it the namespace identifier of the
			// root namespace of the scope
			else
			{
				$namespace = $this->_namespaces[$root_key]['root_namespace'];
			}
		}
		// If the current scope does not have namespaces setup, create one
		// and try to make a namespaced key again
		else
		{
			$this->_create_new_namespace('', $scope, TRUE);

			return $this->_namespaced_key($key, $scope);
		}

		return $this->unique_key($namespace.':'.$key);
	}
}

/* End of file Cache_memcached.php */
/* Location: ./system/libraries/Cache/drivers/Cache_memcached.php */