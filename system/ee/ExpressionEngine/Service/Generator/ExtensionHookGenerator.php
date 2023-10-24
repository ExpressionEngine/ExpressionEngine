<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Generator;

use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Library\String\Str;
use ExpressionEngine\Service\Generator\Enums\Hooks;

class ExtensionHookGenerator
{
    public $name;
    public $addon;
    protected $filesystem;
    protected $str;
    protected $generatorPath;
    protected $addonPath;
    protected $stubPath;
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
        $this->generatorPath = SYSPATH . 'ee/ExpressionEngine/Service/Generator';
        $this->addonPath = SYSPATH . 'user/addons/' . $this->addon . '/';

        // Make sure the addon exists
        if (! ee('Addon')->get($this->addon)) {
            throw new \Exception(lang('cli_error_the_specified_addon_does_not_exist'), 1);
        }

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

    private function stub($file)
    {
        return $this->stubPath . $file;
    }

    private function write($key, $value, $file)
    {
        return str_replace('{{' . $key . '}}', $value, $file);
    }

    private function putFile($name, $contents, $path = null)
    {
        if ($path) {
            $path = trim($path, '/') . '/';
        } else {
            $path = '';
        }

        if (!$this->filesystem->exists($this->addonPath . $path . $name)) {
            $this->filesystem->write($this->addonPath . $path . $name, $contents);
        }
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
