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

/**
* Design Controller
*/
class Copy extends AbstractDesignController
{
    public function fields($id)
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

    public function channels($id)
    {
        $channel = ee('Model')->get('Channel', $id)
            ->first();

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

    public function field_groups($id)
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

    public function fluid_subfield($id)
    {
        // TODO: get the fluid field subfield
    }
}

// EOF
