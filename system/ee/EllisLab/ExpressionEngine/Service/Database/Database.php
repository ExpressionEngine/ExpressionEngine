<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Database;

/**
 * Database
 */
class Database
{
	protected $log;
	protected $config;
	protected $connection;

	/**
	 * Create new Database object
	 *
	 * @param DBConfig $db_config DBConfig object
	 */
	public function __construct(DBConfig $db_config)
	{
		$this->setConfig($db_config);
	}

	/**
	 * Returns a new Query object
	 *
	 * @return Query A Query object.
	 */
	public function newQuery()
	{
		return new Query($this->getConnection());
	}

	/**
	 * Get the config for the selected database group
	 *
	 * @return array Array suitable for loading up the database
	 */
	public function getConfig()
	{
		return $this->config;
	}

	/**
	 * Set the active configuration
	 *
	 * @param DBConfig $config DBConfig object
	 * @return void
	 */
	public function setConfig(DBConfig $config)
	{
		$this->config = $config;
	}

	/**
	 * Gets the current DB connection. If there isn't one a new one is created.
	 *
	 * @return Connection A Connection object.
	 */
	public function getConnection()
	{
		if ( ! isset($this->connection))
		{
			$this->setConnection($this->newConnection());
		}

		return $this->connection;
	}

	/**
	 * Sets the DB connection.
	 *
	 * @param Connection $connection Connection object
	 * @return void
	 */
	public function setConnection(Connection $connection)
	{
		$this->connection = $connection;
		$this->connection->setLog($this->getLog());
	}

	/**
	 * Gets the log. If there isn't a log a new one is created.
	 *
	 * @return Log The Log
	 */
	public function getLog()
	{
		if ( ! isset($this->log))
		{
			$this->setLog($this->newLog());
		}

		return $this->log;
	}

	/**
	 * Sets the log.
	 *
	 * @param Log $log Log object
	 * @return void
	 */
	public function setLog(Log $log)
	{
		$this->log = $log;
	}

	/**
	 * Returns current db execution time
	 *
	 * @return int the total time currently spent on querying the database
	 */
	public function currentExecutionTime()
	{
		$time = 0;
		foreach ($this->log->getQueries() as $query)
		{
			// time is array key 2 in this numerically indexed array
			$time += $query[2];
		}
		return $time;
	}

	/**
	 * Create a default connection object
	 *
	 * @return Connection A Connection object
	 */
	protected function newConnection()
	{
		$config = $this->config->getGroupConfig();
		$connection = new Connection($config);

		if (isset($config['stricton']) && $config['stricton'] == TRUE)
		{
			$connection->open();
			$connection->query('SET SESSION sql_mode="STRICT_ALL_TABLES"');
		}

		return $connection;
	}

	/**
	 * Close the database connection
	 *
	 * @return void
	 */
	public function closeConnection()
	{
		if (isset($this->connection))
		{
			$this->connection->close();
		}

		unset($this->connection);
	}

	/**
	 * Create a default log object
	 *
	 * @return Log A Log object
	 */
	protected function newLog()
	{
		return new Log('default');
	}
}

// EOF
