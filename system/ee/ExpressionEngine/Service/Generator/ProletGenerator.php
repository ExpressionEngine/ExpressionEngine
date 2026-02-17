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

class ProletGenerator extends AbstractGenerator
{
    protected $generateIcon;

    public $requiredComponentFiles = [
        'mod',
    ];

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
        $this->initCommon();

        // This will copy the default icon into our addon, if the add-on doesnt already have an icon
        if ($this->generateIcon && !$this->addonHasIcon()) {
            $defaultIcon = PATH_THEMES . 'asset/img/default-addon-icon.svg';

            $this->filesystem->copy($defaultIcon, $this->addonPath . 'icon.svg');
        }

        // Make sure the addon has a module
        if (! $this->addonHasIcon()) {
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
}
