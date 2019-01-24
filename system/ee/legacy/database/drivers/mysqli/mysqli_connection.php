<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * MySQLi Database Connection
 */
class CI_DB_mysqli_connection {

	/**
	 * @var Array of config values
	 */
	protected $config;

	/**
	 * @var PDO connection
	 */
	protected $connection;

	/**
	 * @var Do we have MySQLnd?
	 */
	protected $mysqlnd;

	/**
	 * Create a conneciton
	 *
	 * @param Array $config Config values
	 */
	public function __construct($config)
	{
		$this->config = $config;
		$this->mysqlnd = extension_loaded('pdo_mysql') && extension_loaded('mysqlnd');
	}

	/**
	 * Get the connection config
	 *
	 * @return Array Config values
	 */
	public function getConfig()
	{
		return $this->config;
	}

	/**
	 * Open the connection
	 */
	public function open()
	{
		$hostname = $this->config['hostname'];
		$username = $this->config['username'];
		$password = $this->config['password'];
		$database = $this->config['database'];
		$char_set = $this->config['char_set'];
		$pconnect = $this->config['pconnect'];
		$dbcollat = $this->config['dbcollat'];
		$port     = $this->config['port'];

		$dsn = "mysql:dbname={$database};host={$hostname};port={$port};charset={$char_set}";

		// If we have MySQLnd then we can set ATTR_STRINGIFY_FETCHES to FALSE
		// otherwise we should set it to TRUE to improve memory performance.
		$options = array(
			PDO::ATTR_PERSISTENT => $pconnect,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_CASE => PDO::CASE_NATURAL,
			PDO::ATTR_STRINGIFY_FETCHES => ! $this->mysqlnd
		);

		$this->connection = @new PDO(
			$dsn,
			$username,
			$password,
			$options
		);

		$this->query("SET NAMES '$char_set' COLLATE '$dbcollat'");
	}

	/**
	 * Close the connection
	 */
	public function close()
	{
		$this->connection = NULL;
	}

	/**
	 * Run a query
	 *
	 * @param String $query SQL to run
	 * @return Query result
	 */
	public function query($query)
	{
		$time_start = microtime(TRUE);
		$memory_start = memory_get_usage();

		$query = trim($query);
		$query = $this->enforceCreateTableParameters($query);

		$this->setEmulatePrepares($query);

		try
		{
			$result = $this->connection->query($query);
		}
		catch (Exception $e)
		{
			throw new \Exception($e->getMessage().":<br>\n".htmlentities($query, ENT_QUOTES, 'UTF-8'));
		}

		$time_end = microtime(TRUE);
		$memory_end = memory_get_usage();

		if (isset($this->log))
		{
			$this->log->addQuery($query, $time_end-$time_start, $memory_end-$memory_start);
		}

		return $result;
	}

	/**
	 * Escape a value
	 *
	 * @param String $str Value to escape
	 * @return Escaped value
	 */
	public function escape($str)
	{
		if ( ! $this->isOpen())
		{
			$this->open();
		}

		$result = $this->connection->quote($str);

		// todo In future, use quoted value directly. For now, do the
		// yucky thing and remove the quotes.
		return substr($result, 1, -1);
	}

	/**
	 * Get the error message
	 *
	 * @return String Error message
	 */
	public function getErrorMessage()
	{
		$error = $this->connection->errorInfo();
		return $error[2];
	}

	/**
	 * Get the error code
	 *
	 * @return Int Error code
	 */
	public function getErrorNumber()
	{
		return $this->connection->errorCode();
	}

	/**
	 * Get last insert id
	 *
	 * @return Int Last insert id
	 */
	public function getInsertId()
	{
		return $this->connection->lastInsertId();
	}

	/**
	 * Get the pdo object
	 *
	 * @return PDO
	 */
	public function getNative()
	{
		return $this->connection;
	}

	/**
	 * Connection is open?
	 *
	 * @return bool Is Open
	 */
	public function isOpen()
	{
		return isset($this->connection);
	}

	/**
	 * Set emulate prepares to false for SELECT statements so as not to clash
	 * with ATTR_STRINGIFY_FETCHES, but keep it on for all other queries since
	 * some cannot run with it off.
	 */
	private function setEmulatePrepares($query)
	{
		if ($this->mysqlnd)
		{
			$on = strncasecmp($query, 'SELECT', 6) != 0;
			$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, $on);
		}
	}

	/**
	 * Enforce charset and collation on CREATE TABLE queries
	 *
	 * @param String $query Query to check
	 * @return Rewritten query, if necessary
	 */
	private function enforceCreateTableParameters($query)
	{
		if (strncasecmp($query, 'CREATE TABLE', 12) != 0)
		{
			return $query;
		}

		$query = $this->enforceCharsetAndCollation($query);
		$query = $this->addEngineIfNotPresent($query);

		return $query;
	}

	private function enforceCharsetAndCollation($query)
	{
		$charset = $this->config['char_set'];
		$collation = $this->config['dbcollat'];

		$find = '/(DEFAULT\s+)?(CHARACTER\s+SET\s+|CHARSET\s*=\s*)\w+(\s+COLLATE\s+\w+)?/';
		$want = "CHARACTER SET {$charset} COLLATE {$collation}";

		if (preg_match($find, $query))
		{
			$query = preg_replace($find, "\\1".$want, $query);
		}
		else
		{
			$query = rtrim($query, ';');
			$query .= ' '.$want.';';
		}

		return $query;
	}

	private function addEngineIfNotPresent($query)
	{
		$find = '/ENGINE\s*=\s*(\w+)/';
		$want = "ENGINE=InnoDB";

		if ( ! preg_match($find, $query))
		{
			$query = rtrim($query, ';');
			$query .= ' '.$want.';';
		}

		return $query;
	}
}

// EOF
