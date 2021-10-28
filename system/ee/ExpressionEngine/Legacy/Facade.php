<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Legacy;

use InvalidArgumentException;
use RuntimeException;

/**
 * Facade to the legacy API, where the SuperObject contained
 * references to all of the silly stuff.
 */
class Facade
{
    protected $loaded = array();
    protected $in_scope = 0;

    // array of legacy objects that are deprecated and removed
    private $deprecated = [
        'blacklist' => 'blockedlist',
    ];

    /**
     *
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     *
     */
    public function __isset($name)
    {
        return $this->has($name);
    }

    /**
     * Setter
     */
    public function __set($name, $value)
    {
        // here only for the duration of the dev preview:
        $this->set($name, $value);
        trigger_error("Setting values on ee()-> is no longer supported. Tried to set {$name}.", E_USER_DEPRECATED);

        // TODO throw this exception for release.
        //throw new RuntimeException("Cannot set variables on the super object. Tried to set {$name}.");
    }

    /**
     * Forward call on this object either to the controller,
     * or to the loader (if we're inside a view). Gah!
     */
    public function __call($method, $args)
    {
        if ($this->in_scope && $this->has('load')) {
            $callback = array($this->get('load'), $method);

            if (is_callable($callback)) {
                return call_user_func_array($callback, $args);
            }
        } elseif ($this->has('__legacy_controller')) {
            $obj = $this->get('__legacy_controller');

            if ($this->has('_mcp_reference')) {
                $obj = $this->get('_mcp_reference');
            }

            return call_user_func_array(array($obj, $method), $args);
        }

        throw new \BadMethodCallException("Could not find {$method}.");
    }

    /**
     *
     */
    public function set($name, $object)
    {
        if ($this->has($name)) {
            throw new RuntimeException("Cannot overwrite {$name} on the loader.");
        }

        $this->loaded[$name] = $object;

        if ($name == 'session') {
            $object->setSessionCookies();
        }
    }

    /**
     *
     */
    public function remove($name)
    {
        unset($this->loaded[$name]);
    }

    /**
     *
     */
    public function get($name)
    {
        if ($this->has($name)) {
            return $this->loaded[$name];
        }

        if (array_key_exists($name, $this->deprecated)) {
            $name = $this->deprecated[$name];
            if ($this->has($name)) {
                if (method_exists($this->loaded[$name], 'deprecate')) {
                    $this->loaded[$name]->deprecate();
                }

                return $this->loaded[$name];
            }
        }

        throw new InvalidArgumentException("No such property: '{$name}' on " . get_called_class());
    }

    /**
     *
     */
    public function has($name)
    {
        return array_key_exists($name, $this->loaded);
    }

    /**
     *
     */
    public function runFileInFacadeScope($path, $vars, $eval = false)
    {
        if ($eval) {
            $str = file_get_contents($path);

            return $this->evalStringInFacadeScope($str, $vars);
        }

        $this->in_scope++;

        extract($vars);
        include($path);

        $this->in_scope--;
    }

    /**
     *
     */
    public function evalStringInFacadeScope($string, $vars)
    {
        $this->in_scope++;

        extract($vars);

        // If the PHP installation does not support short tags we'll
        // do a little string replacement, changing the short tags
        // to standard PHP echo statements.
        echo eval('?>' . preg_replace(
            "/;*\s*\?>/",
            "; ?>",
            str_replace('<?=', '<?php echo ', $string)
        ));

        $this->in_scope--;
    }
}
