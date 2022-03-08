<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once SYSPATH . 'ee/ExpressionEngine/Addons/text/ft.text.php';

/**
 * Number Fieldtype
 */
class Number_ft extends Text_ft
{
    public $info = array(
        'name' => 'Number Input',
        'version' => '1.0.0'
    );

    protected $default_field_content_type = 'number';

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
            'value' => $this->_format_number($data),
        );

        if ($this->settings['field_min_value'] != '') {
            $field['min'] = $this->settings['field_min_value'];
        }
        if ($this->settings['field_max_value'] != '') {
            $field['max'] = $this->settings['field_max_value'];
        }
        if ($this->settings['field_step']) {
            $field['step'] = $this->settings['field_step'];
        }

        if ($this->settings['datalist_items']) {
            $field['datalist'] = [];
            $datalist = explode("\n", $this->settings['datalist_items']);
            foreach ($datalist as $option) {
                if (!empty(trim($option))) {
                    $field['datalist'][] = trim($option);
                }
            }
        }

        return form_number($field);
    }

    public function replace_tag($data, $params = '', $tagdata = '')
    {
        $decimals = isset($params['decimal_place']) ? (int) $params['decimal_place'] : false;
        $type = isset($this->settings['field_content_type']) && in_array($this->settings['field_content_type'], ['number', 'integer', 'decimal']) ? $this->settings['field_content_type'] : $this->default_field_content_type;

        $data = $this->_format_number($data, $type, $decimals);

        return $data;
    }

    public function display_settings($data)
    {
        $settings = array(
            array(
                'title' => 'field_min_value',
                'fields' => array(
                    'field_min_value' => array(
                        'type' => 'text',
                        'value' => isset($data['field_min_value']) ? $data['field_min_value'] : ''
                    )
                )
            ),
            array(
                'title' => 'field_max_value',
                'fields' => array(
                    'field_max_value' => array(
                        'type' => 'text',
                        'value' => isset($data['field_max_value']) ? $data['field_max_value'] : ''
                    )
                )
            ),
            array(
                'title' => 'field_step',
                'fields' => array(
                    'field_step' => array(
                        'type' => 'text',
                        'value' => isset($data['field_step']) ? $data['field_step'] : ''
                    )
                )
            ),
            array(
                'title' => 'datalist_items',
                'desc' => 'datalist_items_desc',
                'fields' => array(
                    'datalist_items' => array(
                        'type' => 'textarea',
                        'value' => isset($data['datalist_items']) ? $data['datalist_items'] : ''
                    )
                )
            ),
        );

        if ($this->content_type() != 'category' && $this->content_type() != 'member') {
            $settings[] = array(
                'title' => 'field_content_text',
                'desc' => 'field_content_text_desc',
                'fields' => array(
                    'field_content_type' => array(
                        'type' => 'radio',
                        'choices' => $this->_get_content_options(),
                        'value' => isset($data['field_content_type']) ? $data['field_content_type'] : ''
                    )
                )
            );
        }

        if ($this->content_type() == 'grid') {
            return array('field_options' => $settings);
        }

        return array('field_options_number' => array(
            'label' => 'field_options',
            'group' => 'number',
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
            'numeric' => lang('type_numeric'),
            'integer' => lang('type_integer'),
            'decimal' => lang('type_decimal')
        );
    }

    public function validate_settings($settings)
    {
        $validator = ee('Validation')->make(array(
            'field_min_value' => 'numeric|matchesContentType',
            'field_max_value' => 'numeric|matchesContentType',
            'field_step' => 'numeric|matchesContentType'
        ));

        $validator->defineRule('matchesContentType', function ($key, $value) use ($settings) {
            if ($settings['field_content_type'] == 'integer' && (int)$value != $value) {
                return 'integer';
            }
            return true;
        });

        return $validator->validate($settings);
    }

    public function save_settings($data)
    {
        $defaults = array(
            'field_min_value' => '',
            'field_max_value' => '',
            'field_step' => '',
            'datalist_items' => '',
            'field_content_type' => 'numeric',
        );

        $all = array_merge($defaults, $data);

        return array_intersect_key($all, $defaults);
    }
}

// END Text_Ft class

// EOF
