<?php

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

/**
 * Initialize the database
 *
 * @category	Database
 * @author		EllisLab Dev Team
 * @link		http://codeigniter.com/user_guide/database/
 */
function DB($params = NULL)
{
	$database = ee('Database');

	if ( ! empty($params))
	{
		// Manually set the things we need
		$database_config = $database->getConfig();
		$database_config->set('hostname', $params['hostname']);
		$database_config->set('database', $params['database']);
		$database_config->set('username', $params['username']);
		$database_config->set('password', $params['password']);
		$database_config->set('dbprefix', $params['dbprefix']);

		if (isset($params['port']))
		{
			$database_config->set('port', $params['port']);
		}

		$database->setConfig($database_config);
	}

	return $database->newQuery();
}

// EOF
