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
use ExpressionEngine\Library\String\Str;

class McpRouteGenerator
{
    public $name;
    public $addon;
    protected $filesystem;
    protected $str;
    protected $generatorPath;
    protected $addonPath;
    protected $stubPath;

    public function __construct(Filesystem $filesystem, Str $str, array $data)
    {
        // Set FS
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
        $this->putFile('Mcp/' . $this->route_uc . '.php', $cpRouteStub);

        // Add Mcp view
        $cpViewStub = $this->filesystem->read($this->stub('views/View.php'));
        $this->putFile('views/' . $this->view . '.php', $cpViewStub);

        // View file:

        // TODO:
        // Update settings exist  in addon.setup.php
        // update $has_cp_backend in upd
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
