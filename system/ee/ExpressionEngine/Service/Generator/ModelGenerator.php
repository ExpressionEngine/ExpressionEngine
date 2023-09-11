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

class ModelGenerator
{
    public $name;
    public $addon;
    protected $filesystem;
    protected $str;
    protected $generatorPath;
    protected $addonPath;
    protected $stubPath;
    protected $className;
    protected $namespace;
    protected $modelPath;

    public function __construct(Filesystem $filesystem, Str $str, array $data)
    {
        // Set FS and String library
        $this->filesystem = $filesystem;
        $this->str = $str;

        $this->name = $data['name'];
        $this->className = $this->str->studly($data['name']);
        $this->addon = $data['addon'];

        $this->verifyAddonExists();

        $this->init();

        $addonSetupArray = require $this->addonPath . 'addon.setup.php';
        $this->namespace = $addonSetupArray['namespace'];
    }

    private function verifyAddonExists()
    {
        if (is_null(ee('Addon')->get($this->addon))) {
            throw new \Exception("Add-on does not exists: " . $this->addon, 1);
        }
    }

    private function init()
    {
        $this->generatorPath = SYSPATH . 'ee/ExpressionEngine/Service/Generator';
        $this->addonPath = SYSPATH . 'user/addons/' . $this->addon . '/';
        $this->modelPath = SYSPATH . 'user/addons/' . $this->addon . '/Model/';

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
        $modelStub = $this->write('addon', strtolower($this->addon), $modelStub);

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
}
