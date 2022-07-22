<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Pro Checkbox Group variable type
 */
class Pro_checkbox_group extends Pro_variables_type
{
    public $info = array(
        'name' => 'Checkbox Group'
    );

    public $default_settings = array(
        'options'   => '',
        'separator' => 'newline'
    );

    // --------------------------------------------------------------------

    /**
     * Display settings sub-form for this variable type
     */
    public function display_settings()
    {
        return $this->settings_form(array(
            LVUI::setting('options', $this->setting_name('options'), $this->settings('options')),
            LVUI::setting('separator', $this->setting_name('separator'), $this->settings('separator'))
        ));
    }

    // --------------------------------------------------------------------

    /**
     * Display input field for regular user
     */
    public function display_field($var_data)
    {
        return array($this->input_name() => array(
            'type' => 'checkbox',
            'choices' => LVUI::choices($this->settings('options')),
            'value' => LVUI::explode($this->settings('separator'), $var_data),
            'wrap' => true
        ));
    }

    // --------------------------------------------------------------------

    /**
     * Prep variable data for saving
     */
    public function save($var_data)
    {
        return is_array($var_data)
            ? LVUI::implode($this->settings('separator'), $var_data)
            : '';
    }

    // --------------------------------------------------------------------
}
