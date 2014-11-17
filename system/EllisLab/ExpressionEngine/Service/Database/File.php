<?php

namespace EllisLab\ExpressionEngine\Service\Database;

use \EllisLab\ExpressionEngine\Service\Config\File as ConfigFile;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Database Config File Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class File extends ConfigFile
{
	protected $defaults = array(
		'active_group'  => 'expressionengine',
		'active_record' => TRUE,
		'hostname'      => 'localhost',
		'username'      => 'root',
		'password'      => '',
		'database'      => 'expressionengine',
		'dbdriver'      => 'mysql',
		'pconnect'      => FALSE,
		'dbprefix'      => 'exp_',
		'swap_pre'      => 'exp_',
		'db_debug'      => TRUE,
		'cache_on'      => FALSE,
		'autoinit'      => FALSE,
		'char_set'      => 'utf8',
		'dbcollat'      => 'utf8_general_ci',
		'cachedir'      => '/Users/wes/Development/expressionengine/system/expressionengine/cache/db_cache/',
		'stricton'      => TRUE,
	);

	public function get($name, $default = NULL)
	{
		$active_group = $this->config['database']['active_group'] ?: $defaults['active_group'];
		$this->config = $this->config['database'][$active_group];

		return parent::get($name, $default);
	}
}
