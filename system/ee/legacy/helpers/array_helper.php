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
 * Array Helpers
 */

/**
 * Element
 *
 * Lets you determine whether an array index is set and whether it has a value.
 * If the element is empty it returns FALSE (or whatever you specify as the default value.)
 *
 * @access	public
 * @param	string
 * @param	array
 * @param	mixed
 * @return	mixed	depends on what the array contains
 */
if (! function_exists('element')) {
    function element($item, $array, $default = false)
    {
        if (! isset($array[$item]) or $array[$item] == "") {
            return $default;
        }

        return $array[$item];
    }
}

/**
 * Random Element - Takes an array as input and returns a random element
 *
 * @access	public
 * @param	array
 * @return	mixed	depends on what the array contains
 */
if (! function_exists('random_element')) {
    function random_element($array)
    {
        if (! is_array($array)) {
            return $array;
        }

        return $array[array_rand($array)];
    }
}

/**
 * Elements
 *
 * Returns only the array items specified.  Will return a default value if
 * it is not set.
 *
 * @access	public
 * @param	array
 * @param	array
 * @param	mixed
 * @return	mixed	depends on what the array contains
 */
if (! function_exists('elements')) {
    function elements($items, $array, $default = false)
    {
        $return = array();

        if (! is_array($items)) {
            $items = array($items);
        }

        foreach ($items as $item) {
            if (isset($array[$item])) {
                $return[$item] = $array[$item];
            } else {
                $return[$item] = $default;
            }
        }

        return $return;
    }
}

// EOF
