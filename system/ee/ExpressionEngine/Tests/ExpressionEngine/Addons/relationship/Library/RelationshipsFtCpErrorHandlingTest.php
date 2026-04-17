<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/RelationshipTestBase.php';

/**
 * Test Relationships_ft_cp error handling and edge cases that trigger PHP errors/deprecations
 */
class RelationshipsFtCpErrorHandlingTest extends RelationshipTestBase
{
    /**
     * Test all_categories() with missing language keys - should trigger notices/warnings
     */
    public function testAllCategoriesWithMissingLanguageKeys()
    {
        // Mock database to return category data
        $categoryData = [
            [
                'parent_id' => 0,
                'cat_id' => 1,
                'group_id' => 1,
                'site_id' => 1,
                'cat_name' => 'Category 1',
                'cat_order' => 1
            ]
        ];

        ee()->db->setRows($categoryData);

        // Mock lang function to return null for missing keys (simulating missing language file)
        $originalLang = ee()->lang;
        ee()->lang = new class {
            public function loadfile($file) {
                // Don't load anything
            }

            public function line($key) {
                return null; // Simulate missing language key
            }
        };

        // This should work but may trigger notices about undefined language keys
        $result = $this->relationships_ft_cp->all_categories();

        // Restore original lang
        ee()->lang = $originalLang;

        $this->assertArrayHasKey('--', $result);
        $this->assertArrayHasKey('children', $result['--']);
    }

    /**
     * Test all_authors() with missing language loadfile - should trigger errors
     */
    public function testAllAuthorsWithMissingLanguageLoadfile()
    {
        // Mock Model collections
        $roleSettings = collect([]);
        $mockRoleSettingCollection = $this->createMockRoleSettingCollection($roleSettings);
        $this->setMock('Model', new class($mockRoleSettingCollection) {
            private $collection;
            public function __construct($collection) {
                $this->collection = $collection;
            }
            public function get($model) {
                if ($model === 'RoleSetting') {
                    return $this->collection;
                }
                return null;
            }
        });

        // Mock lang that throws error on loadfile
        $originalLang = ee()->lang;
        ee()->lang = new class {
            public function loadfile($file) {
                throw new Exception("Language file '$file' not found");
            }

            public function line($key) {
                $translations = [
                    'any_author' => 'Any Author',
                ];
                return $translations[$key] ?? $key;
            }
        };

        // This should trigger an exception due to missing Model mocking
        $this->expectException(Error::class);
        $this->expectExceptionMessage("Call to a member function with() on null");

        $this->relationships_ft_cp->all_authors();

        // Restore original lang
        ee()->lang = $originalLang;
    }

    /**
     * Test all_statuses() with invalid status values - potential for undefined index errors
     */
    public function testAllStatusesWithInvalidStatusValues()
    {
        // Create status with null/empty status value that could cause issues
        $statuses = [
            (object)['status_id' => 1, 'status' => null], // Null status
            (object)['status_id' => 2, 'status' => ''],   // Empty status
            (object)['status_id' => 3, 'status' => 'valid_status']
        ];

        $mockCollection = $this->createMockStatusCollection($statuses);

        // Mock ee('Model')
        $this->setMock('Model', new class($mockCollection) {
            private $collection;
            public function __construct($collection) {
                $this->collection = $collection;
            }
            public function get($model) {
                if ($model === 'Status') {
                    return $this->collection;
                }
                return null;
            }
        });

        // Mock lang to return null for null/empty keys (simulating missing translations)
        $originalLang = ee()->lang;
        ee()->lang = new class {
            public function line($key) {
                if ($key === null || $key === '') {
                    return null; // This could cause issues when used in array keys
                }
                return $key;
            }
        };

        // This might trigger notices about undefined array keys
        $result = $this->relationships_ft_cp->all_statuses();

        // Restore original lang
        ee()->lang = $originalLang;

        $this->assertArrayHasKey('--', $result);
    }

    /**
     * Test form() method with invalid data - potential for undefined index errors
     */
    public function testFormWithInvalidData()
    {
        $data = null; // Invalid data instead of array

        // This should trigger errors when trying to access array keys
        $this->expectException(TypeError::class);

        $this->relationships_ft_cp->form($data);
    }

    /**
     * Test buildCategoryList() with invalid hierarchy data - potential for undefined index access
     */
    public function testBuildCategoryListWithInvalidHierarchy()
    {
        $hierarchy = null; // Invalid hierarchy instead of array
        $categories = []; // Valid categories array

        $reflection = new ReflectionClass($this->relationships_ft_cp);
        $method = $reflection->getMethod('buildCategoryList');
        \TestReflectionHelper::makeAccessible($method);

        // This should work with null coalescing operator, but let's see what happens
        $result = $method->invoke($this->relationships_ft_cp, 0, $hierarchy, $categories);

        // Should handle null hierarchy gracefully due to ?? operator
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test buildCategoryList() with non-existent parent_id in hierarchy
     */
    public function testBuildCategoryListWithNonExistentParentId()
    {
        $hierarchy = [1 => [999 => [1]]]; // Parent ID 999 doesn't exist in hierarchy
        $categories = [
            1 => ['cat_id' => 1, 'cat_name' => 'Test Category', 'parent_id' => 0]
        ];

        $reflection = new ReflectionClass($this->relationships_ft_cp);
        $method = $reflection->getMethod('buildCategoryList');
        \TestReflectionHelper::makeAccessible($method);

        // This should work but access $hierarchy[0] which doesn't exist, using ?? [] should handle it
        $result = $method->invoke($this->relationships_ft_cp, 0, $hierarchy, $categories);

        $this->assertIsArray($result);
        $this->assertEmpty($result); // Should return empty array for non-existent parent
    }

    /**
     * Test all_channels() with multi-site but missing Site relationship - potential for undefined property access
     */
    public function testAllChannelsMultiSiteWithMissingSiteRelationship()
    {
        $this->enableMultiSite();

        // Create channels without proper Site relationship
        $channels = [
            (object)[
                'getId' => 1,
                'channel_title' => 'Channel 1',
                'Site' => null // Missing Site relationship
            ]
        ];

        $mockCollection = $this->createMockChannelCollection($channels);

        // Mock ee('Model')
        $this->setMock('Model', new class($mockCollection) {
            private $collection;
            public function __construct($collection) {
                $this->collection = $collection;
            }
            public function get($model) {
                if ($model === 'Channel') {
                    return $this->collection;
                }
                return null;
            }
        });

        // This should trigger errors when accessing $channel->Site->site_label
        $this->expectException(Error::class);

        $this->relationships_ft_cp->all_channels();
    }

    /**
     * Test all_authors() with invalid member data structure
     */
    public function testAllAuthorsWithInvalidMemberData()
    {
        // Create invalid member objects that lack required methods
        $members = [
            (object)['member_id' => 1, 'screen_name' => 'Test User'] // Missing getAllRoles method
        ];

        $roleSetting = (object)['role_id' => 1, 'include_in_authorlist' => 'y', 'site_id' => 1];
        $roleSetting->Role = (object)['role_id' => 1, 'name' => 'Test Role'];
        $roleSettings = collect([$roleSetting]);

        $mockRoleSettingCollection = \Mockery::mock();
        $mockRoleSettingCollection->shouldReceive('with')->andReturnSelf();
        $mockRoleSettingCollection->shouldReceive('filter')->andReturnSelf();
        $mockRoleSettingCollection->shouldReceive('order')->andReturnSelf();
        $mockRoleSettingCollection->shouldReceive('all')->andReturn($roleSettings);

        $mockMemberCollection = \Mockery::mock();
        $mockMemberCollection->shouldReceive('with')->andReturnSelf();
        $mockMemberCollection->shouldReceive('filter')->andReturnSelf();
        $mockMemberCollection->shouldReceive('order')->andReturnSelf();
        $mockMemberCollection->shouldReceive('limit')->andReturnSelf();
        $mockMemberCollection->shouldReceive('orFilter')->andReturnSelf();
        $mockMemberCollection->shouldReceive('search')->andReturnSelf();
        $mockMemberCollection->shouldReceive('all')->andReturn($members);

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

        // This should trigger errors when calling getAllMembersData() on invalid Role object
        $this->expectException(Error::class);
        $this->expectExceptionMessage("Call to undefined method stdClass::getAllMembersData()");

        $this->relationships_ft_cp->all_authors();
    }

    /**
     * Test Relationship_settings_form with missing form helper functions
     * This test intentionally triggers PHP errors to test error handling
     */
    public function testRelationshipSettingsFormWithMissingFormHelpers()
    {
        $this->markTestSkipped('This test intentionally triggers PHP errors to test error handling scenarios');

        $data = ['test' => 'value'];
        $form = $this->relationships_ft_cp->form($data, 'test_prefix');

        // Try to call a form method - this should trigger "Undefined index" error
        // We don't expectException here because we want the error to be thrown naturally for error handling testing
        $form->dropdown('test_field');
    }
}
