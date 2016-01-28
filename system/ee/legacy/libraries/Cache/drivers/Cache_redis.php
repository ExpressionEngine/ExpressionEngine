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
 * ExpressionEngine Redis Caching Class
 *
 * @package		ExpressionEngine
 * @subpackage	Libraries
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class EE_Cache_redis extends CI_Driver
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
	 * @param	const	$scope	Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE
	 *		 for local or global scoping of the cache item
	 * @return	mixed	value matching $id or FALSE on failure
	 */
	public function get($key, $scope = Cache::LOCAL_SCOPE)
	{
		$data = unserialize($this->_redis->get($this->unique_key($key, $scope)));

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
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function save($key, $value, $ttl = NULL, $scope = Cache::LOCAL_SCOPE)
	{
		$key = $this->unique_key($key, $scope);

		$data = serialize(array($value, ee()->localize->now));

		return ( ! empty($ttl))
			? $this->_redis->setex($key, $ttl, $data)
			: $this->_redis->set($key, $data);
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
		if (strrpos($key, Cache::NAMESPACE_SEPARATOR, strlen($key) - 1) !== FALSE)
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
	 * @param	const	$scope	Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE
	 *		 for local or global scoping of the cache item
	 * @return	bool	TRUE on success, FALSE on failureå
	 */
	public function clean($scope = Cache::LOCAL_SCOPE)
	{
		return ($this->_redis->delete(
			$this->_redis->keys($this->unique_key('', $scope).'*')
		) === 1);
	}

	// ------------------------------------------------------------------------

	/**
	 * Cache Info
	 *
	 * @return	mixed	array containing cache info on success OR FALSE on failure
	 * @see		Redis::info()
	 */
	public function cache_info()
	{
		return $this->_redis->info();
	}

	// ------------------------------------------------------------------------

	/**
	 * Get Cache Metadata
	 *
	 * @param	string	$id		Key to get cache metadata on
	 * @param	const	$scope	Cache::LOCAL_SCOPE or Cache::GLOBAL_SCOPE
	 *		 for local or global scoping of the cache item
	 * @return	mixed	Cache item metadata
	 */
	public function get_metadata($key, $scope = Cache::LOCAL_SCOPE)
	{
		$data = $data = unserialize($this->_redis->get($this->unique_key($key, $scope)));
		$key = $this->unique_key($key, $scope);

		if (is_array($data))
		{
			list($data, $time) = $data;

			$ttl = $this->_redis->ttl($key);

			return array(
				// Infinite TTLs have a TTL value of -1; if that's set, set the
				// expiration time to be the same as mtime to be consistent
				// with our other drivers
				'expire' => ($ttl == -1) ? $time : ee()->localize->now + $ttl,
				'mtime'	=> $time,
				'data' => $data
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

// EOF
