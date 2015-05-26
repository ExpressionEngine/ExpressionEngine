<?php

namespace EllisLab\ExpressionEngine\Core;

use EllisLab\ExpressionEngine\Service\Dependency\InjectionBindingDecorator;
use FilesystemIterator;

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
	 *
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
	 *
	 */
	public function get($prefix)
	{
		if ( ! array_key_exists($prefix, $this->providers))
		{
			throw new \Exception("Unknown prefix: '{$prefix}'");
		}

		return $this->providers[$prefix];
	}

	/**
	 *
	 */
	public function all()
	{
		return $this->providers;
	}
}