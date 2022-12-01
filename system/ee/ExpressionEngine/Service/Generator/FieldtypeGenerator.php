<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Generator;

use ExpressionEngine\Library\Filesystem\Filesystem;

class FieldtypeGenerator
{
    public $name;
    public $addon;
    protected $filesystem;
    protected $generatorPath;
    protected $addonPath;
    protected $stubPath;

    public function __construct(Filesystem $filesystem, array $data)
    {
        // Set FS
        $this->filesystem = $filesystem;

        // Set required data for generator to use
        $this->addon = $data['addon'];
        $this->name = $data['name'];

        // Set up addon path, generator path, and stub path
        $this->init();

        // Get version from addon setup file
        $addonSetupArray = require $this->addonPath . 'addon.setup.php';
        $this->version = $addonSetupArray['version'];
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
        $this->stubPath = $this->generatorPath . '/stubs/MakeAddon/';
    }

    public function build()
    {
        $ftStub = $this->filesystem->read($this->stub('ft.slug.php'));

        $ftStub = $this->write('slug_uc', ucfirst($this->addon), $ftStub);
        $ftStub = $this->write('version', $this->version, $ftStub);
        $ftStub = $this->write('name', $this->name, $ftStub);

        $this->putFile('ft.' . $this->addon . '.php', $ftStub);

        // TODO:
        // Add FT to addon.setup
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
}
