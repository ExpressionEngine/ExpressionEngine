<?php

$project_base = realpath('../').'/';

// Path constants
define('SYSPATH', $project_base);
define('BASEPATH', SYSPATH.'ee/legacy/');
define('APPPATH',  BASEPATH);
define('PATH_THIRD',  SYSPATH.'user/addons/');
define('PATH_ADDONS',  SYSPATH.'ee/EllisLab/Addons/');

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
		return $di->make($dep);
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
