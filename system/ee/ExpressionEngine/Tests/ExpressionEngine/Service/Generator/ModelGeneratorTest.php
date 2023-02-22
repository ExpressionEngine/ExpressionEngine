<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\Generator;

use ExpressionEngine\Service\Generator\ModelGenerator;
use ExpressionEngine\Library\String\Str;
use Mockery;
use PHPUnit\Framework\TestCase;

class ModelGeneratorTest extends TestCase
{
    public $filesystem;
    public $modelGenerator;

    public function setUp(): void
    {
        $this->filesystem = Mockery::mock('ExpressionEngine\Library\Filesystem\Filesystem');
    }

    public function tearDown(): void
    {
        $this->filesystem = null;
        $this->modelGenerator = null;

        Mockery::close();
    }

    /** @test */
    public function it_fails_when_addon_doesnt_exist()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Add-on does not exists: addon_that_doesnt_exist');

        // Populate with sample data
        $str = new Str();
        $data = [
            'name' => 'MyModel',
            'addon' => 'addon_that_doesnt_exist',
        ];

        $this->modelGenerator = new ModelGenerator($this->filesystem, $str, $data);
    }
}
