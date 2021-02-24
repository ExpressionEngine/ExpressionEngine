<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * String Helper
 */

/**
 * Trim Slashes
 *
 * Removes any leading/trailing slashes from a string:
 *
 * /this/that/theother/
 *
 * becomes:
 *
 * this/that/theother
 *
 * @access  public
 * @param   string
 * @return  string
 */
if (! function_exists('trim_slashes')) {
    function trim_slashes($str)
    {
        return trim($str, '/');
    }
}

/**
 * Strip Slashes
 *
 * Removes slashes contained in a string or in an array
 *
 * @access  public
 * @param   mixed   string or array
 * @return  mixed   string or array
 */
if (! function_exists('strip_slashes')) {
    function strip_slashes($str)
    {
        if (is_array($str)) {
            foreach ($str as $key => $val) {
                $str[$key] = strip_slashes($val);
            }
        } else {
            $str = stripslashes($str);
        }

        return $str;
    }
}

/**
 * Strip Quotes
 *
 * Removes single and double quotes from a string
 *
 * @access  public
 * @param   string
 * @return  string
 */
if (! function_exists('strip_quotes')) {
    function strip_quotes($str)
    {
        return str_replace(array('"', "'"), '', $str);
    }
}

/**
 * Quotes to Entities
 *
 * Converts single and double quotes to entities
 *
 * @access  public
 * @param   string
 * @return  string
 */
if (! function_exists('quotes_to_entities')) {
    function quotes_to_entities($str)
    {
        return str_replace(array("\'","\"","'",'"'), array("&#39;","&quot;","&#39;","&quot;"), $str);
    }
}

/**
 * Reduce Double Slashes
 *
 * Converts double slashes in a string to a single slash,
 * except those found in http://
 *
 * http://www.some-site.com//index.php
 *
 * becomes:
 *
 * http://www.some-site.com/index.php
 *
 * @access  public
 * @param   string
 * @return  string
 */
if (! function_exists('reduce_double_slashes')) {
    function reduce_double_slashes($str)
    {
        return preg_replace("#([^/:])/+#", "\\1/", $str);
    }
}

/**
 * Reduce Multiples
 *
 * Reduces multiple instances of a particular character.  Example:
 *
 * Fred, Bill,, Joe, Jimmy
 *
 * becomes:
 *
 * Fred, Bill, Joe, Jimmy
 *
 * @access  public
 * @param   string
 * @param   string  the character you wish to reduce
 * @param   bool    TRUE/FALSE - whether to trim the character from the beginning/end
 * @return  string
 */
if (! function_exists('reduce_multiples')) {
    function reduce_multiples($str, $character = ',', $trim = false)
    {
        $str = preg_replace('#' . preg_quote($character, '#') . '{2,}#', $character, $str);

        if ($trim === true) {
            $str = trim($str, $character);
        }

        return $str;
    }
}

/**
 * Create a Random String
 *
 * Useful for generating passwords or hashes.
 *
 * @access  public
 * @param   string  type of random string.  basic, alpha, alunum, numeric, nozero, unique, md5, encrypt and sha1
 * @param   integer number of characters
 * @return  string
 */
if (! function_exists('random_string')) {
    function random_string($type = 'alnum', $len = 8, $antipool = '')
    {
        switch ($type) {
            case 'basic': return mt_rand();

                break;
            case 'alnum':
            case 'numeric':
            case 'nozero':
            case 'alpha':

                    switch ($type) {
                        case 'alpha':   $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

                            break;
                        case 'alnum':   $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

                            break;
                        case 'numeric':   $pool = '0123456789';

                            break;
                        case 'nozero':   $pool = '123456789';

                            break;
                    }

                    $pool = str_replace(str_split($antipool), '', $pool);

                    $str = '';
                    for ($i = 0; $i < $len; $i++) {
                        $str .= substr($pool, mt_rand(0, strlen($pool) - 1), 1);
                    }

                    return $str;

                break;
            case 'unique':
            case 'md5':

                        return md5(uniqid(random_int(-PHP_INT_MAX, PHP_INT_MAX)));

                break;
            case 'encrypt':
            case 'sha1':

                        return sha1(uniqid(random_int(-PHP_INT_MAX, PHP_INT_MAX), true));

                break;
        }
    }
}

/**
 * Alternator
 *
 * Allows strings to be alternated.  See docs...
 *
 * @access  public
 * @param   string (as many parameters as needed)
 * @return  string
 */
if (! function_exists('alternator')) {
    function alternator()
    {
        static $i;

        if (func_num_args() == 0) {
            $i = 0;

            return '';
        }
        $args = func_get_args();

        return $args[($i++ % count($args))];
    }
}

/**
 * Repeater function
 *
 * @access  public
 * @param   string
 * @param   integer number of repeats
 * @return  string
 */
if (! function_exists('repeater')) {
    function repeater($data, $num = 1)
    {
        return (($num > 0) ? str_repeat($data, $num) : '');
    }
}

 /**
 * Unique Marker
 *
 * The template library and some of our modules temporarily replace
 * pieces of code with a random string. These need to be unique per
 * request to avoid potential security issues.
 *
 * @access  public
 * @param   string  marker identifier
 * @return  string
 */
function unique_marker($ident)
{
    static $rand;

    if (! $rand) {
        $rand = random_string('alnum', 32);
    }

    return $rand . $ident;
}

/**
 * Just like trim, but also removes non-breaking spaces
 *
 * @param string $string The string to trim
 * @return string The trimmed string
 */
function trim_nbs($string)
{
    return trim($string, " \t\n\r\0\xB\xA0" . chr(0xC2) . chr(0xA0));
}

/**
 * Validates format of submitted license number, for soft validation
 *
 * @param string    $license    the string to run the pattern check on
 * @return bool     TRUE on pattern math, FALSE on failure
 **/
function valid_license_pattern($license)
{
    if (count(count_chars(str_replace('-', '', $license), 1)) == 1 or $license == '1234-1234-1234-1234') {
        return false;
    }

    if (! preg_match('/^[\d]{4}-[\d]{4}-[\d]{4}-[\d]{4}$/', $license)) {
        return false;
    }

    return true;
}

/**
 * Returns the surrounding character of a string, if it exists
 *
 * @param   string  $string     The string to check
 * @return  mixed   The surrounding character, or FALSE if there isn't one
 */
function surrounding_character($string)
{
    $first_char = substr($string, 0, 1);

    return ($first_char == substr($string, -1, 1)) ? $first_char : false;
}

/**
 * creates uuid4
 * @return string
 */
if (! function_exists('uuid4')) {
    function uuid4($data = null)
    {
        $data = $data ?: random_bytes(16);

        assert(strlen($data) == 16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
/*
 * Generate a URL friendly "slug" from a given string.
 *
 * @param  string  $title
 * @param  string  $separator
 * @return string
 */

if (! function_exists('slug')) {
    function slug($title, $separator = '-')
    {
        // Convert all dashes/underscores into separator
        $flip = $separator === '-' ? '_' : '-';

        $title = preg_replace('![' . preg_quote($flip) . ']+!u', $separator, $title);

        // Replace @ with the word 'at'
        $title = str_replace('@', $separator . 'at' . $separator, $title);

        // Remove all characters that are not the separator, letters, numbers, or whitespace.
        $title = preg_replace('![^' . preg_quote($separator) . '\pL\pN\s]+!u', '', strtolower($title));

        // Replace all separator characters and whitespace by a single separator
        $title = preg_replace('![' . preg_quote($separator) . '\s]+!u', $separator, $title);

        return trim($title, $separator);
    }
}

/**
 * Convert a value to studly caps case.
 *
 * @param  string  $value
 * @return string
 */
if (! function_exists('studly')) {
    function studly($value)
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        return str_replace(' ', '', $value);
    }
}

/**
 * Determine if a given string contains a given substring.
 *
 * @param  string  $haystack
 * @param  string|string[]  $needles
 * @return bool
 */
if (! function_exists('string_contains')) {
    function string_contains($haystack, $needles)
    {
        ee()->load->helper('multibyte');

        foreach ((array) $needles as $needle) {
            if ($needle !== '' && ee_mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}

// EOF
