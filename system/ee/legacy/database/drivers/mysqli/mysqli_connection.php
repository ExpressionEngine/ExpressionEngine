<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.2.4 or newer
 *
 * @package		CodeIgniter
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc.
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
		if ( ! isset($config['port']))
		{
			$config['port'] = NULL;
		}

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
		$port     = $this->config['port'];

		$dsn = "mysql:dbname={$database};host={$hostname};charset={$char_set}";

		$options = array(
			PDO::ATTR_PERSISTENT => $pconnect
		);

		try {
			$this->connection = new PDO(
				$dsn,
				$username,
				$password,
				$options
			);
		}
		catch (\Exception $e)
		{
			throw $e;

			$message = $e->getMessage();

			if ($this->testBadSocket($message))
			{
				$message = $this->getBadSocketMessage($hostname);
			}

			show_error($message);
		}
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

		$result = $this->connection->query($query);

		$time_end = microtime(TRUE);

		if (isset($this->log))
		{
			$this->log->addQuery($query, $time_end-$time_start);
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
	 * Check if the error message might be caused by a bad socket
	 *
	 * @param String $message The error message
	 * @return Bool Is socket error?
	 */
	private function testBadSocket($message)
	{
		return strpos($message, "SQLSTATE[HY000] [2002] No such file or directory") !== FALSE;
	}

	/**
	 * Generate a message for when the socket connection fails.
	 *
	 * @param String $hostname Connection hostname
	 * @return String Human error message
	 */
	private function getBadSocketMessage($hostname)
	{
		$message =  "Database Connection Error: Could not find socket: '{$hostname}'. ";

		if ($hostname == 'localhost')
		{
			$message .= "Try using '127.0.0.1' instead.";
		}
		else
		{
			$message .= "Try connecting with an IP address.";
		}

		return $message;
	}

}
