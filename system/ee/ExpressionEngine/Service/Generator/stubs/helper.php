<?php

/**
 * {{addon_name}} Helper
 * 
 * @package     {{addon_name}}
 * @author      {{author}}
 * @description {{description}}
 */

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Example helper function
 *
 * @param string $param
 * @return string
 */
if (!function_exists('{{addon_name}}_example_function')) {
    function {{addon_name}}_example_function($param = '')
    {
        return 'Example: ' . $param;
    }
} 