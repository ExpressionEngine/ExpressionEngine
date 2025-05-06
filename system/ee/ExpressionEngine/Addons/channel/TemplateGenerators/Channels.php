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

class Channels extends AbstractTemplateGenerator
{
    protected $name = 'channel_channels_template_generator';

    // This is a list of locations to exclude this generator from
    protected $excludeFrom = ['CP'];

    protected $templates = [
        'index' => ['name' => 'Basic channel usage', 'type' => ''],
    ];

    protected $options = [
        'channel' => [
            'desc' => 'select_channels_to_generate',
            'type' => 'checkbox',
            'required' => true,
            'choices' => 'getChannelList',
        ],
    ];

    protected $_validation_rules = [
        'channel' => 'required|validateChannelExists'
    ];

    /**
     * Populate list of channels for the channel option
     *
     * @return array
     */
    public function getChannelList()
    {
        return ee('Model')->get('Channel')->all(true)->getDictionary('channel_name', 'channel_title');
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
    public function validateChannelExists($key, $value, $params, $rule)
    {
        if (!is_array($value)) {
            $value = [$value];
        }
        $channels = ee('Model')->get('Channel')->filter('channel_name', 'IN', $value)->all();
        if (count($channels) !== count($value)) {
            return 'invalid_channel';
        }

        return true;
    }

    public function getVariables(): array
    {
        ee()->load->library('session'); //getAllCustomFields requires session

        $vars = [
            'fields' => [],
            'channel' => $this->input->get('channel'),
            'show_comments' => $this->input->get('show_comments', false),
        ];

        if (!is_array($vars['channel'])) {
            $vars['channel'] = [$vars['channel']];
        }

        // get the fields for assigned channels
        $channels = ee('Model')->get('Channel')->filter('channel_name', 'IN', $vars['channel'])->all();
        $channel_titles = [];
        if (!empty($channels)) {
            foreach ($channels as $channel) {
                $channel_titles[] = $channel->channel_title;
                $fields = $channel->getAllCustomFields();
                foreach ($fields as $fieldInfo) {
                    // get the field variables
                    $field = ee('TemplateGenerator')->getFieldVariables($fieldInfo);

                    // if field is null, continue to the next field
                    if (is_null($field)) {
                        continue;
                    }

                    // add the field to the list of fields
                    $vars['fields'][$fieldInfo->field_name] = $field;
                }
            }
        }

        // channel is array at this point, but for replacement it needs to be a string
        $vars['channel'] = implode('|', $vars['channel']);
        $vars['channel_title'] = implode(', ', $channel_titles);

        return $vars;
    }
}
