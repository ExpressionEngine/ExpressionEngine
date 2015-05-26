<?php

namespace EllisLab\ExpressionEngine\Core;

use EllisLab\ExpressionEngine\Legacy\App as LegacyApp;
use EllisLab\ExpressionEngine\Service\Dependency\InjectionContainer;

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

		$routing = $this->getRouting($request);
		$routing = $this->loadController($routing);
		$routing = $this->validateRequest($routing);

		$application = $this->loadApplicationCore();

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

		// TODO add seth's changes for the "new" way
		if ($modern_routing)
		{
			$routing = $modern_routing;
		}
		else
		{
			$this->legacy->loadController($routing);
		}

		$this->legacy->markBenchmark('loading_time:_base_classes_end');

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

		// This allows for routes of 'cp/channel/layout/1' to end up calling
		// \EllisLab\ExpressionEngine\Controllers\Channel\Layout::layout(1)
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

		$this->legacy->markBenchmark('controller_execution_time_( '.$class.' / '.$method.' )_start');

		// here we go!
		// Catch anything that might bubble up from inside our app
		try
		{
			$controller = new $class;

			call_user_func_array(array($controller, $method), $params);
		}
		catch (\Exception $ex)
		{
			echo $this->formatException($ex);
			die('Fatal Error.');
		}

		$this->legacy->markBenchmark('controller_execution_time_( '.$class.' / '.$method.' )_end');

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
		return $this->legacy->validateRequest($routing);
	}

	/**
	 * Format any exceptions we catch and display a stack trace
	 */
	protected function formatException(\Exception $ex)
	{
		return '<div>
			<h1>Exception Caught</h1>
			<p><strong>' . $ex->getMessage() . '</strong></p>
			<p><em>'  . $ex->getFile() . ':' . $ex->getLine() . '<em></p>
			<p>Stack Trace:
				<pre>' . str_replace('#', "\n#", str_replace(':', ":\n\t\t", $ex->getTraceAsString())) . '</pre>
			</p>
		</div>';
	}
}
