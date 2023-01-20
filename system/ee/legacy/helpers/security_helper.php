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
 * Security Helpers
 */

/**
 * XSS Filtering
 *
 * @access	public
 * @param	string
 * @param	bool	whether or not the content is an image file
 * @return	string
 */
if (! function_exists('xss_clean')) {
    function xss_clean($str, $is_image = false)
    {
        return ee('Security/XSS')->clean($str, $is_image);
    }
}

/**
 * Sanitize Filename
 *
 * @access	public
 * @param	string
 * @return	string
 */
if (! function_exists('sanitize_filename')) {
    function sanitize_filename($filename)
    {
        return ee()->security->sanitize_filename($filename);
    }
}

/**
 * Strip Image Tags
 *
 * @access	public
 * @param	string
 * @return	string
 */
if (! function_exists('strip_image_tags')) {
    function strip_image_tags($str)
    {
        $str = preg_replace("#<img\s+.*?src\s*=\s*[\"'](.+?)[\"'].*?\>#", "\\1", $str);
        $str = preg_replace("#<img\s+.*?src\s*=\s*(.+?).*?\>#", "\\1", $str);

        return $str;
    }
}

/**
 * Convert PHP tags to entities
 *
 * @access	public
 * @param	string
 * @return	string
 */
if (! function_exists('encode_php_tags')) {
    function encode_php_tags($str)
    {
        $str = str_replace(
            array('<?php', '<?PHP', '<?', '?>', '<%', '%>'),
            array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;', '&lt;%', '%&gt;'),
            $str
        );

        if (stristr($str, '<script') &&
            preg_match_all("/<script.*?language\s*=\s*(\042|\047)?php(\\1)?.*?>.*?<\/script>/is", $str, $matches)) {
            foreach ($matches[0] as $match) {
                $str = str_replace($match, htmlspecialchars($match), $str);
            }
        }

        return $str;
    }
}

// EOF
