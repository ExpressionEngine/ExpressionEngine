<?php

namespace EllisLab\ExpressionEngine\Service\Database;

use \EllisLab\ExpressionEngine\Service\Config\File as ConfigFile;

/**
* blah
*/
class DBConfig
{
	protected $delegate;

	public function __construct(ConfigFile $config)
	{
		$this->delegate = $config;
	}

	public function get($item, $default = NULL)
	{
		return $this->delegate->get('database.'.$item) ?: $default;
	}

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
