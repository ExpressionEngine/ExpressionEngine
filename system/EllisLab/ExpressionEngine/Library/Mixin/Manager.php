<?php

namespace EllisLab\ExpressionEngine\Library\Mixin;

class Manager {

	protected $scope;
	protected $mixins = array();
	protected $instances = array();
	protected $forwarded = array();

	protected $mounted = FALSE;

	/**
	 * @param Object $scope Object to mix into
	 */
	public function __construct($scope)
	{
		$this->scope = $scope;
	}

	/**
	 * @param array $mixins List of class names
	 */
	public function setMixins($mixins)
	{
		$this->mixins = $mixins;
		$this->mountMixins();
	}

	/**
	 * Boot the mixin objects and collect their names
	 */
	public function mountMixins()
	{
		foreach ($this->mixins as $class)
		{
			$this->createMixinObject($class);
		}

		$this->mounted = TRUE;
	}

	/**
	 * Check if a given mixin was mounted on the current scope.
	 *
	 * @param String $name  Mixin name as exposed by getName()
	 * @return Bool Mixin mounted
	 */
	public function hasMixin($name)
	{
		if ( ! $this->mounted)
		{
			throw new \Exception('Mixins not mounted. Cannot check if mixin exists.');
		}

		return array_key_exists($name, $this->instances);
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
	 *
	 * @param String $fn Function name
	 * @param Array $args Arguments to pass to the method
	 * @return Last return value [or NULL].
	 */
	protected function runMixins($fn, $args)
	{
		$return = NULL;

		foreach ($this->instances as $obj)
		{
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
	 *
	 * @param String $fn Function name
	 * @param Array $args Arguments to pass to the method
	 * @return void
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
	 *
	 * @param String $class Class name
	 */
	protected function createMixinObject($class)
	{
		$obj = new $class($this->scope, $this);
		$this->instances[$obj->getName()] = $obj;
	}
}