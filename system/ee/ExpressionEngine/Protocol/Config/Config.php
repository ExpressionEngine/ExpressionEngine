<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Protocol\Config;

/**
 * ExpressionEngine Config Protocol interface
 */
interface Config
{
    /**
     * Get a config item
     *
     * @param string $key Config key name
     * @param mixed $default Default value to return if item does not exist.
     * @return mixed
     */
    public function get($key, $default = null);
}
