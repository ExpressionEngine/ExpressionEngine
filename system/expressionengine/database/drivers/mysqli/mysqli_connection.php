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
 * Note: _DB is an extender class that the app controller
 * creates dynamically based on whether the active record
 * class is being used or not.
 *
 * @package		CodeIgniter
 * @subpackage	Drivers
 * @category	Database
 * @author		EllisLab Dev Team
 * @link		http://codeigniter.com/user_guide/database/
 */
class CI_DB_mysqli_connection {

	protected $connection;
	protected $config;

	public function __construct($config)
	{
		if ( ! isset($config['port']))
		{
			$config['port'] = NULL;
		}

		$this->config = $config;
	}

	public function getConfig()
	{
		return $this->config;
	}

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

		$this->connection = new PDO(
			$dsn,
			$username,
			$password,
			$options
		);
	}

	public function close()
	{
		$this->connection = NULL;
	}

	public function query($query)
	{
		return $this->connection->query($query);
	}

	public function escape($str)
	{
		if ( ! $this->isOpen())
		{
			$this->open();
		}

		// todo In future, use quoted value directly. Yuck.
		$result = $this->connection->quote($str);

		return substr($result, 1, -1);
	}

	public function getErrorMessage()
	{
		$error = $this->connection->errorInfo();
		return $error[2];
	}

	public function getErrorNumber()
	{
		return $this->connection->errorCode();
	}

	public function getInsertId()
	{
		return $this->connection->lastInsertId();
	}

	public function getAffectedRows()
	{
		return $this->connection->rowCount();
	}

	public function getNative()
	{
		return $this->connection;
	}

	public function isOpen()
	{
		return isset($this->connection);
	}
/*
	public function setCharset($charset, $collation)
	{
		$version = $this->connection->server_info;

		// mysqli::set_charset() requires MySQL >= 5.0.7, use SET NAMES as fallback
		if (version_compare($version, '5.0.7', '>='))
		{
			$this->connection->set_charset($charset);
		}
		else
		{
			$this->query("SET NAMES '".$this->escape($charset)."' COLLATE '".$this->escape($collation)."'");
		}
	}
	*/
}
