<?php

namespace EllisLab\ExpressionEngine\Service\Database;

use \EllisLab\ExpressionEngine\Service\Config\File as ConfigFile;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package   ExpressionEngine
 * @author    EllisLab Dev Team
 * @copyright Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license   http://ellislab.com/expressionengine/user-guide/license.html
 * @link      http://ellislab.com
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
 * @link       http://ellislab.com
 */
class DBConfig
{
	protected $delegate;

	/**
	 * Create new Database Config object
	 * @param ConfigFile $config Config\File object
	 */
	public function __construct(ConfigFile $config)
	{
		$this->delegate = $config;
	}

	/**
	 * Get an item from the database config, you can use
	 * "expressionengine.hostname" to drill down in the config
	 * @param  string $item    The config item to get
	 * @param  mixed  $default The value to return if $item can not be found
	 * @return mixed           The value found for $item, otherwise $default
	 */
	public function get($item, $default = NULL)
	{
		return $this->delegate->get('database.'.$item) ?: $default;
	}

	public function set($item, $value)
	{
		return $this->delegate->set('database.'.$item, $value);
	}

	/**
	 * Get the active group's database configuration information for
	 * CI_DB_driver
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
	public function getGroup($group = '')
	{
		$active_group = $group ?: $this->get('active_group');
		$database_config = $this->get($active_group);

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
}
