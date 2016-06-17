<?php

namespace EllisLab\ExpressionEngine\Core;

use EllisLab\ExpressionEngine\Legacy\App as LegacyApp;
use EllisLab\ExpressionEngine\Service\Dependency\InjectionContainer;
use EllisLab\ExpressionEngine\Error\FileNotFound;

abstract class Core {

	/**
	 * @var bool Application done booting?
	 */
	protected $booted = FALSE;

	/**
	 * @var bool Application started?
	 */
	protected $running = FALSE;

	/**
	 * Boot the application
	 */
	public function boot()
	{
		$this->setTimeLimit(300);
		$this->bootLegacyApplicationCore();
		$this->booted = TRUE;
	}

	/**
	 * We have a separate object for the old CI way of doing things.
	 * Currently this class mostly delegates to that.
	 */
	public function getLegacyApp()
	{
		if ( ! $this->booted)
		{
			throw new \Exception('Cannot retrieve legacy app before booting.');
		}

		return $this->legacy;
	}

	/**
	 * Override config before running
	 */
	public function overrideConfig(array $config)
	{
		if ( ! $this->booted || $this->running)
		{
			throw new \Exception('Config overrides must happen after booting and before running the application.');
		}

		$this->legacy->overrideConfig($config);
	}

	/**
	 * Override routing before running
	 */
	public function overrideRouting(array $routing)
	{
		if ( ! $this->booted || $this->running)
		{
			throw new \Exception('Routing overrides must happen after booting and before running the application.');
		}

		$this->legacy->overrideRouting($routing);
	}

	/**
	 * Run a given request
	 *
	 * Currently mostly delegates to the legacy app
	 */
	public function run(Request $request)
	{
		if ( ! $this->booted)
		{
			throw new \Exception('Application must be booted before running.');
		}

		$this->running = TRUE;

		$application = $this->loadApplicationCore();

		$routing = $this->getRouting($request);
		$routing = $this->loadController($routing);
		$routing = $this->validateRequest($routing);

		$application->setRequest($request);
		$application->setResponse(new Response());

		$this->runController($routing);

		return $application->getResponse();
	}

	/**
	 * Load a controller given the routing information
	 */
	protected function loadController($routing)
	{
		$this->legacy->includeBaseController();

		$modern_routing = $this->loadNamespacedController($routing);

		if ($modern_routing)
		{
			$routing = $modern_routing;
		}
		elseif ($this->legacy->isLegacyRouted($routing))
		{
			$this->legacy->loadController($routing);
		}

		return $routing;
	}

	protected function loadNamespacedController($routing)
	{
		$RTR = $GLOBALS['RTR'];
		$class  = $RTR->fetch_class(TRUE);
		$method = $RTR->fetch_method();

		// First try a fully namespaced class, with fallback
		if ( ! class_exists($class))
		{
			// If that didn't work try a fallback class matching the directory name
			$old_class = $RTR->fetch_class();
			$old_method = $method;

			$RTR->set_method($RTR->fetch_class());

			$directories = explode('/', rtrim($RTR->fetch_directory(), '/'));
			$RTR->set_class(array_pop($directories));

			$class  = $RTR->fetch_class(TRUE);
			$method = $RTR->fetch_method();
		}

		if ( ! class_exists($class))
		{
			$RTR->set_class($old_class);
			$RTR->set_method($old_method);

			return FALSE;
		}

		$controller_methods = array_map(
			'strtolower', get_class_methods($class)
		);

		// This allows for routes of 'cp/channels/layout/1' to end up calling
		// \EllisLab\ExpressionEngine\Controller\Channels\Layout::layout(1)
		if ( ! in_array($method, $controller_methods)
			&& in_array($RTR->fetch_class(), $controller_methods))
		{
			array_unshift($routing['segments'], $method);
			$method = $RTR->fetch_class();
		}

		$routing['class'] = $class;
		$routing['method'] = $method;

		return $routing;
	}

	/**
	 * Run a controller given the routing information
	 */
	protected function runController($routing)
	{
		$class  = $routing['class'];
		$method = $routing['method'];
		$params = $routing['segments'];

		// set the legacy facade before instantiating
		$class::_setFacade($this->legacy->getFacade());

		$controller_name = substr($class, strpos($class, 'Controller\\') + 11);

		// here we go!
		// Catch anything that might bubble up from inside our app
		try
		{
			$controller = new $class;

			$result = call_user_func_array(array($controller, $method), $params);
		}
		catch (FileNotFound $ex)
		{
			$error_routing = $this->getErrorRouting();

			if ($routing['class'] == $error_routing['class'])
			{
				die('Fatal: Error handler could not be found.');
			}

			return $this->runController($error_routing);
		}
		catch (\Exception $ex)
		{
			show_exception($ex);
		}

		if (isset($result))
		{
			ee('Response')->setBody($result);
		}
	}

	/**
	 * Get the 404 controller
	 */
	protected function getErrorRouting()
	{
		$qs = '';
		$get = $_GET;

		unset($get['D'], $get['C'], $get['M'], $get['S']);

		if ( ! empty($get))
		{
			$qs = '&'.http_build_query($get);
		}

		return array(
			'class' => 'EllisLab\ExpressionEngine\Controller\Error\FileNotFound',
			'method' => 'index',
			'segments' => array(ee()->uri->uri_string().$qs)
		);
	}

	/**
	 * Set an execution time limit
	 */
	public function setTimeLimit($t)
	{
		if (function_exists("set_time_limit") && @ini_get("safe_mode") == 0)
		{
			@set_time_limit($t);
		}
	}

	/**
	 * Setup the application with the default provider
	 */
	protected function loadApplicationCore()
	{
		$autoloader   = Autoloader::getInstance();
		$dependencies = new InjectionContainer();
		$providers    = new ProviderRegistry($dependencies);
		$application  = new Application($autoloader, $dependencies, $providers);

		$provider = $application->addProvider(
			SYSPATH.'ee/EllisLab/ExpressionEngine',
			'app.setup.php',
			'ee'
		);

		$provider->setConfigPath($this->getConfigPath());

		$dependencies->register('App', function($di, $prefix = NULL) use ($application)
		{
			if (isset($prefix))
			{
				return $application->get($prefix);
			}

			return $application;
		});

		$this->legacy->getFacade()->set('di', $dependencies);

		return $application;
	}


	/**
	 * Retrieve the config path for this core
	 * @return string Config path
	 */
	protected function getConfigPath()
	{
		return SYSPATH.'user/config';
	}

	/**
	 * Boot the legacy application including all of the CI globals
	 */
	protected function bootLegacyApplicationCore()
	{
		$this->legacy = new LegacyApp();
		$this->legacy->boot();
	}

	/**
	 * Get the routing for a request. Smoke and mirrors.
	 */
	protected function getRouting($request)
	{
		return $this->legacy->getRouting();
	}

	/**
	 * Validate the request
	 */
	protected function validateRequest($routing)
	{
		$routing = $this->legacy->validateRequest($routing);

		if ($routing === FALSE)
		{
			return $this->getErrorRouting();
		}

		return $routing;
	}
}

// EOF
