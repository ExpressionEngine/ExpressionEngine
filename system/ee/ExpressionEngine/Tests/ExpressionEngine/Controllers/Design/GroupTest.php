<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Design {

    /**
     * Raised when the Template Group controller denies a request.
     */
    class GroupShowErrorException extends \RuntimeException
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
         * @throws GroupShowErrorException
         */
        function show_error($message, $status = 500)
        {
            throw new GroupShowErrorException((string) $message, $status);
        }
    }
}

namespace ExpressionEngine\Tests\Controllers\Design {

use ExpressionEngine\Controller\Design\Group;
use ExpressionEngine\Controller\Design\GroupShowErrorException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use stdClass;

/**
 * Provide POST input values to the controller under test.
 */
class GroupPostInputStub
{
    /**
     * Return a POST value using the legacy input contract.
     *
     * @param string $key
     * @return mixed
     */
    public function post($key)
    {
        return array_key_exists($key, $_POST) ? $_POST[$key] : false;
    }
}

class GroupTest extends TestCase
{
    /**
     * Load the controller base class used by the Template Group controller.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        require_once(APPPATH . 'core/Controller.php');
    }

    /**
     * Reset request input before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $_POST = array();
        ee()->setMock('input', new GroupPostInputStub());
    }

    /**
     * Clear request input and EE container mocks between tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $_POST = array();
        ee()->resetMocks();
    }

    /**
     * Verify Template Group removal rejects non-POST requests before querying.
     *
     * @return void
     *
     * @throws \ReflectionException
     */
    public function testRemoveRequiresPostRequest()
    {
        $this->mockDeletePermission(true);
        $this->mockRequestMethod('GET');
        $this->mockNoModelLookup();

        $this->expectException(GroupShowErrorException::class);
        $this->expectExceptionMessage('unauthorized_access');
        $this->expectExceptionCode(403);

        $this->makeController()->remove();
    }

    /**
     * Verify Template Group removal rejects missing or malformed identifiers.
     *
     * @param array $post
     * @return void
     *
     * @dataProvider invalidIdentifierProvider
     * @throws \ReflectionException
     */
    public function testRemoveRejectsInvalidIdentifiers($post)
    {
        $_POST = $post;

        $this->mockDeletePermission(true);
        $this->mockRequestMethod('POST');
        $this->mockNoModelLookup();

        $this->expectException(GroupShowErrorException::class);
        $this->expectExceptionMessage('group_not_found');

        $this->makeController()->remove();
    }

    /**
     * Provide missing and malformed Template Group identifiers.
     *
     * @return array
     */
    public static function invalidIdentifierProvider()
    {
        return array(
            'missing identifier' => array(array()),
            'empty group name' => array(array('group_name' => '')),
            'array group name' => array(array('group_name' => array('invalid'))),
            'invalid group id without name' => array(array('group_id' => 'invalid')),
        );
    }

    /**
     * Verify an unknown posted name cannot delete another Template Group.
     *
     * @return void
     *
     * @throws \ReflectionException
     */
    public function testRemoveRejectsUnknownGroupName()
    {
        $_POST = array('group_name' => 'unknown_group');

        $this->mockDeletePermission(true);
        $this->mockRequestMethod('POST');
        $this->mockConfig();

        $collection = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('first'))
            ->getMock();
        $collection->expects($this->once())->method('first')->willReturn(null);

        $query = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('filter', 'all'))
            ->getMock();
        $expectedFilters = array(
            array('group_name', 'unknown_group'),
            array('site_id', 1),
        );
        $query->expects($this->exactly(2))
            ->method('filter')
            ->willReturnCallback(function ($field, $value) use (&$expectedFilters, $query) {
                $this->assertSame(array_shift($expectedFilters), array($field, $value));

                return $query;
            });
        $query->expects($this->once())->method('all')->willReturn($collection);

        $model = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('get'))
            ->getMock();
        $model->expects($this->once())
            ->method('get')
            ->with('TemplateGroup')
            ->willReturn($query);
        ee()->setMock('Model', $model);

        $this->expectException(GroupShowErrorException::class);
        $this->expectExceptionMessage('group_not_found');

        $this->makeController()->remove();
    }

    /**
     * Verify a valid posted identifier deletes only the selected Template Group.
     *
     * @param array $post
     * @param array $identifierFilter
     * @return void
     *
     * @dataProvider validIdentifierProvider
     * @throws \ReflectionException
     */
    public function testRemoveDeletesSelectedGroup($post, $identifierFilter)
    {
        $_POST = $post;

        $this->mockDeletePermission(true);
        $this->mockRequestMethod('POST');
        $this->mockConfig();

        $group = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('delete'))
            ->getMock();
        $group->group_id = 42;
        $group->group_name = 'selected_group';
        $group->expects($this->once())->method('delete');

        $collection = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('first', 'count'))
            ->getMock();
        $collection->expects($this->once())->method('first')->willReturn($group);
        $collection->expects($this->never())->method('count');

        $filters = array();
        $query = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('filter', 'all'))
            ->getMock();
        $query->expects($this->exactly(2))
            ->method('filter')
            ->willReturnCallback(function ($field, $value) use (&$filters, $query) {
                $filters[] = array($field, $value);

                return $query;
            });
        $query->expects($this->once())->method('all')->willReturn($collection);

        $model = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('get'))
            ->getMock();
        $model->expects($this->once())
            ->method('get')
            ->with('TemplateGroup')
            ->willReturn($query);
        ee()->setMock('Model', $model);

        $alert = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('makeInline', 'asSuccess', 'withTitle', 'addToBody', 'defer'))
            ->getMock();
        $alert->expects($this->once())->method('makeInline')->with('template-group')->willReturnSelf();
        $alert->expects($this->once())->method('asSuccess')->willReturnSelf();
        $alert->expects($this->once())->method('withTitle')->willReturnSelf();
        $alert->expects($this->once())->method('addToBody')->willReturnSelf();
        $alert->expects($this->once())->method('defer');
        ee()->setMock('CP/Alert', $alert);

        $url = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('make'))
            ->getMock();
        $url->expects($this->once())->method('make')->with('design')->willReturn('design-url');
        ee()->setMock('CP/URL', $url);

        $functions = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('redirect'))
            ->getMock();
        $functions->expects($this->once())->method('redirect')->with('design-url');
        ee()->setMock('functions', $functions);

        $controller = $this->makeController();
        $assignedGroups = new ReflectionProperty($controller, 'assigned_template_groups');
        $assignedGroups->setAccessible(true);
        $assignedGroups->setValue($controller, array(42));

        $controller->remove();

        $this->assertSame(
            array(
                $identifierFilter,
                array('site_id', 1),
            ),
            $filters
        );
    }

    /**
     * Provide valid Template Group identifiers and their expected filters.
     *
     * @return array
     */
    public static function validIdentifierProvider()
    {
        return array(
            'group name' => array(
                array('group_name' => 'selected_group'),
                array('group_name', 'selected_group'),
            ),
            'group id' => array(
                array('group_id' => '42'),
                array('group_id', '42'),
            ),
        );
    }

    /**
     * Create the controller without running its CP constructor.
     *
     * @return Group
     *
     * @throws \ReflectionException
     */
    private function makeController()
    {
        return (new ReflectionClass(Group::class))->newInstanceWithoutConstructor();
    }

    /**
     * Mock the Template Group deletion permission response.
     *
     * @param bool $allowed
     * @return void
     */
    private function mockDeletePermission($allowed)
    {
        $permission = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('can'))
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
            ->addMethods(array('method'))
            ->getMock();
        $request->expects($this->any())
            ->method('method')
            ->willReturn($method);

        ee()->setMock('Request', $request);
    }

    /**
     * Assert rejected requests do not query the model service.
     *
     * @return void
     */
    private function mockNoModelLookup()
    {
        $model = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('get'))
            ->getMock();
        $model->expects($this->never())->method('get');

        ee()->setMock('Model', $model);
    }

    /**
     * Mock the configuration used when selecting and deleting a group.
     *
     * @return void
     */
    private function mockConfig()
    {
        $config = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('item'))
            ->getMock();
        $config->method('item')->willReturnMap(array(
            array('site_id', 1),
            array('save_tmpl_files', 'n'),
        ));

        ee()->setMock('config', $config);
    }

    /**
     * Verify the controller exposes only the expected public routes.
     *
     * @return void
     */
    public function testRoutableMethods()
    {
        $controller_methods = array();

        foreach (get_class_methods('ExpressionEngine\Controller\Design\Group') as $method) {
            $method = strtolower($method);
            if (strncmp($method, '_', 1) != 0) {
                $controller_methods[] = $method;
            }
        }

        sort($controller_methods);

        $this->assertEquals(array('create', 'edit', 'remove'), $controller_methods);
    }
}

}
