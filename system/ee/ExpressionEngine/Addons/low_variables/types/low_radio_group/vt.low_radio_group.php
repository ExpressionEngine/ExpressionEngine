<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Low Radio Group variable type
 *
 * @package        low_variables
 * @author         Lodewijk Schutte <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-variables
 * @copyright      Copyright (c) 2009-2020, Low
 */
class Low_radio_group extends Low_variables_type
{
    public $info = array(
        'name' => 'Radio Group'
    );

    public $default_settings = array(
        'options' => ''
    );

    // --------------------------------------------------------------------

    /**
     * Display settings sub-form for this variable type
     */
    public function display_settings()
    {
        return $this->settings_form(array(
            LVUI::setting('options', $this->setting_name('options'), $this->settings('options'))
        ));
    }

    // --------------------------------------------------------------------

    /**
     * Display input field for regular user
     */
    public function display_field($var_data)
    {
        return array($this->input_name() => array(
            'type' => 'radio',
            'choices' => LVUI::choices($this->settings('options')),
            'value' => $var_data
        ));
    }

    // --------------------------------------------------------------------
}
