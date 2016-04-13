<?php

/*
 * ------------------------------------------------------
 *  BASEPATH - path to the legacy app folder. Most legacy
 *             files check for this (`if ! defined ...`)
 * ------------------------------------------------------
 */
	define('BASEPATH', SYSPATH.'ee/legacy/');

/*
 * ------------------------------------------------------
 *  Load the autoloader and register it
 * ------------------------------------------------------
 */

	require SYSPATH.'ee/updater/EllisLab/ExpressionEngine/Core/Autoloader.php';

	EllisLab\ExpressionEngine\Core\Autoloader::getInstance()
		->addPrefix('EllisLab', SYSPATH.'ee/updater/EllisLab/')
		->register();

/*
 * ------------------------------------------------------
 *  Route the request to a controller
 * ------------------------------------------------------
 */

	if (php_sapi_name() != 'cli')
	{
		routeRequest();
	}

	// For scoping
	function routeRequest()
	{
		if (isset($_GET['D']) && isset($_GET['C']))
		{
			$class = 'EllisLab\ExpressionEngine\Controller\\'.ucfirst($_GET['D']).'\\'.ucfirst($_GET['C']);

			if (class_exists($class))
			{
				$controller_methods = array_map(
					'strtolower', get_class_methods($class)
				);

				if (isset($_GET['M']) && in_array($_GET['M'], $controller_methods))
				{
					$controller = new $class;
					$method = $_GET['M'];

					echo $controller->$method();
				}
			}
		}
	}
