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

class CommandGenerator extends AbstractGenerator
{
    protected $className;
    protected $signature;
    protected $addonSetup;
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

        // Set up addon path, generator path, and stub path
        $this->init();

        $this->addonSetup = $this->getAddonSetup();
        $this->className = $studlyName;
        $this->commandNamespace = $this->addonSetup['namespace'] . '\\Commands';
        $this->fullClass = $this->commandNamespace . '\\Command' . $studlyName;
        $this->signature = $data['signature'];
        $this->description = $data['description'];
    }

    private function init()
    {
        $this->initCommon();
        $this->commandsPath = $this->addonPath . '/Commands/';

        // Get stub path
        $this->stubPath = $this->generatorPath . '/stubs/';

        if (! $this->filesystem->isDir($this->commandsPath)) {
            $this->filesystem->mkDir($this->commandsPath);
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

        $this->putFile('Command' . $this->className . '.php', $commandStub, 'Commands');

        $this->addCommandToAddonSetup();

        return true;
    }

    private function addCommandToAddonSetup()
    {
        try {
            $addonSetupFile = $this->filesystem->read($this->addonPath . 'addon.setup.php');
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
}
