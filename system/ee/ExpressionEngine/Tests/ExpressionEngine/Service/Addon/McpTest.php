<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\Addon;

use PHPUnit\Framework\TestCase;
use ExpressionEngine\Service\Addon\Mcp;

class McpTest extends TestCase
{
    /**
     * @return Mcp
     */
    public function testMcpInstanceOfController(): Mcp
    {
        $controller = new Mcp();
        $this->assertInstanceOf('ExpressionEngine\Service\Addon\Controllers\Controller', $controller);
        return $controller;
    }

    /**
     * @depends testMcpInstanceOfController
     * @param Mcp $controller
     * @return Mcp
     */
    public function testMcpHasActionProperty(Mcp $controller): Mcp
    {
        $this->assertObjectHasAttribute('action', $controller);
        return $controller;
    }

    /**
     * @depends testMcpHasActionProperty
     * @param Mcp $controller
     * @return Mcp
     */
    public function testMcpHasIdProperty(Mcp $controller): Mcp
    {
        $this->assertObjectHasAttribute('id', $controller);
        return $controller;
    }

    /**
     * @depends testMcpHasIdProperty
     * @param Mcp $controller
     * @return void
     * @throws \ExpressionEngine\Service\Addon\Exceptions\ControllerException
     */
    public function testProcessReturnsNullOnBadDomain(Mcp $controller)
    {
        $this->expectException('ExpressionEngine\Service\Addon\Exceptions\ControllerException');
        $controller->route('does-not-exist');
    }
}
