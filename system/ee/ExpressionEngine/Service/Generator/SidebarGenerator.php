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

class SidebarGenerator extends AbstractGenerator
{
    protected $namespace;

    public function __construct(Filesystem $filesystem, Str $str, array $data)
    {
        // Set FS && string libraries
        $this->filesystem = $filesystem;
        $this->str = $str;

        // Set required data for generator to use
        $this->addon = $data['addon'];

        // Set up addon path, generator path, and stub path
        $this->init();

        // Get namespace from addon setup file
        $addonSetupArray = require $this->addonPath . 'addon.setup.php';
        $this->namespace = $addonSetupArray['namespace'];
    }

    private function init()
    {
        $this->initCommon();

        // Get stub path
        $this->stubPath = $this->generatorPath . '/stubs/MakeAddon/Mcp/';
    }

    public function build()
    {
        // Create add-on control panel sidebar
        $sidebarStub = $this->filesystem->read($this->stub('Sidebar.php'));
        $sidebarStub = $this->write('namespace', $this->namespace, $sidebarStub);
        $this->putFile('ControlPanel/Sidebar.php', $sidebarStub);
    }
}
