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

class ActionGenerator extends AbstractGenerator
{
    protected $ActionName;
    protected $csrf_exempt;
    protected $actionsPath;
    protected $namespace;

    public $requiredComponentFiles = [
        'mod',
    ];

    public function __construct(Filesystem $filesystem, Str $str, array $data)
    {
        // Set FS and String library
        $this->filesystem = $filesystem;
        $this->str = $str;

        // Set required data for generator to use
        $this->ActionName = $this->str->studly($data['name']);
        $this->addon = $this->str->snakecase($data['addon']);
        $this->csrf_exempt = $data['csrf_exempt'] ? 'true' : 'false';

        // Set up addon path, generator path, and stub path
        $this->init();

        $addonSetupArray = require $this->addonPath . 'addon.setup.php';
        $this->namespace = $addonSetupArray['namespace'];
    }

    private function init()
    {
        $this->initCommon();
        $this->actionsPath = $this->addonPath;
        // Get stub path
        $this->stubPath = $this->generatorPath . '/stubs/MakeAddon/';
    }

    public function build()
    {
        $actionStub = $this->filesystem->read($this->stub('Actions/ActionStub.php'));
        $actionStub = $this->write('namespace', ucfirst($this->namespace), $actionStub);
        $actionStub = $this->write('ActionName', $this->ActionName, $actionStub);

        $this->putFile('Actions/' . $this->ActionName . '.php', $actionStub);

        $this->makeMigration();
    }

    private function makeMigration()
    {
        $migration_name = 'CreateAction' . $this->ActionName . 'ForAddon' . $this->addon;

        $data = [
            'action' => $this->ActionName,
            'addon' => ucfirst($this->addon),
            'csrf_exempt' => $this->csrf_exempt,
        ];

        $migration = ee('Migration')->generateMigration($migration_name, $this->addon);
        ee('Migration', $migration)->writeMigrationFileFromTemplate('CreateAction', $data);
    }
}
