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
 * Form Helper
 */

/**
 * Form Declaration
 *
 * Creates the opening portion of the form.
 *
 * @access	public
 * @param	string	the URI segments of the form destination
 * @param	array	a key/value pair of attributes
 * @param	array	a key/value pair hidden data
 * @return	string
 */
if (REQ == 'CP') {
    function form_open($action = '', $attributes = array(), $hidden = array())
    {
        $action = ee()->uri->reformat($action);

        $form = '<form action="' . $action . '"';

        if (is_array($attributes)) {
            if (! isset($attributes['method'])) {
                $form .= ' method="post"';
            }

            if (!isset($attributes['class'])) {
                $attributes['class'] = 'form-standard';
            }

            foreach ($attributes as $key => $val) {
                $form .= ' ' . $key . '="' . $val . '"';
            }
        } else {
            $form .= ' method="post" ' . $attributes;
        }

        $form .= ">\n";

        if (! bool_config_item('disable_csrf_protection')) {
            if (! is_array($hidden)) {
                $hidden = array();
            }

            $hidden['csrf_token'] = CSRF_TOKEN;
        }

        if (is_array($hidden) and count($hidden) > 0) {
            $form .= form_hidden($hidden) . "\n";
        }

        return $form;
    }
} else {
    function form_open($action = '', $attributes = '', $hidden = array())
    {
        if ($attributes == '') {
            $attributes = 'method="post"';
        }

        $action = (strpos($action, '://') === false) ? ee()->config->site_url($action) : $action;

        $form = '<form action="' . $action . '"';

        $form .= _attributes_to_string($attributes, true);

        $form .= '>';

        // CSRF
        if (! bool_config_item('disable_csrf_protection')) {
            $hidden['csrf_token'] = CSRF_TOKEN;
        }

        if (is_array($hidden) and count($hidden) > 0) {
            $form .= sprintf("<div style=\"display:none\">%s</div>", form_hidden($hidden));
        }

        return $form;
    }
}

if (REQ == 'CP') {
    /**
     * Yes / No radio buttons
     *
     * Creates the typical EE yes/no options for a form
     *
     * @access	public
     * @param	string	    the name of the input
     * @param	string|bool checked state as 'y/n' or true/false
     * @return	string      form inputs
     */
    function form_yes_no_toggle($name, $value)
    {
        $insertion_point = strcspn($name, '['); // add y/n flag before arrays
        $name_no = substr_replace($name, '_n', $insertion_point, 0);
        $name_yes = substr_replace($name, '_y', $insertion_point, 0);

        $value = is_bool($value) ? $value : $value == 'y';

        return
            form_radio($name, 'y', $value, 'id="' . $name_yes . '"') .
            NBS .
            lang('yes', $name_yes) .
            NBS . NBS . NBS . NBS . NBS .
            form_radio($name, 'n', (! $value), 'id="' . $name_no . '"') .
            NBS .
            lang('no', $name_no);
    }
}

/**
 * Parses the data from ee()->config->prep_view_vars() and returns
 * the appropriate form control.
 *
 * @access	public
 * @param	string	$name	The name of the field
 * @param	mixed[]	$details	The details related to the field
 *  	e.g.	'type'     => 'r'
 *  			'value'    => 'us'
 *  			'subtext'  => ''
 *  			'selected' => 'us'
 * @return	string	form input
 */
function form_preference($name, $details)
{
    $pref = '';
    switch ($details['type']) {
        // Select
        case 's':
            if (is_array($details['value'])) {
                $pref = form_dropdown($name, $details['value'], $details['selected'], 'id="' . $name . '"');
            } else {
                $pref = '<span class="notice">' . lang('not_available') . '</span>';
            }

            break;
        // Multi Select
        case 'ms':
            $pref = form_multiselect($name . '[]', $details['value'], $details['selected'], 'id="' . $name . '" size="8"');

            break;
        // Radio
        case 'r':
            if (is_array($details['value'])) {
                foreach ($details['value'] as $options) {
                    $pref .= form_radio($options) . NBS . lang($options['label'], $options['id']) . NBS . NBS . NBS . NBS;
                }
            } else {
                $pref = '<span class="notice">' . lang('not_available') . '</span>';
            }

            break;
        // Textarea
        case 't':
            $pref = form_textarea($details['value']);

            break;
        // Input
        case 'i':
            $pref = form_input(array_merge($details['value'], array('id' => $name, 'class' => 'input fullfield', 'size' => 20, 'maxlength' => 120)));

            break;
        // Password
        case 'p':
            $pref = form_password(array_merge($details['value'], array('id' => $name, 'class' => 'input fullfield', 'size' => 20, 'maxlength' => PASSWORD_MAX_LENGTH)));

            break;
        // Checkbox
        case 'c':
            foreach ((array) $details['value'] as $options) {
                $pref .= form_checkbox($options) . NBS . lang($options['label'], $options['id']) . NBS . NBS . NBS . NBS;
            }

            break;
        // Pass the raw value through
        case 'v':
            $pref = $details['value'];

            break;
    }

    return $pref;
}

/**
 * Outputs a standard CP form submit button in the current state of the
 * form validation result. If there are errors, this button will be in a
 * disabled state on load. Button text will be "Errors Found" if
 * there are errors, otherwise the value of $value will be used.
 *
 * @param	string	$value		Standard text for the button
 * @param	string	$work_text	Text to display when form is submitting
 * @param   string  $name       The value of a name="" attribute
 * @param   string  $invalid    Force an invalid/disabled state on the button
 * @param   string  $destructive Add danger class to button
 * @return	string	Button HTML
 */
function cp_form_submit($value, $work_text, $name = null, $invalid = false, $destructive = false)
{
    $class = 'button button--primary';
    if ($destructive) {
        $class .= ' button--danger';
    }
    $disable = '';
    $btn_text = lang($value);
    $validation_errors = validation_errors();

    // Disabled state
    if (! empty($validation_errors) or $invalid) {
        $class .= ' disable';
        $disable = ' disabled="disabled"';
        $btn_text = lang('btn_fix_errors');
    }

    if ($name) {
        $name = ' name="' . $name . '"';
    }
    $shortcut = '';
    if (stripos($value, lang('save')) !== false) {
        $shortcut = ' data-shortcut="s"';
    }

    return '<button class="' . $class . '" type="submit"' . $name . $shortcut . ' value="' . $btn_text . '" data-submit-text="' . lang($value) . '" data-work-text="' . lang($work_text) . '"' . $disable . '>' . $btn_text . '</button>';
}

/**
 * Form Declaration - Multipart type
 *
 * Creates the opening portion of the form, but with "multipart/form-data".
 *
 * @access	public
 * @param	string	the URI segments of the form destination
 * @param	array	a key/value pair of attributes
 * @param	array	a key/value pair hidden data
 * @return	string
 */
if (! function_exists('form_open_multipart')) {
    function form_open_multipart($action, $attributes = array(), $hidden = array())
    {
        if (is_string($attributes)) {
            $attributes .= ' enctype="multipart/form-data"';
        } else {
            $attributes['enctype'] = 'multipart/form-data';
        }

        return form_open($action, $attributes, $hidden);
    }
}

/**
 * Hidden Input Field
 *
 * Generates hidden fields.  You can pass a simple key/value string or an associative
 * array with multiple values.
 *
 * @access	public
 * @param	mixed
 * @param	string
 * @return	string
 */
if (! function_exists('form_hidden')) {
    function form_hidden($name, $value = '', $recursing = false)
    {
        static $form;

        if ($recursing === false) {
            $form = "\n";
        }

        if (is_array($name)) {
            foreach ($name as $key => $val) {
                form_hidden($key, $val, true);
            }

            return $form;
        }

        if (! is_array($value)) {
            $form .= '<input type="hidden" name="' . $name . '" value="' . form_prep($value, $name) . '" />' . "\n";
        } else {
            foreach ($value as $k => $v) {
                $k = (is_int($k)) ? '' : $k;
                form_hidden($name . '[' . $k . ']', $v, true);
            }
        }

        return $form;
    }
}

/**
 * Text Input Field
 *
 * @access	public
 * @param	mixed
 * @param	string
 * @param	string
 * @return	string
 */
if (! function_exists('form_input')) {
    function form_input($data = '', $value = '', $extra = '')
    {
        $defaults = array('type' => 'text', 'name' => ((! is_array($data)) ? $data : ''), 'value' => $value);

        return "<input " . _parse_form_attributes($data, $defaults) . $extra . " />";
    }
}

/**
 * Number input
 */
if (! function_exists('form_number')) {
    function form_number($data = '', $value = '', $extra = '')
    {
        $defaults = array('type' => 'number', 'name' => ((! is_array($data)) ? $data : ''), 'value' => $value);

        $datalist = '';
        if (is_array($data) && isset($data['datalist']) && !empty($data['datalist'])) {
            if (!is_array($data['datalist'])) {
                $data['datalist'] = array($data['datalist']);
            }
            $datalistId = "datalist_" . (isset($data['name']) ? $data['name'] : $defaults['name']) . "_" . (isset($data['value']) ? $data['value'] : $defaults['value']);
            $datalist = "\n<datalist id=\"{$datalistId}\">\n";
            foreach ($data['datalist'] as $option) {
                $datalist .= "<option value=\"{$option}\">\n";
            }
            $datalist .= "</datalist>";
            $extra .= " list=\"{$datalistId}\"";
            unset($data['datalist']);
        }

        return "<input " . _parse_form_attributes($data, $defaults) . $extra . " />" . $datalist;
    }
}

/**
 * Range input
 */
if (! function_exists('form_range')) {
    function form_range($data = '', $value = '', $extra = '')
    {
        $defaults = array('type' => 'range', 'name' => ((! is_array($data)) ? $data : ''), 'value' => $value);

        $datalist = '';
        if (is_array($data) && isset($data['datalist']) && !empty($data['datalist'])) {
            if (!is_array($data['datalist'])) {
                $data['datalist'] = array($data['datalist']);
            }
            $datalistId = "datalist_" . (isset($data['name']) ? $data['name'] : $defaults['name']) . "_" . (isset($data['value']) ? $data['value'] : $defaults['value']);
            $datalist = "\n<datalist id=\"{$datalistId}\">\n";
            foreach ($data['datalist'] as $option) {
                $datalist .= "<option value=\"{$option}\">\n";
            }
            $datalist .= "</datalist>";
            $extra .= " list=\"{$datalistId}\"";
            unset($data['datalist']);
        }

        return "<input " . _parse_form_attributes($data, $defaults) . $extra . " />" . $datalist;
    }
}

/**
 * Password Field
 *
 * Identical to the input function but adds the "password" type
 *
 * @access	public
 * @param	mixed
 * @param	string
 * @param	string
 * @return	string
 */
if (! function_exists('form_password')) {
    function form_password($data = '', $value = '', $extra = '')
    {
        if (! is_array($data)) {
            $data = array('name' => $data);
        }

        $data['type'] = 'password';

        return form_input($data, $value, $extra);
    }
}

/**
 * Upload Field
 *
 * Identical to the input function but adds the "file" type
 *
 * @access	public
 * @param	mixed
 * @param	string
 * @param	string
 * @return	string
 */
if (! function_exists('form_upload')) {
    function form_upload($data = '', $value = '', $extra = '')
    {
        if (! is_array($data)) {
            $data = array('name' => $data);
        }

        $data['type'] = 'file';

        return form_input($data, $value, $extra);
    }
}

/**
 * Textarea field
 *
 * @access	public
 * @param	mixed
 * @param	string
 * @param	string
 * @return	string
 */
if (! function_exists('form_textarea')) {
    function form_textarea($data = '', $value = '', $extra = '')
    {
        $defaults = array('name' => ((! is_array($data)) ? $data : ''), 'cols' => '90', 'rows' => '12');

        if (! is_array($data) or ! isset($data['value'])) {
            $val = $value;
        } else {
            $val = $data['value'];
            unset($data['value']); // textareas don't use the value attribute
        }

        $name = (is_array($data)) ? $data['name'] : $data;

        return "<textarea " . _parse_form_attributes($data, $defaults) . $extra . ">" . form_prep($val, $name) . "</textarea>";
    }
}

/**
 * Multi select menu
 *
 * @access	public
 * @param	string
 * @param	array
 * @param	mixed
 * @param	string
 * @param	boolean	$form_prep	Whether or not to form_prep the displayed value (use caution when FALSE!)
 * @return	type
 */
if (! function_exists('form_multiselect')) {
    function form_multiselect($name = '', $options = array(), $selected = array(), $extra = '', $form_prep = true)
    {
        if (! strpos($extra, 'multiple')) {
            $extra .= ' multiple="multiple"';
        }

        return form_dropdown($name, $options, $selected, $extra, $form_prep);
    }
}

/**
 * Drop-down Menu
 *
 * @access	public
 * @param	string
 * @param	array
 * @param	string
 * @param	string
 * @param	boolean	$form_prep	Whether or not to form_prep the displayed value (use caution when FALSE!)
 * @return	string
 */
if (! function_exists('form_dropdown')) {
    function form_dropdown($name = '', $options = array(), $selected = array(), $extra = '', $form_prep = true)
    {
        if (! is_array($selected)) {
            $selected = array($selected);
        }

        // If no selected state was submitted we will attempt to set it automatically
        if (count($selected) === 0) {
            // If the form name appears in the $_POST array we have a winner!
            if (isset($_POST[$name])) {
                $selected = array($_POST[$name]);
            }
        }

        if ($extra != '') {
            $extra = ' ' . $extra;
        }

        $multiple = (count($selected) > 1 && strpos($extra, 'multiple') === false) ? ' multiple="multiple"' : '';

        $form = '<select aria-label="' . $name . '" tabindex="0" name="' . $name . '"' . $extra . $multiple . ">\n";

        foreach ($options as $key => $val) {
            $key = (string) $key;

            if (is_array($val) && ! empty($val)) {
                $form .= '<optgroup label="' . form_prep($key) . '">' . "\n";

                foreach ($val as $optgroup_key => $optgroup_val) {
                    $sel = (in_array($optgroup_key, $selected)) ? ' selected="selected"' : '';

                    if ($form_prep) {
                        $optgroup_val = form_prep((string) $optgroup_val);
                    }

                    $form .= '<option value="' . form_prep($optgroup_key) . '"' . $sel . '>' . (string) $optgroup_val . "</option>\n";
                }

                $form .= '</optgroup>' . "\n";
            } else {
                $sel = (in_array($key, $selected)) ? ' selected="selected"' : '';

                if ($form_prep) {
                    $val = form_prep((string) $val);
                }

                $form .= '<option value="' . form_prep($key) . '"' . $sel . '>' . (string) $val . "</option>\n";
            }
        }

        $form .= '</select>';

        return $form;
    }
}

/**
 * Checkbox Field
 *
 * @access	public
 * @param	mixed
 * @param	string
 * @param	bool
 * @param	string
 * @return	string
 */
if (! function_exists('form_checkbox')) {
    function form_checkbox($data = '', $value = '', $checked = false, $extra = '')
    {
        $defaults = array('type' => 'checkbox', 'name' => ((! is_array($data)) ? $data : ''), 'value' => $value);

        if (is_array($data) and array_key_exists('checked', $data)) {
            $checked = $data['checked'];

            if ($checked == false) {
                unset($data['checked']);
            } else {
                $data['checked'] = 'checked';
            }
        }

        if ($checked == true) {
            $defaults['checked'] = 'checked';
        } else {
            unset($defaults['checked']);
        }

        return "<input " . _parse_form_attributes($data, $defaults) . $extra . " />";
    }
}

/**
 * Radio Button
 *
 * @access	public
 * @param	mixed
 * @param	string
 * @param	bool
 * @param	string
 * @return	string
 */
if (! function_exists('form_radio')) {
    function form_radio($data = '', $value = '', $checked = false, $extra = '')
    {
        if (! is_array($data)) {
            $data = array('name' => $data);
        }

        $data['type'] = 'radio';

        return form_checkbox($data, $value, $checked, $extra);
    }
}

/**
 * Submit Button
 *
 * @access	public
 * @param	mixed
 * @param	string
 * @param	string
 * @return	string
 */
if (! function_exists('form_submit')) {
    function form_submit($data = '', $value = '', $extra = '')
    {
        $defaults = array('type' => 'submit', 'name' => ((! is_array($data)) ? $data : ''), 'value' => $value);

        return "<input " . _parse_form_attributes($data, $defaults) . $extra . " />";
    }
}

/**
 * Reset Button
 *
 * @access	public
 * @param	mixed
 * @param	string
 * @param	string
 * @return	string
 */
if (! function_exists('form_reset')) {
    function form_reset($data = '', $value = '', $extra = '')
    {
        $defaults = array('type' => 'reset', 'name' => ((! is_array($data)) ? $data : ''), 'value' => $value);

        return "<input " . _parse_form_attributes($data, $defaults) . $extra . " />";
    }
}

/**
 * Form Button
 *
 * @access	public
 * @param	mixed
 * @param	string
 * @param	string
 * @return	string
 */
if (! function_exists('form_button')) {
    function form_button($data = '', $content = '', $extra = '')
    {
        $defaults = array('name' => ((! is_array($data)) ? $data : ''), 'type' => 'button');

        if (is_array($data) and isset($data['content'])) {
            $content = $data['content'];
            unset($data['content']); // content is not an attribute
        }

        return "<button " . _parse_form_attributes($data, $defaults) . $extra . ">" . $content . "</button>";
    }
}

/**
 * Form Label Tag
 *
 * @access	public
 * @param	string	The text to appear onscreen
 * @param	string	The id the label applies to
 * @param	string	Additional attributes
 * @return	string
 */
if (! function_exists('form_label')) {
    function form_label($label_text = '', $id = '', $attributes = array())
    {
        $label = '<label';

        if ($id != '') {
            $label .= " for=\"$id\"";
        }

        if (is_array($attributes) and count($attributes) > 0) {
            foreach ($attributes as $key => $val) {
                $label .= ' ' . $key . '="' . $val . '"';
            }
        }

        $label .= ">$label_text</label>";

        return $label;
    }
}
/**
 * Fieldset Tag
 *
 * Used to produce <fieldset><legend>text</legend>.  To close fieldset
 * use form_fieldset_close()
 *
 * @access	public
 * @param	string	The legend text
 * @param	string	Additional attributes
 * @return	string
 */
if (! function_exists('form_fieldset')) {
    function form_fieldset($legend_text = '', $attributes = array())
    {
        $fieldset = "<fieldset";

        $fieldset .= _attributes_to_string($attributes, false);

        $fieldset .= ">\n";

        if ($legend_text != '') {
            $fieldset .= "<legend>$legend_text</legend>\n";
        }

        return $fieldset;
    }
}

/**
 * Fieldset Close Tag
 *
 * @access	public
 * @param	string
 * @return	string
 */
if (! function_exists('form_fieldset_close')) {
    function form_fieldset_close($extra = '')
    {
        return "</fieldset>" . $extra;
    }
}

/**
 * Form Close Tag
 *
 * @access	public
 * @param	string
 * @return	string
 */
if (! function_exists('form_close')) {
    function form_close($extra = '')
    {
        return "</form>" . $extra;
    }
}

/**
 * Form Prep
 *
 * Formats text so that it can be safely placed in a form field in the event it has HTML tags.
 *
 * @access	public
 * @param	string
 * @return	string
 */
if (! function_exists('form_prep')) {
    function form_prep($str = '', $field_name = '')
    {
        static $prepped_fields = array();

        // if the field name is an array we do this recursively
        if (is_array($str)) {
            foreach ($str as $key => $val) {
                $str[$key] = form_prep($val);
            }

            return $str;
        }

        if ($str === '') {
            return '';
        }

        $hash = md5($field_name . $str);

        if (! isset($prepped_fields[$hash])) {
            $str = htmlspecialchars((string) $str, ENT_QUOTES);
            $hash = md5($field_name . $str);
            $prepped_fields[$hash] = true;
        }

        return $str;
    }
}

/**
 * Form Value
 *
 * Grabs a value from the POST array for the specified field so you can
 * re-populate an input field or textarea.  If Form Validation
 * is active it retrieves the info from the validation class
 *
 * @access	public
 * @param	string
 * @return	mixed
 */
if (! function_exists('set_value')) {
    function set_value($field = '', $default = '')
    {
        if (false === ($OBJ = _get_validation_object())) {
            if (! isset($_POST[$field])) {
                return form_prep($default, $field);
            }

            return form_prep($_POST[$field], $field);
        }

        return form_prep($OBJ->set_value($field, $default), $field);
    }
}

/**
 * Set Select
 *
 * Let's you set the selected value of a <select> menu via data in the POST array.
 * If Form Validation is active it retrieves the info from the validation class
 *
 * @access	public
 * @param	string
 * @param	string
 * @param	bool
 * @return	string
 */
if (! function_exists('set_select')) {
    function set_select($field = '', $value = '', $default = false)
    {
        $OBJ = _get_validation_object();

        if ($OBJ === false) {
            if (! isset($_POST[$field])) {
                if (count($_POST) === 0 and $default == true) {
                    return ' selected="selected"';
                }

                return '';
            }

            $field = $_POST[$field];

            if (is_array($field)) {
                if (! in_array($value, $field)) {
                    return '';
                }
            } else {
                if (($field == '' or $value == '') or ($field != $value)) {
                    return '';
                }
            }

            return ' selected="selected"';
        }

        return $OBJ->set_select($field, $value, $default);
    }
}

/**
 * Set Checkbox
 *
 * Let's you set the selected value of a checkbox via the value in the POST array.
 * If Form Validation is active it retrieves the info from the validation class
 *
 * @access	public
 * @param	string
 * @param	string
 * @param	bool
 * @return	string
 */
if (! function_exists('set_checkbox')) {
    function set_checkbox($field = '', $value = '', $default = false)
    {
        $OBJ = _get_validation_object();

        if ($OBJ === false) {
            if (! isset($_POST[$field])) {
                if (count($_POST) === 0 and $default == true) {
                    return ' checked="checked"';
                }

                return '';
            }

            $field = $_POST[$field];

            if (is_array($field)) {
                if (! in_array($value, $field)) {
                    return '';
                }
            } else {
                if (($field == '' or $value == '') or ($field != $value)) {
                    return '';
                }
            }

            return ' checked="checked"';
        }

        return $OBJ->set_checkbox($field, $value, $default);
    }
}

/**
 * Set Radio
 *
 * Let's you set the selected value of a radio field via info in the POST array.
 * If Form Validation is active it retrieves the info from the validation class
 *
 * @access	public
 * @param	string
 * @param	string
 * @param	bool
 * @return	string
 */
if (! function_exists('set_radio')) {
    function set_radio($field = '', $value = '', $default = false)
    {
        return set_checkbox($field, $value, $default);
    }
}

/**
 * Form Error
 *
 * Returns the error for a specific form field.  This is a helper for the
 * form validation class.
 *
 * @access	public
 * @param	string
 * @param	string
 * @param	string
 * @return	string
 */
if (! function_exists('form_error')) {
    function form_error($field = '', $prefix = '', $suffix = '')
    {
        if (false === ($OBJ = _get_validation_object())) {
            return '';
        }

        // Error messages were forced in by other validation service
        if (! isset($OBJ->_field_data[$field]['error']) &&
            isset($OBJ->_error_array[$field])) {
            return $OBJ->_error_array[$field];
        }

        return $OBJ->error($field, $prefix, $suffix);
    }
}

/**
 * Form Error Class
 *
 * If an error exists for a particular field, returns a bit of text to be
 * used as a class name for specific styling
 *
 * @access	public
 * @param	array	$field	Array of field names, or string of single field name
 * @param	string	$class	Class name to return, defaults to 'fieldset-invalid'
 * @return	string	Empty string if no error, class name if error
 */
if (! function_exists('form_error_class')) {
    function form_error_class($fields = '', $class = 'fieldset-invalid')
    {
        if (! is_array($fields)) {
            $fields = array($fields);
        }

        foreach ($fields as $field) {
            $error = form_error($field);

            if (! empty($error)) {
                return $class;
            }
        }

        return '';
    }
}

/**
 * Validation Error String
 *
 * Returns all the errors associated with a form submission.  This is a helper
 * function for the form validation class.
 *
 * @access	public
 * @param	string
 * @param	string
 * @return	string
 */
if (! function_exists('validation_errors')) {
    function validation_errors($prefix = '', $suffix = '')
    {
        if (false === ($OBJ = _get_validation_object())) {
            return '';
        }

        return $OBJ->error_string($prefix, $suffix);
    }
}

/**
 * Parse the form attributes
 *
 * Helper function used by some of the form helpers
 *
 * @access	private
 * @param	array
 * @param	array
 * @return	string
 */
if (! function_exists('_parse_form_attributes')) {
    function _parse_form_attributes($attributes, $default)
    {
        if (is_array($attributes)) {
            foreach ($default as $key => $val) {
                if (isset($attributes[$key])) {
                    $default[$key] = $attributes[$key];
                    unset($attributes[$key]);
                }
            }

            if (count($attributes) > 0) {
                $default = array_merge($default, $attributes);
            }
        }

        $att = '';

        foreach ($default as $key => $val) {
            if ($key == 'value') {
                $val = form_prep($val, $default['name']);
            }

            $att .= $key . '="' . $val . '" ';
        }

        return $att;
    }
}

/**
 * Attributes To String
 *
 * Helper function used by some of the form helpers
 *
 * @access	private
 * @param	mixed
 * @param	bool
 * @return	string
 */
if (! function_exists('_attributes_to_string')) {
    function _attributes_to_string($attributes, $formtag = false)
    {
        if (is_string($attributes) and strlen($attributes) > 0) {
            if ($formtag == true and strpos($attributes, 'method=') === false) {
                $attributes .= ' method="post"';
            }

            if ($formtag == true and strpos($attributes, 'accept-charset=') === false) {
                $attributes .= ' accept-charset="' . strtolower(config_item('charset')) . '"';
            }

            return ' ' . $attributes;
        }

        if (is_object($attributes) and count($attributes) > 0) {
            $attributes = (array) $attributes;
        }

        if (is_array($attributes) and count($attributes) > 0) {
            $atts = '';

            if (! isset($attributes['method']) and $formtag === true) {
                $atts .= ' method="post"';
            }

            if (! isset($attributes['accept-charset']) and $formtag === true) {
                $atts .= ' accept-charset="' . strtolower(config_item('charset')) . '"';
            }

            foreach ($attributes as $key => $val) {
                $atts .= ' ' . $key . '="' . $val . '"';
            }

            return $atts;
        }
    }
}

/**
 * Validation Object
 *
 * Determines what the form validation class was instantiated as, fetches
 * the object and returns it.
 *
 * @return	mixed
 */
if (! function_exists('_get_validation_object')) {
    function _get_validation_object()
    {
        // We set this as a variable since we're returning by reference.
        $return = false;

        if (false !== ($object = ee()->load->is_loaded('form_validation'))) {
            if (! isset(ee()->$object) or ! is_object(ee()->$object)) {
                return $return;
            }

            return ee()->$object;
        }

        return $return;
    }
}

// EOF
