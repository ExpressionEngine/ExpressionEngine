<?php
namespace EllisLab\ExpressionEngine\Service;

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
 * ExpressionEngine Alias Service
 *
 * Manages class aliases for easy use in the model factory or in service
 * loaders.
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
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
	public function registerClass($alias, $fully_qualified_name)
	{
		if (array_key_exists($alias, $this->aliases))
		{
			throw new \OverflowException($this->identifier.' name has already been registered: '. $alias);
		}

		$this->aliases[$alias] = $fully_qualified_name;
	}

	/**
	 * Get an alias's full qualified name.
	 *
	 * @param String $alias Name of the alias
	 * @return String Fully qualified name of the class
	 */
	public function getRegisteredClass($alias)
	{
		if ( ! array_key_exists($alias, $this->aliases))
		{
			throw new \UnderflowException($this->identifier.' "' . $alias . '" has not been registered yet!');
		}

		return $this->aliases[$alias];
	}

	/**
	 * Reverse alias lookup
	 */
	public function getAlias($class_name)
	{
		$found = array_search($class_name, $this->aliases);
		return ($found == FALSE) ? NULL : $found;
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
