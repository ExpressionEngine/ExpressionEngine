<?php

namespace ExpressionEngine\Cli\Generator\Services;

use ExpressionEngine\Library\Filesystem\Filesystem;

class ModelGeneratorService
{
    public $name;
    public $addon;
    protected $filesystem;
    protected $generatorPath;
    protected $addonPath;
    protected $stubPath;

    public function __construct(Filesystem $filesystem, array $data)
    {
        $this->name = $data['name'];
        $this->filesystem = $filesystem;
        $this->className = $this->studly($data['name']);
        $this->addon = $data['addon'];
        $this->namespace = $this->studly($data['addon']);

        $this->init();
    }

    private function init()
    {
        $this->generatorPath = SYSPATH . 'ee/ExpressionEngine/Cli/Generator';
        $this->addonPath = SYSPATH . 'user/addons/' . $this->addon . '/';
        $this->modelPath = SYSPATH . 'user/addons/' . $this->addon . '/Models/';

        // Get stub path
        $this->stubPath = $this->generatorPath . '/stubs' . '/';

        if (! $this->filesystem->isDir($this->modelPath)) {
            $this->filesystem->mkDir($this->modelPath);
        }
    }

    public function build()
    {
        $modelStub = $this->filesystem->read($this->stub('model.php'));
        $modelStub = $this->write('namespace', $this->namespace, $modelStub);
        $modelStub = $this->write('class', $this->className, $modelStub);

        $this->putFile($this->className . '.php', $modelStub);

        $this->addModelToAddonSetup();
    }

    private function addModelToAddonSetup()
    {
        try {
            $addonSetup = $this->filesystem->read($this->addonPath . 'addon.setup.php');
        } catch (FilesystemException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }

        $modelsStub = $this->filesystem->read($this->stub('addon_model.php'));
        $modelsStub = $this->write('namespace', $this->namespace, $modelsStub);
        $modelsStub = $this->write('class', $this->className, $modelsStub);

        if ($this->string_contains($addonSetup, "'models'") || $this->string_contains($addonSetup, '"models"')) {
            $modelsStub = $this->filesystem->read($this->stub('model.addon.php'));
            $modelsStub = $this->write('model_data', $modelsStub, $modelsStub);

            preg_match('(\]\;|\)\;)', $addonSetup, $matches);

            if (! empty($matches)) {
                $last = array_values(array_slice($matches, -1))[0];

                $addonSetup = $this->write($last, $modelsStub . "\n\n" . $last, $addonSetup);
            }
        } else {
            $stringToReplace = $this->string_contains($addonSetup, "'models'")
                                ? '"models"'
                                : "'models'";

            // TODO: Find models array and add $modelstring to the array
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
        if ($path) {
            $path = trim($path, '/') . '/';
        } else {
            $path = '';
        }

        if (!$this->filesystem->exists($this->modelPath . $path . $name)) {
            $this->filesystem->write($this->modelPath . $path . $name, $contents);
        }
    }

    public function slug($word)
    {
        $word = strtolower($word);

        return str_replace(['-', ' '], '_', $word);
    }

    public function studly($word)
    {
        $word = mb_convert_case($word, MB_CASE_TITLE);

        return  str_replace(['-', '_', ' '], '', $word);
    }

    public function string_contains($textToSearch, $word)
    {
        if (strpos($textToSearch, $word) !== false) {
            return true;
        }

        return false;
    }
}
