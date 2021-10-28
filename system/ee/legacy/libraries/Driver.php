<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Driver Library
 */
class EE_Driver_Library
{
    /**
     * Array of drivers that are available to use with the driver class
     *
     * @var array
     */
    protected $valid_drivers = array();

    /**
     * Name of the current class - usually the driver class
     *
     * @var string
     */
    protected $lib_name;

    /**
     * Get magic method
     *
     * The first time a child is used it won't exist, so we instantiate it with
     * load_driver(). Subsequents calls will go straight to the proper child.
     *
     * @param	string	Child class name
     * @return	object	Child class
     */
    public function __get($child)
    {
        // Try to load the driver
        return $this->load_driver($child);
    }

    /**
     * Load driver
     *
     * Separate load_driver call to support explicit driver load by library or user
     *
     * @param	string	Driver name (w/o parent prefix)
     * @return	object	Child class
     */
    public function load_driver($child)
    {
        $prefix = config_item('subclass_prefix');

        if (! isset($this->lib_name)) {
            // Get library name without any prefix
            $this->lib_name = str_replace(array('CI_', $prefix), '', get_class($this));
        }

        // The child will be prefixed with the parent lib
        $child_name = $this->lib_name . '_' . $child;

        // See if requested child is a valid driver
        if (! in_array($child, $this->valid_drivers)) {
            // The requested driver isn't valid!
            $msg = 'Invalid driver requested: ' . $child_name;
            log_message('error', $msg);
            show_error($msg);
        }

        // Get package paths and filename case variations to search
        $paths = ee()->load->get_package_paths(true);

        // Is there an extension?
        $class_name = $prefix . $child_name;
        $found = class_exists($class_name, false);
        if (! $found) {
            // Check for subclass file
            foreach ($paths as $path) {
                // Does the file exist?
                $file = $path . 'libraries/' . $this->lib_name . '/drivers/' . $prefix . $child_name . '.php';
                if (file_exists($file)) {
                    // Yes - require base class from BASEPATH
                    $basepath = BASEPATH . 'libraries/' . $this->lib_name . '/drivers/' . $child_name . '.php';
                    if (! file_exists($basepath)) {
                        $msg = 'Unable to load the requested class: CI_' . $child_name;
                        log_message('error', $msg);
                        show_error($msg);
                    }

                    // Include both sources and mark found
                    include_once($basepath);
                    include_once($file);
                    $found = true;

                    break;
                }
            }
        }

        // Do we need to search for the class?
        if (! $found) {
            // Use standard class name
            $class_name = 'EE_' . $child_name;
            if (! class_exists($class_name, false)) {
                // Check package paths
                foreach ($paths as $path) {
                    // Does the file exist?
                    $file = $path . 'libraries/' . $this->lib_name . '/drivers/' . $child_name . '.php';
                    if (file_exists($file)) {
                        // Include source
                        include_once($file);

                        break;
                    }
                }
            }
        }

        // Did we finally find the class?
        if (! class_exists($class_name, false)) {
            if (class_exists($child_name, false)) {
                $class_name = $child_name;
            } else {
                $msg = 'Unable to load the requested driver: ' . $class_name;
                log_message('error', $msg);
                show_error($msg);
            }
        }

        // Instantiate, decorate and add child
        $obj = new $class_name();
        $obj->decorate($this);
        $this->$child = $obj;

        return $this->$child;
    }
}
// END CLASS

/**
 * Driver Class
 *
 * This class enables you to create drivers for a Library based on the Driver Library.
 * It handles the drivers' access to the parent library
 */
class EE_Driver
{
    /**
     * Instance of the parent class
     *
     * @var object
     */
    protected $_parent;

    /**
     * List of methods in the parent class
     *
     * @var array
     */
    protected $_methods = array();

    /**
     * List of properties in the parent class
     *
     * @var array
     */
    protected $_properties = array();

    /**
     * Array of methods and properties for the parent class(es)
     *
     * @static
     * @var	array
     */
    protected static $_reflections = array();

    /**
     * Decorate
     *
     * Decorates the child with the parent driver lib's methods and properties
     *
     * @param	object
     * @return	void
     */
    public function decorate($parent)
    {
        $this->_parent = $parent;

        // Lock down attributes to what is defined in the class
        // and speed up references in magic methods

        $class_name = get_class($parent);

        if (! isset(self::$_reflections[$class_name])) {
            $r = new ReflectionObject($parent);

            foreach ($r->getMethods() as $method) {
                if ($method->isPublic()) {
                    $this->_methods[] = $method->getName();
                }
            }

            foreach ($r->getProperties() as $prop) {
                if ($prop->isPublic()) {
                    $this->_properties[] = $prop->getName();
                }
            }

            self::$_reflections[$class_name] = array($this->_methods, $this->_properties);
        } else {
            list($this->_methods, $this->_properties) = self::$_reflections[$class_name];
        }
    }

    /**
     * __call magic method
     *
     * Handles access to the parent driver library's methods
     *
     * @param	string
     * @param	array
     * @return	mixed
     */
    public function __call($method, $args = array())
    {
        if (in_array($method, $this->_methods)) {
            return call_user_func_array(array($this->_parent, $method), $args);
        }

        $trace = debug_backtrace();
        _exception_handler(E_ERROR, "No such method '{$method}'", $trace[1]['file'], $trace[1]['line']);
        exit(EXIT_UNKNOWN_METHOD);
    }

    /**
     * __get magic method
     *
     * Handles reading of the parent driver library's properties
     *
     * @param	string
     * @return	mixed
     */
    public function __get($var)
    {
        if (in_array($var, $this->_properties)) {
            return $this->_parent->$var;
        }
    }

    /**
     * __set magic method
     *
     * Handles writing to the parent driver library's properties
     *
     * @param	string
     * @param	array
     * @return	mixed
     */
    public function __set($var, $val)
    {
        if (in_array($var, $this->_properties)) {
            $this->_parent->$var = $val;
        }
    }
}
// END CLASS

class_alias('EE_Driver_Library', 'CI_Driver_Library');
class_alias('EE_Driver', 'CI_Driver');

// EOF
