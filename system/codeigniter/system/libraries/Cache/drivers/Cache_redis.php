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
 * ExpressionEngine Redis Caching Class
 *
 * @package		ExpressionEngine
 * @subpackage	Libraries
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class CI_Cache_redis extends CI_Driver
{
	/**
	 * Default config
	 *
	 * @static
	 * @var	array
	 */
	protected static $_default_config = array(
		'host' => '127.0.0.1',
		'password' => NULL,
		'port' => 6379,
		'timeout' => 0
	);

	/**
	 * Redis connection
	 *
	 * @var	Redis
	 */
	protected $_redis;

	// ------------------------------------------------------------------------

	/**
	 * Get cache
	 *
	 * @param	string	Cache key identifier
	 * @param	string	Namespace name
	 * @return	mixed
	 */
	public function get($key, $namespace = '')
	{
		return $this->_redis->get($this->_namespaced_key($key, $namespace));
	}

	// ------------------------------------------------------------------------

	/**
	 * Save cache
	 *
	 * @param	string	Cache key identifier
	 * @param	mixed	Data to save
	 * @param	int	Time to live
	 * @param	string	Namespace name
	 * @return	bool
	 */
	public function save($key, $value, $ttl = NULL, $namespace = '')
	{
		$key = $this->_namespaced_key($key, $namespace);

		return ( ! empty($ttl))
			? $this->_redis->setex($key, $ttl, $value)
			: $this->_redis->set($key, $value);
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete from cache
	 *
	 * @param	string	Cache key
	 * @param	string	Namespace name
	 * @return	bool
	 */
	public function delete($key, $namespace = '')
	{
		return ($this->_redis->delete($this->_namespaced_key($key, $namespace)) === 1);
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete keys from cache with a specified prefix
	 *
	 * @param	string	Namepace of group of cache keys to delete
	 * @return	bool
	 */
	public function clear_namepace($namespace)
	{
		$this->_redis->delete(
			$this->_redis->keys($this->_namespaced_key('', $namespace).'*')
		);
	}

	// ------------------------------------------------------------------------

	/**
	 * Clean cache
	 *
	 * @return	bool
	 * @see		Redis::flushDB()
	 */
	public function clean()
	{
		return $this->_redis->flushDB();
	}

	// ------------------------------------------------------------------------

	/**
	 * Get cache driver info
	 *
	 * @param	string	Not supported in Redis.
	 *			Only included in order to offer a
	 *			consistent cache API.
	 * @return	array
	 * @see		Redis::info()
	 */
	public function cache_info($type = NULL)
	{
		return $this->_redis->info();
	}

	// ------------------------------------------------------------------------

	/**
	 * Get cache metadata
	 *
	 * @param	string	Cache key
	 * @param	string	Namespace name
	 * @return	array
	 */
	public function get_metadata($key, $namespace = '')
	{
		$value = $this->get($key, $namespace);
		$key = $this->_namespaced_key($key, $namespace);

		if ($value)
		{
			return array(
				'expire' => ee()->localize->now + $this->_redis->ttl($key),
				'data' => $value
			);
		}

		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Check if Redis driver is supported
	 *
	 * @return	bool
	 */
	public function is_supported()
	{
		if (extension_loaded('redis'))
		{
			$this->_setup_redis();
			return TRUE;
		}
		else
		{
			log_message('debug', 'The Redis extension must be loaded to use Redis cache.');
			return FALSE;
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Setup Redis config and connection
	 *
	 * Loads Redis config file if present. Will halt execution
	 * if a Redis connection can't be established.
	 *
	 * @return	bool
	 * @see		Redis::connect()
	 */
	protected function _setup_redis()
	{
		$config = array();
		$CI =& get_instance();

		if ($CI->config->load('redis', TRUE, TRUE))
		{
			$config += $CI->config->item('redis');
		}

		$config = array_merge(self::$_default_config, $config);

		$this->_redis = new Redis();

		try
		{
			$this->_redis->connect($config['host'], $config['port'], $config['timeout']);
		}
		catch (RedisException $e)
		{
			show_error('Redis connection refused. ' . $e->getMessage());
		}

		if (isset($config['password']))
		{
			$this->_redis->auth($config['password']);
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Class destructor
	 *
	 * Closes the connection to Redis if present.
	 *
	 * @return	void
	 */
	public function __destruct()
	{
		if ($this->_redis)
		{
			$this->_redis->close();
		}
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

/* End of file Cache_redis.php */
/* Location: ./system/libraries/Cache/drivers/Cache_redis.php */