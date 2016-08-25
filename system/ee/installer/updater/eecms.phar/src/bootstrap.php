<?php

$project_base = realpath('../').'/';

// TODO: Add some test to make sure the CLI file hasn't been moved out of system/ee and complain if it has

// Path constants
define('SYSPATH', $project_base);
define('BASEPATH', SYSPATH.'ee/legacy/');
define('APPPATH',  BASEPATH);
define('PATH_THIRD',  SYSPATH.'user/addons/');
define('PATH_ADDONS',  SYSPATH.'ee/EllisLab/Addons/');
define('PATH_CACHE',  SYSPATH . 'user/cache/');

define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

require_once SYSPATH.'ee/EllisLab/ExpressionEngine/Boot/boot.common.php';

define('LD', '{');
define('RD', '}');

define('IS_CORE', FALSE);
define('DEBUG', 1);
define('FIXTURE', TRUE);


require SYSPATH."ee/EllisLab/ExpressionEngine/Core/Autoloader.php";

$autoloader = EllisLab\ExpressionEngine\Core\Autoloader::getInstance()
	->addPrefix('EllisLab', SYSPATH.'ee/EllisLab/');
$autoloader->register();

$di = new EllisLab\ExpressionEngine\Service\Dependency\InjectionContainer();
$reg = new EllisLab\ExpressionEngine\Core\ProviderRegistry($di);
$app = new EllisLab\ExpressionEngine\Core\Application(
	$autoloader,
	$di,
	$reg
);

$provider = $app->addProvider(
	SYSPATH.'ee/EllisLab/ExpressionEngine',
	'app.setup.php',
	'ee'
);

$provider->setConfigPath(SYSPATH.'user/config');

$di->register('App', function($di, $prefix = NULL) use ($app)
{
	if (isset($prefix))
	{
		return $app->get($prefix);
	}

	return $app;
});

function ee($dep = NULL)
{
	if (isset($dep))
	{
		global $di;
		return call_user_func_array(array($di, 'make'), func_get_args());
	}
	static $EE;
	if ( ! $EE)	$EE = new stdClass();
	return $EE;
}


function get_instance()
{
	return ee();
}

ee()->di = $di;

// DB Stuff
require_once(BASEPATH.'database/DB.php');
ee()->db = DB('', NULL);

// EOF
