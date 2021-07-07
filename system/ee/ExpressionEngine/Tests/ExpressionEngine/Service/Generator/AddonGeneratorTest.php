<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\Generator;

use ExpressionEngine\Service\Generator\AddonGenerator;
use Mockery;
use PHPUnit\Framework\TestCase;

class AddonGeneratorTest extends TestCase
{
    public function setUp(): void
    {
        $this->filesystem = Mockery::mock('ExpressionEngine\Library\Filesystem\Filesystem');

        // Populate with sample data
        $data = [];

        $this->addonGenerator = new AddonGenerator($this->filesystem, $data);
    }

    public function tearDown(): void
    {
        $this->filesystem = null;
        $this->addonGenerator = null;

        Mockery::close();
    }

    /** @test */
    public function it_generates_an_addon()
    {
    }
}
