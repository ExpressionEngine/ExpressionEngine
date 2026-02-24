<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Generator;

use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Library\String\Str;

class WidgetGenerator extends AbstractGenerator
{
    public $widgetName;
    public $namespace;
    public $addonName;
    protected $widgetsPath;

    public function __construct(Filesystem $filesystem, Str $str, array $data)
    {
        // Set FS and String library
        $this->filesystem = $filesystem;
        $this->str = $str;

        // Set required data for generator to use
        $this->widgetName = $this->str->studly($data['name']);
        $this->addon = $data['addon'];

        // Set up addon path, generator path, and stub path
        $this->init();

        $addonSetupArray = require $this->addonPath . 'addon.setup.php';
        $this->namespace = $addonSetupArray['namespace'];
        $this->addonName = $addonSetupArray['name'];
    }

    private function init()
    {
        $this->initCommon();
        $this->widgetsPath = $this->addonPath . 'widgets/';

        // If the addon doesn't have a widgets directory, create it
        if (! $this->filesystem->isDir($this->widgetsPath)) {
            $this->filesystem->mkDir($this->widgetsPath, false);
        }

        // Get stub path
        $this->stubPath = $this->generatorPath . '/stubs/';
    }

    public function build()
    {
        $widgetStub = $this->filesystem->read($this->stub('widget.php'));

        $widgetStub = $this->write('widget_name', $this->widgetName, $widgetStub);
        $widgetStub = $this->write('addon', $this->addon, $widgetStub);
        $widgetStub = $this->write('namespace', $this->namespace, $widgetStub);
        $widgetStub = $this->write('addon_name', $this->addonName, $widgetStub);

        $this->putFile($this->widgetName . '.php', $widgetStub, 'widgets');

        if (ee('Addon')->get($this->addon)->isInstalled()) {
            // Update the dashboard widgets and prolets
            $addon = ee('pro:Addon')->get($this->addon);
            $addon->updateDashboardWidgets();
        }
    }
}
