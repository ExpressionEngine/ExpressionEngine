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
 * Core\ExpressionEngine
 */
class ExpressionEngine extends Core
{
    /**
     *
     */
    public function boot()
    {
        define('APPPATH', BASEPATH);
        define('INSTALLER', false);

        get_config(array('subclass_prefix' => 'EE_'));

        parent::boot();
    }
}
