<?php

$project_base = realpath('../').'/';

// TODO: Add some test to make sure the CLI file hasn't been moved out of system/ee and complain if it has

// Path constants
define('SYSPATH', $project_base);
define('DEBUG', 1);

if (isset($argv[1]))
{
	switch ($argv[1]) {
		case 'update':
		case 'upgrade':
		case '_updateDatabase':
			$bootstrap = SYSPATH.'ee/EllisLab/ExpressionEngine/Boot/boot.php';
			if (file_exists($bootstrap))
			{
				define('BOOT_ONLY', TRUE);
				require_once $bootstrap;
			}
			break;
		case '_updaterMicroapp':
		case '_rollback':
			$updater_boot = SYSPATH.'/ee/updater/boot.php';
			if (file_exists($updater_boot))
			{
				require_once $updater_boot;
			}
			break;
		default:
			# code...
			break;
	}

	switch ($argv[1]) {
		case 'update':
		case 'upgrade':
			_beginUpgrade();
			break;
		case '_updaterMicroapp':
			_updaterMicroapp();
			break;
		case '_updateDatabase':
			_updateDatabase();
			break;
		case '_rollback';
			_rollback();
			break;
		default:
			# code...
			break;
	}
}

function _beginUpgrade()
{
	try
	{
		ee('Updater/Runner')->run();
	}
	catch (\Exception $e)
	{
		echo $e->getMessage();
		exit;
	}
	echo system('php eecms.phar _updaterMicroapp');
}

function _updaterMicroapp()
{
	$runner = new EllisLab\ExpressionEngine\Updater\Service\Updater\Runner();

	while (strpos($runner->getNextStep(), 'updateDatabase') === FALSE && $runner->getNextStep() !== FALSE)
	{
		$runner->runStep($runner->getNextStep());
	}

	echo system('php eecms.phar _updateDatabase');
}

function _updateDatabase()
{
	// TODO: We still need access to the updater autoloader here
	$runner = new EllisLab\ExpressionEngine\Updater\Service\Updater\Runner();

	$return = $runner->runStep('updateDatabase');

	while ($return != 'rollback')
	{
		$return = $runner->runStep($return);
	}

	echo 'finished db updates';
	echo system('php eecms.phar _rollback');
}

function _rollback()
{
	$runner = new EllisLab\ExpressionEngine\Updater\Service\Updater\Runner();
	$runner->runStep('rollback');
}
