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

/**
 * Pro Radio Group variable type
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
            PVUI::setting('options', $this->setting_name('options'), $this->settings('options'))
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
            'choices' => PVUI::choices($this->settings('options')),
            'value' => $var_data
        ));
    }

    // --------------------------------------------------------------------
}
