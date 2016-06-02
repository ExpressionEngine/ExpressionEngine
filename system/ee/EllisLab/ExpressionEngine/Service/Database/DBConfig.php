<?php

namespace EllisLab\ExpressionEngine\Service\Database;

use \EllisLab\ExpressionEngine\Service\Config\ConfigWithDefaults;
use \EllisLab\ExpressionEngine\Service\Config\File as ConfigFile;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package   ExpressionEngine
 * @author    EllisLab Dev Team
 * @copyright Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license   https://expressionengine.com/license
 * @link      https://ellislab.com
 * @since     Version 3.0.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Database Config Class
 *
 * @package    ExpressionEngine
 * @subpackage Core
 * @category   Core
 * @author     EllisLab Dev Team
 * @link       https://ellislab.com
 */
class DBConfig extends ConfigWithDefaults {

	protected $delegate;
	protected $active_group;
	protected $defaults = array(
		'port'     => 3306,
		'hostname' => '127.0.0.1',
		'username' => 'root',
		'password' => '',
		'database' => '',
		'dbdriver' => 'mysqli',
		'pconnect' => FALSE,
		'dbprefix' => 'exp_',
		'swap_pre' => 'exp_',
		'db_debug' => TRUE,
		'cache_on' => FALSE,
		'autoinit' => FALSE,
		'char_set' => 'utf8',
		'dbcollat' => 'utf8_unicode_ci',
		'cachedir' => '', // Set in constructor
	);

	/**
	 * Create new Database Config object
	 *
	 * @param ConfigFile $config Config\File object
	 */
	public function __construct(ConfigFile $config)
	{
		$this->delegate = $config;
		$this->active_group = $this->delegate->get(
			'database.active_group',
			'expressionengine'
		);

		$this->defaults['cachedir'] = rtrim(APPPATH, '/').'/user/cache/db_cache/';
	}

	/**
	 * Get an item from the database config, you can use
	 * "expressionengine.hostname" to drill down in the config
	 *
	 * @param  string $item    The config item to get
	 * @param  mixed  $default The value to return if $item can not be found
	 * @return mixed           The value found for $item, otherwise $default
	 */
	public function get($item = '', $default = NULL)
	{
		$default = $this->getDefaultFor($item, $default);

		$result = $this->delegate->get(
			rtrim("database.{$this->active_group}.{$item}", '.'),
			$default
		);

		if (is_array($result))
		{
			return array_merge($default, $result);
		}

		return $result;
	}

	/**
	 * Set the value of a database configuration item
	 *
	 * @param  string $item  The config item to set
	 * @param  mixed  $value The new value of the config item
	 * @return void
	 */
	public function set($item, $value)
	{
		if ($value == $this->getDefaultFor($item))
		{
			$value = NULL;
		}

		$this->delegate->set(
			"database.{$this->active_group}.".$item,
			$value
		);
	}

	/**
	 * Get the active group's database configuration information for
	 * CI_DB_driver
	 *
	 * @param  string $group Optionally pass in a group name to override
	 *                       active_group
	 *
	 * @throws Exception If the $group specified or the active_group specified
	 * in the config does not have related configuration details
	 * @throws Exception If the $group specfiied or the active_group specified
	 * in the config does not contain a username, hostname, and database
	 *
	 * @return array         The database configuration information consumable
	 *                       directly by CI_DB_driver
	 */
	public function getGroupConfig($group = '')
	{
		if ( ! empty($group))
		{
			$this->active_group = $group;
		}

		$database_config = $this->get();

		if (empty($database_config))
		{
			throw new \Exception('You have specified an invalid database connection group.');
		}

		// Check for required items
		$required = array('username', 'hostname', 'database');
		$missing = array();

		foreach ($required as $required_field)
		{
			if (empty($database_config[$required_field]))
			{
				$missing[] = $required_field;
			}
		}

		if ( ! empty($missing))
		{
			throw new \Exception('You must define the following database parameters: '.implode(', ', $missing));
		}

		return $database_config;
	}

	/**
	 * Get the default values
	 *
	 * @return array Default values for config
	 */
	public function getDefaults()
	{
		return $this->defaults;
	}

	/**
	 * Get the name of the active group
	 *
	 * @return string Name of the active database group
	 */
	public function getActiveGroup()
	{
		return $this->active_group;
	}
}

// EOF
