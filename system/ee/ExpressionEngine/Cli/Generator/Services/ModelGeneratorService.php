<?php

namespace ExpressionEngine\Cli\Generator\Services;

use ExpressionEngine\Library\Filesystem\Filesystem;

class ModelGeneratorService
{
    public $name;
    public $addon;
    protected $generatorPath;
    protected $addonPath;
    protected $stubPath;

    public function __construct(array $data)
    {
        ee()->load->helper('string');

        $this->name = $data['name'];
        $this->className = studly($data['name']);
        $this->addon = $data['addon'];
        $this->namespace = studly($data['addon']);

        $this->init();
    }

    private function init()
    {
        $this->generatorPath = SYSPATH . 'ee/ExpressionEngine/Cli/Generator';
        $this->addonPath = SYSPATH . 'user/addons/' . $this->addon . '/';
        $this->modelPath = SYSPATH . 'user/addons/' . $this->addon . '/Models/';
        $filesystem = new Filesystem();

        // Get stub path
        $this->stubPath = $this->generatorPath . '/stubs' . '/';

        if (! $filesystem->isDir($this->modelPath)) {
            $filesystem->mkDir($this->modelPath);
        }
    }

    public function build()
    {
        $filesystem = new Filesystem();

        $modelStub = $filesystem->read($this->stub('model.php'));
        $modelStub = $this->write('namespace', $this->namespace, $modelStub);
        $modelStub = $this->write('class', $this->className, $modelStub);

        $this->putFile($this->className . '.php', $modelStub);

        $this->addModelToAddonSetup();
    }

    private function addModelToAddonSetup()
    {
        $filesystem = new Filesystem();

        try {
            $addonSetup = $filesystem->read($this->addonPath . 'addon.setup.php');
        } catch (FilesystemException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }

        $modelsStub = $filesystem->read($this->stub('addon_model.php'));
        $modelsStub = $this->write('namespace', $this->namespace, $modelsStub);
        $modelsStub = $this->write('class', $this->className, $modelsStub);

        if (string_contains($addonSetup, "'models'") || string_contains($addonSetup, '"models"')) {
            $modelsStub = $filesystem->read($this->stub('model.addon.php'));
            $modelsStub = $this->write('model_data', $commandString, $modelsStub);

            preg_match('(\]\;|\)\;)', $addonSetup, $matches);

            if (! empty($matches)) {
                $last = array_values(array_slice($matches, -1))[0];

                $addonSetup = $this->write($last, $modelsStub . "\n\n" . $last, $addonSetup);
            }
        } else {
            $stringToReplace = string_contains($addonSetup, "'models'")
                                ? '"models"'
                                : "'models'";

            // TODO: Find command array and add $modelstring to the array
            preg_match('/(\'|\")models(\'|\")(\s+)=>(\s+)(\[|array\()/', $addonSetup, $matches);

            if (! empty($matches) && isset($matches[1])) {
                $addonSetup = $this->write($matches[1], $matches[1] . "\n\n" . $modelsStub, $addonSetup);
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
        $filesystem = new Filesystem();

        if ($path) {
            $path = trim($path, '/') . '/';
        } else {
            $path = '';
        }

        if (!$filesystem->exists($this->modelPath . $path . $name)) {
            $filesystem->write($this->modelPath . $path . $name, $contents);
        }
    }
}
