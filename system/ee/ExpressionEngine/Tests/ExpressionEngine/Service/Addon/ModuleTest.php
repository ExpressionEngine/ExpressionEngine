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
use ExpressionEngine\Service\Addon\Module;

class __stub_mod extends Module
{
    protected $route_namespace = 'Test\\Namespace';

    public function forceBuildObject(string $domain, bool $action = false)
    {
        return $this->buildObject($domain, $action);
    }
}

class ModuleTest extends TestCase
{
    /**
     * @return Module
     */
    public function testModuleInstanceOfController(): Module
    {
        $controller = new __stub_mod();
        $this->assertInstanceOf('ExpressionEngine\Service\Addon\Controllers\Controller', $controller);
        return $controller;
    }

    /**
     * @depends testModuleInstanceOfController
     * @param Module $controller
     */
    public function testExceptionThrownOnFailedRequest(Module $controller)
    {
        $this->expectException('ExpressionEngine\Service\Addon\Exceptions\ControllerException');
        $controller->badMethod();
    }

    public function testBuildObjectReturnsString()
    {
        $controller = new __stub_mod;
        $controller->setAddonName('test-addon');
        $this->assertEquals('\\Test\\Namespace\\Module\\Actions\\Foo', $controller->forceBuildObject('foo', true));
        $this->assertEquals('\\Test\\Namespace\\Module\\Tags\\Foo', $controller->forceBuildObject('foo', false));
    }
}
