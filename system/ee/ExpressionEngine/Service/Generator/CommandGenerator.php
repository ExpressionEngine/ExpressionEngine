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
use ExpressionEngine\Library\Filesystem\FilesystemException;
use ExpressionEngine\Library\String\Str;

class CommandGenerator
{
    protected $filesystem;
    protected $str;
    protected $name;
    protected $className;
    protected $signature;
    protected $addon;
    protected $addonSetup;
    protected $generatorPath;
    protected $addonPath;
    protected $stubPath;
    protected $commandNamespace;
    protected $fullClass;
    protected $description;
    protected $commandsPath;

    public function __construct(Filesystem $filesystem, Str $str, array $data)
    {
        // Set FS and String library
        $this->filesystem = $filesystem;
        $this->str = $str;

        $studlyName = $this->str->studly($data['name']);

        $this->name = $data['name'];
        $this->addon = $data['addon'];
        $this->addonPath = PATH_THIRD . $this->addon;

        // Make sure the add-on exists before we load the add-on setup
        $this->verifyAddonExists();

        $this->addonSetup = $this->getAddonSetup();
        $this->className = $studlyName;
        $this->commandNamespace = $this->addonSetup['namespace'] . '\\Commands';
        $this->fullClass = $this->commandNamespace . '\\Command' . $studlyName;
        $this->signature = $data['signature'];
        $this->description = $data['description'];

        $this->init();
    }

    private function init()
    {
        $this->generatorPath = SYSPATH . 'ee/ExpressionEngine/Service/Generator';
        $this->addonPath = $this->addonPath . '/';
        $this->commandsPath = $this->addonPath . '/Commands/';

        // Get stub path
        $this->stubPath = $this->generatorPath . '/stubs/';

        if (! $this->filesystem->isDir($this->commandsPath)) {
            $this->filesystem->mkDir($this->commandsPath);
        }
    }

    private function verifyAddonExists()
    {
        if (is_null(ee('Addon')->get($this->addon))) {
            throw new \Exception("Add-on does not exists: " . $this->addon, 1);
        }
    }

    public function getAddonSetup()
    {
        $addonSetup = include $this->addonPath . '/addon.setup.php';

        return $addonSetup;
    }

    public function build()
    {
        $commandStub = $this->filesystem->read($this->stub('command.php'));
        $commandStub = $this->write('name', $this->name, $commandStub);
        $commandStub = $this->write('namespace', $this->commandNamespace, $commandStub);
        $commandStub = $this->write('class', $this->className, $commandStub);
        $commandStub = $this->write('signature', $this->signature, $commandStub);
        $commandStub = $this->write('description', $this->description, $commandStub);

        $this->putFile('Command' . $this->className . '.php', $commandStub);

        $this->addCommandToAddonSetup();

        return true;
    }

    private function addCommandToAddonSetup()
    {
        try {
            $addonSetupFile = $this->filesystem->read($this->addonPath . 'addon.setup.php');
        } catch (FilesystemException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }

        $addonSetupArray = require $this->addonPath . 'addon.setup.php';

        // Parse Command Stubs
        $commandString = "        '{$this->signature}' => {$this->fullClass}::class,";
        $commandStub = $this->filesystem->read($this->stub('command.addon.php'));
        $commandStub = $this->write('command_data', $commandString, $commandStub);

        // The add-on setup has the commands array
        if (array_key_exists('commands', $addonSetupArray)) {
            $pattern = "/(commands)([^=]+)(=>\s)(array\(|\[)([^\S]*)([\s])([\s\S]*)$/";
            $addonSetupFile = preg_replace($pattern, "$1$2$3$4\n$commandString$5$6$7", $addonSetupFile);
            $this->filesystem->write($this->addonPath . 'addon.setup.php', $addonSetupFile, true);
        } else { // The add-on setup does not have the commands array
            $pattern = '/(,)([^,]+)$/';
            $addonSetupFile = preg_replace($pattern, ",\n    $commandStub $2", $addonSetupFile);
            $this->filesystem->write($this->addonPath . 'addon.setup.php', $addonSetupFile, true);
        }
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

        if (!$this->filesystem->exists($this->commandsPath . $path . $name)) {
            $this->filesystem->write($this->commandsPath . $path . $name, $contents);
        }
    }
}
