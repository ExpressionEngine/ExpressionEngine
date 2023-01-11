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

use ExpressionEngine\Service\Generator\AddonGenerator;
use ExpressionEngine\Library\String\Str;
use Mockery;
use PHPUnit\Framework\TestCase;

class AddonGeneratorTest extends TestCase
{
    public function setUp(): void
    {
        $this->filesystem = Mockery::mock('ExpressionEngine\Library\Filesystem\Filesystem');

        // Populate with sample data
        $str = new Str();
        $data = [
            'name' => 'test',
            'description' => 'test desc',
            'version' => '1.0.0',
            'author' => 'PacketTide',
            'author_url' => 'https://packettide.com'
        ];

        $this->addonGenerator = new AddonGenerator($this->filesystem, $str, $data);
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
