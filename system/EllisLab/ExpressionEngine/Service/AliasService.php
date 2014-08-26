<?php
namespace EllisLab\ExpressionEngine\Core;

class AliasService implements AliasServiceInterface {

	/**
	 * Used for exception texts to help locate the class.
	 */
	protected $identifier = 'Class';
	protected $aliases = array();

	public function __construct($identifier, $aliases_path)
	{
		$this->identifier = $identifier;

		$this->aliases = require_once($aliases_path);
	}

	/**
	 * Register a class under a given alias.
	 *
	 * @param String $alias  Name to use when interacting with the service
	 * @param String $fully_qualified_name  Fully qualified class name of the aliased class
	 * @return void
	 */
	public function registerClass($class_name, $fully_qualified_name)
	{
		if (array_key_exists($alias, $this->aliases))
		{
			throw new \OverflowException($this->identifier.' name has already been registered: '. $class_name);
		}

		$this->aliases[$alias] = $fully_qualified_name;
	}

	/**
	 * Get an alias's full qualified name.
	 *
	 * @param String $name Name of the class
	 * @return String Fully qualified name of the class
	 */
	public function getRegisteredClass($class_name)
	{
		if ( ! array_key_exists($class_name, $this->aliases))
		{
			throw new \UnderflowException($this->identifier.' "' . $class_name . '" has not been registered yet!');
		}
		return $this->aliases[$class_name];
	}

	/**
	 * Take a registered class and alias it with a global name.
	 *
	 * @param String $name Name of the model
	 * @return String Fully qualified name of the class
	 */
	public function createGlobalAlias($registered_name, $facade_name)
	{
		$class = $this->getRegisteredClass($registered_name);

		class_alias($class, $facade_name, TRUE);
	}
}
