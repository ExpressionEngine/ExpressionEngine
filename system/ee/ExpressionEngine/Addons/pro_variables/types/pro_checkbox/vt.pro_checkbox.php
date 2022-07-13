<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Pro Checkbox variable type
 *
 * @package        pro_variables
 * @author         EEHarbor
 * @link           https://eeharbor.com/pro-variables
 * @copyright      Copyright (c) 2009-2022, EEHarbor
 */
class Pro_checkbox extends Pro_variables_type
{
    public $info = array(
        'name' => 'Checkbox',
    );

    public $default_settings = array(
        'label' => ''
    );

    // --------------------------------------------------------------------

    /**
     * Display settings sub-form for this variable type
     */
    public function display_settings()
    {
        return $this->settings_form(array(
            array(
                'title' => 'checkbox_label',
                'fields' => array(
                    $this->setting_name('label') => array(
                        'type' => 'text',
                        'value' => $this->settings('label')
                    )
                )
            )
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
            'choices' => array('y' => $this->settings('label')),
            'value' => $var_data,
            'scalar' => false
        ));
    }

    // --------------------------------------------------------------------
}
