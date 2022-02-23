<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once SYSPATH . 'ee/ExpressionEngine/Addons/slider/ft.slider.php';

/**
 * Range Slider Fieldtype
 */
class Range_slider_ft extends Slider_ft
{
    public $info = array(
        'name' => 'Range Slider',
        'version' => '1.0.0'
    );

    protected $default_field_content_type = 'all';

    public $settings_form_field_name = 'range_slider';

    /**
     * Display the field
     *
     * @param [type] $data
     * @return void
     */
    public function display_field($data)
    {
        $field = array(
            'name' => $this->field_name,
            'min' => ($this->settings['field_min_value'] != '') ? (int) $this->settings['field_min_value'] : 0,
            'max' => ($this->settings['field_max_value'] != '') ? (int) $this->settings['field_max_value'] : 100,
            'step' => ($this->settings['field_step'] != '') ? $this->settings['field_step'] : 1,
            'suffix' => $this->settings['field_suffix'],
            'prefix' => $this->settings['field_prefix']
        );

        ee()->load->helper('custom_field');
        $data = decode_multi_field($data);
        $field['from'] = (isset($data[0])) ? $data[0] : $field['min'];
        $field['to'] = (isset($data[1])) ? $data[1] : $field['max'];

        if (REQ == 'CP') {
            return ee('View')->make('slider:pair')->render($field);
        }

        return form_range($field);
    }

    public function save($data)
    {
        if (is_array($data)) {
            ee()->load->helper('custom_field');
            $data = encode_multi_field($data);
        }

        return $data;
    }

    public function replace_tag($data, $params = '', $tagdata = '')
    {
        $decimals = isset($params['decimal_place']) ? (int) $params['decimal_place'] : false;
        $type = isset($this->settings['field_content_type']) && in_array($this->settings['field_content_type'], ['number', 'integer', 'decimal']) ? $this->settings['field_content_type'] : $this->default_field_content_type;

        $data = $this->_format_number($data, $type, $decimals);

        ee()->load->library('typography');

        return ee()->typography->parse_type($data);
    }

}

// END Text_Ft class

// EOF
