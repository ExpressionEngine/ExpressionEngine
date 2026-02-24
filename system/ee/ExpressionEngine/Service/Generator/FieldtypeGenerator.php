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

class FieldtypeGenerator extends AbstractGenerator
{
    protected $version;

    public function __construct(Filesystem $filesystem, Str $str, array $data)
    {
        // Set FS
        $this->filesystem = $filesystem;
        $this->str = $str;

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
        $this->initCommon();

        // Get stub path
        $this->stubPath = $this->generatorPath . '/stubs/MakeAddon/';
    }

    public function build()
    {
        $ftStub = $this->filesystem->read($this->stub('ft.slug.php'));

        $ftStub = $this->write('slug_uc', ucfirst($this->str->snakecase($this->name)), $ftStub);
        $ftStub = $this->write('version', $this->version, $ftStub);
        $ftStub = $this->write('name', ucfirst($this->name), $ftStub);

        $this->putFile('ft.' . $this->str->snakecase($this->name) . '.php', $ftStub);

        // Add fieldtype to addon.setup
        $this->updateAddonSetup();
    }

    private function updateAddonSetup()
    {
        $addonSetupPath = $this->addonPath . 'addon.setup.php';
        // load addon.setup file
        $addonSetupFile = $this->filesystem->read($addonSetupPath);

        // get array into memory
        $addonSetupArray = require $addonSetupPath;

        "'fieldtypes'        => [{{fieldtypes}}],";

        $ftSetup = $this->filesystem->read($this->stub('AddonSetup/fieldtype_setup.php'));
        $ftSetup = $this->write('fieldtype_slug', $this->name, $ftSetup);
        $ftSetup = $this->write('fieldtype_name', ucfirst($this->name), $ftSetup);
        $ftSetup = $this->write('fieldtype_compatibility', 'text', $ftSetup);

        // The add-on setup has the fieldtypes key
        if (! array_key_exists('fieldtypes', $addonSetupArray)) {
            // Add an empty FT array
            $pattern = '/(,)([^,]+)$/';
            $addonSetupFile = preg_replace($pattern, ",\n    'fieldtypes'        => [{{fieldtypes}}], $2", $addonSetupFile);
            $addonSetupFile = $this->write('fieldtypes', $ftSetup, $addonSetupFile);
            $this->filesystem->write($addonSetupPath, $addonSetupFile, true);

            return true;
        }

        // Add it to the existing fieldtypes array
        $pattern = "/(fieldtypes)([^=]+)(=>\s)(array\(|\[)([^\S]*)([\s])([\s\S]*)$/";
        $addonSetupFile = preg_replace($pattern, "$1$2$3$4\n$ftSetup$5$6$7", $addonSetupFile);
        $this->filesystem->write($this->addonPath . 'addon.setup.php', $addonSetupFile, true);
    }
}
