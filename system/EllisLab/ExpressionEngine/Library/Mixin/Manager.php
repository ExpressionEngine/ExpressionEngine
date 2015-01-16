<?php

namespace EllisLab\ExpressionEngine\Library\Mixin;

class Manager {

	protected $scope;
	protected $mixins = array();
	protected $instances = array();
	protected $forwarded = array();

	/**
	 * @param array $mixins List of class names
	 */
	public function __construct($scope, $mixins)
	{
		$this->mixins = $mixins;
	}

	/**
	 * Add another receiver to the call. This lets us do more
	 * advanced composition. For example model columns are a
	 * mixin whose objects are also mixable by nature of being
	 * entities.
	 *
	 * @param Mixable $receiver Object to forward to. TODO pull out the manager and just call on that?
	 */
	public function forward(Mixable $receiver)
	{
		$this->forwarded[] = $receiver;
	}

	/**
	 * Call a function on any mixin that might implement it.
	 *
	 * It's generally not a good idea to rely on return values, but
	 * if you must the value will be the last mixin called that is
	 * not null.
	 *
	 * @param String $fn Method name
	 * @param Array $args List of arguments
	 * @return Mixed last non-null result, or null if no results
	 */
	public function call($fn, $args)
	{
		$return = NULL;

		foreach ($this->mixins as $class)
		{
			$obj = $this->getMixinObject($class);

			$callable = array($obj, $fn);

			if (is_callable($callable))
			{
				$return = call_user_func_array($callable, $args) ?: $return;
			}
		}

		foreach ($this->forwarded as $receiver)
		{
			call_user_func_array(array($receiver, $fn), $args);
		}

		return $return;
	}

	/**
	 * Helper function to create mixin objects
	 */
	protected function getMixinObject($class)
	{
		if ( ! isset($this->instances[$class]))
		{
			$this->instances[$name] = new $class($this->scope);
		}

		return $this->instances[$name];
	}
}