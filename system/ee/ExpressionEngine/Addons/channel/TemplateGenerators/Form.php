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

use ExpressionEngine\Service\TemplateGenerator\TemplateGeneratorInterface;
use ExpressionEngine\Service\TemplateGenerator\FieldTemplateGeneratorInterface;

class Form extends Entries implements TemplateGeneratorInterface
{
    protected $name = 'channel_form_template_generator';

    protected $templates = [
        'index' => 'Front-end Publish Entry Page'
    ];

    protected $options = [
        'channel' => [
            'title' => 'channel',
            'desc' => 'channel_name',
            'type' => 'select',
            'required' => true,
            'callback' => 'getChannelList',
        ],
    ];

    protected $_validation_rules = [
        'channel' => 'validateChannelExists'
    ];

    public function getVariables(): array
    {
        ee()->load->library('session'); //getAllCustomFields requres session
        $vars = ee('TemplateGenerator')->getOptionValues();
        $vars['fields'] = [];
        // get list of assigned channel fields and pass the data to array
        // for simple fields, we'll just pass field info as variables,
        // for complex fields we'll have to spin their own generators

        //loop through installed fieldtypes and grab the stubs and generators
        $stubsAndGenerators = ee('TemplateGenerator')->getFieldtypeStubsAndGenerators();

        // get the fields for assigned channels
        $channel = ee('Model')->get('Channel')->filter('channel_name', $vars['channel'])->first();
        $vars['channel_title'] = $channel->channel_title;
        $vars['channel_name'] = $channel->channel_name;
        $fields = $channel->getAllCustomFields();
        foreach ($fields as $fieldInfo) {
            if (!isset($stubsAndGenerators[$fieldInfo->field_type])) {
                // fieldtype is not installed, skip it
                continue;
            }
            // by default, we'll use generic field stub
            // but we'll let each field type to override it
            // by either providing stub property, or calling its own generator
            $stub = explode(":", $stubsAndGenerators[$fieldInfo->field_type]['stub']);
            $field = [
                'field_type' => $fieldInfo->field_type,
                'field_name' => $fieldInfo->field_name,
                'field_label' => $fieldInfo->field_label,
                'field_maxl' => $fieldInfo->field_maxl,
                'stub' => $stub[0] . ':' . 'form/' . $stub[1],
                'docs_url' => $stubsAndGenerators[$fieldInfo->field_type]['docs_url'],
                'field_settings' => $fieldInfo->field_settings,
                'field_list_items' => $fieldInfo->getPossibleValuesForEvaluation(),
            ];
            // if the field has its own generator, instantiate the field and pass to generator
            if (!empty($stubsAndGenerators[$fieldInfo->field_type]['generator'])) {
                $interfaces = class_implements($stubsAndGenerators[$fieldInfo->field_type]['generator']);
                if (!empty($interfaces) && in_array(FieldTemplateGeneratorInterface::class, $interfaces)) {
                    $generator = new $stubsAndGenerators[$fieldInfo->field_type]['generator']($fieldInfo);
                    if (method_exists($generator, 'getFormVariables')) {
                        $field = array_merge($field, $generator->getFormVariables());
                    } else {
                        $field = array_merge($field, $generator->getVariables());
                    }
                }
            }
            $vars['fields'][$fieldInfo->field_name] = $field;
        }

        return $vars;
    }
}
