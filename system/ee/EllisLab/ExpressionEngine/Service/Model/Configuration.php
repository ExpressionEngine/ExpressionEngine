<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Model;

/**
 * Model Service Configuration
 */
class Configuration {

	/**
	 * @var String Default model prefix (usually 'ee')
	 */
	private $default_prefix;

	/**
	 * @var Array of valid prefixes
	 */
	private $prefixes = array();

	/**
	 * @var Array of model dependencies [modelname => depends]
	 */
	private $dependencies = array();

	/**
	 * @var Array of model class name aliases [alias => classname]
	 */
	private $aliases = array();


	/**
	 * Set default prefix
	 */
	public function setDefaultPrefix($prefix)
	{
		$this->default_prefix = $prefix;
	}

	/**
	 * Get default prefix
	 */
	public function getDefaultPrefix()
	{
		return $this->default_prefix;
	}

	/**
	 * Set enabled prefixes
	 */
	public function setEnabledPrefixes(array $prefixes)
	{
		$this->prefixes = $prefixes;
	}

	/**
	 * Get enabled prefixes
	 */
	public function getEnabledPrefixes()
	{
		return $this->prefixes;
	}

	/**
	 * Check for an enabled prefix
	 */
	public function isEnabledPrefix($prefix)
	{
		return in_array($prefix, $this->prefixes);
	}

	/**
	 * Set model dependencies
	 */
	public function setModelDependencies(array $dependencies)
	{
		$this->dependencies = $dependencies;
	}

	/**
	 * Get model dependencies
	 */
	public function getModelDependencies()
	{
		return $this->dependencies;
	}

	/**
	 * Set model aliases
	 */
	public function setModelAliases(array $aliases)
	{
		$this->aliases = $aliases;
	}

	/**
	 * Get model aliases
	 */
	public function getModelAliases()
	{
		return $this->aliases;
	}
}
