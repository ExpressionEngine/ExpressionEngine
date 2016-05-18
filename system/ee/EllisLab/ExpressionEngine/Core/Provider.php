<?php

namespace EllisLab\ExpressionEngine\Core;

use Closure;
use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;
use EllisLab\ExpressionEngine\Service\Dependency\ServiceProvider;
use EllisLab\ExpressionEngine\Service\Dependency\InjectionBindingDecorator;

class Provider extends InjectionBindingDecorator {

	/**
	 * @var Array The setup file data
	 */
	protected $data;

	/**
	 * @var String The root directory for this provider
	 */
	protected $path;

	/**
	 * @var String The prefix this provider was registered with
	 */
	protected $prefix;

	/**
	 * @var Autoloader
	 */
	protected $autoloader;

	/**
	 * @var Path to the config directory
	 */
	protected $config_path;

	/**
	 * @var Array of cached config file instances
	 */
	protected $config_files = array();

	/**
	 * @param ServiceProvider $delegate The root dependencies object
	 * @param String $path Core namespace path
	 * @param Array $data The setup file contents
	 */
	public function __construct(ServiceProvider $delegate, $path, array $data)
	{
		$this->path = $path;
		$this->data = $data;

		$this->setConfigPath($path.'/config');

		parent::__construct($delegate);
	}

	/**
	 * Override the default config path
	 *
	 * We need this, because ee's config is now in the user servicable
	 * directory instead of a fixed location.
	 */
	public function setConfigPath($path)
	{
		$this->config_path = rtrim($path, '/');
	}

	/**
	 * Get the default config path
	 *
	 * @return String Path to the config directory
	 */
	public function getConfigPath()
	{
		return $this->config_path;
	}

	/**
	 * Set the prefix in use for this provider
	 *
	 * @param String $prefix Prefix this was registered under
	 */
	public function setPrefix($prefix)
	{
		if (isset($this->prefix))
		{
			throw new \Exception('Cannot override provider prefix.');
		}

		$this->prefix = $prefix;

		$this->registerServices($prefix);
	}

	/**
	 * Set the autoloader
	 *
	 * @param Autoloader $autoloader Autoloader instance
	 */
	public function setAutoloader(Autoloader $autoloader)
	{
		$this->autoloader = $autoloader;
		$this->registerNamespace();
	}

	/**
	 * Get the registered path
	 *
	 * @return String Path in use
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Get the registered prefix
	 *
	 * @return String Prefix in use
	 */
	public function getPrefix()
	{
		return $this->prefix;
	}

	/**
	 * Get the 'author' key
	 *
	 * @return String vendor name
	 */
	public function getAuthor()
	{
		return $this->get('author');
	}

	/**
	 * Get the 'name' key
	 *
	 * @return String product name
	 */
	public function getName()
	{
		return $this->get('name');
	}

	/**
	 * Get the 'version' key
	 *
	 * @return String version number
	 */
	public function getVersion()
	{
		return $this->get('version');
	}

	/**
	 * Get the 'namespace' key
	 *
	 * @return String namespace name
	 */
	public function getNamespace()
	{
		return $this->get('namespace');
	}

	/**
	 * Get the 'services' key
	 *
	 * @return Array [name => closure]
	 */
	public function getServices()
	{
		return $this->get('services', array());
	}

	/**
	 * Get the 'services.singletons' key
	 *
	 * @return Array [name => closure]
	 */
	public function getSingletons()
	{
		return $this->get('services.singletons', array());
	}

	/**
	 * Get the 'models' key
	 *
	 * @return Array [name => class-name-in-namespace]
	 */
	public function getModels()
	{
		$ns = $this->getNamespace();
		$scope = $this;

		return $this->get('models', array(), function($element) use ($ns, $scope)
		{
			if ($element instanceOf Closure)
			{
				return $this->partial($element, $scope);
			}

			return $ns.'\\'.$element;
		});
	}

	/**
	 * Get the 'models.dependencies' key
	 *
	 * @return Array [model => [ee:foo, ee:bar]]
	 */
	public function getModelDependencies()
	{
		return $this->get('models.dependencies', array());
	}

	/**
	 * Helper function to get a given setup key
	 *
	 * @param String $key Key name
	 * @param Mixed $default Default value
	 * @param Closure $map Closure to call on the data before returning
	 * @return Mixed Setup value
	 */
	public function get($key, $default = NULL, Closure $map = NULL)
	{
		if (array_key_exists($key, $this->data))
		{
			$data = $this->data[$key];

			if (isset($map))
			{
				$data = is_array($data) ? array_map($map, $data) : $map($data);
			}

			return $data;
		}

		return $default;
	}

	/**
	 * Register this provider's namespace
	 */
	protected function registerNamespace()
	{
		$this->autoloader->addPrefix($this->getNamespace(), $this->path);
	}

	/**
	 * Register this provider's services
	 *
	 * @param String $prefix The service prefix to use
	 */
	protected function registerServices($prefix)
	{
		$self = $this;

		foreach ($this->getServices() as $name => $closure)
		{
			if (is_string($closure))
			{
				$closure = function() use ($closure, $self)
				{
					$args = func_get_args();
					array_shift($args);
					$class = $self->getNamespace() . '\\' . $closure;
					$object = new \ReflectionClass($class);
					return $object->newInstanceArgs($args);
				};
			}

			if (strpos($name, ':') !== FALSE)
			{
				throw new \Exception("Service names cannot contain ':'. ({$name})");
			}

			$this->register("{$prefix}:{$name}", $this->partial($closure, $this));
		}

		foreach ($this->getSingletons() as $name => $closure)
		{
			if (strpos($name, ':') !== FALSE)
			{
				throw new \Exception("Service names cannot contain ':'. ({$name})");
			}

			$this->registerSingleton("{$prefix}:{$name}", $this->partial($closure, $this));
		}
	}

	/**
	 * Forcably override the first parameter on a given closure
	 *
	 * @param Closure $closure Function to partially apply
	 * @param Mixed $scope First parameter
	 * @return Closure New function
	 */
	protected function partial(Closure $closure, $scope)
	{
		return function() use ($scope, $closure)
		{
			$args = func_get_args();
			$args[0] = $scope;
			return call_user_func_array($closure, $args);
		};
	}

	// -- DependencyInjectionDecorator tweaks to enforce a prefix -- //

	/**
	 * Same as parent::register but forces a prefix
	 *
	 * {@inheritDoc}
	 */
	public function register($name, $object)
	{
		$name = $this->ensurePrefix($name);
		return parent::register($name, $object);
	}

	/**
	 * Same as parent::registerSingleton but forces a prefix
	 *
	 * {@inheritDoc}
	 */
	public function registerSingleton($name, $object)
	{
		$name = $this->ensurePrefix($name);
		return parent::registerSingleton($name, $object);
	}

	/**
	 * Same as parent::make but forces a prefix
	 *
	 * {@inheritDoc}
	 */
	public function make()
	{
		$arguments = func_get_args();
		$arguments[0] = $this->ensurePrefix($arguments[0]);

		return call_user_func_array('parent::make', $arguments);
	}

	/**
	 * Allow rebinding on these classes. Normally the injection
	 * binding decorator is a one time deal.
	 *
	 * {@inheritDoc}
	 */
	public function bind($name, $object)
	{
		$obj = new InjectionBindingDecorator($this);
		return $obj->bind($name, $object);
	}

	/**
	 * Helper function to make sure the DI calls have
	 * a prefix.
	 *
	 * @param String $name Name to prefix
	 * @return String Prefixed name, if it did not have one
	 */
	protected function ensurePrefix($name)
	{
		if ($name == 'App')
		{
			return 'ee:'.$name;
		}

		if ( ! strpos($name, ':'))
		{
			$name = $this->prefix.':'.$name;
		}

		return $name;
	}
}

// EOF
