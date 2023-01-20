<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Members\Profile;

use CP_Controller;

/**
 * Template Settings Controller
 */
class Pro extends Settings
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        if (!ee('pro:Access')->hasRequiredLicense()) {
            show_error(lang('unauthorized_access'), 403);
        }
    }

    public function __call($name, $arguments)
    {
        $function = array_shift($arguments);
        if (empty($function)) {
            $function = $name;
        }
        $name = ucfirst($name);
        $class = "\ExpressionEngine\Addons\Pro\Controller\Members\Profile\\" . $name;
        $controller = new $class();

        return $controller->$function($arguments);
    }
}
// END CLASS

// EOF
