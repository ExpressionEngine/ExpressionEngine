<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\Addon\Controllers;

use PHPUnit\Framework\TestCase;
use ExpressionEngine\Service\Addon\Controllers\Controller;

class _addon_controller_mock extends Controller
{
    public function getRoutespaceProperty()
    {
        return $this->route_namespace;
    }

    public function getAddonNameProperty()
    {
        return $this->addon_name;
    }
}

class ControllerTest extends TestCase
{
    public function testControllerHasRouteNamespaceProperty()
    {
        $controller = new _addon_controller_mock();
        $this->assertObjectHasAttribute('route_namespace', $controller);
        $this->assertEquals('', $controller->getRoutespaceProperty());
    }

    public function testControllerHasAddonNameProperty()
    {
        $controller = new _addon_controller_mock();
        $this->assertObjectHasAttribute('addon_name', $controller);
        $this->assertEquals('', $controller->getAddonNameProperty());
    }

    /**
     * @return Controller
     */
    public function testSetAddonNameReturnInstance(): Controller
    {
        $controller = new Controller();
        $this->assertInstanceOf('ExpressionEngine\Service\Addon\Controllers\Controller', $controller->setAddonName('test-addon'));
        return $controller;
    }

    /**
     * @depends testSetAddonNameReturnInstance
     * @param Controller $controller
     * @return void
     */
    public function testSetAddonNameValue(Controller $controller)
    {
        $this->assertEquals('test-addon', $controller->getAddonName());
    }

    /**
     * @return Controller
     */
    public function testSetRouteNamespaceReturnInstance(): Controller
    {
        $controller = new Controller();
        $this->assertInstanceOf('ExpressionEngine\Service\Addon\Controllers\Controller', $controller->setRouteNamespace('test-route-namespace'));
        return $controller;
    }

    /**
     * @depends testSetRouteNamespaceReturnInstance
     * @param Controller $controller
     * @return void
     */
    public function testSetRouteNamespaceValue(Controller $controller)
    {
        $this->assertEquals('test-route-namespace', $controller->getRouteNamespace());
    }
}
