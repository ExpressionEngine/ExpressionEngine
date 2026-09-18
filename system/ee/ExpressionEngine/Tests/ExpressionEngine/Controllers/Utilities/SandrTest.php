<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Controllers\Utilities;

use ExpressionEngine\Controller\Utilities\UtilitiesShowErrorException;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/UtilitiesTestHelper.php';

class SandrTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once(APPPATH . 'core/Controller.php');
    }

    public function testRoutableMethods()
    {
        $controller_methods = array();

        foreach (get_class_methods('ExpressionEngine\Controller\Utilities\Sandr') as $method) {
            $method = strtolower($method);
            if (strncmp($method, '_', 1) != 0) {
                $controller_methods[] = $method;
            }
        }

        sort($controller_methods);

        $this->assertEquals(array('index'), $controller_methods);
    }

    /**
     * Check Super Admin access before loading or processing the form.
     *
     * @return void
     */
    public function testIndexChecksSuperAdminAccessBeforeLoadingForm()
    {
        ee()->resetMocks();

        $permission = $this->getMockBuilder('stdClass')
            ->addMethods(array('can', 'isSuperAdmin'))
            ->getMock();
        $permission->method('can')->willReturn(true);
        $permission->expects($this->once())
            ->method('isSuperAdmin')
            ->willReturn(false);
        ee()->setMock('Permission', $permission);

        $controller = (new \ReflectionClass('ExpressionEngine\\Controller\\Utilities\\Sandr'))
            ->newInstanceWithoutConstructor();

        $this->expectException(UtilitiesShowErrorException::class);
        $this->expectExceptionMessage('unauthorized_access');
        $this->expectExceptionCode(403);

        try {
            $controller->index();
        } finally {
            ee()->resetMocks();
        }
    }
}
