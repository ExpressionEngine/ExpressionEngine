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

/**
 * ExpressionEngine Autoloader
 *
 * Really basic autoloader using the PSR-4 autoloading rules.
 */
class Autoloader
{
    protected $prefixes = array();

    protected static $instance;

    /**
     * Use as a singleton
     */
    public static function getInstance()
    {
        if (! isset(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Register the autoloader with PHP
     */
    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'));

        return $this;
    }

    /**
     * Remove the autoloader
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));

        return $this;
    }

    /**
     * Map a namespace prefix to a path
     */
    public function addPrefix($namespace, $path)
    {
        $this->prefixes[$namespace] = $path;

        return $this;
    }

    /**
     * Handle the autoload call.
     *
     * @param String $class Fully qualified class name. As of 5.3.3 this does
     *                      not include a leading slash.
     * @return void
     */
    public function loadClass($class)
    {
        // @todo this prefix handling will not do sub-namespaces correctly
        foreach ($this->prefixes as $prefix => $path) {
            if (empty($prefix)) {
                throw new \Exception("No namespace specified for add-on: {$path}");
            }

            if (strpos($class, $prefix) === 0) {
                // Are they looking for an EllisLab namespaced class
                if ($prefix == 'EllisLab\ExpressionEngine' || $prefix == 'EllisLab\Addons') {
                    $el_class = $class;
                    if ($prefix == 'EllisLab\ExpressionEngine') {
                        $class = str_replace($prefix, 'ExpressionEngine', $class);
                    } elseif ($prefix == 'EllisLab\Addons') {
                        $class = str_replace($prefix, 'ExpressionEngine\Addons', $class);
                    }
                    // Alias the class name to the legacy namespaced class
                    class_alias($class, $el_class);
                }

                // From inside to out: Strip off the prefix from the namespace, turn the namespace into
                // a path, prepend the path prefix, append .php.
                $class_path = $path . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

                if (file_exists($class_path)) {
                    require_once $class_path;

                    return;
                }
            }
        }

        // Keep this commented out until we're fully namespaced. PHP will handle it.
        //throw new \RuntimeException('Failed to load class: ' . $class . '!');
    }
}

// EOF
