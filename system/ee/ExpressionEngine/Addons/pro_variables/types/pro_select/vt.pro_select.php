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
 * Pro Select variable type
 */
class Pro_select extends Pro_variables_type
{
    public $info = array(
        'name' => 'Select'
    );

    public $default_settings = array(
        'multiple'  => 'n',
        'options'   => '',
        'separator' => 'newline',
        'multi_interface' => 'select'
    );

    // --------------------------------------------------------------------

    /**
     * Display settings sub-form for this variable type
     */
    public function display_settings()
    {
        return $this->settings_form(array(
            LVUI::setting('options', $this->setting_name('options'), $this->settings('options')),
            LVUI::setting('multiple', $this->setting_name('multiple'), $this->settings('multiple')),
            LVUI::setting('separator', $this->setting_name('separator'), $this->settings('separator')),
            LVUI::setting('interface', $this->setting_name('multi_interface'), $this->settings('multi_interface'))
        ));
    }

    // --------------------------------------------------------------------

    /**
     * Display input field for regular user
     */
    public function display_field($var_data)
    {
        // -------------------------------------
        //  Prep options to choices
        // -------------------------------------

        $choices = LVUI::choices($this->settings('options'));

        // -------------------------------------
        //  Single choice
        // -------------------------------------

        if ($this->settings('multiple') != 'y') {
            return array(
                $this->input_name() => array(
                    'type' => 'select',
                    'choices' => $choices,
                    'value' => $var_data
                )
            );
        } else {
            //  Multiple choice
            $data = array(
                'name' => $this->input_name(),
                'choices' => $choices,
                'value' => LVUI::explode($this->settings('separator'), $var_data),
                'multiple' => true
            );

            return array(array(
                'type' => 'html',
                'content' => LVUI::view_field($this->settings('multi_interface'), $data)
            ));
        }
    }

    // --------------------------------------------------------------------

    /**
     * Prep variable data for saving
     */
    public function save($var_data)
    {
        return is_array($var_data)
            ? LVUI::implode($this->settings('separator'), $var_data)
            : $var_data;
    }

    // --------------------------------------------------------------------
}
