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

class TemplateGeneratorGenerator
{
    public $name;
    public $addon;
    protected $filesystem;
    protected $str;
    protected $addonPath;
    protected $stubPath;
    protected $templateGeneratorName;
    protected $templateGeneratorsPath;
    protected $namespace;

    public function __construct(Filesystem $filesystem, Str $str, array $data)
    {
        // Set FS and String library
        $this->filesystem = $filesystem;
        $this->str = $str;

        // // Set required data for generator to use
        $this->templateGeneratorName = $this->str->studly($data['name']);
        $this->addon = $this->str->snakecase($data['addon']);

        // Set up addon path, generator path, and stub path
        $this->init();

        $addonSetupArray = require $this->addonPath . 'addon.setup.php';
        $this->namespace = $addonSetupArray['namespace'];
    }

    private function init()
    {
        $this->stubPath = SYSPATH . 'ee/ExpressionEngine/Service/Generator/stubs/MakeAddon/TemplateGenerators/';
        $this->addonPath = SYSPATH . 'user/addons/' . $this->addon . '/';
        $this->templateGeneratorsPath = SYSPATH . 'user/addons/' . $this->addon . '/';

        // Make sure the addon exists
        if (! ee('Addon')->get($this->addon)) {
            throw new \Exception(lang('cli_error_the_specified_addon_does_not_exist'), 1);
        }
    }

    public function build()
    {
        // Build the template generator
        $templateGeneratorStub = $this->filesystem->read($this->stub('TemplateGenerator.php'));
        $templateGeneratorStub = $this->write('namespace', ucfirst($this->namespace), $templateGeneratorStub);
        $templateGeneratorStub = $this->write('TemplateGeneratorName', $this->templateGeneratorName, $templateGeneratorStub);

        $this->putFile('TemplateGenerators/' . $this->templateGeneratorName . '.php', $templateGeneratorStub);

        // Build the stub template
        $generatedTemplateStub = $this->filesystem->read($this->stub('GeneratedTemplate.php'));
        $generatedTemplateStub = $this->write('addon_shortname', $this->addon, $generatedTemplateStub);

        $this->putFile('stubs/' . $this->templateGeneratorName . '/index.php', $generatedTemplateStub);

        // add the template generator to the addon.setup.php file
        $this->addTemplateGeneratorToAddonSetup();
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

    private function addTemplateGeneratorToAddonSetup()
    {
        try {
            $addonSetupFile = $this->filesystem->read($this->addonPath . 'addon.setup.php');
        } catch (FilesystemException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }

        $addonSetupArray = require $this->addonPath . 'addon.setup.php';

        // Parse TemplateGenerator Stub
        $templateGeneratorNameString = "        '" . $this->templateGeneratorName . "',";
        $templateGeneratorAddonSetupStub = $this->filesystem->read($this->stub('template_generator.addon.php'));
        $templateGeneratorAddonSetupStub = $this->write('generator_name', $templateGeneratorNameString, $templateGeneratorAddonSetupStub);

        // The add-on setup has the templateGenerators array
        if (array_key_exists('templateGenerators', $addonSetupArray)) {
            $pattern = "/(templateGenerators)([^=]+)(=>\s)(array\(|\[)([^\S]*)([\s])([\s\S]*)$/";
            $addonSetupFile = preg_replace($pattern, "$1$2$3$4\n$templateGeneratorNameString$5$6$7", $addonSetupFile);
            $this->filesystem->write($this->addonPath . 'addon.setup.php', $addonSetupFile, true);
        } else {
            // The add-on setup does not have the templateGenerators array
            $pattern = '/(,)([^,]+)$/';
            $addonSetupFile = preg_replace($pattern, ",\n    $templateGeneratorAddonSetupStub $2", $addonSetupFile);
            $this->filesystem->write($this->addonPath . 'addon.setup.php', $addonSetupFile, true);
        }
    }
}
