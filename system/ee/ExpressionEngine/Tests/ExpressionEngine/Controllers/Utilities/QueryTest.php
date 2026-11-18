<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Controllers\Utilities {

use ExpressionEngine\Controller\Utilities\Query;
use ExpressionEngine\Controller\Utilities\UtilitiesShowErrorException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

require_once __DIR__ . '/UtilitiesTestHelper.php';

class QueryTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once(APPPATH . 'core/Controller.php');
    }

    /**
     * Clear service mocks between tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testRoutableMethods()
    {
        $controller_methods = array();

        foreach (get_class_methods('ExpressionEngine\Controller\Utilities\Query') as $method) {
            $method = strtolower($method);
            if (strncmp($method, '_', 1) != 0) {
                $controller_methods[] = $method;
            }
        }

        sort($controller_methods);

        $this->assertEquals(array('index', 'runquery'), $controller_methods);
    }

    /**
     * Reject members without SQL Manager access at the routable handler.
     *
     * @return void
     */
    public function testRunQueryRequiresSqlManagerPermission()
    {
        $this->mockPermission(false, false);

        $this->expectException(UtilitiesShowErrorException::class);
        $this->expectExceptionMessage('unauthorized_access');
        $this->expectExceptionCode(403);

        $this->makeController()->runQuery();
    }

    /**
     * Reject unsigned POST queries from non-Super Admins.
     *
     * @return void
     */
    public function testRunQueryRequiresSuperAdminForPostQueries()
    {
        $this->mockPermission(true, false);

        $input = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('post'))
            ->getMock();
        $input->method('post')->with('thequery')->willReturn('SELECT 1');
        ee()->setMock('input', $input);

        $this->expectException(UtilitiesShowErrorException::class);
        $this->expectExceptionMessage('unauthorized_access');
        $this->expectExceptionCode(403);

        $this->makeController()->runQuery();
    }

    /**
     * Create a query controller without running the Control Panel bootstrap.
     *
     * @return Query
     */
    private function makeController()
    {
        return (new ReflectionClass(Query::class))->newInstanceWithoutConstructor();
    }

    /**
     * Mock SQL Manager and Super Admin permissions.
     *
     * @param bool $hasSqlManagerAccess
     * @param bool $isSuperAdmin
     * @return void
     */
    private function mockPermission($hasSqlManagerAccess, $isSuperAdmin)
    {
        $permission = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('can', 'isSuperAdmin'))
            ->getMock();
        $permission->method('can')->willReturn($hasSqlManagerAccess);
        $permission->method('isSuperAdmin')->willReturn($isSuperAdmin);
        ee()->setMock('Permission', $permission);
    }
}

}
