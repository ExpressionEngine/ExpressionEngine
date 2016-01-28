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
