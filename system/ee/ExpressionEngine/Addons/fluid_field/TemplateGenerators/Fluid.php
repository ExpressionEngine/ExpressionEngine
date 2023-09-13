<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\FluidField\TemplateGenerators;

use ExpressionEngine\Service\TemplateGenerator\AbstractFieldTemplateGenerator;
use ExpressionEngine\Service\TemplateGenerator\FieldTemplateGeneratorInterface;

class Fluid extends AbstractFieldTemplateGenerator implements FieldTemplateGeneratorInterface
{
    public function getVariables(): array
    {
        $vars = [
            'fluidFields' => [],
            'fluidFieldGroups' => []
        ];
        $stubsAndGenerators = ee('TemplateGenerator')->getFieldtypeStubsAndGenerators();

        $fields = ee('Model')->get('ChannelField', $this->settings['field_channel_fields'])
            ->order('field_label')
            ->all();
        foreach ($fields as $field) {
            $vars['fluidFields'][$field->field_name] = [
                'field_type' => $field->field_type,
                'field_name' => 'content',
                'field_label' => $field->field_label,
                'stub' => $stubsAndGenerators[$field->field_type]['stub'],
                'docs_url' => $stubsAndGenerators[$field->field_type]['docs_url'],
                'is_tag_pair' => $stubsAndGenerators[$field->field_type]['is_tag_pair'],
            ];

            // if the field has its own generator, instantiate the field and pass to generator
            if (!empty($stubsAndGenerators[$field->field_type]['generator'])) {
                $interfaces = class_implements($stubsAndGenerators[$field->field_type]['generator']);
                if (!empty($interfaces) && in_array(FieldTemplateGeneratorInterface::class, $interfaces)) {
                    $generator = new $stubsAndGenerators[$field->field_type]['generator']($field, ['field_prefix' => 'content']);
                    $vars['fluidFields'][$field->field_name] = array_merge($vars['fluidFields'][$field->field_name], $generator->getVariables());
                }
            }
        }

        $fieldGroups = ee('Model')->get('ChannelFieldGroup', $this->settings['field_channel_field_groups'])
            ->with('ChannelFields')
            ->order('group_name')
            ->all();
        foreach ($fieldGroups as $group) {
            $groupFields = [];
            foreach ($group->ChannelFields as $field) {
                $groupFields[$field->field_name] = [
                    'field_type' => $field->field_type,
                    'field_name' => 'content',
                    'field_label' => $field->field_label,
                    'stub' => $stubsAndGenerators[$field->field_type]['stub'],
                    'docs_url' => $stubsAndGenerators[$field->field_type]['docs_url'],
                    'is_tag_pair' => $stubsAndGenerators[$field->field_type]['is_tag_pair'],
                ];

                // if the field has its own generator, instantiate the field and pass to generator
                if (!empty($stubsAndGenerators[$field->field_type]['generator'])) {
                    $interfaces = class_implements($stubsAndGenerators[$field->field_type]['generator']);
                    if (!empty($interfaces) && in_array(FieldTemplateGeneratorInterface::class, $interfaces)) {
                        $generator = new $stubsAndGenerators[$field->field_type]['generator']($field, ['field_prefix' => 'content']);
                        $groupFields = array_merge($groupFields, $generator->getVariables());
                    }
                }
            }
            $vars['fluidFieldGroups'][$group->short_name] = $groupFields;
        }

        return $vars;
    }
}
