<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Utilities {

    /**
     * Raised when member import denies a request.
     */
    class MemberImportShowErrorException extends \RuntimeException
    {
    }

    if (! function_exists(__NAMESPACE__ . '\\show_error')) {
        /**
         * Replace the controller error response with an exception.
         *
         * @param string $message
         * @param int $status
         * @return void
         *
         * @throws MemberImportShowErrorException
         */
        function show_error($message, $status = 500)
        {
            throw new MemberImportShowErrorException((string) $message, $status);
        }
    }
}

namespace ExpressionEngine\Tests\Controllers\Utilities {

use ExpressionEngine\Controller\Utilities\MemberImport;
use ExpressionEngine\Controller\Utilities\MemberImportShowErrorException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use stdClass;

class MemberImportTest extends TestCase
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

        foreach (get_class_methods('ExpressionEngine\Controller\Utilities\MemberImport') as $method) {
            $method = strtolower($method);
            if (strncmp($method, '_', 1) != 0) {
                $controller_methods[] = $method;
            }
        }

        sort($controller_methods);

        $this->assertEquals(array('createcustomfields', 'doimport', 'index', 'memberimportconfirm', 'processxml', 'validatexml'), $controller_methods);
    }

    /**
     * Require the dedicated import permission before the form performs any work.
     *
     * @return void
     */
    public function testIndexRequiresDedicatedImportPermission()
    {
        $permission = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('can', 'isSuperAdmin'))
            ->getMock();
        $permission->method('can')->willReturnCallback(function ($permission) {
            return $permission === 'access_utilities';
        });
        $permission->method('isSuperAdmin')->willReturn(false);
        ee()->setMock('Permission', $permission);

        $this->expectException(MemberImportShowErrorException::class);
        $this->expectExceptionCode(403);

        $this->makeController()->index();
    }

    /**
     * Retain the Utilities permission requirement for the import form.
     *
     * @return void
     */
    public function testIndexRequiresUtilitiesPermission()
    {
        $permission = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('can', 'isSuperAdmin'))
            ->getMock();
        $permission->method('can')->willReturnCallback(function ($permission) {
            return $permission === 'access_import';
        });
        $permission->method('isSuperAdmin')->willReturn(false);
        ee()->setMock('Permission', $permission);

        $this->expectException(MemberImportShowErrorException::class);
        $this->expectExceptionCode(403);

        $this->makeController()->index();
    }

    /**
     * Validate every effective role before saving the first imported member.
     *
     * @return void
     */
    public function testAllEffectiveRolesAreAuthorizedBeforeAnyMemberIsSaved()
    {
        $saveCount = 0;
        $member = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('set', 'save'))
            ->getMock();
        $member->member_id = 10;
        $member->method('set')->willReturn($member);
        $member->method('save')->willReturnCallback(function () use (&$saveCount) {
            $saveCount++;
        });

        $model = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('get', 'make'))
            ->getMock();
        $model->method('get')->willReturnCallback(function ($modelName, $id) {
            $role = $this->getMockBuilder(stdClass::class)
                ->addMethods(array('getId'))
                ->getMock();
            $role->is_locked = (int) $id === 1 ? 'y' : 'n';
            $role->method('getId')->willReturn((int) $id);

            $query = $this->getMockBuilder(stdClass::class)
                ->addMethods(array('first'))
                ->getMock();
            $query->method('first')->willReturn($role);

            return $query;
        });
        $model->method('make')->willReturn($member);
        ee()->setMock('Model', $model);

        $input = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('post'))
            ->getMock();
        $input->method('post')->willReturnCallback(function ($key) {
            return $key === 'role_id' ? 5 : null;
        });
        ee()->setMock('input', $input);

        $encrypt = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('generateKey'))
            ->getMock();
        $encrypt->method('generateKey')->willReturn('member-import-test-key');
        ee()->setMock('Encrypt', $encrypt);
        $this->mockSuperAdmin(false);

        $controller = $this->makeController();
        $controller->localize = (object) array('now' => 1);
        $this->setControllerProperty($controller, 'default_fields', array('username' => '', 'role_id' => ''));
        $this->setControllerProperty($controller, 'members', array(
            array('username' => 'first', 'role_id' => 5),
            array('username' => 'second', 'role_id' => 1),
        ));

        try {
            $controller->doImport();
            $this->fail('Expected the locked role to be rejected.');
        } catch (MemberImportShowErrorException $exception) {
            $this->assertSame(403, $exception->getCode());
        }

        $this->assertSame(0, $saveCount);
    }

    /**
     * Look up each normalized role once per preflight, including the default role.
     *
     * @return void
     */
    public function testPreflightLooksUpEachNormalizedRoleOncePerBatch()
    {
        $query = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('first'))
            ->getMock();
        $query->method('first')->willReturn((object) array('is_locked' => 'n'));

        $lookedUpRoleIds = array();
        $model = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('get'))
            ->getMock();
        $model->expects($this->exactly(4))->method('get')
            ->with('Role', $this->isType('int'))
            ->willReturnCallback(function ($modelName, $id) use (&$lookedUpRoleIds, $query) {
                $lookedUpRoleIds[] = $id;

                return $query;
            });
        ee()->setMock('Model', $model);
        $this->mockSuperAdmin(false);

        $controller = $this->makeController();
        $this->setControllerProperty($controller, 'default_fields', array('role_id' => 5));
        $this->setControllerProperty($controller, 'members', array(
            array('role_id' => '5'),
            array('role_id' => 5),
            array('role_id' => '05'),
            array(),
            array('role_id' => 6),
            array('role_id' => '6'),
        ));

        $method = new ReflectionMethod(MemberImport::class, 'authorizeImportRoles');
        $method->setAccessible(true);
        $method->invoke($controller);
        $method->invoke($controller);

        $this->assertSame(array(5, 6, 5, 6), $lookedUpRoleIds);
    }

    /**
     * Reject a locked role for a non-Super Admin.
     *
     * @return void
     */
    public function testLockedRoleIsNotAssignableByNonSuperAdmin()
    {
        $role = (object) array('is_locked' => 'y');
        $this->mockRoleLookup($role);
        $this->mockSuperAdmin(false);

        $this->expectException(MemberImportShowErrorException::class);
        $this->expectExceptionCode(403);

        $this->invokeAuthorizedRole(1);
    }

    /**
     * Allow an unlocked role for a non-Super Admin.
     *
     * @return void
     */
    public function testUnlockedRoleIsAssignableByNonSuperAdmin()
    {
        $role = (object) array('is_locked' => 'n');
        $this->mockRoleLookup($role);
        $this->mockSuperAdmin(false);

        $this->assertSame($role, $this->invokeAuthorizedRole(5));
    }

    /**
     * Allow a Super Admin to assign a locked role.
     *
     * @return void
     */
    public function testLockedRoleIsAssignableBySuperAdmin()
    {
        $role = (object) array('is_locked' => 'y');
        $this->mockRoleLookup($role);
        $this->mockSuperAdmin(true);

        $this->assertSame($role, $this->invokeAuthorizedRole(1));
    }

    /**
     * Create the controller without running its Control Panel constructor.
     *
     * @return MemberImport
     */
    private function makeController()
    {
        return new class extends MemberImport {
            /** @var object */
            public $localize;

            /**
             * Avoid running the Control Panel controller constructor.
             *
             * @return void
             */
            public function __construct()
            {
            }
        };
    }

    /**
     * Set controller state without running its constructor.
     *
     * @param MemberImport $controller
     * @param string $name
     * @param mixed $value
     * @return void
     */
    private function setControllerProperty(MemberImport $controller, $name, $value)
    {
        $property = (new ReflectionClass(MemberImport::class))->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($controller, $value);
    }

    /**
     * Invoke the role authorization boundary.
     *
     * @param int $roleId
     * @return object
     */
    private function invokeAuthorizedRole($roleId)
    {
        $method = new ReflectionMethod(MemberImport::class, 'getAuthorizedRole');
        $method->setAccessible(true);

        return $method->invoke($this->makeController(), $roleId);
    }

    /**
     * Provide a role returned by the model service.
     *
     * @param object|null $role
     * @return void
     */
    private function mockRoleLookup($role)
    {
        $query = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('first'))
            ->getMock();
        $query->method('first')->willReturn($role);

        $model = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('get'))
            ->getMock();
        $model->method('get')->willReturn($query);

        ee()->setMock('Model', $model);
    }

    /**
     * Configure whether the current member is a Super Admin.
     *
     * @param bool $isSuperAdmin
     * @return void
     */
    private function mockSuperAdmin($isSuperAdmin)
    {
        $permission = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('can', 'isSuperAdmin'))
            ->getMock();
        $permission->method('can')->willReturn(true);
        $permission->method('isSuperAdmin')->willReturn($isSuperAdmin);

        ee()->setMock('Permission', $permission);
    }
}

}
