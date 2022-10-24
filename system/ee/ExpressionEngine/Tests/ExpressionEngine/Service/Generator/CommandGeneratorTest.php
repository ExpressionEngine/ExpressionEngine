<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\Generator;

use ExpressionEngine\Service\Generator\CommandGenerator;
use Mockery;
use PHPUnit\Framework\TestCase;

class CommandGeneratorTest extends TestCase
{
    public function setUp(): void
    {
        $this->filesystem = Mockery::mock('ExpressionEngine\Library\Filesystem\Filesystem');

        // Populate with sample data
        $data = [];

        $this->commandGenerator = new CommandGenerator($this->filesystem, $data);
    }

    public function tearDown(): void
    {
        $this->filesystem = null;
        $this->commandGenerator = null;

        Mockery::close();
    }

    /** @test */
    public function it_generates_a_command()
    {
    }
}
