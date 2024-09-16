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

class Fluid extends AbstractFieldTemplateGenerator
{
    public function getVariables(): array
    {
        $vars = [
            'fluidFields' => [],
            'fluidFieldGroups' => []
        ];

        $fields = ee('Model')->get('ChannelField', $this->settings['field_channel_fields'])->order('field_label')->all();

        foreach ($fields as $field) {
            $fieldtypeGenerator = ee('TemplateGenerator')->getFieldtype($field->field_type);
            $vars['fluidFields'][$field->field_name] = [
                'field_type' => $field->field_type,
                'field_name' => 'content',
                'field_label' => $field->field_label,
                'stub' => $fieldtypeGenerator['stub'],
                'docs_url' => $fieldtypeGenerator['docs_url'],
                'is_tag_pair' => $fieldtypeGenerator['is_tag_pair'],
            ];

            $generator = $this->makeField($field->field_type, $field, ['field_prefix' => 'content']);

            // if the field has its own generator, instantiate the field and pass to generator
            if ($generator) {
                $vars['fluidFields'][$field->field_name] = array_merge(
                    $vars['fluidFields'][$field->field_name], $generator->getVariables()
                );
            }
        }

        // Set the field_channel_field_groups setting to an empty array if it doesn't exist
        if(! isset($this->settings['field_channel_field_groups'])) {
            $this->settings['field_channel_field_groups'] = [];
        }

        $fieldGroups = ee('Model')->get('ChannelFieldGroup', $this->settings['field_channel_field_groups'])
            ->with('ChannelFields')
            ->order('group_name')
            ->all();

        foreach ($fieldGroups as $group) {
            $groupFields = [];
            foreach ($group->ChannelFields as $field) {
                $fieldtypeGenerator = ee('TemplateGenerator')->getFieldtype($field->field_type);
                $groupFields[$field->field_name] = [
                    'field_type' => $field->field_type,
                    'field_name' => 'content',
                    'field_label' => $field->field_label,
                    'stub' => $fieldtypeGenerator['stub'],
                    'docs_url' => $fieldtypeGenerator['docs_url'],
                    'is_tag_pair' => $fieldtypeGenerator['is_tag_pair'],
                ];

                $generator = $this->makeField($field->field_type, $field, ['field_prefix' => 'content']);

                // if the field has its own generator, instantiate the field and pass to generator
                if ($generator) {
                    $groupFields[$field->field_name] = array_merge($groupFields[$field->field_name], $generator->getVariables());
                }
            }
            $vars['fluidFieldGroups'][$group->short_name] = $groupFields;
        }

        return $vars;
    }
}
