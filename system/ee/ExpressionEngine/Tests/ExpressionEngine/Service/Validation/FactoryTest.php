<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\Validation;

use ExpressionEngine\Service\Validation\Factory;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->factory = new Factory();
    }

    public function testCheck()
    {
        $result = $this->factory->check('required', '');
        $this->assertFalse($result);

        $result = $this->factory->check('email', 'hello@packettide.com');
        $this->assertTrue($result);

        $result = $this->factory->check('integer', 1);
        $this->assertTrue($result);
    }
}

// EOF
