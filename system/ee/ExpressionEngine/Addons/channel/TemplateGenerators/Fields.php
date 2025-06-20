<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Channel\TemplateGenerators;

use ExpressionEngine\Service\TemplateGenerator\AbstractTemplateGenerator;

class Fields extends AbstractTemplateGenerator
{
    protected $name = 'channel_fields_template_generator';

    // This is a list of locations to exclude this generator from
    protected $excludeFrom = ['CP'];

    protected $templates = [
        'index' => ['name' => 'Basic field usage', 'type' => ''],
    ];

    protected $options = [
        'field' => [
            'desc' => 'select_fields_to_generate',
            'type' => 'checkbox',
            'required' => true,
            'choices' => 'getFieldList',
        ],
    ];

    protected $_validation_rules = [
        'field' => 'required|validateFieldExists'
    ];

    /**
     * Populate list of fields for the field option
     *
     * @return array
     */
    public function getFieldList()
    {
        return ee('Model')->get('ChannelField')->all(true)->getDictionary('field_name', 'field_label');
    }

    /**
     * Validate that the channel exists
     *
     * @param [type] $key
     * @param [type] $value
     * @param [type] $params
     * @param [type] $rule
     * @return mixed
     */
    public function validateFieldExists($key, $value, $params, $rule)
    {
        if (!is_array($value)) {
            $value = [$value];
        }
        $channelFields = ee('Model')->get('ChannelField')->filter('field_name', 'IN', $value)->all();
        if (count($channelFields) !== count($value)) {
            return 'invalid_field';
        }

        return true;
    }

    public function getVariables(): array
    {
        ee()->load->library('session'); //getAllCustomFields requires session

        $vars = [
            'fields' => [],
            'field' => $this->input->get('field')
        ];

        if (!is_array($vars['field'])) {
            $vars['field'] = [$vars['field']];
        }

        // get the fields for assigned channels
        $fields = ee('Model')->get('ChannelField')->filter('field_name', 'IN', $vars['field'])->all();

        foreach ($fields as $field) {
            // get the field variables
            $fieldVariables = ee('TemplateGenerator')->getFieldVariables($field);

            // if field is null, continue to the next field
            if (is_null($fieldVariables)) {
                continue;
            }

            // add the field to the list of fields
            $vars['fields'][$field->field_name] = $fieldVariables;
        }

        return $vars;
    }
}
