<?php

namespace EllisLab\ExpressionEngine\Service\Database;

use \EllisLab\ExpressionEngine\Service\Database\DBConfig;

class Database
{
	protected $config;
	protected $connection;

	public function __construct(DBConfig $db_config)
	{
		$this->config = $db_config;
	}

	public function getConfig()
	{
		return $this->config->getGroup();
	}

	protected function wrapConfig($config)
	{
		return new DBConfig($config);
	}
}
