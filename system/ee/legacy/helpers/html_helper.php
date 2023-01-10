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
 * HTML Helpers
 */

/**
 * Heading
 *
 * Generates an HTML heading tag.  First param is the data.
 * Second param is the size of the heading tag.
 *
 * @access	public
 * @param	string
 * @param	integer
 * @return	string
 */
if (! function_exists('heading')) {
    function heading($data = '', $h = '1', $attributes = '')
    {
        $attributes = ($attributes != '') ? ' ' . $attributes : $attributes;

        return "<h" . $h . $attributes . ">" . $data . "</h" . $h . ">";
    }
}

/**
 * Unordered List
 *
 * Generates an HTML unordered list from an single or multi-dimensional array.
 *
 * @access	public
 * @param	array
 * @param	mixed
 * @return	string
 */
if (! function_exists('ul')) {
    function ul($list, $attributes = '')
    {
        return _list('ul', $list, $attributes);
    }
}

/**
 * Ordered List
 *
 * Generates an HTML ordered list from an single or multi-dimensional array.
 *
 * @access	public
 * @param	array
 * @param	mixed
 * @return	string
 */
if (! function_exists('ol')) {
    function ol($list, $attributes = '')
    {
        return _list('ol', $list, $attributes);
    }
}

/**
 * Generates the list
 *
 * Generates an HTML ordered list from an single or multi-dimensional array.
 *
 * @access	private
 * @param	string
 * @param	mixed
 * @param	mixed
 * @param	integer
 * @return	string
 */
if (! function_exists('_list')) {
    function _list($type = 'ul', $list = '', $attributes = '', $depth = 0)
    {
        // If an array wasn't submitted there's nothing to do...
        if (! is_array($list)) {
            return $list;
        }

        // Set the indentation based on the depth
        $out = str_repeat(" ", $depth);

        // Were any attributes submitted?  If so generate a string
        if (is_array($attributes)) {
            $atts = '';
            foreach ($attributes as $key => $val) {
                $atts .= ' ' . $key . '="' . $val . '"';
            }
            $attributes = $atts;
        }

        // Write the opening list tag
        $out .= "<" . $type . $attributes . ">\n";

        // Cycle through the list elements.  If an array is
        // encountered we will recursively call _list()

        static $_last_list_item = '';
        foreach ($list as $key => $val) {
            $_last_list_item = $key;

            $out .= str_repeat(" ", $depth + 2);
            $out .= "<li>";

            if (! is_array($val)) {
                $out .= $val;
            } else {
                $out .= $_last_list_item . "\n";
                $out .= _list($type, $val, '', $depth + 4);
                $out .= str_repeat(" ", $depth + 2);
            }

            $out .= "</li>\n";
        }

        // Set the indentation for the closing tag
        $out .= str_repeat(" ", $depth);

        // Write the closing list tag
        $out .= "</" . $type . ">\n";

        return $out;
    }
}

/**
 * Generates HTML BR tags based on number supplied
 *
 * @access	public
 * @param	integer
 * @return	string
 */
if (! function_exists('br')) {
    function br($num = 1)
    {
        return str_repeat("<br />", $num);
    }
}

/**
 * Image
 *
 * Generates an <img /> element
 *
 * @access	public
 * @param	mixed
 * @return	string
 */
if (! function_exists('img')) {
    function img($src = '', $index_page = false)
    {
        if (! is_array($src)) {
            $src = array('src' => $src);
        }

        // If there is no alt attribute defined, set it to an empty string
        if (! isset($src['alt'])) {
            $src['alt'] = '';
        }

        $img = '<img';

        foreach ($src as $k => $v) {
            if ($k == 'src' and strpos($v, '://') === false) {
                if ($index_page === true) {
                    $img .= ' src="' . ee()->config->site_url($v) . '"';
                } else {
                    $img .= ' src="' . ee()->config->slash_item('base_url') . $v . '"';
                }
            } else {
                $img .= " $k=\"$v\"";
            }
        }

        $img .= '/>';

        return $img;
    }
}

/**
 * Doctype
 *
 * Generates a page document type declaration
 *
 * Valid options are xhtml-11, xhtml-strict, xhtml-trans, xhtml-frame,
 * html4-strict, html4-trans, and html4-frame.  Values are saved in the
 * doctypes config file.
 *
 * @access	public
 * @param	string	type	The doctype to be generated
 * @return	string
 */
if (! function_exists('doctype')) {
    function doctype($type = 'xhtml1-strict')
    {
        $doctypes = ee()->config->loadFile('doctypes');

        if (isset($doctypes[$type])) {
            return $doctypes[$type];
        }

        return false;
    }
}

/**
 * Link
 *
 * Generates link to a CSS file
 *
 * @access	public
 * @param	mixed	stylesheet hrefs or an array
 * @param	string	rel
 * @param	string	type
 * @param	string	title
 * @param	string	media
 * @param	boolean	should index_page be added to the css path
 * @return	string
 */
if (! function_exists('link_tag')) {
    function link_tag($href = '', $rel = 'stylesheet', $type = 'text/css', $title = '', $media = '', $index_page = false)
    {
        $link = '<link ';

        if (is_array($href)) {
            foreach ($href as $k => $v) {
                if ($k == 'href' and strpos($v, '://') === false) {
                    if ($index_page === true) {
                        $link .= 'href="' . ee()->config->site_url($v) . '" ';
                    } else {
                        $link .= 'href="' . ee()->config->slash_item('base_url') . $v . '" ';
                    }
                } else {
                    $link .= "$k=\"$v\" ";
                }
            }

            $link .= "/>";
        } else {
            if (strpos($href, '://') !== false) {
                $link .= 'href="' . $href . '" ';
            } elseif ($index_page === true) {
                $link .= 'href="' . ee()->config->site_url($href) . '" ';
            } else {
                $link .= 'href="' . ee()->config->slash_item('base_url') . $href . '" ';
            }

            $link .= 'rel="' . $rel . '" type="' . $type . '" ';

            if ($media != '') {
                $link .= 'media="' . $media . '" ';
            }

            if ($title != '') {
                $link .= 'title="' . $title . '" ';
            }

            $link .= '/>';
        }

        return $link;
    }
}

/**
 * Generates meta tags from an array of key/values
 *
 * @access	public
 * @param	array
 * @return	string
 */
if (! function_exists('meta')) {
    function meta($name = '', $content = '', $type = 'name', $newline = "\n")
    {
        // Since we allow the data to be passes as a string, a simple array
        // or a multidimensional one, we need to do a little prepping.
        if (! is_array($name)) {
            $name = array(array('name' => $name, 'content' => $content, 'type' => $type, 'newline' => $newline));
        } else {
            // Turn single array into multidimensional
            if (isset($name['name'])) {
                $name = array($name);
            }
        }

        $str = '';
        foreach ($name as $meta) {
            $type = (! isset($meta['type']) or $meta['type'] == 'name') ? 'name' : 'http-equiv';
            $name = (! isset($meta['name'])) ? '' : $meta['name'];
            $content = (! isset($meta['content'])) ? '' : $meta['content'];
            $newline = (! isset($meta['newline'])) ? "\n" : $meta['newline'];

            $str .= '<meta ' . $type . '="' . $name . '" content="' . $content . '" />' . $newline;
        }

        return $str;
    }
}

/**
 * Generates non-breaking space entities based on number supplied
 *
 * @access	public
 * @param	integer
 * @return	string
 */
if (! function_exists('nbs')) {
    function nbs($num = 1)
    {
        return str_repeat("&nbsp;", $num);
    }
}

// EOF
