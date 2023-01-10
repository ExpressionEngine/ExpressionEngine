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
 * Path Helpers
 */

/**
 * Set Realpath
 *
 * @access	public
 * @param	string
 * @param	bool	checks to see if the path exists
 * @return	string
 */
if (! function_exists('set_realpath')) {
    function set_realpath($path, $check_existance = false)
    {
        // Security check to make sure the path is NOT a URL.  No remote file inclusion!
        if (preg_match("#^(http:\/\/|https:\/\/|www\.|ftp|[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})#i", $path)) {
            show_error('The path you submitted must be a local server path, not a URL');
        }

        // Resolve the path
        if (function_exists('realpath') and @realpath($path) !== false) {
            $path = realpath($path) . '/';
        }

        // Add a trailing slash
        $path = preg_replace("#([^/])/*$#", "\\1/", $path);

        // Make sure the path exists
        if ($check_existance == true) {
            if (! is_dir($path)) {
                show_error('Not a valid path: ' . $path);
            }
        }

        return $path;
    }
}

// EOF
