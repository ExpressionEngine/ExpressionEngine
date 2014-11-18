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
		'active_group'     => 'expressionengine',
		'active_record'    => TRUE,
		'expressionengine' => array(
			'hostname' => 'localhost',
			'username' => 'root',
			'password' => '',
			'database' => '',
			'dbdriver' => 'mysql',
			'pconnect' => FALSE,
			'dbprefix' => 'exp_',
			'swap_pre' => 'exp_',
			'db_debug' => TRUE,
			'cache_on' => FALSE,
			'autoinit' => FALSE,
			'char_set' => 'utf8',
			'dbcollat' => 'utf8_general_ci',
			'cachedir' => "{APPPATH}/cache/db_cache/",
			'stricton' => TRUE,
		)
	);

	public function __construct($path)
	{
		require($path);

		if (isset($db))
		{
			$this->config = array('database' => $db);
		}
		else
		{
			$this->config = $config;
		}
	}

	public function get($name, $group = '', $default = NULL)
	{
		$this->config = $this->config['database'][$this->getActiveGroup()];
		return parent::get($name, $default);
	}

	public function getGroup($group = '')
	{
		$this->config = $this->config['database'];
		$active_group = $this->getActiveGroup($group);

		if ( ! isset($this->config[$active_group]))
		{
			show_error('You have specified an invalid database connection group.');
		}

		return array_merge(
			$this->defaults['expressionengine'],
			parent::get($this->getActiveGroup())
		);
	}

	protected function getActiveGroup($group = '')
	{
		// Figure out the active group
		if ( ! empty($group))
		{
			return $group;
		}
		else if ( ! empty($this->config['database']['active_group']))
		{
			return $this->config['database']['active_group'];
		}
		else
		{
			return $this->defaults['active_group'];
		}
	}
}
