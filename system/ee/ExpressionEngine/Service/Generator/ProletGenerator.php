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

class ProletGenerator
{
    public $name;
    public $addon;
    protected $filesystem;
    protected $str;
    protected $generatorPath;
    protected $addonPath;
    protected $stubPath;
    protected $generateIcon;

    public function __construct(Filesystem $filesystem, Str $str, array $data)
    {
        // Set FS and String library
        $this->filesystem = $filesystem;
        $this->str = $str;

        // Set required data for generator to use
        $this->name = $data['name'];
        $this->addon = $data['addon'];
        $this->generateIcon = $data['generate-icon'];

        // Set up addon path, generator path, and stub path
        $this->init();
    }

    private function init()
    {
        $this->generatorPath = SYSPATH . 'ee/ExpressionEngine/Service/Generator';
        $this->addonPath = SYSPATH . 'user/addons/' . $this->addon . '/';

        // This will copy the default icon into our addon, if the add-on doesnt already have an icon
        if ($this->generateIcon && !$this->addonHasIcon()) {
            $defaultIcon = PATH_THEMES . 'asset/img/default-addon-icon.svg';

            $this->filesystem->copy($defaultIcon, $this->addonPath . 'icon.svg');
        }

        // Make sure the addon exists
        if (! ee('Addon')->get($this->addon)) {
            throw new \Exception(lang('cli_error_the_specified_addon_does_not_exist'), 1);
        } elseif (! file_exists($this->addonPath . 'mod.' . $this->addon . '.php')) {
            throw new \Exception(lang('command_make_prolet_error_addon_must_have_module'), 1);
        } elseif (! $this->addonHasIcon()) {
            throw new \Exception(lang('command_make_prolet_error_addon_must_have_icon'), 1);
        }

        // Get stub path
        $this->stubPath = $this->generatorPath . '/stubs/';
    }

    public function build()
    {
        $proletStub = $this->filesystem->read($this->stub('prolet.php'));
        $proletStub = $this->write('addon', ucfirst($this->addon), $proletStub);
        $proletStub = $this->write('name', $this->name, $proletStub);

        $this->putFile('pro.' . $this->addon . '.php', $proletStub);

        if (ee('Addon')->get($this->addon)->isInstalled()) {
            // Update prolets in EE
            $addon = ee('pro:Addon')->get($this->addon);
            $addon->updateProlets();
        }
    }

    private function addonHasIcon()
    {
        $addon = ee('Addon')->get($this->addon);

        return ! (stripos($addon->getIconUrl(), 'default-addon-icon.svg') !== false);
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
