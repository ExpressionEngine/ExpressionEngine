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

class FieldGroups extends AbstractTemplateGenerator
{
    protected $name = 'channel_field_groups_template_generator';

    // This is a list of locations to exclude this generator from
    protected $excludeFrom = ['CP'];

    protected $templates = [
        'index' => ['name' => 'Basic field group usage', 'type' => ''],
    ];

    protected $options = [
        'field_group' => [
            'desc' => 'select_field_groups_to_generate',
            'type' => 'checkbox',
            'required' => true,
            'choices' => 'getFieldGroupList',
        ],
    ];

    protected $_validation_rules = [
        'field_group' => 'required|validateFieldGroupExists'
    ];

    /**
     * Populate list of field_groups for the field_group option
     *
     * @return array
     */
    public function getFieldGroupList()
    {
        return ee('Model')->get('ChannelFieldGroup')->all(true)->getDictionary('group_id', 'group_name');
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
    public function validateFieldGroupExists($key, $value, $params, $rule)
    {
        if (!is_array($value)) {
            $value = [$value];
        }
        $channelFieldGroups = ee('Model')->get('ChannelFieldGroup')->filter('group_name', 'IN', $value)->all();
        if (count($channelFieldGroups) !== count($value)) {
            return 'invalid_field_group';
        }

        return true;
    }

    public function getVariables(): array
    {
        return ee('TemplateGenerator')->getFieldGroupVariables($this->input->get('field_group'));
    }
}
