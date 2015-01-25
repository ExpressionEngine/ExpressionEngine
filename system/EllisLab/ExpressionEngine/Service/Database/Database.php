<?php

namespace EllisLab\ExpressionEngine\Service\Database;

use \EllisLab\ExpressionEngine\Service\Database\DBConfig;

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
 * ExpressionEngine Database Class
 *
 * @package    ExpressionEngine
 * @subpackage Core
 * @category   Core
 * @author     EllisLab Dev Team
 * @link       http://ellislab.com
 */
class Database
{
	protected $config;
	protected $connection;

	/**
	 * Create new Database object
	 *
	 * @param DBConfig $db_config DBConfig object
	 */
	public function __construct(DBConfig $db_config)
	{
		$this->config = $db_config;
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
}
