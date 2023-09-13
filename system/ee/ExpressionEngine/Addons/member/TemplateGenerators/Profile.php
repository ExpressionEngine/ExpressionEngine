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
use ExpressionEngine\Service\TemplateGenerator\TemplateGeneratorInterface;

class Profile extends AbstractTemplateGenerator implements TemplateGeneratorInterface
{
    protected $name = 'Member Profile Template Generator';

    protected $templates = [
        'index' => [
            'type' => 'webpage',
            'notes' => 'List all entries'
        ],
        'entry' => [
            'type' => 'webpage',
            'notes' => 'Entry details page'
        ]
    ];

    protected $options = [
        'channel' => [
            'title' => 'channel',
            'desc' => 'channel_desc',
            'type' => 'checkbox',
            'required' => true,
            'choices' => [],
            'callback' => 'getChannels',
        ],
    ];

    protected $_validation_rules = [
        'channel' => 'validateChannelExists'
    ];

    /**
     * Populate list of channels for the channel option
     *
     * @return array
     */
    public function getChannels()
    {
        $channels = ee('Model')->get('Channel')->all(true)->getDictionary('channel_name', 'channel_title');
        return $channels;
    }

    public function validateChannelExists($key, $value, $params, $rule)
    {
        if (!is_array($value)) {
            $value = [$value];
        }
        $channels = ee('Model')->get('Channel')->filter('channel_name', 'IN', $value)->all();
        if (count($channels) !== count($value)) {
            return 'invalid_channels';
        }
        return true;
    }

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
        $stubsAndGenerators = $this->getFieldtypesStubsAndGenerators();

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

    /**
     * Get the stubs and generators for all installed fieldtypes
     *
     * @return array
     */
    private function getFieldtypesStubsAndGenerators()
    {
        $data = [];
        ee()->legacy_api->instantiate('channel_fields');
        foreach (ee('Addon')->installed() as $addon) {
            if ($addon->hasFieldtype()) {
                $provider = $addon->getProvider();
                foreach ($addon->get('fieldtypes', array()) as $fieldtype => $metadata) {
                    $stub = 'field';
                    $generator = null;
                    $ftClassName = ee()->api_channel_fields->include_handler($fieldtype);
                    $reflection = new \ReflectionClass($ftClassName);
                    $instance = $reflection->newInstanceWithoutConstructor();
                    if (isset($instance->stub)) {
                        // grab the stub out of fieldtype property
                        $stub = $instance->stub;
                    }
                    // is a generator set for this field?
                    if (isset($metadata['templateGenerator'])) {
                        $fqcn = trim($provider->getNamespace(), '\\') . '\\TemplateGenerators\\' . $metadata['templateGenerator'];
                        if (class_exists($fqcn)) {
                            $generator = $fqcn;
                        }
                    }
                    $data[$fieldtype] = [
                        'stub' => $provider->getPrefix() . ':' . $stub,
                        'docs_url' => $provider->get('docs_url') ?? $provider->get('author_url'),
                        'generator' => $generator,
                        'is_tag_pair' => (isset($instance->has_array_data) && $instance->has_array_data === true)
                    ];
                }
            }
        }
        return $data;
    }
}
