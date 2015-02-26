<?php

namespace EllisLab\ExpressionEngine\Service\Database;

require_once BASEPATH."database/drivers/mysqli/mysqli_connection.php";

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Database Connection
 *
 * @package		ExpressionEngine
 * @subpackage	Database\Connection
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Connection extends \CI_DB_mysqli_connection {

	protected static $legacy_loaded = FALSE;

	protected $log;

	/**
	 *
	 */
	public function __construct($config)
	{
		parent::__construct($this->parseConfig($config));
	}

	/**
	 *
	 */
	public function getLog()
	{
		return $this->log;
	}

	/**
	 *
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
