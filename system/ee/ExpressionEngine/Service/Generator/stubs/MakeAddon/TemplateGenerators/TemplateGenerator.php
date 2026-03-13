<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace {{namespace}}\TemplateGenerators;

use ExpressionEngine\Service\TemplateGenerator\AbstractTemplateGenerator;

class {{TemplateGeneratorName}} extends AbstractTemplateGenerator
{
    // Can be a language string
    protected $name = '{{TemplateGeneratorName}} Template Generator';

    // This is a list of locations to exclude this generator from
    // protected $excludeFrom = ['CP'];

    // This is a list of templates that can be used when generating the templates
    protected $templates = [
        'index' => ['name' => 'Basic template tag usage', 'type' => 'webpage'],
    ];

    // This is a list of options that can be used when generating the templates
    // These should mimic the parameters your template tag would accept
    protected $options = [
        'color' => [
            'desc' => 'Select a color',
            'type' => 'select',
            'required' => true,
            'choices' => 'getColorsList',
        ],
        'numbers' => [
            'desc' => 'Select numbers',
            'type' => 'checkbox',
            'required' => true,
            'choices' => 'getNumbersList',
        ],
        'channel' => [
            'desc' => 'Select a channel',
            'type' => 'select',
            'required' => true,
            'choices' => 'getChannelList',
        ],
    ];

    protected $_validation_rules = [
        'color' => 'required|validateColorExists',
        'numbers' => 'required|validateNumberExists',
        'channel' => 'required|validateChannelExists',
    ];

    /**
     * Populate list of colors for the color option
     *
     * @return array
     */
    public function getColorsList()
    {
        return [
            'red' => 'Red',
            'blue' => 'Blue',
            'green' => 'Green',
            'yellow' => 'Yellow',
        ];
    }

    /**
     * Populate list of numbers for the number option
     *
     * @return array
     */
    public function getNumbersList()
    {
        return [
            '1' => 'One',
            '2' => 'Two',
            '3' => 'Three',
            '4' => 'Four',
        ];
    }

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
     * Validate that the color exists
     *
     * @param [type] $key
     * @param [type] $value
     * @param [type] $params
     * @param [type] $rule
     * @return mixed
     */
    public function validateColorExists($key, $value, $params, $rule)
    {
        if (!is_array($value)) {
            $value = [$value];
        }
        $colors = ['red', 'green', 'blue', 'yellow'];
        foreach ($value as $color) {
            if (!in_array($color, $colors)) {
                return 'Invalid color';
            }
        }
        return true;
    }

    /**
     * Validate that the number exists
     *
     * @param [type] $key
     * @param [type] $value
     * @param [type] $params
     * @param [type] $rule
     * @return mixed
     */
    public function validateNumberExists($key, $value, $params, $rule)
    {
        if (!is_array($value)) {
            $value = [$value];
        }
        $numbers = ['1', '2', '3', '4'];
        foreach ($value as $number) {
            if (!in_array($number, $numbers)) {
                return 'Invalid number';
            }
        }
        return true;
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
            return 'Invalid channel';
        }

        return true;
    }

    public function getVariables(): array
    {
        $vars = [
            'color' => $this->input->get('color'),
            'numbers' => $this->input->get('numbers'),
            'channel' => $this->input->get('channel'),
        ];

        return $vars;
    }
}
