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
        $this->filesystem  = $filesystem;
        $this->name = $data['name'];
        $this->addon = $data['addon'];
        $this->addonClass = $this->studly($data['addon']);
        $this->className = $this->studly($data['name']);
        $this->fullClass = $this->addonClass . '\\Commands\\' . $this->studly($data['name']);
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

        $this->putFile($this->studly('Command' . $this->className) . '.php', $commandStub);

        $this->addCommandToAddonSetup();

        return true;
    }

    private function addCommandToAddonSetup()
    {
        try {
            $addonSetup = $this->filesystem->read($this->addonPath . 'addon.setup.php');
        } catch (FilesystemException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }

        $commandString = "    '{$this->signature}' => {$this->fullClass}::class,";
        $useCommandString = "use {$this->fullClass};";

        $this->filesystem->findAndReplace($this->addonPath . 'addon.setup.php', "<?php", "<?php\n\n{$useCommandString}\n\n");

        if ($this->string_contains($addonSetup, "'commands'") || $this->string_contains($addonSetup, '"commands"')) {
            $commandStub = $this->filesystem->read($this->stub('command.addon.php'));
            $commandStub = $this->write('command_data', $commandString, $commandStub);

            preg_match('(\]\;|\)\;)', $addonSetup, $matches);

            if (! empty($matches)) {
                $last = array_values(array_slice($matches, -1))[0];

                $addonSetup = $this->write($last, $commandStub . "\n\n" . $last, $addonSetup);
            } else {
                $stringToReplace = $this->string_contains($addonSetup, "'commands'")
                                ? '"commands"'
                                : "'commands'";

                // TODO: Find command array and add $commandString to the array
                preg_match('/(\'|\")commands(\'|\")(\s+)=>(\s+)(\[|array\()/', $addonSetup, $matches);

                if (! empty($matches) && isset($matches[1])) {
                    $addonSetup = $this->write($matches[1], $matches[1] . "\n\n" . $commandString, $addonSetup);
                }
            }
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
