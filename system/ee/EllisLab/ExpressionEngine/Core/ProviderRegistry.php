<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Core;

use EllisLab\ExpressionEngine\Service\Dependency\InjectionBindingDecorator;
use FilesystemIterator;

/**
 * Core Provider Registry
 */
class ProviderRegistry {

	protected $dependencies;
	protected $providers = array();

	/**
	 *
	 */
	public function __construct($dependencies)
	{
		$this->dependencies = $dependencies;
	}

	/**
	 * Register a new provider
	 *
	 * @param String $prefix Prefix to use
	 * @param Provider $provider Provider object
	 */
	public function register($prefix, Provider $provider)
	{
		if (array_key_exists($prefix, $this->providers))
		{
			throw new \Exception("Addon of name {$prefix} already registered.");
		}

		$this->providers[$prefix] = $provider;
	}

	/**
	 * Has a given prefix?
	 *
	 * @param String $prefix Prefix to look for
	 * @return bool
	 */
	public function has($prefix)
	{
		return array_key_exists($prefix, $this->providers);
	}

	/**
	 * Get a given prefix
	 *
	 * @param String $prefix Prefix to look for
	 * @return Provider
	 */
	public function get($prefix)
	{
		if ( ! $this->has($prefix))
		{
			throw new \Exception("Unknown prefix: '{$prefix}'");
		}

		return $this->providers[$prefix];
	}

	/**
	 * Get all providers
	 *
	 * @return array [prefix => Provider]
	 */
	public function all()
	{
		return $this->providers;
	}
}

// EOF
