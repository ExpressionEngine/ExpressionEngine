<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Template\Variables;

/**
 * Legacy Variable Parsing Service
 * This modernizes our interface for a few common methods from legacy code
 */
class LegacyParser
{
    // bring in the :modifier methods
    use ModifiableTrait;

    /**
     * Given a template variable string, this parses out parameters and modifiers
     * e.g. {variable:modifier param1='foo' param2='bar'} returns:
     *
     * array(
     *  'field_name' => 'variable',
     *  'params' => array(
     *      'param1' => 'foo',
     *      'param2' => 'bar',
     *  ),
     *  'modifier' => 'modifier'
     * )
     *
     * Note: 'field_name' is used instead of just 'name' or 'variable_name' as this was
     * originally a method of the legacy Channel Fields API, and the LegacyParser
     * is a non-breaking API change
     *
     * @param  string $template_var Template variable to get the name, modifier, and parameters from
     * @param  string $prefix Optional prefix including colon suffix
     * @return array Variable name, modifier, and parameters
     */
    public function parseVariableProperties($template_var, $prefix = '')
    {
        $props = [
            'invalid_modifier' => false,
        ];

        // strip prefix
        $unprefixed_var = ltrim(preg_replace('/^' . $prefix . '/', '', $template_var));
        // we don't need params - take away what's separated by space
        $orig_field_name_length = strpos($unprefixed_var, ' ') ?: strpos($unprefixed_var, "\n");
        if ($orig_field_name_length === false) {
            $orig_field_name = $unprefixed_var;
        } else {
            $orig_field_name = substr($unprefixed_var, 0, $orig_field_name_length);
        }
        $param_string = trim(substr($unprefixed_var, strlen($orig_field_name)));

        $field_name = $orig_field_name;
        $modifier = '';
        $full_modifier = '';

        $full_modifier_loc = strpos($orig_field_name, ':');
        $modifier_loc = strrpos($orig_field_name, ':');

        // start with the leftmost modifier
        // NOTE: previously we used only the last one
        if ($full_modifier_loc !== false) {
            $field_name = substr($orig_field_name, 0, $full_modifier_loc);
            $full_modifier = substr($orig_field_name, $full_modifier_loc + 1);
            $modifier = $full_modifier;
            if (is_numeric($modifier)) {
                $props['invalid_modifier'] = true;
            }
        }

        if ($modifier_loc !== false && $modifier_loc !== $full_modifier_loc) {
            $modifier = substr($orig_field_name, $modifier_loc + 1);
            if (is_numeric(substr($full_modifier, 0, strpos($full_modifier, ':')))) {
                $props['invalid_modifier'] = true;
            }
        }

        $props['field_name'] = trim($field_name);
        $props['modifier'] = trim($modifier);
        $props['full_modifier'] = trim($full_modifier);
        if (trim($param_string) && ! $props['invalid_modifier']) {
            $props['params'] = $params = $this->parseTagParameters($param_string);
            $prefix = $props['modifier'] . ':';
            $prefix_legnth = strlen($prefix);
            foreach ($props['params'] as $param => $value) {
                if (strpos($param, $prefix) === 0) {
                    $props['params'][substr($param, $prefix_legnth)] = $value;
                }
            }
        } else {
            $props['params'] = $params = [];
        }

        $all_modifiers = explode(':', $props['full_modifier']);
        if (count($all_modifiers) > 1) {
            $props['all_modifiers'] = [];
            foreach ($all_modifiers as $modifier) {
                $modifier_params = $params;
                $prefix = $modifier . ':';
                $prefix_legnth = strlen($prefix);
                foreach ($modifier_params as $param => $value) {
                    if (strpos($param, $prefix) === 0) {
                        $modifier_params[substr($param, $prefix_legnth)] = $value;
                    }
                }
                $props['all_modifiers'][$modifier] = $modifier_params;
            }
        } else {
            $props['all_modifiers'] = [
                $props['modifier'] => $props['params']
            ];
        }

        return $props;
    }

    /**
     * Parse Tag Parameters
     *
     * @param  string $param_string A string of parameters, e.g. param1='foo' param2='bar'
     * @param  array  $defaults     Optional default values
     * @return array Parameters in key (parameter) => value form.
     */
    public function parseTagParameters($param_string, array $defaults = [])
    {
        if ($param_string == "") {
            return $defaults;
        }

        // remove comments before assigning
        $param_string = preg_replace("/\{!--.*?--\}/s", '', $param_string);

        // Using octals for quotes prevents awkward quote escaping for the PHP string and for the regex
        // \047 - Single quote octal
        // \042 - Double quote octal

        // matches[0] => attribute and value
        // matches[1] => attribute name
        // matches[2] => single or double quote
        // matches[3] => attribute value

        $bs = '\\'; // single backslash
        preg_match_all("/(\S+?)\s*=\s*($bs$bs?)(\042|\047)([^\\3]*?)\\2\\3/is", $param_string, $matches, PREG_SET_ORDER);

        if (count($matches) > 0) {
            $result = array();

            foreach ($matches as $match) {
                $result[$match[1]] = (trim($match[4]) == '') ? $match[4] : trim($match[4]);
            }

            foreach ($defaults as $name => $default_value) {
                if (
                    ! isset($result[$name])
                    or (is_numeric($default_value) && ! is_numeric($result[$name]))
                ) {
                    $result[$name] = $default_value;
                }
            }

            return $result;
        }

        return $defaults;
    }

    /**
     * Extract Variables from Tagdata
     *
     * This function extracts the variables contained within the current tag
     * being parsed and assigns them to one of three arrays.
     *
     * There are two types of variables:
     *
     * Single variables: {some_variable}
     *
     * Pair variables: {variable} stuff... {/variable}
     *
     * Each of the two variables is parsed slightly different and appears in its own array
     *
     * This legacy method was originally Functions::assign_variables()
     *
     * @param   string $tagdata The Tagdata to extract variables from
     * @param   string $target A specific variable to target for extraction. Default NULL (all variables)
     * @return  array array('var_single' => ..., 'var_pair' => ..., )
     */
    public function extractVariables($tagdata, $target = null)
    {
        $return['var_single'] = [];
        $return['var_pair'] = [];

        if ($tagdata == '') {
            return $return;
        }

        // No variables?  No reason to continue...
        if (strpos($tagdata, '{') === false) {
            return $return;
        }

        if ($target) {
            preg_match_all('/' . LD . '(' . preg_quote($target, '/') . '.*?)' . RD . '/s', $tagdata, $matches);
        } else {
            preg_match_all('/' . LD . '([^\n].+?)' . RD . '/s', $tagdata, $matches);
        }

        $temp_close = [];
        $temp_misc = [];

        foreach ($matches[1] as $key => $val) {
            if (
                !is_numeric($val) &&
                strncmp($val, 'if ', 3) !== 0 &&
                strncmp($val, 'if:', 3) !== 0 &&
                substr($val, 0, 3) != '/if'
            ) {
                if (strpos($val, '{') !== false) {
                    if (preg_match("/(.+?)" . LD . "(.*)/s", $val, $matches2)) {
                        $temp_misc[$key] = $matches2[2];
                    }
                } elseif (strncmp($val, '/', 1) === 0) {
                    $temp_close[$key] = str_replace('/', '', $val);
                } else {
                    $temp_misc[$key] = $val;
                }
            } elseif (strpos($val, '{') !== false) { // Variable in conditional.  ::sigh::
                $full_conditional = substr($this->getFullTag($tagdata, $matches[0][$key]), 1, -1);

                // We only need the first match here, all others will get caught by our
                // previous code as they won't start with if.

                if (preg_match("/" . LD . "(.*?)" . RD . "/s", $full_conditional, $cond_vars)) {
                    $temp_misc[$key] = $cond_vars[1];
                }
            }
        }

        // $temp_misc contains all (opening) tags
        // $temp_close contains all closing tags

        // In 1.x we assumed that a closing tag meant that the variable was
        // a tag pair.  We now have variables that output as pairs and single tags
        // so we need to properly match the pairs.

        // In order to find proper pairs, we need to find equivalent opening and
        // closing tags that are closest together (no nesting).
        // The easiest way to go about this is to find all opening tags up to a
        // closing tag - and then just take the last one.

        $temp_pair = [];
        $temp_single = [];

        $open_stack = [];

        foreach ($temp_misc as $open_key => $open_tag) {
            if (preg_match("#(.+?)(\s+|=)(.+?)#", $open_tag, $matches)) {
                $open_tag = $matches[1];
            }

            foreach ($temp_close as $close_key => $close_tag) {

                // Find the closest (potential) closing tag following it
                if (($close_key > $open_key) && $open_tag == $close_tag) {
                    // There could be another opening tag between these
                    // so we create a stack of opening tag values
                    $open_stack[$close_key][] = $open_key;

                    continue;
                }
            }
        }

        // Pop the last item off each stack of opening tags - these are pairs
        foreach ($open_stack as $potential_openings) {
            $open_tag_key = array_pop($potential_openings);

            if (isset($temp_misc[$open_tag_key])) {
                $temp_pair[] = $temp_misc[$open_tag_key];
                unset($temp_misc[$open_tag_key]);
            }
        }

        // The rest of them are single tags
        $temp_single = array_values($temp_misc);

        // Weed out the duplicatess
        $temp_single = array_unique($temp_single);
        $temp_pair = array_unique($temp_pair);

        // Assign Single Variables
        $var_single = [];

        foreach ($temp_single as $val) {
            // skip template comments, including runtime annotation markers
            if (strncmp($val, '!--', 3) === 0) {
                continue;
            }

            // simple conditionals
            if (stristr($val, '\|') && substr($val, 0, 6) != 'switch' && substr($val, 0, 11) != 'multi_field') {
                $var_single[$val] = $this->fetch_simple_conditions($val);
            }

            // date variables
            elseif (strpos($val, 'format') !== false && preg_match("/.+?\s+?format/", $val)) {
                $var_single[$val] = $this->extractDateFormat($val);
            } elseif (!is_numeric($val)) {  // single variables
                $var_single[$val] = $val;
            }
        }

        // Assign Variable Pairs
        $var_pair = [];

        foreach ($temp_pair as $val) {
            $var_pair[$val] = ee('Variables/Parser')->parseTagParameters($val);
        }

        $return['var_single'] = $var_single;
        $return['var_pair'] = $var_pair;

        return $return;
    }

    /**
     * Fetch date variables
     *
     * This function looks within a variable for this prototype:
     *
     *  {date format="%Y %m %d"}
     *
     * If found, returns only the date format codes: %Y %m %d
     *
     * @param string the date variable
     * @return string the date format parameter
     */
    public function extractDateFormat($date_string)
    {
        if ($date_string == '') {
            return;
        }

        $bs = '\\'; // single backslash
        if (! preg_match("/format\s*=\s*($bs$bs?)[\'|\"](.*?)\\1[\'|\"]/s", $date_string, $match)) {
            return false;
        }

        return $match[2];
    }

    /**
     * Get Full Tag
     *
     * Useful when tags are nested or split, to make sure you've got the full chunk that you want.
     * Example:
     *
     *  [quote]This is a BBCode style quote. [quote]What kind of quote is this?[/quote] It's still pretty common online.[/quote]
     *
     * A simpler regex may have grabbed only to the first closing tag, resulting in a partially matched tag:
     *
     *  [quote]This is a BBCode style quote. [quote]What kind of quote is this?[/quote]
     *
     * This method will start with your partial match, and expand it to make sure that any matching nested tags that were opened inside
     * of this one are fully closed, so you are left with the complete outer tag's contents.
     *
     * @param string $str The source string / template
     * @param string $partial_tag The partial tag, that might include a nested tag.
     * @param string $opening The opening tag identifier
     * @param string $closing The closing tag identifier
     * @return string The full tag match
     */
    public function getFullTag($str, $partial_tag, $opening = '{', $closing = '}')
    {
        // Warning: preg_match() Compilation failed: regular expression is too large at offset #
        // This error will occur if someone tries to stick over 30k-ish strings as tag parameters that also happen to include curley brackets.
        // Instead of preventing the error, we let it take place, so the user will hopefully visit the forums seeking assistance
        if (! preg_match("/" . preg_quote($partial_tag, '/') . "(.*?)" . preg_quote($closing, '/') . "/s", $str, $matches)) {
            return $partial_tag;
        }

        if (isset($matches[1]) && $matches[1] != '' && stristr($matches[1], $opening) !== false) {
            $matches[0] = $this->getFullTag($str, $matches[0], $opening, $closing);
        }

        return $matches[0];
    }

    /**
     * Parse modified variables {variable:modifier}
     *
     * @param  string $str the string to parse
     * @param  array  $vars Variables source: 'variable_name' => 'content'
     * @return string the string, parsed
     */
    public function parseModifiedVariables($str, $vars = [])
    {
        $conditionals = [];

        foreach ($vars as $name => $value) {
            if (strpos($str, $name . ':') !== false) {
                $prefix = '';

                // embed, layout, etc. will have prefixes
                if (($prefix_pos = strpos($name, ':')) !== false) {
                    $prefix = substr($name, 0, $prefix_pos + 1);
                }

                $extracted_vars = $this->extractVariables($str, $name);

                foreach ($extracted_vars['var_single'] as $modified_var) {
                    $content = $value;
                    $var_props = $this->parseVariableProperties($modified_var, $prefix);

                    // in order to support multiple modifiers, we'll do this in a loop
                    if (isset($var_props['all_modifiers']) && !empty($var_props['all_modifiers'])) {
                        $modifiersCounter = 0;
                        foreach ($var_props['all_modifiers'] as $modifier => $params) {
                            // is the modifier valid?
                            $method = 'replace_' . $modifier;
                            if (! method_exists($this, $method) && ! ee('Variables/Modifiers')->has($modifier)) {
                                if ($modifiersCounter == 0) {
                                    // if first modifier is not valid, we might be dealing with column name and PV early parsing
                                    // continue to next variable
                                    continue 2;
                                }
                                // continue to next modifier
                                continue;
                            }

                            $content = $this->$method($content, $params);
                            $modifiersCounter++;
                        }
                    } else {
                        // fallback to just last modifier if 'all_modifiers' is not set
                        // which should never happen, but...
                        $method = 'replace_' . $var_props['modifier'];
                        if (! method_exists($this, $method) && ! ee('Variables/Modifiers')->has($var_props['modifier'])) {
                            // continue to next variable
                            continue;
                        }

                        $content = $this->$method($content, $var_props['params']);
                    }

                    $str = str_replace(LD . $modified_var . RD, $content, $str);
                    $conditionals[$modified_var] = $content;
                }
            }
        }

        if (! empty($conditionals)) {
            $str = ee()->functions->prep_conditionals(
                $str,
                $conditionals
            );
        }

        return $str;
    }

    /**
     * Parse "OR" parameters
     *
     * Provides a consistent method to handle 'not foo|bar|bat' type parameters.
     * Returns an array of options, and whether or not the options are negated (not true/false):
     *
     *  array (size=2)
     *      'options' =>
     *      array (size=4)
     *          0 => string 'foo' (length=3)
     *          1 => string 'bar' (length=3)
     *          2 => string 'bat' (length=3)
     *          'not' => boolean true
     *
     * @param  string $param The parameter string
     * @return array Array of options and whether the options are negated (not)
     */
    public function parseOrParameter($param)
    {
        $options = [];
        $not = false;

        $param = trim($param);
        if (strncasecmp($param, 'not ', 4) === 0) {
            $param = trim(substr($param, 4));
            $not = true;
        }

        if (strpos($param, '|') !== false) {
            $options = preg_split('/\|/', $param, -1, PREG_SPLIT_NO_EMPTY);
            $options = array_map('trim', $options);
        } elseif (! empty($param)) {
            $options = [$param];
        }

        return [
            'options' => $options,
            'not' => $not,
        ];
    }
}
// END CLASS

// EOF
