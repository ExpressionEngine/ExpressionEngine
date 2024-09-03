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
        return ee('TemplateGenerator')->getChannelVariables($this->input->get('channel'));
    }
}
