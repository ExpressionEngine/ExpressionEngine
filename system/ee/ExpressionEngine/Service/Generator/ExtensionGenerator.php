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

class ExtensionGenerator extends AbstractGenerator
{
    protected $ExtensionHookName;
    protected $namespace;

    public function __construct(Filesystem $filesystem, Str $str, array $data)
    {
        // Set FS
        $this->filesystem = $filesystem;
        $this->str = $str;

        // Set required data for generator to use
        $this->addon = $this->str->snakecase($data['addon']);

        // Set up addon path, generator path, and stub path
        $this->init();
    }

    private function init()
    {
        $this->initCommon();

        // Get stub path
        $this->stubPath = $this->generatorPath . '/stubs/MakeAddon/Extension/';
    }

    public function build()
    {
        $extStub = $this->filesystem->read($this->stub('ext.slug.php'));
        $extStub = $this->write('slug_uc', ucfirst($this->addon), $extStub);
        $extStub = $this->write('slug', $this->addon, $extStub);

        $this->putFile('ext.' . $this->addon . '.php', $extStub);
    }
}
