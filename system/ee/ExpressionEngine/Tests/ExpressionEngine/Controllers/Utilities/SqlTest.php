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

class SqlTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once(APPPATH . 'core/Controller.php');
        require_once(SYSPATH . 'ee/ExpressionEngine/Controller/Utilities/Sql.php');
    }

    /**
     * Clear mocked services after each test.
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

        foreach (get_class_methods('ExpressionEngine\Controller\Utilities\Sql') as $method) {
            $method = strtolower($method);
            if (strncmp($method, '_', 1) != 0) {
                $controller_methods[] = $method;
            }
        }

        sort($controller_methods);

        $this->assertEquals(array('index', 'opresults'), $controller_methods);
    }

    /**
     * Deny direct operation requests without SQL Manager access.
     *
     * @return void
     */
    public function testOperationResultsRequireSqlManagerAccess()
    {
        $permission = $this->getMockBuilder('stdClass')->addMethods(array('can'))->getMock();
        $permission->method('can')->with('access_sql_manager')->willReturn(false);
        ee()->setMock('Permission', $permission);

        $this->expectException(UtilitiesShowErrorException::class);
        $this->expectExceptionCode(403);

        $this->controller()->opResults();
    }

    /**
     * Reject destructive actions before they reach the database.
     *
     * @return void
     */
    public function testOperationResultsRejectDestructiveAction()
    {
        $this->mockOperation('DROP', array('exp_known'));

        $this->expectException(UtilitiesShowErrorException::class);
        $this->expectExceptionCode(403);

        $this->controller()->opResults();
    }

    /**
     * Reject table names outside the database inventory.
     *
     * @return void
     */
    public function testOperationResultsRejectUnknownTable()
    {
        $this->mockOperation('REPAIR', array('exp_other'));

        $this->expectException(UtilitiesShowErrorException::class);
        $this->expectExceptionCode(403);

        $this->controller()->opResults();
    }

    /**
     * Reject another application's table even when it exists in the database.
     *
     * @return void
     */
    public function testOperationResultsRejectTableOutsideEePrefix()
    {
        $this->mockOperation('REPAIR', array('other_known'));

        $this->expectException(UtilitiesShowErrorException::class);
        $this->expectExceptionCode(403);

        $this->controller()->opResults();
    }

    /**
     * Allow a supported action on an enumerated table.
     *
     * @return void
     */
    public function testOperationResultsAcceptKnownTableAndAction()
    {
        $this->mockOperation('REPAIR', array('exp_known'), true);

        $this->expectException(SqlQueryReached::class);

        $this->controller()->opResults();
    }

    /**
     * Set up a submitted table operation and record database execution.
     *
     * @param string $action
     * @param array $tables
     * @param bool $expect_query
     * @return void
     */
    private function mockOperation($action, array $tables, $expect_query = false)
    {
        $permission = $this->getMockBuilder('stdClass')->addMethods(array('can'))->getMock();
        $permission->method('can')->with('access_sql_manager')->willReturn(true);
        ee()->setMock('Permission', $permission);

        $input = $this->getMockBuilder('stdClass')->addMethods(array('post'))->getMock();
        $input->method('post')->willReturnCallback(function ($key) use ($action, $tables) {
            return $key === 'bulk_action' ? $action : $tables;
        });
        ee()->setMock('input', $input);

        $db = $this->getMockBuilder('stdClass')
            ->addMethods(array('list_tables', 'escape_identifiers', 'query'))
            ->getMock();
        $db->dbprefix = 'exp_';
        $db->method('list_tables')->willReturn(array('exp_known', 'other_known'));
        $db->method('escape_identifiers')->willReturn('`exp_known`');
        $db->expects($expect_query ? $this->once() : $this->never())
            ->method('query')
            ->willThrowException(new SqlQueryReached());
        ee()->setMock('db', $db);
    }

    /**
     * Create a controller without running the Control Panel bootstrap.
     *
     * @return \ExpressionEngine\Controller\Utilities\Sql
     */
    private function controller()
    {
        return (new \ReflectionClass('ExpressionEngine\\Controller\\Utilities\\Sql'))
            ->newInstanceWithoutConstructor();
    }
}

class SqlQueryReached extends \RuntimeException
{
}
