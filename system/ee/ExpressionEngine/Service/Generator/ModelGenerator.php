<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Generator;

use ExpressionEngine\Library\Filesystem\Filesystem;

class ModelGenerator
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

        $this->init();

        $addonSetupArray = require $this->addonPath . 'addon.setup.php';
        $this->namespace = $addonSetupArray['namespace'];
    }

    private function init()
    {
        $this->generatorPath = SYSPATH . 'ee/ExpressionEngine/Service/Generator';
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
            $addonSetupFile = $this->filesystem->read($this->addonPath . 'addon.setup.php');
        } catch (FilesystemException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
        $addonSetupArray = require $this->addonPath . 'addon.setup.php';

        $modelStub = $this->filesystem->read($this->stub('addon_model.php'));
        $modelStub = $this->write('namespace', $this->namespace, $modelStub);
        $modelStub = $this->write('class', $this->className, $modelStub);

        // The addon setup has the models array
        if (array_key_exists('models', $addonSetupArray)) {
            $pattern = "/(models)([^=]+)(=>\s)(array\(|\[)([^\S]*)([\s])([\s\S]*)$/";
            $addonSetupFile = preg_replace($pattern, "$1$2$3$4\n$modelStub$5$6$7", $addonSetupFile);
            $this->filesystem->write($this->addonPath . 'addon.setup.php', $addonSetupFile, true);
        } else { // The addon setup does not have the models array
            $modelsStub = $this->filesystem->read($this->stub('model.addon.php'));
            $modelsStub = $this->write('model_data', $modelStub, $modelsStub);
            $pattern = '/(,)([^,]+)$/';
            $addonSetupFile = preg_replace($pattern, ",\n    $modelsStub $2", $addonSetupFile);
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

        if (!$this->filesystem->exists($this->modelPath . $path . $name)) {
            $this->filesystem->write($this->modelPath . $path . $name, $contents);
        }
    }

    public function slug($word)
    {
        $word = strtolower($word);

        return str_replace(['-', ' ', '.'], '_', $word);
    }

    public function studly($word)
    {
        $word = mb_convert_case($word, MB_CASE_TITLE);

        return  str_replace(['-', '_', ' ', '.'], '', $word);
    }

    public function string_contains($textToSearch, $word)
    {
        return (strpos($textToSearch, $word) !== false);
    }
}
