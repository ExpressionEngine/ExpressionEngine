<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Addon;

/**
 * Add-on Installer Service
 */
class Installer
{
    public $addon;
    public $shortname;
    public $has_cp_backend = 'n';
    public $has_publish_fields = 'n';
    public $version;
    public $actions = []; //module actions
    public $settings = []; //extension settings
    public $methods = []; //extensions methods

    public function __construct($settings = [])
    {
        $this->shortname = strtolower(str_replace(['_upd', '_ext'], '', get_class($this)));
        $this->addon = ee('Addon')->get($this->shortname);
        $this->settings = $settings;
        $this->version = $this->addon->getVersion();
    }

    /**
     * Module installer
     * @return bool
     */
    public function install()
    {
        $classname = $this->addon->getModuleClass();

        ee('Model')->make('Module', [
            'module_name' => $classname,
            'module_version' => $this->addon->getVersion(),
            'has_cp_backend' => $this->has_cp_backend,
            'has_publish_fields' => $this->has_publish_fields,
        ])->save();

        // Loop through each action and install it
        foreach ($this->actions as $action) {
            if (!isset($action['class'])) {
                $action['class'] = $classname;
            }
            ee('Model')->make('Action', $action)->save();
        }

        // Install ext hooks
        $this->activate_extension();

        ee('Migration')->migrateAllByType($this->shortname);

        ee()->db->data_cache = []; // Reset the cache so it will re-fetch a list of tables

        return true;
    }

    /**
     * Module updater
     * @return bool
     */
    public function update($current = '')
    {
        if ($current == '' or version_compare($current, $this->version, '==')) {
            return false;
        }

        $classname = $this->addon->getModuleClass();

        // Loop through each action and insert it if it doesnt exist, update if it does
        foreach ($this->actions as $action) {
            if (!isset($action['class'])) {
                $action['class'] = $classname;
            }

            $actionModel = ee('Model')->get('Action')
                ->filter('class', $action['class'])
                ->filter('method', $action['method'])
                ->first();

            if (!empty($actionModel)) {
                $actionModel->set($action)->save();
            } else {
                ee('Model')->make('Action', $action)->save();
            }
        }

        // Run the ext updater
        $this->activate_extension();

        ee('Migration')->migrateAllByType($this->shortname);

        return true;
    }

    /**
     * Module uninstaller
     * @return bool
     */
    public function uninstall()
    {
        $classname = $this->addon->getModuleClass();

        ee('Migration')->rollbackAllByType($this->shortname, false);

        ee('Model')
            ->get('Module')
            ->filter('module_name', $classname)
            ->delete();

        if ($this->addon->hasControlPanel()) {
            ee('Model')
                ->get('Module')
                ->filter('module_name', $this->addon->getControlPanelClass())
                ->delete();
        }

        foreach ($this->actions as $action) {
            if (!isset($action['class'])) {
                $action['class'] = $classname;
            }
            ee('Model')
                ->get('Action')
                ->filter('class', $action['class'])
                ->filter('method', $action['method'])
                ->delete();
        }

        $this->disable_extension();

        return true;
    }

    /**
     * Extension installer
     * @return bool
     */
    public function activate_extension()
    {
        // If we don't have the extension class, return false
        if (! $this->addon->hasExtension()) {
            return false;
        }

        $classname = $this->addon->getExtensionClass();

        // Loop through each extension and insert it if it doesnt exist, update if it does
        foreach ($this->methods as $method) {
            $ext = [
                'class' => $classname,
                'method' => isset($method['method']) ? $method['method'] : $method['hook'],
                'hook' => $method['hook'],
                'settings' => serialize($this->settings),
                'priority' => isset($method['priority']) ? (int) $method['priority'] : 10,
                'version' => $this->addon->getVersion(),
                'enabled' => (isset($method['enabled']) && in_array($method['enabled'], ['n', false])) ? 'n' : 'y'
            ];

            // Get the extension as a model
            $extensionModel = ee('Model')->get('Extension')
                ->filter('class', $ext['class'])
                ->filter('hook', $ext['hook'])
                ->first();

            if (!empty($extensionModel)) {
                // If we found an extension matching this one, update it
                $extensionModel->set($ext)->save();
            } else {
                // If we didnt find a matching Extension, lets just insert it
                ee('Model')->make('Extension', $ext)->save();
            }
        }

        return true;
    }

    /**
     * Extension installer
     * @return bool
     */
    public function disable_extension()
    {
        // If we don't have the extension class, return false
        if (! $this->addon->hasExtension()) {
            return false;
        }

        ee('Model')
            ->get('Extension')
            ->filter('class', $this->addon->getExtensionClass())
            ->delete();

        return true;
    }
}

// EOF
