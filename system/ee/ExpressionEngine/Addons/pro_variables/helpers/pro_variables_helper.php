<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Pro Variables helper functions
 */

// --------------------------------------------------------------------

/**
 * Flatten results
 *
 * Given a DB result set, this will return an (associative) array
 * based on the keys given
 *
 * @param      array
 * @param      string    key of array to use as value
 * @param      string    key of array to use as key (optional)
 * @return     array
 */
if (! function_exists('pro_flatten_results')) {
    function pro_flatten_results($resultset, $val, $key = false)
    {
        $array = array();

        foreach ($resultset as $row) {
            if ($key !== false) {
                $array[$row[$key]] = $row[$val];
            } else {
                $array[] = $row[$val];
            }
        }

        return $array;
    }
}

// --------------------------------------------------------------------

/**
 * Associate results
 *
 * Given a DB result set, this will return an (associative) array
 * based on the keys given
 *
 * @param      array
 * @param      string    key of array to use as key
 * @param      bool      sort by key or not
 * @return     array
 */
if (! function_exists('pro_associate_results')) {
    function pro_associate_results($resultset, $key, $sort = false)
    {
        $array = array();

        foreach ($resultset as $row) {
            if (array_key_exists($key, $row) && ! array_key_exists($row[$key], $array)) {
                $array[$row[$key]] = $row;
            }
        }

        if ($sort === true) {
            ksort($array);
        }

        return $array;
    }
}

// --------------------------------------------------------------

/**
 * Get cache value, either using the cache method (EE2.2+) or directly from cache array
 *
 * @param       string
 * @param       string
 * @return      mixed
 */
if (! function_exists('pro_get_cache')) {
    function pro_get_cache($a, $b)
    {
        if (method_exists(ee()->session, 'cache')) {
            return ee()->session->cache($a, $b);
        } else {
            return (isset(ee()->session->cache[$a][$b]) ? ee()->session->cache[$a][$b] : false);
        }
    }
}

// --------------------------------------------------------------

/**
 * Set cache value, either using the set_cache method (EE2.2+) or directly to cache array
 *
 * @param       string
 * @param       string
 * @param       mixed
 * @return      void
 */
if (! function_exists('pro_set_cache')) {
    function pro_set_cache($a, $b, $c)
    {
        if (method_exists(ee()->session, 'set_cache')) {
            ee()->session->set_cache($a, $b, $c);
        } else {
            ee()->session->cache[$a][$b] = $c;
        }
    }
}

// --------------------------------------------------------------

/**
 * Debug
 *
 * @param       mixed
 * @param       bool
 * @return      void
 */
if (! function_exists('pro_dump')) {
    function pro_dump($var, $exit = true)
    {
        echo '<pre>' . htmlentities(print_r($var, true)) . '</pre>';
        if ($exit) {
            exit;
        }
    }
}

// --------------------------------------------------------------

/* End of file pro_variables_helper.php */
