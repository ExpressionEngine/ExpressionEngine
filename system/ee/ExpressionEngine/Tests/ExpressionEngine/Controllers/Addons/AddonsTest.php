<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Addons {

    /**
     * Raised when the add-ons controller denies the request.
     */
    class AddonsShowErrorException extends \RuntimeException
    {
    }

    if (! function_exists(__NAMESPACE__ . '\show_error')) {
        /**
         * Replace the controller error response with an exception.
         *
         * @param string $message
         * @param int $status
         * @return void
         *
         * @throws AddonsShowErrorException
         */
        function show_error($message, $status = 500)
        {
            throw new AddonsShowErrorException((string) $message, $status);
        }
    }
}

namespace ExpressionEngine\Tests\Controllers\Addons {

use ExpressionEngine\Controller\Addons\Addons;
use ExpressionEngine\Controller\Addons\AddonsShowErrorException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

class AddonsTest extends TestCase
{
    /**
     * Load the controller base class used by the add-ons controller.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        require_once(APPPATH . 'core/Controller.php');
    }

    /**
     * Clear EE container mocks between tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    /**
     * Verify add-on removal rejects non-POST requests before setup work.
     *
     * @return void
     *
     * @throws \ReflectionException
     */
    public function testRemoveRequiresPostRequest()
    {
        $this->mockAdminAddonPermission(true);
        $this->mockRequestMethod('GET');
        $this->mockNoInstallerSetup();

        $this->expectException(AddonsShowErrorException::class);
        $this->expectExceptionMessage('unauthorized_access');
        $this->expectExceptionCode(403);

        $this->makeController()->remove('placeholder');
    }

    /**
     * Create the controller without running its CP constructor.
     *
     * @return Addons
     *
     * @throws \ReflectionException
     */
    private function makeController()
    {
        return (new ReflectionClass(Addons::class))->newInstanceWithoutConstructor();
    }

    /**
     * Mock the permission service response for add-on administration.
     *
     * @param bool $allowed
     * @return void
     */
    private function mockAdminAddonPermission($allowed)
    {
        $permission = $this->getMockBuilder(stdClass::class)
            ->addMethods(['can'])
            ->getMock();
        $permission->method('can')->willReturn($allowed);

        ee()->setMock('Permission', $permission);
    }

    /**
     * Mock the request method seen by the controller guard.
     *
     * @param string $method
     * @return void
     */
    private function mockRequestMethod($method)
    {
        $request = $this->getMockBuilder(stdClass::class)
            ->addMethods(['method'])
            ->getMock();
        $request->expects($this->any())
            ->method('method')
            ->willReturn($method);

        ee()->setMock('Request', $request);
    }

    /**
     * Assert denied requests do not start installer setup.
     *
     * @return void
     */
    private function mockNoInstallerSetup()
    {
        $load = $this->getMockBuilder(stdClass::class)
            ->addMethods(['library'])
            ->getMock();
        $load->expects($this->never())->method('library');

        ee()->setMock('load', $load);
    }

    /**
     * Verify the controller exposes only the expected public routes.
     *
     * @return void
     */
    public function testRoutableMethods()
    {
        $controller_methods = array();

        foreach (get_class_methods('ExpressionEngine\Controller\Addons\Addons') as $method) {
            $method = strtolower($method);
            if (strncmp($method, '_', 1) != 0) {
                $controller_methods[] = $method;
            }
        }

        sort($controller_methods);

        $this->assertEquals(array('confirm', 'index', 'install', 'manual', 'remove', 'settings', 'update'), $controller_methods);
    }
}

}
