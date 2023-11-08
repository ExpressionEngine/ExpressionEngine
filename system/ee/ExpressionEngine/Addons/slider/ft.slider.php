<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once SYSPATH . 'ee/ExpressionEngine/Addons/text/ft.text.php';

/**
 * Slider Fieldtype
 */
class Slider_ft extends Text_ft
{
    public $info = array(
        'name' => 'Value Slider',
        'version' => '1.0.0'
    );

    protected $default_field_content_type = 'numeric';

    public $settings_form_field_name = 'slider';

    /**
     * A list of operators that this fieldtype supports
     *
     * @var array
     */
    public $supportedEvaluationRules = ['lessThan', 'lessOrEqualThan', 'equal', 'greaterThan', 'greaterOrEqualThan'];

    public $defaultEvaluationRule = 'equal';

    /**
     * Display the field
     *
     * @param [type] $data
     * @return void
     */
    public function display_field($data)
    {
        //some fallback if we switched from double to single slider
        if (!is_null($data) && strpos($data, '|') !== false) {
            ee()->load->helper('custom_field');
            $data = decode_multi_field($data);
            if (isset($data[0])) {
                $data = $data[0];
            }
        }

        $field = array(
            'name' => $this->field_name,
            'value' => is_numeric($data) ? $data : $this->settings['field_min_value'],
            'min' => (isset($this->settings['field_min_value']) && is_numeric($this->settings['field_min_value'])) ? $this->settings['field_min_value'] : 0,
            'max' => (isset($this->settings['field_max_value']) && is_numeric($this->settings['field_max_value'])) ? $this->settings['field_max_value'] : 100,
            'step' => (isset($this->settings['field_step']) && is_numeric($this->settings['field_step'])) ? $this->settings['field_step'] : 1,
            'suffix' => isset($this->settings['field_suffix']) ? $this->settings['field_suffix'] : '',
            'prefix' => isset($this->settings['field_prefix']) ? $this->settings['field_prefix'] : ''
        );

        if ($field['value'] < $field['min']) {
            $field['value'] = $field['min'];
        } elseif ($field['value'] > $field['max']) {
            $field['value'] = $field['max'];
        }

        ee()->javascript->output("
            $('.ee-slider-field.range-slider').each(function() {
                var minValue = $(this).find('input[type=range]').attr('min');
                var maxValue = $(this).find('input[type=range]').attr('max');

                $(this).attr('data-min', minValue);
                $(this).attr('data-max', maxValue);
            });
        ");

        return ee('View')->make('slider:single')->render($field);

        return form_range($field);
    }

    public function replace_tag($data, $params = '', $tagdata = '')
    {
        //some fallback if we switched from double to single slider
        if (strpos($data, '|') !== false) {
            ee()->load->helper('custom_field');
            $data = decode_multi_field($data);
            if (isset($data[0])) {
                $data = $data[0];
            }
        }

        $decimals = isset($params['decimal_place']) ? (int) $params['decimal_place'] : false;
        $type = isset($this->settings['field_content_type']) && in_array($this->settings['field_content_type'], ['number', 'integer', 'decimal']) ? $this->settings['field_content_type'] : $this->default_field_content_type;

        $data = $this->_format_number($data, $type, $decimals);

        if (isset($params['prefix']) && $params['prefix'] == 'yes') {
            $data = $this->settings['field_prefix'] . $data;
        }

        if (isset($params['suffix']) && $params['suffix'] == 'yes') {
            $data = $data . $this->settings['field_suffix'];
        }

        return $data;
    }

    public function replace_min($data, $params = '', $tagdata = '')
    {
        return (is_numeric($this->settings['field_min_value'])) ? $this->settings['field_min_value'] : 0;
    }

    public function replace_max($data, $params = '', $tagdata = '')
    {
        return (is_numeric($this->settings['field_max_value'])) ? $this->settings['field_max_value'] : 100;
    }

    public function replace_prefix($data, $params = '', $tagdata = '')
    {
        return $this->settings['field_prefix'];
    }

    public function replace_suffix($data, $params = '', $tagdata = '')
    {
        return $this->settings['field_suffix'];
    }

    private function _default_settings()
    {
        return array(
            'field_min_value' => 0,
            'field_max_value' => 100,
            'field_step' => 1,
            'field_prefix' => '',
            'field_suffix' => '',
            'datalist_items' => '',
            'field_content_type' => $this->default_field_content_type,
        );
    }

    public function display_settings($data = [])
    {
        $defaults = $this->_default_settings();

        foreach ($defaults as $key => $value) {
            if (!isset($data[$key])) {
                $data[$key] = $value;
            }
        }

        $settings = array(
            array(
                'title' => 'field_min_value',
                'fields' => array(
                    'field_min_value' => array(
                        'type' => 'text',
                        'value' => $data['field_min_value']
                    )
                )
            ),
            array(
                'title' => 'field_max_value',
                'fields' => array(
                    'field_max_value' => array(
                        'type' => 'text',
                        'value' => $data['field_max_value']
                    )
                )
            ),
            array(
                'title' => 'field_step',
                'fields' => array(
                    'field_step' => array(
                        'type' => 'text',
                        'value' => $data['field_step']
                    )
                )
            ),
            array(
                'title' => 'field_prefix',
                'fields' => array(
                    'field_prefix' => array(
                        'type' => 'text',
                        'value' => $data['field_prefix']
                    )
                )
            ),
            array(
                'title' => 'field_suffix',
                'fields' => array(
                    'field_suffix' => array(
                        'type' => 'text',
                        'value' => $data['field_suffix']
                    )
                )
            ),
        );

        if ($this->settings_form_field_name == 'slider' && $this->content_type() != 'category' && $this->content_type() != 'member') {
            $settings[] = array(
                'title' => 'field_content_text',
                'desc' => 'field_content_text_desc',
                'fields' => array(
                    'field_content_type' => array(
                        'type' => 'radio',
                        'choices' => $this->_get_content_options(),
                        'value' => $data['field_content_type']
                    )
                )
            );
        }

        if ($this->content_type() == 'grid') {
            return array('field_options' => $settings);
        }

        return array('field_options_' . $this->settings_form_field_name => array(
            'label' => 'field_options',
            'group' => $this->settings_form_field_name,
            'settings' => $settings
        ));
    }

    /**
     * Returns allowed content types for the text fieldtype
     *
     * @return	array
     */
    private function _get_content_options()
    {
        return array(
            'all' => lang('all'),
            'numeric' => lang('type_numeric'),
            'integer' => lang('type_integer'),
            'decimal' => lang('type_decimal')
        );
    }

    public function validate_settings($settings)
    {
        $validator = ee('Validation')->make(array(
            'field_min_value' => 'numeric|matchesContentType|whenNotEmpty[field_max_value]|lessThan[' . $settings['field_max_value'] . ']',
            'field_max_value' => 'numeric|matchesContentType|whenNotEmpty[field_min_value]|greaterThan[' . $settings['field_min_value'] . ']',
            'field_step' => 'numeric|matchesContentType'
        ));

        $validator->defineRule('matchesContentType', function ($key, $value) use ($settings) {
            if ($settings['field_content_type'] == 'integer' && $this->settings_form_field_name == 'slider' && (int)$value != $value) {
                return 'integer';
            }
            return true;
        });

        return $validator->validate($settings);
    }

    public function save_settings($data)
    {
        $defaults = $this->_default_settings();

        $all = array_merge($defaults, $data);

        return array_intersect_key($all, $defaults);
    }
}

// END Text_Ft class

// EOF
