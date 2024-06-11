<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Member\TemplateGenerators;

use ExpressionEngine\Service\TemplateGenerator\AbstractTemplateGenerator;

class Profile extends AbstractTemplateGenerator
{
    protected $name = 'member_profile_template_generator';

    protected $templates = [
        'index' => 'Members list page',
        'registration' => 'New member registration',
        'login' => 'Member login page',
        'forgot-password' => 'Forgot password page',
        'reset-password' => 'Reset password page',
        'profile' => 'Public member profile page',
        'edit-profile' => 'Edit member profile page'
    ];

    public function getVariables(): array
    {
        ee()->load->library('session'); //getAllCustomFields requres session
        $vars = ee('TemplateGenerator')->getOptionValues();
        $vars['fields'] = [];
        // get list of assigned channel fields and pass the data to array
        // for simple fields, we'll just pass field info as variables,
        // for complex fields we'll have to spin their own generators
        if (!is_array($vars['channel'])) {
            $vars['channel'] = [$vars['channel']];
        }
        //loop through installed fieldtypes and grab the stubs and generators
        $stubsAndGenerators = ee('TemplateGenerator')->getFieldtypesStubsAndGenerators();

        // get the fields for assigned channels
        $channels = ee('Model')->get('Channel')->filter('channel_name', 'IN', $vars['channel'])->all();
        foreach ($channels as $channel) {
            $fields = $channel->getAllCustomFields();
            foreach ($fields as $fieldInfo) {
                if (!isset($stubsAndGenerators[$fieldInfo->field_type])) {
                    // fieldtype is not installed, skip it
                    continue;
                }
                // by default, we'll use generic field stub
                // but we'll let each field type to override it
                // by either providing stub property, or calling its own generator
                $field = [
                    'field_type' => $fieldInfo->field_type,
                    'field_name' => $fieldInfo->field_name,
                    'field_label' => $fieldInfo->field_label,
                    'stub' => $stubsAndGenerators[$fieldInfo->field_type]['stub'],
                    'docs_url' => $stubsAndGenerators[$fieldInfo->field_type]['docs_url'],
                    'is_tag_pair' => $stubsAndGenerators[$fieldInfo->field_type]['is_tag_pair'],
                ];
                // if the field has its own generator, spin it
                // we'll not be using service (as it's singletone),
                // but spin and destroy new factory for each field
                // ... or something on that front
                $vars['fields'][$fieldInfo->field_name] = $field;
            }
        }
        // channel is array at this point, but for replacement it needs to be a string
        $vars['channel'] = implode('|', $vars['channel']);

        return $vars;
    }

}
