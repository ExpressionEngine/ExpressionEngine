<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Addon;

/**
 * Addon Installer Service
 */
class Installer
{
    public $addon;
    public $has_cp_backend = 'n';
    public $has_publish_fields = 'n';
    public $actions = [];

    public function __construct()
    {
        $caller = strtolower(str_replace(['_upd', '_ext'], '', get_class($this)));
        $this->addon = ee('Addon')->get($caller);
    }

    /**
     * Module installer
     */
    public function install()
    {
        ee('Model')->make('Module', [
            'module_name' => $this->addon->getModuleClass(),
            'module_version' => $this->addon->getVersion(),
            'has_cp_backend' => $this->has_cp_backend,
            'has_publish_fields' => $this->has_publish_fields,
        ])->save();

        foreach ($this->actions as $action) {
            ee('Model')->make('Action', $action)->save();
        }

        return true;
    }

    /**
     * Module uninstaller
     */
    public function uninstall()
    {
        ee('Model')
            ->get('Module')
            ->filter('module_name', $this->addon->getModuleClass())
            ->delete();

        foreach ($this->actions as $action) {
            ee('Model')
                ->get('Action')
                ->filter('class', $action['class'])
                ->filter('method', $action['method'])
                ->delete();
        }

        return true;
    }
}

// EOF
