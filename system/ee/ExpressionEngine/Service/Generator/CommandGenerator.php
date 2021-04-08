<?php

namespace ExpressionEngine\Service\Generator;

use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Library\Filesystem\FilesystemException;

class CommandGenerator
{
    protected $filesystem;
    protected $name;
    protected $className;
    protected $signature;
    protected $addon;
    protected $addonClass;
    protected $generatorPath;
    protected $addonPath;
    protected $stubPath;

    public function __construct(Filesystem $filesystem, array $data)
    {
        $studlyAddon = $this->studly($data['addon']);
        $studlyName = $this->studly($data['addon']);

        $this->filesystem  = $filesystem;
        $this->name = $data['name'];
        $this->addon = $data['addon'];
        $this->addonClass = $studlyAddon;
        $this->className = $studlyName;
        $this->fullClass = $this->addonClass . '\\Commands\\Command' . $studlyName;
        $this->signature = $data['signature'];
        $this->description = $data['description'];

        $this->init();
    }

    private function init()
    {
        $this->generatorPath = SYSPATH . 'ee/ExpressionEngine/Service/Generator';
        $this->addonPath = SYSPATH . 'user/addons/' . $this->addon . '/';
        $this->commandsPath = SYSPATH . 'user/addons/' . $this->addon . '/Commands/';

        // Get stub path
        $this->stubPath = $this->generatorPath . '/stubs' . '/';

        if (! $this->filesystem->isDir($this->commandsPath)) {
            $this->filesystem->mkDir($this->commandsPath);
        }
    }

    public function build()
    {
        $commandStub = $this->filesystem->read($this->stub('command.php'));
        $commandStub = $this->write('name', $this->name, $commandStub);
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

        // The addon setup has the commands array
        if (array_key_exists('commands', $addonSetupArray)) {
            $pattern = "/(commands)([^=]+)(=>\s)(array\(|\[)([^\S]*)([\s])([\s\S]*)$/";
            $addonSetupFile = preg_replace($pattern, "$1$2$3$4\n$commandString$5$6$7", $addonSetupFile);
            $this->filesystem->write($this->addonPath . 'addon.setup.php', $addonSetupFile, true);
        } else { // The addon setup does not have the commands array

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

    public function studly($word)
    {
        $word = mb_convert_case($word, MB_CASE_TITLE);

        return  str_replace(['-', '_', ' '], '', $word);
    }

    public function string_contains($textToSearch, $word)
    {
        return (strpos($textToSearch, $word) !== false);
    }
}
