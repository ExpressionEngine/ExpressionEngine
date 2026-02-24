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

class TemplateTagGenerator extends AbstractGenerator
{
    public $requiredComponentFiles = [
        'mod',
    ];

    protected $namespace;
    protected $TagName;
    protected $tag_name;
    protected $tagsPath;

    public function __construct(Filesystem $filesystem, Str $str, array $data)
    {
        // Set FS
        $this->filesystem = $filesystem;
        $this->str = $str;

        // Set required data for generator to use
        $this->TagName = $this->str->studly($data['name']);
        $this->tag_name = $this->str->snakecase($data['name']);
        $this->addon = $this->str->snakecase($data['addon']);

        // Set up addon path, generator path, and stub path
        $this->init();

        $addonSetupArray = require $this->addonPath . 'addon.setup.php';
        $this->namespace = $addonSetupArray['namespace'];
    }

    private function init()
    {
        $this->initCommon();
        $this->tagsPath = $this->addonPath;

        // Get stub path
        $this->stubPath = $this->generatorPath . '/stubs/MakeAddon/';
    }

    public function build()
    {
        $tagStub = $this->filesystem->read($this->stub('Tags/TagStub.php'));
        $tagStub = $this->write('slug', $this->addon, $tagStub);
        $tagStub = $this->write('namespace', ucfirst($this->namespace), $tagStub);
        $tagStub = $this->write('TagName', $this->TagName, $tagStub);
        $tagStub = $this->write('tag_name', $this->tag_name, $tagStub);

        $this->putFile('Tags/' . $this->TagName . '.php', $tagStub);
    }
}
