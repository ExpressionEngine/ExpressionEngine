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

use EllisLab\ExpressionEngine\Service\Dependency\ServiceProvider;
use FilesystemIterator;

/**
 * Core Application
 */
class Application {

	/**
	 * @var ProviderRegistry
	 */
	protected $registry;

	/**
	 * @var ServiceProvider object
	 */
	protected $dependencies;

	/**
	 * @var Request Current request
	 */
	protected $request;

	/**
	 * @var Response Current response
	 */
	protected $response;

	/**
	 * @param ServiceProvider $dependencies Dependency object for this application
	 * @param ProviderRegistry $registry Application component provider registry
	 */
	public function __construct(Autoloader $autoloader, ServiceProvider $dependencies, ProviderRegistry $registry)
	{
		$this->autoloader = $autoloader;
		$this->dependencies = $dependencies;
		$this->registry = $registry;
	}

	/**
	 *
	 */
	public function setRequest(Request $request)
	{
		$this->request = $request;
	}

	/**
	 *
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 *
	 */
	public function setResponse(Response $response)
	{
		$this->response = $response;
	}

	/**
	 *
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * @param String $path Path to addon folder
	 */
	public function setupAddons($path)
	{
		$folders = new FilesystemIterator($path, FilesystemIterator::UNIX_PATHS);

		foreach ($folders as $item)
		{
			if ($item->isDir())
			{
				$path = $item->getPathname();

				// for now only setup those that define an addon.setup file
				if ( ! file_exists($path.'/addon.setup.php'))
				{
					continue;
				}

				$this->addProvider($path);
			}
		}
	}

	/**
	 * @return ServiceProvider Dependency object
	 */
	public function getDependencies()
	{
		return $this->dependencies;
	}

	/**
	 * Check for a component provider
	 *
	 * @param String $prefix Component name/prefix
	 * @return bool Exists?
	 */
	public function has($prefix)
	{
		return $this->registry->has($prefix);
	}

	/**
	 * Get a component provider
	 *
	 * @param String $prefix Component name/prefix
	 * @return Provider Component provider
	 */
	public function get($prefix)
	{
		return $this->registry->get($prefix);
	}

	/**
	 * Get prefixes
	 *
	 * @return array of all prefixes
	 */
	public function getPrefixes()
	{
		return array_keys($this->registry->all());
	}

	/**
	 * Get namespaces
	 *
	 * @return array [prefix => namespace]
	 */
	public function getNamespaces()
	{
		return $this->forward('getNamespace');
	}

	/**
	 * Get namespaces
	 *
	 * @return array [prefix => product name]
	 */
	public function getProducts()
	{
		return $this->forward('getProduct');
	}

	/**
	 * List vendors
	 *
	 * @return array off vendor names
	 */
	public function getVendors()
	{
		return array_unique(array_keys($this->forward('getVendor')));
	}

	/**
	* Get all providers
	*
	* @return array of all providers [prefix => object]
	*/
	public function getProviders()
	{
		return $this->registry->all();
	}

	/**
	 * Get all models
	 *
	 * @return array [prefix:model-alias => fqcn]
	 */
	public function getModels()
	{
		return $this->forward('getModels');
	}

	/**
	 * @param String $path Root path for the provider namespace
	 * @param String $file Name of the setup file
	 * @param String $prefix Prefix for our service provider [optional]
	 */
	public function addProvider($path, $file = 'addon.setup.php', $prefix = NULL)
	{
		$path = rtrim($path, '/');
		$file = $path.'/'.$file;

		$prefix = $prefix ?: basename($path);

		if ( ! file_exists($file))
		{
			throw new \Exception("Cannot read setup file: {$path}");
		}

		$provider = new Provider(
			$this->dependencies,
			$path,
			require $file
		);

		$provider->setPrefix($prefix);
		$provider->setAutoloader($this->autoloader);

		$this->registry->register($prefix, $provider);

		return $provider;
	}

	/**
	 * Helper function to collect data from all providers
	 *
	 * @param String $method Method to forward to
	 * @return array Array of method results, nested arrays are flattened
	 */
	public function forward($method)
	{
		$result = array();

		foreach ($this->registry->all() as $prefix => $provider)
		{
			$forwarded = $provider->$method();

			if (is_array($forwarded))
			{
				foreach ($forwarded as $key => $value)
				{
					$result[$prefix.':'.$key] = $value;
				}
			}
			else
			{
				$result[$prefix] = $forwarded;
			}
		}

		return $result;
	}
}

// EOF
