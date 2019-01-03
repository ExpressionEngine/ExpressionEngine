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

require_once BASEPATH."database/drivers/mysqli/mysqli_connection.php";

/**
 * Database Connection
 */
class Connection extends \CI_DB_mysqli_connection {

	protected $log;

	/**
	 *
	 */
	public function __construct($config)
	{
		parent::__construct($this->parseConfig($config));
	}

	/**
	 * Gets the log
	 *
	 * @return Log The log
	 */
	public function getLog()
	{
		return $this->log;
	}

	/**
	 * Sets the log
	 *
	 * @param Log $log The log
	 * @return void
	 */
	public function setLog($log)
	{
		$this->log = $log;
	}

	/**
	 *
	 */
	protected function parseConfig($config)
	{
		if ( ! is_string($config))
		{
			return $config;
		}

		// DSNs must have this prototype:
		// $dsn = 'driver://username:password@hostname/database';
		if (($dns = @parse_url($params)) === FALSE)
		{
			throw new \Exception('Invalid DB Connection String');
		}

		$params = array(
			'dbdriver' => 'mysqli',
			'hostname' => (isset($dns['host'])) ? rawurldecode($dns['host']) : '',
			'username' => (isset($dns['user'])) ? rawurldecode($dns['user']) : '',
			'password' => (isset($dns['pass'])) ? rawurldecode($dns['pass']) : '',
			'database' => (isset($dns['path'])) ? rawurldecode(substr($dns['path'], 1)) : ''
		);

		// were additional config items set?
		if (isset($dns['query']))
		{
			parse_str($dns['query'], $extra);

			foreach($extra as $key => $val)
			{
				// booleans please
				if (strtoupper($val) == "TRUE")
				{
					$val = TRUE;
				}
				elseif (strtoupper($val) == "FALSE")
				{
					$val = FALSE;
				}

				$params[$key] = $val;
			}
		}

		return $params;
	}

}

// EOF
