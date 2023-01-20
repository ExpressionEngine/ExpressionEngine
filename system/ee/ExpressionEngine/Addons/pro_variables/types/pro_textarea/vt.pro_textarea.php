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

class Pro_textarea extends Pro_variables_type
{
    public $info = array(
        'name' => 'Textarea'
    );

    public $default_settings = array(
        'text_direction' => 'ltr',
        'code_format'    => false,
        'wide'           => 'n',
    );

    // --------------------------------------------------------------------

    /**
     * Display settings sub-form for this variable type
     */
    public function display_settings()
    {
        $rows = array(
            PVUI::setting('dir', $this->setting_name('text_direction'), $this->settings('text_direction')),
            array(
                'title' => 'enable_code_format',
                'fields' => array(
                    $this->setting_name('code_format') => array(
                        'type' => 'yes_no',
                        'value' => $this->settings('code_format') ?: 'n'
                    )
                )
            )
        );

        return $this->settings_form($rows);
    }

    // --------------------------------------------------------------------

    /**
     * Display input field for regular user
     */
    public function display_field($var_data)
    {
        // -------------------------------------
        //  Set class name for textarea
        // -------------------------------------

        $attrs = 'dir="' . $this->settings('text_direction', 'ltr') . '"';

        if ($this->settings('code_format') == 'y') {
            $attrs .= ' class="pro_code_format"';
        }

        // -------------------------------------
        //  Return input field(s)
        // -------------------------------------

        return array($this->input_name() => array(
            'type'  => 'textarea',
            'value' => $var_data,
            'attrs' => $attrs
        ));
    }

    /**
     * Are we displaying a wide field?
     */
    public function wide()
    {
        return ($this->settings('wide') == 'y');
    }

    // --------------------------------------------------------------------

    /**
     * Display output, possible formatting, extra processing
     */
    public function replace_tag($tagdata)
    {
        $var_data = $this->data();

        // -------------------------------------
        //  Check for extra vars to be pre-parsed
        // -------------------------------------

        $param_pfx = 'preparse:';
        $var_pfx = ee()->TMPL->fetch_param('preparse_prefix', 'preparse');
        $offset = strlen($param_pfx);
        $extra = ee()->config->_global_vars;

        // Include segment vars
        for ($i = 1; $i < 13; $i++) {
            $extra['segment_' . $i] = ee()->uri->segment($i);
        }

        // Add current time
        $extra['current_time'] = ee()->localize->now;

        foreach (ee()->TMPL->tagparams as $key => $val) {
            if (substr($key, 0, $offset) == $param_pfx) {
                $key = substr($key, $offset);
                $extra[$var_pfx . ':' . $key] = $val;
                $extra[$key] = $val; // Backwards compat
            }
        }

        // Look for any var_pfx:foo vars in here and set them as vars if they don't already exist
        // Makes them act like embed-vars.
        if (preg_match_all("/({$var_pfx}:[\w\-:]+)/", $var_data, $matches)) {
            foreach ($matches[0] as $key) {
                if (! array_key_exists($key, $extra)) {
                    $extra[$key] = null;
                }
            }
        }

        ee()->TMPL->log_item('Pro Variables: parsing globals and preparse vars');
        $var_data = ee()->TMPL->parse_variables_row($var_data, $extra);

        // -------------------------------------
        //  Is there a formatting parameter?
        //  If so, apply the given format
        // -------------------------------------

        if ($format = ee()->TMPL->fetch_param('formatting')) {
            ee()->TMPL->log_item("Pro Variables: Pro_textarea applying {$format} formatting");

            ee()->load->library('typography');

            // Set options
            $options = array('text_format' => $format);

            // Allow for html_format
            if ($html = ee()->TMPL->fetch_param('html')) {
                $options['html_format'] = $html;
            }

            // Run the Typo method
            $var_data = ee()->typography->parse_type($var_data, $options);
        }

        // -------------------------------------
        // return (formatted) data
        // -------------------------------------

        return (empty($tagdata)
            ? $var_data
            : str_replace(LD . $this->name() . RD, $var_data, $tagdata));
    }

    // --------------------------------------------------------------------
}
// End of vt.pro_textarea.php
