<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Addon;

/**
 * Addon Installer Service
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

        foreach ($this->actions as $action) {
            if (!isset($action['class'])) {
                $action['class'] = $classname;
            }
            ee('Model')->make('Action', $action)->save();
        }

        ee('Migration')->migrateAllByType($this->shortname);

        return true;
    }

    /**
     * Module updater
     */
    public function update($current = '')
    {
        ee('Migration')->migrateAllByType($this->shortname);

        return true;
    }

    /**
     * Module uninstaller
     */
    public function uninstall()
    {
        $classname = $this->addon->getModuleClass();

        ee('Migration')->rollbackAllByType($this->shortname, false);

        ee('Model')
            ->get('Module')
            ->filter('module_name', $classname)
            ->delete();

        ee('Model')
            ->get('Module')
            ->filter('module_name', $this->addon->getControlPanelClass())
            ->delete();

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

        return true;
    }

    /**
     * Extension installer
     */
    public function activate_extension()
    {
        $classname = $this->addon->getExtensionClass();

        foreach ($this->methods as $method) {
            ee('Model')->make('Extension', [
                'class' => $classname,
                'method' => isset($method['method']) ? $method['method'] : $method['hook'],
                'hook' => $method['hook'],
                'settings' => serialize($this->settings),
                'priority' => isset($method['priority']) ? (int) $method['priority'] : 10,
                'version' => $this->addon->getVersion(),
                'enabled' => (isset($method['enabled']) && in_array($method['enabled'], ['n', false])) ? 'n' : 'y'
            ])->save();
        }

        return true;
    }

    /**
     * Extension installer
     */
    public function disable_extension()
    {
        ee('Model')
            ->get('Extension')
            ->filter('class', $this->addon->getExtensionClass())
            ->delete();

        return true;
    }
}

// EOF
