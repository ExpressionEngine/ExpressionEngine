<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Filesystem;

/**
 * AdapterManager
 */
class AdapterManager
{
    private $adapters = [];

    public function __construct()
    {
        $this->registerAdapter('local', [
            'name' => 'Local',
            'class' => Adapter\Local::class,
            'settings' => function($values) {
                return [
                    [
                        'title' => 'upload_url',
                        'desc' => 'upload_url_desc',
                        'fields' => [
                            'url' => [
                                'type' => 'text',
                                'value' => $values['url'] ?? '{base_url}',
                                'required' => true
                            ]
                        ]
                    ],
                    [
                        'title' => 'upload_path',
                        'desc' => 'upload_path_desc',
                        'fields' => [
                            'server_path' => [
                                'type' => 'text',
                                'value' => $values['server_path'] ?? '{base_path}',
                                'required' => true
                            ]
                        ]
                    ]
                ];
            }
        ]);
    }

    public function get($key)
    {
        if(!array_key_exists($key, $this->adapters)) {
            throw new \Exception("Missing filesystem adapter for [$key]");
        }

        return $this->adapters[$key];
    }

    public function make($key, $settings = [])
    {
        $adapter = $this->get($key);

        return new $adapter['class']($settings);
    }

    public function createSettingsFields($key, $values = [])
    {
        $adapter = $this->get($key);

        if(!array_key_exists('settings', $adapter)) {
            return [];
        }

        return is_callable($adapter['settings']) ? $adapter['settings']($values) : $adapter['settings'];
    }

    public function filterInputForAdapter($key, $input = [])
    {
        $fields = array_map(function($field) {
            return str_replace(['adapter_settings[', ']'], '', $field);    
        }, array_reduce($this->createSettingsFields($key), function($carry, $row) {
            if(array_key_exists('fields', $row)) {
                $carry = array_merge($carry, array_keys($row['fields']));
            }
            return $carry;
        }, []));

        return array_intersect_key($input, array_flip($fields));
    }

    public function registerAdapter($key, $data)
    {
        if(!array_key_exists('class', $data) || !class_exists($data['class'])) {
            // throw new \Exception("This adapter [$key] does not have a valid implementation.");
        }

        $this->adapters[$key] = $data;
    }

    public function all()
    {
        return $this->adapters;
    }

}