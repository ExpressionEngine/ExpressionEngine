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
	public function get($id)
	{
		$data = $this->_memcached->get($id);

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
	public function save($id, $data, $ttl = 60)
	{
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
	public function delete($id)
	{
		return $this->_memcached->delete($id);
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
	public function get_metadata($id)
	{
		$stored = $this->_memcached->get($id);

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

}

/* End of file Cache_memcached.php */
/* Location: ./system/libraries/Cache/drivers/Cache_memcached.php */