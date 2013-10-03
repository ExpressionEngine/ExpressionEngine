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
	protected $_memcached;
	protected $_key_prefixes = array();

	/**
	 * Memcached configuration
	 *
	 * @var array
	 */
	protected $_memcache_conf	= array(
		'default' => array(
			'host'		=> '127.0.0.1',
			'port'		=> 11211,
			'weight'	=> 1
		)
	);

	/**
	 * Fetch from cache
	 *
	 * @param	mixed	unique key id
	 * @return	mixed	data on success/false on failure
	 */
	public function get($id, $namespace = '')
	{
		$data = $this->_memcached->get($this->_namespaced_key($id, $namespace));

		return is_array($data) ? $data[0] : FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Save
	 *
	 * @param	string	unique identifier
	 * @param	mixed	data being cached
	 * @param	int	time to live
	 * @return	bool	true on success, false on failure
	 */
	public function save($id, $data, $ttl = 60, $namespace = '')
	{
		$id = $this->_namespaced_key($id, $namespace);

		if (get_class($this->_memcached) === 'Memcached')
		{
			return $this->_memcached->set($id, array($data, time(), $ttl), $ttl);
		}
		elseif (get_class($this->_memcached) === 'Memcache')
		{
			return $this->_memcached->set($id, array($data, time(), $ttl), 0, $ttl);
		}

		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete from Cache
	 *
	 * @param	mixed	key to be deleted.
	 * @return	bool	true on success, false on failure
	 */
	public function delete($id, $namespace = '')
	{
		return $this->_memcached->delete($this->_namespaced_key($id, $namespace));
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete keys from cache with a specified prefix
	 *
	 * @param	mixed	Prefix of group of cache keys to delete
	 * @return	bool
	 */
	public function delete_namespace($namespace)
	{
		$this->_create_new_namespace($namespace);
	}

	// ------------------------------------------------------------------------

	/**
	 * Clean the Cache
	 *
	 * @return	bool	false on failure/true on success
	 */
	public function clean()
	{
		return $this->_memcached->flush();
	}

	// ------------------------------------------------------------------------

	/**
	 * Cache Info
	 *
	 * @return	mixed	array on success, false on failure
	 */
	public function cache_info()
	{
		return $this->_memcached->getStats();
	}

	// ------------------------------------------------------------------------

	/**
	 * Get Cache Metadata
	 *
	 * @param	mixed	key to get cache metadata on
	 * @return	mixed	FALSE on failure, array on success.
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
		$defaults = $this->_memcache_conf['default'];

		if (is_array(ee()->config->item('memcached')))
		{
			$this->_memcache_conf = array();

			foreach (ee()->config->item('memcached') as $name => $conf)
			{
				$this->_memcache_conf[$name] = $conf;
			}
		}

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
			log_message('error', 'Failed to create object for Memcached Cache; extension not loaded?');
			return FALSE;
		}

		foreach ($this->_memcache_conf as $cache_server)
		{
			isset($cache_server['host']) OR $cache_server['host'] = $defaults['host'];
			isset($cache_server['port']) OR $cache_server['port'] = $defaults['port'];
			isset($cache_server['weight']) OR $cache_server['weight'] = $defaults['weight'];

			if (get_class($this->_memcached) === 'Memcache')
			{
				// Third parameter is persistance and defaults to TRUE.
				$this->_memcached->addServer(
					$cache_server['host'],
					$cache_server['port'],
					TRUE,
					$cache_server['weight']
				);
			}
			else
			{
				$this->_memcached->addServer(
					$cache_server['host'],
					$cache_server['port'],
					$cache_server['weight']
				);
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
		$this->_key_prefixes[$namespace] = time().':'.$namespace;

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