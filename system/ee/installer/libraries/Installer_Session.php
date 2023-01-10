<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Installer Session
 */
class Installer_Session
{
    public $userdata = array();
    protected $cache = array();

    public function cache($class, $key, $default = false)
    {
        return (isset($this->cache[$class][$key])) ? $this->cache[$class][$key] : $default;
    }

    public function set_cache($class, $key, $val)
    {
        if (! isset($this->cache[$class])) {
            $this->cache[$class] = array();
        }

        $this->cache[$class][$key] = $val;

        return $this;
    }

    public function userdata($which, $default = false)
    {
        return (! isset($this->userdata[$which])) ? $default : $this->userdata[$which];
    }

    public function all_userdata()
    {
        return $this->userdata;
    }

    public function getMember()
    {
        return ee('Model')->get('Member', 1)->first();
    }

    public function session_id($which = '')
    {
        return 0;
    }

    public function setSessionCookies()
    {
        return;
    }
}
// END CLASS

// EOF
