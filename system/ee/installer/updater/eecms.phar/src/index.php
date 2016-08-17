<?php

require_once 'phar://eecms.phar/bootstrap.php';

try
{
	ee('Updater\Runner')->run();
}
catch (\Exception $e)
{
	echo $e->getMessage();
	exit;
}

$updater_boot = SYSPATH.'/ee/updater/boot.php';
if (file_exists($updater_boot))
{
	require_once $updater_boot;

	$runner = new EllisLab\ExpressionEngine\Updater\Service\Updater\Runner();
	$runner->run();
}

echo 'It works!';
