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
     * A list of operators that this fieldtype supports
     *
     * @var array
     */
    public $supportedEvaluationRules = ['lessThan', 'lessOrEqualThan', 'equal', 'greaterThan', 'greaterOrEqualThan', 'isEmpty', 'isNotEmpty'];

    public $defaultEvaluationRule = 'isNotEmpty';

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

    public function validate($data)
    {
        if (is_null($data) || $data === '') {
            return true;
        }

        $validationRules = [];
        if (!is_null($this->settings['field_min_value']) && $this->settings['field_min_value'] !== '') {
            $validationRules[] = 'greaterOrEqualThan[' . $this->settings['field_min_value'] . ']';
        }
        if (!is_null($this->settings['field_max_value']) && $this->settings['field_max_value'] !== '') {
            $validationRules[] = 'lessOrEqualThan[' . $this->settings['field_max_value'] . ']';
        }
        if ($this->settings['field_content_type'] == 'integer') {
            $validationRules[] = 'matchesContentType';
        }

        if (empty($validationRules)) {
            return true;
        }

        $validator = ee('Validation')->make(array('value' => implode('|', $validationRules)));
        if (in_array('matchesContentType', $validationRules)) {
            $validator->defineRule('matchesContentType', array($this, 'matchesContentTypeRule'));
        }
        $validationResult = $validator->validate(array('value' => $data));
        if (! $validationResult->isValid()) {
            $error = $validationResult->getErrors('value');
            return array_shift($error);
        }

        return true;
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
        $validationRules = array(
            'field_min_value' => 'numeric|whenNotEmpty[field_max_value]|lessThan[' . $settings['field_max_value'] . ']',
            'field_max_value' => 'numeric|whenNotEmpty[field_min_value]|greaterThan[' . $settings['field_min_value'] . ']',
            'field_step' => 'numeric'
        );
        if ($settings['field_content_type'] == 'integer') {
            foreach ($validationRules as $key => $value) {
                $validationRules[$key] = 'matchesContentType|' . $value;
            }
        }
        $validator = ee('Validation')->make($validationRules);

        if ($settings['field_content_type'] == 'integer') {
            $validator->defineRule('matchesContentType', array($this, 'matchesContentTypeRule'));
        }

        return $validator->validate($settings);
    }

    public function matchesContentTypeRule($key, $value, $params, $rule) {
        if ((int)$value != $value) {
            return 'integer';
        }
        return true;
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
