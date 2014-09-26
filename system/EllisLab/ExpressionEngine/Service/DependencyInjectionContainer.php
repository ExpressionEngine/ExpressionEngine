<?php
namespace EllisLab\ExpressionEngine\Serivce;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Dependency Injection Container
 *
 * A service to track dependencies in other services and act as a service
 * factory and instance container.
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class DependencyInjectionContainer {

	protected $registry = array();
	protected $substitutes = NULL;


	/**
	 * Construct the DIC, optionally initialize it from another DIC's registry.
	 *
	 * @param	DependencyInjectionContainer	$dic	(Optional) If provided, will
	 * 		be used to initialize this DIC.
	 */
	public function __construct(DependencyInjectionContainer $dic = NULL)
	{
		if ( isset ($dic))
		{
			$this->registry = $dic->registry;
		}
	}

	/**
	 * Bootstrap the Dependency Injection Container
	 *
	 * The bootstrap method is used to set up the DIC.  It is here that
	 * available services and their dependencies are defined as well as any
	 * actions that need to be taken at service creation.
	 *
	 * @return NULL
	 */
	public function bootstrap()
	{
		$this->register('EllisLab:AliasService', function($dic, $config_file)
		{
			return new \EllisLab\ExpressionEngine\Service\AliasService($config_file);

		});

		$this->register('EllisLab:Model\Factory', function($dic)
		{
			$validation = $dic->get('EllisLab:Validation\Factory');
			$alias_service = $dic->get('EllisLab:AliasService', APPPATH . 'config/model_aliases.php');

			return new \EllisLab\ExpressionEngine\Service\Model\Factory($validation, $alias_service);
		});

		$this->register('EllisLab:Validation\Factory', function($dic)
		{
			return new \EllisLab\ExpressionEngine\Service\Validation\Factory();

		});
	}

	/**
	 * Register a Service
	 *
	 * Register a service with a callback method to be used when the service is
	 * needed.  The callback method should handle the creation of the service
	 * and take any actions that need to be taken on service creation.
	 *
	 * @param	string	$name	The name by which the service will be
	 * 		referenced.  Should use the convention of
	 * 		'Vendor/Module:Namespace\Partial\Class'. The namespace partial needs to
	 *		 be beneath that modules service directory. For instance, a class in
	 * 		EllisLab\ExpressionEngine\Modules\Member\Service\MyService\Class would
	 * 		be registered as 'EllisLab/Member:MyService\Class'.
	 *
	 * @param	function	$callback	A callback function to be used to instantiate
	 * 		or retrieve an instance of this service.
	 *
	 * @return NULL
	 */
	public function register($name, $callback)
	{
		if (strpos($name, ':') === FALSE)
		{
			throw \Exception('You must include a Vendor or Vendor/Module in your class name.');
		}

		if ( isset($this->registry[$name]))
		{
			throw \Exception('Attempt to reregister existing class' . $name);
		}

		$this->registry[$name] = $callback;
	}

	/**
	 * Get an instance of a Service
	 *
	 * Retrieves an instance of a service from the DIC using the registered
	 * callback methods.
	 *
	 * @param	string	$name	The name of the registered service to be retrieved
	 * 		in format 'Vendor/Module:Namespace\Class'.
	 *
	 * @param	...	(Optional) Any additional arguments the service needs on
	 * 		initialization.
	 *
	 * @throws	RuntimeException	On attempts to access a service that hasn't
	 * 		been registered, will throw a RuntimeException.
	 *
	 * @return	Object	An instance of the service being requested.
	 */
	public function get()
	{
		if ( ! isset($this->registry[$name]))
		{
			throw new \RuntimeException('Attempt to access unregistered service ' . $name . ' in the DIC.');
		}

		$arguments = func_get_args();

		$name = array_shift($arguments);

		if ( isset($this->substitutes) && isset($this->substitutes[$name]))
		{
			$callback = $this->substitutes[$name];
		}
		else
		{
			$callback = $this->registry[$name];
		}

		// If the callback isn't callable, then the registered service is a
		// singleton.  Return it.
		if ( ! is_callable($callback))
		{
			return $callback;
		}

		array_unshift($arguments, $this);
		return call_user_func_array($callback, $arguments);
	}


	/**
	 * Get an Instance of Service, Substitute its Dependencies
	 *
	 * Get's an instance of a Service and substitutes out provided classes for
	 * the service's dependencies.  Useful for populating a service with mocks
	 * for testing.  Also can be used when a module needs to inject its own
	 * version of a high level service into its other services.
	 *
	 * @param	string	$name	The name of the registered service to be retrieved
	 * 		in the format 'Vendor/Module:Namespace\Class'.
	 *
	 * @param	mixed[]	$substitutes	An array of substitutes to switch out for
	 * 		the requested Services depedendencies.  The array must be of the format
	 * 			'Vendor/Module:Namespace\Class' => Substitute
	 * 		The substitute may either be a callback initializing and returning the
	 * 		service or an instance of the service to be used in all cases as a
	 * 		singleton.
	 *
	 * @param	...	(Optional) Any additional arguments the service needs on initialization.
	 *
	 * @return Object	An instance of the requested service.
	 */
	public function getWithSubtitutes()
	{
		$arguments = func_get_args();

		$name = array_shift($arguments);
		$this->subtitutes = array_shift($arguments);

		$callback = $this->registry[$name];
		array_unshift($arguments, $this);
		$result = call_user_func_array($callback, $arguments);
		$this->substitutes = NULL;
		return $result;
	}

}
