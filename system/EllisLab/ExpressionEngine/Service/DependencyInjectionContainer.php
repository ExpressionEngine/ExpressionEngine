<?php
namespace EllisLab\ExpressionEngine\Service;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use Closure;
use StdClass;

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
	protected $singletonRegistry = array();

	/**
	 * Registers a dependency with the container
	 *
	 * @param string      $name   The name of the dependency in the form
	 *                            Vendor:Namespace
	 * @param Closure|obj $object The object to use
	 * @param bool        $temp   Is this a temporary assignment?
	 * @return void
	 */
	public function register($name, $object, $temp = FALSE)
	{
		if (strpos($name, ':') === FALSE)
		{
			$name = 'EllisLab:' . $name;
		}

		if ($temp)
		{
			$registry =& $this->substitutes;
		}
		else
		{
			$registry =& $this->registry;
		}

		if ( isset($registry[$name]))
		{
			throw \Exception('Attempt to reregister existing class' . $name);
		}

		$registry[$name] = $object;
	}

	/**
	 * Temporarily bind a dependency. Calls $this->register with $temp as TRUE
	 *
	 * @param string      $name   The name of the dependency in the form
	 *                            Vendor:Namespace
	 * @param Closure|obj $object The object to use
	 * @return obj Returns this DependencyInjectionContainer object
	 */
	public function bind($name, $object)
	{
		$this->register($name, $object, TRUE);
		return $this;
	}

	public function singleton(Closure $object)
	{
	    $hash = spl_object_hash($object);

	    if ( ! isset($this->singletonRegistry[$hash]))
	    {
	        $this->singletonRegistry[$hash] = $object($this);
	    }

	    return $this->singletonRegistry[$hash];
	}

	/**
	 * Make an instance of a Service
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
	public function make()
	{
		$arguments = func_get_args();

		$name = array_shift($arguments);

		if (strpos($name, ':') === FALSE)
		{
			$name = 'EllisLab:' . $name;
		}

		if ( isset($this->substitutes) && isset($this->substitutes[$name]))
		{
			$object = $this->substitutes[$name];
		}
		else
		{
			if ( ! isset($this->registry[$name]))
			{
				throw new \RuntimeException('Attempt to access unregistered service ' . $name . ' in the DIC.');
			}
			else
			{
				$object = $this->registry[$name];
			}
		}

		$this->substitutes = NULL;

		if ($object instanceof Closure)
		{
			array_unshift($arguments, $this);
			return call_user_func_array($object, $arguments);
		}

		return $object;
	}

}
// END CLASS

/* End of file DependencyInjectionContainer.php */
/* Location: ./system/EllisLab/ExpressionEngine/Service/DependencyInjectionContainer.php */