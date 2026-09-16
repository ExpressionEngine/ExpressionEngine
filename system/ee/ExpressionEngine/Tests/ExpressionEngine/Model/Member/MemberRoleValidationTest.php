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
     * @param int[] $groupRoleIds
     * @param bool|string $expected
     * @return void
     */
    public function testRoleAssignmentsUseAvailableRoles(
        $isSuperAdmin,
        array $availableRoleIds,
        array $groupRoleIds,
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
        $roleGroup->Roles = $this->roleCollection($groupRoleIds);

        $member = $this->getMockBuilder(Member::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array('getModelFacade', '__get'))
            ->getMock();
        $member->method('getModelFacade')->willReturn($model);
        $member->method('__get')->willReturnMap(array(
            array('Roles', new Collection()),
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
            'group includes unavailable role' => array(false, array(5), array(6), 'invalid_role_id'),
            'all roles are available' => array(false, array(5, 6), array(6), true),
            'empty group' => array(false, array(5), array(0), true),
            'unrestricted assignment' => array(true, array(5, 6), array(6), true),
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
