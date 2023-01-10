<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Utf8 Class
 *
 * Provides support for UTF-8 environments
 */
class EE_Utf8
{
    /**
     * Constructor
     *
     * Determines if UTF-8 support is to be enabled
     *
     */
    public function __construct()
    {
        log_message('debug', "Utf8 Class Initialized");

        global $CFG;

        if (
            preg_match('/./u', 'é') === 1						// PCRE must support UTF-8
            and function_exists('iconv')						// iconv must be installed
            and $CFG->item('charset') == 'UTF-8'				// Application charset must be UTF-8
            ) {
            log_message('debug', "UTF-8 Support Enabled");

            define('UTF8_ENABLED', true);

            // set internal encoding for multibyte string functions if necessary
            // and set a flag so we don't have to repeatedly use extension_loaded()
            // or function_exists()
            if (extension_loaded('mbstring')) {
                define('MB_ENABLED', true);
                mb_internal_encoding('UTF-8');
            } else {
                define('MB_ENABLED', false);
            }
        } else {
            log_message('debug', "UTF-8 Support Disabled");
            define('UTF8_ENABLED', false);
            define('MB_ENABLED', false);
        }
    }

    /**
     * Clean UTF-8 strings
     *
     * Ensures strings are UTF-8
     *
     * @param	string
     * @return	string
     */
    public function clean_string($str)
    {
        if ($this->_is_ascii($str) === false) {
            $str = @iconv('UTF-8', 'UTF-8//IGNORE', $str);
        }

        return $str;
    }

    /**
     * Remove ASCII control characters
     *
     * Removes all ASCII control characters except horizontal tabs,
     * line feeds, and carriage returns, as all others can cause
     * problems in XML
     *
     * @param	string
     * @return	string
     */
    public function safe_ascii_for_xml($str)
    {
        return remove_invisible_characters($str, false);
    }

    /**
     * Convert to UTF-8
     *
     * Attempts to convert a string to UTF-8
     *
     * @param	string
     * @param	string	- input encoding
     * @return	string
     */
    public function convert_to_utf8($str, $encoding)
    {
        if (function_exists('iconv')) {
            $str = @iconv($encoding, 'UTF-8', $str);
        } elseif (function_exists('mb_convert_encoding')) {
            $str = @mb_convert_encoding($str, 'UTF-8', $encoding);
        } else {
            return false;
        }

        return $str;
    }

    /**
     * Is ASCII?
     *
     * Tests if a string is standard 7-bit ASCII or not
     *
     * @param	string
     * @return	bool
     */
    public function _is_ascii($str)
    {
        return (preg_match('/[^\x00-\x7F]/S', $str) == 0);
    }
}
// End Utf8 Class

// EOF
