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
 * Javascript
 */
class EE_Javascript
{
    public $global_vars = array();
    public $_javascript_location = 'js';
    public $js;

    public function __construct($params = array())
    {
        $defaults = array('js_library_driver' => 'jquery', 'autoload' => true);

        foreach ($defaults as $key => $val) {
            if (isset($params[$key]) && $params[$key] !== "") {
                $defaults[$key] = $params[$key];
            }
        }

        extract($defaults);

        // load the requested js library
        ee()->load->library('javascript/' . $js_library_driver, array('autoload' => $autoload));
        // make js to refer to current library
        $this->js = & ee()->$js_library_driver;

        log_message('debug', "Javascript Class Initialized and loaded.  Driver used: $js_library_driver");
    }
    // Event Code
    /**
     * Blur
     *
     * Outputs a javascript library blur event
     *
     * @param	string	The element to attach the event to
     * @param	string	The code to execute
     * @return	string
     */
    public function blur($element = 'this', $js = '')
    {
        return $this->js->_blur($element, $js);
    }

    /**
     * Change
     *
     * Outputs a javascript library change event
     *
     * @param	string	The element to attach the event to
     * @param	string	The code to execute
     * @return	string
     */
    public function change($element = 'this', $js = '')
    {
        return $this->js->_change($element, $js);
    }

    /**
     * Click
     *
     * Outputs a javascript library click event
     *
     * @param	string	The element to attach the event to
     * @param	string	The code to execute
     * @param	boolean	whether or not to return false
     * @return	string
     */
    public function click($element = 'this', $js = '', $ret_false = true)
    {
        return $this->js->_click($element, $js, $ret_false);
    }

    /**
     * Double Click
     *
     * Outputs a javascript library dblclick event
     *
     * @param	string	The element to attach the event to
     * @param	string	The code to execute
     * @return	string
     */
    public function dblclick($element = 'this', $js = '')
    {
        return $this->js->_dblclick($element, $js);
    }

    /**
     * Error
     *
     * Outputs a javascript library error event
     *
     * @param	string	The element to attach the event to
     * @param	string	The code to execute
     * @return	string
     */
    public function error($element = 'this', $js = '')
    {
        return $this->js->_error($element, $js);
    }

    /**
     * Focus
     *
     * Outputs a javascript library focus event
     *
     * @param	string	The element to attach the event to
     * @param	string	The code to execute
     * @return	string
     */
    public function focus($element = 'this', $js = '')
    {
        return $this->js->__add_event($focus, $js);
    }

    /**
     * Hover
     *
     * Outputs a javascript library hover event
     *
     * @param	string	- element
     * @param	string	- Javascript code for mouse over
     * @param	string	- Javascript code for mouse out
     * @return	string
     */
    public function hover($element = 'this', $over = '', $out = '')
    {
        return $this->js->__hover($element, $over, $out);
    }

    /**
     * Keydown
     *
     * Outputs a javascript library keydown event
     *
     * @param	string	The element to attach the event to
     * @param	string	The code to execute
     * @return	string
     */
    public function keydown($element = 'this', $js = '')
    {
        return $this->js->_keydown($element, $js);
    }

    /**
     * Keyup
     *
     * Outputs a javascript library keydown event
     *
     * @param	string	The element to attach the event to
     * @param	string	The code to execute
     * @return	string
     */
    public function keyup($element = 'this', $js = '')
    {
        return $this->js->_keyup($element, $js);
    }

    /**
     * Load
     *
     * Outputs a javascript library load event
     *
     * @param	string	The element to attach the event to
     * @param	string	The code to execute
     * @return	string
     */
    public function load($element = 'this', $js = '')
    {
        return $this->js->_load($element, $js);
    }

    /**
     * Mousedown
     *
     * Outputs a javascript library mousedown event
     *
     * @param	string	The element to attach the event to
     * @param	string	The code to execute
     * @return	string
     */
    public function mousedown($element = 'this', $js = '')
    {
        return $this->js->_mousedown($element, $js);
    }

    /**
     * Mouse Out
     *
     * Outputs a javascript library mouseout event
     *
     * @param	string	The element to attach the event to
     * @param	string	The code to execute
     * @return	string
     */
    public function mouseout($element = 'this', $js = '')
    {
        return $this->js->_mouseout($element, $js);
    }

    /**
     * Mouse Over
     *
     * Outputs a javascript library mouseover event
     *
     * @param	string	The element to attach the event to
     * @param	string	The code to execute
     * @return	string
     */
    public function mouseover($element = 'this', $js = '')
    {
        return $this->js->_mouseover($element, $js);
    }

    /**
     * Mouseup
     *
     * Outputs a javascript library mouseup event
     *
     * @param	string	The element to attach the event to
     * @param	string	The code to execute
     * @return	string
     */
    public function mouseup($element = 'this', $js = '')
    {
        return $this->js->_mouseup($element, $js);
    }

    /**
     * Output
     *
     * Outputs the called javascript to the screen
     *
     * @param	string	The code to output
     * @return	string
     */
    public function output($js)
    {
        return $this->js->_output($js);
    }

    /**
     * Ready
     *
     * Outputs a javascript library mouseup event
     *
     * @param	string	The element to attach the event to
     * @param	string	The code to execute
     * @return	string
     */
    public function ready($js)
    {
        return $this->js->_document_ready($js);
    }

    /**
     * Resize
     *
     * Outputs a javascript library resize event
     *
     * @param	string	The element to attach the event to
     * @param	string	The code to execute
     * @return	string
     */
    public function resize($element = 'this', $js = '')
    {
        return $this->js->_resize($element, $js);
    }

    /**
     * Scroll
     *
     * Outputs a javascript library scroll event
     *
     * @param	string	The element to attach the event to
     * @param	string	The code to execute
     * @return	string
     */
    public function scroll($element = 'this', $js = '')
    {
        return $this->js->_scroll($element, $js);
    }

    /**
     * Unload
     *
     * Outputs a javascript library unload event
     *
     * @param	string	The element to attach the event to
     * @param	string	The code to execute
     * @return	string
     */
    public function unload($element = 'this', $js = '')
    {
        return $this->js->_unload($element, $js);
    }
    // Effects

    /**
     * Add Class
     *
     * Outputs a javascript library addClass event
     *
     * @param	string	- element
     * @param	string	- Class to add
     * @return	string
     */
    public function addClass($element = 'this', $class = '')
    {
        return $this->js->_addClass($element, $class);
    }

    /**
     * Animate
     *
     * Outputs a javascript library animate event
     *
     * @param	string	- element
     * @param	string	- One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param	string	- Javascript callback function
     * @return	string
     */
    public function animate($element = 'this', $params = array(), $speed = '', $extra = '')
    {
        return $this->js->_animate($element, $params, $speed, $extra);
    }

    /**
     * Fade In
     *
     * Outputs a javascript library hide event
     *
     * @param	string	- element
     * @param	string	- One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param	string	- Javascript callback function
     * @return	string
     */
    public function fadeIn($element = 'this', $speed = '', $callback = '')
    {
        return $this->js->_fadeIn($element, $speed, $callback);
    }

    /**
     * Fade Out
     *
     * Outputs a javascript library hide event
     *
     * @param	string	- element
     * @param	string	- One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param	string	- Javascript callback function
     * @return	string
     */
    public function fadeOut($element = 'this', $speed = '', $callback = '')
    {
        return $this->js->_fadeOut($element, $speed, $callback);
    }
    /**
     * Slide Up
     *
     * Outputs a javascript library slideUp event
     *
     * @param	string	- element
     * @param	string	- One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param	string	- Javascript callback function
     * @return	string
     */
    public function slideUp($element = 'this', $speed = '', $callback = '')
    {
        return $this->js->_slideUp($element, $speed, $callback);
    }

    /**
     * Remove Class
     *
     * Outputs a javascript library removeClass event
     *
     * @param	string	- element
     * @param	string	- Class to add
     * @return	string
     */
    public function removeClass($element = 'this', $class = '')
    {
        return $this->js->_removeClass($element, $class);
    }

    /**
     * Slide Down
     *
     * Outputs a javascript library slideDown event
     *
     * @param	string	- element
     * @param	string	- One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param	string	- Javascript callback function
     * @return	string
     */
    public function slideDown($element = 'this', $speed = '', $callback = '')
    {
        return $this->js->_slideDown($element, $speed, $callback);
    }

    /**
     * Slide Toggle
     *
     * Outputs a javascript library slideToggle event
     *
     * @param	string	- element
     * @param	string	- One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param	string	- Javascript callback function
     * @return	string
     */
    public function slideToggle($element = 'this', $speed = '', $callback = '')
    {
        return $this->js->_slideToggle($element, $speed, $callback);
    }

    /**
     * Hide
     *
     * Outputs a javascript library hide action
     *
     * @param	string	- element
     * @param	string	- One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param	string	- Javascript callback function
     * @return	string
     */
    public function hide($element = 'this', $speed = '', $callback = '')
    {
        return $this->js->_hide($element, $speed, $callback);
    }

    /**
     * Toggle
     *
     * Outputs a javascript library toggle event
     *
     * @param	string	- element
     * @return	string
     */
    public function toggle($element = 'this')
    {
        return $this->js->_toggle($element);
    }

    /**
     * Toggle Class
     *
     * Outputs a javascript library toggle class event
     *
     * @param	string	- element
     * @return	string
     */
    public function toggleClass($element = 'this', $class = '')
    {
        return $this->js->_toggleClass($element, $class);
    }

    /**
     * Show
     *
     * Outputs a javascript library show event
     *
     * @param	string	- element
     * @param	string	- One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param	string	- Javascript callback function
     * @return	string
     */
    public function show($element = 'this', $speed = '', $callback = '')
    {
        return $this->js->_show($element, $speed, $callback);
    }

    /**
     * Clear Compile
     *
     * Clears any previous javascript collected for output
     *
     * @return	void
     */
    public function clear_compile()
    {
        $this->js->_clear_compile();
    }

    /**
     * External
     *
     * Outputs a <script> tag with the source as an external js file
     *
     * @param	string	The element to attach the event to
     * @return	string
     */
    public function external($external_file = '', $relative = false)
    {
        if ($external_file !== '') {
            $this->_javascript_location = $external_file;
        } else {
            if (ee()->config->item('javascript_location') != '') {
                $this->_javascript_location = ee()->config->item('javascript_location');
            }
        }

        if ($relative === true or strncmp($external_file, 'http://', 7) == 0 or strncmp($external_file, 'https://', 8) == 0) {
            $str = $this->_open_script($external_file);
        } elseif (strpos($this->_javascript_location, 'http://') !== false) {
            $str = $this->_open_script($this->_javascript_location . $external_file);
        } else {
            $str = $this->_open_script(ee()->config->slash_item('base_url') . $this->_javascript_location . $external_file);
        }

        $str .= $this->_close_script();

        return $str;
    }

    /**
     * Inline
     *
     * Outputs a <script> tag
     *
     * @param	string	The element to attach the event to
     * @param	boolean	If a CDATA section should be added
     * @return	string
     */
    public function inline($script, $cdata = true)
    {
        $str = $this->_open_script();
        $str .= ($cdata) ? "\n// <![CDATA[\n{$script}\n// ]]>\n" : "\n{$script}\n";
        $str .= $this->_close_script();

        return $str;
    }

    /**
     * Open Script
     *
     * Outputs an opening <script>
     *
     * @param	string
     * @return	string
     */
    private function _open_script($src = '')
    {
        $str = '<script type="text/javascript" charset="' . strtolower(ee()->config->item('charset')) . '"';
        $str .= ($src == '') ? '>' : ' src="' . $src . '">';

        return $str;
    }

    /**
     * Close Script
     *
     * Outputs an closing </script>
     *
     * @param	string
     * @return	string
     */
    private function _close_script($extra = "\n")
    {
        return "</script>$extra";
    }

    /**
     * Update
     *
     * Outputs a javascript library slideDown event
     *
     * @param	string	- element
     * @param	string	- One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param	string	- Javascript callback function
     * @return	string
     */
    public function update($element = 'this', $speed = '', $callback = '')
    {
        return $this->js->_updater($element, $speed, $callback);
    }

    /**
     * Is associative array
     *
     * Checks for an associative array
     *
     * @param	type
     * @return	type
     */
    public function _is_associative_array($arr)
    {
        foreach (array_keys($arr) as $key => $val) {
            if ($key !== $val) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prep Args
     *
     * Ensures a standard json value and escapes values
     *
     * @param	type
     * @return	type
     */
    public function _prep_args($result, $is_key = false)
    {
        if (is_null($result)) {
            return 'null';
        } elseif (is_bool($result)) {
            return ($result === true) ? 'true' : 'false';
        } elseif (is_string($result) or $is_key) {
            return '"' . str_replace(array('\\', "\t", "\n", "\r", '"', '/'), array('\\\\', '\\t', '\\n', "\\r", '\"', '\/'), $result) . '"';
        } elseif (is_scalar($result)) {
            return $result;
        }
    }

    /**
     * Set Global
     *
     * Add a variable to the EE javascript object.  Useful if you need
     * to dynamically set variables for your external script.  Will intelligently
     * resolve namespaces (i.e. filemanager.filelist) - use them.
     */
    public function set_global($var, $val = '')
    {
        if (is_array($var)) {
            foreach ($var as $k => $v) {
                $this->set_global($k, $v);
            }

            return;
        }

        $sections = explode('.', $var);
        $var_name = array_pop($sections);

        $current = & $this->global_vars;

        foreach ($sections as $namespace) {
            if (! isset($current[$namespace])) {
                $current[$namespace] = array();
            }

            $current = & $current[$namespace];
        }

        if (is_array($val) &&
            isset($current[$var_name]) &&
            is_array($current[$var_name]) &&
            array_keys($val) !== range(0, count($val) - 1)) {
            $current[$var_name] = array_unique(array_merge($current[$var_name], $val), SORT_STRING);
        } else {
            $current[$var_name] = $val;
        }
    }

    /**
     * Compile
     *
     * gather together all script needing to be output
     *
     * @param	string	The element to attach the event to
     * @return	string
     */
    public function compile($view_var = 'script_foot', $script_tags = true)
    {
        $this->js->_compile($view_var, $script_tags);

        ee()->view->cp_global_js = $this->get_global();
    }

    /**
     * Prepares and returns the HTML+JS for injecting variables into the EE
     * namespace.
     *
     * @param  null|array A list of keys for global variables that should be included
     * @return string The HTML markup containing our JS.
     */
    public function get_global($keys = null)
    {
        $compiled = '';
        $variables = (empty($keys)) ? $this->global_vars : array_intersect_key($this->global_vars, array_flip($keys));
        $encodedVariables = json_encode($variables);

        if (REQ == 'CP') {
            $compiled = '
        document.documentElement.className += "js";';
        }
        $compiled .= '
        if (typeof EE == "undefined" || ! EE) {
            var EE = ' . $encodedVariables . ';
        } else {
            EE = Object.assign(EE, ' . $encodedVariables . ');
        }

        if (typeof console === "undefined" || ! console.log) {
            console = { log: function() { return false; }};
        }';
        return $this->inline($compiled);
    }

    /**
     * Prepares and returns the JS to be output in the foot
     *
     * @return string The HTML markup for our foot JS
     */
    public function script_foot()
    {
        return $this->js->_compile('script_foot', true);
    }
}

// END EE_Javascript

// EOF
