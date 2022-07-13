<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Pro Radio Group variable type
 *
 * @package        pro_variables
 * @author         EEHarbor
 * @link           https://eeharbor.com/pro-variables
 * @copyright      Copyright (c) 2009-2022, EEHarbor
 */
class Pro_radio_group extends Pro_variables_type
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
