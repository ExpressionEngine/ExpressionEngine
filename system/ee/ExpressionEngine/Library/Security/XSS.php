<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Security;

/**
 * Security XSS
 */
class XSS
{
    private static $antiXss;

    public function __construct()
    {
        self::$antiXss = new \ExpressionEngine\Dependency\voku\helper\AntiXSS();
        self::$antiXss->setReplacement('[removed]');
        self::$antiXss->addNaughtyJavascriptPatterns(['console.log']);
    }

    /**
     * XSS Clean
     *
     * Sanitizes data so that Cross Site Scripting Hacks can be
     * prevented.  This function does a fair amount of work but
     * it is extremely thorough, designed to prevent even the
     * most obscure XSS attempts.  Nothing is ever 100% foolproof,
     * of course, but I haven't been able to get anything past
     * the filter.
     *
     * Note: This function should only be used to deal with data
     * upon submission.  It's not something that should
     * be used for general runtime processing.
     *
     * This function was based in part on some code and ideas I
     * got from Bitflux: http://wiki.flux-cms.org/display/BLOG/XSS+Prevention
     *
     * To help develop this script I used this great list of
     * vulnerabilities along with a few other hacks I've
     * harvested from examining vulnerabilities in other programs:
     *
     * @param   string|array[string]    $str    The string to be cleaned or an
     *      array of strings to be cleaned.  This needs to contain enough of the
     *      context to allow it to properly be cleaned, but shouldn't be the whole
     *      final output.  For example, if the data to be cleaned is going to wind
     *      up in the href attribute of a link (<a> tag) then the string needs to
     *      include the full anchor tag.  If attributes of the tag contain dangerous
     *      javascript, the whole attribute will be removed.
     * @param   boolean $is_image   If the data is an image file it requires some special
     *      processing to preserve the meta data.
     * @return  string  The string cleaned of dangerous code.  If an attribute contains dangerous
     *      code it will be removed entirely.  Certain HTML tags will be encoded (html and body
     *      among them).
     */
    public function clean($str, $is_image = false)
    {
        return self::$antiXss->xss_clean($str);
    }

    /**
     * HTML Entities Decode
     *
     * A replacement for html_entity_decode()
     *
     * The reason we are not using html_entity_decode() by itself is because
     * while it is not technically correct to leave out the semicolon
     * at the end of an entity most browsers will still interpret the entity
     * correctly. html_entity_decode() does not convert entities without
     * semicolons, so we are left with our own little solution here. Bummer.
     *
     * @link        http://php.net/html-entity-decode
     *
     * @param        string        $str                Input
     * @param        string        $charset        Character set
     * @return        string
     */
    public function entity_decode($str, $charset = null)
    {
        return self::$antiXss->_entity_decode($str);
    }
}

// EOF
