<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Xml_encode Plugin
 */
class Xml_encode
{
    public $return_data;

    /**
     * Constructor
     *
     */
    public function __construct($str = '')
    {
        $protect_all = (ee()->TMPL->fetch_param('protect_entities') === 'yes') ? true : false;

        $str = ($str == '') ? ee()->TMPL->tagdata : $str;

        // Load the XML Helper
        ee()->load->helper('xml');

        $str = xml_convert(strip_tags($str), $protect_all);

        // Strip [email] tags
        $str = preg_replace("/\[email=(.*?)\](.*?)\[\/email\]/i", '\\2', $str);
        $str = preg_replace("/\[email\](.*?)\[\/email\]/i", '\\1', $str);

        $this->return_data = trim(str_replace('&nbsp;', '&#160;', $str));
    }
}
// END CLASS

// EOF
