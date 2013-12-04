<?php

// Report all errors
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

// Path constants
define('PROJECT_BASE',	realpath($dir.'/../../').'/');
define('BASEPATH',		PROJECT_BASE.'codeigniter/system/');
define('APPPATH',		PROJECT_BASE.'expressionengine/');

// Create a test suite autoloader
spl_autoload_register(function($class) {

	if (strpos($class, 'EllisLab\\') === 0)
	{
		$file = __DIR__.'/../../'.str_replace('\\', '/', $class) . '.php';

		if (file_exists($file))
		{
			require_once $file;
		}
	}
});