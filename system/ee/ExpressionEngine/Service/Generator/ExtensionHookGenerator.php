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
use ExpressionEngine\Service\Generator\Enums\Hooks;

class ExtensionHookGenerator extends AbstractGenerator
{
    protected $ExtensionHookName;
    protected $namespace;

    public function __construct(Filesystem $filesystem, Str $str, array $data)
    {
        // Set FS
        $this->filesystem = $filesystem;
        $this->str = $str;

        // Set required data for generator to use
        $this->name = $this->str->snakecase($data['name']);
        $this->ExtensionHookName = $this->str->studly($this->name);
        $this->addon = $this->str->snakecase($data['addon']);

        // Set up addon path, generator path, and stub path
        $this->init();

        $addonSetupArray = require $this->addonPath . 'addon.setup.php';
        $this->namespace = $addonSetupArray['namespace'];
    }

    private function init()
    {
        $this->initCommon();

        // Get stub path
        $this->stubPath = $this->generatorPath . '/stubs/MakeAddon/Extension/';
    }

    public function build()
    {
        $hookData = Hooks::getByKey(trim(strtoupper($this->name)));

        // If we didnt get a real hook, set up a default
        if ($hookData === false) {
            $hookData = [
                'name' => $this->name,
                'params' => '',
                'library' => ''
            ];
        }

        $extensionHookStub = $this->filesystem->read($this->stub('ExtensionStub.php'));
        $extensionHookStub = $this->write('namespace', ucfirst($this->namespace), $extensionHookStub);
        $extensionHookStub = $this->write('hook_name_studly', $this->ExtensionHookName, $extensionHookStub);
        $extensionHookStub = $this->write('hook_methods', $hookData['params'], $extensionHookStub);

        $this->putFile('Extensions/' . $this->ExtensionHookName . '.php', $extensionHookStub);

        // Generate Ext file if necessary
        $this->generateExtension();

        $this->makeMigration();
    }

    private function generateExtension()
    {
        $addon = ee('Addon')->get($this->addon);

        //  Only do this if there is no extension
        if ($addon->hasExtension()) {
            return;
        }

        $data = ['addon' => $this->addon];

        $service = ee('ExtensionGenerator', $data);
        $service->build();
    }

    private function makeMigration()
    {
        $migration_name = 'CreateExtHook' . $this->ExtensionHookName . 'ForAddon' . $this->str->studly($this->addon);

        $data = [
            'classname' => $migration_name,
            'ext_method' => $this->name,
            'ext_hook' => $this->name,
            'addon' => $this->addon,
        ];

        $migration = ee('Migration')->generateMigration($migration_name, $this->addon);
        ee('Migration', $migration)->writeMigrationFileFromTemplate('CreateExtensionHook', $data);
    }
}
