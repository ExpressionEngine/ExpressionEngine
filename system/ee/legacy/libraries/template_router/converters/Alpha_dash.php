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
 * Template Router Alphanumeric + Dash Converter
 */
class EE_Template_router_alpha_dash_converter implements EE_Template_router_converter
{
    public function validator()
    {
        return "([A-Za-z0-9_-]+)";
    }
}

// EOF
