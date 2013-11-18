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
	 * Keeps current namespaces in memory in the format of:
	 *
	 *     'namespace' => 'key_prefix'
	 *
	 * @var array
	 */
	protected $_key_prefixes = array();

	/**
	 * Look for a value in the cache. If it exists, return the data
	 * if not, return FALSE
	 *
	 * @param	string	$id 		Key name
	 * @param	string	$namespace	Namespace name
	 * @return	mixed	value matching $id or FALSE on failure
	 */
	public function get($id, $namespace = '')
	{
		$data = $this->_memcached->get($this->_namespaced_key($id, $namespace));

		return is_array($data) ? $data[0] : FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Save value to cache
	 *
	 * @param	string	$id			Key name
	 * @param	mixed	$data		Data to store
	 * @param	int		$ttl = 60	Cache TTL (in seconds)
	 * @param	string	$namespace	Namespace name
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function save($id, $data, $ttl = 60, $namespace = '')
	{
		$id = $this->_namespaced_key($id, $namespace);

		if (get_class($this->_memcached) === 'Memcached')
		{
			return $this->_memcached->set($id, array($data, ee()->localize->now, $ttl), $ttl);
		}
		elseif (get_class($this->_memcached) === 'Memcache')
		{
			return $this->_memcached->set($id, array($data, ee()->localize->now, $ttl), 0, $ttl);
		}

		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete from cache
	 *
	 * @param	string	$id			Key name
	 * @param	string	$namespace	Namespace name
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function delete($id, $namespace = '')
	{
		return $this->_memcached->delete($this->_namespaced_key($id, $namespace));
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
		$this->_create_new_namespace($namespace);
	}

	// ------------------------------------------------------------------------

	/**
	 * Clean the cache
	 *
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function clean()
	{
		return $this->_memcached->flush();
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
	 * @param	string	$id			Key to get cache metadata on
	 * @param	string	$namespace	Namespace name
	 * @return	mixed	Cache item metadata
	 */
	public function get_metadata($id, $namespace = '')
	{
		$stored = $this->_memcached->get($this->_namespaced_key($id, $namespace));

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

		// We'll keep track of the return values of addServer here to make
		// sure we have at least one good server
		$results = array();

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

		return TRUE;
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
	 * namespace. Since Memcache doesn't let us delete cache keys based on a
	 * pattern, and we don't want to iterate over every key stored on each
	 * Memcache server we're connected to to figure out what to delete, we'll
	 * just create a new namespace every time we want to clear a particular
	 * subset of cached items. For example, for page caches, our namespace may
	 * be "1234:page:", but if we want to start the page cache fresh, we'll
	 * change it to "1235:page:", so that new page cache items will be forced
	 * to be created anew.
	 *
	 * @param	string	$namespace	Name of the namespace, eg. "page", "tag"
	 * @return	string	New namespace/prefix for keys to be stored in this
	 *					namespace
	 */
	protected function _create_new_namespace($namespace)
	{
		// We'll use the current time, that way we're pretty much guaranteed
		// not to use an existing namespace
		$this->_key_prefixes[$namespace] = ee()->localize->now.':'.$namespace;

		// Save it to memcache so we can access it on subsequent page loads
		$this->save($namespace.'-namespace', $this->_key_prefixes[$namespace], 0);

		return $this->_key_prefixes[$namespace];
	}

	// ------------------------------------------------------------------------

	/**
	 * Creates a properly namespaced key ready for storage or retreval of any
	 * cache item. Given something like "some_data" for the key and "tag" for
	 * the namespace, returns something like "//ee2/:12345:tag:some_data" to
	 * ensure we are pulling from the correct namespace while making the key
	 * unique to this site since Memcache can store data for many sites
	 *
	 * @param	string	$key	Key name
	 * @param	string	$namespace	Name of the namespace, eg. "page", "tag"
	 * @return	string	Key that has been prefixed with the proper namespace
	 *					and make unique to this site
	 */
	protected function _namespaced_key($key, $namespace)
	{
		if ( ! empty($namespace))
		{
			// If key isn't already cached locally, try to get it from Memcache
			if ( ! isset($this->_key_prefixes[$namespace]))
			{
				$data = $this->_memcached->get($this->unique_key($namespace.'-namespace'));

				// Cache the new namespace, or create a new one if we didn't
				// find one
				if (is_array($data))
				{
					$namespace = $this->_key_prefixes[$namespace] = $data[0];
				}
				else
				{
					$namespace = $this->_create_new_namespace($namespace);
				}
			}
			// Return cached namespace
			elseif (isset($this->_key_prefixes[$namespace]))
			{
				$namespace = $this->_key_prefixes[$namespace];
			}

			$namespace .= ':';
		}

		return $this->unique_key($namespace.$key);
	}
}

/* End of file Cache_memcached.php */
/* Location: ./system/libraries/Cache/drivers/Cache_memcached.php */