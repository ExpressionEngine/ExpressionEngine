<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
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

    public $has_array_data = true;

    public $can_be_cloned = true;

    public $entry_manager_compatible = true;

    /**
     * A list of operators that this fieldtype supports
     *
     * @var array
     */
    public $supportedEvaluationRules = ['rangeIncludes', 'rangeNotIncludes'];

    public $defaultEvaluationRule = 'rangeIncludes';

    /**
     * Display the field
     *
     * @param [type] $data
     * @return void
     */
    public function display_field($data)
    {
        $field = array(
            'name' => $this->field_name . '[]',
            'min' => (isset($this->settings['field_min_value']) && is_numeric($this->settings['field_min_value'])) ? $this->settings['field_min_value'] : 0,
            'max' => (isset($this->settings['field_max_value']) && is_numeric($this->settings['field_max_value'])) ? $this->settings['field_max_value'] : 100,
            'step' => (isset($this->settings['field_step']) && is_numeric($this->settings['field_step'])) ? $this->settings['field_step'] : 1,
            'suffix' => isset($this->settings['field_suffix']) ? $this->settings['field_suffix'] : '',
            'prefix' => isset($this->settings['field_prefix']) ? $this->settings['field_prefix'] : ''
        );

        ee()->load->helper('custom_field');
        $data = decode_multi_field($data);
        $field['from'] = (isset($data[0])) ? $data[0] : $field['min'];
        $field['to'] = (isset($data[1])) ? $data[1] : $field['max'];

        if ($field['from'] < $field['min']) {
            $field['from'] = $field['min'];
        } elseif ($field['from'] > $field['max']) {
            $field['from'] = $field['max'];
        }

        if ($field['to'] < $field['min']) {
            $field['to'] = $field['min'];
        } elseif ($field['to'] > $field['max']) {
            $field['to'] = $field['max'];
        }

        ee()->javascript->output("
            $('.ee-slider-field.range-slider').each(function() {
                var minValue = $(this).find('input[type=range]').attr('min');
                var maxValue = $(this).find('input[type=range]').attr('max');

                $(this).attr('data-min', minValue);
                $(this).attr('data-max', maxValue);
            });
        ");

        return ee('View')->make('slider:pair')->render($field);

        return form_range(array_merge($field, ['value' => $field['from']])) . BR . form_range(array_merge($field, ['value' => $field['to']]));
    }

    
    public function save($data)
    {
        if (is_array($data)) {
            ee()->load->helper('custom_field');
            sort($data);
            $data = encode_multi_field($data);
        }

        return $data;
    }

    public function replace_tag($data, $params = '', $tagdata = '')
    {
        ee()->load->helper('custom_field');
        $data = decode_multi_field($data);
        if (!isset($data[0])) {
            $data[0] = (isset($this->settings['field_min_value']) && is_numeric($this->settings['field_min_value'])) ? $this->settings['field_min_value'] : 0;
        }
        if (!isset($data[1])) {
            $data[1] = (isset($this->settings['field_max_value']) && is_numeric($this->settings['field_max_value'])) ? $this->settings['field_max_value'] : 100;
        }

        $vars = [
            'from' => parent::replace_tag($data[0], $params),
            'to' => parent::replace_tag($data[1], $params)
        ];

        if (!empty($tagdata)) {
            return ee()->TMPL->parse_variables_row($tagdata, $vars);
        }

        return $vars['from'] . ' &mdash; ' . $vars['to'];
    }

    public function replace_from($data, $params = '', $tagdata = '')
    {
        return $this->replace_tag($data, $params, '{from}');
    }

    public function replace_to($data, $params = '', $tagdata = '')
    {
        return $this->replace_tag($data, $params, '{to}');
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        if (is_null($data) || $data === '') {
            return '';
        }
        return html_entity_decode($this->replace_tag($data));
    }

}

// END Text_Ft class

// EOF
