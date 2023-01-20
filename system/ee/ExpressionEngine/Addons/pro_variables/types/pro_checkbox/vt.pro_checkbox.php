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
 * Pro Checkbox variable type
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
