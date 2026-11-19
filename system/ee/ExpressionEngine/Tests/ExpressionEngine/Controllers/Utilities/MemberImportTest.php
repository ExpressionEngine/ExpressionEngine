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

use ExpressionEngine\Controller\Utilities\MemberImport;
use ExpressionEngine\Controller\Utilities\UtilitiesShowErrorException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use stdClass;

require_once __DIR__ . '/UtilitiesTestHelper.php';

class MemberImportTest extends TestCase
{
    /**
     * Role assignment relationships and the member methods that report them.
     */
    private const ASSIGNMENTS = array(
        'AssignedModules' => 'getAssignedModules',
        'AssignedChannels' => 'getAssignedChannels',
        'AssignedUploadDestinations' => 'getAssignedUploadDestinations',
        'AssignedStatuses' => 'getAssignedStatuses',
        'AssignedTemplateGroups' => 'getAssignedTemplateGroups',
        'AssignedTemplates' => 'getAssignedTemplates',
    );

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

        $this->expectException(UtilitiesShowErrorException::class);
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

        $this->expectException(UtilitiesShowErrorException::class);
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
        } catch (UtilitiesShowErrorException $exception) {
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
        $query->method('first')->willReturn($this->makeRole(5, 'n'));

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
        \TestReflectionHelper::makeAccessible($method);
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

        $this->expectException(UtilitiesShowErrorException::class);
        $this->expectExceptionCode(403);

        $this->invokeAuthorizedRole(1);
    }

    /**
     * Allow the default member role for a non-Super Admin.
     *
     * @return void
     */
    public function testDefaultMemberRoleIsAssignableByNonSuperAdmin()
    {
        $role = $this->makeRole(5, 'n');
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
     * Reject a locked default member role for a non-Super Admin.
     *
     * @return void
     */
    public function testLockedDefaultMemberRoleIsNotAssignableByNonSuperAdmin()
    {
        $this->mockRoleLookup($this->makeRole(5, 'y'));
        $this->mockSuperAdmin(false);

        $this->expectException(UtilitiesShowErrorException::class);
        $this->expectExceptionCode(403);

        $this->invokeAuthorizedRole(5);
    }

    /**
     * Allow an unlocked role whose permissions and assignments the importer also holds.
     *
     * @return void
     */
    public function testRoleWithinImporterAccessIsAssignableByNonSuperAdmin()
    {
        $role = $this->makeGrantingRole(8, 'n', array('1:can_access_cp'), array('AssignedChannels' => array(2)));
        $this->mockRoleLookup($role);
        $this->mockSuperAdmin(false);
        $this->mockImporter(array('1:can_access_cp', '1:can_access_utilities'), array('AssignedChannels' => array(2, 3)));

        $this->assertSame($role, $this->invokeAuthorizedRole(8));
    }

    /**
     * Reject an unlocked role that holds a permission the importer does not.
     *
     * @return void
     */
    public function testRoleWithPermissionTheImporterLacksIsNotAssignable()
    {
        $this->mockRoleLookup($this->makeGrantingRole(8, 'n', array('1:can_access_cp', '1:can_edit_members')));
        $this->mockSuperAdmin(false);
        $this->mockImporter(array('1:can_access_cp'));

        $this->expectException(UtilitiesShowErrorException::class);
        $this->expectExceptionCode(403);

        $this->invokeAuthorizedRole(8);
    }

    /**
     * Compare permissions site by site.
     *
     * @return void
     */
    public function testRolePermissionsAreComparedForEachSite()
    {
        $this->mockRoleLookup($this->makeGrantingRole(8, 'n', array('2:can_access_cp')));
        $this->mockSuperAdmin(false);
        $this->mockImporter(array('1:can_access_cp'));

        $this->expectException(UtilitiesShowErrorException::class);
        $this->expectExceptionCode(403);

        $this->invokeAuthorizedRole(8);
    }

    /**
     * Reject an unlocked role that has an assignment the importer does not.
     *
     * @dataProvider assignmentProvider
     * @param string $relationship
     * @return void
     */
    public function testRoleWithAssignmentTheImporterLacksIsNotAssignable($relationship)
    {
        $this->mockRoleLookup($this->makeGrantingRole(8, 'n', array(), array($relationship => array(7))));
        $this->mockSuperAdmin(false);
        $this->mockImporter(array(), array($relationship => array(6)));

        $this->expectException(UtilitiesShowErrorException::class);
        $this->expectExceptionCode(403);

        $this->invokeAuthorizedRole(8);
    }

    /**
     * Provide each kind of role assignment.
     *
     * @return array
     */
    public function assignmentProvider()
    {
        $relationships = array();

        foreach (array_keys(self::ASSIGNMENTS) as $relationship) {
            $relationships[$relationship] = array($relationship);
        }

        return $relationships;
    }

    /**
     * Compare a role with the importer's access once, however many members use it.
     *
     * @return void
     */
    public function testRoleComparisonRunsOncePerRole()
    {
        $role = $this->makeGrantingRole(8, 'n', array('1:can_access_cp'));
        $role->AssignedModules = new class {
            /** @var int */
            public $calls = 0;

            public function getIds()
            {
                $this->calls++;

                return array();
            }
        };
        $this->mockRoleLookup($role);
        $this->mockSuperAdmin(false);
        $this->mockImporter(array('1:can_access_cp'));

        $controller = $this->makeController();
        $method = new ReflectionMethod(MemberImport::class, 'getAuthorizedRole');
        \TestReflectionHelper::makeAccessible($method);

        $this->assertSame($role, $method->invoke($controller, 8));
        $this->assertSame($role, $method->invoke($controller, 8));
        $this->assertSame(1, $role->AssignedModules->calls);
    }

    /**
     * Validate every pending role before saving the first imported member.
     *
     * @return void
     */
    public function testPendingRolesAreAuthorizedBeforeAnyMemberIsSaved()
    {
        $saveCount = 0;
        $member = $this->getMockBuilder(stdClass::class)->addMethods(array('set', 'save'))->getMock();
        $member->member_id = 10;
        $member->method('set')->willReturn($member);
        $member->method('save')->willReturnCallback(function () use (&$saveCount) {
            $saveCount++;
        });

        $this->mockImportRequest(array(
            4 => $this->makeGrantingRole(4, 'n'),
            5 => $this->makeRole(5, 'n'),
            8 => $this->makeGrantingRole(8, 'n', array('1:can_edit_members')),
        ), $member);
        $this->mockSuperAdmin(false);
        $this->mockImporter(array('1:can_access_cp'));

        $controller = $this->makeController();
        $controller->localize = (object) array('now' => 1);
        $this->setControllerProperty($controller, 'default_fields', array('username' => '', 'role_id' => '', 'pending_role_id' => ''));
        $this->setControllerProperty($controller, 'members', array(
            array('username' => 'first', 'role_id' => 5),
            array('username' => 'second', 'role_id' => 4, 'pending_role_id' => 8),
        ));

        try {
            $controller->doImport();
            $this->fail('Expected the pending role to be rejected.');
        } catch (UtilitiesShowErrorException $exception) {
            $this->assertSame(403, $exception->getCode());
        }

        $this->assertSame(0, $saveCount);
    }

    /**
     * Save a member with the looked-up role IDs and without the older role alias.
     *
     * @return void
     */
    public function testSavedMemberDataCarriesTheAuthorizedRoles()
    {
        $saved = null;
        $member = $this->getMockBuilder(stdClass::class)->addMethods(array('set', 'save'))->getMock();
        $member->method('set')->willReturnCallback(function ($data) use (&$saved, $member) {
            $saved = $data;

            return $member;
        });
        $member->method('save')->willThrowException(new \RuntimeException('Member write reached.'));

        $this->mockImportRequest(array(
            4 => $this->makeGrantingRole(4, 'n'),
            5 => $this->makeRole(5, 'n'),
            8 => $this->makeGrantingRole(8, 'n', array('1:can_access_cp')),
        ), $member);
        $this->mockSuperAdmin(false);
        $this->mockImporter(array('1:can_access_cp'));

        $controller = $this->makeController();
        $controller->localize = (object) array('now' => 1);
        $this->setControllerProperty($controller, 'default_fields', array('group_id' => '', 'role_id' => '', 'pending_role_id' => '', 'username' => ''));
        $this->setControllerProperty($controller, 'members', array(
            array('username' => 'first', 'group_id' => '9', 'role_id' => '4', 'pending_role_id' => '8'),
        ));

        try {
            $controller->doImport();
            $this->fail('Expected the member write to be reached.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Member write reached.', $exception->getMessage());
        }

        $this->assertSame(4, $saved['role_id']);
        $this->assertSame(8, $saved['pending_role_id']);
        $this->assertArrayNotHasKey('group_id', $saved);
    }

    /**
     * Save the pending role given for a member who is not pending when it is
     * a role the importer may assign, and leave it out when it is not.
     *
     * @dataProvider recordedPendingRoleProvider
     * @param string $pendingRoleId
     * @param int $expected
     * @return void
     */
    public function testPendingRoleOfAMemberWhoIsNotPending($pendingRoleId, $expected)
    {
        $saved = null;
        $member = $this->getMockBuilder(stdClass::class)->addMethods(array('set', 'save'))->getMock();
        $member->method('set')->willReturnCallback(function ($data) use (&$saved, $member) {
            $saved = $data;

            return $member;
        });
        $member->method('save')->willThrowException(new \RuntimeException('Member write reached.'));

        $this->mockImportRequest(array(
            5 => $this->makeRole(5, 'n'),
            8 => $this->makeGrantingRole(8, 'n', array('1:can_access_cp')),
            9 => $this->makeGrantingRole(9, 'n', array('1:can_access_cp', '1:can_admin_design')),
        ), $member);
        $this->mockSuperAdmin(false);
        $this->mockImporter(array('1:can_access_cp'));

        $controller = $this->makeController();
        $controller->localize = (object) array('now' => 1);
        $this->setControllerProperty($controller, 'default_fields', array('group_id' => '', 'role_id' => '', 'pending_role_id' => '', 'username' => ''));
        $this->setControllerProperty($controller, 'members', array(
            array('username' => 'first', 'role_id' => '5', 'pending_role_id' => $pendingRoleId),
        ));

        try {
            $controller->doImport();
            $this->fail('Expected the member write to be reached.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Member write reached.', $exception->getMessage());
        }

        $this->assertSame(5, $saved['role_id']);
        $this->assertSame($expected, $saved['pending_role_id']);
    }

    public function recordedPendingRoleProvider()
    {
        return array(
            'role the importer may assign' => array('8', 8),
            'role beyond the importer' => array('9', 0),
            'role that does not exist' => array('77', 0),
            'no role' => array('0', 0),
        );
    }

    /**
     * Leave out permissions for a channel or template group that has been
     * removed when comparing a role with the importer.
     *
     * @return void
     */
    public function testPermissionsForRemovedRecordsAreNotCompared()
    {
        $this->mockRoleAndRecords($this->makeGrantingRole(8, 'n', array(
            '1:can_access_cp',
            '1:can_edit_other_entries_channel_id_7',
            '1:can_edit_templates_template_group_id_4',
        )), array(3), array(2));
        $this->mockSuperAdmin(false);
        $this->mockImporter(array('1:can_access_cp'));

        $this->assertSame(8, $this->invokeAuthorizedRole(8)->getId());
    }

    /**
     * Still compare permissions for a channel that exists.
     *
     * @return void
     */
    public function testPermissionsForExistingRecordsAreCompared()
    {
        $this->mockRoleAndRecords($this->makeGrantingRole(8, 'n', array(
            '1:can_access_cp',
            '1:can_edit_other_entries_channel_id_3',
        )), array(3), array(2));
        $this->mockSuperAdmin(false);
        $this->mockImporter(array('1:can_access_cp'));

        $this->expectException(UtilitiesShowErrorException::class);
        $this->expectExceptionCode(403);

        $this->invokeAuthorizedRole(8);
    }

    /**
     * Keep a pending role only for a member whose role is the pending role.
     *
     * @dataProvider pendingRoleProvider
     * @param mixed $roleId
     * @param mixed $pendingRoleId
     * @param int $expected
     * @return void
     */
    public function testPendingRoleAppliesToPendingMembersOnly($roleId, $pendingRoleId, $expected)
    {
        $method = new ReflectionMethod(MemberImport::class, 'getPendingRoleId');
        \TestReflectionHelper::makeAccessible($method);

        $this->assertSame($expected, $method->invoke($this->makeController(), $roleId, $pendingRoleId));
    }

    public function pendingRoleProvider()
    {
        return array(
            array(4, 8, 8),
            array('4', ' 8 ', 8),
            array(5, 8, 0),
            array(1, '8', 0),
            array(4, 0, 0),
            array(4, '000', 0),
            array(4, ' 0 ', 0),
            array(4, '', 0),
            array(4, array(8), 0),
        );
    }

    /**
     * Check assigned member IDs before writing imported members.
     *
     * @return void
     */
    public function testExistingMemberIdsAreCheckedBeforeImportWrites()
    {
        $role = $this->getMockBuilder(stdClass::class)->addMethods(array('getId'))->getMock();
        $role->is_locked = 'n';
        $role->method('getId')->willReturn(5);

        $existingMember = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('set', 'save'))
            ->getMock();
        $existingMember->method('set')->willReturnSelf();
        $existingMember->method('save')->willThrowException(new \RuntimeException('Member write reached.'));

        $model = $this->getMockBuilder(stdClass::class)->addMethods(array('get', 'make'))->getMock();
        $model->method('get')->willReturnCallback(function ($modelName) use ($role, $existingMember) {
            $query = $this->getMockBuilder(stdClass::class)->addMethods(array('first'))->getMock();
            $query->method('first')->willReturn($modelName === 'Role' ? $role : $existingMember);

            return $query;
        });
        $model->expects($this->never())->method('make');
        ee()->setMock('Model', $model);

        $input = $this->getMockBuilder(stdClass::class)->addMethods(array('post'))->getMock();
        $input->method('post')->willReturnCallback(function ($key) {
            return $key === 'role_id' ? 5 : null;
        });
        ee()->setMock('input', $input);

        $encrypt = $this->getMockBuilder(stdClass::class)->addMethods(array('generateKey'))->getMock();
        $encrypt->method('generateKey')->willReturn('member-import-test-key');
        ee()->setMock('Encrypt', $encrypt);

        $session = $this->getMockBuilder(stdClass::class)->addMethods(array('userdata'))->getMock();
        $session->method('userdata')->with('member_id')->willReturn(99);
        ee()->setMock('session', $session);
        $this->mockSuperAdmin(false);

        $controller = $this->makeController();
        $controller->localize = (object) array('now' => 1);
        $this->setControllerProperty($controller, 'default_fields', array('username' => '', 'role_id' => ''));
        $this->setControllerProperty($controller, 'members', array(
            array('username' => 'first', 'role_id' => 5),
            array('member_id' => 7, 'username' => 'imported', 'role_id' => 5),
        ));

        $this->expectException(UtilitiesShowErrorException::class);
        $this->expectExceptionCode(422);

        $controller->doImport();
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
        \TestReflectionHelper::makeAccessible($property);
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
        \TestReflectionHelper::makeAccessible($method);

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
     * Provide a role, and the channels and template groups that exist.
     *
     * @param object $role
     * @param array $channelIds
     * @param array $templateGroupIds
     * @return void
     */
    private function mockRoleAndRecords($role, array $channelIds, array $templateGroupIds)
    {
        $roleQuery = $this->getMockBuilder(stdClass::class)->addMethods(array('first'))->getMock();
        $roleQuery->method('first')->willReturn($role);

        $records = array('Channel' => $channelIds, 'TemplateGroup' => $templateGroupIds);
        $queries = array('Role' => $roleQuery);

        foreach ($records as $name => $ids) {
            $query = $this->getMockBuilder(stdClass::class)->addMethods(array('fields', 'all'))->getMock();
            $query->method('fields')->willReturnSelf();
            $query->method('all')->willReturn($this->makeIds($ids));
            $queries[$name] = $query;
        }

        $model = $this->getMockBuilder(stdClass::class)->addMethods(array('get'))->getMock();
        $model->method('get')->willReturnCallback(function ($name) use ($queries) {
            return $queries[$name];
        });

        ee()->setMock('Model', $model);
    }

    /**
     * Create a role with an ID and lock state.
     *
     * @param int $id
     * @param string $isLocked
     * @return object
     */
    private function makeRole($id, $isLocked)
    {
        $role = $this->getMockBuilder(stdClass::class)->addMethods(array('getId'))->getMock();
        $role->is_locked = $isLocked;
        $role->method('getId')->willReturn($id);

        return $role;
    }

    /**
     * Create a role with permissions and assignments.
     *
     * @param int $id
     * @param string $isLocked
     * @param array $permissions Entries written as "site_id:permission"
     * @param array $assignments Assigned IDs keyed by relationship name
     * @return object
     */
    private function makeGrantingRole($id, $isLocked, array $permissions = array(), array $assignments = array())
    {
        $role = $this->makeRole($id, $isLocked);
        $role->Permissions = array();

        foreach ($permissions as $permission) {
            list($siteId, $name) = explode(':', $permission, 2);
            $role->Permissions[] = (object) array('site_id' => (int) $siteId, 'permission' => $name);
        }

        foreach (array_keys(self::ASSIGNMENTS) as $relationship) {
            $role->{$relationship} = $this->makeIds(isset($assignments[$relationship]) ? $assignments[$relationship] : array());
        }

        return $role;
    }

    /**
     * Create a collection stand-in that reports the given IDs.
     *
     * @param array $ids
     * @return object
     */
    private function makeIds(array $ids)
    {
        $collection = $this->getMockBuilder(stdClass::class)->addMethods(array('getIds'))->getMock();
        $collection->method('getIds')->willReturn($ids);

        return $collection;
    }

    /**
     * Provide the importing member with permissions and assignments.
     *
     * @param array $permissions Entries written as "site_id:permission"
     * @param array $assignments Assigned IDs keyed by relationship name
     * @return void
     */
    private function mockImporter(array $permissions, array $assignments = array())
    {
        $member = $this->getMockBuilder(stdClass::class)
            ->addMethods(array_merge(array('getAllRoles'), array_values(self::ASSIGNMENTS)))
            ->getMock();
        $member->method('getAllRoles')->willReturn(array($this->makeGrantingRole(20, 'n', $permissions)));

        foreach (self::ASSIGNMENTS as $relationship => $getter) {
            $member->method($getter)->willReturn(
                $this->makeIds(isset($assignments[$relationship]) ? $assignments[$relationship] : array())
            );
        }

        $session = $this->getMockBuilder(stdClass::class)->addMethods(array('getMember', 'userdata'))->getMock();
        $session->method('getMember')->willReturn($member);
        $session->method('userdata')->willReturn(99);
        ee()->setMock('session', $session);
    }

    /**
     * Provide the services an import run uses: roles by ID, the member to save, and the form values.
     *
     * @param array $rolesById
     * @param object $member
     * @return void
     */
    private function mockImportRequest(array $rolesById, $member)
    {
        $model = $this->getMockBuilder(stdClass::class)->addMethods(array('get', 'make'))->getMock();
        $model->method('get')->willReturnCallback(function ($modelName, $id) use ($rolesById) {
            $query = $this->getMockBuilder(stdClass::class)->addMethods(array('first'))->getMock();
            $query->method('first')->willReturn(isset($rolesById[(int) $id]) ? $rolesById[(int) $id] : null);

            return $query;
        });
        $model->method('make')->willReturn($member);
        ee()->setMock('Model', $model);

        $input = $this->getMockBuilder(stdClass::class)->addMethods(array('post'))->getMock();
        $input->method('post')->willReturnCallback(function ($key) {
            return $key === 'role_id' ? 5 : null;
        });
        ee()->setMock('input', $input);

        $encrypt = $this->getMockBuilder(stdClass::class)->addMethods(array('generateKey'))->getMock();
        $encrypt->method('generateKey')->willReturn('member-import-test-key');
        ee()->setMock('Encrypt', $encrypt);
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

        // Role 5 is the default member role in these tests.
        $config = $this->getMockBuilder(stdClass::class)->addMethods(array('item'))->getMock();
        $config->method('item')->willReturnCallback(function ($key) {
            return $key === 'default_primary_role' ? 5 : null;
        });
        ee()->setMock('config', $config);
    }
}

}
