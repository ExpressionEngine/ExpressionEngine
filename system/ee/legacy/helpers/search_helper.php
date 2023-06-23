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
 * Search Helpers
 */

/**
 * Sanitize Search Terms
 *
 * Filters a search string for security
 *
 * @access	public
 * @param	string
 * @return	string
 */
if (! function_exists('sanitize_search_terms')) {
    function sanitize_search_terms($str)
    {

        $str = strip_tags($str);

        $str = preg_replace("(\s+)", " ", $str);

        // Kill naughty stuff...
        $str = ee('Security/XSS')->clean($str);

        return trim($str);
    }
}

// EOF
