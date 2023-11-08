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

class CpRouteGenerator
{
    public $name;
    public $addon;
    protected $filesystem;
    protected $str;
    protected $generatorPath;
    protected $addonPath;
    protected $stubPath;
    protected $namespace;
    protected $route;
    protected $route_uc;
    protected $view;


    public function __construct(Filesystem $filesystem, Str $str, array $data)
    {
        // Set FS && string libraries
        $this->filesystem = $filesystem;
        $this->str = $str;

        // Set required data for generator to use
        $this->addon = $data['addon'];
        $this->route = $this->str->snakecase($data['name']);
        $this->route_uc = $this->str->studly($data['name']);
        $this->view = $this->str->studly($data['name']);

        $this->name = $data['name'];

        // Set up addon path, generator path, and stub path
        $this->init();

        // Get namespace from addon setup file
        $addonSetupArray = require $this->addonPath . 'addon.setup.php';
        $this->namespace = $addonSetupArray['namespace'];
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
        // Create CP route
        $cpRouteStub = $this->filesystem->read($this->stub('Mcp/Route.php'));
        $cpRouteStub = $this->write('route', $this->route, $cpRouteStub);
        $cpRouteStub = $this->write('route_uc', $this->route_uc, $cpRouteStub);
        $cpRouteStub = $this->write('view', $this->view, $cpRouteStub);
        $cpRouteStub = $this->write('namespace', $this->namespace, $cpRouteStub);
        $this->putFile('ControlPanel/Routes/' . $this->route_uc . '.php', $cpRouteStub);

        // Add Mcp view
        $cpViewStub = $this->filesystem->read($this->stub('views/View.php'));
        $this->putFile('views/' . $this->view . '.php', $cpViewStub);

        // Create mcp file if it doesnt exist:
        $this->generateMcp();

        // Update settings exist  in addon.setup.php
        $this->updateAddonSetup();

        // update $has_cp_backend in upd
        $this->updateUpdFile();
    }

    private function updateAddonSetup()
    {
        // load addon.setup file
        $addonSetupFile = $this->filesystem->read($this->addonPath . 'addon.setup.php');

        // get array into memory
        $addonSetupArray = require $this->addonPath . 'addon.setup.php';

        // The add-on setup has the settings_exist key
        if (array_key_exists('settings_exist', $addonSetupArray)) {
            $pattern = "/(settings_exist)([^=]+)(=>\s)(false|true)/";
            $addonSetupFile = preg_replace($pattern, "$1$2$3true", $addonSetupFile);
            $this->filesystem->write($this->addonPath . 'addon.setup.php', $addonSetupFile, true);
        } else {
            // The add-on setup does not have the settings_exist key. Add it
            $pattern = '/(,)([^,]+)$/';
            $addonSetupFile = preg_replace($pattern, ",\n    'settings_exist'    => true, $2", $addonSetupFile);
            $this->filesystem->write($this->addonPath . 'addon.setup.php', $addonSetupFile, true);
        }
    }

    private function updateUpdFile()
    {
        // Read the UPD file
        $updFilePath = $this->addonPath . 'upd.' . $this->addon . '.php';
        $updFile = $this->filesystem->read($updFilePath);

        // Replace the has_cp_backend variable
        $pattern = "/(has_cp_backend)([\s]*=[\s]*['\"])([^']*)(['\"];)/";
        $updFile = preg_replace($pattern, "$1$2y$4", $updFile);
        $this->filesystem->write($updFilePath, $updFile, true);
    }

    private function generateMcp()
    {
        $addon = ee('Addon')->get($this->addon);

        //  Only do this if there is no mcp file
        if ($addon->hasControlPanel()) {
            return;
        }

        // Create mcp file if it doesnt exist:
        $mcpStub = $this->filesystem->read($this->stub('Mcp/mcp.slug.php'));
        $mcpStub = $this->write('slug_uc', ucfirst($this->addon), $mcpStub);
        $mcpStub = $this->write('slug', $this->addon, $mcpStub);
        $this->putFile('mcp.' . $this->addon . '.php', $mcpStub);
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
