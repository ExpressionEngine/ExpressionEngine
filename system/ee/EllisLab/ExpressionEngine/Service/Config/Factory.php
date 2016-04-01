<?php

namespace EllisLab\ExpressionEngine\Service\Config;

use EllisLab\ExpressionEngine\Core\Provider;

class Factory {

	/**
	 * @var EllisLab\ExpressionEngine\Core\Provider
	 */
	protected $provider;

	/**
	 * @var Array of cached directories
	 */
	protected $directories = array();

	/**
	 * Constructor
	 *
	 * @param Provider $provider The default provider for config items
	 */
	public function __construct(Provider $provider)
	{
		$this->provider = $provider;
	}

	/**
	 * Get a config directory
	 *
	 * @param string $path The path to the directory
	 * @return obj Returns a Directory object
	 */
	public function getDirectory($path)
	{
		$path = realpath($path);

		if ( ! array_key_exists($path, $this->directories))
		{
			$this->directories[$path] = new Directory($path);
		}

		return $this->directories[$path];
	}

	/**
	 * Get a config file
	 *
	 * @param String $name Config file name, optionally with a provider prefix
	 * @return Object File The config file
	 */
	public function getFile($name = 'config')
	{
		list($directory, $name) = $this->expandPrefixToDirectory($name);

		return $directory->getFile($name);
	}

	/**
	 * Get a config item
	 *
	 * @param String $name Config item name, optionally with a provider prefix
	 * @param Mixed  $default The value to return if $path can not be found
	 * @return Mixed The config item, or `$default` if it doesn't exist
	 */
	public function get($item, $default = NULL)
	{
		list($directory, $item) = $this->expandPrefixToDirectory($item);

		return $directory->get($item, $default);
	}

	/**
	 * Take a prefixed item and figure out what directory that provider
	 * should be looking at.
	 *
	 * @param String $item Config item name, prefixes allowed (e.g. "rte:item")
	 * @return Array [Directory, un-prefixed item]
	 */
	private function expandPrefixToDirectory($item)
	{
		$provider = $this->provider;

		if (strpos($item, ':'))
		{
			list($prefix, $item) = explode(':', $item, 2);
			$provider = $provider->make('App')->get($prefix);
		}

		$directory = $this->getDirectory($provider->getConfigPath());

		return array($directory, $item);
	}
}

// EOF
