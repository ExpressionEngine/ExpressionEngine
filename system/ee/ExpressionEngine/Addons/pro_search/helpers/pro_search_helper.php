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
 * Pro Search helper functions
 */

// --------------------------------------------------------------------

/**
 * Encode an array for use in the URI
 */
if (! function_exists('pro_search_encode')) {
    function pro_search_encode($array = array(), $url = true)
    {
        // Filter the array
        $array = array_filter($array, 'pro_not_empty');

        // PHP 5.2 support
        $str = defined('JSON_FORCE_OBJECT')
            ? json_encode($array, JSON_FORCE_OBJECT)
            : json_encode($array);

        // If we want a url-safe encode, base64-it
        if ($url) {
            // Our own version of URL encoding
            $str = base64_encode($str);

            // Clean stuff
            $str = rtrim($str, '=');
            $str = str_replace('/', '_', $str);
        }

        return $str;
    }
}

/**
 * Decode a query back to the array
 */
if (! function_exists('pro_search_decode')) {
    function pro_search_decode($str = '', $url = true)
    {
        // Bail out if not valid
        if (! (is_string($str) && strlen($str))) {
            return array();
        }

        // Override url setting if we're looking at an encoded string
        if (substr($str, 0, 3) == 'YTo') {
            $url = true;
        }

        // Are we decoding a url-safe query?
        if ($url) {
            // Translate back
            $str = str_replace('_', '/', $str);

            // In a URI, plusses get replaced by spaces. Put the plusses back
            $str = str_replace(' ', '+', $str);

            // Decode back
            $str = base64_decode($str);
        }

        // Decoding method
        $array = (substr($str, 0, 2) == 'a:') ? @unserialize($str) : @json_decode($str, true);

        // Force array output
        if (! is_array($array)) {
            $array = array();
        }

        return $array;
    }
}

// --------------------------------------------------------------------

/**
 * Returns an array of all substring index positions
 *
 * @param      string
 * @param      string
 * @return     array
 */
if (! function_exists('pro_strpos_all')) {
    function pro_strpos_all($haystack, $needle)
    {
        $all = array();

        if (preg_match_all('#' . preg_quote($needle, '#') . '#', $haystack, $matches)) {
            $total = count($matches[0]);
            $offset = 0;

            while ($total--) {
                $pos = strpos($haystack, $needle, $offset);
                $all[] = $pos;
                $offset = $pos + 1;
            }
        }

        return $all;
    }
}

/**
 * Returns an array of all substrings within haystack with given length and optional padding
 *
 * @param      string
 * @param      array
 * @param      int
 * @param      int
 * @return     array
 */
if (! function_exists('pro_substr_pad')) {
    function pro_substr_pad($haystack, $pos = array(), $length = 0, $pad = 0)
    {
        $all = array();
        $haystack_length = strlen($haystack);

        foreach ($pos as $p) {
            // account for left padding
            $p -= $pad;
            if ($p < 0) {
                $p = 0;
            }

            // Account for right padding
            $l = $length + ($pad * 2);
            if (($p + $l) > $haystack_length) {
                $l = $haystack_length - $p;
            }

            $all[] = substr($haystack, $p, $l);
        }

        return $all;
    }
}

/**
 * Wraps occurrences of $needle found in $haystack in <mark> tags
 *
 * @param      string
 * @param      string
 * @return     string
 */
if (! function_exists('pro_hilite')) {
    function pro_hilite($haystack, $needle)
    {
        return preg_replace('#(' . preg_quote($needle, '#') . ')#', '<mark>$1</mark>', $haystack);
    }
}

// --------------------------------------------------------------------

/**
 * Legacy function, here for backward compat.
 *
 * @param      string    String to clean up
 * @param      array     Array of words to ignore (strip out)
 * @return     string
 */
if (! function_exists('pro_clean_string')) {
    function pro_clean_string($str, $ignore = null)
    {
        if (empty($str)) {
            return $str;
        }

        ee()->load->library('pro_search_words');

        // Force bool
        $ignore = ! empty($ignore);

        // Clean and strip
        $str = ee()->pro_search_words->clean($str, $ignore);
        $str = ee()->pro_search_words->remove_diacritics($str);

        return $str;
    }
}

// --------------------------------------------------------------------

/**
 * Get utf-8 character from ascii integer
 *
 * @access     public
 * @param      int
 * @return     string
 */
if (! function_exists('pro_chr')) {
    function pro_chr($int)
    {
        $ent = is_numeric($int) ? '#' . $int : $int;

        return html_entity_decode("&{$ent};", ENT_QUOTES, 'UTF-8');
    }
}

// --------------------------------------------------------------------

/**
 * Clean up given list of words
 *
 * @access      private
 * @param       string
 * @return      string
 */
if (! function_exists('pro_prep_word_list')) {
    function pro_prep_word_list($str = '')
    {
        $str = ee()->pro_multibyte->strtolower($str);
        $str = preg_replace('/[^\w\'\s\n]/iu', '', $str);
        $str = array_unique(array_filter(preg_split('/(\s|\n)/', $str)));
        sort($str);

        return implode(' ', $str);
    }
}

// --------------------------------------------------------------------

/**
 * Format string in given format
 *
 * @access     public
 * @param      string
 * @param      string
 * @return     string
 */
if (! function_exists('pro_format')) {
    function pro_format($str = '', $format = 'html')
    {
        // Encode/decode chars specifically for EE params
        $code = array(
            '&quot;' => '"',
            '&apos;' => "'",
            '&#123;' => '{',
            '&#125;' => '}'
        );

        switch ($format) {
            case 'url':
                $str = urlencode($str);

                break;

            case 'html':
                $str = htmlspecialchars($str);
                $str = pro_format($str, 'ee-encode');

                break;

            case 'clean':
                $str = pro_clean_string($str);

                break;

            case 'ee-encode':
                $str = str_replace(array_values($code), array_keys($code), $str);

                break;

            case 'ee-decode':
                $str = str_replace(array_keys($code), array_values($code), $str);

                break;
        }

        return $str;
    }
}

// --------------------------------------------------------------------

/**
 * Create parameter string from array
 *
 * @access     public
 * @param      array
 * @return     string
 */
if (! function_exists('pro_param_string')) {
    function pro_param_string($array)
    {
        // prep output
        $out = array();

        foreach ($array as $key => $val) {
            // Disallow non-string values
            if (! is_string($val)) {
                continue;
            }

            $out[] = sprintf('%s="%s"', $key, $val);
        }

        // Return the string
        return implode(' ', $out);
    }
}

// --------------------------------------------------------------------

/**
 * Converts {if foo IN (1|2|3)} to {if foo == "1" OR foo == "2" OR foo == "3"}
 * in given tagdata
 *
 * @access     public
 * @param      string    tagdata
 * @return     string    Prep'ed tagdata
 */
if (! function_exists('pro_prep_in_conditionals')) {
    function pro_prep_in_conditionals($tagdata = '')
    {
        if (preg_match_all('#' . LD . 'if (([\w\-_]+)|((\'|")(.+)\\4)) (NOT)?\s?IN \((.*?)\)' . RD . '#', $tagdata, $matches)) {
            foreach ($matches[0] as $key => $match) {
                $left = $matches[1][$key];
                $operand = $matches[6][$key] ? '!=' : '==';
                $andor = $matches[6][$key] ? ' AND ' : ' OR ';
                $items = preg_replace('/(&(amp;)?)+/', '|', $matches[7][$key]);
                $cond = array();
                foreach (explode('|', $items) as $right) {
                    $tmpl = preg_match('#^(\'|").+\\1$#', $right) ? '%s %s %s' : '%s %s "%s"';
                    $cond[] = sprintf($tmpl, $left, $operand, $right);
                }

                // Replace {if foo IN (a|b|c)} with {if foo == 'a' OR foo == 'b' OR foo == 'c'}
                $tagdata = str_replace(
                    $match,
                    LD . 'if ' . implode($andor, $cond) . RD,
                    $tagdata
                );
            }
        }

        return $tagdata;
    }
}

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
 * Is current request an Ajax request or not?
 *
 * @return     bool
 */
if (! function_exists('is_ajax')) {
    function is_ajax()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
    }
}

// --------------------------------------------------------------

/**
 * Returns TRUE if var is not empty (NULL, FALSE or empty string)
 *
 * @param      mixed
 * @return     bool
 */
if (! function_exists('pro_not_empty')) {
    function pro_not_empty($var)
    {
        $empty = false;

        if (is_null($var) || $var === false || (is_string($var) && ! strlen($var))) {
            $empty = true;
        }

        return ! $empty;
    }
}

// --------------------------------------------------------------

/**
 * Is array numeric; filled with numeric values?
 *
 * @param      array
 * @return     bool
 */
if (! function_exists('pro_array_is_numeric')) {
    function pro_array_is_numeric($array = array())
    {
        $numeric = true;

        foreach ($array as $val) {
            if (! is_numeric($val)) {
                $numeric = false;

                break;
            }
        }

        return $numeric;
    }
}

/**
 * Get prefixed parameters
 *
 * @access     public
 * @param      string
 * @return     array
 */
if (! function_exists('pro_array_get_prefixed')) {
    function pro_array_get_prefixed($array, $prefix = '', $strip = false)
    {
        $vals = array();

        // Do we have a prefix?
        if (is_array($array) && $prefix_length = strlen($prefix)) {
            // Loop through array
            foreach ($array as $key => $val) {
                // Check the prefix
                if (strpos($key, $prefix) === 0) {
                    if ($strip === true) {
                        $key = substr($key, $prefix_length);
                    }
                    $vals[$key] = $val;
                }
            }
        }

        return $vals;
    }
}

/**
 * Add prefix to values in arra
 *
 * @access     public
 * @param      string
 * @return     array
 */
if (! function_exists('pro_array_add_prefix')) {
    function pro_array_add_prefix($array, $prefix = '')
    {
        foreach ($array as &$val) {
            $val = $prefix . (string) $val;
        }

        return $array;
    }
}

// --------------------------------------------------------------

/**
 * Is parameter numeric?
 *
 * @param      string
 * @return     bool
 */
if (! function_exists('pro_param_is_numeric')) {
    function pro_param_is_numeric($str)
    {
        return preg_match('/^(not\s|=)?(\d+[|&]?)+$/i', $str);
    }
}

// --------------------------------------------------------------

/**
 * Order by keywords
 *
 * @param       array
 * @param       array
 * @return      int
 */
if (! function_exists('pro_by_keywords')) {
    function pro_by_keywords($a, $b)
    {
        return strcasecmp($a['keywords_clean'], $b['keywords_clean']);
    }
}

// --------------------------------------------------------------

/**
 * Get languages from config file
 *
 * @return      array
 */
if (! function_exists('pro_languages')) {
    function pro_languages()
    {
        static $langs;

        if (is_null($langs)) {
            $langs = (array) ee()->config->loadFile('languages');
            ksort($langs);
        }

        return $langs;
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
 * Zebra table helper
 *
 * @param       bool
 * @return      string
 */
if (! function_exists('pro_zebra')) {
    function pro_zebra($reset = false)
    {
        static $i = 0;

        if ($reset) {
            $i = 0;
        }

        return (++$i % 2 ? 'odd' : 'even');
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
        echo '<pre>' . print_r($var, true) . '</pre>';
        if ($exit) {
            exit;
        }
    }
}

// --------------------------------------------------------------

/* End of file pro_search_helper.php */
