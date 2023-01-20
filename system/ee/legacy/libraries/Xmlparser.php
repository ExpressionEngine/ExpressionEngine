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
 * XML Parser Cache
 */
class XML_Cache
{
    public $tag;
    public $attributes;
    public $value;
    public $children;
}
/**
 * XML Parser
 *
 * Contains all of the methods for handling XML data
 */
class EE_XMLparser
{
    public $tagdata;
    public $index;
    public $errors = array();
    public $encoding = '';		// 'ISO-8859-1', 'UTF-8', or 'US-ASCII' - empty string should auto-detect

    public function __construct()
    {
        // Load the XML Helper
        ee()->load->helper('xml');
    }

    /** -------------------------------------
    /**  Parse the XML data into an array
    /** -------------------------------------*/
    public function parse_xml($xml)
    {
        // PHP's XML array structures stink so make our own
        if ($this->parse_into_struct($xml) === false) {
            error_log('Unable to parse XML data');

            return false;
        } else {
            $elements = array();
            $child = array();
            foreach ($this->tagdata as $item) {
                $current = count($elements);

                if ($item['type'] == 'open' or $item['type'] == 'complete') {
                    $elements[$current] = new XML_Cache();
                    $elements[$current]->tag = $item['tag'];
                    $elements[$current]->attributes = (array_key_exists('attributes', $item)) ? $item['attributes'] : '';
                    $elements[$current]->value = (array_key_exists('value', $item)) ? $item['value'] : '';

                    /** -------------------------------------
                    /**  Create a new child layer for 'open'
                    /** -------------------------------------*/
                    if ($item['type'] == "open") {
                        $elements[$current]->children = array();
                        $child[count($child)] = &$elements;
                        $elements = &$elements[$current]->children;
                    }
                }

                /** -------------------------------------
                /**  Put child layer into root object
                /** -------------------------------------*/
                elseif ($item['type'] == 'close') {
                    $elements = &$child[count($child) - 1];
                    unset($child[count($child) - 1]);
                }
            }
        }

        return $elements[0];
    }

    /** -------------------------------------
    /**  Convert delimited text to XML
    /** -------------------------------------*/
    public function delimited_to_xml($params, $reduce_null = false)
    {
        if (! is_array($params)) {
            return false;
        }

        $defaults = array(
            'data' => null,
            'structure' => array(),
            'root' => 'root',
            'element' => 'element',
            'delimiter' => "\t",
            'enclosure' => ''
        );

        foreach ($defaults as $key => $val) {
            if (! isset($params[$key])) {
                $params[$key] = $val;
            }
        }

        extract($params);

        /*
          $data 		- string containing delimited data
          $structure 	- array providing a key for $data elements
          $root			- the root XML document tag name
          $element		- the tag name for the element used to enclose the tag data
          $delimiter	- the character delimiting the text, default is \t (tab)
          $enclosure	- character used to enclose the data, such as " in the case of $data = '"item", "item2", "item3"';
        */

        if ($data === null or ! is_array($structure) or count($structure) == 0) {
            return false;
        }

        /** -------------------------------------
        /**  Convert delimited text to array
        /** -------------------------------------*/
        $data_arr = array();
        $data = str_replace(array("\r\n", "\r"), "\n", $data);
        $lines = explode("\n", $data);

        if (empty($lines)) {
            $this->errors[] = "No data to work with";

            return false;
        }

        if ($enclosure == '') {
            foreach ($lines as $key => $val) {
                if (! empty($val)) {
                    $data_arr[$key] = explode($delimiter, $val);
                }
            }
        } else {  // values are enclosed by a character, e.g.: "value","value2","value3"
            foreach ($lines as $key => $val) {
                if (! empty($val)) {
                    preg_match_all("/" . preg_quote($enclosure) . "(.*?)" . preg_quote($enclosure) . "/si", $val, $matches);
                    $data_arr[$key] = $matches[1];

                    if (empty($data_arr[$key])) {
                        $this->errors[] = 'Structure mismatch, skipping line: ' . $val;
                        unset($data_arr[$key]);
                    }
                }
            }
        }

        //  Construct the XML

        $xml = "<{$root}>\n";

        foreach ($data_arr as $datum) {
            if (! empty($datum) and count($datum) == count($structure)) {
                $xml .= "\t<{$element}>\n";

                foreach ($datum as $key => $val) {
                    if ($reduce_null == true && $structure[$key] == '') {
                        continue;
                    }

                    $xml .= "\t\t<{$structure[$key]}>" . xml_convert($val) . "</{$structure[$key]}>\n";
                }

                $xml .= "\t</{$element}>\n";
            } else {
                $details = '';

                foreach ($datum as $val) {
                    $details .= "{$val}, ";
                }

                $this->errors[] = 'Line does not match structure: ' . substr($details, 0, -2);
            }
        }

        $xml .= "</{$root}>\n";

        if (! stristr($xml, "<{$element}>")) {
            $this->errors[] = "No valid elements to build XML";

            return false;
        }

        return $xml;
    }

    /** -------------------------------------
    /**  Parse XML into PHP's array structures
    /** -------------------------------------*/
    public function parse_into_struct($xml, $case = false)
    {
        // use an empty string to trick PHP into doing what it's supposed to do and auto-detect the encoding
        $parser = ($this->encoding == '') ? xml_parser_create('') : xml_parser_create($this->encoding);
        if ($case === false) {
            xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        }
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);

        $entities = $this->fetch_entity_definitions($xml);
        $xml = ($entities === false) ? $xml : $this->replace_entities($xml, $entities);

        if (xml_parse_into_struct($parser, $xml, $this->tagdata, $this->index) === 0) {
            xml_parser_free($parser);

            return false;
        }

        xml_parser_free($parser);

        return true;
    }

    /** -------------------------------------
    /**  Read XML DTD entity definitions
    /** -------------------------------------*/
    public function fetch_entity_definitions($xml)
    {
        $entities = array();

        preg_match_all("/\<\!ENTITY\s*([\w-]+)\s*\"(.+)\"/siU", $xml, $matches);

        if (isset($matches[0][0])) {
            $entities[0] = $matches[1];
            $entities[1] = $matches[2];

            return $entities;
        }

        return false;
    }

    /** -------------------------------------
    /**  Replace DTD entities in XML
    /** -------------------------------------*/
    public function replace_entities($xml, $entities)
    {
        foreach ($entities[0] as $key => $val) {
            $xml = str_replace('&' . $entities[0][$key] . ';', $entities[1][$key], $xml);
        }

        return $xml;
    }
}

// EOF
