<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * View Helper
 */

/**
 * Extend a view in the _template directory
 */
function extend_template($which, $disable = array())
{
    ee()->view->extend('_templates/' . $which, $disable);
}

/**
 * Extend a view. Contents of the current view
 * are passed in as $EE_Rendered_view
 */
function extend_view($which, $disable = array())
{
    ee()->view->extend($which, $disable);
}

/**
 * Check if a view block is disabled
 */
function disabled($which)
{
    return ee()->view->disabled($which);
}

/**
 * Check if a view block is enabled
 */
function enabled($which)
{
    return ! ee()->view->disabled($which);
}
