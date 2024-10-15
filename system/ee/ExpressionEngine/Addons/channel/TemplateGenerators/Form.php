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

class Form extends Entries
{
    protected $name = 'channel_form_template_generator';

    protected $templates = [
        'index' => 'Entry Publish Page'
    ];

    protected $options = [
        'channel' => [
            'title' => 'channel',
            'desc' => 'select_channels_to_generate',
            'type' => 'select',
            'required' => true,
            'choices' => 'getChannelList',
        ],
    ];

    protected $_validation_rules = [
        'channel' => 'validateChannelExists'
    ];

    public function prepareVariables($options): array
    {
        ee()->load->library('session'); //getAllCustomFields requres session
        // we need to make sure the fields can load their JS and that might be, um, tricky
        ee()->load->helper('form');
        ee()->router->set_class('cp');
        ee()->load->library('cp');
        ee()->router->set_class('ee');
        ee()->load->library('javascript');
        $vars = $options;
        $vars['fields'] = [];
        // get list of assigned channel fields and pass the data to array
        // for simple fields, we'll just pass field info as variables,
        // for complex fields we'll have to spin their own generators

        // get the fields for assigned channels
        $channel = ee('Model')->get('Channel')->filter('channel_name', $vars['channel'])->first();
        $vars['channel_title'] = $channel->channel_title;
        $vars['channel_name'] = $channel->channel_name;
        $fields = $channel->getAllCustomFields();
        foreach ($fields as $fieldInfo) {
            $fieldtypeGenerator = ee('TemplateGenerator')->getFieldtype($fieldInfo->field_type);

            // fieldtype is not installed, skip it
            if (!$fieldtypeGenerator) {
                continue;
            }

            // by default, we'll use generic field stub
            // but we'll let each field type to override it
            // by either providing stub property, or calling its own generator
            $stub = explode(":", $fieldtypeGenerator['stub']);
            $field = [
                'field_type' => $fieldInfo->field_type,
                'field_name' => $fieldInfo->field_name,
                'field_label' => $fieldInfo->field_label,
                'field_maxl' => $fieldInfo->field_maxl,
                'stub' => $stub[0] . ':' . 'form/' . $stub[1],
                'docs_url' => $fieldtypeGenerator['docs_url'],
                'field_settings' => $fieldInfo->field_settings,
                'field_text_direction' => $fieldInfo->field_text_direction,
                'field_ta_rows' => $fieldInfo->field_ta_rows,
                'field_list_items' => $fieldInfo->getPossibleValuesForEvaluation()
            ];

            $generator = $this->makeField($fieldInfo->field_type, $fieldInfo);

            // if the field has its own generator, instantiate the field and pass to generator
            if ($generator) {
                $field = array_merge(
                    $field,
                    (method_exists($generator, 'getFormVariables')) ? $generator->getFormVariables() : $generator->getVariables()
                );
            }
            $vars['fields'][$fieldInfo->field_name] = $field;
        }

        return $vars;
    }
}
