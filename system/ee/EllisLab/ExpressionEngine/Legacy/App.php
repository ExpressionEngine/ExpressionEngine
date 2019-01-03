<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Legacy;

/**
 * Legacy App
 */
class App {

	protected $router_ready = FALSE;

	/**
	 * Boot the legacy application
	 */
	public function boot()
	{
		$this->startBenchmark();
		$this->exposeGlobals();
		$this->aliasClasses();
		$this->overrideRoutingConfig();
	}

	/**
	 * Get the superobject facade
	 */
	public function getFacade()
	{
		if ( ! isset($this->facade))
		{
			$this->facade = new Facade();

			$loader = load_class('Loader', 'core');
			$loader->setFacade($this->facade);
		}


		return $this->facade;
	}

	/**
	 * Override the default config
	 */
	public function overrideConfig(array $config)
	{
		$GLOBALS['CFG']->_assign_to_config($config);
	}

	/**
	 * Override the automatic routing
	 */
	public function overrideRouting(array $routing)
	{
		if ( ! $this->router_ready)
		{
			$GLOBALS['RTR']->_set_routing();
			$this->router_ready = TRUE;
		}

		$GLOBALS['RTR']->_set_overrides($routing);
	}

	/**
	 * Run the router and get back the requested path, method, and
	 * additional segments
	 */
	public function getRouting()
	{
		$URI = $GLOBALS['URI'];
		$RTR = $GLOBALS['RTR'];

		if ( ! $this->router_ready)
		{
			$RTR->_set_routing();
			$this->router_ready = TRUE;
		}

		$directory = $RTR->fetch_directory();
		$class     = $RTR->fetch_class();
		$method    = $RTR->fetch_method();
		$segments  = array_slice($URI->rsegments, 2);

		return compact('directory', 'class', 'method', 'segments');
	}

	/**
	 * Include the controller base classes
	 */
	public function includeBaseController()
	{
		$CFG = $GLOBALS['CFG'];

		require BASEPATH.'core/Controller.php';

		if (file_exists(APPPATH.'core/'.$CFG->item('subclass_prefix').'Controller.php'))
		{
			require APPPATH.'core/'.$CFG->item('subclass_prefix').'Controller.php';
		}
	}

	/**
	 * Attempt to load the requested controller
	 */
	public function loadController($routing)
	{
		if ( ! file_exists(APPPATH.'controllers/'.$routing['directory'].$routing['class'].'.php'))
		{
			show_error('Unable to load the requested controller.');
		}

		require APPPATH.'controllers/'.$routing['directory'].$routing['class'].'.php';
	}

	/**
	 * Returns a list of valid
	 */
	public function isLegacyRouted($routing)
	{
		if (defined('REQ') && constant('REQ') == 'CP')
		{
			if ($routing['class'] == 'wizard')
			{
				return TRUE;
			}

			return (
				$routing['directory'] == 'cp/'
			 && in_array($routing['class'], array('css', 'javascript', 'login'))
			);
		}

		return TRUE;
	}


	/**
	 * Set a benchmark point
	 */
	public function markBenchmark($str)
	{
		$GLOBALS['BM']->mark($str);
	}

	/**
	 * Validate the request
	 *
	 * Ensures that we're not going to call something that doesn't
	 * exist or was marked as pseudo-private.
	 */
	public function validateRequest($routing)
	{
		$class = $routing['class'];
		$method = $routing['method'];

		if (class_exists($class) && strncmp($method, '_', 1) != 0)
		{
			$controller_methods = array_map(
				'strtolower', get_class_methods($class)
			);

			// if there's a _remap method we'll call it, regardless of
			// the method they requested
			if (in_array('_remap', $controller_methods))
			{
				$routing['method'] = '_remap';
				$routing['segments'] = array($method, $routing['segments']);

				return $routing;
			}

			if (in_array(strtolower($method), $controller_methods)
				|| method_exists($class, '__call'))
			{
				return $routing;
			}
		}

		return FALSE;
	}

	/**
	 * Set EE's default routing config
	 */
	protected function overrideRoutingConfig()
	{
		$routing_config = array(
			'directory_trigger'    => 'D',
			'controller_trigger'   => 'C',
			'function_trigger'     => 'M',
			'enable_query_strings' => FALSE
		);

		if (defined('REQ') && REQ == 'CP')
		{
			$routing_config['enable_query_strings'] = TRUE;
		}

		$this->overrideConfig($routing_config);
	}

	/**
	 * Start the benchmark library early
	 */
	protected function startBenchmark()
	{
		$BM = load_class('Benchmark', 'core');
		$BM->mark('total_execution_time_start');
	}

	/**
	 * Expose silly globals
	 */
	protected function exposeGlobals()
	{
		// in php 5.4 $GLOBALS is a JIT variable, so this is
		// technically a performance hit. Yet another reason
		// to ditch it all very soon.
		$GLOBALS['BM']   = load_class('Benchmark', 'core');
		$GLOBALS['CFG']  = load_class('Config', 'core');
		$GLOBALS['UNI']  = load_class('Utf8', 'core');
		$GLOBALS['URI']  = load_class('URI', 'core');
		$GLOBALS['RTR']  = load_class('Router', 'core');
		$GLOBALS['OUT']  = load_class('Output', 'core');
		$GLOBALS['SEC']  = load_class('Security', 'core');
		$GLOBALS['IN']   = load_class('Input', 'core');
		$GLOBALS['LANG'] = load_class('Lang', 'core');
	}

	/**
	 * Alias core classes that were renamed from CI_ to EE_
	 */
	protected function aliasClasses()
	{
		class_alias('EE_Benchmark', 'CI_Benchmark');
		class_alias('EE_Config', 'CI_Config');
		class_alias('EE_Input', 'CI_Input');
		class_alias('EE_Lang', 'CI_Lang');
		class_alias('EE_Output', 'CI_Output');
		class_alias('EE_URI', 'CI_URI');
		class_alias('EE_Utf8', 'CI_Utf8');
		class_alias('EE_Router', 'CI_Router');
		class_alias('EE_Security', 'CI_Security');
	}
}

// EOF
