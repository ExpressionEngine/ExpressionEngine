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

class __stub_mcp extends Mcp
{
    public function forceParseParams(array $params)
    {
        $this->parseParams($params);
    }

    public function forceBuildObject(string $domain)
    {
        return $this->buildObject($domain);
    }

    public function forceProcess(string $domain)
    {
        return $this->process($domain);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAction()
    {
        return $this->action;
    }
}

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

    /**
     * @return Mcp
     */
    public function testParseParams(): Mcp
    {
        $controller = new __stub_mcp;
        $controller->forceParseParams(['action', 34]);
        $this->assertEquals(34, $controller->getId());
        $this->assertEquals('action', $controller->getAction());
        return $controller;
    }

    public function testParseParamsWithActionOnly(): Mcp
    {
        $controller = new __stub_mcp;
        $controller->forceParseParams(['action']);
        $this->assertEquals('', $controller->getId());
        $this->assertEquals('action', $controller->getAction());
        return $controller;
    }

    public function testParamsIdDetection(): Mcp
    {
        $controller = new __stub_mcp;
        $controller->forceParseParams([500]);
        $this->assertEquals(500, $controller->getId());
        $this->assertEquals('', $controller->getAction());
        return $controller;
    }

    public function testBuildObjectThrowsExceptionOnMissingNamespace(): Mcp
    {
        $this->expectException('ExpressionEngine\Service\Addon\Exceptions\ControllerException');
        $controller = new __stub_mcp;
        $controller->forceBuildObject('foo');
        return $controller;
    }

    public function testBuildObjectWithActionDeciphersActionAndId(): Mcp
    {
        $controller = new __stub_mcp;
        $controller->setRouteNamespace('TestAddon');
        $controller->forceParseParams(['my-action']);
        $this->assertEquals('\\TestAddon\\Mcp\\TestDomain', $controller->forceBuildObject('test-domain'));
        return $controller;
    }

    public function testBuildObjectCreatesBasicNamespaceString(): Mcp
    {
        $controller = new __stub_mcp;
        $controller->setRouteNamespace('TestAddon');
        $this->assertEquals('\\TestAddon\\Mcp\\TestDomain', $controller->forceBuildObject('test-domain'));
        return $controller;
    }

    /**
     * @depends testBuildObjectCreatesBasicNamespaceString
     * @param __stub_mcp $controller
     * @return Mcp
     */
    public function testNonExistentNamespaceReturnsNullOnBuild(__stub_mcp $controller): Mcp
    {
        $this->assertNull($controller->forceProcess('my-domain'));
        return $controller;
    }
}
