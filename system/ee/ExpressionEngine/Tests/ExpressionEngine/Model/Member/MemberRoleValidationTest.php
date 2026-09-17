<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Model\Member;

use ExpressionEngine\Model\Member\Member;
use ExpressionEngine\Service\Model\Association\ToMany;
use ExpressionEngine\Service\Model\Collection;
use PHPUnit\Framework\TestCase;
use stdClass;

class MemberRoleValidationTest extends TestCase
{
    /**
     * Clear service mocks between tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    /**
     * Validate direct and grouped role assignments consistently.
     *
     * @dataProvider roleAssignmentProvider
     * @param bool $isSuperAdmin
     * @param int[] $availableRoleIds
     * @param int[]|null $directRoleIds Null indicates an unloaded association.
     * @param int[]|null $groupRoleIds Null indicates an unloaded association.
     * @param bool $isNew
     * @param bool|string $expected
     * @return void
     */
    public function testRoleAssignmentsUseAvailableRoles(
        $isSuperAdmin,
        array $availableRoleIds,
        $directRoleIds,
        $groupRoleIds,
        $isNew,
        $expected
    ) {
        $query = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('filter', 'all'))
            ->getMock();
        $query->method('all')->willReturn($this->roleCollection($availableRoleIds));

        if ($isSuperAdmin) {
            $query->expects($this->never())->method('filter');
        } else {
            $query->expects($this->once())->method('filter')->with('is_locked', 'n')->willReturnSelf();
        }

        $model = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('get'))
            ->getMock();
        $model->method('get')->with('Role')->willReturn($query);

        $roleGroup = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('getId'))
            ->getMock();
        $roleGroup->method('getId')->willReturn(3);
        // A lazy lookup without a member ID can return unrelated, unavailable roles.
        $roleGroup->Roles = $this->roleCollection($groupRoleIds === null ? array(99) : $groupRoleIds);

        $directRoles = $this->getMockBuilder(ToMany::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array('isLoaded'))
            ->getMock();
        $directRoles->method('isLoaded')->willReturn($directRoleIds !== null);

        $roleGroups = $this->getMockBuilder(ToMany::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array('isLoaded'))
            ->getMock();
        $roleGroups->method('isLoaded')->willReturn($groupRoleIds !== null);

        $member = $this->getMockBuilder(Member::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array('getModelFacade', '__get', 'isNew', 'getAssociation'))
            ->getMock();
        $member->method('getModelFacade')->willReturn($model);
        $member->method('isNew')->willReturn($isNew);
        $member->method('getAssociation')->willReturnMap(array(
            array('Roles', $directRoles),
            array('RoleGroups', $roleGroups),
        ));
        $member->method('__get')->willReturnMap(array(
            array('Roles', $this->roleCollection($directRoleIds === null ? array(99) : $directRoleIds)),
            array('RoleGroups', new Collection(array($roleGroup))),
        ));

        $permission = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('isSuperAdmin'))
            ->getMock();
        $permission->method('isSuperAdmin')->willReturn($isSuperAdmin);
        ee()->setMock('Permission', $permission);

        $this->assertSame($expected, $member->validateRoles('role_id', 5));
    }

    /**
     * Provide role assignment combinations.
     *
     * @return array
     */
    public static function roleAssignmentProvider()
    {
        return array(
            'group includes unavailable role' => array(false, array(5), array(), array(6), false, 'invalid_role_id'),
            'direct role is unavailable' => array(false, array(5), array(6), array(), false, 'invalid_role_id'),
            'all roles are available' => array(false, array(5, 6), array(6), array(6), false, true),
            'empty group' => array(false, array(5), array(), array(0), false, true),
            'unrestricted assignment' => array(true, array(5, 6), array(6), array(6), false, true),
            'stored roles require validation' => array(false, array(5), null, array(), false, 'invalid_role_id'),
            'stored groups require validation' => array(false, array(5), array(), null, false, 'invalid_role_id'),
            'new member without extra roles' => array(false, array(5), null, null, true, true),
            'new member with allowed direct role' => array(false, array(5, 6), array(6), null, true, true),
            'new member with unavailable role' => array(false, array(5), array(6), null, true, 'invalid_role_id'),
            'new member with allowed group' => array(false, array(5, 6), null, array(6), true, true),
            'new member with unavailable group' => array(false, array(5), null, array(6), true, 'invalid_role_id'),
            'new member with unavailable primary role' => array(false, array(6), null, null, true, 'invalid_role_id'),
        );
    }

    /**
     * Create role records for a model collection.
     *
     * @param int[] $roleIds
     * @return Collection
     */
    private function roleCollection(array $roleIds)
    {
        return new Collection(array_map(function ($roleId) {
            return (object) array('role_id' => $roleId);
        }, $roleIds));
    }
}
