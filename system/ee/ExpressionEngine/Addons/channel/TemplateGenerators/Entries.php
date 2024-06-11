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
use ExpressionEngine\Service\TemplateGenerator\FieldTemplateGeneratorInterface;

class Entries extends AbstractTemplateGenerator
{
    protected $name = 'channel_entries_template_generator';

    protected $templates = [
        'index' => 'Listing for all entries',
        'entry' => 'Entry detail page',
        'archive' => 'Entry listing for a given year and month',
        'category' => 'Entry listing for a given category',
        'feed' => ['name' => 'RSS feed for all entries', 'type' => 'feed'],
        'sitemap' => ['name' => 'XML sitemap for all entries', 'type' => 'xml'],
    ];

    protected $options = [
        'channel' => [
            'desc' => 'select_channels_to_generate',
            'type' => 'checkbox',
            'required' => true,
            'choices' => [],
            'callback' => 'getChannelList',
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
        $channels = ee('Model')->get('Channel')->all(true)->getDictionary('channel_name', 'channel_title');
        return $channels;
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

    public function prepareVariables($options): array
    {
        ee()->load->library('session'); //getAllCustomFields requires session
        $vars = $options;
        $vars['fields'] = [];
        // get list of assigned channel fields and pass the data to array
        // for simple fields, we'll just pass field info as variables,
        // for complex fields we'll have to spin their own generators
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
                    $fieldtypeGenerator = ee('TemplateGenerator')->getFieldtype($fieldInfo->field_type);

                    // fieldtype is not installed, skip it
                    if (!$fieldtypeGenerator) {
                        continue;
                    }

                    // by default, we'll use generic field stub
                    // but we'll let each field type to override it
                    // by either providing stub property, or calling its own generator
                    $field = [
                        'field_type' => $fieldInfo->field_type,
                        'field_name' => $fieldInfo->field_name,
                        'field_label' => $fieldInfo->field_label,
                        'field_settings' => $fieldInfo->field_settings,
                        'stub' => $fieldtypeGenerator['stub'],
                        'docs_url' => $fieldtypeGenerator['docs_url'],
                        'is_tag_pair' => $fieldtypeGenerator['is_tag_pair'],
                        'is_search_excerpt' => $channel->search_excerpt == $fieldInfo->field_id,
                    ];

                    $generator = $this->makeField($fieldInfo->field_type, $fieldInfo);

                    // if the field has its own generator, instantiate the field and pass to generator
                    if ($generator) {
                        $field = array_merge($field, $generator->getVariables());
                    }

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
