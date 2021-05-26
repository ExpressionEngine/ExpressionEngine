<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Pro;

use CP_Controller;

/**
 * Pro version Controller
 * The purpose of this is re-route requests to controllers in Pro folder
 */
class Pro extends CP_Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        if (!IS_PRO) {
            show_error(lang('unauthorized_access'), 403);
        }
    }

    public function __call($name, $arguments)
    {
        $function = array_shift($arguments);
        $name = ucfirst($name);
        $class = "\ExpressionEngine\Addons\Pro\Controller\\" . $name . "\\" . $name;
        $controller = new $class();

        return $controller->$function($arguments);
    }
}

// EOF
