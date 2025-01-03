<?php
/**
* This source file is part of the open source project
* ExpressionEngine (https://expressionengine.com)
*
* @link      https://expressionengine.com/
* @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
* @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
*/

namespace ExpressionEngine\Controller\Design;

use ExpressionEngine\Controller\Design\AbstractDesign as AbstractDesignController;
use ExpressionEngine\Model\Channel\ChannelField;

/**
* Design Controller
*/
class Copy extends AbstractDesignController
{
    public function __construct()
    {
        parent::__construct();

        ee()->load->library('api');
        ee()->legacy_api->instantiate('template_structure');
    }


    public function fields(int $id)
    {
        $field = ee('Model')->get('ChannelField', $id)->first();

        if (! $field) {
            show_404();
        }

        // Get the template data
        $data = [
            'field' => $field->field_name,
            'template_group' => '',
            'templates' => ['all']
        ];

        // Register all template generators, and include ones disabled for template generation
        ee('TemplateGenerator')->setCallLocation('copy')->registerAllTemplateGenerators();

        $generator = ee('TemplateGenerator')->make('channel:fields');
        $validationResult = $generator->validatePartial($data);
        $result = $generator->generate($data, false);

        // return the template_data
        return $result['templates']['index']['template_data'];
    }

    public function channels(int $id)
    {
        $channel = ee('Model')->get('Channel', $id)->first();

        if (! $channel) {
            show_404();
        }

        // Get the template data
        $data = [
            'channel' => $channel->channel_name,
            'template_group' => '',
            'templates' => ['all']
        ];

        // Register all template generators, and include ones disabled for template generation
        ee('TemplateGenerator')->setCallLocation('copy')->registerAllTemplateGenerators();

        $generator = ee('TemplateGenerator')->make('channel:channels');
        $validationResult = $generator->validatePartial($data);
        $result = $generator->generate($data, false);

        // return the template_data
        return $result['templates']['index']['template_data'];
    }

    public function fieldGroups(int $id)
    {
        $field_group = ee('Model')->get('ChannelFieldGroup', $id)->first();

        if (! $field_group) {
            show_404();
        }

        // Get the template data
        $data = [
            'field_group' => $field_group->group_name,
            'template_group' => '',
            'templates' => ['all']
        ];

        // Register all template generators, and include ones disabled for template generation
        ee('TemplateGenerator')->setCallLocation('copy')->registerAllTemplateGenerators();
        $generator = ee('TemplateGenerator')->make('channel:fieldGroups');

        $validationResult = $generator->validatePartial($data);
        $result = $generator->generate($data, false);

        // return the template_data
        return $result['templates']['index']['template_data'];
    }

    public function fluid(int $fluid_id, $context, $content_id)
    {
        $fluidField = ee('Model')->get('ChannelField', $fluid_id)->filter('field_type', 'fluid_field')->first();

        if (! $fluidField) {
            show_404();
        }

        if ($context == 'group') {
            return $this->fluidFieldGroup($fluidField, $content_id);
        } else {
            return $this->fluidSubfield($fluidField, $content_id);
        }
    }

    private function fluidSubfield(ChannelField $fluidField, int $field_id)
    {
        $field = ee('Model')->get('ChannelField', $field_id)->first();

        if (! $field) {
            show_404();
        }

        // Get the template data
        $fluidTempGen = ee('TemplateGenerator')->makeField($fluidField->field_type, $fluidField, ['field_prefix' => 'content']);

        // Build the field in a fluid field context
        $vars['field_name'] = $fluidField->field_name;
        $vars['fluidFields'] = [];
        $vars['fluidFieldGroups'] = [];

        // Get the field variables
        $vars['fluidFields'][$field->field_name] = $fluidTempGen->getFieldVars($field);

        return ee('View/Stub')->make('fluid_field:field')
            ->setTemplateEngine(ee()->api_template_structure->get_default_template_engine())
            ->setTemplateType('copy')
            ->render($vars);
    }

    private function fluidFieldGroup(ChannelField $fluidField, int $field_group_id)
    {
        $fieldGroup = ee('Model')->get('ChannelFieldGroup', $field_group_id)
            ->with('ChannelFields')
            ->first();

        if (! $fieldGroup) {
            show_404();
        }

        // Get the template data
        $fluidTempGen = ee('TemplateGenerator')->makeField($fluidField->field_type, $fluidField, ['field_prefix' => 'content']);

        // Build the field in a fluid field context
        $vars['field_name'] = $fluidField->field_name;
        $vars['fluidFields'] = [];
        $vars['fluidFieldGroups'] = [];

        // Get all fields in the group
        $fields = ee('Model')->get('ChannelField')->with('ChannelFieldGroups')->filter('ChannelFieldGroups.group_id', $field_group_id)->all()->indexBy('field_id');

        foreach ($fields as $field) {
            $vars['fluidFieldGroups'][$fieldGroup->short_name][$field->field_name] = $fluidTempGen->getFieldVars($field);
        }

        return ee('View/Stub')->make('fluid_field:field')
            ->setTemplateEngine(ee()->api_template_structure->get_default_template_engine())
            ->setTemplateType('copy')
            ->render($vars);
    }
}

// EOF
