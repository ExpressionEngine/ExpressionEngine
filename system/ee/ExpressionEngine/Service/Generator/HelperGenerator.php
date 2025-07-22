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

class HelperGenerator extends AbstractGenerator
{

    protected $description;
    protected $author;
    protected $addonType;
    protected $helpersPath;

    public function __construct(Filesystem $filesystem, Str $str, array $data)
    {
        // Set FS and String library
        $this->filesystem = $filesystem;
        $this->str = $str;

        $this->addon = $data['addon_name'];
        $this->addonType = $data['addon_type'];
        $this->description = $data['description'];
        $this->author = $data['author'];


        // Set up addon path, generator path, and stub path
        $this->init();
    }

    private function init()
    {
        $this->initCommon();
        $this->helpersPath = $this->addonPath . '/helpers/';

        // Get stub path
        $this->stubPath = $this->generatorPath . '/stubs/';

        if (!$this->filesystem->isDir($this->helpersPath)) {
            $this->filesystem->mkDir($this->helpersPath);
        }
    }

    public function build()
    {
        // Create main helper file
        $this->createMainHelperFile();

        return true;
    }

    /**
     * Create the main helper file
     */
    private function createMainHelperFile()
    {
        $helperStub = $this->filesystem->read($this->stub('helper.php'));
        $helperStub = $this->write('addon_name', $this->addon, $helperStub);
        $helperStub = $this->write('author', $this->author, $helperStub);
        $helperStub = $this->write('description', $this->description, $helperStub);

        $this->putFile($this->addon . '_helper.php', $helperStub, 'helpers');
    }


} 