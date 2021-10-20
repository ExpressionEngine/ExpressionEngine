<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Advisor;

class AddonAdvisor
{
    public function getMissingAddons()
    {
        $data = [];
        $modules = ee('Model')->get('Module')->all();
        foreach ($modules as $module) {
            $name = strtolower($module->module_name);
            $addon = ee('Addon')->get($name);
            if (empty($addon) || !$addon->hasModule()) {
                if (!isset($data[$name])) {
                    $data[$name] = [];
                }
                $data[$name][] = 'module';
            }
        }

        $plugins = ee('Model')->get('Plugin')->all();
        foreach ($plugins as $plugin) {
            $name = strtolower($plugin->plugin_name);
            $addon = ee('Addon')->get($name);
            if (empty($addon) || !$addon->hasPlugin()) {
                if (!isset($data[$name])) {
                    $data[$name] = [];
                }
                $data[$name][] = 'plugin';
            }
        }

        $extensions = ee('Model')->get('Extension')->all();
        foreach ($extensions as $extension) {
            $name = substr(strtolower($extension->class), 0, -4);
            $addon = ee('Addon')->get($name);
            if (empty($addon) || !$addon->hasExtension()) {
                if (!isset($data[$name])) {
                    $data[$name] = [];
                }
                $data[$name][] = 'extension';
            }
        }

        $fieldtypes = ee('Model')->get('Fieldtype')->all();
        $all_fieldtypes = [];
        foreach (ee('Addon')->installed() as $addon) {
            if ($addon->hasFieldtype()) {
                $all_fieldtypes = array_merge($all_fieldtypes, $addon->getFieldtypeNames());
            }
        }
        foreach ($fieldtypes as $fieldtype) {
            $name = strtolower($fieldtype->name);
            $addon = ee('Addon')->get($name);
            if (empty($addon) && !array_key_exists($name, $all_fieldtypes)) {
                if (!isset($data[$name])) {
                    $data[$name] = [];
                }
                $data[$name][] = 'fieldtype';
            }
        }

        return $data;
    }

    public function getMissingAddonsCount()
    {
        return count($this->getMissingAddons());
    }
}

// EOF
