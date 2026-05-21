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

use ExpressionEngine\Model\Member\Member as MemberModel;
use ExpressionEngine\Model\Role\RoleSetting;
use ExpressionEngine\Service\Model\Collection;
use PHPUnit\Framework\TestCase;

class MemberRoleSettingsForSiteTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
        ee()->config->resetConfig();
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
        ee()->config->resetConfig();
    }

    public function testPrefersPrimaryRoleWhenItQualifiesForSite()
    {
        $primarySetting = $this->createRoleSettingMock();
        $secondarySetting = $this->createRoleSettingMock();

        $member = new MemberRoleSettingsForSiteMemberStub();
        $member->setAllRoles([
            $this->createRoleStub(7, [2 => $primarySetting]),
            $this->createRoleStub(3, [2 => $secondarySetting]),
        ]);
        $this->setPrimaryRoleId($member, 7);

        ee()->setMock('Permission', new MemberRoleSettingsForSitePermissionStub([3, 7]));

        $result = $member->getRoleSettingsForSite(2, true);

        $this->assertSame($primarySetting, $result);
    }

    public function testFallsBackToLowestRoleIdWhenPrimaryHasNoSiteSettings()
    {
        $roleTwoSetting = $this->createRoleSettingMock();
        $roleFiveSetting = $this->createRoleSettingMock();

        $member = new MemberRoleSettingsForSiteMemberStub();
        $member->setAllRoles([
            $this->createRoleStub(10, [1 => $this->createRoleSettingMock()]),
            $this->createRoleStub(5, [2 => $roleFiveSetting]),
            $this->createRoleStub(2, [2 => $roleTwoSetting]),
        ]);
        $this->setPrimaryRoleId($member, 10);

        ee()->setMock('Permission', new MemberRoleSettingsForSitePermissionStub([2, 5, 10]));

        $result = $member->getRoleSettingsForSite(2, true);

        $this->assertSame($roleTwoSetting, $result);
    }

    public function testReturnsNullWhenNoCpAccessRoleCandidatesExist()
    {
        $member = new MemberRoleSettingsForSiteMemberStub();
        $member->setAllRoles([
            $this->createRoleStub(2, [2 => $this->createRoleSettingMock()]),
            $this->createRoleStub(5, [2 => $this->createRoleSettingMock()]),
        ]);
        $this->setPrimaryRoleId($member, 2);

        ee()->setMock('Permission', new MemberRoleSettingsForSitePermissionStub([9]));

        $result = $member->getRoleSettingsForSite(2, true);

        $this->assertNull($result);
    }

    public function testCanResolveUsingPrimaryRoleWithoutCpAccessRequirement()
    {
        $primarySetting = $this->createRoleSettingMock();
        $secondarySetting = $this->createRoleSettingMock();

        $member = new MemberRoleSettingsForSiteMemberStub();
        $member->setAllRoles([
            $this->createRoleStub(10, [2 => $primarySetting]),
            $this->createRoleStub(2, [2 => $secondarySetting]),
        ]);
        $this->setPrimaryRoleId($member, 10);

        ee()->setMock('Permission', new MemberRoleSettingsForSitePermissionStub([2]));

        $result = $member->getRoleSettingsForSite(2, false);

        $this->assertSame($primarySetting, $result);
    }

    public function testReturnsNullWhenNoAssignedRolesHaveSiteSettings()
    {
        $member = new MemberRoleSettingsForSiteMemberStub();
        $member->setAllRoles([
            $this->createRoleStub(2, [1 => $this->createRoleSettingMock()]),
            $this->createRoleStub(5, [1 => $this->createRoleSettingMock()]),
        ]);
        $this->setPrimaryRoleId($member, 2);

        ee()->setMock('Permission', new MemberRoleSettingsForSitePermissionStub([2, 5]));

        $result = $member->getRoleSettingsForSite(2, true);

        $this->assertNull($result);
    }

    private function createRoleSettingMock(): RoleSetting
    {
        return $this->getMockBuilder(RoleSetting::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function createRoleStub(int $roleId, array $settingsBySite): object
    {
        return (object) [
            'role_id' => $roleId,
            'RoleSettings' => new MemberRoleSettingsForSiteRoleSettingsCollectionStub($settingsBySite),
        ];
    }

    private function setPrimaryRoleId(MemberModel $member, int $roleId): void
    {
        $property = new \ReflectionProperty(MemberModel::class, 'role_id');
        $property->setAccessible(true);
        $property->setValue($member, $roleId);
    }
}

class MemberRoleSettingsForSiteMemberStub extends MemberModel
{
    private $roles = [];

    public function setAllRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function getAllRoles($cache = true)
    {
        return new Collection($this->roles);
    }
}

class MemberRoleSettingsForSitePermissionStub
{
    private $roleIds;

    public function __construct(array $roleIds)
    {
        $this->roleIds = $roleIds;
    }

    public function rolesThatCan($permission, $site_id = null)
    {
        return $this->roleIds;
    }
}

class MemberRoleSettingsForSiteRoleSettingsCollectionStub
{
    private $settingsBySite;

    public function __construct(array $settingsBySite)
    {
        $this->settingsBySite = $settingsBySite;
    }

    public function filter($field, $value)
    {
        if ($field !== 'site_id') {
            return new MemberRoleSettingsForSiteRoleSettingsFilterResultStub(null);
        }

        return new MemberRoleSettingsForSiteRoleSettingsFilterResultStub(
            $this->settingsBySite[$value] ?? null
        );
    }
}

class MemberRoleSettingsForSiteRoleSettingsFilterResultStub
{
    private $setting;

    public function __construct($setting)
    {
        $this->setting = $setting;
    }

    public function first()
    {
        return $this->setting;
    }
}

// EOF
