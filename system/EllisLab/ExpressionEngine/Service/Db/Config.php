<?php

namespace EllisLab\ExpressionEngine\Service\Db;

use \EllisLab\ExpressionEngine\Service\Config as ServiceConfig;

/**
*
*/
class Config
{
	private $database;
	private $defaults = array(
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

	public function __construct()
	{
		$config = new ServiceConfig();
		$this->database = $config->item('database');
	}

	public function item($name)
	{

	}


}
