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

class JumpsGenerator extends AbstractGenerator
{
    public function __construct(Filesystem $filesystem, Str $str, array $data)
    {
        // Set FS and String library
        $this->filesystem = $filesystem;
        $this->str = $str;

        // Set required data for generator to use
        $this->addon = $data['addon'];

        // Set up addon path, generator path, and stub path
        $this->init();
    }

    private function init()
    {
        $this->initCommon();

        // Get stub path
        $this->stubPath = $this->generatorPath . '/stubs/';
    }

    public function build()
    {
        $jumpStub = $this->filesystem->read($this->stub('jumps.php'));
        $jumpStub = $this->write('Addon', ucfirst($this->addon), $jumpStub);
        $jumpStub = $this->write('addon', $this->addon, $jumpStub);

        $this->putFile('jump.' . $this->addon . '.php', $jumpStub);

        // Clear all jump caches
        ee('CP/JumpMenu')->clearAllCaches();
    }
}
