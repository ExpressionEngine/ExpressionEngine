<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.2.4 or newer
 *
 * @package		CodeIgniter
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2016, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * MySQLi Database Adapter Class
 *
 * @package		CodeIgniter
 * @subpackage	Drivers
 * @category	Database
 * @author		EllisLab Dev Team
 * @link		http://codeigniter.com/user_guide/database/
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
	 * Create a conneciton
	 *
	 * @param Array $config Config values
	 */
	public function __construct($config)
	{
		$this->config = $config;
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

		$options = array(
			PDO::ATTR_PERSISTENT => $pconnect,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_CASE => PDO::CASE_NATURAL,
			PDO::ATTR_STRINGIFY_FETCHES => FALSE
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
		$on = strncasecmp($query, 'SELECT', 6) != 0;
		$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, $on);
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
		$want = "DEFAULT CHARACTER SET {$charset} COLLATE {$collation}";

		if (preg_match($find, $query))
		{
			$query = preg_replace($find, $want, $query);
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
