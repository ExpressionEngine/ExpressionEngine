<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Dependency;

use Closure;

/**
 * Service Provider Interface
 */
interface ServiceProvider
{
    public function register($name, $object);
    public function bind($name, $object);
    public function registerSingleton($name, $object);
    public function make();
}
