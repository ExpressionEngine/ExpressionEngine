<?php

require_once 'phar://eecms.phar/bootstrap.php';

$project_base = realpath('../').'/';

// TODO: Add some test to make sure the CLI file hasn't been moved out of system/ee and complain if it has

// Path constants
define('SELF', basename(__FILE__));
define('SYSPATH', $project_base);
define('SYSDIR', basename($project_base));
define('DEBUG', 1);

$args = parseArguments();

if ( ! isset($args['--no-bootstrap']))
{
	// Currently needed for installer conditional in boot.php
	if ( ! defined('REQ'))
	{
		define('REQ', 'CP');
	}

	$bootstrap = SYSPATH.'ee/EllisLab/ExpressionEngine/Boot/boot.php';
	if (file_exists($bootstrap))
	{
		define('BOOT_ONLY', TRUE);
		require_once $bootstrap;
	}
}

$supported_commands = ['upgrade'];

if (isset($args[0]) && in_array($args[0], $supported_commands))
{
	$command = array_shift($args);

	try
	{
		require_once 'phar://eecms.phar/'.$command.'.php';
		new Command($args);
	}
	catch (\Exception $e)
	{
		echo $e->getMessage();
		exit;
	}
}
else
{
	exit('show help');
}
