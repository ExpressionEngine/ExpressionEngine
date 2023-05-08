<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Core;

use Closure;
use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Service\Dependency\ServiceProvider;
use ExpressionEngine\Service\Dependency\InjectionBindingDecorator;

/**
 * Core Provider
 */
class Provider extends InjectionBindingDecorator
{
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

        $this->setConfigPath($path . '/config');

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
        if (isset($this->prefix)) {
            throw new \Exception('Cannot override provider prefix.');
        }

        $this->prefix = $prefix;

        $this->registerServices($prefix);
        $this->registerCookies();
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
     * @return array [name => closure]
     */
    public function getServices()
    {
        return $this->get('services', array());
    }

    /**
     * Get the 'services.singletons' key
     *
     * @return array [name => closure]
     */
    public function getSingletons()
    {
        return $this->get('services.singletons', array());
    }

    /**
     * Get the 'models' key
     *
     * @return array [name => class-name-in-namespace]
     */
    public function getModels()
    {
        $ns = $this->getNamespace();
        $scope = $this;

        return $this->get('models', array(), function ($element) use ($ns, $scope) {
            if ($element instanceof Closure) {
                return $this->partial($element, $scope);
            }

            //do not namespace models that are root
            if (strpos($element, '\\') === 0) {
                return $element;
            }

            return $ns . '\\' . $element;
        });
    }

    /**
     * Get the 'models.dependencies' key
     *
     * @return array [model => [ee:foo, ee:bar]]
     */
    public function getModelDependencies()
    {
        return $this->get('models.dependencies', array());
    }

    /**
     * Set up aliases
     *
     * @return void
     */
    public function setClassAliases()
    {
        $aliases = $this->get('aliases', array());

        if (!empty($aliases)) {
            foreach ($aliases as $origClassName => $aliasClassName) {
                if (is_numeric($origClassName)) {
                    $origClassName = $aliasClassName;
                    if (strpos($aliasClassName, 'ExpressionEngine\Addons') === 0) {
                        $replace_once = 1;
                        $aliasClassName = str_replace('ExpressionEngine\Addons', 'Addons', $aliasClassName, $replace_once);
                    }
                    $aliasClassName = 'EllisLab\\' . $aliasClassName;
                }
                if (!class_exists($aliasClassName)) {
                    class_alias($aliasClassName, $origClassName);
                }
            }
            unset($origClassName, $aliasClassName);
        }

        unset($aliases);
    }

    /**
     * Helper function to get a given setup key
     *
     * @param String $key Key name
     * @param Mixed $default Default value
     * @param Closure $map Closure to call on the data before returning
     * @return Mixed Setup value
     */
    public function get($key, $default = null, Closure $map = null)
    {
        if (array_key_exists($key, $this->data)) {
            $data = $this->data[$key];

            if (isset($map)) {
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

        foreach ($this->getServices() as $name => $closure) {
            if (is_string($closure)) {
                $closure = function () use ($closure, $self) {
                    $args = func_get_args();
                    array_shift($args);
                    $class = $self->getNamespace() . '\\' . $closure;
                    $object = new \ReflectionClass($class);

                    return $object->newInstanceArgs($args);
                };
            }

            if (strpos($name, ':') !== false) {
                throw new \Exception("Service names cannot contain ':'. ({$name})");
            }

            $this->register("{$prefix}:{$name}", $this->partial($closure, $this));
        }

        foreach ($this->getSingletons() as $name => $closure) {
            if (strpos($name, ':') !== false) {
                throw new \Exception("Service names cannot contain ':'. ({$name})");
            }

            $this->registerSingleton("{$prefix}:{$name}", $this->partial($closure, $this));
        }
    }

    protected function registerCookies()
    {
        $cookie_reg = $this->make('ee:CookieRegistry');
        foreach (['Necessary', 'Functionality', 'Performance', 'Targeting'] as $type) {
            foreach ($this->get('cookies.' . strtolower($type), []) as $cookie_name) {
                $method = 'register' . $type;
                $cookie_reg->{$method}($cookie_name);
            }
        }
    }

    
    /**
     * Registers cookie settings in memory and database
     *
     * @param $name Name of the cookie
     * @return void
     */
    public function registerCookiesSettings()
    {
        $cookieService = $this->make('ee:Cookie');
        if ($this->getPrefix() != 'ee') {
            $addon = ee('Addon')->get($this->getPrefix());
            if (!$addon || !$addon->isInstalled()) {
                return;
            }
        }
        $builtinCookieSettings = $this->get('cookie_settings');
        $providerCookieSettings = null;
        foreach (['Necessary', 'Functionality', 'Performance', 'Targeting'] as $type) {
            foreach ($this->get('cookies.' . strtolower($type), []) as $cookie_name) {
                if (is_null($providerCookieSettings)) {
                    $providerCookieSettings = ee('Model')
                        ->get('CookieSetting')
                        ->fields('cookie_name', 'cookie_provider');
                    if ($this->getPrefix() != 'ee') {
                        $providerCookieSettings->filter('cookie_provider', $this->getPrefix());
                    } else {
                        $providerCookieSettings->filter('cookie_provider', 'IN', ['ee', 'cp']);
                    }
                    $providerCookieSettings = $providerCookieSettings->all()
                        ->getDictionary('cookie_name', 'cookie_provider');
                }
                $cookieParams = [
                    'cookie_provider' => $this->getPrefix(),
                    'cookie_name' => $cookie_name
                ];
                if (!isset($providerCookieSettings[$cookie_name])) {
                    $cookieSettings = ee('Model')->make('CookieSetting', $cookieParams);
                    switch ($cookieParams['cookie_provider']) {
                        // first-party add-ons
                        case 'pro':
                        case 'comment':
                            ee()->lang->load($cookieParams['cookie_provider']);
                            break;
                        // core EE
                        case 'ee':
                            ee()->lang->load('core');
                            break;
                        // third-party add-ons
                        default:
                            ee()->lang->loadfile($cookieParams['cookie_provider'], $cookieParams['cookie_provider'], false);
                            break;
                    }

                    $cookieSettings->cookie_title = (lang('cookie_' . $cookie_name) != 'cookie_' . $cookie_name) ? lang('cookie_' . $cookie_name) : lang($cookie_name);
                    if (!empty($builtinCookieSettings) && isset($builtinCookieSettings[$cookie_name])) {
                        if (isset($builtinCookieSettings[$cookie_name]['description'])) {
                            if (strpos($builtinCookieSettings[$cookie_name]['description'], 'lang:') === 0) {
                                $cookieSettings->cookie_description = lang(substr($builtinCookieSettings[$cookie_name]['description'], 5));
                            } else {
                                $cookieSettings->cookie_description = $builtinCookieSettings[$cookie_name]['description'];
                            }
                        }
                        if (isset($builtinCookieSettings[$cookie_name]['provider']) && $cookieParams['cookie_provider'] == 'ee') {
                            $cookieSettings->cookie_provider = $builtinCookieSettings[$cookie_name]['provider'];
                        }
                    }
                    $cookieSettings->cookie_lifetime = null; //unknown at this point
                    $cookieSettings->cookie_enforced_lifetime = null;
                    $cookieSettings->save();
                    ee('CookieRegistry')->registerCookieSettings($cookieSettings);
                }
            }
        }
    }

    /**
     * Registers filesystem adapters
     *
     * @return void
     */
    public function registerFilesystemAdapters()
    {
        $filesystem_adapters = $this->get('filesystem_adapters', array());
        if (!empty($filesystem_adapters)) {
            ee()->lang->loadfile($this->getPrefix());
            foreach ($filesystem_adapters as $adapter) {
                ee('Filesystem/Adapter')->registerAdapter($adapter);
            }
        }
        unset($filesystem_adapters);
    }

    /**
     * Register variable modifiers
     * 
     * @return void
     */
    public function registerVariableModifiers()
    {
        $modifiers = $this->get('modifiers', array());
        if (!empty($modifiers)) {
            foreach ($modifiers as $modifier) {
                ee('Variables/Modifiers')->register($modifier, $this);
            }
        }
        unset($modifiers);
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
        return function () use ($scope, $closure) {
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

        return call_user_func_array(parent::class . "::make", $arguments);
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
        if ($name == 'App') {
            return 'ee:' . $name;
        }

        if (! strpos($name, ':')) {
            $name = $this->prefix . ':' . $name;
        }

        return $name;
    }
}

// EOF
