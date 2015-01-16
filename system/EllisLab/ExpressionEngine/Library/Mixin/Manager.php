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
		$this->scope = $scope;
		$this->mixins = $mixins;
	}

	/**
	 * Add another receiver to the call. This lets us do more
	 * advanced composition. For example model columns are a
	 * mixin whose objects are also mixable by nature of being
	 * entities.
	 *
	 * @param Mixable $receiver Object to forward to.
	 */
	public function forward(Mixable $receiver)
	{
		$this->forwarded[] = $receiver;
	}

	/**
	 * Call a function on the aggregate of all mixins as well as
	 * all other mixables.
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
		$result = $this->runMixins($fn, $args);

		$this->runForwarded($fn, $args);

		return $result;
	}

	/**
	 * Run a function on all mixins
	 */
	protected function runMixins($fn, $args)
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

		return $return;
	}

	/**
	 * Run a function on all forwarded mixables
	 */
	protected function runForwarded($fn, $args)
	{
		foreach ($this->forwarded as $receiver)
		{
			$callable = array($receiver, $fn);

			if (is_callable($callable))
			{
				call_user_func_array($callable, $args);
			}
		}
	}

	/**
	 * Helper function to create mixin objects
	 */
	protected function getMixinObject($class)
	{
		if ( ! isset($this->instances[$class]))
		{
			$mixin = new $class($this->scope);
			$mixin->setMixinManager($this);

			$this->instances[$class] = $mixin;
		}

		return $this->instances[$class];
	}
}