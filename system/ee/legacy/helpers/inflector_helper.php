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
 * Inflector Helpers
 */

/**
 * Singular
 *
 * Takes a plural word and makes it singular
 *
 * @access	public
 * @param	string
 * @return	str
 */
if (! function_exists('singular')) {
    function singular($str)
    {
        $str = strtolower(trim($str));
        $end = substr($str, -3);

        if ($end == 'ies') {
            $str = substr($str, 0, strlen($str) - 3) . 'y';
        } elseif ($end == 'ses') {
            $str = substr($str, 0, strlen($str) - 2);
        } else {
            $end = substr($str, -1);

            if ($end == 's') {
                $str = substr($str, 0, strlen($str) - 1);
            }
        }

        return $str;
    }
}

/**
 * Plural
 *
 * Takes a singular word and makes it plural
 *
 * @access	public
 * @param	string
 * @param	bool
 * @return	str
 */
if (! function_exists('plural')) {
    function plural($str, $force = false)
    {
        $str = strtolower(trim($str));
        $end = substr($str, -1);

        if ($end == 'y') {
            // Y preceded by vowel => regular plural
            $vowels = array('a', 'e', 'i', 'o', 'u');
            $str = in_array(substr($str, -2, 1), $vowels) ? $str . 's' : substr($str, 0, -1) . 'ies';
        } elseif ($end == 'h') {
            if (substr($str, -2) == 'ch' || substr($str, -2) == 'sh') {
                $str .= 'es';
            } else {
                $str .= 's';
            }
        } elseif ($end == 's') {
            if ($force == true) {
                $str .= 'es';
            }
        } else {
            $str .= 's';
        }

        return $str;
    }
}

/**
 * Camelize
 *
 * Takes multiple words separated by spaces or underscores and camelizes them
 *
 * @access	public
 * @param	string
 * @return	str
 */
if (! function_exists('camelize')) {
    function camelize($str)
    {
        $str = 'x' . strtolower(trim($str));
        $str = ucwords(preg_replace('/[\s_]+/', ' ', $str));

        return substr(str_replace(' ', '', $str), 1);
    }
}

/**
 * Underscore
 *
 * Takes multiple words separated by spaces and underscores them
 *
 * @access	public
 * @param	string
 * @return	str
 */
if (! function_exists('underscore')) {
    function underscore($str)
    {
        return preg_replace('/[\s]+/', '_', strtolower(trim($str)));
    }
}

/**
 * Humanize
 *
 * Takes multiple words separated by underscores and changes them to spaces
 *
 * @access	public
 * @param	string
 * @return	str
 */
if (! function_exists('humanize')) {
    function humanize($str)
    {
        return ucwords(preg_replace('/[_]+/', ' ', strtolower(trim($str))));
    }
}

// EOF
