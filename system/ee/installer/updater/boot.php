<?php

// TODO: We need to figure out how to include the latest boot.common helper functions
// after the old ones were already included in the CLI bootstrap
if ( ! function_exists('is_php'))
{
	require __DIR__.'/EllisLab/ExpressionEngine/Updater/Boot/boot.common.php';
}

/*
 * ------------------------------------------------------
 *  Constants
 * ------------------------------------------------------
 */
	if ( ! defined('BASEPATH') && file_exists($autoloader_path = SYSPATH . 'ee/EllisLab/ExpressionEngine/Core/Autoloader.php'))
	{
		define('BASEPATH', SYSPATH.'ee/legacy/');
		define('FILE_READ_MODE', 0644);
		define('FILE_WRITE_MODE', 0666);
		define('DIR_READ_MODE', 0755);
		define('DIR_WRITE_MODE', 0777);

		define('APPPATH',  BASEPATH);
		define('PATH_THIRD',  SYSPATH.'user/addons/');
		define('PATH_ADDONS',  SYSPATH.'ee/EllisLab/Addons/');
		define('PATH_CACHE',  SYSPATH . 'user/cache/');

		define('LD', '{');
		define('RD', '}');

		define('IS_CORE', FALSE);
		define('FIXTURE', TRUE);

		require $autoloader_path;

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
	}

/*
 * ------------------------------------------------------
 *  Load the autoloader and register it
 * ------------------------------------------------------
 */

	require SYSPATH.'ee/updater/EllisLab/ExpressionEngine/Updater/Core/Autoloader.php';

	EllisLab\ExpressionEngine\Updater\Core\Autoloader::getInstance()
		->addPrefix('EllisLab', SYSPATH.'ee/updater/EllisLab/')
		->register();

/*
 * ------------------------------------------------------
 *  Route the request to a controller
 * ------------------------------------------------------
 */

	if (php_sapi_name() != 'cli')
	{
		$directory = (isset($_GET['D']) && $_GET['D'] !== 'cp') ? $_GET['D'] : 'updater';
		$controller = (isset($_GET['C'])) ? $_GET['C'] : 'updater';
		$method = (isset($_GET['M'])) ? $_GET['M'] : 'index';
		routeRequest($directory, $controller, $method);
	}

	function routeRequest($directory, $controller, $method = '')
	{
		$class = 'EllisLab\ExpressionEngine\Updater\Controller\\'.ucfirst($directory).'\\'.ucfirst($controller);

		if (class_exists($class))
		{
			$controller_methods = array_map(
				'strtolower', get_class_methods($class)
			);

			if ( ! empty($method) && in_array($method, $controller_methods))
			{
				$controller_object = new $class;

				echo $controller_object->$method();
			}
		}
	}
