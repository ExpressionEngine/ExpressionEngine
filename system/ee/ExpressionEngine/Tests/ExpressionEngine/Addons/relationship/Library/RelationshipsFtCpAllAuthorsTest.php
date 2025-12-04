<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/RelationshipTestBase.php';

use Mockery as m;

 

/**
 * Test Relationships_ft_cp::all_authors() method
 * @group complex
 */
class RelationshipsFtCpAllAuthorsTest extends RelationshipTestBase
{
    /**
     * Test all_authors() returns cached result on second call
     */
    public function testAllAuthorsReturnsCachedResult()
    {
        $mockRoleSettings = $this->createMockRoleSettings();
        $mockMembers = $this->createMockMembers();

        $this->mockAuthorQueries($mockRoleSettings, $mockMembers);

        // First call - should query database
        $result1 = $this->relationships_ft_cp->all_authors();

        // Second call - should use cached result
        $result2 = $this->relationships_ft_cp->all_authors();

        // Results should be identical (cached)
        $this->assertEquals($result1, $result2);
        $this->assertArrayHasKey('--', $result1);
        $this->assertEquals('any_author', $result1['--']['name']);
    }

    /**
     * Test all_authors() without search parameter
     * @group complex
     */
    public function testAllAuthorsWithoutSearch()
    {
        $this->disableMultiSite();

        $mockRoleSettings = $this->createMockRoleSettings();
        $mockMembers = $this->createMockMembers();

        $this->mockAuthorQueries($mockRoleSettings, $mockMembers);

        $result = $this->relationships_ft_cp->all_authors();

        // Verify structure
        $this->assertArrayHasKey('--', $result);
        $this->assertEquals('any_author', $result['--']['name']);
        $this->assertArrayHasKey('children', $result['--']);

        // Should have one role group with one member
        $children = $result['--']['children'];
        $this->assertArrayHasKey('g_1', $children); // Admin role
        $this->assertEquals('Admin', $children['g_1']['name']);
        $this->assertArrayHasKey('children', $children['g_1']);
        $this->assertArrayHasKey('m_1', $children['g_1']['children']); // John Doe
        $this->assertEquals('John Doe', $children['g_1']['children']['m_1']);
    }

    /**
     * Test all_authors() with search parameter
     * @group complex
     */
    public function testAllAuthorsWithSearch()
    {
        $this->disableMultiSite();

        $mockRoleSettings = $this->createMockRoleSettings();
        $mockMembers = $this->createMockMembers();

        // Mock search functionality - create a mock that filters by search
        $this->mockAuthorQueriesWithSearch($mockRoleSettings, $mockMembers, 'John');

        $result = $this->relationships_ft_cp->all_authors('John');

        // Verify structure
        $this->assertArrayHasKey('--', $result);
        $this->assertEquals('any_author', $result['--']['name']);
        $this->assertArrayHasKey('children', $result['--']);

        // Should still have the role group with the matching member
        $children = $result['--']['children'];
        $this->assertArrayHasKey('g_1', $children);
        $this->assertArrayHasKey('m_1', $children['g_1']['children']);
    }

    /**
     * Test all_authors() with role that has no members (should be filtered out)
     * @group complex
     */
    public function testAllAuthorsFiltersEmptyRoleGroups()
    {
        $this->disableMultiSite();

        // Create role settings with one role that has members and one that doesn't
        $mockRoleSettings = $this->createMockRoleSettingsWithEmpty();

        $mockMembers = $this->createMockMembers(); // Only for role 1

        $this->mockAuthorQueries($mockRoleSettings, $mockMembers);

        $result = $this->relationships_ft_cp->all_authors();

        // Verify structure
        $this->assertArrayHasKey('--', $result);
        $this->assertArrayHasKey('children', $result['--']);

        $children = $result['--']['children'];

        // Should only have the role with members (g_1), empty role (g_2) should be filtered out
        $this->assertArrayHasKey('g_1', $children);
        $this->assertArrayNotHasKey('g_2', $children);
    }

    /**
     * Test all_authors() in multi-site mode
     */
    public function testAllAuthorsMultiSiteMode()
    {
        $this->enableMultiSite();

        $mockRoleSettings = [
            $this->createMockRoleSetting(1, 'Admin'), // Site 1
        ];
        $mockMembers = $this->createMockMembers();

        // In multi-site mode, should NOT filter by site_id
        $this->mockAuthorQueriesMultiSite($mockRoleSettings, $mockMembers);

        $result = $this->relationships_ft_cp->all_authors();

        // Verify structure
        $this->assertArrayHasKey('--', $result);
        $this->assertEquals('any_author', $result['--']['name']);
        $this->assertArrayHasKey('children', $result['--']);

        // Should have the role group
        $children = $result['--']['children'];
        $this->assertArrayHasKey('g_1', $children);
        $this->assertEquals('Admin', $children['g_1']['name']);
    }

    /**
     * Test all_authors() with members having multiple roles
     */
    public function testAllAuthorsWithMembersInMultipleRoles()
    {
        $this->disableMultiSite();

        // Create two roles
        $mockRoleSettings = [
            $this->createMockRoleSetting(1, 'Admin'),
            $this->createMockRoleSetting(2, 'Editor'),
        ];

        // Create member that belongs to both roles
        $mockMembers = [
            $this->createMockMember(1, 'John Doe', [1, 2]), // Member in both roles
        ];

        $this->mockAuthorQueries($mockRoleSettings, $mockMembers);

        $result = $this->relationships_ft_cp->all_authors();

        // Verify structure
        $this->assertArrayHasKey('--', $result);
        $this->assertArrayHasKey('children', $result['--']);

        $children = $result['--']['children'];

        // Should have both role groups
        $this->assertArrayHasKey('g_1', $children); // Admin role
        $this->assertArrayHasKey('g_2', $children); // Editor role

        // Both roles should have the same member
        $this->assertArrayHasKey('m_1', $children['g_1']['children']);
        $this->assertArrayHasKey('m_1', $children['g_2']['children']);
        $this->assertEquals('John Doe', $children['g_1']['children']['m_1']);
        $this->assertEquals('John Doe', $children['g_2']['children']['m_1']);
    }

    /**
     * Test all_authors() with empty results
     */
    public function testAllAuthorsWithEmptyResults()
    {
        $this->disableMultiSite();

        // No role settings
        $mockRoleSettings = [];
        $mockMembers = [];

        $this->mockAuthorQueries($mockRoleSettings, $mockMembers);

        $result = $this->relationships_ft_cp->all_authors();

        // Should still return basic structure but with empty children
        $this->assertArrayHasKey('--', $result);
        $this->assertEquals('any_author', $result['--']['name']);
        $this->assertArrayHasKey('children', $result['--']);
        $this->assertEmpty($result['--']['children']);
    }

    /**
     * Helper to mock the complex author queries
     */
    private function mockAuthorQueries($mockRoleSettings, $mockMembers)
    {
        // Mock RoleSetting collection using Mockery
        $mockRoleSettingCollection = m::mock();
        $mockRoleSettingCollection->shouldReceive('with')->andReturnSelf();
        $mockRoleSettingCollection->shouldReceive('filter')->andReturnSelf();
        $mockRoleSettingCollection->shouldReceive('order')->andReturnSelf();
        $mockRoleSettingCollection->shouldReceive('all')->andReturn(collect($mockRoleSettings));

        // Mock Member collection using Mockery
        $mockMemberCollection = m::mock();
        $mockMemberCollection->shouldReceive('with')->andReturnSelf();
        $mockMemberCollection->shouldReceive('filter')->andReturnSelf();
        $mockMemberCollection->shouldReceive('order')->andReturnSelf();
        $mockMemberCollection->shouldReceive('limit')->andReturnSelf();
        $mockMemberCollection->shouldReceive('orFilter')->andReturnSelf();
        $mockMemberCollection->shouldReceive('search')->andReturnSelf();
        $mockMemberCollection->shouldReceive('all')->andReturn(collect($mockMembers));

        // Mock ee('Model') using anonymous class like other tests
        $this->setMock('Model', new class($mockRoleSettingCollection, $mockMemberCollection) {
            private $roleSettingCollection;
            private $memberCollection;
            public function __construct($roleSettingCollection, $memberCollection) {
                $this->roleSettingCollection = $roleSettingCollection;
                $this->memberCollection = $memberCollection;
            }
            public function get($model) {
                if ($model === 'RoleSetting') {
                    return $this->roleSettingCollection;
                } elseif ($model === 'Member') {
                    return $this->memberCollection;
                }
                return null;
            }
        });
    }

    /**
     * Helper to mock author queries with search functionality
     */
    private function mockAuthorQueriesWithSearch($mockRoleSettings, $mockMembers, $searchTerm)
    {
        // Mock RoleSetting collection using Mockery
        $mockRoleSettingCollection = m::mock();
        $mockRoleSettingCollection->shouldReceive('with')->andReturnSelf();
        $mockRoleSettingCollection->shouldReceive('filter')->andReturnSelf();
        $mockRoleSettingCollection->shouldReceive('order')->andReturnSelf();
        $mockRoleSettingCollection->shouldReceive('all')->andReturn(collect($mockRoleSettings));

        // Mock Member collection with search that filters results
        $mockMemberCollection = m::mock();
        $mockMemberCollection->shouldReceive('with')->andReturnSelf();
        $mockMemberCollection->shouldReceive('filter')->andReturnSelf();
        $mockMemberCollection->shouldReceive('order')->andReturnSelf();
        $mockMemberCollection->shouldReceive('limit')->andReturnSelf();
        $mockMemberCollection->shouldReceive('orFilter')->andReturnSelf();
        $mockMemberCollection->shouldReceive('search')->andReturnSelf();
        // Simulate search - if searching for 'John' and we have 'John Doe', return members
        if (stripos('John Doe', $searchTerm) !== false) {
            $mockMemberCollection->shouldReceive('all')->andReturn(collect($mockMembers));
        } else {
            $mockMemberCollection->shouldReceive('all')->andReturn(collect([]));
        }

        // Mock ee('Model') using anonymous class like other tests
        $this->setMock('Model', new class($mockRoleSettingCollection, $mockMemberCollection) {
            private $roleSettingCollection;
            private $memberCollection;
            public function __construct($roleSettingCollection, $memberCollection) {
                $this->roleSettingCollection = $roleSettingCollection;
                $this->memberCollection = $memberCollection;
            }
            public function get($model) {
                if ($model === 'RoleSetting') {
                    return $this->roleSettingCollection;
                } elseif ($model === 'Member') {
                    return $this->memberCollection;
                }
                return null;
            }
        });
    }

    /**
     * Helper to create mock role settings
     */
    private function createMockRoleSettings()
    {
        return [
            $this->createMockRoleSetting(1, 'Admin'),
        ];
    }

    /**
     * Helper to create mock role settings with empty roles
     */
    private function createMockRoleSettingsWithEmpty()
    {
        return [
            $this->createMockRoleSetting(1, 'Admin'),
            $this->createMockRoleSetting(2, 'Empty Role', []), // Empty role
        ];
    }

    /**
     * Helper to mock author queries for multi-site mode (no site filtering)
     */
    private function mockAuthorQueriesMultiSite($mockRoleSettings, $mockMembers)
    {
        // Mock RoleSetting collection using Mockery - should NOT filter by site in multi-site mode
        $mockRoleSettingCollection = m::mock();
        $mockRoleSettingCollection->shouldReceive('with')->andReturnSelf();
        $mockRoleSettingCollection->shouldReceive('filter')->andReturnSelf(); // No site filtering in multi-site
        $mockRoleSettingCollection->shouldReceive('order')->andReturnSelf();
        $mockRoleSettingCollection->shouldReceive('all')->andReturn(collect($mockRoleSettings));

        // Mock Member collection using Mockery
        $mockMemberCollection = m::mock();
        $mockMemberCollection->shouldReceive('with')->andReturnSelf();
        $mockMemberCollection->shouldReceive('filter')->andReturnSelf();
        $mockMemberCollection->shouldReceive('order')->andReturnSelf();
        $mockMemberCollection->shouldReceive('limit')->andReturnSelf();
        $mockMemberCollection->shouldReceive('orFilter')->andReturnSelf();
        $mockMemberCollection->shouldReceive('search')->andReturnSelf();
        $mockMemberCollection->shouldReceive('all')->andReturn(collect($mockMembers));

        // Mock ee('Model') using anonymous class like other tests
        $this->setMock('Model', new class($mockRoleSettingCollection, $mockMemberCollection) {
            private $roleSettingCollection;
            private $memberCollection;
            public function __construct($roleSettingCollection, $memberCollection) {
                $this->roleSettingCollection = $roleSettingCollection;
                $this->memberCollection = $memberCollection;
            }
            public function get($model) {
                if ($model === 'RoleSetting') {
                    return $this->roleSettingCollection;
                } elseif ($model === 'Member') {
                    return $this->memberCollection;
                }
                return null;
            }
        });
    }

    /**
     * Helper to create mock members
     */
    private function createMockMembers()
    {
        return [
            $this->createMockMember(1, 'John Doe', [1]),
        ];
    }

    /**
     * Helper to create a mock role setting
     */
    private function createMockRoleSetting($roleId, $roleName, $memberIds = [1])
    {
        $mockRole = new class($roleId, $roleName, $memberIds) {
            public $role_id;
            public $name;
            private $memberIds;

            public function __construct($roleId, $roleName, $memberIds) {
                $this->role_id = $roleId;
                $this->name = $roleName;
                $this->memberIds = $memberIds;
            }

            public function getAllMembersData($field) {
                return $this->memberIds;
            }
        };

        $mockRoleSetting = (object) [
            'site_id' => 1,
            'Role' => $mockRole
        ];

        return $mockRoleSetting;
    }

    /**
     * Helper to create a mock member
     */
    private function createMockMember($memberId, $memberName, $roleIds)
    {
        $roles = [];
        foreach ($roleIds as $roleId) {
            $roles[] = (object) ['role_id' => $roleId];
        }

        $mockMember = new class($memberId, $memberName, $roles) {
            public $member_id;
            public $in_authorlist;
            private $memberName;
            private $roles;

            public function __construct($memberId, $memberName, $roles) {
                $this->member_id = $memberId;
                $this->in_authorlist = 'y';
                $this->memberName = $memberName;
                $this->roles = $roles;
            }

            public function getMemberName() {
                return $this->memberName;
            }

            public function getAllRoles($param) {
                return $this->roles;
            }
        };

        return $mockMember;
    }
}
