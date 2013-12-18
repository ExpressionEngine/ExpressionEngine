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
	 * Redis connection
	 *
	 * @var	Redis
	 */
	protected $_redis;

	// ------------------------------------------------------------------------

	/**
	 * Look for a value in the cache. If it exists, return the data
	 * if not, return FALSE
	 *
	 * @param	string	$key 	Key name
	 * @param	const	$scope	CI_Cache::CACHE_LOCAL or CI_Cache::CACHE_GLOBAL
	 *		 for local or global scoping of the cache item
	 * @return	mixed	value matching $id or FALSE on failure
	 */
	public function get($key, $scope = CI_Cache::CACHE_LOCAL)
	{
		return unserialize($this->_redis->get($this->unique_key($key, $scope)));
	}

	// ------------------------------------------------------------------------

	/**
	 * Save value to cache
	 *
	 * @param	string	$key		Key name
	 * @param	mixed	$data		Data to store
	 * @param	int		$ttl = 60	Cache TTL (in seconds)
	 * @param	const	$scope		CI_Cache::CACHE_LOCAL or CI_Cache::CACHE_GLOBAL
	 *		 for local or global scoping of the cache item
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function save($key, $value, $ttl = NULL, $scope = CI_Cache::CACHE_LOCAL)
	{
		$key = $this->unique_key($key, $scope);
		$value = serialize($value);

		return ( ! empty($ttl))
			? $this->_redis->setex($key, $ttl, $value)
			: $this->_redis->set($key, $value);
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete from cache
	 *
	 * @param	string	$key	Key name
	 * @param	const	$scope	CI_Cache::CACHE_LOCAL or CI_Cache::CACHE_GLOBAL
	 *		 for local or global scoping of the cache item
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function delete($key, $scope = CI_Cache::CACHE_LOCAL)
	{
		// Delete namespace contents
		if (strrpos($key, $this->namespace_separator(), -1) !== FALSE)
		{
			return ($this->_redis->delete(
				$this->_redis->keys($this->unique_key($key, $scope).'*')
			) === 1);
		}

		// Delete specific key
		return ($this->_redis->delete($this->unique_key($key, $scope)) === 1);
	}

	// ------------------------------------------------------------------------

	/**
	 * Clean cache for the current scope
	 *
	 * @param	const	$scope	CI_Cache::CACHE_LOCAL or CI_Cache::CACHE_GLOBAL
	 *		 for local or global scoping of the cache item
	 * @return	bool	TRUE on success, FALSE on failureå
	 */
	public function clean($scope = CI_Cache::CACHE_LOCAL)
	{
		return ($this->_redis->delete(
			$this->_redis->keys($this->unique_key('', $scope).'*')
		) === 1);
	}

	// ------------------------------------------------------------------------

	/**
	 * Cache Info
	 *
	 * @param	string	$type = 'user'	user/filehits (not used in this driver)
	 * @return	mixed	array containing cache info on success OR FALSE on failure
	 * @see		Redis::info()
	 */
	public function cache_info($type = NULL)
	{
		return $this->_redis->info();
	}

	// ------------------------------------------------------------------------

	/**
	 * Get Cache Metadata
	 *
	 * @param	string	$id		Key to get cache metadata on
	 * @param	const	$scope	CI_Cache::CACHE_LOCAL or CI_Cache::CACHE_GLOBAL
	 *		 for local or global scoping of the cache item
	 * @return	mixed	Cache item metadata
	 */
	public function get_metadata($key, $scope = CI_Cache::CACHE_LOCAL)
	{
		$value = $this->get($key, $scope);
		$key = $this->unique_key($key, $scope);

		if ($value)
		{
			return array(
				'expire' => ee()->localize->now + $this->_redis->ttl($key),
				'mtime'	=> NULL,
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
		// Redis already set up
		if ( ! empty($this->_redis))
		{
			return TRUE;
		}
		if (extension_loaded('redis') && class_exists('Redis', FALSE))
		{
			return $this->_setup_redis();
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
		$config = array(
			'host' => '127.0.0.1',
			'password' => NULL,
			'port' => 6379,
			'timeout' => 0
		);

		if (($user_config = ee()->config->item('redis')) !== FALSE)
		{
			$config = array_merge($config, $user_config);
		}

		$this->_redis = new Redis();

		// Our return value which we will update as we setup Redis; if it's
		// TRUE at the end, allow Redis to be used
		$result = FALSE;

		try
		{
			$result = $this->_redis->connect($config['host'], $config['port'], $config['timeout']);
		}
		catch (RedisException $e)
		{
			log_message('debug', 'Redis connection refused: '.$e->getMessage());
			$this->_redis = FALSE;
			return FALSE;
		}

		// Redis will return FALSE sometimes instead of throwing an exeption
		if ( ! $result)
		{
			log_message('debug', 'Redis connection failed.');
			$this->_redis = FALSE;
			return FALSE;
		}

		// If a password is set, attempt to authenticate
		if ( ! empty($config['password']) && $result)
		{
			$result = $this->_redis->auth($config['password']);
		}

		return $result;
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
}

/* End of file Cache_redis.php */
/* Location: ./system/libraries/Cache/drivers/Cache_redis.php */